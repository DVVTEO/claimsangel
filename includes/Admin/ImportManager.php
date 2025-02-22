<?php
namespace ClaimsAngel\Admin;

class ImportManager {
    private static $instance = null;
    private $page_hook;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_process_prospect_upload', [$this, 'process_upload']);
        add_action('wp_ajax_approve_country_prospects', [$this, 'approve_country_prospects']);
        add_action('wp_ajax_get_temp_prospects', [$this, 'get_temp_prospects']);
    }

    public function add_admin_menu() {
        $this->page_hook = add_menu_page(
            'Import Prospects',
            'Import Prospects',
            'manage_options',
            'prospect-import',
            [$this, 'render_import_page'],
            'dashicons-upload',
            30
        );
    }

    public function render_import_page() {
        require_once plugin_dir_path(__FILE__) . 'Views/import-page.php';
    }

    public function enqueue_scripts($hook) {
        if ($hook !== $this->page_hook) {
            return;
        }

        wp_enqueue_script(
            'prospect-import',
            CLAIMS_ANGEL_PLUGIN_URL . 'assets/js/import-manager.js',
            ['jquery'],
            CLAIMS_ANGEL_VERSION,
            true
        );

        wp_localize_script('prospect-import', 'prospectImport', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('prospect_import_ajax_nonce')
        ]);
    }

    public function process_upload() {
        check_ajax_referer('prospect_import_ajax_nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        if (!isset($_FILES['prospect_file'])) {
            wp_send_json_error(['message' => 'No file uploaded']);
            return;
        }

        $processor = ProspectImportProcessor::get_instance();
        $result = $processor->process_upload($_FILES['prospect_file']);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            return;
        }

        wp_send_json_success($result);
    }

    public function approve_country_prospects() {
        check_ajax_referer('prospect_import_ajax_nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        if (!isset($_POST['country']) || empty($_POST['country'])) {
            wp_send_json_error(['message' => 'Country code is required']);
            return;
        }

        global $wpdb;
        $country = sanitize_text_field($_POST['country']);
        $table_name = $wpdb->prefix . 'temp_prospects';

        $result = $wpdb->update(
            $table_name,
            ['status' => 'approved'],
            [
                'country' => $country,
                'status' => 'pending'
            ],
            ['%s'],
            ['%s', '%s']
        );

        if ($result === false) {
            wp_send_json_error(['message' => 'Database error occurred']);
            return;
        }

        wp_send_json_success([
            'updated' => $result,
            'message' => sprintf('%d records approved', $result)
        ]);
    }

    public function get_temp_prospects() {
        check_ajax_referer('prospect_import_ajax_nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'temp_prospects';

        $prospects = $wpdb->get_results(
            "SELECT * FROM {$table_name} 
            ORDER BY created_at DESC",
            ARRAY_A
        );

        if ($prospects === null) {
            wp_send_json_error(['message' => 'Database error occurred']);
            return;
        }

        wp_send_json_success($prospects);
    }
}