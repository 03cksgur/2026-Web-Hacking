<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<div style="max-width: 1000px; margin: 0 auto; padding: 40px 20px;">
    <div style="text-align: center; margin-bottom: 50px;">
        <h1 style="font-size: 3rem; margin-bottom: 10px; background: linear-gradient(45deg, #4cc9f0, #4361ee); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">당신을 위한 AI 맞춤 추천</h1>
        <p style="color: var(--text-muted); font-size: 1.2rem;">CineAI가 분석한 당신의 취향입니다.</p>
    </div>

    <div id="loading-recommendations" style="text-align: center; padding: 100px 0;">
        <div class="spinner" style="width: 50px; height: 50px; border: 5px solid var(--glass-border); border-top-color: var(--primary-color); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
        <p>AI가 당신의 리뷰와 보관함을 분석 중입니다...</p>
    </div>

    <div id="recommendations-container" class="review-grid" style="display: none;">
        <!-- Recommendations will be injected here -->
    </div>

    <div id="no-history-msg" style="display: none; text-align: center; padding: 60px; background: var(--glass-bg); border-radius: 12px; border: 1px solid var(--glass-border);">
        <h3 style="margin-bottom: 15px;">앗! 분석할 데이터가 부족해요.</h3>
        <p style="margin-bottom: 25px;">영화 리뷰를 작성하거나 관심 화를 보관함에 담아주시면<br>CineAI가 당신의 취향을 정확하게 파악할 수 있습니다.</p>
        <a href="index.php" class="btn btn-primary">영화 둘러보기</a>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.recommend-card {
    animation: fadeInUp 0.5s ease-out forwards;
    opacity: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const res = await fetch('api/ai_recommend.php');
        const data = await res.json();
        
        document.getElementById('loading-recommendations').style.display = 'none';

        if (data.recommendations && data.recommendations.length > 0) {
            const container = document.getElementById('recommendations-container');
            container.style.display = 'grid';
            
            data.recommendations.forEach((rec, index) => {
                const card = document.createElement('div');
                card.className = 'glass-card recommend-card';
                card.style.animationDelay = (index * 0.2) + 's';
                card.style.padding = '30px';
                card.style.display = 'flex';
                card.style.flexDirection = 'column';
                card.style.justifyContent = 'space-between';
                
                card.innerHTML = `
                    <div>
                        <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 15px; color: var(--secondary-color);">✨ ${rec.title}</div>
                        <p style="line-height: 1.6; color: var(--text-main);">${rec.reason}</p>
                    </div>
                    <div style="margin-top: 30px;">
                        <a href="https://www.themoviedb.org/search?query=${encodeURIComponent(rec.title)}" target="_blank" class="btn btn-outline" style="width: 100%; text-align: center;">상세 정보 보기</a>
                    </div>
                `;
                container.appendChild(card);
            });
        } else {
            document.getElementById('no-history-msg').style.display = 'block';
        }
    } catch (e) {
        document.getElementById('loading-recommendations').innerHTML = '<p style="color: var(--danger-color);">추천 정보를 가져오는 중 오류가 발생했습니다.</p>';
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
