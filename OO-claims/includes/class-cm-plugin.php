<?php
/**
 * Main Plugin Class
 *
 * @package ClaimsManagement
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * CM_Plugin Class
 */
class CM_Plugin {
    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @var object
     */
    protected static $instance = null;

    /**
     * Get instance of this class.
     *
     * @since 1.0.0
     * @return CM_Plugin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'init', array( $this, 'init_post_types' ) );
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        $permissions = CM_Permissions::get_instance();

        if ( $permissions->user_can( 'manage_claims' ) ) {
            add_menu_page(
                __( 'Claims Management', 'claims-management' ),
                __( 'Claims', 'claims-management' ),
                'manage_claims',
                'claims-management',
                array( $this, 'render_admin_page' ),
                'dashicons-clipboard',
                25
            );

            // Add submenus
            add_submenu_page(
                'claims-management',
                __( 'All Claims', 'claims-management' ),
                __( 'All Claims', 'claims-management' ),
                'view_claims',
                'claims-list',
                array( $this, 'render_claims_list' )
            );
        }
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'cm_options', 'cm_settings' );
    }

    /**
     * Initialize post types
     */
    public function init_post_types() {
        register_post_type( 'claim', array(
            'labels' => array(
                'name' => __( 'Claims', 'claims-management' ),
                'singular_name' => __( 'Claim', 'claims-management' ),
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array( 'title', 'editor', 'author', 'thumbnail' ),
            'capability_type' => 'claim',
            'map_meta_cap' => true,
        ) );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Implementation for main admin page
    }

    /**
     * Render claims list
     */
    public function render_claims_list() {
        // Implementation for claims list
    }
}
