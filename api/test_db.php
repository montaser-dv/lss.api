<?php

header('Content-Type: application/json; charset=utf-8');

try {
    require_once dirname(__DIR__) . '/config.php';

    $info = [
        'ok'       => !$db->connect_error,
        'db_error' => $db->connect_error ?: null,
        'db_name'  => 'trak_db',
        'php'      => PHP_VERSION,
    ];

    if (!$db->connect_error) {
        $tables = [];
        $res = $db->query("SHOW TABLES LIKE 'quote_requests'");
        $info['quote_requests_exists'] = $res && $res->num_rows > 0;
        $res2 = $db->query("SHOW TABLES LIKE 'admin_users'");
        $info['admin_users_exists'] = $res2 && $res2->num_rows > 0;
    }

    echo json_encode($info, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
