# 🎯 アプリケーションを動的に表示させる方法

WordPress サイト（https://joseikin-insight.com）にAI補助金マッチングアプリを動的に表示させる方法をご紹介します。

---

## 🚀 方法1: ショートコード（最も簡単！）

### functions.php に追加

```php
<?php
/**
 * AI補助金マッチングアプリのショートコード
 */
function subsidy_diagnosis_shortcode($atts) {
    // パラメータの設定
    $atts = shortcode_atts(array(
        'height' => '800px',
        'width' => '100%',
        'max_width' => '1200px',
    ), $atts);
    
    ob_start();
    ?>
    <div class="subsidy-diagnosis-embed" style="max-width: <?php echo esc_attr($atts['max_width']); ?>; margin: 0 auto;">
        <div style="position: relative; width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>; overflow: hidden; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
            <div class="loading-overlay" style="position: absolute; width: 100%; height: 100%; background: rgba(255,255,255,0.9); display: flex; align-items: center; justify-content: center; z-index: 10;">
                <div class="spinner" style="width: 50px; height: 50px; border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            </div>
            <iframe 
                src="https://matching-public.pages.dev/" 
                style="width: 100%; height: 100%; border: none;"
                title="AI補助金マッチング診断"
                onload="this.previousElementSibling.style.display='none';">
            </iframe>
        </div>
    </div>
    <style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('subsidy_diagnosis', 'subsidy_diagnosis_shortcode');
```

### 使い方

#### 投稿・固定ページに埋め込む
```
[subsidy_diagnosis]
```

#### パラメータ付き
```
[subsidy_diagnosis height="600px" width="100%" max_width="1000px"]
```

#### PHPテンプレートで使用
```php
<?php echo do_shortcode('[subsidy_diagnosis]'); ?>
```

---

## 🎨 方法2: ウィジェット（サイドバー・フッター対応）

### functions.php に追加

```php
<?php
/**
 * AI補助金マッチング ウィジェット
 */
class Subsidy_Diagnosis_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'subsidy_diagnosis_widget',
            'AI補助金マッチング',
            array('description' => 'AI補助金マッチングアプリを表示')
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $height = !empty($instance['height']) ? $instance['height'] : '600px';
        ?>
        <div class="subsidy-diagnosis-widget">
            <div style="position: relative; width: 100%; height: <?php echo esc_attr($height); ?>; overflow: hidden; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
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
        $title = !empty($instance['title']) ? $instance['title'] : 'AI補助金診断';
        $height = !empty($instance['height']) ? $instance['height'] : '600px';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">タイトル:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('height'); ?>">高さ:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo esc_attr($height); ?>" placeholder="600px">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['height'] = (!empty($new_instance['height'])) ? strip_tags($new_instance['height']) : '600px';
        return $instance;
    }
}

function register_subsidy_diagnosis_widget() {
    register_widget('Subsidy_Diagnosis_Widget');
}
add_action('widgets_init', 'register_subsidy_diagnosis_widget');
```

### 使い方
1. WordPress管理画面 → 外観 → ウィジェット
2. 「AI補助金マッチング」を任意のエリアにドラッグ
3. タイトルと高さを設定
4. 保存

---

## 🔥 方法3: Gutenbergブロック（最新！）

### functions.php に追加

```php
<?php
/**
 * AI補助金マッチング Gutenbergブロック
 */
function register_subsidy_diagnosis_block() {
    wp_register_script(
        'subsidy-diagnosis-block',
        get_template_directory_uri() . '/js/subsidy-diagnosis-block.js',
        array('wp-blocks', 'wp-element', 'wp-editor'),
        filemtime(get_template_directory() . '/js/subsidy-diagnosis-block.js')
    );
    
    register_block_type('custom/subsidy-diagnosis', array(
        'editor_script' => 'subsidy-diagnosis-block',
        'render_callback' => 'render_subsidy_diagnosis_block',
        'attributes' => array(
            'height' => array(
                'type' => 'string',
                'default' => '800px'
            ),
            'showFeatures' => array(
                'type' => 'boolean',
                'default' => true
            )
        )
    ));
}
add_action('init', 'register_subsidy_diagnosis_block');

function render_subsidy_diagnosis_block($attributes) {
    $height = isset($attributes['height']) ? $attributes['height'] : '800px';
    $show_features = isset($attributes['showFeatures']) ? $attributes['showFeatures'] : true;
    
    ob_start();
    ?>
    <div class="wp-block-subsidy-diagnosis">
        <?php if ($show_features) : ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); text-align: center;">
                <div style="font-size: 3rem;">🤖</div>
                <h3>AI診断</h3>
                <p style="color: #666;">最新AI技術で最適な補助金を選定</p>
            </div>
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); text-align: center;">
                <div style="font-size: 3rem;">⚡</div>
                <h3>最短3分</h3>
                <p style="color: #666;">簡単な質問ですぐに結果</p>
            </div>
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); text-align: center;">
                <div style="font-size: 3rem;">🎯</div>
                <h3>高精度マッチング</h3>
                <p style="color: #666;">8,000件以上のデータから提案</p>
            </div>
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); text-align: center;">
                <div style="font-size: 3rem;">🆓</div>
                <h3>完全無料</h3>
                <p style="color: #666;">登録不要で何度でも利用可能</p>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="position: relative; width: 100%; height: <?php echo esc_attr($height); ?>; overflow: hidden; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); background: white;">
            <iframe 
                src="https://matching-public.pages.dev/" 
                style="width: 100%; height: 100%; border: none;"
                title="AI補助金マッチング診断"
                loading="eager">
            </iframe>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
```

### js/subsidy-diagnosis-block.js を作成

```javascript
(function(blocks, element, editor) {
    var el = element.createElement;
    var InspectorControls = editor.InspectorControls;
    var TextControl = wp.components.TextControl;
    var ToggleControl = wp.components.ToggleControl;
    var PanelBody = wp.components.PanelBody;
    
    blocks.registerBlockType('custom/subsidy-diagnosis', {
        title: 'AI補助金マッチング',
        icon: 'chart-bar',
        category: 'widgets',
        attributes: {
            height: {
                type: 'string',
                default: '800px'
            },
            showFeatures: {
                type: 'boolean',
                default: true
            }
        },
        
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            
            return [
                el(InspectorControls, {},
                    el(PanelBody, {title: '設定', initialOpen: true},
                        el(TextControl, {
                            label: '高さ',
                            value: attributes.height,
                            onChange: function(value) {
                                setAttributes({height: value});
                            }
                        }),
                        el(ToggleControl, {
                            label: '特徴カードを表示',
                            checked: attributes.showFeatures,
                            onChange: function(value) {
                                setAttributes({showFeatures: value});
                            }
                        })
                    )
                ),
                el('div', {className: 'subsidy-diagnosis-block-preview'},
                    el('div', {style: {padding: '20px', background: '#f5f5f5', borderRadius: '8px', textAlign: 'center'}},
                        el('p', {style: {fontSize: '24px'}}, '🤖'),
                        el('p', {}, 'AI補助金マッチング'),
                        el('p', {style: {fontSize: '12px', color: '#666'}}, '高さ: ' + attributes.height),
                        el('p', {style: {fontSize: '12px', color: '#666'}}, '特徴カード: ' + (attributes.showFeatures ? '表示' : '非表示'))
                    )
                )
            ];
        },
        
        save: function() {
            return null; // Dynamic block
        }
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor
);
```

---

## 💪 方法4: 条件付き動的表示

### functions.php に追加

```php
<?php
/**
 * 条件に応じて自動的にアプリを表示
 */
function auto_insert_subsidy_diagnosis($content) {
    // 特定のカテゴリーやタグの投稿にのみ表示
    if (is_single() && (has_category('補助金') || has_tag('助成金'))) {
        $app_html = '
        <div class="auto-subsidy-diagnosis" style="margin: 40px 0; padding: 30px; background: #f8f9ff; border-radius: 12px; border-left: 4px solid #667eea;">
            <h3 style="margin-top: 0; color: #667eea;">💡 あなたに合った補助金を診断</h3>
            <p style="margin-bottom: 20px;">この記事に関連する補助金を、AIが最短3分で診断します。</p>
            <div style="position: relative; width: 100%; height: 600px; overflow: hidden; border-radius: 8px; background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <iframe 
                    src="https://matching-public.pages.dev/" 
                    style="width: 100%; height: 100%; border: none;"
                    title="AI補助金マッチング"
                    loading="lazy">
                </iframe>
            </div>
        </div>
        ';
        
        // 記事の後に追加
        $content .= $app_html;
    }
    
    return $content;
}
add_filter('the_content', 'auto_insert_subsidy_diagnosis');
```

### カスタマイズ例

```php
<?php
// 特定のページIDにのみ表示
if (is_page(array(123, 456, 789))) {
    // アプリを表示
}

// ログインユーザーにのみ表示
if (is_user_logged_in()) {
    // アプリを表示
}

// モバイルユーザーのみ表示
if (wp_is_mobile()) {
    // アプリを表示
}

// 投稿の文字数が一定以上の場合のみ表示
if (str_word_count($content) > 500) {
    // アプリを表示
}
```

---

## 🎯 方法5: ポップアップ/モーダル表示

### functions.php に追加

```php
<?php
/**
 * フローティングボタンとモーダルで表示
 */
function add_subsidy_diagnosis_modal() {
    ?>
    <style>
    .subsidy-diagnosis-float-button {
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
        transition: transform 0.3s ease;
    }
    .subsidy-diagnosis-float-button:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 25px rgba(102, 126, 234, 0.7);
    }
    .subsidy-diagnosis-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    .subsidy-diagnosis-modal.active {
        display: flex;
    }
    .subsidy-diagnosis-modal-content {
        position: relative;
        width: 90%;
        max-width: 1200px;
        height: 90%;
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    .subsidy-diagnosis-modal-close {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(0,0,0,0.5);
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 24px;
        z-index: 10;
    }
    .subsidy-diagnosis-modal-close:hover {
        background: rgba(0,0,0,0.8);
    }
    .subsidy-diagnosis-modal iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
    </style>
    
    <div class="subsidy-diagnosis-float-button" onclick="openSubsidyDiagnosis()">
        💡 AI補助金診断
    </div>
    
    <div class="subsidy-diagnosis-modal" id="subsidyDiagnosisModal">
        <div class="subsidy-diagnosis-modal-content">
            <div class="subsidy-diagnosis-modal-close" onclick="closeSubsidyDiagnosis()">×</div>
            <iframe src="https://matching-public.pages.dev/" title="AI補助金マッチング"></iframe>
        </div>
    </div>
    
    <script>
    function openSubsidyDiagnosis() {
        document.getElementById('subsidyDiagnosisModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeSubsidyDiagnosis() {
        document.getElementById('subsidyDiagnosisModal').classList.remove('active');
        document.body.style.overflow = '';
    }
    // ESCキーで閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSubsidyDiagnosis();
        }
    });
    // 背景クリックで閉じる
    document.getElementById('subsidyDiagnosisModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeSubsidyDiagnosis();
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'add_subsidy_diagnosis_modal');
```

---

## 📊 方法6: REST API連携（高度）

アプリのデータをWordPress側で取得して独自UIで表示：

```php
<?php
/**
 * Cloudflare D1のデータをWordPress側で取得
 */
function get_matching_grants($user_data) {
    $api_url = 'https://matching-public.pages.dev/api/recommendations';
    
    $response = wp_remote_post($api_url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => json_encode($user_data),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}

// ショートコードで使用
function custom_grant_search_shortcode() {
    ob_start();
    ?>
    <div id="custom-grant-search">
        <h3>補助金検索</h3>
        <form id="grant-search-form">
            <input type="text" name="business_type" placeholder="事業種別">
            <input type="text" name="prefecture" placeholder="都道府県">
            <button type="submit">検索</button>
        </form>
        <div id="search-results"></div>
    </div>
    <script>
    document.getElementById('grant-search-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('https://matching-public.pages.dev/api/grants?' + new URLSearchParams(formData))
            .then(res => res.json())
            .then(data => {
                const resultsDiv = document.getElementById('search-results');
                resultsDiv.innerHTML = data.grants.map(grant => `
                    <div class="grant-item">
                        <h4>${grant.title}</h4>
                        <p>${grant.excerpt}</p>
                    </div>
                `).join('');
            });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('custom_grant_search', 'custom_grant_search_shortcode');
```

---

## 🎨 推奨される実装方法

### 🥇 最も簡単: ショートコード（方法1）
- **利点**: 簡単、柔軟、どこでも使える
- **用途**: 特定のページや投稿に埋め込む
- **難易度**: ★☆☆☆☆

### 🥈 最もモダン: Gutenbergブロック（方法3）
- **利点**: ビジュアル編集、WordPress標準
- **用途**: ブロックエディタで使用
- **難易度**: ★★★☆☆

### 🥉 最も目立つ: フローティングボタン（方法5）
- **利点**: 全ページで表示、目立つ
- **用途**: サイト全体でアクセス可能に
- **難易度**: ★★☆☆☆

---

## 📱 モバイル対応の注意点

```php
<?php
// モバイルでは高さを調整
function get_responsive_height() {
    if (wp_is_mobile()) {
        return '100vh'; // モバイルはビューポート全体
    } else {
        return '800px'; // デスクトップは固定
    }
}
```

---

## 🔧 パフォーマンス最適化

```php
<?php
// 特定のページでのみスクリプトを読み込む
function conditional_load_subsidy_diagnosis() {
    if (is_page('subsidy-diagnosis')) {
        // 必要なスクリプトのみ読み込み
        wp_enqueue_script('subsidy-diagnosis');
    }
}
add_action('wp_enqueue_scripts', 'conditional_load_subsidy_diagnosis');
```

---

## ✅ どの方法を選ぶべき？

| 目的 | 推奨方法 |
|------|----------|
| 1つのページに埋め込み | ショートコード or ページテンプレート |
| 複数ページで使い回し | ショートコード |
| サイドバー表示 | ウィジェット |
| 全ページで常時表示 | フローティングボタン |
| 記事末尾に自動挿入 | 条件付き動的表示 |
| ブロックエディタ活用 | Gutenbergブロック |

---

## 🆘 サポート

実装でお困りの場合は：
1. `WORDPRESS_EMBED_GUIDE.md` を確認
2. `page-subsidy-diagnosis.php` をテンプレートとして参照
3. ブラウザの開発者ツールでエラー確認

---

**全ての方法を試して、あなたのサイトに最適な方法を見つけてください！** 🚀
