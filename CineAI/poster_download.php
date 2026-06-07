<?php
// session_start(); // REMOVED: Avoid session locking which blocks concurrent requests!
$file = $_GET['file'] ?? null;

if (!$file) {
    die("File not specified.");
}

// Basic security check to prevent directory traversal
$file = basename($file);
$filePath = __DIR__ . '/uploads/' . $file;

if (file_exists($filePath) && is_file($filePath)) {
    // ETag-based caching - return 304 if unchanged
    $etag = '"' . md5_file($filePath) . '"';
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
        header("HTTP/1.1 304 Not Modified");
        exit;
    }

    // Get file mime type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . $file . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: public, max-age=86400'); // 1 day browser cache
    header('ETag: ' . $etag);
    readfile($filePath);
    exit;
} else {
    header("HTTP/1.0 404 Not Found");
    echo "File not found.";
}
?>
