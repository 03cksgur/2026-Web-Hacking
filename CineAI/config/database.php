<?php
// config/database.php

// A04 & A07: Enforce secure session cookies globally
// Only set session ini values if no session is active yet
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    // ini_set('session.cookie_secure', 1); // Enable this in production with SSL
}

$mysql_host = '127.0.0.1';
$mysql_user = 'root';
$mysql_pass = '';
$mysql_db = 'movie_reviews_db';

try {
    $pdo = new PDO("mysql:host=$mysql_host;dbname=$mysql_db;charset=utf8mb4", $mysql_user, $mysql_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // IP Block Check
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($client_ip) {
        $stmt_block = $pdo->prepare("SELECT id FROM blocked_ips WHERE ip_address = ?");
        $stmt_block->execute([$client_ip]);
        if ($stmt_block->fetch()) {
            http_response_code(403);
            die("Error 403: Access Denied. Your IP ($client_ip) has been blocked due to security reasons.");
        }
    }
    
} catch (PDOException $e) {
    // A10: Do not leak $e->getMessage() to users
    error_log("Database Connection Error: " . $e->getMessage());
    die("시스템 오류: 관리자에게 문의하거나 잠시 후 다시 시도해 주세요.");
}
?>
