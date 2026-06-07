<?php
// setup_security_logs.php
require_once __DIR__ . '/config/database.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS security_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_type VARCHAR(50) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "security_logs table created successfully.";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
