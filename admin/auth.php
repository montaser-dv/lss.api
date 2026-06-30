<?php

require_once __DIR__ . '/bootstrap.php';

function requireAdmin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function attemptLogin(string $username, string $password): array
{
    global $db;

    if ($db->connect_error) {
        return ['ok' => false, 'error' => 'db_connect', 'message' => $db->connect_error];
    }

    if (!adminTableExists('admin_users')) {
        return ['ok' => false, 'error' => 'no_table', 'message' => 'admin_users table missing'];
    }

    $stmt = $db->prepare('SELECT id, password_hash FROM admin_users WHERE username = ? LIMIT 1');
    if (!$stmt) {
        return ['ok' => false, 'error' => 'db_query', 'message' => $db->error];
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_user'] = $username;
        return ['ok' => true];
    }

    return ['ok' => false, 'error' => 'invalid_credentials'];
}
