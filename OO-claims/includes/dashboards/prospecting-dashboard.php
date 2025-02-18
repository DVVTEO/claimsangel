<?php
/**
 * Plugin Name: Second Dashboard Submenu
 * Description: Adds a second dashboard as a submenu under the native Dashboard with its own widgets.
 * Version: 1.0
 * Author: Your Name
 */

function sd_register_second_dashboard_submenu() {
    add_submenu_page(
        'index.php',                                  // Parent slug (native Dashboard)
        __( 'Prospecting', 'second-dashboard' ), // Page title (displayed in the header)
        __( 'Prospecting', 'second-dashboard' ), // Menu title (displayed in the Dashboard submenu)
        'read',                                       // Capability required
        'prospecting-dashboard',                           // Menu slug
        'sd_render_second_dashboard'                  // Callback function to render the page
    );
}
add_action( 'admin_menu', 'sd_register_second_dashboard_submenu' );

/**
 * Register meta boxes (widgets) for the second dashboard.
 * These meta boxes will only appear on our custom screen.
 */
function sd_add_dashboard_meta_boxes() {
    add_meta_box(
        'sd_widget_1',
        __( 'Widget 1', 'second-dashboard' ),
        'sd_widget_1_callback',
        'second_dashboard', // Screen ID for our custom dashboard
        'normal'
    );
    add_meta_box(
        'sd_widget_2',
        __( 'Widget 2', 'second-dashboard' ),
        'sd_widget_2_callback',
        'second_dashboard',
        'side'
    );
}
add_action( 'add_meta_boxes', 'sd_add_dashboard_meta_boxes' );

/**
 * Render the custom second dashboard page.
 */
function sd_render_second_dashboard() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Second Dashboard', 'second-dashboard' ); ?></h1>
        <div id="dashboard-widgets" class="metabox-holder">
            <div id="postbox-container-1" class="postbox-container">
                <?php do_meta_boxes( 'second_dashboard', 'side', null ); ?>
            </div>
            <div id="postbox-container-2" class="postbox-container">
                <?php do_meta_boxes( 'second_dashboard', 'normal', null ); ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Callback for Widget 1.
 */
function sd_widget_1_callback() {
    echo '<p>' . esc_html__( 'Content for Widget 1 goes here.', 'second-dashboard' ) . '</p>';
}

/**
 * Callback for Widget 2.
 */
function sd_widget_2_callback() {
    echo '<p>' . esc_html__( 'Content for Widget 2 goes here.', 'second-dashboard' ) . '</p>';
}