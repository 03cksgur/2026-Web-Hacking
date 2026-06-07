<?php
// watchlist_action.php
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
    $action = $_POST['action'] ?? '';
    $tmdb_id = (int)($_POST['tmdb_id'] ?? 0);
    $title = $_POST['title'] ?? '';
    $poster_path = $_POST['poster_path'] ?? '';
    $redirect = $_POST['redirect'] ?? 'watchlist.php';

    if ($action === 'add' && $tmdb_id) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO watchlist (user_id, tmdb_id, title, poster_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $tmdb_id, $title, $poster_path]);
    } elseif ($action === 'remove' && $tmdb_id) {
        $stmt = $pdo->prepare("DELETE FROM watchlist WHERE user_id = ? AND tmdb_id = ?");
        $stmt->execute([$_SESSION['user_id'], $tmdb_id]);
    }
}

header("Location: " . $redirect);
exit;
