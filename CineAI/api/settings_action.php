<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Login required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? '';

if ($type === 'profile') {
    $nickname = trim($input['nickname'] ?? '');
    if (mb_strlen($nickname) < 2) {
        echo json_encode(['error' => '닉네임은 최소 2자 이상이어야 합니다.']);
        exit;
    }
    $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
    $stmt->execute([$nickname, $user_id]);
    $_SESSION['username'] = $nickname;
    echo json_encode(['success' => '프로필이 업데이트되었습니다.']);
} 
elseif ($type === 'password') {
    $current = $input['current_password'] ?? '';
    $new = $input['new_password'] ?? '';
    $confirm = $input['confirm_password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!password_verify($current, $user['password_hash'])) {
        echo json_encode(['error' => '현재 비밀번호가 일치하지 않습니다.']);
        exit;
    }

    if ($new !== $confirm) {
        echo json_encode(['error' => '새 비밀번호와 비밀번호 확인이 일치하지 않습니다.']);
        exit;
    }

    if (strlen($new) < 8 || !preg_match("/[a-zA-Z]/", $new) || !preg_match("/[0-9]/", $new) || !preg_match("/[\W_]/", $new)) {
        echo json_encode(['error' => '새 비밀번호는 8자 이상이며, 영문자, 숫자, 특수문자를 최소 1개씩 포함해야 합니다.']);
        exit;
    }

    $hashed = password_hash($new, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$hashed, $user_id]);
    echo json_encode(['success' => '비밀번호가 안전하게 변경되었습니다.']);
}
elseif ($type === 'delete') {
    // Soft delete or anonymize
    $stmt = $pdo->prepare("UPDATE users SET deleted_at = NOW(), username = '탈퇴한 사용자' WHERE id = ?");
    $stmt->execute([$user_id]);
    session_destroy();
    echo json_encode(['success' => '탈퇴 처리가 완료되었습니다.']);
}
?>
