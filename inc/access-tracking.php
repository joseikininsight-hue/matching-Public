<?php
/**
 * Access Tracking System
 * アクセストラッキングシステム
 * 
 * Features:
 * - ページビュー数の記録
 * - 日別アクセス統計
 * - ランキング表示（全期間・3日間・7日間・30日間）
 * - IPアドレスベースの重複除外（1時間）
 * 
 * @package Grant_Insight
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class JI_Access_Tracking {
    
    private $table_name_views;
    private $table_name_daily;
    
    public function __construct() {
        global $wpdb;
        $this->table_name_views = $wpdb->prefix . 'ji_post_views';
        $this->table_name_daily = $wpdb->prefix . 'ji_post_views_daily';
        
        // フック登録
        add_action('init', array($this, 'init'));
        add_action('wp_head', array($this, 'track_view'), 10);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('manage_grant_posts_columns', array($this, 'add_views_column'));
        add_action('manage_grant_posts_custom_column', array($this, 'display_views_column'), 10, 2);
        add_action('manage_column_posts_columns', array($this, 'add_views_column'));
        add_action('manage_column_posts_custom_column', array($this, 'display_views_column'), 10, 2);
        
        // AJAXハンドラー
        add_action('wp_ajax_get_ranking_data', array($this, 'ajax_get_ranking_data'));
        add_action('wp_ajax_nopriv_get_ranking_data', array($this, 'ajax_get_ranking_data'));
    }
    
    /**
     * 初期化
     */
    public function init() {
        $this->create_tables();
        $this->cleanup_old_data();
    }
    
    /**
     * データベーステーブル作成
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // ビューテーブル（リアルタイム記録用）
        $sql_views = "CREATE TABLE IF NOT EXISTS {$this->table_name_views} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            post_type varchar(50) NOT NULL,
            ip_address varchar(100) NOT NULL,
            user_agent text DEFAULT NULL,
            viewed_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY post_type (post_type),
            KEY viewed_at (viewed_at),
            KEY ip_post (ip_address(50), post_id, viewed_at)
        ) $charset_collate;";
        
        // 日別統計テーブル
        $sql_daily = "CREATE TABLE IF NOT EXISTS {$this->table_name_daily} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            post_type varchar(50) NOT NULL,
            view_date date NOT NULL,
            view_count int(11) NOT NULL DEFAULT 0,
            unique_count int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY post_date (post_id, view_date),
            KEY post_id (post_id),
            KEY view_date (view_date),
            KEY post_type (post_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_views);
        dbDelta($sql_daily);
    }
    
    /**
     * アクセスを記録
     */
    public function track_view() {
        if (is_singular(array('grant', 'column'))) {
            global $post, $wpdb;
            
            if (!$post) {
                return;
            }
            
            $post_id = $post->ID;
            $post_type = $post->post_type;
            $ip_address = $this->get_client_ip();
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            
            // ボット除外
            if ($this->is_bot($user_agent)) {
                return;
            }
            
            // 管理者除外
            if (current_user_can('manage_options')) {
                return;
            }
            
            // 1時間以内の同じIPからのアクセスは除外
            $recent_view = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name_views} 
                WHERE post_id = %d 
                AND ip_address = %s 
                AND viewed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                $post_id,
                $ip_address
            ));
            
            if ($recent_view > 0) {
                return;
            }
            
            // ビュー記録
            $wpdb->insert(
                $this->table_name_views,
                array(
                    'post_id' => $post_id,
                    'post_type' => $post_type,
                    'ip_address' => $ip_address,
                    'user_agent' => $user_agent,
                    'viewed_at' => current_time('mysql'),
                ),
                array('%d', '%s', '%s', '%s', '%s')
            );
            
            // 日別統計を更新
            $this->update_daily_stats($post_id, $post_type);
        }
    }
    
    /**
     * 日別統計を更新
     */
    private function update_daily_stats($post_id, $post_type) {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        
        // 今日のユニーク訪問者数を計算
        $unique_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT ip_address) 
            FROM {$this->table_name_views} 
            WHERE post_id = %d 
            AND DATE(viewed_at) = %s",
            $post_id,
            $today
        ));
        
        // 今日の総ビュー数を計算
        $view_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) 
            FROM {$this->table_name_views} 
            WHERE post_id = %d 
            AND DATE(viewed_at) = %s",
            $post_id,
            $today
        ));
        
        // 日別統計を更新または挿入
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name_daily} 
            WHERE post_id = %d AND view_date = %s",
            $post_id,
            $today
        ));
        
        if ($existing) {
            $wpdb->update(
                $this->table_name_daily,
                array(
                    'view_count' => $view_count,
                    'unique_count' => $unique_count,
                ),
                array(
                    'post_id' => $post_id,
                    'view_date' => $today,
                ),
                array('%d', '%d'),
                array('%d', '%s')
            );
        } else {
            $wpdb->insert(
                $this->table_name_daily,
                array(
                    'post_id' => $post_id,
                    'post_type' => $post_type,
                    'view_date' => $today,
                    'view_count' => $view_count,
                    'unique_count' => $unique_count,
                ),
                array('%d', '%s', '%s', '%d', '%d')
            );
        }
    }
    
    /**
     * ランキングを取得
     */
    public function get_ranking($post_type = 'grant', $period_days = 0, $limit = 10) {
        global $wpdb;
        
        if ($period_days > 0) {
            // 期間指定ありの場合
            $date_condition = $wpdb->prepare(
                "AND view_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)",
                $period_days
            );
            
            $query = $wpdb->prepare(
                "SELECT 
                    d.post_id,
                    SUM(d.view_count) as total_views,
                    SUM(d.unique_count) as total_unique
                FROM {$this->table_name_daily} d
                INNER JOIN {$wpdb->posts} p ON d.post_id = p.ID
                WHERE d.post_type = %s 
                AND p.post_status = 'publish'
                {$date_condition}
                GROUP BY d.post_id
                ORDER BY total_views DESC
                LIMIT %d",
                $post_type,
                $limit
            );
        } else {
            // 全期間の場合
            $query = $wpdb->prepare(
                "SELECT 
                    d.post_id,
                    SUM(d.view_count) as total_views,
                    SUM(d.unique_count) as total_unique
                FROM {$this->table_name_daily} d
                INNER JOIN {$wpdb->posts} p ON d.post_id = p.ID
                WHERE d.post_type = %s 
                AND p.post_status = 'publish'
                GROUP BY d.post_id
                ORDER BY total_views DESC
                LIMIT %d",
                $post_type,
                $limit
            );
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * 投稿のビュー数を取得
     */
    public function get_post_views($post_id, $period_days = 0) {
        global $wpdb;
        
        if ($period_days > 0) {
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT 
                    SUM(view_count) as total_views,
                    SUM(unique_count) as total_unique
                FROM {$this->table_name_daily}
                WHERE post_id = %d 
                AND view_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)",
                $post_id,
                $period_days
            ));
        } else {
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT 
                    SUM(view_count) as total_views,
                    SUM(unique_count) as total_unique
                FROM {$this->table_name_daily}
                WHERE post_id = %d",
                $post_id
            ));
        }
        
        return $result ? intval($result->total_views) : 0;
    }
    
    /**
     * 古いデータをクリーンアップ
     */
    private function cleanup_old_data() {
        global $wpdb;
        
        // 90日以前のビューデータを削除
        $wpdb->query(
            "DELETE FROM {$this->table_name_views} 
            WHERE viewed_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );
        
        // 365日以前の日別統計を削除
        $wpdb->query(
            "DELETE FROM {$this->table_name_daily} 
            WHERE view_date < DATE_SUB(CURDATE(), INTERVAL 365 DAY)"
        );
    }
    
    /**
     * クライアントIPアドレスを取得
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // IPv6をIPv4に変換
        if ($ip === '::1') {
            $ip = '127.0.0.1';
        }
        
        return $ip;
    }
    
    /**
     * ボット判定
     */
    private function is_bot($user_agent) {
        $bots = array(
            'bot', 'crawl', 'spider', 'slurp', 'wordpress', 'preview',
            'google', 'bing', 'yahoo', 'baidu', 'yandex', 'facebook'
        );
        
        $user_agent_lower = strtolower($user_agent);
        
        foreach ($bots as $bot) {
            if (strpos($user_agent_lower, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 管理メニュー追加
     */
    public function add_admin_menu() {
        add_menu_page(
            'アクセス統計',
            'アクセス統計',
            'manage_options',
            'ji-access-stats',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            26
        );
    }
    
    /**
     * 管理ページ
     */
    public function admin_page() {
        echo '<div class="wrap">';
        echo '<h1>アクセス統計</h1>';
        echo '<p>詳細な統計情報は準備中です。投稿一覧でビュー数を確認できます。</p>';
        echo '</div>';
    }
    
    /**
     * 投稿一覧にビュー数カラムを追加
     */
    public function add_views_column($columns) {
        $columns['views'] = 'ビュー数';
        return $columns;
    }
    
    /**
     * ビュー数カラムの表示
     */
    public function display_views_column($column, $post_id) {
        if ($column === 'views') {
            $views = $this->get_post_views($post_id);
            echo number_format($views);
        }
    }
    
    /**
     * AJAX: ランキングデータ取得
     */
    public function ajax_get_ranking_data() {
        try {
            $period = isset($_POST['period']) ? intval($_POST['period']) : 3;
            $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'grant';
            
            // テーブルが存在するか確認
            global $wpdb;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name_daily}'");
            
            if (!$table_exists) {
                // テーブルがない場合は作成
                $this->create_tables();
                // 作成直後はデータがないので空状態を返す
                ob_start();
                ?>
                <div class="ranking-empty" style="text-align: center; padding: 30px 20px; color: #666;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 10px; opacity: 0.3;">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    <p style="margin: 0; font-size: 14px;">まだデータがありません</p>
                    <p style="margin: 5px 0 0; font-size: 12px; opacity: 0.7;">ページが閲覧されるとランキングが表示されます</p>
                </div>
                <?php
                $html = ob_get_clean();
                wp_send_json_success($html);
                return;
            }
            
            $ranking = $this->get_ranking($post_type, $period, 10);
            
            if (empty($ranking)) {
                // データはあるがランキングが空の場合
                ob_start();
                ?>
                <div class="ranking-empty" style="text-align: center; padding: 30px 20px; color: #666;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 10px; opacity: 0.3;">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                    <p style="margin: 0; font-size: 14px;">表示できる助成金がありません</p>
                    <p style="margin: 5px 0 0; font-size: 12px; opacity: 0.7;">この期間のアクセスデータがありません</p>
                </div>
                <?php
                $html = ob_get_clean();
                wp_send_json_success($html);
                return;
            }
        
        ob_start();
        ?>
        <ol class="ranking-list">
            <?php foreach ($ranking as $rank => $item): ?>
                <li class="ranking-item rank-<?php echo $rank + 1; ?>">
                    <a href="<?php echo esc_url(get_permalink($item->post_id)); ?>" class="ranking-link">
                        <span class="ranking-number"><?php echo $rank + 1; ?></span>
                        <span class="ranking-title">
                            <?php echo esc_html(get_the_title($item->post_id)); ?>
                        </span>
                        <span class="ranking-views">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <?php echo number_format($item->total_views); ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ol>
        <?php
        $html = ob_get_clean();
        
        wp_send_json_success($html);
        
        } catch (Exception $e) {
            // エラーログに記録
            error_log('JI Access Tracking AJAX Error: ' . $e->getMessage());
            
            // ユーザーフレンドリーなエラーメッセージを返す
            ob_start();
            ?>
            <div class="ranking-error" style="text-align: center; padding: 30px 20px; color: #999;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 10px; opacity: 0.3;">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <p style="margin: 0; font-size: 14px;">データを読み込めませんでした</p>
                <p style="margin: 5px 0 0; font-size: 12px; opacity: 0.7;">しばらくしてから再度お試しください</p>
            </div>
            <?php
            $html = ob_get_clean();
            wp_send_json_success($html);
        }
    }
}

// 初期化
new JI_Access_Tracking();

/**
 * ヘルパー関数: ランキングを取得
 */
function ji_get_ranking($post_type = 'grant', $period_days = 0, $limit = 10) {
    $tracker = new JI_Access_Tracking();
    return $tracker->get_ranking($post_type, $period_days, $limit);
}

/**
 * ヘルパー関数: ビュー数を取得
 */
function ji_get_views($post_id, $period_days = 0) {
    $tracker = new JI_Access_Tracking();
    return $tracker->get_post_views($post_id, $period_days);
}
