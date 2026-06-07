<?php
// setup_security_db.php
require_once __DIR__ . '/config/database.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        login_id VARCHAR(100) NOT NULL,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip_time (ip_address, attempted_at)
    )");
    echo "login_attempts table created successfully.";
} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage());
}
?>
