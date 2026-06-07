<?php
// includes/moderation_engine.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/gemini.php';

class CineAIGuard {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Simple Rate Limiting (Session-based)
     */
    public function checkRateLimit() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $now = time();
        $last_post = $_SESSION['last_post_time'] ?? 0;
        $post_count = $_SESSION['post_count'] ?? 0;

        if ($now - $last_post < 60) {
            if ($post_count >= 5) {
                return false; // Rate limit exceeded
            }
            $_SESSION['post_count'] = $post_count + 1;
        } else {
            $_SESSION['last_post_time'] = $now;
            $_SESSION['post_count'] = 1;
        }
        return true;
    }

    /**
     * Main moderation process
     */
    public function moderate($type, $content, $poster_path = null) {
        $status = 'ACTIVE';
        $reason = null;

        // 1. Text Guard (Regex & PII)
        $textResult = $this->checkText($content);
        if ($textResult['status'] !== 'ACTIVE') {
            $status = $textResult['status'];
            $reasonRaw[] = $textResult['reason'];
        }

        // 2. Visual Guard (AI & OCR) - if image exists
        if ($poster_path && file_exists($poster_path)) {
            $visualResult = $this->checkVisual($poster_path);
            if ($visualResult['status'] !== 'ACTIVE') {
                // If text is already blocked, keep blocked. If visual is manual, set manual.
                if ($status !== 'BLOCKED') {
                    $status = $visualResult['status'];
                }
                $reasonRaw[] = $visualResult['reason'];
            }
        }

        $reason = isset($reasonRaw) ? implode(" | ", $reasonRaw) : null;

        return [
            'status' => $status,
            'reason' => $reason
        ];
    }

    /**
     * Text Guard: Regex bypass detection & PII detection
     */
    public function checkText($text) {
        // Fetch rules from DB
        $stmt = $this->pdo->query("SELECT pattern, rule_type, description FROM rules");
        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Built-in PII patterns (Phone, Account)
        $piiPatterns = [
            'phone' => '/\d{2,3}[-.\s]?\d{3,4}[-.\s]?\d{4}/',
            'account' => '/\d{3,6}-\d{2,6}-\d{3,6}/'
        ];

        // 1. Check PII
        foreach ($piiPatterns as $type => $pattern) {
            if (preg_match($pattern, $text)) {
                return ['status' => 'BLOCKED', 'reason' => "개인정보 감지 ($type)"];
            }
        }

        // 2. Check Custom Rules (Regex)
        foreach ($rules as $rule) {
            // Handle bitmask/bypass (e.g. 비.속.어 -> 비속어)
            // Simple logic: remove dots/spaces and check
            $cleanText = str_replace(['.', ' ', '-', '_'], '', $text);
            if (preg_match($rule['pattern'], $text) || preg_match($rule['pattern'], $cleanText)) {
                return [
                    'status' => ($rule['rule_type'] === 'BLOCK' ? 'BLOCKED' : 'MANUAL_REVIEW'),
                    'reason' => $rule['description'] ?? '금칙어 감지'
                ];
            }
        }

        return ['status' => 'ACTIVE', 'reason' => null];
    }

    /**
     * Visual Guard: Image Safety & OCR
     */
    public function checkVisual($imagePath) {
        // In a real app, this would call AWS Rekognition or Google Vision.
        // For CineAI, we use Gemini as a fail-safe / fallback.
        
        // Mocking AI response if Gemini Key is missing or for demonstration
        // For now, let's implement a 'Fail-Safe' logic: default to MANUAL_REVIEW if processing fails
        try {
            // Simple logic for OCR spam detection (simulated)
            // If the filename contains 'spam', block it.
            if (strpos(strtolower(basename($imagePath)), 'spam') !== false) {
                return ['status' => 'BLOCKED', 'reason' => '이미지 내 스팸 요소 감지 (OCR)'];
            }

            // Real AI check would happen here. 
            // We'll simulate 'Uncertain' cases defaulting to MANUAL_REVIEW.
            return ['status' => 'ACTIVE', 'reason' => null];
        } catch (Exception $e) {
            // Fail-Safe: Default to MANUAL_REVIEW on error
            return ['status' => 'MANUAL_REVIEW', 'reason' => '검역 API 장애 (Fail-Safe)'];
        }
    }

    /**
     * Audit Log Helper
     */
    public function logAction($adminId, $action, $targetType, $targetId, $reason) {
        $stmt = $this->pdo->prepare("INSERT INTO audit_logs (admin_id, action, target_type, target_id, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$adminId, $action, $targetType, $targetId, $reason]);
    }
}
?>
