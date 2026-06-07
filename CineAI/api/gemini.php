<?php
function callGeminiAPI($prompt, $expectedJson = false) {
    $apiKeyFile = __DIR__ . '/../config/gemini_key.txt';
    $apiKey = file_exists($apiKeyFile) ? trim(file_get_contents($apiKeyFile)) : '';

    if (empty($apiKey)) return null;

    // List of models to try in order of preference
    $models = [
        'gemini-2.5-flash',
        'gemini-2.5-flash-lite',
        'gemini-2.0-flash',
        'gemini-2.0-flash-lite'
    ];

    $lastError = "";

    foreach ($models as $model) {
        $url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key=" . $apiKey;
        $data = ["contents" => [["parts" => [["text" => $prompt]]]]];

        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];

        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);

        if ($result !== FALSE) {
            $response = json_decode($result, true);
            if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                $rawText = trim($response['candidates'][0]['content']['parts'][0]['text']);
                if ($expectedJson) {
                    $cleaned = str_replace(['```json', '```'], '', $rawText);
                    $decoded = json_decode(trim($cleaned), true);
                    if ($decoded) return $decoded;
                } else {
                    return $rawText;
                }
            }
            
            if (isset($response['error'])) {
                $lastError = $response['error']['message'] ?? "Unknown API Error";
                // If the error isn't about demand/quota, it might be a prompt issue, but we try next model anyway
                continue; 
            }
        }
    }
    return null;
}

function analyzeSentimentWithGemini($text) {
    $fallback = ['sentiment_score' => 0.5, 'summary' => htmlspecialchars(mb_substr($text, 0, 50)) . '...', 'is_spoiler' => 0];
    
    $prompt = "다음 영화 리뷰 텍스트를 분석하여 오직 아래의 JSON 형식으로만 응답해. 모든 문장은 친절한 존댓말(~해요, ~합니다)을 사용해. 추가적인 인사말이나 마크다운 백틱(```) 기호 없이 순순 JSON 문자열만 출력해.\n" .
              "{ \"sentiment_score\": -1.0 부터 1.0 사이의 실수 하나, \"summary\": \"리뷰의 내용을 3문장으로 간결하게 요약한 텍스트\", \"is_spoiler\": 핵심 반전이나 결말, 스포일러가 포함되어 있다면 true, 아니면 false }\n\n" .
              "리뷰: " . $text;
    
    $result = callGeminiAPI($prompt, true);
    
    if ($result) {
        return [
            'sentiment_score' => isset($result['sentiment_score']) ? max(-1.0, min(1.0, (float)$result['sentiment_score'])) : 0.5,
            'summary' => $result['summary'] ?? $fallback['summary'],
            'is_spoiler' => !empty($result['is_spoiler']) ? 1 : 0
        ];
    }
    
    // Static fallback if API fails
    if (strpos($text, '최악') !== false) $fallback['sentiment_score'] = -0.7;
    if (strpos($text, '최고') !== false) $fallback['sentiment_score'] = 0.8;
    return $fallback;
}

function recommendMoviesWithGemini($positive_titles) {
    if (empty($positive_titles)) return ["영화를 평가해 주시면 취향에 맞는 영화를 추천해 드립니다!"];
    $titles_str = implode(", ", $positive_titles);
    $prompt = "사용자가 다음 영화들을 긍정적으로 평가했습니다: $titles_str.\n이 사용자가 좋아할 만한 새로운 영화 딱 3가지를 추천해주고 그 이유를 짧게 한국어 존댓말(~합니다)로 설명해. 결과는 리스트 형태로 텍스트만 출력해.";
    
    $result = callGeminiAPI($prompt);
    return $result ? array_filter(explode("\n", $result)) : ["현재 추천 시스템이 붐비고 있습니다. 잠시 후 다시 시도해주세요."];
}

function callGemini($prompt) {
    $contextPrompt = "당신은 Cine AI 영화 보조원입니다. 모든 답변은 매우 정중하고 친절한 한국어 존댓말(~해요, ~합니다)로 작성하세요. 반말은 절대로 사용하지 마세요.\n질문: " . $prompt;
    $result = callGeminiAPI($contextPrompt);
    return $result ?? "Cine AI: 현재 AI 서버가 매우 바쁩니다. 잠시 후 다시 질문해주시면 친절히 답변해드리겠습니다! 📽️";
}
?>
