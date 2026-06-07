<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/gemini.php';
require_once __DIR__ . '/../api/privacy_helper.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$user_query = $input['query'] ?? '';

if (empty($user_query)) {
    echo json_encode(['error' => 'Query is empty']);
    exit;
}

// De-identify user query
$masked_query = PrivacyHelper::maskPII($user_query);

$prompt = "You are CineAI, an expert movie recommendation chatbot. 
Answer the user query in a helpful and friendly tone (in Korean).
If you recommend any specific movies, please put the movie titles in double brackets like this: [[Movie Title]]. 
Keep your response concise but engaging.

User Query: $masked_query";

$response = callGemini($prompt);

// Find titles in brackets and replace with image + title
$final_response = $response;
if (preg_match_all('/\[\[(.*?)\]\]/', $response, $matches)) {
    // Read the mock movies config directly
    $movies = require __DIR__ . '/../config/mock_movies.php';

    foreach ($matches[1] as $index => $title) {
        $found_movie = null;
        foreach ($movies as $movie) {
            // Check if title matches (partial or exact)
            if (stripos($movie['title'], $title) !== false || stripos($title, $movie['title']) !== false) {
                $found_movie = $movie;
                break;
            }
        }

        if ($found_movie && isset($found_movie['poster_path'])) {
            $img_url = "api/tmdb_image_proxy.php?size=w92&path=" . urlencode($found_movie['poster_path']);
            // Add a small styled card for the movie in the chat
            $replacement = "<div style='margin:10px 0; display:flex; align-items:center; gap:12px; background:rgba(255,255,255,0.05); padding:10px; border-radius:10px; border:1px solid var(--glass-border);'>
                <img src='{$img_url}' style='width:50px; height:75px; object-fit:cover; border-radius:6px; box-shadow:0 2px 8px rgba(0,0,0,0.3);'>
                <div style='display:flex; flex-direction:column; gap:4px;'>
                    <div style='font-weight:bold; color:var(--secondary-color); font-size:0.95rem;'>{$found_movie['title']}</div>
                    <div style='font-size:0.8rem; color:var(--text-muted);'>추천된 영화 포스터</div>
                </div>
            </div>";
            $final_response = str_replace($matches[0][$index], $replacement, $final_response);
        } else {
            // Just show the title if no poster found
            $final_response = str_replace($matches[0][$index], "<strong style='color:var(--secondary-color);'>$title</strong>", $final_response);
        }
    }
}

echo json_encode(['response' => $final_response]);
?>
