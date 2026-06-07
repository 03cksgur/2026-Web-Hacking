<?php
// admin/notice_action.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        
        if ($title && $content) {
            $stmt = $pdo->prepare("INSERT INTO notices (title, content) VALUES (?, ?)");
            $stmt->execute([$title, $content]);
            header('Location: notices.php?msg=created');
            exit;
        }
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("UPDATE notices SET is_active = 1 - is_active WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: notices.php?msg=toggled');
        exit;
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM notices WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: notices.php?msg=deleted');
        exit;
    }
}

header('Location: notices.php');
exit;
