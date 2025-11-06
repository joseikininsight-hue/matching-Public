<?php
/**
 * Performance Optimization Module
 * 
 * Lighthouse スコアを改善するための包括的なパフォーマンス最適化
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0.0
 * @since 9.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GI_Performance_Optimizer {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // 画像最適化
        add_filter('wp_generate_attachment_metadata', [$this, 'generate_webp_on_upload'], 10, 2);
        add_filter('wp_get_attachment_image', [$this, 'output_webp_picture'], 10, 3);
        add_filter('wp_get_attachment_image', [$this, 'add_image_dimensions'], 15, 2);
        add_filter('the_content', [$this, 'add_dimensions_to_content_images']);
        
        // HTTPS強制
        add_filter('wp_get_attachment_url', [$this, 'force_https']);
        add_filter('wp_get_attachment_image_src', [$this, 'force_https_array']);
        add_filter('the_content', [$this, 'https_content']);
        add_filter('widget_text', [$this, 'https_content']);
        
        // CSS/JS最適化
        add_action('wp_head', [$this, 'inline_critical_css'], 1);
        add_action('wp_head', [$this, 'optimize_google_fonts'], 3);
        add_action('wp_head', [$this, 'async_styles'], 5);
        add_action('wp_head', [$this, 'add_font_display_swap'], 2);
        add_filter('script_loader_tag', [$this, 'defer_scripts'], 10, 3);
        
        // 不要なWordPress機能削除
        add_action('wp_enqueue_scripts', [$this, 'remove_unused_wp_scripts'], 100);
        add_action('init', [$this, 'disable_emojis']);
        add_action('wp_head', [$this, 'remove_wp_version'], 1);
        
        // HTML圧縮
        if (!is_admin()) {
            add_action('template_redirect', [$this, 'start_html_minification'], 0);
        }
        
        // サードパーティスクリプト最適化
        add_action('wp_footer', [$this, 'lazy_load_third_party_scripts'], 1);
        
        // カスタム画像サイズ
        add_action('after_setup_theme', [$this, 'custom_image_sizes']);
        
        // キャッシュヘッダー
        add_action('send_headers', [$this, 'add_cache_headers']);
    }
    
    /**
     * ========================================
     * 画像最適化
     * ========================================
     */
    
    /**
     * アップロード時に自動的にWebPを生成
     */
    public function generate_webp_on_upload($metadata, $attachment_id) {
        if (!isset($metadata['file'])) {
            return $metadata;
        }
        
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/' . $metadata['file'];
        
        if (!file_exists($file_path)) {
            return $metadata;
        }
        
        // WebP変換を試みる
        $webp_path = $this->convert_to_webp($file_path);
        
        if ($webp_path) {
            update_post_meta($attachment_id, '_webp_path', $webp_path);
            
            // サイズごとのWebPも生成
            if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
                foreach ($metadata['sizes'] as $size => $size_data) {
                    $size_file = dirname($file_path) . '/' . $size_data['file'];
                    if (file_exists($size_file)) {
                        $this->convert_to_webp($size_file);
                    }
                }
            }
        }
        
        return $metadata;
    }
    
    /**
     * 画像をWebP形式に変換
     */
    private function convert_to_webp($file_path) {
        if (!function_exists('imagewebp')) {
            return false;
        }
        
        $file_info = pathinfo($file_path);
        $webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
        
        // すでに存在する場合はスキップ
        if (file_exists($webp_path)) {
            return $webp_path;
        }
        
        $image = false;
        
        // 画像タイプに応じて読み込み
        switch (strtolower($file_info['extension'])) {
            case 'jpg':
            case 'jpeg':
                $image = @imagecreatefromjpeg($file_path);
                break;
            case 'png':
                $image = @imagecreatefrompng($file_path);
                // PNG の透明度を保持
                if ($image) {
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                }
                break;
            case 'gif':
                $image = @imagecreatefromgif($file_path);
                break;
        }
        
        if ($image) {
            // WebPに変換（品質85）
            $result = imagewebp($image, $webp_path, 85);
            imagedestroy($image);
            
            if ($result) {
                return $webp_path;
            }
        }
        
        return false;
    }
    
    /**
     * <picture>タグでWebPを優先的に提供
     */
    public function output_webp_picture($html, $attachment_id, $size) {
        $webp_path = get_post_meta($attachment_id, '_webp_path', true);
        
        if (!$webp_path || !file_exists($webp_path)) {
            return $html;
        }
        
        $upload_dir = wp_upload_dir();
        $webp_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $webp_path);
        
        // 元の画像情報
        $src = wp_get_attachment_image_src($attachment_id, $size);
        $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        
        if (!$src) {
            return $html;
        }
        
        // <picture>タグで出力
        $picture_html = sprintf(
            '<picture>
                <source srcset="%s" type="image/webp">
                <img src="%s" alt="%s" loading="lazy" width="%d" height="%d">
            </picture>',
            esc_url($webp_url),
            esc_url($src[0]),
            esc_attr($alt),
            $src[1],
            $src[2]
        );
        
        return $picture_html;
    }
    
    /**
     * カスタム画像サイズを定義
     */
    public function custom_image_sizes() {
        // ヒーロー画像（最大幅800px）
        add_image_size('grant-hero', 800, 600, true);
        
        // カードサムネイル（グリッド表示用）
        add_image_size('grant-card', 400, 300, true);
        
        // モバイルビュー
        add_image_size('grant-mobile', 375, 280, true);
        
        // リストビュー用小サイズ
        add_image_size('grant-list', 200, 150, true);
    }
    
    /**
     * 画像に明示的なwidth/heightを追加
     */
    public function add_image_dimensions($html, $attachment_id) {
        // すでに設定されている場合はスキップ
        if (strpos($html, 'width=') !== false && strpos($html, 'height=') !== false) {
            return $html;
        }
        
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        if (!empty($metadata['width']) && !empty($metadata['height'])) {
            $html = str_replace(
                '<img ',
                sprintf('<img width="%d" height="%d" ', $metadata['width'], $metadata['height']),
                $html
            );
        }
        
        return $html;
    }
    
    /**
     * コンテンツ内の画像にも適用
     */
    public function add_dimensions_to_content_images($content) {
        if (empty($content)) {
            return $content;
        }
        
        // 正規表現で<img>タグを検索
        preg_match_all('/<img[^>]+>/i', $content, $matches);
        
        if (empty($matches[0])) {
            return $content;
        }
        
        foreach ($matches[0] as $img_tag) {
            // すでにwidth/heightがある場合はスキップ
            if (strpos($img_tag, 'width=') !== false && strpos($img_tag, 'height=') !== false) {
                continue;
            }
            
            // attachment IDを取得
            preg_match('/wp-image-(\d+)/i', $img_tag, $class_id);
            
            if (empty($class_id[1])) {
                continue;
            }
            
            $attachment_id = intval($class_id[1]);
            $metadata = wp_get_attachment_metadata($attachment_id);
            
            if (!empty($metadata['width']) && !empty($metadata['height'])) {
                $new_img_tag = str_replace(
                    '<img ',
                    sprintf('<img width="%d" height="%d" ', $metadata['width'], $metadata['height']),
                    $img_tag
                );
                
                $content = str_replace($img_tag, $new_img_tag, $content);
            }
        }
        
        return $content;
    }
    
    /**
     * ========================================
     * HTTPS強制
     * ========================================
     */
    
    /**
     * URLを強制的にHTTPSに変換
     */
    public function force_https($url) {
        return str_replace('http://', 'https://', $url);
    }
    
    /**
     * 配列形式のURLをHTTPSに変換
     */
    public function force_https_array($image) {
        if (is_array($image) && isset($image[0])) {
            $image[0] = $this->force_https($image[0]);
        }
        return $image;
    }
    
    /**
     * コンテンツ内のすべてのURLをHTTPSに
     */
    public function https_content($content) {
        return str_replace('http://joseikin-insight.com', 'https://joseikin-insight.com', $content);
    }
    
    /**
     * ========================================
     * CSS/JS最適化
     * ========================================
     */
    
    /**
     * クリティカルCSSをインライン化
     */
    public function inline_critical_css() {
        ?>
        <style id="critical-css">
        /* クリティカルCSS - Above the Fold */
        :root {
            --color-black: #000;
            --color-white: #fff;
            --color-gray-100: #f5f5f5;
            --color-gray-800: #1a1a1a;
            --transition-base: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            line-height: 1.6;
            color: var(--color-gray-800);
            background: var(--color-white);
        }
        
        /* ヘッダー - Above the Fold */
        .stylish-header {
            position: fixed;
            top: 0;
            width: 100%;
            background: var(--color-white);
            border-bottom: 1px solid rgba(0,0,0,0.1);
            z-index: 1000;
            height: 70px;
        }
        
        /* ヒーローセクション - Above the Fold */
        .hero-section {
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 100px;
        }
        
        /* 検索ボックス - Above the Fold */
        .search-input-wrapper {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 16px 20px;
            font-size: 16px;
            border: 1px solid rgba(0,0,0,0.2);
            border-radius: 8px;
            /* GPU加速プロパティのみ使用 - TBT改善 */
            transition: border-color var(--transition-base), box-shadow var(--transition-base);
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--color-black);
            box-shadow: 0 0 0 3px rgba(0,0,0,0.1);
        }
        
        /* GPU加速アニメーションのみ - Uncomposed Animations削減 */
        .card, .card-item, .grant-card {
            transition: transform var(--transition-base), opacity var(--transition-base);
        }
        
        .card:hover, .card-item:hover, .grant-card:hover {
            will-change: transform;
            transform: translateY(-4px);
        }
        
        /* デフォルトで全要素のtransitionを無効化（パフォーマンス向上） */
        * {
            transition: none !important;
        }
        
        /* インタラクティブ要素のみtransitionを有効化 */
        button, a, input, textarea, select, 
        .search-input, .card, .card-item, .grant-card,
        .button, .btn, .link {
            transition: revert !important;
        }
        
        /* ローディング状態 */
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s ease-in-out infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* レイアウトシフト防止 */
        img {
            max-width: 100%;
            height: auto;
            display: block;
        }
        </style>
        <?php
    }
    
    /**
     * Google Fontsを最適化して読み込む
     */
    public function optimize_google_fonts() {
        ?>
        <!-- Preconnect for performance -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        
        <!-- Font display: swap で即座にフォールバックフォント表示 -->
        <link rel="preload" as="style" 
              href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@400;600;700&family=Noto+Sans+JP:wght@400;500;700&display=swap">
        
        <link rel="stylesheet" 
              href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@400;600;700&family=Noto+Sans+JP:wght@400;500;700&display=swap"
              media="print" onload="this.media='all'">
        
        <noscript>
            <link rel="stylesheet" 
                  href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@400;600;700&family=Noto+Sans+JP:wght@400;500;700&display=swap">
        </noscript>
        <?php
    }
    
    /**
     * CSSを非同期で読み込む
     */
    public function async_styles() {
        ?>
        <script>
        // CSS非同期読み込み関数
        function loadCSS(href, media) {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            link.media = media || 'all';
            document.head.appendChild(link);
        }
        
        // クリティカルでないCSSを遅延読み込み
        if (window.requestIdleCallback) {
            requestIdleCallback(function() {
                loadCSS('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
            });
        } else {
            window.addEventListener('load', function() {
                loadCSS('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
            });
        }
        </script>
        <?php
    }
    
    /**
     * JavaScriptを defer/async で読み込む
     */
    public function defer_scripts($tag, $handle, $src) {
        // 遅延読み込みしたいスクリプトのハンドル
        $defer_scripts = [
            'jquery',
            'wp-embed',
            'gi-main-scripts',
        ];
        
        // 非同期読み込みしたいスクリプト
        $async_scripts = [
            'google-analytics',
            'google-tag-manager',
        ];
        
        if (in_array($handle, $defer_scripts)) {
            return str_replace('<script ', '<script defer ', $tag);
        }
        
        if (in_array($handle, $async_scripts)) {
            return str_replace('<script ', '<script async ', $tag);
        }
        
        return $tag;
    }
    
    /**
     * ========================================
     * サードパーティスクリプト最適化
     * ========================================
     */
    
    /**
     * サードパーティスクリプトをユーザー操作後に読み込む
     * Phase 2最適化: requestIdleCallback使用でTBT削減
     */
    public function lazy_load_third_party_scripts() {
        ?>
        <script>
        (function() {
            let thirdPartyScriptsLoaded = false;
            
            // GTMを読み込む
            function loadGTM() {
                // GTM IDが設定されている場合のみ読み込む
                // header.phpで実際のGTM IDに置き換えてください
                const gtmId = '<?php echo defined("GTM_ID") ? GTM_ID : ""; ?>';
                if (gtmId) {
                    const script = document.createElement('script');
                    script.src = 'https://www.googletagmanager.com/gtm.js?id=' + gtmId;
                    script.async = true;
                    document.head.appendChild(script);
                    console.log('GTM loaded in idle time');
                }
            }
            
            // Google Adsを読み込む
            function loadAds() {
                // 広告スクリプトがある場合はここに追加
                console.log('Ads loading deferred');
            }
            
            // Google Analyticsを読み込む（GA4用）
            function loadGA() {
                const gaId = '<?php echo defined("GA_MEASUREMENT_ID") ? GA_MEASUREMENT_ID : ""; ?>';
                if (gaId) {
                    const script = document.createElement('script');
                    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + gaId;
                    script.async = true;
                    document.head.appendChild(script);
                    
                    window.dataLayer = window.dataLayer || [];
                    function gtag(){dataLayer.push(arguments);}
                    gtag('js', new Date());
                    gtag('config', gaId);
                    console.log('GA4 loaded in idle time');
                }
            }
            
            // サードパーティスクリプトを読み込む関数（改善版）
            function loadThirdPartyScripts() {
                if (thirdPartyScriptsLoaded) return;
                thirdPartyScriptsLoaded = true;
                
                console.log('Loading third-party scripts in idle time...');
                
                // requestIdleCallbackを使用してアイドル時に読み込む
                // これによりメインスレッドブロッキングを回避
                if ('requestIdleCallback' in window) {
                    requestIdleCallback(function() {
                        loadGTM();
                        loadGA();
                        loadAds();
                    }, { timeout: 3000 }); // 3秒後にはタイムアウトして実行
                } else {
                    // requestIdleCallback非対応ブラウザ用フォールバック
                    setTimeout(function() {
                        loadGTM();
                        loadGA();
                        loadAds();
                    }, 3000);
                }
            }
            
            // ユーザー操作を検知したら読み込む（改善版）
            const events = ['scroll', 'click', 'mousemove', 'touchstart'];
            const triggerLoad = function() {
                loadThirdPartyScripts();
                // イベントリスナーを削除してメモリリーク防止
                events.forEach(event => {
                    window.removeEventListener(event, triggerLoad);
                });
            };
            
            // 各イベントにリスナーを設定（passive: trueでスクロールパフォーマンス向上）
            events.forEach(event => {
                window.addEventListener(event, triggerLoad, { 
                    once: true,      // 一度だけ実行
                    passive: true    // パフォーマンス向上
                });
            });
            
            // 3秒経過したら自動的に読み込む（5秒→3秒に短縮）
            setTimeout(loadThirdPartyScripts, 3000);
        })();
        </script>
        <?php
    }
    
    /**
     * ========================================
     * 不要なWordPress機能削除
     * ========================================
     */
    
    /**
     * 不要なWordPressデフォルトスクリプトを削除
     */
    public function remove_unused_wp_scripts() {
        // 使用していない場合は削除
        if (!is_admin()) {
            // Gutenbergブロックライブラリ（クラシックテーマの場合）
            wp_dequeue_style('wp-block-library');
            wp_dequeue_style('wp-block-library-theme');
            wp_dequeue_style('classic-theme-styles');
            wp_dequeue_style('global-styles');
            
            // WordPress埋め込み機能を削除
            wp_dequeue_script('wp-embed');
        }
    }
    
    /**
     * 絵文字機能を無効化
     */
    public function disable_emojis() {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        
        // TinyMCE用の絵文字プラグインを削除
        add_filter('tiny_mce_plugins', function($plugins) {
            if (is_array($plugins)) {
                return array_diff($plugins, array('wpemoji'));
            }
            return $plugins;
        });
        
        // DNS Prefetchから絵文字を削除
        add_filter('wp_resource_hints', function($urls, $relation_type) {
            if ('dns-prefetch' === $relation_type) {
                $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/');
                $urls = array_diff($urls, array($emoji_svg_url));
            }
            return $urls;
        }, 10, 2);
    }
    
    /**
     * WordPressバージョン情報を削除
     */
    public function remove_wp_version() {
        remove_action('wp_head', 'wp_generator');
    }
    
    /**
     * font-display: swap を追加
     */
    public function add_font_display_swap() {
        ?>
        <style>
            @font-face {
                font-display: swap;
            }
        </style>
        <?php
    }
    
    /**
     * ========================================
     * HTML圧縮
     * ========================================
     */
    
    /**
     * HTML圧縮を開始
     */
    public function start_html_minification() {
        ob_start([$this, 'minify_html']);
    }
    
    /**
     * HTMLを圧縮
     */
    public function minify_html($html) {
        // pre, textarea, script タグの内容を保護
        $protected = [];
        $html = preg_replace_callback(
            '/<(pre|textarea|script)[^>]*?>.*?<\/\1>/is',
            function($matches) use (&$protected) {
                $placeholder = '###PROTECTED' . count($protected) . '###';
                $protected[$placeholder] = $matches[0];
                return $placeholder;
            },
            $html
        );
        
        // HTML圧縮
        $search = [
            '/\>[^\S ]+/s',           // タグ後の空白を削除
            '/[^\S ]+\</s',           // タグ前の空白を削除
            '/(\s)+/s',               // 複数の空白を1つに
            '/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s' // コメント削除（条件付きコメントは保持）
        ];
        $replace = ['>', '<', '\\1', ''];
        $html = preg_replace($search, $replace, $html);
        
        // 保護したコンテンツを復元
        foreach ($protected as $placeholder => $content) {
            $html = str_replace($placeholder, $content, $html);
        }
        
        return $html;
    }
    
    /**
     * ========================================
     * キャッシュヘッダー
     * ========================================
     */
    
    /**
     * 静的アセットにキャッシュヘッダーを追加
     */
    public function add_cache_headers() {
        if (is_admin() || is_user_logged_in()) {
            return;
        }
        
        // 静的ファイルの場合のみ
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // 画像ファイル
        if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg|ico)$/i', $request_uri)) {
            header('Cache-Control: public, max-age=31536000, immutable');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        }
        
        // CSS/JSファイル
        elseif (preg_match('/\.(css|js)$/i', $request_uri)) {
            header('Cache-Control: public, max-age=2592000'); // 30日
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
        }
        
        // フォントファイル
        elseif (preg_match('/\.(woff2|woff|ttf|eot)$/i', $request_uri)) {
            header('Cache-Control: public, max-age=31536000, immutable');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        }
    }
}

// インスタンス化
GI_Performance_Optimizer::get_instance();
