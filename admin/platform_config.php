<?php

/**
 * SaaS platform tickets API (lss.web).
 * Override via environment variables or platform_config.local.php
 */
$platformConfig = [
    // Tenant system that stores support tickets
    'api_base' => getenv('TRAKMILE_API_BASE') ?: 'https://demo.trakmile.com',

    // Must match PLATFORM_SUPPORT_API_KEY on the SaaS .env
    'api_key' => getenv('TRAKMILE_PLATFORM_API_KEY') ?: 'CHANGE_ME',
];

$local = __DIR__ . '/platform_config.local.php';
if (is_file($local)) {
    $override = require $local;
    if (is_array($override)) {
        $platformConfig = array_merge($platformConfig, $override);
    }
}

return $platformConfig;
