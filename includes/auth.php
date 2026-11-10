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

// True for both admins and superadmins (superadmins inherit all admin abilities).
function is_admin_role() {
    return in_array($_SESSION['admin_role'] ?? '', ['admin', 'superadmin'], true);
}

// Only superadmins may manage user accounts.
function is_superadmin() {
    return ($_SESSION['admin_role'] ?? '') === 'superadmin';
}

function admin_login($username, $password) {
    global $pdo;
    if (!$pdo) return false;
    $stmt = $pdo->prepare('SELECT id, username, password_hash, role, display_name, avatar_url FROM users WHERE username = ? AND role IN ("superadmin","admin","editor")');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) return false;
    $_SESSION['admin_user_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_role'] = $user['role'];
    $_SESSION['admin_display_name'] = $user['display_name'] ?? '';
    $_SESSION['admin_avatar'] = $user['avatar_url'] ?? '';
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