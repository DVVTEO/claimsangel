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
        add_filter('rest_prepare_business', [$this, 'modify_business_rest_response'], 10, 3);
    }

    function modify_business_rest_response($response, $post, $request)
    {
        foreach ($request['meta'] as $meta_key => $meta_value) {
            if (in_array($meta_key, ['general_notes'])) {
                update_post_meta($post->ID, $meta_key, $meta_value);
            }
        }

        // Example: Append text to 'business_name'
        if (!empty($response->data['meta']['business_name'])) {
            $response->data['meta']['general_notes'] = get_post_meta($post->ID, 'general_notes', true);
        }

        return $response;
    }
    /**
     * Register all meta fields for both post types
     * Defines the structure and properties of all custom meta fields
     */
    public function register_meta_fields() {
        // Unregister date_created first
        unregister_meta_key('post', 'date_created', 'business');
        
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
            'prospect_prospector' => 'integer',
            'prospect_closer' => 'integer',
            'general_notes' => 'string'
        ];

        foreach ($basic_fields as $field => $type) {
            register_post_meta('business', $field, [
                'type'              => $type,
                'single'            => true,
                'default'           => '',
                'sanitize_callback' => ($type === 'string' ? 'sanitize_text_field' : null),
                'auth_callback'     => function($allowed, $meta_key, $object_id, $request) {
                    return current_user_can('edit_post', $object_id);
                },
                'show_in_rest'      => [
                    'schema' => [
                        'description' => ucfirst(str_replace('_', ' ', $field)),
                        'type'        => $type,
                        'context'     => [ 'view', 'edit' ],
                    ],
                ],
            ]);
        }

        // Register date_created separately with timestamp configuration
        register_post_meta('business', 'date_created', [
            'type'              => 'integer',
            'description'       => 'Date when the business was created, stored as Unix timestamp',
            'single'            => true,
            'sanitize_callback' => 'absint',
            'auth_callback'     => function($allowed, $meta_key, $object_id, $request) {
                return current_user_can('edit_post', $object_id);
            },
            'show_in_rest'      => true,
        ]);
    }

    /**
     * Register complex business meta fields
     * These fields store structured data as arrays
     */
    private function register_business_complex_fields() {
        // Key People Schema
        register_post_meta('business', 'key_people', [
            'type'         => 'array',
            'single'       => true,
            'auth_callback'=> function($allowed, $meta_key, $object_id, $request) {
                return current_user_can('edit_post', $object_id);
            },
            'show_in_rest' => [
                'schema' => [
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'first_name' => ['type' => 'string'],
                            'last_name'  => ['type' => 'string'],
                            'title'      => ['type' => 'string'],
                            'email'      => ['type' => 'string'],
                            'phone'      => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ]);

        // Call Back Reminders Schema
        register_post_meta('business', 'call_back_reminders', [
            'type'         => 'array',
            'single'       => true,
            'auth_callback'=> function($allowed, $meta_key, $object_id, $request) {
                return current_user_can('edit_post', $object_id);
            },
            'show_in_rest' => [
                'schema' => [
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'datetime' => ['type' => 'string'],
                            'notes'    => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ]);

        // Call Logs Schema
        register_post_meta('business', 'call_logs', [
            'type'         => 'array',
            'single'       => true,
            'auth_callback'=> function($allowed, $meta_key, $object_id, $request) {
                return current_user_can('edit_post', $object_id);
            },
            'show_in_rest' => [
                'schema' => [
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'timestamp' => ['type' => 'integer'],
                            'user_id'   => ['type' => 'integer'],
                            'notes'     => ['type' => 'string'],
                            'outcome'   => ['type' => 'string'],
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
            'vehicle_brand'                         => 'string',
            'vehicle_type'                          => 'string',
            'vehicle_chassis_number'                => 'string',
            'vehicle_registration_number'           => 'string',
            'vehicle_purchase_type'                 => 'string',
            'vehicle_purchase_new_used'             => 'string',
            'vehicle_purchase_acquistion_startdate' => 'string',
            'vehicle_purchase_acquistion_enddate'   => 'string',
            'vehicle_purchase_acquistion_monthly_payment' => 'number',
            'vehicle_purchase_acquistion_totalpaymentsmade' => 'number',
            'transaction_order_date'                => 'string',
            'transaction_invoice_date'              => 'string',
            'transaction_invoice_number'            => 'string',
            'transaction_purchase_amount'           => 'number',
            'transaction_currency'                  => 'string',
            'transaction_payment_terms'             => 'string',
            'vehicle_sale_date'                     => 'string',
            'vehicle_usage_duration'                => 'string',
            'vehicle_termination_document'          => 'string',
            'vehicle_bankstatement'                 => 'string',
            'vehicle_registration_certificate'      => 'string'
        ];

        foreach ($vehicle_fields as $field => $type) {
            register_post_meta('vehicle', $field, [
                'type'              => $type,
                'single'            => true,
                'auth_callback'     => function($allowed, $meta_key, $object_id, $request) {
                    return current_user_can('edit_post', $object_id);
                },
                'show_in_rest'      => true,
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

        // Get current values based on registered fields
        $vehicle_brand = get_post_meta($post->ID, 'vehicle_brand', true);
        $vehicle_type = get_post_meta($post->ID, 'vehicle_type', true);
        $vehicle_chassis_number = get_post_meta($post->ID, 'vehicle_chassis_number', true);
        $vehicle_registration_number = get_post_meta($post->ID, 'vehicle_registration_number', true);
        $vehicle_purchase_type = get_post_meta($post->ID, 'vehicle_purchase_type', true);
        $vehicle_purchase_new_used = get_post_meta($post->ID, 'vehicle_purchase_new_used', true);
        $vehicle_purchase_acquistion_startdate = get_post_meta($post->ID, 'vehicle_purchase_acquistion_startdate', true);
        $vehicle_purchase_acquistion_enddate = get_post_meta($post->ID, 'vehicle_purchase_acquistion_enddate', true);
        $vehicle_purchase_acquistion_monthly_payment = get_post_meta($post->ID, 'vehicle_purchase_acquistion_monthly_payment', true);
        $vehicle_purchase_acquistion_totalpaymentsmade = get_post_meta($post->ID, 'vehicle_purchase_acquistion_totalpaymentsmade', true);
        $transaction_order_date = get_post_meta($post->ID, 'transaction_order_date', true);
        $transaction_invoice_date = get_post_meta($post->ID, 'transaction_invoice_date', true);
        $transaction_invoice_number = get_post_meta($post->ID, 'transaction_invoice_number', true);
        $transaction_purchase_amount = get_post_meta($post->ID, 'transaction_purchase_amount', true);
        $transaction_currency = get_post_meta($post->ID, 'transaction_currency', true);
        $transaction_payment_terms = get_post_meta($post->ID, 'transaction_payment_terms', true);
        $vehicle_sale_date = get_post_meta($post->ID, 'vehicle_sale_date', true);
        $vehicle_usage_duration = get_post_meta($post->ID, 'vehicle_usage_duration', true);
        $vehicle_termination_document = get_post_meta($post->ID, 'vehicle_termination_document', true);
        $vehicle_bankstatement = get_post_meta($post->ID, 'vehicle_bankstatement', true);
        $vehicle_registration_certificate = get_post_meta($post->ID, 'vehicle_registration_certificate', true);

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
            'vehicle_brand',
            'vehicle_type',
            'vehicle_chassis_number',
            'vehicle_registration_number',
            'vehicle_purchase_type',
            'vehicle_purchase_new_used',
            'vehicle_purchase_acquistion_startdate',
            'vehicle_purchase_acquistion_enddate',
            'vehicle_purchase_acquistion_monthly_payment',
            'vehicle_purchase_acquistion_totalpaymentsmade',
            'transaction_order_date',
            'transaction_invoice_date',
            'transaction_invoice_number',
            'transaction_purchase_amount',
            'transaction_currency',
            'transaction_payment_terms',
            'vehicle_sale_date',
            'vehicle_usage_duration',
            'vehicle_termination_document',
            'vehicle_bankstatement',
            'vehicle_registration_certificate'
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];
                // For fields expected to be numbers, ensure conversion
                if (in_array($field, [
                    'vehicle_purchase_acquistion_monthly_payment',
                    'vehicle_purchase_acquistion_totalpaymentsmade',
                    'transaction_purchase_amount'
                ])) {
                    $value = floatval($value);
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