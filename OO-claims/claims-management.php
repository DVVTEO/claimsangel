<?php
/**
 * Plugin Name: Claims Management
 * Plugin URI: https://example.com/
 * Description: Create unique, password‑protected client portals and manage claims. This version creates custom roles for Claims Manager (who sees only their own clients) and Claims Admin (who sees all data), and uses custom user type 'cm_client' for clients.
 * Version: 3.4
 * Author: Your Name
 * Author URI: https://example.com/
 * Text Domain: claims-management
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Enqueue jQuery on admin pages.
add_action( 'admin_enqueue_scripts', 'cm_enqueue_admin_scripts' );
function cm_enqueue_admin_scripts() {
	wp_enqueue_script( 'jquery' );
}

// Load required files.
require_once plugin_dir_path( __FILE__ ) . 'includes/functions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/core/class-cm-plugin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin/class-cm-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin/class-cm-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/frontend/class-cm-public.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/api/class-cm-ajax.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/core/class-cm-pages.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/data/country-mapping.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/prospecting/upload-prospects.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/prospecting/my-prospects.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/prospecting/prospect-profile-page.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/dashboards/prospecting-dashboard.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/dashboards/claims-dashboard.php';


// Initialize the plugin.
\ClaimsManagement\Plugin::get_instance();

/**
 * Create custom roles for Claims Manager and Claims Admin.
 */
function cm_add_custom_roles() {
	add_role(
		'claims_manager',
		__( 'Claims Manager', 'claims-management' ),
		[ 'read' => true ]
	);
	add_role(
		'claims_admin',
		__( 'Claims Admin', 'claims-management' ),
		[ 'read' => true, 'manage_options' => true ]
	);
}
register_activation_hook( __FILE__, 'cm_add_custom_roles' );

/**
 * Plugin activation hook.
 */
function cm_activate_plugin() {
	flush_rewrite_rules();
	\ClaimsManagement\Pages_Creator::create_pages();
}
register_activation_hook( __FILE__, 'cm_activate_plugin' );

/**
 * Plugin deactivation hook.
 */
function cm_deactivate_plugin() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'cm_deactivate_plugin' );

/**
 * Remove default roles that are not needed.
 */
function cm_remove_default_roles() {
	remove_role( 'subscriber' );
	remove_role( 'author' );
	remove_role( 'editor' );
	remove_role( 'contributor' );
}
register_activation_hook( __FILE__, 'cm_remove_default_roles' );

/**
 * Remove unwanted admin menu items for Claims Manager and Claims Admin.
 */
function cm_remove_admin_menu_items_for_claims_roles() {
	if ( current_user_can( 'claims_manager' ) || current_user_can( 'claims_admin' ) ) {
		remove_menu_page( 'index.php' );
		remove_menu_page( 'jetpack' );
		remove_menu_page( 'edit.php' );
		remove_menu_page( 'upload.php' );
		remove_menu_page( 'edit.php?post_type=page' );
		remove_menu_page( 'edit-comments.php' );
		remove_menu_page( 'themes.php' );
		remove_menu_page( 'plugins.php' );
		remove_menu_page( 'tools.php' );
		remove_menu_page( 'options-general.php' );
	}
}
add_action( 'admin_menu', 'cm_remove_admin_menu_items_for_claims_roles', 999 );
add_action( 'admin_enqueue_scripts', function() {
    add_thickbox();
});