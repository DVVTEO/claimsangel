<?php
namespace ClaimsAngel\Admin\Aircall;

use ClaimsAngel\Admin\AdminAccessController;

/**
 * ConnectAircall Class
 * Handles the Aircall connection page for connecting an account.
 *
 * Created: 2025-02-21
 * Last Modified: 2025-02-24 10:31:09
 * Author: DVVTEO
 */
class ConnectAircall {
    /**
     * Static flag to ensure the admin pages are registered only once.
     *
     * @var bool
     */
    protected static $menu_registered = false;

    /**
     * Constructor
     * Initialize hooks for admin pages.
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_pages']);
    }

    /**
     * Register admin pages under the parent "Aircall" menu.
     */
    public function register_admin_pages() {
        // Prevent duplicate registration.
        if ( self::$menu_registered ) {
            return;
        }
        self::$menu_registered = true;

        // Create top-level Aircall menu.
        add_menu_page(
            'Aircall',           // Page title.
            'Aircall',           // Menu title.
            'manage_options',    // Capability.
            'aircall',           // Menu slug.
            [$this, 'redirect_to_connect'], // Callback that redirects.
            'dashicons-phone',   // Icon.
            60                   // Position.
        );
        
        // Add a submenu item "Connect Account" under "Aircall".
        add_submenu_page(
            'aircall',                  // Parent slug.
            'Connect Account',          // Page title.
            'Connect Account',          // Menu title.
            'manage_options',           // Capability.
            'aircall-connect-account',  // Submenu slug.
            [$this, 'render_main_page'] // Callback.
        );
        
        // Remove the default duplicate submenu item.
        remove_submenu_page('aircall', 'aircall');
    }
    
    /**
     * Redirect function for the top-level menu item.
     */
    public function redirect_to_connect() {
        wp_redirect(admin_url('admin.php?page=aircall-connect-account'));
        exit;
    }

    /**
     * Render the "Connect Account" page.
     */
    public function render_main_page() {
        // Process "Connect Account" submission.
        if ( isset($_POST['connect_aircall_account']) && check_admin_referer('connect_aircall_account', 'connect_aircall_account_nonce') ) {
            $user_id = get_current_user_id();
            $aircallUserID    = sanitize_text_field($_POST['AirCallUserID']);
            $aircallUserEmail = sanitize_email($_POST['AirCallUserEmail']);
            $aircallPhoneID   = sanitize_text_field($_POST['AirCallPhoneID']);
            
            $updated1 = update_user_meta($user_id, 'AirCallUserID', $aircallUserID);
            $updated2 = update_user_meta($user_id, 'AirCallUserEmail', $aircallUserEmail);
            $updated3 = update_user_meta($user_id, 'AirCallPhoneID', $aircallPhoneID);
            
            if ( $updated1 !== false && $updated2 !== false && $updated3 !== false ) {
                echo '<p style="color: green;">Aircall Account Connected.</p>';
            } else {
                echo '<p style="color: red;">An error occurred.</p>';
            }
        }
        
        ?>
        <div class="wrap">
            <h1>Connect Aircall Account</h1>
            
            <?php
            // Process disconnect submission.
            if ( isset($_POST['disconnect_aircall_account']) && 
                 check_admin_referer('disconnect_aircall_account', 'disconnect_aircall_account_nonce') ) {
                $user_id = get_current_user_id();
                delete_user_meta($user_id, 'AirCallUserID');
                delete_user_meta($user_id, 'AirCallUserEmail');
                delete_user_meta($user_id, 'AirCallPhoneID');
                echo '<p style="color: green;">Aircall Account Disconnected.</p>';
            }
            
            // Check if the user already has connected AirCall meta fields.
            $current_user_id    = get_current_user_id();
            $aircall_user_id    = get_user_meta($current_user_id, 'AirCallUserID', true);
            $aircall_user_email = get_user_meta($current_user_id, 'AirCallUserEmail', true);
            $aircall_phone_id   = get_user_meta($current_user_id, 'AirCallPhoneID', true);
            
            if ( !empty($aircall_user_id) && !empty($aircall_user_email) && !empty($aircall_phone_id) ) {
                echo '<h2>Your Connected Aircall Account</h2>';
                echo '<table class="widefat">';
                echo '<thead><tr><th>AirCallUserID</th><th>AirCallUserEmail</th><th>Action</th></tr></thead>';
                echo '<tbody><tr>';
                echo '<td>' . esc_html($aircall_user_id) . '</td>';
                echo '<td>' . esc_html($aircall_user_email) . '</td>';
                echo '<td>';
                echo '<form method="post">';
                wp_nonce_field('disconnect_aircall_account', 'disconnect_aircall_account_nonce');
                echo '<input type="submit" name="disconnect_aircall_account" value="Disconnect" class="button button-secondary">';
                echo '</form>';
                echo '</td>';
                echo '</tr></tbody>';
                echo '</table>';
            } else {
                // If not connected, show the "Find Aircall Account" form.
                ?>
                <form method="post">
                    <?php wp_nonce_field('find_aircall_account', 'find_aircall_account_nonce'); ?>
                    <input type="submit" name="find_aircall_account" class="button button-primary" value="Find Aircall Account">
                </form>
                <?php
                // Process "Find Aircall Account" submission.
                if ( isset($_POST['find_aircall_account']) && check_admin_referer('find_aircall_account', 'find_aircall_account_nonce') ) {
                    echo '<h2>Aircall User Details</h2>';
                    echo $this->get_aircall_user_details_for_current_user();
                }
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render the hidden details page.
     */
    public function render_details_page() {
        ?>
        <div class="wrap">
            <h1>Business Details</h1>
            <p>Details page content goes here.</p>
        </div>
        <?php
    }
    
    /**
     * Retrieve the matched Aircall user for the logged-in user's email,
     * then retrieve the full details for that user.
     *
     * @return string HTML output of the user details or an error message.
     */
    private function get_aircall_user_details_for_current_user() {
        // Replace these with your actual Aircall API credentials.
        $api_key    = 'ff00d09ba5b548e577b1967136b42152';
        $api_secret = '0807cec442d1e4ba18e6e85f87a26c6c';
        
        // Aircall API endpoint to retrieve users.
        $url = 'https://api.aircall.io/v1/users';
        
        // Initialize cURL.
        $ch = curl_init($url);
        
        // Set cURL options for Basic Auth.
        curl_setopt($ch, CURLOPT_USERPWD, "$api_key:$api_secret");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        // Execute the request.
        $response = curl_exec($ch);
        
        // Check for cURL errors.
        if (curl_errno($ch)) {
            curl_close($ch);
            return '<p>There is an API error.</p>';
        }
        
        // Get HTTP response code.
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            return '<p>There is an API error.</p>';
        }
        
        // Decode the JSON response.
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '<p>There is an API error.</p>';
        }
        
        // Depending on Aircall's API, the list of users might be wrapped in a "users" key.
        $users = isset($data['users']) ? $data['users'] : $data;
        
        // Retrieve the logged-in user's email.
        $current_user = wp_get_current_user();
        $logged_in_email = $current_user->user_email;
        
        // Filter users to only include the one with a matching email.
        $matched_users = array_filter($users, function($user) use ($logged_in_email) {
            return isset($user['email']) && strtolower($user['email']) === strtolower($logged_in_email);
        });
        
        if (empty($matched_users)) {
            return '<p>Matching aircall account cannot be located, ensure your email addresses are the same.</p>';
        }
        
        // Use the first matched user.
        $matched_user = reset($matched_users);
        
        // Retrieve full user details using the user ID.
        return $this->get_aircall_user_details($matched_user['id']);
    }
    
    /**
     * Retrieve detailed information for a given Aircall user ID.
     *
     * @param int $user_id The Aircall user ID.
     * @return string HTML output containing the full user details in a table row with a "Connect Account" button or an error message.
     */
    private function get_aircall_user_details($user_id) {
        // Replace these with your actual Aircall API credentials.
        $api_key    = 'ff00d09ba5b548e577b1967136b42152';
        $api_secret = '0807cec442d1e4ba18e6e85f87a26c6c';
        
        // Aircall API endpoint for retrieving a specific user.
        $url = 'https://api.aircall.io/v1/users/' . intval($user_id);
        
        // Initialize cURL.
        $ch = curl_init($url);
        
        // Set cURL options for Basic Auth.
        curl_setopt($ch, CURLOPT_USERPWD, "$api_key:$api_secret");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        // Execute the request.
        $response = curl_exec($ch);
        
        // Check for cURL errors.
        if (curl_errno($ch)) {
            curl_close($ch);
            return '<p>There is an API error.</p>';
        }
        
        // Get HTTP response code.
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            return '<p>There is an API error.</p>';
        }
        
        // Decode the JSON response.
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return '<p>There is an API error.</p>';
        }
        
        if (!isset($data['user'])) {
            return '<p>Invalid API response structure.</p>';
        }
        
        $user = $data['user'];
        $name  = isset($user['name']) ? $user['name'] : '';
        $email = isset($user['email']) ? $user['email'] : '';
        $default_number_id = isset($user['default_number_id']) ? $user['default_number_id'] : '';
        $digits = '';
        
        // Find the number whose id matches default_number_id.
        if (isset($user['numbers']) && is_array($user['numbers'])) {
            foreach ($user['numbers'] as $number) {
                if (isset($number['id']) && $number['id'] == $default_number_id) {
                    $digits = isset($number['digits']) ? $number['digits'] : '';
                    break;
                }
            }
        }
        
        // Build HTML table row with a "Connect Account" button.
        $output  = '<table class="widefat">';
        $output .= '<thead><tr><th>Name</th><th>Email</th><th>Digits</th><th>Action</th></tr></thead>';
        $output .= '<tbody><tr>';
        $output .= '<td>' . esc_html($name) . '</td>';
        $output .= '<td>' . esc_html($email) . '</td>';
        $output .= '<td>' . esc_html($digits) . '</td>';
        $output .= '<td>';
        $output .= '<form method="post">';
        $output .= wp_nonce_field('connect_aircall_account', 'connect_aircall_account_nonce', true, false);
        $output .= '<input type="hidden" name="AirCallUserID" value="' . esc_attr($user['id']) . '">';
        $output .= '<input type="hidden" name="AirCallUserEmail" value="' . esc_attr($email) . '">';
        $output .= '<input type="hidden" name="AirCallPhoneID" value="' . esc_attr($default_number_id) . '">';
        $output .= '<input type="submit" name="connect_aircall_account" value="Connect Account" class="button">';
        $output .= '</form>';
        $output .= '</td>';
        $output .= '</tr></tbody>';
        $output .= '</table>';
        
        return $output;
    }
}

// Initialize the ConnectAircall class.
new ConnectAircall();