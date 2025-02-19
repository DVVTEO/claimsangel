<?php
namespace ClaimsManagement;

class Ajax_Handler {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_cm_save_vehicle', [ $this, 'save_vehicle' ] );
		add_action( 'wp_ajax_nopriv_cm_save_vehicle', [ $this, 'save_vehicle' ] );
		add_action( 'wp_ajax_cm_submit_claim', [ $this, 'submit_claim' ] );
		add_action( 'wp_ajax_nopriv_cm_submit_claim', [ $this, 'submit_claim' ] );
		add_action( 'wp_ajax_cm_delete_client', [ $this, 'delete_client' ] );
	}

	/**
	 * Handle vehicle submission via AJAX.
	 */
	public function save_vehicle() {
		if ( ! is_user_logged_in() || ! current_user_can( 'cm_client' ) ) {
			wp_send_json_error( [ 'message' => __( 'Not authenticated.', 'claims-management' ) ] );
			exit;
		}
		if ( empty( $_POST['vehicle_type'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing required fields for vehicle.', 'claims-management' ) ] );
			exit;
		}
		$vehicle_type = sanitize_text_field( wp_unslash( $_POST['vehicle_type'] ) );
		if ( empty( $_FILES['document'] ) || $_FILES['document']['error'] !== 0 ) {
			wp_send_json_error( [ 'message' => __( 'Document is required.', 'claims-management' ) ] );
			exit;
		}
		$upload = wp_handle_upload( $_FILES['document'], [ 'test_form' => false ] );
		if ( isset( $upload['error'] ) ) {
			error_log( 'File upload error: ' . $upload['error'] );
			wp_send_json_error( [ 'message' => __( 'Error uploading document: ', 'claims-management' ) . $upload['error'] ] );
			exit;
		}
		$document_url = $upload['url'];

		$vehicle_data = [
			'post_title'  => $vehicle_type . ' Vehicle',
			'post_type'   => 'cm_vehicle',
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
		];
		$vehicle_id = wp_insert_post( $vehicle_data );
		if ( $vehicle_id && ! is_wp_error( $vehicle_id ) ) {
			update_post_meta( $vehicle_id, '_cm_vehicle_type', $vehicle_type );
			update_post_meta( $vehicle_id, '_cm_document_url', esc_url_raw( $document_url ) );
			update_post_meta( $vehicle_id, '_cm_vehicle_status', __( 'Pending Approval', 'claims-management' ) );
			update_post_meta( $vehicle_id, '_cm_client_id', get_current_user_id() );
		}
		
		// If the current user's status is "New Client", update it to "In Progress"
		// and record the timestamp in 'cm_in_progress_date'.
		$current_status = get_user_meta( get_current_user_id(), 'cm_status', true );
		if ( $current_status === __( 'New Client', 'claims-management' ) ) {
			update_user_meta( get_current_user_id(), 'cm_status', __( 'In Progress', 'claims-management' ) );
			update_user_meta( get_current_user_id(), 'cm_in_progress_date', current_time( 'mysql' ) );
		}
		
		wp_send_json_success();
	}

	/**
	 * Handle claim submission via AJAX.
	 */
	public function submit_claim() {
		if ( ! is_user_logged_in() || ! current_user_can( 'cm_client' ) ) {
			wp_send_json_error( [ 'message' => __( 'Not authenticated.', 'claims-management' ) ] );
			exit;
		}
		$current_status = get_user_meta( get_current_user_id(), 'cm_status', true );
		if ( $current_status === __( 'In Progress', 'claims-management' ) ) {
			// Update status to "Verification Needed" and record the claim submission timestamp.
			update_user_meta( get_current_user_id(), 'cm_status', __( 'Verification Needed', 'claims-management' ) );
			update_user_meta( get_current_user_id(), 'cm_claim_submitted_date', current_time( 'mysql' ) );
			wp_send_json_success();
		} else {
			wp_send_json_error( [ 'message' => __( 'Invalid claim status.', 'claims-management' ) ] );
		}
	}

	/**
	 * Handle client deletion via AJAX.
	 */
	public function delete_client() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Not allowed', 'claims-management' ) ] );
			exit;
		}
		if ( empty( $_POST['client_slug'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Client slug missing.', 'claims-management' ) ] );
			exit;
		}
		$client_slug = sanitize_text_field( wp_unslash( $_POST['client_slug'] ) );
		$users       = get_users( [
			'meta_key'   => 'cm_client_slug',
			'meta_value' => $client_slug,
			'number'     => 1,
		] );
		if ( ! empty( $users ) ) {
			$user = $users[0];
			wp_delete_user( $user->ID );
			wp_send_json_success();
		} else {
			wp_send_json_error( [ 'message' => __( 'Client not found.', 'claims-management' ) ] );
		}
		exit;
	}
}

function update_prospect_field_callback() {
    // Verify nonce.
    check_ajax_referer( 'update_prospect_field', '_ajax_nonce' );

    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    $field   = isset( $_POST['field'] ) ? sanitize_text_field( $_POST['field'] ) : '';
    $value   = isset( $_POST['value'] ) ? sanitize_text_field( $_POST['value'] ) : '';

    if ( ! $post_id || empty( $field ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid parameters.', 'claims-management' ) ] );
    }

    // Update the specified field.
    update_post_meta( $post_id, $field, $value );

    // If the field being updated is the web address and a reset is requested,
    // clear the stored screenshot URL so that it gets regenerated.
    if ( 'cm_web_address' === $field && isset( $_POST['reset_screenshot'] ) && 'true' === $_POST['reset_screenshot'] ) {
        update_post_meta( $post_id, 'cm_screenshot_url', '' );
    }

    wp_send_json_success( [ 'message' => __( 'Field updated.', 'claims-management' ) ] );
}
add_action( 'wp_ajax_update_prospect_field', __NAMESPACE__ . '\\update_prospect_field_callback' );