<?php
/**
 * Run once to create the first admin user.
 * Delete this file after use for security.
 */
require_once __DIR__ . '/../includes/config.php';

if (defined('ENV') && ENV === 'production') {
    die('Setup is disabled in production. Create the admin user locally or via DB, then deploy.');
}
if (!$pdo) {
    die('Database not configured. Run database/schema.sql first.');
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(80) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin','editor') NOT NULL DEFAULT 'editor',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_username (username)
    )");
} catch (PDOException $e) {
    // table may already exist
}

$username = 'Salah';
$password = 'Salahaa00!';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, "admin") ON DUPLICATE KEY UPDATE password_hash = ?');
$stmt->execute([$username, $hash, $hash]);

echo "Admin user created: username=<strong>" . htmlspecialchars($username) . "</strong>, password=<strong>••••••••••</strong><br>";
echo "Log in at <a href='login.php'>monitor/login.php</a>. Then DELETE this setup.php file.";
