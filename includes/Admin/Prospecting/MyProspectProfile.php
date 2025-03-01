<?php
/*
Plugin Name: Business CRM Profile Page (Optimized)
Description: Adds a gorgeous business profile page with CRM capabilities (General Notes, Call Logging, Reminder Logging, Key People) to the WordPress backend.
Version: 1.1
Author: Your Name
*/

namespace ClaimsAngel\Admin\Prospecting;

// Exit if accessed directly.
if (!defined("ABSPATH")) {
    exit();
}

class MyProspectProfile
{
    /**
     * Singleton instance.
     *
     * @var NewProspectProfile|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @return NewProspectProfile
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        // Core WordPress hooks
        add_action('add_myprospect_menu', [$this, 'add_myprospect_menu']);
        add_action("admin_enqueue_scripts", [$this, "enqueue_admin_assets"]);

        // Register AJAX handlers - Call Logs
        add_action("wp_ajax_update_call_log", [$this, "ajax_update_call_log"]);

        // Register AJAX handlers - General
        add_action("wp_ajax_call_prospect_now", [
            $this,
            "ajax_call_prospect_now",
        ]);
        add_action("wp_ajax_save_general_notes", [
            $this,
            "ajax_save_general_notes",
        ]);

        // Register AJAX handlers - Key People
        add_action("wp_ajax_save_key_person", [$this, "ajax_save_key_person"]);
        add_action("wp_ajax_get_key_people", [$this, "ajax_get_key_people"]);
        add_action("wp_ajax_delete_key_person", [
            $this,
            "ajax_delete_key_person",
        ]);

        // Register AJAX handlers - Call Reminders
        add_action("wp_ajax_save_call_back_reminder", [
            $this,
            "ajax_save_call_back_reminder",
        ]);
        add_action("wp_ajax_get_call_reminders", [
            $this,
            "ajax_get_call_reminders",
        ]);
        add_action("wp_ajax_delete_call_reminder", [
            $this,
            "ajax_delete_call_reminder",
        ]);
        
        // Delete the business
        add_action("wp_ajax_delete_prospect_meta", [$this, "ajax_delete_prospect_meta"]);
        
        // Update general Information
        add_action("wp_ajax_update_general_info", [$this, "ajax_update_general_info"]);
    }

    //-----------------------------------------------------------------------------------
    // CORE ADMIN SETUP METHODS
    //-----------------------------------------------------------------------------------

    /**
     * Register the custom admin page.
     */
    public function add_myprospect_menu() {
    // Make sure this slug matches exactly with what's in the parent menu
    $parent_slug = 'prospects';
    
        add_submenu_page(
            $parent_slug,                     // Parent slug
            __('My Prospect Profile', 'claims-angel'), // Page title
            $submenu_title,                   // Menu title with count
            'manage_options',                 // Capability
            'my-prospect-profile',                   // Submenu slug (used in your render_admin_page callback)
            [$this, 'render_my_prospect_profile'],     // Callback function
        );
    
    // Add a fix for the URL issue
    add_action('admin_init', function() {
        global $pagenow;
        if ($pagenow === 'my-prospect-profile') {
            wp_redirect(admin_url('admin.php?page=my-prospect-profile'));
            exit;
        }
    });

        
            // Use the AdminAccessController to restrict access to administrators only.
    \ClaimsAngel\Admin\AdminAccessController::get_instance()->register_admin_page(
        'my-prospect-profile',
        ['administrator'], // Only allow administrators.
        [
            'page_title' => 'My Prospect Profile',
            'menu_title' => 'My Prospect Profile',
            'menu_slug'  => 'my-prospect-profile'
        ],
        false // not hidden
    );
    }

    /**
     * Get the count of available prospects for a given country code.
     * 
     * @param string $user_country_code The country code to filter by
     * @return int The count of available prospects
     */
    private function get_prospect_count($user_country_code) {
        // Create a unique cache key based on country code
        $cache_key = 'prospect_count_' . $user_country_code;
        
        // Try to get the cached count first
        $count = wp_cache_get($cache_key);
        
        // If not cached, query the database
        if (false === $count) {
            // Query for IDs only - much faster
            $args = [
                'post_type'      => 'business',
                'posts_per_page' => -1, // Get all for counting
                'fields'         => 'ids', // Only get IDs
                'meta_query'     => [
                    'relation' => 'AND',
                    [
                        'key'     => 'business_status',
                        'value'   => 'prospect',
                        'compare' => '='
                    ],
                    [
                        'relation' => 'OR',
                        [
                            'key'     => 'prospect_prospector',
                            'compare' => 'NOT EXISTS'
                        ],
                        [
                            'key'     => 'prospect_prospector',
                            'value'   => '',
                            'compare' => '='
                        ]
                    ],
                    [
                        'key'     => 'country',
                        'value'   => $user_country_code,
                        'compare' => '='
                    ],
                    [
                        'relation' => 'OR',
                        [
                            'key'     => 'business_delete',
                            'compare' => 'NOT EXISTS'
                        ],
                        [
                            'key'     => 'business_delete',
                            'value'   => '',
                            'compare' => '='
                        ]
                    ]
                ]
            ];
            
            $query = new \WP_Query($args);
            $count = $query->found_posts;
            wp_reset_postdata();
            
            // Cache the result for 5 minutes
            wp_cache_set($cache_key, $count, '', 300);
        }
        
        return $count;
    }

    /**
     * Enqueue custom scripts and styles for our admin page.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_admin_assets($hook)
    {
        // Only load assets on our custom page.
        if ($hook !== "prospects_page_my-prospect-profile") {
            return;
        }

    // Enqueue your CSS file.
    wp_enqueue_style(
        "jquery-ui-css",
        "https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css"
    );
    // Enqueue necessary jQuery UI scripts.
    wp_enqueue_script("jquery-ui-dialog");
    wp_enqueue_script("jquery-ui-datepicker");

    // Enqueue your custom JavaScript.
    wp_enqueue_script(
        "pagedata-js",
        plugin_dir_url(__FILE__) . "pagedata.js",
        ["jquery"],
        "1.0",
        true
    );

        // Localize script with nonces for AJAX calls
        wp_localize_script("pagedata-js", "myAjaxSettings", [
            "saveNonce" => wp_create_nonce("save_call_back_reminder"),
            "getNonce" => wp_create_nonce("get_call_reminders_nonce"),
            "deleteNonce" => wp_create_nonce("delete_call_reminder"),
            "keyPersonNonce" => wp_create_nonce("save_key_person"),
            "deleteKeyPersonNonce" => wp_create_nonce("delete_key_person"),
            "getKeyPeopleNonce" => wp_create_nonce("get_key_people_nonce"),
            "callProspectNonce" => wp_create_nonce("call_prospect_now"),
            "updateCallLogNonce" => wp_create_nonce("update_call_log"),
            "deleteProspectNonce" => wp_create_nonce("delete_prospect_meta_nonce"),
            "updateGeneralInfoNonce" => wp_create_nonce("update_general_info_nonce")
        ]);
    }

    //-----------------------------------------------------------------------------------
    // OPTIMIZED PROSPECT RETRIEVAL METHODS
    //-----------------------------------------------------------------------------------

    /**
     * Get the next available prospect ID
     * 
     * @param string $user_country_code User's country code
     * @param int $current_user_id Current user ID
     * @return int|false Post ID or false if none found
     */
    private function get_next_available_prospect($user_country_code, $current_user_id) {
        // Get the last prospect ID this user viewed
        $last_viewed_id = get_user_meta($current_user_id, 'last_viewed_prospect', true);
        
        // Query for IDs only - much faster
        $args = [
            'post_type'      => 'business',
            'posts_per_page' => 1,
            'fields'         => 'ids', // Only get IDs
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => 'business_status',
                    'value'   => 'prospect',
                    'compare' => '='
                ],
                [
                    'relation' => 'OR',
                    [
                        'key'     => 'prospect_prospector',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key'     => 'prospect_prospector',
                        'value'   => '',
                        'compare' => '='
                    ]
                ],
                [
                    'key'     => 'country',
                    'value'   => $user_country_code,
                    'compare' => '='
                ],
                [
                    'relation' => 'OR',
                    [
                        'key'     => 'business_delete',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key'     => 'business_delete',
                        'value'   => '',
                        'compare' => '='
                    ]
                ]
            ]
        ];
        
        // If we have a last viewed ID, get the next one after that
        if (!empty($last_viewed_id)) {
            // Find the next ID greater than the last viewed
            $args['post__not_in'] = [$last_viewed_id]; 
        }
        
        // Add orderby ID to get the "next" one consistently
        $args['orderby'] = 'ID';
        $args['order'] = 'ASC';
        
        $query = new \WP_Query($args);
        
        if ($query->have_posts()) {
            // Get the ID of the prospect
            $prospect_id = $query->posts[0];
            
            // Update the last viewed ID for this user
            update_user_meta($current_user_id, 'last_viewed_prospect', $prospect_id);
            
            // Update the prospector ID for this prospect
            update_post_meta($prospect_id, 'prospect_prospector', $current_user_id);
            
            return $prospect_id;
        }
        
        // If we've gone through all records, start over
        if (!empty($last_viewed_id)) {
            // Clear the last viewed ID
            delete_user_meta($current_user_id, 'last_viewed_prospect');
            
            // Try again without the exclusion
            return $this->get_next_available_prospect($user_country_code, $current_user_id);
        }
        
        return false;
    }

    /**
 * Get all necessary prospect data after we have the ID using batch metadata retrieval
 * 
 * @param int $prospect_id The prospect post ID
 * @return array|false Associative array of prospect data or false if invalid ID
 */
private function get_prospect_data($prospect_id) {
    if (!$prospect_id) {
        return false;
    }
    
    global $wpdb;
    
    // Fetch all metadata for this prospect in a single query
    $all_meta = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d",
            $prospect_id
        ),
        ARRAY_A
    );
    
    // Initialize data with default values
    $data = [
        'prospect_id'         => $prospect_id,
        'business_name'       => '',
        'web_address'         => '',
        'phone_number'        => '',
        'linkedin_profile'    => '',
        'date_created'        => current_time("timestamp"),
        'general_notes'       => '',
        'key_people'          => [],
        'call_logs'           => [],
        'call_back_reminders' => []
    ];
    
    // Process all meta values from the single query result
    foreach ($all_meta as $meta) {
        $key = $meta['meta_key'];
        $value = $meta['meta_value'];
        
        // Process serialized data for array values
        if (in_array($key, ['key_people', 'call_logs', 'call_back_reminders'])) {
            $value = maybe_unserialize($value);
            if (!is_array($value)) {
                $value = [];
            }
        }
        
        // Only save the keys we're interested in
        if (array_key_exists($key, $data)) {
            $data[$key] = $value;
        }
    }
    
    return $data;
}

    //-----------------------------------------------------------------------------------
    // PAGE RENDERING
    //-----------------------------------------------------------------------------------

    /**
     * Render the custom admin page.
     */
    public function render_my_prospect_profile()
    {
        // --- Begin: Optimized Query for getting a prospect ---
        $current_user_id = get_current_user_id();
        $user_country = get_user_meta($current_user_id, "user_country", true);
        $countries = \ClaimsAngel\Data\Countries::get_instance();
        $user_country_code = $countries->find_country_code($user_country);

        // Set default fallback values.
        $business_name = __("Business Name", "claims-angel");
        $web_address = __("Web Address", "claims-angel");
        $phone_number = __("(555) 123-4567", "claims-angel");
        $linkedin_profile = __("LinkedIn Profile", "claims-angel");
        $date_created = current_time("timestamp");
        $general_notes = "";
        $key_people = [];
        $call_logs = [];
        $call_back_reminders = [];
        $prospect_id = 0;

        if ($user_country_code) {
            // Get the next available prospect ID
            $prospect_id = $this->get_next_available_prospect($user_country_code, $current_user_id);
            
            // In the render_admin_page() method, after getting the prospect data
    if ($prospect_id) {
    // Check for AirCall credentials before proceeding
    $aircall_user_id = get_user_meta($current_user_id, "AirCallUserID", true);
    $aircall_phone_id = get_user_meta($current_user_id, "AirCallPhoneID", true);
    
    if (empty($aircall_user_id) || empty($aircall_phone_id)) {
        echo '<div style="background-color:#f1f1f1; padding:20px; text-align:center; color:#d63638; font-weight:bold;">' 
            . esc_html__('Please Connect your Aircall Account', 'claims-angel') 
            . '</div>';
        return;
    }
    
    // Get all the needed prospect data in one go
    $prospect_data = $this->get_prospect_data($prospect_id);
    
    if ($prospect_data) {
        // Extract variables from the prospect data for use in the template
        $business_name = $prospect_data['business_name'];
        $web_address = $prospect_data['web_address'];
        $phone_number = $prospect_data['phone_number'];
        $linkedin_profile = $prospect_data['linkedin_profile'];
        $date_created = $prospect_data['date_created'];
        $general_notes = $prospect_data['general_notes'];
        $key_people = $prospect_data['key_people'];
        $call_logs = $prospect_data['call_logs'];
        $call_back_reminders = $prospect_data['call_back_reminders'];
}
            } else {
                // No prospects found: display the message
                echo '<div style="background-color:#f1f1f1; padding:20px; text-align:center;">' 
                    . esc_html__('No New Prospects At This Time', 'claims-angel') 
                    . '</div>';
                return;
            }
        } else {
            // No country code: display an error
            echo '<div style="background-color:#f1f1f1; padding:20px; text-align:center; color:#d63638; font-weight:bold;">' 
                . esc_html__('Country setting missing', 'claims-angel') 
                . '</div>';
            return;
        }

        // Format the date as dd/mm/yy.
        $formatted_date = !empty($date_created)
            ? date("d/m/y", $date_created)
            : "";
        $days_ago = !empty($date_created)
            ? floor(
                (current_time("timestamp") - $date_created) / (60 * 60 * 24)
            )
            : 0;
        // --- End: Query for prospect ---
        ?>
        
        <div style="position: absolute; top: 20px; right: 20px;">
    <button class="button" onclick="window.location.reload();">Next Prospect</button>
</div>
        
        <div class="wrap bc-wrap">
            <!-- The <h1> is now the business name -->
            <h1><?php echo esc_html($business_name); ?></h1>

<h3 style="margin-top:25px">Take Action</h3>
<div style="display: flex; gap: 10px; align-items: center; margin-top: 10px; margin-bottom: 50px;">
    <?php
    $button_disabled = empty($phone_number) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '';
    ?>
    <form id="call-prospect-form" method="post">
    <?php wp_nonce_field("call_prospect_now", "call_prospect_now_nonce"); ?>
    <!-- Hidden input field now contains the prospect's phone number -->
    <input type="hidden" name="prospect_phone" value="<?php echo esc_attr($phone_number); ?>">
    <input type="hidden" name="prospect_id" value="<?php echo esc_attr($prospect_id); ?>">
    <input type="submit" name="call_prospect_now" class="button button-primary" value="Call Prospect Now" <?php echo $button_disabled; ?>>
</form>
    
<div id="reminder-lightbox" title="Set A Call Reminder" style="display:none; padding:20px">
  <form id="reminder-form">
    <p>
      <label for="reminder_date">Reminder Date:</label><br>
      <input type="text" id="reminder_date" name="reminder_date" placeholder="Select a date">
    </p>
    <br/>
    <p>
      <label for="reminder_notes">Notes:</label><br>
      <textarea id="reminder_notes" name="reminder_notes" rows="4" cols="30" placeholder="Enter your notes here"></textarea>
    </p>
    <p>
        
      <button type="button" id="save-reminder" class="button">Save</button>
    </p>
  </form>
</div>
    
        <!-- Email Information (Yellow) -->
    <form id="email-information-form" method="post" style="display:inline-block; margin-left: 10px;">
        <?php wp_nonce_field("email_information", "email_information_nonce"); ?>
        <!-- Hidden input field now contains the prospect's email (using the current user's email as a placeholder) -->
        <input type="submit" name="email_information" class="button" style="background-color: #ffde21; color: #000;" value="Call Reminder" id="callreminder">
</form>
    
            <!-- Interested Button -->
<form id="prospect-interested" method="post" style="display:inline-block; margin-left: 10px;">
    <input type="submit" name="email_information" class="button" style="background-color: #60b317; color: #FFF;" value="Interested">
</form>
    
    <!-- Delete Button -->
<form id="prospect-delete" method="post" style="display:inline-block; margin-left: 10px;">
    <input type="submit" name="delete_prospect" class="button" style="background-color: #d63638; color: #FFF;" value="Delete">
</form>
    
</div>

<!-- Delete Section: Hidden by default -->
<div id="delete-section" style="display:none; width: 100%; max-width: 550px; background-color: #ffe5e5; padding: 20px; margin-bottom: 20px;">
    <h3 style="margin-top: 0px">Delete Prospect</h3>
    <form id="delete-prospect-form" style="display: inline-block;">
        <!-- Hidden field with the prospect id -->
        <input type="hidden" id="delete_prospect_id" value="<?php echo esc_attr($prospect_id); ?>">
        <select name="delete_reason" id="delete_reason">
            <option value="">Select reason</option>
            <option value="not interested">Not Interested</option>
            <option value="do not call request">Do Not Call Request</option>
            <option value="not eligible">Not Eligible</option>
            <option value="legal action taken">Legal Action Taken</option>
            <option value="unresponsive">Unresponsive</option>
            <option value="other">Other</option>
        </select>
        <button type="button" id="save-delete" class="button button-primary" style="margin-left:10px;">Save</button>
        <button type="button" id="cancel-delete" class="button" style="margin-left:10px;">Cancel</button>
    </form>
</div>

<div style="display: flex; align-items: center; gap: 20px;">
  <h3 style="margin: 0;">General Information</h3>
  <button id="toggle-general-info-edit" class="button">Edit Info</button>
</div>

<!-- Toggleable Edit Section (hidden by default) -->
<div id="general-info-edit-section" style="display: none; margin-top: 10px; border: 1px solid #ccc; padding: 15px;">
  <form id="general-info-edit-form">
    <input type="hidden" name="prospect_id" value="<?php echo esc_attr($prospect_id); ?>">
    <p>
      <label for="edit_business_name">Business Name:</label><br>
      <input type="text" id="edit_business_name" name="business_name" value="<?php echo esc_attr($business_name); ?>">
    </p>
    <p>
      <label for="edit_web_address">Web Address:</label><br>
      <input type="url" id="edit_web_address" name="web_address" value="<?php echo esc_attr($web_address); ?>">
    </p>
    <p>
      <label for="edit_phone_number">Phone Number:</label><br>
      <input type="text" id="edit_phone_number" name="phone_number" value="<?php echo esc_attr($phone_number); ?>">
    </p>
    <p>
      <label for="edit_linkedin_profile">LinkedIn Profile:</label><br>
      <input type="url" id="edit_linkedin_profile" name="linkedin_profile" value="<?php echo esc_attr($linkedin_profile); ?>">
    </p>
    <p>
      <button type="button" id="save-general-info" class="button button-primary">Save</button>
      <button type="button" id="cancel-general-info" class="button">Cancel</button>
    </p>
  </form>
</div>
            <!-- Prospect Data Block -->
<div style="width: 100%; max-width: 600px; margin: 20px 0; font-family: Arial, sans-serif; border-collapse: collapse; border: 1px solid #ccc; background: #fff; text-align: left; margin-bottom: 50px;">
    <table style="width: 100%; border-collapse: collapse; margin-left: 0;">
        <tr>
            <td style="padding: 10px; border-bottom: 1px solid #ddd; color: #23282d; font-weight: 500;"><strong><?php esc_html_e(
                "Web Address:",
                "claims-angel"
            ); ?></strong></td>
            <td style="padding: 10px; border-bottom: 1px solid #ddd;"><a href="<?php echo esc_url(
                $web_address
            ); ?>" target="_blank" style="color: #0073aa; text-decoration: none; font-weight: 500;"> <?php echo esc_html(
    $web_address
); ?></a></td>
        </tr>
        <tr style="background-color: #fafafa;">
            <td style="padding: 10px; border-bottom: 1px solid #ddd; color: #23282d; font-weight: 500;"><strong><?php esc_html_e(
                "Phone Number:",
                "claims-angel"
            ); ?></strong></td>
            <td style="padding: 10px; border-bottom: 1px solid #ddd;"><a href="tel:<?php echo esc_attr(
                preg_replace("/\D/", "", $phone_number)
            ); ?>" style="color: #0073aa; text-decoration: none; font-weight: 500;"> <?php echo esc_html(
    $phone_number
); ?></a></td>
        </tr>
        <tr>
            <td style="padding: 10px; border-bottom: 1px solid #ddd; color: #23282d; font-weight: 500;"><strong><?php esc_html_e(
                "LinkedIn Profile:",
                "claims-angel"
            ); ?></strong></td>
            <td style="padding: 10px; border-bottom: 1px solid #ddd;"><a href="<?php echo esc_url(
                $linkedin_profile
            ); ?>" target="_blank" style="color: #0073aa; text-decoration: none; font-weight: 500;"> <?php echo esc_html(
    $linkedin_profile
); ?></a></td>
        </tr>
        <tr style="background-color: #fafafa;">
            <td style="padding: 10px; color: #23282d; font-weight: 500;"><strong><?php esc_html_e(
                "Date Created:",
                "claims-angel"
            ); ?></strong></td>
            <td style="padding: 10px;"> <?php echo esc_html(
                $formatted_date
            ); ?> (<?php echo esc_html($days_ago); ?> days ago)</td>
        </tr>
    </table>
</div>

            <!-- CRM Modules Tabs with Icons -->
            <h2 class="nav-tab-wrapper">
                <a href="#" class="nav-tab nav-tab-active" data-tab="notes">
                    <span class="dashicons dashicons-edit"></span> <?php esc_html_e(
                        "General Notes",
                        "claims-angel"
                    ); ?>
                </a>
                <a href="#" class="nav-tab" data-tab="key_people">
                    <span class="dashicons dashicons-admin-users"></span> <?php esc_html_e(
                        "Key People",
                        "claims-angel"
                    ); ?>
                </a>
                <a href="#" class="nav-tab" data-tab="calls">
                    <span class="dashicons dashicons-phone"></span> <?php esc_html_e(
                        "Call History",
                        "claims-angel"
                    ); ?>
                </a>
                <a href="#" class="nav-tab" data-tab="reminders">
                    <span class="dashicons dashicons-calendar"></span> <?php esc_html_e(
                        "Call Back Reminders",
                        "claims-angel"
                    ); ?>
                </a>
            </h2>

            <!-- Tab Content: General Notes -->
            <div class="bc-tab-content" id="notes">
<h3><?php esc_html_e("General Notes", "claims-angel"); ?></h3>
                <form id="note-form" method="post" action="">
                    <!-- Include prospect ID and the new nonce for AJAX saving -->
                    <input type="hidden" name="prospect_id" value="<?php echo esc_attr(
                        $prospect_id
                    ); ?>">
                    <input type="hidden" name="nonce" id="save_general_notes_nonce" value="<?php echo esc_attr(
                        wp_create_nonce("save_general_notes")
                    ); ?>">
                    <textarea name="note_content" id="note_content" rows="8" style="width:500px; height:200px;" placeholder="<?php esc_attr_e(
                        "Enter your note here...",
                        "claims-angel"
                    ); ?>"><?php echo esc_textarea(
    $general_notes
); ?></textarea>
                    <br>
                    <button type="submit" class="button button-primary"><?php esc_html_e(
                        "Update Note",
                        "claims-angel"
                    ); ?></button>
                </form>
            </div>

<!-- Tab Content: Key People -->
<div class="bc-tab-content" id="key_people" style="display:none;">
    <h3><?php esc_html_e("Key People", "claims-angel"); ?></h3>
    <!-- New Key Person Form (AJAX) -->
    <div class="bc-key-people-form">
        <form id="new-key-person-form" method="post">
            <input type="hidden" name="prospect_id" value="<?php echo esc_attr(
                $prospect_id
            ); ?>">
            <input type="text" name="kp_first_name" placeholder="<?php esc_attr_e(
                "First Name",
                "claims-angel"
            ); ?>" required>
            <input type="text" name="kp_last_name" placeholder="<?php esc_attr_e(
                "Last Name",
                "claims-angel"
            ); ?>" required>
            <input type="text" name="kp_role" placeholder="<?php esc_attr_e(
                "Role",
                "claims-angel"
            ); ?>" required>
            <input type="email" name="kp_email" placeholder="<?php esc_attr_e(
                "Email Address",
                "claims-angel"
            ); ?>" required>
            <input type="tel" name="kp_phone" placeholder="<?php esc_attr_e(
                "Phone Number",
                "claims-angel"
            ); ?>" required>
            <button type="submit" class="button"><?php esc_html_e(
                "Add Key Person",
                "claims-angel"
            ); ?></button>
        </form>
    </div>
    
    <!-- Container for the key people table -->
    <div id="key-people-list" style="margin-top: 15px">
        <?php echo $this->render_key_people_table($key_people); ?>
    </div>
</div>

<!-- Tab Content: Call Logging -->
<div class="bc-tab-content" id="calls" style="display:none;">
    <h3><?php esc_html_e("Call Log", "claims-angel"); ?></h3>
    <div id="call-logs-container">
        <?php
        $call_logs = get_post_meta($prospect_id, "call_logs", true);
        if (!is_array($call_logs)) {
            $call_logs = [];
        }
        echo $this->render_call_logs_table($call_logs);
        ?>
    </div>
</div>

<!-- Tab Content: Reminder Logging -->
<div class="bc-tab-content" id="reminders" style="display:none;">
    <h3><?php esc_html_e("Call Reminder", "claims-angel"); ?></h3>
    <div id="reminders-list">
        <?php
        // Retrieve the call_back_reminders meta field.
        $call_back_reminders = get_post_meta(
            $prospect_id,
            "call_back_reminders",
            true
        );
        if (!is_array($call_back_reminders)) {
            $call_back_reminders = [];
        }
        echo $this->render_reminders_table($call_back_reminders);?>
    </div>

</div>
        <?php
    }

    //-----------------------------------------------------------------------------------
    // GENERAL NOTES AJAX HANDLERS
    //-----------------------------------------------------------------------------------

    /**
     * AJAX handler for saving general notes.
     */
    public function ajax_save_general_notes()
    {
        // Verify nonce.
        if (
            !isset($_POST["nonce"]) ||
            !wp_verify_nonce($_POST["nonce"], "save_general_notes")
        ) {
            wp_send_json_error("Invalid nonce.");
        }

        // Validate prospect id.
        $prospect_id = isset($_POST["prospect_id"])
            ? intval($_POST["prospect_id"])
            : 0;
        if (!$prospect_id) {
            wp_send_json_error("Invalid prospect.");
        }

        // Get the note content.
        $note_content = isset($_POST["note_content"])
            ? sanitize_textarea_field($_POST["note_content"])
            : "";

        // Update the general_notes meta field.
        update_post_meta($prospect_id, "general_notes", $note_content);

        wp_send_json_success("Notes updated successfully.");
    }

    //-----------------------------------------------------------------------------------
    // KEY PEOPLE AJAX HANDLERS
    //-----------------------------------------------------------------------------------

    /**
     * AJAX handler for saving key person.
     */
    public function ajax_save_key_person()
    {
        // Verify nonce.
        if (
            !isset($_POST["nonce"]) ||
            !wp_verify_nonce($_POST["nonce"], "save_key_person")
        ) {
            wp_send_json_error("Invalid nonce.");
        }

        // Validate prospect ID.
        $prospect_id = isset($_POST["prospect_id"])
            ? intval($_POST["prospect_id"])
            : 0;
        if (!$prospect_id) {
            wp_send_json_error("Invalid prospect.");
        }

        // Retrieve and sanitize key person fields.
        $first_name = isset($_POST["kp_first_name"])
            ? sanitize_text_field($_POST["kp_first_name"])
            : "";
        $last_name = isset($_POST["kp_last_name"])
            ? sanitize_text_field($_POST["kp_last_name"])
            : "";
        $role = isset($_POST["kp_role"])
            ? sanitize_text_field($_POST["kp_role"])
            : "";
        $email = isset($_POST["kp_email"])
            ? sanitize_email($_POST["kp_email"])
            : "";
        $phone = isset($_POST["kp_phone"])
            ? sanitize_text_field($_POST["kp_phone"])
            : "";

        // Ensure all required fields are provided.
        if (
            empty($first_name) ||
            empty($last_name) ||
            empty($role) ||
            empty($email) ||
            empty($phone)
        ) {
            wp_send_json_error("Missing required fields.");
        }

        // Build new key person data.
        $new_person = [
            "first_name" => $first_name,
            "last_name" => $last_name,
            "role" => $role,
            "email" => $email,
            "phone" => $phone,
        ];

        // Retrieve existing key people.
        $existing = get_post_meta($prospect_id, "key_people", true);
        if (!is_array($existing)) {
            $existing = [];
        }
        $existing[] = $new_person;

        // Update the meta field.
        update_post_meta($prospect_id, "key_people", $existing);

        // Use template function to generate HTML
        $html = $this->render_key_people_table($existing);

        wp_send_json_success($html);
    }

    /**
     * AJAX handler for deleting key person.
     */
    public function ajax_delete_key_person()
    {
        // Verify nonce.
        if (
            !isset($_POST["nonce"]) ||
            !wp_verify_nonce($_POST["nonce"], "delete_key_person")
        ) {
            wp_send_json_error("Invalid nonce.");
        }

        // Validate prospect ID.
        $prospect_id = isset($_POST["prospect_id"])
            ? intval($_POST["prospect_id"])
            : 0;
        if (!$prospect_id) {
            wp_send_json_error("Invalid prospect.");
        }

        // Validate key person index.
        $index = isset($_POST["index"]) ? intval($_POST["index"]) : -1;
        if ($index < 0) {
            wp_send_json_error("Invalid key person index.");
        }

        // Retrieve existing key people.
        $key_people = get_post_meta($prospect_id, "key_people", true);
        if (!is_array($key_people)) {
            wp_send_json_error("No key people found.");
        }

        // Check if the key person exists.
        if (!isset($key_people[$index])) {
            wp_send_json_error("Key person not found.");
        }

        // Remove the specified key person and reindex the array.
        unset($key_people[$index]);
        $key_people = array_values($key_people);

        // Update the meta field.
        update_post_meta($prospect_id, "key_people", $key_people);

        // Use template function to generate HTML
        $html = $this->render_key_people_table($key_people);

        wp_send_json_success($html);
    }

    /**
     * AJAX handler to get key people.
     */
    public function ajax_get_key_people()
    {
        // Verify nonce.
        if (
            !isset($_POST["nonce"]) ||
            !wp_verify_nonce($_POST["nonce"], "get_key_people_nonce")
        ) {
            wp_send_json_error("Invalid nonce.");
        }

        $prospect_id = isset($_POST["prospect_id"])
            ? intval($_POST["prospect_id"])
            : 0;
        if (!$prospect_id) {
            wp_send_json_error("Invalid prospect.");
        }

        // Retrieve the key_people meta field.
        $key_people = get_post_meta($prospect_id, "key_people", true);
        if (!is_array($key_people)) {
            $key_people = [];
        }

        // Use template function to generate HTML
        $html = $this->render_key_people_table($key_people);

        wp_send_json_success($html);
    }

    //-----------------------------------------------------------------------------------
    // CALL REMINDER AJAX HANDLERS
    //-----------------------------------------------------------------------------------

    /**
     * AJAX handler to get call reminders.
     */
    public function ajax_get_call_reminders()
    {
        // Verify nonce.
        if (
            !isset($_POST["nonce"]) ||
            !wp_verify_nonce($_POST["nonce"], "get_call_reminders_nonce")
        ) {
            wp_send_json_error("Invalid nonce.");
        }

        $prospect_id = isset($_POST["prospect_id"])
            ? intval($_POST["prospect_id"])
            : 0;
        if (!$prospect_id) {
            wp_send_json_error("Invalid prospect.");
        }

        // Retrieve the call_back_reminders meta field.
        $reminders = get_post_meta($prospect_id, "call_back_reminders", true);
        if (!is_array($reminders)) {
            $reminders = [];
        }

        // Use template function to generate HTML
        $html = $this->render_reminders_table($reminders);

        wp_send_json_success($html);
    }

    /**
     * AJAX handler to delete a call reminder.
     */
    public function ajax_delete_call_reminder()
    {
        // Verify nonce.
        if (
            !isset($_POST["nonce"]) ||
            !wp_verify_nonce($_POST["nonce"], "delete_call_reminder")
        ) {
            wp_send_json_error("Invalid nonce.");
        }

        // Get prospect ID and index.
        $prospect_id = isset($_POST["prospect_id"])
            ? intval($_POST["prospect_id"])
            : 0;
        $index = isset($_POST["index"]) ? intval($_POST["index"]) : -1;
        if (!$prospect_id || $index < 0) {
            wp_send_json_error("Invalid prospect or reminder index.");
        }

        // Retrieve the reminders.
        $reminders = get_post_meta($prospect_id, "call_back_reminders", true);
        if (!is_array($reminders)) {
            wp_send_json_error("No reminders found.");
        }

        // Check if the index exists.
        if (!isset($reminders[$index])) {
            wp_send_json_error("Reminder not found.");
        }

        // Remove the specified reminder and reindex the array.
        unset($reminders[$index]);
        $reminders = array_values($reminders);

        // Save the updated reminders.
        update_post_meta($prospect_id, "call_back_reminders", $reminders);

        // Use template function to generate HTML
        $html = $this->render_reminders_table($reminders);

        wp_send_json_success($html);
    }

    /**
     * AJAX handler to save a callback reminder.
     */
    public function ajax_save_call_back_reminder()
    {
        // Verify nonce.
        if (
            !isset($_POST["nonce"]) ||
            !wp_verify_nonce($_POST["nonce"], "save_call_back_reminder")
        ) {
            wp_send_json_error("Invalid nonce.");
        }

        // Make sure prospect_id is passed
        $prospect_id = isset($_POST["prospect_id"])
            ? intval($_POST["prospect_id"])
            : 0;
        if (!$prospect_id) {
            wp_send_json_error("Invalid prospect.");
        }

        // Get the reminder data from the POST request.
        $reminder_date = isset($_POST["reminder_date"])
            ? sanitize_text_field($_POST["reminder_date"])
            : "";
        $reminder_notes = isset($_POST["reminder_notes"])
            ? sanitize_textarea_field($_POST["reminder_notes"])
            : "";

        // Build a new reminder array.
        $new_reminder = [
            "reminder_date" => $reminder_date,
            "reminder_notes" => $reminder_notes,
            "timestamp" => current_time("timestamp"),
        ];

        // Retrieve existing reminders.
        $existing_reminders = get_post_meta(
            $prospect_id,
            "call_back_reminders",
            true
        );
        if (!is_array($existing_reminders)) {
            $existing_reminders = [];
        }
        $existing_reminders[] = $new_reminder;

        // Update the meta field.
        update_post_meta(
            $prospect_id,
            "call_back_reminders",
            $existing_reminders
        );

        // Use template function to generate HTML
        $html = $this->render_reminders_table($existing_reminders);

        wp_send_json_success($html);
    }

    //-----------------------------------------------------------------------------------
    // CALL PROSPECT FUNCTIONALITY
    //-----------------------------------------------------------------------------------

    /**
     * AJAX handler for calling a prospect via AirCall API.
     */
    public function ajax_call_prospect_now()
{
    // Verify nonce.
    if (
        !isset($_POST["call_prospect_now_nonce"]) ||
        !wp_verify_nonce($_POST["call_prospect_now_nonce"], "call_prospect_now")
    ) {
        wp_send_json_error("Invalid nonce.");
    }

    $current_user_id = get_current_user_id();
    $aircall_user_id = get_user_meta($current_user_id, "AirCallUserID", true);
    $aircall_phone_id = get_user_meta($current_user_id, "AirCallPhoneID", true);
    $prospect_phone = isset($_POST["prospect_phone"]) ? sanitize_text_field($_POST["prospect_phone"]) : "";
    $prospect_id    = isset($_POST["prospect_id"]) ? intval($_POST["prospect_id"]) : 0;

    if (empty($aircall_user_id) || empty($aircall_phone_id) || empty($prospect_phone)) {
        wp_send_json_error("Missing required AirCall meta or prospect phone.");
    }

    // Initiate the outbound call.
    $result = $this->initiate_outbound_call($aircall_user_id, $aircall_phone_id, $prospect_phone);

    // Only update call log if we have a successful API response
    if ($result === true || $result === "Call Started") {
        $call_logs_html = '';
        if ($prospect_id) {
            // Retrieve existing call logs.
            $call_logs = get_post_meta($prospect_id, "call_logs", true);
            if (!is_array($call_logs)) {
                $call_logs = [];
            }
            // Append a new call log entry.
            $new_call_log = [
                'date_time' => current_time('mysql'), // e.g., "2025-02-26 12:34:56"
                'notes'     => '' // Empty notes to be updated later.
            ];
            $call_logs[] = $new_call_log;
            update_post_meta($prospect_id, "call_logs", $call_logs);
            // Render the updated call log table HTML.
            $call_logs_html = $this->render_call_logs_table($call_logs);
        }

        $response_message = ($result === true) ? "Call initiated successfully." : "Call Started";
        wp_send_json_success([
            'message'       => $response_message,
            'call_log_html' => $call_logs_html
        ]);
    } else {
        wp_send_json_error("There is an API error.");
    }
}

    /**
     * Initiate an outbound call via the Aircall API.
     *
     * @param int $aircall_user_id The AirCallUserID from user meta.
     * @param int $aircall_phone_id The AirCallPhoneID from user meta.
     * @param string $prospect_phone The prospect's phone number (E.164 format).
     * @return bool|string True on success, "Call Started" for 204 response, false on failure.
     */
    private function initiate_outbound_call(
        $aircall_user_id,
        $aircall_phone_id,
        $prospect_phone
    ) {
        // Replace these with your actual credentials.
        $api_key = "ff00d09ba5b548e577b1967136b42152";
        $api_secret = "0807cec442d1e4ba18e6e85f87a26c6c";

        $url =
            "https://api.aircall.io/v1/users/" .
            intval($aircall_user_id) .
            "/calls";
        $data = [
            "number_id" => $aircall_phone_id, // Using the correct key per Aircall's API
            "to" => $prospect_phone,
            "direction" => "outbound",
        ];
        $payload = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERPWD, "$api_key:$api_secret");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // If there's an error or the HTTP code is not one of our accepted ones, return false.
        if (
            $error ||
            ($http_code != 200 && $http_code != 201 && $http_code != 204)
        ) {
            return false;
        }

        // If we received a 204, return a special value.
        if ($http_code == 204) {
            return "Call Started";
        }

        // Otherwise, decode the response for 200 or 201.
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return true;
    }

    //-----------------------------------------------------------------------------------
    // HTML TEMPLATE FUNCTIONS
    //-----------------------------------------------------------------------------------

    /**
     * Render HTML table for key people.
     *
     * @param array $key_people Array of key people data.
     * @return string HTML for the key people table.
     */
    private function render_key_people_table($key_people)
{
    ob_start(); ?>
    <table class="wp-list-table widefat fixed striped" style="max-width:1100px;">
        <thead>
            <tr>
                <th><?php esc_html_e("First Name", "claims-angel"); ?></th>
                <th><?php esc_html_e("Last Name", "claims-angel"); ?></th>
                <th><?php esc_html_e("Role", "claims-angel"); ?></th>
                <th><?php esc_html_e("Email", "claims-angel"); ?></th>
                <th><?php esc_html_e("Phone", "claims-angel"); ?></th>
                <th><?php esc_html_e("Actions", "claims-angel"); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($key_people)):
                foreach ($key_people as $index => $person): ?>
                    <tr>
                        <td><?php echo esc_html($person["first_name"]); ?></td>
                        <td><?php echo esc_html($person["last_name"]); ?></td>
                        <td><?php echo esc_html($person["role"]); ?></td>
                        <td><?php echo esc_html($person["email"]); ?></td>
                        <td><?php echo esc_html($person["phone"]); ?></td>
                        <td>
                            <button class="button delete-key-person" data-index="<?php echo esc_attr($index); ?>">
                                <?php esc_html_e("Delete", "claims-angel"); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach;
            else: ?>
                <tr>
                    <td colspan="6"><?php esc_html_e("No key people available.", "claims-angel"); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php return ob_get_clean();
}

    /**
     * Render HTML table for call reminders.
     *
     * @param array $reminders Array of reminder data.
     * @return string HTML for the reminders table.
     */
    private function render_reminders_table($reminders)
{
    ob_start(); ?>
    <table class="wp-list-table widefat fixed striped" style="max-width:1100px;">
        <thead>
            <tr>
                <th><?php esc_html_e("Reminder Date", "claims-angel"); ?></th>
                <th><?php esc_html_e("Notes", "claims-angel"); ?></th>
                <th><?php esc_html_e("Actions", "claims-angel"); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($reminders)):
                foreach ($reminders as $i => $reminder):
                    $reminder_date = isset($reminder["reminder_date"]) ? $reminder["reminder_date"] : "N/A";
                    $reminder_notes = isset($reminder["reminder_notes"]) ? $reminder["reminder_notes"] : "N/A";
                    ?>
                    <tr>
                        <td><?php echo esc_html($reminder_date); ?></td>
                        <td><?php echo esc_html($reminder_notes); ?></td>
                        <td>
                            <button class="button delete-reminder" data-index="<?php echo esc_attr($i); ?>">
                                <?php esc_html_e("Delete", "claims-angel"); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach;
            else: ?>
                <tr>
                    <td colspan="3"><?php esc_html_e("No call logs available.", "claims-angel"); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php return ob_get_clean();
}

    /**
     * Render HTML table for call logs.
     *
     * @param array $call_logs Array of call log data.
     * @return string HTML for the call logs table.
     */
    private function render_call_logs_table($call_logs)
{
    // Sort the call logs array by date_time in descending order (most recent first)
    if (!empty($call_logs) && is_array($call_logs)) {
        usort($call_logs, function($a, $b) {
            $timeA = isset($a["date_time"]) ? strtotime($a["date_time"]) : 0;
            $timeB = isset($b["date_time"]) ? strtotime($b["date_time"]) : 0;
            return $timeB - $timeA;
        });
    }

    ob_start(); ?>
    <table class="wp-list-table widefat fixed striped" style="max-width:1100px;">
        <thead>
            <tr>
                <th><?php esc_html_e("Date & Time", "claims-angel"); ?></th>
                <th><?php esc_html_e("Notes", "claims-angel"); ?></th>
                <th><?php esc_html_e("Actions", "claims-angel"); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($call_logs)):
                foreach ($call_logs as $index => $log):
                    $date_time = isset($log["date_time"]) ? $log["date_time"] : "N/A";
                    $formatted_date_time = ($date_time !== "N/A") ? date('d/m/y H:i', strtotime($date_time)) : "N/A";
                    $daysAgo = "N/A";
                    if ($date_time !== "N/A") {
                        $daysAgoInt = floor((current_time('timestamp') - strtotime($date_time)) / (60 * 60 * 24));
                        $daysAgo = $daysAgoInt . " day" . ($daysAgoInt !== 1 ? "s" : "") . " ago";
                    }
                    $notes = isset($log["notes"]) ? $log["notes"] : "";
                    ?>
                    <tr data-index="<?php echo esc_attr($index); ?>">
                        <td>
                            <?php echo esc_html($formatted_date_time); ?><br>
                            <small>(<?php echo esc_html($daysAgo); ?>)</small>
                        </td>
                        <td class="call-log-note"><?php echo esc_html($notes); ?></td>
                        <td>
                            <button class="button edit-call-log" data-index="<?php echo esc_attr($index); ?>">Edit</button>
                        </td>
                    </tr>
                <?php endforeach;
            else: ?>
                <tr>
                    <td colspan="3"><?php esc_html_e("No Calls Logged", "claims-angel"); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php return ob_get_clean();
}

public function ajax_update_call_log() {
    // Verify nonce using a nonce we’ll pass as 'nonce'
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'update_call_log')) {
        wp_send_json_error("Invalid nonce.");
    }

    $prospect_id = isset($_POST['prospect_id']) ? intval($_POST['prospect_id']) : 0;
    $log_index   = isset($_POST['log_index']) ? intval($_POST['log_index']) : -1;
    $new_note    = isset($_POST['new_note']) ? sanitize_text_field($_POST['new_note']) : '';

    if (!$prospect_id || $log_index < 0) {
        wp_send_json_error("Invalid parameters.");
    }

    $call_logs = get_post_meta($prospect_id, "call_logs", true);
    if (!is_array($call_logs)) {
        wp_send_json_error("Call logs not found.");
    }

    // Sort the call logs array in descending order (most recent first)
    usort($call_logs, function($a, $b) {
        $timeA = isset($a["date_time"]) ? strtotime($a["date_time"]) : 0;
        $timeB = isset($b["date_time"]) ? strtotime($b["date_time"]) : 0;
        return $timeB - $timeA;
    });

    if (!isset($call_logs[$log_index])) {
        wp_send_json_error("Call log entry not found.");
    }

    // Update the notes field for the specified call log in the sorted array.
    $call_logs[$log_index]['notes'] = $new_note;

    // Optionally, update the meta with the sorted array (so that subsequent retrievals remain consistent)
    update_post_meta($prospect_id, "call_logs", $call_logs);

    // Return the updated table HTML.
    $html = $this->render_call_logs_table($call_logs);
    wp_send_json_success($html);
}

public function ajax_delete_prospect_meta() {
    // Verify nonce.
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'delete_prospect_meta_nonce') ) {
        wp_send_json_error("Invalid nonce.");
    }

    // Get the prospect id and delete reason.
    $prospect_id = isset($_POST['prospect_id']) ? intval($_POST['prospect_id']) : 0;
    $delete_reason = isset($_POST['delete_reason']) ? sanitize_text_field($_POST['delete_reason']) : '';

    if ( ! $prospect_id ) {
        wp_send_json_error("Invalid prospect.");
    }
    if ( empty($delete_reason) ) {
        wp_send_json_error("Please select a delete reason.");
    }

    // Update meta fields.
    update_post_meta($prospect_id, 'business_delete', 'yes');
    update_post_meta($prospect_id, 'business_delete_reason', $delete_reason);

    wp_send_json_success("Prospect deleted successfully.");
}

public function ajax_update_general_info() {
    // Verify nonce.
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'update_general_info_nonce')) {
        wp_send_json_error("Invalid nonce.");
    }

    $prospect_id = isset($_POST['prospect_id']) ? intval($_POST['prospect_id']) : 0;
    if (!$prospect_id) {
        wp_send_json_error("Invalid prospect.");
    }

    // Retrieve and sanitize new values.
    $business_name    = isset($_POST['business_name']) ? sanitize_text_field($_POST['business_name']) : '';
    $web_address      = isset($_POST['web_address']) ? esc_url_raw($_POST['web_address']) : '';
    $phone_number     = isset($_POST['phone_number']) ? sanitize_text_field($_POST['phone_number']) : '';
    $linkedin_profile = isset($_POST['linkedin_profile']) ? esc_url_raw($_POST['linkedin_profile']) : '';

    // Update the meta fields.
    update_post_meta($prospect_id, 'business_name', $business_name);
    update_post_meta($prospect_id, 'web_address', $web_address);
    update_post_meta($prospect_id, 'phone_number', $phone_number);
    update_post_meta($prospect_id, 'linkedin_profile', $linkedin_profile);

    wp_send_json_success("General information updated successfully.");
}

}

// Initialize the Business CRM Profile Page.
MyProspectProfile::get_instance();