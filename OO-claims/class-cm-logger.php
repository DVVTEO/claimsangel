namespace ClaimsManagement;

class CM_Logger {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function log_error($message, $context = array()) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log_entry = sprintf(
            "[%s] %s | Context: %s\n",
            current_time('mysql'),
            $message,
            json_encode($context)
        );
        
        error_log($log_entry, 3, CM_PLUGIN_DIR . 'logs/error.log');
    }
}