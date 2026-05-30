<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (empty($_SESSION['user'])) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

// All admin pages must be authenticated.
require_login();
