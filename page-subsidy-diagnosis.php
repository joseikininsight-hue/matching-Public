<?php
/**
 * Template Name: 補助金診断ページ
 * Description: AI補助金マッチングアプリを埋め込んだ専用ページテンプレート
 */

get_header(); ?>

<style>
/* 補助金診断ページ専用スタイル */
.subsidy-diagnosis-container {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0;
}

.subsidy-diagnosis-hero {
    text-align: center;
    padding: 40px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 0 0 20px 20px;
    margin-bottom: 30px;
}

.subsidy-diagnosis-hero h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 15px;
    color: white;
}

.subsidy-diagnosis-hero p {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
    color: white;
}

.subsidy-diagnosis-content {
    padding: 0 20px;
}

.subsidy-diagnosis-iframe-wrapper {
    position: relative;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding-bottom: 80%;
    height: 0;
    overflow: hidden;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.subsidy-diagnosis-iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: none;
    border-radius: 16px;
}

.subsidy-diagnosis-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.feature-card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
}

.feature-icon {
    font-size: 3rem;
    margin-bottom: 15px;
}

.feature-title {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 10px;
    color: #333;
}

.feature-description {
    font-size: 0.95rem;
    color: #666;
    line-height: 1.6;
}

.subsidy-diagnosis-cta {
    text-align: center;
    margin: 40px auto;
    padding: 30px 20px;
    max-width: 800px;
}

.subsidy-diagnosis-cta h3 {
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 15px;
    color: #333;
}

.subsidy-diagnosis-cta p {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 20px;
}

.cta-button {
    display: inline-block;
    padding: 15px 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.cta-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    color: white;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .subsidy-diagnosis-hero h1 {
        font-size: 1.8rem;
    }
    
    .subsidy-diagnosis-hero p {
        font-size: 1rem;
    }
    
    .subsidy-diagnosis-iframe-wrapper {
        padding-bottom: 100%;
    }
    
    .subsidy-diagnosis-features {
        grid-template-columns: 1fr;
    }
}

/* ローディングアニメーション */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    z-index: 10;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* フルスクリーンモード用 */
body.subsidy-diagnosis-fullscreen {
    overflow: hidden;
}

body.subsidy-diagnosis-fullscreen .subsidy-diagnosis-iframe-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    max-width: none;
    padding-bottom: 0;
    border-radius: 0;
    z-index: 9999;
}

/* アクセシビリティ対応 */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}
</style>

<div class="subsidy-diagnosis-container">
    
    <!-- ヒーローセクション -->
    <div class="subsidy-diagnosis-hero">
        <h1>💡 AI補助金マッチング</h1>
        <p>あなたの事業に最適な補助金を、AIが最短3分で診断します</p>
    </div>

    <!-- 特徴セクション -->
    <div class="subsidy-diagnosis-features">
        <div class="feature-card">
            <div class="feature-icon">🤖</div>
            <h3 class="feature-title">AI診断</h3>
            <p class="feature-description">最新のAI技術で、8,000件以上の補助金データからあなたに最適なものを瞬時に選定</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">⚡</div>
            <h3 class="feature-title">最短3分</h3>
            <p class="feature-description">簡単な質問に答えるだけで、すぐに結果が分かります</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🎯</div>
            <h3 class="feature-title">高精度マッチング</h3>
            <p class="feature-description">あなたの事業内容や状況に合わせた、最も適した補助金を提案</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🆓</div>
            <h3 class="feature-title">完全無料</h3>
            <p class="feature-description">登録不要、何度でも無料でご利用いただけます</p>
        </div>
    </div>

    <!-- メインコンテンツ：埋め込みアプリ -->
    <div class="subsidy-diagnosis-content">
        <div class="subsidy-diagnosis-iframe-wrapper" id="iframe-wrapper">
            <!-- ローディング表示 -->
            <div class="loading-overlay" id="loading-overlay">
                <div class="loading-spinner"></div>
            </div>
            
            <!-- 埋め込みアプリ -->
            <iframe 
                id="subsidy-diagnosis-iframe"
                class="subsidy-diagnosis-iframe"
                src="https://matching-public.pages.dev/" 
                title="AI補助金マッチング診断ツール"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                loading="eager"
                onload="document.getElementById('loading-overlay').style.display='none';">
                <p>お使いのブラウザはiframeに対応していません。</p>
                <p><a href="https://matching-public.pages.dev/" target="_blank" rel="noopener noreferrer">こちらから直接アクセスしてください</a></p>
            </iframe>
        </div>
    </div>

    <!-- CTAセクション -->
    <div class="subsidy-diagnosis-cta">
        <h3>今すぐ診断を始める</h3>
        <p>無料で簡単！あなたに合った補助金を見つけましょう</p>
        <a href="#iframe-wrapper" class="cta-button">診断スタート</a>
    </div>

</div>

<script>
// スムーススクロール
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// iframe高さ自動調整（親子通信）
window.addEventListener('message', function(e) {
    if (e.origin === 'https://matching-public.pages.dev') {
        const iframe = document.getElementById('subsidy-diagnosis-iframe');
        if (e.data.height && iframe) {
            iframe.style.height = e.data.height + 'px';
        }
    }
}, false);

// フルスクリーンボタン追加（オプション）
function toggleFullscreen() {
    const body = document.body;
    body.classList.toggle('subsidy-diagnosis-fullscreen');
}

// エラーハンドリング
document.getElementById('subsidy-diagnosis-iframe').addEventListener('error', function() {
    console.error('iframe loading failed');
    const wrapper = document.getElementById('iframe-wrapper');
    if (wrapper) {
        wrapper.innerHTML = '<div style="padding: 40px; text-align: center;"><p>アプリケーションの読み込みに失敗しました。</p><p><a href="https://matching-public.pages.dev/" target="_blank">こちらから直接アクセスしてください</a></p></div>';
    }
});

// パフォーマンス監視
if ('PerformanceObserver' in window) {
    const observer = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
            console.log('Page load performance:', entry);
        }
    });
    observer.observe({ entryTypes: ['navigation'] });
}
</script>

<?php get_footer(); ?>
