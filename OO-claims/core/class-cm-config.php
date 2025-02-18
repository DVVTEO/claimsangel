<?php
namespace ClaimsManagement\Core;

if (!defined('ABSPATH')) {
    exit;
}

class CM_Config {
    private static $instance = null;
    private $config = [];
    private $option_name = 'cm_config';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_config();
    }
    
    private function load_config() {
        // Load configuration from database
        $saved_config = get_option($this->option_name, []);
        
        // Default configuration
        $defaults = [
            'security' => [
                'max_login_attempts' => 5,
                'login_lockout_duration' => 900, // 15 minutes
                'password_expiry_days' => 90,
                'require_strong_passwords' => true,
                'session_expiry' => 3600, // 1 hour
            ],
            'performance' => [
                'cache_ttl' => HOUR_IN_SECONDS,
                'items_per_page' => 25,
                'max_bulk_operations' => 100,
            ],
            'notifications' => [
                'email_enabled' => true,
                'admin_email' => get_option('admin_email'),
                'notification_types' => [
                    'new_claim' => true,
                    'claim_status_change' => true,
                    'claim_assignment' => true,
                ]
            ],
            'features' => [
                'enable_client_portal' => true,
                'enable_file_uploads' => true,
                'allowed_file_types' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
                'max_file_size' => 5 * 1024 * 1024, // 5MB
            ]
        ];
        
        $this->config = wp_parse_args($saved_config, $defaults);
    }
    
    public function get($key = null, $default = null) {
        if (null === $key) {
            return $this->config;
        }
        
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $key_part) {
            if (!isset($value[$key_part])) {
                return $default;
            }
            $value = $value[$key_part];
        }
        
        return $value;
    }
    
    public function set($key, $value) {
        $keys = explode('.', $key);
        $config = &$this->config;
        
        while (count($keys) > 1) {
            $current_key = array_shift($keys);
            if (!isset($config[$current_key]) || !is_array($config[$current_key])) {
                $config[$current_key] = [];
            }
            $config = &$config[$current_key];
        }
        
        $config[array_shift($keys)] = $value;
        return $this->save_config();
    }
    
    private function save_config() {
        return update_option($this->option_name, $this->config);
    }
}