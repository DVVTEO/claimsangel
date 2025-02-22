<?php
/**
 * Plugin Name: Claims Angel Business Manager
 * Description: Business Management System for Claims Angel
 * Version: 1.0.0
 * Author: DVVTEO
 * Author URI: https://claimsangel.com
 * Created: 2025-02-21
 * Last Modified: 2025-02-21 23:00:07
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: claims-angel
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Define core plugin constants
 * These constants are used throughout the plugin for consistency
 */
define('CLAIMS_ANGEL_VERSION', '1.0.0');
define('CLAIMS_ANGEL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CLAIMS_ANGEL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CLAIMS_ANGEL_PLUGIN_FILE', __FILE__);

// Legacy constant support for backward compatibility
define('BUSINESS_MANAGER_VERSION', CLAIMS_ANGEL_VERSION);
define('BUSINESS_MANAGER_PLUGIN_DIR', CLAIMS_ANGEL_PLUGIN_DIR);
define('BUSINESS_MANAGER_PLUGIN_URL', CLAIMS_ANGEL_PLUGIN_URL);

/**
 * Autoloader for plugin classes
 * Automatically loads classes from the ClaimsAngel namespace
 * Classes should follow PSR-4 naming convention
 */
spl_autoload_register(function ($class) {
    $prefix = 'ClaimsAngel\\';
    $base_dir = CLAIMS_ANGEL_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Main Plugin Class
 * 
 * This class is responsible for initializing all core components of the plugin.
 * Uses singleton pattern to ensure only one instance is created.
 */
class ClaimsAngel {
    /**
     * Singleton instance
     *
     * @var ClaimsAngel|null
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return ClaimsAngel
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct creation
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize plugin hooks
     */
    private function init() {
        add_action('plugins_loaded', [$this, 'init_plugin']);
    }

    /**
     * Initialize plugin components
     * Creates instances of core classes for roles, post types, and meta fields
     */
    public function init_plugin() {
        // Initialize role manager
        new \ClaimsAngel\Roles\RoleManager();
        
        // Initialize post types
        new \ClaimsAngel\PostTypes\PostTypeManager();
        
        // Initialize meta fields
        new \ClaimsAngel\Meta\MetaFieldManager();

        // Initialize admin access controller
        \ClaimsAngel\Admin\AdminAccessController::init();

        // Initialize Countries system
        \ClaimsAngel\Data\Countries::get_instance();

        // Initialize Reference page
        \ClaimsAngel\Admin\Reference::init();

        // Initialize User Manager
        \ClaimsAngel\User\Manager::init();
        
        // Initialize Admin BEFORE Reference
        \ClaimsAngel\Admin\Admin::init();
        
        // Then initialize Reference 
        \ClaimsAngel\Admin\Reference::init();

        // Initialize User Manager
        \ClaimsAngel\User\Manager::init();

        // Initialize Business Admin
        new \ClaimsAngel\Admin\BusinessAdmin();

        // Initialize Import System Components in admin
        if (is_admin()) {
            \ClaimsAngel\Data\WebAddress::get_instance();
            \ClaimsAngel\Admin\ImportManager::get_instance();
            \ClaimsAngel\Admin\ProspectImportProcessor::get_instance();
        }
    }

    /**
     * Plugin activation hook callback
     * Sets up roles, post types, and refreshes permalinks
     */
    public static function activate() {
        // Initialize role manager for activation
        $role_manager = new \ClaimsAngel\Roles\RoleManager();
        $role_manager->setup_roles();

        // Initialize post types for activation
        $post_type_manager = new \ClaimsAngel\PostTypes\PostTypeManager();
        $post_type_manager->register_post_types();

        // Initialize import system tables
        if (class_exists('\ClaimsAngel\Admin\ProspectImportProcessor')) {
            \ClaimsAngel\Admin\ProspectImportProcessor::get_instance()->create_temp_table();
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook callback
     * Cleans up rewrite rules
     */
    public static function deactivate() {
        // Clean up import system tables
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}temp_prospects");

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['ClaimsAngel', 'activate']);
register_deactivation_hook(__FILE__, ['ClaimsAngel', 'deactivate']);

// Initialize the plugin
ClaimsAngel::get_instance();