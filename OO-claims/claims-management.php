<?php
/**
 * Plugin Name: Claims Management
 * Description: A comprehensive claims management system
 * Version: 1.0.0
 * Author: DVVTEO
 * Author URI: https://github.com/DVVTEO
 * Text Domain: claims-management
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 *
 * @package ClaimsManagement
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define( 'CM_VERSION', '1.0.0' );
define( 'CM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Autoload classes
spl_autoload_register( function ( $class ) {
    $prefix = 'CM_';
    $base_dir = CM_PLUGIN_DIR . 'includes/';

    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, strlen( $prefix ) );
    $file = $base_dir . 'class-cm-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
} );

// Initialize the plugin
function cm_init() {
    // Initialize permissions
    $permissions = CM_Permissions::get_instance();
    
    // Run migrations if needed
    if ( get_option( 'cm_needs_migration' ) ) {
        CM_Permission_Migration::run();
        delete_option( 'cm_needs_migration' );
    }
}
add_action( 'plugins_loaded', 'cm_init' );

// Activation hook
register_activation_hook( __FILE__, 'cm_activate' );
function cm_activate() {
    update_option( 'cm_needs_migration', true );
}

// Deactivation hook
register_deactivation_hook( __FILE__, 'cm_deactivate' );
function cm_deactivate() {
    // Cleanup if needed
}
