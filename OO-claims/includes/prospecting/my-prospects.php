<?php
namespace ClaimsManagement;

if ( ! class_exists( __NAMESPACE__ . '\\ProspectListPage' ) ) {

    class ProspectListPage {

        /**
         * Constructor.
         */
        public function __construct() {
            add_action( 'admin_menu', [ $this, 'register_prospect_list_page' ] );
        }

        /**
         * Register a new submenu page to display permanent prospect records.
         */
        public function register_prospect_list_page() {
            add_submenu_page(
                'upload-prospects',
                __( 'My Prospects', 'claims-management' ),
                __( 'My Prospects', 'claims-management' ),
                'manage_options',
                'permanent-prospects',
                [ $this, 'render_prospect_list_page' ]
            );
        }

        /**
         * Render the admin page that lists permanent prospect records split into two tables.
         */
        public function render_prospect_list_page() {
            $args = [
                'post_type'      => 'cm_prospect',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ];
            $query = new \WP_Query( $args );

            // Prepare arrays for prospects.
            $due_prospects      = []; // Prospects with callback reminders due (today or overdue)
            $not_called_prospects = []; // Prospects with no call logs

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    $post_id = get_the_ID();
                    
                    // Check callback reminders.
                    $callbacks = get_post_meta( $post_id, 'cm_callback_notifications', true );
                    if ( ! is_array( $callbacks ) ) {
                        $callbacks = [];
                    }
                    $has_due = false;
                    foreach ( $callbacks as $callback ) {
                        if ( isset( $callback['callback_datetime'] ) && intval( $callback['callback_datetime'] ) <= time() ) {
                            $has_due = true;
                            break;
                        }
                    }
                    if ( $has_due ) {
                        $due_prospects[] = $post_id;
                        // Skip adding to "not called" group if already in due group.
                        continue;
                    }
                    
                    // If no call logs exist, add to not-called prospects.
                    $call_logs = get_post_meta( $post_id, 'cm_logged_calls', true );
                    if ( empty( $call_logs ) ) {
                        $not_called_prospects[] = $post_id;
                    }
                }
                wp_reset_postdata();
            }
            ?>
            <div class="wrap">
                <h1><?php esc_html_e( 'Permanent Prospect Records', 'claims-management' ); ?></h1>

                <!-- Table 1: Prospects with Due Callback Reminders -->
                <h2><?php esc_html_e( 'Prospects with Callback Reminders Due (Today or Overdue)', 'claims-management' ); ?></h2>
                <?php if ( ! empty( $due_prospects ) ) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Business', 'claims-management' ); ?></th>
                                <th><?php esc_html_e( 'Date Created', 'claims-management' ); ?></th>
                                <th><?php esc_html_e( 'Web Address', 'claims-management' ); ?></th>
                                <th><?php esc_html_e( 'Phone Number', 'claims-management' ); ?></th>
                                <th><?php esc_html_e( 'Country', 'claims-management' ); ?></th>
                                <th><?php esc_html_e( 'View', 'claims-management' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ( $due_prospects as $post_id ) {
                                $business_name = get_post_meta( $post_id, 'cm_business_name', true );
                                $web_address   = get_post_meta( $post_id, 'cm_web_address', true );
                                $phone         = get_post_meta( $post_id, 'cm_phone', true );
                                $country       = get_post_meta( $post_id, 'cm_country', true );
                                ?>
                                <tr>
                                    <td><?php echo esc_html( $business_name ); ?></td>
                                    <td><?php echo get_the_date( '', $post_id ); ?></td>
                                    <td><?php echo esc_url( $web_address ); ?></td>
                                    <td><?php echo esc_html( $phone ); ?></td>
                                    <td><?php echo esc_html( $country ); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=cm_view_prospect&post_id=' . $post_id ) ); ?>" class="button">
                                            <?php esc_html_e( 'View Prospect', 'claims-management' ); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p><?php esc_html_e( 'No prospects with due callback reminders found.', 'claims-management' ); ?></p>
                <?php endif; ?>

                <!-- Table 2: Prospects Not Called Yet -->
                <h2><?php esc_html_e( 'Prospects Not Called Yet', 'claims-management' ); ?></h2>
                <?php if ( ! empty( $not_called_prospects ) ) : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Business', 'claims-management' ); ?></th>
                                <th><?php esc_html_e( 'Date Created', 'claims-management' ); ?></th>
                                <th><?php esc_html_e( 'Web Address', 'claims-management' ); ?></th>
                                <th><?php esc_html_e( 'Phone Number', 'claims-management' ); ?></th>
                                <th><?php esc_html_e( 'Country', 'claims-management' ); ?></th>
                                <th><?php esc_html_e( 'View', 'claims-management' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ( $not_called_prospects as $post_id ) {
                                $business_name = get_post_meta( $post_id, 'cm_business_name', true );
                                $web_address   = get_post_meta( $post_id, 'cm_web_address', true );
                                $phone         = get_post_meta( $post_id, 'cm_phone', true );
                                $country       = get_post_meta( $post_id, 'cm_country', true );
                                ?>
                                <tr>
                                    <td><?php echo esc_html( $business_name ); ?></td>
                                    <td><?php echo get_the_date( '', $post_id ); ?></td>
                                    <td><?php echo esc_url( $web_address ); ?></td>
                                    <td><?php echo esc_html( $phone ); ?></td>
                                    <td><?php echo esc_html( $country ); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=cm_view_prospect&post_id=' . $post_id ) ); ?>" class="button">
                                            <?php esc_html_e( 'View Prospect', 'claims-management' ); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p><?php esc_html_e( 'No prospects found that have not been called yet.', 'claims-management' ); ?></p>
                <?php endif; ?>
            </div>
            <?php
        }
    }

    new ProspectListPage();
}