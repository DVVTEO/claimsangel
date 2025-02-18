<?php
namespace ClaimsManagement\Security;

if (!defined('ABSPATH')) {
    exit;
}

class CM_Security {
    private static $instance = null;
    private $config;
    private $logger;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->config = \ClaimsManagement\Core\CM_Config::get_instance();
        $this->logger = \ClaimsManagement\Core\CM_Logger::get_instance();
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_filter('authenticate', [$this, 'check_login_attempts'], 30, 3);
        add_action('wp_login_failed', [$this, 'log_failed_login']);
        add_action('wp_login', [$this, 'clear_login_attempts'], 10, 2);
        add_filter('password_required', [$this, 'enforce_strong_passwords'], 10, 2);
        add_action('admin_init', [$this, 'check_password_expiry']);
        add_action('init', [$this, 'start_secure_session']);
    }
    
    public function check_login_attempts($user, $username, $password) {
        if (!empty($username)) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $attempts = get_transient('login_attempts_' . $ip);
            $max_attempts = $this->config->get('security.max_login_attempts');
            
            if ($attempts >= $max_attempts) {
                $this->logger->warning('Login blocked due to too many attempts', [
                    'ip' => $ip,
                    'username' => $username
                ]);
                
                return new \WP_Error(
                    'too_many_attempts',
                    sprintf(
                        __('Too many failed login attempts. Please try again in %d minutes.', 'claims-management'),
                        ceil($this->config->get('security.login_lockout_duration') / 60)
                    )
                );
            }
        }
        return $user;
    }
    
    public function log_failed_login($username) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $attempts = get_transient('login_attempts_' . $ip);
        $attempts = $attempts ? $attempts + 1 : 1;
        
        set_transient(
            'login_attempts_' . $ip,
            $attempts,
            $this->config->get('security.login_lockout_duration')
        );
        
        $this->logger->warning('Failed login attempt', [
            'ip' => $ip,
            'username' => $username,
            'attempt_number' => $attempts
        ]);
    }
    
    public function clear_login_attempts($user_login, $user) {
        delete_transient('login_attempts_' . $_SERVER['REMOTE_ADDR']);
    }
    
    public function enforce_strong_passwords($required, $user) {
        if (!$this->config->get('security.require_strong_passwords')) {
            return $required;
        }
        
        // Add your password strength requirements here
        return $required;
    }
    
    public function check_password_expiry() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $last_password_change = get_user_meta($user_id, 'cm_last_password_change', true);
        $expiry_days = $this->config->get('security.password_expiry_days');
        
        if (!$last_password_change || 
            (time() - strtotime($last_password_change)) > ($expiry_days * DAY_IN_SECONDS)) {
            add_action('admin_notices', [$this, 'show_password_expiry_notice']);
        }
    }
    
    public function start_secure_session() {
        if (!session_id() && !headers_sent()) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => is_ssl()
            ]);
        }
    }
}