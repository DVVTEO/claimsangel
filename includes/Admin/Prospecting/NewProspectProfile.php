<?php
/*
Plugin Name: Business CRM Profile Page
Description: Adds a gorgeous business profile page with CRM capabilities (General Notes, Call Logging, Reminder Logging, Key People) to the WordPress backend.
Version: 1.0
Author: Your Name
*/

namespace ClaimsAngel\Admin\Prospecting;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NewProspectProfile {
    /**
     * Singleton instance.
     *
     * @var NewProspectProfile|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @return NewProspectProfile
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'admin_init', [ $this, 'handle_form_submission' ] );
        add_action( 'wp_ajax_bc_add_note_ajax', [ $this, 'ajax_update_general_notes' ] );
    }
    
    
    /**
 * AJAX handler for updating the general notes.
 */
public function ajax_update_general_notes() {

    // Check nonce.
    
    if ( ! isset( $_POST['bc_note_nonce'] ) || ! wp_verify_nonce( $_POST['bc_note_nonce'], 'bc_add_note' ) ) {
        wp_send_json_error( 'Nonce verification failed.' );
    }

    $prospect_id  = isset( $_POST['prospect_id'] ) ? intval( $_POST['prospect_id'] ) : 0;
    $note_content = isset( $_POST['note_content'] ) ? sanitize_textarea_field( $_POST['note_content'] ) : '';

    if ( ! $prospect_id ) {
        wp_send_json_error( 'Invalid prospect ID.' );
    }

    // Update the general_notes meta field.
    if ( update_post_meta( $prospect_id, 'general_notes', $note_content ) ) {
        // Return the new value in the JSON response.
        wp_send_json_success( array( 'new_general_notes' => $note_content ) );
    } else {
        wp_send_json_error( 'Failed to update note.' );
    }
    wp_die();
}

    /**
     * Register the custom admin page.
     */
    public function add_admin_menu() {
        $hook = add_menu_page(
            'Business CRM',
            'Business CRM',
            'manage_options',
            'business-crm',
            [ $this, 'render_admin_page' ],
            'dashicons-businessman',
            6
        );

        // Use the ClaimsAngel AdminAccessController to restrict access.
        \ClaimsAngel\Admin\AdminAccessController::get_instance()->register_admin_page(
            'business-crm',
            ['administrator'], // Allowed roles: adjust as needed.
            [
                'page_title' => 'Business CRM',
                'menu_title' => 'Business CRM',
                'menu_slug'  => 'business-crm'
            ],
            false
        );
    }

    /**
     * Enqueue custom scripts and styles for our admin page.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load assets on our custom page.
        if ( $hook !== 'toplevel_page_business-crm' ) {
            return;
        }

        // Adjust the asset paths if your folder structure differs.
        wp_enqueue_style( 'bc_admin_css', plugin_dir_url( __FILE__ ) . '../css/bc_admin.css' );
        wp_enqueue_script( 'bc_admin_js', plugin_dir_url( __FILE__ ) . '../js/bc_admin.js', array( 'jquery' ), false, true );
        wp_enqueue_script( 'pageajax_js', plugin_dir_url( __FILE__ ) . 'pageajax.js', array( 'jquery' ), false, true );
    }

    /**
     * Render the custom admin page.
     */
    public function render_admin_page() {
        // --- Begin: Query for a random prospect based on user country ---
        $current_user_id   = get_current_user_id();
        $user_country      = get_user_meta( $current_user_id, 'user_country', true );
        $countries         = \ClaimsAngel\Data\Countries::get_instance();
        $user_country_code = $countries->find_country_code( $user_country );

        // Set default fallback values.
        $business_name    = __( 'Business Name', 'claims-angel' );
        $web_address      = __( 'Web Address', 'claims-angel' );
        $phone_number     = __( '(555) 123-4567', 'claims-angel' );
        $linkedin_profile = __( 'LinkedIn Profile', 'claims-angel' );
        $date_created     = current_time( 'timestamp' );
        $general_notes    = '';
        $key_people       = [];
        $prospect_id      = 0;

        if ( $user_country_code ) {
            $args = [
                'post_type'      => 'business',
                'posts_per_page' => 1,
                'orderby'        => 'rand',
                'meta_query'     => [
                    'relation' => 'AND',
                    [
                        'key'     => 'business_status',
                        'value'   => 'prospect',
                        'compare' => '='
                    ],
                    [
                        'relation' => 'OR',
                        [
                            'key'     => 'prospect_prospector',
                            'compare' => 'NOT EXISTS'
                        ],
                        [
                            'key'     => 'prospect_prospector',
                            'value'   => '',
                            'compare' => '='
                        ]
                    ],
                    [
                        'key'     => 'country',
                        'value'   => $user_country_code,
                        'compare' => '='
                    ]
                ]
            ];
            $query = new \WP_Query( $args );
            if ( $query->have_posts() ) {
                $query->the_post();
                $prospect_id      = get_the_ID();
                $business_name    = get_post_meta( $prospect_id, 'business_name', true );
                $web_address      = get_post_meta( $prospect_id, 'web_address', true );
                $phone_number     = get_post_meta( $prospect_id, 'phone_number', true );
                $linkedin_profile = get_post_meta( $prospect_id, 'linkedin_profile', true );
                $date_created     = get_post_meta( $prospect_id, 'date_created', true );
                $general_notes    = get_post_meta( $prospect_id, 'general_notes', true );
                $key_people       = get_post_meta( $prospect_id, 'key_people', true );
                if ( ! is_array( $key_people ) ) {
                    $key_people = [];
                }
                wp_reset_postdata();
            }
        }
        // Format the date as dd/mm/yy.
        $formatted_date = ! empty( $date_created ) ? date( 'd/m/y', $date_created ) : '';
        $days_ago       = ! empty( $date_created ) ? floor(( current_time( 'timestamp' ) - $date_created ) / ( 60 * 60 * 24 )) : 0;
        // --- End: Query for prospect ---

        ?>
        <div class="wrap bc-wrap">
            <!-- The <h1> is now the business name -->
            <h1><?php echo esc_html( $business_name ); ?></h1>

            <!-- Prospect Data Block -->
            <div class="bc-prospect-data">
                <p>
                    <strong><?php esc_html_e( 'Web Address:', 'claims-angel' ); ?></strong>
                    <a href="<?php echo esc_url( $web_address ); ?>" target="_blank"><?php echo esc_html( $web_address ); ?></a>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Phone Number:', 'claims-angel' ); ?></strong>
                    <a href="tel:<?php echo esc_attr( preg_replace( '/\D/', '', $phone_number ) ); ?>">
                        <?php echo esc_html( $phone_number ); ?>
                    </a>
                </p>
                <p>
                    <strong><?php esc_html_e( 'LinkedIn Profile:', 'claims-angel' ); ?></strong>
                    <a href="<?php echo esc_url( $linkedin_profile ); ?>" target="_blank"><?php echo esc_html( $linkedin_profile ); ?></a>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Date Created:', 'claims-angel' ); ?></strong>
                    <?php echo esc_html( $formatted_date ); ?> (<?php echo esc_html( $days_ago ); ?> days ago)
                </p>
            </div>

            <!-- CRM Modules Tabs with Icons -->
            <h2 class="nav-tab-wrapper">
                <a href="#" class="nav-tab nav-tab-active" data-tab="notes">
                    <span class="dashicons dashicons-edit"></span> <?php esc_html_e( 'General Notes', 'claims-angel' ); ?>
                </a>
                <a href="#" class="nav-tab" data-tab="key_people">
                    <span class="dashicons dashicons-admin-users"></span> <?php esc_html_e( 'Key People', 'claims-angel' ); ?>
                </a>
                <a href="#" class="nav-tab" data-tab="calls">
                    <span class="dashicons dashicons-phone"></span> <?php esc_html_e( 'Call Logging', 'claims-angel' ); ?>
                </a>
                <a href="#" class="nav-tab" data-tab="reminders">
                    <span class="dashicons dashicons-calendar"></span> <?php esc_html_e( 'Reminder Logging', 'claims-angel' ); ?>
                </a>
            </h2>

            <!-- Tab Content: General Notes -->
<div class="bc-tab-content" id="notes">
    <h3><?php esc_html_e( 'General Notes', 'claims-angel' ); ?></h3>
    <div id="notes-list">
        <p><?php esc_html_e( 'No notes found. Add a new note below.', 'claims-angel' ); ?></p>
    </div>
    <form id="note-form" method="post" action="">
        <?php wp_nonce_field( 'bc_add_note', 'bc_note_nonce' ); ?>
        <input type="hidden" name="prospect_id" value="<?php echo esc_attr( $prospect_id ); ?>">
<textarea name="note_content" id="note_content" rows="8" style="width:500px; height:200px;" placeholder="<?php esc_attr_e( 'Enter your note here...', 'claims-angel' ); ?>"><?php echo esc_textarea( $general_notes ); ?></textarea>
        <br>
        <button type="submit" class="button button-primary"><?php esc_html_e( 'Add Note', 'claims-angel' ); ?></button>
    </form>
</div>

            <!-- Tab Content: Key People -->
            <div class="bc-tab-content" id="key_people" style="display:none;">
                <h3><?php esc_html_e( 'Key People', 'claims-angel' ); ?></h3>
                <?php if ( ! empty( $prospect_id ) ) : ?>
                    <!-- Form to add a new key person -->
                    <div class="bc-key-people-form">
                        <form method="post" action="">
                            <?php wp_nonce_field( 'bc_add_key_person', 'bc_key_person_nonce' ); ?>
                            <input type="hidden" name="prospect_id" value="<?php echo esc_attr( $prospect_id ); ?>">
                            <input type="text" name="kp_first_name" placeholder="<?php esc_attr_e( 'First Name', 'claims-angel' ); ?>" required>
                            <input type="text" name="kp_last_name" placeholder="<?php esc_attr_e( 'Last Name', 'claims-angel' ); ?>" required>
                            <input type="text" name="kp_role" placeholder="<?php esc_attr_e( 'Role', 'claims-angel' ); ?>" required>
                            <input type="email" name="kp_email" placeholder="<?php esc_attr_e( 'Email Address', 'claims-angel' ); ?>" required>
                            <input type="tel" name="kp_phone" placeholder="<?php esc_attr_e( 'Phone Number', 'claims-angel' ); ?>" required>
                            <button type="submit" class="button"><?php esc_html_e( 'Add Key Person', 'claims-angel' ); ?></button>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- List existing key people as editable inline forms -->
                <?php if ( ! empty( $key_people ) ) : ?>
                    <div class="bc-key-people-list">
                        <?php foreach ( $key_people as $index => $person ) : ?>
                            <form method="post" action="">
                                <?php wp_nonce_field( 'bc_update_key_person', 'bc_update_key_person_nonce' ); ?>
                                <input type="hidden" name="prospect_id" value="<?php echo esc_attr( $prospect_id ); ?>">
                                <input type="hidden" name="kp_index" value="<?php echo esc_attr( $index ); ?>">
                                <input type="text" name="kp_first_name" value="<?php echo esc_attr( $person['first_name'] ); ?>" placeholder="<?php esc_attr_e( 'First Name', 'claims-angel' ); ?>">
                                <input type="text" name="kp_last_name" value="<?php echo esc_attr( $person['last_name'] ); ?>" placeholder="<?php esc_attr_e( 'Last Name', 'claims-angel' ); ?>">
                                <input type="text" name="kp_role" value="<?php echo esc_attr( $person['role'] ); ?>" placeholder="<?php esc_attr_e( 'Role', 'claims-angel' ); ?>">
                                <input type="email" name="kp_email" value="<?php echo esc_attr( $person['email'] ); ?>" placeholder="<?php esc_attr_e( 'Email Address', 'claims-angel' ); ?>">
                                <input type="tel" name="kp_phone" value="<?php echo esc_attr( $person['phone'] ); ?>" placeholder="<?php esc_attr_e( 'Phone Number', 'claims-angel' ); ?>">
                                <button type="submit" class="button"><?php esc_html_e( 'Save', 'claims-angel' ); ?></button>
                                <button type="submit" name="delete_key_person" value="1" class="button button-secondary"><?php esc_html_e( 'Delete', 'claims-angel' ); ?></button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p><?php esc_html_e( 'No key people available.', 'claims-angel' ); ?></p>
                <?php endif; ?>
            </div>

            <!-- Tab Content: Call Logging -->
            <div class="bc-tab-content" id="calls" style="display:none;">
                <h3><?php esc_html_e( 'Call Logging', 'claims-angel' ); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Caller', 'claims-angel' ); ?></th>
                            <th><?php esc_html_e( 'Duration', 'claims-angel' ); ?></th>
                            <th><?php esc_html_e( 'Time', 'claims-angel' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'claims-angel' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'claims-angel' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="calls-list">
                        <tr>
                            <td colspan="5"><?php esc_html_e( 'No call logs available.', 'claims-angel' ); ?></td>
                        </tr>
                    </tbody>
                </table>
                <form id="call-form" method="post" action="">
                    <?php wp_nonce_field( 'bc_add_call', 'bc_call_nonce' ); ?>
                    <input type="text" name="caller" placeholder="<?php esc_attr_e( 'Caller Name', 'claims-angel' ); ?>" required>
                    <input type="text" name="duration" placeholder="<?php esc_attr_e( 'Duration (e.g., 5 mins)', 'claims-angel' ); ?>" required>
                    <input type="text" name="status" placeholder="<?php esc_attr_e( 'Status (incoming/outgoing/missed)', 'claims-angel' ); ?>" required>
                    <br>
                    <textarea name="call_notes" placeholder="<?php esc_attr_e( 'Call notes...', 'claims-angel' ); ?>"></textarea>
                    <br>
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Log Call', 'claims-angel' ); ?></button>
                </form>
            </div>

            <!-- Tab Content: Reminder Logging -->
            <div class="bc-tab-content" id="reminders" style="display:none;">
                <h3><?php esc_html_e( 'Reminder Logging', 'claims-angel' ); ?></h3>
                <div id="reminders-list">
                    <p><?php esc_html_e( 'No reminders set. Add a new reminder below.', 'claims-angel' ); ?></p>
                </div>
                <form id="reminder-form" method="post" action="">
                    <?php wp_nonce_field( 'bc_add_reminder', 'bc_reminder_nonce' ); ?>
                    <input type="text" name="reminder_title" placeholder="<?php esc_attr_e( 'Reminder Title', 'claims-angel' ); ?>" required>
                    <input type="datetime-local" name="reminder_date" required>
                    <textarea name="reminder_notes" placeholder="<?php esc_attr_e( 'Additional notes...', 'claims-angel' ); ?>"></textarea>
                    <br>
                    <button type="submit" class="button button-primary"><?php esc_html_e( 'Add Reminder', 'claims-angel' ); ?></button>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Handle form submissions.
     */
    public function handle_form_submission() {
        // Handle General Note submission.
        if ( isset( $_POST['bc_note_nonce'] ) && wp_verify_nonce( $_POST['bc_note_nonce'], 'bc_add_note' ) ) {
            $note = sanitize_textarea_field( $_POST['note_content'] );
            error_log( "New note added: " . $note );
            wp_redirect( admin_url( 'admin.php?page=business-crm' ) );
            exit;
        }

        // Handle Call Logging submission.
        if ( isset( $_POST['bc_call_nonce'] ) && wp_verify_nonce( $_POST['bc_call_nonce'], 'bc_add_call' ) ) {
            $caller     = sanitize_text_field( $_POST['caller'] );
            $duration   = sanitize_text_field( $_POST['duration'] );
            $status     = sanitize_text_field( $_POST['status'] );
            $call_notes = sanitize_textarea_field( $_POST['call_notes'] );
            error_log( "New call logged: Caller: $caller, Duration: $duration, Status: $status, Notes: $call_notes" );
            wp_redirect( admin_url( 'admin.php?page=business-crm' ) );
            exit;
        }

        // Handle Reminder Logging submission.
        if ( isset( $_POST['bc_reminder_nonce'] ) && wp_verify_nonce( $_POST['bc_reminder_nonce'], 'bc_add_reminder' ) ) {
            $reminder_title = sanitize_text_field( $_POST['reminder_title'] );
            $reminder_date  = sanitize_text_field( $_POST['reminder_date'] );
            $reminder_notes = sanitize_textarea_field( $_POST['reminder_notes'] );
            error_log( "New reminder added: Title: $reminder_title, Date: $reminder_date, Notes: $reminder_notes" );
            wp_redirect( admin_url( 'admin.php?page=business-crm' ) );
            exit;
        }

        // Handle Adding a New Key Person.
        if ( isset( $_POST['bc_key_person_nonce'] ) && wp_verify_nonce( $_POST['bc_key_person_nonce'], 'bc_add_key_person' ) ) {
            $prospect_id = intval( $_POST['prospect_id'] );
            $new_person = [
                'first_name' => sanitize_text_field( $_POST['kp_first_name'] ),
                'last_name'  => sanitize_text_field( $_POST['kp_last_name'] ),
                'role'       => sanitize_text_field( $_POST['kp_role'] ),
                'email'      => sanitize_email( $_POST['kp_email'] ),
                'phone'      => sanitize_text_field( $_POST['kp_phone'] ),
            ];
            $existing = get_post_meta( $prospect_id, 'key_people', true );
            if ( ! is_array( $existing ) ) {
                $existing = [];
            }
            $existing[] = $new_person;
            update_post_meta( $prospect_id, 'key_people', $existing );
            wp_redirect( admin_url( 'admin.php?page=business-crm' ) );
            exit;
        }

        // Handle Update/Delete Key Person.
        if ( isset( $_POST['bc_update_key_person_nonce'] ) && wp_verify_nonce( $_POST['bc_update_key_person_nonce'], 'bc_update_key_person' ) ) {
            $prospect_id = intval( $_POST['prospect_id'] );
            $index       = intval( $_POST['kp_index'] );
            $existing    = get_post_meta( $prospect_id, 'key_people', true );
            if ( ! is_array( $existing ) ) {
                $existing = [];
            }
            if ( isset( $_POST['delete_key_person'] ) && '1' === $_POST['delete_key_person'] ) {
                if ( isset( $existing[ $index ] ) ) {
                    unset( $existing[ $index ] );
                    $existing = array_values( $existing );
                }
            } else {
                $existing[ $index ] = [
                    'first_name' => sanitize_text_field( $_POST['kp_first_name'] ),
                    'last_name'  => sanitize_text_field( $_POST['kp_last_name'] ),
                    'role'       => sanitize_text_field( $_POST['kp_role'] ),
                    'email'      => sanitize_email( $_POST['kp_email'] ),
                    'phone'      => sanitize_text_field( $_POST['kp_phone'] ),
                ];
            }
            update_post_meta( $prospect_id, 'key_people', $existing );
            wp_redirect( admin_url( 'admin.php?page=business-crm' ) );
            exit;
        }
    }
}

// Initialize the Business CRM Profile Page.
NewProspectProfile::get_instance();