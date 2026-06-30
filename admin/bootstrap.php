<?php

if (session_status() === PHP_SESSION_NONE) {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_start();
}

function adminResolveDbFile(): string
{
    $paths = [
        dirname(__DIR__) . '/api/db.php',
        __DIR__ . '/db.php',
    ];

    foreach ($paths as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return '';
}

$dbFile = adminResolveDbFile();

if ($dbFile === '') {
    die('Database config not found. Expected api/db.php relative to site root.');
}

require_once $dbFile;

if (!isset($db) || !($db instanceof mysqli)) {
    die('Database connection ($db) is not available.');
}

function adminDbError(): string
{
    global $db;
    return $db->connect_error ?: '';
}

function adminTableExists(string $table): bool
{
    global $db;
    if ($db->connect_error) {
        return false;
    }
    $safe = $db->real_escape_string($table);
    $result = $db->query("SHOW TABLES LIKE '$safe'");
    return $result && $result->num_rows > 0;
}
