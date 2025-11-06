<?php
/**
 * Grant Dynamic CSS Generator
 * 助成金投稿の本文内容に応じて自動的にCSSクラスを生成・適用するシステム
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0.0
 * @since 9.4.0
 * 
 * 機能:
 * - 投稿本文の自動分析（表、リスト、引用、画像等の検出）
 * - コンテンツタイプに応じた最適なCSSクラスの動的生成
 * - 白黒スタイリッシュデザインの自動適用
 * - SEO最適化との統合
 * - レスポンシブデザイン対応
 */

if (!defined('ABSPATH')) {
    exit; // セキュリティチェック
}

/**
 * Grant_Dynamic_CSS_Generator クラス
 * 
 * 投稿内容を分析し、適切なCSSクラスとスタイルを自動生成
 */
class Grant_Dynamic_CSS_Generator {
    
    /**
     * @var string CSSファイルのバージョン（キャッシュバスティング用）
     */
    private $css_version = '1.0.0';
    
    /**
     * @var array 検出されたコンテンツ要素のタイプ
     */
    private $detected_elements = array();
    
    /**
     * @var array 生成されたCSSクラスのリスト
     */
    private $generated_classes = array();
    
    /**
     * コンストラクタ - フックの登録
     */
    public function __construct() {
        // CSSファイルのエンキュー
        add_action('wp_enqueue_scripts', array($this, 'enqueue_dynamic_styles'), 20);
        
        // コンテンツフィルター（最優先で実行）
        add_filter('the_content', array($this, 'analyze_and_enhance_content'), 5);
        
        // 管理画面でのプレビュー対応
        add_filter('the_content', array($this, 'add_style_preview_mode'), 999);
        
        // インラインCSSの動的生成
        add_action('wp_head', array($this, 'output_dynamic_inline_css'), 100);
    }
    
    /**
     * 動的スタイルシートをエンキュー
     */
    public function enqueue_dynamic_styles() {
        // grant投稿タイプのみで読み込み
        if (!is_singular('grant')) {
            return;
        }
        
        $css_file = get_template_directory_uri() . '/assets/css/grant-dynamic-styles.css';
        
        wp_enqueue_style(
            'grant-dynamic-styles',
            $css_file,
            array(), // 依存なし
            $this->css_version,
            'all'
        );
    }
    
    /**
     * コンテンツを分析し、動的にCSSクラスを追加
     * 
     * @param string $content 投稿本文
     * @return string 拡張されたHTML
     */
    public function analyze_and_enhance_content($content) {
        // grant投稿タイプ以外はスキップ
        if (!is_singular('grant') || empty($content)) {
            return $content;
        }
        
        // DOMパーサーを使用してコンテンツを解析
        $dom = $this->create_dom_from_content($content);
        
        if (!$dom) {
            return $content; // パース失敗時は元のコンテンツを返す
        }
        
        // 要素タイプの検出と分析
        $this->detect_content_elements($dom);
        
        // 各要素にCSSクラスを動的に追加
        $this->apply_dynamic_classes($dom);
        
        // 拡張されたHTMLを出力
        $enhanced_content = $this->extract_body_content($dom);
        
        return $enhanced_content;
    }
    
    /**
     * HTMLコンテンツからDOMDocumentオブジェクトを生成
     * 
     * @param string $content HTML文字列
     * @return DOMDocument|false DOMオブジェクトまたはfalse
     */
    private function create_dom_from_content($content) {
        // UTF-8エンコーディング対応（PHP 8.2+ の非推奨警告を回避）
        // mb_convert_encoding の HTML-ENTITIES モードは非推奨のため、htmlspecialchars を使用
        $content = htmlspecialchars_decode(htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5);
        
        // DOMDocumentの作成
        $dom = new DOMDocument('1.0', 'UTF-8');
        
        // エラー抑制（HTML5対応でない警告を無視）
        libxml_use_internal_errors(true);
        
        // HTMLをロード
        $wrapped_content = '<div id="grant-content-wrapper">' . $content . '</div>';
        $success = $dom->loadHTML('<?xml encoding="UTF-8">' . $wrapped_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        // エラークリア
        libxml_clear_errors();
        
        return $success ? $dom : false;
    }
    
    /**
     * コンテンツ内の要素タイプを検出
     * 
     * @param DOMDocument $dom DOMオブジェクト
     */
    private function detect_content_elements($dom) {
        $xpath = new DOMXPath($dom);
        
        // テーブル検出
        $tables = $xpath->query('//table');
        if ($tables->length > 0) {
            $this->detected_elements['table'] = $tables->length;
        }
        
        // リスト検出（ul, ol）
        $lists = $xpath->query('//ul | //ol');
        if ($lists->length > 0) {
            $this->detected_elements['list'] = $lists->length;
        }
        
        // 引用検出
        $blockquotes = $xpath->query('//blockquote');
        if ($blockquotes->length > 0) {
            $this->detected_elements['blockquote'] = $blockquotes->length;
        }
        
        // 画像検出
        $images = $xpath->query('//img');
        if ($images->length > 0) {
            $this->detected_elements['image'] = $images->length;
        }
        
        // 段落検出
        $paragraphs = $xpath->query('//p');
        if ($paragraphs->length > 0) {
            $this->detected_elements['paragraph'] = $paragraphs->length;
        }
        
        // 見出し検出（h2-h6）
        $headings = $xpath->query('//h2 | //h3 | //h4 | //h5 | //h6');
        if ($headings->length > 0) {
            $this->detected_elements['heading'] = $headings->length;
        }
        
        // 強調テキスト検出（strong, b, em, i）
        $emphasis = $xpath->query('//strong | //b | //em | //i');
        if ($emphasis->length > 0) {
            $this->detected_elements['emphasis'] = $emphasis->length;
        }
        
        // リンク検出
        $links = $xpath->query('//a');
        if ($links->length > 0) {
            $this->detected_elements['link'] = $links->length;
        }
    }
    
    /**
     * 検出した要素に動的CSSクラスを適用
     * 
     * @param DOMDocument $dom DOMオブジェクト
     */
    private function apply_dynamic_classes($dom) {
        $xpath = new DOMXPath($dom);
        
        // テーブルのスタイリング
        if (isset($this->detected_elements['table'])) {
            $this->style_tables($xpath);
        }
        
        // リストのスタイリング
        if (isset($this->detected_elements['list'])) {
            $this->style_lists($xpath);
        }
        
        // 引用のスタイリング
        if (isset($this->detected_elements['blockquote'])) {
            $this->style_blockquotes($xpath);
        }
        
        // 画像のスタイリング
        if (isset($this->detected_elements['image'])) {
            $this->style_images($xpath);
        }
        
        // 段落のスタイリング
        if (isset($this->detected_elements['paragraph'])) {
            $this->style_paragraphs($xpath);
        }
        
        // 見出しのスタイリング
        if (isset($this->detected_elements['heading'])) {
            $this->style_headings($xpath);
        }
        
        // 強調テキストのスタイリング
        if (isset($this->detected_elements['emphasis'])) {
            $this->style_emphasis($xpath);
        }
        
        // リンクのスタイリング
        if (isset($this->detected_elements['link'])) {
            $this->style_links($xpath);
        }
    }
    
    /**
     * テーブルにスタイルクラスを適用
     * 
     * @param DOMXPath $xpath XPathオブジェクト
     */
    private function style_tables($xpath) {
        $tables = $xpath->query('//table');
        
        foreach ($tables as $index => $table) {
            $existing_class = $table->getAttribute('class');
            $new_classes = array('gdc-table', 'gdc-table--monochrome');
            
            // テーブル構造を分析
            $has_thead = $xpath->query('.//thead', $table)->length > 0;
            $has_tbody = $xpath->query('.//tbody', $table)->length > 0;
            $row_count = $xpath->query('.//tr', $table)->length;
            
            // 構造に応じたクラス追加
            if ($has_thead) {
                $new_classes[] = 'gdc-table--with-header';
            }
            
            if ($row_count > 10) {
                $new_classes[] = 'gdc-table--large';
            } elseif ($row_count <= 3) {
                $new_classes[] = 'gdc-table--compact';
            }
            
            // ストライプパターン（奇数行）
            $new_classes[] = 'gdc-table--striped';
            
            // クラス適用
            $combined_class = trim($existing_class . ' ' . implode(' ', $new_classes));
            $table->setAttribute('class', $combined_class);
            
            // データ属性追加（スタイリングの参考情報）
            $table->setAttribute('data-gdc-rows', $row_count);
            $table->setAttribute('data-gdc-index', $index);
            
            $this->generated_classes[] = 'table-' . $index;
        }
    }
    
    /**
     * リストにスタイルクラスを適用
     * 
     * @param DOMXPath $xpath XPathオブジェクト
     */
    private function style_lists($xpath) {
        $lists = $xpath->query('//ul | //ol');
        
        foreach ($lists as $index => $list) {
            $existing_class = $list->getAttribute('class');
            $list_type = $list->nodeName; // ul or ol
            $new_classes = array('gdc-list', 'gdc-list--monochrome');
            
            // リストタイプ別クラス
            if ($list_type === 'ul') {
                $new_classes[] = 'gdc-list--unordered';
            } else {
                $new_classes[] = 'gdc-list--ordered';
            }
            
            // リスト項目数を分析
            $item_count = $xpath->query('.//li', $list)->length;
            
            if ($item_count > 10) {
                $new_classes[] = 'gdc-list--long';
            } elseif ($item_count <= 3) {
                $new_classes[] = 'gdc-list--short';
            }
            
            // ネストレベル検出（親リスト内にあるか）
            $parent_list = $xpath->query('ancestor::ul | ancestor::ol', $list);
            if ($parent_list->length > 0) {
                $new_classes[] = 'gdc-list--nested';
            }
            
            // クラス適用
            $combined_class = trim($existing_class . ' ' . implode(' ', $new_classes));
            $list->setAttribute('class', $combined_class);
            
            $this->generated_classes[] = 'list-' . $index;
        }
    }
    
    /**
     * 引用にスタイルクラスを適用
     * 
     * @param DOMXPath $xpath XPathオブジェクト
     */
    private function style_blockquotes($xpath) {
        $blockquotes = $xpath->query('//blockquote');
        
        foreach ($blockquotes as $index => $blockquote) {
            $existing_class = $blockquote->getAttribute('class');
            $new_classes = array('gdc-blockquote', 'gdc-blockquote--monochrome');
            
            // テキスト長を分析
            $text_length = strlen($blockquote->textContent);
            
            if ($text_length > 200) {
                $new_classes[] = 'gdc-blockquote--long';
            } else {
                $new_classes[] = 'gdc-blockquote--short';
            }
            
            // クラス適用
            $combined_class = trim($existing_class . ' ' . implode(' ', $new_classes));
            $blockquote->setAttribute('class', $combined_class);
            
            $this->generated_classes[] = 'blockquote-' . $index;
        }
    }
    
    /**
     * 画像にスタイルクラスを適用
     * 
     * @param DOMXPath $xpath XPathオブジェクト
     */
    private function style_images($xpath) {
        $images = $xpath->query('//img');
        
        foreach ($images as $index => $img) {
            $existing_class = $img->getAttribute('class');
            $new_classes = array('gdc-image', 'gdc-image--monochrome');
            
            // 画像の配置を分析（親要素のクラスから推測）
            $parent = $img->parentNode;
            if ($parent) {
                $parent_class = $parent->getAttribute('class');
                
                if (strpos($parent_class, 'aligncenter') !== false) {
                    $new_classes[] = 'gdc-image--center';
                } elseif (strpos($parent_class, 'alignleft') !== false) {
                    $new_classes[] = 'gdc-image--left';
                } elseif (strpos($parent_class, 'alignright') !== false) {
                    $new_classes[] = 'gdc-image--right';
                }
            }
            
            // レスポンシブ対応
            $new_classes[] = 'gdc-image--responsive';
            
            // クラス適用
            $combined_class = trim($existing_class . ' ' . implode(' ', $new_classes));
            $img->setAttribute('class', $combined_class);
            
            // lazyload対応
            if (!$img->hasAttribute('loading')) {
                $img->setAttribute('loading', 'lazy');
            }
            
            $this->generated_classes[] = 'image-' . $index;
        }
    }
    
    /**
     * 段落にスタイルクラスを適用
     * 
     * @param DOMXPath $xpath XPathオブジェクト
     */
    private function style_paragraphs($xpath) {
        $paragraphs = $xpath->query('//p');
        
        foreach ($paragraphs as $index => $p) {
            // 空の段落はスキップ
            if (trim($p->textContent) === '') {
                continue;
            }
            
            $existing_class = $p->getAttribute('class');
            $new_classes = array('gdc-paragraph');
            
            // テキスト長を分析
            $text_length = strlen($p->textContent);
            
            if ($text_length > 300) {
                $new_classes[] = 'gdc-paragraph--long';
            } elseif ($text_length < 50) {
                $new_classes[] = 'gdc-paragraph--short';
            }
            
            // 最初の段落（リード文）
            if ($index === 0) {
                $new_classes[] = 'gdc-paragraph--lead';
            }
            
            // クラス適用
            $combined_class = trim($existing_class . ' ' . implode(' ', $new_classes));
            $p->setAttribute('class', $combined_class);
        }
    }
    
    /**
     * 見出しにスタイルクラスを適用
     * 
     * @param DOMXPath $xpath XPathオブジェクト
     */
    private function style_headings($xpath) {
        $headings = $xpath->query('//h2 | //h3 | //h4 | //h5 | //h6');
        
        foreach ($headings as $index => $heading) {
            $existing_class = $heading->getAttribute('class');
            $heading_level = $heading->nodeName; // h2, h3, etc.
            $new_classes = array('gdc-heading', 'gdc-heading--' . $heading_level);
            
            // モノクロームスタイル
            $new_classes[] = 'gdc-heading--monochrome';
            
            // テキスト長を分析
            $text_length = strlen($heading->textContent);
            
            if ($text_length > 50) {
                $new_classes[] = 'gdc-heading--long';
            }
            
            // クラス適用
            $combined_class = trim($existing_class . ' ' . implode(' ', $new_classes));
            $heading->setAttribute('class', $combined_class);
        }
    }
    
    /**
     * 強調テキストにスタイルクラスを適用
     * 
     * @param DOMXPath $xpath XPathオブジェクト
     */
    private function style_emphasis($xpath) {
        $emphasis_elements = $xpath->query('//strong | //b | //em | //i');
        
        foreach ($emphasis_elements as $elem) {
            $existing_class = $elem->getAttribute('class');
            $tag_name = $elem->nodeName;
            
            $new_class = '';
            
            if ($tag_name === 'strong' || $tag_name === 'b') {
                $new_class = 'gdc-strong';
            } elseif ($tag_name === 'em' || $tag_name === 'i') {
                $new_class = 'gdc-emphasis';
            }
            
            // クラス適用
            $combined_class = trim($existing_class . ' ' . $new_class);
            $elem->setAttribute('class', $combined_class);
        }
    }
    
    /**
     * リンクにスタイルクラスを適用
     * 
     * @param DOMXPath $xpath XPathオブジェクト
     */
    private function style_links($xpath) {
        $links = $xpath->query('//a');
        
        foreach ($links as $index => $link) {
            $existing_class = $link->getAttribute('class');
            $new_classes = array('gdc-link');
            
            $href = $link->getAttribute('href');
            
            // 外部リンク判定
            if (!empty($href) && (strpos($href, 'http://') === 0 || strpos($href, 'https://') === 0)) {
                $site_url = get_site_url();
                if (strpos($href, $site_url) !== 0) {
                    $new_classes[] = 'gdc-link--external';
                    
                    // セキュリティ対策
                    if (!$link->hasAttribute('rel')) {
                        $link->setAttribute('rel', 'noopener noreferrer');
                    }
                } else {
                    $new_classes[] = 'gdc-link--internal';
                }
            }
            
            // クラス適用
            $combined_class = trim($existing_class . ' ' . implode(' ', $new_classes));
            $link->setAttribute('class', $combined_class);
        }
    }
    
    /**
     * DOMからbodyコンテンツのみを抽出
     * 
     * @param DOMDocument $dom DOMオブジェクト
     * @return string HTML文字列
     */
    private function extract_body_content($dom) {
        $wrapper = $dom->getElementById('grant-content-wrapper');
        
        if (!$wrapper) {
            return $dom->saveHTML();
        }
        
        // ラッパー内のHTMLを取得
        $html = '';
        foreach ($wrapper->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }
        
        return $html;
    }
    
    /**
     * 動的インラインCSSを出力（カスタマイズ用）
     */
    public function output_dynamic_inline_css() {
        if (!is_singular('grant')) {
            return;
        }
        
        // 必要に応じて投稿ごとのカスタムCSSを出力
        // 現在は基本CSSファイルのみ使用
        
        ?>
        <style id="grant-dynamic-custom-css">
        /* 動的CSS生成: 投稿ID <?php echo get_the_ID(); ?> */
        .gdc-content-wrapper {
            /* 投稿ごとのカスタムスタイルをここに追加可能 */
        }
        </style>
        <?php
    }
    
    /**
     * プレビューモード用のスタイル表示
     * 
     * @param string $content コンテンツ
     * @return string 拡張されたコンテンツ
     */
    public function add_style_preview_mode($content) {
        if (!is_singular('grant') || !current_user_can('edit_posts')) {
            return $content;
        }
        
        // 管理者向けのデバッグ情報（プレビューモード）
        if (isset($_GET['gdc_debug']) && $_GET['gdc_debug'] === '1') {
            $debug_info = '<div class="gdc-debug-info" style="background: #f0f0f0; border: 2px solid #333; padding: 15px; margin: 20px 0; font-family: monospace; font-size: 12px;">';
            $debug_info .= '<h4 style="margin-top: 0;">動的CSS生成 - デバッグ情報</h4>';
            $debug_info .= '<p><strong>検出された要素:</strong></p>';
            $debug_info .= '<pre>' . print_r($this->detected_elements, true) . '</pre>';
            $debug_info .= '<p><strong>生成されたクラス:</strong></p>';
            $debug_info .= '<pre>' . print_r($this->generated_classes, true) . '</pre>';
            $debug_info .= '</div>';
            
            $content = $debug_info . $content;
        }
        
        return $content;
    }
    
    /**
     * 検出された要素情報を取得（外部アクセス用）
     * 
     * @return array 検出された要素の配列
     */
    public function get_detected_elements() {
        return $this->detected_elements;
    }
    
    /**
     * 生成されたクラス情報を取得（外部アクセス用）
     * 
     * @return array 生成されたクラスの配列
     */
    public function get_generated_classes() {
        return $this->generated_classes;
    }
}

// クラスのインスタンス化
function gi_initialize_dynamic_css_generator() {
    return new Grant_Dynamic_CSS_Generator();
}

// 初期化（functions.phpから自動読み込み）
add_action('init', 'gi_initialize_dynamic_css_generator');
