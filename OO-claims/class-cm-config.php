namespace ClaimsManagement;

class CM_Config {
    private static $instance = null;
    private $config = [];
    
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
        $this->config = [
            'db_version' => '1.0.0',
            'cache_ttl' => HOUR_IN_SECONDS,
            'max_login_attempts' => 5,
            'security' => [
                'password_expiry_days' => 90,
                'require_2fa' => true
            ],
            'notifications' => [
                'email' => true,
                'slack' => false
            ]
        ];
    }
    
    public function get($key, $default = null) {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
}