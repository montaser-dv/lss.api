<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'lss_main_user');
define('DB_PASS', '905DY2KpP60WfvDb');
define('DB_NAME', 'lss_main');

function getDB(): mysqli
{
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($db->connect_error) {
        throw new RuntimeException('Database connection failed');
    }
    $db->set_charset('utf8mb4');
    return $db;
}

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
