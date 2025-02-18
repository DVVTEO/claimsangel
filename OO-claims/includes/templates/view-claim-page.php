<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

$client_slug = isset( $_GET['client_slug'] ) ? sanitize_text_field( wp_unslash( $_GET['client_slug'] ) ) : '';

if ( ! $client_slug ) {
    echo '<div class="wrap"><h1>' . esc_html__( 'Client not found.', 'claims-management' ) . '</h1></div>';
    return;
}

$users = get_users( [
    'meta_key'   => 'cm_client_slug',
    'meta_value' => $client_slug,
    'number'     => 1,
] );

if ( empty( $users ) ) {
    echo '<div class="wrap"><h1>' . esc_html__( 'Client not found.', 'claims-management' ) . '</h1></div>';
    return;
}

$client = $users[0];
?>
<div class="wrap">
    <h1><?php esc_html_e( 'View Claim for', 'claims-management' ); ?> <?php echo esc_html( get_user_meta( $client->ID, 'cm_business_name', true ) ); ?></h1>
    <h2><?php esc_html_e( 'Client Details', 'claims-management' ); ?></h2>
    <ul>
        <li><strong><?php esc_html_e( 'Business Name:', 'claims-management' ); ?></strong> <?php echo esc_html( get_user_meta( $client->ID, 'cm_business_name', true ) ); ?></li>
        <li><strong><?php esc_html_e( 'First Name:', 'claims-management' ); ?></strong> <?php echo esc_html( get_user_meta( $client->ID, 'cm_first_name', true ) ); ?></li>
        <li><strong><?php esc_html_e( 'Last Name:', 'claims-management' ); ?></strong> <?php echo esc_html( get_user_meta( $client->ID, 'cm_last_name', true ) ); ?></li>
        <li><strong><?php esc_html_e( 'Email:', 'claims-management' ); ?></strong> <?php echo esc_html( $client->user_email ); ?></li>
        <li><strong><?php esc_html_e( 'Country:', 'claims-management' ); ?></strong> <?php echo esc_html( get_user_meta( $client->ID, 'cm_country', true ) ); ?></li>
        <li><strong><?php esc_html_e( 'Phone:', 'claims-management' ); ?></strong> <?php echo esc_html( get_user_meta( $client->ID, 'cm_phone', true ) ); ?></li>
        <li><strong><?php esc_html_e( 'Status:', 'claims-management' ); ?></strong> <?php echo esc_html( get_user_meta( $client->ID, 'cm_status', true ) ); ?></li>
    </ul>
    <h2><?php esc_html_e( 'Vehicles', 'claims-management' ); ?></h2>
    <?php
    $vehicle_args = [
        'post_type'      => 'cm_vehicle',
        'posts_per_page' => -1,
        'author'         => $client->ID,
    ];
    $vehicle_query = new \WP_Query( $vehicle_args );
    if ( $vehicle_query->have_posts() ) {
        echo '<ul>';
        while ( $vehicle_query->have_posts() ) {
            $vehicle_query->the_post();
            echo '<li>' . esc_html( get_the_title() ) . ' - ' . esc_html( get_post_meta( get_the_ID(), '_cm_vehicle_status', true ) ) . '</li>';
        }
        echo '</ul>';
        wp_reset_postdata();
    } else {
        echo '<p>' . esc_html__( 'No vehicles found for this client.', 'claims-management' ) . '</p>';
    }
    ?>
</div>