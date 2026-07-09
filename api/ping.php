<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-cache');

echo json_encode([
    'ok' => true,
    'service' => 'trakmile-api',
    'php' => PHP_VERSION,
    'api_version' => '2.0',
    'timestamp' => gmdate('c'),
    'endpoints' => [
        'platform' => '/api/platform.php',
        'submit_quote' => '/api/submit_quote.php',
    ],
], JSON_UNESCAPED_UNICODE);
