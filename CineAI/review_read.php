<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

// Fetch review & stats
$stmt = $pdo->prepare("SELECT r.*, u.username, u.id as author_id,
    (SELECT COUNT(*) FROM likes WHERE review_id = r.id) as like_count
    FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->execute([$id]);
$review = $stmt->fetch();

if (!$review) {
    echo "<div class='container' style='margin-top: 50px;'><div class='alert alert-error'>존재하지 않는 리뷰입니다.</div></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Status & Privacy Check
$is_author = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['author_id'];
$is_admin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['Super-Admin', 'Admin']);

if ($review['status'] !== 'ACTIVE' && !$is_author && !$is_admin) {
    echo "<div class='container' style='margin-top: 50px;'><div class='alert alert-error'>이 리뷰는 현재 비공개 상태이거나 검토 중입니다.</div></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

if ($review['deleted_at'] !== null && !$is_admin) {
    echo "<div class='container' style='margin-top: 50px;'><div class='alert alert-error'>삭제된 리뷰입니다.</div></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$can_edit = isset($_SESSION['user_id']) && (in_array($_SESSION['role'], ['Super-Admin', 'Admin']) || $_SESSION['user_id'] == $review['author_id']);

// Check if user liked it
$user_liked = false;
if (isset($_SESSION['user_id'])) {
    $likeStmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ? AND review_id = ?");
    $likeStmt->execute([$_SESSION['user_id'], $id]);
    if ($likeStmt->fetchColumn() > 0) {
        $user_liked = true;
    }
}

// Fetch Comments
if ($is_admin || $is_author) {
    $commentStmt = $pdo->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.review_id = ? AND c.deleted_at IS NULL ORDER BY c.created_at ASC");
} else {
    $commentStmt = $pdo->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.review_id = ? AND c.status = 'ACTIVE' AND c.deleted_at IS NULL ORDER BY c.created_at ASC");
}
$commentStmt->execute([$id]);
$comments = $commentStmt->fetchAll();
?>

<div style="max-width: 900px; margin: 0 auto; margin-top: 40px;">
    <div class="glass-card" style="display: flex; gap: 40px; flex-wrap: wrap; margin-bottom: 30px;">
        <div style="flex: 1; min-width: 300px;">
            <?php if ($review['poster_file']): ?>
                <img src="poster_download.php?file=<?php echo urlencode($review['poster_file']); ?>" class="review-poster" alt="Movie Poster" style="height: auto; max-width: 100%;">
            <?php else: ?>
                <div class="review-poster" style="display: flex; align-items: center; justify-content: center; color: var(--text-muted); height: 400px;">
                    No Poster
                </div>
            <?php endif; ?>
        </div>

        <div style="flex: 2; min-width: 300px;">
            <h1 style="margin-bottom: 10px;"><?php echo htmlspecialchars($review['movie_title']); ?></h1>
            <div style="color: #fbbf24; margin-bottom: 15px; font-size: 1.5rem;">
                <?php echo str_repeat('⭐', (int)$review['star_rating']); ?>
            </div>
            
            <div class="review-meta" style="margin-bottom: 20px;">
                작성자: <?php echo htmlspecialchars($review['username']); ?> | 
                작성일: <?php echo date('Y-m-d H:i', strtotime($review['created_at'])); ?>
            </div>

            <?php if ($review['summary']): ?>
                <div style="background: rgba(76, 201, 240, 0.05); border-left: 4px solid var(--secondary-color); padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <strong style="color: var(--secondary-color);">✨ AI 핵심 요약</strong>
                    <p style="margin-top: 5px; font-size: 0.95rem;"><?php echo nl2br(htmlspecialchars($review['summary'])); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($review['sentiment_score'] !== null): ?>
                <div style="margin-bottom: 30px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 8px; display: inline-block;">
                    <span style="color: var(--text-muted); font-size: 0.9rem; margin-right: 10px;">AI 감성 스코어:</span>
                    <?php 
                    $score = (float)$review['sentiment_score'];
                    if ($score > 0) {
                        echo '<span class="sentiment-badge sentiment-positive">긍정적 (' . round($score * 100, 1) . ' 점)</span>';
                    } elseif ($score < 0) {
                        echo '<span class="sentiment-badge sentiment-negative">부정적 (' . round($score * 100, 1) . ' 점)</span>';
                    } else {
                        echo '<span class="sentiment-badge sentiment-neutral">중립적 (0 점)</span>';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($review['is_spoiler']): ?>
                <div id="spoiler-warning" style="padding: 30px; text-align: center; background: rgba(239, 68, 68, 0.1); border: 2px dashed #ef4444; border-radius: 8px; margin-bottom: 30px; cursor: pointer;" onclick="document.getElementById('spoiler-content').style.display='block'; this.style.display='none';">
                    <h3 style="color: #ef4444; margin-bottom: 10px;">🚫 AI 스포일러 경고 🚫</h3>
                    <p style="color: var(--text-muted);">이 리뷰에는 중요한 스포일러가 포함되어 있을 수 있습니다.<br>클릭하여 원문 보기</p>
                </div>
                <div id="spoiler-content" style="display: none; line-height: 1.8; font-size: 1.1rem; margin-bottom: 40px; white-space: pre-wrap;"><?php echo htmlspecialchars($review['content']); ?></div>
            <?php else: ?>
                <div style="line-height: 1.8; font-size: 1.1rem; margin-bottom: 40px; white-space: pre-wrap;"><?php echo htmlspecialchars($review['content']); ?></div>
            <?php endif; ?>

            <!-- Social Action -->
            <div style="display: flex; gap: 15px; align-items: center; border-top: 1px solid var(--glass-border); padding-top: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                <form action="review_like.php" method="POST" style="margin: 0;">
                    <?php echo get_csrf_input(); ?>
                    <input type="hidden" name="review_id" value="<?php echo $id; ?>">
                    <button type="submit" class="btn" style="background: <?php echo $user_liked ? 'var(--primary-color)' : 'var(--glass-bg)'; ?>; color: #fff; border: 1px solid var(--primary-color);">
                        <?php echo $user_liked ? '❤️ 좋아요 취소' : '🤍 좋아요'; ?> (<?php echo $review['like_count']; ?>)
                    </button>
                </form>

                <?php if (isset($_SESSION['user_id']) && $review['tmdb_id']): ?>
                    <?php
                    $watchlistStmt = $pdo->prepare("SELECT COUNT(*) FROM watchlist WHERE user_id = ? AND tmdb_id = ?");
                    $watchlistStmt->execute([$_SESSION['user_id'], $review['tmdb_id']]);
                    $in_watchlist = $watchlistStmt->fetchColumn() > 0;
                    ?>
                    <form action="watchlist_action.php" method="POST" style="margin: 0;">
                        <?php echo get_csrf_input(); ?>
                        <input type="hidden" name="action" value="<?php echo $in_watchlist ? 'remove' : 'add'; ?>">
                        <input type="hidden" name="tmdb_id" value="<?php echo $review['tmdb_id']; ?>">
                        <input type="hidden" name="title" value="<?php echo htmlspecialchars($review['movie_title']); ?>">
                        <input type="hidden" name="poster_path" value="<?php echo htmlspecialchars($review['poster_file']); ?>">
                        <input type="hidden" name="redirect" value="review_read.php?id=<?php echo $id; ?>">
                        <button type="submit" class="btn btn-outline" style="border-color: var(--secondary-color); color: var(--secondary-color);">
                            <?php echo $in_watchlist ? '✅ 보관함에서 제거' : '🔖 나중에 볼 영화'; ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>


            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="index.php" class="btn btn-outline">목록으로</a>
                <a href="review_download.php?id=<?php echo $review['id']; ?>" class="btn btn-outline" style="border-color: var(--secondary-color); color: var(--secondary-color);">📥 리뷰 다운로드 (.txt)</a>
                <?php if ($can_edit): ?>
                    <a href="review_edit.php?id=<?php echo $review['id']; ?>" class="btn btn-primary">수정</a>
                    <a href="review_delete.php?id=<?php echo $review['id']; ?>" class="btn btn-danger" onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Comments Section -->
    <div class="glass-card">
        <h3 style="margin-bottom: 20px;">💬 댓글 (<?php echo count($comments); ?>)</h3>
        
        <?php foreach ($comments as $comment): ?>
            <div style="padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between;">
                <div>
                    <strong style="color: var(--secondary-color);"><?php echo htmlspecialchars($comment['username']); ?></strong>
                    <span style="color: var(--text-muted); font-size: 0.8rem; margin-left: 10px;"><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></span>
                    <p style="margin-top: 8px;"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                </div>
                <?php if (isset($_SESSION['user_id']) && (in_array($_SESSION['role'], ['Super-Admin', 'Admin']) || $_SESSION['user_id'] == $comment['user_id'])): ?>
                    <form action="comment_action.php" method="POST" style="margin: 0;">
                        <?php echo get_csrf_input(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                        <input type="hidden" name="review_id" value="<?php echo $id; ?>">
                        <button type="submit" style="background:none; border:none; color: var(--danger-color); cursor: pointer; text-decoration: underline; font-size: 0.85rem;" onclick="return confirm('댓글을 삭제하시겠습니까?');">삭제</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
            <form action="comment_action.php" method="POST" style="margin-top: 30px; display: flex; gap: 10px;">
                <?php echo get_csrf_input(); ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="review_id" value="<?php echo $id; ?>">
                <textarea name="content" class="form-control" rows="2" placeholder="댓글을 남겨보세요." required style="flex: 1; resize: vertical;"></textarea>
                <button type="submit" class="btn btn-primary" style="white-space: nowrap;">등록</button>
            </form>
        <?php else: ?>
            <div style="margin-top: 30px; text-align: center; padding: 20px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                댓글을 작성하려면 <a href="login.php">로그인</a>해주세요.
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
