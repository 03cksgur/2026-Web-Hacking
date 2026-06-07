<?php
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = $_GET['token'] ?? '';
$error = '';
$user_id = null;

if (!$token) {
    header("Location: login.php");
    exit;
}

// Validate token
$stmt = $pdo->prepare("SELECT id, reset_token_expiry FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user || $user['reset_token_expiry'] < time()) {
    $error = "유효하지 않거나 만료된 토큰입니다.";
} else {
    $user_id = $user['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id) {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($password) || $password !== $password_confirm) {
        $error = "비밀번호가 일치하지 않습니다.";
    } elseif (strlen($password) < 8 || (preg_match("/[a-zA-Z]/", $password) + preg_match("/[0-9]/", $password) + preg_match("/[\W_]/", $password)) < 2) {
        $error = "비밀번호는 8자 이상, 영문/숫자/특수문자 중 2가지 이상 조합해야 합니다.";
    } else {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $update = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
        if ($update->execute([$password_hash, $user_id])) {
            header("Location: login.php?reset=1");
            exit;
        } else {
            $error = "비밀번호 변경에 실패했습니다.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 500px; margin: 0 auto; margin-top: 50px;">
    <div class="glass-card">
        <h2 style="text-align: center; margin-bottom: 30px;">새 비밀번호 설정</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="find_account.php" class="btn btn-outline">다시 시도하기</a>
            </div>
        <?php else: ?>
            <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>">
                <div class="form-group">
                    <label for="password">새 비밀번호</label>
                    <input type="password" id="password" name="password" class="form-control" required pattern="^(?:(?=.*[a-zA-Z])(?=.*\d)|(?=.*[a-zA-Z])(?=.*[\W_])|(?=.*\d)(?=.*[\W_])).{8,}$" title="비밀번호는 8자 이상, 영문/숫자/특수문자 중 2가지 이상 조합해야 합니다.">
                    <small style="color: var(--text-muted); font-size: 0.8rem; display: block; margin-top: 5px;">* 최소 8자리 이상, 영문/숫자/특수문자 중 2가지 이상 조합 필수</small>
                </div>
                <div class="form-group">
                    <label for="password_confirm">비밀번호 확인</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">비밀번호 변경</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
