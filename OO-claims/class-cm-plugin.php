<?php
namespace ClaimsManagement;

class Plugin {
	private static $instance;

	/**
	 * Get the singleton instance.
	 *
	 * @return Plugin
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
		$this->init();
	}

	/**
	 * Initialize hooks and load modules.
	 */
	private function init() {
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		if ( is_admin() ) {
			new Admin();
		}
		new Public_Functionality();
		new Ajax_Handler();
		new Pages_Creator();
		add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\cm_enqueue_frontend_scripts' );
		// Restrict backend access for clients.
		add_action( 'admin_init', __NAMESPACE__ . '\\cm_redirect_clients_from_admin', 1 );
		add_filter( 'show_admin_bar', __NAMESPACE__ . '\\cm_hide_admin_bar_for_clients' );
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_textdomain() {
		$plugin_lang = get_option( 'cm_plugin_language' );
		$locale      = ! empty( $plugin_lang ) ? $plugin_lang : determine_locale();
		load_plugin_textdomain( 'claims-management', false, dirname( plugin_basename( __FILE__ ) ) . '/../languages/' );
	}
}