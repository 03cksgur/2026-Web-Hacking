<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Mark all as read
$update = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$update->execute([$user_id]);

// Fetch all
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();
?>

<div style="max-width: 800px; margin: 0 auto; padding: 40px 20px;">
    <h1 style="margin-bottom: 30px;">🔔 모든 알림</h1>

    <?php if (count($notifications) > 0): ?>
        <div class="glass-card" style="padding: 0;">
            <?php foreach ($notifications as $n): ?>
                <div style="padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <a href="<?php echo htmlspecialchars($n['link']); ?>" style="text-decoration: none; color: var(--text-main); font-weight: 500;">
                            <?php echo htmlspecialchars($n['message']); ?>
                        </a>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 5px;">
                            <?php echo $n['created_at']; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="glass-card" style="text-align: center; padding: 60px;">
            <p style="color: var(--text-muted);">알림 내역이 없습니다.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
