<?php
if (!defined('ABSPATH')) {
    exit;
}

// Initialize error/success message variables
$upload_error = '';
$upload_success = '';
$import_stats = [];
$session_id = '';

// Display deletion success message if applicable
if (isset($_GET['deleted']) && isset($_GET['country'])) {
    $deleted_count = intval($_GET['deleted']);
    $country_code = sanitize_text_field($_GET['country']);
    $countries = \ClaimsAngel\Data\Countries::get_instance()->get_all();
    $country_name = isset($countries[$country_code]) ? $countries[$country_code]['name'] : $country_code;
    
    echo '<div class="notice notice-success is-dismissible">';
    echo '<p>' . sprintf(
        esc_html__('Successfully deleted %d prospects from %s.', 'claims-angel'),
        $deleted_count,
        esc_html($country_name)
    ) . '</p>';
    echo '</div>';
}

// Handle CSV export
if (isset($_POST['export_rejected']) && isset($_POST['session_id']) && isset($_POST['export_nonce'])) {
    if (wp_verify_nonce($_POST['export_nonce'], 'export_rejected_action')) {
        $processor = \ClaimsAngel\Admin\Prospecting\ProspectImportProcessor::get_instance();
        
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Generate filename with timestamp
        $filename = 'rejected-records-' . sanitize_file_name($_POST['session_id']) . '-' . date('Y-m-d-H-i-s') . '.csv';
        
        // Set headers for file download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $processor->export_rejected_records_csv($_POST['session_id']);
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prospect_import_nonce'])) {
    if (wp_verify_nonce($_POST['prospect_import_nonce'], 'prospect_import_action')) {
        if (isset($_FILES['prospect_file'])) {
            // Get the file processing instance
            $processor = \ClaimsAngel\Admin\Prospecting\ProspectImportProcessor::get_instance();
            
            // Process the upload
            $result = $processor->process_upload($_FILES['prospect_file']);
            
            if (is_wp_error($result)) {
                $upload_error = $result->get_error_message();
                error_log(sprintf(
                    '[Claims Angel Import] Error processing file: %s | User: %s | Date: %s',
                    $result->get_error_message(),
                    wp_get_current_user()->user_login,
                    current_time('mysql')
                ));
            } else {
                $session_id = $result['session_id'];
                $import_stats = $result['stats'];
                $upload_success = sprintf(
                    'File processed successfully. Valid: %d, Invalid: %d, Duplicate: %d',
                    $result['stats']['valid'],
                    $result['stats']['invalid'],
                    $result['stats']['duplicate']
                );
                error_log(sprintf(
                    '[Claims Angel Import] Successful import | Session: %s | Stats: %s | User: %s | Date: %s',
                    $result['session_id'],
                    json_encode($result['stats']),
                    wp_get_current_user()->user_login,
                    current_time('mysql')
                ));

                // Get rejected records if any exist
                if ($result['stats']['invalid'] > 0 || $result['stats']['duplicate'] > 0) {
                    $rejected_records = $processor->get_rejected_records($result['session_id']);
                }
            }
        } else {
            $upload_error = 'No file was uploaded.';
            error_log(sprintf(
                '[Claims Angel Import] No file uploaded | User: %s | Date: %s',
                wp_get_current_user()->user_login,
                current_time('mysql')
            ));
        }
    } else {
        $upload_error = 'Security check failed.';
        error_log(sprintf(
            '[Claims Angel Import] Security check failed | User: %s | Date: %s',
            wp_get_current_user()->user_login,
            current_time('mysql')
        ));
    }
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if (!empty($upload_error)): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($upload_error); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($upload_success)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($upload_success); ?></p>
        </div>
    <?php endif; ?>

    <div class="upload-section">
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('prospect_import_action', 'prospect_import_nonce'); ?>
            <div class="form-field">
                <label for="prospect_file">Select File to Import</label>
                <input type="file" 
                       name="prospect_file" 
                       id="prospect_file" 
                       accept=".csv" 
                       required />
                <p class="description">
                    Accepted file types: CSV<br>
                    Required columns: Business Name, Country
                </p>
            </div>
            <input type="submit" 
                   class="button button-primary" 
                   value="Upload File" />
        </form>
    </div>

    <?php if (!empty($rejected_records)): ?>
        <div class="rejected-records-section">
            <h2>Rejected Records</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="export_rejected_records" />
                <?php wp_nonce_field('export_rejected_action', 'export_nonce'); ?>
                <input type="hidden" name="session_id" value="<?php echo esc_attr($session_id); ?>" />
                <input type="submit" 
                       name="export_rejected" 
                       class="button" 
                       value="Download Rejected Records (CSV)" />
            </form>

            <table class="widefat">
                <thead>
                    <tr>
                        <th>Business Name</th>
                        <th>Web Address</th>
                        <th>Phone Number</th>
                        <th>LinkedIn Profile</th>
                        <th>Country</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rejected_records as $record): ?>
                        <tr>
                            <td><?php echo esc_html($record->business_name); ?></td>
                            <td><?php echo esc_html($record->web_address); ?></td>
                            <td><?php echo esc_html($record->phone_number); ?></td>
                            <td><?php echo esc_html($record->linkedin_profile); ?></td>
                            <td><?php echo esc_html($record->country); ?></td>
                            <td><?php echo esc_html($record->rejection_reason); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="results-section">
        <?php
        global $wpdb;
        $temp_table = $wpdb->prefix . 'temp_prospects';
        
        // Get all countries that have records in the temp table
        $countries_with_records = $wpdb->get_col(
            "SELECT DISTINCT country FROM {$temp_table}"
        );

        if (!empty($countries_with_records)) {
            $countries = \ClaimsAngel\Data\Countries::get_instance()->get_all();
            foreach ($countries_with_records as $country_code):
                if (!isset($countries[$country_code]) || !$countries[$country_code]['enabled']) continue;
                
                // Get records for this country
                $records = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$temp_table} WHERE country = %s" . 
                    (!empty($session_id) ? " AND session_id = %s" : ""),
                    array_merge([$country_code], !empty($session_id) ? [$session_id] : [])
                ));
                
                if (empty($records)) continue;
            ?>
                <div class="country-section" id="country-<?php echo esc_attr($country_code); ?>">
                    <h2 style="margin-top:50px"><?php echo esc_html($countries[$country_code]['name']); ?></h2>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Business Name</th>
                                <th>Web Address</th>
                                <th>Phone Number</th>
                                <th>LinkedIn Profile</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo esc_html($record->business_name); ?></td>
                                    <td><?php echo esc_html($record->web_address); ?></td>
                                    <td><?php echo esc_html($record->phone_number); ?></td>
                                    <td><?php echo esc_html($record->linkedin_profile); ?></td>
                                    <td><?php echo esc_html($record->status); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="table-actions" style="margin-top: 10px;">
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                            <input type="hidden" name="action" value="delete_prospects" />
                            <input type="hidden" name="country" value="<?php echo esc_attr($country_code); ?>" />
                            <?php if (!empty($session_id)): ?>
                                <input type="hidden" name="session_id" value="<?php echo esc_attr($session_id); ?>" />
                            <?php endif; ?>
                            <?php wp_nonce_field('delete_prospects_action', 'delete_prospects_nonce'); ?>
                            <input type="submit" 
                                   class="button" 
                                   value="<?php echo esc_attr(sprintf(__('Delete %s Prospects', 'claims-angel'), $countries[$country_code]['name'])); ?>"
                                   onclick="return confirm('<?php echo esc_js(sprintf(__('Are you sure you want to delete all prospects for %s?', 'claims-angel'), $countries[$country_code]['name'])); ?>');" style="background-color: #D4403A; color: #fff" />
                        </form>
                        
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
        <input type="hidden" name="action" value="convert_prospects" />
        <input type="hidden" name="country" value="<?php echo esc_attr($country_code); ?>" />
        <?php if (!empty($session_id)): ?>
            <input type="hidden" name="session_id" value="<?php echo esc_attr($session_id); ?>" />
        <?php endif; ?>
        <?php wp_nonce_field('convert_prospects_action', 'convert_prospects_nonce'); ?>
        <input type="submit" 
               class="button" 
               value="<?php echo esc_attr(sprintf(__('Accept %s Prospects', 'claims-angel'), $countries[$country_code]['name'])); ?>"
               onclick="return confirm('<?php echo esc_js(sprintf(__('Are you sure you want to accept all prospects for %s?', 'claims-angel'), $countries[$country_code]['name'])); ?>');" style="background-color: #5cb85c; color: #fff" />
    </form>
                    </div>
                </div>
            <?php endforeach;
        } ?>
    </div>
</div>