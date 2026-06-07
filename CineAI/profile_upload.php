<?php
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed)) {
        header("Location: settings.php?error=Invalid file type");
        exit;
    }

    if ($file['size'] > 2 * 1024 * 1024) { // 2MB
        header("Location: settings.php?error=File too large");
        exit;
    }

    $upload_dir = __DIR__ . '/uploads/profiles/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $filename = "profile_" . $user_id . "_" . time() . "." . $ext;
    $target = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        // Update DB
        $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
        $stmt->execute([$filename, $user_id]);
        header("Location: settings.php?success=Profile picture updated");
    } else {
        header("Location: settings.php?error=Upload failed");
    }
    exit;
}
?>
