<?php
// optimize_db.php
require_once __DIR__ . '/config/database.php';

try {
    echo "Starting DB Optimization...<br>";
    
    // Add indexes for performance
    $pdo->exec("CREATE INDEX idx_reviews_movie_title ON reviews(movie_title)");
    $pdo->exec("CREATE INDEX idx_users_login_id ON users(login_id)");
    $pdo->exec("CREATE INDEX idx_comments_review_id ON comments(review_id)");
    
    echo "Successfully added indexes to 'reviews', 'users', and 'comments'.<br>";
    echo "Optimization Complete!";
} catch (PDOException $e) {
    echo "Optimization failed or indexes already exist: " . $e->getMessage();
}
?>
