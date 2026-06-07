<?php
// includes/admin_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Double check admin or sub-admin role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Super-Admin', 'Admin', 'Sub-Admin'])) {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>CineAI - Admin Panel</title>
    <!-- Use same base css but we'll add admin specifics inline -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background-color: #050a15 !important; margin: 0; padding: 0; color: #f8fafc; font-family: 'Inter', sans-serif;}
        
        /* Ambient Blobs for Admin background */
        .admin-bg-blob1 { position: fixed; top: -20vh; left: -10vw; width: 60vw; height: 60vw; background: radial-gradient(circle, rgba(76,201,240,0.1) 0%, transparent 60%); z-index: -1; }
        .admin-bg-blob2 { position: fixed; bottom: -20vh; right: -10vw; width: 50vw; height: 50vw; background: radial-gradient(circle, rgba(239,68,68,0.05) 0%, transparent 60%); z-index: -1; }

        .admin-sidebar {
            width: 300px;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            background: rgba(15, 23, 42, 0.4); 
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-right: 1px solid rgba(255,255,255,0.05);
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }
        .admin-sidebar-header {
            padding: 30px 25px;
            font-size: 1.6rem;
            font-weight: 900;
            background: linear-gradient(135deg, #4CC9F0, #4361ee);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 20px;
            letter-spacing: -0.5px;
        }
        .admin-nav-item {
            padding: 16px 25px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            position: relative;
        }
        .admin-nav-item:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }
        .admin-nav-item.active {
            color: #fff;
            background: linear-gradient(90deg, rgba(76,201,240,0.15) 0%, transparent 100%);
            border-left: 4px solid var(--secondary-color);
            font-weight: 700;
        }
        
        .admin-wrapper {
            margin-left: 300px;
            padding: 50px 40px;
            min-height: 100vh;
            position: relative;
        }

        /* Elevating Glass Cards for Admin */
        .glass-card {
            background: rgba(30, 41, 59, 0.4) !important;
            border: 1px solid rgba(255,255,255,0.08) !important;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3) !important;
            backdrop-filter: blur(15px);
            border-radius: 16px !important;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .glass-card:hover { border-color: rgba(255,255,255,0.2) !important; }

        .stat-card { text-align: left; padding: 25px !important; position: relative; overflow: hidden; }
        .stat-card h2 { font-size: 2.5rem; margin-top: 15px; font-weight: 800; letter-spacing:-1px; }
        
        /* Modern Admin Tables */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
            border-radius: 12px;
        }
        .admin-table th {
            text-align: left;
            padding: 18px 20px;
            color: rgba(255,255,255,0.5);
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1.5px;
            font-weight: 700;
        }
        .admin-table td {
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            color: var(--text-main);
        }
        .admin-table tbody tr:hover { background: rgba(255,255,255,0.03); }
        .admin-table tbody tr:last-child td { border-bottom: none; }
    </style>
</head>
<body>
    <div class="admin-bg-blob1"></div>
    <div class="admin-bg-blob2"></div>

    <nav class="admin-sidebar">
        <div class="admin-sidebar-header">
            🎬 CineAI <span style="background: rgba(255,255,255,0.1); color:#fff; font-size:0.75rem; padding: 3px 8px; border-radius: 6px; font-weight:normal; vertical-align: middle; margin-left:10px;">PRO</span>
        </div>
        
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <a href="dashboard.php" class="admin-nav-item <?php echo $current_page=='dashboard.php'?'active':''; ?>">📊 오버뷰</a>
        <a href="moderation_queue.php" class="admin-nav-item <?php echo $current_page=='moderation_queue.php'?'active':''; ?>">🛡️ AI 검토 대기열</a>
        <?php if (in_array($_SESSION['role'], ['Super-Admin', 'Admin'])): ?>
            <a href="users.php" class="admin-nav-item <?php echo $current_page=='users.php'?'active':''; ?>">👥 유저 관리</a>
        <?php endif; ?>
        <a href="notices.php" class="admin-nav-item <?php echo $current_page=='notices.php'?'active':''; ?>">📢 시스템 공지</a>
        <a href="audit_logs.php" class="admin-nav-item <?php echo $current_page=='audit_logs.php'?'active':''; ?>">📝 보안 로그</a>
        
        <div style="margin-top: auto; padding: 25px;">
            <a href="../index.php" class="btn btn-outline" style="width: 100%; text-align: center; border-radius: 8px; font-size:0.9rem;">← 메인 사이트로</a>
        </div>
    </nav>

    <div class="admin-wrapper">
