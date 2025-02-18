<?php
namespace ClaimsManagement\Core;

class CM_Core {
    private static $instance = null;
    private $loaded_components = [];
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_core_components();
    }
    
    private function init_core_components() {
        // Load essential components first
        $this->load_component('config', CM_Config::class);
        $this->load_component('logger', CM_Logger::class);
        $this->load_component('security', '\ClaimsManagement\Security\CM_Security');
        
        // Load cache after config is available
        $this->load_component('cache', CM_Cache::class);
        
        // Initialize AJAX handlers
        if (wp_doing_ajax()) {
            $this->load_component('ajax', '\ClaimsManagement\Ajax\CM_Ajax_Handler');
        }
    }
    
    private function load_component($key, $class) {
        if (!isset($this->loaded_components[$key])) {
            $this->loaded_components[$key] = call_user_func([$class, 'get_instance']);
        }
        return $this->loaded_components[$key];
    }
    
    public function activate() {
        // Perform activation tasks
        $this->create_tables();
        $this->set_default_options();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Perform deactivation tasks
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        // Create any necessary database tables
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Example table creation
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cm_claims (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            created_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            modified_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function set_default_options() {
        // Set default configuration options
        $config = CM_Config::get_instance();
        $config->set_defaults();
    }
}