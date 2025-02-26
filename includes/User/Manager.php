<?php
namespace ClaimsAngel\User;

use ClaimsAngel\Data\Countries;

/**
 * User Manager Class
 * Handles user interface modifications and data management
 * 
 * Created: 2025-02-21
 * Last Modified: 2025-02-21 19:57:44
 * Author: DVVTEO
 */
class Manager {
    /**
     * Initialize the user manager
     */
    public static function init() {
        // Add country field to user forms
        add_action('user_new_form', [__CLASS__, 'add_country_field_to_form']);
        add_action('edit_user_profile', [__CLASS__, 'add_country_field_to_form']);
        add_action('show_user_profile', [__CLASS__, 'add_country_field_to_form']);
        
        // Save country data
        add_action('user_register', [__CLASS__, 'save_user_country']);
        add_action('profile_update', [__CLASS__, 'save_user_country']);
        
        // Add validation for required country
        add_filter('user_profile_update_errors', [__CLASS__, 'validate_user_country'], 10, 3);
        
        add_action('admin_init', function() {
            global $pagenow;
            // Removed debug logging
        });
    }

    /**
     * Add country field to user forms
     *
     * @param \WP_User|null $user User object or null on creation
     */
    public static function add_country_field_to_form($user) {
        $countries = Countries::get_instance()->get_countries();
        $selected = $user ? get_user_meta($user->ID, 'user_country', true) : '';
        ?>
        <table class="form-table">
            <tr>
                <th>
                    <label for="user_country"><?php _e('Country', 'claims-angel'); ?></label>
                </th>
                <td>
                    <select name="user_country" id="user_country" class="regular-text" required>
                        <option value=""><?php _e('Select a country...', 'claims-angel'); ?></option>
                        <?php foreach ($countries as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($selected, $code); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php _e('Please select your country from the list.', 'claims-angel'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save user country data
     *
     * @param int $user_id The user ID
     */
    public static function save_user_country($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        $validator = new Validator();
        $country = isset($_POST['user_country']) ? sanitize_text_field($_POST['user_country']) : '';
        
        if ($validator->is_valid_country($country)) {
            update_user_meta($user_id, 'user_country', $country);
        }
    }

    /**
     * Validate user country on profile update
     *
     * @param \WP_Error $errors Error object
     * @param bool      $update Whether this is an update
     * @param \stdClass $user   User object
     */
    public static function validate_user_country($errors, $update, $user) {
        $validator = new Validator();
        $country = isset($_POST['user_country']) ? sanitize_text_field($_POST['user_country']) : '';
        
        if (!$validator->validate_country_selection($country)) {
            $errors->add('user_country', __('Please select a valid country.', 'claims-angel'));
        }

        return $errors;
    }

    /**
     * Get user's country
     *
     * @param int $user_id The user ID
     * @return string Country code or empty string if not set
     */
    public static function get_user_country($user_id) {
        return get_user_meta($user_id, 'user_country', true);
    }
}