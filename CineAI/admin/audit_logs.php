<?php
// admin/audit_logs.php
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_header.php';

// Fetch Logs with Pagination
$logs = $pdo->query("SELECT l.*, u.username as admin_name FROM audit_logs l JOIN users u ON l.admin_id = u.id ORDER BY l.created_at DESC LIMIT 50")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
    <div>
        <h1 style="font-size: 2.2rem; font-weight: 800; margin-bottom: 5px;">📜 보안 감사 로그</h1>
        <p style="color: var(--text-muted);">관리자의 시스템 접근 및 액션 내역을 확인합니다. (최근 50건)</p>
    </div>
</div>

<div class="glass-card" style="padding: 0 !important; overflow-x: auto;">
    <div style="min-width: 1000px;">
        <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>관리자</th>
                <th>액션</th>
                <th>대상 타입</th>
                <th>대상 ID</th>
                <th>사유/내역</th>
                <th>일시</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($logs) > 0): ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="color: var(--text-muted);"><?php echo $log['id']; ?></td>
                        <td><span style="background:rgba(255,255,255,0.1); padding:4px 8px; border-radius:4px; font-weight:bold; font-size:0.8rem;"><?php echo htmlspecialchars($log['admin_name']); ?></span></td>
                        <td>
                            <span class="sentiment-badge <?php echo (strpos($log['action'], 'DELETE') !== false || strpos($log['action'], 'REJECT') !== false) ? 'sentiment-negative' : 'sentiment-positive'; ?>" style="border-radius:4px; font-size:0.8rem;">
                                <?php echo htmlspecialchars($log['action']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($log['target_type']); ?></td>
                        <td style="font-weight: 600;">#<?php echo $log['target_id']; ?></td>
                        <td style="max-width: 250px; color:rgba(255,255,255,0.8);"><?php echo htmlspecialchars($log['reason']); ?></td>
                        <td style="color: var(--text-muted); font-size:0.85rem;"><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">아직 기록된 로그가 없습니다.</td></tr>
            <?php endif; ?>
        </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
