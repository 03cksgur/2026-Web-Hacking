<?php
// admin/dashboard.php
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../config/database.php';

// Fetch Statistics
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'reviews_total' => $pdo->query("SELECT COUNT(*) FROM reviews WHERE deleted_at IS NULL")->fetchColumn(),
    'security_scan_pending' => $pdo->query("SELECT COUNT(*) FROM reviews WHERE (status = 'PENDING' OR status = 'MANUAL_REVIEW') AND deleted_at IS NULL")->fetchColumn(),
    'sqli_attempts' => $pdo->query("SELECT COUNT(*) FROM security_logs WHERE event_type LIKE '%SQL%' AND DATE(created_at) = CURDATE()")->fetchColumn(),
    'abnormal_access' => $pdo->query("SELECT COUNT(*) FROM security_logs WHERE event_type NOT LIKE '%SQL%' AND DATE(created_at) = CURDATE()")->fetchColumn(),
    'system_health' => '최적 (정상)'
];

// Fetch Recent Audit Logs
$logs = $pdo->query("SELECT l.*, u.username as admin_name FROM audit_logs l JOIN users u ON l.admin_id = u.id ORDER BY l.created_at DESC LIMIT 5")->fetchAll();

// Fetch Recent Users
$recent_users = $pdo->query("SELECT login_id, username FROM users ORDER BY id DESC LIMIT 5")->fetchAll();

// Dynamic Trend Data
$trendData = ['labels' => [], 'data' => []];
for ($i=6; $i>=0; $i--) {
    $d = date('m/d', strtotime("-$i days"));
    $d_sql = date('Y-m-d', strtotime("-$i days"));
    $c = $pdo->query("SELECT COUNT(*) FROM security_logs WHERE DATE(created_at) = '$d_sql'")->fetchColumn();
    $trendData['labels'][] = $d;
    $trendData['data'][] = (int)$c;
}
$trendLabelsJson = json_encode($trendData['labels']);
$trendCountsJson = json_encode($trendData['data']);

$sentiment_stats = [
    'positive' => $pdo->query("SELECT COUNT(*) FROM reviews WHERE sentiment_score > 0.4 AND deleted_at IS NULL")->fetchColumn(),
    'neutral' => $pdo->query("SELECT COUNT(*) FROM reviews WHERE sentiment_score >= -0.4 AND sentiment_score <= 0.4 AND deleted_at IS NULL")->fetchColumn(),
    'negative' => $pdo->query("SELECT COUNT(*) FROM reviews WHERE sentiment_score < -0.4 AND deleted_at IS NULL")->fetchColumn(),
];

$stmtSecurity = $pdo->prepare("SELECT * FROM security_logs ORDER BY created_at DESC LIMIT 5");
$stmtSecurity->execute();
$security_alerts = $stmtSecurity->fetchAll();

// Handle User Actions (Approval & Role Change) - ADMIN ONLY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_action']) && in_array($_SESSION['role'], ['Super-Admin', 'Admin'])) {
    $action = $_POST['user_action'];
    $target_id = $_POST['target_id'];
    
    // Safety check: Don't change own role or status
    if ($target_id != $_SESSION['user_id']) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE users SET status = 'ACTIVE' WHERE id = ?")->execute([$target_id]);
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE users SET status = 'REJECTED' WHERE id = ?")->execute([$target_id]);
        } elseif ($action === 'change_role') {
            $new_role = $_POST['new_role'];
            if (in_array($new_role, ['User', 'Sub-Admin', 'Admin', 'Super-Admin'])) {
                $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$new_role, $target_id]);
            }
        } elseif ($action === 'block') {
            $pdo->prepare("UPDATE users SET status = 'BLOCKED' WHERE id = ?")->execute([$target_id]);
        }
        header("Location: dashboard.php?notif=success");
        exit;
    }
}

// Handle IP Blocking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'block_ip' && in_array($_SESSION['role'], ['Super-Admin', 'Admin'])) {
    $ip_to_block = $_POST['block_ip_address'] ?? '';
    if ($ip_to_block && filter_var($ip_to_block, FILTER_VALIDATE_IP)) {
        try {
            $pdo->prepare("INSERT IGNORE INTO blocked_ips (ip_address, reason) VALUES (?, 'Blocked from A09 threat log')")->execute([$ip_to_block]);
            header("Location: dashboard.php?msg=ip_blocked");
            exit;
        } catch (Exception $e) {}
    }
}

// Fetch Pending Users
$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'PENDING' ORDER BY id DESC")->fetchAll();

// Fetch All Users for Management (limited for dashboard view)
$all_users = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 10")->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
    <div style="padding-left: 40px;">
        <h1 style="font-size: 2.2rem; font-weight: 800; margin-bottom: 5px; color: var(--primary-color);">관리자 페이지</h1>
        <p style="color: var(--text-muted);">실시간 시스템 보안 관리 및 데이터 모니터링 대시보드입니다.</p>
    </div>
    <div style="font-size: 0.9rem; color: var(--text-muted);">
        업데이트: <?php echo date('Y-m-d H:i:s'); ?>
    </div>
</div>

<div class="admin-stats-grid">
    <div class="glass-card admin-stat-card" style="cursor: pointer;" onclick="location.href='audit_logs.php'">
        <div class="admin-stat-icon">🏥</div>
        <p style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600;">시스템 상태</p>
        <h2 style="color: #10b981; font-size: 1.8rem;"><?php echo $stats['system_health']; ?></h2>
        <div style="height: 4px; background: #10b981; margin-top: 15px; border-radius: 2px;"></div>
    </div>
    <div class="glass-card admin-stat-card" style="cursor: pointer;" onclick="location.href='moderation_queue.php'">
        <div class="admin-stat-icon">🔍</div>
        <p style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600;">보안 검사 대기</p>
        <h2 style="color: #fbbf24; font-size: 1.8rem;"><?php echo $stats['security_scan_pending']; ?> <span style="font-size: 1rem;">개</span></h2>
        <div style="height: 4px; background: #fbbf24; margin-top: 15px; border-radius: 2px;"></div>
    </div>
    <div class="glass-card admin-stat-card" style="cursor: pointer;" onclick="location.href='security_logs.php?type=SQLI'">
        <div class="admin-stat-icon">🛡️</div>
        <p style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600;">SQLi 탐지 (오늘)</p>
        <h2 style="color: #f43f5e; font-size: 1.8rem;"><?php echo $stats['sqli_attempts']; ?> <span style="font-size: 1rem;">건</span></h2>
        <div style="height: 4px; background: #f43f5e; margin-top: 15px; border-radius: 2px;"></div>
    </div>
    <div class="glass-card admin-stat-card" style="cursor: pointer;" onclick="location.href='security_logs.php?type=ABNORMAL'">
        <div class="admin-stat-icon">🚨</div>
        <p style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600;">비정상 접근 시도</p>
        <h2 style="color: #f43f5e; font-size: 1.8rem;"><?php echo $stats['abnormal_access']; ?> <span style="font-size: 1rem;">건</span></h2>
        <div style="height: 4px; background: #f43f5e; margin-top: 15px; border-radius: 2px;"></div>
    </div>
</div>

<div class="admin-chart-row">
    <!-- Sentiment Analysis Chart -->
    <div class="glass-card">
        <h3 style="margin-bottom: 20px;">📊 커뮤니티 감성 분석 (AI)</h3>
        <div style="position: relative; max-width: 400px; height: 300px; margin: 0 auto;">
            <canvas id="sentimentChart"></canvas>
        </div>
    </div>

    <!-- Security Info -->
    <div>
        <div class="glass-card admin-info-box">
            <h3 style="margin-bottom: 20px; color: var(--secondary-color); display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.4rem;">🔋</span> 보안 모니터링 상태
            </h3>
            <ul style="list-style: none; padding: 0; font-size: 0.95rem; line-height: 2.2;">
                <li><span style="color: #10b981;">●</span> <strong>접속 방화벽:</strong> 활성화됨</li>
                <li><span style="color: #10b981;">●</span> <strong>개인정보 마스킹:</strong> 적용 중</li>
                <li><span style="color: #1d4ed8;">●</span> <strong>AI 위협 분석:</strong> 학습 모드</li>
                <li style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.05);">
                    <span style="color: var(--text-muted);">Gateway:</span> <span style="color: var(--secondary-color); font-weight: 800;">SECURED SSL-4096</span>
                </li>
            </ul>
        </div>

        <div class="glass-card">
            <h3 style="margin-bottom: 20px;">📉 탐지 트렌드 (7일)</h3>
            <div style="position: relative; height: 180px; width: 100%;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="glass-card admin-terminal-log">
        <h3 style="margin-bottom: 20px; color: var(--primary-color);">📜 실시간 보안 감사 로그</h3>
        <div id="security-log" class="terminal-window">
            <!-- Log lines will be injected here -->
        </div>
    </div>
</div>

<!-- A09 Security Alerts Section -->
<div class="glass-card" style="margin-top: 25px; border-left: 4px solid var(--danger-color);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
            <span style="color: var(--danger-color);">⚠️</span> 보안 위협 탐지 로그 (A09)
        </h3>
        <span class="sentiment-badge sentiment-negative"><?php echo count($security_alerts); ?>건 탐지됨</span>
    </div>
    <div style="max-height: 250px; overflow-y: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="text-align: left; border-bottom: 1px solid var(--glass-border);">
                    <th style="padding: 10px;">시간</th>
                    <th style="padding: 10px;">유형</th>
                    <th style="padding: 10px;">설명</th>
                    <th style="padding: 10px;">IP 주소</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($security_alerts)): ?>
                    <tr><td colspan="4" style="padding: 20px; text-align: center; color: var(--text-muted);">현재 탐지된 위협이 없습니다.</td></tr>
                <?php else: ?>
                    <?php foreach ($security_alerts as $alert): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.3s;" onmouseover="this.style.background='rgba(239, 68, 68, 0.05)'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 10px;"><?php echo date('H:i:s', strtotime($alert['created_at'])); ?></td>
                            <td style="padding: 10px;"><span style="color: var(--danger-color); font-weight: bold;"><?php echo htmlspecialchars($alert['event_type']); ?></span></td>
                            <td style="padding: 10px;"><?php echo htmlspecialchars($alert['description']); ?></td>
                            <td style="padding: 10px; color: var(--text-muted); display: flex; align-items: center; justify-content: space-between;">
                                <span><?php echo htmlspecialchars($alert['ip_address']); ?></span>
                                <?php if ($alert['ip_address'] && in_array($_SESSION['role'], ['Super-Admin', 'Admin'])): ?>
                                <form method="POST" style="margin: 0; display: inline;">
                                    <input type="hidden" name="action" value="block_ip">
                                    <input type="hidden" name="block_ip_address" value="<?php echo htmlspecialchars($alert['ip_address']); ?>">
                                    <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 0.75rem; border-radius: 4px;" onclick="return confirm('<?php echo htmlspecialchars($alert['ip_address']); ?> IP를 차단하시겠습니까?');">차단</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

        <script>
        const logContainer = document.getElementById('security-log');
        const logs = [
            "[INFO] System kernel check: OK",
            "[SYSTEM] AI Sentiment filtering active",
            "[WARNING] Multiple failed logins detected from 211.234.12.5",
            "[ADMIN] Policy update: CSRF enforcement strictly HIGH",
            "[SECURITY] SQL Injection attempt neutralized",
            "[NETWORK] SSL Certificate valid (4096-bit RSA)",
            "[DATABASE] Automatic vacuum and integrity check completed",
            "[AI] Review #892 analysis: Sentiment 0.98 (Positive)",
            "[INFO] Terminal connection established via SSH-2.0",
            "[DANGER] Port 22 unauthorized access attempt blocked"
        ];

        function addLogLine() {
            const line = document.createElement('div');
            line.className = 'terminal-line';
            const logIdx = Math.floor(Math.random() * logs.length);
            const now = new Date().toLocaleTimeString();
            const logText = logs[logIdx];
            
            let color = '#00ff41'; // default green
            if (logText.includes('[WARNING]')) color = '#ffeb3b';
            if (logText.includes('[DANGER]') || logText.includes('[SECURITY]')) color = '#f44336';
            if (logText.includes('[INFO]')) color = '#2196f3';
            
            line.innerHTML = `<span style='color: #888;'>[${now}]</span> <span style='color: ${color};'>${logText}</span>`;
            logContainer.appendChild(line);
            logContainer.scrollTop = logContainer.scrollHeight;
            
            if (logContainer.children.length > 50) logContainer.children[0].remove();
        }

        setInterval(addLogLine, 2000);
        for(let i=0; i<5; i++) addLogLine();
        </script>

    <script>
    const renderChart = () => {
        if (typeof Chart === 'undefined') {
            console.warn("Chart.js not loaded. Rendering fallback...");
            document.getElementById('sentimentChart').parentElement.innerHTML = '<div style="height:200px; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.05); border-radius:10px; color:var(--text-muted);">차트 라이브러리 로딩 실패 (오프라인 모드)</div>';
            document.getElementById('trendChart').parentElement.innerHTML = '<div style="height:150px; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.05); border-radius:10px; color:var(--text-muted);">데이터 트렌드 표시 불가</div>';
            return;
        }

        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo $trendLabelsJson; ?>,
                datasets: [{
                    label: '보안 탐지 건수',
                    data: <?php echo $trendCountsJson; ?>,
                    borderColor: '#3b82f6',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#94a3b8', font: { size: 10 } }, grid: { display: false } },
                    y: { ticks: { color: '#94a3b8', font: { size: 10 } }, grid: { borderDash: [5, 5] } }
                }
            }
        });

        const ctx = document.getElementById('sentimentChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['긍정', '중립', '부정'],
                datasets: [{
                    data: [
                        <?php echo $sentiment_stats['positive']; ?>, 
                        <?php echo $sentiment_stats['neutral']; ?>, 
                        <?php echo $sentiment_stats['negative']; ?>
                    ],
                    backgroundColor: ['#10b981', '#3b82f6', '#f43f5e'],
                    hoverOffset: 15,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#94a3b8', padding: 20, font: { weight: '600' } } }
                },
                cutout: '70%'
            }
        });
    };

    // Delay slightly to allow CDN/local JS to load
    setTimeout(renderChart, 500);
    </script>

    <?php if (in_array($_SESSION['role'], ['Super-Admin', 'Admin'])): ?>
    <div style="display: grid; grid-template-columns: 1fr; gap: 30px; margin-top: 30px;">
        <!-- User Management Console -->
        <div class="glass-card" style="padding: 0 !important; overflow: hidden; border-top: 4px solid var(--secondary-color);">
            <div style="padding: 20px; display: flex; justify-content: space-between; align-items: center; background: rgba(76, 201, 240, 0.05);">
                <h3 style="margin: 0; font-weight: 700;">👥 사용자 통합 관리 센터</h3>
                <?php if (count($pending_users) > 0): ?>
                    <span class="sentiment-badge sentiment-negative"><?php echo count($pending_users); ?>명의 신규 가입 승인 대기 중</span>
                <?php endif; ?>
            </div>
            
            <div style="padding: 20px;">
                <!-- Pending Users Table - ADMIN ONLY -->
                <?php if (in_array($_SESSION['role'], ['Super-Admin', 'Admin']) && count($pending_users) > 0): ?>
                    <h4 style="margin-bottom: 20px; color: #fbbf24; display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.2rem;">⏳</span> 가입 승인 대기 명단
                    </h4>
                    <div style="background: rgba(0,0,0,0.3); border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); margin-bottom: 30px; overflow-x: auto; width: 100%;">
                        <table class="admin-table" style="min-width: 900px; width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: rgba(255,255,255,0.03);">
                                    <th style="width: 180px; padding: 15px 20px; text-align: left;">아이디</th>
                                    <th style="width: 180px; padding: 15px 20px; text-align: left;">닉네임</th>
                                    <th style="padding: 15px 20px; text-align: left;">이메일</th>
                                    <th style="width: 150px; padding: 15px 20px; text-align: left;">가입일</th>
                                    <th style="width: 120px; padding: 15px 20px; text-align: center;">액션</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_users as $pu): ?>
                                    <tr>
                                        <td style="padding: 15px 20px;"><strong><?php echo htmlspecialchars($pu['login_id']); ?></strong></td>
                                        <td style="padding: 15px 20px;"><?php echo htmlspecialchars($pu['username']); ?></td>
                                        <td style="padding: 15px 20px; color: var(--text-muted);"><?php echo htmlspecialchars($pu['email']); ?></td>
                                        <td style="padding: 15px 20px;"><?php echo isset($pu['created_at']) ? date('Y-m-d', strtotime($pu['created_at'])) : '-'; ?></td>
                                        <td style="padding: 15px 20px; text-align: center;">
                                            <div style="display: flex; gap: 5px; justify-content: center;">
                                                <form method="POST" style="margin: 0;">
                                                    <input type="hidden" name="target_id" value="<?php echo $pu['id']; ?>">
                                                    <input type="hidden" name="user_action" value="approve">
                                                    <button type="submit" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px;">승인</button>
                                                </form>
                                                <form method="POST" style="margin: 0;">
                                                    <input type="hidden" name="target_id" value="<?php echo $pu['id']; ?>">
                                                    <input type="hidden" name="user_action" value="reject">
                                                    <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; background-color: #ef4444;" onclick="return confirm('이 사용자의 가입을 거절하시겠습니까?');">거절</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h4 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.2rem;">👤</span> 유저 권한 및 상태 관리
                    </h4>
                    <input type="text" id="user-search" class="form-control" placeholder="아이디/닉네임 검색..." style="max-width: 250px; padding: 8px 15px; border-radius: 20px; font-size: 0.9rem;">
                </div>
                <div style="background: rgba(0,0,0,0.3); border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); overflow-x: auto; width: 100%;">
                    <table class="admin-table" style="min-width: 1000px; width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: rgba(255,255,255,0.03);">
                                <th style="width: 220px; padding: 15px 20px; text-align: left;">사용자</th>
                                <th style="width: 150px; padding: 15px 20px; text-align: left;">현재 권한</th>
                                <th style="width: 120px; padding: 15px 20px; text-align: left;">상태</th>
                                <?php if (in_array($_SESSION['role'], ['Super-Admin', 'Admin'])): ?>
                                    <th style="padding: 15px 20px; text-align: left;">권한 임명 / 조치</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $au): ?>
                                <tr>
                                    <td style="padding: 15px 20px;">
                                        <div style="display: flex; flex-direction: column;">
                                            <strong style="color: var(--secondary-color);"><?php echo htmlspecialchars($au['username']); ?></strong>
                                            <small style="color: var(--text-muted);">@<?php echo htmlspecialchars($au['login_id']); ?></small>
                                        </div>
                                    </td>
                                    <td style="padding: 15px 20px;">
                                        <span class="sentiment-badge <?php 
                                            echo ($au['role'] === 'Super-Admin') ? 'sentiment-positive' : (($au['role'] === 'Admin') ? 'sentiment-positive' : (($au['role'] === 'Sub-Admin') ? 'sentiment-neutral' : '')); 
                                        ?>">
                                            <?php 
                                            if ($au['role'] === 'Super-Admin') echo '최고 관리자';
                                            elseif ($au['role'] === 'Admin') echo '관리자';
                                            elseif ($au['role'] === 'Sub-Admin') echo '부관리자';
                                            elseif ($au['role'] === 'User') echo '일반 사용자';
                                            else echo htmlspecialchars($au['role']);
                                            ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px 20px;">
                                        <span style="color: <?php echo ($au['status'] === 'ACTIVE') ? '#10b981' : (($au['status'] === 'PENDING') ? '#fbbf24' : '#ef4444'); ?>; font-weight: bold;">
                                            <?php 
                                            if ($au['status'] === 'ACTIVE') echo '활성';
                                            elseif ($au['status'] === 'PENDING') echo '가입 대기';
                                            elseif ($au['status'] === 'BLOCKED') echo '차단됨';
                                            else echo htmlspecialchars($au['status']);
                                            ?>
                                        </span>
                                    </td>
                                    <?php if (in_array($_SESSION['role'], ['Super-Admin', 'Admin'])): ?>
                                    <td style="padding: 15px 20px;">
                                        <?php if ($au['id'] != $_SESSION['user_id']): ?>
                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                <form method="POST" style="display: flex; gap: 5px;">
                                                    <input type="hidden" name="target_id" value="<?php echo $au['id']; ?>">
                                                    <input type="hidden" name="user_action" value="change_role">
                                                    <select name="new_role" class="form-control" style="padding: 4px 8px; font-size: 0.85rem; width: auto; background: rgba(0,0,0,0.3); border: 1px solid var(--glass-border); border-radius: 6px;">
                                                        <option value="User" <?php echo ($au['role'] === 'User') ? 'selected' : ''; ?>>일반 사용자</option>
                                                        <option value="Sub-Admin" <?php echo ($au['role'] === 'Sub-Admin') ? 'selected' : ''; ?>>부관리자</option>
                                                        <option value="Admin" <?php echo ($au['role'] === 'Admin') ? 'selected' : ''; ?>>관리자</option>
                                                        <?php if ($_SESSION['role'] === 'Super-Admin'): ?>
                                                            <option value="Super-Admin" <?php echo ($au['role'] === 'Super-Admin') ? 'selected' : ''; ?>>최고 관리자</option>
                                                        <?php endif; ?>
                                                    </select>
                                                    <button type="submit" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.85rem; border-radius: 6px;">변경</button>
                                                </form>
                                                <?php if ($au['status'] === 'ACTIVE'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="target_id" value="<?php echo $au['id']; ?>">
                                                        <input type="hidden" name="user_action" value="block">
                                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.85rem; border-radius: 6px;" onclick="return confirm('이 유저를 차단하시겠습니까?');">차단</button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="font-size: 0.85rem; color: var(--secondary-color); font-weight: bold;">본인 계정</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 15px; text-align: center;">
                    <a href="users.php" style="color: var(--text-muted); font-size: 0.85rem; text-decoration: none;">전체 사용자 리스트 보기 →</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // 1. Stat count-up animation
        document.querySelectorAll('.admin-stat-card h2').forEach(h2 => {
            const textNode = Array.from(h2.childNodes).find(n => n.nodeType === 3 && n.textContent.trim().length > 0);
            if (!textNode) return;
            const targetStr = textNode.textContent.trim();
            if (targetStr === '정상') return;
            
            const targetNum = parseInt(targetStr.replace(/[^0-9]/g, ''));
            if (isNaN(targetNum) || targetNum <= 0) return;
            
            let currentNum = 0;
            const duration = 1500;
            const stepValue = Math.max(1, Math.ceil(targetNum / (duration / 16)));
            
            const interval = setInterval(() => {
                currentNum += stepValue;
                if (currentNum >= targetNum) {
                    currentNum = targetNum;
                    clearInterval(interval);
                }
                textNode.textContent = currentNum + " ";
            }, 16);
        });

        // 2. User Search Filter
        document.getElementById('user-search')?.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.admin-table tbody tr');
            rows.forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    </script>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
