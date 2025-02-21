<?php
namespace ClaimsAngel\Data;

/**
 * Countries Class
 * 
 * Centralized management of country data including:
 * - Country codes
 * - Dialing codes
 * - Flag URLs
 * - Standardized country names
 * 
 * Created: 2025-02-21
 * Last Modified: 2025-02-21 19:13:38
 * Author: DVVTEO
 */
class Countries {
    /**
     * Instance of this class
     *
     * @var Countries
     */
    private static $instance = null;

    /**
     * Array of all supported countries with their data
     *
     * @var array
     */
    private $countries = [
        'GB' => [
            'name' => 'United Kingdom',
            'dialing_code' => '44',
            'flag_url' => 'gb.svg',
            'enabled' => true
        ],
        'DE' => [
            'name' => 'Germany',
            'dialing_code' => '49',
            'flag_url' => 'de.svg',
            'enabled' => true
        ],
        'FR' => [
            'name' => 'France',
            'dialing_code' => '33',
            'flag_url' => 'fr.svg',
            'enabled' => true
        ],
        'ES' => [
            'name' => 'Spain',
            'dialing_code' => '34',
            'flag_url' => 'es.svg',
            'enabled' => true
        ],
        'IT' => [
            'name' => 'Italy',
            'dialing_code' => '39',
            'flag_url' => 'it.svg',
            'enabled' => true
        ],
        'NL' => [
            'name' => 'Netherlands',
            'dialing_code' => '31',
            'flag_url' => 'nl.svg',
            'enabled' => true
        ],
        'BE' => [
            'name' => 'Belgium',
            'dialing_code' => '32',
            'flag_url' => 'be.svg',
            'enabled' => true
        ],
        'PT' => [
            'name' => 'Portugal',
            'dialing_code' => '351',
            'flag_url' => 'pt.svg',
            'enabled' => true
        ],
        'IE' => [
            'name' => 'Ireland',
            'dialing_code' => '353',
            'flag_url' => 'ie.svg',
            'enabled' => true
        ],
        'DK' => [
            'name' => 'Denmark',
            'dialing_code' => '45',
            'flag_url' => 'dk.svg',
            'enabled' => true
        ],
        'SE' => [
            'name' => 'Sweden',
            'dialing_code' => '46',
            'flag_url' => 'se.svg',
            'enabled' => true
        ],
        'NO' => [
            'name' => 'Norway',
            'dialing_code' => '47',
            'flag_url' => 'no.svg',
            'enabled' => true
        ],
        'FI' => [
            'name' => 'Finland',
            'dialing_code' => '358',
            'flag_url' => 'fi.svg',
            'enabled' => true
        ],
        'AT' => [
            'name' => 'Austria',
            'dialing_code' => '43',
            'flag_url' => 'at.svg',
            'enabled' => true
        ],
        'CH' => [
            'name' => 'Switzerland',
            'dialing_code' => '41',
            'flag_url' => 'ch.svg',
            'enabled' => true
        ],
        'PL' => [
            'name' => 'Poland',
            'dialing_code' => '48',
            'flag_url' => 'pl.svg',
            'enabled' => true
        ],
        'CZ' => [
            'name' => 'Czech Republic',
            'dialing_code' => '420',
            'flag_url' => 'cz.svg',
            'enabled' => true
        ],
        'GR' => [
            'name' => 'Greece',
            'dialing_code' => '30',
            'flag_url' => 'gr.svg',
            'enabled' => true
        ],
        'HU' => [
            'name' => 'Hungary',
            'dialing_code' => '36',
            'flag_url' => 'hu.svg',
            'enabled' => true
        ],
        'RO' => [
            'name' => 'Romania',
            'dialing_code' => '40',
            'flag_url' => 'ro.svg',
            'enabled' => true
        ]
    ];

    /**
     * Constructor
     */
    private function __construct() {
        // Initialize any necessary hooks or filters
    }

    /**
     * Get instance of this class
     *
     * @return Countries
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get all countries
     *
     * @param bool $enabled_only Optional. Whether to return only enabled countries
     * @return array Array of all countries
     */
    public function get_all($enabled_only = false) {
        if (!$enabled_only) {
            return $this->countries;
        }

        return array_filter($this->countries, function($country) {
            return $country['enabled'];
        });
    }

    /**
     * Get country data by ISO code
     *
     * @param string $code The ISO country code
     * @return array|null Country data or null if not found
     */
    public function get_country($code) {
        $code = strtoupper($code);
        return isset($this->countries[$code]) ? $this->countries[$code] : null;
    }

    /**
     * Find country ISO code by name
     * 
     * @param string $name The country name to search for
     * @return string|null The ISO code if found, null if not found
     */
    public function find_country_code($name) {
        // Normalize the input name
        $search_name = strtolower(trim($name));

        // Direct match first
        foreach ($this->countries as $code => $data) {
            if (strtolower($data['name']) === $search_name) {
                return $code;
            }
        }

        // Try common variations
        $variations = [
            'UK' => 'GB',
            'United Kingdom' => 'GB',
            'Great Britain' => 'GB',
            'England' => 'GB',
            'Deutschland' => 'DE',
            'España' => 'ES',
            'Espana' => 'ES',
            'Nederland' => 'NL',
            'Holland' => 'NL',
            'Suisse' => 'CH',
            'Schweiz' => 'CH',
            'Österreich' => 'AT',
            'Osterreich' => 'AT',
            'Ceská republika' => 'CZ',
            'Ceska republika' => 'CZ',
            'Hellas' => 'GR',
            'Suomi' => 'FI',
            'Sverige' => 'SE',
            'Norge' => 'NO'
        ];

        // Check variations
        $search_key = array_search($search_name, array_map('strtolower', $variations));
        if ($search_key !== false) {
            return $variations[$search_key];
        }

        return null;
    }

    /**
     * Get country name by ISO code
     *
     * @param string $code The ISO country code
     * @return string|null Country name or null if not found
     */
    public function get_country_name($code) {
        $country = $this->get_country($code);
        return $country ? $country['name'] : null;
    }

    /**
     * Get dialing code by ISO code
     *
     * @param string $code The ISO country code
     * @return string|null Dialing code or null if not found
     */
    public function get_dialing_code($code) {
        $country = $this->get_country($code);
        return $country ? $country['dialing_code'] : null;
    }

    /**
     * Get flag URL by ISO code
     *
     * @param string $code The ISO country code
     * @return string|null Flag URL or null if not found
     */
    public function get_flag_url($code) {
        $country = $this->get_country($code);
        if (!$country) {
            return null;
        }

        return plugins_url('assets/flags/' . $country['flag_url'], CLAIMS_ANGEL_PLUGIN_FILE);
    }

    /**
     * Get countries as options array (for select fields)
     *
     * @param bool $enabled_only Optional. Whether to return only enabled countries
     * @return array Array of countries in [code => name] format
     */
    public function get_countries_options($enabled_only = false) {
        $countries = $this->get_all($enabled_only);
        $options = [];

        foreach ($countries as $code => $data) {
            $options[$code] = $data['name'];
        }

        asort($options); // Sort by country name
        return $options;
    }

    /**
     * Format a phone number according to country format
     *
     * @param string $phone The phone number to format
     * @param string $country_code The ISO country code
     * @return string|null Formatted phone number or null if invalid
     */
    public function format_phone_number($phone, $country_code) {
        $country = $this->get_country($country_code);
        if (!$country) {
            return null;
        }

        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Remove country code if it exists
        $dialing_code = $country['dialing_code'];
        if (strpos($phone, $dialing_code) === 0) {
            $phone = substr($phone, strlen($dialing_code));
        }

        // Add country code back without space
        return '+' . $dialing_code . $phone;
    }

    /**
     * Check if a country is enabled
     *
     * @param string $code The ISO country code
     * @return bool Whether the country is enabled
     */
    public function is_country_enabled($code) {
        $country = $this->get_country($code);
        return $country ? $country['enabled'] : false;
    }
}