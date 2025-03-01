<?php

namespace ClaimsAngel\Admin\Prospecting;

class ImportProspects {
    private static $instance = null;
    private $page_hook;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

private function __construct() {
    // Use a higher priority to ensure this runs after the parent menu
    add_action('admin_menu', [$this, 'add_admin_menu'], 20);
}

public function add_admin_menu() {
    // Make sure this slug matches exactly with what's in the parent menu
    $parent_slug = 'prospects';
    
    $this->page_hook = add_submenu_page(
        $parent_slug,                 // Parent slug - must match exactly
        'Import Prospects',           // Page title
        'Import Prospects',           // Menu title
        'manage_options',             // Capability
        'prospect-import',            // Menu slug (this becomes the page parameter)
        [$this, 'render_import_page'], // Callback function
    );
    
    // Add a fix for the URL issue
    add_action('admin_init', function() {
        global $pagenow;
        if ($pagenow === 'prospect-import') {
            wp_redirect(admin_url('admin.php?page=prospect-import'));
            exit;
        }
    });


        
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