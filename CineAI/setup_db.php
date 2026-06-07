<?php
// setup_db.php
require_once __DIR__ . '/config/database.php';

try {
    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            login_id VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            username VARCHAR(50) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'User',
            status VARCHAR(20) DEFAULT 'PENDING',
            profile_pic VARCHAR(255) NULL,
            bio TEXT NULL,
            reset_token VARCHAR(64) NULL,
            reset_token_expiry INTEGER NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            movie_title VARCHAR(200) NOT NULL,
            star_rating INTEGER NOT NULL DEFAULT 0,
            content TEXT NOT NULL,
            summary TEXT NULL,
            poster_file VARCHAR(255) NULL,
            sentiment_score REAL NULL,
            status VARCHAR(50) DEFAULT 'ACTIVE',
            deleted_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            review_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            comment_text TEXT NOT NULL,
            status VARCHAR(50) DEFAULT 'ACTIVE',
            deleted_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS hashtags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tag_name VARCHAR(50) UNIQUE NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS review_hashtags (
            review_id INTEGER NOT NULL,
            hashtag_id INTEGER NOT NULL,
            PRIMARY KEY (review_id, hashtag_id),
            FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
            FOREIGN KEY (hashtag_id) REFERENCES hashtags(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS likes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            review_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(review_id, user_id),
            FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS notices (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title VARCHAR(200) NOT NULL,
            content TEXT NOT NULL,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS security_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )"
    ];

    foreach ($queries as $query) {
        $pdo->exec($query);
    }
    
    // Add default admin if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE login_id = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hash = password_hash('admin1234', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (login_id, email, username, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute(['admin', 'admin@cineai.com', 'Admin', $hash, 'Admin', 'ACTIVE']);
        echo "Admin user created (admin / admin1234).\n";
    }

    echo "Database initialized successfully (SQLite).\n";
} catch (PDOException $e) {
    echo "Error initializing database: " . $e->getMessage();
}
?>
