require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf_helper.php';
require_once __DIR__ . '/includes/moderation_engine.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$guard = new CineAIGuard($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die(); // A01/A06 CSRF Protection
    $action = $_POST['action'] ?? '';
    $review_id = $_POST['review_id'] ?? '';
    
    if (!$review_id) {
        header("Location: index.php");
        exit;
    }

    if ($action === 'add') {
        $content = trim($_POST['content'] ?? '');
        if ($content) {
            $mod_result = $guard->moderate('comment', $content);
            
            if ($mod_result['status'] === 'BLOCKED') {
                echo "<script>alert('부적절한 내용이 포함되어 댓글을 등록할 수 없습니다: " . addslashes($mod_result['reason']) . "'); window.location.href='review_read.php?id=" . $review_id . "';</script>";
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO comments (review_id, user_id, content, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$review_id, $_SESSION['user_id'], $content, $mod_result['status']]);
            
            // Notification Logic
            $stmtOwner = $pdo->prepare("SELECT r.user_id, r.movie_title, u.notif_enabled FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
            $stmtOwner->execute([$review_id]);
            $owner = $stmtOwner->fetch();

            if ($owner && $owner['user_id'] != $_SESSION['user_id'] && $owner['notif_enabled']) {
                $msg = $_SESSION['username'] . "님이 [" . $owner['movie_title'] . "] 리뷰에 댓글을 남겼습니다.";
                $link = "review_read.php?id=" . $review_id;
                $notif = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
                $notif->execute([$owner['user_id'], $msg, $link]);
            }

            if ($mod_result['status'] === 'MANUAL_REVIEW') {
                echo "<script>alert('댓글이 등록되었으나 검토 후 공개됩니다.'); window.location.href='review_read.php?id=" . $review_id . "';</script>";
                exit;
            }
        }
    } elseif ($action === 'delete') {
        $comment_id = $_POST['comment_id'] ?? '';
        if ($comment_id) {
            // RBAC Check
            $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            $owner = $stmt->fetchColumn();

            if ($owner && (in_array($_SESSION['role'], ['Super-Admin', 'Admin']) || $_SESSION['user_id'] == $owner)) {
                $del = $pdo->prepare("DELETE FROM comments WHERE id = ?");
                $del->execute([$comment_id]);
            }
        }
    }
    
    header("Location: review_read.php?id=" . $review_id);
    exit;
}
header("Location: index.php");
?>
