<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include('order_helpers.php');

$response = ['status' => 0, 'message' => 'Invalid request', 'path' => '', 'url' => ''];

$mobile_domain = trim($_POST['domain'] ?? '');
$mobile_token = trim($_POST['token'] ?? '');
$mobile_awb = trim($_POST['awb'] ?? '');

if (strlen($mobile_token) <= 10 || $mobile_domain === '' || $mobile_awb === '') {
    $response['message'] = 'Missing auth or AWB';
    echo json_encode($response);
    exit;
}

if (empty($_FILES['pod_file']) || !isset($_FILES['pod_file']['error'])) {
    $response['message'] = 'No file received';
    echo json_encode($response);
    exit;
}

$file = $_FILES['pod_file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds server limit',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
        UPLOAD_ERR_PARTIAL => 'File partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
        UPLOAD_ERR_EXTENSION => 'Upload blocked by extension',
    ];
    $response['message'] = $uploadErrors[$file['error']] ?? 'Upload failed';
    echo json_encode($response);
    exit;
}

$allowed = [
    'image/jpeg' => 'jpg',
    'image/jpg' => 'jpg',
    'image/pjpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
];

$mime = '';
if (is_uploaded_file($file['tmp_name'])) {
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = (string) finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
    }
    if ($mime === '' && function_exists('mime_content_type')) {
        $mime = (string) mime_content_type($file['tmp_name']);
    }
}
if ($mime === '' && !empty($file['type'])) {
    $mime = strtolower((string) $file['type']);
}
if ($mime === 'image/jpg') {
    $mime = 'image/jpeg';
}

$extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
if (!isset($allowed[$mime]) && in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
    $mimeMap = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
    ];
    $mime = $mimeMap[$extension];
}

if (!isset($allowed[$mime])) {
    $response['message'] = 'Unsupported file type';
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
$storage = mobile_resolve_pod_storage($row);
if (!$storage) {
    $response['message'] = 'Unable to prepare POD directory';
    echo json_encode($response);
    exit;
}

$safeAwb = preg_replace('/[^A-Za-z0-9_-]/', '', $mobile_awb);
$fileName = $safeAwb . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
$targetPath = rtrim($storage['dir'], '/\\') . DIRECTORY_SEPARATOR . $fileName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    $response['message'] = 'Unable to save file';
    echo json_encode($response);
    exit;
}

@chmod($targetPath, 0644);

$relativePath = mobile_normalize_pod_file_db_value(
    mobile_pod_db_value_from_storage($storage, $fileName)
);
$response['status'] = 1;
$response['message'] = 'Uploaded';
$response['path'] = $relativePath;
$response['url'] = mobile_build_pod_public_url($storage, $fileName);

echo json_encode($response);
