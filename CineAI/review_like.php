<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die(); // A01/A06 CSRF Protection
    $review_id = $_POST['review_id'] ?? '';
    if (!$review_id) {
        header("Location: index.php");
        exit;
    }

    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ? AND review_id = ?");
    $stmt->execute([$user_id, $review_id]);
    
    if ($stmt->fetchColumn() > 0) {
        // Toggle Unlike
        $del = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND review_id = ?");
        $del->execute([$user_id, $review_id]);
    } else {
        // Toggle Like
        $ins = $pdo->prepare("INSERT INTO likes (user_id, review_id) VALUES (?, ?)");
        $ins->execute([$user_id, $review_id]);

        // Notification Logic
        $stmtOwner = $pdo->prepare("SELECT r.user_id, r.movie_title, u.notif_enabled FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
        $stmtOwner->execute([$review_id]);
        $owner = $stmtOwner->fetch();

        if ($owner && $owner['user_id'] != $user_id && $owner['notif_enabled']) {
            $msg = $_SESSION['username'] . "님이 [" . $owner['movie_title'] . "] 리뷰를 좋아합니다.";
            $link = "review_read.php?id=" . $review_id;
            $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
            $notif->execute([$owner['user_id'], $msg, $link]);
        }
    }

    header("Location: review_read.php?id=" . $review_id);
    exit;
}
header("Location: index.php");
?>
