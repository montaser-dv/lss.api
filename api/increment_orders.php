<?php

/**
 * Increment domains.current_total_orders by company c_code.
 *
 * POST /api/increment_orders.php
 * JSON/body:
 *   c_code (required)  matches domains.c_code / companies.CCode
 *   token  (required)  domains.Token for that company row
 *   by     (optional)  default 1
 */

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

function jsonResponse(array $data, int $code = 200): void
{
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Domain-Token, X-Company-Code');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

require_once __DIR__ . '/db.php';

if (!isset($db) || !($db instanceof mysqli) || $db->connect_error) {
    jsonResponse([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => isset($db) && $db instanceof mysqli ? $db->connect_error : 'db unavailable',
    ], 500);
}

$db->set_charset('utf8mb4');

$raw = file_get_contents('php://input');
$json = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
$input = is_array($json) ? $json : $_POST;

$cCode = trim((string) (
    $input['c_code']
    ?? $input['CCode']
    ?? ($_SERVER['HTTP_X_COMPANY_CODE'] ?? '')
));
$token = trim((string) (
    $input['token']
    ?? ($_SERVER['HTTP_X_DOMAIN_TOKEN'] ?? '')
));
$by = (int) ($input['by'] ?? $input['count'] ?? 1);
if ($by < 1) {
    $by = 1;
}
if ($by > 10000) {
    $by = 10000;
}

if ($cCode === '' || $token === '') {
    jsonResponse([
        'success' => false,
        'message' => 'c_code and token are required',
    ], 422);
}

$check = $db->query("SHOW COLUMNS FROM domains LIKE 'current_total_orders'");
if (!$check || $check->num_rows === 0) {
    jsonResponse([
        'success' => false,
        'message' => 'current_total_orders column missing on domains table',
    ], 500);
}

// Auth + locate company domain row by c_code (same code as companies.CCode)
$sql = 'SELECT ID, Sub_Domain, c_code, current_total_orders, Status
        FROM domains
        WHERE c_code = ? AND Token = ?
        LIMIT 1';
$stmt = $db->prepare($sql);
if (!$stmt) {
    jsonResponse(['success' => false, 'message' => 'prepare failed', 'error' => $db->error], 500);
}

$stmt->bind_param('ss', $cCode, $token);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized company code'], 401);
}

if (isset($row['Status']) && strcasecmp((string) $row['Status'], 'Suspended') === 0) {
    jsonResponse(['success' => false, 'message' => 'Domain suspended'], 403);
}

// Update the domains row for this company c_code
$update = $db->prepare(
    'UPDATE domains
     SET current_total_orders = COALESCE(current_total_orders, 0) + ?
     WHERE c_code = ?'
);
if (!$update) {
    jsonResponse(['success' => false, 'message' => 'prepare update failed', 'error' => $db->error], 500);
}

$update->bind_param('is', $by, $cCode);
$ok = $update->execute();
$affected = $update->affected_rows;
$update->close();

if (!$ok || $affected < 1) {
    jsonResponse(['success' => false, 'message' => 'update failed', 'error' => $db->error], 500);
}

$fresh = $db->prepare('SELECT current_total_orders, Sub_Domain FROM domains WHERE c_code = ? LIMIT 1');
$fresh->bind_param('s', $cCode);
$fresh->execute();
$freshRes = $fresh->get_result();
$freshRow = $freshRes ? $freshRes->fetch_assoc() : null;
$fresh->close();

jsonResponse([
    'success' => true,
    'message' => 'updated',
    'c_code' => $cCode,
    'sub_domain' => $freshRow['Sub_Domain'] ?? ($row['Sub_Domain'] ?? null),
    'incremented_by' => $by,
    'current_total_orders' => (int) ($freshRow['current_total_orders'] ?? ((int) $row['current_total_orders'] + $by)),
]);
