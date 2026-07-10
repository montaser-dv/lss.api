<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');

include('order_helpers.php');

$response = ['status' => 0, 'message' => 'Invalid request', 'path' => ''];

$mobile_domain = $_POST['domain'] ?? '';
$mobile_token = $_POST['token'] ?? '';
$mobile_awb = trim($_POST['awb'] ?? '');

if (strlen($mobile_token) <= 10 || $mobile_domain === '' || $mobile_awb === '' || empty($_FILES['pod_file'])) {
    echo json_encode($response);
    exit;
}

$file = $_FILES['pod_file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'Upload failed';
    echo json_encode($response);
    exit;
}

$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
];
$mime = mime_content_type($file['tmp_name']);
if (!isset($allowed[$mime])) {
    $response['message'] = 'Unsupported file type';
    echo json_encode($response);
    exit;
}

include('config.php');

$getData = $mainDB->query("SELECT * FROM domains WHERE Sub_Domain='" . $mainDB->real_escape_string($mobile_domain) . "' AND Token='" . $mainDB->real_escape_string($mobile_token) . "' LIMIT 1");
if (!$getData || $getData->num_rows === 0) {
    $response['message'] = 'Invalid domain';
    echo json_encode($response);
    exit;
}

$row = $getData->fetch_array(MYSQLI_ASSOC);
$cCode = preg_replace('/\D+/', '', (string) ($row['c_code'] ?? '0'));
$uploadDir = __DIR__ . '/uploads/pod/' . $cCode;

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    $response['message'] = 'Unable to create upload directory';
    echo json_encode($response);
    exit;
}

$safeAwb = preg_replace('/[^A-Za-z0-9_-]/', '', $mobile_awb);
$fileName = $safeAwb . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
$targetPath = $uploadDir . '/' . $fileName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    $response['message'] = 'Unable to save file';
    echo json_encode($response);
    exit;
}

$relativePath = 'uploads/pod/' . $cCode . '/' . $fileName;
$response['status'] = 1;
$response['message'] = 'Uploaded';
$response['path'] = $relativePath;

echo json_encode($response);
