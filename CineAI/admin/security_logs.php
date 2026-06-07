<?php
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/admin_header.php';

$type = $_GET['type'] ?? '';
$where = "WHERE 1=1";
$params = [];

if ($type) {
    if ($type === 'SQLI') {
        $where .= " AND event_type LIKE '%SQL%'";
    } elseif ($type === 'ABNORMAL') {
        $where .= " AND event_type NOT LIKE '%SQL%'";
    }
}

$stmt = $pdo->prepare("SELECT * FROM security_logs $where ORDER BY created_at DESC");
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h1 style="font-size: 2rem; color: var(--danger-color);">⚠️ 보안 로그 상세 분석</h1>
        <p style="color: var(--text-muted);">시스템에서 탐지된 보안 위협 및 비정상 접근 시도 상세 내역입니다.</p>
    </div>
    <div>
        <a href="dashboard.php" class="btn btn-outline">← 관리자 페이지로 돌아가기</a>
    </div>
</div>

<div class="glass-card">
    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
        <a href="security_logs.php" class="btn <?php echo !$type ? 'btn-primary' : 'btn-outline'; ?>">전체</a>
        <a href="security_logs.php?type=SQLI" class="btn <?php echo $type === 'SQLI' ? 'btn-primary' : 'btn-outline'; ?>">SQL Injection</a>
        <a href="security_logs.php?type=ABNORMAL" class="btn <?php echo $type === 'ABNORMAL' ? 'btn-primary' : 'btn-outline'; ?>">기타 비정상 접근</a>
    </div>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align: left; border-bottom: 1px solid var(--glass-border);">
                <th style="padding: 15px;">일시</th>
                <th style="padding: 15px;">유형</th>
                <th style="padding: 15px;">설명</th>
                <th style="padding: 15px;">IP 주소</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="4" style="padding: 40px; text-align: center; color: var(--text-muted);">표시할 로그가 없습니다.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.3s;" onmouseover="this.style.background='rgba(239, 68, 68, 0.05)'" onmouseout="this.style.background='transparent'">
                        <td style="padding: 15px;"><?php echo htmlspecialchars($log['created_at']); ?></td>
                        <td style="padding: 15px;"><span class="sentiment-badge sentiment-negative"><?php echo htmlspecialchars($log['event_type']); ?></span></td>
                        <td style="padding: 15px; font-family: 'Courier New', monospace; font-size: 0.9rem;"><?php echo htmlspecialchars($log['description']); ?></td>
                        <td style="padding: 15px; color: var(--secondary-color);"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
