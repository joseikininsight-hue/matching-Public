<?php
/**
 * Column System - Complete Integration File
 * コラム機能の全機能を統合管理
 * 
 * 含まれる機能:
 * - カスタム投稿タイプ「column」登録
 * - タクソノミー（カテゴリ・タグ）登録
 * - ACFフィールド定義
 * - 補助金連携関数
 * - Ajax処理ハンドラー
 * - 承認システム
 * - Analytics機能（PV計測・ランキング）
 * 
 * @package Grant_Insight_Perfect
 * @subpackage Column_System
 * @version 1.0.0
 * @since 2025-11-02
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// 1. カスタム投稿タイプ「column」登録
// =============================================================================

/**
 * カスタム投稿タイプ「column」を登録
 * 
 * @return void
 */
function gi_register_column_post_type() {
    $labels = array(
        'name'                  => 'コラム',
        'singular_name'         => 'コラム',
        'menu_name'             => 'コラム',
        'name_admin_bar'        => 'コラム',
        'add_new'               => '新規追加',
        'add_new_item'          => '新規コラム追加',
        'new_item'              => '新規コラム',
        'edit_item'             => 'コラムを編集',
        'view_item'             => 'コラムを表示',
        'all_items'             => 'すべてのコラム',
        'search_items'          => 'コラムを検索',
        'parent_item_colon'     => '親コラム:',
        'not_found'             => 'コラムが見つかりませんでした。',
        'not_found_in_trash'    => 'ゴミ箱にコラムはありません。',
        'featured_image'        => 'アイキャッチ画像',
        'set_featured_image'    => 'アイキャッチ画像を設定',
        'remove_featured_image' => 'アイキャッチ画像を削除',
        'use_featured_image'    => 'アイキャッチ画像として使用',
        'archives'              => 'コラムアーカイブ',
        'insert_into_item'      => 'コラムに挿入',
        'uploaded_to_this_item' => 'このコラムにアップロード',
        'filter_items_list'     => 'コラムリストを絞り込み',
        'items_list_navigation' => 'コラムリストナビゲーション',
        'items_list'            => 'コラムリスト',
    );

    $args = array(
        'labels'              => $labels,
        'description'         => '補助金・助成金に関するコラム記事',
        'public'              => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array('slug' => 'column'),
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-edit-large',
        'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions', 'custom-fields'),
        'show_in_rest'        => true, // Gutenbergエディタ対応
        'rest_base'           => 'columns',
        'rest_controller_class' => 'WP_REST_Posts_Controller',
    );

    register_post_type('column', $args);
}
add_action('init', 'gi_register_column_post_type');

// =============================================================================
// 2. タクソノミー登録
// =============================================================================

/**
 * タクソノミー「column_category」を登録（階層あり）
 * 
 * @return void
 */
function gi_register_column_category_taxonomy() {
    $labels = array(
        'name'              => 'カテゴリ',
        'singular_name'     => 'カテゴリ',
        'search_items'      => 'カテゴリを検索',
        'all_items'         => 'すべてのカテゴリ',
        'parent_item'       => '親カテゴリ',
        'parent_item_colon' => '親カテゴリ:',
        'edit_item'         => 'カテゴリを編集',
        'update_item'       => 'カテゴリを更新',
        'add_new_item'      => '新規カテゴリを追加',
        'new_item_name'     => '新規カテゴリ名',
        'menu_name'         => 'カテゴリ',
    );

    $args = array(
        'hierarchical'      => true, // 階層あり（WordPressのカテゴリと同じ）
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'column-category'),
        'show_in_rest'      => true, // Gutenbergエディタ対応
        'rest_base'         => 'column-categories',
    );

    register_taxonomy('column_category', array('column'), $args);
}
add_action('init', 'gi_register_column_category_taxonomy');

/**
 * タクソノミー「column_tag」を登録（階層なし）
 * 
 * @return void
 */
function gi_register_column_tag_taxonomy() {
    $labels = array(
        'name'                       => 'タグ',
        'singular_name'              => 'タグ',
        'search_items'               => 'タグを検索',
        'popular_items'              => '人気のタグ',
        'all_items'                  => 'すべてのタグ',
        'edit_item'                  => 'タグを編集',
        'update_item'                => 'タグを更新',
        'add_new_item'               => '新規タグを追加',
        'new_item_name'              => '新規タグ名',
        'separate_items_with_commas' => 'タグをカンマで区切る',
        'add_or_remove_items'        => 'タグを追加または削除',
        'choose_from_most_used'      => 'よく使われているタグから選択',
        'not_found'                  => 'タグが見つかりませんでした。',
        'menu_name'                  => 'タグ',
    );

    $args = array(
        'hierarchical'          => false, // 階層なし（WordPressのタグと同じ）
        'labels'                => $labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var'             => true,
        'rewrite'               => array('slug' => 'column-tag'),
        'show_in_rest'          => true, // Gutenbergエディタ対応
        'rest_base'             => 'column-tags',
    );

    register_taxonomy('column_tag', array('column'), $args);
}
add_action('init', 'gi_register_column_tag_taxonomy');

/**
 * デフォルトのカテゴリとタグを自動生成
 * テーマ有効化時に一度だけ実行
 * 
 * @return void
 */
function gi_create_default_column_terms() {
    // 初回実行チェック
    if (get_option('gi_column_default_terms_created')) {
        return;
    }

    // デフォルトカテゴリを作成
    $default_categories = array(
        array(
            'name'        => '申請のコツ',
            'slug'        => 'application-tips',
            'description' => '補助金申請のノウハウやコツを紹介',
        ),
        array(
            'name'        => '制度解説',
            'slug'        => 'system-explanation',
            'description' => '補助金制度の詳細解説',
        ),
        array(
            'name'        => '動向・ニュース',
            'slug'        => 'news',
            'description' => '補助金に関する最新動向やニュース',
        ),
        array(
            'name'        => '成功事例',
            'slug'        => 'success-stories',
            'description' => '補助金活用の成功事例',
        ),
        array(
            'name'        => 'その他',
            'slug'        => 'other',
            'description' => 'その他のコラム記事',
        ),
    );

    foreach ($default_categories as $category) {
        if (!term_exists($category['slug'], 'column_category')) {
            wp_insert_term(
                $category['name'],
                'column_category',
                array(
                    'slug'        => $category['slug'],
                    'description' => $category['description'],
                )
            );
        }
    }

    // デフォルトタグを作成
    $default_tags = array(
        '事業再構築補助金',
        'IT導入補助金',
        'ものづくり補助金',
        'DX推進',
        '設備投資',
        '人材育成',
        '環境・省エネ',
        '創業・スタートアップ',
        '販路拡大',
        '海外展開',
    );

    foreach ($default_tags as $tag) {
        $slug = sanitize_title($tag);
        if (!term_exists($slug, 'column_tag')) {
            wp_insert_term($tag, 'column_tag');
        }
    }

    // 実行済みフラグを設定
    update_option('gi_column_default_terms_created', true);
}
add_action('init', 'gi_create_default_column_terms', 20);

// =============================================================================
// 3. リライトルール設定
// =============================================================================

/**
 * コラムのリライトルールをフラッシュ
 * テーマ有効化時に一度だけ実行
 * 
 * @return void
 */
function gi_column_rewrite_flush() {
    // カスタム投稿タイプとタクソノミーを登録
    gi_register_column_post_type();
    gi_register_column_category_taxonomy();
    gi_register_column_tag_taxonomy();

    // リライトルールをフラッシュ
    if (get_option('gi_column_rewrite_flushed') !== 'yes') {
        flush_rewrite_rules(false);
        update_option('gi_column_rewrite_flushed', 'yes');
    }
}
add_action('after_switch_theme', 'gi_column_rewrite_flush');

// =============================================================================
// 4. 管理画面カスタマイズ
// =============================================================================

/**
 * コラム一覧画面にカスタムカラムを追加
 * 
 * @param array $columns 既存のカラム配列
 * @return array 修正されたカラム配列
 */
function gi_column_custom_columns($columns) {
    // チェックボックスとタイトルの後にサムネイルを挿入
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['thumbnail'] = 'アイキャッチ';
        }
    }

    // カテゴリとタグの後にカスタムフィールドを追加
    $new_columns['column_status'] = 'ステータス';
    $new_columns['view_count'] = '閲覧数';
    $new_columns['read_time'] = '読了時間';
    $new_columns['related_grants'] = '関連補助金';
    
    return $new_columns;
}
add_filter('manage_column_posts_columns', 'gi_column_custom_columns');

/**
 * カスタムカラムの内容を表示
 * 
 * @param string $column カラム名
 * @param int $post_id 投稿ID
 * @return void
 */
function gi_column_custom_column_content($column, $post_id) {
    switch ($column) {
        case 'thumbnail':
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, array(60, 60));
            } else {
                echo '<span style="color: #999;">なし</span>';
            }
            break;

        case 'column_status':
            $status = get_field('column_status', $post_id);
            $status_labels = array(
                'draft'    => '<span style="color: #999;">下書き</span>',
                'pending'  => '<span style="color: #f59e0b;">レビュー待ち</span>',
                'approved' => '<span style="color: #059669;">承認済み</span>',
                'featured' => '<span style="color: #ef4444; font-weight: bold;">⭐ 特集記事</span>',
            );
            echo isset($status_labels[$status]) ? $status_labels[$status] : '<span style="color: #999;">-</span>';
            break;

        case 'view_count':
            $count = get_field('view_count', $post_id);
            echo $count ? number_format($count) . ' views' : '0 views';
            break;

        case 'read_time':
            $time = get_field('estimated_read_time', $post_id);
            echo $time ? $time . '分' : '-';
            break;

        case 'related_grants':
            $grants = get_field('related_grants', $post_id);
            if ($grants && is_array($grants)) {
                echo count($grants) . '件';
            } else {
                echo '-';
            }
            break;
    }
}
add_action('manage_column_posts_custom_column', 'gi_column_custom_column_content', 10, 2);

/**
 * カスタムカラムをソート可能にする
 * 
 * @param array $columns ソート可能なカラム配列
 * @return array 修正されたカラム配列
 */
function gi_column_sortable_columns($columns) {
    $columns['view_count'] = 'view_count';
    $columns['read_time'] = 'read_time';
    return $columns;
}
add_filter('manage_edit-column_sortable_columns', 'gi_column_sortable_columns');

/**
 * カスタムカラムのソートクエリを調整
 * 
 * @param WP_Query $query クエリオブジェクト
 * @return void
 */
function gi_column_custom_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $orderby = $query->get('orderby');

    switch ($orderby) {
        case 'view_count':
            $query->set('meta_key', 'view_count');
            $query->set('orderby', 'meta_value_num');
            break;

        case 'read_time':
            $query->set('meta_key', 'estimated_read_time');
            $query->set('orderby', 'meta_value_num');
            break;
    }
}
add_action('pre_get_posts', 'gi_column_custom_orderby');

/**
 * 管理画面のコラム一覧にフィルタを追加
 * 
 * @return void
 */
function gi_column_admin_filters() {
    global $typenow;

    if ($typenow !== 'column') {
        return;
    }

    // ステータスフィルタ
    $status = isset($_GET['column_status_filter']) ? $_GET['column_status_filter'] : '';
    ?>
    <select name="column_status_filter">
        <option value="">すべてのステータス</option>
        <option value="draft" <?php selected($status, 'draft'); ?>>下書き</option>
        <option value="pending" <?php selected($status, 'pending'); ?>>レビュー待ち</option>
        <option value="approved" <?php selected($status, 'approved'); ?>>承認済み</option>
        <option value="featured" <?php selected($status, 'featured'); ?>>特集記事</option>
    </select>
    <?php
}
add_action('restrict_manage_posts', 'gi_column_admin_filters');

/**
 * 管理画面フィルタのクエリを調整
 * 
 * @param WP_Query $query クエリオブジェクト
 * @return void
 */
function gi_column_admin_filter_query($query) {
    global $pagenow, $typenow;

    if ($pagenow !== 'edit.php' || $typenow !== 'column' || !is_admin()) {
        return;
    }

    if (isset($_GET['column_status_filter']) && $_GET['column_status_filter'] !== '') {
        $meta_query = array(
            array(
                'key'     => 'column_status',
                'value'   => sanitize_text_field($_GET['column_status_filter']),
                'compare' => '=',
            ),
        );
        $query->set('meta_query', $meta_query);
    }
}
add_filter('parse_query', 'gi_column_admin_filter_query');

// =============================================================================
// 5. ヘルパー関数
// =============================================================================

/**
 * コラムが表示可能かチェック
 * 公開済み + 承認済み（approved/featured）のみ表示
 * 
 * @param int $post_id 投稿ID
 * @return bool 表示可能ならtrue
 */
function gi_column_can_display($post_id) {
    $post_status = get_post_status($post_id);
    $column_status = get_field('column_status', $post_id);

    // 公開済み + 承認済みのみ表示
    return $post_status === 'publish' && 
           in_array($column_status, array('approved', 'featured'));
}

/**
 * コラム一覧を取得
 * 
 * @param array $args WP_Query引数
 * @return WP_Query クエリオブジェクト
 */
function gi_get_columns($args = array()) {
    $defaults = array(
        'post_type'      => 'column',
        'post_status'    => 'publish',
        'posts_per_page' => 10,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_query'     => array(
            array(
                'key'     => 'column_status',
                'value'   => array('approved', 'featured'),
                'compare' => 'IN',
            ),
        ),
    );

    $args = wp_parse_args($args, $defaults);
    return new WP_Query($args);
}

/**
 * 特集コラム一覧を取得
 * 
 * @param int $limit 取得件数
 * @return WP_Query クエリオブジェクト
 */
function gi_get_featured_columns($limit = 3) {
    return gi_get_columns(array(
        'posts_per_page' => $limit,
        'meta_query'     => array(
            array(
                'key'     => 'column_status',
                'value'   => 'featured',
                'compare' => '=',
            ),
        ),
    ));
}

/**
 * カテゴリ別コラム一覧を取得
 * 
 * @param string|int $category カテゴリスラッグまたはID
 * @param int $limit 取得件数
 * @return WP_Query クエリオブジェクト
 */
function gi_get_columns_by_category($category, $limit = 10) {
    $tax_query = array();

    if (is_numeric($category)) {
        $tax_query[] = array(
            'taxonomy' => 'column_category',
            'field'    => 'term_id',
            'terms'    => $category,
        );
    } else {
        $tax_query[] = array(
            'taxonomy' => 'column_category',
            'field'    => 'slug',
            'terms'    => $category,
        );
    }

    return gi_get_columns(array(
        'posts_per_page' => $limit,
        'tax_query'      => $tax_query,
    ));
}

/**
 * 人気コラム一覧を取得（閲覧数順）
 * 
 * @param int $limit 取得件数
 * @return WP_Query クエリオブジェクト
 */
function gi_get_popular_columns($limit = 10) {
    return gi_get_columns(array(
        'posts_per_page' => $limit,
        'meta_key'       => 'view_count',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
    ));
}

/**
 * 関連コラム一覧を取得（Phase 2 改善版）
 * 1. 同じカテゴリ + 同じタグ
 * 2. 同じカテゴリ
 * 3. 人気記事
 * の優先順で取得
 * 
 * @param int $post_id 投稿ID
 * @param int $limit 取得件数
 * @return WP_Query クエリオブジェクト
 */
function gi_get_related_columns($post_id, $limit = 3) {
    $categories = wp_get_post_terms($post_id, 'column_category', array('fields' => 'ids'));
    $tags = wp_get_post_terms($post_id, 'column_tag', array('fields' => 'ids'));

    // 優先度1: 同じカテゴリ + 同じタグ
    if (!empty($categories) && !empty($tags)) {
        $query = gi_get_columns(array(
            'posts_per_page' => $limit,
            'post__not_in'   => array($post_id),
            'tax_query'      => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'column_category',
                    'field'    => 'term_id',
                    'terms'    => $categories,
                ),
                array(
                    'taxonomy' => 'column_tag',
                    'field'    => 'term_id',
                    'terms'    => $tags,
                ),
            ),
        ));

        if ($query->found_posts >= $limit) {
            return $query;
        }
    }

    // 優先度2: 同じカテゴリ
    if (!empty($categories)) {
        $query = gi_get_columns(array(
            'posts_per_page' => $limit,
            'post__not_in'   => array($post_id),
            'tax_query'      => array(
                array(
                    'taxonomy' => 'column_category',
                    'field'    => 'term_id',
                    'terms'    => $categories,
                ),
            ),
        ));

        if ($query->found_posts >= $limit) {
            return $query;
        }
    }

    // 優先度3: 人気記事
    return gi_get_popular_columns($limit);
}

// =============================================================================
// 6. ACFフィールド定義
// =============================================================================

/**
 * ACFフィールドグループを登録
 * コラム投稿に必要なカスタムフィールドを定義
 * 
 * @return void
 */
function gi_register_column_acf_fields() {
    // ACFがインストールされていない場合はスキップ
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_column_fields',
        'title' => 'コラム設定',
        'fields' => array(
            // =============================
            // ステータス管理
            // =============================
            array(
                'key' => 'field_column_status',
                'label' => '記事ステータス',
                'name' => 'column_status',
                'type' => 'select',
                'instructions' => '記事の公開ステータスを選択してください。',
                'required' => 1,
                'choices' => array(
                    'draft' => '下書き',
                    'pending' => 'レビュー待ち',
                    'approved' => '承認済み',
                    'featured' => '特集記事（トップページに大きく表示）',
                ),
                'default_value' => 'draft',
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 1,
                'return_format' => 'value',
            ),

            // =============================
            // 読了時間
            // =============================
            array(
                'key' => 'field_estimated_read_time',
                'label' => '読了時間（分）',
                'name' => 'estimated_read_time',
                'type' => 'number',
                'instructions' => '記事を読むのにかかる時間（分）を入力してください。自動計算: 記事文字数 ÷ 400文字/分',
                'required' => 0,
                'default_value' => 5,
                'placeholder' => '5',
                'min' => 1,
                'max' => 60,
                'step' => 1,
            ),

            // =============================
            // 難易度
            // =============================
            array(
                'key' => 'field_difficulty_level',
                'label' => '難易度',
                'name' => 'difficulty_level',
                'type' => 'select',
                'instructions' => '記事の難易度を選択してください。',
                'required' => 0,
                'choices' => array(
                    'beginner' => '初心者向け（補助金初心者でもわかる内容）',
                    'intermediate' => '中級者向け（ある程度の知識が必要）',
                    'advanced' => '上級者向け（専門的な内容）',
                ),
                'default_value' => 'beginner',
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 1,
                'return_format' => 'value',
            ),

            // =============================
            // 関連補助金（Relationship）
            // =============================
            array(
                'key' => 'field_related_grants',
                'label' => '関連補助金',
                'name' => 'related_grants',
                'type' => 'relationship',
                'instructions' => 'この記事に関連する補助金を選択してください。最大5件まで。',
                'required' => 0,
                'post_type' => array('grant'),
                'taxonomy' => array(),
                'filters' => array(
                    'search',
                    'taxonomy',
                ),
                'elements' => array(
                    'featured_image',
                ),
                'min' => 0,
                'max' => 5,
                'return_format' => 'id',
            ),

            // =============================
            // 関連度スコア（内部用・非表示）
            // =============================
            array(
                'key' => 'field_relation_scores',
                'label' => '関連度スコア（自動生成）',
                'name' => 'relation_scores',
                'type' => 'textarea',
                'instructions' => 'システムが自動計算した関連度スコア。編集不要。',
                'required' => 0,
                'default_value' => '[]',
                'placeholder' => 'JSON形式のスコアデータ',
                'maxlength' => '',
                'rows' => 3,
                'readonly' => 1,
                'wrapper' => array(
                    'class' => 'acf-hidden',
                ),
            ),

            // =============================
            // 閲覧回数（自動更新）
            // =============================
            array(
                'key' => 'field_view_count',
                'label' => '閲覧回数',
                'name' => 'view_count',
                'type' => 'number',
                'instructions' => 'この記事の閲覧回数。自動的に更新されます。',
                'required' => 0,
                'default_value' => 0,
                'placeholder' => '0',
                'min' => 0,
                'readonly' => 1,
            ),

            // =============================
            // 最終更新日
            // =============================
            array(
                'key' => 'field_last_updated',
                'label' => '最終更新日',
                'name' => 'last_updated',
                'type' => 'date_picker',
                'instructions' => '記事の最終更新日。空欄の場合、投稿日が使用されます。',
                'required' => 0,
                'display_format' => 'Y年m月d日',
                'return_format' => 'Y-m-d',
                'first_day' => 0,
            ),

            // =============================
            // SEO タイトル
            // =============================
            array(
                'key' => 'field_seo_title',
                'label' => 'SEO タイトル',
                'name' => 'seo_title',
                'type' => 'text',
                'instructions' => '検索エンジン用のタイトル。空欄の場合、記事タイトルが使用されます。（最大60文字推奨）',
                'required' => 0,
                'maxlength' => 60,
                'placeholder' => '空欄の場合、記事タイトルが使用されます',
            ),

            // =============================
            // SEO 説明文
            // =============================
            array(
                'key' => 'field_seo_description',
                'label' => 'SEO 説明文（メタディスクリプション）',
                'name' => 'seo_description',
                'type' => 'textarea',
                'instructions' => '検索結果に表示される説明文。（最大160文字推奨）',
                'required' => 0,
                'maxlength' => 160,
                'rows' => 3,
                'placeholder' => 'この記事では○○について解説します...',
            ),

            // =============================
            // 対象読者
            // =============================
            array(
                'key' => 'field_target_audience',
                'label' => '対象読者',
                'name' => 'target_audience',
                'type' => 'checkbox',
                'instructions' => 'この記事のターゲット読者を選択してください。（複数選択可）',
                'required' => 0,
                'choices' => array(
                    'startup' => '創業・スタートアップ',
                    'sme' => '中小企業',
                    'individual' => '個人事業主',
                    'npo' => 'NPO・一般社団法人',
                    'agriculture' => '農業者',
                    'other' => 'その他',
                ),
                'allow_custom' => 0,
                'default_value' => array(),
                'layout' => 'vertical',
                'toggle' => 0,
                'return_format' => 'value',
            ),

            // =============================
            // キーポイント（要点まとめ）
            // =============================
            array(
                'key' => 'field_key_points',
                'label' => 'キーポイント（要点まとめ）',
                'name' => 'key_points',
                'type' => 'wysiwyg',
                'instructions' => '記事の要点を箇条書きでまとめてください。記事の冒頭に表示されます。',
                'required' => 0,
                'default_value' => '',
                'tabs' => 'visual',
                'toolbar' => 'basic',
                'media_upload' => 0,
                'delay' => 0,
            ),

            // =============================
            // アイキャッチ画像キャプション
            // =============================
            array(
                'key' => 'field_featured_image_caption',
                'label' => 'アイキャッチ画像キャプション',
                'name' => 'featured_image_caption',
                'type' => 'text',
                'instructions' => 'アイキャッチ画像の説明文やクレジット表記を入力してください。',
                'required' => 0,
                'maxlength' => 200,
                'placeholder' => '例: Photo by John Doe',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'column',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => array(),
        'active' => true,
        'description' => 'コラム記事のカスタムフィールド設定',
    ));
}
add_action('acf/init', 'gi_register_column_acf_fields');

/**
 * 記事保存時に読了時間を自動計算
 * 
 * @param int $post_id 投稿ID
 * @return void
 */
function gi_column_auto_calculate_read_time($post_id) {
    // 自動保存、リビジョン、バルク編集をスキップ
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    // コラム投稿タイプ以外はスキップ
    if (get_post_type($post_id) !== 'column') {
        return;
    }

    // 読了時間が手動設定されている場合はスキップ
    $manual_time = get_field('estimated_read_time', $post_id);
    if ($manual_time && $manual_time > 0) {
        return;
    }

    // 記事本文から文字数をカウント
    $content = get_post_field('post_content', $post_id);
    $content = wp_strip_all_tags($content);
    $char_count = mb_strlen($content, 'UTF-8');

    // 読了時間を計算（400文字/分）
    $read_time = max(1, ceil($char_count / 400));

    // ACFフィールドを更新
    update_field('estimated_read_time', $read_time, $post_id);
}
add_action('save_post_column', 'gi_column_auto_calculate_read_time', 10);

/**
 * 記事保存時に最終更新日を自動設定
 * 
 * @param int $post_id 投稿ID
 * @return void
 */
function gi_column_auto_update_last_updated($post_id) {
    // 自動保存、リビジョンをスキップ
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    // コラム投稿タイプ以外はスキップ
    if (get_post_type($post_id) !== 'column') {
        return;
    }

    // 最終更新日が手動設定されていない場合のみ自動設定
    $manual_date = get_field('last_updated', $post_id);
    if (!$manual_date) {
        update_field('last_updated', date('Y-m-d'), $post_id);
    }
}
add_action('save_post_column', 'gi_column_auto_update_last_updated', 10);

// =============================================================================
// 7. Analytics機能（PV計測・ランキング）
// =============================================================================

/**
 * 記事閲覧時にPVカウントを増加
 * Cookieで重複カウント防止（1日1回）
 * 
 * @param int $post_id 投稿ID
 * @return void
 */
function gi_column_count_view($post_id) {
    // コラム投稿タイプ以外はスキップ
    if (get_post_type($post_id) !== 'column') {
        return;
    }

    // Cookieで重複カウント防止（24時間）
    $cookie_name = 'column_viewed_' . $post_id;
    if (isset($_COOKIE[$cookie_name])) {
        return;
    }

    // 現在のカウント数を取得
    $current_count = (int) get_field('view_count', $post_id);

    // カウント増加
    update_field('view_count', $current_count + 1, $post_id);

    // Cookie設定（24時間）
    setcookie($cookie_name, '1', time() + 86400, '/');

    // ログ記録（デバッグ用）
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[Column Analytics] PV count increased for post ID: {$post_id}, New count: " . ($current_count + 1));
    }
}

/**
 * single-column.phpで自動的にPVカウント
 * テンプレート読み込み時に実行
 * 
 * @param string $template テンプレートパス
 * @return string テンプレートパス
 */
function gi_column_auto_count_view($template) {
    if (is_singular('column')) {
        gi_column_count_view(get_the_ID());
    }
    return $template;
}
add_filter('template_include', 'gi_column_auto_count_view');

/**
 * 人気記事ランキングを取得（キャッシュ機能付き）
 * 1時間キャッシュで負荷軽減
 * 
 * @param int $limit 取得件数
 * @param string $period 期間（all, month, week）
 * @return array 人気記事の配列
 */
function gi_get_column_ranking($limit = 10, $period = 'all') {
    $cache_key = "column_ranking_{$period}_{$limit}";
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    // 基本クエリ
    $args = array(
        'post_type'      => 'column',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'meta_key'       => 'view_count',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'meta_query'     => array(
            array(
                'key'     => 'column_status',
                'value'   => array('approved', 'featured'),
                'compare' => 'IN',
            ),
        ),
    );

    // 期間指定
    if ($period === 'month') {
        $args['date_query'] = array(
            array(
                'after' => '1 month ago',
            ),
        );
    } elseif ($period === 'week') {
        $args['date_query'] = array(
            array(
                'after' => '1 week ago',
            ),
        );
    }

    $query = new WP_Query($args);
    $results = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $results[] = array(
                'id'         => get_the_ID(),
                'title'      => get_the_title(),
                'permalink'  => get_permalink(),
                'view_count' => (int) get_field('view_count', get_the_ID()),
                'thumbnail'  => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                'date'       => get_the_date('Y.m.d'),
            );
        }
        wp_reset_postdata();
    }

    // 1時間キャッシュ
    set_transient($cache_key, $results, HOUR_IN_SECONDS);

    return $results;
}

/**
 * ランキングキャッシュをクリア
 * 記事が更新されたときに実行
 * 
 * @param int $post_id 投稿ID
 * @return void
 */
function gi_clear_column_ranking_cache($post_id) {
    if (get_post_type($post_id) !== 'column') {
        return;
    }

    // すべての期間のキャッシュをクリア
    $periods = array('all', 'month', 'week');
    $limits = array(5, 10, 20);

    foreach ($periods as $period) {
        foreach ($limits as $limit) {
            delete_transient("column_ranking_{$period}_{$limit}");
        }
    }
}
add_action('save_post_column', 'gi_clear_column_ranking_cache');
add_action('delete_post', 'gi_clear_column_ranking_cache');

// =============================================================================
// 8. 補助金自動連携機能
// =============================================================================
// Note: gi_extract_keywords() is defined in ajax-functions.php
// =============================================================================

/**
 * 2つのテキスト間の類似度を計算
 * 
 * @param array $keywords1 キーワード配列1
 * @param string $text2 テキスト2
 * @return float 類似度スコア（0.0〜1.0）
 */
function gi_calculate_similarity($keywords1, $text2) {
    if (empty($keywords1)) {
        return 0.0;
    }

    $text2_lower = mb_strtolower($text2, 'UTF-8');
    $match_count = 0;

    foreach ($keywords1 as $keyword) {
        $keyword_lower = mb_strtolower($keyword, 'UTF-8');
        if (mb_strpos($text2_lower, $keyword_lower) !== false) {
            $match_count++;
        }
    }

    return $match_count / count($keywords1);
}

/**
 * 記事保存時に補助金との関連付けを自動実行
 * 
 * @param int $post_id 投稿ID
 * @return void
 */
function gi_column_auto_link_grants($post_id) {
    // 自動保存、リビジョンをスキップ
    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
        return;
    }

    // コラム投稿タイプ以外はスキップ
    if (get_post_type($post_id) !== 'column') {
        return;
    }

    // 既に関連補助金が手動設定されている場合はスキップ
    $existing_grants = get_field('related_grants', $post_id);
    if (!empty($existing_grants) && is_array($existing_grants) && count($existing_grants) > 0) {
        return;
    }

    // 1. 記事本文からキーワード抽出
    $content = get_post_field('post_content', $post_id);
    $title = get_post_field('post_title', $post_id);
    $keywords = gi_extract_keywords($title . ' ' . $content);

    if (empty($keywords)) {
        return;
    }

    // 2. 補助金データベースを取得
    $grants = get_posts(array(
        'post_type'      => 'grant',
        'post_status'    => 'publish',
        'posts_per_page' => 100, // 上位100件の補助金を対象
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));

    if (empty($grants)) {
        return;
    }

    // 3. 各補助金との関連度を計算
    $scores = array();
    foreach ($grants as $grant) {
        $grant_text = $grant->post_title . ' ' . $grant->post_content;
        $score = gi_calculate_similarity($keywords, $grant_text);

        if ($score >= 0.3) { // 関連度30%以上を対象
            $scores[$grant->ID] = $score;
        }
    }

    // 4. スコアでソート（降順）
    arsort($scores);

    // 5. 上位5件を関連補助金として保存
    $related_grant_ids = array_slice(array_keys($scores), 0, 5);

    if (!empty($related_grant_ids)) {
        update_field('related_grants', $related_grant_ids, $post_id);
        update_field('relation_scores', json_encode($scores), $post_id);

        // ログ記録（デバッグ用）
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Column Grant Link] Auto-linked " . count($related_grant_ids) . " grants to post ID: {$post_id}");
        }
    }
}
add_action('save_post_column', 'gi_column_auto_link_grants', 20);

/**
 * 補助金から関連コラムを取得
 * 
 * @param int $grant_id 補助金ID
 * @param int $limit 取得件数
 * @return WP_Query クエリオブジェクト
 */
function gi_get_columns_by_grant($grant_id, $limit = 5) {
    return new WP_Query(array(
        'post_type'      => 'column',
        'post_status'    => 'publish',
        'posts_per_page' => $limit,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'related_grants',
                'value'   => '"' . $grant_id . '"',
                'compare' => 'LIKE',
            ),
            array(
                'key'     => 'column_status',
                'value'   => array('approved', 'featured'),
                'compare' => 'IN',
            ),
        ),
    ));
}

// =============================================================================
// 9. Ajax処理ハンドラー（Phase 2で実装予定）
// =============================================================================

/**
 * Ajaxでコラム一覧を取得
 * タブ切り替え、無限スクロールで使用
 * 
 * @return void
 */
function gi_ajax_get_columns() {
    // nonce検証
    check_ajax_referer('gi_column_ajax', 'nonce');

    // パラメータ取得
    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 6;

    // クエリ引数
    $args = array(
        'post_type'      => 'column',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'meta_query'     => array(
            array(
                'key'     => 'column_status',
                'value'   => array('approved', 'featured'),
                'compare' => 'IN',
            ),
        ),
    );

    // カテゴリフィルタ
    if (!empty($category) && $category !== 'all') {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'column_category',
                'field'    => 'slug',
                'terms'    => $category,
            ),
        );
    }

    $query = new WP_Query($args);
    $results = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            ob_start();
            get_template_part('template-parts/column/card');
            $results[] = ob_get_clean();
        }
        wp_reset_postdata();
    }

    wp_send_json_success(array(
        'html'       => implode('', $results),
        'has_more'   => $query->max_num_pages > $paged,
        'max_pages'  => $query->max_num_pages,
        'found_posts' => $query->found_posts,
    ));
}
add_action('wp_ajax_gi_get_columns', 'gi_ajax_get_columns');
add_action('wp_ajax_nopriv_gi_get_columns', 'gi_ajax_get_columns');

// =============================================================================
// 10. ユーティリティ関数
// =============================================================================

/**
 * コラムのカテゴリ一覧を取得
 * 
 * @param bool $hide_empty 空のカテゴリを除外するか
 * @return array カテゴリ配列
 */
function gi_get_column_categories($hide_empty = true) {
    return get_terms(array(
        'taxonomy'   => 'column_category',
        'hide_empty' => $hide_empty,
        'orderby'    => 'count',
        'order'      => 'DESC',
    ));
}

/**
 * コラムのタグクラウドを取得
 * 
 * @param int $limit 取得件数
 * @return array タグ配列
 */
function gi_get_column_tag_cloud($limit = 20) {
    return get_terms(array(
        'taxonomy'   => 'column_tag',
        'hide_empty' => true,
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => $limit,
    ));
}

/**
 * カテゴリ別の記事数を取得
 * 
 * @return array カテゴリ別記事数の連想配列
 */
function gi_get_column_category_counts() {
    $categories = gi_get_column_categories(false);
    $counts = array();

    foreach ($categories as $category) {
        $counts[$category->slug] = $category->count;
    }

    return $counts;
}

// =============================================================================
// Note: gi_get_difficulty_label() is defined in ajax-functions.php
// =============================================================================

/**
 * カテゴリ別アイコンを取得
 * 
 * @param string $slug カテゴリスラッグ
 * @return string アイコン（絵文字）
 */
function gi_get_category_icon($slug) {
    $icons = array(
        'application-tips'    => '💡',
        'system-explanation'  => '📚',
        'news'                => '📰',
        'success-stories'     => '🏆',
        'other'               => '📝',
    );

    return isset($icons[$slug]) ? $icons[$slug] : '📄';
}

// =============================================================================
// 11. Phase 2 機能 - 検索Ajax
// =============================================================================

/**
 * Ajaxでコラムを検索
 * 
 * @return void
 */
function gi_ajax_search_columns() {
    // nonce検証
    check_ajax_referer('gi_column_ajax', 'nonce');

    // パラメータ取得
    $query_string = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 12;

    // 検索クエリが空の場合
    if (empty($query_string)) {
        wp_send_json_error(array(
            'message' => '検索キーワードを入力してください',
        ));
        return;
    }

    // クエリ引数
    $args = array(
        'post_type'      => 'column',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        's'              => $query_string,
        'meta_query'     => array(
            array(
                'key'     => 'column_status',
                'value'   => array('approved', 'featured'),
                'compare' => 'IN',
            ),
        ),
    );

    $query = new WP_Query($args);
    $results = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            ob_start();
            get_template_part('template-parts/column/card');
            $results[] = ob_get_clean();
        }
        wp_reset_postdata();
    }

    wp_send_json_success(array(
        'html'        => implode('', $results),
        'has_more'    => $query->max_num_pages > $paged,
        'max_pages'   => $query->max_num_pages,
        'found_posts' => $query->found_posts,
        'query'       => $query_string,
    ));
}
add_action('wp_ajax_gi_search_columns', 'gi_ajax_search_columns');
add_action('wp_ajax_nopriv_gi_search_columns', 'gi_ajax_search_columns');

// =============================================================================
// Column System 統合ファイル終了 (Phase 2完了)
// =============================================================================
