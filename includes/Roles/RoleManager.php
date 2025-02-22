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
     * Note: Role setup is only performed on plugin activation, not on every load
     */
    public function __construct() {
        // Constructor intentionally left empty
        // Role setup is handled during plugin activation only
    }

    /**
     * Setup roles
     * This method is called during plugin activation to configure all user roles
     */
    public function setup_roles() {
        $this->remove_default_roles();
        $this->add_custom_roles();
    }

    /**
     * Remove default WordPress roles
     * Removes all standard WordPress roles except administrator
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
     * Add custom roles specific to the business management system
     * Creates four specialized roles with specific capabilities:
     * - Prospector: Can create and edit businesses
     * - Closer: Can create, edit, and publish businesses
     * - Claims Assistant: Can manage vehicles
     * - Client: Has limited read-only access
     */
    private function add_custom_roles() {
        // Prospector Role - Business lead generation
        add_role(
            'prospector',
            __('Prospector', 'business-manager'),
            [
                'read' => true,
                'edit_business' => true,
                'edit_businesses' => true,
                'read_business' => true,
                'read_private_businesses' => true
            ]
        );

        // Closer Role - Business deal finalization
        add_role(
            'closer',
            __('Closer', 'business-manager'),
            [
                'read' => true,
                'edit_business' => true,
                'edit_businesses' => true,
                'read_business' => true,
                'read_private_businesses' => true,
                'publish_businesses' => true
            ]
        );

        // Claims Assistant Role - Vehicle management
        add_role(
            'claims_assistant',
            __('Claims Assistant', 'business-manager'),
            [
                'read' => true,
                'edit_vehicle' => true,
                'edit_vehicles' => true,
                'read_vehicle' => true,
                'read_private_vehicles' => true,
                'publish_vehicles' => true
            ]
        );

        // Client Role - Limited access
        add_role(
            'client',
            __('Client', 'business-manager'),
            [
                'read' => true,
                'read_business' => true,
                'read_vehicle' => true
            ]
        );
    }
}