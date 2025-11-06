/**
 * Grant Viewing History Tracker
 * 補助金閲覧履歴トラッキングシステム (Cookie-based)
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0.0
 */

(function() {
    'use strict';
    
    const COOKIE_NAME = 'gi_grant_viewing_history';
    const MAX_HISTORY_ITEMS = 50; // 最大保存件数
    const COOKIE_EXPIRY_DAYS = 90; // Cookie有効期限（90日）
    
    /**
     * Cookieから閲覧履歴を取得
     */
    function getViewingHistory() {
        const cookie = document.cookie
            .split('; ')
            .find(row => row.startsWith(COOKIE_NAME + '='));
        
        if (!cookie) {
            return [];
        }
        
        try {
            const value = decodeURIComponent(cookie.split('=')[1]);
            const history = JSON.parse(value);
            return Array.isArray(history) ? history : [];
        } catch (e) {
            console.error('[Viewing History] Parse error:', e);
            return [];
        }
    }
    
    /**
     * 閲覧履歴をCookieに保存
     */
    function saveViewingHistory(history) {
        try {
            const value = encodeURIComponent(JSON.stringify(history));
            const expiry = new Date();
            expiry.setDate(expiry.getDate() + COOKIE_EXPIRY_DAYS);
            
            document.cookie = `${COOKIE_NAME}=${value}; expires=${expiry.toUTCString()}; path=/; SameSite=Lax`;
            return true;
        } catch (e) {
            console.error('[Viewing History] Save error:', e);
            return false;
        }
    }
    
    /**
     * 補助金閲覧を記録
     */
    function trackGrantView(grantId, grantData = {}) {
        if (!grantId) {
            return false;
        }
        
        let history = getViewingHistory();
        
        // 既存の同じIDを削除（重複防止）
        history = history.filter(item => item.id !== grantId);
        
        // 新しい閲覧データを先頭に追加
        history.unshift({
            id: grantId,
            title: grantData.title || '',
            category: grantData.category || '',
            prefecture: grantData.prefecture || '',
            timestamp: Date.now(),
            viewCount: 1
        });
        
        // 最大件数を超える場合は古いものを削除
        if (history.length > MAX_HISTORY_ITEMS) {
            history = history.slice(0, MAX_HISTORY_ITEMS);
        }
        
        return saveViewingHistory(history);
    }
    
    /**
     * 閲覧履歴から関連カテゴリーを取得
     */
    function getFrequentCategories(limit = 3) {
        const history = getViewingHistory();
        const categoryCount = {};
        
        history.forEach(item => {
            if (item.category) {
                categoryCount[item.category] = (categoryCount[item.category] || 0) + 1;
            }
        });
        
        // カウント順にソート
        const sorted = Object.entries(categoryCount)
            .sort((a, b) => b[1] - a[1])
            .slice(0, limit)
            .map(entry => entry[0]);
        
        return sorted;
    }
    
    /**
     * 閲覧履歴から関連都道府県を取得
     */
    function getFrequentPrefectures(limit = 3) {
        const history = getViewingHistory();
        const prefectureCount = {};
        
        history.forEach(item => {
            if (item.prefecture) {
                prefectureCount[item.prefecture] = (prefectureCount[item.prefecture] || 0) + 1;
            }
        });
        
        // カウント順にソート
        const sorted = Object.entries(prefectureCount)
            .sort((a, b) => b[1] - a[1])
            .slice(0, limit)
            .map(entry => entry[0]);
        
        return sorted;
    }
    
    /**
     * Single grant page用: ページロード時に閲覧を記録
     */
    function initSingleGrantTracking() {
        // single-grantページの場合のみ実行
        if (!document.body.classList.contains('single-grant')) {
            return;
        }
        
        // データ属性から補助金情報を取得
        const grantData = {
            id: document.body.dataset.grantId,
            title: document.body.dataset.grantTitle || document.title,
            category: document.body.dataset.grantCategory || '',
            prefecture: document.body.dataset.grantPrefecture || ''
        };
        
        if (grantData.id) {
            trackGrantView(grantData.id, grantData);
            console.log('[Viewing History] Tracked grant view:', grantData.id);
        }
    }
    
    /**
     * Ajax用: 履歴に基づくおすすめ補助金を取得
     */
    function fetchPersonalizedGrants(callback) {
        const categories = getFrequentCategories();
        const prefectures = getFrequentPrefectures();
        
        if (categories.length === 0 && prefectures.length === 0) {
            // 履歴がない場合は特集記事を返す
            callback(null, { hasHistory: false });
            return;
        }
        
        // WordPressのAjax APIを使用
        if (typeof wp !== 'undefined' && wp.ajax) {
            wp.ajax.post('get_personalized_grants', {
                categories: categories,
                prefectures: prefectures
            }).done(function(response) {
                callback(null, { hasHistory: true, grants: response });
            }).fail(function(error) {
                callback(error, null);
            });
        } else {
            // Fallback: 通常のfetch API
            fetch(window.giAjaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_personalized_grants',
                    categories: categories.join(','),
                    prefectures: prefectures.join(',')
                })
            })
            .then(response => response.json())
            .then(data => {
                callback(null, { hasHistory: true, grants: data });
            })
            .catch(error => {
                callback(error, null);
            });
        }
    }
    
    /**
     * デバッグ用: 閲覧履歴をコンソールに出力
     */
    function debugHistory() {
        const history = getViewingHistory();
        console.log('[Viewing History] Total items:', history.length);
        console.log('[Viewing History] Categories:', getFrequentCategories());
        console.log('[Viewing History] Prefectures:', getFrequentPrefectures());
        console.table(history.slice(0, 10)); // 最新10件を表示
    }
    
    // グローバルAPIとして公開
    window.giViewingHistory = {
        track: trackGrantView,
        getHistory: getViewingHistory,
        getFrequentCategories: getFrequentCategories,
        getFrequentPrefectures: getFrequentPrefectures,
        fetchPersonalized: fetchPersonalizedGrants,
        debug: debugHistory
    };
    
    // ページロード時に初期化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSingleGrantTracking);
    } else {
        initSingleGrantTracking();
    }
    
    console.log('[OK] Grant Viewing History Tracker initialized');
    
})();
