<?php
namespace ClaimsManagement\Ajax;

if (!defined('ABSPATH')) {
    exit;
}

class CM_Ajax_Handler {
    private static $instance = null;
    private $logger;
    private $config;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->logger = \ClaimsManagement\Core\CM_Logger::get_instance();
        $this->config = \ClaimsManagement\Core\CM_Config::get_instance();
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_ajax_get_claims_data', [$this, 'handle_get_claims_data']);
        add_action('wp_ajax_get_claims_statistics', [$this, 'handle_get_claims_statistics']);
        add_action('wp_ajax_update_claim', [$this, 'handle_update_claim']);
        add_action('wp_ajax_delete_claim', [$this, 'handle_delete_claim']);
    }
    
    public function handle_get_claims_data() {
        try {
            $this->verify_nonce('claims_nonce');
            $this->verify_capability('view_claims');
            
            $params = $this->get_datatable_params();
            $filters = $this->sanitize_filters($_POST['filters'] ?? []);
            
            $claims = $this->get_claims_data($params, $filters);
            $total = $this->get_total_claims_count($filters);
            
            wp_send_json_success([
                'draw' => intval($_POST['draw']),
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $claims
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching claims data: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    public function handle_get_claims_statistics() {
        try {
            $this->verify_nonce('claims_nonce');
            $this->verify_capability('view_claims');
            
            $filters = $this->sanitize_filters($_POST['filters'] ?? []);
            $stats = $this->get_claims_statistics($filters);
            
            wp_send_json_success($stats);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching claims statistics: ' . $e->getMessage());
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    private function verify_nonce($action) {
        if (!check_ajax_referer($action, 'nonce', false)) {
            throw new \Exception('Invalid security token');
        }
    }
    
    private function verify_capability($capability) {
        if (!current_user_can($capability)) {
            throw new \Exception('You do not have permission to perform this action');
        }
    }
    
    private function get_datatable_params() {
        return [
            'start' => intval($_POST['start'] ?? 0),
            'length' => intval($_POST['length'] ?? 10),
            'search' => sanitize_text_field($_POST['search']['value'] ?? ''),
            'order_by' => sanitize_text_field($_POST['order'][0]['column'] ?? 'created_date'),
            'order_dir' => sanitize_text_field($_POST['order'][0]['dir'] ?? 'desc')
        ];
    }
    
    private function sanitize_filters($filters) {
        return [
            'dateRange' => [
                'start' => sanitize_text_field($filters['dateRange']['start'] ?? ''),
                'end' => sanitize_text_field($filters['dateRange']['end'] ?? '')
            ],
            'statuses' => array_map('sanitize_text_field', $filters['statuses'] ?? [])
        ];
    }
}