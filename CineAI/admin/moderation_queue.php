<?php
// admin/moderation_queue.php
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/moderation_engine.php';
require_once __DIR__ . '/../includes/admin_header.php';

$guard = new CineAIGuard($pdo);

// Action Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? '';
    $type = $_POST['type'] ?? 'review'; // review or comment

    if ($id && $action) {
        $status = ($action === 'approve') ? 'ACTIVE' : 'BLOCKED';
        $table = ($type === 'review') ? 'reviews' : 'comments';
        
        $stmt = $pdo->prepare("UPDATE $table SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        $guard->logAction($_SESSION['user_id'], strtoupper($action), $type, $id, "수동 검토 결과: $status");
        
        header("Location: moderation_queue.php?msg=success");
        exit;
    }
}

// Fetch Pending Reviews
$reviews = $pdo->query("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE (r.status = 'PENDING' OR r.status = 'MANUAL_REVIEW') AND r.deleted_at IS NULL ORDER BY r.created_at ASC")->fetchAll();

// Fetch Pending Comments
$comments = $pdo->query("SELECT c.*, u.username, r.movie_title FROM comments c JOIN users u ON c.user_id = u.id JOIN reviews r ON c.review_id = r.id WHERE (c.status = 'PENDING' OR c.status = 'MANUAL_REVIEW') AND c.deleted_at IS NULL ORDER BY c.created_at ASC")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
    <div>
        <h1 style="font-size: 2.2rem; font-weight: 800; margin-bottom: 5px;">🛡️ AI 검토 대기열</h1>
        <p style="color: var(--text-muted);">CineAIGuard(AI)에 의해 보류되었거나 유저가 신고한 리뷰/댓글입니다.</p>
    </div>
</div>
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">처리가 완료되었습니다.</div>
    <?php endif; ?>

    <!-- Pending Reviews -->
    <div class="glass-card" style="margin-bottom: 40px; padding: 0 !important; overflow-x: auto;">
        <h3 style="padding: 20px; font-weight: 700;">📄 리뷰 검토 (<?php echo count($reviews); ?>건)</h3>
        <?php if (count($reviews) > 0): ?>
            <div style="min-width: 900px;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>영화</th>
                            <th>작성자</th>
                            <th>내용</th>
                            <th>상태</th>
                            <th>액션</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($review['movie_title']); ?></td>
                                <td><?php echo htmlspecialchars($review['username']); ?></td>
                                <td style="max-width: 300px; color: var(--text-muted); font-size: 0.9rem;"><?php echo htmlspecialchars(mb_substr($review['content'], 0, 100)); ?>...</td>
                                <td><span class="sentiment-badge sentiment-neutral" style="border-radius:4px; font-size:0.8rem;"><?php echo $review['status']; ?></span></td>
                                <td>
                                    <form method="POST" style="display: flex; gap: 5px;">
                                        <input type="hidden" name="id" value="<?php echo $review['id']; ?>">
                                        <input type="hidden" name="type" value="review">
                                        <button type="submit" name="action" value="approve" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem; border-radius:6px;">승인</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8rem; border-radius:6px;">삭제</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-muted); padding: 30px;">대기 중인 리뷰가 없습니다. 👍</p>
        <?php endif; ?>
    </div>

    <!-- Pending Comments -->
    <div class="glass-card" style="padding: 0 !important; overflow-x: auto;">
        <h3 style="padding: 20px; font-weight: 700;">💬 댓글 검토 (<?php echo count($comments); ?>건)</h3>
        <?php if (count($comments) > 0): ?>
            <div style="min-width: 900px;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>리뷰 대상</th>
                            <th>작성자</th>
                            <th>내용</th>
                            <th>액션</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $comment): ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($comment['movie_title']); ?></td>
                                <td><span style="background:rgba(255,255,255,0.1); padding:4px 8px; border-radius:4px; font-size:0.8rem;"><?php echo htmlspecialchars($comment['username']); ?></span></td>
                                <td style="color: var(--text-muted); font-size: 0.95rem;"><?php echo htmlspecialchars($comment['content']); ?></td>
                                <td>
                                    <form method="POST" style="display: flex; gap: 5px;">
                                        <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                                        <input type="hidden" name="type" value="comment">
                                        <button type="submit" name="action" value="approve" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem; border-radius:6px;">승인</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8rem; border-radius:6px;">삭제</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-muted); padding: 30px;">대기 중인 댓글이 없습니다. 👍</p>
        <?php endif; ?>
    </div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
