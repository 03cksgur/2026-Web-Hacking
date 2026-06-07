<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf_helper.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/upload_helper.php';
require_once __DIR__ . '/api/gemini.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = ?");
$stmt->execute([$id]);
$review = $stmt->fetch();

if (!$review) {
    die("존재하지 않는 리뷰입니다.");
}

// RBAC
if (!in_array($_SESSION['role'], ['Super-Admin', 'Admin']) && $_SESSION['user_id'] != $review['user_id']) {
    die("수정 권한이 없습니다.");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die(); // A01/A06 CSRF Protection
    $movie_title = trim($_POST['movie_title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($movie_title) || empty($content)) {
        $error = "영화 제목과 리뷰 내용을 입력해주세요.";
    } elseif (mb_strlen($content, 'UTF-8') > 1000) {
        $error = "리뷰 내용은 최대 1000자를 초과할 수 없습니다. 보안 정책에 의해 차단되었습니다.";
    } else {
        $poster_file = $review['poster_file'];
        
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $new_poster = handlePosterUpload($_FILES['poster']);
            if ($new_poster) {
                // Delete old poster if exists
                if ($poster_file && file_exists(__DIR__ . '/uploads/' . $poster_file)) {
                    unlink(__DIR__ . '/uploads/' . $poster_file);
                }
                $poster_file = $new_poster;
            } else {
                $error = "새 포스터 업로드에 실패했습니다.";
            }
        }

        if (!$error) {
            $star_rating = (int)($_POST['star_rating'] ?? 5);
            $ai_result = analyzeSentimentWithGemini($content);

            $update = $pdo->prepare("UPDATE reviews SET movie_title = ?, content = ?, poster_file = ?, sentiment_score = ?, summary = ?, is_spoiler = ?, star_rating = ? WHERE id = ?");
            if ($update->execute([$movie_title, $content, $poster_file, $ai_result['sentiment_score'], $ai_result['summary'], $ai_result['is_spoiler'], $star_rating, $id])) {
                header("Location: review_read.php?id=" . $id);
                exit;
            } else {
                $error = "리뷰 수정에 실패했습니다.";
            }
        }
    }
}
?>

<div style="max-width: 800px; margin: 0 auto; margin-top: 40px;">
    <div class="glass-card">
        <h2 style="margin-bottom: 30px;">리뷰 수정</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="review_edit.php?id=<?php echo (int)$id; ?>" enctype="multipart/form-data">
            <?php echo get_csrf_input(); ?>
            <div class="form-group">
                <label for="movie_title">영화 제목</label>
                <input type="text" id="movie_title" name="movie_title" class="form-control" required value="<?php echo htmlspecialchars($_POST['movie_title'] ?? $review['movie_title']); ?>">
            </div>

            <div class="form-group">
                <label for="star_rating">별점 (My Rating)</label>
                <select id="star_rating" name="star_rating" class="form-control">
                    <option value="5" <?php echo ((int)$review['star_rating'] === 5) ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ (5점)</option>
                    <option value="4" <?php echo ((int)$review['star_rating'] === 4) ? 'selected' : ''; ?>>⭐⭐⭐⭐ (4점)</option>
                    <option value="3" <?php echo ((int)$review['star_rating'] === 3) ? 'selected' : ''; ?>>⭐⭐⭐ (3점)</option>
                    <option value="2" <?php echo ((int)$review['star_rating'] === 2) ? 'selected' : ''; ?>>⭐⭐ (2점)</option>
                    <option value="1" <?php echo ((int)$review['star_rating'] === 1) ? 'selected' : ''; ?>>⭐ (1점)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>현재 포스터</label>
                <?php if ($review['poster_file']): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="poster_download.php?file=<?php echo urlencode($review['poster_file']); ?>" style="max-width: 150px; border-radius: 8px;">
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted);">없음</p>
                <?php endif; ?>
                <label for="poster">포스터 변경 (선택)</label>
                <input type="file" id="poster" name="poster" class="form-control" accept="image/*">
            </div>

            <div class="form-group" style="position: relative;">
                <div style="display: flex; justify-content: space-between; align-items: baseline;">
                    <label for="content">리뷰 내용</label>
                    <span id="char-counter" style="font-size: 0.85rem; color: var(--text-muted);">0 / 1000</span>
                </div>
                <textarea id="content" name="content" class="form-control" rows="10" required maxlength="1000"><?php echo htmlspecialchars($_POST['content'] ?? $review['content']); ?></textarea>
                <script>
                    const contentArea = document.getElementById('content');
                    const charCounter = document.getElementById('char-counter');
                    contentArea.addEventListener('input', function() {
                        const len = this.value.length;
                        charCounter.innerText = len + ' / 1000';
                        if (len >= 1000) charCounter.style.color = 'var(--danger-color)';
                        else charCounter.style.color = 'var(--text-muted)';
                    });
                    // init
                    charCounter.innerText = contentArea.value.length + ' / 1000';
                </script>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">수정하기 (AI 재분석 포함)</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
