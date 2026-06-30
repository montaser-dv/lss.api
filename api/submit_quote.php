<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    require_once __DIR__ . '/config.php';
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

require_once __DIR__ . '/config.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$name        = trim($input['name'] ?? '');
$phone       = trim($input['phone'] ?? '');
$email       = trim($input['email'] ?? '');
$description = trim($input['description'] ?? '');

$errors = [];

if ($name === '' || mb_strlen($name) < 2) {
    $errors[] = 'name';
}
if ($phone === '' || !preg_match('/^[0-9+\-\s()]{8,20}$/', $phone)) {
    $errors[] = 'phone';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'email';
}

if ($errors) {
    jsonResponse(['success' => false, 'message' => 'validation_error', 'fields' => $errors], 422);
}

try {
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO quote_requests (name, phone, email, description) VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('ssss', $name, $phone, $email, $description);
    $stmt->execute();
    $stmt->close();
    $db->close();

    jsonResponse(['success' => true, 'message' => 'submitted']);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'server_error'], 500);
}
