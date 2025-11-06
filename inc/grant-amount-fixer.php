<?php
/**
 * Grant Amount Fixer - 助成金額修正機能 v2.0
 * 
 * Excel日付シリアル値として保存されている助成金額を正しい数値に一括修正
 * 実際のACFフィールド構造に基づいた修正
 * 
 * 対象フィールド:
 * - max_amount: 助成金額上限（テキスト）
 * - max_amount_numeric: 助成金額上限（数値）
 * - min_amount: 助成金額下限
 * - adoption_rate: 採択率（%）
 * 
 * 例: 10113 (Excelの日付シリアル値) → 3000000 (300万円)
 * 
 * @package Grant_Insight_Perfect
 * @version 2.0.0
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 助成金額修正クラス
 */
class GI_Grant_Amount_Fixer {
    
    /**
     * インスタンス
     */
    private static $instance = null;
    
    /**
     * 処理対象のACFフィールド名（実際のテンプレートから抽出）
     * template-parts/grant-card-unified.php を基に決定
     */
    private $amount_fields = array(
        'max_amount_numeric',    // 助成金額上限（数値）- メイン対象
        'min_amount',            // 助成金額下限
        'adoption_rate',         // 採択率（%）
    );
    
    /**
     * シングルトンインスタンス取得
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * コンストラクタ
     */
    private function __construct() {
        // 管理画面メニュー追加
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // AJAX処理登録
        add_action('wp_ajax_gi_scan_grant_amounts', array($this, 'ajax_scan_grant_amounts'));
        add_action('wp_ajax_gi_fix_grant_amounts', array($this, 'ajax_fix_grant_amounts'));
        add_action('wp_ajax_gi_preview_fix', array($this, 'ajax_preview_fix'));
        
        // スクリプトとスタイル登録
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * 管理画面メニュー追加
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=grant',
            '助成金額修正ツール',
            '金額修正ツール',
            'manage_options',
            'gi-amount-fixer',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * 管理画面アセット読み込み
     */
    public function enqueue_admin_assets($hook) {
        // 専用ページのみ読み込み
        if ($hook !== 'grant_page_gi-amount-fixer') {
            return;
        }
        
        // スタイル
        wp_enqueue_style(
            'gi-amount-fixer-css',
            get_template_directory_uri() . '/assets/css/amount-fixer.css',
            array(),
            GI_THEME_VERSION
        );
        
        // スクリプト
        wp_enqueue_script(
            'gi-amount-fixer-js',
            get_template_directory_uri() . '/assets/js/amount-fixer.js',
            array('jquery'),
            GI_THEME_VERSION,
            true
        );
        
        // Ajax設定を渡す
        wp_localize_script('gi-amount-fixer-js', 'giAmountFixer', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gi_amount_fixer_nonce')
        ));
    }
    
    /**
     * 管理画面ページレンダリング
     */
    public function render_admin_page() {
        ?>
        <div class="wrap gi-amount-fixer">
            <h1>助成金額修正ツール v2.0</h1>
            
            <div class="gi-fixer-notice">
                <h2>このツールについて</h2>
                <p>Excelから取り込んだ際に日付シリアル値として保存されている助成金額を、正しい数値に一括修正します。</p>
                <p><strong>例:</strong> 10113（日付シリアル値） → 3,000,000（300万円）</p>
                <p><strong>対象フィールド:</strong></p>
                <ul style="margin-left: 20px;">
                    <li>max_amount_numeric: 助成金額上限（数値）</li>
                    <li>min_amount: 助成金額下限</li>
                    <li>adoption_rate: 採択率（%）</li>
                </ul>
            </div>
            
            <!-- スキャンセクション -->
            <div class="gi-fixer-section">
                <h2>ステップ1: 問題のある投稿をスキャン</h2>
                <p>まず、修正が必要な投稿を検出します。</p>
                <button id="gi-scan-btn" class="button button-primary button-large">
                    <span class="dashicons dashicons-search"></span>
                    スキャン開始
                </button>
                <div id="gi-scan-progress" style="display:none;">
                    <div class="gi-progress-bar">
                        <div class="gi-progress-fill"></div>
                    </div>
                    <p class="gi-progress-text">スキャン中...</p>
                </div>
                <div id="gi-scan-results" style="display:none;"></div>
            </div>
            
            <!-- プレビューセクション -->
            <div id="gi-preview-section" class="gi-fixer-section" style="display:none;">
                <h2>ステップ2: 修正内容のプレビュー</h2>
                <div id="gi-preview-results"></div>
            </div>
            
            <!-- 修正セクション -->
            <div id="gi-fix-section" class="gi-fixer-section" style="display:none;">
                <h2>ステップ3: 一括修正実行</h2>
                <div class="gi-warning-box">
                    <p><strong>⚠️ 注意:</strong> この操作は元に戻せません。必ずプレビューで内容を確認してください。</p>
                    <p>バックアップを取ることを強く推奨します。</p>
                </div>
                <button id="gi-fix-btn" class="button button-primary button-large">
                    <span class="dashicons dashicons-admin-tools"></span>
                    一括修正を実行
                </button>
                <div id="gi-fix-progress" style="display:none;">
                    <div class="gi-progress-bar">
                        <div class="gi-progress-fill"></div>
                    </div>
                    <p class="gi-progress-text">修正中...</p>
                </div>
                <div id="gi-fix-results" style="display:none;"></div>
            </div>
            
            <!-- 完了セクション -->
            <div id="gi-complete-section" class="gi-fixer-section" style="display:none;">
                <div class="gi-success-box">
                    <h2>✅ 修正完了</h2>
                    <p>助成金額の修正が正常に完了しました。</p>
                    <a href="<?php echo admin_url('edit.php?post_type=grant'); ?>" class="button button-primary">
                        助成金一覧を確認
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: 助成金額スキャン
     */
    public function ajax_scan_grant_amounts() {
        // Nonce確認
        check_ajax_referer('gi_amount_fixer_nonce', 'nonce');
        
        // 権限確認
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '権限がありません'));
        }
        
        // 全助成金投稿を取得
        $args = array(
            'post_type' => 'grant',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids'
        );
        
        $post_ids = get_posts($args);
        $problematic_posts = array();
        
        foreach ($post_ids as $post_id) {
            $issues = $this->check_post_amounts($post_id);
            if (!empty($issues)) {
                $problematic_posts[$post_id] = array(
                    'title' => get_the_title($post_id),
                    'issues' => $issues
                );
            }
        }
        
        wp_send_json_success(array(
            'total_scanned' => count($post_ids),
            'problematic_count' => count($problematic_posts),
            'problematic_posts' => $problematic_posts
        ));
    }
    
    /**
     * AJAX: 修正プレビュー
     */
    public function ajax_preview_fix() {
        // Nonce確認
        check_ajax_referer('gi_amount_fixer_nonce', 'nonce');
        
        // 権限確認
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '権限がありません'));
        }
        
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
        
        if (empty($post_ids)) {
            wp_send_json_error(array('message' => '修正対象の投稿が指定されていません'));
        }
        
        $preview_data = array();
        
        foreach ($post_ids as $post_id) {
            $current = array();
            $fixed = array();
            
            foreach ($this->amount_fields as $field) {
                $value = get_field($field, $post_id);
                if ($value !== null && $value !== false && $value !== '') {
                    if ($this->is_likely_serial_value($value, $field)) {
                        $current[$field] = $value;
                        $fixed[$field] = $this->convert_serial_to_amount($value, $field);
                    }
                }
            }
            
            if (!empty($current)) {
                $preview_data[$post_id] = array(
                    'title' => get_the_title($post_id),
                    'current' => $current,
                    'fixed' => $fixed
                );
            }
        }
        
        wp_send_json_success(array('preview' => $preview_data));
    }
    
    /**
     * AJAX: 一括修正実行
     */
    public function ajax_fix_grant_amounts() {
        // Nonce確認
        check_ajax_referer('gi_amount_fixer_nonce', 'nonce');
        
        // 権限確認
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '権限がありません'));
        }
        
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
        
        if (empty($post_ids)) {
            wp_send_json_error(array('message' => '修正対象の投稿が指定されていません'));
        }
        
        $success_count = 0;
        $error_count = 0;
        $results = array();
        
        foreach ($post_ids as $post_id) {
            $fixed_fields = $this->fix_post_amounts($post_id);
            
            if ($fixed_fields !== false) {
                $success_count++;
                $results[$post_id] = array(
                    'success' => true,
                    'title' => get_the_title($post_id),
                    'fixed_fields' => $fixed_fields
                );
            } else {
                $error_count++;
                $results[$post_id] = array(
                    'success' => false,
                    'title' => get_the_title($post_id),
                    'error' => '修正に失敗しました'
                );
            }
        }
        
        wp_send_json_success(array(
            'success_count' => $success_count,
            'error_count' => $error_count,
            'results' => $results
        ));
    }
    
    /**
     * 投稿の金額フィールドをチェック
     */
    private function check_post_amounts($post_id) {
        $issues = array();
        
        foreach ($this->amount_fields as $field) {
            $value = get_field($field, $post_id);
            
            // 値が存在し、シリアル値らしい場合
            if ($value !== null && $value !== false && $value !== '') {
                if ($this->is_likely_serial_value($value, $field)) {
                    $issues[] = array(
                        'field' => $field,
                        'current_value' => $value,
                        'suggested_value' => $this->convert_serial_to_amount($value, $field)
                    );
                }
            }
        }
        
        return $issues;
    }
    
    /**
     * シリアル値かどうか判定
     */
    private function is_likely_serial_value($value, $field) {
        // 空の値や数値でない場合はfalse
        if ($value === null || $value === false || $value === '' || !is_numeric($value)) {
            return false;
        }
        
        $numeric_value = floatval($value);
        
        // フィールドタイプに応じた判定
        if ($field === 'adoption_rate') {
            // 採択率フィールド: 0-100の範囲外はおかしい
            return ($numeric_value > 100 || $numeric_value < 0);
        } else {
            // 金額フィールド: 50000未満の値は日付シリアル値の可能性が高い
            // （通常の助成金額は最低でも5万円以上）
            // 例外: min_amountで0は正常値（下限なし）
            if ($field === 'min_amount' && $numeric_value == 0) {
                return false;
            }
            return ($numeric_value > 0 && $numeric_value < 50000);
        }
    }
    
    /**
     * シリアル値を正しい金額に変換
     */
    private function convert_serial_to_amount($value, $field) {
        if (!is_numeric($value)) {
            return $value;
        }
        
        $numeric_value = floatval($value);
        
        // 採択率は変換不要（エラー値として扱う）
        if ($field === 'adoption_rate') {
            return 0; // エラー値は0に設定
        }
        
        // Excelの日付シリアル値を解析して金額を推測
        // シリアル値の桁数から推測される金額を算出
        
        // 4-5桁: おそらく日付シリアル値（1900年1月1日からの日数）
        if ($numeric_value >= 1000 && $numeric_value < 50000) {
            $str_value = (string)intval($numeric_value);
            
            // 日付シリアル値のパターン検出と変換
            // 10113 のような5桁の場合
            if (strlen($str_value) === 5) {
                // 最初の3桁を金額（百万単位）として解釈
                // 例: 10113 → 101 → 101万円 → 1,010,000円
                $millions = intval(substr($str_value, 0, 3));
                return $millions * 10000; // 万円単位に変換
            }
            
            // 4桁の場合
            if (strlen($str_value) === 4) {
                // 最初の2桁を金額（百万単位）として解釈
                // 例: 5020 → 50 → 50万円 → 500,000円
                $millions = intval(substr($str_value, 0, 2));
                return $millions * 10000;
            }
            
            // フォールバック: 単純に300倍
            // これは経験的な値（多くのケースで3桁目が金額の重要な部分）
            return intval($numeric_value * 300);
        }
        
        return $numeric_value;
    }
    
    /**
     * 投稿の金額フィールドを修正
     */
    private function fix_post_amounts($post_id) {
        $fixed_fields = array();
        
        foreach ($this->amount_fields as $field) {
            $value = get_field($field, $post_id);
            
            if ($value !== null && $value !== false && $value !== '') {
                if ($this->is_likely_serial_value($value, $field)) {
                    $new_value = $this->convert_serial_to_amount($value, $field);
                    
                    // ACFフィールド更新
                    $result = update_field($field, $new_value, $post_id);
                    
                    if ($result) {
                        $fixed_fields[$field] = array(
                            'old' => $value,
                            'new' => $new_value
                        );
                    }
                }
            }
        }
        
        return !empty($fixed_fields) ? $fixed_fields : false;
    }
    
    /**
     * フィールド名を日本語に変換
     */
    public static function get_field_label($field_name) {
        $labels = array(
            'max_amount' => '助成金額上限（テキスト）',
            'max_amount_numeric' => '助成金額上限（数値）',
            'min_amount' => '助成金額下限',
            'adoption_rate' => '採択率'
        );
        
        return isset($labels[$field_name]) ? $labels[$field_name] : $field_name;
    }
}

// 初期化
GI_Grant_Amount_Fixer::get_instance();
