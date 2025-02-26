<?php
namespace ClaimsAngel\Admin;

/**
 * Admin Access Controller Class
 * 
 * Handles all admin access control including:
 * - Menu item visibility
 * - Page access permissions
 * - Custom page registrations
 * - Role-based access control
 * - Hidden page support
 * 
 * Created: 2025-02-21
 * Last Modified: 2025-02-21 18:49:49
 * Author: DVVTEO
 */
class AdminAccessController {
    /**
     * Instance of this class
     *
     * @var AdminAccessController
     */
    private static $instance = null;

    /**
     * Stored access rules for pages and menus
     *
     * @var array
     */
    private $access_rules = [];

    /**
     * Array of custom roles managed by the plugin
     *
     * @var array
     */
    private $custom_roles = [
        'prospector',
        'closer',
        'claims_assistant',
        'client'
    ];

    /**
     * Array of restricted roles that should only see profile
     *
     * @var array
     */
    private $restricted_roles = [
        'prospector',
        'closer',
        'claims_assistant'
    ];

    /**
     * Constructor
     * Initialize hooks for admin access control
     */
    private function __construct() {
        // Hook into WordPress admin menu
        add_action('admin_menu', [$this, 'modify_admin_menu'], 999);
        add_action('admin_init', [$this, 'check_page_access']);
    }

    /**
     * Get instance of this class
     * Ensures only one instance is created (Singleton)
     *
     * @return AdminAccessController
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the admin access controller
     * This method should be called from the main plugin file
     */
    public static function init() {
        self::get_instance();
    }

    /**
     * Register a new admin page with access rules
     * This method is called by other files when registering new menu items
     * 
     * @param string $page_slug The slug of the admin page
     * @param array $allowed_roles Array of role slugs that can access the page
     * @param array $menu_args Optional. Arguments for menu registration
     * @param bool $hidden Optional. Whether to hide from menu but keep accessible
     * @return void
     */
    public function register_admin_page($page_slug, array $allowed_roles, array $menu_args = [], $hidden = false) {
        $this->access_rules[$page_slug] = [
            'allowed_roles' => $allowed_roles,
            'menu_args' => $menu_args,
            'hidden' => $hidden
        ];
    }

    /**
     * Modify admin menu items based on user role
     * Uses stored access rules to determine visibility
     * 
     * @return void
     */
    public function modify_admin_menu() {
        $current_user = wp_get_current_user();
        
        if (!$current_user || !$current_user->exists()) {
            return;
        }

        // Check if user has any restricted roles
        $has_restricted_role = (bool)array_intersect($this->restricted_roles, $current_user->roles);

        if ($has_restricted_role) {
            // Remove default WordPress menu items
            remove_menu_page('index.php');          // Dashboard
            remove_menu_page('edit.php');           // Posts
            remove_menu_page('upload.php');         // Media
            remove_menu_page('edit.php?post_type=page'); // Pages
            remove_menu_page('edit-comments.php');  // Comments
            remove_menu_page('themes.php');         // Appearance
            remove_menu_page('plugins.php');        // Plugins
            remove_menu_page('users.php');          // Users
            remove_menu_page('tools.php');          // Tools
            remove_menu_page('options-general.php'); // Settings

            // Keep profile.php accessible but remove users.php
            global $submenu;
            if (isset($submenu['users.php'])) {
                foreach ($submenu['users.php'] as $key => $item) {
                    if ($item[2] === 'profile.php') {
                        continue;
                    }
                    remove_submenu_page('users.php', $item[2]);
                }
            }
        }

        // Process custom page access rules
        foreach ($this->access_rules as $page_slug => $rules) {
            // Remove menu items that are marked as hidden
            if (!empty($rules['hidden'])) {
                remove_menu_page($page_slug);
                
                // Also remove from submenus if it's a submenu item
                if (!empty($rules['menu_args']['parent_slug'])) {
                    remove_submenu_page($rules['menu_args']['parent_slug'], $page_slug);
                }
                continue;
            }

            // Remove menu items that user can't access
            if (!$this->can_access_page($page_slug)) {
                remove_menu_page($page_slug);
                
                // Also handle submenu items if this is a parent
                if (!empty($rules['menu_args']['parent_slug'])) {
                    remove_submenu_page($rules['menu_args']['parent_slug'], $page_slug);
                }
            }
        }
    }

    /**
     * Check if current user can access requested admin page
     * 
     * @return void
     */
    public function check_page_access() {
        global $pagenow;
        
        if (!is_admin()) {
            return;
        }

        $current_user = wp_get_current_user();
        $has_restricted_role = (bool)array_intersect($this->restricted_roles, $current_user->roles);

        // Allow access to profile.php for restricted roles
        if ($has_restricted_role && $pagenow === 'profile.php') {
            return;
        }

        $current_page = isset($_GET['page']) ? $_GET['page'] : $pagenow;

        // Check if this is a restricted user trying to access a core WordPress page
        if ($has_restricted_role && !isset($this->access_rules[$current_page])) {
            wp_safe_redirect(admin_url('profile.php'));
            exit;
        }

        if (isset($this->access_rules[$current_page]) && !$this->can_access_page($current_page)) {
            wp_safe_redirect(admin_url());
            exit;
        }
    }

    /**
     * Check if a user has access to a specific admin page
     * 
     * @param string $page_slug The slug of the admin page
     * @param \WP_User|null $user Optional. The user to check. Defaults to current user.
     * @return bool Whether the user has access to the page
     */
    public function can_access_page($page_slug, $user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }

        if (!$user || !$user->exists()) {
            return false;
        }

        if (!isset($this->access_rules[$page_slug])) {
            // For restricted roles, only allow access to registered pages
            if (array_intersect($this->restricted_roles, $user->roles)) {
                return false;
            }
            return true;
        }

        $allowed_roles = $this->access_rules[$page_slug]['allowed_roles'];

        // Check if user has any of the allowed roles
        return (bool)array_intersect($allowed_roles, $user->roles);
    }
}