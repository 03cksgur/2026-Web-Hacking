<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Login required']);
    exit;
}

$follower_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$following_id = $input['following_id'] ?? 0;

if (!$following_id || $follower_id == $following_id) {
    echo json_encode(['error' => 'Invalid target']);
    exit;
}

// Check if already following
$stmt = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND following_id = ?");
$stmt->execute([$follower_id, $following_id]);
$exists = $stmt->fetch();

if ($exists) {
    // Unfollow
    $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?")
        ->execute([$follower_id, $following_id]);
    $is_following = false;
} else {
    // Follow
    $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)")
        ->execute([$follower_id, $following_id]);
    $is_following = true;
}

// Get updated follower count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
$stmt->execute([$following_id]);
$follower_count = $stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'is_following' => $is_following,
    'follower_count' => $follower_count
]);
?>
