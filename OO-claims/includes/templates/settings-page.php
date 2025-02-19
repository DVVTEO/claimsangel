<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
// Display success message only for our custom parameter.
if ( isset( $_GET['cm_settings_updated'] ) && $_GET['cm_settings_updated'] ) {
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings have been saved successfully.', 'claims-management' ) . '</p></div>';
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Claims Management Settings', 'claims-management' ); ?></h1>
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php
        // Add the nonce field.
        wp_nonce_field( 'cm_save_settings', 'cm_settings_nonce_field' );
        ?>
        <!-- Hidden action field to trigger our custom handler -->
        <input type="hidden" name="action" value="save_cm_settings">
        
        <?php
        // Render settings sections and fields.
        do_settings_sections( 'cm-settings' );
        submit_button();
        ?>
    </form>
</div>