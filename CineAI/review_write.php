<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf_helper.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/upload_helper.php';
require_once __DIR__ . '/api/gemini.php';
require_once __DIR__ . '/includes/moderation_engine.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$guard = new CineAIGuard($pdo);

$pre_tmdb_id = $_GET['tmdb_id'] ?? '';
$pre_title = $_GET['title'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die(); // A01/A06 CSRF Protection
    $movie_title = trim($_POST['movie_title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($movie_title) || empty($content)) {
        $error = "영화 제목과 리뷰 내용을 입력해주세요.";
    } elseif (mb_strlen($content, 'UTF-8') > 1000) {
        $error = "리뷰 내용은 최대 1000자를 초과할 수 없습니다. 보안 정책에 의해 차단되었습니다.";
    } else {
        $tmdb_id = !empty($_POST['tmdb_id']) ? (int)$_POST['tmdb_id'] : null;
        $poster_file = null;
        
        // If TMDB poster is provided, download it
        // A10: SSRF Protection - strictly validate external poster domain
        if (!empty($_POST['tmdb_poster_path'])) {
            $tmdb_path = $_POST['tmdb_poster_path'];
            // Basic validation of path structure
            if (preg_match('/^\/[a-zA-Z0-9\._-]+\.[a-z]{3,4}$/', $tmdb_path)) {
                $remote_url = "https://image.tmdb.org/t/p/w500" . $tmdb_path;
                $file_extension = pathinfo($tmdb_path, PATHINFO_EXTENSION) ?: 'jpg';
                $new_name = 'uploads/' . uniqid() . '.' . $file_extension;
                
                // Fetch content safely
                $img_content = @file_get_contents($remote_url);
                if ($img_content && file_put_contents($new_name, $img_content)) {
                    $poster_file = $new_name;
                }
            }
        }

        if (!$poster_file && isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $poster_file = handlePosterUpload($_FILES['poster']);
            if (!$poster_file) {
                $error = "포스터 업로드에 실패했습니다.";
            }
        }


        if (!$error) {
            // Rate Limiting Check
            if (!$guard->checkRateLimit()) {
                $error = "도배 방지를 위해 잠시 후 다시 시도해주세요. (분당 최대 5건)";
            } else {
                // Moderation Check
                $mod_result = $guard->moderate('review', $content, $poster_file ? __DIR__ . '/' . $poster_file : null);
            
                if ($mod_result['status'] === 'BLOCKED') {
                    $error = "부적절한 콘텐츠가 포함되어 있어 게시가 차단되었습니다: " . $mod_result['reason'];
                } else {
                    $star_rating = (int)($_POST['star_rating'] ?? 5);
                    $ai_result = analyzeSentimentWithGemini($content);

                    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, tmdb_id, movie_title, content, poster_file, sentiment_score, summary, is_spoiler, star_rating, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$_SESSION['user_id'], $tmdb_id, $movie_title, $content, $poster_file, $ai_result['sentiment_score'], $ai_result['summary'], $ai_result['is_spoiler'], $star_rating, $mod_result['status']])) {
                        
                        $review_id = $pdo->lastInsertId();
                        $tags_raw = $_POST['hashtags'] ?? '';
                        $tag_array = array_filter(array_map('trim', explode(',', $tags_raw)));
                        
                        foreach ($tag_array as $tag_text) {
                            $tag_text = preg_replace('/^#/', '', $tag_text); // remove leading #
                            if (empty($tag_text)) continue;
                            
                            $stmt_tag = $pdo->prepare("SELECT id FROM hashtags WHERE tag_name = ?");
                            $stmt_tag->execute([$tag_text]);
                            $tag_id = $stmt_tag->fetchColumn();
                            
                            if (!$tag_id) {
                                $pdo->prepare("INSERT INTO hashtags (tag_name) VALUES (?)")->execute([$tag_text]);
                                $tag_id = $pdo->lastInsertId();
                            }
                            // Insert mapping
                            try {
                                $pdo->prepare("INSERT INTO review_hashtags (review_id, hashtag_id) VALUES (?, ?)")->execute([$review_id, $tag_id]);
                            } catch (Exception $e) {} // Ignore duplicate inserts
                        }

                        if ($mod_result['status'] === 'MANUAL_REVIEW') {
                            echo "<script>alert('리뷰가 등록되었으나, 운영자의 검토가 필요하여 검토 후 공개됩니다.'); window.location.href='index.php';</script>";
                            exit;
                        }
                        header("Location: index.php");
                        exit;
                    } else {
                        $error = "리뷰 등록에 실패했습니다.";
                    }
                }
            }
        }
    }
}
?>

<div style="max-width: 800px; margin: 0 auto; margin-top: 40px;">
    <div class="glass-card">
        <h2 style="margin-bottom: 30px;">새 리뷰 작성</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="review_write.php" enctype="multipart/form-data">
            <?php echo get_csrf_input(); ?>
            <div class="form-group">
                <label for="movie_title">영화 제목</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="movie_title" name="movie_title" class="form-control" required value="<?php echo htmlspecialchars($pre_title ?: ($_POST['movie_title'] ?? '')); ?>" placeholder="영화를 선택하거나 검색하세요">
                    <button type="button" id="open-discovery-btn" class="btn btn-primary" style="white-space: nowrap; flex-shrink: 0;">✨ 영화 라이브러리</button>
                </div>
                <input type="hidden" id="tmdb_id" name="tmdb_id" value="<?php echo htmlspecialchars($pre_tmdb_id); ?>">
                <input type="hidden" id="tmdb_poster_path" name="tmdb_poster_path">
                
                <!-- Selected Movie Preview -->
                <div id="selected-movie-preview" style="display: none; margin-top: 15px; padding: 15px; background: rgba(76, 201, 240, 0.05); border: 1px solid var(--secondary-color); border-radius: 8px; align-items: center; gap: 15px;">
                    <div id="preview-poster" style="width: 60px; height: 90px; border-radius: 4px; overflow: hidden; background: #000;"></div>
                    <div>
                        <div id="preview-title" style="font-weight: bold; font-size: 1.1rem; color: #fff;"></div>
                        <div id="preview-date" style="font-size: 0.85rem; color: var(--text-muted);"></div>
                    </div>
                    <button type="button" class="btn btn-outline" style="margin-left: auto; padding: 5px 10px; font-size: 0.8rem;" onclick="resetSelection()">변경</button>
                </div>
            </div>



            <div class="form-group">
                <label for="star_rating">별점 (My Rating)</label>
                <select id="star_rating" name="star_rating" class="form-control">
                    <option value="5">⭐⭐⭐⭐⭐ (5점)</option>
                    <option value="4">⭐⭐⭐⭐ (4점)</option>
                    <option value="3">⭐⭐⭐ (3점)</option>
                    <option value="2">⭐⭐ (2점)</option>
                    <option value="1">⭐ (1점)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="poster">포스터 이미지 업로드 (선택)</label>
                <input type="file" id="poster" name="poster" class="form-control" accept="image/*">
            </div>

            <div class="form-group">
                <label>추천 해시태그 / 장르 (클릭해서 쉽게 추가)</label>
                <?php
                // 실제 영화 리뷰 앱에서 가장 흔하게 쓰이는 핵심 12가지 장르/테마 해시태그로 선별
                $all_tags = ['액션', '로맨틱코미디', '스릴러', 'SF', '공포', '애니메이션', '인생영화', '킬링타임용', '명작', '눈물버튼', '반전소름', '넷플릭스추천'];
                ?>
                <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:15px; padding: 12px; background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px solid var(--glass-border);">
                    <?php foreach($all_tags as $t): ?>
                        <button type="button" class="btn btn-outline" style="padding: 4px 12px; font-size: 0.85rem; border-radius: 20px; white-space: nowrap;" onclick="addTag('<?php echo htmlspecialchars($t); ?>')">#<?php echo htmlspecialchars($t); ?></button>
                    <?php endforeach; ?>
                </div>
                <input type="text" id="hashtags" name="hashtags" class="form-control" placeholder="클릭하여 추가하거나 쉼표(,)로 구분해서 자유롭게 작성 (예: 판타지, 최고예요)">
            </div>

            <div class="form-group" style="position: relative;">
                <div style="display: flex; justify-content: space-between; align-items: baseline;">
                    <label for="content">리뷰 내용</label>
                    <span id="char-counter" style="font-size: 0.85rem; color: var(--text-muted);">0 / 1000</span>
                </div>
                <textarea id="content" name="content" class="form-control" rows="10" required maxlength="1000" placeholder="이 영화에 대한 느낌을 자세히 적어주세요. AI가 긍정/부정 감성을 분석합니다! (공백 포함 최대 1000자)"><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
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
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">리뷰 등록하기 (AI 분석 포함)</button>
        </form>
    </div>
</div>

<!-- Discovery Center Modal -->
<div id="discovery-modal" style="display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 2000; align-items:center; justify-content:center;">
    <div class="glass-card" style="width: 90%; max-width: 1000px; max-height: 85vh; padding: 0; display:flex; flex-direction:column; overflow:hidden; border: 1px solid var(--secondary-color);">
        <div style="padding: 20px; border-bottom: 1px solid var(--glass-border); display:flex; justify-content:space-between; align-items:center; background: var(--primary-color);">
            <h3 style="margin:0; color:#fff;">CineAI Discovery Center</h3>
            <span style="cursor:pointer; font-size:1.5rem; color:#fff;" onclick="closeDiscovery()">✕</span>
        </div>
        
        <div style="padding: 20px; display:flex; flex-direction:column; gap:20px; flex:1; overflow:hidden;">
            <input type="text" id="lib-search" class="form-control" placeholder="100+개 영화 중 검색..." oninput="searchLibrary()">
            
            <div style="display:flex; gap:10px; overflow-x:auto; padding-bottom:10px; min-height: 60px; align-items: center;">
                <button type="button" class="lib-tab active" style="min-width: 80px; height: 45px; display: inline-flex; align-items: center; justify-content: center;" onclick="switchCategory('', this)">전체</button>
                <button type="button" class="lib-tab" style="min-width: 80px; height: 45px; display: inline-flex; align-items: center; justify-content: center;" onclick="switchCategory('latest', this)">최신/인기</button>
                <button type="button" class="lib-tab" style="min-width: 80px; height: 45px; display: inline-flex; align-items: center; justify-content: center;" onclick="switchCategory('classic', this)">할리우드 명작</button>
                <button type="button" class="lib-tab" style="min-width: 80px; height: 45px; display: inline-flex; align-items: center; justify-content: center;" onclick="switchCategory('korean', this)">한국 영화</button>
                <button type="button" class="lib-tab" style="min-width: 80px; height: 45px; display: inline-flex; align-items: center; justify-content: center;" onclick="switchCategory('animation', this)">애니메이션</button>
            </div>
            
            <div id="lib-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap:20px; overflow-y:auto; flex:1; padding: 10px;">
                <!-- Items go here -->
            </div>
        </div>
    </div>
</div>


<style>
    /* Skeleton Loader */
    .skeleton {
        background: linear-gradient(90deg, #1e293b 25%, #334155 50%, #1e293b 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
        border-radius: 4px;
    }
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
    .poster-fallback { background: linear-gradient(135deg, #1e293b, #0f172a); border: 1px solid var(--glass-border); display:flex; align-items:center; justify-content:center; flex-direction:column; padding: 5px; color: var(--secondary-color); font-weight: bold; text-align: center; }
</style>

<script>
function addTag(tag) {
    const input = document.getElementById('hashtags');
    let current = input.value.split(',').map(s => s.trim()).filter(s => s);
    if (!current.includes(tag)) {
        current.push(tag);
        input.value = current.join(', ');
    }
}

let currentCategory = '';
let currentSearch = '';

const discoveryModal = document.getElementById('discovery-modal');
const libGrid = document.getElementById('lib-grid');

document.getElementById('open-discovery-btn').onclick = () => {
    discoveryModal.style.display = 'flex';
    loadLibrary();
}

function closeDiscovery() {
    discoveryModal.style.display = 'none';
}

function searchLibrary() {
    currentSearch = document.getElementById('lib-search').value;
    loadLibrary();
}

function switchCategory(cat, btn) {
    currentCategory = cat;
    document.querySelectorAll('.lib-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    loadLibrary();
}

function getPosterGradient(title) {
    let hash = 0;
    for (let i = 0; i < title.length; i++) {
        hash = title.charCodeAt(i) + ((hash << 5) - hash);
    }
    const h1 = Math.abs(hash % 360);
    const h2 = (h1 + 40) % 360;
    return `linear-gradient(135deg, hsl(${h1}, 70%, 25%), hsl(${h2}, 60%, 15%))`;
}

async function loadLibrary() {
    // Helper function appended to window to avoid syntax errors inside inline onerror
    window.showMovieFallback = function(imgElement, title, gradient) {
        const div = document.createElement('div');
        div.className = "poster-fallback";
        div.style.cssText = `width:100%; height:180px; border-radius:4px; font-size:0.75rem; background: ${gradient};`;
        div.innerHTML = `<span style="font-size:1.5rem; margin-bottom:10px; display:block;">🎞️</span><div style="padding:0 5px; word-break:keep-all; text-align:center;">${title}</div>`;
        if (imgElement && imgElement.parentNode) {
            imgElement.parentNode.replaceChild(div, imgElement);
        }
    };
    
    libGrid.innerHTML = `
        <div style="grid-column: 1/-1; display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 20px;">
            ${Array(12).fill('<div class="lib-item"><div class="skeleton" style="width:100%; height:180px;"></div><div class="skeleton" style="width:80%; height:15px; margin: 10px auto;"></div></div>').join('')}
        </div>`;
        
    try {
        const url = `api/tmdb_proxy.php?action=search&query=${encodeURIComponent(currentSearch)}&category=${currentCategory}&_t=${Date.now()}`;
        const response = await fetch(url);
        const data = await response.json();
        
        libGrid.innerHTML = '';
        if (data.results && data.results.length > 0) {
            data.results.forEach(movie => {
                const item = document.createElement('div');
                item.className = 'lib-item';
                
                let posterHtml = '';
                if (movie.poster_path) {
                    // Use proxy for all images to ensure server-side SSL handling and caching
                    const posterUrl = `api/tmdb_image_proxy.php?size=w185&path=${encodeURIComponent(movie.poster_path)}`;
                    const gradient = getPosterGradient(movie.title);
                    // Ensure backticks and dollar signs are escaped to avoid template literal execution
                    const safeTitle = movie.title.replace(/`/g, '\\`').replace(/\$/g, '\\$');
                    posterHtml = `
                        <img src="${posterUrl}" class="lib-poster-img"
                             onerror="window.showMovieFallback(this, \`${safeTitle}\`, '${gradient}')">`;
                } else {
                    const gradient = getPosterGradient(movie.title);
                    posterHtml = `
                        <div class="poster-fallback" style="width: 100%; height: 180px; border-radius: 4px; font-size: 0.75rem; background: ${gradient};">
                            <span style="font-size: 1.5rem; margin-bottom: 10px;">🎞️</span>
                            <div style="padding:0 5px; word-break:keep-all; text-align:center;">${movie.title}</div>
                        </div>`;
                }

                item.innerHTML = `
                    ${posterHtml}
                    <div style="font-weight: 600; font-size: 0.85rem; margin-top: 10px; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${movie.title}</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">${(movie.release_date || '').split('-')[0] || 'N/A'}</div>
                `;
                
                item.onclick = () => selectMovie(movie);
                libGrid.appendChild(item);
            });
        } else {
            libGrid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:50px; color:var(--text-muted);">조건에 맞는 영화가 없습니다.</div>';
        }
    } catch (e) {
        libGrid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:50px; color:var(--danger-color);">데이터를 불러오지 못했습니다.</div>';
    }
}

function selectMovie(movie) {
    document.getElementById('movie_title').value = movie.title;
    document.getElementById('tmdb_id').value = movie.id;
    document.getElementById('tmdb_poster_path').value = movie.poster_path;
    
    // Update preview
    const preview = document.getElementById('selected-movie-preview');
    const poster = document.getElementById('preview-poster');
    const title = document.getElementById('preview-title');
    const date = document.getElementById('preview-date');
    
    if (movie.poster_path) {
        poster.innerHTML = `<img src="api/tmdb_image_proxy.php?size=w92&path=${movie.poster_path}" style="width: 100%; height: 100%; object-fit: cover;">`;
    } else {
        const gradient = getPosterGradient(movie.title);
        poster.innerHTML = `<div class="poster-fallback" style="width: 100%; height: 100%; font-size: 0.5rem; background: ${gradient};">CineAI</div>`;
    }
    
    title.innerText = movie.title;
    date.innerText = movie.release_date;
    preview.style.display = 'flex';
    
    closeDiscovery();
}

function resetSelection() {
    document.getElementById('movie_title').value = '';
    document.getElementById('tmdb_id').value = '';
    document.getElementById('tmdb_poster_path').value = '';
    document.getElementById('selected-movie-preview').style.display = 'none';
}

window.onload = () => {
    const preId = document.getElementById('tmdb_id').value;
    const preTitle = document.getElementById('movie_title').value;
    if (preId && preTitle) {
        document.getElementById('selected-movie-preview').style.display = 'flex';
        document.getElementById('preview-title').innerText = preTitle;
        document.getElementById('preview-poster').innerHTML = `<div class="poster-fallback" style="width: 100%; height: 100%; font-size: 0.5rem;">CineAI</div>`;
    }
}
</script>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
