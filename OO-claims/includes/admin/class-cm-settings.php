<?php
namespace ClaimsManagement;

class Settings {
	/**
	 * Register settings, sections, and fields.
	 */
	public static function register() {
		\register_setting(
			'cm_settings_group',
			'cm_plugin_language',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'en_US',
			]
		);
		\register_setting(
			'cm_settings_group',
			'cm_client_webhook',
			[
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			]
		);
		\register_setting(
			'cm_settings_group',
			'cm_countries',
			[
				'type'              => 'array',
				'sanitize_callback' => __NAMESPACE__ . '\\sanitize_countries',
				'default'           => \ClaimsManagement\get_default_countries(),
			]
		);

		\add_settings_section(
			'cm_general_settings',
			__( 'General Settings', 'claims-management' ),
			[ __CLASS__, 'general_settings_callback' ],
			'cm-settings'
		);

		\add_settings_field(
			'cm_countries',
			__( 'Countries List', 'claims-management' ),
			[ __CLASS__, 'countries_field_callback' ],
			'cm-settings',
			'cm_general_settings'
		);

		\add_settings_field(
			'cm_client_webhook',
			__( 'Client Portal Access Email Webhook', 'claims-management' ),
			[ __CLASS__, 'client_webhook_field_callback' ],
			'cm-settings',
			'cm_general_settings'
		);

		\add_settings_field(
			'cm_plugin_language',
			__( 'Plugin Language Preference', 'claims-management' ),
			[ __CLASS__, 'plugin_language_field_callback' ],
			'cm-settings',
			'cm_general_settings'
		);
	}

	/**
	 * Callback for the general settings section.
	 */
	public static function general_settings_callback() {
		echo '<p>' . esc_html__( 'Configure general settings for the Claims Management plugin.', 'claims-management' ) . '</p>';
	}

	/**
	 * Render the Countries field.
	 */
	public static function countries_field_callback() {
		$countries      = \get_option( 'cm_countries', \ClaimsManagement\get_default_countries() );
		$countries_text = is_array( $countries ) ? implode( "\n", $countries ) : '';
		echo '<textarea name="cm_countries" id="cm_countries" rows="10" cols="50">' . esc_textarea( $countries_text ) . '</textarea>';
		echo '<p class="description">' . esc_html__( 'Enter one country per line.', 'claims-management' ) . '</p>';
	}

	/**
	 * Render the Client Webhook field.
	 */
	public static function client_webhook_field_callback() {
		$webhook = \get_option( 'cm_client_webhook', '' );
		echo '<input type="url" name="cm_client_webhook" id="cm_client_webhook" value="' . esc_url( $webhook ) . '" style="width:400px;" />';
		echo '<p class="description">' . esc_html__( 'Enter the webhook URL to which client access details should be pushed.', 'claims-management' ) . '</p>';
	}

	/**
	 * Render the Plugin Language field.
	 */
	public static function plugin_language_field_callback() {
		$language = \get_option( 'cm_plugin_language', 'en_US' );
		?>
		<select name="cm_plugin_language" id="cm_plugin_language">
			<option value="en_US" <?php selected( $language, 'en_US' ); ?>>
				<?php esc_html_e( 'English (US)', 'claims-management' ); ?>
			</option>
			<option value="es_ES" <?php selected( $language, 'es_ES' ); ?>>
				<?php esc_html_e( 'Spanish (Spain)', 'claims-management' ); ?>
			</option>
			<option value="fr_FR" <?php selected( $language, 'fr_FR' ); ?>>
				<?php esc_html_e( 'French (France)', 'claims-management' ); ?>
			</option>
			<option value="de_DE" <?php selected( $language, 'de_DE' ); ?>>
				<?php esc_html_e( 'German (Germany)', 'claims-management' ); ?>
			</option>
			<option value="it_IT" <?php selected( $language, 'it_IT' ); ?>>
				<?php esc_html_e( 'Italian (Italy)', 'claims-management' ); ?>
			</option>
			<option value="nl_NL" <?php selected( $language, 'nl_NL' ); ?>>
				<?php esc_html_e( 'Dutch (Netherlands)', 'claims-management' ); ?>
			</option>
			<option value="pl_PL" <?php selected( $language, 'pl_PL' ); ?>>
				<?php esc_html_e( 'Polish', 'claims-management' ); ?>
			</option>
			<option value="sv_SE" <?php selected( $language, 'sv_SE' ); ?>>
				<?php esc_html_e( 'Swedish', 'claims-management' ); ?>
			</option>
			<option value="pt_PT" <?php selected( $language, 'pt_PT' ); ?>>
				<?php esc_html_e( 'Portuguese (Portugal)', 'claims-management' ); ?>
			</option>
		</select>
		<p class="description"><?php esc_html_e( 'Select the language for the plugin.', 'claims-management' ); ?></p>
		<?php
	}
	
	/**
	 * Handle saving of plugin settings via custom form submission.
	 */
	public static function save_settings_handler() {
		// Verify the nonce.
		if ( ! isset( $_POST['cm_settings_nonce_field'] ) || ! wp_verify_nonce( $_POST['cm_settings_nonce_field'], 'cm_save_settings' ) ) {
			wp_die( esc_html__( 'Nonce verification failed', 'claims-management' ) );
		}
		
		// Process settings updates.
		if ( isset( $_POST['cm_plugin_language'] ) ) {
			update_option( 'cm_plugin_language', sanitize_text_field( wp_unslash( $_POST['cm_plugin_language'] ) ) );
		}
		if ( isset( $_POST['cm_client_webhook'] ) ) {
			update_option( 'cm_client_webhook', esc_url_raw( wp_unslash( $_POST['cm_client_webhook'] ) ) );
		}
		if ( isset( $_POST['cm_countries'] ) ) {
			$countries = \ClaimsManagement\sanitize_countries( wp_unslash( $_POST['cm_countries'] ) );
			update_option( 'cm_countries', $countries );
		}
		
		// Redirect back to the plugin settings page with a success query parameter.
		wp_redirect( admin_url( 'admin.php?page=cm-settings&cm_settings_updated=1' ) );
		exit;
	}
}

// Hook the custom save settings handler.
add_action( 'admin_post_save_cm_settings', __NAMESPACE__ . '\\Settings::save_settings_handler' );