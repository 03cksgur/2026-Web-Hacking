<?php
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$file = $_GET['file'] ?? '';
if (!$file) die("No file");

$path = __DIR__ . '/uploads/profiles/' . basename($file);
if (file_exists($path)) {
    $mime = mime_content_type($path);
    header("Content-Type: $mime");
    readfile($path);
    exit;
}
die("Not found");
?>
