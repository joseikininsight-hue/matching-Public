/**
 * Ad Error Handler
 * 広告読み込みエラーを処理し、フォールバックコンテンツを表示
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0.0
 */

(function() {
    'use strict';

    /**
     * 広告エラーハンドラー
     */
    class AdErrorHandler {
        constructor() {
            this.failedAds = new Set();
            this.init();
        }

        init() {
            // Google AdSenseの読み込み監視
            this.monitorAdSense();
            
            // ネットワークエラーの抑制（コンソールをクリーンに保つ）
            this.suppressAdNetworkErrors();
            
            console.log('[Ad Handler] Initialized');
        }

        /**
         * Google AdSenseの読み込み監視
         */
        monitorAdSense() {
            // AdSense広告の読み込み完了を待機
            if (typeof window.adsbygoogle !== 'undefined') {
                this.handleAdSenseLoaded();
            } else {
                // AdSenseが読み込まれなかった場合（広告ブロッカー等）
                setTimeout(() => {
                    if (typeof window.adsbygoogle === 'undefined') {
                        this.handleAdSenseBlocked();
                    }
                }, 3000);
            }
        }

        /**
         * AdSense読み込み成功時の処理
         */
        handleAdSenseLoaded() {
            console.log('[Ad Handler] AdSense loaded successfully');
            
            // 広告の読み込み状態を監視
            this.observeAdSlots();
        }

        /**
         * AdSenseがブロックされた場合の処理
         */
        handleAdSenseBlocked() {
            console.log('[Ad Handler] AdSense blocked or failed to load');
            
            // 広告スロットにフォールバックコンテンツを表示
            const adSlots = document.querySelectorAll('.adsbygoogle, [data-ad-client]');
            
            adSlots.forEach((slot, index) => {
                if (!this.isAdLoaded(slot)) {
                    this.showFallbackContent(slot, index);
                }
            });
        }

        /**
         * 広告スロットの監視
         */
        observeAdSlots() {
            const adSlots = document.querySelectorAll('.adsbygoogle');
            
            // Intersection Observerで広告の表示状態を監視
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // 広告が表示領域に入ったら読み込み状態をチェック
                        setTimeout(() => {
                            if (!this.isAdLoaded(entry.target)) {
                                this.showFallbackContent(entry.target);
                            }
                        }, 2000);
                    }
                });
            }, {
                threshold: 0.1
            });

            adSlots.forEach(slot => observer.observe(slot));
        }

        /**
         * 広告が読み込まれたかチェック
         */
        isAdLoaded(slot) {
            // data-adsbygoogle-statusがfilled（埋まった）かチェック
            const status = slot.getAttribute('data-adsbygoogle-status');
            return status === 'filled' || status === 'done';
        }

        /**
         * フォールバックコンテンツの表示
         */
        showFallbackContent(slot, index = 0) {
            // 既にフォールバックを表示済みの場合はスキップ
            if (this.failedAds.has(slot)) return;
            
            this.failedAds.add(slot);

            // フォールバックメッセージ（オプション：完全に非表示も可能）
            const fallbackOptions = [
                // オプション1: 何も表示しない（推奨）
                () => {
                    slot.style.display = 'none';
                },
                
                // オプション2: 代替コンテンツを表示
                () => {
                    slot.innerHTML = `
                        <div style="padding: 20px; background: #f5f5f5; border: 1px solid #e0e0e0; border-radius: 8px; text-align: center;">
                            <p style="margin: 0; color: #666; font-size: 13px;">
                                広告がブロックされています<br>
                                <small style="color: #999;">このサイトは広告収入で運営されています</small>
                            </p>
                        </div>
                    `;
                },
                
                // オプション3: 自社プロモーションを表示
                () => {
                    slot.innerHTML = `
                        <div style="padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; color: white; text-align: center;">
                            <h3 style="margin: 0 0 8px; font-size: 16px; font-weight: 600;">
                                💡 補助金診断を受けてみませんか？
                            </h3>
                            <p style="margin: 0 0 12px; font-size: 13px; opacity: 0.9;">
                                あなたに最適な補助金を3分で診断
                            </p>
                            <a href="/subsidy-diagnosis/" 
                               style="display: inline-block; padding: 8px 20px; background: white; color: #667eea; text-decoration: none; border-radius: 20px; font-weight: 600; font-size: 13px;">
                                無料で診断する
                            </a>
                        </div>
                    `;
                }
            ];

            // デフォルトでオプション1（非表示）を使用
            fallbackOptions[0]();
            
            console.log('[Ad Handler] Fallback displayed for slot:', index);
        }

        /**
         * 広告ネットワークエラーの抑制
         */
        suppressAdNetworkErrors() {
            // エラーイベントをキャプチャ
            window.addEventListener('error', (e) => {
                const errorUrl = e.filename || '';
                
                // 広告関連のドメインからのエラーを抑制
                const adDomains = [
                    'doubleclick.net',
                    'googlesyndication.com',
                    'adservice.google.com',
                    'googleads.g.doubleclick.net',
                    'spotxchange.com',
                    'ads.yahoo.com'
                ];

                const isAdError = adDomains.some(domain => errorUrl.includes(domain));
                
                if (isAdError) {
                    // エラーを抑制（コンソールに表示しない）
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);

            // fetch/XHRエラーも監視
            const originalFetch = window.fetch;
            window.fetch = function(...args) {
                return originalFetch.apply(this, args).catch(error => {
                    const url = args[0];
                    const adDomains = ['doubleclick.net', 'googlesyndication.com', 'spotxchange.com'];
                    const isAdRequest = adDomains.some(domain => url.includes(domain));
                    
                    if (!isAdRequest) {
                        // 広告以外のエラーは通常通り処理
                        throw error;
                    }
                    
                    // 広告エラーは静かに無視
                    console.log('[Ad Handler] Ad request failed (expected with ad blockers)');
                    return Promise.reject(error);
                });
            };
        }
    }

    // DOMContentLoaded後に初期化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            new AdErrorHandler();
        });
    } else {
        new AdErrorHandler();
    }

    // グローバルに公開
    window.AdErrorHandler = AdErrorHandler;

})();
