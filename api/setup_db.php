<?php

require_once __DIR__ . '/config.php';

$sqlFile = dirname(__DIR__) . '/docs/DB/quote_tables.sql';
$sql = file_get_contents($sqlFile);

$db = getDB();
$db->multi_query($sql);

do {
    if ($result = $db->store_result()) {
        $result->free();
    }
} while ($db->next_result());

$db->close();

echo "OK: Tables created successfully.\n";
