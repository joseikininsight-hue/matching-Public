<?php
/**
 * AI補助金マッチング - 動的表示機能
 * 
 * このファイルを functions.php にコピーするか、
 * functions.php に require_once(get_template_directory() . '/subsidy-diagnosis-functions.php'); を追加
 */

// ============================================
// 方法1: ショートコード
// ============================================

/**
 * AI補助金マッチングアプリのショートコード
 * 
 * 使い方:
 * [subsidy_diagnosis]
 * [subsidy_diagnosis height="600px" width="100%"]
 */
function subsidy_diagnosis_shortcode($atts) {
    $atts = shortcode_atts(array(
        'height' => '800px',
        'width' => '100%',
        'max_width' => '1200px',
        'show_features' => 'yes',
        'show_header' => 'yes',
    ), $atts);
    
    ob_start();
    ?>
    <div class="subsidy-diagnosis-embed" style="max-width: <?php echo esc_attr($atts['max_width']); ?>; margin: 0 auto; padding: 20px 0;">
        
        <?php if ($atts['show_header'] === 'yes') : ?>
        <!-- ヘッダー -->
        <div style="text-align: center; margin-bottom: 30px;">
            <h2 style="font-size: 2rem; font-weight: 700; color: #333; margin-bottom: 10px;">
                💡 AI補助金マッチング
            </h2>
            <p style="font-size: 1.1rem; color: #666;">
                あなたの事業に最適な補助金を、AIが最短3分で診断します
            </p>
        </div>
        <?php endif; ?>
        
        <?php if ($atts['show_features'] === 'yes') : ?>
        <!-- 特徴カード -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 10px;">🤖</div>
                <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 5px;">AI診断</h3>
                <p style="font-size: 0.9rem; color: #666; margin: 0;">8,000件以上のデータから最適なものを選定</p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 10px;">⚡</div>
                <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 5px;">最短3分</h3>
                <p style="font-size: 0.9rem; color: #666; margin: 0;">簡単な質問に答えるだけ</p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 10px;">🎯</div>
                <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 5px;">高精度マッチング</h3>
                <p style="font-size: 0.9rem; color: #666; margin: 0;">事業内容に合わせて提案</p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 10px;">🆓</div>
                <h3 style="font-size: 1.1rem; font-weight: 600; margin-bottom: 5px;">完全無料</h3>
                <p style="font-size: 0.9rem; color: #666; margin: 0;">登録不要で何度でも</p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- アプリ埋め込み -->
        <div style="position: relative; width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>; overflow: hidden; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); background: white;">
            <div class="subsidy-loading" style="position: absolute; width: 100%; height: 100%; background: rgba(255,255,255,0.95); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10;">
                <div style="width: 50px; height: 50px; border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; animation: subsidy-spin 1s linear infinite;"></div>
                <p style="margin-top: 20px; color: #667eea; font-weight: 600;">読み込み中...</p>
            </div>
            <iframe 
                src="https://matching-public.pages.dev/" 
                style="width: 100%; height: 100%; border: none;"
                title="AI補助金マッチング診断"
                loading="eager"
                onload="if(this.previousElementSibling) this.previousElementSibling.style.display='none';">
            </iframe>
        </div>
    </div>
    
    <style>
    @keyframes subsidy-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    @media (max-width: 768px) {
        .subsidy-diagnosis-embed > div[style*="grid"] {
            grid-template-columns: 1fr !important;
        }
    }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('subsidy_diagnosis', 'subsidy_diagnosis_shortcode');


// ============================================
// 方法2: ウィジェット
// ============================================

class Subsidy_Diagnosis_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'subsidy_diagnosis_widget',
            '💡 AI補助金マッチング',
            array('description' => 'AI補助金マッチングアプリを表示するウィジェット')
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $height = !empty($instance['height']) ? $instance['height'] : '600px';
        $show_cta = !empty($instance['show_cta']) ? $instance['show_cta'] : 'yes';
        ?>
        <div class="subsidy-diagnosis-widget">
            <?php if ($show_cta === 'yes') : ?>
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 15px; text-align: center;">
                <p style="margin: 0; font-weight: 600;">あなたに合った補助金を診断</p>
                <p style="margin: 5px 0 0 0; font-size: 0.9rem; opacity: 0.9;">最短3分・完全無料</p>
            </div>
            <?php endif; ?>
            
            <div style="position: relative; width: 100%; height: <?php echo esc_attr($height); ?>; overflow: hidden; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <iframe 
                    src="https://matching-public.pages.dev/" 
                    style="width: 100%; height: 100%; border: none;"
                    title="AI補助金マッチング"
                    loading="lazy">
                </iframe>
            </div>
        </div>
        <?php
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '💡 AI補助金診断';
        $height = !empty($instance['height']) ? $instance['height'] : '600px';
        $show_cta = !empty($instance['show_cta']) ? $instance['show_cta'] : 'yes';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">タイトル:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('height'); ?>">高さ (例: 600px):</label>
            <input class="widefat" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo esc_attr($height); ?>">
        </p>
        <p>
            <input type="checkbox" id="<?php echo $this->get_field_id('show_cta'); ?>" name="<?php echo $this->get_field_name('show_cta'); ?>" value="yes" <?php checked($show_cta, 'yes'); ?>>
            <label for="<?php echo $this->get_field_id('show_cta'); ?>">CTAボックスを表示</label>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['height'] = (!empty($new_instance['height'])) ? strip_tags($new_instance['height']) : '600px';
        $instance['show_cta'] = (!empty($new_instance['show_cta'])) ? 'yes' : 'no';
        return $instance;
    }
}

function register_subsidy_diagnosis_widget() {
    register_widget('Subsidy_Diagnosis_Widget');
}
add_action('widgets_init', 'register_subsidy_diagnosis_widget');


// ============================================
// 方法3: フローティングボタン（全ページ表示）
// ============================================

/**
 * フローティングボタンを有効化するには、この関数のコメントを外してください
 */
/*
function add_subsidy_diagnosis_floating_button() {
    // 管理画面では表示しない
    if (is_admin()) {
        return;
    }
    ?>
    <style>
    .subsidy-float-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 25px;
        border-radius: 50px;
        box-shadow: 0 4px 20px rgba(102, 126, 234, 0.5);
        cursor: pointer;
        z-index: 9998;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        font-size: 16px;
    }
    .subsidy-float-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 25px rgba(102, 126, 234, 0.7);
    }
    .subsidy-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.75);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(5px);
    }
    .subsidy-modal.active {
        display: flex;
    }
    .subsidy-modal-content {
        position: relative;
        width: 95%;
        max-width: 1200px;
        height: 90%;
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        animation: modalSlideIn 0.3s ease-out;
    }
    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .subsidy-modal-close {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(0,0,0,0.6);
        color: white;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 28px;
        z-index: 10;
        transition: all 0.2s ease;
        border: 2px solid white;
    }
    .subsidy-modal-close:hover {
        background: rgba(0,0,0,0.9);
        transform: rotate(90deg);
    }
    .subsidy-modal iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
    @media (max-width: 768px) {
        .subsidy-float-btn {
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            font-size: 14px;
        }
        .subsidy-modal-content {
            width: 100%;
            height: 100%;
            border-radius: 0;
        }
    }
    </style>
    
    <button class="subsidy-float-btn" onclick="openSubsidyModal()">
        💡 AI補助金診断
    </button>
    
    <div class="subsidy-modal" id="subsidyModal" onclick="if(event.target===this) closeSubsidyModal()">
        <div class="subsidy-modal-content">
            <div class="subsidy-modal-close" onclick="closeSubsidyModal()">×</div>
            <iframe src="about:blank" data-src="https://matching-public.pages.dev/" title="AI補助金マッチング"></iframe>
        </div>
    </div>
    
    <script>
    function openSubsidyModal() {
        const modal = document.getElementById('subsidyModal');
        const iframe = modal.querySelector('iframe');
        
        // 初回のみiframeのsrcを設定（パフォーマンス最適化）
        if (iframe.src === 'about:blank') {
            iframe.src = iframe.dataset.src;
        }
        
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeSubsidyModal() {
        document.getElementById('subsidyModal').classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // ESCキーで閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSubsidyModal();
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'add_subsidy_diagnosis_floating_button');
*/


// ============================================
// 方法4: 条件付き自動挿入
// ============================================

/**
 * 特定の条件で記事末尾に自動挿入
 * 使用するには、この関数のコメントを外してください
 */
/*
function auto_insert_subsidy_diagnosis($content) {
    // 投稿ページでのみ実行
    if (!is_single()) {
        return $content;
    }
    
    // 特定のカテゴリーまたはタグの記事にのみ表示
    $show_on_categories = array('補助金', '助成金', 'ビジネス'); // カテゴリースラッグ
    $show_on_tags = array('補助金', '助成金', '資金調達'); // タグスラッグ
    
    $has_category = false;
    foreach ($show_on_categories as $cat) {
        if (has_category($cat)) {
            $has_category = true;
            break;
        }
    }
    
    $has_tag = false;
    foreach ($show_on_tags as $tag) {
        if (has_tag($tag)) {
            $has_tag = true;
            break;
        }
    }
    
    if (!$has_category && !$has_tag) {
        return $content;
    }
    
    // アプリを記事の末尾に追加
    $app_html = '
    <div class="auto-subsidy-diagnosis" style="margin: 50px 0; padding: 40px; background: linear-gradient(135deg, #f8f9ff 0%, #fff5f7 100%); border-radius: 16px; border: 2px solid #e5e7ff;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h3 style="font-size: 1.8rem; font-weight: 700; color: #667eea; margin: 0 0 10px 0;">
                💡 この記事に関連する補助金を診断
            </h3>
            <p style="font-size: 1.1rem; color: #666; margin: 0;">
                あなたの事業に最適な補助金を、AIが最短3分で診断します
            </p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 25px;">
            <div style="text-align: center; padding: 15px; background: white; border-radius: 10px;">
                <div style="font-size: 2rem;">🤖</div>
                <div style="font-size: 0.9rem; font-weight: 600; color: #333;">AI診断</div>
            </div>
            <div style="text-align: center; padding: 15px; background: white; border-radius: 10px;">
                <div style="font-size: 2rem;">⚡</div>
                <div style="font-size: 0.9rem; font-weight: 600; color: #333;">最短3分</div>
            </div>
            <div style="text-align: center; padding: 15px; background: white; border-radius: 10px;">
                <div style="font-size: 2rem;">🎯</div>
                <div style="font-size: 0.9rem; font-weight: 600; color: #333;">高精度</div>
            </div>
            <div style="text-align: center; padding: 15px; background: white; border-radius: 10px;">
                <div style="font-size: 2rem;">🆓</div>
                <div style="font-size: 0.9rem; font-weight: 600; color: #333;">完全無料</div>
            </div>
        </div>
        
        <div style="position: relative; width: 100%; height: 700px; overflow: hidden; border-radius: 12px; background: white; box-shadow: 0 8px 24px rgba(0,0,0,0.12);">
            <iframe 
                src="https://matching-public.pages.dev/" 
                style="width: 100%; height: 100%; border: none;"
                title="AI補助金マッチング"
                loading="lazy">
            </iframe>
        </div>
    </div>
    ';
    
    return $content . $app_html;
}
add_filter('the_content', 'auto_insert_subsidy_diagnosis');
*/


// ============================================
// 補助: セキュリティヘッダー
// ============================================

/**
 * iframe埋め込みのためのセキュリティヘッダーを追加
 */
function subsidy_diagnosis_security_headers() {
    if (!is_admin()) {
        header('X-Frame-Options: ALLOW-FROM https://matching-public.pages.dev');
        header('Content-Security-Policy: frame-ancestors \'self\' https://matching-public.pages.dev');
    }
}
add_action('send_headers', 'subsidy_diagnosis_security_headers');
