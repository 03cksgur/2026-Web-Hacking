<?php
require_once __DIR__ . '/config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = ?");
$stmt->execute([$id]);
$review = $stmt->fetch();

if (!$review) {
    die("존재하지 않는 리뷰입니다.");
}

// RBAC
if (!in_array($_SESSION['role'], ['Super-Admin', 'Admin']) && $_SESSION['user_id'] != $review['user_id']) {
    die("삭제 권한이 없습니다.");
}

// Delete poster file if exists
if ($review['poster_file'] && file_exists(__DIR__ . '/uploads/' . $review['poster_file'])) {
    unlink(__DIR__ . '/uploads/' . $review['poster_file']);
}

$del = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
$del->execute([$id]);

header("Location: index.php");
exit;
?>
