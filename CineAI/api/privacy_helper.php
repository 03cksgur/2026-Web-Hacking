<?php
/**
 * api/privacy_helper.php
 * Utility for Data De-identification (Masking)
 */

class PrivacyHelper {
    /**
     * Mask potential PII (Email, Phone, Names) from text
     */
    public static function maskPII($text) {
        // 1. Mask Emails
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', '[EMAIL_MASKED]', $text);
        
        // 2. Mask Phone Numbers (simple pattern)
        $text = preg_replace('/(\d{2,3})[- .](\d{3,4})[- .](\d{4})/', '$1-****-$3', $text);
        
        // 3. Mask potentially sensitive words (customizable)
        $sensitive_patterns = [
            '/주민등록번호/u',
            '/비밀번호/u',
            '/카드번호/u'
        ];
        foreach ($sensitive_patterns as $pattern) {
            $text = preg_replace($pattern, '[SENSITIVE_DATA_REMOVED]', $text);
        }

        return $text;
    }

    /**
     * Mask user metadata for AI recommendations
     */
    public static function maskUserContext($reviews) {
        $masked_reviews = [];
        foreach ($reviews as $review) {
            $masked_reviews[] = [
                'movie_title' => $review['movie_title'],
                'star_rating' => $review['star_rating'],
                'content' => self::maskPII($review['content'])
            ];
        }
        return $masked_reviews;
    }
}
?>
