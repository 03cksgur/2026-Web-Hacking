<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
// api/tmdb_image_proxy.php
// Proxies images from image.tmdb.org with LOCAL FILE CACHING

$path = $_GET['path'] ?? '';
$size = $_GET['size'] ?? 'w500';

if (empty($path)) {
    header("HTTP/1.1 400 Bad Request");
    exit("Path is required");
}

// Support local mock posters
if (strpos($path, 'local:') === 0) {
    $filename = str_replace('local:', '', $path);
    $local_path = __DIR__ . '/../assets/img/posters/' . $filename;
    
    if (file_exists($local_path)) {
        $mime = mime_content_type($local_path);
        header("Content-Type: $mime");
        header("Cache-Control: public, max-age=604800"); // 7 days
        readfile($local_path);
        exit;
    }
}

// Ensure the path starts with a slash
if (isset($path[0]) && $path[0] !== '/') {
    $path = '/' . $path;
}

// If the path starts with DEMO_, serve the local asset
if (strpos($path, '/DEMO_') === 0) {
    $local_name = substr($path, 6);
    $local_file = __DIR__ . '/../assets/img/demo_poster/' . $local_name;
    if (file_exists($local_file)) {
        header("Content-Type: image/png");
        header("Cache-Control: public, max-age=604800");
        readfile($local_file);
        exit;
    }
}

// Fail-fast for known placeholder/invalid paths to avoid slow external requests
$invalid_patterns = ['some_id', 'SfsSfs', 'sfsfsf', 'SfsSf'];
foreach ($invalid_patterns as $pattern) {
    if (stripos($path, $pattern) !== false) {
        serveFallbackImage($path);
        exit;
    }
}

// Also fail-fast for paths that are just numbers (e.g. /369972.jpg) - these are invalid TMDB paths
if (preg_match('#^/\d+\.\w+$#', $path)) {
    serveFallbackImage($path);
    exit;
}

// --- SERVER-SIDE FILE CACHE ---
$cache_dir = sys_get_temp_dir() . '/tmdb_cache';
if (!is_dir($cache_dir)) {
    @mkdir($cache_dir, 0777, true);
}

$cache_key = md5($size . $path);
$cache_file = $cache_dir . '/' . $cache_key;
$cache_meta = $cache_file . '.meta';
$cache_ttl = 604800; // 7 days

// Serve from cache if valid
if (file_exists($cache_file) && file_exists($cache_meta) && (time() - filemtime($cache_file)) < $cache_ttl) {
    $mime = file_get_contents($cache_meta);
    header("Content-Type: {$mime}");
    header("Cache-Control: public, max-age=86400");
    header("X-Cache: HIT");
    readfile($cache_file);
    exit;
}

// Construct the TMDB image URL
$url = "https://image.tmdb.org/t/p/{$size}{$path}";

// Fetch the image with a User-Agent header and a reasonable timeout
$options = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n",
        "timeout" => 15
    ],
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false
    ]
];
$context = stream_context_create($options);
$image_data = @file_get_contents($url, false, $context);

if ($image_data === false && $size !== 'original') {
    // If w500/w185 fails, try 'original' as a fallback
    $url = "https://image.tmdb.org/t/p/original{$path}";
    $image_data = @file_get_contents($url, false, $context);
}

if ($image_data === false) {
    serveFallbackImage($path);
    exit;
}

// Get the extension to set the correct content type
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime = "image/jpeg";
if ($ext === 'png') $mime = "image/png";
if ($ext === 'webp') $mime = "image/webp";

// Save to cache
@file_put_contents($cache_file, $image_data);
@file_put_contents($cache_meta, $mime);

header("Content-Type: {$mime}");
header("Cache-Control: public, max-age=86400"); // Browser cache 1 day
header("X-Cache: MISS");
echo $image_data;

// --- Helper: generate a local SVG fallback (no external request!) ---
function serveFallbackImage($path) {
    header("Content-Type: image/svg+xml");
    header("Cache-Control: public, max-age=3600"); // cache fallback 1 hour
    $name = htmlspecialchars(basename($path, '.' . pathinfo($path, PATHINFO_EXTENSION)));
    echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="500" height="750" viewBox="0 0 500 750">
  <rect width="500" height="750" fill="#1e293b"/>
  <text x="250" y="350" text-anchor="middle" fill="#64748b" font-family="sans-serif" font-size="24">No Image</text>
  <text x="250" y="390" text-anchor="middle" fill="#475569" font-family="sans-serif" font-size="14">{$name}</text>
</svg>
SVG;
}
