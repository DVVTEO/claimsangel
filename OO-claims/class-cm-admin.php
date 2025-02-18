<?php
namespace ClaimsManagement;

class Admin {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_admin_menu_pages' ] );
		// Hook the settings registration to admin_init so that the Settings API functions are available.
		add_action( 'admin_init', [ 'ClaimsManagement\\Settings', 'register' ] );
	}

	/**
	 * Register admin menu pages.
	 */
	public function register_admin_menu_pages() {
		add_menu_page(
			__( 'Claims Management', 'claims-management' ),
			__( 'Claims Management', 'claims-management' ),
			'manage_options',
			'claims-management',
			[ $this, 'admin_page_callback' ],
			'dashicons-money-alt',
			3
		);
		add_submenu_page(
			'claims-management',
			__( 'Settings', 'claims-management' ),
			__( 'Settings', 'claims-management' ),
			'manage_options',
			'cm-settings',
			[ $this, 'settings_page_callback' ]
		);
		add_submenu_page(
			null,
			__( 'View Claim', 'claims-management' ),
			__( 'View Claim', 'claims-management' ),
			'manage_options',
			'cm_view_claim',
			[ $this, 'view_claim_page_callback' ]
		);
	}

	/**
	 * Render the main admin page.
	 */
	public function admin_page_callback() {
    include_once plugin_dir_path( __FILE__ ) . 'templates/admin-page.php';
}

	/**
	 * Render the settings page.
	 */
	public function settings_page_callback() {
		include_once plugin_dir_path( __FILE__ ) . 'templates/settings-page.php';
	}

	/**
	 * Render the view claim page.
	 */
	public function view_claim_page_callback() {
		include_once plugin_dir_path( __FILE__ ) . 'templates/view-claim-page.php';
	}
}

function cm_remove_admin_menu_items_for_claims_roles() {
    // Check if the current user is either a Claims Manager or a Claims Admin.
    if ( current_user_can( 'claims_manager' ) || current_user_can( 'claims_admin' ) ) {
        // Remove Dashboard
        remove_menu_page( 'index.php' );
        // Remove Jetpack (if installed)
        remove_menu_page( 'jetpack' );
        // Remove Posts, Media, Pages, Comments
        remove_menu_page( 'edit.php' );
        remove_menu_page( 'upload.php' );
        remove_menu_page( 'edit.php?post_type=page' );
        remove_menu_page( 'edit-comments.php' );
        // Remove Appearance, Plugins, Tools, Settings
        remove_menu_page( 'themes.php' );
        remove_menu_page( 'plugins.php' );
        remove_menu_page( 'tools.php' );
        remove_menu_page( 'options-general.php' );
        // If needed, remove other menu pages...
    }
}
add_action( 'admin_menu', 'cm_remove_admin_menu_items_for_claims_roles', 999 );