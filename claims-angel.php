<?php
/**
 * Plugin Name: Claims Angel Business Manager
 * Description: Business Management System for Claims Angel
 * Version: 1.0.0
 * Author: DVVTEO
 * Author URI: https://claimsangel.com
 * Created: 2025-02-21
 * Last Modified: 2025-02-28
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: claims-angel
 */

namespace ClaimsAngel;

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Define core plugin constants
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
 * Uses PSR-4 namespace structure and improves the initialization sequence.
 */
class Plugin {
    /**
     * Singleton instance
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Core component instances
     */
    private $role_manager;
    private $post_type_manager;
    private $meta_field_manager;

    /**
     * Get singleton instance
     *
     * @return Plugin
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
        // Don't initialize components here - wait for WordPress plugins_loaded hook
        add_action('plugins_loaded', [$this, 'init_core_components']);
        add_action('admin_menu', [$this, 'reorder_prospects_submenu'], 999);
    }

    /**
     * Initialize core plugin components in the correct order
     */
    public function init_core_components() {
        // Core system components - these should be initialized first
        $this->role_manager = new Roles\RoleManager();
        $this->post_type_manager = new PostTypes\PostTypeManager();
        $this->meta_field_manager = new Meta\MetaFieldManager();

        // Admin components
        Admin\AdminAccessController::init();
        Admin\Admin::init();
        
        // Data layer components
        Data\Countries::get_instance();
        Data\WebAddress::get_instance();
        
        // User management
        User\Manager::init();
        
        // Admin pages - initialized after admin framework is ready
        Admin\Reference::init();
        Admin\VehicleManagement\VehicleManagementPage::init();
        
        // Admin-only components
        if (is_admin()) {
            $this->init_admin_components();
        }

        // Register REST API filters
        add_filter('rest_prepare_business', [$this, 'prepare_business_rest_response'], 10, 3);
    }

    /**
     * Initialize admin-only components
     */
    private function init_admin_components() {
        // Aircall integration
        new Admin\Aircall\ConnectAircall();
        
        // Prospecting system - initialize in order of dependencies
        Admin\Prospecting\ImportProspects::get_instance();
        Admin\Prospecting\ProspectImportProcessor::get_instance();
        Admin\Prospecting\MyProspects::get_instance();
        Admin\Prospecting\NewProspectProfile::get_instance();
    }

    /**
     * Modify REST API response for business post type
     */
    public function prepare_business_rest_response($response, $post, $request) {
        // Retrieve all meta for this post.
        $meta = get_post_meta($post->ID);
        
        // Add the meta data to the response.
        $data = $response->get_data();
        $data['meta'] = $meta;
        $response->set_data($data);
        
        return $response;
    }

    /**
     * Reorder the prospects submenu items
     */
    public function reorder_prospects_submenu() {
        global $submenu;
        $parent_slug = 'prospects';

        if (isset($submenu[$parent_slug])) {
            // Define custom order mapping: lower numbers appear first
            $order_map = [
                'prospect-list'   => 5,
                'prospect-import' => 10,
                'business-crm'    => 20,
            ];

            // Sort submenu items using our mapping
            usort($submenu[$parent_slug], function($a, $b) use ($order_map) {
                // $a[2] and $b[2] are the submenu slugs
                $order_a = isset($order_map[$a[2]]) ? $order_map[$a[2]] : 999;
                $order_b = isset($order_map[$b[2]]) ? $order_map[$b[2]] : 999;
                return $order_a - $order_b;
            });
        }
    }

    /**
     * Plugin activation hook callback
     */
    public static function activate() {
        error_log('[Claims Angel] Starting plugin activation...');

        // Initialize role manager for activation
        $role_manager = new Roles\RoleManager();
        $role_manager->remove_roles(); // Ensure previous roles are removed
        $role_manager->setup_roles();  // Now set up fresh roles
        error_log('[Claims Angel] Roles setup completed');

        // Initialize post types for activation
        $post_type_manager = new PostTypes\PostTypeManager();
        $post_type_manager->register_post_types();
        error_log('[Claims Angel] Post types registered');

        // Initialize import system tables
        if (class_exists('ClaimsAngel\Admin\Prospecting\ProspectImportProcessor')) {
            error_log('[Claims Angel] ProspectImportProcessor class exists, creating tables...');
            Admin\Prospecting\ProspectImportProcessor::get_instance()->create_tables();
        } else {
            error_log('[Claims Angel] ERROR: ProspectImportProcessor class not found!');
        }
        
        // Create database indexes
        self::create_postmeta_indexes();
        
        // Reset meta fields
        self::reset_meta_fields();

        // Flush rewrite rules
        flush_rewrite_rules();
        error_log('[Claims Angel] Plugin activation completed');
    }

    /**
     * Plugin deactivation hook callback
     */
    public static function deactivate() {
        error_log('[Claims Angel] Starting plugin deactivation...');
        
        // Remove custom roles
        $role_manager = new Roles\RoleManager();
        $role_manager->remove_roles();
        
        // Remove database indexes
        self::remove_postmeta_indexes();
        
        // Clean up import system tables
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}temp_prospects");
        error_log('[Claims Angel] Dropped temp_prospects table');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        error_log('[Claims Angel] Plugin deactivation completed');
    }

    /**
     * Reset meta fields during activation
     */
    public static function reset_meta_fields() {
        // Get all 'business' posts
        $posts = get_posts([
            'post_type'   => 'business',
            'numberposts' => -1,
            'post_status' => 'any',
        ]);
        
        // Delete specific meta keys for each post
        foreach ($posts as $post) {
            delete_post_meta($post->ID, 'key_people');
            delete_post_meta($post->ID, 'call_logs');
            delete_post_meta($post->ID, 'call_back_reminders');
        }
    }

    /**
     * Create database indexes for improved performance
     */
    public static function create_postmeta_indexes() {
        global $wpdb;

        $indexes = [
            'business_status_idx'        => 'business_status',
            'country_idx'                => 'country',
            'prospect_prospector_idx'    => 'prospect_prospector',
            'prospect_closer_idx'        => 'prospect_closer',
            'claims_manager_idx'         => 'business_claims_manager',
            'business_delete_idx'        => 'business_delete',
        ];

        foreach ($indexes as $index_name => $meta_key) {
            // Check if the index already exists to avoid duplication
            $exists = $wpdb->get_row($wpdb->prepare(
                "SHOW INDEX FROM {$wpdb->postmeta} WHERE Key_name = %s",
                $index_name
            ));

            if (!$exists) {
                $wpdb->query(
                    "CREATE INDEX {$index_name} ON {$wpdb->postmeta} (meta_key(191), meta_value(191))"
                );
            }
        }
    }

    /**
     * Remove database indexes during deactivation
     */
    public static function remove_postmeta_indexes() {
        global $wpdb;

        $index_names = [
            'business_status_idx',
            'country_idx',
            'prospect_prospector_idx',
            'prospect_closer_idx',
            'claims_manager_idx',
            'business_delete_idx',
        ];

        foreach ($index_names as $index_name) {
            $wpdb->query("DROP INDEX {$index_name} ON {$wpdb->postmeta}");
        }
    }
}

// Register activation and deactivation hooks using the namespaced class
register_activation_hook(CLAIMS_ANGEL_PLUGIN_FILE, ['ClaimsAngel\\Plugin', 'activate']);
register_deactivation_hook(CLAIMS_ANGEL_PLUGIN_FILE, ['ClaimsAngel\\Plugin', 'deactivate']);

// Initialize the plugin
Plugin::get_instance();