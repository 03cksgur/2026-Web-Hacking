<?php
// includes/csrf_helper.php

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function get_csrf_input() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF token for POST requests
 * Dies with a security message if validation fails.
 */
function validate_csrf_or_die() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verify_csrf_token($token)) {
            // A09: Logging Failure
            $pdo = $GLOBALS['pdo'];
            $ip = $_SERVER['REMOTE_ADDR'];
            $msg = "CSRF 검증 실패 (IP: $ip, Page: " . $_SERVER['PHP_SELF'] . ")";
            $stmt = $pdo->prepare("INSERT INTO security_logs (event_type, description, ip_address) VALUES ('CSRF_FAILURE', ?, ?)");
            $stmt->execute([$msg, $ip]);
            
            error_log($msg);
            http_response_code(403);
            die("보안 경고: 유효하지 않은 요청 세션입니다. (CSRF Validation Failed)");
        }
    }
}
?>
