<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');
error_reporting(E_ALL);

define('DEBUG_MODE', true);

function debugError(Throwable $e): string
{
    return DEBUG_MODE ? $e->getMessage() : '';
}

function debugDbError(mysqli $db): string
{
    return DEBUG_MODE ? ($db->error ?: $db->connect_error) : '';
}
