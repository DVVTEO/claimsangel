<?php
namespace ClaimsAngel\Admin\Vehicles;

class VehicleManagementPage {
    /**
     * Singleton instance
     *
     * @var VehicleManagementPage|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance
     *
     * @return VehicleManagementPage
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - register our admin menu page.
     */
    private function __construct() {
        add_action('admin_menu', [ $this, 'add_admin_menu' ]);
    }

    /**
     * Register the Vehicle Management admin menu page.
     */
    public function add_admin_menu() {
        $page_hook = add_menu_page(
            'Vehicle Dashboard',              // Page title
            'Vehicle Dashboard',              // Menu title
            'read',                            // Capability required (clients have "read")
            'vehicle-management',              // Menu slug
            [ $this, 'render_page' ],          // Callback function
            'dashicons-car',                   // Icon
            32                                 // Position
        );

        // Use the AdminAccessController to restrict access only to users with the "client" role.
        \ClaimsAngel\Admin\AdminAccessController::get_instance()->register_admin_page(
    'vehicle-management',
    ['client'], // Allowed roles: clients only.
    [
        'page_title' => 'Vehicle Dashboard',
        'menu_title' => 'Vehicle Dashboard',
        'menu_slug'  => 'vehicle-management' // corrected slug
    ],
    false
);
    }

    /**
     * Render the Vehicle Management page.
     */
    public function render_page() {
        $current_user = wp_get_current_user();
        
        // Double-check that the user has the "client" role.
        if ( ! in_array( 'client', (array) $current_user->roles ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'You do not have sufficient permissions to access this page.', 'claims-angel' ) . '</p></div>';
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Vehicle Management', 'claims-angel'); ?></h1>
            <p><?php esc_html_e('Welcome to the Vehicle Management page. This area is available exclusively for clients.', 'claims-angel'); ?></p>
            <?php
            // Optionally, you can add code here to display a list of vehicle posts or other functionality.
            // For example, you might query 'vehicle' posts and display them in a table.
            ?>
        </div>
        <?php
    }
}

// Initialize the Vehicle Management page.
VehicleManagementPage::get_instance();