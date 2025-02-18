<?php
namespace ClaimsManagement;

class Pages_Creator {

	/**
	 * Auto-create the required pages on plugin activation.
	 *
	 * This method checks if pages with the slugs 'custom-client-login' and
	 * 'client-portal' exist, and if not, creates them with the appropriate shortcode content.
	 */
	public static function create_pages() {
		// Create the Custom Client Login page if it doesn't exist.
		if ( ! get_page_by_path( 'custom-client-login' ) ) {
			wp_insert_post( [
				'post_title'   => 'Custom Client Login',
				'post_content' => '[cm_custom_client_login]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_author'  => 1,
			] );
		}
		// Create the Client Portal page if it doesn't exist.
		if ( ! get_page_by_path( 'client-portal' ) ) {
			wp_insert_post( [
				'post_title'   => 'Client Portal',
				'post_content' => '[cm_client_portal]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_author'  => 1,
			] );
		}
	}
}