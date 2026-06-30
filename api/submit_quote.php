<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

require_once dirname(__DIR__) . '/config.php';

if ($db->connect_error) {
    jsonResponse(['success' => false, 'message' => 'server_error'], 500);
}

function readInput(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $json = json_decode($raw, true);
    if (is_array($json)) {
        return $json;
    }

    parse_str($raw, $parsed);
    return is_array($parsed) ? $parsed : [];
}

function strLen(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function normalizePhone(string $phone): string
{
    $arabic = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $western = ['0','1','2','3','4','5','6','7','8','9'];
    $phone = str_replace($arabic, $western, trim($phone));
    return preg_replace('/[^\d+]/', '', $phone) ?? '';
}

$input = readInput();

$name        = trim($input['name'] ?? '');
$phone       = normalizePhone($input['phone'] ?? '');
$email       = trim($input['email'] ?? '');
$description = trim($input['description'] ?? '');

$errors = [];

if ($name === '' || strLen($name) < 2) {
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

try {
    $stmt = $db->prepare(
        'INSERT INTO quote_requests (name, phone, email, description) VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('ssss', $name, $phone, $email, $description);
    $stmt->execute();
    $stmt->close();

    jsonResponse(['success' => true, 'message' => 'submitted']);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'message' => 'server_error'], 500);
}
