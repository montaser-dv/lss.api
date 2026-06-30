<?php

require_once dirname(__DIR__) . '/config.php';

$sqlFile = dirname(__DIR__) . '/docs/DB/quote_tables.sql';
$sql = file_get_contents($sqlFile);

if ($db->connect_error) {
    die('Database connection failed: ' . $db->connect_error);
}

$db->multi_query($sql);

do {
    if ($result = $db->store_result()) {
        $result->free();
    }
} while ($db->next_result());

echo "OK: Tables created successfully.\n";
