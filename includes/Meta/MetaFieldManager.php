<?php
namespace ClaimsAngel\Meta;

/**
 * Meta Field Manager Class
 * 
 * Handles registration and management of custom meta fields for both
 * Business and Vehicle post types. Includes complex meta fields for
 * storing structured data like key people and call logs.
 * Created: 2025-02-21
 * Last Modified: 2025-02-21 19:35:19
 * Author: DVVTEO
 */
class MetaFieldManager {
    /**
     * Constructor
     * Sets up WordPress hooks for meta field registration and handling
     */
    public function __construct() {
        add_action('init', [$this, 'register_meta_fields']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_fields']);
    }

    /**
     * Register all meta fields for both post types
     * Defines the structure and properties of all custom meta fields
     */
    public function register_meta_fields() {
        // Business Meta Fields - Basic Information
        $this->register_business_basic_fields();
        // Business Meta Fields - Complex Data Structures
        $this->register_business_complex_fields();
        // Vehicle Meta Fields
        $this->register_vehicle_fields();
    }

    /**
     * Register basic business meta fields
     * These are simple string and integer fields
     */
    private function register_business_basic_fields() {
        $basic_fields = [
            'business_name' => 'string',
            'business_status' => 'string',
            'web_address' => 'string',
            'linkedin_profile' => 'string',
            'phone_number' => 'string',
            'country' => 'string',
            'date_created' => 'string',
            'prospect_prospector' => 'integer',
            'prospect_closer' => 'integer',
            'general_notes' => 'string'
        ];

        foreach ($basic_fields as $field => $type) {
            register_post_meta('business', $field, [
                'type' => $type,
                'single' => true,
                'show_in_rest' => true,
            ]);
        }
    }

    /**
     * Register complex business meta fields
     * These fields store structured data as arrays
     */
    private function register_business_complex_fields() {
        // Key People Schema
        register_post_meta('business', 'key_people', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => [
                'schema' => [
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'first_name' => ['type' => 'string'],
                            'last_name' => ['type' => 'string'],
                            'title' => ['type' => 'string'],
                            'email' => ['type' => 'string'],
                            'phone' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ]);

        // Call Back Reminders Schema
        register_post_meta('business', 'call_back_reminders', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => [
                'schema' => [
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'datetime' => ['type' => 'string'],
                            'notes' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ]);

        // Call Logs Schema
        register_post_meta('business', 'call_logs', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => [
                'schema' => [
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'datetime' => ['type' => 'string'],
                            'caller' => ['type' => 'string'],
                            'contact' => ['type' => 'string'],
                            'notes' => ['type' => 'string'],
                            'outcome' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Register vehicle meta fields
     * These fields store vehicle-specific information
     */
    private function register_vehicle_fields() {
        $vehicle_fields = [
            'make' => 'string',
            'model' => 'string',
            'year' => 'integer',
            'registration' => 'string',
            'vin' => 'string',
            'mileage' => 'integer',
            'service_history' => 'boolean',
            'last_service_date' => 'string',
            'next_service_due' => 'string',
            'notes' => 'string'
        ];

        foreach ($vehicle_fields as $field => $type) {
            register_post_meta('vehicle', $field, [
                'type' => $type,
                'single' => true,
                'show_in_rest' => true,
            ]);
        }
    }

    /**
     * Add meta boxes to post edit screens
     */
    public function add_meta_boxes() {
        add_meta_box(
            'business_details',
            __('Business Details', 'claims-angel'),
            [$this, 'render_business_meta_box'],
            'business',
            'normal',
            'high'
        );

        add_meta_box(
            'vehicle_details',
            __('Vehicle Details', 'claims-angel'),
            [$this, 'render_vehicle_meta_box'],
            'vehicle',
            'normal',
            'high'
        );
    }

    /**
     * Render business meta box
     * 
     * @param WP_Post $post Current post object
     */
    public function render_business_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('business_meta_box', 'business_meta_box_nonce');

        // Get current values
        $business_name = get_post_meta($post->ID, 'business_name', true);
        $business_status = get_post_meta($post->ID, 'business_status', true);
        $web_address = get_post_meta($post->ID, 'web_address', true);
        // Add other fields as needed

        // Include template
        include CLAIMS_ANGEL_PLUGIN_DIR . 'templates/admin/business-meta-box.php';
    }

    /**
     * Render vehicle meta box
     * 
     * @param WP_Post $post Current post object
     */
    public function render_vehicle_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('vehicle_meta_box', 'vehicle_meta_box_nonce');

        // Get current values
        $make = get_post_meta($post->ID, 'make', true);
        $model = get_post_meta($post->ID, 'model', true);
        $year = get_post_meta($post->ID, 'year', true);
        // Add other fields as needed

        // Include template
        include CLAIMS_ANGEL_PLUGIN_DIR . 'templates/admin/vehicle-meta-box.php';
    }

    /**
     * Save meta box data
     * 
     * @param int $post_id The ID of the post being saved
     */
    public function save_meta_fields($post_id) {
        // Security checks
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check post type and nonce
        $post_type = get_post_type($post_id);
        if ($post_type === 'business') {
            if (!isset($_POST['business_meta_box_nonce']) ||
                !wp_verify_nonce($_POST['business_meta_box_nonce'], 'business_meta_box')) {
                return;
            }
            $this->save_business_fields($post_id);
        } elseif ($post_type === 'vehicle') {
            if (!isset($_POST['vehicle_meta_box_nonce']) ||
                !wp_verify_nonce($_POST['vehicle_meta_box_nonce'], 'vehicle_meta_box')) {
                return;
            }
            $this->save_vehicle_fields($post_id);
        }
    }

    /**
     * Save business-specific fields
     * 
     * @param int $post_id The ID of the post being saved
     */
    private function save_business_fields($post_id) {
        $fields = [
            'business_name',
            'business_status',
            'web_address',
            'linkedin_profile',
            'phone_number',
            'country',
            'general_notes'
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Handle complex fields
        if (isset($_POST['key_people'])) {
            update_post_meta($post_id, 'key_people', $this->sanitize_array($_POST['key_people']));
        }
        if (isset($_POST['call_logs'])) {
            update_post_meta($post_id, 'call_logs', $this->sanitize_array($_POST['call_logs']));
        }
        if (isset($_POST['call_back_reminders'])) {
            update_post_meta($post_id, 'call_back_reminders', $this->sanitize_array($_POST['call_back_reminders']));
        }
    }

    /**
     * Save vehicle-specific fields
     * 
     * @param int $post_id The ID of the post being saved
     */
    private function save_vehicle_fields($post_id) {
        $fields = [
            'make',
            'model',
            'year',
            'registration',
            'vin',
            'mileage',
            'service_history',
            'last_service_date',
            'next_service_due',
            'notes'
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];
                if (in_array($field, ['year', 'mileage'])) {
                    $value = intval($value);
                } elseif ($field === 'service_history') {
                    $value = (bool)$value;
                } else {
                    $value = sanitize_text_field($value);
                }
                update_post_meta($post_id, $field, $value);
            }
        }
    }

    /**
     * Sanitize an array of data recursively
     * 
     * @param array $array The array to sanitize
     * @return array The sanitized array
     */
    private function sanitize_array($array) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->sanitize_array($value);
            } else {
                $array[$key] = sanitize_text_field($value);
            }
        }
        return $array;
    }
}