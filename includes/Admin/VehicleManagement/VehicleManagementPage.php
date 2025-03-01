<?php
namespace ClaimsAngel\Admin\VehicleManagement;

/**
 * Vehicle Management Page Class
 * 
 * Handles the admin interface for managing vehicles.
 * Created: 2025-02-23 19:52:27
 * Last Modified: 2025-02-23 19:52:27
 * Author: DVVTEO
 */
class VehicleManagementPage {
    /**
     * Instance of this class
     *
     * @var VehicleManagementPage
     */
    private static $instance = null;

    /**
     * Constructor
     * Initialize hooks for the vehicle management page
     */
    private function __construct() {
        add_action('admin_menu', [$this, 'add_vehicles_page']);
    }

    /**
     * Get instance of this class
     * Ensures only one instance is created (Singleton)
     *
     * @return VehicleManagementPage
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the vehicle management page
     * This method should be called from the main plugin file
     */
    public static function init() {
        self::get_instance();
    }

    /**
     * Add the vehicles management page to the admin menu
     */
    public function add_vehicles_page() {
        add_menu_page(
            __('Vehicle Management', 'claims-angel'),
            __('Vehicles', 'claims-angel'),
            'edit_vehicle',
            'vehicle-management',
            [$this, 'render_page'],
            'dashicons-car',
            30
        );
    }

    /**
     * Render the vehicle management page
     */
    public function render_page() {
        // Security check
        if (!current_user_can('edit_vehicle')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'claims-angel'));
        }

        // Include the template
        include CLAIMS_ANGEL_PLUGIN_DIR . 'templates/admin/vehicle-management-page.php';
    }
}