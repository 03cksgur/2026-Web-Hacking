<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS movie_reviews_db");
    $pdo->exec("USE movie_reviews_db");
    
    // Create all tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        login_id VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        username VARCHAR(50) NOT NULL,
        role VARCHAR(20) DEFAULT 'User',
        status VARCHAR(20) DEFAULT 'PENDING',
        email VARCHAR(100) NULL,
        profile_pic VARCHAR(255) NULL,
        bio TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        movie_title VARCHAR(200) NOT NULL,
        rating INT NOT NULL,
        review_text TEXT NOT NULL,
        poster_path VARCHAR(255) NULL,
        sentiment_score FLOAT DEFAULT 0,
        status VARCHAR(50) DEFAULT 'ACTIVE',
        deleted_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        review_id INT NOT NULL,
        user_id INT NOT NULL,
        comment_text TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS review_tags (
        review_id INT NOT NULL,
        tag_id INT NOT NULL,
        PRIMARY KEY (review_id, tag_id),
        FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        login_id VARCHAR(50) NOT NULL,
        attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS security_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_type VARCHAR(50) NOT NULL,
        description TEXT NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS notices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        content TEXT NOT NULL,
        is_active TINYINT DEFAULT 1,
        is_important TINYINT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        target_type VARCHAR(50) NULL,
        target_id INT NULL,
        reason TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
    )");

    // Create the test admin user
    $hash = password_hash('admin1234', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO users (login_id, password_hash, username, role) VALUES ('admin', '$hash', 'Admin', 'Admin')");
    $pdo->exec("INSERT IGNORE INTO users (login_id, password_hash, username, role) VALUES ('testadmin', '$hash', 'TestAdmin', 'Admin')");
    $pdo->exec("INSERT IGNORE INTO users (login_id, password_hash, username, role) VALUES ('testuser', '$hash', 'TestUser', 'User')");
    
    echo "MySQL Database Initialization Complete.\n";

} catch (PDOException $e) {
    echo "MYSQL_ERROR:" . $e->getMessage() . "\n";
}
?>
