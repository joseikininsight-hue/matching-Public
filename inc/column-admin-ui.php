<?php
/**
 * Column Admin UI - Phase 3
 * 承認ワークフロー、ダッシュボード、管理画面カスタマイズ
 * 
 * @package Grant_Insight_Perfect
 * @subpackage Column_System
 * @version 3.0.0 (Phase 3 - Admin Features)
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// 1. 管理画面メニューの追加
// =============================================================================

/**
 * コラム管理メニューを追加
 */
function gi_column_add_admin_menu() {
    // メインメニュー（既存の「コラム」の下に追加）
    add_submenu_page(
        'edit.php?post_type=column',
        'コラム分析',
        '📊 分析ダッシュボード',
        'edit_posts',
        'column-analytics',
        'gi_column_analytics_page'
    );

    add_submenu_page(
        'edit.php?post_type=column',
        '承認待ち',
        '✅ 承認待ち',
        'publish_posts',
        'column-pending',
        'gi_column_pending_page'
    );

    add_submenu_page(
        'edit.php?post_type=column',
        'コラム設定',
        '⚙️ 設定',
        'manage_options',
        'column-settings',
        'gi_column_settings_page'
    );
}
add_action('admin_menu', 'gi_column_add_admin_menu');

// =============================================================================
// 2. 承認待ちページ
// =============================================================================

/**
 * 承認待ちページを表示
 */
function gi_column_pending_page() {
    // 権限チェック
    if (!current_user_can('publish_posts')) {
        wp_die('権限がありません');
    }

    // 承認処理
    if (isset($_POST['approve_column']) && check_admin_referer('approve_column_action', 'approve_column_nonce')) {
        $post_id = intval($_POST['post_id']);
        gi_column_approve_post($post_id);
        echo '<div class="notice notice-success"><p>コラムを承認しました。</p></div>';
    }

    // 差し戻し処理
    if (isset($_POST['reject_column']) && check_admin_referer('reject_column_action', 'reject_column_nonce')) {
        $post_id = intval($_POST['post_id']);
        gi_column_reject_post($post_id);
        echo '<div class="notice notice-warning"><p>コラムを差し戻しました。</p></div>';
    }

    // 承認待ちの記事を取得
    $pending_query = new WP_Query(array(
        'post_type' => 'column',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'column_status',
                'value' => 'pending',
                'compare' => '=',
            ),
        ),
        'orderby' => 'modified',
        'order' => 'DESC',
    ));

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">✅ 承認待ちコラム</h1>
        <hr class="wp-header-end">

        <?php if ($pending_query->have_posts()): ?>
            <p class="description">レビュー待ちのコラムが <strong><?php echo $pending_query->found_posts; ?>件</strong> あります。</p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="50%">タイトル</th>
                        <th width="15%">投稿者</th>
                        <th width="15%">最終更新</th>
                        <th width="10%">閲覧数</th>
                        <th width="10%">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($pending_query->have_posts()): $pending_query->the_post(); ?>
                        <?php
                        $post_id = get_the_ID();
                        $author = get_the_author();
                        $modified = get_the_modified_date('Y/m/d H:i');
                        $view_count = get_field('view_count', $post_id);
                        $categories = get_the_terms($post_id, 'column_category');
                        ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="<?php echo get_edit_post_link($post_id); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </strong>
                                <?php if ($categories && !is_wp_error($categories)): ?>
                                    <br>
                                    <span class="category-badge">
                                        <?php echo esc_html($categories[0]->name); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($author); ?></td>
                            <td><?php echo $modified; ?></td>
                            <td><?php echo number_format($view_count); ?> views</td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('approve_column_action', 'approve_column_nonce'); ?>
                                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                    <button type="submit" name="approve_column" class="button button-primary" 
                                            onclick="return confirm('この記事を承認しますか？')">
                                        承認
                                    </button>
                                </form>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('reject_column_action', 'reject_column_nonce'); ?>
                                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                                    <button type="submit" name="reject_column" class="button" 
                                            onclick="return confirm('この記事を差し戻しますか？')">
                                        差し戻し
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="notice notice-info">
                <p>✨ 承認待ちのコラムはありません。</p>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .category-badge {
            display: inline-block;
            padding: 2px 8px;
            background: #059669;
            color: white;
            font-size: 11px;
            border-radius: 3px;
            margin-top: 4px;
        }
    </style>
    <?php
}

/**
 * 記事を承認する
 * 
 * @param int $post_id 投稿ID
 */
function gi_column_approve_post($post_id) {
    update_field('column_status', 'approved', $post_id);
    
    // 承認通知メール送信
    gi_column_send_approval_email($post_id);
    
    // ログ記録
    error_log("[Column Admin] Post #{$post_id} approved by " . wp_get_current_user()->user_login);
}

/**
 * 記事を差し戻す
 * 
 * @param int $post_id 投稿ID
 */
function gi_column_reject_post($post_id) {
    update_field('column_status', 'draft', $post_id);
    
    // 差し戻し通知メール送信
    gi_column_send_rejection_email($post_id);
    
    // ログ記録
    error_log("[Column Admin] Post #{$post_id} rejected by " . wp_get_current_user()->user_login);
}

// =============================================================================
// 3. 分析ダッシュボード
// =============================================================================

/**
 * 分析ダッシュボードページを表示
 */
function gi_column_analytics_page() {
    // 権限チェック
    if (!current_user_can('edit_posts')) {
        wp_die('権限がありません');
    }

    // 統計データを取得
    $total_columns = wp_count_posts('column');
    $total_views = gi_column_get_total_views();
    $popular_columns = gi_get_column_ranking(10, 'all');
    $recent_columns = gi_get_columns(array('posts_per_page' => 5));
    $category_stats = gi_column_get_category_stats();

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">📊 コラム分析ダッシュボード</h1>
        <hr class="wp-header-end">

        <!-- 統計カード -->
        <div class="column-stats-grid">
            <div class="stats-card">
                <div class="stats-icon">📝</div>
                <div class="stats-content">
                    <div class="stats-number"><?php echo number_format($total_columns->publish); ?></div>
                    <div class="stats-label">公開中の記事</div>
                </div>
            </div>

            <div class="stats-card">
                <div class="stats-icon">👁️</div>
                <div class="stats-content">
                    <div class="stats-number"><?php echo number_format($total_views); ?></div>
                    <div class="stats-label">総閲覧数</div>
                </div>
            </div>

            <div class="stats-card">
                <div class="stats-icon">⏳</div>
                <div class="stats-content">
                    <div class="stats-number"><?php echo number_format($total_columns->pending ?? 0); ?></div>
                    <div class="stats-label">下書き・レビュー待ち</div>
                </div>
            </div>

            <div class="stats-card">
                <div class="stats-icon">📈</div>
                <div class="stats-content">
                    <div class="stats-number">
                        <?php echo $total_views > 0 ? number_format($total_views / max($total_columns->publish, 1), 1) : '0'; ?>
                    </div>
                    <div class="stats-label">平均閲覧数/記事</div>
                </div>
            </div>
        </div>

        <div class="column-analytics-content">
            <!-- 人気記事ランキング -->
            <div class="analytics-section">
                <h2>🔥 人気記事 TOP10</h2>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th width="5%">順位</th>
                            <th width="50%">タイトル</th>
                            <th width="15%">カテゴリ</th>
                            <th width="15%">公開日</th>
                            <th width="15%">閲覧数</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($popular_columns)): ?>
                            <?php foreach ($popular_columns as $index => $column): ?>
                                <tr>
                                    <td class="rank-<?php echo $index + 1; ?>">
                                        <strong><?php echo $index + 1; ?></strong>
                                    </td>
                                    <td>
                                        <a href="<?php echo get_edit_post_link($column['id']); ?>">
                                            <?php echo esc_html($column['title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php 
                                        $cats = get_the_terms($column['id'], 'column_category');
                                        echo $cats && !is_wp_error($cats) ? esc_html($cats[0]->name) : '-';
                                        ?>
                                    </td>
                                    <td><?php echo esc_html($column['date']); ?></td>
                                    <td><strong><?php echo number_format($column['view_count']); ?></strong> views</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5">データがありません</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- カテゴリ別統計 -->
            <div class="analytics-section">
                <h2>📁 カテゴリ別統計</h2>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th width="40%">カテゴリ</th>
                            <th width="20%">記事数</th>
                            <th width="20%">総閲覧数</th>
                            <th width="20%">平均閲覧数</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($category_stats)): ?>
                            <?php foreach ($category_stats as $stat): ?>
                                <tr>
                                    <td>
                                        <?php echo gi_get_category_icon($stat['slug']); ?>
                                        <strong><?php echo esc_html($stat['name']); ?></strong>
                                    </td>
                                    <td><?php echo number_format($stat['count']); ?> 件</td>
                                    <td><?php echo number_format($stat['total_views']); ?> views</td>
                                    <td><?php echo number_format($stat['avg_views'], 1); ?> views</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4">データがありません</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- 最近の記事 -->
            <div class="analytics-section">
                <h2>📅 最近公開された記事</h2>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th width="50%">タイトル</th>
                            <th width="20%">公開日</th>
                            <th width="15%">投稿者</th>
                            <th width="15%">閲覧数</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_columns->have_posts()): ?>
                            <?php while ($recent_columns->have_posts()): $recent_columns->the_post(); ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo get_edit_post_link(get_the_ID()); ?>">
                                            <?php the_title(); ?>
                                        </a>
                                    </td>
                                    <td><?php echo get_the_date('Y/m/d'); ?></td>
                                    <td><?php the_author(); ?></td>
                                    <td><?php echo number_format(get_field('view_count', get_the_ID())); ?> views</td>
                                </tr>
                            <?php endwhile; ?>
                            <?php wp_reset_postdata(); ?>
                        <?php else: ?>
                            <tr><td colspan="4">データがありません</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .column-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .stats-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .stats-icon {
            font-size: 48px;
            margin-right: 20px;
        }

        .stats-number {
            font-size: 32px;
            font-weight: bold;
            color: #059669;
        }

        .stats-label {
            font-size: 14px;
            color: #666;
            margin-top: 4px;
        }

        .column-analytics-content {
            margin-top: 30px;
        }

        .analytics-section {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .analytics-section h2 {
            margin: 0 0 15px 0;
            font-size: 18px;
            border-bottom: 2px solid #059669;
            padding-bottom: 10px;
        }

        .rank-1 strong {
            color: #FFD700;
            font-size: 18px;
        }

        .rank-2 strong {
            color: #C0C0C0;
            font-size: 16px;
        }

        .rank-3 strong {
            color: #CD7F32;
            font-size: 16px;
        }
    </style>
    <?php
}

/**
 * 総閲覧数を取得
 * 
 * @return int 総閲覧数
 */
function gi_column_get_total_views() {
    global $wpdb;
    
    $total = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(CAST(meta_value AS UNSIGNED))
        FROM {$wpdb->postmeta}
        WHERE meta_key = %s
        AND post_id IN (
            SELECT ID FROM {$wpdb->posts}
            WHERE post_type = %s
            AND post_status = %s
        )
    ", 'view_count', 'column', 'publish'));
    
    return intval($total);
}

/**
 * カテゴリ別統計を取得
 * 
 * @return array カテゴリ別統計
 */
function gi_column_get_category_stats() {
    $categories = gi_get_column_categories(true);
    $stats = array();
    
    foreach ($categories as $category) {
        $query = gi_get_columns_by_category($category->slug, -1);
        
        $total_views = 0;
        $count = 0;
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $total_views += (int) get_field('view_count', get_the_ID());
                $count++;
            }
            wp_reset_postdata();
        }
        
        $stats[] = array(
            'name' => $category->name,
            'slug' => $category->slug,
            'count' => $count,
            'total_views' => $total_views,
            'avg_views' => $count > 0 ? $total_views / $count : 0,
        );
    }
    
    // 総閲覧数でソート
    usort($stats, function($a, $b) {
        return $b['total_views'] - $a['total_views'];
    });
    
    return $stats;
}

// =============================================================================
// 4. 設定ページ
// =============================================================================

/**
 * 設定ページを表示
 */
function gi_column_settings_page() {
    // 権限チェック
    if (!current_user_can('manage_options')) {
        wp_die('権限がありません');
    }

    // 設定保存
    if (isset($_POST['save_column_settings']) && check_admin_referer('column_settings_action', 'column_settings_nonce')) {
        update_option('column_enable_notifications', isset($_POST['enable_notifications']));
        update_option('column_notification_email', sanitize_email($_POST['notification_email']));
        update_option('column_auto_approve', isset($_POST['auto_approve']));
        update_option('column_posts_per_page', intval($_POST['posts_per_page']));
        
        echo '<div class="notice notice-success"><p>設定を保存しました。</p></div>';
    }

    $enable_notifications = get_option('column_enable_notifications', false);
    $notification_email = get_option('column_notification_email', get_option('admin_email'));
    $auto_approve = get_option('column_auto_approve', false);
    $posts_per_page = get_option('column_posts_per_page', 6);

    ?>
    <div class="wrap">
        <h1>⚙️ コラム設定</h1>
        
        <form method="post">
            <?php wp_nonce_field('column_settings_action', 'column_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">メール通知</th>
                    <td>
                        <label>
                            <input type="checkbox" name="enable_notifications" value="1" <?php checked($enable_notifications); ?>>
                            新規投稿時・承認時にメール通知を送信
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">通知先メールアドレス</th>
                    <td>
                        <input type="email" name="notification_email" value="<?php echo esc_attr($notification_email); ?>" class="regular-text">
                        <p class="description">管理者への通知メールアドレス</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">自動承認</th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_approve" value="1" <?php checked($auto_approve); ?>>
                            新規投稿を自動的に承認する（推奨しません）
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">1ページあたりの表示件数</th>
                    <td>
                        <input type="number" name="posts_per_page" value="<?php echo esc_attr($posts_per_page); ?>" min="1" max="50" class="small-text">
                        <p class="description">コラム一覧ページの表示件数（デフォルト: 6）</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="save_column_settings" class="button button-primary">
                    変更を保存
                </button>
            </p>
        </form>
    </div>
    <?php
}

// =============================================================================
// 5. メール通知機能
// =============================================================================

/**
 * 承認通知メールを送信
 * 
 * @param int $post_id 投稿ID
 * @return bool 送信成功時はtrue
 */
function gi_column_send_approval_email($post_id) {
    // 通知機能が無効の場合はスキップ
    if (!get_option('column_enable_notifications', false)) {
        return false;
    }
    
    $post = get_post($post_id);
    if (!$post) {
        return false;
    }
    
    // 投稿者情報を取得
    $author = get_user_by('id', $post->post_author);
    if (!$author) {
        return false;
    }
    
    // メール送信先
    $to = $author->user_email;
    
    // メール件名
    $subject = '[' . get_bloginfo('name') . '] コラムが承認されました';
    
    // メール本文
    $message = "こんにちは、{$author->display_name}様\n\n";
    $message .= "あなたのコラム記事が承認され、公開されました。\n\n";
    $message .= "【記事タイトル】\n{$post->post_title}\n\n";
    $message .= "【記事URL】\n" . get_permalink($post_id) . "\n\n";
    $message .= "【承認日時】\n" . current_time('Y年m月d日 H:i') . "\n\n";
    $message .= "引き続き、質の高いコンテンツの作成をお願いいたします。\n\n";
    $message .= "---\n";
    $message .= get_bloginfo('name') . "\n";
    $message .= get_bloginfo('url') . "\n";
    
    // メールヘッダー
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
    );
    
    // メール送信
    $sent = wp_mail($to, $subject, $message, $headers);
    
    if ($sent) {
        error_log("[Column Admin] Approval email sent to {$to} for post #{$post_id}");
    } else {
        error_log("[Column Admin] Failed to send approval email to {$to} for post #{$post_id}");
    }
    
    return $sent;
}

/**
 * 差し戻し通知メールを送信
 * 
 * @param int $post_id 投稿ID
 * @return bool 送信成功時はtrue
 */
function gi_column_send_rejection_email($post_id) {
    // 通知機能が無効の場合はスキップ
    if (!get_option('column_enable_notifications', false)) {
        return false;
    }
    
    $post = get_post($post_id);
    if (!$post) {
        return false;
    }
    
    // 投稿者情報を取得
    $author = get_user_by('id', $post->post_author);
    if (!$author) {
        return false;
    }
    
    // メール送信先
    $to = $author->user_email;
    
    // メール件名
    $subject = '[' . get_bloginfo('name') . '] コラムが差し戻されました';
    
    // メール本文
    $message = "こんにちは、{$author->display_name}様\n\n";
    $message .= "あなたのコラム記事がレビューの結果、差し戻されました。\n\n";
    $message .= "【記事タイトル】\n{$post->post_title}\n\n";
    $message .= "【編集URL】\n" . get_edit_post_link($post_id, '') . "\n\n";
    $message .= "【差し戻し日時】\n" . current_time('Y年m月d日 H:i') . "\n\n";
    $message .= "【改善が必要な点】\n";
    $message .= "※ 管理者からのコメントがある場合は、記事編集画面をご確認ください。\n\n";
    $message .= "内容を修正の上、再度レビュー依頼をお願いいたします。\n\n";
    $message .= "---\n";
    $message .= get_bloginfo('name') . "\n";
    $message .= get_bloginfo('url') . "\n";
    
    // メールヘッダー
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
    );
    
    // メール送信
    $sent = wp_mail($to, $subject, $message, $headers);
    
    if ($sent) {
        error_log("[Column Admin] Rejection email sent to {$to} for post #{$post_id}");
    } else {
        error_log("[Column Admin] Failed to send rejection email to {$to} for post #{$post_id}");
    }
    
    return $sent;
}

/**
 * 管理者への新規投稿通知メールを送信
 * 
 * @param int $post_id 投稿ID
 * @return bool 送信成功時はtrue
 */
function gi_column_send_new_post_notification($post_id) {
    // 通知機能が無効の場合はスキップ
    if (!get_option('column_enable_notifications', false)) {
        return false;
    }
    
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'column') {
        return false;
    }
    
    // 投稿者情報を取得
    $author = get_user_by('id', $post->post_author);
    
    // メール送信先（管理者）
    $to = get_option('column_notification_email', get_option('admin_email'));
    
    // メール件名
    $subject = '[' . get_bloginfo('name') . '] 新しいコラムがレビュー待ちです';
    
    // メール本文
    $message = "新しいコラム記事が投稿されました。\n\n";
    $message .= "【投稿者】\n{$author->display_name} ({$author->user_email})\n\n";
    $message .= "【記事タイトル】\n{$post->post_title}\n\n";
    $message .= "【編集URL】\n" . get_edit_post_link($post_id, '') . "\n\n";
    $message .= "【承認URL】\n" . admin_url('edit.php?post_type=column&page=column-pending') . "\n\n";
    $message .= "【投稿日時】\n" . get_the_date('Y年m月d日 H:i', $post_id) . "\n\n";
    $message .= "レビューと承認処理をお願いいたします。\n\n";
    $message .= "---\n";
    $message .= get_bloginfo('name') . " 管理システム\n";
    
    // メールヘッダー
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
    );
    
    // メール送信
    $sent = wp_mail($to, $subject, $message, $headers);
    
    if ($sent) {
        error_log("[Column Admin] New post notification sent to {$to} for post #{$post_id}");
    } else {
        error_log("[Column Admin] Failed to send new post notification to {$to} for post #{$post_id}");
    }
    
    return $sent;
}

/**
 * 新規コラム投稿時に管理者へ通知
 * publish状態でcolumn_status=pendingの場合に通知
 */
function gi_column_notify_on_new_post($post_id, $post, $update) {
    // 新規投稿のみ（更新は除外）
    if ($update) {
        return;
    }
    
    // コラム投稿タイプのみ
    if ($post->post_type !== 'column') {
        return;
    }
    
    // 公開状態かつ承認待ちの場合のみ通知
    if ($post->post_status === 'publish' && get_field('column_status', $post_id) === 'pending') {
        gi_column_send_new_post_notification($post_id);
    }
}
add_action('wp_insert_post', 'gi_column_notify_on_new_post', 10, 3);

// =============================================================================
// Column Admin UI Phase 3 完了
// =============================================================================
