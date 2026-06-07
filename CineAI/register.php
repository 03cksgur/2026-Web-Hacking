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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die(); // A01 CSRF Protection
    $login_id = trim($_POST['login_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($login_id) || empty($email) || empty($username) || empty($password)) {
        $error = "모든 항목을 입력해주세요.";
    } elseif ($password !== $password_confirm) {
        $error = "비밀번호가 일치하지 않습니다.";
    } elseif (strlen($password) < 8 || (preg_match("/[a-zA-Z]/", $password) + preg_match("/[0-9]/", $password) + preg_match("/[\W_]/", $password)) < 2) {
        $error = "비밀번호는 8자 이상, 영문/숫자/특수문자 중 2가지 이상 조합해야 합니다.";
    } else {
        // Check if exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE login_id = ? OR email = ?");
        $stmt->execute([$login_id, $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "이미 존재하는 아이디 또는 이메일입니다.";
        } else {
            // Include Bcrypt hashing
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            require_once __DIR__ . '/includes/mailer.php';
            
            // Default role is User, but make first user Admin just for convenience or just keep User
            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
            $role = ($stmt->fetchColumn() == 0) ? 'Admin' : 'User';
            $status = ($role == 'Admin') ? 'ACTIVE' : 'UNVERIFIED';

            $token = bin2hex(random_bytes(32));

            $insert = $pdo->prepare("INSERT INTO users (login_id, email, username, password_hash, role, status, email_verification_token) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($insert->execute([$login_id, $email, $username, $password_hash, $role, $status, $token])) {
                if ($status == 'UNVERIFIED') {
                    sendVerificationEmail($email, $token);
                    header("Location: login.php?unverified=1");
                } else {
                    header("Location: login.php?registered=1");
                }
                exit;
            } else {
                $error = "회원가입 실패. 다시 시도해주세요.";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 500px; margin: 0 auto; margin-top: 50px;">
    <div class="glass-card">
        <h2 style="text-align: center; margin-bottom: 30px;">회원가입</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <?php echo get_csrf_input(); ?>
            <div class="form-group">
                <label for="login_id">아이디</label>
                <input type="text" id="login_id" name="login_id" class="form-control" required value="<?php echo htmlspecialchars($_POST['login_id'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="email">이메일</label>
                <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="username">닉네임</label>
                <input type="text" id="username" name="username" class="form-control" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">비밀번호</label>
                <input type="password" id="password" name="password" class="form-control" required pattern="^(?:(?=.*[a-zA-Z])(?=.*\d)|(?=.*[a-zA-Z])(?=.*[\W_])|(?=.*\d)(?=.*[\W_])).{8,}$" title="비밀번호는 8자 이상, 영문/숫자/특수문자 중 2가지 이상 조합해야 합니다.">
                <div class="pwd-validator">
                    <div class="pwd-validator-item" id="req-length">최소 8자리 이상</div>
                    <div class="pwd-validator-item" id="req-combo">영문/숫자/특수문자 중 2가지 이상 조합</div>
                </div>
                <script>
                    document.getElementById('password').addEventListener('input', function() {
                        const val = this.value;
                        const lenNode = document.getElementById('req-length');
                        const comboNode = document.getElementById('req-combo');
                        
                        // Check length
                        if (val.length >= 8) lenNode.classList.add('valid');
                        else lenNode.classList.remove('valid');

                        // Check combination
                        let typeCount = 0;
                        if (/[a-zA-Z]/.test(val)) typeCount++;
                        if (/[0-9]/.test(val)) typeCount++;
                        if (/[\W_]/.test(val)) typeCount++;
                        
                        if (typeCount >= 2) comboNode.classList.add('valid');
                        else comboNode.classList.remove('valid');
                    });
                </script>
            </div>
            <div class="form-group">
                <label for="password_confirm">비밀번호 확인</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">가입하기</button>
        </form>
        
        <!-- Social Login Buttons -->
        <div style="margin-top: 25px; border-top: 1px solid var(--glass-border); padding-top: 20px;">
            <p style="text-align: center; color: var(--text-muted); font-size: 0.9rem; margin-bottom: 15px;">또는 SNS 계정으로 간편 가입</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <a href="oauth_redirect.php?provider=kakao" class="btn" style="flex:1; background: #FEE500; color: #000000; font-weight: bold; border-radius: 8px;">카카오</a>
                <a href="oauth_redirect.php?provider=naver" class="btn" style="flex:1; background: #03C75A; color: #ffffff; font-weight: bold; border-radius: 8px;">네이버</a>
                <a href="oauth_redirect.php?provider=google" class="btn btn-outline" style="flex:1; border-color: #ddd; color: var(--text-main); font-weight: bold; border-radius: 8px; background: rgba(255,255,255,0.1);">구글</a>
            </div>
        </div>

        <p style="text-align: center; margin-top: 20px;">
            이미 계정이 있으신가요? <a href="login.php">로그인</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
