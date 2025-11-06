/**
 * Column System JavaScript
 * タブ切り替え、Ajax読み込み、インタラクション
 * 
 * @package Grant_Insight_Perfect
 * @subpackage Column_System
 * @version 2.0.0 (Phase 2 - Ajax Complete)
 */

(function() {
    'use strict';

    // グローバル変数
    let currentPage = 1;
    let currentCategory = 'all';
    let isLoading = false;
    let hasMorePosts = true;

    /**
     * DOMContentLoaded後に実行
     */
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[Column System] Initializing Phase 2...');

        // タブ切り替え機能の初期化
        initTabNavigation();

        // 無限スクロールの初期化
        initInfiniteScroll();

        // スムーススクロールの初期化
        initSmoothScroll();

        // 検索機能の初期化
        initColumnSearch();

        console.log('[Column System] Initialized successfully (Phase 2)');
    });

    /**
     * タブナビゲーション（カテゴリ切り替え）- Phase 2完全版
     */
    function initTabNavigation() {
        const tabLinks = document.querySelectorAll('.column-tab-link');
        
        if (tabLinks.length === 0) {
            return;
        }

        tabLinks.forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                
                const category = this.getAttribute('data-category');
                console.log('[Column Tab] Switching to category:', category);

                // 既に選択中のタブをクリックした場合は何もしない
                if (this.classList.contains('active') && category === currentCategory) {
                    return;
                }

                // すべてのタブからactiveクラスを削除
                tabLinks.forEach(function(t) {
                    t.classList.remove('active');
                });

                // クリックされたタブにactiveクラスを追加
                this.classList.add('active');

                // カテゴリを更新
                currentCategory = category;
                currentPage = 1;
                hasMorePosts = true;

                // Ajaxでコンテンツを読み込み
                loadColumnsByCategory(category, true);
            });
        });
    }

    /**
     * カテゴリ別にコラムを読み込み（Ajax）- Phase 2完全版
     * 
     * @param {string} category カテゴリスラッグ
     * @param {boolean} replace trueの場合は置き換え、falseの場合は追加
     */
    function loadColumnsByCategory(category, replace = true) {
        const grid = document.getElementById('column-article-grid');
        const loading = document.getElementById('column-loading');

        if (!grid || !loading) {
            console.warn('[Column Ajax] Required elements not found');
            return;
        }

        // 既にローディング中の場合はスキップ
        if (isLoading) {
            console.log('[Column Ajax] Already loading, skipping...');
            return;
        }

        isLoading = true;
        loading.classList.remove('hidden');

        // スムーズスクロールでグリッドの位置に移動（置き換えの場合のみ）
        if (replace) {
            const gridTop = grid.getBoundingClientRect().top + window.pageYOffset - 100;
            window.scrollTo({
                top: gridTop,
                behavior: 'smooth'
            });
        }
        
        // Ajaxリクエスト
        fetch(gi_column_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'gi_get_columns',
                nonce: gi_column_ajax.nonce,
                category: category,
                paged: currentPage,
                per_page: 6
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (replace) {
                    // 置き換えモード：既存のコンテンツをクリア
                    grid.innerHTML = data.data.html;
                    console.log('[Column Ajax] Replaced content:', data.data.found_posts, 'posts');
                } else {
                    // 追加モード：既存のコンテンツに追加
                    grid.insertAdjacentHTML('beforeend', data.data.html);
                    console.log('[Column Ajax] Appended content:', data.data.found_posts, 'posts');
                }

                // 次のページがあるかチェック
                hasMorePosts = data.data.has_more;
                console.log('[Column Ajax] Has more posts:', hasMorePosts);

                // フェードインアニメーション
                animateCards();
            } else {
                console.error('[Column Ajax] Error:', data.data);
                if (replace) {
                    grid.innerHTML = '<div class="col-span-2 text-center py-12 text-gray-500">' +
                                   '<p class="text-xl mb-2">😔</p>' +
                                   '<p>記事が見つかりませんでした。</p>' +
                                   '</div>';
                }
            }
        })
        .catch(error => {
            console.error('[Column Ajax] Fetch error:', error);
            if (replace) {
                grid.innerHTML = '<div class="col-span-2 text-center py-12 text-red-500">' +
                               '<p class="text-xl mb-2">❌</p>' +
                               '<p>記事の読み込みに失敗しました。</p>' +
                               '<p class="text-sm mt-2">しばらくしてから再度お試しください。</p>' +
                               '</div>';
            }
        })
        .finally(() => {
            loading.classList.add('hidden');
            isLoading = false;
        });
    }

    /**
     * カードにフェードインアニメーションを適用
     */
    function animateCards() {
        const cards = document.querySelectorAll('.column-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 50);
        });
    }

    /**
     * スムーススクロール
     */
    function initSmoothScroll() {
        const links = document.querySelectorAll('a[href^="#"]');
        
        links.forEach(function(link) {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                // ハッシュのみの場合はスキップ
                if (!href || href === '#' || href === '#0') {
                    return;
                }

                const target = document.querySelector(href);
                
                if (target) {
                    e.preventDefault();
                    
                    const offset = 80; // ヘッダーの高さ分
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    /**
     * 無限スクロール - Phase 2完全実装
     */
    function initInfiniteScroll() {
        const grid = document.getElementById('column-article-grid');
        
        if (!grid) {
            console.log('[Infinite Scroll] Grid not found, skipping initialization');
            return;
        }

        // Intersection Observer でスクロール検知
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !isLoading && hasMorePosts) {
                    console.log('[Infinite Scroll] Loading more posts...');
                    currentPage++;
                    loadColumnsByCategory(currentCategory, false);
                }
            });
        }, {
            root: null,
            rootMargin: '200px', // 200px手前で発火
            threshold: 0.1
        });

        // 監視用の要素を作成
        const sentinel = document.createElement('div');
        sentinel.id = 'infinite-scroll-sentinel';
        sentinel.style.height = '10px';
        
        const container = document.getElementById('column-grid-container');
        if (container) {
            container.appendChild(sentinel);
            observer.observe(sentinel);
            console.log('[Infinite Scroll] Initialized successfully');
        }
    }

    /**
     * コラム検索機能 - Phase 2実装
     */
    function initColumnSearch() {
        const searchForm = document.getElementById('column-search-form');
        const searchInput = document.getElementById('column-search-input');
        
        if (!searchForm || !searchInput) {
            console.log('[Column Search] Search elements not found');
            return;
        }

        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const query = searchInput.value.trim();
            
            if (query.length < 2) {
                alert('2文字以上で検索してください');
                return;
            }

            console.log('[Column Search] Searching for:', query);
            performSearch(query);
        });

        // リアルタイム検索（オプション）
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    console.log('[Column Search] Real-time search:', query);
                    performSearch(query);
                }, 500);
            }
        });
    }

    /**
     * 検索を実行
     * 
     * @param {string} query 検索クエリ
     */
    function performSearch(query) {
        const grid = document.getElementById('column-article-grid');
        const loading = document.getElementById('column-loading');

        if (!grid || !loading) {
            return;
        }

        loading.classList.remove('hidden');

        fetch(gi_column_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'gi_search_columns',
                nonce: gi_column_ajax.nonce,
                query: query,
                paged: 1,
                per_page: 12
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                grid.innerHTML = data.data.html;
                
                // 検索結果数を表示
                const resultsCount = data.data.found_posts;
                showSearchResults(query, resultsCount);
                
                animateCards();
            } else {
                grid.innerHTML = '<div class="col-span-2 text-center py-12 text-gray-500">' +
                               '<p class="text-xl mb-2">🔍</p>' +
                               '<p>「' + query + '」の検索結果が見つかりませんでした。</p>' +
                               '</div>';
            }
        })
        .catch(error => {
            console.error('[Column Search] Error:', error);
        })
        .finally(() => {
            loading.classList.add('hidden');
        });
    }

    /**
     * 検索結果数を表示
     * 
     * @param {string} query 検索クエリ
     * @param {number} count 結果数
     */
    function showSearchResults(query, count) {
        const container = document.getElementById('column-grid-container');
        
        if (!container) {
            return;
        }

        // 既存の結果表示を削除
        const existingResult = document.getElementById('search-result-info');
        if (existingResult) {
            existingResult.remove();
        }

        // 新しい結果表示を追加
        const resultInfo = document.createElement('div');
        resultInfo.id = 'search-result-info';
        resultInfo.className = 'mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg';
        resultInfo.innerHTML = `
            <p class="text-sm text-gray-700">
                <strong class="text-blue-600">"${query}"</strong> の検索結果: 
                <strong>${count}件</strong>
            </p>
            <button onclick="location.reload()" class="text-sm text-blue-600 hover:underline mt-2">
                × 検索をクリア
            </button>
        `;

        container.insertBefore(resultInfo, container.firstChild);
    }

    /**
     * ソーシャルシェアボタン - Phase 2実装
     */
    function initShareButtons() {
        const shareButtons = document.querySelectorAll('[data-share]');
        
        shareButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const platform = this.getAttribute('data-share');
                const url = encodeURIComponent(window.location.href);
                const title = encodeURIComponent(document.title);
                
                let shareUrl = '';
                
                switch(platform) {
                    case 'twitter':
                        shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
                        break;
                    case 'facebook':
                        shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                        break;
                    case 'line':
                        shareUrl = `https://social-plugins.line.me/lineit/share?url=${url}`;
                        break;
                    case 'pocket':
                        shareUrl = `https://getpocket.com/edit?url=${url}&title=${title}`;
                        break;
                    case 'hatena':
                        shareUrl = `https://b.hatena.ne.jp/add?mode=confirm&url=${url}&title=${title}`;
                        break;
                }
                
                if (shareUrl) {
                    window.open(shareUrl, 'share', 'width=600,height=400');
                }
            });
        });
        
        console.log('[Share Buttons] Initialized:', shareButtons.length, 'buttons');
    }

    // ウィンドウリサイズ時の処理
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            console.log('[Column System] Window resized');
            // 必要に応じてレイアウト調整
        }, 250);
    });

})();
