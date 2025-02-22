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
            plugins_url('assets/js/import-manager.js', dirname(__FILE__)),
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('prospect-import', 'prospectImport', [
            'ajaxurl' => admin_url('ajax.php'),
            'nonce' => wp_create_nonce('prospect_import_ajax_nonce')
        ]);
    }
}