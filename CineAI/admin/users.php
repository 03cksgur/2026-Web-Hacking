<?php
// admin/users.php
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/moderation_engine.php';

$guard = new CineAIGuard($pdo);

// Action Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    
    if ($user_id && $_SESSION['user_id'] != $user_id) { // Prevent self-action for safety
        if ($action === 'toggle_role') {
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $current_role = $stmt->fetchColumn();
            $new_role = ($current_role === 'Admin') ? 'User' : 'Admin';
            
            $update = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $update->execute([$new_role, $user_id]);
            $guard->logAction($_SESSION['user_id'], 'CHANGE_ROLE', 'user', $user_id, "Role changed to $new_role");
        } elseif ($action === 'delete') {
            // Soft delete user could be complex, for now let's just delete or ban
            // Actually user asked for "Account Suspend" or "Bulk Delete"
            // Let's implement Delete (cascades to reviews/comments)
            $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $del->execute([$user_id]);
            $guard->logAction($_SESSION['user_id'], 'DELETE_USER', 'user', $user_id, "User deleted from system");
        }
        header("Location: users.php?msg=success");
        exit;
    }
}

// Fetch Users
$users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
    <div>
        <h1 style="font-size: 2.2rem; font-weight: 800; margin-bottom: 5px;">👥 사용자 관리</h1>
        <p style="color: var(--text-muted);">사이트의 회원을 관리하고 권한을 변경합니다.</p>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success" style="border-radius: 8px;">처리가 완료되었습니다.</div>
<?php endif; ?>

<div class="glass-card" style="padding: 0 !important; overflow-x: auto;">
    <div style="min-width: 900px;">
        <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>아이디</th>
                <th>이름</th>
                <th>이메일</th>
                <th>권한</th>
                <th>액션</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr style="<?php echo ($_SESSION['user_id'] == $user['id']) ? 'background: rgba(44, 206, 219, 0.1);' : ''; ?>">
                    <td><?php echo $user['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($user['login_id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td style="color: var(--text-muted);"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <span class="sentiment-badge <?php echo ($user['role'] === 'Admin') ? 'sentiment-positive' : (($user['role'] === 'Sub-Admin') ? 'sentiment-neutral' : ''); ?>" style="border-radius: 4px;">
                            <?php 
                            if ($user['role'] === 'Admin') echo '관리자';
                            elseif ($user['role'] === 'Sub-Admin') echo '준관리자';
                            elseif ($user['role'] === 'User') echo '일반유저';
                            else echo htmlspecialchars($user['role']);
                            ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($_SESSION['user_id'] != $user['id']): ?>
                            <form method="POST" style="display: flex; gap: 5px;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="action" value="toggle_role" class="btn btn-outline" style="padding: 4px 10px; font-size: 0.8rem; border-radius: 4px;">권한변경</button>
                                <button type="submit" name="action" value="delete" class="btn btn-danger" style="padding: 4px 10px; font-size: 0.8rem; border-radius: 4px;" onclick="return confirm('정말 삭제하시겠습니까? 관련 모든 게시물과 댓글이 삭제됩니다.');">삭제</button>
                            </form>
                        <?php else: ?>
                            <span style="font-size: 0.85rem; color: #4cc9f0; font-weight: bold;">본인</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
