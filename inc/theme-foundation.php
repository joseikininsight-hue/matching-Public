<?php
/**
 * Grant Insight Perfect - Core Setup File
 *
 * テーマの基本設定、投稿タイプ、タクソノミーを管理
 * 
 * @package Grant_Insight_Perfect
 * @version 8.0.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * =============================================================================
 * 1. テーマ基本設定
 * =============================================================================
 */

/**
 * 安全なタクソノミー操作のためのヘルパー関数
 */
function gi_safe_get_term_slug($term) {
    if (is_object($term) && isset($term->slug)) {
        return $term->slug;
    } elseif (is_numeric($term)) {
        $term_obj = get_term($term);
        if ($term_obj && !is_wp_error($term_obj) && isset($term_obj->slug)) {
            return $term_obj->slug;
        }
    }
    return '';
}

/**
 * 安全なタクソノミー操作のためのフィルター
 */
add_filter('get_term', 'gi_validate_term_object', 10, 2);
function gi_validate_term_object($term, $taxonomy = '') {
    if ($term && is_object($term) && !isset($term->slug)) {
        // If slug is missing, try to retrieve it
        if (isset($term->term_id)) {
            $full_term = get_term($term->term_id, $taxonomy);
            if ($full_term && !is_wp_error($full_term)) {
                return $full_term;
            }
        }
    }
    return $term;
}

/**
 * テーマ基本設定
 */
function gi_setup() {
    // 基本的なテーマサポート
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption'
    ));
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-width'  => true,
        'flex-height' => true,
    ));
    add_theme_support('automatic-feed-links');
    
    // 助成金関連の画像サイズ
    add_image_size('grant-thumbnail', 400, 300, true);
    add_image_size('grant-featured', 800, 450, true);
    
    // 言語ファイル
    load_theme_textdomain('grant-insight', get_template_directory() . '/languages');
    
    // HTTPS強制化 - Mixed Content対策
    if (is_ssl()) {
        add_filter('upload_dir', 'gi_force_https_uploads');
        add_filter('wp_get_attachment_url', 'gi_force_https_url');
        add_filter('get_site_icon_url', 'gi_force_https_url');
        add_filter('site_icon_url', 'gi_force_https_url');
    }
    
    // メニュー登録
    register_nav_menus(array(
        'primary' => 'メインメニュー',
        'footer' => 'フッターメニュー'
    ));
}
add_action('after_setup_theme', 'gi_setup');

/**
 * コンテンツ幅設定
 */
function gi_content_width() {
    $GLOBALS['content_width'] = 1200;
}
add_action('after_setup_theme', 'gi_content_width', 0);

/**
 * スクリプト・スタイルの読み込み - Tailwind CSS Build Edition
 */
function gi_enqueue_scripts() {
    // Tailwind CSS Build Edition（本番用：最小化版、開発用：通常版）
    $tailwind_file = (defined('WP_DEBUG') && WP_DEBUG) 
        ? 'tailwind-build.css' 
        : 'tailwind-build.min.css';
    
    wp_enqueue_style(
        'gi-tailwind', 
        get_template_directory_uri() . '/assets/css/' . $tailwind_file, 
        array(), 
        GI_THEME_VERSION
    );
    
    // WordPressテーマメインスタイルシート（Tailwindの後に読み込み）
    wp_enqueue_style('gi-style', get_stylesheet_uri(), array('gi-tailwind'), GI_THEME_VERSION);
    
    // 統合フロントエンドCSS（カスタムスタイル用）
    if (file_exists(get_template_directory() . '/assets/css/unified-frontend.css')) {
        wp_enqueue_style('gi-unified-frontend', get_template_directory_uri() . '/assets/css/unified-frontend.css', array('gi-tailwind'), GI_THEME_VERSION);
    }
    
    // Google Fonts（日本語フォント）
    wp_enqueue_style('google-fonts-noto', 'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap', array(), null);
    
    // 統合フロントエンドJavaScript（jQuery不要のVanilla JS）
    if (file_exists(get_template_directory() . '/assets/js/unified-frontend.js')) {
        wp_enqueue_script('gi-unified-frontend', get_template_directory_uri() . '/assets/js/unified-frontend.js', array(), GI_THEME_VERSION, true);
        
        // AJAX設定
        wp_localize_script('gi-unified-frontend', 'gi_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gi_ajax_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'gi_enqueue_scripts');

/**
 * ページテンプレートをロード
 * 
 * @param string $template_name テンプレート名（例: 'about', 'contact'）
 * @param string $page_title ページタイトル（未使用、後方互換性のため）
 */
function gi_load_page_template($template_name, $page_title = '') {
    $template_path = get_template_directory() . '/pages/templates/page-' . $template_name . '.php';
    
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        // テンプレートが見つからない場合は404
        get_header();
        echo '<div class="container"><h1>ページが見つかりません</h1><p>テンプレートファイルが存在しません。</p></div>';
        get_footer();
    }
}

/**
 * ウィジェットエリア登録
 */
function gi_widgets_init() {
    // サイドバー
    register_sidebar(array(
        'name'          => 'サイドバー',
        'id'            => 'sidebar-1',
        'description'   => 'サイドバーウィジェットエリア',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
    
    // フッター
    register_sidebar(array(
        'name'          => 'フッター',
        'id'            => 'footer-1',
        'description'   => 'フッターウィジェットエリア',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title">',
        'after_title'   => '</h4>',
    ));
}
add_action('widgets_init', 'gi_widgets_init');

/**
 * カスタマイザー設定
 */
function gi_customize_register($wp_customize) {
    // 助成金表示設定セクション
    $wp_customize->add_section('gi_grant_display', array(
        'title' => '助成金表示設定',
        'priority' => 30,
    ));
    
    // 1ページあたりの表示件数
    $wp_customize->add_setting('gi_grants_per_page', array(
        'default' => 12,
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('gi_grants_per_page', array(
        'label' => '1ページあたりの表示件数',
        'section' => 'gi_grant_display',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 6,
            'max' => 30,
            'step' => 3,
        ),
    ));
    
    // グリッド表示の列数
    $wp_customize->add_setting('gi_grid_columns', array(
        'default' => 3,
        'sanitize_callback' => 'absint',
    ));
    
    $wp_customize->add_control('gi_grid_columns', array(
        'label' => 'グリッド表示の列数',
        'section' => 'gi_grant_display',
        'type' => 'select',
        'choices' => array(
            2 => '2列',
            3 => '3列',
            4 => '4列',
        ),
    ));
}
add_action('customize_register', 'gi_customize_register');

/**
 * 助成金検索機能の強化
 */
function gi_enhance_grant_search($query) {
    if (!is_admin() && $query->is_main_query()) {
        // 助成金アーカイブページの表示件数
        if (is_post_type_archive('grant') || is_tax('grant_category') || is_tax('grant_prefecture')) {
            $per_page = get_theme_mod('gi_grants_per_page', 12);
            $query->set('posts_per_page', $per_page);
            $query->set('orderby', 'date');
            $query->set('order', 'DESC');
        }
        
        // 検索結果に助成金を含める
        if ($query->is_search()) {
            $post_types = $query->get('post_type');
            if (empty($post_types)) {
                $query->set('post_type', array('post', 'grant'));
            }
        }
    }
}
add_action('pre_get_posts', 'gi_enhance_grant_search');

/**
 * セキュリティ強化
 */
function gi_security_headers() {
    if (!is_admin()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
    }
}
add_action('send_headers', 'gi_security_headers');

// 不要なヘッダー情報を削除
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_shortlink_wp_head');

/**
 * =============================================================================
 * 2. カスタム投稿タイプ・タクソノミー
 * =============================================================================
 */

/**
 * カスタム投稿タイプ登録
 */
function gi_register_post_types() {
    // 助成金投稿タイプ
    register_post_type('grant', array(
        'labels' => array(
            'name' => '助成金・補助金',
            'singular_name' => '助成金・補助金',
            'add_new' => '新規追加',
            'add_new_item' => '新しい助成金・補助金を追加',
            'edit_item' => '助成金・補助金を編集',
            'new_item' => '新しい助成金・補助金',
            'view_item' => '助成金・補助金を表示',
            'search_items' => '助成金・補助金を検索',
            'not_found' => '助成金・補助金が見つかりませんでした',
            'not_found_in_trash' => 'ゴミ箱に助成金・補助金はありません',
            'all_items' => 'すべての助成金・補助金',
            'menu_name' => '助成金・補助金'
        ),
        'description' => '助成金・補助金情報を管理します',
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_admin_bar' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'grants',
            'with_front' => false
        ),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-money-alt',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions'),
        'show_in_rest' => true,
        'rest_base' => 'grants',
        'rest_controller_class' => 'WP_REST_Posts_Controller'
    ));
}
add_action('init', 'gi_register_post_types');

// パーマリンクフラッシュ（投稿タイプが確実に認識されるように）
add_action('after_switch_theme', 'flush_rewrite_rules');
add_action('wp_loaded', function() {
    static $flushed = false;
    // Force flush for REST API endpoint fix (v11.0.1)
    if (!$flushed && !get_option('gi_permalinks_flushed_v11_rest_api_fix')) {
        flush_rewrite_rules(true);
        update_option('gi_permalinks_flushed_v11_rest_api_fix', current_time('mysql'));
        $flushed = true;
    }
});

// Force flush on theme activation or when post type doesn't have proper archive
add_action('init', function() {
    // Check if grants archive is accessible
    if (post_type_exists('grant')) {
        $archive_link = get_post_type_archive_link('grant');
        if (!$archive_link || !get_option('gi_permalinks_forced_flush_v2')) {
            flush_rewrite_rules(true);
            update_option('gi_permalinks_forced_flush_v2', current_time('mysql'));
        }
    }
}, 20);

/**
 * カスタムタクソノミー登録
 */
function gi_register_taxonomies() {
    // 助成金カテゴリー
    register_taxonomy('grant_category', 'grant', array(
        'labels' => array(
            'name' => '助成金カテゴリー',
            'singular_name' => '助成金カテゴリー',
            'search_items' => 'カテゴリーを検索',
            'all_items' => 'すべてのカテゴリー',
            'parent_item' => '親カテゴリー',
            'parent_item_colon' => '親カテゴリー:',
            'edit_item' => 'カテゴリーを編集',
            'update_item' => 'カテゴリーを更新',
            'add_new_item' => '新しいカテゴリーを追加',
            'new_item_name' => '新しいカテゴリー名'
        ),
        'description' => '助成金・補助金をカテゴリー別に分類します',
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'grant-category',
            'with_front' => false,
            'hierarchical' => true
        )
    ));
    
    // 都道府県タクソノミー（市町村の親として階層化）
    register_taxonomy('grant_prefecture', 'grant', array(
        'labels' => array(
            'name' => '対象都道府県',
            'singular_name' => '都道府県',
            'search_items' => '都道府県を検索',
            'all_items' => 'すべての都道府県',
            'parent_item' => '親都道府県',
            'parent_item_colon' => '親都道府県:',
            'edit_item' => '都道府県を編集',
            'update_item' => '都道府県を更新',
            'add_new_item' => '新しい都道府県を追加',
            'new_item_name' => '新しい都道府県名'
        ),
        'description' => '助成金・補助金の対象都道府県を管理します（市町村の親分類）',
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => true, // 階層構造を有効化（市町村の親として）
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'prefecture',
            'with_front' => false
        )
    ));
    
    // 助成金タグ
    register_taxonomy('grant_tag', 'grant', array(
        'labels' => array(
            'name' => '助成金タグ',
            'singular_name' => '助成金タグ',
            'search_items' => 'タグを検索',
            'all_items' => 'すべてのタグ',
            'edit_item' => 'タグを編集',
            'update_item' => 'タグを更新',
            'add_new_item' => '新しいタグを追加',
            'new_item_name' => '新しいタグ名'
        ),
        'description' => '助成金・補助金をタグで分類します',
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'grant-tag',
            'with_front' => false
        )
    ));
    
    // 市町村タクソノミー（都道府県の子として階層化）
    register_taxonomy('grant_municipality', 'grant', array(
        'labels' => array(
            'name' => '対象市町村',
            'singular_name' => '市町村',
            'search_items' => '市町村を検索',
            'all_items' => 'すべての市町村',
            'parent_item' => '親都道府県',
            'parent_item_colon' => '親都道府県:',
            'edit_item' => '市町村を編集',
            'update_item' => '市町村を更新',
            'add_new_item' => '新しい市町村を追加',
            'new_item_name' => '新しい市町村名'
        ),
        'description' => '助成金・補助金の対象市町村を管理します（都道府県の子分類）',
        'public' => true,
        'publicly_queryable' => true,
        'hierarchical' => true, // 都道府県との親子関係を有効化
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'grant_municipality',
            'with_front' => false,
            'hierarchical' => true
        )
    ));
}
add_action('init', 'gi_register_taxonomies');

/**
 * =============================================================================
 * 3. 都道府県データ初期化
 * =============================================================================
 */

/**
 * 47都道府県の初期データを登録
 */
function gi_init_prefecture_terms() {
    // タクソノミーが存在するか確認
    if (!taxonomy_exists('grant_prefecture')) {
        return;
    }
    
    $prefectures = array(
        // 北海道・東北
        array('name' => '北海道', 'slug' => 'hokkaido'),
        array('name' => '青森県', 'slug' => 'aomori'),
        array('name' => '岩手県', 'slug' => 'iwate'),
        array('name' => '宮城県', 'slug' => 'miyagi'),
        array('name' => '秋田県', 'slug' => 'akita'),
        array('name' => '山形県', 'slug' => 'yamagata'),
        array('name' => '福島県', 'slug' => 'fukushima'),
        // 関東
        array('name' => '茨城県', 'slug' => 'ibaraki'),
        array('name' => '栃木県', 'slug' => 'tochigi'),
        array('name' => '群馬県', 'slug' => 'gunma'),
        array('name' => '埼玉県', 'slug' => 'saitama'),
        array('name' => '千葉県', 'slug' => 'chiba'),
        array('name' => '東京都', 'slug' => 'tokyo'),
        array('name' => '神奈川県', 'slug' => 'kanagawa'),
        // 中部
        array('name' => '新潟県', 'slug' => 'niigata'),
        array('name' => '富山県', 'slug' => 'toyama'),
        array('name' => '石川県', 'slug' => 'ishikawa'),
        array('name' => '福井県', 'slug' => 'fukui'),
        array('name' => '山梨県', 'slug' => 'yamanashi'),
        array('name' => '長野県', 'slug' => 'nagano'),
        array('name' => '岐阜県', 'slug' => 'gifu'),
        array('name' => '静岡県', 'slug' => 'shizuoka'),
        array('name' => '愛知県', 'slug' => 'aichi'),
        // 近畿
        array('name' => '三重県', 'slug' => 'mie'),
        array('name' => '滋賀県', 'slug' => 'shiga'),
        array('name' => '京都府', 'slug' => 'kyoto'),
        array('name' => '大阪府', 'slug' => 'osaka'),
        array('name' => '兵庫県', 'slug' => 'hyogo'),
        array('name' => '奈良県', 'slug' => 'nara'),
        array('name' => '和歌山県', 'slug' => 'wakayama'),
        // 中国
        array('name' => '鳥取県', 'slug' => 'tottori'),
        array('name' => '島根県', 'slug' => 'shimane'),
        array('name' => '岡山県', 'slug' => 'okayama'),
        array('name' => '広島県', 'slug' => 'hiroshima'),
        array('name' => '山口県', 'slug' => 'yamaguchi'),
        // 四国
        array('name' => '徳島県', 'slug' => 'tokushima'),
        array('name' => '香川県', 'slug' => 'kagawa'),
        array('name' => '愛媛県', 'slug' => 'ehime'),
        array('name' => '高知県', 'slug' => 'kochi'),
        // 九州・沖縄
        array('name' => '福岡県', 'slug' => 'fukuoka'),
        array('name' => '佐賀県', 'slug' => 'saga'),
        array('name' => '長崎県', 'slug' => 'nagasaki'),
        array('name' => '熊本県', 'slug' => 'kumamoto'),
        array('name' => '大分県', 'slug' => 'oita'),
        array('name' => '宮崎県', 'slug' => 'miyazaki'),
        array('name' => '鹿児島県', 'slug' => 'kagoshima'),
        array('name' => '沖縄県', 'slug' => 'okinawa')
    );
    
    // 各都道府県を登録
    foreach ($prefectures as $prefecture) {
        if (!term_exists($prefecture['slug'], 'grant_prefecture')) {
            wp_insert_term(
                $prefecture['name'],
                'grant_prefecture',
                array('slug' => $prefecture['slug'])
            );
        }
    }
}
add_action('after_setup_theme', 'gi_init_prefecture_terms');

/**
 * 都道府県データを取得するヘルパー関数
 */
function gi_get_all_prefectures() {
    return array(
        // 北海道・東北
        array('name' => '北海道', 'slug' => 'hokkaido', 'region' => 'hokkaido'),
        array('name' => '青森県', 'slug' => 'aomori', 'region' => 'tohoku'),
        array('name' => '岩手県', 'slug' => 'iwate', 'region' => 'tohoku'),
        array('name' => '宮城県', 'slug' => 'miyagi', 'region' => 'tohoku'),
        array('name' => '秋田県', 'slug' => 'akita', 'region' => 'tohoku'),
        array('name' => '山形県', 'slug' => 'yamagata', 'region' => 'tohoku'),
        array('name' => '福島県', 'slug' => 'fukushima', 'region' => 'tohoku'),
        // 関東
        array('name' => '茨城県', 'slug' => 'ibaraki', 'region' => 'kanto'),
        array('name' => '栃木県', 'slug' => 'tochigi', 'region' => 'kanto'),
        array('name' => '群馬県', 'slug' => 'gunma', 'region' => 'kanto'),
        array('name' => '埼玉県', 'slug' => 'saitama', 'region' => 'kanto'),
        array('name' => '千葉県', 'slug' => 'chiba', 'region' => 'kanto'),
        array('name' => '東京都', 'slug' => 'tokyo', 'region' => 'kanto'),
        array('name' => '神奈川県', 'slug' => 'kanagawa', 'region' => 'kanto'),
        // 中部
        array('name' => '新潟県', 'slug' => 'niigata', 'region' => 'chubu'),
        array('name' => '富山県', 'slug' => 'toyama', 'region' => 'chubu'),
        array('name' => '石川県', 'slug' => 'ishikawa', 'region' => 'chubu'),
        array('name' => '福井県', 'slug' => 'fukui', 'region' => 'chubu'),
        array('name' => '山梨県', 'slug' => 'yamanashi', 'region' => 'chubu'),
        array('name' => '長野県', 'slug' => 'nagano', 'region' => 'chubu'),
        array('name' => '岐阜県', 'slug' => 'gifu', 'region' => 'chubu'),
        array('name' => '静岡県', 'slug' => 'shizuoka', 'region' => 'chubu'),
        array('name' => '愛知県', 'slug' => 'aichi', 'region' => 'chubu'),
        // 近畿
        array('name' => '三重県', 'slug' => 'mie', 'region' => 'kinki'),
        array('name' => '滋賀県', 'slug' => 'shiga', 'region' => 'kinki'),
        array('name' => '京都府', 'slug' => 'kyoto', 'region' => 'kinki'),
        array('name' => '大阪府', 'slug' => 'osaka', 'region' => 'kinki'),
        array('name' => '兵庫県', 'slug' => 'hyogo', 'region' => 'kinki'),
        array('name' => '奈良県', 'slug' => 'nara', 'region' => 'kinki'),
        array('name' => '和歌山県', 'slug' => 'wakayama', 'region' => 'kinki'),
        // 中国
        array('name' => '鳥取県', 'slug' => 'tottori', 'region' => 'chugoku'),
        array('name' => '島根県', 'slug' => 'shimane', 'region' => 'chugoku'),
        array('name' => '岡山県', 'slug' => 'okayama', 'region' => 'chugoku'),
        array('name' => '広島県', 'slug' => 'hiroshima', 'region' => 'chugoku'),
        array('name' => '山口県', 'slug' => 'yamaguchi', 'region' => 'chugoku'),
        // 四国
        array('name' => '徳島県', 'slug' => 'tokushima', 'region' => 'shikoku'),
        array('name' => '香川県', 'slug' => 'kagawa', 'region' => 'shikoku'),
        array('name' => '愛媛県', 'slug' => 'ehime', 'region' => 'shikoku'),
        array('name' => '高知県', 'slug' => 'kochi', 'region' => 'shikoku'),
        // 九州・沖縄
        array('name' => '福岡県', 'slug' => 'fukuoka', 'region' => 'kyushu'),
        array('name' => '佐賀県', 'slug' => 'saga', 'region' => 'kyushu'),
        array('name' => '長崎県', 'slug' => 'nagasaki', 'region' => 'kyushu'),
        array('name' => '熊本県', 'slug' => 'kumamoto', 'region' => 'kyushu'),
        array('name' => '大分県', 'slug' => 'oita', 'region' => 'kyushu'),
        array('name' => '宮崎県', 'slug' => 'miyazaki', 'region' => 'kyushu'),
        array('name' => '鹿児島県', 'slug' => 'kagoshima', 'region' => 'kyushu'),
        array('name' => '沖縄県', 'slug' => 'okinawa', 'region' => 'kyushu')
    );
}

/**
 * 都道府県別市町村マスターデータを取得
 */
function gi_get_municipalities_by_prefecture($prefecture_slug) {
    $municipality_map = array(
        'hokkaido' => array('札幌市', '函館市', '小樽市', '旭川市', '室蘭市', '釧路市', '帯広市', '北見市', '夕張市', '岩見沢市'),
        'aomori' => array('青森市', '弘前市', '八戸市', '黒石市', '五所川原市', 'つがる市', '平川市'),
        'iwate' => array('盛岡市', '宮古市', '大船渡市', '花巻市', '北上市', '久慈市', '遠野市', '一関市', '陸前高田市'),
        'miyagi' => array('仙台市', '石巻市', '塩竈市', '気仙沼市', '白石市', '名取市', '角田市', '多賀城市', '岩沼市', '登米市'),
        'akita' => array('秋田市', '能代市', '横手市', '大館市', '男鹿市', '湯沢市', '鹿角市', '由利本荘市', 'にかほ市'),
        'yamagata' => array('山形市', '米沢市', '鶴岡市', '酒田市', '新庄市', '寒河江市', '上山市', '村山市', '長井市'),
        'fukushima' => array('福島市', '会津若松市', '郡山市', 'いわき市', '白河市', '須賀川市', '喜多方市', '相馬市'),
        'ibaraki' => array('水戸市', '日立市', '土浦市', '古河市', '石岡市', '結城市', '龍ケ崎市', '下妻市', '常総市'),
        'tochigi' => array('宇都宮市', '足利市', '栃木市', '佐野市', '鹿沼市', '日光市', '小山市', '真岡市', '大田原市'),
        'gunma' => array('前橋市', '高崎市', '桐生市', '伊勢崎市', '太田市', '沼田市', '館林市', '渋川市', '藤岡市'),
        'saitama' => array('さいたま市', '川越市', '熊谷市', '川口市', '行田市', '秩父市', '所沢市', '飯能市', '加須市', '本庄市'),
        'chiba' => array('千葉市', '銚子市', '市川市', '船橋市', '館山市', '木更津市', '松戸市', '野田市', '茂原市', '成田市'),
        'tokyo' => array('千代田区', '中央区', '港区', '新宿区', '文京区', '台東区', '墨田区', '江東区', '品川区', '目黒区', '大田区', '世田谷区', '渋谷区', '中野区', '杉並区', '豊島区', '北区', '荒川区', '板橋区', '練馬区', '足立区', '葛飾区', '江戸川区', '八王子市', '立川市', '武蔵野市', '三鷹市', '青梅市', '府中市', '昭島市', '調布市'),
        'kanagawa' => array('横浜市', '川崎市', '相模原市', '横須賀市', '平塚市', '鎌倉市', '藤沢市', '小田原市', '茅ヶ崎市', '逗子市'),
        'niigata' => array('新潟市', '長岡市', '三条市', '柏崎市', '新発田市', '小千谷市', '加茂市', '十日町市', '見附市'),
        'toyama' => array('富山市', '高岡市', '魚津市', '氷見市', '滑川市', '黒部市', '砺波市', '小矢部市', '南砺市'),
        'ishikawa' => array('金沢市', '七尾市', '小松市', '輪島市', '珠洲市', '加賀市', '羽咋市', 'かほく市', '白山市'),
        'fukui' => array('福井市', '敦賀市', '小浜市', '大野市', '勝山市', '鯖江市', 'あわら市', '越前市'),
        'yamanashi' => array('甲府市', '富士吉田市', '都留市', '山梨市', '大月市', '韮崎市', '南アルプス市', '北杜市'),
        'nagano' => array('長野市', '松本市', '上田市', '岡谷市', '飯田市', '諏訪市', '須坂市', '小諸市', '伊那市'),
        'gifu' => array('岐阜市', '大垣市', '高山市', '多治見市', '関市', '中津川市', '美濃市', '瑞浪市', '羽島市'),
        'shizuoka' => array('静岡市', '浜松市', '沼津市', '熱海市', '三島市', '富士宮市', '伊東市', '島田市', '富士市'),
        'aichi' => array('名古屋市', '豊橋市', '岡崎市', '一宮市', '瀬戸市', '半田市', '春日井市', '豊川市', '津島市'),
        'mie' => array('津市', '四日市市', '伊勢市', '松阪市', '桑名市', '鈴鹿市', '名張市', '尾鷲市', '亀山市'),
        'shiga' => array('大津市', '彦根市', '長浜市', '近江八幡市', '草津市', '守山市', '栗東市', '甲賀市', '野洲市'),
        'kyoto' => array('京都市', '福知山市', '舞鶴市', '綾部市', '宇治市', '宮津市', '亀岡市', '城陽市', '向日市'),
        'osaka' => array('大阪市', '堺市', '岸和田市', '豊中市', '池田市', '吹田市', '泉大津市', '高槻市', '貝塚市'),
        'hyogo' => array('神戸市', '姫路市', '尼崎市', '明石市', '西宮市', '洲本市', '芦屋市', '伊丹市', '相生市'),
        'nara' => array('奈良市', '大和高田市', '大和郡山市', '天理市', '橿原市', '桜井市', '五條市', '御所市'),
        'wakayama' => array('和歌山市', '海南市', '橋本市', '有田市', '御坊市', '田辺市', '新宮市', '紀の川市'),
        'tottori' => array('鳥取市', '米子市', '倉吉市', '境港市'),
        'shimane' => array('松江市', '浜田市', '出雲市', '益田市', '大田市', '安来市', '江津市', '雲南市'),
        'okayama' => array('岡山市', '倉敷市', '津山市', '玉野市', '笠岡市', '井原市', '総社市', '高梁市', '新見市'),
        'hiroshima' => array('広島市', '呉市', '竹原市', '三原市', '尾道市', '福山市', '府中市', '三次市', '庄原市'),
        'yamaguchi' => array('下関市', '宇部市', '山口市', '萩市', '防府市', '下松市', '岩国市', '光市', '長門市'),
        'tokushima' => array('徳島市', '鳴門市', '小松島市', '阿南市', '吉野川市', '阿波市', '美馬市', '三好市'),
        'kagawa' => array('高松市', '丸亀市', '坂出市', '善通寺市', '観音寺市', 'さぬき市', '東かがわ市', '三豊市'),
        'ehime' => array('松山市', '今治市', '宇和島市', '八幡浜市', '新居浜市', '西条市', '大洲市', '伊予市'),
        'kochi' => array('高知市', '室戸市', '安芸市', '南国市', '土佐市', '須崎市', '宿毛市', '土佐清水市'),
        'fukuoka' => array('北九州市', '福岡市', '大牟田市', '久留米市', '直方市', '飯塚市', '田川市', '柳川市'),
        'saga' => array('佐賀市', '唐津市', '鳥栖市', '多久市', '伊万里市', '武雄市', '鹿島市', '小城市'),
        'nagasaki' => array('長崎市', '佐世保市', '島原市', '諫早市', '大村市', '平戸市', '松浦市', '対馬市'),
        'kumamoto' => array('熊本市', '八代市', '人吉市', '荒尾市', '水俣市', '玉名市', '山鹿市', '菊池市'),
        'oita' => array('大分市', '別府市', '中津市', '日田市', '佐伯市', '臼杵市', '津久見市', '竹田市'),
        'miyazaki' => array('宮崎市', '都城市', '延岡市', '日南市', '小林市', '日向市', '串間市', '西都市'),
        'kagoshima' => array('鹿児島市', '鹿屋市', '枕崎市', '阿久根市', '出水市', '指宿市', '西之表市', '垂水市'),
        'okinawa' => array('那覇市', '宜野湾市', '石垣市', '浦添市', '名護市', '糸満市', '沖縄市', '豊見城市')
    );
    
    return isset($municipality_map[$prefecture_slug]) ? $municipality_map[$prefecture_slug] : array();
}

/**
 * 都道府県の市町村タームを初期化
 */
function gi_init_municipalities_for_prefecture($prefecture_slug, $prefecture_name) {
    $municipalities = gi_get_municipalities_by_prefecture($prefecture_slug);
    
    if (empty($municipalities)) {
        return false;
    }
    
    // 都道府県レベルの市町村ターム（従来通り）
    $prefecture_muni_term = get_term_by('name', $prefecture_name, 'grant_municipality');
    if (!$prefecture_muni_term) {
        $pref_result = wp_insert_term(
            $prefecture_name,
            'grant_municipality',
            array(
                'slug' => $prefecture_slug . '-prefecture-level',
                'description' => $prefecture_name . 'レベルの助成金'
            )
        );
        $prefecture_muni_term_id = !is_wp_error($pref_result) ? $pref_result['term_id'] : null;
    } else {
        $prefecture_muni_term_id = $prefecture_muni_term->term_id;
    }
    
    // 各市町村のタームを作成（都道府県タームの子要素として）
    foreach ($municipalities as $municipality_name) {
        $existing_term = get_term_by('name', $municipality_name, 'grant_municipality');
        if (!$existing_term) {
            wp_insert_term(
                $municipality_name,
                'grant_municipality',
                array(
                    'slug' => $prefecture_slug . '-' . sanitize_title($municipality_name),
                    'description' => $prefecture_name . 'の' . $municipality_name,
                    'parent' => $prefecture_muni_term_id
                )
            );
        }
    }
    
    return true;
}

/**
 * 全都道府県の市町村データを初期化（一度だけ実行）
 */
function gi_init_all_municipalities() {
    $init_done = get_option('gi_municipalities_initialized', false);
    
    if (!$init_done) {
        $prefectures = gi_get_all_prefectures();
        
        foreach ($prefectures as $prefecture) {
            gi_init_municipalities_for_prefecture($prefecture['slug'], $prefecture['name']);
        }
        
        update_option('gi_municipalities_initialized', true);
    }
}
add_action('after_setup_theme', 'gi_init_all_municipalities', 15);

/**
 * =============================================================================
 * 5. データクリーンアップ・最適化機能
 * =============================================================================
 */

/**
 * Note: gi_cleanup_and_optimize_location_data function is defined later in this file
 * with enhanced error handling and comprehensive cleanup logic.
 */

/**
 * 都道府県・市町村データの完全リセット
 */
function gi_reset_location_data() {
    // 既存のオプションフラグをリセット
    delete_option('gi_municipalities_initialized');
    delete_option('gi_prefecture_municipality_sync_done');
    
    // 全ての都道府県のタームを削除
    $prefectures = get_terms(['taxonomy' => 'grant_prefecture', 'hide_empty' => false]);
    foreach ($prefectures as $prefecture) {
        wp_delete_term($prefecture->term_id, 'grant_prefecture');
    }
    
    // 全ての市町村のタームを削除
    $municipalities = get_terms(['taxonomy' => 'grant_municipality', 'hide_empty' => false]);
    foreach ($municipalities as $municipality) {
        wp_delete_term($municipality->term_id, 'grant_municipality');
    }
    
    // 都道府県データを再初期化
    gi_init_prefecture_terms();
    
    // 市町村データを再初期化
    gi_init_all_municipalities();
    
    return true;
}

/**
 * Note: gi_enhanced_init_municipalities_for_prefecture function is defined later in this file
 * with enhanced error handling and improved logic.
 */

/**
 * =============================================================================
 * 4. ユーティリティ関数
 * =============================================================================
 */

/**
 * パンくずリスト生成関数
 */
function gi_breadcrumbs() {
    if (is_front_page()) return;
    
    echo '<nav class="breadcrumbs">';
    echo '<a href="' . home_url() . '">ホーム</a>';
    
    if (is_post_type_archive('grant')) {
        echo ' > 助成金・補助金一覧';
    } elseif (is_tax('grant_category')) {
        echo ' > <a href="' . get_post_type_archive_link('grant') . '">助成金・補助金一覧</a>';
        echo ' > ' . single_term_title('', false);
    } elseif (is_tax('grant_prefecture')) {
        echo ' > <a href="' . get_post_type_archive_link('grant') . '">助成金・補助金一覧</a>';
        echo ' > ' . single_term_title('', false);
    } elseif (is_singular('grant')) {
        echo ' > <a href="' . get_post_type_archive_link('grant') . '">助成金・補助金一覧</a>';
        echo ' > ' . get_the_title();
    } elseif (is_page()) {
        echo ' > ' . get_the_title();
    } elseif (is_single()) {
        $categories = get_the_category();
        if ($categories) {
            echo ' > <a href="' . get_category_link($categories[0]->term_id) . '">' . $categories[0]->name . '</a>';
        }
        echo ' > ' . get_the_title();
    }
    
    echo '</nav>';
}

/**
 * ページネーション関数
 */
function gi_pagination($pages = '') {
    global $paged;
    
    if (empty($paged)) $paged = 1;
    
    if ($pages == '') {
        global $wp_query;
        $pages = $wp_query->max_num_pages;
        if (!$pages) $pages = 1;
    }
    
    if ($pages != 1) {
        echo '<div class="pagination">';
        
        if ($paged > 1) {
            echo '<a href="' . get_pagenum_link($paged - 1) . '" class="prev">前へ</a>';
        }
        
        for ($i = 1; $i <= $pages; $i++) {
            if ($paged == $i) {
                echo '<span class="current">' . $i . '</span>';
            } else {
                echo '<a href="' . get_pagenum_link($i) . '">' . $i . '</a>';
            }
        }
        
        if ($paged < $pages) {
            echo '<a href="' . get_pagenum_link($paged + 1) . '" class="next">次へ</a>';
        }
        
        echo '</div>';
    }
}

/**
 * 管理画面用のアセット - 統合版
 */
function gi_admin_assets() {
    // 統合管理画面CSS読み込み
    wp_enqueue_style('gi-admin-consolidated', get_template_directory_uri() . '/assets/css/admin-consolidated.css', array(), GI_THEME_VERSION);
    
    // 統合管理画面JavaScript読み込み
    wp_enqueue_script('gi-admin-consolidated', get_template_directory_uri() . '/assets/js/admin-consolidated.js', array('jquery'), GI_THEME_VERSION, true);
    
    // JavaScript設定の出力
    wp_localize_script('gi-admin-consolidated', 'giSheetsAdmin', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gi_admin_nonce'),
        'strings' => array(
            'testing' => 'テスト中...',
            'syncing' => '同期中...',
            'confirm_sync' => '同期を実行しますか？この操作には時間がかかる場合があります。'
        )
    ));
    
    wp_localize_script('gi-admin-consolidated', 'grantMetaboxes', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gi_metabox_nonce')
    ));
    
    // 管理画面メニューアイコン設定
    echo '<style>
        #adminmenu .menu-icon-grant div.wp-menu-image:before {
            content: "\f155";
        }
    </style>';
}
add_action('admin_enqueue_scripts', 'gi_admin_assets');

/**
 * =============================================================================
 * HTTPS 強制化・Mixed Content対策
 * =============================================================================
 */

/**
 * アップロードディレクトリをHTTPSに強制
 */
function gi_force_https_uploads($upload_dir) {
    if (is_ssl()) {
        $upload_dir['url'] = str_replace('http://', 'https://', $upload_dir['url']);
        $upload_dir['baseurl'] = str_replace('http://', 'https://', $upload_dir['baseurl']);
    }
    return $upload_dir;
}

/**
 * 添付ファイルURLをHTTPSに強制
 */
function gi_force_https_url($url) {
    if (is_ssl() && !empty($url)) {
        $url = str_replace('http://', 'https://', $url);
    }
    return $url;
}

/**
 * すべてのコンテンツURLをHTTPSに変換
 */
function gi_force_https_content($content) {
    if (is_ssl()) {
        $site_url = get_site_url();
        $http_site_url = str_replace('https://', 'http://', $site_url);
        $content = str_replace($http_site_url, $site_url, $content);
    }
    return $content;
}
add_filter('the_content', 'gi_force_https_content');
add_filter('widget_text', 'gi_force_https_content');

// 追加のMixed Content対策
add_filter('wp_get_attachment_image_attributes', 'gi_force_https_image_attributes');
add_filter('wp_calculate_image_srcset', 'gi_force_https_srcset');

/**
 * カスタマイザーでのMixed Content警告抑制
 */
function gi_customize_https_fix() {
    if (is_customize_preview() && is_ssl()) {
        add_filter('wp_get_attachment_url', function($url) {
            return str_replace('http://', 'https://', $url);
        });
        add_filter('wp_get_attachment_image_src', function($image) {
            if (is_array($image) && isset($image[0])) {
                $image[0] = str_replace('http://', 'https://', $image[0]);
            }
            return $image;
        });
    }
}
add_action('init', 'gi_customize_https_fix');

/**
 * =============================================================================
 * 都道府県・市町村データ最適化機能
 * =============================================================================
 */

/**
 * 都道府県・市町村データをクリーンアップして最適化
 */
function gi_cleanup_and_optimize_location_data() {
    global $wpdb;
    
    try {
        // 1. 重複する都道府県タームを削除
        $prefecture_duplicates = $wpdb->get_results("
            SELECT name, COUNT(*) as count 
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
            WHERE tt.taxonomy = 'grant_prefecture'
            GROUP BY name 
            HAVING count > 1
        ");
        
        $cleaned_prefectures = 0;
        foreach ($prefecture_duplicates as $duplicate) {
            // 同じ名前の都道府県タームを取得
            $duplicate_terms = get_terms([
                'taxonomy' => 'grant_prefecture',
                'name' => $duplicate->name,
                'hide_empty' => false
            ]);
            
            if (count($duplicate_terms) > 1) {
                // 最初のターム以外を削除（投稿との関連付けを保持しながら）
                $keep_term = array_shift($duplicate_terms);
                
                foreach ($duplicate_terms as $term_to_delete) {
                    // 投稿との関連付けを保持するタームに移行
                    $posts_to_update = get_objects_in_term($term_to_delete->term_id, 'grant_prefecture');
                    if (!empty($posts_to_update)) {
                        foreach ($posts_to_update as $post_id) {
                            wp_set_object_terms($post_id, $keep_term->term_id, 'grant_prefecture', true);
                        }
                    }
                    
                    // 重複タームを削除
                    wp_delete_term($term_to_delete->term_id, 'grant_prefecture');
                    $cleaned_prefectures++;
                }
            }
        }
        
        // 2. 重複する市町村タームを削除
        $municipality_duplicates = $wpdb->get_results("
            SELECT name, COUNT(*) as count 
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
            WHERE tt.taxonomy = 'grant_municipality'
            GROUP BY name 
            HAVING count > 1
        ");
        
        $cleaned_municipalities = 0;
        foreach ($municipality_duplicates as $duplicate) {
            $duplicate_terms = get_terms([
                'taxonomy' => 'grant_municipality',
                'name' => $duplicate->name,
                'hide_empty' => false
            ]);
            
            if (count($duplicate_terms) > 1) {
                // prefecture_slug メタデータでグループ化
                $grouped_terms = [];
                foreach ($duplicate_terms as $term) {
                    $pref_slug = get_term_meta($term->term_id, 'prefecture_slug', true);
                    $grouped_terms[$pref_slug][] = $term;
                }
                
                // 各都道府県グループ内で重複を削除
                foreach ($grouped_terms as $pref_slug => $terms) {
                    if (count($terms) > 1) {
                        $keep_term = array_shift($terms);
                        
                        foreach ($terms as $term_to_delete) {
                            // 投稿との関連付けを移行
                            $posts_to_update = get_objects_in_term($term_to_delete->term_id, 'grant_municipality');
                            if (!empty($posts_to_update)) {
                                foreach ($posts_to_update as $post_id) {
                                    wp_set_object_terms($post_id, $keep_term->term_id, 'grant_municipality', true);
                                }
                            }
                            
                            wp_delete_term($term_to_delete->term_id, 'grant_municipality');
                            $cleaned_municipalities++;
                        }
                    }
                }
            }
        }
        
        // 3. 都道府県メタデータの修復
        $municipalities_without_metadata = get_terms([
            'taxonomy' => 'grant_municipality',
            'hide_empty' => false,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'prefecture_slug',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => 'prefecture_slug',
                    'value' => '',
                    'compare' => '='
                ]
            ]
        ]);
        
        $fixed_metadata = 0;
        foreach ($municipalities_without_metadata as $municipality) {
            // スラッグから都道府県を推定
            $slug_parts = explode('-', $municipality->slug);
            if (count($slug_parts) >= 2) {
                $possible_pref_slug = $slug_parts[0];
                
                // 都道府県の存在確認
                $prefecture_term = get_term_by('slug', $possible_pref_slug, 'grant_prefecture');
                if ($prefecture_term) {
                    update_term_meta($municipality->term_id, 'prefecture_slug', $possible_pref_slug);
                    update_term_meta($municipality->term_id, 'prefecture_name', $prefecture_term->name);
                    $fixed_metadata++;
                }
            }
        }
        
        return [
            'success' => true,
            'cleaned_prefectures' => $cleaned_prefectures,
            'cleaned_municipalities' => $cleaned_municipalities,
            'fixed_metadata' => $fixed_metadata,
            'message' => "データクリーンアップ完了: 都道府県重複{$cleaned_prefectures}件、市町村重複{$cleaned_municipalities}件、メタデータ修復{$fixed_metadata}件"
        ];
        
    } catch (Exception $e) {
        error_log('Location Data Cleanup Error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'データクリーンアップ中にエラーが発生しました: ' . $e->getMessage()
        ];
    }
}

/**
 * 強化された市町村初期化機能（重複防止付き）
 */
function gi_enhanced_init_municipalities_for_prefecture($prefecture_slug, $prefecture_name) {
    try {
        // 既存の市町村数をチェック
        $existing_municipalities = get_terms([
            'taxonomy' => 'grant_municipality',
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key' => 'prefecture_slug',
                    'value' => $prefecture_slug,
                    'compare' => '='
                ]
            ]
        ]);
        
        // 既に十分な数の市町村がある場合はスキップ
        if (count($existing_municipalities) > 5) {
            return [
                'success' => true,
                'message' => "{$prefecture_name}の市町村データは既に存在しています",
                'count' => count($existing_municipalities)
            ];
        }
        
        // 標準市町村リストを取得
        $standard_municipalities = gi_get_standard_municipalities_by_prefecture($prefecture_slug);
        
        if (empty($standard_municipalities)) {
            return [
                'success' => false,
                'message' => "{$prefecture_name}の標準市町村データが見つかりません"
            ];
        }
        
        $created_count = 0;
        $updated_count = 0;
        
        foreach ($standard_municipalities as $municipality_name) {
            $municipality_slug = $prefecture_slug . '-' . sanitize_title($municipality_name);
            
            // 既存チェック
            $existing_term = get_term_by('slug', $municipality_slug, 'grant_municipality');
            
            if (!$existing_term) {
                // 新規作成
                $result = wp_insert_term(
                    $municipality_name,
                    'grant_municipality',
                    [
                        'slug' => $municipality_slug,
                        'description' => $prefecture_name . '・' . $municipality_name
                    ]
                );
                
                if (!is_wp_error($result)) {
                    // メタデータ追加
                    add_term_meta($result['term_id'], 'prefecture_slug', $prefecture_slug);
                    add_term_meta($result['term_id'], 'prefecture_name', $prefecture_name);
                    $created_count++;
                }
            } else {
                // 既存タームのメタデータ更新
                $current_pref_slug = get_term_meta($existing_term->term_id, 'prefecture_slug', true);
                if (empty($current_pref_slug)) {
                    update_term_meta($existing_term->term_id, 'prefecture_slug', $prefecture_slug);
                    update_term_meta($existing_term->term_id, 'prefecture_name', $prefecture_name);
                    $updated_count++;
                }
            }
        }
        
        return [
            'success' => true,
            'created' => $created_count,
            'updated' => $updated_count,
            'message' => "{$prefecture_name}: 新規作成{$created_count}件、更新{$updated_count}件"
        ];
        
    } catch (Exception $e) {
        error_log("Enhanced Municipality Init Error for {$prefecture_slug}: " . $e->getMessage());
        return [
            'success' => false,
            'message' => "市町村初期化エラー: " . $e->getMessage()
        ];
    }
}

/**
 * 都道府県別標準市町村リストを取得
 */
function gi_get_standard_municipalities_by_prefecture($pref_slug) {
    // 主要都道府県の市町村リスト（簡略版）
    $municipalities_data = [
        'hokkaido' => ['札幌市', '函館市', '小樽市', '旭川市', '室蘭市', '釧路市', '帯広市', '北見市', '夕張市', '岩見沢市'],
        'aomori' => ['青森市', '弘前市', '八戸市', '黒石市', '五所川原市', '十和田市', 'つがる市', '平川市'],
        'iwate' => ['盛岡市', '宮古市', '大船渡市', '花巻市', '北上市', '久慈市', '遠野市', '一関市', '陸前高田市', '釜石市'],
        'miyagi' => ['仙台市', '石巻市', '塩竈市', '気仙沼市', '白石市', '名取市', '角田市', '多賀城市', '岩沼市', '登米市'],
        'akita' => ['秋田市', '能代市', '横手市', '大館市', '男鹿市', '湯沢市', '鹿角市', '由利本荘市', '潟上市', '大仙市'],
        'yamagata' => ['山形市', '米沢市', '鶴岡市', '酒田市', '新庄市', '寒河江市', '上山市', '村山市', '長井市', '天童市'],
        'fukushima' => ['福島市', '会津若松市', '郡山市', 'いわき市', '白河市', '須賀川市', '喜多方市', '相馬市', '二本松市', '田村市'],
        'ibaraki' => ['水戸市', '日立市', '土浦市', '古河市', '石岡市', '結城市', '龍ケ崎市', '下妻市', '常総市', '常陸太田市'],
        'tochigi' => ['宇都宮市', '足利市', '栃木市', '佐野市', '鹿沼市', '日光市', '小山市', '真岡市', '大田原市', '矢板市'],
        'gunma' => ['前橋市', '高崎市', '桐生市', '伊勢崎市', '太田市', '沼田市', '館林市', '渋川市', '藤岡市', '富岡市'],
        'saitama' => ['さいたま市', '川越市', '熊谷市', '川口市', '行田市', '秩父市', '所沢市', '飯能市', '加須市', '本庄市'],
        'chiba' => ['千葉市', '銚子市', '市川市', '船橋市', '館山市', '木更津市', '松戸市', '野田市', '茂原市', '成田市'],
        'tokyo' => ['千代田区', '中央区', '港区', '新宿区', '文京区', '台東区', '墨田区', '江東区', '品川区', '目黒区', '大田区', '世田谷区', '渋谷区', '中野区', '杉並区', '豊島区', '北区', '荒川区', '板橋区', '練馬区', '足立区', '葛飾区', '江戸川区'],
        'kanagawa' => ['横浜市', '川崎市', '相模原市', '横須賀市', '平塚市', '鎌倉市', '藤沢市', '小田原市', '茅ヶ崎市', '逗子市'],
        'niigata' => ['新潟市', '長岡市', '三条市', '柏崎市', '新発田市', '小千谷市', '加茂市', '十日町市', '見附市', '村上市'],
        'toyama' => ['富山市', '高岡市', '魚津市', '氷見市', '滑川市', '黒部市', '砺波市', '小矢部市', '南砺市', '射水市'],
        'ishikawa' => ['金沢市', '七尾市', '小松市', '輪島市', '珠洲市', '加賀市', '羽咋市', 'かほく市', '白山市', '能美市'],
        'fukui' => ['福井市', '敦賀市', '小浜市', '大野市', '勝山市', '鯖江市', 'あわら市', '越前市'],
        'yamanashi' => ['甲府市', '富士吉田市', '都留市', '山梨市', '大月市', '韮崎市', '南アルプス市', '北杜市', '甲斐市', '笛吹市'],
        'nagano' => ['長野市', '松本市', '上田市', '岡谷市', '飯田市', '諏訪市', '須坂市', '小諸市', '伊那市', '駒ヶ根市'],
        'gifu' => ['岐阜市', '大垣市', '高山市', '多治見市', '関市', '中津川市', '美濃市', '瑞浪市', '羽島市', '恵那市'],
        'shizuoka' => ['静岡市', '浜松市', '沼津市', '熱海市', '三島市', '富士宮市', '伊東市', '島田市', '富士市', '磐田市'],
        'aichi' => ['名古屋市', '豊橋市', '岡崎市', '一宮市', '瀬戸市', '半田市', '春日井市', '豊川市', '津島市', '碧南市'],
        'mie' => ['津市', '四日市市', '伊勢市', '松阪市', '桑名市', '鈴鹿市', '名張市', '尾鷲市', '亀山市', '鳥羽市'],
        'shiga' => ['大津市', '彦根市', '長浜市', '近江八幡市', '草津市', '守山市', '栗東市', '甲賀市', '野洲市', '湖南市'],
        'kyoto' => ['京都市', '福知山市', '舞鶴市', '綾部市', '宇治市', '宮津市', '亀岡市', '城陽市', '向日市', '長岡京市'],
        'osaka' => ['大阪市', '堺市', '岸和田市', '豊中市', '池田市', '吹田市', '泉大津市', '高槻市', '貝塚市', '守口市'],
        'hyogo' => ['神戸市', '姫路市', '尼崎市', '明石市', '西宮市', '洲本市', '芦屋市', '伊丹市', '相生市', '豊岡市'],
        'nara' => ['奈良市', '大和高田市', '大和郡山市', '天理市', '橿原市', '桜井市', '五條市', '御所市', '生駒市', '香芝市'],
        'wakayama' => ['和歌山市', '海南市', '橋本市', '有田市', '御坊市', '田辺市', '新宮市', '紀の川市', '岩出市'],
        'tottori' => ['鳥取市', '米子市', '倉吉市', '境港市'],
        'shimane' => ['松江市', '浜田市', '出雲市', '益田市', '大田市', '安来市', '江津市', '雲南市'],
        'okayama' => ['岡山市', '倉敷市', '津山市', '玉野市', '笠岡市', '井原市', '総社市', '高梁市', '新見市', '備前市'],
        'hiroshima' => ['広島市', '呉市', '竹原市', '三原市', '尾道市', '福山市', '府中市', '三次市', '庄原市', '大竹市'],
        'yamaguchi' => ['下関市', '宇部市', '山口市', '萩市', '防府市', '下松市', '岩国市', '光市', '長門市', '柳井市'],
        'tokushima' => ['徳島市', '鳴門市', '小松島市', '阿南市', '吉野川市', '阿波市', '美馬市', '三好市'],
        'kagawa' => ['高松市', '丸亀市', '坂出市', '善通寺市', '観音寺市', 'さぬき市', '東かがわ市', '三豊市'],
        'ehime' => ['松山市', '今治市', '宇和島市', '八幡浜市', '新居浜市', '西条市', '大洲市', '伊予市', '四国中央市', '西予市'],
        'kochi' => ['高知市', '室戸市', '安芸市', '南国市', '土佐市', '須崎市', '宿毛市', '土佐清水市', '四万十市', '香南市'],
        'fukuoka' => ['北九州市', '福岡市', '大牟田市', '久留米市', '直方市', '飯塚市', '田川市', '柳川市', '八女市', '筑後市'],
        'saga' => ['佐賀市', '唐津市', '鳥栖市', '多久市', '伊万里市', '武雄市', '鹿島市', '小城市', '嬉野市', '神埼市'],
        'nagasaki' => ['長崎市', '佐世保市', '島原市', '諫早市', '大村市', '平戸市', '松浦市', '対馬市', '壱岐市', '五島市'],
        'kumamoto' => ['熊本市', '八代市', '人吉市', '荒尾市', '水俣市', '玉名市', '山鹿市', '菊池市', '宇土市', '上天草市'],
        'oita' => ['大分市', '別府市', '中津市', '日田市', '佐伯市', '臼杵市', '津久見市', '竹田市', '豊後高田市', '杵築市'],
        'miyazaki' => ['宮崎市', '都城市', '延岡市', '日南市', '小林市', '日向市', '串間市', '西都市', 'えびの市'],
        'kagoshima' => ['鹿児島市', '鹿屋市', '枕崎市', '阿久根市', '出水市', '指宿市', '西之表市', '垂水市', '薩摩川内市', '日置市'],
        'okinawa' => ['那覇市', '宜野湾市', '石垣市', '浦添市', '名護市', '糸満市', '沖縄市', '豊見城市', 'うるま市', '宮古島市']
    ];
    
    return $municipalities_data[$pref_slug] ?? [];
}

/**
 * 全都道府県の市町村データを一括初期化
 */
function gi_initialize_all_municipalities() {
    $prefectures = get_terms([
        'taxonomy' => 'grant_prefecture',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ]);
    
    $results = [];
    $total_created = 0;
    $total_updated = 0;
    
    if (!empty($prefectures) && !is_wp_error($prefectures)) {
        foreach ($prefectures as $prefecture) {
            $result = gi_enhanced_init_municipalities_for_prefecture($prefecture->slug, $prefecture->name);
            $results[] = $result;
            
            if ($result['success']) {
                $total_created += $result['created'] ?? 0;
                $total_updated += $result['updated'] ?? 0;
            }
        }
    }
    
    return [
        'success' => true,
        'total_created' => $total_created,
        'total_updated' => $total_updated,
        'details' => $results,
        'message' => "全都道府県市町村初期化完了: 作成{$total_created}件、更新{$total_updated}件"
    ];
}

/**
 * データ最適化を実行する管理画面用関数
 */
function gi_run_location_data_optimization() {
    // 管理者権限チェック
    if (!current_user_can('manage_options')) {
        return ['success' => false, 'message' => '権限が不足しています'];
    }
    
    // 1. データクリーンアップ
    $cleanup_result = gi_cleanup_and_optimize_location_data();
    
    // 2. 市町村データ初期化
    $init_result = gi_initialize_all_municipalities();
    
    return [
        'success' => true,
        'cleanup' => $cleanup_result,
        'initialization' => $init_result,
        'message' => 'データ最適化が完了しました'
    ];
}

/**
 * URL パラメータによる最適化実行（開発用）
 */
function gi_handle_optimization_request() {
    // セキュリティチェック
    if (!isset($_GET['gi_optimize']) || $_GET['gi_optimize'] !== 'run_now') {
        return;
    }
    
    if (!current_user_can('manage_options')) {
        wp_die('権限が不足しています');
    }
    
    // 最適化実行
    $result = gi_run_location_data_optimization();
    
    // 結果表示
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>データ最適化結果</title></head><body>';
    echo '<h1>都道府県・市町村データ最適化結果</h1>';
    echo '<div style="font-family: monospace; white-space: pre-line; background: #f9f9f9; padding: 20px; margin: 20px;">';
    
    if ($result['success']) {
        echo "✅ 最適化成功\n\n";
        
        if (isset($result['cleanup'])) {
            echo "=== クリーンアップ結果 ===\n";
            echo "都道府県重複削除: " . ($result['cleanup']['cleaned_prefectures'] ?? 0) . "件\n";
            echo "市町村重複削除: " . ($result['cleanup']['cleaned_municipalities'] ?? 0) . "件\n";
            echo "メタデータ修復: " . ($result['cleanup']['fixed_metadata'] ?? 0) . "件\n\n";
        }
        
        if (isset($result['initialization'])) {
            echo "=== 初期化結果 ===\n";
            echo "新規作成: " . ($result['initialization']['total_created'] ?? 0) . "件\n";
            echo "更新: " . ($result['initialization']['total_updated'] ?? 0) . "件\n\n";
        }
        
        // 現在の都道府県一覧を表示
        echo "=== 現在の都道府県一覧 ===\n";
        $prefectures = get_terms([
            'taxonomy' => 'grant_prefecture',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);
        
        if (!empty($prefectures) && !is_wp_error($prefectures)) {
            foreach ($prefectures as $pref) {
                $municipalities_count = wp_count_terms([
                    'taxonomy' => 'grant_municipality',
                    'hide_empty' => false,
                    'meta_query' => [
                        [
                            'key' => 'prefecture_slug',
                            'value' => $pref->slug,
                            'compare' => '='
                        ]
                    ]
                ]);
                
                echo "- {$pref->name} ({$pref->slug}): {$municipalities_count}市町村\n";
            }
        }
    } else {
        echo "❌ 最適化失敗: " . $result['message'];
    }
    
    echo '</div>';
    echo '<p><a href="' . admin_url() . '">管理画面に戻る</a> | <a href="' . home_url('/grants/') . '">助成金一覧</a></p>';
    echo '</body></html>';
    exit;
}
add_action('init', 'gi_handle_optimization_request');

/**
 * 管理画面メニューに最適化ページを追加
 */
function gi_add_optimization_admin_menu() {
    add_management_page(
        '都道府県・市町村データ最適化',
        'データ最適化',
        'manage_options',
        'gi-location-optimization',
        'gi_optimization_admin_page'
    );
}
add_action('admin_menu', 'gi_add_optimization_admin_menu');

/**
 * 最適化管理画面の表示
 */
function gi_optimization_admin_page() {
    // 最適化実行処理
    if (isset($_POST['gi_run_optimization']) && wp_verify_nonce($_POST['gi_optimization_nonce'], 'gi_optimization')) {
        $result = gi_run_location_data_optimization();
        
        echo '<div class="notice notice-' . ($result['success'] ? 'success' : 'error') . '"><p>';
        echo $result['success'] ? '✅ ' : '❌ ';
        echo esc_html($result['message']);
        echo '</p></div>';
        
        if ($result['success'] && isset($result['cleanup']) && isset($result['initialization'])) {
            echo '<div class="notice notice-info"><p>';
            echo '重複削除: ' . ($result['cleanup']['cleaned_prefectures'] ?? 0) . '都道府県, ' . ($result['cleanup']['cleaned_municipalities'] ?? 0) . '市町村<br>';
            echo 'メタデータ修復: ' . ($result['cleanup']['fixed_metadata'] ?? 0) . '件<br>';
            echo '新規作成: ' . ($result['initialization']['total_created'] ?? 0) . '件, 更新: ' . ($result['initialization']['total_updated'] ?? 0) . '件';
            echo '</p></div>';
        }
    }
    
    ?>
    <div class="wrap">
        <h1>都道府県・市町村データ最適化</h1>
        
        <?php
        // 現在の状態を表示
        $prefectures = get_terms(['taxonomy' => 'grant_prefecture', 'hide_empty' => false]);
        $municipalities = get_terms(['taxonomy' => 'grant_municipality', 'hide_empty' => false]);
        
        // 重複チェック
        $pref_names = [];
        $pref_duplicates = 0;
        if (!empty($prefectures)) {
            $pref_names = array_column($prefectures, 'name');
            $pref_duplicates = count($pref_names) - count(array_unique($pref_names));
        }
        
        // メタデータ不足チェック
        $municipalities_without_metadata = 0;
        if (!empty($municipalities)) {
            foreach ($municipalities as $municipality) {
                if (empty(get_term_meta($municipality->term_id, 'prefecture_slug', true))) {
                    $municipalities_without_metadata++;
                }
            }
        }
        ?>
        
        <div class="card">
            <h2>現在の状態</h2>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <th>項目</th>
                    <th>件数</th>
                    <th>状態</th>
                </tr>
                <tr>
                    <td>都道府県</td>
                    <td><?php echo count($prefectures ?? []); ?></td>
                    <td><?php echo $pref_duplicates > 0 ? '<span style="color:red;">重複' . $pref_duplicates . '件</span>' : '<span style="color:green;">正常</span>'; ?></td>
                </tr>
                <tr>
                    <td>市町村</td>
                    <td><?php echo count($municipalities ?? []); ?></td>
                    <td><?php echo $municipalities_without_metadata > 0 ? '<span style="color:orange;">メタデータ不足' . $municipalities_without_metadata . '件</span>' : '<span style="color:green;">正常</span>'; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h2>都道府県別市町村数</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>都道府県</th>
                        <th>スラッグ</th>
                        <th>市町村数</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($prefectures)):
                        foreach ($prefectures as $prefecture):
                            $municipality_count = wp_count_terms([
                                'taxonomy' => 'grant_municipality',
                                'hide_empty' => false,
                                'meta_query' => [
                                    [
                                        'key' => 'prefecture_slug',
                                        'value' => $prefecture->slug,
                                        'compare' => '='
                                    ]
                                ]
                            ]);
                            
                            $row_style = $municipality_count == 0 ? 'background-color: #ffcccc;' : ($municipality_count < 5 ? 'background-color: #fff3cd;' : '');
                    ?>
                    <tr style="<?php echo $row_style; ?>">
                        <td><?php echo esc_html($prefecture->name); ?></td>
                        <td><?php echo esc_html($prefecture->slug); ?></td>
                        <td><?php echo $municipality_count; ?></td>
                    </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <tr>
                        <td colspan="3">都道府県データが見つかりません</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h2>データ最適化</h2>
            <p>以下の処理を実行します：</p>
            <ul>
                <li>重複する都道府県・市町村タームの統合</li>
                <li>市町村の都道府県メタデータ修復</li>
                <li>不足している市町村データの補完</li>
            </ul>
            
            <form method="post">
                <?php wp_nonce_field('gi_optimization', 'gi_optimization_nonce'); ?>
                <p>
                    <input type="submit" name="gi_run_optimization" class="button button-primary" value="データ最適化を実行" onclick="return confirm('データ最適化を実行しますか？この処理は元に戻すことができません。');">
                </p>
            </form>
        </div>
        
        <div class="card">
            <h2>🔧 都道府県レベル助成金の市町村自動設定</h2>
            <p>都道府県レベルの助成金で市町村が正しく設定されていない場合の一括修正機能です。</p>
            <div class="notice notice-info" style="margin: 15px 0;">
                <p><strong>📍 都道府県レベル助成金とは：</strong></p>
                <ul>
                    <li>地域制限が「都道府県のみ」「全国」または未設定の助成金</li>
                    <li>都道府県全域が対象で、市町村は自動で設定されます</li>
                    <li>例：「東京都-prefecture-level」という市町村タームが自動作成されます</li>
                </ul>
            </div>
            
            <div id="prefecture-fix-result" style="display: none; margin: 15px 0;"></div>
            
            <p>
                <button type="button" id="fix-prefecture-municipalities" class="button button-primary" style="background: #e67e22; border-color: #d35400;">
                    🔧 都道府県レベル助成金の市町村を一括修正
                </button>
                <span class="spinner" id="fix-spinner" style="float: none; margin-left: 10px;"></span>
            </p>
            
            <script>
            jQuery(document).ready(function($) {
                $('#fix-prefecture-municipalities').on('click', function() {
                    if (!confirm('都道府県レベル助成金の市町村設定を一括で修正します。\n\n処理には時間がかかる場合があります。実行しますか？')) {
                        return;
                    }
                    
                    var $button = $(this);
                    var $spinner = $('#fix-spinner');
                    var $result = $('#prefecture-fix-result');
                    
                    $button.prop('disabled', true);
                    $spinner.addClass('is-active');
                    $result.hide();
                    
                    $.post(ajaxurl, {
                        action: 'gi_bulk_fix_prefecture_municipalities',
                        _wpnonce: '<?php echo wp_create_nonce("gi_bulk_fix_nonce"); ?>'
                    })
                    .done(function(response) {
                        if (response.success) {
                            $result.html(
                                '<div class="notice notice-success"><p>' +
                                '<strong>✅ 修正完了:</strong> ' + response.data.message + '<br>' +
                                (response.data.initialization.success ? 
                                    '<small>初期化: 作成 ' + response.data.initialization.total_created + ' 件, 更新 ' + response.data.initialization.total_updated + ' 件</small>' : 
                                    '<small>⚠️ 初期化でエラーが発生しました</small>') +
                                '</p></div>'
                            ).show();
                            
                            // 詳細があれば表示
                            if (response.data.details && response.data.details.length > 0) {
                                var details = '<div class="notice notice-info"><p><strong>修正詳細 (最初の10件):</strong></p><ul>';
                                response.data.details.forEach(function(detail) {
                                    details += '<li>' + detail + '</li>';
                                });
                                details += '</ul></div>';
                                $result.append(details);
                            }
                        } else {
                            $result.html(
                                '<div class="notice notice-error"><p>' +
                                '<strong>❌ エラー:</strong> ' + response.data.message +
                                '</p></div>'
                            ).show();
                        }
                    })
                    .fail(function() {
                        $result.html(
                            '<div class="notice notice-error"><p>' +
                            '<strong>❌ エラー:</strong> 通信エラーが発生しました' +
                            '</p></div>'
                        ).show();
                    })
                    .always(function() {
                        $button.prop('disabled', false);
                        $spinner.removeClass('is-active');
                    });
                });
            });
            </script>
        </div>
    </div>
    <?php
}

/**
 * 追加のMixed Content対策関数
 */

/**
 * 画像属性のHTTPS強制化
 */
function gi_force_https_image_attributes($attr) {
    if (is_ssl() && isset($attr['src'])) {
        $attr['src'] = str_replace('http://', 'https://', $attr['src']);
    }
    if (is_ssl() && isset($attr['srcset'])) {
        $attr['srcset'] = str_replace('http://', 'https://', $attr['srcset']);
    }
    return $attr;
}

/**
 * srcsetのHTTPS強制化
 */
function gi_force_https_srcset($sources) {
    if (is_ssl() && is_array($sources)) {
        foreach ($sources as &$source) {
            if (isset($source['url'])) {
                $source['url'] = str_replace('http://', 'https://', $source['url']);
            }
        }
    }
    return $sources;
}

/**
 * グローバルMixed Content修正
 */
function gi_global_https_fix() {
    if (is_ssl()) {
        ob_start();
        add_action('wp_footer', function() {
            $content = ob_get_clean();
            $content = str_replace('http://', 'https://', $content);
            echo $content;
        }, 999);
    }
}
add_action('init', 'gi_global_https_fix', 1);

/**
 * 締切日を過ぎた助成金のステータスを自動で「募集終了」に更新する
 */
function gi_auto_update_expired_grants_status() {
    $today = date('Y-m-d');

    $args = array(
        'post_type'      => 'grant',
        'posts_per_page' => -1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'application_status', // ステータスのフィールド名
                'value'   => 'open',             // 現在「募集中」のもの
                'compare' => '=',
            ),
            array(
                'key'     => 'deadline_date',      // 締切日のフィールド名
                'value'   => $today,
                'compare' => '<',              // 今日の日付より前のもの
                'type'    => 'DATE',
            ),
        ),
    );

    $expired_grants = new WP_Query($args);

    if ($expired_grants->have_posts()) {
        while ($expired_grants->have_posts()) {
            $expired_grants->the_post();
            // ステータスを 'closed' (募集終了) に更新
            update_field('application_status', 'closed', get_the_ID());
        }
    }
    wp_reset_postdata();
}

// 毎日1回、上記の関数を実行するようスケジュール
add_action('wp', function() {
    if (!wp_next_scheduled('gi_daily_grant_status_update')) {
        // 毎日深夜3時に実行するよう設定
        wp_schedule_event(strtotime('today 3:00'), 'daily', 'gi_daily_grant_status_update');
    }
});
add_action('gi_daily_grant_status_update', 'gi_auto_update_expired_grants_status');

