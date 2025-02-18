<?php
namespace ClaimsManagement\Core;

if (!defined('ABSPATH')) {
    exit;
}

class CM_Cache {
    private static $instance = null;
    private $config;
    private $cache_group = 'claims_management';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->config = CM_Config::get_instance();
    }
    
    public function get($key, $default = null) {
        $value = wp_cache_get($key, $this->cache_group);
        return false === $value ? $default : $value;
    }
    
    public function set($key, $value, $expiration = null) {
        if (null === $expiration) {
            $expiration = $this->config->get('performance.cache_ttl');
        }
        
        return wp_cache_set($key, $value, $this->cache_group, $expiration);
    }
    
    public function delete($key) {
        return wp_cache_delete($key, $this->cache_group);
    }
    
    public function flush() {
        return wp_cache_flush();
    }
}