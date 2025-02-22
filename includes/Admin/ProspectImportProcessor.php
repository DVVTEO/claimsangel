<?php
namespace ClaimsAngel\Admin;

use ClaimsAngel\Data\WebAddress;
use ClaimsAngel\Data\Countries;

class ProspectImportProcessor {
    private static $instance = null;
    private $required_columns = ['business_name', 'country'];
    private $allowed_columns = ['business_name', 'web_address', 'phone_number', 'linkedin_profile', 'country'];
    private $temp_table_name;
    private $wpdb;
    private $countries;

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
        $this->countries = Countries::get_instance();
        
        // Change to admin_init hook
        add_action('admin_init', array($this, 'create_temp_table'));
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

            // Read the CSV file
            $handle = fopen($file['tmp_name'], 'r');
            if ($handle === false) {
                return new \WP_Error('file_read_error', __('Unable to read the file.', 'claims-angel'));
            }

            // Read headers
            $headers = array_map('strtolower', array_map('trim', fgetcsv($handle)));

            // Validate headers
            foreach ($this->required_columns as $required) {
                if (!in_array($required, $headers)) {
                    fclose($handle);
                    return new \WP_Error(
                        'missing_column',
                        sprintf(__('Required column "%s" is missing.', 'claims-angel'), $required)
                    );
                }
            }

            // Process data rows
            $data = [];
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) === count($headers)) {
                    $data[] = array_combine($headers, array_map('trim', $row));
                }
            }
            fclose($handle);

            // Process the data
            $results = $this->process_data($data, $session_id);

            return [
                'success' => true,
                'session_id' => $session_id,
                'stats' => $results
            ];

        } catch (\Exception $e) {
            return new \WP_Error('import_failed', $e->getMessage());
        }
    }

    /**
     * Process extracted data
     * @param array $data Extracted data
     * @param string $session_id Session identifier
     * @return array Processing results
     */
    private function process_data($data, $session_id) {
        $web_address = WebAddress::get_instance();
        $results = ['valid' => 0, 'invalid' => 0, 'duplicate' => 0];

        foreach ($data as $row) {
            // First, validate and standardize the country
            $country_code = $this->validate_country($row['country']);
            if (!$country_code) {
                $results['invalid']++;
                continue;
            }

            // Clean and validate data
            $clean_data = [
                'business_name' => sanitize_text_field($row['business_name']),
                'web_address' => isset($row['web_address']) ? $web_address->normalize($row['web_address']) : '',
                'phone_number' => isset($row['phone_number']) ? $this->normalize_phone($row['phone_number'], $country_code) : '',
                'linkedin_profile' => isset($row['linkedin_profile']) ? esc_url_raw($row['linkedin_profile']) : '',
                'country' => $country_code,
                'session_id' => $session_id,
                'status' => 'pending'
            ];

            // Check for duplicates
            if ($this->is_duplicate($clean_data)) {
                $clean_data['status'] = 'duplicate';
                $results['duplicate']++;
            } else {
                $results['valid']++;
            }

            // Insert into temporary table
            $this->wpdb->insert(
                $this->temp_table_name,
                $clean_data,
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );
        }

        return $results;
    }

    /**
     * Create temporary table for import data
     */
    public function create_temp_table() {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->temp_table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(32) NOT NULL,
            business_name varchar(255) NOT NULL,
            web_address varchar(255),
            phone_number varchar(32),
            linkedin_profile varchar(255),
            country varchar(2) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY session_id (session_id),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Check if a record is duplicate
     * @param array $data Record data
     * @return bool Whether record exists
     */
    private function is_duplicate($data) {
        $query = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->temp_table_name} 
            WHERE business_name = %s AND country = %s",
            $data['business_name'],
            $data['country']
        );

        return (int) $this->wpdb->get_var($query) > 0;
    }

    /**
     * Validate and standardize country input
     * @param string $country_input Raw country input
     * @return string|false Standardized country code or false if invalid
     */
    private function validate_country($country_input) {
        // First try direct match with country code
        $country_code = strtoupper(trim($country_input));
        if ($this->countries->get_country($country_code)) {
            // Verify country is enabled
            if ($this->countries->is_country_enabled($country_code)) {
                return $country_code;
            }
            return false;
        }

        // Try to find country code by name
        $country_code = $this->countries->find_country_code($country_input);
        if ($country_code && $this->countries->is_country_enabled($country_code)) {
            return $country_code;
        }

        return false;
    }

    /**
     * Normalize phone number using country-specific formatting
     * @param string $phone Phone number
     * @param string $country_code Country code
     * @return string Normalized phone number
     */
    private function normalize_phone($phone, $country_code) {
        if (empty($phone)) {
            return '';
        }

        // Use the Countries class to format the phone number
        $formatted = $this->countries->format_phone_number($phone, $country_code);
        
        // If formatting fails, return empty string
        return $formatted !== null ? $formatted : '';
    }
}