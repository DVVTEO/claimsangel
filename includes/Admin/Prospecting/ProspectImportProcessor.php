<?php
namespace ClaimsAngel\Admin\Prospecting;

use ClaimsAngel\Data\WebAddress;
use ClaimsAngel\Data\Countries;

class ProspectImportProcessor {
    private static $instance = null;
    private $required_columns = ['business_name', 'country'];
    private $allowed_columns = ['business_name', 'web_address', 'phone_number', 'linkedin_profile', 'country'];
    private $temp_table_name;
    private $rejected_table_name;
    private $wpdb;
    private $countries;
    private $web_address;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->temp_table_name = $wpdb->prefix . 'temp_prospects';
        $this->rejected_table_name = $wpdb->prefix . 'rejected_prospects';
        $this->countries = Countries::get_instance();
        $this->web_address = WebAddress::get_instance();
        
        add_action('admin_init', array($this, 'create_tables'));
        add_action('admin_post_delete_prospects', array($this, 'handle_delete_prospects'));
        add_action('admin_post_convert_prospects', array($this, 'handle_convert_prospects'));
    }
    
    /**
 * Convert temporary prospects to 'business' custom post type.
 */
public function handle_convert_prospects() {
    // Verify user permissions.
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access', 'Error', [
            'response' => 403,
            'back_link' => true,
        ]);
    }

    // Verify nonce.
    if (!isset($_POST['convert_prospects_nonce']) || 
        !wp_verify_nonce($_POST['convert_prospects_nonce'], 'convert_prospects_action')) {
        wp_die('Invalid security token', 'Error', [
            'response' => 403,
            'back_link' => true,
        ]);
    }

    // Get and validate parameters.
    $country_code = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';

    if (empty($country_code)) {
        wp_die('Country code is required', 'Error', [
            'response' => 400,
            'back_link' => true,
        ]);
    }

    // Retrieve records from the temporary table.
    $where = "country = %s";
    $params = [$country_code];
    if (!empty($session_id)) {
        $where .= " AND session_id = %s";
        $params[] = $session_id;
    }
    $query = "SELECT * FROM {$this->temp_table_name} WHERE {$where}";
    $records = $this->wpdb->get_results($this->wpdb->prepare($query, $params));

    $converted = 0;
    if (!empty($records)) {
        foreach ($records as $record) {
            // Create a new 'business' post.
            $postarr = [
                'post_type'    => 'business',
                'post_title'   => $record->business_name,
                'post_status'  => 'publish',  // Or a custom status as needed.
                'post_date'    => current_time('mysql'),
            ];
            $post_id = wp_insert_post($postarr, true);

            if (!is_wp_error($post_id)) {
                // Map meta fields.
                update_post_meta($post_id, 'business_name', $record->business_name);
                update_post_meta($post_id, 'web_address', $record->web_address);
                update_post_meta($post_id, 'linkedin_profile', $record->linkedin_profile);
                update_post_meta($post_id, 'phone_number', $record->phone_number);
                update_post_meta($post_id, 'country', $record->country);
                update_post_meta($post_id, 'business_status', 'prospect');
                update_post_meta($post_id, 'date_created', time()); // Unix timestamp of conversion.
                
                $converted++;
            } else {
                error_log('Error converting prospect: ' . $post_id->get_error_message());
            }
        }
    }

    // Optionally, you might delete the converted records from the temp table.
    $this->wpdb->query($this->wpdb->prepare("DELETE FROM {$this->temp_table_name} WHERE {$where}", $params));

    // Redirect back with a success message.
    $redirect_url = add_query_arg([
        'page'      => 'prospect-import',
        'converted' => $converted,
        'country'   => $country_code
    ], admin_url('admin.php'));
    wp_redirect($redirect_url);
    exit;
}

    /**
     * Create necessary database tables
     */
     public function create_tables() {
    try {
        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }

        // Remove any trailing semicolon from the charset/collation string
        $charset_collate = rtrim($this->wpdb->get_charset_collate(), ';');

        // Create temp prospects table query
        $sql_temp = "CREATE TABLE IF NOT EXISTS {$this->temp_table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(32) NOT NULL,
            business_name varchar(255) NOT NULL,
            web_address varchar(255),
            phone_number varchar(32),
            linkedin_profile varchar(255),
            country varchar(2) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY status (status)
        ) $charset_collate";

        $result_temp = $this->wpdb->query($sql_temp);
        if ($result_temp === false) {
            error_log('[Claims Angel] Temp table creation failed: ' . $this->wpdb->last_error);
        }

        // Create rejected prospects table query
        $sql_rejected = "CREATE TABLE IF NOT EXISTS {$this->rejected_table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(32) NOT NULL,
            business_name varchar(255) NOT NULL,
            web_address varchar(255),
            phone_number varchar(32),
            linkedin_profile varchar(255),
            country varchar(255) NOT NULL,
            rejection_reason varchar(255) NOT NULL,
            original_data text NOT NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id)
        ) $charset_collate";

        $result_rejected = $this->wpdb->query($sql_rejected);
        if ($result_rejected === false) {
            error_log('[Claims Angel] Rejected table creation failed: ' . $this->wpdb->last_error);
        }

    } catch (\Exception $e) {
        error_log('[Claims Angel] Exception during table creation: ' . $e->getMessage());
    }
}

    /**
     * Process file upload
     * @param array $file Uploaded file data
     * @return array|WP_Error Processing results or error
     */
    public function process_upload($file) {
        try {
            // Validate file exists
            if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
                return new \WP_Error('no_file', __('No file was uploaded.', 'claims-angel'));
            }

            // Validate file type
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($file_extension !== 'csv') {
                return new \WP_Error('invalid_type', __('Invalid file type. Please upload a CSV file.', 'claims-angel'));
            }

            // Create unique session ID for this import
            $session_id = uniqid('import_');

            // Initialize counters
            $stats = [
                'valid' => 0,
                'invalid' => 0,
                'duplicate' => 0
            ];

            // Read the CSV file
            $handle = fopen($file['tmp_name'], 'r');
            if ($handle === false) {
                return new \WP_Error('file_read_error', __('Could not read the uploaded file.', 'claims-angel'));
            }

            // Read headers
            $headers = fgetcsv($handle);
            if ($headers === false) {
                fclose($handle);
                return new \WP_Error('invalid_csv', __('Could not read CSV headers.', 'claims-angel'));
            }

            // Validate required columns exist
            $headers = array_map('strtolower', $headers);
            foreach ($this->required_columns as $required) {
                if (!in_array($required, $headers)) {
                    fclose($handle);
                    return new \WP_Error(
                        'missing_column',
                        sprintf(__('Required column "%s" is missing.', 'claims-angel'), $required)
                    );
                }
            }

            // Process rows
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($headers, $row);
                
                // Validate and clean data
                $validation_result = $this->validate_prospect_data($data);
                
                if ($validation_result === true) {
                    // Add session ID
                    $data['session_id'] = $session_id;
                    
                    // Check for duplicates
                    if ($this->is_duplicate_prospect($data)) {
                        $stats['duplicate']++;
                        $this->add_rejected_record($data, 'Duplicate entry', $session_id);
                        continue;
                    }
                    
                    // Insert valid record
                    $insert_result = $this->wpdb->insert(
                        $this->temp_table_name,
                        $data,
                        ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
                    );
                    
                    if ($insert_result) {
                        $stats['valid']++;
                    } else {
                        $stats['invalid']++;
                        $this->add_rejected_record($data, 'Database insertion failed', $session_id);
                    }
                } else {
                    $stats['invalid']++;
                    $this->add_rejected_record($data, $validation_result, $session_id);
                }
            }

            fclose($handle);

            return [
                'session_id' => $session_id,
                'stats' => $stats
            ];

        } catch (\Exception $e) {
            error_log('Claims Angel Import Error: ' . $e->getMessage());
            return new \WP_Error('import_error', __('An error occurred during import.', 'claims-angel'));
        }
    }

    /**
     * Validate prospect data
     * @param array $data Prospect data to validate
     * @return true|string True if valid, error message if invalid
     */
private function validate_prospect_data(&$data) {
    // Validate business name
    if (empty($data['business_name'])) {
        return 'Business name is required';
    }

    // Validate country
    if (empty($data['country'])) {
        return 'Country is required';
    }

    // Normalize country: get ISO code using the Countries class
    $country_input = trim($data['country']);
    $country_code = $this->countries->find_country_code($country_input);
    if (!$country_code) {
        return sprintf('Invalid country: %s', $country_input);
    }
    $data['country'] = $country_code;

    // Validate web address if present
if (!empty($data['web_address'])) {
    // Normalize first
    $data['web_address'] = $this->web_address->normalize($data['web_address']);
    // Then validate the normalized URL
    if (!$this->web_address->validate($data['web_address'])) {
        return 'Invalid web address';
    }
}

    // Clean and standardize phone number if present using Countries::format_phone_number()
    if (!empty($data['phone_number'])) {
        $formatted_phone = $this->countries->format_phone_number($data['phone_number'], $data['country']);
        if (!$formatted_phone) {
            return 'Invalid phone number';
        }
        $data['phone_number'] = $formatted_phone;
    }

    return true;
}

    /**
     * Check if prospect already exists
     * @param array $data Prospect data to check
     * @return bool True if duplicate exists
     */
private function is_duplicate_prospect($data) {
    $duplicate_count = 0;

    // 1. Check for duplicate in temporary prospects table based on business_name and country.
    $query = $this->wpdb->prepare(
        "SELECT COUNT(*) FROM {$this->temp_table_name} WHERE business_name = %s AND country = %s",
        $data['business_name'],
        $data['country']
    );
    $duplicate_count += (int) $this->wpdb->get_var($query);

    // 2. Also check duplicate by normalized web_address in the temporary table (if provided).
    if (!empty($data['web_address'])) {
        $query = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->temp_table_name} WHERE web_address = %s",
            $data['web_address']
        );
        $duplicate_count += (int) $this->wpdb->get_var($query);
    }

    // 3. Check for duplicates in the 'business' custom post type.
    // We'll search for a post where either:
    //   - Both business_name and country match, or
    //   - The web_address matches (if provided)
    $meta_queries = [];

    // Query for matching business_name and country
    $meta_queries[] = [
        'relation' => 'AND',
        [
            'key'     => 'business_name',
            'value'   => $data['business_name'],
            'compare' => '='
        ],
        [
            'key'     => 'country',
            'value'   => $data['country'],
            'compare' => '='
        ]
    ];

    // Query for matching web_address (if provided)
    if (!empty($data['web_address'])) {
        $meta_queries[] = [
            'key'     => 'web_address',
            'value'   => $data['web_address'],
            'compare' => '='
        ];
    }

    $args = [
        'post_type'      => 'business',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_query'     => [
            'relation' => 'OR',
            ...$meta_queries
        ]
    ];
    $business_query = new \WP_Query($args);
    if ($business_query->have_posts()) {
        $duplicate_count++;
    }

    return $duplicate_count > 0;
}

    /**
     * Add record to rejected table
     * @param array $data Original data
     * @param string $reason Rejection reason
     * @param string $session_id Import session ID
     */
    private function add_rejected_record($data, $reason, $session_id) {
        $this->wpdb->insert(
            $this->rejected_table_name,
            [
                'session_id' => $session_id,
                'business_name' => $data['business_name'],
                'web_address' => isset($data['web_address']) ? $data['web_address'] : '',
                'phone_number' => isset($data['phone_number']) ? $data['phone_number'] : '',
                'linkedin_profile' => isset($data['linkedin_profile']) ? $data['linkedin_profile'] : '',
                'country' => $data['country'],
                'rejection_reason' => $reason,
                'original_data' => json_encode($data)
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Get rejected records for a session
     * @param string $session_id Session ID
     * @return array Array of rejected records
     */
    public function get_rejected_records($session_id) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->rejected_table_name} WHERE session_id = %s",
                $session_id
            )
        );
    }

    /**
     * Delete prospects for a specific country and session
     * 
     * @param string $country_code The country code to delete prospects for
     * @param string $session_id Optional session ID to limit deletion to
     * @return int|false Number of records deleted or false on error
     */
    public function delete_prospects_by_country($country_code, $session_id = '') {
        if (empty($country_code)) {
            return false;
        }

        $where = ['country = %s'];
        $params = [$country_code];
        
        if (!empty($session_id)) {
            $where[] = 'session_id = %s';
            $params[] = $session_id;
        }
        
        $query = "DELETE FROM {$this->temp_table_name} WHERE " . implode(' AND ', $where);
        
        return $this->wpdb->query($this->wpdb->prepare($query, $params));
    }

    /**
     * Handle POST request to delete prospects
     */
    public function handle_delete_prospects() {
        // Verify user has necessary permissions
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access', 'Error', [
                'response' => 403,
                'back_link' => true,
            ]);
        }

        // Verify nonce
        if (!isset($_POST['delete_prospects_nonce']) || 
            !wp_verify_nonce($_POST['delete_prospects_nonce'], 'delete_prospects_action')) {
            wp_die('Invalid security token', 'Error', [
                'response' => 403,
                'back_link' => true,
            ]);
        }

        // Get and validate parameters
        $country_code = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';

        if (empty($country_code)) {
            wp_die('Country code is required', 'Error', [
                'response' => 400,
                'back_link' => true,
            ]);
        }

        // Perform deletion
        $deleted = $this->delete_prospects_by_country($country_code, $session_id);

        if ($deleted === false) {
            // Log the error
            error_log(sprintf(
                '[Claims Angel Import] Failed to delete prospects | Country: %s | Session: %s | User: %s | Date: %s',
                $country_code,
                $session_id,
                wp_get_current_user()->user_login,
                current_time('mysql')
            ));

            wp_die('Failed to delete prospects', 'Error', [
                'response' => 500,
                'back_link' => true,
            ]);
        }

        // Log successful deletion
        error_log(sprintf(
            '[Claims Angel Import] Successfully deleted prospects | Country: %s | Session: %s | Count: %d | User: %s | Date: %s',
            $country_code,
            $session_id,
            $deleted,
            wp_get_current_user()->user_login,
            current_time('mysql')
        ));

        // Redirect back with success message
        $redirect_url = add_query_arg([
            'page' => 'prospect-import',
            'deleted' => $deleted,
            'country' => $country_code
        ], admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Export rejected records as CSV
     * @param string $session_id Session ID to export
     */
    public function export_rejected_records_csv($session_id) {
        $records = $this->get_rejected_records($session_id);
        
        if (empty($records)) {
            return;
        }
        
        $output = fopen('php://output', 'w');
        
        // Add headers
        fputcsv($output, [
            'Business Name',
            'Web Address',
            'Phone Number',
            'LinkedIn Profile',
            'Country',
            'Rejection Reason'
        ]);
        
        // Add records
        foreach ($records as $record) {
            fputcsv($output, [
                $record->business_name,
                $record->web_address,
                $record->phone_number,
                $record->linkedin_profile,
                $record->country,
                $record->rejection_reason
            ]);
        }
        
        fclose($output);
    }
}