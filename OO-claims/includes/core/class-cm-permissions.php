<?php
/**
 * Permissions Management Class
 *
 * @package ClaimsManagement
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * CM_Permissions Class
 */
class CM_Permissions {
    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @var object
     */
    protected static $instance = null;

    /**
     * Role capabilities array.
     *
     * @var array
     */
    private $role_capabilities;

    /**
     * Get instance of this class.
     *
     * @since 1.0.0
     * @return CM_Permissions
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->init_capabilities();
        add_action( 'admin_init', array( $this, 'ensure_roles_exist' ) );
    }

    /**
     * Initialize capabilities
     */
    private function init_capabilities() {
        $this->role_capabilities = array(
            'claims_manager' => array(
                'manage_claims',
                'edit_claims',
                'delete_claims',
                'view_reports',
                'manage_settings',
            ),
            'claims_processor' => array(
                'edit_claims',
                'view_claims',
                'process_claims',
            ),
            'claims_viewer' => array(
                'view_claims',
                'view_reports',
            ),
        );
    }

    /**
     * Ensure all required roles exist
     */
    public function ensure_roles_exist() {
        foreach ( $this->role_capabilities as $role => $capabilities ) {
            $this->create_role( $role, $capabilities );
        }
    }

    /**
     * Create a role if it doesn't exist
     *
     * @param string $role Role name.
     * @param array  $capabilities Array of capabilities.
     */
    private function create_role( $role, $capabilities ) {
        if ( ! get_role( $role ) ) {
            $display_name = ucwords( str_replace( '_', ' ', $role ) );
            $role_obj = add_role( $role, $display_name );
            
            if ( $role_obj ) {
                foreach ( $capabilities as $cap ) {
                    $role_obj->add_cap( $cap );
                }
            }
        }
    }

    /**
     * Check if user has specific capability
     *
     * @param string $capability Capability to check.
     * @param int    $user_id Optional. User ID to check.
     * @return bool
     */
    public function user_can( $capability, $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata( $user_id );
        return $user && $user->has_cap( $capability );
    }

    /**
     * Get all registered capabilities
     *
     * @return array
     */
    public function get_all_capabilities() {
        $all_caps = array();
        foreach ( $this->role_capabilities as $caps ) {
            $all_caps = array_merge( $all_caps, $caps );
        }
        return array_unique( $all_caps );
    }

    /**
     * Get role capabilities
     *
     * @param string $role Role name.
     * @return array
     */
    public function get_role_capabilities( $role ) {
        return isset( $this->role_capabilities[ $role ] ) 
            ? $this->role_capabilities[ $role ] 
            : array();
    }
}
