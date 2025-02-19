<?php
// Security check to ensure the file is being accessed through WordPress.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Claims Management Settings', 'claims-management' ); ?></h1>
    <form method="post" action="options.php">
        <?php
        // Output security fields for the registered setting "cm_settings".
        settings_fields( 'cm_settings' );

        // Output setting sections and their fields.
        // Sections and fields are registered with the register_setting() function.
        do_settings_sections( 'cm-settings' );

        // Output save settings button.
        submit_button( __( 'Save Settings', 'claims-management' ) );
        ?>
    </form>
</div>