<?php
namespace ClaimsManagement;

if ( ! class_exists( __NAMESPACE__ . '\\ProspectProfilePage' ) ) {

    class ProspectProfilePage {

        /**
         * Constructor.
         */
        public function __construct() {
            add_action( 'admin_menu', [ $this, 'register_prospect_profile_page' ] );
        }

        /**
         * Register the "View Prospect" page as a submenu under "Upload Prospects."
         */
        public function register_prospect_profile_page() {
            add_submenu_page(
                'upload-prospects',                                  // Parent slug
                __( 'View Prospect', 'claims-management' ),          // Page title (displayed in header)
                __( 'View Prospect', 'claims-management' ),          // Menu title
                'read',                                              // Capability required
                'cm_view_prospect',                                  // Menu slug
                [ $this, 'render_prospect_profile' ]                 // Callback function
            );
        }

        /**
         * Render the prospect profile page.
         */
        public function render_prospect_profile() {
            if ( ! current_user_can( 'read' ) ) {
                wp_die( esc_html__( 'Insufficient permissions.', 'claims-management' ) );
            }
            $post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;
            if ( ! $post_id ) {
                echo '<div class="wrap"><h1>' . esc_html__( 'No Prospect Selected', 'claims-management' ) . '</h1></div>';
                return;
            }
            $post = get_post( $post_id );
            if ( ! $post || 'cm_prospect' !== $post->post_type ) {
                echo '<div class="wrap"><h1>' . esc_html__( 'Invalid Prospect', 'claims-management' ) . '</h1></div>';
                return;
            }
            $business_name = get_post_meta( $post_id, 'cm_business_name', true );
            $web_address   = get_post_meta( $post_id, 'cm_web_address', true );
            $phone         = get_post_meta( $post_id, 'cm_phone', true );
            $country       = get_post_meta( $post_id, 'cm_country', true );

            // (Screenshot code remains even if unused)
            $screenshot_url = get_post_meta( $post_id, 'cm_screenshot_url', true );
            if ( empty( $screenshot_url ) && ! empty( $web_address ) ) {
                $api_url = 'https://api.capturescreenshotapi.com/take?'
                    . 'access_key=15LECCfT96s'
                    . '&url=' . urlencode( $web_address )
                    . '&viewport_width=1280'
                    . '&viewport_height=720'
                    . '&device_scale_factor=1'
                    . '&format=png'
                    . '&full_page=false'
                    . '&scroll=false';
                $response = wp_remote_get( $api_url );
                if ( ! is_wp_error( $response ) ) {
                    $body = wp_remote_retrieve_body( $response );
                    if ( ! empty( $body ) ) {
                        $upload = wp_upload_bits( 'prospect-screenshot-' . $post_id . '.png', null, $body );
                        if ( empty( $upload['error'] ) ) {
                            $screenshot_url = $upload['url'];
                            update_post_meta( $post_id, 'cm_screenshot_url', $screenshot_url );
                        }
                    }
                }
            }
            
            // Retrieve key people, call logs, and callback notifications.
            $key_people = get_post_meta( $post_id, 'cm_key_people', true );
            if ( ! is_array( $key_people ) ) { $key_people = array(); }
            $call_logs = get_post_meta( $post_id, 'cm_logged_calls', true );
            if ( ! is_array( $call_logs ) ) { $call_logs = array(); }
            $callbacks = get_post_meta( $post_id, 'cm_callback_notifications', true );
            if ( ! is_array( $callbacks ) ) { $callbacks = array(); }
            ?>
            <div class="wrap">
                <!-- Page Header -->
                <div class="page-header" style="display: flex; align-items: center; justify-content: space-between;">
                    <h1 style="margin: 0;"><?php echo esc_html( $post->post_title ); ?></h1>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=upload-prospects' ) ); ?>" class="button"><?php esc_html_e( 'Go Back', 'claims-management' ); ?></a>
                </div>
                <!-- Horizontal Button Row (Left aligned, fixed width 160px each) -->
                <div class="button-row" style="display: flex; gap: 10px; justify-content: flex-start; margin: 20px 0;">
                    <button type="button" id="log-call-btn" class="btn btn-primary" style="width: 160px;">Log Call</button>
                    <button type="button" id="call-back-btn" class="btn btn-warning" style="width: 160px;">Call Back</button>
                    <button type="button" class="btn btn-orange" style="width: 160px;">Archive</button>
                    <button type="button" class="btn btn-success" style="width: 160px;">Start Claim</button>
                </div>
                <!-- Main Content: Tabs, Callback Reminders, and Call Log History -->
                <div style="display: flex; gap: 20px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <div id="tabs">
                            <ul>
                                <li><a href="#tab-business-details"><?php esc_html_e( 'Business Details', 'claims-management' ); ?></a></li>
                                <li><a href="#tab-key-people"><?php esc_html_e( 'Key People', 'claims-management' ); ?></a></li>
                                <li><a href="#tab-general-notes"><?php esc_html_e( 'General Notes', 'claims-management' ); ?></a></li>
                            </ul>
                            <div id="tab-business-details">
                                <!-- Header Row with Business Details Title and Edit Details Button -->
                                <div class="business-details-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                    <h2 style="margin: 0;"><?php esc_html_e( 'Business Details', 'claims-management' ); ?></h2>
                                    <button id="edit-details-tab-btn" class="button"><?php esc_html_e( 'Edit Details', 'claims-management' ); ?></button>
                                </div>
                                <!-- Business Details Table -->
                                <div class="responsive-table">
                                    <table class="wp-list-table widefat fixed striped" id="prospect-profile-table">
                                        <tr>
                                            <th>
                                                <span class="dashicons dashicons-businessman" style="margin-right:5px;vertical-align:middle;"></span>
                                                <?php esc_html_e( 'Business Name', 'claims-management' ); ?>
                                            </th>
                                            <td><?php echo esc_html( $business_name ); ?></td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <span class="dashicons dashicons-admin-site" style="margin-right:5px;vertical-align:middle;"></span>
                                                <?php esc_html_e( 'Web Address', 'claims-management' ); ?>
                                            </th>
                                            <td><?php echo $web_address ? '<a href="' . esc_url( $web_address ) . '" target="_blank">' . esc_html( $web_address ) . '</a>' : esc_html__( 'N/A', 'claims-management' ); ?></td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <span class="dashicons dashicons-phone" style="margin-right:5px;vertical-align:middle;"></span>
                                                <?php esc_html_e( 'Phone', 'claims-management' ); ?>
                                            </th>
                                            <td><?php echo esc_html( $phone ); ?></td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <span class="dashicons dashicons-flag" style="margin-right:5px;vertical-align:middle;"></span>
                                                <?php esc_html_e( 'Country', 'claims-management' ); ?>
                                            </th>
                                            <td><?php echo esc_html( $country ); ?></td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <span class="dashicons dashicons-calendar" style="margin-right:5px;vertical-align:middle;"></span>
                                                <?php esc_html_e( 'Date Created', 'claims-management' ); ?>
                                            </th>
                                            <td><?php echo get_the_date( '', $post_id ); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <!-- Non-Editable Key People Table -->
                                <h2 style="margin-top: 30px;"><?php esc_html_e( 'Key People', 'claims-management' ); ?></h2>
                                <?php if ( ! empty( $key_people ) ) : ?>
                                    <div class="responsive-table">
                                        <table class="wp-list-table widefat fixed striped">
                                            <thead>
                                                <tr>
                                                    <th><?php esc_html_e( 'Name', 'claims-management' ); ?></th>
                                                    <th><?php esc_html_e( 'Title', 'claims-management' ); ?></th>
                                                    <th><?php esc_html_e( 'Email Address', 'claims-management' ); ?></th>
                                                    <th><?php esc_html_e( 'Phone Number', 'claims-management' ); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ( $key_people as $person ) : ?>
                                                    <tr>
                                                        <td><?php echo esc_html( $person['name'] ); ?></td>
                                                        <td><?php echo esc_html( $person['title'] ); ?></td>
                                                        <td><?php echo esc_html( $person['email'] ); ?></td>
                                                        <td><?php echo esc_html( $person['phone'] ); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else : ?>
                                    <p><?php esc_html_e( 'No key people saved.', 'claims-management' ); ?></p>
                                <?php endif; ?>
                            </div>
                            <div id="tab-key-people">
                                <!-- Editable Key People Form -->
                                <form id="key-people-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                    <?php wp_nonce_field( 'update_prospect_key_people', 'update_prospect_key_people_nonce' ); ?>
                                    <input type="hidden" name="action" value="update_prospect_key_people">
                                    <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
                                    <div class="responsive-table">
                                        <table id="editable-key-people-table" class="wp-list-table widefat fixed striped">
                                            <thead>
                                                <tr>
                                                    <th><?php esc_html_e( 'Name', 'claims-management' ); ?> <span style="color:red;">*</span></th>
                                                    <th><?php esc_html_e( 'Title', 'claims-management' ); ?></th>
                                                    <th><?php esc_html_e( 'Email Address', 'claims-management' ); ?></th>
                                                    <th><?php esc_html_e( 'Phone Number', 'claims-management' ); ?></th>
                                                    <th><?php esc_html_e( 'Actions', 'claims-management' ); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if ( ! empty( $key_people ) ) {
                                                    $index = 0;
                                                    foreach ( $key_people as $person ) {
                                                        ?>
                                                        <tr>
                                                            <td><input type="text" name="key_people[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $person['name'] ); ?>" required /></td>
                                                            <td><input type="text" name="key_people[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $person['title'] ); ?>" /></td>
                                                            <td><input type="email" name="key_people[<?php echo esc_attr( $index ); ?>][email]" value="<?php echo esc_attr( $person['email'] ); ?>" /></td>
                                                            <td><input type="text" name="key_people[<?php echo esc_attr( $index ); ?>][phone]" value="<?php echo esc_attr( $person['phone'] ); ?>" /></td>
                                                            <td><button type="button" class="remove-key-person button">Remove</button></td>
                                                        </tr>
                                                        <?php
                                                        $index++;
                                                    }
                                                } else {
                                                    ?>
                                                    <tr>
                                                        <td><input type="text" name="key_people[0][name]" value="" required /></td>
                                                        <td><input type="text" name="key_people[0][title]" value="" /></td>
                                                        <td><input type="email" name="key_people[0][email]" value="" /></td>
                                                        <td><input type="text" name="key_people[0][phone]" value="" /></td>
                                                        <td><button type="button" class="remove-key-person button">Remove</button></td>
                                                    </tr>
                                                    <?php
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p>
                                        <button type="button" id="add-key-person" class="button">Add Person</button>
                                        <input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Key People', 'claims-management' ); ?>">
                                    </p>
                                </form>
                            </div>
                            <div id="tab-general-notes">
                                <!-- General Notes Form -->
                                <form id="general-notes-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                    <?php wp_nonce_field( 'update_prospect_notes', 'update_prospect_notes_nonce' ); ?>
                                    <input type="hidden" name="action" value="update_prospect_notes">
                                    <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
                                    <textarea name="general_notes" rows="5" class="large-text" style="width:100%;"><?php echo esc_textarea( get_post_meta( $post_id, 'cm_general_notes', true ) ); ?></textarea>
                                    <p>
                                        <input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Notes', 'claims-management' ); ?>">
                                    </p>
                                </form>
                            </div>
                        </div>
                        <!-- Extra space between tabs and callback reminders -->
                        <div style="margin-top:30px;"></div>
                        <!-- Callback Reminders Table -->
                        <h2><?php esc_html_e( 'Callback Reminders', 'claims-management' ); ?></h2>
                        <?php if ( ! empty( $callbacks ) ) : ?>
                            <div class="responsive-table">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e( 'Callback Date & Time', 'claims-management' ); ?></th>
                                            <th><?php esc_html_e( 'Notes', 'claims-management' ); ?></th>
                                            <th><?php esc_html_e( 'Days Until', 'claims-management' ); ?></th>
                                            <th><?php esc_html_e( 'Actions', 'claims-management' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Sort callbacks by callback_datetime ascending.
                                        usort( $callbacks, function( $a, $b ) {
                                            return $a['callback_datetime'] - $b['callback_datetime'];
                                        });
                                        foreach ( $callbacks as $index => $callback ) :
                                            $callback_datetime = intval( $callback['callback_datetime'] );
                                            $days_until = ceil( ($callback_datetime - time()) / 86400 );
                                        ?>
                                        <tr>
                                            <td><?php echo date( 'F j, Y H:i', $callback_datetime ); ?></td>
                                            <td><?php echo esc_html( $callback['notes'] ); ?></td>
                                            <td><?php echo $days_until; ?></td>
                                            <td>
                                                <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                                                    <?php wp_nonce_field( 'delete_callback_reminder', 'delete_callback_reminder_nonce' ); ?>
                                                    <input type="hidden" name="action" value="delete_callback_reminder">
                                                    <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
                                                    <input type="hidden" name="callback_index" value="<?php echo esc_attr( $index ); ?>">
                                                    <input type="submit" class="button" value="<?php esc_attr_e( 'Done', 'claims-management' ); ?>">
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p><?php esc_html_e( 'No callback reminders set.', 'claims-management' ); ?></p>
                        <?php endif; ?>
                        <!-- Extra space between callback reminders and call log history -->
                        <div style="margin-top:30px;"></div>
                        <!-- Call Log History -->
                        <h2><?php esc_html_e( 'Call Log History', 'claims-management' ); ?></h2>
                        <?php if ( ! empty( $call_logs ) ) : ?>
                            <div class="responsive-table">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e( 'Date & Time', 'claims-management' ); ?></th>
                                            <th><?php esc_html_e( 'Notes', 'claims-management' ); ?></th>
                                            <th><?php esc_html_e( 'Days Ago', 'claims-management' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        usort( $call_logs, function( $a, $b ) {
                                            return $b['timestamp'] - $a['timestamp'];
                                        });
                                        foreach ( $call_logs as $log ) : 
                                            $log_time = intval( $log['timestamp'] );
                                            $days_ago = floor( ( time() - $log_time ) / 86400 );
                                        ?>
                                        <tr>
                                            <td><?php echo date( 'Y-m-d H:i', $log_time ); ?></td>
                                            <td><?php echo esc_html( $log['notes'] ); ?></td>
                                            <td><?php printf( _n( '%d day ago', '%d days ago', $days_ago, 'claims-management' ), $days_ago ); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p><?php esc_html_e( 'No calls logged yet.', 'claims-management' ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Hidden Modal for Editing Details -->
            <div id="edit-details-modal" style="display:none;">
                <form id="edit-details-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'update_prospect_details', 'update_prospect_details_nonce' ); ?>
                    <input type="hidden" name="action" value="update_prospect_details">
                    <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
                    <table class="form-table">
                        <tr>
                            <th><label for="new_business_name"><?php esc_html_e( 'Business Name', 'claims-management' ); ?></label></th>
                            <td><input type="text" name="new_business_name" id="new_business_name" class="regular-text" value="<?php echo esc_attr( $business_name ); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="new_web_address"><?php esc_html_e( 'Web Address', 'claims-management' ); ?></label></th>
                            <td><input type="text" name="new_web_address" id="new_web_address" class="regular-text" value="<?php echo esc_attr( $web_address ); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="new_phone"><?php esc_html_e( 'Phone', 'claims-management' ); ?></label></th>
                            <td><input type="text" name="new_phone" id="new_phone" class="regular-text" value="<?php echo esc_attr( $phone ); ?>"></td>
                        </tr>
                    </table>
                    <p>
                        <input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'claims-management' ); ?>">
                        <button type="button" id="cancel-edit-details" class="button"><?php esc_html_e( 'Cancel', 'claims-management' ); ?></button>
                    </p>
                </form>
            </div>
            <!-- Hidden Modal for Logging a Call -->
            <div id="log-call-modal" style="display:none;">
                <form id="log-call-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'log_call', 'log_call_nonce' ); ?>
                    <input type="hidden" name="action" value="log_call">
                    <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
                    <p>
                        <label for="call_notes"><?php esc_html_e( 'Call Notes', 'claims-management' ); ?></label><br>
                        <textarea name="call_notes" id="call_notes" rows="5" style="width:100%;"></textarea>
                    </p>
                    <p>
                        <input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Call', 'claims-management' ); ?>">
                        <button type="button" id="cancel-log-call" class="button"><?php esc_html_e( 'Cancel', 'claims-management' ); ?></button>
                    </p>
                </form>
            </div>
            <!-- Hidden Modal for Call Back -->
            <div id="call-back-modal" style="display:none;">
                <form id="call-back-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'set_callback', 'set_callback_nonce' ); ?>
                    <input type="hidden" name="action" value="set_callback">
                    <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
                    <p>
                        <label for="callback_date"><?php esc_html_e( 'Callback Date', 'claims-management' ); ?></label><br>
                        <input type="text" name="callback_date" id="callback_date" style="width:100%;" placeholder="YYYY-MM-DD">
                    </p>
                    <p>
                        <label for="callback_time"><?php esc_html_e( 'Callback Hour', 'claims-management' ); ?></label><br>
                        <select name="callback_time" id="callback_time" style="width:100%;">
                            <?php
                            for ( $i = 0; $i < 24; $i++ ) {
                                $hour = str_pad( $i, 2, '0', STR_PAD_LEFT ) . ":00";
                                echo '<option value="' . esc_attr( $hour ) . '">' . esc_html( $hour ) . '</option>';
                            }
                            ?>
                        </select>
                    </p>
                    <p>
                        <label for="callback_notes"><?php esc_html_e( 'Notes', 'claims-management' ); ?></label><br>
                        <textarea name="callback_notes" id="callback_notes" rows="5" style="width:100%;"></textarea>
                    </p>
                    <p>
                        <input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Callback', 'claims-management' ); ?>">
                        <button type="button" id="cancel-call-back" class="button"><?php esc_html_e( 'Cancel', 'claims-management' ); ?></button>
                    </p>
                </form>
            </div>
            <script>
            jQuery(document).ready(function($) {
                // Initialize jQuery UI Tabs.
                $("#tabs").tabs();
                
                // Initialize datepicker for callback date.
                $("#callback_date").datepicker({ dateFormat: "yy-mm-dd" });
                
                var keyPeopleIndex = <?php echo count( $key_people ); ?>;
                $('#add-key-person').on('click', function(e) {
                    e.preventDefault();
                    var rowHtml = '<tr>' +
                                  '<td><input type="text" name="key_people['+keyPeopleIndex+'][name]" value="" required /></td>' +
                                  '<td><input type="text" name="key_people['+keyPeopleIndex+'][title]" value="" /></td>' +
                                  '<td><input type="email" name="key_people['+keyPeopleIndex+'][email]" value="" /></td>' +
                                  '<td><input type="text" name="key_people['+keyPeopleIndex+'][phone]" value="" /></td>' +
                                  '<td><button type="button" class="remove-key-person button">Remove</button></td>' +
                                  '</tr>';
                    $('#editable-key-people-table tbody').append(rowHtml);
                    keyPeopleIndex++;
                });
                $('#editable-key-people-table').on('click', '.remove-key-person', function(e) {
                    e.preventDefault();
                    $(this).closest('tr').remove();
                });
                
                // Log Call button: show log call modal.
                $('#log-call-btn').click(function(e) {
                    e.preventDefault();
                    tb_show('<?php esc_html_e( 'Log Call', 'claims-management' ); ?>', '#TB_inline?height=300&width=500&inlineId=log-call-modal');
                });
                $('#cancel-log-call').click(function(e) {
                    e.preventDefault();
                    tb_remove();
                });
                
                // Call Back button: show call back modal.
                $('#call-back-btn').click(function(e) {
                    e.preventDefault();
                    tb_show('<?php esc_html_e( 'Call Back', 'claims-management' ); ?>', '#TB_inline?height=400&width=500&inlineId=call-back-modal');
                });
                $('#cancel-call-back').click(function(e) {
                    e.preventDefault();
                    tb_remove();
                });
                
                // Attach event to new Edit Details button in Business Details tab.
                $('#edit-details-tab-btn').click(function(e) {
                    e.preventDefault();
                    tb_show('<?php esc_html_e( 'Edit Details', 'claims-management' ); ?>', '#TB_inline?height=300&width=500&inlineId=edit-details-modal');
                });
                $('#cancel-edit-details').click(function(e) {
                    e.preventDefault();
                    tb_remove();
                });
            });
            </script>
            <style>
            .inline-edit {
                font-size: 14px;
                padding: 2px 4px;
            }
            .editable {
                cursor: pointer;
            }
            /* Responsive Table Styling */
            .responsive-table {
                width: 100%;
                overflow-x: auto;
            }
            @media screen and (max-width: 600px) {
                .responsive-table table {
                    width: 100%;
                }
                .responsive-table table th,
                .responsive-table table td {
                    white-space: nowrap;
                }
            }
            /* Bootstrap-like Button Styling */
            .btn {
                display: inline-block;
                font-weight: 400;
                text-align: center;
                white-space: nowrap;
                vertical-align: middle;
                user-select: none;
                border: 1px solid transparent;
                padding: .375rem .75rem;
                font-size: 1rem;
                line-height: 1.5;
                border-radius: .25rem;
                transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out;
                cursor: pointer;
            }
            .btn-block {
                display: block;
                width: 100%;
            }
            .btn-primary {
                color: #fff;
                background-color: #007bff;
                border-color: #007bff;
            }
            .btn-warning {
                color: #212529;
                background-color: #ffc107;
                border-color: #ffc107;
            }
            .btn-success {
                color: #fff;
                background-color: #28a745;
                border-color: #28a745;
            }
            .btn-orange {
                color: #fff;
                background-color: #fd7e14;
                border-color: #fd7e14;
            }
            /* Extra margin below tabs */
            #tabs {
                margin-bottom: 30px;
            }
            </style>
            <?php
        }
    }
}

new ProspectProfilePage();

// Enqueue necessary scripts and styles.
add_action( 'admin_enqueue_scripts', function() {
    wp_enqueue_script( 'jquery-ui-tabs' );
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_script( 'thickbox' );
    wp_enqueue_style( 'jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
    wp_enqueue_style( 'thickbox' );
});

/* Update Handlers */
add_action( 'admin_post_update_prospect_details', __NAMESPACE__ . '\\update_prospect_details' );
function update_prospect_details() {
    if ( ! current_user_can( 'read' ) ) {
        wp_die( esc_html__( 'Insufficient permissions.', 'claims-management' ) );
    }
    check_admin_referer( 'update_prospect_details', 'update_prospect_details_nonce' );
    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    if ( ! $post_id ) {
        wp_die( esc_html__( 'No Prospect Selected', 'claims-management' ) );
    }
    $new_business_name = isset( $_POST['new_business_name'] ) ? sanitize_text_field( wp_unslash( $_POST['new_business_name'] ) ) : '';
    $new_web_address   = isset( $_POST['new_web_address'] ) ? esc_url_raw( wp_unslash( $_POST['new_web_address'] ) ) : '';
    $new_phone         = isset( $_POST['new_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['new_phone'] ) ) : '';
    $country = get_post_meta( $post_id, 'cm_country', true );
    if ( empty( $country ) ) { $country = 'United States'; }
    $new_phone = clean_phone_number( $new_phone, $country );
    $new_web_address = clean_root_domain_url( $new_web_address );
    $current_web_address = get_post_meta( $post_id, 'cm_web_address', true );
    if ( $new_web_address !== $current_web_address ) {
        delete_post_meta( $post_id, 'cm_screenshot_url' );
    }
    update_post_meta( $post_id, 'cm_business_name', $new_business_name );
    update_post_meta( $post_id, 'cm_web_address', $new_web_address );
    update_post_meta( $post_id, 'cm_phone', $new_phone );
    wp_redirect( admin_url( 'admin.php?page=cm_view_prospect&post_id=' . $post_id ) );
    exit;
}

add_action( 'admin_post_update_prospect_notes', __NAMESPACE__ . '\\update_prospect_notes' );
function update_prospect_notes() {
    if ( ! current_user_can( 'read' ) ) { wp_die( esc_html__( 'Insufficient permissions.', 'claims-management' ) ); }
    check_admin_referer( 'update_prospect_notes', 'update_prospect_notes_nonce' );
    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    if ( ! $post_id ) { wp_die( esc_html__( 'No Prospect Selected', 'claims-management' ) ); }
    $general_notes = isset( $_POST['general_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['general_notes'] ) ) : '';
    update_post_meta( $post_id, 'cm_general_notes', $general_notes );
    wp_redirect( admin_url( 'admin.php?page=cm_view_prospect&post_id=' . $post_id ) );
    exit;
}

add_action( 'admin_post_update_prospect_key_people', __NAMESPACE__ . '\\update_prospect_key_people' );
function update_prospect_key_people() {
    if ( ! current_user_can( 'read' ) ) { wp_die( esc_html__( 'Insufficient permissions.', 'claims-management' ) ); }
    check_admin_referer( 'update_prospect_key_people', 'update_prospect_key_people_nonce' );
    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    if ( ! $post_id ) { wp_die( esc_html__( 'No Prospect Selected', 'claims-management' ) ); }
    $key_people = isset( $_POST['key_people'] ) ? $_POST['key_people'] : array();
    $clean_key_people = array();
    if ( is_array( $key_people ) ) {
        foreach ( $key_people as $person ) {
            if ( ! empty( $person['name'] ) ) {
                $clean_key_people[] = array(
                    'name'  => sanitize_text_field( $person['name'] ),
                    'title' => isset( $person['title'] ) ? sanitize_text_field( $person['title'] ) : '',
                    'email' => isset( $person['email'] ) ? sanitize_email( $person['email'] ) : '',
                    'phone' => isset( $person['phone'] ) ? sanitize_text_field( $person['phone'] ) : '',
                );
            }
        }
    }
    update_post_meta( $post_id, 'cm_key_people', $clean_key_people );
    wp_redirect( admin_url( 'admin.php?page=cm_view_prospect&post_id=' . $post_id ) );
    exit;
}

add_action( 'admin_post_log_call', __NAMESPACE__ . '\\log_call_handler' );
function log_call_handler() {
    if ( ! current_user_can( 'read' ) ) { wp_die( esc_html__( 'Insufficient permissions.', 'claims-management' ) ); }
    check_admin_referer( 'log_call', 'log_call_nonce' );
    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    if ( ! $post_id ) { wp_die( esc_html__( 'No Prospect Selected', 'claims-management' ) ); }
    $call_notes = isset( $_POST['call_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['call_notes'] ) ) : '';
    $timestamp = time();
    $call_logs = get_post_meta( $post_id, 'cm_logged_calls', true );
    if ( ! is_array( $call_logs ) ) { $call_logs = array(); }
    array_unshift( $call_logs, array(
        'timestamp' => $timestamp,
        'notes'     => $call_notes,
    ) );
    update_post_meta( $post_id, 'cm_logged_calls', $call_logs );
    wp_redirect( admin_url( 'admin.php?page=cm_view_prospect&post_id=' . $post_id ) );
    exit;
}

add_action( 'admin_post_set_callback', __NAMESPACE__ . '\\set_callback_handler' );
function set_callback_handler() {
    if ( ! current_user_can( 'read' ) ) { wp_die( esc_html__( 'Insufficient permissions.', 'claims-management' ) ); }
    check_admin_referer( 'set_callback', 'set_callback_nonce' );
    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    if ( ! $post_id ) { wp_die( esc_html__( 'No Prospect Selected', 'claims-management' ) ); }
    $callback_date = isset( $_POST['callback_date'] ) ? sanitize_text_field( $_POST['callback_date'] ) : '';
    $callback_time = isset( $_POST['callback_time'] ) ? sanitize_text_field( $_POST['callback_time'] ) : '';
    $callback_notes = isset( $_POST['callback_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['callback_notes'] ) ) : '';
    $datetime_str = $callback_date . ' ' . $callback_time;
    $callback_timestamp = strtotime( $datetime_str );
    if ( false === $callback_timestamp ) {
        $callback_timestamp = time();
    }
    $callbacks = get_post_meta( $post_id, 'cm_callback_notifications', true );
    if ( ! is_array( $callbacks ) ) { $callbacks = array(); }
    $new_callback = array(
         'callback_datetime' => $callback_timestamp,
         'notes'             => $callback_notes,
         'created_at'        => time(),
    );
    $callbacks[] = $new_callback;
    update_post_meta( $post_id, 'cm_callback_notifications', $callbacks );
    wp_redirect( admin_url( 'admin.php?page=cm_view_prospect&post_id=' . $post_id ) );
    exit;
}

add_action( 'admin_post_delete_callback_reminder', __NAMESPACE__ . '\\delete_callback_reminder_handler' );
function delete_callback_reminder_handler() {
    if ( ! current_user_can( 'read' ) ) { wp_die( esc_html__( 'Insufficient permissions.', 'claims-management' ) ); }
    check_admin_referer( 'delete_callback_reminder', 'delete_callback_reminder_nonce' );
    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    $callback_index = isset( $_POST['callback_index'] ) ? intval( $_POST['callback_index'] ) : -1;
    if ( ! $post_id || $callback_index < 0 ) {
        wp_die( esc_html__( 'Invalid request.', 'claims-management' ) );
    }
    $callbacks = get_post_meta( $post_id, 'cm_callback_notifications', true );
    if ( ! is_array( $callbacks ) ) { $callbacks = array(); }
    if ( isset( $callbacks[$callback_index] ) ) {
        unset( $callbacks[$callback_index] );
        $callbacks = array_values( $callbacks );
        update_post_meta( $post_id, 'cm_callback_notifications', $callbacks );
    }
    wp_redirect( admin_url( 'admin.php?page=cm_view_prospect&post_id=' . $post_id ) );
    exit;
}
?>