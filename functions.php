<?php
/**
 * Grant Insight Perfect - Functions File (Consolidated & Clean Edition)
 * 
 * Simplified structure with consolidated files in single /inc/ directory
 * - Removed unused code and duplicate functionality
 * - Merged related files for better organization
 * - Eliminated folder over-organization
 * 
 * @package Grant_Insight_Perfect
 * @version 10.0.0 (Yahoo!-Style Tabbed Grant Browsing)
 * 
 * Changelog v10.0.0:
 * - Implemented Yahoo! JAPAN-style tabbed grant browsing system
 * - Added 4 tabs: 締切間近(30日以内), おすすめ, 新着, あなたにおすすめ
 * - Added cookie-based viewing history tracking
 * - Created reusable grant card template (template-parts/grant/card.php)
 * - Added personalized recommendations based on browsing history
 * - Replaced separate grant sections with unified tabbed interface
 * - Current theme styling (black/white, Yahoo! functionality)
 *
 * Previous v9.2.1:
 * - Fixed Jetpack duplicate store registration errors
 * - Added React key prop warning fixes
 * - Fixed Gutenberg block editor JavaScript errors
 * - Added customizer 500 error prevention
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// テーマバージョン定数
if (!defined('GI_THEME_VERSION')) {
    define('GI_THEME_VERSION', '11.0.1'); // REST API endpoint fix for grant post type
}
if (!defined('GI_THEME_PREFIX')) {
    define('GI_THEME_PREFIX', 'gi_');
}

// EMERGENCY: File editing temporarily disabled to prevent memory exhaustion
// All theme editor functionality removed until memory issue is resolved

// 🔧 MEMORY OPTIMIZATION
// Increase memory limit for admin area only
if (is_admin() && !wp_doing_ajax()) {
    @ini_set('memory_limit', '256M');
    
    // Limit WordPress features that consume memory
    add_action('init', function() {
        // Disable post revisions temporarily
        if (!defined('WP_POST_REVISIONS')) {
            define('WP_POST_REVISIONS', 3);
        }
        
        // Reduce autosave interval
        if (!defined('AUTOSAVE_INTERVAL')) {
            define('AUTOSAVE_INTERVAL', 300); // 5 minutes
        }
    }, 1);
}

/**
 * 🔧 JavaScript Error Handling & Optimization
 * Fixes for WordPress admin JavaScript errors
 */

// Dequeue problematic Jetpack scripts to prevent duplicate store registration
add_action('admin_enqueue_scripts', 'gi_fix_jetpack_conflicts', 100);
function gi_fix_jetpack_conflicts() {
    // Check if Jetpack is active
    if (class_exists('Jetpack')) {
        // Deregister duplicate Jetpack stores
        wp_deregister_script('jetpack-ai-logo-generator');
        wp_deregister_script('jetpack-modules-store');
    }
}

// Fix Gutenberg block editor JavaScript errors
add_action('enqueue_block_editor_assets', 'gi_fix_block_editor_errors', 100);
function gi_fix_block_editor_errors() {
    // Add error handling for block editor
    wp_add_inline_script('wp-blocks', '
        (function() {
            // Prevent duplicate store registration errors
            var originalRegisterStore = wp.data && wp.data.registerStore;
            if (originalRegisterStore) {
                wp.data.registerStore = function(storeName, options) {
                    try {
                        return originalRegisterStore.call(wp.data, storeName, options);
                    } catch (error) {
                        if (!error.message.includes("already registered")) {
                            console.error("Store registration error:", error);
                        }
                        return wp.data.select(storeName);
                    }
                };
            }
        })();
    ', 'before');
}

// Disable Jetpack modules that cause conflicts
add_filter('jetpack_get_available_modules', 'gi_disable_problematic_jetpack_modules', 999);
function gi_disable_problematic_jetpack_modules($modules) {
    // Remove modules that cause store registration conflicts
    $problematic_modules = array('photon', 'photon-cdn', 'videopress');
    foreach ($problematic_modules as $module) {
        if (isset($modules[$module])) {
            unset($modules[$module]);
        }
    }
    return $modules;
}

// Fix customizer 500 error by limiting customizer features
add_action('customize_register', 'gi_fix_customizer_errors', 999);
function gi_fix_customizer_errors($wp_customize) {
    // Remove sections that might cause conflicts
    $wp_customize->remove_section('custom_css');
}

// Add error logging for JavaScript errors
add_action('wp_footer', 'gi_add_js_error_logging');
add_action('admin_footer', 'gi_add_js_error_logging');
function gi_add_js_error_logging() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        ?>
        <script>
        // Global error handler for JavaScript
        window.addEventListener('error', function(e) {
            if (console && console.error) {
                console.error('JS Error caught:', e.message, 'at', e.filename + ':' + e.lineno);
            }
        });
        
        // Handle unhandled promise rejections
        window.addEventListener('unhandledrejection', function(e) {
            if (console && console.error) {
                console.error('Unhandled Promise Rejection:', e.reason);
            }
        });
        </script>
        <?php
    }
}

// Purpose page rewrite rules
add_action('init', 'gi_register_purpose_rewrite_rules');
function gi_register_purpose_rewrite_rules() {
    add_rewrite_rule(
        '^purpose/([^/]+)/?$',
        'index.php?gi_purpose=$matches[1]',
        'top'
    );
}

// AUTO-FLUSH: Rewrite rules for purpose pages (remove after first load)
add_action('init', function() {
    if (get_option('gi_purpose_rewrite_flushed') !== 'yes') {
        flush_rewrite_rules(false);
        update_option('gi_purpose_rewrite_flushed', 'yes');
    }
}, 99);

// Register purpose query var
add_filter('query_vars', 'gi_register_purpose_query_var');
function gi_register_purpose_query_var($vars) {
    $vars[] = 'gi_purpose';
    return $vars;
}

// Template redirect for purpose pages
add_action('template_redirect', 'gi_purpose_template_redirect');
function gi_purpose_template_redirect() {
    $purpose_slug = get_query_var('gi_purpose');
    if ($purpose_slug) {
        $template = locate_template('page-purpose.php');
        if ($template) {
            include $template;
            exit;
        }
    }
}

/**
 * Get purpose-to-category mapping
 * Maps purpose slugs to actual grant_category taxonomy term slugs from database
 * 
 * @return array Associative array of purpose_slug => array of category_slugs
 */
function gi_get_purpose_category_mapping() {
    // Static cache to avoid repeated queries
    static $mapping = null;
    
    if ($mapping !== null) {
        return $mapping;
    }
    
    // Define mapping between purpose slugs and category term names (Japanese)
    // v2.1: Updated to match new 8 main + 5 additional purpose structure
    // Categories are stored as Japanese names to match the actual WordPress taxonomy terms
    $mapping = array(
        // ===== 8 Main Purposes =====
        'equipment' => array(
            '設備投資', 'ものづくり・新商品開発', 'IT導入・DX', 
            '生産性向上・業務効率化', '防犯・防災・BCP', 
            '省エネ・再エネ', '医療・福祉', '観光・インバウンド', 
            '農業・林業・漁業'
        ),
        'training' => array(
            '人材育成・人材確保', '雇用維持・促進', 
            '働き方改革・待遇改善', '女性活躍・多様性', 
            '若者・学生支援', 'シニア・障害者支援', 
            'IT導入・DX', '生産性向上・業務効率化'
        ),
        'sales' => array(
            '販路拡大', '事業拡大', '新規事業・第二創業', 
            'ものづくり・新商品開発', '広告・マーケティング', 
            'EC・オンライン販売', '展示会・商談会', 
            '海外展開', '観光・インバウンド'
        ),
        'startup' => array(
            '創業・スタートアップ', '新規事業・第二創業', 
            '事業拡大', '販路拡大', '資金調達', 
            'IT導入・DX', '人材育成・人材確保', 
            '起業・独立'
        ),
        'digital' => array(
            'IT導入・DX', '生産性向上・業務効率化', 
            'EC・オンライン販売', '働き方改革・待遇改善', 
            'クラウド・SaaS', 'セキュリティ', 
            'AI・IoT・先端技術', '設備投資'
        ),
        'funding' => array(
            '資金調達', '運転資金', '設備投資', 
            '事業拡大', '創業・スタートアップ', 
            '事業再構築・転換', '新規事業・第二創業'
        ),
        'environment' => array(
            '省エネ・再エネ', '環境保護・脱炭素', 
            '設備投資', '生産性向上・業務効率化', 
            'SDGs', '循環型経済', '農業・林業・漁業'
        ),
        'global' => array(
            '海外展開', '輸出促進', '観光・インバウンド', 
            '販路拡大', 'クールジャパン・コンテンツ', 
            '国際交流', '展示会・商談会'
        ),
        
        // ===== 5 Additional Purposes =====
        'succession' => array(
            '事業承継', 'M&A', '経営改善', 
            '事業再構築・転換', '後継者育成', 
            '人材育成・人材確保'
        ),
        'rnd' => array(
            '研究開発', 'AI・IoT・先端技術', 
            'ものづくり・新商品開発', '設備投資', 
            '産学連携', 'イノベーション', 
            '特許・知的財産'
        ),
        'housing' => array(
            '住宅支援', 'リフォーム・改修', 
            '省エネ・再エネ', '防犯・防災・BCP', 
            '空き家対策', '子育て支援', 
            '移住・定住'
        ),
        'agriculture' => array(
            '農業・林業・漁業', '6次産業化', 
            '設備投資', '販路拡大', 
            '省エネ・再エネ', '人材育成・人材確保', 
            '地域活性化'
        ),
        'individual' => array(
            '起業・独立', 'フリーランス', 
            '資格取得・スキルアップ', '若者・学生支援', 
            '創業・スタートアップ', 'テレワーク・在宅ワーク', 
            '副業・兼業'
        )
    );
    
    return $mapping;
}

/**
 * Get grant categories for a specific purpose
 * 
 * @param string $purpose_slug The purpose slug
 * @return array Array of WP_Term objects, or empty array if not found
 */
function gi_get_categories_for_purpose($purpose_slug) {
    $mapping = gi_get_purpose_category_mapping();
    
    if (!isset($mapping[$purpose_slug])) {
        error_log('[Purpose Debug] No mapping found for purpose: ' . $purpose_slug);
        return array();
    }
    
    $category_names = $mapping[$purpose_slug];
    
    // Query actual terms from database using Japanese names
    $terms = get_terms(array(
        'taxonomy' => 'grant_category',
        'name' => $category_names,
        'hide_empty' => false
    ));
    
    if (is_wp_error($terms)) {
        error_log('[Purpose Debug] Error querying categories: ' . $terms->get_error_message());
        return array();
    }
    
    error_log('[Purpose Debug] Found ' . count($terms) . ' category terms for purpose: ' . $purpose_slug);
    
    return $terms;
}

/**
 * Get category slugs for a specific purpose
 * 
 * @param string $purpose_slug The purpose slug
 * @return array Array of category slugs
 */
function gi_get_category_slugs_for_purpose($purpose_slug) {
    $terms = gi_get_categories_for_purpose($purpose_slug);
    $slugs = array();
    
    if (empty($terms)) {
        error_log('[Purpose Debug] No categories found for purpose: ' . $purpose_slug);
        return $slugs; // Return empty array
    }
    
    foreach ($terms as $term) {
        $slugs[] = $term->slug;
    }
    
    error_log('[Purpose Debug] Found ' . count($slugs) . ' category slugs for purpose: ' . $purpose_slug);
    error_log('[Purpose Debug] Category slugs: ' . implode(', ', $slugs));
    
    return $slugs;
}

// 統合されたファイルの読み込み（シンプルな配列）
$inc_dir = get_template_directory() . '/inc/';

$required_files = array(
    // Core files
    'theme-foundation.php',        // テーマ設定、投稿タイプ、タクソノミー
    'data-processing.php',         // データ処理・ヘルパー関数
    
    // Admin & UI
    'admin-functions.php',         // 管理画面カスタマイズ + メタボックス (統合済み)
    'acf-fields.php',              // ACF設定とフィールド定義
    'customizer-error-handler.php', // カスタマイザーエラーハンドリング (v9.2.1+)
    
    // Core functionality
    'card-display.php',            // カードレンダリング・表示機能
    'ajax-functions.php',          // AJAX処理
    'ai-functions.php',            // AI機能・検索履歴 (統合済み)
    
    // Performance optimization
    'performance-optimization.php', // パフォーマンス最適化（v9.2.0+）
    
    // Google Sheets integration (consolidated into one file)
    'google-sheets-integration.php', // Google Sheets統合（全機能統合版）
    'safe-sync-manager.php',         // 安全同期管理システム
    
    // Grant Content SEO Optimizer (v9.3.0+) - DISABLED: Duplicate SEO with single-grant.php
    // 'grant-content-seo-optimizer.php',  // 助成金コンテンツSEO最適化
    
    // Dynamic CSS Generator (v9.3.1+)
    'grant-dynamic-css-generator.php',  // 投稿内容に応じた動的CSS生成
    
    // Advanced SEO Enhancer (v9.3.2+) - DISABLED: Duplicate SEO with single-grant.php
    // 'grant-advanced-seo-enhancer.php'   // SEO大幅強化（OGP、Schema.org拡張、内部リンク）
    
    // Column System (v1.0.0+) - NEW: コラム機能統合システム
    'column-system.php',  // コラム機能（カスタム投稿タイプ、ACF、補助金連携、Analytics）
    // 'column-admin-ui.php',  // コラム管理UI（Phase 3: 承認ワークフロー、分析ダッシュボード、設定） - TEMPORARILY DISABLED
    
    // Grant Amount Fixer (v1.0.0+) - NEW: 助成金額修正ツール
    'grant-amount-fixer.php',  // 日付シリアル値を正しい金額に一括修正
);

// ファイルを安全に読み込み
foreach ($required_files as $file) {
    $file_path = $inc_dir . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        // デバッグモードの場合のみエラーログに記録
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Grant Insight: Missing required file: ' . $file);
        }
    }
}

/**
 * Remove duplicate ACF content from post_content
 * 
 * This filter removes HTML sections that are duplicated from ACF fields
 * which are already rendered separately in single-grant.php
 * 
 * @param string $content The post content
 * @return string Filtered content without duplicates
 */
function gi_remove_duplicate_acf_content($content) {
    // Only process grant post type single pages
    if (!is_singular('grant')) {
        return $content;
    }
    
    // Remove div elements with specific class patterns that duplicate ACF field output
    $duplicate_patterns = array(
        // Main section containers
        '/<div[^>]*class=["\'][^"\']*grant-target[^"\']*["\'][^>]*>.*?<\/div>/is',
        '/<div[^>]*class=["\'][^"\']*grant-target-section[^"\']*["\'][^>]*>.*?<\/div>/is',
        
        // Eligible expenses sections
        '/<div[^>]*class=["\'][^"\']*eligible-expenses[^"\']*["\'][^>]*>.*?<\/div>/is',
        '/<div[^>]*class=["\'][^"\']*eligible-expenses-detailed[^"\']*["\'][^>]*>.*?<\/div>/is',
        
        // Required documents sections
        '/<div[^>]*class=["\'][^"\']*required-documents[^"\']*["\'][^>]*>.*?<\/div>/is',
        '/<div[^>]*class=["\'][^"\']*required-documents-detailed[^"\']*["\'][^>]*>.*?<\/div>/is',
        '/<div[^>]*class=["\'][^"\']*required-documents-display[^"\']*["\'][^>]*>.*?<\/div>/is',
        
        // Grant section wrappers (from SEO optimizer)
        '/<section[^>]*class=["\'][^"\']*grant-section[^"\']*["\'][^>]*>.*?<\/section>/is',
        '/<article[^>]*class=["\'][^"\']*grant-article[^"\']*["\'][^>]*>.*?<\/article>/is',
    );
    
    // Apply all removal patterns
    foreach ($duplicate_patterns as $pattern) {
        $content = preg_replace($pattern, '', $content);
    }
    
    // Remove empty paragraphs and excessive whitespace
    $content = preg_replace('/<p[^>]*>\s*<\/p>/i', '', $content);
    $content = preg_replace('/\n\s*\n\s*\n/i', "\n\n", $content);
    
    // Trim extra whitespace
    $content = trim($content);
    
    return $content;
}

// Add filter with high priority to run early
add_filter('the_content', 'gi_remove_duplicate_acf_content', 5);

/**
 * Enqueue Column System CSS and JavaScript
 * コラムシステムのCSS・JavaScriptを読み込み
 * 
 * @return void
 */
function gi_enqueue_column_assets() {
    // コラム関連ページのみ読み込み
    if (is_singular('column') || is_post_type_archive('column') || 
        is_tax('column_category') || is_tax('column_tag') || is_front_page()) {
        
        // CSS
        wp_enqueue_style(
            'gi-column-styles',
            get_template_directory_uri() . '/assets/css/column.css',
            array(),
            GI_THEME_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'gi-column-scripts',
            get_template_directory_uri() . '/assets/js/column.js',
            array(),
            GI_THEME_VERSION,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'gi_enqueue_column_assets');

/**
 * Enqueue Admin Error Fix Script
 * 管理画面のJavaScriptエラーを修正するスクリプトを読み込み
 * 
 * @return void
 */
function gi_enqueue_admin_error_fix() {
    wp_enqueue_script(
        'gi-admin-error-fix',
        get_template_directory_uri() . '/assets/js/admin-error-fix.js',
        array('jquery'),
        GI_THEME_VERSION,
        false // Load in header to catch early errors
    );
}
add_action('admin_enqueue_scripts', 'gi_enqueue_admin_error_fix', 1); // Priority 1 to load early

/**
 * Enqueue Ad Error Handler Script
 * 広告エラーを処理するスクリプトを読み込み
 * 
 * @return void
 */
function gi_enqueue_ad_error_handler() {
    wp_enqueue_script(
        'gi-ad-error-handler',
        get_template_directory_uri() . '/assets/js/ad-error-handler.js',
        array(),
        GI_THEME_VERSION,
        true // Load in footer
    );
}
add_action('wp_enqueue_scripts', 'gi_enqueue_ad_error_handler');

/**
 * Enqueue Grant Viewing History Tracker
 * 補助金閲覧履歴トラッキングスクリプトを読み込み
 * 
 * @return void
 */
function gi_enqueue_viewing_history() {
    wp_enqueue_script(
        'gi-viewing-history',
        get_template_directory_uri() . '/assets/js/grant-viewing-history.js',
        array(),
        GI_THEME_VERSION,
        true // Load in footer
    );
    
    // Ajax URL をJavaScriptに渡す
    wp_localize_script('gi-viewing-history', 'giAjaxConfig', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gi_viewing_history_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'gi_enqueue_viewing_history');

/**
 * WordPress REST API 設定をJavaScriptに渡す
 * AI AssistantやREST API呼び出しに必要
 */
function gi_enqueue_rest_api_settings() {
    // Make sure jQuery is enqueued first
    wp_enqueue_script('jquery');
    
    // Localize script for REST API settings
    wp_localize_script('jquery', 'wpApiSettings', array(
        'root' => esc_url_raw(rest_url()),
        'nonce' => wp_create_nonce('wp_rest')
    ));
    
    // AJAX URL も追加（フォールバック用）
    wp_localize_script('jquery', 'ajaxSettings', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wp_rest')
    ));
    
    // Debug log
    error_log('🔵 REST API Settings localized - Nonce: ' . wp_create_nonce('wp_rest'));
}
add_action('wp_enqueue_scripts', 'gi_enqueue_rest_api_settings');

/**
 * Add inline script to verify settings are loaded
 */
function gi_add_api_settings_debug() {
    ?>
    <script>
    console.log('🔍 Checking API Settings on page load...');
    console.log('wpApiSettings:', typeof window.wpApiSettings !== 'undefined' ? window.wpApiSettings : '❌ NOT LOADED');
    console.log('ajaxSettings:', typeof window.ajaxSettings !== 'undefined' ? window.ajaxSettings : '❌ NOT LOADED');
    </script>
    <?php
}
add_action('wp_footer', 'gi_add_api_settings_debug', 999);

/**
 * REST API: コラムカテゴリフィルタリングに関する注記
 * 
 * WordPressのREST APIは標準でタクソノミーIDによるフィルタリングをサポートしています。
 * 
 * - Post Type: 'column' with rest_base 'columns'
 * - Taxonomy: 'column_category' with rest_base 'column-categories'
 * - Filtering: /wp-json/wp/v2/columns?column-categories={term_id}
 * 
 * Note: column-categoriesパラメータはterm ID（整数）のみを受け付けます。
 * スラッグによるフィルタリングは標準ではサポートされていないため、
 * JavaScriptでterm IDを使用する必要があります。
 */


/**
 * Affiliate Ad Manager System
 * アフィリエイト広告管理システム読み込み
 * 
 * @since 1.0.0
 */
error_log('🔵 functions.php: About to load affiliate-ad-manager.php');
$affiliate_ad_file = get_template_directory() . '/inc/affiliate-ad-manager.php';
error_log('🔵 functions.php: File path: ' . $affiliate_ad_file);
error_log('🔵 functions.php: File exists: ' . (file_exists($affiliate_ad_file) ? 'YES' : 'NO'));

require_once $affiliate_ad_file;

error_log('🔵 functions.php: affiliate-ad-manager.php loaded');
error_log('🔵 functions.php: ji_display_ad exists: ' . (function_exists('ji_display_ad') ? 'YES' : 'NO'));

/**
 * Access Tracking System
 * アクセストラッキングシステム
 * 
 * @since 1.0.0
 */
require_once get_template_directory() . '/inc/access-tracking.php';
