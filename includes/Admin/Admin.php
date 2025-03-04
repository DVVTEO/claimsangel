<?php
namespace ClaimsAngel\Admin;

/**
 * Admin Class
 * Handles all admin-related functionality
 * 
 * Created: 2025-02-21
 * Last Modified: 2025-02-22 12:24:27
 * Author: DVVTEO
 */
class Admin {
    /**
     * Initialize admin hooks
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
    }

    /**
     * Add menu pages to WordPress admin
     */
    public static function add_menu_pages() {
        add_menu_page(
            __('Claims Angel', 'claims-angel'),
            __('Claims Angel', 'claims-angel'),
            'manage_options',
            'claims-angel',
            [__CLASS__, 'render_main_page'],
            CLAIMS_ANGEL_PLUGIN_URL . 'assets/images/icon.png',
            30
        );
    }

    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin_assets($hook) {
        // Only load on Claims Angel admin pages
        if (strpos($hook, 'claims-angel') === false && 
            strpos($hook, 'prospect-import') === false) {
            return;
        }

        wp_enqueue_style(
            'claims-angel-admin',
            CLAIMS_ANGEL_PLUGIN_URL . 'assets/css/admin.css',
            [],
            CLAIMS_ANGEL_VERSION
        );

        wp_enqueue_script(
            'claims-angel-admin',
            CLAIMS_ANGEL_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            CLAIMS_ANGEL_VERSION,
            true
        );

        // Use the WordPress global ajaxurl
        wp_localize_script('claims-angel-admin', 'claimsAngel', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('claims_angel_nonce')
        ]);
    }

    /**
     * Render main admin page
     */
    public static function render_main_page() {
        include CLAIMS_ANGEL_PLUGIN_DIR . 'templates/admin/main-page.php';
    }
}