<?php
namespace ClaimsAngel\Admin;

/**
 * Reference Class
 * Provides an admin interface displaying registered custom post types, roles, and meta fields
 * 
 * Created: 2025-02-21
 * Last Modified: 2025-02-21 19:22:15
 * Author: DVVTEO
 */
class Reference {
    /**
     * Initialize the reference page
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu_page']);
    }

    /**
     * Add the reference page to the admin menu
     */
    public static function add_menu_page() {
        add_submenu_page(
            'claims-angel',                    // Parent slug
            __('Reference', 'claims-angel'),   // Page title
            __('Reference', 'claims-angel'),   // Menu title
            'manage_options',                  // Capability
            'claims-angel-reference',          // Menu slug
            [__CLASS__, 'render_page']         // Callback function
        );
    }

    /**
     * Get all registered custom post types
     *
     * @return array
     */
    private static function get_custom_post_types() {
        $args = array(
            '_builtin' => false
        );
        return get_post_types($args, 'objects');
    }

    /**
     * Get all registered custom roles
     *
     * @return array
     */
    private static function get_custom_roles() {
        global $wp_roles;
        $all_roles = $wp_roles->roles;
        $custom_roles = array();
        
        // Default WordPress roles to exclude
        $default_roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');
        
        foreach ($all_roles as $role_slug => $role_details) {
            if (!in_array($role_slug, $default_roles)) {
                $custom_roles[$role_slug] = $role_details;
            }
        }
        
        return $custom_roles;
    }

    /**
     * Get registered meta fields for a post type
     *
     * @param string $post_type
     * @return array
     */
    private static function get_post_type_meta_fields($post_type) {
        global $wpdb;
        
        $meta_keys = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT meta_key 
            FROM $wpdb->postmeta pm 
            JOIN $wpdb->posts p ON p.ID = pm.post_id 
            WHERE p.post_type = %s 
            AND meta_key NOT LIKE '\_%'
        ", $post_type));
        
        return $meta_keys;
    }

    /**
     * Get registered user meta fields for a role
     *
     * @param string $role
     * @return array
     */
    private static function get_role_meta_fields($role) {
        global $wpdb;
        
        $meta_keys = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT meta_key 
            FROM $wpdb->usermeta um 
            JOIN $wpdb->users u ON u.ID = um.user_id 
            JOIN $wpdb->usermeta cap ON cap.user_id = u.ID 
            WHERE cap.meta_key = %s 
            AND um.meta_key NOT LIKE '\_%'
        ", $wpdb->prefix . 'capabilities'));
        
        return $meta_keys;
    }

    /**
     * Render the reference page
     */
    public static function render_page() {
        $custom_post_types = self::get_custom_post_types();
        $custom_roles = self::get_custom_roles();
        ?>
        <div class="wrap">
            <h1><?php _e('Claims Angel Reference', 'claims-angel'); ?></h1>
            
            <!-- Custom Post Types Section -->
            <h2><?php _e('Custom Post Types', 'claims-angel'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Post Type', 'claims-angel'); ?></th>
                        <th><?php _e('Label', 'claims-angel'); ?></th>
                        <th><?php _e('Meta Fields', 'claims-angel'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($custom_post_types as $post_type): ?>
                        <tr>
                            <td><code><?php echo esc_html($post_type->name); ?></code></td>
                            <td><?php echo esc_html($post_type->label); ?></td>
                            <td>
                                <?php 
                                $meta_fields = self::get_post_type_meta_fields($post_type->name);
                                if (!empty($meta_fields)) {
                                    echo '<ul>';
                                    foreach ($meta_fields as $field) {
                                        echo '<li><code>' . esc_html($field) . '</code></li>';
                                    }
                                    echo '</ul>';
                                } else {
                                    _e('No custom meta fields', 'claims-angel');
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Custom Roles Section -->
            <h2><?php _e('Custom Roles', 'claims-angel'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Role', 'claims-angel'); ?></th>
                        <th><?php _e('Display Name', 'claims-angel'); ?></th>
                        <th><?php _e('Capabilities', 'claims-angel'); ?></th>
                        <th><?php _e('Meta Fields', 'claims-angel'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($custom_roles as $role_slug => $role): ?>
                        <tr>
                            <td><code><?php echo esc_html($role_slug); ?></code></td>
                            <td><?php echo esc_html($role['name']); ?></td>
                            <td>
                                <?php 
                                if (!empty($role['capabilities'])) {
                                    echo '<ul>';
                                    foreach ($role['capabilities'] as $cap => $grant) {
                                        if ($grant) {
                                            echo '<li><code>' . esc_html($cap) . '</code></li>';
                                        }
                                    }
                                    echo '</ul>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $meta_fields = self::get_role_meta_fields($role_slug);
                                if (!empty($meta_fields)) {
                                    echo '<ul>';
                                    foreach ($meta_fields as $field) {
                                        echo '<li><code>' . esc_html($field) . '</code></li>';
                                    }
                                    echo '</ul>';
                                } else {
                                    _e('No custom meta fields', 'claims-angel');
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}