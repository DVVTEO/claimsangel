<?php
namespace ClaimsManagement\Frontend;

class CM_Frontend {
    private static $instance = null;
    private $config;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->config = \ClaimsManagement\Core\CM_Config::get_instance();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Register shortcodes
        add_shortcode('claims_dashboard', [$this, 'render_dashboard']);
        
        // Register frontend scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }
    
    public function enqueue_assets() {
        if (!is_admin() && $this->should_load_assets()) {
            wp_enqueue_script(
                'claims-frontend',
                CM_PLUGIN_URL . 'assets/js/claims-frontend.js',
                ['jquery'],
                CM_VERSION,
                true
            );
            
            wp_enqueue_style(
                'claims-frontend',
                CM_PLUGIN_URL . 'assets/css/claims-frontend.css',
                [],
                CM_VERSION
            );
        }
    }
    
    private function should_load_assets() {
        global $post;
        return (
            is_singular() && 
            has_shortcode($post->post_content, 'claims_dashboard')
        );
    }
}