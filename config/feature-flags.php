<?php

/**
 * Centralized feature flag configuration.
 * Flags can be overridden via environment variables (prefixed with FEATURE_).
 */

return [
    'admin_new_experience' => envFlag('FEATURE_ADMIN_NEW_EXPERIENCE', true),
    'tokens_api' => envFlag('FEATURE_TOKENS_API', true),
    'admin_account_workspace' => envFlag('FEATURE_ADMIN_ACCOUNT_WORKSPACE', true),
];

/**
 * Resolve a boolean flag from environment variables.
 *
 * @param string $name
 * @param bool $default
 * @return bool
 */
function envFlag(string $name, bool $default = false): bool
{
    $value = getenv($name);

    if ($value === false) {
        return $default;
    }

    $normalized = strtolower($value);
    return in_array($normalized, ['1', 'true', 'on', 'yes'], true);
}

