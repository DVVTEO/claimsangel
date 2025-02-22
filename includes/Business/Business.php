<?php
namespace ClaimsAngel\Business;

/**
 * Business Class
 * Handles core business functionality
 * 
 * Created: 2025-02-21
 * Last Modified: 2025-02-21 19:19:34
 * Author: DVVTEO
 */
class Business {
    /**
     * Initialize business hooks
     */
    public static function init() {
        add_action('init', [__CLASS__, 'register_post_types']);
        add_action('init', [__CLASS__, 'register_taxonomies']);
    }

    /**
     * Register custom post types
     */
    public static function register_post_types() {
        register_post_type('business', [
            'labels' => [
                'name' => __('Businesses', 'claims-angel'),
                'singular_name' => __('Business', 'claims-angel')
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_icon' => 'dashicons-building'
        ]);
    }

    /**
     * Register custom taxonomies
     */
    public static function register_taxonomies() {
        register_taxonomy('business_type', 'business', [
            'labels' => [
                'name' => __('Business Types', 'claims-angel'),
                'singular_name' => __('Business Type', 'claims-angel')
            ],
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true
        ]);
    }
}