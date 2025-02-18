<?php
namespace ClaimsManagement\Core;

if (!defined('ABSPATH')) {
    exit;
}

class CM_Logger {
    private static $instance = null;
    private $log_directory;
    
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->log_directory = CM_PLUGIN_DIR . 'logs/';
        $this->ensure_log_directory_exists();
    }
    
    private function ensure_log_directory_exists() {
        if (!file_exists($this->log_directory)) {
            wp_mkdir_p($this->log_directory);
            file_put_contents($this->log_directory . '.htaccess', 'deny from all');
        }
    }
    
    public function log($message, $level = self::LEVEL_INFO, $context = []) {
        if (!is_string($message)) {
            $message = print_r($message, true);
        }
        
        $log_entry = sprintf(
            "[%s] [%s] %s | Context: %s\n",
            current_time('mysql'),
            $level,
            $message,
            json_encode($context)
        );
        
        $filename = $this->log_directory . date('Y-m-d') . '.log';
        error_log($log_entry, 3, $filename);
        
        if ($level === self::LEVEL_CRITICAL) {
            $this->notify_admin($message, $context);
        }
    }
    
    public function info($message, $context = []) {
        $this->log($message, self::LEVEL_INFO, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log($message, self::LEVEL_WARNING, $context);
    }
    
    public function error($message, $context = []) {
        $this->log($message, self::LEVEL_ERROR, $context);
    }
    
    public function critical($message, $context = []) {
        $this->log($message, self::LEVEL_CRITICAL, $context);
    }
    
    private function notify_admin($message, $context) {
        $admin_email = CM_Config::get_instance()->get('notifications.admin_email');
        $subject = sprintf('[%s] Critical Error Detected', get_bloginfo('name'));
        
        $body = sprintf(
            "A critical error has occurred:\n\nMessage: %s\n\nContext: %s\n\nTime: %s",
            $message,
            json_encode($context, JSON_PRETTY_PRINT),
            current_time('mysql')
        );
        
        wp_mail($admin_email, $subject, $body);
    }
}