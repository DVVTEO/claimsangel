<?php
namespace ClaimsAngel\User;

use ClaimsAngel\Data\Countries;

/**
 * User Validator Class
 * Handles validation of user-related data
 * 
 * Created: 2025-02-21
 * Last Modified: 2025-02-21 19:27:03
 * Author: DVVTEO
 */
class Validator {
    /**
     * Validate country selection
     *
     * @param string $country Country code to validate
     * @return bool True if valid, false otherwise
     */
    public function validate_country_selection($country) {
        if (empty($country)) {
            return false;
        }

        return $this->is_valid_country($country);
    }

    /**
     * Check if country exists in approved list
     *
     * @param string $country Country code to check
     * @return bool True if valid, false otherwise
     */
    public function is_valid_country($country) {
        $countries = Countries::get_instance()->get_countries();
        return array_key_exists($country, $countries);
    }

    /**
     * Get validation errors
     *
     * @param string $country Country code to validate
     * @return array Array of error messages
     */
    public function get_validation_errors($country) {
        $errors = [];

        if (empty($country)) {
            $errors[] = __('Country selection is required.', 'claims-angel');
        } elseif (!$this->is_valid_country($country)) {
            $errors[] = __('Selected country is not valid.', 'claims-angel');
        }

        return $errors;
    }

    /**
     * Sanitize country input
     *
     * @param string $country Country code to sanitize
     * @return string Sanitized country code
     */
    public function sanitize_country_input($country) {
        return sanitize_text_field($country);
    }
}