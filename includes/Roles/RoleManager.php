<?php
namespace ClaimsAngel\Roles;

/**
 * Role Manager Class
 * 
 * Handles the creation and management of custom user roles for the plugin.
 * Removes default WordPress roles and creates specialized roles for the business management system.
 * Created: 2025-02-21
 * Author: DVVTEO
 */
class RoleManager {
    /**
     * Constructor
     * Note: Role setup is only performed on plugin activation, not on every load.
     */
    public function __construct() {
        // Constructor intentionally left empty.
        // Role setup is handled during plugin activation only.
    }

    /**
     * Setup roles
     * This method is called during plugin activation to configure all user roles.
     */
    public function setup_roles() {
        $this->remove_default_roles();
        $this->add_custom_roles();
        $this->add_admin_capabilities();
    }

    /**
     * Remove default WordPress roles.
     * Removes all standard WordPress roles except administrator.
     */
    private function remove_default_roles() {
        $roles_to_remove = [
            'subscriber',
            'contributor',
            'author',
            'editor'
        ];

        foreach ($roles_to_remove as $role) {
            remove_role($role);
        }
    }

    /**
     * Add custom roles specific to the business management system.
     * Creates specialized roles with specific capabilities.
     */
    private function add_custom_roles() {
    // Prospector Role - Business lead generation.
    add_role(
        'prospector',
        __('Prospector', 'business-manager'),
        [
            'read'                    => true,
            'edit_business'           => true,
            'read_business'           => true,
            'delete_business'         => true,
            'edit_businesses'         => true,
            'edit_others_businesses'  => true,
            'publish_businesses'      => true,
            'read_private_businesses' => true,
        ]
    );

    // Closer Role - Business deal finalization.
    add_role(
        'closer',
        __('Closer', 'business-manager'),
        [
            'read'                    => true,
            'edit_business'           => true,
            'read_business'           => true,
            'delete_business'         => true,
            'edit_businesses'         => true,
            'edit_others_businesses'  => true,
            'publish_businesses'      => true,
            'read_private_businesses' => true,
        ]
    );

    // Claims Assistant Role - Vehicle management.
    add_role(
        'claims_assistant',
        __('Claims Assistant', 'business-manager'),
        [
            'read'                    => true,
            'edit_business'           => true,
            'read_business'           => true,
            'delete_business'         => true,
            'edit_businesses'         => true,
            'edit_others_businesses'  => true,
            'publish_businesses'      => true,
            'read_private_businesses' => true,
            'edit_vehicle'            => true,
            'read_vehicle'            => true,
            'publish_vehicles'        => true,
        ]
    );

    // Client Role - Limited access.
    add_role(
        'client',
        __('Client', 'business-manager'),
        [
            'read'                    => true,
            'edit_business'           => true,
            'read_business'           => true,
            'delete_business'         => true,
            'edit_businesses'         => true,
            'edit_others_businesses'  => true,
            'publish_businesses'      => true,
            'read_private_businesses' => true,
            'edit_vehicle'            => true,
            'read_vehicle'            => true,
        ]
    );
}

    /**
     * Grant the administrator role all custom capabilities for 'business' and 'vehicle' post types.
     */
    private function add_admin_capabilities() {
        $admin = get_role('administrator');
        if ( ! is_null($admin) ) {
            // Capabilities for Business post type.
            $admin->add_cap('edit_business');
            $admin->add_cap('read_business');
            $admin->add_cap('delete_business');
            $admin->add_cap('edit_businesses');
            $admin->add_cap('edit_others_businesses');
            $admin->add_cap('publish_businesses');
            $admin->add_cap('read_private_businesses');

            // Capabilities for Vehicle post type.
            $admin->add_cap('edit_vehicle');
            $admin->add_cap('read_vehicle');
            $admin->add_cap('delete_vehicle');
            $admin->add_cap('edit_vehicles');
            $admin->add_cap('edit_others_vehicles');
            $admin->add_cap('publish_vehicles');
            $admin->add_cap('read_private_vehicles');
        }
    }

    /**
     * Remove custom roles added by the plugin.
     * This method is intended to be called during plugin deactivation.
     */
    public function remove_roles() {
        remove_role('prospector');
        remove_role('closer');
        remove_role('claims_assistant');
        remove_role('client');

        // Clear role cache so changes are immediately recognized.
        wp_cache_delete('wp_user_roles', 'options');
    }
}