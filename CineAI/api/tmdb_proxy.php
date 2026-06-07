<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$secrets = require_once __DIR__ . '/../config/secrets.php';
$apiKey = $secrets['tmdb_api_key'];
$query = $_GET['query'] ?? '';
$action = $_GET['action'] ?? 'search';
$category = $_GET['category'] ?? '';

if ($apiKey === 'YOUR_TMDB_API_KEY_HERE') {
    if ($action === 'search') {
        $allMockResults = require __DIR__ . '/../config/mock_movies.php';
        $results = $allMockResults;
        if (!empty($query)) {
            $results = array_filter($allMockResults, function($m) use ($query) {
                return stripos($m['title'], $query) !== false;
            });
        }
        if ($category) {
            $results = array_filter($results, function($m) use ($category) {
                return $m['category'] === $category;
            });
        }
        echo json_encode(['results' => array_values($results)]);
        exit;
    }
}
if ($action === 'search') {
    $url = "https://api.themoviedb.org/3/search/movie?api_key={$apiKey}&query=" . urlencode($query) . "&language=ko-KR";
    echo @file_get_contents($url);
} elseif ($action === 'details') {
    $id = $_GET['id'] ?? '';
    $url = "https://api.themoviedb.org/3/movie/{$id}?api_key={$apiKey}&language=ko-KR";
    echo @file_get_contents($url);
}