<?php
/**
 * Dashboard Widgets and Customization for Claims Management.
 *
 * This file contains all code related to customizing the WordPress Dashboard:
 * - Removing default widgets.
 * - Registering custom dashboard widgets (such as Global Claims Metrics, Performance By Claims Manager, and Performance By Country).
 * - Enqueuing custom dashboard styles and scripts.
 * - Enforcing a one‐column layout.
 *
 * @package ClaimsManagement
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Force a 1-column layout on the Dashboard.
 */
function cm_set_dashboard_columns( $columns, $screen ) {
	if ( 'dashboard' === $screen->id ) {
		$columns[$screen->id] = 1;
	}
	return $columns;
}
add_filter( 'screen_layout_columns', 'cm_set_dashboard_columns', 10, 2 );

/**
 * Global Date Range Filter for the Dashboard.
 */
function cm_global_date_filter_notice() {
	if ( ! current_user_can( 'claims_manager' ) && ! current_user_can( 'claims_admin' ) && ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! is_object( $screen ) || $screen->base !== 'dashboard' ) {
		return;
	}
	$start_date = isset( $_GET['cm_global_start_date'] ) ? sanitize_text_field( $_GET['cm_global_start_date'] ) : '';
	$end_date   = isset( $_GET['cm_global_end_date'] ) ? sanitize_text_field( $_GET['cm_global_end_date'] ) : '';
	?>
	<div class="notice notice-info">
		<form method="get" style="display: flex; align-items: center;">
			<div style="margin-right: 10px;">
				<label for="cm_global_start_date"><?php esc_html_e( 'Start Date:', 'claims-management' ); ?></label>
				<input type="date" name="cm_global_start_date" id="cm_global_start_date" value="<?php echo esc_attr( $start_date ); ?>">
			</div>
			<div style="margin-right: 10px;">
				<label for="cm_global_end_date"><?php esc_html_e( 'End Date:', 'claims-management' ); ?></label>
				<input type="date" name="cm_global_end_date" id="cm_global_end_date" value="<?php echo esc_attr( $end_date ); ?>">
			</div>
			<input type="submit" class="button" value="<?php esc_attr_e( 'Apply Global Filter', 'claims-management' ); ?>">
		</form>
	</div>
	<?php
}
add_action( 'admin_notices', 'cm_global_date_filter_notice' );

/**
 * Remove the default "WordPress Events & News" widget.
 */
function cm_remove_dashboard_primary_widget() {
	global $wp_meta_boxes;
	if ( isset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_primary'] ) ) {
		unset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_primary'] );
	}
}
add_action( 'wp_dashboard_setup', 'cm_remove_dashboard_primary_widget', 9999 );

/**
 * Remove other default dashboard widgets.
 */
function cm_remove_default_dashboard_widgets() {
    remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_primary', 'dashboard', 'normal' );
}
add_action( 'wp_dashboard_setup', 'cm_remove_default_dashboard_widgets', 999 );

/**
 * Register a container widget for Global Claims Metrics.
 */
function cm_register_global_claims_metrics_widget() {
	wp_add_dashboard_widget(
		'cm_global_claims_metrics_widget',
		__( 'Global Claims Metrics', 'claims-management' ),
		'cm_global_claims_metrics_widget_callback'
	);
}
add_action( 'wp_dashboard_setup', 'cm_register_global_claims_metrics_widget' );

/**
 * Dashboard widget callback: Outputs a container with four metric cards.
 */
function cm_global_claims_metrics_widget_callback() {
	$start_date = isset( $_GET['cm_global_start_date'] ) ? sanitize_text_field( $_GET['cm_global_start_date'] ) : '';
	$end_date   = isset( $_GET['cm_global_end_date'] ) ? sanitize_text_field( $_GET['cm_global_end_date'] ) : '';

	$args_new = [ 'role' => 'cm_client' ];
	if ( $start_date || $end_date ) {
		$args_new['date_query'] = [];
		if ( $start_date ) {
			$args_new['date_query'][] = [
				'column'    => 'user_registered',
				'after'     => $start_date,
				'inclusive' => true,
			];
		}
		if ( $end_date ) {
			$args_new['date_query'][] = [
				'column'    => 'user_registered',
				'before'    => $end_date,
				'inclusive' => true,
			];
		}
	}
	$user_query_new = new WP_User_Query( $args_new );
	$new_clients_count = count( $user_query_new->get_results() );

	$meta_query_in_progress = [
		[
			'key'     => 'cm_status',
			'value'   => 'In Progress',
			'compare' => '='
		]
	];
	if ( $start_date || $end_date ) {
		$in_progress_start_date = $start_date ? $start_date : '1970-01-01';
		$in_progress_end_date   = $end_date ? $end_date : date( 'Y-m-d' );
		$meta_query_in_progress[] = [
			'key'     => 'cm_in_progress_date',
			'value'   => [ $in_progress_start_date, $in_progress_end_date ],
			'compare' => 'BETWEEN',
			'type'    => 'DATETIME'
		];
	}
	$args_in_progress = [
		'role'       => 'cm_client',
		'meta_query' => $meta_query_in_progress,
		'fields'     => 'ID',
	];
	$user_query_in_progress = new WP_User_Query( $args_in_progress );
	$in_progress_count = count( $user_query_in_progress->get_results() );

	$meta_query_submitted = [
		[
			'key'     => 'cm_status',
			'value'   => 'Verification Needed',
			'compare' => '='
		]
	];
	if ( $start_date || $end_date ) {
		$submitted_start_date = $start_date ? $start_date : '1970-01-01';
		$submitted_end_date   = $end_date ? $end_date : date( 'Y-m-d' );
		$meta_query_submitted[] = [
			'key'     => 'cm_claim_submitted_date',
			'value'   => [ $submitted_start_date, $submitted_end_date ],
			'compare' => 'BETWEEN',
			'type'    => 'DATETIME'
		];
	}
	$args_submitted = [
		'role'       => 'cm_client',
		'meta_query' => $meta_query_submitted,
		'fields'     => 'ID',
	];
	$user_query_submitted = new WP_User_Query( $args_submitted );
	$submitted_count = count( $user_query_submitted->get_results() );

	$meta_query_finalised = [
		[
			'key'     => 'cm_status',
			'value'   => 'finalised',
			'compare' => '='
		]
	];
	if ( $start_date || $end_date ) {
		$finalised_start_date = $start_date ? $start_date : '1970-01-01';
		$finalised_end_date   = $end_date ? $end_date : date( 'Y-m-d' );
		$meta_query_finalised[] = [
			'key'     => 'cm_claim_finalised_date',
			'value'   => [ $finalised_start_date, $finalised_end_date ],
			'compare' => 'BETWEEN',
			'type'    => 'DATETIME'
		];
	}
	$args_finalised = [
		'role'       => 'cm_client',
		'meta_query' => $meta_query_finalised,
		'fields'     => 'ID',
	];
	$user_query_finalised = new WP_User_Query( $args_finalised );
	$finalised_count = count( $user_query_finalised->get_results() );
	?>
	<style>
		#cm_global_claims_metrics_widget .inside {
			box-sizing: border-box;
			padding: 10px;
			width: 100%;
		}
		.cm-global-metrics-container {
			display: flex;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 20px;
			width: 100%;
			box-sizing: border-box;
		}
		.cm-metric-card {
			flex: 1 1 calc(25% - 20px);
			min-width: 200px;
			padding: 15px;
			background: #fff;
			border: 1px solid #ddd;
			border-radius: 4px;
			text-align: center;
			box-sizing: border-box;
		}
		.cm-metric-card h2 {
			font-size: 18px;
			margin-bottom: 5px;
		}
		.cm-metric-value {
			font-size: 42px;
			font-weight: bold;
			margin: 5px 0;
		}
		@media screen and (max-width: 768px) {
			.cm-metric-card {
				flex: 1 1 100%;
			}
		}
	</style>
	<div class="cm-global-metrics-container">
		<div class="cm-metric-card">
			<h2><?php esc_html_e( 'New Clients', 'claims-management' ); ?></h2>
			<div class="cm-metric-value"><?php echo esc_html( $new_clients_count ); ?></div>
		</div>
		<div class="cm-metric-card">
			<h2><?php esc_html_e( 'Getting Started', 'claims-management' ); ?></h2>
			<div class="cm-metric-value"><?php echo esc_html( $in_progress_count ); ?></div>
		</div>
		<div class="cm-metric-card">
			<h2><?php esc_html_e( 'Submitted', 'claims-management' ); ?></h2>
			<div class="cm-metric-value"><?php echo esc_html( $submitted_count ); ?></div>
		</div>
		<div class="cm-metric-card">
			<h2><?php esc_html_e( 'Finalised', 'claims-management' ); ?></h2>
			<div class="cm-metric-value"><?php echo esc_html( $finalised_count ); ?></div>
		</div>
	</div>
	<?php
}
add_action( 'wp_dashboard_setup', 'cm_register_global_claims_metrics_widget' );

/**
 * Custom Dashboard Styles to force our widget container to span full width.
 */
function cm_custom_dashboard_widget_styles() {
	?>
	<style>
		#dashboard-widgets .postbox-container { 
			width: 100% !important;
		}
		#cm_global_claims_metrics_widget .inside { 
			width: 100% !important;
			box-sizing: border-box;
			padding: 10px !important;
		}
	</style>
	<?php
}
add_action( 'admin_head', 'cm_custom_dashboard_widget_styles' );

/**
 * Disable drag-and-drop dashboard widget reordering by deregistering the postbox script.
 */
function disable_drag_metabox() {
	wp_deregister_script('postbox');
}
add_action( 'admin_init', 'disable_drag_metabox' );

function cm_fix_dashboard_widgets_width() {
	?>
	<style>
		#dashboard-widgets.metabox-holder {
			max-width: 100% !important;
			width: 100% !important;
			box-sizing: border-box;
			overflow: hidden;
		}
	</style>
	<?php
}
add_action( 'admin_head', 'cm_fix_dashboard_widgets_width' );

/**
 * Hide the widget ordering and toggle icons.
 */
function cm_hide_widget_icons() {
	?>
	<style>
		#dashboard-widgets .handle-order-higher,
		#dashboard-widgets .handle-order-lower,
		#dashboard-widgets .handlediv {
			display: none !important;
		}
	</style>
	<?php
}
add_action( 'admin_head', 'cm_hide_widget_icons' );

/**
 * Register a dashboard widget for Performance By Claims Manager.
 */
function cm_register_performance_by_claims_manager_widget() {
	wp_add_dashboard_widget(
		'cm_performance_by_claims_manager_widget',
		__( 'Performance By Claims Manager', 'claims-management' ),
		'cm_performance_by_claims_manager_widget_callback'
	);
}
add_action( 'wp_dashboard_setup', 'cm_register_performance_by_claims_manager_widget' );

/**
 * Dashboard widget callback for Performance By Claims Manager.
 */
function cm_performance_by_claims_manager_widget_callback() {
	$start_date = isset( $_GET['cm_global_start_date'] ) ? sanitize_text_field( $_GET['cm_global_start_date'] ) : '';
	$end_date   = isset( $_GET['cm_global_end_date'] ) ? sanitize_text_field( $_GET['cm_global_end_date'] ) : '';

	global $wpdb;
	$creator_ids = $wpdb->get_col( "SELECT DISTINCT meta_value FROM $wpdb->usermeta WHERE meta_key = 'cm_created_by'" );
	
	if ( empty( $creator_ids ) ) {
		echo '<p>' . esc_html__( 'No creator data found.', 'claims-management' ) . '</p>';
		return;
	}
	?>
	<style>
		.cm-breakdown-table {
			width: 100%;
			border-collapse: collapse;
		}
		.cm-breakdown-table th,
		.cm-breakdown-table td {
			border: 1px solid #ddd;
			padding: 8px;
			text-align: center;
		}
		.cm-breakdown-table th {
			background: #f7f7f7;
		}
	</style>
	<table class="cm-breakdown-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Claims Manager', 'claims-management' ); ?></th>
				<th><?php esc_html_e( 'New Clients', 'claims-management' ); ?></th>
				<th><?php esc_html_e( 'Getting Started', 'claims-management' ); ?></th>
				<th><?php esc_html_e( 'Submitted', 'claims-management' ); ?></th>
				<th><?php esc_html_e( 'Finalised', 'claims-management' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $creator_ids as $creator_id ) {
			$creator = get_user_by( 'ID', $creator_id );
			if ( ! $creator ) {
				continue;
			}

			$args_new = [
				'role'       => 'cm_client',
				'meta_query' => [
					[
						'key'     => 'cm_created_by',
						'value'   => $creator_id,
						'compare' => '='
					]
				]
			];
			if ( $start_date || $end_date ) {
				$args_new['date_query'] = [];
				if ( $start_date ) {
					$args_new['date_query'][] = [
						'column'    => 'user_registered',
						'after'     => $start_date,
						'inclusive' => true,
					];
				}
				if ( $end_date ) {
					$args_new['date_query'][] = [
						'column'    => 'user_registered',
						'before'    => $end_date,
						'inclusive' => true,
					];
				}
			}
			$query_new = new WP_User_Query( $args_new );
			$new_count = count( $query_new->get_results() );

			$meta_query_in_progress = [
				[
					'key'     => 'cm_status',
					'value'   => 'In Progress',
					'compare' => '='
				],
				[
					'key'     => 'cm_created_by',
					'value'   => $creator_id,
					'compare' => '='
				]
			];
			if ( $start_date || $end_date ) {
				$in_progress_start_date = $start_date ? $start_date : '1970-01-01';
				$in_progress_end_date   = $end_date ? $end_date : date( 'Y-m-d' );
				$meta_query_in_progress[] = [
					'key'     => 'cm_in_progress_date',
					'value'   => [ $in_progress_start_date, $in_progress_end_date ],
					'compare' => 'BETWEEN',
					'type'    => 'DATETIME'
				];
			}
			$args_in_progress = [
				'role'       => 'cm_client',
				'meta_query' => $meta_query_in_progress,
				'fields'     => 'ID'
			];
			$query_in_progress = new WP_User_Query( $args_in_progress );
			$in_progress_count = count( $query_in_progress->get_results() );

			$meta_query_submitted = [
				[
					'key'     => 'cm_status',
					'value'   => 'Verification Needed',
					'compare' => '='
				],
				[
					'key'     => 'cm_created_by',
					'value'   => $creator_id,
					'compare' => '='
				]
			];
			if ( $start_date || $end_date ) {
				$submitted_start_date = $start_date ? $start_date : '1970-01-01';
				$submitted_end_date   = $end_date ? $end_date : date( 'Y-m-d' );
				$meta_query_submitted[] = [
					'key'     => 'cm_claim_submitted_date',
					'value'   => [ $submitted_start_date, $submitted_end_date ],
					'compare' => 'BETWEEN',
					'type'    => 'DATETIME'
				];
			}
			$args_submitted = [
				'role'       => 'cm_client',
				'meta_query' => $meta_query_submitted,
				'fields'     => 'ID'
			];
			$query_submitted = new WP_User_Query( $args_submitted );
			$submitted_count = count( $query_submitted->get_results() );

			$meta_query_finalised = [
				[
					'key'     => 'cm_status',
					'value'   => 'finalised',
					'compare' => '='
				],
				[
					'key'     => 'cm_created_by',
					'value'   => $creator_id,
					'compare' => '='
				]
			];
			if ( $start_date || $end_date ) {
				$finalised_start_date = $start_date ? $start_date : '1970-01-01';
				$finalised_end_date   = $end_date ? $end_date : date( 'Y-m-d' );
				$meta_query_finalised[] = [
					'key'     => 'cm_claim_finalised_date',
					'value'   => [ $finalised_start_date, $finalised_end_date ],
					'compare' => 'BETWEEN',
					'type'    => 'DATETIME'
				];
			}
			$args_finalised = [
				'role'       => 'cm_client',
				'meta_query' => $meta_query_finalised,
				'fields'     => 'ID'
			];
			$query_finalised = new WP_User_Query( $args_finalised );
			$finalised_count = count( $query_finalised->get_results() );

			echo '<tr>';
			echo '<td>' . esc_html( $creator->display_name ) . '</td>';
			echo '<td>' . esc_html( $new_count ) . '</td>';
			echo '<td>' . esc_html( $in_progress_count ) . '</td>';
			echo '<td>' . esc_html( $submitted_count ) . '</td>';
			echo '<td>' . esc_html( $finalised_count ) . '</td>';
			echo '</tr>';
		}
		?>
		</tbody>
	</table>
	<?php
}

/**
 * Register a new dashboard widget for Performance By Country.
 */
function cm_register_metrics_by_country_widget() {
	wp_add_dashboard_widget(
		'cm_metrics_by_country_widget',
		__( 'Performance By Country', 'claims-management' ),
		'cm_metrics_by_country_widget_callback'
	);
}
add_action( 'wp_dashboard_setup', 'cm_register_metrics_by_country_widget' );

/**
 * Dashboard widget callback for Performance By Country.
 */
function cm_metrics_by_country_widget_callback() {
	$start_date = isset( $_GET['cm_global_start_date'] ) ? sanitize_text_field( $_GET['cm_global_start_date'] ) : '';
	$end_date   = isset( $_GET['cm_global_end_date'] ) ? sanitize_text_field( $_GET['cm_global_end_date'] ) : '';

	global $wpdb;
	$countries = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_value FROM $wpdb->usermeta WHERE meta_key = %s", 'cm_country' ) );
	
	if ( empty( $countries ) ) {
		echo '<p>' . esc_html__( 'No country data found.', 'claims-management' ) . '</p>';
		return;
	}
	?>
	<style>
		.cm-breakdown-table {
			width: 100%;
			border-collapse: collapse;
		}
		.cm-breakdown-table th,
		.cm-breakdown-table td {
			border: 1px solid #ddd;
			padding: 8px;
			text-align: center;
		}
		.cm-breakdown-table th {
			background: #f7f7f7;
		}
		.cm-breakdown-table img {
			vertical-align: middle;
		}
	</style>
	<table class="cm-breakdown-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Country', 'claims-management' ); ?></th>
				<th><?php esc_html_e( 'New Clients', 'claims-management' ); ?></th>
				<th><?php esc_html_e( 'Getting Started', 'claims-management' ); ?></th>
				<th><?php esc_html_e( 'Submitted', 'claims-management' ); ?></th>
				<th><?php esc_html_e( 'Finalised', 'claims-management' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $countries as $country ) {

			$args_new = [
				'role'       => 'cm_client',
				'meta_query' => [
					[
						'key'     => 'cm_country',
						'value'   => $country,
						'compare' => '='
					]
				]
			];
			if ( $start_date || $end_date ) {
				$args_new['date_query'] = [];
				if ( $start_date ) {
					$args_new['date_query'][] = [
						'column'    => 'user_registered',
						'after'     => $start_date,
						'inclusive' => true,
					];
				}
				if ( $end_date ) {
					$args_new['date_query'][] = [
						'column'    => 'user_registered',
						'before'    => $end_date,
						'inclusive' => true,
					];
				}
			}
			$query_new = new WP_User_Query( $args_new );
			$new_count = count( $query_new->get_results() );

			$meta_query_in_progress = [
				[
					'key'     => 'cm_status',
					'value'   => 'In Progress',
					'compare' => '='
				],
				[
					'key'     => 'cm_country',
					'value'   => $country,
					'compare' => '='
				]
			];
			if ( $start_date || $end_date ) {
				$in_progress_start_date = $start_date ? $start_date : '1970-01-01';
				$in_progress_end_date   = $end_date ? $end_date : date( 'Y-m-d' );
				$meta_query_in_progress[] = [
					'key'     => 'cm_in_progress_date',
					'value'   => [ $in_progress_start_date, $in_progress_end_date ],
					'compare' => 'BETWEEN',
					'type'    => 'DATETIME'
				];
			}
			$args_in_progress = [
				'role'       => 'cm_client',
				'meta_query' => $meta_query_in_progress,
				'fields'     => 'ID'
			];
			$query_in_progress = new WP_User_Query( $args_in_progress );
			$in_progress_count = count( $query_in_progress->get_results() );

			$meta_query_submitted = [
				[
					'key'     => 'cm_status',
					'value'   => 'Verification Needed',
					'compare' => '='
				],
				[
					'key'     => 'cm_country',
					'value'   => $country,
					'compare' => '='
				]
			];
			if ( $start_date || $end_date ) {
				$submitted_start_date = $start_date ? $start_date : '1970-01-01';
				$submitted_end_date   = $end_date ? $end_date : date( 'Y-m-d' );
				$meta_query_submitted[] = [
					'key'     => 'cm_claim_submitted_date',
					'value'   => [ $submitted_start_date, $submitted_end_date ],
					'compare' => 'BETWEEN',
					'type'    => 'DATETIME'
				];
			}
			$args_submitted = [
				'role'       => 'cm_client',
				'meta_query' => $meta_query_submitted,
				'fields'     => 'ID'
			];
			$query_submitted = new WP_User_Query( $args_submitted );
			$submitted_count = count( $query_submitted->get_results() );

			$meta_query_finalised = [
				[
					'key'     => 'cm_status',
					'value'   => 'finalised',
					'compare' => '='
				],
				[
					'key'     => 'cm_country',
					'value'   => $country,
					'compare' => '='
				]
			];
			if ( $start_date || $end_date ) {
				$finalised_start_date = $start_date ? $start_date : '1970-01-01';
				$finalised_end_date   = $end_date ? $end_date : date( 'Y-m-d' );
				$meta_query_finalised[] = [
					'key'     => 'cm_claim_finalised_date',
					'value'   => [ $finalised_start_date, $finalised_end_date ],
					'compare' => 'BETWEEN',
					'type'    => 'DATETIME'
				];
			}
			$args_finalised = [
				'role'       => 'cm_client',
				'meta_query' => $meta_query_finalised,
				'fields'     => 'ID'
			];
			$query_finalised = new WP_User_Query( $args_finalised );
			$finalised_count = count( $query_finalised->get_results() );

			$iso_code  = ( strlen( trim( $country ) ) === 2 ) ? strtolower( trim( $country ) ) : cm_map_country_to_iso( $country );
			$flag_html = $iso_code ? cm_get_flag_img( $iso_code, $country ) : '';

			echo '<tr>';
			echo '<td>' . $flag_html . esc_html( $country ) . '</td>';
			echo '<td>' . esc_html( $new_count ) . '</td>';
			echo '<td>' . esc_html( $in_progress_count ) . '</td>';
			echo '<td>' . esc_html( $submitted_count ) . '</td>';
			echo '<td>' . esc_html( $finalised_count ) . '</td>';
			echo '</tr>';
		}
		?>
		</tbody>
	</table>
	<?php
}

// Output the table ordering script in admin_footer to ensure jQuery is loaded.
add_action('admin_footer', 'cm_table_sorting_inline_script');
function cm_table_sorting_inline_script() {
	?>
	<script>
	jQuery(document).ready(function($) {
	    console.log('Sorting script loaded');
	    // Function to sort table rows based on column index.
	    function sortTable(table, colIndex, ascending) {
	        var tbody = table.find('tbody');
	        var rows = tbody.find('tr').get();

	        rows.sort(function(a, b) {
	            var A = $(a).children('td').eq(colIndex).text().trim();
	            var B = $(b).children('td').eq(colIndex).text().trim();
	            var numA = parseFloat(A.replace(/,/g, ''));
	            var numB = parseFloat(B.replace(/,/g, ''));
	            if (!isNaN(numA) && !isNaN(numB)) {
	                A = numA;
	                B = numB;
	            }
	            if (A < B) {
	                return ascending ? -1 : 1;
	            }
	            if (A > B) {
	                return ascending ? 1 : -1;
	            }
	            return 0;
	        });

	        $.each(rows, function(index, row) {
	            tbody.append(row);
	        });
	    }

	    // Attach click events to headers in tables with class "cm-breakdown-table".
	    $('.cm-breakdown-table thead th').css('cursor', 'pointer').on('click', function() {
	        var th = $(this);
	        var table = th.closest('table');
	        var colIndex = th.index();
	        var ascending = !th.hasClass('sorted-asc');

	        table.find('thead th').removeClass('sorted-asc sorted-desc');
	        if (ascending) {
	            th.addClass('sorted-asc');
	        } else {
	            th.addClass('sorted-desc');
	        }
	        sortTable(table, colIndex, ascending);
	    });
	});
	</script>
	<style>
	.sorted-asc:after {
	    content: " ▲";
	}
	.sorted-desc:after {
	    content: " ▼";
	}
	</style>
	<?php
}