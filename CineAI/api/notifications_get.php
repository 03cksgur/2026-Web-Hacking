<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Login required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
$stmt->execute([$user_id, $limit]);
$notifications = $stmt->fetchAll();

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$countStmt->execute([$user_id]);
$unread_count = $countStmt->fetchColumn();

// Optional: Mark as read if a flag is passed
if (isset($_GET['mark_read'])) {
    $update = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $update->execute([$user_id]);
}

echo json_encode(['notifications' => $notifications, 'unread_count' => $unread_count]);
?>
