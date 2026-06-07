<?php
// verify_email.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$token = $_GET['token'] ?? '';
$message = '';
$is_success = false;

if ($token) {
    $stmt = $pdo->prepare("SELECT id, status FROM users WHERE email_verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['status'] === 'UNVERIFIED') {
            // Update to PENDING (Awaiting Admin)
            $update = $pdo->prepare("UPDATE users SET status = 'PENDING', email_verification_token = NULL WHERE id = ?");
            if ($update->execute([$user['id']])) {
                $is_success = true;
                $message = "이메일 인증이 완료되었습니다! 관리자 승인 후 로그인할 수 있습니다.";
            } else {
                $message = "인증 처리 중 오류가 발생했습니다.";
            }
        } else {
            $message = "이미 인증되었거나 만료된 링크입니다.";
        }
    } else {
        $message = "유효하지 않은 인증 링크입니다.";
    }
} else {
    $message = "잘못된 접근입니다.";
}
?>

<div style="max-width: 500px; margin: 0 auto; margin-top: 50px; text-align: center;">
    <div class="glass-card">
        <h2 style="margin-bottom: 20px;">📧 이메일 인증</h2>
        <?php if ($is_success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <a href="login.php" class="btn btn-primary" style="margin-top:20px;">로그인 페이지로</a>
        <?php else: ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($message); ?></div>
            <a href="index.php" class="btn btn-outline" style="margin-top:20px;">홈으로 돌아가기</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
