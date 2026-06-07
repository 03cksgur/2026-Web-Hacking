<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/gemini.php'; 
require_once __DIR__ . '/../api/privacy_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Login required']);
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Get user's review history & watchlist
$stmt = $pdo->prepare("SELECT movie_title, star_rating, content FROM reviews WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$reviews = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT title FROM watchlist WHERE user_id = ? LIMIT 5");
$stmt->execute([$user_id]);
$watchlist = $stmt->fetchAll();

if (empty($reviews) && empty($watchlist)) {
    echo json_encode(['recommendations' => [], 'message' => '리뷰를 작성하거나 보관함에 영화를 담으면 AI가 추천을 시작합니다!']);
    exit;
}

// 1.5 De-identify data before sending to AI
$reviews = PrivacyHelper::maskUserContext($reviews);

// 2. Build prompt for Gemini
$history_text = "";
foreach ($reviews as $r) {
    $history_text .= "- {$r['movie_title']} (Rating: {$r['star_rating']}/5): {$r['content']}\n";
}
foreach ($watchlist as $w) {
    $history_text .= "- Watchlist: {$w['title']}\n";
}

$prompt = "Based on the following movie review history and watchlist of a user, recommend 3 movies they might like. 
Provide the title and a short reason for each. Format as JSON array of objects with 'title' and 'reason' keys.
User History:\n$history_text";

// 3. Call AI
$raw_ai_text = callGemini($prompt); 
$clean_json = preg_replace('/^```json\s*|\s*```$/', '', trim($raw_ai_text));
$recommendations = json_decode($clean_json, true);

if (!$recommendations) {
    // Fallback if parsing fails
    $recommendations = [['title' => '신세계', 'reason' => '당신의 취향에 맞는 무거운 분위기의 영화입니다.']];
}

echo json_encode(['recommendations' => $recommendations]);
?>
