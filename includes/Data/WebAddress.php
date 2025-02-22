<?php
namespace ClaimsAngel\Data;

/**
 * Web Address Handler Class
 * 
 * Handles validation and normalization of web addresses
 * 
 * Created: 2025-02-21
 * Last Modified: 2025-02-21 23:02:35
 * Author: DVVTEO
 */
class WebAddress {
    /**
     * Instance of this class
     *
     * @var WebAddress
     */
    private static $instance = null;

    /**
     * Get instance of this class
     *
     * @return WebAddress
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor
     */
    private function __construct() {}

    /**
     * Normalize a web address
     *
     * @param string $url The URL to normalize
     * @return string The normalized URL
     */
    public function normalize($url) {
        if (empty($url)) {
            return '';
        }

        // Remove whitespace
        $url = trim($url);

        // Add https:// if no protocol specified
        if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
            $url = 'https://' . $url;
        }

        // Convert to lowercase
        $url = strtolower($url);

        // Remove trailing slashes
        $url = rtrim($url, '/');

        // Parse URL to components
        $parsed = parse_url($url);
        
        if (!$parsed || !isset($parsed['host'])) {
            return '';
        }

        // Remove 'www.' if present
        $host = preg_replace('/^www\./', '', $parsed['host']);
        
        // Rebuild URL
        $normalized = isset($parsed['scheme']) ? $parsed['scheme'] : 'https';
        $normalized .= '://' . $host;
        
        if (isset($parsed['path'])) {
            $normalized .= $parsed['path'];
        }
        
        if (isset($parsed['query'])) {
            $normalized .= '?' . $parsed['query'];
        }

        return $normalized;
    }

    /**
     * Validate a web address
     *
     * @param string $url The URL to validate
     * @return bool Whether the URL is valid
     */
    public function validate($url) {
        if (empty($url)) {
            return false;
        }

        // Use WordPress's URL validation
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}