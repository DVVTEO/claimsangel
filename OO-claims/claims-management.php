<?php
/**
 * Plugin Name: Claims Management
 * Plugin URI: https://example.com/
 * Description: Create unique, password-protected client portals and manage claims. This version creates custom roles for Claims Manager (who sees only their own clients) and Claims Admin.
 * Version: 3.5
 * Author: DVVTEO
 * Author URI: https://github.com/DVVTEO
 * Text Domain: claims-management
 * Domain Path: /languages/
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('CM_VERSION', '3.5');
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

// Enqueue jQuery on admin pages
function cm_enqueue_admin_scripts() {
    wp_enqueue_script('jquery');
    add_thickbox();
    
    // Only load dashboard assets on claims pages
    $screen = get_current_screen();
    if (strpos($screen->id, 'claims') !== false || strpos($screen->id, 'prospect') !== false) {
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
add_action('admin_enqueue_scripts', 'cm_enqueue_admin_scripts');

// Load required files
require_once CM_PLUGIN_DIR . 'includes/functions.php';
require_once CM_PLUGIN_DIR . 'includes/class-cm-plugin.php';
require_once CM_PLUGIN_DIR . 'includes/class-cm-admin.php';
require_once CM_PLUGIN_DIR . 'includes/class-cm-settings.php';
require_once CM_PLUGIN_DIR . 'includes/class-cm-public.php';
require_once CM_PLUGIN_DIR . 'includes/class-cm-ajax.php';
require_once CM_PLUGIN_DIR . 'includes/class-cm-pages.php';
require_once CM_PLUGIN_DIR . 'includes/country-mapping.php';
require_once CM_PLUGIN_DIR . 'includes/prospecting/upload-prospects.php';
require_once CM_PLUGIN_DIR . 'includes/prospecting/my-prospects.php';
require_once CM_PLUGIN_DIR . 'includes/prospecting/prospect-profile-page.php';
require_once CM_PLUGIN_DIR . 'includes/dashboards/prospecting-dashboard.php';
require_once CM_PLUGIN_DIR . 'includes/dashboards/claims-dashboard.php';

// Core class requirement (new)
require_once CM_PLUGIN_DIR . 'includes/core/class-cm-core.php';

// Initialize the plugin
function cm_init() {
    try {
        // Initialize legacy plugin instance
        \ClaimsManagement\Plugin::get_instance();
        
        // Initialize new core functionality
        \ClaimsManagement\Core\CM_Core::get_instance();
        
    } catch (Exception $e) {
        // Log error and show admin notice
        error_log('Claims Management Plugin Error: ' . $e->getMessage());
        add_action('admin_notices', function() use ($e) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html('Claims Management Plugin Error: ' . $e->getMessage())
            );
        });
    }
}
add_action('plugins_loaded', 'cm_init');

/**
 * Create custom roles for Claims Manager and Claims Admin
 */
function cm_add_custom_roles() {
    // Claims Manager role
    add_role(
        'claims_manager',
        __('Claims Manager', 'claims-management'),
        [
            'read' => true,
            'edit_claims' => true,
            'view_claims' => true,
            'manage_claims' => true
        ]
    );
    
    // Claims Admin role
    add_role(
        'claims_admin',
        __('Claims Admin', 'claims-management'),
        [
            'read' => true,
            'manage_options' => true,
            'edit_claims' => true,
            'delete_claims' => true,
            'view_claims' => true,
            'manage_claims' => true,
            'assign_claims' => true
        ]
    );
    
    // Register custom capabilities
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_role->add_cap('edit_claims');
        $admin_role->add_cap('delete_claims');
        $admin_role->add_cap('view_claims');
        $admin_role->add_cap('manage_claims');
        $admin_role->add_cap('assign_claims');
    }
}
register_activation_hook(__FILE__, 'cm_add_custom_roles');

/**
 * Plugin activation hook
 */
function cm_activate_plugin() {
    // Ensure roles are created
    cm_add_custom_roles();
    
    // Create required database tables
    cm_create_tables();
    
    // Create required pages
    \ClaimsManagement\Pages_Creator::create_pages();
    
    // Clear rewrite rules
    flush_rewrite_rules();
    
    // Set default options
    cm_set_default_options();
}
register_activation_hook(__FILE__, 'cm_activate_plugin');

/**
 * Create required database tables
 */
function cm_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $claims_table = $wpdb->prefix . 'cm_claims';
    $claims_meta_table = $wpdb->prefix . 'cm_claimsmeta';
    
    $claims_sql = "CREATE TABLE IF NOT EXISTS $claims_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        status varchar(50) NOT NULL DEFAULT 'pending',
        created_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        modified_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY status (status)
    ) $charset_collate;";
    
    $claims_meta_sql = "CREATE TABLE IF NOT EXISTS $claims_meta_table (
        meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        claim_id bigint(20) unsigned NOT NULL,
        meta_key varchar(255) DEFAULT NULL,
        meta_value longtext,
        PRIMARY KEY (meta_id),
        KEY claim_id (claim_id),
        KEY meta_key (meta_key(191))
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($claims_sql);
    dbDelta($claims_meta_sql);
}

/**
 * Set default plugin options
 */
function cm_set_default_options() {
    $defaults = [
        'cm_version' => CM_VERSION,
        'claims_per_page' => 25,
        'enable_client_portal' => true,
        'notification_emails' => get_option('admin_email'),
        'date_format' => 'Y-m-d',
        'currency' => 'USD',
        'security' => [
            'max_login_attempts' => 5,
            'login_lockout_duration' => 900,
            'password_expiry_days' => 90,
            'require_strong_passwords' => true
        ]
    ];
    
    foreach ($defaults as $key => $value) {
        if (is_array($value)) {
            update_option('cm_' . $key, $value, false);
        } else {
            add_option('cm_' . $key, $value, '', false);
        }
    }
}

/**
 * Plugin deactivation hook
 */
function cm_deactivate_plugin() {
    // Clear rewrite rules
    flush_rewrite_rules();
    
    // Optionally clean up temporary data
    delete_transient('cm_temp_data');
}
register_deactivation_hook(__FILE__, 'cm_deactivate_plugin');

/**
 * Remove default roles that are not needed
 */
function cm_remove_default_roles() {
    remove_role('subscriber');
    remove_role('author');
    remove_role('editor');
    remove_role('contributor');
}
register_activation_hook(__FILE__, 'cm_remove_default_roles');

/**
 * Remove unwanted admin menu items for Claims Manager and Claims Admin
 */
function cm_remove_admin_menu_items_for_claims_roles() {
    if (current_user_can('claims_manager') || current_user_can('claims_admin')) {
        $remove_menus = [
            'index.php',          // Dashboard
            'jetpack',            // Jetpack
            'edit.php',           // Posts
            'upload.php',         // Media
            'edit.php?post_type=page', // Pages
            'edit-comments.php',  // Comments
            'themes.php',         // Appearance
            'plugins.php',        // Plugins
            'tools.php',          // Tools
            'options-general.php' // Settings
        ];
        
        foreach ($remove_menus as $menu) {
            remove_menu_page($menu);
        }
    }
}
add_action('admin_menu', 'cm_remove_admin_menu_items_for_claims_roles', 999);

// Add error handling for AJAX requests
add_action('wp_ajax_nopriv_claims_error', function() {
    wp_send_json_error(['message' => 'You must be logged in to perform this action.']);
});

// Initialize custom post types and taxonomies
function cm_init_post_types() {
    // Register any custom post types if needed
}
add_action('init', 'cm_init_post_types');

// Add plugin action links
function cm_add_plugin_action_links($links) {
    $plugin_links = [
        '<a href="' . admin_url('admin.php?page=claims-settings') . '">' . __('Settings', 'claims-management') . '</a>',
    ];
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cm_add_plugin_action_links');

// Load text domain for translations
function cm_load_textdomain() {
    load_plugin_textdomain('claims-management', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'cm_load_textdomain');
