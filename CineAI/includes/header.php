<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/csrf_helper.php';
$header_user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT username, profile_pic, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $header_user = $stmt->fetch();
    if ($header_user) {
        $_SESSION['role'] = $header_user['role'];
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineAI - Security Monitoring Center</title>
    <meta name="description" content="AI 기반 보안 관제 및 영화 분석 시스템">
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        // Immediate theme application to prevent flash
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.add('light-theme');
        }
    </script>
</head>
<body>
    <!-- Ambient Background -->
    <div class="ambient-bg matrix-bg">
        <div class="ambient-blob blob-1"></div>
        <div class="ambient-blob blob-2"></div>
        <div class="ambient-blob blob-3"></div>
    </div>
    <header>
        <div class="container nav-container">
            <a href="index.php" class="logo" style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.2rem;">🛡️</span> CineAI <span style="font-size: 0.8rem; opacity: 0.7; font-weight: normal; margin-left: 10px;">보안 모니터링 시스템</span>
            </a>
            <div style="display: flex; align-items: center; gap: 20px;">
                <button id="theme-toggle" class="btn btn-outline" style="padding: 5px 10px; font-size: 1.2rem; border-radius: 50%;">☀️</button>
                <nav class="nav-links">
                    <a href="index.php">홈</a>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Notification Bell -->
                    <div id="notif-wrapper" style="position: relative; cursor: pointer; display: flex; align-items: center; margin-right: 15px;">
                        <span style="font-size: 1.3rem;">🔔</span>
                        <span id="notif-badge" style="display: none; position: absolute; top: -5px; right: -5px; background: #ef4444; color: #fff; font-size: 0.7rem; padding: 2px 5px; border-radius: 10px; min-width: 15px; text-align: center;">0</span>
                        <div id="notif-dropdown" style="display: none; position: absolute; top: 35px; right: 0; width: 300px; max-height: 400px; background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 8px; z-index: 1001; overflow-y: auto; box-shadow: 0 10px 20px rgba(0,0,0,0.4);">
                            <div style="padding: 12px; border-bottom: 1px solid var(--glass-border); font-weight: bold; display: flex; justify-content: space-between;">
                                <span>알림</span>
                                <a href="notifications.php" style="font-size: 0.8rem; font-weight: normal; color: var(--secondary-color);">전체보기</a>
                            </div>
                            <div id="notif-list" style="padding: 10px;">
                                <div style="text-align: center; color: var(--text-muted); padding: 20px;">알림이 없습니다.</div>
                            </div>
                        </div>
                    </div>

                    <a href="watchlist.php">내 보관함</a>
                    <a href="profile.php?user_id=<?php echo $_SESSION['user_id']; ?>" style="color: var(--secondary-color); font-weight: bold;">내 활동 (작성글)</a>
                    <a href="review_write.php" class="btn btn-primary">리뷰 작성</a>

                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden; background: var(--secondary-color); border: 1px solid var(--glass-border);">
                            <?php if ($header_user && $header_user['profile_pic']): ?>
                                <img src="profile_download.php?file=<?php echo urlencode($header_user['profile_pic']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">👤</div>
                            <?php endif; ?>
                        </div>
                        <span style="font-size: 0.9rem; white-space: nowrap;"><strong><?php echo htmlspecialchars($header_user['username'] ?? $_SESSION['username']); ?></strong>님</span>
                    </div>
                    <?php if (in_array($_SESSION['role'], ['Super-Admin', 'Admin', 'Sub-Admin'])): ?>
                        <span class="sentiment-badge <?php echo ($_SESSION['role'] === 'Super-Admin') ? 'sentiment-positive' : 'sentiment-neutral'; ?>">
                            <?php 
                            if ($_SESSION['role'] === 'Super-Admin') echo '최고 관리자';
                            elseif ($_SESSION['role'] === 'Admin') echo '관리자';
                            elseif ($_SESSION['role'] === 'Sub-Admin') echo '부관리자'; 
                            ?>
                        </span>
                        <a href="admin/dashboard.php" class="btn btn-outline" style="border-color: #fbbf24; color: #fbbf24;">관리자 페이지</a>
                    <?php endif; ?>
                    <a href="settings.php" class="btn btn-outline" title="Settings">⚙️</a>
                    <a href="logout.php" class="btn btn-outline">로그아웃</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline">로그인</a>
                    <a href="register.php" class="btn btn-primary">회원가입</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    
    <!-- AI Chatbot Floating Button & Modal -->
    <div id="ai-chat-btn" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000; background: var(--primary-color); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.3); transition: transform 0.3s; border: 1px solid var(--glass-border);" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
        <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:28px; height:28px;">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            <path d="m12 6-1.5 3L8 10l2.5 1 1.5 3 1.5-3L16 10l-2.5-1Z">
                <animate attributeName="opacity" values="0.5;1;0.5" dur="1.5s" repeatCount="indefinite" />
            </path>
        </svg>
    </div>

    <div id="ai-chat-modal" style="display: none; position: fixed; bottom: 90px; right: 20px; width: 350px; hieght: 450px; background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--glass-border); border-radius: 12px; z-index: 1000; flex-direction: column; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
        <div style="padding: 15px; border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; background: var(--primary-color); border-radius: 12px 12px 0 0;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" style="width:18px; height:18px;">
                    <circle cx="12" cy="12" r="9"/>
                    <circle cx="12" cy="12" r="2" fill="#fff"/>
                </svg>
                <strong style="color: #fff;">Cine AI</strong>
            </div>
            <span onclick="document.getElementById('ai-chat-modal').style.display='none'" style="cursor: pointer; color: #fff;">✕</span>
        </div>
        <div id="chat-messages" style="flex: 1; min-height: 300px; max-height: 400px; overflow-y: auto; padding: 15px; font-size: 0.9rem;">
            <div style="margin-bottom: 15px;">안녕하세요! 어떤 영화를 찾으시나요? 제가 도와드릴게요!</div>
        </div>
        <div style="padding: 15px; border-top: 1px solid var(--glass-border); display: flex; gap: 8px;">
            <input type="text" id="chat-input" class="form-control" placeholder="영화 추천해줘..." style="flex: 1;">
            <button id="send-chat" class="btn btn-primary" style="padding: 8px 12px;">전송</button>
        </div>
    </div>

    <script>
        // Notification Logic
        const notifWrapper = document.getElementById('notif-wrapper');
        if (notifWrapper) {
            const notifDropdown = document.getElementById('notif-dropdown');
            const notifBadge = document.getElementById('notif-badge');
            const notifList = document.getElementById('notif-list');

            notifWrapper.onclick = (e) => {
                e.stopPropagation();
                const isVisible = notifDropdown.style.display === 'block';
                notifDropdown.style.display = isVisible ? 'none' : 'block';
                
                if (!isVisible) {
                    // Mark as read when opening
                    fetch('api/notifications_get.php?mark_read=1')
                        .then(res => res.json())
                        .then(data => {
                            notifBadge.style.display = 'none';
                        });
                }
            };

            document.addEventListener('click', () => {
                notifDropdown.style.display = 'none';
            });

            const updateNotifs = () => {
                fetch('api/notifications_get.php?limit=5')
                    .then(res => res.json())
                    .then(data => {
                        if (data.unread_count > 0) {
                            notifBadge.innerText = data.unread_count;
                            notifBadge.style.display = 'block';
                        } else {
                            notifBadge.style.display = 'none';
                        }

                        if (data.notifications.length > 0) {
                            notifList.innerHTML = data.notifications.map(n => `
                                <div style="padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.85rem; ${n.is_read == 0 ? 'background: rgba(76, 201, 240, 0.05);' : ''}">
                                    <a href="${n.link}" style="text-decoration: none; color: inherit;">
                                        ${n.message}
                                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 5px;">${n.created_at}</div>
                                    </a>
                                </div>
                            `).join('');
                        }
                    });
            };

            updateNotifs();
            setInterval(updateNotifs, 10000); // Check every 10 seconds
        }

        document.getElementById('ai-chat-btn').onclick = () => {
            const modal = document.getElementById('ai-chat-modal');
            modal.style.display = modal.style.display === 'none' ? 'flex' : 'none';
            if (modal.style.display === 'flex') {
                document.getElementById('chat-input').focus();
            }
        }

        // Enter key support
        document.getElementById('chat-input').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('send-chat').click();
            }
        });

        document.getElementById('send-chat').onclick = async () => {
            const input = document.getElementById('chat-input');
            const msg = input.value.trim();
            if(!msg) return;

            const chatDiv = document.getElementById('chat-messages');
            chatDiv.innerHTML += `<div style="text-align: right; margin-bottom: 15px;"><span style="background: var(--glass-bg); padding: 8px 12px; border-radius: 8px; border: 1px solid var(--glass-border);">${msg}</span></div>`;
            input.value = '';

            const loadingId = 'loading-' + Date.now();
            chatDiv.innerHTML += `<div id="${loadingId}" class="typing-indicator" style="margin-bottom: 15px;"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>`;
            chatDiv.scrollTop = chatDiv.scrollHeight;

            try {
                const res = await fetch('api/ai_chatbot.php', {
                    method: 'POST',
                    body: JSON.stringify({query: msg})
                });
                const data = await res.json();
                document.getElementById(loadingId).remove();
                chatDiv.innerHTML += `<div style="margin-bottom: 15px;"><span style="background: rgba(76, 201, 240, 0.1); padding: 8px 12px; border-radius: 8px; display: inline-block; white-space: pre-wrap;">${data.response}</span></div>`;
                chatDiv.scrollTop = chatDiv.scrollHeight;
            } catch(e) {
                document.getElementById(loadingId).innerHTML = "오류가 발생했습니다.";
            }
        }
    </script>
    <script src="assets/js/theme-toggle.js"></script>
    
    <!-- Premium UI Injections -->
    <div id="toast-container"></div>
    <button class="btn-to-top" id="btn-to-top" title="Go to top">↑</button>

    <script>
        // Premium Toast Notification
        window.showToast = function(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icon = type === 'success' ? '✅' : (type === 'error' ? '❌' : 'ℹ️');
            toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'toast-in 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) reverse forwards';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        };

        // Premium Back-to-Top
        const topBtn = document.getElementById('btn-to-top');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                topBtn.classList.add('visible');
            } else {
                topBtn.classList.remove('visible');
            }
        });
        topBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>

    <main class="container">
