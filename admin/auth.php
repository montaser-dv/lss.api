<?php

session_start();

require_once __DIR__ . '/../config.php';

function requireAdmin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function attemptLogin(string $username, string $password): bool
{
    global $db;

    if ($db->connect_error) {
        return false;
    }

    $stmt = $db->prepare('SELECT id, password_hash FROM admin_users WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_user'] = $username;
        return true;
    }

    return false;
}
