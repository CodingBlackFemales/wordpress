<?php

/**
 * Handles converting truthy values to true boolean values.
 *
 * @since 1.0.0
 *
 * @package StellarWP\Templates
 */
namespace LearnDash\Zapier\StellarWP\Templates\Utils;

use LearnDash\Zapier\StellarWP\Templates\Config;
/**
 * A class for standardizing conditions.
 *
 * @since 1.0.0
 *
 * @package \StellarWP\Templates
 */
class Conditions
{
    /**
     * Determines if the provided value should be regarded as 'true'.
     *
     * @since 1.0.0
     *
     * @param mixed $value The variable to check.
     *
     * @return bool
     */
    public static function is_truthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $hook_prefix = Config::get_hook_prefix();
        /**
         * Provides an opportunity to modify strings that will be
         * deemed to evaluate to true.
         *
         * @param array $truthy_strings
         */
        $truthy_strings = (array) apply_filters("stellarwp/templates/{$hook_prefix}/is_truthy_strings", ['1', 'enable', 'enabled', 'on', 'y', 'yes', 'true']);
        // Makes sure we are dealing with lowercase for testing.
        if (is_string($value)) {
            $value = strtolower($value);
        }
        // If $value is a string, it is only true if it is contained in the above array.
        if (in_array($value, $truthy_strings, true)) {
            return true;
        }
        // All other strings will be treated as false.
        if (is_string($value)) {
            return false;
        }
        // For other types (ints, floats etc) cast to bool.
        return (bool) $value;
    }
}