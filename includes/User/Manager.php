<?php
/**
 * ClaimsAngel User Manager
 *
 * Manages user profiles with extended functionality:
 * 1. Adds country selection field
 * 2. Replaces standard WordPress role dropdown with multi-select checkboxes
 * 3. Handles validation and saving of custom user data
 *
 * @package ClaimsAngel
 * @subpackage User
 * @since 1.0.0
 */

namespace ClaimsAngel\User;
use ClaimsAngel\Data\Countries;

/**
 * User Manager Class
 * 
 * @since 1.0.0
 */
class Manager {
    /**
     * Hook names for user forms
     * 
     * @var array
     */
    private static $user_form_hooks = [
        'user_new_form',
        'edit_user_profile',
        'show_user_profile',
    ];
    
    /**
     * Initialize the user manager
     * 
     * @return void
     */
    public static function init() {
        // Add country selection to user forms
        self::register_country_handlers();
        
        // Replace default role selector with multi-role checkboxes
        self::register_role_handlers();
    }
    
    /**
     * Register all country-related hooks and filters
     * 
     * @return void
     */
    private static function register_country_handlers() {
        // Add country field to all user forms
        foreach (self::$user_form_hooks as $hook) {
            add_action($hook, [__CLASS__, 'add_country_field_to_form']);
        }
        
        // Save country data
        add_action('user_register', [__CLASS__, 'save_user_country']);
        add_action('profile_update', [__CLASS__, 'save_user_country']);
        
        // Add validation for required country
        add_filter('user_profile_update_errors', [__CLASS__, 'validate_user_country'], 10, 3);
    }
    
    /**
     * Register all role-related hooks and filters
     * 
     * @return void
     */
    private static function register_role_handlers() {
        // Hide default role selector
        add_action('admin_print_footer_scripts', [__CLASS__, 'remove_default_role_selector']);
        
        // Add multi-role checkboxes to all user forms
        foreach (self::$user_form_hooks as $hook) {
            add_action($hook, [__CLASS__, 'add_role_checkboxes']);
        }
        
        // Save multiple roles
        add_action('user_register', [__CLASS__, 'save_user_roles']);
        add_action('profile_update', [__CLASS__, 'save_user_roles']);
    }
    
    /**
     * Add country field to user forms
     *
     * Displays a dropdown of countries for the user to select from.
     * For new users, no country is pre-selected.
     * For existing users, their saved country is pre-selected.
     *
     * @param \WP_User|null $user User object or null on creation form
     * @return void
     */
    public static function add_country_field_to_form($user) {
        // Get countries list
        $countries = Countries::get_instance()->get_countries();
        
        // Get user's selected country if editing an existing user
        $selected = $user instanceof \WP_User ? get_user_meta($user->ID, 'user_country', true) : '';
        
        // Output the country selection field
        ?>
        <h3><?php _e('Location Information', 'claims-angel'); ?></h3>
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
     * Remove the default role selector dropdown via JavaScript
     *
     * Hides but doesn't remove the default role selector to prevent
     * form submission errors while still allowing our custom
     * role selection interface to take precedence.
     *
     * @return void
     */
    public static function remove_default_role_selector() {
        // Only run on user-related admin pages
        global $pagenow;
        if (!in_array($pagenow, ['user-new.php', 'user-edit.php', 'profile.php'])) {
            return;
        }
        
        ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                // Find and hide the default role selector row
                const roleSelector = document.querySelector('.form-table select#role');
                if (roleSelector) {
                    const row = roleSelector.closest('tr');
                    if (row) {
                        row.style.display = 'none';
                    }
                }
            });
        </script>
        <?php
    }
    
    /**
     * Add role checkboxes to user forms
     *
     * Displays checkboxes for all available roles, allowing
     * administrators to assign multiple roles to a single user.
     *
     * @param \WP_User|null $user User object or null on creation form
     * @return void
     */
    public static function add_role_checkboxes($user) {
        // Check if current user can assign roles
        if (!current_user_can('promote_users')) {
            return;
        }
        
        // Get all registered roles
        $roles = get_editable_roles();
        
        // Get user's current roles if editing
        $user_roles = [];
        if ($user instanceof \WP_User) {
            $user_roles = $user->roles;
        }
        
        // Create a custom table section for roles
        ?>
        <h3><?php _e('User Roles', 'claims-angel'); ?></h3>
        <table class="form-table" id="claims-angel-roles-table">
            <tr>
                <th>
                    <label><?php _e('Assign Roles', 'claims-angel'); ?></label>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text">
                            <span><?php _e('Assign Roles', 'claims-angel'); ?></span>
                        </legend>
                        <div class="claims-angel-roles-container">
                            <?php foreach ($roles as $role_id => $role_info): ?>
                                <label for="role_<?php echo esc_attr($role_id); ?>" class="claims-angel-role-label">
                                    <input type="checkbox" 
                                        name="claims_angel_user_roles[]" 
                                        id="role_<?php echo esc_attr($role_id); ?>" 
                                        value="<?php echo esc_attr($role_id); ?>"
                                        <?php checked(in_array($role_id, $user_roles)); ?>
                                    />
                                    <?php echo esc_html(translate_user_role($role_info['name'])); ?>
                                    
                                    <?php if (!empty($role_info['capabilities'])): ?>
                                        <span class="claims-angel-role-cap-count">
                                            (<?php 
                                            printf(
                                                _n('%s capability', '%s capabilities', count($role_info['capabilities']), 'claims-angel'),
                                                number_format_i18n(count($role_info['capabilities']))
                                            ); 
                                            ?>)
                                        </span>
                                    <?php endif; ?>
                                </label><br />
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                    <p class="description">
                        <?php _e('Select one or more roles to assign to this user. At least one role is required.', 'claims-angel'); ?>
                    </p>
                    
                    <style type="text/css">
                        .claims-angel-roles-container {
                            max-height: 300px;
                            overflow-y: auto;
                            padding: 10px;
                            border: 1px solid #ddd;
                            background: #f9f9f9;
                        }
                        .claims-angel-role-label {
                            display: block;
                            margin-bottom: 5px;
                            padding: 5px;
                        }
                        .claims-angel-role-label:hover {
                            background: #f0f0f0;
                        }
                        .claims-angel-role-cap-count {
                            color: #777;
                            font-size: 0.9em;
                            margin-left: 5px;
                        }
                    </style>
                </td>
            </tr>
        </table>
        
        <?php
        // Add a hidden field to detect if our form was submitted
        echo '<input type="hidden" name="claims_angel_roles_submitted" value="1" />';
    }
    
    /**
     * Save user roles
     *
     * Handles saving multiple roles to a user when the profile is updated.
     * Ensures at least one role is assigned, defaulting to the site default
     * if none are selected.
     *
     * @param int $user_id The user ID being saved
     * @return void
     */
    public static function save_user_roles($user_id) {
        // Check if the current user can edit users
        if (!current_user_can('edit_user', $user_id) || !current_user_can('promote_users')) {
            return;
        }
        
        // Check if our form was submitted
        if (!isset($_POST['claims_angel_roles_submitted'])) {
            return;
        }
        
        // Get the user object
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        // Security check: don't modify super admins
        if (is_multisite() && is_super_admin($user_id)) {
            return;
        }
        
        // Process and validate submitted roles
        $new_roles = self::process_submitted_roles();
        
        // Apply the roles to the user
        self::apply_roles_to_user($user, $new_roles);
        
        // Log role changes for the audit trail
        error_log(sprintf(
            'User #%d roles updated by %s. New roles: %s',
            $user_id,
            wp_get_current_user()->user_login,
            implode(', ', $new_roles)
        ));
    }
    
    /**
     * Process submitted roles
     * 
     * Retrieves and validates roles from form submission
     * 
     * @return array Array of validated role slugs
     */
    private static function process_submitted_roles() {
        // Get all submitted roles
        $new_roles = isset($_POST['claims_angel_user_roles']) ? (array) $_POST['claims_angel_user_roles'] : [];
        
        // Sanitize role names
        $new_roles = array_map('sanitize_key', $new_roles);
        
        // Get all valid roles
        $valid_roles = array_keys(get_editable_roles());
        
        // Filter to only include valid roles
        $new_roles = array_intersect($new_roles, $valid_roles);
        
        // If no roles are selected, make sure there's at least a default role
        if (empty($new_roles)) {
            $new_roles = [get_option('default_role')];
        }
        
        return $new_roles;
    }
    
    /**
     * Apply roles to a user
     * 
     * @param \WP_User $user User object
     * @param array $new_roles Array of role slugs to apply
     * @return void
     */
    private static function apply_roles_to_user($user, $new_roles) {
        // Remove all existing roles
        $existing_roles = (array) $user->roles;
        foreach ($existing_roles as $role) {
            $user->remove_role($role);
        }
        
        // Add each new role
        foreach ($new_roles as $role) {
            $user->add_role($role);
        }
    }
    
    /**
     * Save user country data
     *
     * @param int $user_id The user ID
     * @return void
     */
    public static function save_user_country($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        if (!isset($_POST['user_country'])) {
            return;
        }
        
        $validator = new Validator();
        $country = sanitize_text_field($_POST['user_country']);
        
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
     * @return \WP_Error Updated error object
     */
    public static function validate_user_country($errors, $update, $user) {
        if (!isset($_POST['user_country'])) {
            $errors->add('user_country', __('Country selection is required.', 'claims-angel'));
            return $errors;
        }
        
        $validator = new Validator();
        $country = sanitize_text_field($_POST['user_country']);
        
        if (!$validator->validate_country_selection($country)) {
            $errors->add('user_country', __('Please select a valid country.', 'claims-angel'));
        }
        
        return $errors;
    }
    
    /**
     * Get user's country
     *
     * Utility method to retrieve a user's country from metadata
     *
     * @param int $user_id The user ID
     * @return string Country code or empty string if not set
     */
    public static function get_user_country($user_id) {
        return get_user_meta($user_id, 'user_country', true);
    }
}