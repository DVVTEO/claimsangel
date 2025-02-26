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
        
            // Use the AdminAccessController to restrict access to administrators only.
    \ClaimsAngel\Admin\AdminAccessController::get_instance()->register_admin_page(
        'prospect-import',
        ['administrator'], // Only allow administrators.
        [
            'page_title' => 'Import Prospects',
            'menu_title' => 'Import Prospects',
            'menu_slug'  => 'prospect-import'
        ],
        false // not hidden
    );
    }

    public function render_import_page() {
        require_once plugin_dir_path(__FILE__) . 'Views/import-page.php';
    }
}