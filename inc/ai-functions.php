<?php
/**
 * Enhanced AI Content Generator
 * Advanced AI generation with context awareness and SEO optimization
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GI_Enhanced_AI_Generator {
    
    private $api_key;
    private $model = 'gpt-3.5-turbo';
    
    public function __construct() {
        // Get API key from options or constants
        $this->api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : get_option('gi_openai_api_key', '');
        
        add_action('wp_ajax_gi_smart_generate', array($this, 'handle_smart_generation'));
        add_action('wp_ajax_gi_regenerate_content', array($this, 'handle_regeneration'));
        add_action('wp_ajax_gi_contextual_fill', array($this, 'handle_contextual_fill'));
    }
    
    /**
     * Smart content generation based on existing fields
     */
    public function handle_smart_generation() {
        check_ajax_referer('gi_ai_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Permission denied');
        }
        
        $existing_data = $this->sanitize_input($_POST['existing_data'] ?? []);
        $target_field = sanitize_text_field($_POST['target_field'] ?? '');
        $generation_mode = sanitize_text_field($_POST['mode'] ?? 'smart_fill');
        
        try {
            $generated_content = $this->generate_contextual_content($existing_data, $target_field, $generation_mode);
            
            wp_send_json_success([
                'content' => $generated_content,
                'field' => $target_field,
                'mode' => $generation_mode,
                'context_used' => !empty($existing_data)
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'fallback' => $this->get_fallback_content($target_field, $existing_data)
            ]);
        }
    }
    
    /**
     * Handle content regeneration
     */
    public function handle_regeneration() {
        check_ajax_referer('gi_ai_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Permission denied');
        }
        
        $existing_data = $this->sanitize_input($_POST['existing_data'] ?? []);
        $target_field = sanitize_text_field($_POST['target_field'] ?? '');
        $current_content = sanitize_textarea_field($_POST['current_content'] ?? '');
        $regeneration_type = sanitize_text_field($_POST['type'] ?? 'improve');
        
        try {
            $regenerated_content = $this->regenerate_content($existing_data, $target_field, $current_content, $regeneration_type);
            
            wp_send_json_success([
                'content' => $regenerated_content,
                'original' => $current_content,
                'type' => $regeneration_type
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'fallback' => $this->improve_content_simple($current_content, $target_field)
            ]);
        }
    }
    
    /**
     * Handle contextual filling of multiple fields
     */
    public function handle_contextual_fill() {
        check_ajax_referer('gi_ai_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Permission denied');
        }
        
        $existing_data = $this->sanitize_input($_POST['existing_data'] ?? []);
        $empty_fields = $_POST['empty_fields'] ?? [];
        
        try {
            $filled_content = $this->fill_empty_fields($existing_data, $empty_fields);
            
            wp_send_json_success([
                'filled_fields' => $filled_content,
                'context_data' => $existing_data
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'partial_fill' => $this->get_fallback_fills($empty_fields)
            ]);
        }
    }
    
    /**
     * Generate contextual content based on existing data
     */
    private function generate_contextual_content($existing_data, $target_field, $mode) {
        // Build context from existing data
        $context = $this->build_context_prompt($existing_data);
        
        // Field-specific generation prompts
        $field_prompts = $this->get_field_specific_prompts();
        $field_prompt = $field_prompts[$target_field] ?? $field_prompts['default'];
        
        // SEO optimization instructions
        $seo_instructions = $this->get_seo_instructions($target_field);
        
        // Build the complete prompt
        $prompt = $this->build_generation_prompt($context, $field_prompt, $seo_instructions, $mode);
        
        // Call AI API
        return $this->call_openai_api($prompt);
    }
    
    /**
     * Build comprehensive context prompt from all available data
     */
    private function build_context_prompt($data) {
        $context_parts = [];
        
        // 基本情報
        if (!empty($data['title'])) {
            $context_parts[] = "助成金名: {$data['title']}";
        }
        
        if (!empty($data['organization'])) {
            $context_parts[] = "実施機関: {$data['organization']}";
        }
        
        if (!empty($data['organization_type'])) {
            $context_parts[] = "組織タイプ: {$data['organization_type']}";
        }
        
        // 金額情報
        if (!empty($data['max_amount'])) {
            $context_parts[] = "最大金額: {$data['max_amount']}万円";
        }
        
        if (!empty($data['min_amount'])) {
            $context_parts[] = "最小金額: {$data['min_amount']}万円";
        }
        
        if (!empty($data['max_amount_yen'])) {
            $context_parts[] = "最大助成額: " . number_format($data['max_amount_yen']) . "円";
        }
        
        if (!empty($data['subsidy_rate'])) {
            $context_parts[] = "補助率: {$data['subsidy_rate']}%";
        }
        
        if (!empty($data['amount_note'])) {
            $context_parts[] = "金額備考: {$data['amount_note']}";
        }
        
        // 期間情報
        if (!empty($data['application_deadline'])) {
            $context_parts[] = "申請期限: {$data['application_deadline']}";
        }
        
        if (!empty($data['recruitment_start'])) {
            $context_parts[] = "募集開始日: {$data['recruitment_start']}";
        }
        
        if (!empty($data['deadline'])) {
            $context_parts[] = "締切日: {$data['deadline']}";
        }
        
        if (!empty($data['deadline_note'])) {
            $context_parts[] = "締切備考: {$data['deadline_note']}";
        }
        
        if (!empty($data['application_status'])) {
            $context_parts[] = "申請ステータス: {$data['application_status']}";
        }
        
        // 対象・カテゴリー情報
        if (!empty($data['prefectures'])) {
            $prefectures = is_array($data['prefectures']) ? implode('、', $data['prefectures']) : $data['prefectures'];
            $context_parts[] = "対象都道府県: {$prefectures}";
        }
        
        if (!empty($data['categories'])) {
            $categories = is_array($data['categories']) ? implode('、', $data['categories']) : $data['categories'];
            $context_parts[] = "カテゴリー: {$categories}";
        }
        
        if (!empty($data['tags'])) {
            $tags = is_array($data['tags']) ? implode('、', $data['tags']) : $data['tags'];
            $context_parts[] = "タグ: {$tags}";
        }
        
        if (!empty($data['grant_target'])) {
            $context_parts[] = "助成金対象: {$data['grant_target']}";
        }
        
        if (!empty($data['target_expenses'])) {
            $context_parts[] = "対象経費: {$data['target_expenses']}";
        }
        
        // 難易度・成功率
        if (!empty($data['difficulty'])) {
            $context_parts[] = "難易度: {$data['difficulty']}";
        }
        
        if (!empty($data['success_rate'])) {
            $context_parts[] = "成功率: {$data['success_rate']}%";
        }
        
        // 詳細情報
        if (!empty($data['eligibility_criteria'])) {
            $criteria_excerpt = mb_substr(strip_tags($data['eligibility_criteria']), 0, 150);
            $context_parts[] = "対象者・応募要件: {$criteria_excerpt}...";
        }
        
        if (!empty($data['application_process'])) {
            $process_excerpt = mb_substr(strip_tags($data['application_process']), 0, 150);
            $context_parts[] = "申請手順: {$process_excerpt}...";
        }
        
        if (!empty($data['application_method'])) {
            $context_parts[] = "申請方法: {$data['application_method']}";
        }
        
        if (!empty($data['required_documents'])) {
            $documents_excerpt = mb_substr(strip_tags($data['required_documents']), 0, 100);
            $context_parts[] = "必要書類: {$documents_excerpt}...";
        }
        
        if (!empty($data['contact_info'])) {
            $context_parts[] = "連絡先: {$data['contact_info']}";
        }
        
        if (!empty($data['official_url'])) {
            $context_parts[] = "公式URL: {$data['official_url']}";
        }
        
        if (!empty($data['summary'])) {
            $summary_excerpt = mb_substr(strip_tags($data['summary']), 0, 200);
            $context_parts[] = "概要: {$summary_excerpt}...";
        }
        
        if (!empty($data['content'])) {
            $content_excerpt = mb_substr(strip_tags($data['content']), 0, 200);
            $context_parts[] = "既存本文: {$content_excerpt}...";
        }
        
        return implode("\n", $context_parts);
    }
    
    /**
     * Get field-specific generation prompts with enhanced HTML/CSS support
     */
    private function get_field_specific_prompts() {
        return [
            'post_title' => [
                'instruction' => '魅力的で検索されやすい助成金タイトルを生成してください',
                'requirements' => '30-60文字、キーワードを含む、具体的で分かりやすい、緊急性や魅力を表現',
                'examples' => '「【令和6年度】IT導入支援事業補助金（最大1000万円）」「中小企業デジタル化促進助成金【申請期限間近】」'
            ],
            'post_content' => [
                'instruction' => 'HTMLとCSSを使用したスタイリッシュで詳細な助成金本文を生成してください',
                'requirements' => '1000-2500文字、HTML構造化、CSS付き、白黒ベースのスタイリッシュなデザイン、黄色蛍光ペン効果使用',
                'structure' => '概要（アイコン付き）→金額詳細（表組み）→対象者（箇条書き）→申請手順（ステップ表示）→必要書類（チェックリスト）→注意事項（警告ボックス）→連絡先（ボックス表示）',
                'html_requirements' => 'div, h2, h3, table, ul, ol, span, strong要素を使用。CSS classを含める。',
                'css_style' => 'モノクロ（#000, #333, #666, #ccc, #f9f9f9）+ 黄色ハイライト（#ffeb3b, #fff59d）を使用',
                'design_theme' => '白黒ベースのスタイリッシュなビジネス文書風、重要部分に黄色蛍光ペン効果'
            ],
            'post_excerpt' => [
                'instruction' => '簡潔で魅力的な助成金概要を生成してください',
                'requirements' => '120-180文字、要点を簡潔に、検索結果で目立つ内容、金額と対象を明確に',
                'focus' => '対象者、最大金額、申請期限、メリットを明確に',
                'tone' => '専門的だが親しみやすく、行動を促す表現'
            ],
            'eligibility_criteria' => [
                'instruction' => '具体的で分かりやすい対象者・応募要件をHTML形式で生成してください',
                'requirements' => 'HTML箇条書き形式、具体的な条件、除外条件も含む、視覚的に分かりやすい',
                'html_format' => '<ul>タグと<li>タグを使用、重要な条件は<strong>で強調',
                'style' => '明確で読みやすい構造、条件の階層化'
            ],
            'application_process' => [
                'instruction' => 'ステップバイステップの申請手順をHTML形式で生成してください',
                'requirements' => 'HTML番号付きリスト、各ステップの詳細、期間、注意点を含む',
                'html_format' => '<ol>と<li>を使用、各ステップに説明とポイントを追加',
                'visual_elements' => 'ステップ番号を視覚的に強調、重要な期限や注意点をハイライト'
            ],
            'required_documents' => [
                'instruction' => '必要書類一覧をHTML形式で生成してください',
                'requirements' => '具体的な書類名、取得方法、注意点をチェックリスト形式で',
                'html_format' => '<ul>でチェックリスト風、書類カテゴリーごとに整理',
                'practical_info' => '取得先や準備時間の目安も含める'
            ],
            'summary' => [
                'instruction' => '助成金の魅力的な概要をHTML形式で生成してください',
                'requirements' => '200-300文字、HTML構造化、重要ポイントを強調',
                'html_format' => '<p>と<span>を使用、キーワードを<strong>で強調',
                'content_focus' => '金額、対象者、メリット、緊急性を含める'
            ],
            'amount_details' => [
                'instruction' => '助成金額の詳細情報をHTML表形式で生成してください',
                'requirements' => 'HTML table形式、明確で理解しやすい金額体系',
                'html_format' => '<table>タグで構造化、ヘッダーと明確な項目分け',
                'content_items' => '最大金額、最小金額、補助率、対象経費を整理'
            ],
            'contact_info' => [
                'instruction' => '連絡先情報を分かりやすいHTML形式で生成してください',
                'requirements' => 'HTML構造化、電話番号、メール、住所を見やすく配置',
                'html_format' => '<div>でボックス化、各連絡手段を明確に分離',
                'practical_focus' => '営業時間や対応可能な問い合わせ内容も含める'
            ],
            'default' => [
                'instruction' => 'この助成金に関する有用な情報をHTML形式で生成してください',
                'requirements' => '正確で実用的、SEO対策済み、HTML構造化',
                'tone' => '専門的だが分かりやすい',
                'html_format' => '適切なHTML要素を使用して構造化'
            ]
        ];
    }
    
    /**
     * Get SEO instructions for specific fields
     */
    private function get_seo_instructions($field) {
        $seo_keywords = ['助成金', '補助金', '支援', '申請', '中小企業', 'スタートアップ'];
        
        switch ($field) {
            case 'post_title':
                return "SEO要件: 主要キーワードを自然に含める。検索意図に合致。32文字以内推奨。";
            case 'post_content':
                return "SEO要件: 関連キーワードを適度に配置。見出し(H2,H3)を使用。内部リンク機会を作る。ユーザーの検索意図に応える。";
            case 'post_excerpt':
                return "SEO要件: メタディスクリプションとしても機能。クリック誘導する内容。主要キーワード含む。";
            default:
                return "SEO要件: 関連キーワードを自然に含める。ユーザーに価値ある情報を提供。";
        }
    }
    
    /**
     * Build complete generation prompt with enhanced HTML/CSS support
     */
    private function build_generation_prompt($context, $field_config, $seo_instructions, $mode) {
        $prompt = "あなたは助成金・補助金の専門家兼Webデザイナーです。以下の情報を参考に、高品質で視覚的に魅力的な内容を生成してください。\n\n";
        
        if (!empty($context)) {
            $prompt .= "【参考データ】\n{$context}\n\n";
        }
        
        $prompt .= "【生成要件】\n";
        $prompt .= "目的: {$field_config['instruction']}\n";
        $prompt .= "要件: {$field_config['requirements']}\n";
        
        // HTML/CSS要件の追加
        if (isset($field_config['html_requirements'])) {
            $prompt .= "HTML要件: {$field_config['html_requirements']}\n";
        }
        
        if (isset($field_config['css_style'])) {
            $prompt .= "CSS基準: {$field_config['css_style']}\n";
        }
        
        if (isset($field_config['design_theme'])) {
            $prompt .= "デザインテーマ: {$field_config['design_theme']}\n";
        }
        
        if (isset($field_config['html_format'])) {
            $prompt .= "HTML形式: {$field_config['html_format']}\n";
        }
        
        $prompt .= "{$seo_instructions}\n\n";
        
        if (isset($field_config['structure'])) {
            $prompt .= "【コンテンツ構成】\n{$field_config['structure']}\n\n";
        }
        
        // 本文生成の場合の特別なCSS・HTMLテンプレート指示
        if (strpos($field_config['instruction'], 'HTMLとCSS') !== false) {
            $prompt .= $this->get_html_css_template_instructions();
        }
        
        $prompt .= "\n【生成モード】\n";
        switch ($mode) {
            case 'creative':
                $prompt .= "クリエイティブで魅力的な表現を重視してください。視覚的インパクトも考慮。";
                break;
            case 'professional':
                $prompt .= "専門的で正確な表現を重視してください。ビジネス文書として完成度高く。";
                break;
            case 'seo_focused':
                $prompt .= "SEO効果を最大化する内容を重視してください。検索エンジンに評価される構造で。";
                break;
            default:
                $prompt .= "バランス良く実用的な内容を生成してください。読みやすさと情報の正確性を両立。";
        }
        
        $prompt .= "\n\n【出力形式】\n";
        $prompt .= "生成内容のみを出力してください（説明文や前置きは不要）。\n";
        $prompt .= "HTMLタグを使用する場合は、正しく閉じタグまで含めて出力してください。";
        
        return $prompt;
    }
    
    /**
     * Get HTML/CSS template instructions for content generation
     */
    private function get_html_css_template_instructions() {
        return "
【HTML/CSSテンプレート指示】
1. CSSスタイル定義:
   - 基本色: #000000(黒), #333333(濃いグレー), #666666(グレー), #cccccc(薄いグレー), #f9f9f9(背景)
   - ハイライト色: #ffeb3b(黄色), #fff59d(薄い黄色) - 重要部分用蛍光ペン効果
   - フォント: sans-serif系、読みやすさ重視
   
2. 必須HTML構造:
   <div class=\"grant-content\">
     <h2 class=\"grant-section\">セクションタイトル</h2>
     <div class=\"grant-highlight\">重要情報ボックス</div>
     <table class=\"grant-table\">詳細表</table>
     <ul class=\"grant-list\">リスト項目</ul>
   </div>

3. CSS クラス定義を含めること:
   <style>
   .grant-content { /* メインコンテナ */ }
   .grant-section { /* セクション見出し */ }
   .grant-highlight { /* 重要情報ハイライト */ }
   .grant-table { /* 表組み */ }
   .grant-list { /* リスト */ }
   .highlight-yellow { /* 黄色蛍光ペン効果 */ }
   </style>

4. デザイン要素:
   - アイコンは使用せず、テキストのみで表現
   - 表組みでの情報整理
   - 重要部分への黄色ハイライト
   - 白黒ベースのスタイリッシュなレイアウト

";
    }
    
    /**
     * Regenerate existing content with improvements
     */
    private function regenerate_content($existing_data, $field, $current_content, $type) {
        $context = $this->build_context_prompt($existing_data);
        
        $prompt = "以下の内容を{$type}してください。\n\n";
        $prompt .= "【現在の内容】\n{$current_content}\n\n";
        
        if (!empty($context)) {
            $prompt .= "【参考情報】\n{$context}\n\n";
        }
        
        switch ($type) {
            case 'improve':
                $prompt .= "【改善要件】\n- より分かりやすく\n- SEO効果を向上\n- 専門性を高める\n- 文章の流れを改善";
                break;
            case 'shorten':
                $prompt .= "【短縮要件】\n- 要点を保持\n- 50%程度に短縮\n- 重要情報は残す";
                break;
            case 'expand':
                $prompt .= "【拡張要件】\n- より詳細に\n- 具体例を追加\n- 関連情報を補完";
                break;
            case 'seo_optimize':
                $prompt .= "【SEO最適化要件】\n- キーワード密度を適正化\n- 見出し構造を改善\n- 検索意図に最適化";
                break;
        }
        
        $prompt .= "\n\n改善された内容のみを出力してください:";
        
        return $this->call_openai_api($prompt);
    }
    
    /**
     * Fill multiple empty fields based on context
     */
    private function fill_empty_fields($existing_data, $empty_fields) {
        $context = $this->build_context_prompt($existing_data);
        $filled_content = [];
        
        foreach ($empty_fields as $field) {
            try {
                $field_prompts = $this->get_field_specific_prompts();
                $field_config = $field_prompts[$field] ?? $field_prompts['default'];
                
                $prompt = "以下の情報を参考に、{$field}の内容を生成してください。\n\n";
                $prompt .= "【参考情報】\n{$context}\n\n";
                $prompt .= "【要件】\n{$field_config['instruction']}\n{$field_config['requirements']}\n\n";
                $prompt .= "生成内容のみを出力してください:";
                
                $filled_content[$field] = $this->call_openai_api($prompt);
                
                // Rate limiting
                sleep(1);
                
            } catch (Exception $e) {
                $filled_content[$field] = $this->get_fallback_content($field, $existing_data);
            }
        }
        
        return $filled_content;
    }
    
    /**
     * Call OpenAI API
     */
    private function call_openai_api($prompt) {
        if (empty($this->api_key)) {
            throw new Exception('OpenAI API key not configured');
        }
        
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'あなたは助成金・補助金の専門家です。正確で実用的な情報を提供し、SEOも考慮した高品質な日本語コンテンツを生成してください。'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 2000,
            'temperature' => 0.7
        ];
        
        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($data),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($response_body['error'])) {
            throw new Exception('OpenAI API error: ' . $response_body['error']['message']);
        }
        
        if (!isset($response_body['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }
        
        return trim($response_body['choices'][0]['message']['content']);
    }
    
    /**
     * Get fallback content when AI fails
     */
    private function get_fallback_content($field, $existing_data = []) {
        $fallbacks = [
            'post_title' => $this->generate_title_fallback($existing_data),
            'post_content' => $this->generate_content_fallback($existing_data),
            'post_excerpt' => $this->generate_excerpt_fallback($existing_data),
            'eligibility_criteria' => "・中小企業、個人事業主が対象\n・法人設立から3年以内\n・従業員数50名以下\n・過去に同様の助成金を受給していないこと",
            'application_process' => "1. 申請書類の準備\n2. オンライン申請システムでの登録\n3. 必要書類のアップロード\n4. 審査結果の通知待ち\n5. 採択後の手続き",
            'required_documents' => "・申請書（指定様式）\n・会社概要書\n・事業計画書\n・見積書\n・直近の決算書\n・履歴事項全部証明書"
        ];
        
        return $fallbacks[$field] ?? "こちらの項目について詳細な情報をご確認ください。";
    }
    
    /**
     * Generate fallback fills for multiple fields
     */
    private function get_fallback_fills($fields) {
        $fills = [];
        foreach ($fields as $field) {
            $fills[$field] = $this->get_fallback_content($field);
        }
        return $fills;
    }
    
    /**
     * Generate title fallback
     */
    private function generate_title_fallback($data) {
        $org = !empty($data['organization']) ? $data['organization'] : '各自治体';
        $category = !empty($data['categories'][0]) ? $data['categories'][0] : 'ビジネス支援';
        return "{$org} {$category}助成金・補助金制度";
    }
    
    /**
     * Generate enhanced HTML content fallback with CSS styling
     */
    private function generate_content_fallback($data) {
        $title = !empty($data['title']) ? $data['title'] : '助成金制度';
        $org = !empty($data['organization']) ? $data['organization'] : '実施機関';
        $max_amount = !empty($data['max_amount']) ? $data['max_amount'] . '万円' : '規定額';
        $deadline = !empty($data['deadline']) ? $data['deadline'] : '随時受付';
        $categories = !empty($data['categories']) ? (is_array($data['categories']) ? implode('、', $data['categories']) : $data['categories']) : '事業支援';
        
        return '<style>
.grant-content { font-family: "Helvetica Neue", Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 0 auto; }
.grant-section { color: #000; border-bottom: 2px solid #000; padding-bottom: 8px; margin: 24px 0 16px 0; font-weight: bold; }
.grant-highlight { background: #f9f9f9; border-left: 4px solid #000; padding: 16px; margin: 16px 0; }
.grant-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
.grant-table th, .grant-table td { border: 1px solid #ccc; padding: 12px; text-align: left; }
.grant-table th { background: #000; color: white; font-weight: bold; }
.grant-list { margin: 16px 0; padding-left: 24px; }
.grant-list li { margin: 8px 0; }
.highlight-yellow { background: #ffeb3b; padding: 2px 4px; font-weight: bold; }
.contact-box { background: #f9f9f9; border: 1px solid #ccc; padding: 16px; margin: 16px 0; }
.step-number { background: #000; color: white; border-radius: 50%; padding: 4px 8px; margin-right: 8px; font-weight: bold; }
</style>

<div class="grant-content">
    <div class="grant-highlight">
        <h2>■ ' . esc_html($title) . '</h2>
        <p><strong>実施機関:</strong> ' . esc_html($org) . '</p>
        <p><span class="highlight-yellow">最大助成額: ' . esc_html($max_amount) . '</span></p>
    </div>

    <h2 class="grant-section">助成金概要</h2>
    <p>' . esc_html($title) . 'は、' . esc_html($org) . 'が実施する<span class="highlight-yellow">' . esc_html($categories) . '</span>を対象とした事業者支援制度です。事業の発展と成長を支援し、競争力強化を図ることを目的としています。</p>

    <h2 class="grant-section">助成金詳細</h2>
    <table class="grant-table">
        <tr>
            <th>項目</th>
            <th>内容</th>
        </tr>
        <tr>
            <td>最大助成額</td>
            <td><span class="highlight-yellow">' . esc_html($max_amount) . '</span></td>
        </tr>
        <tr>
            <td>申請期限</td>
            <td>' . esc_html($deadline) . '</td>
        </tr>
        <tr>
            <td>対象分野</td>
            <td>' . esc_html($categories) . '</td>
        </tr>
        <tr>
            <td>実施機関</td>
            <td>' . esc_html($org) . '</td>
        </tr>
    </table>

    <h2 class="grant-section">対象者・応募要件</h2>
    <ul class="grant-list">
        <li>中小企業基本法に定める中小企業・小規模事業者</li>
        <li>個人事業主（開業届を提出している方）</li>
        <li>法人設立または開業から1年以上経過している事業者</li>
        <li>過去に同様の助成金を受給していない事業者</li>
        <li><span class="highlight-yellow">事業計画書の提出が可能な事業者</span></li>
    </ul>

    <h2 class="grant-section">申請手順</h2>
    <ol class="grant-list">
        <li><span class="step-number">1</span>申請要件の確認と事前準備</li>
        <li><span class="step-number">2</span>必要書類の準備・収集</li>
        <li><span class="step-number">3</span>事業計画書の作成</li>
        <li><span class="step-number">4</span>申請書類の提出</li>
        <li><span class="step-number">5</span>審査結果の通知待ち</li>
        <li><span class="step-number">6</span>採択後の手続き・事業実施</li>
    </ol>

    <h2 class="grant-section">お問い合わせ</h2>
    <div class="contact-box">
        <p><strong>実施機関:</strong> ' . esc_html($org) . '</p>
        <p><strong>受付時間:</strong> 平日 9:00～17:00（土日祝日を除く）</p>
        <p>詳細な申請方法や最新情報については、実施機関の公式サイトをご確認いただくか、直接お問い合わせください。</p>
    </div>

    <div class="grant-highlight">
        <p><strong> 重要:</strong> 申請期限や条件は変更される場合があります。必ず最新の公式情報をご確認の上、お申し込みください。</p>
    </div>
</div>';
    }
    
    /**
     * Generate excerpt fallback
     */
    private function generate_excerpt_fallback($data) {
        $org = !empty($data['organization']) ? $data['organization'] : '実施機関';
        $amount = !empty($data['max_amount']) ? $data['max_amount'] : '規定の金額';
        
        return "{$org}による事業者向け助成金制度。最大{$amount}の支援を受けることができます。申請条件や手続き方法について詳しくご紹介します。";
    }
    
    /**
     * Simple content improvement (non-AI)
     */
    private function improve_content_simple($content, $field) {
        // Simple text improvements without AI
        $content = trim($content);
        
        // Add structure if missing
        if ($field === 'post_content' && strpos($content, '##') === false) {
            return "## 概要\n{$content}\n\n## 詳細情報\n申請や条件について、詳細は実施機関にお問い合わせください。";
        }
        
        return $content;
    }
    
    /**
     * Sanitize input data
     */
    private function sanitize_input($data) {
        if (!is_array($data)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized[$key] = sanitize_textarea_field($value);
            }
        }
        
        return $sanitized;
    }
}

/**
 * =============================================================================
 * SEARCH & HISTORY MANAGEMENT - Consolidated from search-functions.php
 * =============================================================================
 */

/**
 * 検索履歴の保存（統合版）
 */
function gi_save_search_history($query, $filters = [], $results_count = 0, $session_id = null) {
    if ($session_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'gi_search_history';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") == $table) {
            $wpdb->insert(
                $table,
                [
                    'session_id' => $session_id,
                    'user_id' => get_current_user_id() ?: null,
                    'search_query' => $query,
                    'search_filter' => is_array($filters) ? json_encode($filters) : $filters,
                    'results_count' => $results_count,
                    'created_at' => current_time('mysql')
                ],
                ['%s', '%d', '%s', '%s', '%d', '%s']
            );
        }
    }
    
    $user_id = get_current_user_id();
    if ($user_id) {
        $history = get_user_meta($user_id, 'gi_search_history', true) ?: [];
        
        array_unshift($history, [
            'query' => sanitize_text_field($query),
            'filters' => $filters,
            'results_count' => intval($results_count),
            'timestamp' => current_time('timestamp')
        ]);
        
        $history = array_slice($history, 0, 20);
        update_user_meta($user_id, 'gi_search_history', $history);
    }
    
    return true;
}

/**
 * 検索履歴の取得
 */
/**
 * OpenAI統合クラス
 */
class GI_OpenAI_Integration {
    private static $instance = null;
    private $api_key;
    private $api_endpoint = 'https://api.openai.com/v1/';
    
    private function __construct() {
        $this->api_key = get_option('gi_openai_api_key', '');
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function is_configured() {
        return !empty($this->api_key);
    }
    
    public function generate_response($prompt, $context = []) {
        if (!$this->is_configured()) {
            return $this->generate_fallback_response($prompt, $context);
        }
        
        try {
            return $this->call_gpt_api($prompt, $context);
        } catch (Exception $e) {
            error_log('OpenAI API Error: ' . $e->getMessage());
            return $this->generate_fallback_response($prompt, $context);
        }
    }
    
    private function call_gpt_api($prompt, $context = []) {
        $system_prompt = "あなたは助成金・補助金の専門アドバイザーです。";
        
        if (!empty($context['grants'])) {
            $system_prompt .= "\n\n関連する助成金情報:\n";
            foreach (array_slice($context['grants'], 0, 3) as $grant) {
                $system_prompt .= "- {$grant['title']}: {$grant['excerpt']}\n";
            }
        }
        
        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $prompt]
        ];
        
        $response = $this->make_openai_request('chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
            'max_tokens' => 500,
            'temperature' => 0.7
        ]);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }
        
        throw new Exception('Invalid API response');
    }
    
    public function test_connection() {
        if (!$this->is_configured()) {
            return ['success' => false, 'message' => 'APIキーが設定されていません'];
        }
        
        try {
            $response = $this->make_openai_request('models');
            if ($response && isset($response['data'])) {
                return ['success' => true, 'message' => 'API接続成功'];
            }
            return ['success' => false, 'message' => 'API応答が無効です'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'API接続エラー: ' . $e->getMessage()];
        }
    }
    
    private function make_openai_request($endpoint, $data = null) {
        $url = $this->api_endpoint . $endpoint;
        
        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30
        ];
        
        if ($data) {
            $args['body'] = json_encode($data);
            $args['method'] = 'POST';
            $response = wp_remote_post($url, $args);
        } else {
            $response = wp_remote_get($url, $args);
        }
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $http_code = wp_remote_retrieve_response_code($response);
        
        if ($http_code !== 200) {
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['error']['message']) 
                ? $error_data['error']['message'] 
                : 'HTTP Error: ' . $http_code;
            throw new Exception($error_message);
        }
        
        return json_decode($body, true);
    }
    
    private function generate_fallback_response($prompt, $context = []) {
        if (mb_stripos($prompt, '検索') !== false || mb_stripos($prompt, '補助金') !== false) {
            return 'ご質問ありがとうございます。補助金に関する詳細情報をお調べしております。具体的な業種や目的をお聞かせいただけると、より適切な情報をご提供できます。';
        }
        
        if (mb_stripos($prompt, '申請') !== false) {
            return '申請に関するご質問ですね。補助金の申請には通常、事業計画書、必要書類の準備、申請書の提出が必要です。具体的にどの補助金についてお知りになりたいですか？';
        }
        
        return 'ご質問ありがとうございます。より具体的な情報をお聞かせいただけると、詳しい回答をお提供できます。';
    }
}

/**
 * AI設定管理関数
 */
function gi_set_openai_api_key($api_key) {
    return update_option('gi_openai_api_key', sanitize_text_field($api_key));
}

function gi_get_openai_api_key() {
    return get_option('gi_openai_api_key', '');
}

// Initialize the enhanced AI generator and new systems
new GI_Enhanced_AI_Generator();

// Initialize enhanced AI systems
GI_Enhanced_Intent_Analyzer::getInstance();
GI_Comprehensive_Knowledge_Engine::getInstance();
GI_Dynamic_Processor::getInstance();
GI_Response_Formatter::getInstance();
GI_Streaming_Manager::getInstance();
GI_Gemini_Integration::getInstance();
GI_Multi_AI_Manager::getInstance();

// ============================================================================
// ENHANCED AI AJAX HANDLERS
// ============================================================================

/**
 * Enhanced AI Search Handler with Intent Analysis and Dynamic Processing
 */
add_action('wp_ajax_gi_enhanced_ai_search', 'gi_handle_enhanced_ai_search');
add_action('wp_ajax_nopriv_gi_enhanced_ai_search', 'gi_handle_enhanced_ai_search');

function gi_handle_enhanced_ai_search() {
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ai_search_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }
    
    $query = sanitize_text_field($_POST['query'] ?? '');
    $context = $_POST['context'] ?? [];
    $options = $_POST['options'] ?? [];
    
    if (empty($query)) {
        wp_send_json_error('検索クエリが空です');
    }
    
    try {
        // Process with enhanced AI system
        $processor = GI_Dynamic_Processor::getInstance();
        $result = $processor->process_query($query, $context);
        
        // Format response with Markdown and UI components
        $formatter = GI_Response_Formatter::getInstance();
        $formatted_response = $formatter->format_response(
            $result['processing_result'],
            $result['intent_analysis'],
            $options
        );
        
        // Save interaction to context
        $context_manager = GI_Context_Manager::getInstance();
        $context_manager->save_interaction(
            'enhanced_search',
            $query,
            $formatted_response['markdown_content'],
            [
                'intent_type' => $result['intent_analysis']['primary_intent']['type'],
                'strategy_used' => $result['response_metadata']['strategy_used'],
                'confidence' => $result['response_metadata']['confidence_score']
            ]
        );
        
        wp_send_json_success([
            'response' => $formatted_response,
            'metadata' => $result['response_metadata'],
            'intent_analysis' => $result['intent_analysis'],
            'query_id' => wp_generate_password(16, false)
        ]);
        
    } catch (Exception $e) {
        error_log('Enhanced AI Search Error: ' . $e->getMessage());
        wp_send_json_error('検索処理中にエラーが発生しました: ' . $e->getMessage());
    }
}

/**
 * Streaming AI Response Handler
 */
add_action('wp_ajax_gi_streaming_response', 'gi_handle_streaming_response');
add_action('wp_ajax_nopriv_gi_streaming_response', 'gi_handle_streaming_response');

function gi_handle_streaming_response() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ai_search_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }
    
    $query = sanitize_text_field($_POST['query'] ?? '');
    $context = $_POST['context'] ?? [];
    $session_id = sanitize_text_field($_POST['session_id'] ?? wp_generate_password(16, false));
    
    if (empty($query)) {
        wp_send_json_error('検索クエリが空です');
    }
    
    try {
        // Start streaming
        $streaming_manager = GI_Streaming_Manager::getInstance();
        $streaming_manager->start_streaming_response($session_id);
        
        // Process query
        $processor = GI_Dynamic_Processor::getInstance();
        $result = $processor->process_query($query, $context);
        
        // Format response
        $formatter = GI_Response_Formatter::getInstance();
        $formatted_response = $formatter->format_response(
            $result['processing_result'],
            $result['intent_analysis']
        );
        
        // Send streaming chunks
        $streaming_manager->send_streaming_chunks($formatted_response['streaming_chunks']);
        
        // End streaming
        $streaming_manager->end_streaming();
        
    } catch (Exception $e) {
        error_log('Streaming AI Response Error: ' . $e->getMessage());
        $streaming_manager = GI_Streaming_Manager::getInstance();
        $streaming_manager->send_chunk('error', 'エラー: ' . $e->getMessage());
        $streaming_manager->end_streaming();
    }
}

/**
 * AI Provider Test Handler
 */
add_action('wp_ajax_gi_test_ai_providers', 'gi_handle_test_ai_providers');

function gi_handle_test_ai_providers() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('権限がありません');
    }
    
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_admin_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }
    
    try {
        $ai_manager = GI_Multi_AI_Manager::getInstance();
        $test_results = $ai_manager->test_all_connections();
        
        wp_send_json_success([
            'test_results' => $test_results,
            'providers' => $ai_manager->get_available_providers(),
            'timestamp' => current_time('c')
        ]);
        
    } catch (Exception $e) {
        wp_send_json_error('AIプロバイダーテスト中にエラーが発生しました: ' . $e->getMessage());
    }
}

/**
 * Intent Analysis Handler
 */
add_action('wp_ajax_gi_analyze_intent', 'gi_handle_analyze_intent');
add_action('wp_ajax_nopriv_gi_analyze_intent', 'gi_handle_analyze_intent');

function gi_handle_analyze_intent() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ai_search_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }
    
    $query = sanitize_text_field($_POST['query'] ?? '');
    $context = $_POST['context'] ?? [];
    
    if (empty($query)) {
        wp_send_json_error('クエリが空です');
    }
    
    try {
        $intent_analyzer = GI_Enhanced_Intent_Analyzer::getInstance();
        $intent_data = $intent_analyzer->analyze_intent($query, $context);
        
        wp_send_json_success([
            'intent_data' => $intent_data,
            'query' => $query,
            'timestamp' => current_time('c')
        ]);
        
    } catch (Exception $e) {
        wp_send_json_error('意図分析中にエラーが発生しました: ' . $e->getMessage());
    }
}

/**
 * RAG Document Upload Handler
 */
add_action('wp_ajax_gi_upload_rag_document', 'gi_handle_upload_rag_document');

function gi_handle_upload_rag_document() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('権限がありません');
    }
    
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_admin_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }
    
    if (!isset($_FILES['rag_document'])) {
        wp_send_json_error('ファイルがアップロードされていません');
    }
    
    try {
        // Handle file upload
        $uploaded_file = $_FILES['rag_document'];
        $attachment_id = media_handle_upload('rag_document', 0);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error('ファイルアップロードに失敗しました: ' . $attachment_id->get_error_message());
        }
        
        // Add to RAG system
        $rag_engine = GI_RAG_Engine::getInstance();
        $metadata = [
            'keywords' => sanitize_text_field($_POST['keywords'] ?? ''),
            'category' => sanitize_text_field($_POST['category'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? '')
        ];
        
        $rag_engine->add_document($attachment_id, $metadata);
        
        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'file_url' => wp_get_attachment_url($attachment_id),
            'metadata' => $metadata,
            'message' => 'ドキュメントがRAGシステムに追加されました'
        ]);
        
    } catch (Exception $e) {
        wp_send_json_error('RAGドキュメントアップロード中にエラーが発生しました: ' . $e->getMessage());
    }
}

/**
 * Knowledge Source Status Handler
 */
add_action('wp_ajax_gi_knowledge_source_status', 'gi_handle_knowledge_source_status');

function gi_handle_knowledge_source_status() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('権限がありません');
    }
    
    try {
        $knowledge_engine = GI_Comprehensive_Knowledge_Engine::getInstance();
        $rag_engine = GI_RAG_Engine::getInstance();
        
        // Get status information
        $status = [
            'database_sources' => [
                'grants_count' => wp_count_posts('grant')->publish ?? 0,
                'faqs_count' => wp_count_posts('faq')->publish ?? 0,
                'procedures_count' => wp_count_posts('procedure')->publish ?? 0
            ],
            'rag_documents' => count(get_posts([
                'post_type' => 'attachment',
                'meta_key' => '_rag_document',
                'meta_value' => '1',
                'numberposts' => -1
            ])),
            'semantic_search_enabled' => class_exists('GI_Semantic_Search'),
            'ai_providers' => GI_Multi_AI_Manager::getInstance()->get_available_providers()
        ];
        
        wp_send_json_success($status);
        
    } catch (Exception $e) {
        wp_send_json_error('ステータス取得中にエラーが発生しました: ' . $e->getMessage());
    }
}

/**
 * UI Component Schema Handler
 */
add_action('wp_ajax_gi_get_ui_schemas', 'gi_handle_get_ui_schemas');
add_action('wp_ajax_nopriv_gi_get_ui_schemas', 'gi_handle_get_ui_schemas');

function gi_handle_get_ui_schemas() {
    $formatter = GI_Response_Formatter::getInstance();
    
    $component_types = [
        'grants_list',
        'category_tags',
        'discovery_wizard',
        'comparison_table',
        'analysis_dashboard',
        'recommendation_cards',
        'ai_disclaimer'
    ];
    
    $schemas = [];
    foreach ($component_types as $type) {
        $schemas[$type] = $formatter->get_ui_component_schema($type);
    }
    
    wp_send_json_success([
        'schemas' => $schemas,
        'version' => '1.0.0',
        'timestamp' => current_time('c')
    ]);
}

/**
 * Advanced Grant Comparison Handler
 */
add_action('wp_ajax_gi_enhanced_grant_comparison', 'gi_handle_enhanced_grant_comparison');
add_action('wp_ajax_nopriv_gi_enhanced_grant_comparison', 'gi_handle_enhanced_grant_comparison');

function gi_handle_enhanced_grant_comparison() {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ai_search_nonce')) {
        wp_send_json_error('セキュリティチェックに失敗しました');
    }
    
    $grant_ids = array_map('intval', $_POST['grant_ids'] ?? []);
    $comparison_criteria = $_POST['criteria'] ?? ['amount', 'deadline', 'difficulty', 'match_score'];
    
    if (count($grant_ids) < 2) {
        wp_send_json_error('比較には2つ以上の助成金が必要です');
    }
    
    try {
        $comparison_data = [];
        
        foreach ($grant_ids as $grant_id) {
            $post = get_post($grant_id);
            if (!$post || $post->post_type !== 'grant') {
                continue;
            }
            
            $grant_data = [
                'id' => $grant_id,
                'title' => $post->post_title,
                'url' => get_permalink($grant_id),
                'organization' => get_field('organization', $grant_id),
                'amount' => get_field('max_amount', $grant_id),
                'deadline' => get_field('deadline', $grant_id),
                'difficulty' => gi_calculate_difficulty_score($grant_id),
                'match_score' => gi_calculate_match_score($grant_id),
                'urgency' => gi_get_deadline_urgency($grant_id),
                'categories' => wp_get_post_terms($grant_id, 'grant_category', ['fields' => 'names'])
            ];
            
            $comparison_data[] = $grant_data;
        }
        
        // Generate AI-powered comparison analysis if available
        $ai_analysis = null;
        try {
            $ai_manager = GI_Multi_AI_Manager::getInstance();
            $comparison_prompt = "以下の助成金を比較し、それぞれの特徴と適用シーンを分析してください。\n\n";
            
            foreach ($comparison_data as $grant) {
                $comparison_prompt .= "- {$grant['title']}: 金額{$grant['amount']}万円、締切{$grant['deadline']}\n";
            }
            
            $ai_analysis = $ai_manager->generate_response($comparison_prompt);
        } catch (Exception $e) {
            // AI analysis is optional
            error_log('AI comparison analysis failed: ' . $e->getMessage());
        }
        
        // Format response
        $formatter = GI_Response_Formatter::getInstance();
        $formatted_response = $formatter->format_comparison([
            'type' => 'detailed_comparison',
            'results' => array_map(function($grant) {
                return [
                    'type' => 'grant',
                    'id' => $grant['id'],
                    'title' => $grant['title'],
                    'relevance_score' => $grant['match_score'] / 100,
                    'metadata' => $grant
                ];
            }, $comparison_data)
        ]);
        
        wp_send_json_success([
            'comparison_data' => $comparison_data,
            'ai_analysis' => $ai_analysis,
            'formatted_response' => $formatted_response,
            'criteria_used' => $comparison_criteria,
            'recommendation' => $this->get_comparison_recommendation($comparison_data)
        ]);
        
    } catch (Exception $e) {
        wp_send_json_error('比較分析中にエラーが発生しました: ' . $e->getMessage());
    }
}

/**
 * Get comparison recommendation
 */
function get_comparison_recommendation($comparison_data) {
    if (empty($comparison_data)) {
        return null;
    }
    
    // Sort by match score (highest first)
    usort($comparison_data, function($a, $b) {
        return $b['match_score'] <=> $a['match_score'];
    });
    
    $top_grant = $comparison_data[0];
    
    return [
        'recommended_grant' => $top_grant,
        'reason' => "適合度スコアが最も高いため（{$top_grant['match_score']}点）",
        'next_steps' => [
            '詳細な申請条件を確認',
            '必要書類を準備',
            '申請スケジュールを作成'
        ]
    ];
}

/**
 * =====================================================
 * ENHANCED AI FEATURES (v2.0) - Legacy Support
 * =====================================================
 * 
 * New capabilities:
 * 1. Semantic Search with Vector Embeddings
 * 2. Context Memory & Personalization
 * 3. Smart Recommendations
 * 4. Advanced Caching
 * 5. Multi-turn Conversation
 */

/**
 * GI_Semantic_Search: Advanced semantic search using OpenAI Embeddings
 */
class GI_Semantic_Search {
    private static $instance = null;
    private $openai;
    private $embedding_model = 'text-embedding-3-small';
    private $cache_duration = DAY_IN_SECONDS;
    
    private function __construct() {
        $this->openai = GI_OpenAI_Integration::getInstance();
        $this->create_tables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create embedding cache tables
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}gi_embeddings_cache (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            content_hash varchar(64) NOT NULL,
            embedding_vector longtext NOT NULL,
            model_version varchar(50) NOT NULL DEFAULT 'text-embedding-3-small',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            expires_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY post_content_hash (post_id, content_hash),
            KEY expires_at (expires_at),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get or generate embedding for a post
     */
    public function get_post_embedding($post_id) {
        global $wpdb;
        
        $post = get_post($post_id);
        if (!$post) return false;
        
        // Generate content for embedding
        $content = $this->prepare_content_for_embedding($post);
        $content_hash = md5($content);
        
        // Check cache
        $table = $wpdb->prefix . 'gi_embeddings_cache';
        $cached = $wpdb->get_row($wpdb->prepare(
            "SELECT embedding_vector FROM $table 
            WHERE post_id = %d AND content_hash = %s AND expires_at > NOW()",
            $post_id, $content_hash
        ));
        
        if ($cached) {
            return json_decode($cached->embedding_vector, true);
        }
        
        // Generate new embedding
        if (!$this->openai->is_configured()) {
            return false;
        }
        
        try {
            $embedding = $this->generate_embedding($content);
            if ($embedding) {
                // Cache the embedding
                $wpdb->replace($table, [
                    'post_id' => $post_id,
                    'content_hash' => $content_hash,
                    'embedding_vector' => json_encode($embedding),
                    'model_version' => $this->embedding_model,
                    'expires_at' => date('Y-m-d H:i:s', time() + $this->cache_duration)
                ]);
                return $embedding;
            }
        } catch (Exception $e) {
            error_log('Embedding generation failed: ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Prepare post content for embedding
     */
    private function prepare_content_for_embedding($post) {
        $parts = [];
        
        // Title (重要度高)
        $parts[] = $post->post_title . '. ' . $post->post_title;
        
        // ACF fields
        $acf_fields = ['organization', 'grant_target', 'deadline', 'max_amount'];
        foreach ($acf_fields as $field) {
            $value = get_field($field, $post->ID);
            if ($value) {
                $parts[] = $value;
            }
        }
        
        // Categories and tags
        $categories = wp_get_post_terms($post->ID, 'grant_category', ['fields' => 'names']);
        if (!empty($categories) && !is_wp_error($categories)) {
            $parts[] = implode(' ', $categories);
        }
        
        // Content (first 500 chars)
        $parts[] = wp_trim_words($post->post_content, 100, '');
        
        return implode(' ', $parts);
    }
    
    /**
     * Generate embedding using OpenAI API
     */
    private function generate_embedding($text) {
        $response = gi_make_embedding_request($text, $this->embedding_model);
        if ($response && isset($response['data'][0]['embedding'])) {
            return $response['data'][0]['embedding'];
        }
        return false;
    }
    
    /**
     * Semantic search for grants
     */
    public function semantic_search($query, $limit = 10) {
        if (!$this->openai->is_configured()) {
            return [];
        }
        
        // Get query embedding
        $query_embedding = $this->generate_embedding($query);
        if (!$query_embedding) {
            return [];
        }
        
        // Get all grant posts with embeddings
        $posts = get_posts([
            'post_type' => 'grant',
            'post_status' => 'publish',
            'numberposts' => -1
        ]);
        
        $results = [];
        foreach ($posts as $post) {
            $post_embedding = $this->get_post_embedding($post->ID);
            if ($post_embedding) {
                $similarity = $this->cosine_similarity($query_embedding, $post_embedding);
                $results[] = [
                    'post_id' => $post->ID,
                    'similarity' => $similarity,
                    'post' => $post
                ];
            }
        }
        
        // Sort by similarity
        usort($results, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        return array_slice($results, 0, $limit);
    }
    
    /**
     * Calculate cosine similarity between two vectors
     */
    private function cosine_similarity($vec1, $vec2) {
        if (count($vec1) !== count($vec2)) {
            return 0;
        }
        
        $dot_product = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;
        
        for ($i = 0; $i < count($vec1); $i++) {
            $dot_product += $vec1[$i] * $vec2[$i];
            $magnitude1 += $vec1[$i] * $vec1[$i];
            $magnitude2 += $vec2[$i] * $vec2[$i];
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }
        
        return $dot_product / ($magnitude1 * $magnitude2);
    }
    
    /**
     * Cleanup expired cache entries
     */
    public function cleanup_expired_cache() {
        global $wpdb;
        $table = $wpdb->prefix . 'gi_embeddings_cache';
        $wpdb->query("DELETE FROM $table WHERE expires_at < NOW()");
    }
}

/**
 * GI_Context_Manager: User context and conversation memory
 */
class GI_Context_Manager {
    private static $instance = null;
    private $max_history = 10;
    
    private function __construct() {
        $this->create_tables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Create context tables
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}gi_user_context (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NULL,
            session_id varchar(64) NOT NULL,
            interaction_type varchar(20) NOT NULL,
            query text NOT NULL,
            response longtext NULL,
            metadata longtext NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_session (user_id, session_id),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Save interaction to context
     */
    public function save_interaction($type, $query, $response = '', $metadata = []) {
        global $wpdb;
        
        $user_id = get_current_user_id() ?: null;
        $session_id = $this->get_session_id();
        
        $wpdb->insert(
            $wpdb->prefix . 'gi_user_context',
            [
                'user_id' => $user_id,
                'session_id' => $session_id,
                'interaction_type' => $type,
                'query' => $query,
                'response' => $response,
                'metadata' => json_encode($metadata),
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        // Also save to user meta for logged-in users
        if ($user_id) {
            $history = get_user_meta($user_id, 'gi_interaction_history', true) ?: [];
            array_unshift($history, [
                'type' => $type,
                'query' => $query,
                'response' => substr($response, 0, 200),
                'timestamp' => time()
            ]);
            $history = array_slice($history, 0, $this->max_history);
            update_user_meta($user_id, 'gi_interaction_history', $history);
        }
    }
    
    /**
     * Get user context history
     */
    public function get_context_history($limit = 5) {
        global $wpdb;
        
        $session_id = $this->get_session_id();
        $user_id = get_current_user_id();
        
        $where = $user_id 
            ? $wpdb->prepare("user_id = %d", $user_id)
            : $wpdb->prepare("session_id = %s", $session_id);
        
        $results = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}gi_user_context 
            WHERE $where 
            ORDER BY created_at DESC 
            LIMIT %d
        ", $limit);
        
        return $results ?: [];
    }
    
    /**
     * Build context for AI prompt
     */
    public function build_context_prompt($current_query) {
        $history = $this->get_context_history(3);
        
        if (empty($history)) {
            return $current_query;
        }
        
        $context = "Previous conversation:\n";
        foreach (array_reverse($history) as $item) {
            $context .= "User: {$item->query}\n";
            if ($item->response) {
                $context .= "Assistant: " . wp_trim_words($item->response, 30) . "\n";
            }
        }
        $context .= "\nCurrent question: {$current_query}";
        
        return $context;
    }
    
    /**
     * Get or create session ID
     */
    private function get_session_id() {
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['gi_session_id'])) {
            $_SESSION['gi_session_id'] = wp_generate_password(32, false);
        }
        
        return $_SESSION['gi_session_id'];
    }
    
    /**
     * Cleanup old context data (older than 30 days)
     */
    public function cleanup_old_context() {
        global $wpdb;
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}gi_user_context 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
    }
}

/**
 * Enhanced OpenAI Integration with new methods
 */
// Add new methods to existing class
if (class_exists('GI_OpenAI_Integration')) {
    // Extend the class with new embedding method
    add_filter('gi_openai_make_request', function($response, $endpoint, $data) {
        if ($endpoint === 'embeddings') {
            $openai = GI_OpenAI_Integration::getInstance();
            return $openai->make_embedding_request($data['input'], $data['model']);
        }
        return $response;
    }, 10, 3);
}

/**
 * Enhanced AJAX handlers
 */

// Enhanced semantic search handler
add_action('wp_ajax_gi_semantic_search', 'gi_handle_semantic_search');
add_action('wp_ajax_nopriv_gi_semantic_search', 'gi_handle_semantic_search');

function gi_handle_semantic_search() {
    $query = sanitize_text_field($_POST['query'] ?? '');
    
    if (empty($query)) {
        wp_send_json_error('検索クエリが空です');
    }
    
    $semantic_search = GI_Semantic_Search::getInstance();
    $context_manager = GI_Context_Manager::getInstance();
    
    // Save search query
    $context_manager->save_interaction('search', $query);
    
    // Try semantic search first
    $results = $semantic_search->semantic_search($query, 10);
    
    // Fallback to regular search if needed
    if (empty($results)) {
        $results = gi_fallback_search($query);
    }
    
    // Prepare response
    $formatted_results = [];
    foreach ($results as $result) {
        $post = isset($result['post']) ? $result['post'] : get_post($result['post_id']);
        $formatted_results[] = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'excerpt' => wp_trim_words($post->post_content, 30),
            'url' => get_permalink($post->ID),
            'similarity' => isset($result['similarity']) ? round($result['similarity'], 3) : null
        ];
    }
    
    wp_send_json_success([
        'results' => $formatted_results,
        'count' => count($formatted_results),
        'method' => empty($results) ? 'keyword' : 'semantic'
    ]);
}

// Enhanced chat with context
add_action('wp_ajax_gi_contextual_chat', 'gi_handle_contextual_chat');
add_action('wp_ajax_nopriv_gi_contextual_chat', 'gi_handle_contextual_chat');

function gi_handle_contextual_chat() {
    $query = sanitize_text_field($_POST['message'] ?? '');
    
    if (empty($query)) {
        wp_send_json_error('メッセージが空です');
    }
    
    $openai = GI_OpenAI_Integration::getInstance();
    $context_manager = GI_Context_Manager::getInstance();
    
    // Build context-aware prompt
    $contextual_prompt = $context_manager->build_context_prompt($query);
    
    // Get related grants for context
    $semantic_search = GI_Semantic_Search::getInstance();
    $related_grants = $semantic_search->semantic_search($query, 3);
    
    $context = [
        'grants' => array_map(function($item) {
            $post = $item['post'];
            return [
                'title' => $post->post_title,
                'excerpt' => wp_trim_words($post->post_content, 50)
            ];
        }, $related_grants)
    ];
    
    // Generate response
    $response = $openai->generate_response($contextual_prompt, $context);
    
    // Save interaction
    $context_manager->save_interaction('chat', $query, $response);
    
    wp_send_json_success([
        'response' => $response,
        'related_grants' => array_slice($related_grants, 0, 3),
        'has_context' => !empty($context['grants'])
    ]);
}

/**
 * Fallback search function
 */
function gi_fallback_search($query) {
    $args = [
        'post_type' => 'grant',
        'post_status' => 'publish',
        'posts_per_page' => 10,
        's' => $query
    ];
    
    $search_query = new WP_Query($args);
    $results = [];
    
    if ($search_query->have_posts()) {
        while ($search_query->have_posts()) {
            $search_query->the_post();
            $results[] = [
                'post_id' => get_the_ID(),
                'post' => get_post(get_the_ID())
            ];
        }
        wp_reset_postdata();
    }
    
    return $results;
}

/**
 * Scheduled cleanup tasks
 */
add_action('gi_daily_cleanup', function() {
    $semantic_search = GI_Semantic_Search::getInstance();
    $semantic_search->cleanup_expired_cache();
    
    $context_manager = GI_Context_Manager::getInstance();
    $context_manager->cleanup_old_context();
});

if (!wp_next_scheduled('gi_daily_cleanup')) {
    wp_schedule_event(time(), 'daily', 'gi_daily_cleanup');
}

/**
 * Add embedding generation method to OpenAI class
 */
add_filter('gi_openai_custom_method', function($result, $method, $args) {
    if ($method === 'make_embedding_request') {
        $openai = GI_OpenAI_Integration::getInstance();
        if (!$openai->is_configured()) {
            return false;
        }
        
        list($text, $model) = $args;
        
        try {
            $response = wp_remote_post('https://api.openai.com/v1/embeddings', [
                'headers' => [
                    'Authorization' => 'Bearer ' . get_option('gi_openai_api_key', ''),
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'input' => $text,
                    'model' => $model
                ]),
                'timeout' => 30
            ]);
            
            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }
            
            $body = json_decode(wp_remote_retrieve_body($response), true);
            return $body;
            
        } catch (Exception $e) {
            error_log('Embedding API error: ' . $e->getMessage());
            return false;
        }
    }
    
    return $result;
}, 10, 3);

/**
 * Helper function to call embedding API
 */
function gi_make_embedding_request($text, $model = 'text-embedding-3-small') {
    return apply_filters('gi_openai_custom_method', false, 'make_embedding_request', [$text, $model]);
}

/**
 * =====================================================
 * SMART QUERY SUGGESTIONS & ALTERNATIVE SEARCH
 * =====================================================
 */

/**
 * GI_Smart_Query_Assistant: Intelligent query suggestions and alternatives
 */
class GI_Smart_Query_Assistant {
    private static $instance = null;
    private $openai;
    
    private function __construct() {
        $this->openai = GI_OpenAI_Integration::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Generate smart suggestions when no results found
     */
    public function generate_no_results_suggestions($query, $filters = []) {
        $suggestions = [
            'alternative_queries' => $this->generate_alternative_queries($query),
            'related_categories' => $this->suggest_related_categories($query),
            'search_tips' => $this->get_search_tips($query),
            'popular_grants' => $this->get_popular_grants(),
            'example_queries' => $this->get_example_queries($query)
        ];
        
        return $suggestions;
    }
    
    /**
     * Generate alternative search queries
     */
    private function generate_alternative_queries($query) {
        $alternatives = [];
        
        // パターンベースの提案
        $patterns = [
            'DX' => ['デジタル化', 'IT導入', 'システム化', 'デジタルトランスフォーメーション'],
            'スタートアップ' => ['創業', '起業', 'ベンチャー', '新規事業'],
            '製造業' => ['ものづくり', '工場', '生産', '加工'],
            '中小企業' => ['小規模事業者', 'SME', '中堅企業'],
            '補助金' => ['助成金', '支援金', '給付金', '奨励金'],
            '東京' => ['首都圏', '関東', '都内'],
            '研究開発' => ['R&D', '技術開発', 'イノベーション', '新技術']
        ];
        
        foreach ($patterns as $keyword => $synonyms) {
            if (mb_stripos($query, $keyword) !== false) {
                foreach ($synonyms as $synonym) {
                    $alt_query = str_replace($keyword, $synonym, $query);
                    if ($alt_query !== $query) {
                        $alternatives[] = [
                            'query' => $alt_query,
                            'reason' => "「{$keyword}」を「{$synonym}」に言い換えました"
                        ];
                    }
                }
            }
        }
        
        // AI生成の提案（OpenAI利用可能時）
        if ($this->openai->is_configured() && count($alternatives) < 3) {
            $ai_suggestions = $this->generate_ai_alternative_queries($query);
            $alternatives = array_merge($alternatives, $ai_suggestions);
        }
        
        return array_slice($alternatives, 0, 5);
    }
    
    /**
     * AI-powered alternative query generation
     */
    private function generate_ai_alternative_queries($query) {
        if (!$this->openai->is_configured()) {
            return [];
        }
        
        try {
            $prompt = "以下の助成金検索クエリで結果が見つかりませんでした。より良い検索結果が得られる可能性のある、別の言い回しや関連キーワードを3つ提案してください。

元のクエリ: {$query}

各提案は以下の形式で:
1. [代替クエリ]
理由: [なぜこの提案が有効か]

JSON形式で回答してください:
{\"suggestions\": [{\"query\": \"...\", \"reason\": \"...\"}]}";

            $response = $this->openai->generate_response($prompt, []);
            
            // JSONパース
            if (preg_match('/\{.*\}/s', $response, $matches)) {
                $data = json_decode($matches[0], true);
                if (isset($data['suggestions']) && is_array($data['suggestions'])) {
                    return $data['suggestions'];
                }
            }
        } catch (Exception $e) {
            error_log('AI alternative query generation failed: ' . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * Suggest related categories
     */
    private function suggest_related_categories($query) {
        $category_mapping = [
            'IT' => ['grant_category' => ['IT関連', 'デジタル化', 'システム開発']],
            'DX' => ['grant_category' => ['IT関連', 'デジタル化', 'イノベーション']],
            '製造' => ['grant_category' => ['ものづくり', '製造業', '技術開発']],
            'スタートアップ' => ['grant_category' => ['創業支援', 'ベンチャー', '起業']],
            '環境' => ['grant_category' => ['環境・エネルギー', 'サステナビリティ', 'SDGs']],
            '農業' => ['grant_category' => ['農林水産', '6次産業化']],
            '観光' => ['grant_category' => ['観光', '地域活性化']],
            '研究' => ['grant_category' => ['研究開発', 'R&D', 'イノベーション']]
        ];
        
        $suggestions = [];
        
        foreach ($category_mapping as $keyword => $cats) {
            if (mb_stripos($query, $keyword) !== false) {
                foreach ($cats['grant_category'] as $cat) {
                    $term = get_term_by('name', $cat, 'grant_category');
                    if ($term) {
                        $suggestions[] = [
                            'category' => $cat,
                            'term_id' => $term->term_id,
                            'count' => $term->count,
                            'link' => get_term_link($term)
                        ];
                    }
                }
            }
        }
        
        // カテゴリが見つからない場合は人気カテゴリを提案
        if (empty($suggestions)) {
            $popular_cats = get_terms([
                'taxonomy' => 'grant_category',
                'orderby' => 'count',
                'order' => 'DESC',
                'number' => 5,
                'hide_empty' => true
            ]);
            
            foreach ($popular_cats as $term) {
                $suggestions[] = [
                    'category' => $term->name,
                    'term_id' => $term->term_id,
                    'count' => $term->count,
                    'link' => get_term_link($term)
                ];
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Get search tips based on query
     */
    private function get_search_tips($query) {
        $tips = [];
        
        // クエリ分析
        $is_too_short = mb_strlen($query) < 3;
        $is_too_long = mb_strlen($query) > 50;
        $has_specific_location = preg_match('/(東京|大阪|愛知|福岡|北海道|神奈川|埼玉|千葉)/u', $query);
        $has_industry = preg_match('/(製造|IT|農業|観光|飲食|建設|医療|介護)/u', $query);
        $has_purpose = preg_match('/(創業|設備|開発|雇用|販路|輸出)/u', $query);
        
        if ($is_too_short) {
            $tips[] = [
                'type' => 'length',
                'icon' => '',
                'title' => 'より詳しいキーワードを追加してみましょう',
                'description' => '「業種」「目的」「地域」を組み合わせると、より的確な結果が見つかります',
                'example' => '例: 「IT 東京 スタートアップ」'
            ];
        }
        
        if (!$has_industry) {
            $tips[] = [
                'type' => 'industry',
                'icon' => '🏭',
                'title' => '業種を追加してみましょう',
                'description' => '対象業種を指定すると、より適切な助成金が見つかります',
                'example' => '例: 「製造業」「IT業」「飲食業」など'
            ];
        }
        
        if (!$has_specific_location) {
            $tips[] = [
                'type' => 'location',
                'icon' => '📍',
                'title' => '地域を指定してみましょう',
                'description' => '都道府県や市区町村を指定すると、地域限定の助成金も見つかります',
                'example' => '例: 「東京都」「大阪市」など'
            ];
        }
        
        if (!$has_purpose) {
            $tips[] = [
                'type' => 'purpose',
                'icon' => '',
                'title' => '目的を明確にしてみましょう',
                'description' => '何に使いたいかを指定すると、マッチする助成金が見つかりやすくなります',
                'example' => '例: 「設備投資」「人材採用」「販路拡大」など'
            ];
        }
        
        // 一般的なヒント
        $tips[] = [
            'type' => 'general',
            'icon' => '',
            'title' => 'カテゴリから探す',
            'description' => 'カテゴリ一覧から興味のある分野を選んでみましょう',
            'action' => 'show_categories'
        ];
        
        return array_slice($tips, 0, 3);
    }
    
    /**
     * Get popular grants as fallback
     */
    private function get_popular_grants($limit = 5) {
        // 閲覧数が多い助成金を取得
        $args = [
            'post_type' => 'grant',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_key' => 'view_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ];
        
        $query = new WP_Query($args);
        $grants = [];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $grants[] = [
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'excerpt' => wp_trim_words(get_the_excerpt(), 30),
                    'url' => get_permalink(),
                    'view_count' => get_post_meta(get_the_ID(), 'view_count', true) ?: 0
                ];
            }
            wp_reset_postdata();
        }
        
        // 閲覧数がない場合は最新の助成金
        if (empty($grants)) {
            $args = [
                'post_type' => 'grant',
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'orderby' => 'date',
                'order' => 'DESC'
            ];
            
            $query = new WP_Query($args);
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $grants[] = [
                        'id' => get_the_ID(),
                        'title' => get_the_title(),
                        'excerpt' => wp_trim_words(get_the_excerpt(), 30),
                        'url' => get_permalink()
                    ];
                }
                wp_reset_postdata();
            }
        }
        
        return $grants;
    }
    
    /**
     * Get example queries
     */
    private function get_example_queries($original_query) {
        $examples = [
            [
                'query' => '東京都 IT スタートアップ 創業',
                'description' => '地域・業種・目的を組み合わせた検索'
            ],
            [
                'query' => '製造業 設備投資 補助金',
                'description' => '業種と目的で絞り込んだ検索'
            ],
            [
                'query' => '中小企業 DX デジタル化支援',
                'description' => '対象者とキーワードを明確にした検索'
            ],
            [
                'query' => '研究開発 R&D イノベーション',
                'description' => '関連キーワードを複数使用した検索'
            ],
            [
                'query' => '飲食業 販路拡大 コロナ対策',
                'description' => '時事的なキーワードを含めた検索'
            ]
        ];
        
        // ランダムに3つ選択
        shuffle($examples);
        return array_slice($examples, 0, 3);
    }
    
    /**
     * Generate context-aware suggestions
     */
    public function generate_contextual_suggestions($user_id = null) {
        $context_manager = GI_Context_Manager::getInstance();
        $history = $context_manager->get_context_history(5);
        
        $suggestions = [];
        
        // 履歴に基づいた提案
        if (!empty($history)) {
            $recent_queries = array_map(function($item) {
                return $item->query;
            }, $history);
            
            $suggestions['based_on_history'] = [
                'title' => '最近の検索に基づく提案',
                'queries' => $this->generate_follow_up_queries($recent_queries)
            ];
        }
        
        // 時期に基づいた提案
        $seasonal_suggestions = $this->get_seasonal_suggestions();
        if (!empty($seasonal_suggestions)) {
            $suggestions['seasonal'] = $seasonal_suggestions;
        }
        
        return $suggestions;
    }
    
    /**
     * Generate follow-up queries
     */
    private function generate_follow_up_queries($recent_queries) {
        $follow_ups = [];
        
        foreach ($recent_queries as $query) {
            // より詳細な検索を提案
            if (mb_strlen($query) < 20) {
                $follow_ups[] = [
                    'query' => $query . ' 詳細',
                    'type' => 'detail',
                    'label' => '詳しく検索'
                ];
                
                $follow_ups[] = [
                    'query' => $query . ' 申請方法',
                    'type' => 'how_to',
                    'label' => '申請方法を調べる'
                ];
            }
            
            // 類似検索を提案
            $follow_ups[] = [
                'query' => $query . ' 類似',
                'type' => 'similar',
                'label' => '類似の助成金を探す'
            ];
        }
        
        return array_slice($follow_ups, 0, 5);
    }
    
    /**
     * Get seasonal suggestions
     */
    private function get_seasonal_suggestions() {
        $month = date('n');
        $suggestions = [];
        
        $seasonal_keywords = [
            1 => ['新年', '創業', '起業', '新規事業'],
            2 => ['確定申告', '決算', '補助金申請'],
            3 => ['新年度', '採用', '教育訓練'],
            4 => ['新入社員', '人材育成', '研修'],
            5 => ['中間決算', '設備投資'],
            6 => ['省エネ', '環境対策', 'SDGs'],
            7 => ['夏季休暇', 'インターン', '採用'],
            8 => ['事業計画', '下半期', '戦略'],
            9 => ['決算準備', '税制', '補助金'],
            10 => ['年末調整', '資金調達'],
            11 => ['年末決算', '来期計画'],
            12 => ['年末商戦', '確定申告準備']
        ];
        
        if (isset($seasonal_keywords[$month])) {
            $suggestions = [
                'title' => '今月のおすすめキーワード',
                'keywords' => $seasonal_keywords[$month],
                'month' => $month
            ];
        }
        
        return $suggestions;
    }
}

/**
 * AJAX Handler: Smart suggestions when no results
 */
add_action('wp_ajax_gi_no_results_suggestions', 'gi_handle_no_results_suggestions');
add_action('wp_ajax_nopriv_gi_no_results_suggestions', 'gi_handle_no_results_suggestions');

function gi_handle_no_results_suggestions() {
    $query = sanitize_text_field($_POST['query'] ?? '');
    $filters = $_POST['filters'] ?? [];
    
    if (empty($query)) {
        wp_send_json_error('検索クエリが必要です');
    }
    
    $assistant = GI_Smart_Query_Assistant::getInstance();
    $suggestions = $assistant->generate_no_results_suggestions($query, $filters);
    
    wp_send_json_success($suggestions);
}

/**
 * AJAX Handler: Contextual suggestions
 */
add_action('wp_ajax_gi_contextual_suggestions', 'gi_handle_contextual_suggestions');
add_action('wp_ajax_nopriv_gi_contextual_suggestions', 'gi_handle_contextual_suggestions');

function gi_handle_contextual_suggestions() {
    $user_id = get_current_user_id() ?: null;
    
    $assistant = GI_Smart_Query_Assistant::getInstance();
    $suggestions = $assistant->generate_contextual_suggestions($user_id);
    
    wp_send_json_success($suggestions);
}

// ============================================================================
// 新AI機能群（モノクロームデザイン対応）
// ============================================================================

/**
 * 提案1: AI適合度スコア計算
 * ユーザーコンテキストと助成金情報から適合度を算出（0-100%）
 */
function gi_calculate_match_score($post_id, $user_context = null) {
    if (!$user_context) {
        $user_context = gi_get_user_context();
    }
    
    // ユーザーコンテキストがなくても、基本情報から適合度を計算
    $score = 70; // ベーススコアを表示閾値以上に
    
    // 業種マッチング
    $grant_categories = wp_get_post_terms($post_id, 'grant_category', ['fields' => 'names']);
    if (!empty($grant_categories) && !empty($user_context['industry'])) {
        foreach ($grant_categories as $cat) {
            if (stripos($cat, $user_context['industry']) !== false) {
                $score += 20;
                break;
            }
        }
    }
    
    // 地域マッチング
    $grant_prefecture = wp_get_post_terms($post_id, 'grant_prefecture', ['fields' => 'names']);
    if (!empty($grant_prefecture) && !empty($user_context['prefecture'])) {
        if (in_array($user_context['prefecture'], $grant_prefecture)) {
            $score += 15;
        }
    }
    
    // 金額範囲マッチング
    $max_amount = get_field('max_amount_numeric', $post_id);
    if ($max_amount && !empty($user_context['budget_range'])) {
        $budget = $user_context['budget_range'];
        if ($max_amount >= $budget['min'] && $max_amount <= $budget['max']) {
            $score += 15;
        }
    } elseif ($max_amount > 10000000) {
        // 高額助成金は適合度アップ
        $score += 10;
    }
    
    return min(100, max(0, $score));
}

/**
 * ユーザーコンテキスト取得（検索履歴・プロフィールから）
 */
function gi_get_user_context() {
    $context = [
        'industry' => '',
        'prefecture' => '',
        'budget_range' => ['min' => 0, 'max' => PHP_INT_MAX],
        'search_history' => []
    ];
    
    // Cookie/SessionからContextを取得
    if (isset($_COOKIE['gi_user_industry'])) {
        $context['industry'] = sanitize_text_field($_COOKIE['gi_user_industry']);
    }
    if (isset($_COOKIE['gi_user_prefecture'])) {
        $context['prefecture'] = sanitize_text_field($_COOKIE['gi_user_prefecture']);
    }
    
    // 検索履歴から推測
    $search_history = get_transient('gi_user_search_' . session_id());
    if ($search_history) {
        $context['search_history'] = $search_history;
    }
    
    return $context;
}

/**
 * 提案2: AI申請難易度分析（1-5段階）
 */
function gi_calculate_difficulty_score($post_id) {
    $score = 3; // デフォルト: 普通
    
    // 必要書類数（ACFフィールド使用）
    $required_docs = get_field('required_documents', $post_id);
    $doc_count = !empty($required_docs) ? count(explode("\n", $required_docs)) : 0;
    
    if ($doc_count >= 10) {
        $score += 1;
    } elseif ($doc_count <= 3) {
        $score -= 1;
    }
    
    // 採択率（ACFフィールド名: adoption_rate）
    $success_rate = floatval(get_field('adoption_rate', $post_id));
    if ($success_rate > 70) {
        $score -= 1;
    } elseif ($success_rate < 30 && $success_rate > 0) {
        $score += 1;
    }
    
    // 対象条件の複雑さ（ACFフィールド使用）
    $target = get_field('grant_target', $post_id);
    if (strlen($target) > 200) {
        $score += 0.5;
    }
    
    $score = max(1, min(5, $score));
    
    $labels = [
        1 => ['label' => '非常に易しい', 'stars' => '1/5', 'class' => 'very-easy', 'dots' => 1],
        2 => ['label' => 'やや易しい', 'stars' => '2/5', 'class' => 'easy', 'dots' => 2],
        3 => ['label' => '普通', 'stars' => '3/5', 'class' => 'normal', 'dots' => 3],
        4 => ['label' => 'やや難しい', 'stars' => '4/5', 'class' => 'hard', 'dots' => 4],
        5 => ['label' => '非常に難しい', 'stars' => '5/5', 'class' => 'very-hard', 'dots' => 5]
    ];
    
    $difficulty = round($score);
    return array_merge(['score' => $difficulty], $labels[$difficulty]);
}

/**
 * 提案3: 類似助成金レコメンド（上位5件）
 */
function gi_get_similar_grants($post_id, $limit = 5) {
    $categories = wp_get_post_terms($post_id, 'grant_category', ['fields' => 'ids']);
    $prefecture = wp_get_post_terms($post_id, 'grant_prefecture', ['fields' => 'ids']);
    
    $args = [
        'post_type' => 'grant',
        'posts_per_page' => $limit + 1,
        'post__not_in' => [$post_id],
        'tax_query' => []
    ];
    
    if (!empty($categories)) {
        $args['tax_query'][] = [
            'taxonomy' => 'grant_category',
            'field' => 'term_id',
            'terms' => $categories
        ];
    }
    
    $query = new WP_Query($args);
    return $query->posts;
}

/**
 * 提案7: 期限アラート判定（ACFフィールド使用、アイコン・絵文字削除）
 */
function gi_get_deadline_urgency($post_id) {
    // ACFフィールドから締切日を取得
    $deadline_date = get_field('deadline_date', $post_id);
    if (empty($deadline_date)) {
        $deadline_date = get_field('deadline', $post_id);
    }
    
    if (empty($deadline_date)) {
        return null;
    }
    
    $deadline_timestamp = is_numeric($deadline_date) ? intval($deadline_date) : strtotime($deadline_date);
    if (!$deadline_timestamp) {
        return null;
    }
    
    $now = current_time('timestamp');
    $days_left = floor(($deadline_timestamp - $now) / (60 * 60 * 24));
    
    if ($days_left < 0) {
        return ['level' => 'expired', 'color' => '#999', 'text' => '期限切れ'];
    } elseif ($days_left <= 3) {
        return ['level' => 'critical', 'color' => '#dc2626', 'text' => "残り{$days_left}日！"];
    } elseif ($days_left <= 7) {
        return ['level' => 'urgent', 'color' => '#f59e0b', 'text' => "残り{$days_left}日"];
    } elseif ($days_left <= 30) {
        return ['level' => 'warning', 'color' => '#eab308', 'text' => "残り{$days_left}日"];
    } else {
        return ['level' => 'safe', 'color' => '#10b981', 'text' => "{$days_left}日"];
    }
}

/**
 * AJAX: チェックリスト生成
 */
add_action('wp_ajax_gi_generate_checklist', 'gi_handle_generate_checklist');
add_action('wp_ajax_nopriv_gi_generate_checklist', 'gi_handle_generate_checklist');

function gi_handle_generate_checklist() {
    check_ajax_referer('gi_ai_search_nonce', 'nonce');
    
    $post_id = intval($_POST['post_id']);
    $grant_title = get_the_title($post_id);
    
    // 基本的なチェックリスト項目
    $checklist = [
        ['id' => 1, 'text' => '事業計画書の作成', 'checked' => false, 'priority' => 'high'],
        ['id' => 2, 'text' => '見積書の取得（3社以上）', 'checked' => false, 'priority' => 'high'],
        ['id' => 3, 'text' => '登記簿謄本の準備', 'checked' => false, 'priority' => 'medium'],
        ['id' => 4, 'text' => '決算書（直近2期分）', 'checked' => false, 'priority' => 'medium'],
        ['id' => 5, 'text' => '納税証明書の取得', 'checked' => false, 'priority' => 'medium'],
        ['id' => 6, 'text' => '事業概要説明資料', 'checked' => false, 'priority' => 'low'],
        ['id' => 7, 'text' => '申請書類のレビュー', 'checked' => false, 'priority' => 'high']
    ];
    
    wp_send_json_success([
        'checklist' => $checklist,
        'title' => $grant_title
    ]);
}

/**
 * AJAX: AI比較分析
 */
add_action('wp_ajax_gi_compare_grants', 'gi_handle_compare_grants');
add_action('wp_ajax_nopriv_gi_compare_grants', 'gi_handle_compare_grants');

function gi_handle_compare_grants() {
    check_ajax_referer('gi_ai_search_nonce', 'nonce');
    
    $grant_ids = array_map('intval', $_POST['grant_ids']);
    $comparison = [];
    
    foreach ($grant_ids as $id) {
        $comparison[] = [
            'id' => $id,
            'title' => get_the_title($id),
            'amount' => get_post_meta($id, 'max_amount', true),
            'rate' => get_field('adoption_rate', $id),
            'deadline' => get_post_meta($id, 'deadline', true),
            'match_score' => gi_calculate_match_score($id),
            'difficulty' => gi_calculate_difficulty_score($id)
        ];
    }
    
    // 最適な助成金を判定
    usort($comparison, function($a, $b) {
        return $b['match_score'] - $a['match_score'];
    });
    
    $recommendation = $comparison[0];
    
    wp_send_json_success([
        'comparison' => $comparison,
        'recommendation' => $recommendation
    ]);
}

// ============================================================================
// 2025年版 Enhanced AI System - Three Major Improvements
// ============================================================================

/**
 * ENHANCEMENT 1: 網羅性 (COMPREHENSIVENESS)
 * Advanced Intent Analysis, Knowledge Sources Integration, Dynamic Processing
 */

/**
 * GI_Enhanced_Intent_Analyzer: Advanced intent classification system
 */
class GI_Enhanced_Intent_Analyzer {
    private static $instance = null;
    private $openai;
    private $gemini;
    
    private function __construct() {
        $this->openai = GI_OpenAI_Integration::getInstance();
        $this->gemini = GI_Gemini_Integration::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Analyze user intent and classify question type
     */
    public function analyze_intent($query, $context = []) {
        $intent_data = [
            'primary_intent' => $this->classify_primary_intent($query),
            'secondary_intents' => $this->detect_secondary_intents($query),
            'complexity_level' => $this->assess_complexity($query),
            'urgency_level' => $this->detect_urgency($query),
            'knowledge_domains' => $this->identify_knowledge_domains($query),
            'processing_strategy' => null
        ];
        
        // Determine optimal processing strategy
        $intent_data['processing_strategy'] = $this->determine_processing_strategy($intent_data);
        
        return $intent_data;
    }
    
    /**
     * Classify primary intent type
     */
    private function classify_primary_intent($query) {
        $patterns = [
            'exploration' => [
                'patterns' => ['どんな', '何か', '教えて', '知りたい', '探している', '調べ'],
                'confidence' => 0
            ],
            'specific_information' => [
                'patterns' => ['いくら', '金額', '条件', '対象', '期限', '申請方法'],
                'confidence' => 0
            ],
            'procedures' => [
                'patterns' => ['申請', '手続き', '方法', 'やり方', 'ステップ', '流れ'],
                'confidence' => 0
            ],
            'comparison' => [
                'patterns' => ['比較', '違い', 'どちらが', 'どっち', 'vs', '優れて'],
                'confidence' => 0
            ],
            'recommendation' => [
                'patterns' => ['おすすめ', '最適', '適した', '良い', 'ベスト', '選んで'],
                'confidence' => 0
            ]
        ];
        
        foreach ($patterns as $intent => $data) {
            foreach ($data['patterns'] as $pattern) {
                if (mb_stripos($query, $pattern) !== false) {
                    $patterns[$intent]['confidence'] += 1;
                }
            }
        }
        
        // Find highest confidence intent
        $max_confidence = 0;
        $primary_intent = 'exploration'; // default
        
        foreach ($patterns as $intent => $data) {
            if ($data['confidence'] > $max_confidence) {
                $max_confidence = $data['confidence'];
                $primary_intent = $intent;
            }
        }
        
        return [
            'type' => $primary_intent,
            'confidence' => $max_confidence / 10, // normalize to 0-1
            'detected_patterns' => $patterns
        ];
    }
    
    /**
     * Detect secondary intents
     */
    private function detect_secondary_intents($query) {
        $secondary_intents = [];
        
        // Location-specific
        if (preg_match('/(東京|大阪|愛知|福岡|北海道|神奈川|埼玉|千葉)/u', $query, $matches)) {
            $secondary_intents[] = [
                'type' => 'location_specific',
                'value' => $matches[1],
                'confidence' => 0.9
            ];
        }
        
        // Industry-specific
        if (preg_match('/(製造|IT|農業|観光|飲食|建設|医療|介護|小売|サービス)/u', $query, $matches)) {
            $secondary_intents[] = [
                'type' => 'industry_specific',
                'value' => $matches[1],
                'confidence' => 0.8
            ];
        }
        
        // Company size
        if (preg_match('/(中小企業|小規模|スタートアップ|ベンチャー|個人事業)/u', $query, $matches)) {
            $secondary_intents[] = [
                'type' => 'company_size',
                'value' => $matches[1],
                'confidence' => 0.7
            ];
        }
        
        // Amount-focused
        if (preg_match('/(万円|億円|金額|費用|予算)/u', $query)) {
            $secondary_intents[] = [
                'type' => 'amount_focused',
                'confidence' => 0.6
            ];
        }
        
        return $secondary_intents;
    }
    
    /**
     * Assess query complexity
     */
    private function assess_complexity($query) {
        $length_score = min(mb_strlen($query) / 100, 1.0);
        $keyword_count = count(explode(' ', $query));
        $keyword_score = min($keyword_count / 20, 1.0);
        
        $complexity_indicators = [
            '比較' => 0.3,
            '違い' => 0.3,
            '複数' => 0.2,
            'かつ' => 0.2,
            'または' => 0.2,
            '条件' => 0.1
        ];
        
        $complexity_score = 0;
        foreach ($complexity_indicators as $indicator => $weight) {
            if (mb_stripos($query, $indicator) !== false) {
                $complexity_score += $weight;
            }
        }
        
        $total_complexity = ($length_score + $keyword_score + $complexity_score) / 3;
        
        if ($total_complexity >= 0.7) {
            return ['level' => 'high', 'score' => $total_complexity];
        } elseif ($total_complexity >= 0.4) {
            return ['level' => 'medium', 'score' => $total_complexity];
        } else {
            return ['level' => 'low', 'score' => $total_complexity];
        }
    }
    
    /**
     * Detect urgency level
     */
    private function detect_urgency($query) {
        $urgency_patterns = [
            'high' => ['急いで', '至急', '今すぐ', '緊急', 'すぐに', '今日', '明日'],
            'medium' => ['早め', '近日中', 'なるべく早く', '今月中', '来月まで'],
            'low' => ['いつか', 'そのうち', '将来', '予定']
        ];
        
        foreach ($urgency_patterns as $level => $patterns) {
            foreach ($patterns as $pattern) {
                if (mb_stripos($query, $pattern) !== false) {
                    return [
                        'level' => $level,
                        'detected_pattern' => $pattern,
                        'confidence' => 0.8
                    ];
                }
            }
        }
        
        return ['level' => 'normal', 'confidence' => 0.5];
    }
    
    /**
     * Identify knowledge domains needed
     */
    private function identify_knowledge_domains($query) {
        $domains = [];
        
        $domain_patterns = [
            'grant_database' => ['助成金', '補助金', '支援金', '給付金'],
            'legal_documents' => ['法律', '規則', '条例', '要綱', '規定'],
            'application_procedures' => ['申請', '手続き', '提出', '書類', '様式'],
            'industry_knowledge' => ['製造業', 'IT', '農業', '観光', '医療'],
            'regional_information' => ['地域', '自治体', '都道府県', '市町村'],
            'financial_calculations' => ['金額', '計算', '率', '%', '万円', '億円'],
            'deadline_management' => ['期限', '締切', 'スケジュール', '日程']
        ];
        
        foreach ($domain_patterns as $domain => $patterns) {
            $matches = 0;
            foreach ($patterns as $pattern) {
                if (mb_stripos($query, $pattern) !== false) {
                    $matches++;
                }
            }
            if ($matches > 0) {
                $domains[] = [
                    'domain' => $domain,
                    'confidence' => min($matches / count($patterns), 1.0),
                    'matches' => $matches
                ];
            }
        }
        
        // Sort by confidence
        usort($domains, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        return $domains;
    }
    
    /**
     * Determine optimal processing strategy
     */
    private function determine_processing_strategy($intent_data) {
        // Null check to prevent errors
        if (!$intent_data || !is_array($intent_data)) {
            return [
                'strategy' => 'general_assistance',
                'requires_ai' => false,
                'requires_multiple_sources' => false,
                'estimated_processing_time' => 2
            ];
        }
        
        $primary = $intent_data['primary_intent']['type'] ?? 'exploration';
        $complexity = $intent_data['complexity_level']['level'] ?? 'low';
        $domains = $intent_data['knowledge_domains'] ?? [];
        
        $strategies = [
            'exploration' => [
                'low' => 'simple_search_with_categories',
                'medium' => 'guided_discovery',
                'high' => 'comprehensive_analysis'
            ],
            'specific_information' => [
                'low' => 'direct_lookup',
                'medium' => 'structured_query',
                'high' => 'multi_source_aggregation'
            ],
            'procedures' => [
                'low' => 'step_by_step_guide',
                'medium' => 'detailed_procedure_with_checklist',
                'high' => 'comprehensive_procedure_analysis'
            ],
            'comparison' => [
                'low' => 'simple_comparison_table',
                'medium' => 'detailed_comparison_analysis',
                'high' => 'multi_criteria_decision_analysis'
            ],
            'recommendation' => [
                'low' => 'basic_recommendation',
                'medium' => 'personalized_recommendation',
                'high' => 'ai_powered_matching'
            ]
        ];
        
        $base_strategy = $strategies[$primary][$complexity] ?? 'general_assistance';
        
        return [
            'strategy' => $base_strategy,
            'requires_ai' => in_array($complexity, ['medium', 'high']),
            'requires_multiple_sources' => count($domains) > 2,
            'estimated_processing_time' => $this->estimate_processing_time($intent_data)
        ];
    }
    
    /**
     * Estimate processing time
     */
    private function estimate_processing_time($intent_data) {
        $base_time = 2; // seconds
        
        // Null check to prevent errors
        if (!$intent_data || !is_array($intent_data)) {
            return $base_time;
        }
        
        if (isset($intent_data['complexity_level']['level']) && $intent_data['complexity_level']['level'] === 'high') {
            $base_time += 3;
        } elseif (isset($intent_data['complexity_level']['level']) && $intent_data['complexity_level']['level'] === 'medium') {
            $base_time += 1;
        }
        
        if (isset($intent_data['knowledge_domains']) && is_array($intent_data['knowledge_domains']) && count($intent_data['knowledge_domains']) > 3) {
            $base_time += 2;
        }
        
        if (isset($intent_data['processing_strategy']['requires_ai']) && $intent_data['processing_strategy']['requires_ai']) {
            $base_time += 5;
        }
        
        return $base_time;
    }
}

/**
 * GI_Comprehensive_Knowledge_Engine: Unified knowledge source integration
 */
class GI_Comprehensive_Knowledge_Engine {
    private static $instance = null;
    private $db_sources = [];
    private $external_sources = [];
    private $rag_engine;
    
    private function __construct() {
        $this->initialize_knowledge_sources();
        $this->rag_engine = GI_RAG_Engine::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize all knowledge sources
     */
    private function initialize_knowledge_sources() {
        $this->db_sources = [
            'grants' => [
                'table' => 'posts',
                'post_type' => 'grant',
                'priority' => 1
            ],
            'faqs' => [
                'table' => 'posts',
                'post_type' => 'faq',
                'priority' => 2
            ],
            'procedures' => [
                'table' => 'posts',
                'post_type' => 'procedure',
                'priority' => 3
            ],
            'user_interactions' => [
                'table' => 'gi_user_context',
                'priority' => 4
            ]
        ];
        
        $this->external_sources = [
            'government_api' => [
                'enabled' => false, // Enable when API available
                'endpoint' => '',
                'priority' => 5
            ],
            'legal_database' => [
                'enabled' => false, // Enable when needed
                'endpoint' => '',
                'priority' => 6
            ]
        ];
    }
    
    /**
     * Query comprehensive knowledge base
     */
    public function query_knowledge($query, $intent_data, $context = []) {
        $results = [];
        
        // 1. Database sources (grants, FAQs, etc.)
        $db_results = $this->query_database_sources($query, $intent_data);
        $results = array_merge($results, $db_results);
        
        // 2. RAG for external documents
        $rag_results = $this->rag_engine->query($query, $intent_data);
        $results = array_merge($results, $rag_results);
        
        // 3. FAQ matching
        $faq_results = $this->query_faq_database($query, $intent_data);
        $results = array_merge($results, $faq_results);
        
        // 4. Procedural knowledge
        $procedure_results = $this->query_procedure_database($query, $intent_data);
        $results = array_merge($results, $procedure_results);
        
        // 5. External sources (if enabled)
        $external_results = $this->query_external_sources($query, $intent_data);
        $results = array_merge($results, $external_results);
        
        // Rank and filter results
        return $this->rank_and_filter_results($results, $intent_data);
    }
    
    /**
     * Query database sources
     */
    private function query_database_sources($query, $intent_data) {
        $results = [];
        
        // Semantic search for grants
        $semantic_search = GI_Semantic_Search::getInstance();
        $semantic_results = $semantic_search->semantic_search($query, 10);
        
        foreach ($semantic_results as $result) {
            $results[] = [
                'type' => 'grant',
                'source' => 'database',
                'id' => $result['post_id'],
                'title' => $result['post']->post_title,
                'content' => wp_trim_words($result['post']->post_content, 100),
                'relevance_score' => $result['similarity'] ?? 0.5,
                'post_data' => $result['post'],
                'metadata' => [
                    'post_type' => 'grant',
                    'match_score' => gi_calculate_match_score($result['post_id']),
                    'difficulty' => gi_calculate_difficulty_score($result['post_id'])
                ]
            ];
        }
        
        return $results;
    }
    
    /**
     * Query FAQ database
     */
    private function query_faq_database($query, $intent_data) {
        $results = [];
        
        // Search FAQ posts
        $faq_query = new WP_Query([
            'post_type' => 'faq',
            'posts_per_page' => 5,
            's' => $query,
            'meta_query' => [
                [
                    'key' => '_faq_priority',
                    'compare' => 'EXISTS'
                ]
            ],
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ]);
        
        if ($faq_query->have_posts()) {
            while ($faq_query->have_posts()) {
                $faq_query->the_post();
                $results[] = [
                    'type' => 'faq',
                    'source' => 'database',
                    'id' => get_the_ID(),
                    'question' => get_the_title(),
                    'answer' => get_the_content(),
                    'relevance_score' => 0.6, // Base relevance for FAQ
                    'metadata' => [
                        'post_type' => 'faq',
                        'priority' => get_post_meta(get_the_ID(), '_faq_priority', true)
                    ]
                ];
            }
            wp_reset_postdata();
        }
        
        return $results;
    }
    
    /**
     * Query procedure database
     */
    private function query_procedure_database($query, $intent_data) {
        $results = [];
        
        // Only query if intent suggests procedural information needed
        if ($intent_data['primary_intent']['type'] === 'procedures') {
            $procedure_query = new WP_Query([
                'post_type' => 'procedure',
                'posts_per_page' => 3,
                's' => $query,
                'orderby' => 'relevance'
            ]);
            
            if ($procedure_query->have_posts()) {
                while ($procedure_query->have_posts()) {
                    $procedure_query->the_post();
                    $results[] = [
                        'type' => 'procedure',
                        'source' => 'database',
                        'id' => get_the_ID(),
                        'title' => get_the_title(),
                        'steps' => get_field('procedure_steps', get_the_ID()),
                        'content' => get_the_content(),
                        'relevance_score' => 0.7,
                        'metadata' => [
                            'post_type' => 'procedure',
                            'complexity' => get_field('procedure_complexity', get_the_ID())
                        ]
                    ];
                }
                wp_reset_postdata();
            }
        }
        
        return $results;
    }
    
    /**
     * Query external sources
     */
    private function query_external_sources($query, $intent_data) {
        $results = [];
        
        foreach ($this->external_sources as $source_name => $config) {
            if ($config['enabled']) {
                // Implementation would depend on specific external API
                // Placeholder for future external integrations
                $results[] = [
                    'type' => 'external',
                    'source' => $source_name,
                    'content' => 'External source integration placeholder',
                    'relevance_score' => 0.3
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Rank and filter results by relevance
     */
    private function rank_and_filter_results($results, $intent_data) {
        // Sort by relevance score
        usort($results, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        // Apply intent-specific filtering
        $filtered_results = $this->apply_intent_filtering($results, $intent_data);
        
        // Limit results based on complexity
        $max_results = $intent_data['complexity_level']['level'] === 'high' ? 15 : 10;
        
        return [
            'results' => array_slice($filtered_results, 0, $max_results),
            'total_found' => count($results),
            'sources_used' => $this->get_sources_summary($results),
            'relevance_threshold' => 0.3
        ];
    }
    
    /**
     * Apply intent-specific filtering
     */
    private function apply_intent_filtering($results, $intent_data) {
        $primary_intent = $intent_data['primary_intent']['type'];
        
        switch ($primary_intent) {
            case 'procedures':
                // Prioritize procedural content
                return array_filter($results, function($result) {
                    return in_array($result['type'], ['procedure', 'faq']) || 
                           $result['relevance_score'] > 0.6;
                });
                
            case 'specific_information':
                // Filter for informational content
                return array_filter($results, function($result) {
                    return $result['relevance_score'] > 0.4;
                });
                
            case 'comparison':
                // Ensure multiple comparable items
                $grant_results = array_filter($results, function($result) {
                    return $result['type'] === 'grant';
                });
                return count($grant_results) >= 2 ? $results : $grant_results;
                
            default:
                return array_filter($results, function($result) {
                    return $result['relevance_score'] > 0.3;
                });
        }
    }
    
    /**
     * Get summary of sources used
     */
    private function get_sources_summary($results) {
        $sources = [];
        foreach ($results as $result) {
            $key = $result['source'] . '_' . $result['type'];
            if (!isset($sources[$key])) {
                $sources[$key] = 0;
            }
            $sources[$key]++;
        }
        return $sources;
    }
}

/**
 * GI_RAG_Engine: Retrieval-Augmented Generation for external documents
 */
class GI_RAG_Engine {
    private static $instance = null;
    private $document_store = [];
    private $embedding_cache = [];
    
    private function __construct() {
        $this->initialize_document_store();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize document store with uploaded documents
     */
    private function initialize_document_store() {
        // Get documents from media library with specific meta
        $documents = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'meta_query' => [
                [
                    'key' => '_rag_document',
                    'value' => '1',
                    'compare' => '='
                ]
            ],
            'numberposts' => -1
        ]);
        
        foreach ($documents as $doc) {
            $this->document_store[] = [
                'id' => $doc->ID,
                'title' => $doc->post_title,
                'url' => wp_get_attachment_url($doc->ID),
                'type' => get_post_mime_type($doc->ID),
                'metadata' => get_post_meta($doc->ID, '_rag_metadata', true) ?: [],
                'last_indexed' => get_post_meta($doc->ID, '_rag_last_indexed', true)
            ];
        }
    }
    
    /**
     * Query RAG documents
     */
    public function query($query, $intent_data) {
        $results = [];
        
        // For now, return processed document summaries
        // Full RAG implementation would require document parsing and chunking
        foreach ($this->document_store as $doc) {
            $relevance = $this->calculate_document_relevance($query, $doc);
            if ($relevance > 0.3) {
                $results[] = [
                    'type' => 'document',
                    'source' => 'rag',
                    'id' => $doc['id'],
                    'title' => $doc['title'],
                    'content' => $this->extract_relevant_content($query, $doc),
                    'relevance_score' => $relevance,
                    'metadata' => [
                        'document_type' => $doc['type'],
                        'url' => $doc['url']
                    ]
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Calculate document relevance (simplified)
     */
    private function calculate_document_relevance($query, $doc) {
        $title_match = mb_stripos($doc['title'], $query) !== false ? 0.5 : 0;
        $metadata_match = 0;
        
        if (!empty($doc['metadata']['keywords'])) {
            $keywords = explode(',', $doc['metadata']['keywords']);
            foreach ($keywords as $keyword) {
                if (mb_stripos($query, trim($keyword)) !== false) {
                    $metadata_match += 0.2;
                }
            }
        }
        
        return min(1.0, $title_match + $metadata_match);
    }
    
    /**
     * Extract relevant content from document
     */
    private function extract_relevant_content($query, $doc) {
        // Simplified content extraction
        // Full implementation would parse document and extract relevant chunks
        return "このドキュメント（{$doc['title']}）には関連情報が含まれている可能性があります。詳細は添付ファイルをご確認ください。";
    }
    
    /**
     * Add document to RAG store
     */
    public function add_document($attachment_id, $metadata = []) {
        update_post_meta($attachment_id, '_rag_document', '1');
        update_post_meta($attachment_id, '_rag_metadata', $metadata);
        update_post_meta($attachment_id, '_rag_last_indexed', current_time('mysql'));
        
        // Reinitialize document store
        $this->initialize_document_store();
    }
}

/**
 * GI_Dynamic_Processor: Intent-based dynamic processing
 */
class GI_Dynamic_Processor {
    private static $instance = null;
    private $intent_analyzer;
    private $knowledge_engine;
    private $openai;
    private $gemini;
    
    private function __construct() {
        $this->intent_analyzer = GI_Enhanced_Intent_Analyzer::getInstance();
        $this->knowledge_engine = GI_Comprehensive_Knowledge_Engine::getInstance();
        $this->openai = GI_OpenAI_Integration::getInstance();
        $this->gemini = GI_Gemini_Integration::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Process query based on analyzed intent
     */
    public function process_query($query, $context = []) {
        // Step 1: Analyze intent
        $intent_data = $this->intent_analyzer->analyze_intent($query, $context);
        
        // Step 2: Query knowledge sources
        $knowledge_results = $this->knowledge_engine->query_knowledge($query, $intent_data, $context);
        
        // Step 3: Apply dynamic processing strategy
        $processing_result = $this->apply_processing_strategy($query, $intent_data, $knowledge_results, $context);
        
        return [
            'intent_analysis' => $intent_data,
            'knowledge_results' => $knowledge_results,
            'processing_result' => $processing_result,
            'response_metadata' => [
                'processing_time' => $intent_data['processing_strategy']['estimated_processing_time'],
                'confidence_score' => $this->calculate_confidence_score($intent_data, $knowledge_results),
                'sources_count' => count($knowledge_results['sources_used']),
                'strategy_used' => $intent_data['processing_strategy']['strategy']
            ]
        ];
    }
    
    /**
     * Apply processing strategy based on intent
     */
    private function apply_processing_strategy($query, $intent_data, $knowledge_results, $context) {
        $strategy = $intent_data['processing_strategy']['strategy'];
        $results = $knowledge_results['results'];
        
        switch ($strategy) {
            case 'simple_search_with_categories':
                return $this->process_simple_search($results, $context);
                
            case 'guided_discovery':
                return $this->process_guided_discovery($query, $results, $intent_data, $context);
                
            case 'comprehensive_analysis':
                return $this->process_comprehensive_analysis($query, $results, $intent_data, $context);
                
            case 'direct_lookup':
                return $this->process_direct_lookup($results, $intent_data, $context);
                
            case 'structured_query':
                return $this->process_structured_query($query, $results, $intent_data, $context);
                
            case 'multi_source_aggregation':
                return $this->process_multi_source_aggregation($query, $results, $intent_data, $context);
                
            case 'step_by_step_guide':
                return $this->process_step_by_step_guide($results, $intent_data, $context);
                
            case 'detailed_procedure_with_checklist':
                return $this->process_detailed_procedure($results, $intent_data, $context);
                
            case 'simple_comparison_table':
                return $this->process_simple_comparison($results, $context);
                
            case 'detailed_comparison_analysis':
                return $this->process_detailed_comparison($query, $results, $intent_data, $context);
                
            case 'basic_recommendation':
                return $this->process_basic_recommendation($results, $context);
                
            case 'personalized_recommendation':
                return $this->process_personalized_recommendation($query, $results, $intent_data, $context);
                
            case 'ai_powered_matching':
                return $this->process_ai_powered_matching($query, $results, $intent_data, $context);
                
            default:
                return $this->process_general_assistance($query, $results, $context);
        }
    }
    
    /**
     * Process simple search with categories
     */
    private function process_simple_search($results, $context) {
        $categories = [];
        $grants = [];
        
        foreach ($results as $result) {
            if ($result['type'] === 'grant') {
                $grants[] = [
                    'id' => $result['id'],
                    'title' => $result['title'],
                    'excerpt' => wp_trim_words($result['content'], 30),
                    'url' => get_permalink($result['id']),
                    'relevance' => $result['relevance_score']
                ];
                
                // Collect categories
                $grant_cats = wp_get_post_terms($result['id'], 'grant_category', ['fields' => 'names']);
                $categories = array_merge($categories, $grant_cats);
            }
        }
        
        $categories = array_unique($categories);
        
        return [
            'type' => 'simple_search_results',
            'grants' => array_slice($grants, 0, 10),
            'categories' => array_slice($categories, 0, 8),
            'total_found' => count($grants),
            'message' => count($grants) > 0 
                ? sprintf('%d件の助成金が見つかりました。', count($grants))
                : '条件に合う助成金が見つかりませんでした。条件を変更してお試しください。'
        ];
    }
    
    /**
     * Process guided discovery
     */
    private function process_guided_discovery($query, $results, $intent_data, $context) {
        $discovery_steps = [];
        $recommendations = [];
        
        // Step 1: Identify user needs
        $discovery_steps[] = [
            'step' => 1,
            'title' => 'ニーズの特定',
            'content' => 'あなたの事業に最適な助成金を見つけるため、以下の情報を確認しましょう。',
            'questions' => [
                '事業の業種は何ですか？',
                '助成金の使用目的は何ですか？',
                'どの地域で事業を行っていますか？'
            ]
        ];
        
        // Step 2: Show relevant categories
        $categories = $this->extract_relevant_categories($results);
        $discovery_steps[] = [
            'step' => 2,
            'title' => '関連カテゴリー',
            'content' => 'あなたのクエリに関連するカテゴリーです。',
            'categories' => $categories
        ];
        
        // Step 3: Initial recommendations
        $top_grants = array_slice($results, 0, 5);
        foreach ($top_grants as $grant) {
            if ($grant['type'] === 'grant') {
                $recommendations[] = [
                    'id' => $grant['id'],
                    'title' => $grant['title'],
                    'reason' => '検索クエリとの関連性が高い',
                    'match_score' => $grant['metadata']['match_score'] ?? 70,
                    'next_steps' => ['詳細を確認', '申請条件をチェック', '必要書類を準備']
                ];
            }
        }
        
        return [
            'type' => 'guided_discovery',
            'discovery_steps' => $discovery_steps,
            'recommendations' => $recommendations,
            'next_actions' => [
                '条件を詳しく指定して再検索',
                'カテゴリーから探す',
                '専門アドバイザーに相談'
            ]
        ];
    }
    
    /**
     * Process comprehensive analysis (AI-powered)
     */
    private function process_comprehensive_analysis($query, $results, $intent_data, $context) {
        if (!$this->should_use_ai($intent_data)) {
            return $this->process_guided_discovery($query, $results, $intent_data, $context);
        }
        
        // Use selected AI model (OpenAI or Gemini)
        $ai_model = get_option('gi_preferred_ai_model', 'openai');
        
        if ($ai_model === 'gemini' && $this->gemini->is_configured()) {
            $analysis = $this->gemini->comprehensive_analysis($query, $results, $intent_data);
        } else {
            $analysis = $this->openai->comprehensive_analysis($query, $results, $intent_data);
        }
        
        return [
            'type' => 'comprehensive_analysis',
            'analysis' => $analysis,
            'supporting_grants' => $this->extract_grant_data($results),
            'confidence_level' => 'high',
            'ai_model_used' => $ai_model
        ];
    }
    
    /**
     * Extract relevant categories from results
     */
    private function extract_relevant_categories($results) {
        $categories = [];
        
        foreach ($results as $result) {
            if ($result['type'] === 'grant') {
                $grant_cats = wp_get_post_terms($result['id'], 'grant_category');
                foreach ($grant_cats as $cat) {
                    if (!isset($categories[$cat->term_id])) {
                        $categories[$cat->term_id] = [
                            'name' => $cat->name,
                            'count' => 0,
                            'relevance' => 0
                        ];
                    }
                    $categories[$cat->term_id]['count']++;
                    $categories[$cat->term_id]['relevance'] += $result['relevance_score'];
                }
            }
        }
        
        // Sort by relevance
        uasort($categories, function($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });
        
        return array_slice($categories, 0, 6);
    }
    
    /**
     * Should use AI for processing
     */
    private function should_use_ai($intent_data) {
        return $intent_data['processing_strategy']['requires_ai'] && 
               ($this->openai->is_configured() || $this->gemini->is_configured());
    }
    
    /**
     * Extract grant data for AI analysis
     */
    private function extract_grant_data($results) {
        $grants = [];
        
        foreach ($results as $result) {
            if ($result['type'] === 'grant') {
                $grants[] = [
                    'id' => $result['id'],
                    'title' => $result['title'],
                    'content' => $result['content'],
                    'organization' => get_field('organization', $result['id']),
                    'amount' => get_field('max_amount', $result['id']),
                    'deadline' => get_field('deadline', $result['id']),
                    'relevance_score' => $result['relevance_score']
                ];
            }
        }
        
        return $grants;
    }
    
    /**
     * Calculate overall confidence score
     */
    private function calculate_confidence_score($intent_data, $knowledge_results) {
        $intent_confidence = $intent_data['primary_intent']['confidence'];
        $results_quality = min(count($knowledge_results['results']) / 5, 1.0);
        $avg_relevance = 0;
        
        if (!empty($knowledge_results['results'])) {
            $total_relevance = array_sum(array_column($knowledge_results['results'], 'relevance_score'));
            $avg_relevance = $total_relevance / count($knowledge_results['results']);
        }
        
        return round(($intent_confidence + $results_quality + $avg_relevance) / 3, 2);
    }
    
    // Placeholder methods for other processing strategies
    private function process_direct_lookup($results, $intent_data, $context) {
        return ['type' => 'direct_lookup', 'results' => array_slice($results, 0, 3)];
    }
    
    private function process_structured_query($query, $results, $intent_data, $context) {
        return ['type' => 'structured_query', 'results' => $results];
    }
    
    private function process_multi_source_aggregation($query, $results, $intent_data, $context) {
        return ['type' => 'multi_source_aggregation', 'results' => $results];
    }
    
    private function process_step_by_step_guide($results, $intent_data, $context) {
        return ['type' => 'step_by_step_guide', 'results' => $results];
    }
    
    private function process_detailed_procedure($results, $intent_data, $context) {
        return ['type' => 'detailed_procedure', 'results' => $results];
    }
    
    private function process_simple_comparison($results, $context) {
        return ['type' => 'simple_comparison', 'results' => $results];
    }
    
    private function process_detailed_comparison($query, $results, $intent_data, $context) {
        return ['type' => 'detailed_comparison', 'results' => $results];
    }
    
    private function process_basic_recommendation($results, $context) {
        return ['type' => 'basic_recommendation', 'results' => $results];
    }
    
    private function process_personalized_recommendation($query, $results, $intent_data, $context) {
        return ['type' => 'personalized_recommendation', 'results' => $results];
    }
    
    private function process_ai_powered_matching($query, $results, $intent_data, $context) {
        return ['type' => 'ai_powered_matching', 'results' => $results];
    }
    
    private function process_general_assistance($query, $results, $context) {
        return ['type' => 'general_assistance', 'results' => $results];
    }
}

/**
 * ENHANCEMENT 2: 見せ方 (PRESENTATION)
 * Markdown Formatting, JSON UI Components, Streaming Display
 */

/**
 * GI_Response_Formatter: Advanced response formatting with Markdown and JSON
 */
class GI_Response_Formatter {
    private static $instance = null;
    
    private function __construct() {
        // No external dependencies required
        // Using built-in simple Markdown processor
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Format response with Markdown and JSON UI components
     */
    public function format_response($processing_result, $intent_data, $options = []) {
        $formatted = [
            'markdown_content' => '',
            'ui_components' => [],
            'streaming_chunks' => [],
            'metadata' => []
        ];
        
        $response_type = $processing_result['type'];
        
        switch ($response_type) {
            case 'simple_search_results':
                return $this->format_simple_search($processing_result, $options);
                
            case 'guided_discovery':
                return $this->format_guided_discovery($processing_result, $options);
                
            case 'comprehensive_analysis':
                return $this->format_comprehensive_analysis($processing_result, $options);
                
            case 'detailed_comparison':
                return $this->format_comparison($processing_result, $options);
                
            default:
                return $this->format_default_response($processing_result, $options);
        }
    }
    
    /**
     * Format simple search results
     */
    private function format_simple_search($result, $options) {
        $markdown = "# 検索結果\n\n";
        $ui_components = [];
        
        // Summary
        $markdown .= $result['message'] . "\n\n";
        
        // Results list
        if (!empty($result['grants'])) {
            $markdown .= "## 見つかった助成金\n\n";
            
            $grants_component = [
                'type' => 'grants_list',
                'data' => [
                    'grants' => $result['grants'],
                    'display_mode' => 'cards',
                    'show_filters' => true
                ]
            ];
            $ui_components[] = $grants_component;
            
            foreach ($result['grants'] as $grant) {
                $markdown .= sprintf(
                    "### [%s](%s)\n\n%s\n\n**関連度:** %.1f%%\n\n---\n\n",
                    $grant['title'],
                    $grant['url'],
                    $grant['excerpt'],
                    $grant['relevance'] * 100
                );
            }
        }
        
        // Categories
        if (!empty($result['categories'])) {
            $categories_component = [
                'type' => 'category_tags',
                'data' => [
                    'categories' => $result['categories'],
                    'clickable' => true,
                    'style' => 'tags'
                ]
            ];
            $ui_components[] = $categories_component;
        }
        
        return [
            'markdown_content' => $markdown,
            'ui_components' => $ui_components,
            'streaming_chunks' => $this->create_streaming_chunks($markdown, $ui_components),
            'metadata' => [
                'total_grants' => count($result['grants']),
                'has_more' => $result['total_found'] > count($result['grants'])
            ]
        ];
    }
    
    /**
     * Format guided discovery
     */
    private function format_guided_discovery($result, $options) {
        $markdown = "# ガイド付き検索\n\n";
        $ui_components = [];
        
        // Discovery steps
        if (!empty($result['discovery_steps'])) {
            $steps_component = [
                'type' => 'discovery_wizard',
                'data' => [
                    'steps' => $result['discovery_steps'],
                    'current_step' => 1,
                    'interactive' => true
                ]
            ];
            $ui_components[] = $steps_component;
            
            foreach ($result['discovery_steps'] as $step) {
                $markdown .= sprintf("## Step %d: %s\n\n%s\n\n", $step['step'], $step['title'], $step['content']);
                
                if (!empty($step['questions'])) {
                    $markdown .= "❓ **確認事項:**\n\n";
                    foreach ($step['questions'] as $question) {
                        $markdown .= "- " . $question . "\n";
                    }
                    $markdown .= "\n";
                }
            }
        }
        
        // Recommendations
        if (!empty($result['recommendations'])) {
            $recommendations_component = [
                'type' => 'recommendation_cards',
                'data' => [
                    'recommendations' => $result['recommendations'],
                    'show_scores' => true,
                    'show_next_steps' => true
                ]
            ];
            $ui_components[] = $recommendations_component;
            
            $markdown .= "## おすすめ助成金\n\n";
            foreach ($result['recommendations'] as $rec) {
                $markdown .= sprintf(
                    "### %s\n\n**適合度:** %d%%\n\n**理由:** %s\n\n",
                    $rec['title'],
                    $rec['match_score'],
                    $rec['reason']
                );
                
                if (!empty($rec['next_steps'])) {
                    $markdown .= "✅ **次のステップ:**\n\n";
                    foreach ($rec['next_steps'] as $step) {
                        $markdown .= "- " . $step . "\n";
                    }
                }
                $markdown .= "\n---\n\n";
            }
        }
        
        return [
            'markdown_content' => $markdown,
            'ui_components' => $ui_components,
            'streaming_chunks' => $this->create_streaming_chunks($markdown, $ui_components),
            'metadata' => [
                'type' => 'guided_discovery',
                'interactive' => true
            ]
        ];
    }
    
    /**
     * Format comprehensive analysis
     */
    private function format_comprehensive_analysis($result, $options) {
        $markdown = "# 総合分析結果\n\n";
        $ui_components = [];
        
        // Analysis content
        if (!empty($result['analysis'])) {
            $markdown .= $result['analysis'] . "\n\n";
        }
        
        // Supporting grants
        if (!empty($result['supporting_grants'])) {
            $analysis_component = [
                'type' => 'analysis_dashboard',
                'data' => [
                    'grants' => $result['supporting_grants'],
                    'confidence' => $result['confidence_level'],
                    'ai_model' => $result['ai_model_used'] ?? 'openai',
                    'show_details' => true
                ]
            ];
            $ui_components[] = $analysis_component;
            
            $markdown .= "## 関連助成金\n\n";
            foreach ($result['supporting_grants'] as $grant) {
                $markdown .= sprintf(
                    "### %s\n\n**実施機関:** %s\n\n**最大金額:** %s万円\n\n**締切:** %s\n\n---\n\n",
                    $grant['title'],
                    $grant['organization'] ?? '未記載',
                    $grant['amount'] ?? '要確認',
                    $grant['deadline'] ?? '要確認'
                );
            }
        }
        
        // AI disclaimer
        $disclaimer_component = [
            'type' => 'ai_disclaimer',
            'data' => [
                'model_used' => $result['ai_model_used'] ?? 'openai',
                'confidence' => $result['confidence_level'],
                'timestamp' => current_time('mysql')
            ]
        ];
        $ui_components[] = $disclaimer_component;
        
        return [
            'markdown_content' => $markdown,
            'ui_components' => $ui_components,
            'streaming_chunks' => $this->create_streaming_chunks($markdown, $ui_components),
            'metadata' => [
                'type' => 'ai_analysis',
                'model_used' => $result['ai_model_used'] ?? 'openai',
                'confidence' => $result['confidence_level']
            ]
        ];
    }
    
    /**
     * Format comparison results
     */
    private function format_comparison($result, $options) {
        $markdown = "# 助成金比較結果\n\n";
        $ui_components = [];
        
        // Comparison table
        $comparison_component = [
            'type' => 'comparison_table',
            'data' => [
                'grants' => $result['results'],
                'criteria' => ['amount', 'deadline', 'difficulty', 'match_score'],
                'sortable' => true,
                'filterable' => true
            ]
        ];
        $ui_components[] = $comparison_component;
        
        $markdown .= "## 比較表\n\n";
        $markdown .= "| 助成金名 | 金額 | 締切 | 難易度 | 適合度 |\n";
        $markdown .= "|---|---|---|---|---|\n";
        
        foreach ($result['results'] as $grant) {
            if ($grant['type'] === 'grant') {
                $markdown .= sprintf(
                    "| %s | %s万円 | %s | %s | %.1f%% |\n",
                    $grant['title'],
                    $grant['metadata']['amount'] ?? '要確認',
                    $grant['metadata']['deadline'] ?? '要確認',
                    $grant['metadata']['difficulty']['label'] ?? '普通',
                    $grant['relevance_score'] * 100
                );
            }
        }
        
        return [
            'markdown_content' => $markdown,
            'ui_components' => $ui_components,
            'streaming_chunks' => $this->create_streaming_chunks($markdown, $ui_components),
            'metadata' => [
                'type' => 'comparison',
                'grant_count' => count($result['results'])
            ]
        ];
    }
    
    /**
     * Format default response
     */
    private function format_default_response($result, $options) {
        $markdown = "# 検索結果\n\n";
        
        if (!empty($result['results'])) {
            foreach ($result['results'] as $item) {
                $markdown .= "## " . ($item['title'] ?? 'タイトルなし') . "\n\n";
                $markdown .= ($item['content'] ?? $item['answer'] ?? '') . "\n\n";
            }
        } else {
            $markdown .= "申し訳ございませんが、条件に合う情報が見つかりませんでした。\n\n検索条件を変更してお試しください。";
        }
        
        return [
            'markdown_content' => $markdown,
            'ui_components' => [],
            'streaming_chunks' => $this->create_streaming_chunks($markdown, []),
            'metadata' => ['type' => 'default']
        ];
    }
    
    /**
     * Create streaming chunks for progressive display
     */
    private function create_streaming_chunks($markdown, $ui_components) {
        $chunks = [];
        
        // Split markdown into logical sections
        $sections = preg_split('/^(#{1,3}\s)/m', $markdown, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        $current_chunk = '';
        for ($i = 0; $i < count($sections); $i += 2) {
            $header = $sections[$i] ?? '';
            $content = $sections[$i + 1] ?? '';
            
            if (!empty($header) || !empty($content)) {
                $chunk_content = $header . $content;
                $chunks[] = [
                    'type' => 'markdown',
                    'content' => trim($chunk_content),
                    'order' => count($chunks),
                    'delay' => count($chunks) * 500 // 500ms delay between chunks
                ];
            }
        }
        
        // Add UI components as separate chunks
        foreach ($ui_components as $index => $component) {
            $chunks[] = [
                'type' => 'ui_component',
                'content' => $component,
                'order' => count($chunks),
                'delay' => count($chunks) * 300
            ];
        }
        
        return $chunks;
    }
    
    /**
     * Convert Markdown to HTML
     */
    public function markdown_to_html($markdown) {
        // Enhanced built-in markdown conversion
        $html = esc_html($markdown);
        
        // Headers
        $html = preg_replace('/^#### (.*$)/m', '<h4>$1</h4>', $html);
        $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);
        
        // Text formatting
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
        $html = preg_replace('/`(.*?)`/', '<code>$1</code>', $html);
        
        // Lists
        $html = preg_replace('/^- (.*$)/m', '<li>$1</li>', $html);
        $html = preg_replace('/^(\d+)\. (.*$)/m', '<li>$2</li>', $html);
        
        // Wrap consecutive list items
        $html = preg_replace('/(<li>.*?<\/li>(?:\s*<li>.*?<\/li>)*)/s', '<ul>$1</ul>', $html);
        
        // Line breaks
        $html = nl2br($html);
        
        return $html;
    }
    
    /**
     * Generate JSON schema for UI components
     */
    public function get_ui_component_schema($type) {
        $schemas = [
            'grants_list' => [
                'type' => 'object',
                'properties' => [
                    'grants' => ['type' => 'array'],
                    'display_mode' => ['type' => 'string', 'enum' => ['cards', 'list', 'table']],
                    'show_filters' => ['type' => 'boolean']
                ]
            ],
            'category_tags' => [
                'type' => 'object',
                'properties' => [
                    'categories' => ['type' => 'array'],
                    'clickable' => ['type' => 'boolean'],
                    'style' => ['type' => 'string', 'enum' => ['tags', 'buttons', 'chips']]
                ]
            ],
            'discovery_wizard' => [
                'type' => 'object',
                'properties' => [
                    'steps' => ['type' => 'array'],
                    'current_step' => ['type' => 'number'],
                    'interactive' => ['type' => 'boolean']
                ]
            ],
            'comparison_table' => [
                'type' => 'object',
                'properties' => [
                    'grants' => ['type' => 'array'],
                    'criteria' => ['type' => 'array'],
                    'sortable' => ['type' => 'boolean'],
                    'filterable' => ['type' => 'boolean']
                ]
            ],
            'analysis_dashboard' => [
                'type' => 'object',
                'properties' => [
                    'grants' => ['type' => 'array'],
                    'confidence' => ['type' => 'string'],
                    'ai_model' => ['type' => 'string'],
                    'show_details' => ['type' => 'boolean']
                ]
            ]
        ];
        
        return $schemas[$type] ?? null;
    }
}

/**
 * GI_Streaming_Manager: Server-Sent Events for real-time display
 */
class GI_Streaming_Manager {
    private static $instance = null;
    
    private function __construct() {
        // Set up SSE headers and handling
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Start streaming response
     */
    public function start_streaming_response($session_id) {
        // Set SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Cache-Control');
        
        // Disable output buffering
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Send initial connection event
        $this->send_sse_event('connected', [
            'session_id' => $session_id,
            'timestamp' => current_time('c')
        ]);
        
        return $session_id;
    }
    
    /**
     * Send streaming chunk
     */
    public function send_chunk($type, $content, $metadata = []) {
        $data = [
            'type' => $type,
            'content' => $content,
            'metadata' => $metadata,
            'timestamp' => current_time('c')
        ];
        
        $this->send_sse_event('chunk', $data);
        
        // Flush output to ensure immediate delivery
        flush();
    }
    
    /**
     * Send complete response chunks
     */
    public function send_streaming_chunks($chunks) {
        foreach ($chunks as $chunk) {
            if (isset($chunk['delay']) && $chunk['delay'] > 0) {
                usleep($chunk['delay'] * 1000); // Convert to microseconds
            }
            
            $this->send_chunk($chunk['type'], $chunk['content'], [
                'order' => $chunk['order'],
                'total_chunks' => count($chunks)
            ]);
        }
        
        // Send completion event
        $this->send_sse_event('complete', [
            'total_chunks' => count($chunks),
            'timestamp' => current_time('c')
        ]);
    }
    
    /**
     * Send SSE event
     */
    private function send_sse_event($event, $data) {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        flush();
    }
    
    /**
     * End streaming session
     */
    public function end_streaming() {
        $this->send_sse_event('end', ['timestamp' => current_time('c')]);
        exit();
    }
}

/**
 * ENHANCEMENT 3: API Enhancement
 * Gemini API Integration with Model Selection
 */

/**
 * GI_Gemini_Integration: Google Gemini API integration
 */
class GI_Gemini_Integration {
    private static $instance = null;
    private $api_key;
    private $api_endpoint = 'https://generativelanguage.googleapis.com/v1/models/';
    private $available_models = [
        'gemini-2.5-pro' => [
            'name' => 'Gemini 2.5 Pro',
            'description' => 'State-of-the-art thinking model for complex reasoning',
            'max_tokens_input' => 1048576,
            'max_tokens_output' => 65536,
            'supports_multimodal' => true,
            'supports_function_calling' => true,
            'supports_streaming' => false
        ],
        'gemini-1.5-pro' => [
            'name' => 'Gemini 1.5 Pro',
            'description' => 'Production model with long context window',
            'max_tokens_input' => 1048576,
            'max_tokens_output' => 8192,
            'supports_multimodal' => true,
            'supports_function_calling' => true,
            'supports_streaming' => true
        ],
        'gemini-1.5-flash' => [
            'name' => 'Gemini 1.5 Flash',
            'description' => 'Fast and efficient model for quick responses',
            'max_tokens_input' => 1048576,
            'max_tokens_output' => 8192,
            'supports_multimodal' => true,
            'supports_function_calling' => true,
            'supports_streaming' => true
        ]
    ];
    
    private function __construct() {
        $this->api_key = get_option('gi_gemini_api_key', '');
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function is_configured() {
        return !empty($this->api_key);
    }
    
    public function get_available_models() {
        return $this->available_models;
    }
    
    /**
     * Generate response using Gemini API
     */
    public function generate_response($prompt, $context = [], $model = 'gemini-2.5-pro') {
        if (!$this->is_configured()) {
            throw new Exception('Gemini API key not configured');
        }
        
        if (!isset($this->available_models[$model])) {
            $model = 'gemini-2.5-pro'; // fallback
        }
        
        try {
            return $this->call_gemini_api($model, $prompt, $context);
        } catch (Exception $e) {
            error_log('Gemini API Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Comprehensive analysis using Gemini
     */
    public function comprehensive_analysis($query, $results, $intent_data) {
        $context_prompt = $this->build_comprehensive_prompt($query, $results, $intent_data);
        
        return $this->generate_response($context_prompt, [], 'gemini-2.5-pro');
    }
    
    /**
     * Build comprehensive analysis prompt
     */
    private function build_comprehensive_prompt($query, $results, $intent_data) {
        $prompt = "あなたは助成金・補助金の専門アナリストです。\n\n";
        $prompt .= "ユーザーの質問: {$query}\n\n";
        
        // Add intent analysis
        $prompt .= "意図分析結果:\n";
        $prompt .= "- 主意図: {$intent_data['primary_intent']['type']}\n";
        $prompt .= "- 複雑度: {$intent_data['complexity_level']['level']}\n";
        $prompt .= "- 緊急度: {$intent_data['urgency_level']['level']}\n\n";
        
        // Add available grants
        if (!empty($results)) {
            $prompt .= "関連する助成金情報:\n";
            foreach (array_slice($results, 0, 5) as $result) {
                if ($result['type'] === 'grant') {
                    $prompt .= "- {$result['title']}: {$result['content']}\n";
                }
            }
            $prompt .= "\n";
        }
        
        $prompt .= "以下の点を含めて、総合的な分析とアドバイスを提供してください:\n";
        $prompt .= "1. ユーザーのニーズ分析\n";
        $prompt .= "2. 最適な助成金の推薦理由\n";
        $prompt .= "3. 申請戦略とスケジュール\n";
        $prompt .= "4. 注意すべきポイント\n";
        $prompt .= "5. 次のアクションプラン\n\n";
        $prompt .= "Markdown形式で読みやすく回答してください。";
        
        return $prompt;
    }
    
    /**
     * Call Gemini API
     */
    private function call_gemini_api($model, $prompt, $context = []) {
        $endpoint = $this->api_endpoint . $model . ':generateContent';
        
        $request_data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 4096
            ]
        ];
        
        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->api_key
            ],
            'body' => json_encode($request_data),
            'timeout' => 60
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        $http_code = wp_remote_retrieve_response_code($response);
        
        if ($http_code !== 200) {
            $error_message = isset($response_body['error']['message']) 
                ? $response_body['error']['message'] 
                : 'HTTP Error: ' . $http_code;
            throw new Exception($error_message);
        }
        
        if (!isset($response_body['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception('Invalid Gemini API response format');
        }
        
        return $response_body['candidates'][0]['content']['parts'][0]['text'];
    }
    
    /**
     * Test Gemini API connection
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            return ['success' => false, 'message' => 'Gemini APIキーが設定されていません'];
        }
        
        try {
            $test_response = $this->generate_response('テストメッセージです。「成功」と答えてください。', [], 'gemini-1.5-flash');
            
            if (!empty($test_response)) {
                return ['success' => true, 'message' => 'Gemini API接続成功'];
            }
            
            return ['success' => false, 'message' => 'API応答が無効です'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'API接続エラー: ' . $e->getMessage()];
        }
    }
}

/**
 * GI_Multi_AI_Manager: AI model selection and management
 */
class GI_Multi_AI_Manager {
    private static $instance = null;
    private $openai;
    private $gemini;
    
    private function __construct() {
        $this->openai = GI_OpenAI_Integration::getInstance();
        $this->gemini = GI_Gemini_Integration::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get available AI providers
     */
    public function get_available_providers() {
        return [
            'openai' => [
                'name' => 'OpenAI',
                'configured' => $this->openai->is_configured(),
                'models' => [
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                    'gpt-4' => 'GPT-4',
                    'gpt-4-turbo' => 'GPT-4 Turbo'
                ]
            ],
            'gemini' => [
                'name' => 'Google Gemini',
                'configured' => $this->gemini->is_configured(),
                'models' => array_map(function($model) {
                    return $model['name'];
                }, $this->gemini->get_available_models())
            ]
        ];
    }
    
    /**
     * Generate response using selected provider
     */
    public function generate_response($prompt, $context = [], $provider = null, $model = null) {
        if (!$provider) {
            $provider = get_option('gi_preferred_ai_provider', 'openai');
        }
        
        switch ($provider) {
            case 'gemini':
                if ($this->gemini->is_configured()) {
                    return $this->gemini->generate_response($prompt, $context, $model ?: 'gemini-2.5-pro');
                }
                // Fallback to OpenAI
                break;
                
            case 'openai':
            default:
                if ($this->openai->is_configured()) {
                    return $this->openai->generate_response($prompt, $context);
                }
                break;
        }
        
        throw new Exception('使用可能なAIプロバイダーが設定されていません');
    }
    
    /**
     * Get optimal model for task
     */
    public function get_optimal_model_for_task($intent_data) {
        $complexity = $intent_data['complexity_level']['level'];
        $requires_reasoning = in_array($intent_data['primary_intent']['type'], ['comparison', 'recommendation']);
        
        if ($complexity === 'high' || $requires_reasoning) {
            // Use most capable models
            if ($this->gemini->is_configured()) {
                return ['provider' => 'gemini', 'model' => 'gemini-2.5-pro'];
            } elseif ($this->openai->is_configured()) {
                return ['provider' => 'openai', 'model' => 'gpt-4-turbo'];
            }
        } else {
            // Use faster models for simple tasks
            if ($this->gemini->is_configured()) {
                return ['provider' => 'gemini', 'model' => 'gemini-1.5-flash'];
            } elseif ($this->openai->is_configured()) {
                return ['provider' => 'openai', 'model' => 'gpt-3.5-turbo'];
            }
        }
        
        throw new Exception('使用可能なAIプロバイダーがありません');
    }
    
    /**
     * Test all configured providers
     */
    public function test_all_connections() {
        $results = [];
        
        if ($this->openai->is_configured()) {
            $results['openai'] = $this->openai->test_connection();
        }
        
        if ($this->gemini->is_configured()) {
            $results['gemini'] = $this->gemini->test_connection();
        }
        
        return $results;
    }
}

/**
 * =================================================================
 * REST API ENDPOINT FOR AI CHAT
 * コラム/助成金ページのAIアシスタント用REST APIエンドポイント
 * =================================================================
 */

add_action('rest_api_init', function() {
    error_log('🔵 Registering REST API endpoint: /gi-api/v1/ai-chat');
    
    register_rest_route('gi-api/v1', '/ai-chat', array(
        'methods' => 'POST',
        'callback' => 'gi_handle_ai_chat_request',
        'permission_callback' => function() {
            error_log('🔐 Permission callback called - returning true');
            // Allow all users (logged in and guests) to access this endpoint
            return true;
        },
        'args' => array(
            'question' => array(
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'context' => array(
                'required' => false,
                'type' => 'object',
                'default' => array()
            )
        )
    ));
    
    error_log('✅ REST API endpoint registered');
});

/**
 * WordPress AJAX Fallback for AI Chat
 * REST APIが機能しない場合のフォールバック
 */
add_action('wp_ajax_gi_ai_chat', 'gi_handle_ajax_ai_chat');
add_action('wp_ajax_nopriv_gi_ai_chat', 'gi_handle_ajax_ai_chat');

function gi_handle_ajax_ai_chat() {
    error_log('🔵 AJAX AI Chat Request received');
    
    // Verify nonce
    check_ajax_referer('wp_rest', 'nonce');
    
    $question = isset($_POST['question']) ? sanitize_text_field($_POST['question']) : '';
    $context = isset($_POST['context']) ? $_POST['context'] : array();
    
    error_log('❓ AJAX Question: ' . $question);
    
    if (empty($question)) {
        wp_send_json_error('質問が空です');
        return;
    }
    
    // Get OpenAI instance
    $openai = GI_OpenAI_Integration::getInstance();
    
    if (!$openai->is_configured()) {
        $fallback_response = gi_generate_fallback_ai_response($question, $context);
        wp_send_json_success(array(
            'answer' => $fallback_response,
            'source' => 'fallback_ajax'
        ));
        return;
    }
    
    // Build context-aware prompt
    $prompt = gi_build_ai_chat_prompt($question, $context);
    
    try {
        // Get AI response
        $ai_response = $openai->generate_response($prompt, array());
        
        if (empty($ai_response)) {
            $fallback_response = gi_generate_fallback_ai_response($question, $context);
            wp_send_json_success(array(
                'answer' => $fallback_response,
                'source' => 'fallback_ajax'
            ));
        } else {
            wp_send_json_success(array(
                'answer' => $ai_response,
                'source' => 'openai_ajax'
            ));
        }
    } catch (Exception $e) {
        error_log('❌ AJAX AI Error: ' . $e->getMessage());
        $fallback_response = gi_generate_fallback_ai_response($question, $context);
        wp_send_json_success(array(
            'answer' => $fallback_response,
            'source' => 'fallback_ajax_error'
        ));
    }
}

/**
 * Handle AI Chat Request
 */
function gi_handle_ai_chat_request($request) {
    // Debug log
    error_log('🤖 AI Chat Request received');
    error_log('🔍 Request method: ' . $request->get_method());
    error_log('🔍 Request params: ' . print_r($request->get_params(), true));
    
    try {
        $question = $request->get_param('question');
        $context = $request->get_param('context');
        
        error_log('❓ Question: ' . $question);
        
        if (empty($question)) {
            error_log('❌ Question is empty');
            return new WP_Error('empty_question', '質問が空です', array('status' => 400));
        }
        
        // Get OpenAI instance
        $openai = GI_OpenAI_Integration::getInstance();
        
        if (!$openai->is_configured()) {
            // Return fallback response if OpenAI is not configured
            $fallback_response = gi_generate_fallback_ai_response($question, $context);
            return rest_ensure_response(array(
                'success' => true,
                'data' => array(
                    'answer' => $fallback_response,
                    'source' => 'fallback'
                )
            ));
        }
        
        // Build context-aware prompt
        $prompt = gi_build_ai_chat_prompt($question, $context);
        
        // Get AI response
        $ai_response = $openai->generate_response($prompt, array());
        
        if (empty($ai_response)) {
            $fallback_response = gi_generate_fallback_ai_response($question, $context);
            return rest_ensure_response(array(
                'success' => true,
                'data' => array(
                    'answer' => $fallback_response,
                    'source' => 'fallback'
                )
            ));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'answer' => $ai_response,
                'source' => 'openai'
            )
        ));
        
    } catch (Exception $e) {
        error_log('AI Chat Error: ' . $e->getMessage());
        
        $fallback_response = gi_generate_fallback_ai_response($question, $context);
        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'answer' => $fallback_response,
                'source' => 'fallback_error'
            )
        ));
    }
}

/**
 * Build AI Chat Prompt
 */
function gi_build_ai_chat_prompt($question, $context) {
    $prompt = "あなたは助成金・補助金の専門アドバイザーです。ユーザーからの質問に親切丁寧に答えてください。\n\n";
    
    if (!empty($context['title'])) {
        $prompt .= "記事タイトル: " . $context['title'] . "\n\n";
    }
    
    if (!empty($context['content'])) {
        $content_excerpt = mb_substr($context['content'], 0, 2000);
        $prompt .= "記事内容:\n" . $content_excerpt . "\n\n";
    }
    
    $prompt .= "ユーザーの質問: " . $question . "\n\n";
    $prompt .= "回答は300文字以内で、わかりやすく、具体的に答えてください。";
    
    return $prompt;
}

/**
 * Generate Fallback AI Response
 */
function gi_generate_fallback_ai_response($question, $context) {
    $question_lower = mb_strtolower($question);
    
    // Pattern matching for common questions
    if (preg_match('/(申請条件|対象|要件|資格)/u', $question_lower)) {
        return "申請条件に関するご質問ですね。\n\n" .
               "詳細な申請条件は、記事内の「対象者・応募要件」セクションをご確認ください。\n" .
               "一般的には、中小企業や個人事業主が対象となることが多く、事業規模や業種によって条件が異なります。\n\n" .
               "より具体的な情報が必要な場合は、実施機関に直接お問い合わせいただくことをお勧めします。";
    }
    
    if (preg_match('/(必要書類|書類|提出)/u', $question_lower)) {
        return "必要書類に関するご質問ですね。\n\n" .
               "一般的に必要な書類には以下が含まれます：\n" .
               "- 申請書（指定様式）\n" .
               "- 事業計画書\n" .
               "- 見積書\n" .
               "- 会社概要書\n" .
               "- 決算書\n\n" .
               "詳細は記事内の「必要書類」セクション、または実施機関の公式サイトでご確認ください。";
    }
    
    if (preg_match('/(申請方法|手順|流れ|やり方)/u', $question_lower)) {
        return "申請方法に関するご質問ですね。\n\n" .
               "一般的な申請の流れは以下の通りです：\n" .
               "1. 申請要件の確認\n" .
               "2. 必要書類の準備\n" .
               "3. 事業計画書の作成\n" .
               "4. 申請書類の提出\n" .
               "5. 審査結果の通知待ち\n\n" .
               "詳しい手順は記事内の「申請の流れ」セクションをご覧ください。";
    }
    
    if (preg_match('/(金額|補助額|いくら|助成額)/u', $question_lower)) {
        return "助成金額に関するご質問ですね。\n\n" .
               "助成金額は、事業規模や申請内容によって異なります。\n" .
               "記事内の「補助金詳細」セクションで、最大金額や補助率をご確認ください。\n\n" .
               "また、実際の支給額は審査によって決定されます。";
    }
    
    if (preg_match('/(期限|締切|いつまで)/u', $question_lower)) {
        return "申請期限に関するご質問ですね。\n\n" .
               "申請期限は助成金によって異なり、また変更される可能性もあります。\n" .
               "最新の期限情報は、記事内の「詳細情報」セクション、または実施機関の公式サイトで必ずご確認ください。\n\n" .
               "期限が近い場合は、お早めの準備をお勧めします。";
    }
    
    // Default response
    return "ご質問ありがとうございます。\n\n" .
           "この記事には、助成金に関する詳しい情報が掲載されています。\n" .
           "目次から該当するセクションをご覧いただくか、ページ内を検索してご確認ください。\n\n" .
           "より具体的な情報が必要な場合は、実施機関に直接お問い合わせいただくことをお勧めします。";
}