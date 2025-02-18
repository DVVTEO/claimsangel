<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Process settings form submission.
 * This code assumes that your settings form submits via POST with a field "save_settings"
 * and includes a nonce field with action "cm_save_settings" and name "cm_settings_nonce_field".
 */
if ( isset( $_POST['save_settings'] ) && check_admin_referer( 'cm_save_settings', 'cm_settings_nonce_field' ) ) {
	// Process your settings updates here. For example:
	// $new_countries = sanitize_text_field( wp_unslash( $_POST['cm_countries'] ) );
	// update_option( 'cm_countries', $new_countries );
	
	// Then display the success message:
	echo '<div class="updated"><p>' . esc_html__( 'Settings have been saved successfully.', 'claims-management' ) . '</p></div>';
}

// Process new client creation form submission.
if ( isset( $_POST['create_client'] ) && check_admin_referer( 'cm_create_client', 'cm_nonce_field' ) ) {
	$required = [ 'business_name', 'first_name', 'last_name', 'email', 'country' ];
	$missing  = [];
	foreach ( $required as $field ) {
		if ( empty( $_POST[ $field ] ) ) {
			$missing[] = $field;
		}
	}
	if ( empty( $missing ) ) {
		$business_name = sanitize_text_field( wp_unslash( $_POST['business_name'] ) );
		$first_name    = sanitize_text_field( wp_unslash( $_POST['first_name'] ) );
		$last_name     = sanitize_text_field( wp_unslash( $_POST['last_name'] ) );
		$email         = sanitize_email( wp_unslash( $_POST['email'] ) );
		$country       = sanitize_text_field( wp_unslash( $_POST['country'] ) );
		$phone_number  = sanitize_text_field( wp_unslash( $_POST['phone_number'] ) );
		$unique_slug   = \ClaimsManagement\generate_unique_slug();
		$password      = wp_generate_password();
		$status        = __( 'New Client', 'claims-management' );

		$user_data = [
			'user_login'   => $unique_slug,
			'user_pass'    => $password,
			'user_email'   => $email,
			'role'         => 'cm_client',
			'display_name' => $business_name,
		];
		$user_id = wp_insert_user( $user_data );
		if ( ! is_wp_error( $user_id ) ) {
			update_user_meta( $user_id, 'cm_business_name', $business_name );
			update_user_meta( $user_id, 'cm_first_name', $first_name );
			update_user_meta( $user_id, 'cm_last_name', $last_name );
			update_user_meta( $user_id, 'cm_country', $country );
			update_user_meta( $user_id, 'cm_phone', $phone_number );
			update_user_meta( $user_id, 'cm_status', $status );
			update_user_meta( $user_id, 'cm_client_slug', $unique_slug );
			// Store the creator's user ID.
			update_user_meta( $user_id, 'cm_created_by', get_current_user_id() );

			echo '<div class="updated"><p>' . esc_html__( 'Client created! Their login details have been sent to their email. Alternatively, they can log in from the Client Portal page.', 'claims-management' ) . '</p></div>';
		}
	}
}

$default_countries = \ClaimsManagement\get_default_countries();
$countries         = get_option( 'cm_countries', $default_countries );

// Determine current user.
$current_user = wp_get_current_user();

// If the current user is a Claims Manager, only show clients created by them.
if ( in_array( 'claims_manager', (array) $current_user->roles, true ) ) {
	$clients = get_users( [
		'role'      => 'cm_client',
		'meta_key'  => 'cm_created_by',
		'meta_value'=> $current_user->ID,
		'orderby'   => 'registered',
		'order'     => 'DESC',
		'number'    => -1,
	] );
} else {
	// Otherwise (for Claims Admin or others), show all clients.
	$clients = get_users( [
		'role'    => 'cm_client',
		'orderby' => 'registered',
		'order'   => 'DESC',
		'number'  => -1,
	] );
}

// Count clients by status.
$new_count          = 0;
$in_progress_count  = 0;
$verification_count = 0;
$finalised_count    = 0;
foreach ( $clients as $client ) {
	$status = get_user_meta( $client->ID, 'cm_status', true );
	if ( $status === __( 'New Client', 'claims-management' ) ) {
		$new_count++;
	} elseif ( $status === __( 'In Progress', 'claims-management' ) ) {
		$in_progress_count++;
	} elseif ( $status === __( 'Verification Needed', 'claims-management' ) ) {
		$verification_count++;
	} elseif ( $status === __( 'Finalised', 'claims-management' ) ) {
		$finalised_count++;
	}
}

?>
<!-- Include Flag Icon CSS from CDN if needed elsewhere -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/css/flag-icon.min.css" />

<div class="wrap">
	<h1><?php esc_html_e( 'Claims Management', 'claims-management' ); ?></h1>
	<button id="toggle-new-client" class="button" style="margin-top: 10px;"><?php esc_html_e( 'New Client', 'claims-management' ); ?></button>
	
	<div id="cm-new-client-form" style="display:none; margin-top: 15px; border: 1px solid #ccc; padding: 15px;">
		<form method="post">
			<?php wp_nonce_field( 'cm_create_client', 'cm_nonce_field' ); ?>
			<h3><?php esc_html_e( 'Create New Client', 'claims-management' ); ?></h3>
			<table class="form-table">
				<tr>
					<th><label for="business_name"><?php esc_html_e( 'Business Name', 'claims-management' ); ?></label></th>
					<td><input type="text" name="business_name" id="business_name" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="first_name"><?php esc_html_e( 'First Name', 'claims-management' ); ?></label></th>
					<td><input type="text" name="first_name" id="first_name" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="last_name"><?php esc_html_e( 'Last Name', 'claims-management' ); ?></label></th>
					<td><input type="text" name="last_name" id="last_name" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="email"><?php esc_html_e( 'Email Address', 'claims-management' ); ?></label></th>
					<td><input type="email" name="email" id="email" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="country"><?php esc_html_e( 'Country', 'claims-management' ); ?></label></th>
					<td>
						<select name="country" id="country" required>
							<?php
							if ( is_array( $countries ) ) {
								foreach ( $countries as $ctry ) {
									echo '<option value="' . esc_attr( $ctry ) . '">' . esc_html( $ctry ) . '</option>';
								}
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="phone_number"><?php esc_html_e( 'Phone Number', 'claims-management' ); ?></label></th>
					<td><input type="text" name="phone_number" id="phone_number" class="regular-text"></td>
				</tr>
			</table>
			<div style="margin-top: 15px;">
				<input type="submit" name="create_client" value="<?php esc_attr_e( 'Save', 'claims-management' ); ?>" class="button" style="background-color: green; color: white; margin-right: 10px;">
				<button type="button" id="cancel-new-client" class="button" style="background-color: red; color: white;"><?php esc_html_e( 'Cancel', 'claims-management' ); ?></button>
			</div>
		</form>
	</div>
	
	<div id="cm-client-table" style="margin-top: 50px; margin-bottom: 20px;">
		<h2><?php esc_html_e( 'Client List', 'claims-management' ); ?></h2>
		<input type="text" id="cm-search" placeholder="<?php esc_attr_e( 'Search Clients...', 'claims-management' ); ?>" style="margin-bottom: 10px; width: 300px;">
		<div id="cm-client-tabs" style="margin-top: 15px; margin-bottom: 15px;">
			<ul style="list-style: none; margin: 0; padding: 0;">
				<li style="display: inline; margin-right: 10px;">
					<a href="#" class="cm-tab active" data-status="New Client">
						<?php printf( esc_html__( 'New Clients (%d)', 'claims-management' ), $new_count ); ?>
					</a>
				</li>
				<li style="display: inline; margin-right: 10px;">
					<a href="#" class="cm-tab" data-status="In Progress">
						<?php printf( esc_html__( 'In Progress (%d)', 'claims-management' ), $in_progress_count ); ?>
					</a>
				</li>
				<li style="display: inline; margin-right: 10px;">
					<a href="#" class="cm-tab" data-status="Verification Needed">
						<?php printf( esc_html__( 'Verification Needed (%d)', 'claims-management' ), $verification_count ); ?>
					</a>
				</li>
				<li style="display: inline; margin-right: 10px;">
					<a href="#" class="cm-tab" data-status="Finalised">
						<?php printf( esc_html__( 'Finalised (%d)', 'claims-management' ), $finalised_count ); ?>
					</a>
				</li>
			</ul>
		</div>
		<?php
		if ( ! empty( $clients ) ) {
			echo '<table class="wp-list-table widefat fixed striped">';
			echo '<thead><tr>';
			echo '<th>' . esc_html__( 'Business Name', 'claims-management' ) . '</th>';
			echo '<th>' . esc_html__( 'Country', 'claims-management' ) . '</th>';
			echo '<th>' . esc_html__( 'Vehicle Count', 'claims-management' ) . '</th>';
			echo '<th>' . esc_html__( 'Created By', 'claims-management' ) . '</th>';
			echo '<th style="text-align: right;">' . esc_html__( 'Actions', 'claims-management' ) . '</th>';
			echo '</tr></thead><tbody>';
			foreach ( $clients as $client ) {
				$client_slug   = get_user_meta( $client->ID, 'cm_client_slug', true );
				$business_name = get_user_meta( $client->ID, 'cm_business_name', true );
				
				// Get client country.
				$client_country = get_user_meta( $client->ID, 'cm_country', true );
				// Use the new helper functions for mapping and flag output.
				$iso_code  = ( strlen( trim( $client_country ) ) === 2 ) ? strtolower( trim( $client_country ) ) : cm_map_country_to_iso( $client_country );
				$flag_html = $iso_code ? cm_get_flag_img( $iso_code, $client_country ) : '';
				
				// Count vehicles for this client.
				$vehicle_count = count_user_posts( $client->ID, 'cm_vehicle' );
				
				// Get the creator's information.
				$creator_id   = get_user_meta( $client->ID, 'cm_created_by', true );
				$creator_name = 'N/A';
				if ( $creator_id ) {
					$creator = get_userdata( $creator_id );
					if ( $creator ) {
						$creator_name = trim( $creator->first_name . ' ' . $creator->last_name );
					}
				}
				
				$status   = get_user_meta( $client->ID, 'cm_status', true );
				$view_url = admin_url( 'admin.php?page=cm_view_claim&client_slug=' . esc_attr( $client_slug ) );
				
				echo '<tr data-status="' . esc_attr( $status ) . '">';
				echo '<td><a href="' . esc_url( $view_url ) . '">' . esc_html( $business_name ) . '</a></td>';
				echo '<td>' . $flag_html . esc_html( $client_country ) . '</td>';
				echo '<td>' . esc_html( $vehicle_count ) . '</td>';
				echo '<td>' . esc_html( $creator_name ) . '</td>';
				echo '<td style="text-align: right;">';
				echo '<button class="cm-send-access button" data-slug="' . esc_attr( $client_slug ) . '">' . esc_html__( 'Send Access', 'claims-management' ) . '</button> ';
				if ( $status === __( 'Verification Needed', 'claims-management' ) ) {
					echo '<a class="button" target="_blank" href="' . esc_url( $view_url ) . '">' . esc_html__( 'View Claim', 'claims-management' ) . '</a> ';
					echo '<button class="cm-approve-claim button" data-slug="' . esc_attr( $client_slug ) . '">' . esc_html__( 'Approve', 'claims-management' ) . '</button> ';
				}
				echo '<button class="cm-delete-client button" data-slug="' . esc_attr( $client_slug ) . '" style="background-color: red; color: white;">' . esc_html__( 'Delete', 'claims-management' ) . '</button>';
				echo '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		} else {
			echo '<p>' . esc_html__( 'No clients found.', 'claims-management' ) . '</p>';
		}
		?>
		<div id="cm-pagination" style="margin-top: 10px;"></div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	var currentPage = 1;
	var rowsPerPage = 20;
	
	function paginateTable() {
		var rows = $('#cm-client-table tbody tr:visible');
		var totalRows = rows.length;
		var totalPages = Math.ceil(totalRows / rowsPerPage);
		if ( currentPage > totalPages ) {
			currentPage = 1;
		}
		rows.hide();
		rows.slice((currentPage - 1) * rowsPerPage, currentPage * rowsPerPage).show();
		var paginationHtml = '';
		if ( totalPages > 1 ) {
			for ( var i = 1; i <= totalPages; i++ ) {
				paginationHtml += '<a href="#" class="cm-pagination-link" data-page="'+i+'">'+i+'</a> ';
			}
		}
		$('#cm-pagination').html(paginationHtml);
		$('#cm-pagination a').each(function(){
			if ($(this).data('page') == currentPage) {
				$(this).css({'font-weight': 'bold'});
			} else {
				$(this).css({'font-weight': 'normal'});
			}
		});
	}
	
	$(document).on('click', '.cm-pagination-link', function(e) {
		e.preventDefault();
		currentPage = $(this).data('page');
		paginateTable();
	});
	
	$('#toggle-new-client').on('click', function(e) {
		e.preventDefault();
		$('#cm-new-client-form').slideToggle();
	});
	$('#cancel-new-client').on('click', function(e) {
		e.preventDefault();
		$('#cm-new-client-form').slideUp();
	});
	
	function filterClientRows(status) {
		$('#cm-client-table tbody tr').each(function() {
			if ($(this).data('status') == status) {
				$(this).show();
			} else {
				$(this).hide();
			}
		});
	}
	filterClientRows('New Client');
	paginateTable();
	$('.cm-tab').on('click', function(e) {
		e.preventDefault();
		currentPage = 1;
		var selectedStatus = $(this).data('status');
		$('.cm-tab').removeClass('active');
		$(this).addClass('active');
		filterClientRows(selectedStatus);
		paginateTable();
	});
	
	// Updated search: check Business Name, Country, Vehicle Count, and Created By columns.
	$('#cm-search').on('keyup', function() {
		currentPage = 1;
		var searchTerm = $(this).val().toLowerCase();
		if ( searchTerm === '' ) {
			$('.cm-tab.active').trigger('click');
		} else {
			$('#cm-client-table tbody tr').each(function() {
				var businessName = $(this).find('td').eq(0).text().toLowerCase();
				var country      = $(this).find('td').eq(1).text().toLowerCase();
				var vehicleCount = $(this).find('td').eq(2).text().toLowerCase();
				var createdBy    = $(this).find('td').eq(3).text().toLowerCase();
				
				if ( businessName.indexOf(searchTerm) > -1 || country.indexOf(searchTerm) > -1 || vehicleCount.indexOf(searchTerm) > -1 || createdBy.indexOf(searchTerm) > -1 ) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
			paginateTable();
		}
	});
	
	$('.cm-delete-client').on('click', function(e) {
		e.preventDefault();
		var clientSlug = $(this).data('slug');
		if ( confirm("Are you sure you want to delete this client? This action is irreversible.") ) {
			$.post(ajaxurl, { action: 'cm_delete_client', client_slug: clientSlug }, function(response) {
				if(response.success) {
					alert("Client deleted.");
					location.reload();
				} else {
					alert("Error deleting client: " + (response.data.message || "Unknown error"));
				}
			});
		}
	});
});
</script>
<style>
	#cm-toast-container { position: fixed; top: 20px; right: 20px; z-index: 10000; }
	.cm-toast-notice { background: #fff; padding: 10px 15px; border-left: 4px solid; margin-bottom: 10px; box-shadow: 0 1px 1px rgba(0,0,0,0.1); font-size: 13px; }
	.cm-toast-success { border-color: #46b450; }
	.cm-toast-error { border-color: #dc3232; }
	#cm-client-tabs ul li a { text-decoration: none; font-weight: normal; }
	#cm-client-tabs ul li a:hover { text-decoration: underline; }
	#cm-client-tabs ul li a.cm-tab.active { font-weight: bold; }
	@media screen and (min-width: 768px) { .cm-send-access { margin-right: 10px; } }
	.flag-icon { margin-right: 5px; }
</style>