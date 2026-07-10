<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$response = ['status' => 0, 'message' => 'Invalid request'];

$mobile_ccode = trim($_REQUEST['ccode'] ?? '');
$mobile_domain = trim($_REQUEST['domain'] ?? '');
$mobile_token = trim($_REQUEST['token'] ?? '');
$mobile_lat = trim($_REQUEST['lat'] ?? '');
$mobile_lng = trim($_REQUEST['lng'] ?? '');

if (strlen($mobile_token) <= 10 || $mobile_ccode === '' || $mobile_domain === '') {
    $response['message'] = 'Missing auth';
    echo json_encode($response);
    exit;
}

if ($mobile_lat === '' || $mobile_lng === '' || !is_numeric($mobile_lat) || !is_numeric($mobile_lng)) {
    $response['message'] = 'Invalid coordinates';
    echo json_encode($response);
    exit;
}

include('config.php');

$safeDomain = $mainDB->real_escape_string($mobile_domain);
$safeToken = $mainDB->real_escape_string($mobile_token);
$getData = $mainDB->query("SELECT * FROM domains WHERE Sub_Domain='$safeDomain' AND Token='$safeToken' LIMIT 1");

if (!$getData || $getData->num_rows === 0) {
    $response['message'] = 'Invalid domain or token';
    echo json_encode($response);
    exit;
}

$row = $getData->fetch_array(MYSQLI_ASSOC);
$db = new mysqli('localhost', $row['DB_User'], $row['DB_Pass'], $row['DB_Name']);
if ($db->connect_error) {
    $response['message'] = 'Database connection failed';
    echo json_encode($response);
    exit;
}

$db->set_charset('utf8');
$safeCcode = $db->real_escape_string($mobile_ccode);
$safeLat = $db->real_escape_string($mobile_lat);
$safeLng = $db->real_escape_string($mobile_lng);

$updated = $db->query("UPDATE couriers SET lat='$safeLat', lng='$safeLng' WHERE courier_code='$safeCcode'");

if ($updated) {
    $response['status'] = 1;
    $response['message'] = 'Location updated';
} else {
    $response['message'] = 'Update failed';
}

echo json_encode($response);
