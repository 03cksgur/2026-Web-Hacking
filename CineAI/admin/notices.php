<?php
// admin/notices.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../includes/admin_header.php';

// Fetch all notices
$stmt = $pdo->query("SELECT * FROM notices ORDER BY created_at DESC");
$notices = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
    <div>
        <h1 style="font-size: 2.2rem; font-weight: 800; margin-bottom: 5px;">📢 공지사항 관리</h1>
        <p style="color: var(--text-muted);">사이트 이용자들에게 표시될 공지 및 안내사항을 관리합니다.</p>
    </div>
</div>    <!-- Create Notice Form -->
    <div class="glass-card" style="margin-bottom: 40px;">
        <h2 style="margin-bottom: 20px;">새 공지사항 작성</h2>
        <form action="notice_action.php" method="POST">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label for="title">제목</label>
                <input type="text" id="title" name="title" class="form-control" required placeholder="공지사항 제목을 입력하세요">
            </div>
            <div class="form-group">
                <label for="content">내용</label>
                <textarea id="content" name="content" class="form-control" rows="4" required placeholder="공지 내용을 입력하세요"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">공지 등록하기</button>
        </form>
    </div>

    <!-- Notice List -->
    <div class="glass-card" style="padding: 0 !important; overflow: hidden;">
        <h2 style="margin-bottom: 20px; padding: 20px;">기존 공지 내역</h2>
        <?php if (count($notices) > 0): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>제목</th>
                        <th>상태</th>
                        <th>작성일</th>
                        <th style="text-align: right;">관리</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notices as $notice): ?>
                        <tr>
                            <td><?php echo $notice['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($notice['title']); ?></strong>
                                <div style="font-size: 0.85rem; color: var(--text-muted); padding-top: 5px;"><?php echo htmlspecialchars(mb_substr($notice['content'], 0, 50)); ?>...</div>
                            </td>
                            <td>
                                <?php if ($notice['is_active']): ?>
                                    <span class="sentiment-badge sentiment-positive" style="border-radius:4px;">활성</span>
                                <?php else: ?>
                                    <span class="sentiment-badge sentiment-neutral" style="border-radius:4px;">비활성</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.9rem;"><?php echo date('Y-m-d', strtotime($notice['created_at'])); ?></td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                    <form action="notice_action.php" method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">
                                        <button type="submit" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.85rem;">
                                            <?php echo $notice['is_active'] ? '해제' : '게시'; ?>
                                        </button>
                                    </form>
                                    <form action="notice_action.php" method="POST" style="display: inline;" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.85rem;">삭제</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-muted); padding: 30px;">등록된 공지사항이 없습니다.</p>
        <?php endif; ?>
    </div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
