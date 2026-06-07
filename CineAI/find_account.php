<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$msg_id = '';
$msg_pw = '';
$reset_link = '';

// Find ID Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'find_id') {
    $email = trim($_POST['email'] ?? '');
    if ($email) {
        $stmt = $pdo->prepare("SELECT login_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $msg_id = "회원님의 아이디는 <strong>" . htmlspecialchars($user['login_id']) . "</strong> 입니다.";
        } else {
            $msg_id = "해당 이메일로 가입된 계정이 없습니다.";
        }
    }
}

// Reset PW Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_pw') {
    $login_id = trim($_POST['login_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($login_id && $email) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login_id = ? AND email = ?");
        $stmt->execute([$login_id, $email]);
        $user = $stmt->fetch();
        if ($user) {
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (60 * 60); // 1 hour

            $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
            $update->execute([$token, $expiry, $user['id']]);

            // For testing purposes, we display it on screen instead of actual email
            $msg_pw = "비밀번호 재설정 링크가 생성되었습니다. (실제 환경에서는 이메일 발송)";
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $reset_link = "{$protocol}://{$host}{$uri}/reset_password.php?token={$token}";
        } else {
            $msg_pw = "입력하신 정보와 일치하는 계정이 없습니다.";
        }
    }
}
?>

<div style="max-width: 800px; margin: 0 auto; margin-top: 50px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
    
    <!-- Find ID -->
    <div class="glass-card">
        <h2 style="text-align: center; margin-bottom: 20px;">아이디 찾기</h2>
        <?php if ($msg_id): ?>
            <div class="alert alert-success"><?php echo $msg_id; ?></div>
        <?php endif; ?>
        <form method="POST" action="find_account.php">
            <input type="hidden" name="action" value="find_id">
            <div class="form-group">
                <label for="find_email">가입한 이메일</label>
                <input type="email" id="find_email" name="email" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-outline" style="width: 100%; margin-top: 10px;">아이디 찾기</button>
        </form>
    </div>

    <!-- Reset Password -->
    <div class="glass-card">
        <h2 style="text-align: center; margin-bottom: 20px;">비밀번호 재설정</h2>
        <?php if ($msg_pw): ?>
            <div class="alert alert-success"><?php echo $msg_pw; ?></div>
        <?php endif; ?>
        <?php if ($reset_link): ?>
            <div class="alert" style="background: rgba(76, 201, 240, 0.2); border-left-color: #4CC9F0; word-break: break-all;">
                <a href="<?php echo htmlspecialchars($reset_link); ?>"><?php echo htmlspecialchars($reset_link); ?></a>
            </div>
        <?php endif; ?>
        <form method="POST" action="find_account.php">
            <input type="hidden" name="action" value="reset_pw">
            <div class="form-group">
                <label for="reset_id">아이디</label>
                <input type="text" id="reset_id" name="login_id" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="reset_email">이메일</label>
                <input type="email" id="reset_email" name="email" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">재설정 링크 생성</button>
        </form>
    </div>

</div>

<div style="text-align: center; margin-top: 30px;">
    <a href="login.php" class="btn btn-outline">로그인으로 돌아가기</a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
