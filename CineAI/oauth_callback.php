<?php
require_once __DIR__ . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$provider = $_GET['provider'] ?? '';
$code = $_GET['code'] ?? '';

if (!$provider || !$code) {
    header("Location: login.php");
    exit;
}

// MOCK: Generate a fake user payload based on provider
// In a real app, you would exchange $code for an access token, then fetch user profile using cURL.
$mock_id = $provider . '_' . rand(10000, 99999);
$mock_email = $mock_id . "@" . $provider . ".com";
$mock_name = ucfirst($provider) . " 유저";

// Check if user exists in DB with this social_id
$stmt = $pdo->prepare("SELECT * FROM users WHERE social_provider = ? AND social_id = ?");
$stmt->execute([$provider, $mock_id]);
$user = $stmt->fetch();

if (!$user) {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$mock_email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Link to existing account
        $pdo->prepare("UPDATE users SET social_provider = ?, social_id = ? WHERE id = ?")
            ->execute([$provider, $mock_id, $existing['id']]);
        $user = $existing;
    } else {
        // Create new account
        $pw_hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT); // Dummy password
        $login_id = "social_" . uniqid(); // Mock login_id
        $insert = $pdo->prepare("INSERT INTO users (login_id, email, username, password_hash, role, social_provider, social_id) VALUES (?, ?, ?, ?, 'User', ?, ?)");
        $insert->execute([$login_id, $mock_email, $mock_name, $pw_hash, $provider, $mock_id]);
        
        $new_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$new_id]);
        $user = $stmt->fetch();
    }
}

// Log in
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];

header("Location: index.php");
exit;
?>
