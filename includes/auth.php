<?php
/**
 * Blemhader - Admin authentication
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_admin_logged_in() {
    return !empty($_SESSION['admin_user_id']) && !empty($_SESSION['admin_username']);
}

function require_admin() {
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function get_admin_username() {
    return $_SESSION['admin_username'] ?? '';
}

function is_admin_role() {
    return ($_SESSION['admin_role'] ?? '') === 'admin';
}

function admin_login($username, $password) {
    global $pdo;
    if (!$pdo) return false;
    $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ? AND role IN ("admin","editor")');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) return false;
    $_SESSION['admin_user_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_role'] = $user['role'];
    return true;
}

function admin_logout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
