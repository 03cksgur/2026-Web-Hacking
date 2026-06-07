<?php
// watchlist.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM watchlist WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$watchlist = $stmt->fetchAll();
?>

<div style="margin-top: 40px;">
    <h1 style="margin-bottom: 30px;">🔖 나의 보관함 (나중에 볼 영화)</h1>

    <?php if (count($watchlist) > 0): ?>
        <div class="review-grid">
            <?php foreach ($watchlist as $item): ?>
                <div class="glass-card review-card" style="position: relative;">
                    <?php if ($item['poster_path']): ?>
                        <img src="poster_download.php?file=<?php echo urlencode($item['poster_path']); ?>" class="review-poster" alt="Movie Poster">
                    <?php else: ?>
                        <div class="review-poster" style="display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                            No Poster
                        </div>
                    <?php endif; ?>
                    
                    <h2 class="review-title"><?php echo htmlspecialchars($item['title']); ?></h2>
                    <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 15px;">
                        저장일: <?php echo date('Y-m-d', strtotime($item['created_at'])); ?>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: auto;">
                        <a href="review_write.php?tmdb_id=<?php echo $item['tmdb_id']; ?>&title=<?php echo urlencode($item['title']); ?>" class="btn btn-primary" style="flex: 1;">📝 이 영화 리뷰 쓰기</a>
                        <form action="watchlist_action.php" method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="tmdb_id" value="<?php echo $item['tmdb_id']; ?>">
                            <button type="submit" class="btn btn-outline" style="border-color: var(--danger-color); color: var(--danger-color);">삭제</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="glass-card" style="text-align: center; padding: 60px;">
            <p style="font-size: 1.2rem; color: var(--text-muted);">아직 보관함에 담긴 영화가 없습니다.</p>
            <p style="margin-top: 10px;">다른 유저의 리뷰를 보고 관심 있는 영화를 담아보세요!</p>
            <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">리뷰 둘러보기</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
