<?php

require_once __DIR__ . '/debug.php';

mysqli_report(MYSQLI_REPORT_OFF);

$db = new mysqli('localhost', 'trak_user', '1M^9n1P-Oj3j90JE', 'trak_db');

if (!$db->connect_error) {
    $db->set_charset('utf8mb4');
}
