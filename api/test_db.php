<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

$quoteTable = false;
$adminTable = false;

if (!$db->connect_error) {
    $r1 = $db->query("SHOW TABLES LIKE 'quote_requests'");
    $quoteTable = $r1 && $r1->num_rows > 0;
    $r2 = $db->query("SHOW TABLES LIKE 'admin_users'");
    $adminTable = $r2 && $r2->num_rows > 0;
}

echo json_encode([
    'ok'                    => !$db->connect_error,
    'db_error'              => $db->connect_error ?: null,
    'quote_requests_exists' => $quoteTable,
    'admin_users_exists'    => $adminTable,
    'php'                   => PHP_VERSION,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
