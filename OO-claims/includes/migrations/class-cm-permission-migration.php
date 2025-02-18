<?php
/**
 * Permission Migration Class
 *
 * @package ClaimsManagement
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * CM_Permission_Migration Class
 */
class CM_Permission_Migration {
    /**
     * Run the migration
     */
    public static function run() {
        self::backup_current_roles();
        self::migrate_users();
    }

    /**
     * Backup current roles
     */
    private static function backup_current_roles() {
        $roles = get_option( 'wp_user_roles' );
        update_option( 'cm_roles_backup_' . time(), $roles );
    }

    /**
     * Migrate users to new permission system
     */
    private static function migrate_users() {
        $users = get_users( array(
            'fields' => array( 'ID', 'roles' )
        ) );

        foreach ( $users as $user ) {
            self::migrate_user_roles( $user );
        }
    }

    /**
     * Migrate individual user roles
     *
     * @param WP_User $user User object.
     */
    private static function migrate_user_roles( $user ) {
        $user_obj = new WP_User( $user->ID );
        $current_roles = $user_obj->roles;

        foreach ( $current_roles as $role ) {
            switch ( $role ) {
                case 'administrator':
                    $user_obj->add_role( 'claims_manager' );
                    break;
                case 'editor':
                    $user_obj->add_role( 'claims_processor' );
                    break;
                case 'subscriber':
                    $user_obj->add_role( 'claims_viewer' );
                    break;
            }
        }
    }

    /**
     * Rollback migration
     *
     * @param string $backup_key Backup timestamp key.
     * @return bool
     */
    public static function rollback( $backup_key ) {
        $backup = get_option( 'cm_roles_backup_' . $backup_key );
        if ( $backup ) {
            update_option( 'wp_user_roles', $backup );
            return true;
        }
        return false;
    }
}
