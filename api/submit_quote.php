<?php

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

register_shutdown_function(function () {
    $err = error_get_last();
    if (!$err || !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'message' => 'fatal_error',
        'error'   => $err['message'],
        'file'    => basename($err['file']),
        'line'    => $err['line'],
    ], JSON_UNESCAPED_UNICODE);
});

function jsonResponse(array $data, int $code = 200): void
{
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$dbFile = __DIR__ . '/db.php';
if (!is_file($dbFile)) {
    jsonResponse(['success' => false, 'message' => 'server_error', 'error' => 'db.php not found'], 500);
}

require_once $dbFile;

if (!isset($db) || !($db instanceof mysqli)) {
    jsonResponse(['success' => false, 'message' => 'server_error', 'error' => '$db not available'], 500);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

if ($db->connect_error) {
    jsonResponse([
        'success' => false,
        'message' => 'server_error',
        'error'   => 'DB connect: ' . $db->connect_error,
    ], 500);
}

$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $json = json_decode($raw, true);
        $input = is_array($json) ? $json : [];
    }
}

$name        = trim($input['name'] ?? '');
$phone       = preg_replace('/[^\d+]/', '', trim($input['phone'] ?? ''));
$email       = trim($input['email'] ?? '');
$description = trim($input['description'] ?? '');

$errors = [];
if ($name === '' || strlen($name) < 2) {
    $errors[] = 'name';
}
if ($phone === '' || !preg_match('/^(\+966|966|0)?5\d{8}$/', $phone)) {
    $errors[] = 'phone';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'email';
}

if ($errors) {
    jsonResponse(['success' => false, 'message' => 'validation_error', 'fields' => $errors], 422);
}

$stmt = $db->prepare(
    'INSERT INTO quote_requests (name, phone, email, description) VALUES (?, ?, ?, ?)'
);

if (!$stmt) {
    jsonResponse([
        'success' => false,
        'message' => 'server_error',
        'error'   => 'Prepare: ' . $db->error,
    ], 500);
}

$stmt->bind_param('ssss', $name, $phone, $email, $description);

if (!$stmt->execute()) {
    jsonResponse([
        'success' => false,
        'message' => 'server_error',
        'error'   => 'Execute: ' . $stmt->error,
    ], 500);
}

$stmt->close();
jsonResponse(['success' => true, 'message' => 'submitted']);
