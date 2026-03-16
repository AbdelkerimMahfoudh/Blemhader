<?php
/**
 * Blemhader - Database connection (PDO)
 * Requires DB_HOST, DB_NAME, DB_USER, DB_PASS (from config.php / environment).
 */

if (!defined('DB_HOST') || !defined('DB_NAME')) {
    return;
}

$pdo = null;
$db_error = null;

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    if (defined('ENV') && ENV === 'production') {
        $db_error = 'Database unavailable.';
        if (defined('DEBUG') && DEBUG) {
            $db_error = $e->getMessage();
        }
    } else {
        $db_error = $e->getMessage();
    }
    $pdo = null;
}
