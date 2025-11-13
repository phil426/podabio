<?php

/**
 * Feature flag helper utilities.
 */

if (!function_exists('feature_flag')) {
    /**
     * Retrieve a feature flag value.
     *
     * @param string $key
     * @param bool $default
     * @return bool
     */
    function feature_flag(string $key, bool $default = false): bool
    {
        static $flags = null;

        if ($flags === null) {
            $flags = require __DIR__ . '/../config/feature-flags.php';
        }

        return $flags[$key] ?? $default;
    }
}

