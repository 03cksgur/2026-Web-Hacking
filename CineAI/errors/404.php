<?php
// errors/404.php
require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 60vh; text-align: center;">
    <div style="font-size: 8rem; font-weight: 800; color: var(--primary-color); opacity: 0.2; position: absolute; z-index: -1;">404</div>
    <div style="position: relative; z-index: 1;">
        <h1 style="font-size: 3rem; margin-bottom: 20px;">페이지를 찾을 수 없습니다</h1>
        <p style="color: var(--text-muted); font-size: 1.2rem; margin-bottom: 40px; max-width: 500px;">찾으시는 페이지가 이동되었거나 삭제되었을 수 있습니다.<br>보안 점검 결과 비정상적인 접근으로 판단되지 않았으니 걱정 마세요!</p>
        <div style="display: flex; gap: 15px; justify-content: center;">
            <a href="../index.php" class="btn btn-primary">홈으로 돌아가기</a>
            <button onclick="history.back()" class="btn btn-outline">이전 페이지</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
