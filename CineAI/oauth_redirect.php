<?php
require_once __DIR__ . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$provider = $_GET['provider'] ?? '';
// In a real application, this file builds the authorization URL using API keys from config/secrets.php
// and redirects the user to Kakao/Google/Naver.
// For this live demo, we generate a mock token and redirect directly to the callback to simulate success.

header("Location: oauth_callback.php?provider=" . urlencode($provider) . "&code=MOCK_AUTH_CODE_" . rand(1000, 9999));
exit;
?>
