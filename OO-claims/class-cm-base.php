namespace ClaimsManagement;

abstract class CM_Base {
    protected $version;
    protected $plugin_name;
    
    public function __construct() {
        $this->version = CM_VERSION;
        $this->plugin_name = 'claims-management';
        $this->load_dependencies();
        $this->register_hooks();
    }
    
    abstract protected function load_dependencies();
    abstract protected function register_hooks();
    
    protected function get_plugin_name() {
        return $this->plugin_name;
    }
    
    protected function get_version() {
        return $this->version;
    }
}