<?php
namespace ClaimsManagement;

function cm_enqueue_frontend_scripts() {
    wp_enqueue_style( 'bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css' );
    wp_enqueue_script( 'jquery' );
}
add_action( 'wp_enqueue_scripts', 'cm_enqueue_frontend_scripts' );

/**
 * Redirect clients away from the backend.
 */
function cm_redirect_clients_from_admin() {
	if ( is_user_logged_in() && current_user_can( 'cm_client' ) && ! defined( 'DOING_AJAX' ) ) {
		wp_redirect( home_url( '/client-portal/' ) );
		exit;
	}
}

/**
 * Hide the admin bar for clients.
 *
 * @param bool $show Whether to show the admin bar.
 * @return bool
 */
function cm_hide_admin_bar_for_clients( $show ) {
	if ( current_user_can( 'cm_client' ) ) {
		return false;
	}
	return $show;
}

/**
 * Generate a unique 5-digit numeric string to be used as a client username.
 *
 * @return string A unique 5-digit string.
 */
function generate_unique_slug() {
	do {
		$slug = strval( rand( 10000, 99999 ) );
		$user = get_user_by( 'login', $slug );
	} while ( $user );
	return $slug;
}

/**
 * Return the default countries.
 *
 * @return array Default countries.
 */
function get_default_countries() {
	return [
		'Albania',
		'Andorra',
		'Austria',
		'Belarus',
		'Belgium',
		'Bosnia and Herzegovina',
		'Bulgaria',
		'Croatia',
		'Cyprus',
		'Czech Republic',
		'Denmark',
		'Estonia',
		'Finland',
		'France',
		'Germany',
		'Greece',
		'Hungary',
		'Iceland',
		'Ireland',
		'Italy',
		'Kosovo',
		'Latvia',
		'Liechtenstein',
		'Lithuania',
		'Luxembourg',
		'Malta',
		'Moldova',
		'Monaco',
		'Montenegro',
		'Netherlands',
		'North Macedonia',
		'Norway',
		'Poland',
		'Portugal',
		'Romania',
		'Russia',
		'San Marino',
		'Serbia',
		'Slovakia',
		'Slovenia',
		'Spain',
		'Sweden',
		'Switzerland',
		'Turkey',
		'Ukraine',
		'United Kingdom',
		'Vatican City',
	];
}

/**
 * Sanitize the countries input.
 *
 * @param mixed $input The input from the textarea.
 * @return array Sanitized countries array.
 */
function sanitize_countries( $input ) {
	if ( is_array( $input ) ) {
		return array_map( 'sanitize_text_field', $input );
	} else {
		$lines     = explode( "\n", $input );
		$countries = array_map( 'trim', $lines );
		return array_filter( array_map( 'sanitize_text_field', $countries ) );
	}
}


/*----------------------------------------------------------------------------*\
	Add Country Field to Claims Manager and Claims Admin User Profiles
\*----------------------------------------------------------------------------*/

/**
 * Add the country field to the user profile for Claims Managers and Claims Admins.
 *
 * @param WP_User $user The current user object.
 */
function cm_add_country_field_to_profile( $user ) {
	// Only show this field if the user is a Claims Manager or Claims Admin.
	if ( ! in_array( 'claims_manager', (array) $user->roles, true ) && ! in_array( 'claims_admin', (array) $user->roles, true ) ) {
		return;
	}
	
	// Retrieve the list of countries from the plugin settings.
	$countries = get_option( 'cm_countries', get_default_countries() );
	
	// Get the currently assigned country (if any) from user meta.
	$user_country = get_user_meta( $user->ID, 'cm_user_country', true );
	?>
	<h3><?php esc_html_e( 'Claims Management Country Settings', 'claims-management' ); ?></h3>
	<table class="form-table">
		<tr>
			<th><label for="cm_user_country"><?php esc_html_e( 'Country', 'claims-management' ); ?></label></th>
			<td>
				<select name="cm_user_country_display" id="cm_user_country" <?php if ( in_array( 'claims_manager', (array) $user->roles, true ) && ! empty( $user_country ) ) { echo 'disabled="disabled"'; } ?>>
					<?php
					if ( is_array( $countries ) ) {
						foreach ( $countries as $country ) {
							?>
							<option value="<?php echo esc_attr( $country ); ?>" <?php selected( $user_country, $country ); ?>>
								<?php echo esc_html( $country ); ?>
							</option>
							<?php
						}
					}
					?>
				</select>
				<?php
				// For Claims Managers, output a hidden field to retain the value if the select is disabled.
				if ( in_array( 'claims_manager', (array) $user->roles, true ) && ! empty( $user_country ) ) {
					?>
					<input type="hidden" name="cm_user_country" value="<?php echo esc_attr( $user_country ); ?>">
					<?php
				} else {
					// For users who can edit, use the normal field name.
					?>
					<input type="hidden" name="cm_user_country" value="">
					<?php
				}
				?>
				<p class="description"><?php esc_html_e( 'Assign the country for this user.', 'claims-management' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', __NAMESPACE__ . '\\cm_add_country_field_to_profile' );
add_action( 'edit_user_profile', __NAMESPACE__ . '\\cm_add_country_field_to_profile' );

/**
 * Save the country field when the user profile is updated.
 *
 * @param int $user_id The ID of the user being saved.
 */
function cm_save_country_field_from_profile( $user_id ) {
	// Check current user's capability to edit the user.
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}
	
	// Only save if the field is set.
	if ( isset( $_POST['cm_user_country'] ) && ! empty( $_POST['cm_user_country'] ) ) {
		update_user_meta( $user_id, 'cm_user_country', sanitize_text_field( wp_unslash( $_POST['cm_user_country'] ) ) );
	} elseif ( isset( $_POST['cm_user_country_display'] ) ) {
		// For users allowed to edit, use the value from the editable select.
		update_user_meta( $user_id, 'cm_user_country', sanitize_text_field( wp_unslash( $_POST['cm_user_country_display'] ) ) );
	}
}
add_action( 'personal_options_update', __NAMESPACE__ . '\\cm_save_country_field_from_profile' );
add_action( 'edit_user_profile_update', __NAMESPACE__ . '\\cm_save_country_field_from_profile' );

function rename_dashboard_home_submenu() {
    global $submenu;
    if ( isset( $submenu['index.php'] ) && is_array( $submenu['index.php'] ) ) {
        // Change the first submenu item (usually "Home")
        $submenu['index.php'][0][0] = 'Claims';
    }
}

add_action( 'admin_menu', __NAMESPACE__ . '\\rename_dashboard_home_submenu', 999 );

function remove_dashboard_updates_submenu() {
    remove_submenu_page( 'index.php', 'update-core.php' );
}
add_action( 'admin_menu', __NAMESPACE__ . '\\remove_dashboard_updates_submenu', 999 );

if ( ! function_exists( 'clean_phone_number' ) ) {
    /**
     * Clean and standardize a phone number.
     *
     * If the phone number already begins with the same numeric prefix as the
     * country’s dialing code (but without a plus), only a plus is prepended.
     * Otherwise, the full dialing code is prepended.
     *
     * @param string $phone   The input phone number.
     * @param string $country The country name (to look up its dialing code).
     * @return string         The cleaned phone number.
     */
    function clean_phone_number( $phone, $country ) {
        // Remove leading/trailing whitespace.
        $phone = trim( $phone );

        // If the phone number already starts with a '+' then assume it’s good.
        if ( strpos( $phone, '+' ) === 0 ) {
            return '+' . preg_replace( '/[^\d]/', '', substr( $phone, 1 ) );
        }

        // Get the dialing code using your external function.
        if ( function_exists( 'cm_get_country_dialing_code' ) ) {
            $dialCode = cm_get_country_dialing_code( $country );
        } else {
            // Fallback mapping if necessary.
            $fallback_codes = array(
                'ES' => '+34',
                'US' => '+1',
                // Add more fallbacks as needed.
            );
            $dialCode = isset( $fallback_codes[ strtoupper( $country ) ] ) ? $fallback_codes[ strtoupper( $country ) ] : '';
        }

        // Remove any non-digit characters from the phone number.
        $digits = preg_replace( '/\D/', '', $phone );
        // Also remove non-digits from the dialing code (e.g. "+44" becomes "44").
        $dialNumeric = preg_replace( '/\D/', '', $dialCode );

        // If the dialing code is more than one digit, compare the first two digits.
        if ( strlen( $dialNumeric ) > 1 ) {
            if ( substr( $digits, 0, 2 ) === substr( $dialNumeric, 0, 2 ) ) {
                return '+' . $digits;
            }
        }
        // If the dialing code is one digit, compare the first digit.
        elseif ( strlen( $dialNumeric ) === 1 ) {
            if ( substr( $digits, 0, 1 ) === $dialNumeric ) {
                return '+' . $digits;
            }
        }

        // Otherwise, prepend the full dialing code.
        return $dialCode . $digits;
    }
}

if ( ! function_exists( 'clean_root_domain_url' ) ) {
    /**
     * Clean a URL to include only the scheme, host, and port (if any).
     *
     * Strips any paths, queries, or fragments from the URL.
     *
     * @param string $url The original URL.
     * @return string The cleaned URL.
     */
    function clean_root_domain_url( $url ) {
        $url = trim( $url );
        if ( ! preg_match( '/^https?:\/\//i', $url ) ) {
            $url = 'http://' . $url;
        }
        $parts = parse_url( $url );
        if ( ! $parts || ! isset( $parts['host'] ) ) {
            return $url;
        }
        $clean_url = $parts['scheme'] . '://' . $parts['host'];
        if ( isset( $parts['port'] ) ) {
            $clean_url .= ':' . $parts['port'];
        }
        return $clean_url;
    }
}