<?php
namespace ClaimsAngel\Admin;

/**
 * Business Admin Class
 * Handles all business-related admin pages and functionality
 *
 * Created: 2025-02-21
 * Last Modified: 2025-02-21 18:45:05
 * Author: DVVTEO
 */
class BusinessAdmin {
    /**
     * Constructor
     * Initialize hooks for admin pages
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_pages']);
    }

    /**
     * Register all business-related admin pages
     */
    public function register_admin_pages() {
        // Register main business page
        add_menu_page(
            'Business Management',
            'Business',
            'read',
            'business-management',
            [$this, 'render_main_page'],
            'dashicons-building',
            30
        );

        // Register the hidden details page
        AdminAccessController::get_instance()->register_admin_page(
            'business-details',
            ['prospector', 'closer'],
            [
                'page_title' => 'Business Details',
                'menu_title' => 'Business Details',
                'capability' => 'read',
                'parent_slug' => null
            ],
            true  // Set hidden to true
        );

        // Register this page with WordPress but let our controller handle visibility
        add_submenu_page(
            null, // No parent - won't show in menu
            'Business Details',
            'Business Details',
            'read',
            'business-details',
            [$this, 'render_details_page']
        );
    }

    /**
     * Render the main business page
     */
    public function render_main_page() {
        // Main page content would go here
    }

    /**
     * Render the hidden details page
     */
    public function render_details_page() {
        // Details page content would go here
    }
}