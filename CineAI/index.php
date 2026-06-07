<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

// Pagination logic
$limit = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$filter_tag = $_GET['tag'] ?? '';
$tag_join = '';
$tag_bind = [];
if ($filter_tag) {
    $tag_join = " JOIN review_hashtags rh ON r.id = rh.review_id JOIN hashtags h ON rh.hashtag_id = h.id AND h.tag_name = ? ";
    $tag_bind[] = $filter_tag;
}

// Count total for pagination
$count_sql = "SELECT COUNT(DISTINCT r.id) FROM reviews r $tag_join WHERE r.status = 'ACTIVE' AND r.deleted_at IS NULL";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($tag_bind);
$total_reviews = $count_stmt->fetchColumn();
$total_pages = ceil($total_reviews / $limit);

// Fetch only ACTIVE reviews with comment & like counts (PAGINATED)
$sql = "SELECT r.*, u.username, 
    (SELECT COUNT(*) FROM comments WHERE review_id = r.id AND status = 'ACTIVE' AND deleted_at IS NULL) as comment_count,
    (SELECT COUNT(*) FROM likes WHERE review_id = r.id) as like_count
    FROM reviews r JOIN users u ON r.user_id = u.id 
    $tag_join
    WHERE r.status = 'ACTIVE' AND r.deleted_at IS NULL
    ORDER BY r.created_at DESC
    LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($tag_bind);
$reviews = $stmt->fetchAll();

// Optimized Hashtag Fetching (Solve N+1)
$review_tags = [];
if (count($reviews) > 0) {
    $review_ids = array_column($reviews, 'id');
    $placeholders = str_repeat('?,', count($review_ids) - 1) . '?';
    $tag_sql = "SELECT rh.review_id, h.tag_name 
                FROM hashtags h 
                JOIN review_hashtags rh ON h.id = rh.hashtag_id 
                WHERE rh.review_id IN ($placeholders)";
    $tag_stmt = $pdo->prepare($tag_sql);
    $tag_stmt->execute($review_ids);
    while ($row = $tag_stmt->fetch()) {
        $review_tags[$row['review_id']][] = $row['tag_name'];
    }
}

// Fetch active notices
$stmt_notices = $pdo->query("SELECT * FROM notices WHERE is_active = 1 ORDER BY created_at DESC");
$notices = $stmt_notices->fetchAll();

// Fetch Top Rated for Hall of Fame Carousel
$stmt_top = $pdo->query("SELECT r.*, u.username, 
    (SELECT COUNT(*) FROM comments WHERE review_id = r.id AND status = 'ACTIVE' AND deleted_at IS NULL) as comment_count,
    (SELECT COUNT(*) FROM likes WHERE review_id = r.id) as like_count
    FROM reviews r JOIN users u ON r.user_id = u.id 
    WHERE r.status = 'ACTIVE' AND r.deleted_at IS NULL
    ORDER BY r.star_rating DESC, r.created_at DESC
    LIMIT 6");
$top_reviews = $stmt_top->fetchAll();
?>

<?php if (count($notices) > 0): ?>
    <div class="notices-section" style="margin-bottom: 30px;">
        <?php foreach ($notices as $notice): ?>
            <div class="glass-card" style="border-left: 4px solid var(--secondary-color); padding: 15px 20px; margin-bottom: 10px; display: flex; align-items: flex-start; gap: 15px;">
                <span style="font-size: 1.5rem; line-height: 1;">📢</span>
                <div>
                    <h3 style="font-size: 1rem; margin-bottom: 5px; color: var(--secondary-color);">[공지] <?php echo htmlspecialchars($notice['title']); ?></h3>
                    <p style="font-size: 0.9rem; color: var(--text-main);"><?php echo nl2br(htmlspecialchars($notice['content'])); ?></p>
                    <small style="color: var(--text-muted); font-size: 0.8rem;"><?php echo date('Y-m-d', strtotime($notice['created_at'])); ?></small>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


<div class="home-hero">
    <div class="hero-scan-line"></div>
    <div style="position: absolute; top:0; left:0; right:0; bottom:0; background: radial-gradient(circle at center, rgba(59, 130, 246, 0.1), transparent); z-index: -1;"></div>
    <div style="position: absolute; top:0; left:0; right:0; bottom:0; backdrop-filter: blur(8px); z-index: -2;"></div>
    
    <div class="security-status-indicator">
        <div class="status-dot"></div>
        SYSTEM STATUS: ONLINE / SECURITY MONITORING ACTIVE [AES-256]
    </div>
    
    <h1 class="hero-title" style="animation: glitch 5s infinite;">CineAI MONITORING</h1>
    <p class="hero-subtitle">실시간 영화 감성 수집 및 악성 리뷰 필터링 시스템<br>보안 강화가 완료된 프리미엄 영화 분석 플랫폼입니다.</p>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="hero-cta">
            <a href="ai_recommend.php" class="btn btn-primary hero-cta-btn">
                <span style="margin-right: 10px;">🤖</span> AI 취항 맞춤 영화 추천
            </a>
            <div class="security-badge">
                <span style="font-size: 1rem;">🛡️</span> 시스템 내 모든 데이터는 실시간 보안 마스킹 처리됨
            </div>
        </div>
    <?php else: ?>
        <div style="display: flex; justify-content: center; gap: 15px;">
            <a href="register.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 16px 40px; border-radius: 50px;">🎬 시작하기</a>
            <a href="login.php" class="btn btn-outline" style="font-size: 1.1rem; padding: 16px 40px; border-radius: 50px;">로그인</a>
        </div>
    <?php endif; ?>
</div>
<script src="assets/js/toast.js"></script>

<!-- Hall of Fame Carousel -->
<?php if (count($top_reviews) > 0): ?>
    <h2 style="font-size: 1.8rem; margin-bottom: 10px; border-left: 5px solid #fbbf24; padding-left: 15px; text-shadow: 0 2px 10px rgba(251, 191, 36, 0.2);">🔥 명예의 전당 <span style="font-size: 1rem; color: var(--text-muted); font-weight: 400; margin-left: 10px;">가장 평점이 높은 영화</span></h2>
    <div class="carousel-container" style="margin-bottom: 50px;">
        <?php foreach ($top_reviews as $tr): ?>
            <div class="glass-card review-card carousel-card" style="padding: 15px; cursor: pointer;" onclick="location.href='review_read.php?id=<?php echo $tr['id']; ?>'">
                <div class="review-poster-wrapper" style="height: 220px; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
                    <?php if ($tr['poster_file']): ?>
                        <img src="poster_download.php?file=<?php echo urlencode($tr['poster_file']); ?>" class="review-poster" style="height: 100%; object-fit: cover;" alt="Movie Poster" loading="lazy" decoding="async">
                    <?php else: ?>
                        <div class="review-poster" style="height: 100%; display: flex; align-items: center; justify-content: center;">No Poster</div>
                    <?php endif; ?>
                </div>
                <h3 style="font-size: 1.1rem; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($tr['movie_title']); ?></h3>
                <div style="color: #fbbf24; font-size: 0.95rem; margin-bottom: 5px;"><?php echo str_repeat('⭐', (int)$tr['star_rating']); ?></div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">
                    ❤️ <?php echo $tr['like_count']; ?> &nbsp; 💬 <?php echo $tr['comment_count']; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<h2 style="font-size: 1.8rem; margin-bottom: 15px; border-left: 5px solid var(--secondary-color); padding-left: 15px;">✨ 최신 피드</h2>

<!-- Tag Filter Bar -->
<?php
$all_tags = ['액션', '로맨틱코미디', '스릴러', 'SF', '공포', '애니메이션', '인생영화', '킬링타임용', '명작', '눈물버튼', '반전소름', '넷플릭스추천'];
?>
<div style="display:flex; gap:10px; overflow-x:auto; margin-bottom:20px; padding-bottom:12px; scrollbar-width: auto; scrollbar-color: var(--secondary-color) transparent;">
    <a href="index.php" class="btn <?php echo !$filter_tag ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius:20px; padding: 6px 15px; white-space: nowrap; flex-shrink: 0;">전체 보기</a>
    <?php foreach($all_tags as $t): ?>
        <a href="index.php?tag=<?php echo urlencode($t); ?>" class="btn <?php echo ($filter_tag === $t) ? 'btn-primary' : 'btn-outline'; ?>" style="border-radius:20px; padding: 6px 15px; white-space: nowrap; flex-shrink: 0;">#<?php echo htmlspecialchars($t); ?></a>
    <?php endforeach; ?>
</div>

<div class="review-grid">
    <?php if (count($reviews) > 0): ?>
        <?php foreach ($reviews as $review): ?>
            <div class="glass-card review-card">
                <div class="review-poster-wrapper" style="height: 350px;">
                    <?php if ($review['poster_file']): ?>
                        <img src="poster_download.php?file=<?php echo urlencode($review['poster_file']); ?>" 
                             class="review-poster" 
                             style="height: 100%; object-fit: cover;" 
                             alt="Movie Poster"
                             loading="lazy"
                             decoding="async">
                    <?php else: ?>
                        <div class="review-poster" style="height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
                            No Poster
                        </div>
                    <?php endif; ?>
                </div>
                
                <h2 class="review-title"><?php echo htmlspecialchars($review['movie_title']); ?></h2>
                <div style="color: #fbbf24; margin-bottom: 5px; font-size: 1.2rem;">
                    <?php echo str_repeat('⭐', (int)$review['star_rating']); ?>
                </div>

                <div class="review-meta">
                    작성자: <a href="profile.php?user_id=<?php echo $review['user_id']; ?>" style="color: var(--secondary-color); font-weight: bold;"><?php echo htmlspecialchars($review['username']); ?></a> | 
                    작성일: <?php echo date('Y-m-d', strtotime($review['created_at'])); ?>
                </div>
                
                <?php if ($review['sentiment_score'] !== null): ?>
                    <div style="margin-bottom: 15px;">
                        <?php 
                        $score = (float)$review['sentiment_score'];
                        if ($score > 0.4) {
                            echo '<span class="sentiment-badge sentiment-positive">긍정적 AI 분석</span>';
                        } elseif ($score < -0.4) {
                            echo '<span class="sentiment-badge sentiment-negative">부정적 AI 분석</span>';
                        } else {
                            echo '<span class="sentiment-badge sentiment-neutral">중립적 AI 분석</span>';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <div style="flex: 1; margin-bottom: 15px;">
                    <?php if ($review['is_spoiler']): ?>
                        <div style="padding: 20px; text-align: center; background: rgba(239, 68, 68, 0.1); border: 1px dashed rgba(239, 68, 68, 0.5); border-radius: 8px;">
                            <span style="color: #ef4444; font-weight: bold;">🚫 스포일러 주의</span><br>
                            <span style="font-size: 0.9rem; color: var(--text-muted);">클릭하여 내용을 확인하세요.</span>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--text-main); font-weight: 600; font-size: 0.95rem; margin-bottom: 8px;">[AI 요약]</p>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">
                            <?php 
                            $summary = $review['summary'] ?: $review['content'];
                            echo nl2br(htmlspecialchars(mb_substr($summary, 0, 100) . (mb_strlen($summary) > 100 ? '...' : ''))); 
                            ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="tags" style="margin-bottom:15px; display:flex; flex-wrap:wrap; gap:5px;">
                    <?php
                    $tags = $review_tags[$review['id']] ?? [];
                    foreach($tags as $t){
                        echo "<a href='index.php?tag=" . urlencode($t) . "' style='font-size:0.8rem; background:rgba(76, 201, 240, 0.1); color:var(--secondary-color); padding:3px 8px; border-radius:12px; display:inline-block; border: 1px solid rgba(76, 201, 240, 0.3); text-decoration:none;'>#" . htmlspecialchars($t) . "</a>";
                    }
                    ?>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; font-size: 0.9rem; color: var(--text-muted);">
                    <span>❤️ 좋아요 <?php echo $review['like_count']; ?></span>
                    <span>💬 댓글 <?php echo $review['comment_count']; ?></span>
                </div>
                
                <a href="review_read.php?id=<?php echo $review['id']; ?>" class="btn btn-outline" style="width: 100%;">자세히 보기</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); font-size: 1.2rem; padding: 40px;">아직 작성된 리뷰가 없습니다. 첫 리뷰를 작성해보세요!</p>
    <?php endif; ?>
</div>

<!-- Pagination UI -->
<?php if ($total_pages > 1): ?>
    <div class="pagination" style="display: flex; justify-content: center; gap: 10px; margin-top: 40px; margin-bottom: 20px;">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?><?php echo $filter_tag ? '&tag='.urlencode($filter_tag) : ''; ?>" class="btn btn-outline" style="padding: 8px 16px;">&laquo; 이전</a>
        <?php endif; ?>
        
        <?php
        $start_p = max(1, $page - 2);
        $end_p = min($total_pages, $page + 2);
        for ($i = $start_p; $i <= $end_p; $i++):
        ?>
            <a href="?page=<?php echo $i; ?><?php echo $filter_tag ? '&tag='.urlencode($filter_tag) : ''; ?>" 
               class="btn <?php echo ($i === $page) ? 'btn-primary' : 'btn-outline'; ?>" 
               style="padding: 8px 16px; min-width: 45px;">
               <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo $filter_tag ? '&tag='.urlencode($filter_tag) : ''; ?>" class="btn btn-outline" style="padding: 8px 16px;">다음 &raquo;</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
