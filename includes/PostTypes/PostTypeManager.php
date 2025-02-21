<?php
namespace ClaimsAngel\PostTypes;

/**
 * Post Type Manager Class
 * 
 * Handles registration and configuration of custom post types:
 * - Business post type for managing business entities
 * - Vehicle post type for managing vehicles
 * 
 * Post types are configured to be private and excluded from sitemaps
 * to maintain data privacy and security.
 * 
 * Created: 2025-02-21
 * Last Modified: 2025-02-21 18:15:45
 * Author: DVVTEO
 */
class PostTypeManager {
    /**
     * Constructor
     * Initializes post type registration on WordPress init
     */
    public function __construct() {
        add_action('init', [$this, 'register_post_types']);
        // Ensure post types are excluded from sitemaps
        add_filter('wp_sitemaps_post_types', [$this, 'exclude_post_types_from_sitemap']);
    }

    /**
     * Register all custom post types
     */
    public function register_post_types() {
        $this->register_business_post_type();
        $this->register_vehicle_post_type();
    }

    /**
     * Exclude our custom post types from WordPress sitemaps
     * 
     * @param array $post_types Array of post types
     * @return array Modified array of post types
     */
    public function exclude_post_types_from_sitemap($post_types) {
        unset($post_types['business']);
        unset($post_types['vehicle']);
        return $post_types;
    }

    /**
     * Register Business post type
     * Creates a custom post type for managing business entities
     * Includes custom capabilities and labels
     * Configured to be private and excluded from public queries
     */
    private function register_business_post_type() {
        $labels = [
            'name'                  => _x('Businesses', 'Post type general name', 'business-manager'),
            'singular_name'         => _x('Business', 'Post type singular name', 'business-manager'),
            'menu_name'            => _x('Businesses', 'Admin Menu text', 'business-manager'),
            'add_new'              => __('Add New', 'business-manager'),
            'add_new_item'         => __('Add New Business', 'business-manager'),
            'edit_item'            => __('Edit Business', 'business-manager'),
            'new_item'             => __('New Business', 'business-manager'),
            'view_item'            => __('View Business', 'business-manager'),
            'search_items'         => __('Search Businesses', 'business-manager'),
            'not_found'            => __('No businesses found', 'business-manager'),
            'not_found_in_trash'   => __('No businesses found in Trash', 'business-manager'),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => false, // Set to false to make it private
            'publicly_queryable'  => false, // Prevent public queries
            'show_ui'            => true, // Show in admin UI
            'show_in_menu'       => true, // Show in admin menu
            'query_var'          => false, // Prevent query var
            'rewrite'            => false, // Prevent URL rewriting
            'capability_type'     => 'business',
            'map_meta_cap'       => true,
            'has_archive'        => false, // Prevent archive pages
            'hierarchical'       => false,
            'menu_position'      => 5,
            'supports'           => ['title', 'editor', 'author', 'revisions'],
            'menu_icon'          => 'dashicons-building',
            'exclude_from_search' => true, // Exclude from search results
            'show_in_nav_menus'  => false, // Prevent showing in navigation menus
            'show_in_rest'       => true, // Keep REST API support for admin
            'show_in_sitemap'    => false, // Exclude from sitemap
            'delete_with_user'   => false, // Prevent deletion with user
        ];

        register_post_type('business', $args);
    }

    /**
     * Register Vehicle post type
     * Creates a custom post type for managing vehicles
     * Includes custom capabilities and labels
     * Configured to be private and excluded from public queries
     */
    private function register_vehicle_post_type() {
        $labels = [
            'name'                  => _x('Vehicles', 'Post type general name', 'business-manager'),
            'singular_name'         => _x('Vehicle', 'Post type singular name', 'business-manager'),
            'menu_name'            => _x('Vehicles', 'Admin Menu text', 'business-manager'),
            'add_new'              => __('Add New', 'business-manager'),
            'add_new_item'         => __('Add New Vehicle', 'business-manager'),
            'edit_item'            => __('Edit Vehicle', 'business-manager'),
            'new_item'             => __('New Vehicle', 'business-manager'),
            'view_item'            => __('View Vehicle', 'business-manager'),
            'search_items'         => __('Search Vehicles', 'business-manager'),
            'not_found'            => __('No vehicles found', 'business-manager'),
            'not_found_in_trash'   => __('No vehicles found in Trash', 'business-manager'),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => false, // Set to false to make it private
            'publicly_queryable'  => false, // Prevent public queries
            'show_ui'            => true, // Show in admin UI
            'show_in_menu'       => true, // Show in admin menu
            'query_var'          => false, // Prevent query var
            'rewrite'            => false, // Prevent URL rewriting
            'capability_type'     => 'vehicle',
            'map_meta_cap'       => true,
            'has_archive'        => false, // Prevent archive pages
            'hierarchical'       => false,
            'menu_position'      => 6,
            'supports'           => ['title', 'editor', 'author', 'revisions'],
            'menu_icon'          => 'dashicons-car',
            'exclude_from_search' => true, // Exclude from search results
            'show_in_nav_menus'  => false, // Prevent showing in navigation menus
            'show_in_rest'       => true, // Keep REST API support for admin
            'show_in_sitemap'    => false, // Exclude from sitemap
            'delete_with_user'   => false, // Prevent deletion with user
        ];

        register_post_type('vehicle', $args);
    }
}