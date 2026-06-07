<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, login_id, profile_pic, notif_enabled FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<div style="max-width: 900px; margin: 40px auto; padding: 0 20px;">
    <h1 style="margin-bottom: 30px;">⚙️ 계정 설정</h1>

    <div style="display: grid; grid-template-columns: 250px 1fr; gap: 30px;">
        <!-- Sidebar Tabs -->
        <div class="glass-card" style="padding: 10px; height: fit-content;">
            <button class="tab-btn active" onclick="showTab('profile')">👤 프로필 수정</button>
            <button class="tab-btn" onclick="showTab('security')">🔒 보안 및 비밀번호</button>
            <button class="tab-btn" onclick="showTab('notif')">🔔 알림 설정</button>
            <button class="tab-btn" onclick="showTab('danger')" style="color: #ef4444;">⚠️ 계정 탈퇴</button>
        </div>

        <!-- Tab Contents -->
        <div class="glass-card" style="padding: 30px;">
            <!-- Profile Tab -->
            <div id="tab-profile" class="tab-content">
                <h3 style="margin-bottom: 25px;">프로필 수정</h3>
                
                <div style="display: flex; gap: 30px; align-items: center; margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                    <div style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; background: var(--primary-color); border: 2px solid var(--glass-border);">
                        <?php if ($user['profile_pic']): ?>
                            <img src="profile_download.php?file=<?php echo urlencode($user['profile_pic']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 2rem;">👤</div>
                        <?php endif; ?>
                    </div>
                    <form action="profile_upload.php" method="POST" enctype="multipart/form-data">
                        <input type="file" name="profile_pic" accept="image/*" style="margin-bottom: 10px; display: block; font-size: 0.8rem;">
                        <button type="submit" class="btn btn-outline" style="font-size: 0.8rem; padding: 5px 15px;">사진 변경</button>
                    </form>
                </div>

                <div class="form-group">
                    <label>아이디 (Login ID)</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['login_id']); ?>" disabled style="background: rgba(0,0,0,0.2); cursor: not-allowed;">
                    <small style="color: var(--text-muted);">아이디는 변경할 수 없습니다.</small>
                </div>
                <div class="form-group">
                    <label>닉네임 (Username)</label>
                    <input type="text" id="nickname" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>">
                </div>
                <button onclick="updateSettings('profile')" class="btn btn-primary" style="margin-top: 20px;">저장하기</button>
            </div>

            <!-- Security Tab -->
            <div id="tab-security" class="tab-content" style="display: none;">
                <h3 style="margin-bottom: 25px;">비밀번호 변경</h3>
                <div class="form-group">
                    <label>현재 비밀번호</label>
                    <input type="password" id="cur-pw" class="form-control">
                </div>
                <div class="form-group">
                    <label>새 비밀번호</label>
                    <input type="password" id="new-pw" class="form-control" pattern="(?=.*[a-zA-Z])(?=.*\d)(?=.*[\W_]).{8,}" title="최소 8자리 이상, 영문+숫자+특수문자 조합 필수">
                    <small style="color: var(--text-muted); font-size: 0.8rem;">* 최소 8자리 이상, 영문+숫자+특수문자 포함</small>
                </div>
                <div class="form-group">
                    <label>새 비밀번호 확인</label>
                    <input type="password" id="confirm-pw" class="form-control">
                </div>
                <button onclick="updateSettings('password')" class="btn btn-primary" style="margin-top: 20px;">비밀번호 변경</button>
            </div>

            <!-- Notif Tab -->
            <div id="tab-notif" class="tab-content" style="display: none;">
                <h3 style="margin-bottom: 25px;">알림 설정</h3>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                    <div>
                        <strong>실시간 알림</strong>
                        <p style="font-size: 0.8rem; color: var(--text-muted);">댓글, 좋아요 알림을 받습니다.</p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" id="notif-toggle" <?php echo $user['notif_enabled'] ? 'checked' : ''; ?> onchange="updateSettings('notif')">
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>

            <!-- Danger Tab -->
            <div id="tab-danger" class="tab-content" style="display: none;">
                <h3 style="margin-bottom: 25px; color: #ef4444;">계정 탈퇴</h3>
                <p style="margin-bottom: 20px; line-height: 1.6;">탈퇴 시 작성한 리뷰와 댓글은 유지되지만, 본인 확인이 불가능하게 처리됩니다. 이 작업은 되돌릴 수 없습니다.</p>
                <button onclick="confirmDelete()" class="btn" style="background: #ef4444; color: #fff;">회원 탈퇴</button>
            </div>
        </div>
    </div>
</div>

<style>
.tab-btn { width: 100%; padding: 12px 15px; text-align: left; background: none; border: none; color: var(--text-main); cursor: pointer; border-radius: 6px; transition: 0.3s; margin-bottom: 5px; font-weight: 500; }
.tab-btn:hover { background: rgba(255,255,255,0.05); }
.tab-btn.active { background: var(--primary-color); color: #fff; }

.switch { position: relative; display: inline-block; width: 50px; height: 26px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #555; transition: .4s; border-radius: 34px; }
.slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
input:checked + .slider { background-color: var(--primary-color); }
input:checked + .slider:before { transform: translateX(24px); }
</style>

<script>
function showTab(id) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.getElementById('tab-' + id).style.display = 'block';
    
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    event.currentTarget.classList.add('active');
}

async function updateSettings(type) {
    let payload = { type: type };
    if (type === 'profile') {
        payload.nickname = document.getElementById('nickname').value;
    } else if (type === 'password') {
        payload.current_password = document.getElementById('cur-pw').value;
        payload.new_password = document.getElementById('new-pw').value;
        payload.confirm_password = document.getElementById('confirm-pw').value;
    } else if (type === 'notif') {
        // Toggle action was handled separately in previous step, but let's integrate here
        payload.notif_enabled = document.getElementById('notif-toggle').checked ? 1 : 0;
    }

    try {
        const res = await fetch('api/settings_action.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            alert(data.success);
            if (type === 'profile') location.reload();
            if (type === 'password') {
                document.getElementById('cur-pw').value = '';
                document.getElementById('new-pw').value = '';
                document.getElementById('confirm-pw').value = '';
            }
        } else {
            alert(data.error);
        }
    } catch(e) { alert("서버 오류가 발생했습니다."); }
}

function confirmDelete() {
    if (confirm("정말로 탈퇴하시겠습니까? 모든 정보가 사라집니다.")) {
        updateSettings('delete').then(() => location.href = 'index.php');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
