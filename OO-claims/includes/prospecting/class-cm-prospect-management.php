<?php
namespace ClaimsManagement;

if ( ! class_exists( __NAMESPACE__ . '\\CM_Prospect_Management' ) ) {

    class CM_Prospect_Management {

        /**
         * Constructor.
         */
        public function __construct() {
            // Register the submenu page for prospect management.
            add_action( 'admin_menu', [ $this, 'register_prospecting_management_submenu' ] );
        }

        /**
         * Register the "My Assigned Prospects" submenu page under the parent "Upload Prospects" menu.
         */
        public function register_prospecting_management_submenu() {
            add_submenu_page(
                'upload-prospects', // Parent slug – same as the top-level "Upload Prospects" page.
                __( 'My Prospects', 'claims-management' ),
                __( 'My Prospects', 'claims-management' ),
                'read',
                'cm-prospecting-assigned',
                [ $this, 'display_assigned_prospects' ]
            );
        }

        /**
         * Display a table of all prospects assigned to the logged‑in user.
         */
        public function display_assigned_prospects() {
            // Ensure the user is logged in.
            if ( ! is_user_logged_in() ) {
                wp_die( __( 'You must be logged in to view this page.', 'claims-management' ) );
            }
            $current_user_id = get_current_user_id();
            // Retrieve all prospects from the option.
            $all_prospects = get_option( 'cm_prospects', [] );
            $assigned_prospects = [];
            // Filter prospects that have been assigned to the current user.
            foreach ( $all_prospects as $prospect ) {
                if ( ( isset( $prospect['sales_manager'] ) && $prospect['sales_manager'] == $current_user_id ) ||
                     ( isset( $prospect['claims_manager'] ) && $prospect['claims_manager'] == $current_user_id ) ) {
                    $assigned_prospects[] = $prospect;
                }
            }
            ?>
            <div class="wrap">
                <h1><?php esc_html_e( 'My Assigned Prospects', 'claims-management' ); ?></h1>
                <?php if ( empty( $assigned_prospects ) ) : ?>
                    <p><?php esc_html_e( 'No prospects have been assigned to you yet.', 'claims-management' ); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped" style="margin-bottom: 30px;">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Business Name', 'claims-management' ); ?></th>
                                <th><?php esc_html_e( 'Web Address', 'claims-management' ); ?></th>
                                <th><?php esc_html_e( 'Phone Number', 'claims-management' ); ?></th>
                                <th><?php esc_html_e( 'Country', 'claims-management' ); ?></th>
                                <th><?php esc_html_e( 'Assignment Type', 'claims-management' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $assigned_prospects as $prospect ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $prospect['Business Name'] ); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url( $prospect['Web Address'] ); ?>" target="_blank">
                                            <?php echo esc_html( $prospect['Web Address'] ); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html( $prospect['Phone Number'] ); ?></td>
                                    <td>
                                        <?php 
                                        // Optionally display a flag image if your external functions exist.
                                        $iso = ( strlen( trim( $prospect['Country'] ) ) === 2 ) 
                                            ? strtolower( trim( $prospect['Country'] ) ) 
                                            : ( function_exists( 'cm_map_country_to_iso' ) ? cm_map_country_to_iso( $prospect['Country'] ) : '' );
                                        echo ( $iso && function_exists( 'cm_get_flag_img' ) ) ? cm_get_flag_img( $iso, $prospect['Country'] ) : '';
                                        echo ' ' . esc_html( $prospect['Country'] );
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ( isset( $prospect['sales_manager'] ) && $prospect['sales_manager'] == $current_user_id ) {
                                            echo esc_html__( 'Sales Manager', 'claims-management' );
                                        } elseif ( isset( $prospect['claims_manager'] ) && $prospect['claims_manager'] == $current_user_id ) {
                                            echo esc_html__( 'Claims Manager', 'claims-management' );
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <?php
        }
    }
}

new CM_Prospect_Management();