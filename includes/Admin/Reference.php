<?php
namespace ClaimsAngel\Admin;

/**
 * Reference Class
 * Provides an admin interface displaying registered custom post types, roles, and meta fields
 * 
 * Created: 2025-02-21
 * Last Modified: 2025-02-21 20:26:59
 * Author: DVVTEO
 */
class Reference {
    /**
     * Initialize the reference page
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu_page'], 20);
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
        // Get all registered meta keys for this post type
        $registered_meta = get_registered_meta_keys('post', $post_type);
        $meta_fields = array();
        
        if (!empty($registered_meta)) {
            foreach ($registered_meta as $meta_key => $meta_args) {
                // Skip WordPress internal meta fields
                if (strpos($meta_key, '_') === 0) {
                    continue;
                }
                
                $meta_fields[$meta_key] = array(
                    'type'          => $meta_args['type'],
                    'description'   => isset($meta_args['description']) ? $meta_args['description'] : '',
                    'single'        => $meta_args['single'],
                    'show_in_rest'  => isset($meta_args['show_in_rest']) ? $meta_args['show_in_rest'] : false,
                );
            }
        }
        
        return $meta_fields;
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
            <h1><?php echo esc_html__('Claims Angel Reference', 'claims-angel'); ?></h1>
            
            <h2><?php echo esc_html__('Custom Post Types', 'claims-angel'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Name', 'claims-angel'); ?></th>
                        <th><?php echo esc_html__('Label', 'claims-angel'); ?></th>
                        <th><?php echo esc_html__('Meta Fields', 'claims-angel'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($custom_post_types)): ?>
                        <?php foreach ($custom_post_types as $post_type): ?>
                            <tr>
                                <td><code><?php echo esc_html($post_type->name); ?></code></td>
                                <td><?php echo esc_html($post_type->label); ?></td>
                                <td>
                                    <?php 
                                    $meta_fields = self::get_post_type_meta_fields($post_type->name);
                                    if (!empty($meta_fields)) {
                                        echo '<table class="widefat" style="margin-top: 5px;">';
                                        echo '<thead><tr>';
                                        echo '<th>' . esc_html__('Field Name', 'claims-angel') . '</th>';
                                        echo '<th>' . esc_html__('Type', 'claims-angel') . '</th>';
                                        echo '<th>' . esc_html__('Storage', 'claims-angel') . '</th>';
                                        echo '<th>' . esc_html__('REST Support', 'claims-angel') . '</th>';
                                        echo '</tr></thead><tbody>';
                                        
                                        foreach ($meta_fields as $field_name => $field_data) {
                                            echo '<tr>';
                                            echo '<td><code>' . esc_html($field_name) . '</code></td>';
                                            echo '<td><code>' . esc_html($field_data['type']) . '</code></td>';
                                            echo '<td>' . esc_html($field_data['single'] ? 'Single' : 'Multiple') . '</td>';
                                            echo '<td><code>' . ( $field_data['show_in_rest'] ? 'true' : 'false' ) . '</code></td>';
                                            echo '</tr>';
                                        }
                                        
                                        echo '</tbody></table>';
                                    } else {
                                        echo esc_html__('No registered meta fields', 'claims-angel');
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3"><?php echo esc_html__('No custom post types found', 'claims-angel'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2><?php echo esc_html__('Custom Roles', 'claims-angel'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Role', 'claims-angel'); ?></th>
                        <th><?php echo esc_html__('Display Name', 'claims-angel'); ?></th>
                        <th><?php echo esc_html__('Capabilities', 'claims-angel'); ?></th>
                        <th><?php echo esc_html__('Meta Fields', 'claims-angel'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($custom_roles)): ?>
                        <?php foreach ($custom_roles as $role_slug => $role_data): ?>
                            <tr>
                                <td><code><?php echo esc_html($role_slug); ?></code></td>
                                <td><?php echo esc_html($role_data['name']); ?></td>
                                <td>
                                    <?php 
                                    $capabilities = array_keys(array_filter($role_data['capabilities']));
                                    if (!empty($capabilities)) {
                                        foreach ($capabilities as $cap) {
                                            echo '<code>' . esc_html($cap) . '</code><br>';
                                        }
                                    } else {
                                        echo esc_html__('No special capabilities', 'claims-angel');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $meta_fields = self::get_role_meta_fields($role_slug);
                                    if (!empty($meta_fields)) {
                                        foreach ($meta_fields as $field) {
                                            echo '<code>' . esc_html($field) . '</code><br>';
                                        }
                                    } else {
                                        echo esc_html__('No custom meta fields', 'claims-angel');
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4"><?php echo esc_html__('No custom roles found', 'claims-angel'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- New Section: Display Registered Custom Post Type Settings -->
            <h2><?php echo esc_html__('Custom Post Type Settings', 'claims-angel'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Post Type', 'claims-angel'); ?></th>
                        <th><?php echo esc_html__('Public', 'claims-angel'); ?></th>
                        <th><?php echo esc_html__('Publicly Queryable', 'claims-angel'); ?></th>
                        <th><?php echo esc_html__('Show in REST', 'claims-angel'); ?></th>
                        <th><?php echo esc_html__('Capability Type', 'claims-angel'); ?></th>
                        <th><?php echo esc_html__('Rewrite', 'claims-angel'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($custom_post_types as $post_type): ?>
                        <tr>
                            <td><code><?php echo esc_html($post_type->name); ?></code></td>
                            <td><?php echo esc_html($post_type->public ? 'true' : 'false'); ?></td>
                            <td><?php echo esc_html($post_type->publicly_queryable ? 'true' : 'false'); ?></td>
                            <td><?php echo esc_html($post_type->show_in_rest ? 'true' : 'false'); ?></td>
                            <td><code><?php echo esc_html($post_type->capability_type); ?></code></td>
                            <td>
                                <?php 
                                if (is_array($post_type->rewrite)) {
                                    echo '<pre>' . esc_html(print_r($post_type->rewrite, true)) . '</pre>';
                                } else {
                                    echo esc_html($post_type->rewrite);
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
            // Display REST API endpoint for the 'business' custom post type.
            function display_custom_endpoint() {
                $post_type_obj = get_post_type_object('business');
                if ($post_type_obj && !empty($post_type_obj->rest_base)) {
                    // Build the endpoint URL.
                    $endpoint = rest_url('wp/v2/' . $post_type_obj->rest_base);
                    echo '<p>' . __('The REST API endpoint for your custom post type is:', 'claims-angel') . ' <code>' . esc_url($endpoint) . '</code></p>';
                } else {
                    echo '<p>' . __('Custom post type endpoint not available.', 'claims-angel') . '</p>';
                }
            }
            display_custom_endpoint();
            ?>
        </div>
        <?php
    }
}