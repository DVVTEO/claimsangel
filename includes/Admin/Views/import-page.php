<?php
if (!defined('ABSPATH')) {
    exit;
}

// Initialize error/success message variables
$upload_error = '';
$upload_success = '';
$import_stats = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prospect_import_nonce'])) {
    if (wp_verify_nonce($_POST['prospect_import_nonce'], 'prospect_import_action')) {
        if (isset($_FILES['prospect_file'])) {
            // Get the file processing instance
            $processor = \ClaimsAngel\Admin\ProspectImportProcessor::get_instance();
            
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
        <?php if (!empty($import_stats)): ?>
            <div class="import-statistics">
                <h3>Import Statistics</h3>
                <ul>
                    <li>Valid Records: <strong><?php echo intval($import_stats['valid']); ?></strong></li>
                    <li>Invalid Records: <strong><?php echo intval($import_stats['invalid']); ?></strong></li>
                    <li>Duplicate Records: <strong><?php echo intval($import_stats['duplicate']); ?></strong></li>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="upload-section">
        <form method="post" enctype="multipart/form-data" id="importForm">
            <?php wp_nonce_field('prospect_import_action', 'prospect_import_nonce'); ?>
            <div class="form-field">
                <label for="prospect_file">Select File to Import</label>
                <input type="file" 
                       name="prospect_file" 
                       id="prospect_file" 
                       accept=".csv,.xlsx,.xls" 
                       required />
                <p class="description">
                    Accepted file types: CSV, Excel (.xlsx, .xls)<br>
                    Required columns: Business Name, Country
                </p>
            </div>
            <input type="submit" 
                   class="button button-primary" 
                   value="Upload File" />
        </form>
    </div>

    <div id="import-results" style="display: <?php echo !empty($import_stats) ? 'block' : 'none'; ?>">
        <?php
        $countries = \ClaimsAngel\Data\Countries::get_instance()->get_all();
        foreach ($countries as $country_code => $country_data):
            if (!$country_data['enabled']) continue;
        ?>
            <div class="country-section" id="country-<?php echo esc_attr($country_code); ?>">
                <h2><?php echo esc_html($country_data['name']); ?></h2>
                <table class="widefat prospects-table">
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
                        <!-- Data will be populated via AJAX -->
                    </tbody>
                </table>
                <div class="table-actions">
                    <button class="button button-primary approve-country" 
                            data-country="<?php echo esc_attr($country_code); ?>">
                        Approve All <?php echo esc_html($country_data['name']); ?> Records
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="ca-import-container" style="display: none;">
        <h3>Temporary Records</h3>
        <table class="widefat ca-import-table">
            <thead>
                <tr>
                    <th>Business Name</th>
                    <th>Web Address</th>
                    <th>Phone Number</th>
                    <th>LinkedIn Profile</th>
                    <th>Country</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <!-- Temporary records will be populated via AJAX -->
            </tbody>
        </table>
    </div>
</div>