<?php
/**
 * Plugin Name: Claims Management
 * Description: A comprehensive claims management system
 * Version: 1.0.0
 * Author: DVVTEO
 * Author URI: https://github.com/DVVTEO
 * Text Domain: claims-management
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CM_VERSION', '1.0.0');
define('CM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader function
function cm_autoloader($class) {
    // Only handle classes in our namespace
    $prefix = 'ClaimsManagement\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    
    // Convert namespace to file path
    $relative_class = substr($class, strlen($prefix));
    $file = CM_PLUGIN_DIR . 'includes/' . str_replace('\\', '/', strtolower($relative_class)) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require_once $file;
    }
}

// Register autoloader
spl_autoload_register('cm_autoloader');

// Initialize the plugin
function cm_init() {
    // Initialize core
    $core = \ClaimsManagement\Core\CM_Core::get_instance();
    
    // Register activation/deactivation hooks
    register_activation_hook(__FILE__, [$core, 'activate']);
    register_deactivation_hook(__FILE__, [$core, 'deactivate']);
    
    // Initialize components
    if (is_admin()) {
        cm_init_admin();
    }
    cm_init_frontend();
}

// Initialize admin components
function cm_init_admin() {
    // Load admin-specific components
    \ClaimsManagement\Admin\CM_Admin::get_instance();
}

// Initialize frontend components
function cm_init_frontend() {
    // Load frontend-specific components
    \ClaimsManagement\Frontend\CM_Frontend::get_instance();
}

// Hook into WordPress init
add_action('plugins_loaded', 'cm_init');

// Enqueue scripts and styles
function cm_enqueue_scripts() {
    if (is_admin()) {
        // Admin scripts and styles
        wp_enqueue_script(
            'claims-dashboard',
            CM_PLUGIN_URL . 'assets/js/claims-dashboard.js',
            ['jquery', 'wp-api'],
            CM_VERSION,
            true
        );
        
        wp_enqueue_style(
            'claims-dashboard',
            CM_PLUGIN_URL . 'assets/css/claims-dashboard.css',
            [],
            CM_VERSION
        );
        
        // Localize script with configuration and user capabilities
        wp_localize_script('claims-dashboard', 'cmConfig', [
            'nonce' => wp_create_nonce('claims_nonce'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'userCan' => [
                'delete_claims' => current_user_can('delete_claims'),
                'edit_claims' => current_user_can('edit_claims'),
                'view_claims' => current_user_can('view_claims')
            ],
            'itemsPerPage' => 25
        ]);
    }
}
add_action('admin_enqueue_scripts', 'cm_enqueue_scripts');