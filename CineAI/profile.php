<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

$target_user_id = $_GET['user_id'] ?? null;
if (!$target_user_id) {
    echo "<div class='container'><p>유효하지 않은 유저입니다.</p></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch user info
$stmt = $pdo->prepare("SELECT id, username, profile_pic FROM users WHERE id = ?");
$stmt->execute([$target_user_id]);
$target_user = $stmt->fetch();

if (!$target_user) {
    echo "<div class='container'><p>존재하지 않는 유저입니다.</p></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch counts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
$stmt->execute([$target_user_id]);
$follower_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$stmt->execute([$target_user_id]);
$following_count = $stmt->fetchColumn();

// Fetch target user's reviews
$stmt = $pdo->prepare("SELECT r.*, 
    (SELECT COUNT(*) FROM comments WHERE review_id = r.id AND status = 'ACTIVE' AND deleted_at IS NULL) as comment_count,
    (SELECT COUNT(*) FROM likes WHERE review_id = r.id) as like_count
    FROM reviews r 
    WHERE r.user_id = ? AND r.status = 'ACTIVE' AND r.deleted_at IS NULL
    ORDER BY r.created_at DESC");
$stmt->execute([$target_user_id]);
$reviews = $stmt->fetchAll();

// Check if current user is following
$is_following = false;
$current_user_id = $_SESSION['user_id'] ?? null;

if ($current_user_id) {
    $stmt = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$current_user_id, $target_user_id]);
    $is_following = $stmt->fetchColumn() ? true : false;
}
?>

<div class="container" style="max-width: 900px; margin-top: 50px;">
    <!-- Profile Header -->
    <div class="glass-card" style="display: flex; align-items: center; gap: 30px; margin-bottom: 40px; background: rgba(0,0,0,0.4); border-color: var(--primary-color);">
        <div style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; background: var(--bg-color); border: 3px solid var(--secondary-color);">
            <?php if ($target_user['profile_pic']): ?>
                <img src="profile_download.php?file=<?php echo urlencode($target_user['profile_pic']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3rem;">👤</div>
            <?php endif; ?>
        </div>
        
        <div style="flex: 1;">
            <h1 style="margin-bottom: 10px; font-size: 2.2rem;"><?php echo htmlspecialchars($target_user['username']); ?></h1>
            <div style="display: flex; gap: 20px; color: var(--text-muted); font-size: 1.1rem; margin-bottom: 15px;">
                <span>작성한 리뷰: <strong style="color:var(--text-main);"><?php echo count($reviews); ?></strong></span>
                <span>팔로워: <strong id="follower-count" style="color:var(--text-main);"><?php echo $follower_count; ?></strong></span>
                <span>팔로잉: <strong style="color:var(--text-main);"><?php echo $following_count; ?></strong></span>
            </div>
            
            <?php if ($current_user_id && $current_user_id != $target_user_id): ?>
                <button id="follow-btn" class="btn <?php echo $is_following ? 'btn-outline' : 'btn-primary'; ?>" onclick="toggleFollow(<?php echo $target_user_id; ?>)" style="padding: 8px 25px; border-radius: 20px;">
                    <?php echo $is_following ? '언팔로우' : '팔로우'; ?>
                </button>
            <?php elseif (!$current_user_id): ?>
                <a href="login.php" class="btn btn-outline" style="padding: 8px 25px; border-radius: 20px;">팔로우 하려면 로그인</a>
            <?php else: ?>
                <span class="sentiment-badge sentiment-positive">나의 프로필 입니다</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- User's Reviews -->
    <h2 style="font-size: 1.8rem; margin-bottom: 20px; border-left: 5px solid var(--secondary-color); padding-left: 15px;">작성한 리뷰</h2>
    <div class="review-grid">
        <?php if (count($reviews) > 0): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="glass-card review-card">
                    <div class="review-poster-wrapper" style="height: 250px;">
                        <?php if ($review['poster_file']): ?>
                            <img src="poster_download.php?file=<?php echo urlencode($review['poster_file']); ?>" class="review-poster" style="height: 100%; object-fit: cover;" alt="Movie Poster">
                        <?php else: ?>
                            <div class="review-poster" style="height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">No Poster</div>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="review-title" style="font-size: 1.2rem;"><?php echo htmlspecialchars($review['movie_title']); ?></h3>
                    <div style="color: #fbbf24; margin-bottom: 5px; font-size: 1rem;">
                        <?php echo str_repeat('⭐', (int)$review['star_rating']); ?>
                    </div>
                    <div class="review-meta" style="margin-bottom: 10px;">
                        작성일: <?php echo date('Y-m-d', strtotime($review['created_at'])); ?>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; font-size: 0.85rem; color: var(--text-muted);">
                        <span>❤️ 좋아요 <?php echo $review['like_count']; ?></span>
                        <span>💬 댓글 <?php echo $review['comment_count']; ?></span>
                    </div>
                    <a href="review_read.php?id=<?php echo $review['id']; ?>" class="btn btn-outline" style="width: 100%;">자세히 보기</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column: 1 / -1; color: var(--text-muted); text-align: center; padding: 30px;">아직 작성한 리뷰가 없습니다.</p>
        <?php endif; ?>
    </div>
</div>

<script>
async function toggleFollow(followingId) {
    const btn = document.getElementById('follow-btn');
    const countSpan = document.getElementById('follower-count');
    
    btn.disabled = true;
    try {
        const res = await fetch('api/follow_action.php', {
            method: 'POST',
            body: JSON.stringify({ following_id: followingId })
        });
        const data = await res.json();
        if (data.success) {
            countSpan.innerText = data.follower_count;
            if (data.is_following) {
                btn.className = 'btn btn-outline';
                btn.innerText = '언팔로우';
            } else {
                btn.className = 'btn btn-primary';
                btn.innerText = '팔로우';
            }
        } else {
            alert(data.error);
        }
    } catch (e) {
        alert("오류가 발생했습니다.");
    }
    btn.disabled = false;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
