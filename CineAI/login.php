<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = isset($_GET['registered']) ? '회원가입이 완료되었습니다. 로그인해주세요.' : '';
$success .= isset($_GET['pending']) ? '회원가입 신청이 완료되었습니다. 관리자의 승인 후 로그인할 수 있습니다.' : '';
$success .= isset($_GET['unverified']) ? '회원가입이 진행 중입니다. 이메일로 발송된 인증 링크를 확인해주세요.' : '';
$success .= isset($_GET['reset']) ? '비밀번호가 재설정되었습니다. 로그인해주세요.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die(); // A01 CSRF Protection
    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login_id) || empty($password)) {
        $error = "아이디와 비밀번호를 입력해주세요.";
    } else {
        // A07: Brute Force Protection (MySQL compatible)
        $ip = $_SERVER['REMOTE_ADDR'];
        // Get the 5 most recent attempts in the last 15 minutes
        $stmt_check = $pdo->prepare("SELECT COUNT(*), MIN(attempted_at) FROM (SELECT attempted_at FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE) ORDER BY attempted_at DESC LIMIT 5) as recent_attempts");
        $stmt_check->execute([$ip]);
        $row = $stmt_check->fetch();
        $attempt_count = $row ? $row[0] : 0;
        
        if ($attempt_count >= 5) {
            $earliest_attempt = $row[1];
            // Calculate remaining time (approximate)
            // Use time() comparison or database comparison
            $earliest_ts = strtotime($earliest_attempt . ' UTC');
            $expires_ts = $earliest_ts + (15 * 60);
            $remaining = $expires_ts - time();
            $mins = max(1, ceil($remaining / 60));
            $error = "너무 많은 로그인 시도가 감짐되었습니다. 약 {$mins}분 후 다시 시도해주세요.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE login_id = ?");
            $stmt->execute([$login_id]);
            $user = $stmt->fetch();
    
            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['status'] !== 'ACTIVE') {
                    if ($user['status'] === 'PENDING') {
                        $error = "가입 승인 대기 중입니다. 관리자의 승인 후 사용 가능합니다.";
                    } elseif ($user['status'] === 'UNVERIFIED') {
                        $error = "이메일 인증이 완료되지 않았습니다. 메일함의 인증 링크를 클릭해주세요.";
                    } else {
                        $error = "계정이 비활성화되었거나 차단되었습니다. 관리자에게 문의하세요.";
                    }
                } else {
                    // Login success
                    session_regenerate_id(true); 
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    header("Location: index.php");
                    exit;
                }
            } else {
                // A07: Record failed attempt
                $stmt_log = $pdo->prepare("INSERT INTO login_attempts (ip_address, login_id) VALUES (?, ?)");
                $stmt_log->execute([$ip, $login_id]);
                
                $error = "아이디 또는 비밀번호가 올바르지 않습니다.";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 500px; margin: 0 auto; margin-top: 50px;">
    <div class="glass-card">
        <h2 style="text-align: center; margin-bottom: 30px;">로그인</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <?php echo get_csrf_input(); ?>
            <div class="form-group">
                <label for="login_id">아이디</label>
                <input type="text" id="login_id" name="login_id" class="form-control" required value="<?php echo htmlspecialchars($_POST['login_id'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">비밀번호</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">로그인</button>
        </form>
        
        <!-- Social Login Buttons -->
        <div style="margin-top: 25px; border-top: 1px solid var(--glass-border); padding-top: 20px;">
            <p style="text-align: center; color: var(--text-muted); font-size: 0.9rem; margin-bottom: 15px;">또는 SNS 계정으로 간편 로그인</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <a href="oauth_redirect.php?provider=kakao" class="btn" style="flex:1; background: #FEE500; color: #000000; font-weight: bold; border-radius: 8px;">카카오</a>
                <a href="oauth_redirect.php?provider=naver" class="btn" style="flex:1; background: #03C75A; color: #ffffff; font-weight: bold; border-radius: 8px;">네이버</a>
                <a href="oauth_redirect.php?provider=google" class="btn btn-outline" style="flex:1; border-color: #ddd; color: var(--text-main); font-weight: bold; border-radius: 8px; background: rgba(255,255,255,0.1);">구글</a>
            </div>
        </div>

        <p style="text-align: center; margin-top: 20px;">
            계정이 없으신가요? <a href="register.php">회원가입</a><br><br>
            <a href="find_account.php" style="color: var(--text-muted);">아이디/비밀번호 찾기</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
