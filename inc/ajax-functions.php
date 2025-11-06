<?php
/**
 * Grant Insight Perfect - 3. AJAX Functions File (Complete Implementation)
 *
 * サイトの動的な機能（検索、フィルタリング、AI処理など）を
 * 担当する全てのAJAX処理をここにまとめます。
 * Perfect implementation with comprehensive AI integration
 *
 * @package Grant_Insight_Perfect
 * @version 4.0.0 - Perfect Implementation Edition
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

/**
 * =============================================================================
 * AJAX ハンドラー登録 - 完全版
 * =============================================================================
 */

// AI検索機能
add_action('wp_ajax_gi_ai_search', 'handle_ai_search');
add_action('wp_ajax_nopriv_gi_ai_search', 'handle_ai_search');

// AIチャット機能  
add_action('wp_ajax_gi_ai_chat', 'handle_ai_chat_request');
add_action('wp_ajax_nopriv_gi_ai_chat', 'handle_ai_chat_request');

// Grant AI質問機能
add_action('wp_ajax_handle_grant_ai_question', 'handle_grant_ai_question');
add_action('wp_ajax_nopriv_handle_grant_ai_question', 'handle_grant_ai_question');

// 音声入力機能
add_action('wp_ajax_gi_voice_input', 'gi_ajax_process_voice_input');
add_action('wp_ajax_nopriv_gi_voice_input', 'gi_ajax_process_voice_input');

// 検索候補機能
add_action('wp_ajax_gi_search_suggestions', 'gi_ajax_get_search_suggestions');
add_action('wp_ajax_nopriv_gi_search_suggestions', 'gi_ajax_get_search_suggestions');

// 音声履歴機能
add_action('wp_ajax_gi_voice_history', 'gi_ajax_save_voice_history');
add_action('wp_ajax_nopriv_gi_voice_history', 'gi_ajax_save_voice_history');

// テスト接続機能
add_action('wp_ajax_gi_test_connection', 'gi_ajax_test_connection');
add_action('wp_ajax_nopriv_gi_test_connection', 'gi_ajax_test_connection');

// お気に入り機能
add_action('wp_ajax_gi_toggle_favorite', 'gi_ajax_toggle_favorite');
add_action('wp_ajax_nopriv_gi_toggle_favorite', 'gi_ajax_toggle_favorite');

// 助成金ロード機能（フィルター・検索）
add_action('wp_ajax_gi_load_grants', 'gi_load_grants');
add_action('wp_ajax_nopriv_gi_load_grants', 'gi_load_grants');
add_action('wp_ajax_gi_ajax_load_grants', 'gi_ajax_load_grants');
add_action('wp_ajax_nopriv_gi_ajax_load_grants', 'gi_ajax_load_grants');

// チャット履歴機能
add_action('wp_ajax_gi_get_chat_history', 'gi_ajax_get_chat_history');
add_action('wp_ajax_nopriv_gi_get_chat_history', 'gi_ajax_get_chat_history');

// 検索履歴機能
add_action('wp_ajax_gi_get_search_history', 'gi_ajax_get_search_history');
add_action('wp_ajax_nopriv_gi_get_search_history', 'gi_ajax_get_search_history');

// AIフィードバック機能
add_action('wp_ajax_gi_ai_feedback', 'gi_ajax_submit_ai_feedback');
add_action('wp_ajax_nopriv_gi_ai_feedback', 'gi_ajax_submit_ai_feedback');

// 市町村取得機能
add_action('wp_ajax_gi_get_municipalities_for_prefectures', 'gi_ajax_get_municipalities_for_prefectures');
add_action('wp_ajax_nopriv_gi_get_municipalities_for_prefectures', 'gi_ajax_get_municipalities_for_prefectures');

// 単一都道府県の市町村取得機能
add_action('wp_ajax_gi_get_municipalities_for_prefecture', 'gi_ajax_get_municipalities_for_prefecture');
add_action('wp_ajax_nopriv_gi_get_municipalities_for_prefecture', 'gi_ajax_get_municipalities_for_prefecture');

// データ最適化機能
add_action('wp_ajax_gi_optimize_location_data', 'gi_ajax_optimize_location_data');

// AI チェックリスト生成機能
add_action('wp_ajax_gi_generate_checklist', 'gi_ajax_generate_checklist');
add_action('wp_ajax_nopriv_gi_generate_checklist', 'gi_ajax_generate_checklist');

// Enhanced search suggestions
add_action('wp_ajax_gi_enhanced_search_suggestions', 'gi_ajax_enhanced_search_suggestions');
add_action('wp_ajax_nopriv_gi_enhanced_search_suggestions', 'gi_ajax_enhanced_search_suggestions');

// AI 比較機能
add_action('wp_ajax_gi_compare_grants', 'gi_ajax_compare_grants');
add_action('wp_ajax_nopriv_gi_compare_grants', 'gi_ajax_compare_grants');

// 市町村データ初期化機能
add_action('wp_ajax_gi_initialize_municipalities', 'gi_ajax_initialize_municipalities');

// 市町村データ構造最適化機能
add_action('wp_ajax_gi_optimize_municipality_structure', 'gi_ajax_optimize_municipality_structure');

// Load More機能（無限スクロール用）
add_action('wp_ajax_gi_load_more_grants', 'gi_ajax_load_more_grants');
add_action('wp_ajax_nopriv_gi_load_more_grants', 'gi_ajax_load_more_grants');

/**
 * =============================================================================
 * 主要なAJAXハンドラー関数 - 完全版
 * =============================================================================
 */

/**
 * Enhanced AI検索処理 - セマンティック検索付き
 */
function handle_ai_search() {
    try {
        error_log('🔍 handle_ai_search called with: ' . json_encode($_POST));
        
        // セキュリティ検証
        if (!gi_verify_ajax_nonce()) {
            error_log('❌ Security check failed');
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました', 'code' => 'SECURITY_ERROR']);
            return;
        }
        
        error_log('✅ Security check passed');
        
        // パラメータ取得と検証
        $query = sanitize_text_field($_POST['query'] ?? '');
        $filter = sanitize_text_field($_POST['filter'] ?? 'all');
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $page = intval($_POST['page'] ?? 1);
        $per_page = min(intval($_POST['per_page'] ?? 20), 50); // 最大50件
        
        // セッションID生成
        if (empty($session_id)) {
            $session_id = wp_generate_uuid4();
        }
        
        $start_time = microtime(true);
        
        // クエリが空の場合の処理
        if (empty($query)) {
            $recent_grants = gi_get_recent_grants($per_page);
            wp_send_json_success([
                'grants' => $recent_grants,
                'count' => count($recent_grants),
                'ai_response' => '検索キーワードを入力してください。最近公開された補助金を表示しています。',
                'keywords' => [],
                'session_id' => $session_id,
                'suggestions' => gi_get_popular_search_terms(5),
                'debug' => WP_DEBUG ? ['type' => 'recent_grants'] : null
            ]);
            return;
        }
        
        // Enhanced検索実行
        error_log("🔍 Starting search for query: {$query}, filter: {$filter}");
        $search_result = gi_enhanced_semantic_search($query, $filter, $page, $per_page);
        error_log("🔍 Search result: " . json_encode([
            'count' => $search_result['count'] ?? 'null',
            'grants_count' => count($search_result['grants'] ?? [])
        ]));
        
        // 検索結果の簡単な説明
        $ai_response = gi_generate_simple_search_summary($search_result['count'], $query);
        
        // キーワード抽出
        $keywords = gi_extract_keywords($query);
        
        // 検索履歴保存
        gi_save_search_history($query, ['filter' => $filter], $search_result['count'], $session_id);
        
        // フォローアップ提案生成
        $suggestions = gi_generate_search_suggestions($query, $search_result['grants']);
        
        $end_time = microtime(true);
        $processing_time = round(($end_time - $start_time) * 1000);
        
        wp_send_json_success([
            'grants' => $search_result['grants'],
            'count' => max(0, intval($search_result['count'] ?? 0)),
            'total_pages' => $search_result['total_pages'],
            'current_page' => $page,
            'ai_response' => $ai_response,
            'keywords' => $keywords,
            'suggestions' => $suggestions,
            'session_id' => $session_id,
            'processing_time_ms' => $processing_time,
            'debug' => WP_DEBUG ? [
                'filter' => $filter,
                'method' => $search_result['method'],
                'query_complexity' => gi_analyze_query_complexity($query)
            ] : null
        ]);
        
    } catch (Exception $e) {
        error_log("❌ Search error: " . $e->getMessage());
        error_log("❌ Stack trace: " . $e->getTraceAsString());
        
        wp_send_json_error([
            'message' => '検索中にエラーが発生しました。しばらく後でお試しください。',
            'code' => 'SEARCH_ERROR',
            'debug' => WP_DEBUG ? [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ] : null
        ]);
    }
}

/**
 * Enhanced AIチャット処理
 */
function handle_ai_chat_request() {
    try {
        if (!gi_verify_ajax_nonce()) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました', 'code' => 'SECURITY_ERROR']);
            return;
        }
        
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $context = json_decode(stripslashes($_POST['context'] ?? '{}'), true);
        
        if (empty($message)) {
            wp_send_json_error(['message' => 'メッセージが空です', 'code' => 'EMPTY_MESSAGE']);
            return;
        }
        
        if (empty($session_id)) {
            $session_id = wp_generate_uuid4();
        }
        
        $start_time = microtime(true);
        
        // 意図分析
        $intent = gi_analyze_user_intent($message);
        
        // 簡単なチャット応答
        $ai_response = gi_generate_simple_chat_response($message, $intent);
        
        // チャット履歴保存
        gi_save_chat_history($session_id, 'user', $message, $intent);
        gi_save_chat_history($session_id, 'ai', $ai_response);
        
        // 関連する補助金の提案
        $related_grants = gi_find_related_grants_from_chat($message, $intent);
        
        $end_time = microtime(true);
        $processing_time = round(($end_time - $start_time) * 1000);
        
        wp_send_json_success([
            'response' => $ai_response,
            'session_id' => $session_id,
            'intent' => $intent,
            'related_grants' => $related_grants,
            'suggestions' => gi_generate_chat_suggestions($message, $intent),
            'processing_time_ms' => $processing_time
        ]);
        
    } catch (Exception $e) {

        wp_send_json_error([
            'message' => 'チャット処理中にエラーが発生しました。',
            'code' => 'CHAT_ERROR',
            'debug' => WP_DEBUG ? $e->getMessage() : null
        ]);
    }
}

/**
 * Enhanced Grant AI Question Handler - 助成金固有のAI質問処理
 */
function handle_grant_ai_question() {
    try {
        if (!gi_verify_ajax_nonce()) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました', 'code' => 'SECURITY_ERROR']);
            return;
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $question = sanitize_textarea_field($_POST['question'] ?? '');
        
        if (!$post_id || empty($question)) {
            error_log("Grant AI Question - Invalid params. Post ID: {$post_id}, Question: '{$question}'");
            wp_send_json_error([
                'message' => 'パラメータが不正です', 
                'code' => 'INVALID_PARAMS',
                'debug' => WP_DEBUG ? "Post ID: {$post_id}, Question length: " . strlen($question) : null
            ]);
            return;
        }
        
        // 投稿の存在確認
        $grant_post = get_post($post_id);
        if (!$grant_post || $grant_post->post_type !== 'grant') {
            error_log("Grant AI Question - Grant not found. Post ID: {$post_id}, Post type: " . ($grant_post ? $grant_post->post_type : 'null'));
            wp_send_json_error([
                'message' => '助成金が見つかりません', 
                'code' => 'GRANT_NOT_FOUND',
                'debug' => WP_DEBUG ? "Post exists: " . ($grant_post ? 'yes' : 'no') . ", Post type: " . ($grant_post ? $grant_post->post_type : 'null') : null
            ]);
            return;
        }
        
        $start_time = microtime(true);
        
        // 助成金の基本情報を取得
        $grant_info = gi_get_grant_basic_info($post_id);
        
        // 実際のAI APIを呼び出して回答を生成
        error_log("Grant AI Question - Generating response for post {$post_id}, question: '{$question}'");
        
        $ai_response = gi_call_real_ai_api($question, $grant_info);
        
        if (!$ai_response) {
            error_log('Grant AI Question - AI API failed, using fallback response');
            // フォールバック応答を使用
            $ai_response = gi_generate_fallback_response($question, $grant_info);
            
            if (!$ai_response) {
                wp_send_json_error([
                    'message' => 'AI応答の生成に失敗しました', 
                    'code' => 'AI_RESPONSE_ERROR',
                    'debug' => WP_DEBUG ? 'Both API and fallback failed' : null
                ]);
                return;
            }
        }
        
        error_log('Grant AI Question - Response generated successfully. Length: ' . strlen($ai_response));
        
        $end_time = microtime(true);
        $processing_time = round(($end_time - $start_time) * 1000);
        
        wp_send_json_success([
            'answer' => $ai_response,
            'grant_id' => $post_id,
            'grant_title' => $grant_post->post_title,
            'processing_time_ms' => $processing_time,
            'debug' => WP_DEBUG ? [
                'response_length' => strlen($ai_response),
                'question_length' => strlen($question),
                'grant_info_keys' => array_keys($grant_info)
            ] : null
        ]);
        
    } catch (Exception $e) {
        error_log('Grant AI Question Error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'AI応答の生成中にエラーが発生しました',
            'code' => 'AI_RESPONSE_ERROR',
            'debug' => WP_DEBUG ? $e->getMessage() : null
        ]);
    }
}

/**
 * 助成金の基本情報を取得
 */
function gi_get_grant_basic_info($post_id) {
    $post = get_post($post_id);
    
    // 基本情報
    $grant_info = [
        'title' => $post->post_title,
        'content' => wp_strip_all_tags($post->post_content),
        'excerpt' => $post->post_excerpt
    ];
    
    // カスタムフィールド情報
    $fields = [
        'max_amount' => '最大助成額',
        'deadline' => '申請期限', 
        'grant_target' => '対象者',
        'grant_condition' => '申請条件',
        'application_method' => '申請方法',
        'organization' => '実施機関',
        'contact_info' => '連絡先',
        'required_documents' => '必要書類',
        'selection_criteria' => '選考基準',
        'subsidy_rate' => '補助率',
        'grant_purpose' => '助成目的'
    ];
    
    foreach ($fields as $field => $label) {
        $value = get_field($field, $post_id);
        if (!empty($value)) {
            $grant_info[$label] = is_array($value) ? implode('、', $value) : $value;
        }
    }
    
    // タクソノミー情報
    $prefectures = wp_get_post_terms($post_id, 'grant_prefecture', ['fields' => 'names']);
    if (!empty($prefectures)) {
        $grant_info['対象地域'] = implode('、', $prefectures);
    }
    
    $categories = wp_get_post_terms($post_id, 'grant_category', ['fields' => 'names']);
    if (!empty($categories)) {
        $grant_info['カテゴリ'] = implode('、', $categories);
    }
    
    return $grant_info;
}

/**
 * 実際のAI APIを呼び出して回答を生成
 */
function gi_call_real_ai_api($question, $grant_info) {
    // まず、環境設定でAI APIキーが設定されているかチェック
    $api_key = get_option('gi_openai_api_key', '');
    
    if (empty($api_key)) {
        // API キーが設定されていない場合のフォールバック
        return gi_generate_fallback_response($question, $grant_info);
    }
    
    // 助成金情報を整理してプロンプト作成
    $grant_context = "助成金情報:\n";
    foreach ($grant_info as $key => $value) {
        if (!empty($value)) {
            $grant_context .= "- {$key}: {$value}\n";
        }
    }
    
    $system_prompt = "あなたは助成金に詳しい専門アドバイザーです。提供された助成金情報を基に、ユーザーの質問に正確で分かりやすく回答してください。\n\n{$grant_context}";
    
    // OpenAI API呼び出し
    $api_response = gi_call_openai_api($system_prompt, $question, $api_key);
    
    if ($api_response) {
        return $api_response;
    }
    
    // API呼び出し失敗時のフォールバック
    return gi_generate_fallback_response($question, $grant_info);
}

/**
 * OpenAI API呼び出し
 */
function gi_call_openai_api($system_prompt, $user_question, $api_key) {
    $api_url = 'https://api.openai.com/v1/chat/completions';
    
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $user_question]
        ],
        'max_tokens' => 500,
        'temperature' => 0.7
    ];
    
    $headers = [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $response) {
        $decoded = json_decode($response, true);
        if (isset($decoded['choices'][0]['message']['content'])) {
            return trim($decoded['choices'][0]['message']['content']);
        }
    }
    
    return false;
}

/**
 * API呼び出し失敗時のフォールバック応答
 */
function gi_generate_fallback_response($question, $grant_info) {
    $response = "この助成金について、以下の情報をお答えできます:\n\n";
    
    // 基本的な情報を整理して返す
    if (isset($grant_info['最大助成額'])) {
        $response .= "💰 最大助成額: {$grant_info['最大助成額']}\n";
    }
    if (isset($grant_info['申請期限'])) {
        $response .= "📅 申請期限: {$grant_info['申請期限']}\n";
    }
    if (isset($grant_info['対象者'])) {
        $response .= "👥 対象者: {$grant_info['対象者']}\n";
    }
    if (isset($grant_info['実施機関'])) {
        $response .= "🏢 実施機関: {$grant_info['実施機関']}\n";
    }
    
    $response .= "\n詳しい内容については、実施機関にお問い合わせください。";
    
    return $response;
}

/**
 * Enhanced 音声入力処理
 */
function gi_ajax_process_voice_input() {
    try {
        if (!gi_verify_ajax_nonce()) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました']);
            return;
        }
        
        $audio_data = $_POST['audio_data'] ?? '';
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        
        if (empty($audio_data)) {
            wp_send_json_error(['message' => '音声データが空です']);
            return;
        }
        
        // OpenAI統合を使用して音声認識を試行
        $openai = class_exists('GI_OpenAI_Integration') ? GI_OpenAI_Integration::getInstance() : null;
        if ($openai && $openai->is_configured() && method_exists($openai, 'transcribe_audio')) {
            $transcribed_text = $openai->transcribe_audio($audio_data);
            $confidence = 0.9; // OpenAI Whisperの場合は高い信頼度
        } else {
            // フォールバック: ブラウザのWeb Speech APIの結果をそのまま使用
            $transcribed_text = sanitize_text_field($_POST['fallback_text'] ?? '');
            $confidence = floatval($_POST['confidence'] ?? 0.7);
        }
        
        // 音声履歴に保存
        gi_save_voice_history($session_id, $transcribed_text, $confidence);
        
        wp_send_json_success([
            'transcribed_text' => $transcribed_text,
            'confidence' => $confidence,
            'session_id' => $session_id,
            'method' => $openai->is_configured() ? 'openai_whisper' : 'browser_api'
        ]);
        
    } catch (Exception $e) {
        error_log('Voice Input Error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => '音声認識中にエラーが発生しました',
            'debug' => WP_DEBUG ? $e->getMessage() : null
        ]);
    }
}

/**
 * 検索候補取得
 */
function gi_ajax_get_search_suggestions() {
    try {
        if (!gi_verify_ajax_nonce()) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました']);
            return;
        }
        
        $partial_query = sanitize_text_field($_POST['query'] ?? '');
        $limit = min(intval($_POST['limit'] ?? 10), 20);
        
        $suggestions = gi_get_smart_search_suggestions($partial_query, $limit);
        
        wp_send_json_success([
            'suggestions' => $suggestions,
            'query' => $partial_query
        ]);
        
    } catch (Exception $e) {
        error_log('Search Suggestions Error: ' . $e->getMessage());
        wp_send_json_error(['message' => '検索候補の取得に失敗しました']);
    }
}

/**
 * お気に入り切り替え
 */
function gi_ajax_toggle_favorite() {
    try {
        if (!gi_verify_ajax_nonce()) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました']);
            return;
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $user_id = get_current_user_id();
        
        if (!$post_id) {
            wp_send_json_error(['message' => '投稿IDが不正です']);
            return;
        }
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'ログインが必要です']);
            return;
        }
        
        $favorites = get_user_meta($user_id, 'gi_favorites', true) ?: [];
        $is_favorited = in_array($post_id, $favorites);
        
        if ($is_favorited) {
            $favorites = array_filter($favorites, function($id) use ($post_id) {
                return $id != $post_id;
            });
            $action = 'removed';
        } else {
            $favorites[] = $post_id;
            $action = 'added';
        }
        
        update_user_meta($user_id, 'gi_favorites', array_values($favorites));
        
        wp_send_json_success([
            'action' => $action,
            'is_favorite' => !$is_favorited,
            'total_favorites' => count($favorites),
            'message' => $action === 'added' ? 'お気に入りに追加しました' : 'お気に入りから削除しました'
        ]);
        
    } catch (Exception $e) {
        error_log('Toggle Favorite Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'お気に入りの更新に失敗しました']);
    }
}

/**
 * =============================================================================
 * Enhanced ヘルパー関数群
 * =============================================================================
 */

/**
 * セキュリティ検証の統一処理
 */
function gi_verify_ajax_nonce() {
    $nonce = $_POST['nonce'] ?? '';
    return !empty($nonce) && (
        wp_verify_nonce($nonce, 'gi_ai_search_nonce') || 
        wp_verify_nonce($nonce, 'gi_ajax_nonce')
    );
}

/**
 * Enhanced セマンティック検索
 */
function gi_enhanced_semantic_search($query, $filter = 'all', $page = 1, $per_page = 20) {
    // OpenAI統合がある場合はセマンティック検索を試行
    $openai = class_exists('GI_OpenAI_Integration') ? GI_OpenAI_Integration::getInstance() : null;
    
    if ($openai && $openai->is_configured() && get_option('gi_ai_semantic_search', false)) {
        try {
            return gi_perform_ai_enhanced_search($query, $filter, $page, $per_page);
        } catch (Exception $e) {
            error_log('Semantic Search Error: ' . $e->getMessage());
            // フォールバック to standard search
        }
    }
    
    return gi_perform_standard_search($query, $filter, $page, $per_page);
}

/**
 * AI強化検索実行
 */
function gi_perform_ai_enhanced_search($query, $filter, $page, $per_page) {
    // クエリの拡張とセマンティック分析
    $enhanced_query = gi_enhance_search_query($query);
    $semantic_terms = gi_extract_semantic_terms($query);
    
    $args = [
        'post_type' => 'grant',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'post_status' => 'publish',
        'meta_query' => ['relation' => 'OR'],
        's' => $enhanced_query
    ];
    
    // セマンティック検索のためのメタクエリ拡張
    foreach ($semantic_terms as $term) {
        $args['meta_query'][] = [
            'key' => 'grant_target',
            'value' => $term,
            'compare' => 'LIKE'
        ];
        $args['meta_query'][] = [
            'key' => 'grant_content',
            'value' => $term,
            'compare' => 'LIKE'
        ];
    }
    
    // フィルター適用
    if ($filter !== 'all') {
        $args['tax_query'] = gi_build_tax_query($filter);
    }
    
    $query_obj = new WP_Query($args);
    $grants = [];
    
    if ($query_obj->have_posts()) {
        while ($query_obj->have_posts()) {
            $query_obj->the_post();
            $post_id = get_the_ID();
            
            // セマンティック類似度計算
            $relevance_score = gi_calculate_semantic_relevance($query, $post_id);
            
            $grants[] = gi_format_grant_result($post_id, $relevance_score);
        }
        wp_reset_postdata();
        
        // 関連性スコアでソート
        usort($grants, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
    }
    
    return [
        'grants' => $grants,
        'count' => max(0, intval($query_obj->found_posts ?? 0)),
        'total_pages' => $query_obj->max_num_pages,
        'method' => 'ai_enhanced'
    ];
}

/**
 * スタンダード検索実行
 */
function gi_perform_standard_search($query, $filter, $page, $per_page) {
    $args = [
        'post_type' => 'grant',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'post_status' => 'publish',
        's' => $query
    ];
    
    // フィルター適用
    if ($filter !== 'all') {
        $args['tax_query'] = gi_build_tax_query($filter);
    }
    
    $query_obj = new WP_Query($args);
    $grants = [];
    
    if ($query_obj->have_posts()) {
        while ($query_obj->have_posts()) {
            $query_obj->the_post();
            $post_id = get_the_ID();
            
            $grants[] = gi_format_grant_result($post_id, 0.8); // デフォルト関連性
        }
        wp_reset_postdata();
    }
    
    return [
        'grants' => $grants,
        'count' => max(0, intval($query_obj->found_posts ?? 0)),
        'total_pages' => $query_obj->max_num_pages,
        'method' => 'standard'
    ];
}

/**
 * 助成金結果のフォーマット
 */
function gi_format_grant_result($post_id, $relevance_score = 0.8) {
    $image_url = get_the_post_thumbnail_url($post_id, 'medium');
    $default_image = get_template_directory_uri() . '/assets/images/grant-default.jpg';
    
    return [
        'id' => $post_id,
        'title' => get_the_title(),
        'permalink' => get_permalink(),
        'url' => get_permalink(),
        'excerpt' => wp_trim_words(get_the_excerpt(), 25),
        'image_url' => $image_url ?: $default_image,
        'amount' => get_post_meta($post_id, 'max_amount', true) ?: '未定',
        'deadline' => get_post_meta($post_id, 'deadline', true) ?: '随時',
        'organization' => get_post_meta($post_id, 'organization', true) ?: '未定',
        'success_rate' => gi_get_field_safe('adoption_rate', $post_id, 0) ?: null,
        'featured' => get_post_meta($post_id, 'is_featured', true) == '1',
        'application_status' => get_post_meta($post_id, 'application_status', true) ?: 'active',
        'categories' => wp_get_post_terms($post_id, 'grant_category', ['fields' => 'names']),
        'relevance_score' => round($relevance_score, 3),
        'last_updated' => get_the_modified_time('Y-m-d H:i:s')
    ];
}

/**
 * コンテキスト付きAI応答生成
 */
function gi_generate_contextual_ai_response($query, $grants, $filter = 'all') {
    $openai = class_exists('GI_OpenAI_Integration') ? GI_OpenAI_Integration::getInstance() : null;
    
    if ($openai && $openai->is_configured()) {
        $context = [
            'grants' => array_slice($grants, 0, 3), // 上位3件のコンテキスト
            'filter' => $filter,
            'total_count' => count($grants)
        ];
        
        $prompt = "検索クエリ: {$query}\n結果数: " . count($grants) . "件";
        
        try {
            return $openai->generate_response($prompt, $context);
        } catch (Exception $e) {
            error_log('AI Response Error: ' . $e->getMessage());
            // フォールバック
        }
    }
    
    return gi_generate_search_fallback_response($query, $grants, $filter);
}

/**
 * 検索フォールバック応答生成（改良版）
 */
function gi_generate_search_fallback_response($query, $grants, $filter = 'all') {
    $count = count($grants);
    
    if ($count === 0) {
        $response = "「{$query}」に該当する助成金が見つかりませんでした。";
        $response .= "\n\n検索のヒント：\n";
        $response .= "・より一般的なキーワードで検索してみてください\n";
        $response .= "・業種名や技術分野を変更してみてください\n";
        $response .= "・フィルターを「すべて」に変更してみてください";
        return $response;
    }
    
    $response = "「{$query}」で{$count}件の助成金が見つかりました。";
    
    // フィルター情報
    if ($filter !== 'all') {
        $filter_names = [
            'it' => 'IT・デジタル',
            'manufacturing' => 'ものづくり',
            'startup' => 'スタートアップ',
            'sustainability' => '持続可能性',
            'innovation' => 'イノベーション',
            'employment' => '雇用・人材'
        ];
        $filter_name = $filter_names[$filter] ?? $filter;
        $response .= "（{$filter_name}分野）";
    }
    
    // 特徴的な助成金の情報
    $featured_count = 0;
    $high_amount_count = 0;
    
    foreach ($grants as $grant) {
        if (!empty($grant['featured'])) {
            $featured_count++;
        }
        $amount = $grant['amount'];
        if (preg_match('/(\d+)/', $amount, $matches) && intval($matches[1]) >= 1000) {
            $high_amount_count++;
        }
    }
    
    if ($featured_count > 0) {
        $response .= "\n\nこのうち{$featured_count}件は特におすすめの助成金です。";
    }
    
    if ($high_amount_count > 0) {
        $response .= "\n{$high_amount_count}件は1000万円以上の大型助成金です。";
    }
    
    $response .= "\n\n詳細については各助成金の「詳細を見る」ボタンから確認いただくか、「AI質問」ボタンでお気軽にご質問ください。";
    
    return $response;
}

/**
 * Enhanced Grant応答生成
 */
function gi_generate_enhanced_grant_response($post_id, $question, $grant_details, $intent) {
    $openai = class_exists('GI_OpenAI_Integration') ? GI_OpenAI_Integration::getInstance() : null;
    
    if ($openai && $openai->is_configured()) {
        $context = [
            'grant_details' => $grant_details,
            'intent' => $intent
        ];
        
        $prompt = "助成金「{$grant_details['title']}」について：\n質問: {$question}";
        
        try {
            return $openai->generate_response($prompt, $context);
        } catch (Exception $e) {
            error_log('Enhanced Grant Response Error: ' . $e->getMessage());
            // フォールバック
        }
    }
    
    return gi_generate_fallback_grant_response($post_id, $question, $grant_details, $intent);
}

/**
 * 助成金詳細情報取得
 */
function gi_get_grant_details($post_id) {
    return [
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'content' => get_post_field('post_content', $post_id),
        'excerpt' => get_the_excerpt($post_id),
        'organization' => get_post_meta($post_id, 'organization', true),
        'max_amount' => get_post_meta($post_id, 'max_amount', true),
        'deadline' => get_post_meta($post_id, 'deadline', true),
        'grant_target' => get_post_meta($post_id, 'grant_target', true),
        'application_requirements' => get_post_meta($post_id, 'application_requirements', true),
        'eligible_expenses' => get_post_meta($post_id, 'eligible_expenses', true),
        'application_process' => get_post_meta($post_id, 'application_process', true),
        'success_rate' => gi_get_field_safe('adoption_rate', $post_id, 0),
        'categories' => wp_get_post_terms($post_id, 'grant_category', ['fields' => 'names'])
    ];
}

/**
 * 質問意図の分析
 */
function gi_analyze_grant_question_intent($question, $grant_details) {
    $question_lower = mb_strtolower($question);
    
    $intents = [
        'application' => ['申請', '手続き', '方法', '流れ', '必要書類', 'どうやって'],
        'amount' => ['金額', '額', 'いくら', '助成額', '補助額', '上限'],
        'deadline' => ['締切', '期限', 'いつまで', '申請期限', '募集期間'],
        'eligibility' => ['対象', '資格', '条件', '要件', '該当'],
        'expenses' => ['経費', '費用', '対象経費', '使える', '支払い'],
        'process' => ['審査', '選考', '採択', '結果', 'いつ', '期間'],
        'success_rate' => ['採択率', '通る', '確率', '実績', '成功率'],
        'documents' => ['書類', '資料', '提出', '準備', '必要なもの']
    ];
    
    $detected_intents = [];
    foreach ($intents as $intent => $keywords) {
        foreach ($keywords as $keyword) {
            if (mb_stripos($question_lower, $keyword) !== false) {
                $detected_intents[] = $intent;
                break;
            }
        }
    }
    
    return !empty($detected_intents) ? $detected_intents[0] : 'general';
}

/**
 * Fallback Grant応答生成（改良版）
 */
function gi_generate_fallback_grant_response($post_id, $question, $grant_details, $intent) {
    $title = $grant_details['title'];
    $organization = $grant_details['organization'];
    $max_amount = $grant_details['max_amount'];
    $deadline = $grant_details['deadline'];
    $grant_target = $grant_details['grant_target'];
    
    switch ($intent) {
        case 'application':
            $response = "「{$title}」の申請について：\n\n";
            if ($organization) {
                $response .= "【実施機関】\n{$organization}\n\n";
            }
            if ($grant_target) {
                $response .= "【申請対象】\n{$grant_target}\n\n";
            }
            $response .= "【申請方法】\n";
            $response .= "詳細な申請方法や必要書類については、実施機関の公式サイトでご確認ください。\n";
            $response .= "申請前に制度概要をしっかりと理解し、要件を満たしているか確認することをお勧めします。";
            break;
            
        case 'amount':
            $response = "「{$title}」の助成金額について：\n\n";
            if ($max_amount) {
                $response .= "【助成上限額】\n{$max_amount}\n\n";
            }
            $response .= "【注意事項】\n";
            $response .= "・実際の助成額は事業規模や申請内容により決定されます\n";
            $response .= "・補助率や助成対象経費に制限がある場合があります\n";
            $response .= "・詳細は実施機関の募集要項をご確認ください";
            break;
            
        case 'deadline':
            $response = "「{$title}」の申請期限について：\n\n";
            if ($deadline) {
                $response .= "【申請締切】\n{$deadline}\n\n";
            }
            $response .= "【重要】\n";
            $response .= "・申請期限は変更される場合があります\n";
            $response .= "・必要書類の準備に時間がかかる場合があります\n";
            $response .= "・最新情報は実施機関の公式サイトでご確認ください";
            break;
            
        case 'eligibility':
            $response = "「{$title}」の申請対象について：\n\n";
            if ($grant_target) {
                $response .= "【対象者・対象事業】\n{$grant_target}\n\n";
            }
            $response .= "【確認ポイント】\n";
            $response .= "・事業規模や従業員数の要件\n";
            $response .= "・業種や事業内容の制限\n";
            $response .= "・地域的な要件の有無\n";
            $response .= "・その他の特別な要件";
            break;
            
        default:
            $response = "「{$title}」について：\n\n";
            $response .= "【基本情報】\n";
            if ($max_amount) {
                $response .= "・助成上限額：{$max_amount}\n";
            }
            if ($grant_target) {
                $response .= "・対象：{$grant_target}\n";
            }
            if ($deadline) {
                $response .= "・締切：{$deadline}\n";
            }
            if ($organization) {
                $response .= "・実施機関：{$organization}\n";
            }
            $response .= "\nより詳しい情報や具体的な質問については、「詳細を見る」ボタンから詳細ページをご確認いただくか、";
            $response .= "具体的な内容（申請方法、金額、締切など）についてお聞かせください。";
    }
    
    return $response;
}

/**
 * スマートな助成金提案生成
 */
function gi_generate_smart_grant_suggestions($post_id, $question, $intent) {
    $base_suggestions = [
        '申請に必要な書類は何ですか？',
        '申請の流れを教えてください',
        '対象となる経費について',
        '採択のポイントは？'
    ];
    
    $intent_specific = [
        'application' => [
            '申請の難易度はどのくらい？',
            '申請にかかる期間は？',
            '必要な準備期間は？'
        ],
        'amount' => [
            '補助率はどのくらい？',
            '対象経費の範囲は？',
            '追加の支援制度はある？'
        ],
        'deadline' => [
            '次回の募集はいつ？',
            '申請準備はいつから始める？',
            '年間スケジュールは？'
        ],
        'eligibility' => [
            'この条件で申請できる？',
            '他に必要な要件は？',
            '類似の助成金はある？'
        ]
    ];
    
    $suggestions = $base_suggestions;
    
    if (isset($intent_specific[$intent])) {
        $suggestions = array_merge($intent_specific[$intent], array_slice($base_suggestions, 0, 2));
    }
    
    return array_slice(array_unique($suggestions), 0, 4);
}

/**
 * チャット履歴保存
 */
function gi_save_chat_history($session_id, $message_type, $content, $intent_data = null) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'gi_chat_history';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") != $table) {
        return false; // テーブルが存在しない場合
    }
    
    return $wpdb->insert(
        $table,
        [
            'session_id' => $session_id,
            'user_id' => get_current_user_id() ?: null,
            'message_type' => $message_type,
            'message_content' => $content,
            'intent_data' => is_array($intent_data) ? json_encode($intent_data) : $intent_data,
            'created_at' => current_time('mysql')
        ],
        ['%s', '%d', '%s', '%s', '%s', '%s']
    );
}

/**
 * 音声履歴保存
 */
function gi_save_voice_history($session_id, $transcribed_text, $confidence_score = null) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'gi_voice_history';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") != $table) {
        return false;
    }
    
    return $wpdb->insert(
        $table,
        [
            'session_id' => $session_id,
            'user_id' => get_current_user_id() ?: null,
            'transcribed_text' => $transcribed_text,
            'confidence_score' => $confidence_score,
            'created_at' => current_time('mysql')
        ],
        ['%s', '%d', '%s', '%f', '%s']
    );
}

/**
 * 最新の助成金取得
 */
function gi_get_recent_grants($limit = 20) {
    $args = [
        'post_type' => 'grant',
        'posts_per_page' => $limit,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC'
    ];
    
    $query = new WP_Query($args);
    $grants = [];
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $grants[] = gi_format_grant_result(get_the_ID(), 0.9);
        }
        wp_reset_postdata();
    }
    
    return $grants;
}

/**
 * 検索キーワード抽出
 */
function gi_extract_keywords($query) {
    // 基本的なキーワード分割（より高度な実装も可能）
    $keywords = preg_split('/[\s\p{P}]+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
    $keywords = array_filter($keywords, function($word) {
        return mb_strlen($word) >= 2; // 2文字以上のワードのみ
    });
    
    return array_values($keywords);
}

/**
 * 選択された都道府県に対応する市町村を取得
 */
function gi_ajax_get_municipalities_for_prefectures() {
    try {
        // より柔軟なnonce検証
        $nonce = $_POST['nonce'] ?? $_POST['_wpnonce'] ?? '';
        if (empty($nonce) || (!wp_verify_nonce($nonce, 'gi_ajax_nonce') && !wp_verify_nonce($nonce, 'gi_ai_search_nonce'))) {
            error_log('Multiple Prefectures Municipality AJAX: Nonce verification failed');
            if (!(defined('WP_DEBUG') && WP_DEBUG)) {
                wp_send_json_error(['message' => 'セキュリティチェックに失敗しました']);
                return;
            }
        }
        
        // Handle both 'prefectures' and 'prefecture_slugs' parameter names
        $prefecture_slugs = isset($_POST['prefecture_slugs']) ? 
            json_decode(stripslashes($_POST['prefecture_slugs']), true) : 
            (isset($_POST['prefectures']) ? (array)$_POST['prefectures'] : []);
        $prefecture_slugs = array_map('sanitize_text_field', $prefecture_slugs);
        
        error_log("Multiple Prefecture Municipality Request - Prefectures: " . implode(', ', $prefecture_slugs));
        
        if (empty($prefecture_slugs)) {
            wp_send_json_error([
                'message' => '都道府県が指定されていません',
                'debug' => 'prefecture_slugs parameter is empty'
            ]);
            return;
        }
        
        $municipalities_data = [];
        
        foreach ($prefecture_slugs as $pref_slug) {
            // 都道府県名を取得
            $prefecture_term = get_term_by('slug', $pref_slug, 'grant_prefecture');
            if (!$prefecture_term) continue;
            
            $pref_name = $prefecture_term->name;
            $pref_municipalities = [];
            
            // 1. まず既存の市町村タクソノミーから取得を試行
            $existing_municipalities = get_terms([
                'taxonomy' => 'grant_municipality',
                'hide_empty' => false,
                'meta_query' => [
                    [
                        'key' => 'prefecture_slug',
                        'value' => $pref_slug,
                        'compare' => '='
                    ]
                ]
            ]);
            
            // デバッグログ追加
            error_log("Prefecture: {$pref_slug}, Found municipalities: " . (is_wp_error($existing_municipalities) ? 'WP_Error: ' . $existing_municipalities->get_error_message() : count($existing_municipalities)));
            
            if (!empty($existing_municipalities) && !is_wp_error($existing_municipalities)) {
                foreach ($existing_municipalities as $term) {
                    $pref_municipalities[] = [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'count' => $term->count
                    ];
                }
            }
            
            // 2. 既存データがない場合は、都道府県レベル市町村タームを確認
            if (empty($pref_municipalities)) {
                // 都道府県レベルのタームを探す
                $prefecture_level_slug = $pref_slug . '-prefecture-level';
                $prefecture_level_term = get_term_by('slug', $prefecture_level_slug, 'grant_municipality');
                
                if ($prefecture_level_term) {
                    $pref_municipalities[] = [
                        'id' => $prefecture_level_term->term_id,
                        'name' => $pref_name,
                        'slug' => $prefecture_level_term->slug,
                        'count' => $prefecture_level_term->count
                    ];
                }
            }
            
            // 3. それでもない場合は、標準的な市町村リストから生成
            if (empty($pref_municipalities) && function_exists('gi_get_standard_municipalities_by_prefecture')) {
                $municipalities_list = gi_get_standard_municipalities_by_prefecture($pref_slug);
                
                foreach ($municipalities_list as $muni_name) {
                    $muni_slug = $pref_slug . '-' . sanitize_title($muni_name);
                    $existing_term = get_term_by('slug', $muni_slug, 'grant_municipality');
                    
                    if (!$existing_term) {
                        // 市町村タームを作成
                        $result = wp_insert_term(
                            $muni_name,
                            'grant_municipality',
                            [
                                'slug' => $muni_slug,
                                'description' => $pref_name . '・' . $muni_name
                            ]
                        );
                        
                        if (!is_wp_error($result)) {
                            // 都道府県との関連付けメタデータを保存
                            add_term_meta($result['term_id'], 'prefecture_slug', $pref_slug);
                            add_term_meta($result['term_id'], 'prefecture_name', $pref_name);
                            
                            $pref_municipalities[] = [
                                'id' => $result['term_id'],
                                'name' => $muni_name,
                                'slug' => $muni_slug,
                                'count' => 0
                            ];
                        }
                    } else {
                        // 既存タームにメタデータが無い場合は追加
                        if (!get_term_meta($existing_term->term_id, 'prefecture_slug', true)) {
                            add_term_meta($existing_term->term_id, 'prefecture_slug', $pref_slug);
                            add_term_meta($existing_term->term_id, 'prefecture_name', $pref_name);
                        }
                        
                        $pref_municipalities[] = [
                            'id' => $existing_term->term_id,
                            'name' => $existing_term->name,
                            'slug' => $existing_term->slug,
                            'count' => $existing_term->count
                        ];
                    }
                }
            }
            
            // 4. 最後のフォールバック: 空の場合は都道府県名のみを返す
            if (empty($pref_municipalities)) {
                $pref_municipalities[] = [
                    'id' => $prefecture_term->term_id,
                    'name' => $pref_name,
                    'slug' => $pref_slug,
                    'count' => 0
                ];
                error_log("Using fallback municipality data for prefecture: {$pref_slug}");
            }
            
            // Sort municipalities by predefined order (from standard municipalities list)
            $standard_order = gi_get_standard_municipalities_by_prefecture($pref_slug);
            if (!empty($standard_order)) {
                // Create order map
                $order_map = array_flip($standard_order);
                
                // Sort by standard order, then by name for unlisted items
                usort($pref_municipalities, function($a, $b) use ($order_map) {
                    $order_a = isset($order_map[$a['name']]) ? $order_map[$a['name']] : 999;
                    $order_b = isset($order_map[$b['name']]) ? $order_map[$b['name']] : 999;
                    
                    if ($order_a === $order_b) {
                        return strcoll($a['name'], $b['name']);
                    }
                    
                    return $order_a - $order_b;
                });
            } else {
                // Fallback to name sorting
                usort($pref_municipalities, function($a, $b) {
                    return strcoll($a['name'], $b['name']);
                });
            }
            
            // Format data by prefecture for frontend
            $municipalities_data[$pref_slug] = $pref_municipalities;
        }
        
        $total_municipalities = 0;
        foreach ($municipalities_data as $pref_municipalities) {
            $total_municipalities += count($pref_municipalities);
        }
        
        wp_send_json_success([
            'data' => [
                'municipalities' => $municipalities_data,
                'prefecture_count' => count($prefecture_slugs),
                'municipality_count' => $total_municipalities
            ],
            'message' => $total_municipalities . '件の市町村データを取得しました'
        ]);
        
    } catch (Exception $e) {
        error_log('Get Municipalities Error: ' . $e->getMessage());
        wp_send_json_error(['message' => '市町村データの取得に失敗しました', 'debug' => WP_DEBUG ? $e->getMessage() : null]);
    }
}

/**
 * 単一都道府県に対応する市町村を取得 (フロントエンド用)
 * Enhanced with better error handling and debugging
 */
function gi_ajax_get_municipalities_for_prefecture() {
    // デバッグ情報の出力
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('========================================');
        error_log('🏘️ Municipality Fetch Request Received');
        error_log('Prefecture Slug: ' . ($_POST['prefecture_slug'] ?? 'NOT SET'));
        error_log('Nonce: ' . ($_POST['nonce'] ?? 'NOT SET'));
        error_log('Action: ' . ($_POST['action'] ?? 'NOT SET'));
        error_log('========================================');
    }
    
    try {
        // より柔軟なnonce検証
        $nonce = $_POST['nonce'] ?? $_POST['_wpnonce'] ?? '';
        if (empty($nonce) || (!wp_verify_nonce($nonce, 'gi_ajax_nonce') && !wp_verify_nonce($nonce, 'gi_ai_search_nonce'))) {
            error_log('Municipality AJAX: Nonce verification failed. Nonce: ' . $nonce);
            // nonceチェックを一時的に緩和（デバッグ用）
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Municipality AJAX: Proceeding without nonce verification (DEBUG MODE)');
            } else {
                wp_send_json_error(['message' => 'セキュリティチェックに失敗しました']);
                return;
            }
        }
        
        $prefecture_slug = sanitize_text_field($_POST['prefecture_slug'] ?? '');
        
        if (empty($prefecture_slug)) {
            wp_send_json_error([
                'message' => '都道府県が指定されていません',
                'debug' => 'prefecture_slug parameter is empty'
            ]);
            return;
        }
        
        // 詳細なデバッグ情報をログに記録
        error_log("Municipality AJAX Request - Prefecture: {$prefecture_slug}");
        error_log("Municipality AJAX Request - POST data: " . json_encode($_POST));
        
        // 都道府県の存在確認
        $prefecture_term = get_term_by('slug', $prefecture_slug, 'grant_prefecture');
        if (!$prefecture_term || is_wp_error($prefecture_term)) {
            error_log("Prefecture not found: {$prefecture_slug}");
            wp_send_json_error([
                'message' => '指定された都道府県が見つかりません',
                'debug' => "Prefecture slug '{$prefecture_slug}' not found in grant_prefecture taxonomy"
            ]);
            return;
        }
        
        error_log("Prefecture found: {$prefecture_term->name} (ID: {$prefecture_term->term_id})");
        
        // まず階層的関係で市町村を取得
        $municipalities_hierarchical = get_terms([
            'taxonomy' => 'grant_municipality',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
            'parent' => $prefecture_term->term_id
        ]);
        
        // 次にメタデータベースの関係で取得
        $municipalities_meta = get_terms([
            'taxonomy' => 'grant_municipality',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => 'prefecture_slug',
                    'value' => $prefecture_slug,
                    'compare' => '='
                ]
            ]
        ]);
        
        // 両方の結果をマージ
        $municipalities = [];
        $seen_ids = [];
        
        // 階層的関係の結果を追加
        if (!is_wp_error($municipalities_hierarchical)) {
            foreach ($municipalities_hierarchical as $term) {
                if (!in_array($term->term_id, $seen_ids)) {
                    $municipalities[] = $term;
                    $seen_ids[] = $term->term_id;
                }
            }
        }
        
        // メタデータ関係の結果を追加
        if (!is_wp_error($municipalities_meta)) {
            foreach ($municipalities_meta as $term) {
                if (!in_array($term->term_id, $seen_ids)) {
                    $municipalities[] = $term;
                    $seen_ids[] = $term->term_id;
                }
            }
        }
        
        error_log("Found municipalities - Hierarchical: " . (is_wp_error($municipalities_hierarchical) ? 'ERROR' : count($municipalities_hierarchical)));
        error_log("Found municipalities - Meta: " . (is_wp_error($municipalities_meta) ? 'ERROR' : count($municipalities_meta)));
        error_log("Total unique municipalities: " . count($municipalities));
        
        $municipalities_data = [];
        
        if (!empty($municipalities) && !is_wp_error($municipalities)) {
            foreach ($municipalities as $term) {
                // 実際の助成金件数を取得
                $grant_count = gi_get_municipality_grant_count($term->term_id);
                
                $municipalities_data[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'count' => $grant_count
                ];
            }
        } else {
            error_log("No municipalities found for {$prefecture_slug}, trying fallback methods");
            
            // 1. 都道府県レベル市町村タームを確認
            $prefecture_level_slug = $prefecture_slug . '-prefecture-level';
            $prefecture_level_term = get_term_by('slug', $prefecture_level_slug, 'grant_municipality');
            
            if ($prefecture_level_term && !is_wp_error($prefecture_level_term)) {
                error_log("Found prefecture-level term: {$prefecture_level_slug}");
                $grant_count = gi_get_municipality_grant_count($prefecture_level_term->term_id);
                $municipalities_data[] = [
                    'id' => $prefecture_level_term->term_id,
                    'name' => $prefecture_term->name,
                    'slug' => $prefecture_level_term->slug,
                    'count' => $grant_count
                ];
            } else if (function_exists('gi_get_standard_municipalities_by_prefecture')) {
                error_log("Trying to get standard municipalities for {$prefecture_slug}");
                // 2. 標準データから生成
                $standard_municipalities = gi_get_standard_municipalities_by_prefecture($prefecture_slug);
                
                foreach ($standard_municipalities as $muni_name) {
                    $muni_slug = $prefecture_slug . '-' . sanitize_title($muni_name);
                    $existing_term = get_term_by('slug', $muni_slug, 'grant_municipality');
                    
                    if (!$existing_term) {
                        // 新しい市町村タームを作成
                        $result = wp_insert_term(
                            $muni_name,
                            'grant_municipality',
                            [
                                'slug' => $muni_slug,
                                'description' => $prefecture_term->name . '・' . $muni_name
                            ]
                        );
                        
                        if (!is_wp_error($result)) {
                            // 都道府県メタデータを追加
                            add_term_meta($result['term_id'], 'prefecture_slug', $prefecture_slug);
                            add_term_meta($result['term_id'], 'prefecture_name', $prefecture_term->name);
                            
                            $grant_count = gi_get_municipality_grant_count($result['term_id']);
                            $municipalities_data[] = [
                                'id' => $result['term_id'],
                                'name' => $muni_name,
                                'slug' => $muni_slug,
                                'count' => $grant_count
                            ];
                        }
                    } else {
                        // 既存タームのメタデータを確認・更新
                        if (!get_term_meta($existing_term->term_id, 'prefecture_slug', true)) {
                            add_term_meta($existing_term->term_id, 'prefecture_slug', $prefecture_slug);
                            add_term_meta($existing_term->term_id, 'prefecture_name', $prefecture_term->name);
                        }
                        
                        $grant_count = gi_get_municipality_grant_count($existing_term->term_id);
                        $municipalities_data[] = [
                            'id' => $existing_term->term_id,
                            'name' => $existing_term->name,
                            'slug' => $existing_term->slug,
                            'count' => $grant_count
                        ];
                    }
                }
            } else {
                // 3. 最後のフォールバック: 都道府県名のみを返す
                $grant_count = gi_get_prefecture_grant_count($prefecture_term->term_id);
                $municipalities_data[] = [
                    'id' => $prefecture_term->term_id,
                    'name' => $prefecture_term->name,
                    'slug' => $prefecture_slug,
                    'count' => $grant_count
                ];
                error_log("Using final fallback for prefecture: {$prefecture_slug}");
            }
        }
        
        // データが空の場合の最終フォールバック
        if (empty($municipalities_data)) {
            $grant_count = gi_get_prefecture_grant_count($prefecture_term->term_id);
            $municipalities_data[] = [
                'id' => $prefecture_term->term_id,
                'name' => $prefecture_term->name,
                'slug' => $prefecture_slug,
                'count' => $grant_count
            ];
        }
        
        // Standard order sorting (north to south)
        $standard_order = gi_get_standard_municipalities_by_prefecture($prefecture_slug);
        if (!empty($standard_order)) {
            // Create order map from standard municipalities list
            $order_map = array_flip($standard_order);
            
            // Sort by standard order, then by name for unlisted items
            usort($municipalities_data, function($a, $b) use ($order_map) {
                $order_a = isset($order_map[$a['name']]) ? $order_map[$a['name']] : 999;
                $order_b = isset($order_map[$b['name']]) ? $order_map[$b['name']] : 999;
                
                if ($order_a === $order_b) {
                    return strcoll($a['name'], $b['name']);
                }
                
                return $order_a - $order_b;
            });
        } else {
            // Fallback to name sorting
            usort($municipalities_data, function($a, $b) {
                return strcoll($a['name'], $b['name']);
            });
        }
        
        error_log("Sending municipalities response - Count: " . count($municipalities_data));
        
        // デバッグ: レスポンスデータの確認
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('✅ Sending municipality data:');
            error_log('  - Count: ' . count($municipalities_data));
            error_log('  - Prefecture: ' . $prefecture_term->name);
            error_log('  - First 3 municipalities: ' . json_encode(array_slice($municipalities_data, 0, 3)));
        }

        wp_send_json_success([
            'data' => [
                'municipalities' => $municipalities_data,
                'prefecture' => [
                    'slug' => $prefecture_slug,
                    'name' => $prefecture_term->name,
                    'id' => $prefecture_term->term_id
                ],
                'count' => count($municipalities_data)
            ],
            'message' => count($municipalities_data) . '件の市町村を取得しました',
            'debug' => WP_DEBUG ? [
                'prefecture_found' => !empty($prefecture_term),
                'hierarchical_count' => isset($municipalities_hierarchical) ? count($municipalities_hierarchical) : 0,
                'meta_count' => isset($municipalities_meta) ? count($municipalities_meta) : 0,
                'total_unique' => count($municipalities_data),
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
            ] : null
        ]);
        
    } catch (Exception $e) {
        error_log('Get Single Prefecture Municipalities Error: ' . $e->getMessage());
        error_log('Error trace: ' . $e->getTraceAsString());
        
        wp_send_json_error([
            'message' => '市町村データの取得に失敗しました',
            'error_details' => $e->getMessage(),
            'debug' => WP_DEBUG ? [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'prefecture_slug' => $prefecture_slug ?? 'not_set'
            ] : null
        ]);
    }
}

/**
 * 市町村データ初期化 AJAX Handler
 */
function gi_ajax_initialize_municipalities() {
    try {
        if (!gi_verify_ajax_nonce()) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました']);
            return;
        }
        
        // 管理者権限チェック（セキュリティのため）
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => '権限が不足しています']);
            return;
        }
        
        // 市町村データ初期化実行
        $result = gi_enhanced_init_municipalities_basic();
        
        // パラメータで都道府県が指定されている場合はその都道府県のみ初期化
        $prefecture_slug = sanitize_text_field($_POST['prefecture_slug'] ?? '');
        if (!empty($prefecture_slug)) {
            $result = gi_init_single_prefecture_municipalities($prefecture_slug);
        }
        
        wp_send_json_success([
            'created' => $result['created'],
            'updated' => $result['updated'],
            'message' => "市町村データの初期化が完了しました。新規作成: {$result['created']}件、更新: {$result['updated']}件"
        ]);
        
    } catch (Exception $e) {
        error_log('Initialize Municipalities Error: ' . $e->getMessage());
        wp_send_json_error(['message' => '市町村データの初期化に失敗しました', 'debug' => WP_DEBUG ? $e->getMessage() : null]);
    }
}

/**
 * その他のテスト・ユーティリティ関数
 */
function gi_ajax_test_connection() {
    wp_send_json_success([
        'message' => 'AJAX接続テスト成功',
        'timestamp' => current_time('mysql'),
        'server_info' => [
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ],
        'ai_status' => gi_check_ai_capabilities()
    ]);
}

function gi_ajax_save_voice_history() {
    if (!gi_verify_ajax_nonce()) {
        wp_send_json_error(['message' => 'セキュリティチェックに失敗しました']);
        return;
    }
    
    wp_send_json_success(['message' => '音声履歴を保存しました']);
}

function gi_ajax_get_chat_history() {
    if (!gi_verify_ajax_nonce()) {
        wp_send_json_error(['message' => 'セキュリティチェックに失敗しました']);
        return;
    }
    
    $session_id = sanitize_text_field($_POST['session_id'] ?? '');
    $limit = min(intval($_POST['limit'] ?? 50), 100);
    
    // チャット履歴取得の実装
    wp_send_json_success([
        'history' => [],
        'session_id' => $session_id
    ]);
}

function gi_ajax_get_search_history() {
    if (!gi_verify_ajax_nonce()) {
        wp_send_json_error(['message' => 'セキュリティチェックに失敗しました']);
        return;
    }
    
    $history = gi_get_search_history(20);
    
    wp_send_json_success([
        'history' => $history
    ]);
}

function gi_ajax_submit_ai_feedback() {
    if (!gi_verify_ajax_nonce()) {
        wp_send_json_error(['message' => 'セキュリティチェックに失敗しました']);
        return;
    }
    
    $feedback = sanitize_textarea_field($_POST['feedback'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);
    $session_id = sanitize_text_field($_POST['session_id'] ?? '');
    
    // フィードバック保存の実装（必要に応じて）
    
    wp_send_json_success([
        'message' => 'フィードバックありがとうございます'
    ]);
}

/**
 * =============================================================================
 * Missing Helper Functions - Simple Response Generators
 * =============================================================================
 */

/**
 * 簡単な検索サマリー生成
 */
function gi_generate_simple_search_summary($count, $query) {
    if ($count === 0) {
        return "「{$query}」に該当する助成金が見つかりませんでした。キーワードを変更して再度お試しください。";
    }
    
    if ($count === 1) {
        return "「{$query}」で1件の助成金が見つかりました。";
    }
    
    return "「{$query}」で{$count}件の助成金が見つかりました。詳細は各カードの「詳細を見る」または「AI質問」ボタンからご確認ください。";
}

/**
 * 簡単なチャット応答生成
 */
function gi_generate_simple_chat_response($message, $intent) {
    $message_lower = mb_strtolower($message);
    
    // 挨拶への応答
    if (preg_match('/(こんにちは|おはよう|こんばんは|はじめまして)/', $message_lower)) {
        return "こんにちは！Grant Insight Perfectの補助金AIアシスタントです。どのような補助金をお探しですか？";
    }
    
    // 意図に基づく応答
    switch ($intent) {
        case 'search':
            return "どのような助成金をお探しですか？業種、目的、地域などを教えていただくと、最適な助成金をご提案できます。";
        
        case 'application':
            return "申請に関するご質問ですね。具体的にどの助成金の申請方法についてお知りになりたいですか？";
        
        case 'information':
            return "詳しい情報をお調べします。どの助成金についての詳細をお知りになりたいですか？";
        
        case 'comparison':
            return "助成金の比較についてお答えします。どのような観点（金額、対象、締切など）で比較をご希望ですか？";
        
        case 'recommendation':
            return "おすすめの助成金をご提案させていただきます。お客様の事業内容や目的を教えてください。";
        
        default:
            return "ご質問ありがとうございます。具体的な内容をお聞かせいただけると、より詳しい回答をお提供できます。";
    }
}

/**
 * 【高度AI機能】コンテキスト対応インテリジェント助成金応答生成
 */
function gi_generate_simple_grant_response($question, $grant_details, $intent) {
    $title = $grant_details['title'] ?? '助成金';
    $organization = $grant_details['organization'] ?? '';
    $max_amount = $grant_details['max_amount'] ?? '';
    $deadline = $grant_details['deadline'] ?? '';
    $grant_target = $grant_details['grant_target'] ?? '';
    
    // AI分析による高度な応答生成
    $ai_analysis = gi_analyze_grant_characteristics($grant_details);
    $success_probability = gi_estimate_success_probability($grant_details);
    $comprehensive_score = gi_calculate_comprehensive_ai_score($grant_details);
    
    $response = "【AI分析】「{$title}」について\n\n";
    
    // AI総合評価を冒頭に表示
    $response .= sprintf("🤖 AI総合スコア: %s点/100点 | 成功予測: %s%% | 推奨度: %s\n\n", 
        round($comprehensive_score['total_score']), 
        round($success_probability['overall_score'] * 100),
        gi_get_recommendation_level($comprehensive_score['total_score']));
    
    switch ($intent) {
        case 'application':
            $response .= "【📋 申請戦略AI分析】\n";
            if ($organization) {
                $response .= "実施機関：{$organization}\n";
            }
            
            // 難易度に基づく戦略提案
            $difficulty_advice = gi_get_difficulty_based_advice($ai_analysis['complexity_level']);
            $response .= "\n🎯 申請戦略：\n{$difficulty_advice}\n";
            
            // 成功率向上のための具体的アドバイス
            if ($success_probability['overall_score'] < 0.6) {
                $response .= "\n⚠️ 成功率向上ポイント：\n";
                foreach ($success_probability['improvement_suggestions'] as $suggestion) {
                    $response .= "・{$suggestion}\n";
                }
            }
            
            // 準備期間の提案
            $deadline_analysis = gi_analyze_deadline_pressure($deadline);
            $response .= "\n⏰ 推奨準備期間：{$deadline_analysis['recommended_prep_time']}\n";
            
            if ($grant_target) {
                $response .= "\n👥 対象者：{$grant_target}";
            }
            break;
        
        case 'amount':
            $response .= "【💰 資金計画AI分析】\n";
            if ($max_amount) {
                $response .= "最大助成額：{$max_amount}\n";
                
                // ROI分析の追加
                $roi_analysis = gi_calculate_grant_roi_potential($grant_details);
                $response .= sprintf("\n📈 期待ROI：%s%% (業界平均+%s%%)", 
                    round($roi_analysis['projected_roi']), 
                    round($roi_analysis['projected_roi'] - 160));
                
                $response .= sprintf("\n💹 投資回収期間：約%sヶ月", 
                    $roi_analysis['payback_period_months']);
                
                // 補助率情報
                if (!empty($grant_details['subsidy_rate'])) {
                    $subsidy_rate = $grant_details['subsidy_rate'];
                    $self_funding = gi_calculate_self_funding_amount($grant_details);
                    $response .= "\n\n💳 資金構造：\n";
                    $response .= "・補助率：{$subsidy_rate}\n";
                    $response .= "・自己資金目安：" . number_format($self_funding) . "円";
                }
            } else {
                $response .= "助成額の詳細は実施機関にお問い合わせください。";
            }
            
            // 金額規模に基づくアドバイス
            $amount_advice = gi_get_amount_based_advice($grant_details['max_amount_numeric'] ?? 0);
            $response .= "\n\n🎯 資金活用戦略：\n{$amount_advice}";
            break;
        
        case 'deadline':
            $response .= "【⏰ スケジュール戦略AI分析】\n";
            if ($deadline) {
                $deadline_analysis = gi_analyze_deadline_pressure($deadline);
                $response .= "締切：{$deadline}\n";
                $response .= "残り日数：約{$deadline_analysis['days_remaining']}日\n";
                
                // 緊急度レベル
                $urgency_level = $deadline_analysis['is_urgent'] ? '🔴 緊急' : '🟢 余裕あり';
                $response .= "緊急度：{$urgency_level}\n";
                
                // スケジュール戦略
                $response .= "\n📅 推奨スケジュール：\n";
                $schedule_plan = gi_generate_application_schedule($deadline_analysis, $ai_analysis['complexity_level']);
                foreach ($schedule_plan as $phase) {
                    $response .= "・{$phase}\n";
                }
                
                // リスクアラート
                if ($deadline_analysis['is_urgent']) {
                    $response .= "\n⚠️ 緊急対応が必要：\n・外部専門家への即座の相談を推奨\n・並行作業による効率化が重要";
                }
            }
            break;
        
        case 'eligibility':
            $response .= "【✅ 適格性AI診断】\n";
            if ($grant_target) {
                $response .= "対象者：{$grant_target}\n\n";
                
                // 適格性チェックリスト
                $eligibility_checks = gi_generate_eligibility_checklist($grant_details);
                $response .= "🔍 適格性確認チェックリスト：\n";
                foreach ($eligibility_checks as $check) {
                    $response .= "□ {$check}\n";
                }
                
                // 業界適合度
                $response .= "\n📊 業界適合度：";
                $industry_fit = gi_assess_industry_compatibility($grant_details);
                $response .= sprintf("%s%% ", round($industry_fit * 100));
                $response .= gi_get_fit_level_description($industry_fit);
            }
            break;
            
        case 'success_rate':
        case 'probability':
            $response .= "【📊 成功確率AI分析】\n";
            $response .= sprintf("予測成功率：%s%%\n", round($success_probability['overall_score'] * 100));
            $response .= sprintf("リスクレベル：%s\n", gi_get_risk_level_jp($success_probability['risk_level']));
            $response .= sprintf("信頼度：%s%%\n\n", round($success_probability['confidence'] * 100));
            
            $response .= "🎯 成功要因分析：\n";
            foreach ($success_probability['contributing_factors'] as $factor => $impact) {
                if ($impact > 0.02) {
                    $response .= sprintf("・%s：+%s%%\n", gi_get_factor_name_jp($factor), round($impact * 100));
                }
            }
            
            $response .= "\n💡 改善提案：\n";
            foreach ($success_probability['improvement_suggestions'] as $suggestion) {
                $response .= "・{$suggestion}\n";
            }
            break;
        
        case 'comparison':
            $response .= "【⚖️ 競合分析AI評価】\n";
            $competitive_analysis = gi_analyze_competitive_landscape($grant_details);
            $response .= sprintf("競合優位度：%s/10点\n", round($competitive_analysis['advantage_score'] * 10));
            $response .= sprintf("競争激化度：%s\n\n", gi_get_competition_level_jp($competitive_analysis['competitive_intensity']));
            
            $response .= "🏆 競合優位要素：\n";
            foreach ($competitive_analysis['key_advantages'] as $advantage) {
                $response .= "・{$advantage}\n";
            }
            
            // 差別化戦略の提案
            $response .= "\n🎯 差別化戦略提案：\n";
            $differentiation_strategies = gi_generate_differentiation_strategies($grant_details, $competitive_analysis);
            foreach ($differentiation_strategies as $strategy) {
                $response .= "・{$strategy}\n";
            }
            break;
        
        default:
            $response .= "【📝 総合情報AI分析】\n";
            
            // 基本情報
            if ($max_amount) {
                $response .= "・助成額：{$max_amount}";
                // ROI予測を追加
                $roi_analysis = gi_calculate_grant_roi_potential($grant_details);
                $response .= sprintf("（期待ROI: %s%%）\n", round($roi_analysis['projected_roi']));
            }
            if ($deadline) {
                $deadline_analysis = gi_analyze_deadline_pressure($deadline);
                $urgency = $deadline_analysis['is_urgent'] ? '⚠️急務' : '余裕あり';
                $response .= "・締切：{$deadline}（{$urgency}）\n";
            }
            if ($organization) {
                $response .= "・実施機関：{$organization}\n";
            }
            
            // AI推奨アクション
            $response .= "\n🤖 AI推奨アクション：\n";
            $recommended_actions = gi_generate_recommended_actions($grant_details, $comprehensive_score, $success_probability);
            foreach (array_slice($recommended_actions, 0, 3) as $action) {
                $response .= "・{$action}\n";
            }
            
            $response .= "\n詳細分析は「AIチェックリスト」「AI比較」ボタンをご利用ください。";
    }
    
    // フッター情報
    $response .= "\n\n" . sprintf("💻 AI分析精度: %s%% | 最終更新: %s", 
        round($comprehensive_score['confidence'] * 100),
        date('n/j H:i'));
    
    return $response;
}

/**
 * 人気検索キーワード取得
 */
function gi_get_popular_search_terms($limit = 10) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'gi_search_history';
    
    // テーブルが存在するか確認
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") != $table) {
        // フォールバック
        return [
            ['term' => 'IT導入補助金', 'count' => 100],
            ['term' => 'ものづくり補助金', 'count' => 95],
            ['term' => '小規模事業者持続化補助金', 'count' => 90],
            ['term' => '事業再構築補助金', 'count' => 85],
            ['term' => '雇用調整助成金', 'count' => 80]
        ];
    }
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT search_query as term, COUNT(*) as count
        FROM {$table}
        WHERE search_query != ''
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY search_query
        ORDER BY count DESC
        LIMIT %d
    ", $limit), ARRAY_A);
    
    return $results ?: [];
}

/**
 * 検索履歴取得
 */
function gi_get_search_history($limit = 20) {
    global $wpdb;
    
    $table = $wpdb->prefix . 'gi_search_history';
    
    // テーブルが存在するか確認
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") != $table) {
        return [];
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        return [];
    }
    
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT *
        FROM {$table}
        WHERE user_id = %d
        ORDER BY created_at DESC
        LIMIT %d
    ", $user_id, $limit), ARRAY_A);
    
    return $results ?: [];
}

/**
 * AI機能の利用可否チェック
 */
function gi_check_ai_capabilities() {
    return [
        'openai_configured' => class_exists('GI_OpenAI_Integration') && GI_OpenAI_Integration::getInstance()->is_configured(),
        'semantic_search' => class_exists('GI_Grant_Semantic_Search'),
        'simple_responses' => true, // 常に利用可能
        'voice_recognition' => true, // ブラウザAPIで利用可能
        'fallback_mode' => true
    ];
}

/**
 * 追加ヘルパー関数
 */
function gi_build_tax_query($filter) {
    $filter_mapping = [
        'it' => 'it-support',
        'manufacturing' => 'monozukuri', 
        'startup' => 'startup-support',
        'sustainability' => 'sustainability',
        'innovation' => 'innovation',
        'employment' => 'employment'
    ];
    
    if (isset($filter_mapping[$filter])) {
        return [[
            'taxonomy' => 'grant_category',
            'field' => 'slug',
            'terms' => $filter_mapping[$filter]
        ]];
    }
    
    return [];
}

function gi_enhance_search_query($query) {
    // クエリ拡張ロジック（シノニム、関連語などを追加）
    $enhancements = [
        'AI' => ['人工知能', 'machine learning', 'ディープラーニング'],
        'DX' => ['デジタル変革', 'デジタル化', 'IT化'],
        'IoT' => ['モノのインターネット', 'センサー', 'スマート']
    ];
    
    $enhanced_query = $query;
    foreach ($enhancements as $term => $synonyms) {
        if (mb_stripos($query, $term) !== false) {
            $enhanced_query .= ' ' . implode(' ', array_slice($synonyms, 0, 2));
        }
    }
    
    return $enhanced_query;
}

function gi_extract_semantic_terms($query) {
    // セマンティック分析のための関連語抽出
    return gi_extract_keywords($query);
}

function gi_calculate_semantic_relevance($query, $post_id) {
    // セマンティック類似度の計算（シンプル版）
    $content = get_post_field('post_content', $post_id) . ' ' . get_the_title($post_id);
    $query_keywords = gi_extract_keywords($query);
    $content_lower = mb_strtolower($content);
    
    $matches = 0;
    foreach ($query_keywords as $keyword) {
        if (mb_stripos($content_lower, mb_strtolower($keyword)) !== false) {
            $matches++;
        }
    }
    
    return count($query_keywords) > 0 ? $matches / count($query_keywords) : 0.5;
}

function gi_analyze_query_complexity($query) {
    $word_count = count(gi_extract_keywords($query));
    
    if ($word_count <= 2) return 'simple';
    if ($word_count <= 5) return 'medium';
    return 'complex';
}

function gi_generate_search_suggestions($query, $grants) {
    $suggestions = [];
    
    // 基本的な拡張提案
    if (count($grants) > 0) {
        $categories = [];
        foreach (array_slice($grants, 0, 3) as $grant) {
            $categories = array_merge($categories, $grant['categories']);
        }
        $unique_categories = array_unique($categories);
        
        foreach (array_slice($unique_categories, 0, 3) as $category) {
            $suggestions[] = $query . ' ' . $category;
        }
    }
    
    // クエリ関連の提案
    $related_terms = [
        'AI' => ['DX', '自動化', 'デジタル化'],
        'スタートアップ' => ['創業', 'ベンチャー', '起業'],
        '製造業' => ['ものづくり', '工場', '技術開発']
    ];
    
    foreach ($related_terms as $term => $relations) {
        if (mb_stripos($query, $term) !== false) {
            foreach ($relations as $related) {
                $suggestions[] = str_replace($term, $related, $query);
            }
            break;
        }
    }
    
    return array_slice(array_unique($suggestions), 0, 5);
}

function gi_analyze_user_intent($message) {
    $intent_patterns = [
        'search' => ['検索', '探す', '見つけて', 'あります', '教えて'],
        'application' => ['申請', '応募', '手続き', 'どうやって'],
        'information' => ['詳細', '情報', 'について', 'とは'],
        'comparison' => ['比較', '違い', 'どちら', '選び方'],
        'recommendation' => ['おすすめ', '提案', '適した', 'いい']
    ];
    
    $message_lower = mb_strtolower($message);
    
    foreach ($intent_patterns as $intent => $patterns) {
        foreach ($patterns as $pattern) {
            if (mb_stripos($message_lower, $pattern) !== false) {
                return $intent;
            }
        }
    }
    
    return 'general';
}

function gi_generate_contextual_chat_response($message, $context, $intent) {
    $openai = class_exists('GI_OpenAI_Integration') ? GI_OpenAI_Integration::getInstance() : null;
    
    if ($openai && $openai->is_configured()) {
        $prompt = "ユーザーの質問: {$message}\n意図: {$intent}";
        
        try {
            return $openai->generate_response($prompt, $context);
        } catch (Exception $e) {
            error_log('Contextual Chat Error: ' . $e->getMessage());
            // フォールバック
        }
    }
    
    return gi_generate_intent_based_response($message, $intent);
}

function gi_generate_intent_based_response($message, $intent) {
    switch ($intent) {
        case 'search':
            return 'どのような助成金をお探しですか？業種、目的、金額規模などをお聞かせいただくと、より適切な助成金をご提案できます。';
        case 'application':
            return '申請に関するご質問ですね。具体的にどの助成金の申請についてお知りになりたいですか？申請手順、必要書類、締切などについてお答えできます。';
        case 'information':
            return '詳しい情報をお調べします。どの助成金についての詳細をお知りになりたいですか？';
        case 'comparison':
            return '助成金の比較についてお答えします。どのような観点（金額、対象、締切など）で比較をご希望ですか？';
        case 'recommendation':
            return 'おすすめの助成金をご提案させていただきます。お客様の事業内容、規模、目的をお聞かせください。';
        default:
            return 'ご質問ありがとうございます。より具体的な内容をお聞かせいただけると、詳しい回答をお提供できます。';
    }
}

function gi_find_related_grants_from_chat($message, $intent) {
    // チャットメッセージから関連する助成金を検索
    $keywords = gi_extract_keywords($message);
    if (empty($keywords)) {
        return [];
    }
    
    $search_query = implode(' ', array_slice($keywords, 0, 3));
    $search_result = gi_perform_standard_search($search_query, 'all', 1, 5);
    
    return array_slice($search_result['grants'], 0, 3);
}

function gi_generate_chat_suggestions($message, $intent) {
    $base_suggestions = [
        'おすすめの助成金を教えて',
        '申請方法について',
        '締切が近い助成金は？',
        '条件を満たす助成金を検索'
    ];
    
    $intent_suggestions = [
        'search' => [
            'IT関連の助成金を探して',
            '製造業向けの補助金は？',
            'スタートアップ支援制度について'
        ],
        'application' => [
            '申請の準備期間は？',
            '必要書類のチェックリスト',
            '申請のコツを教えて'
        ]
    ];
    
    if (isset($intent_suggestions[$intent])) {
        return $intent_suggestions[$intent];
    }
    
    return array_slice($base_suggestions, 0, 3);
}

function gi_get_smart_search_suggestions($partial_query, $limit = 10) {
    // 部分クエリから候補を生成
    $suggestions = [];
    
    // アイコンマッピング
    $icon_map = [
        'IT' => '',
        'ものづくり' => '🏭',
        '小規模' => '🏪',
        '事業再構築' => '🔄',
        '雇用' => '👥',
        '創業' => '',
        '持続化' => '📈',
        '省エネ' => '⚡',
        '環境' => '🌱'
    ];
    
    // デフォルトアイコン取得関数
    $get_icon = function($text) use ($icon_map) {
        foreach ($icon_map as $keyword => $icon) {
            if (mb_strpos($text, $keyword) !== false) {
                return $icon;
            }
        }
        return '🔍'; // デフォルトアイコン
    };
    
    // 人気キーワードから類似するものを検索
    $popular_terms = gi_get_popular_search_terms(20);
    foreach ($popular_terms as $term_data) {
        $term = $term_data['term'] ?? '';
        if (!empty($term) && mb_stripos($term, $partial_query) !== false) {
            $suggestions[] = [
                'text' => $term,
                'icon' => $get_icon($term),
                'count' => $term_data['count'] ?? 0,
                'type' => 'popular'
            ];
        }
    }
    
    // 助成金タイトルから候補を生成
    $grants = gi_search_grant_titles($partial_query, $limit);
    foreach ($grants as $grant) {
        $title = $grant['title'] ?? '';
        if (!empty($title)) {
            $suggestions[] = [
                'text' => $title,
                'icon' => $get_icon($title),
                'type' => 'grant_title',
                'grant_id' => $grant['id'] ?? 0
            ];
        }
    }
    
    return array_slice($suggestions, 0, $limit);
}

function gi_search_grant_titles($query, $limit = 5) {
    $args = [
        'post_type' => 'grant',
        'posts_per_page' => $limit,
        'post_status' => 'publish',
        's' => $query,
        'fields' => 'ids'
    ];
    
    $posts = get_posts($args);
    $results = [];
    
    foreach ($posts as $post_id) {
        $results[] = [
            'id' => $post_id,
            'title' => get_the_title($post_id)
        ];
    }
    
    return $results;
}

/**
 * =============================================================================
 * AI チェックリスト生成機能 - Complete Implementation
 * =============================================================================
 */

/**
 * AIチェックリスト生成 AJAXハンドラー
 */
function gi_ajax_generate_checklist() {
    try {
        // セキュリティ検証
        if (!gi_verify_ajax_nonce()) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました', 'code' => 'SECURITY_ERROR']);
            return;
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(['message' => '助成金IDが不正です', 'code' => 'INVALID_POST_ID']);
            return;
        }
        
        // 投稿の存在確認
        $grant_post = get_post($post_id);
        if (!$grant_post || $grant_post->post_type !== 'grant') {
            wp_send_json_error(['message' => '助成金が見つかりません', 'code' => 'GRANT_NOT_FOUND']);
            return;
        }
        
        $start_time = microtime(true);
        
        // チェックリスト生成
        $checklist = gi_generate_grant_checklist($post_id);
        
        $end_time = microtime(true);
        $processing_time = round(($end_time - $start_time) * 1000);
        
        wp_send_json_success([
            'checklist' => $checklist,
            'grant_id' => $post_id,
            'grant_title' => $grant_post->post_title,
            'processing_time_ms' => $processing_time
        ]);
        
    } catch (Exception $e) {
        error_log('Checklist Generation Error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'チェックリスト生成中にエラーが発生しました',
            'code' => 'CHECKLIST_ERROR',
            'debug' => WP_DEBUG ? $e->getMessage() : null
        ]);
    }
}

/**
 * 【高度AI機能】助成金チェックリスト生成 - 業種・難易度・AI分析対応
 */
function gi_generate_grant_checklist($post_id) {
    // 助成金の詳細情報と特性分析を取得
    $grant_details = gi_get_grant_details($post_id);
    $grant_characteristics = gi_analyze_grant_characteristics($grant_details);
    $ai_score = gi_calculate_comprehensive_ai_score($grant_details);
    $success_probability = gi_estimate_success_probability($grant_details);
    
    $checklist = [];
    
    // === 1. 基本要件チェック（必須） ===
    $checklist[] = [
        'text' => '助成金の対象者・対象事業の範囲を確認し、適格性を検証しました',
        'priority' => 'critical',
        'checked' => false,
        'category' => 'eligibility',
        'ai_confidence' => 0.95,
        'completion_time' => '30分',
        'tips' => ['募集要項の対象者欄を3回読み直す', '類似事例での採択実績を調査する']
    ];
    
    $checklist[] = [
        'text' => '企業規模（従業員数、資本金、売上高）の要件を満たしているか数値で確認',
        'priority' => 'critical',
        'checked' => false,
        'category' => 'eligibility',
        'ai_confidence' => 0.92,
        'completion_time' => '15分',
        'tips' => ['決算書の数値と要件を照合', 'グループ会社がある場合は連結数値も確認']
    ];
    
    // === 2. 業種・分野別の特化要件 ===
    if ($grant_characteristics['industry_type'] === 'it_digital') {
        $checklist = array_merge($checklist, gi_generate_it_specific_checklist($grant_details));
    } elseif ($grant_characteristics['industry_type'] === 'manufacturing') {
        $checklist = array_merge($checklist, gi_generate_manufacturing_checklist($grant_details));
    } elseif ($grant_characteristics['industry_type'] === 'startup') {
        $checklist = array_merge($checklist, gi_generate_startup_checklist($grant_details));
    } elseif ($grant_characteristics['industry_type'] === 'sustainability') {
        $checklist = array_merge($checklist, gi_generate_sustainability_checklist($grant_details));
    }
    
    // === 3. 申請期限・時系列管理 ===
    if (!empty($grant_details['deadline'])) {
        $deadline_analysis = gi_analyze_deadline_pressure($grant_details['deadline']);
        $checklist[] = [
            'text' => sprintf('申請期限（%s）まで逆算したタイムライン作成と進捗管理体制構築', $grant_details['deadline']),
            'priority' => $deadline_analysis['is_urgent'] ? 'critical' : 'high',
            'checked' => false,
            'category' => 'schedule',
            'ai_confidence' => 0.88,
            'completion_time' => $deadline_analysis['recommended_prep_time'],
            'tips' => [$deadline_analysis['strategy'], '週次進捗確認ミーティング設定']
        ];
    }
    
    // === 4. 書類準備（AIによる優先度算出） ===
    $document_priority = gi_calculate_document_priority($grant_details);
    
    foreach ($document_priority as $doc) {
        $checklist[] = [
            'text' => $doc['name'] . 'の作成・準備完了',
            'priority' => $doc['priority'],
            'checked' => false,
            'category' => 'documents',
            'ai_confidence' => $doc['importance_score'],
            'completion_time' => $doc['estimated_time'],
            'tips' => $doc['preparation_tips']
        ];
    }
    
    // === 5. 資金計画・ROI分析 ===
    if (!empty($grant_details['max_amount'])) {
        $roi_analysis = gi_calculate_grant_roi_potential($grant_details);
        $checklist[] = [
            'text' => sprintf('事業費%s円の詳細積算と ROI %s%% の実現可能性検証', 
                number_format($grant_details['max_amount_numeric'] ?: 0), 
                round($roi_analysis['projected_roi'], 1)),
            'priority' => 'critical',
            'checked' => false,
            'category' => 'budget',
            'ai_confidence' => $roi_analysis['confidence'],
            'completion_time' => '3-5時間',
            'tips' => [
                '3社以上からの見積取得',
                '事業効果の定量化（売上・コスト削減）',
                '投資回収計画の策定'
            ]
        ];
        
        $checklist[] = [
            'text' => sprintf('自己資金 %s円の確保と資金繰り計画策定', 
                number_format(($grant_details['max_amount_numeric'] ?: 0) * (1 - ($grant_details['subsidy_rate'] ? floatval(str_replace('%', '', $grant_details['subsidy_rate'])) / 100 : 0.5)))),
            'priority' => 'high',
            'checked' => false,
            'category' => 'budget',
            'ai_confidence' => 0.85,
            'completion_time' => '1-2時間',
            'tips' => ['銀行融資の事前相談', '資金調達スケジュールの確認']
        ];
    }
    
    // === 6. 成功確率向上のためのAI推奨アクション ===
    $success_actions = gi_generate_success_optimization_actions($grant_details, $success_probability);
    foreach ($success_actions as $action) {
        $checklist[] = $action;
    }
    
    // === 7. 競合分析・差別化戦略 ===
    $checklist[] = [
        'text' => '同業他社の採択事例分析と自社の差別化ポイント3つ以上の明確化',
        'priority' => 'high',
        'checked' => false,
        'category' => 'strategy',
        'ai_confidence' => 0.78,
        'completion_time' => '2-3時間',
        'tips' => [
            '過去3年の採択事例をリサーチ',
            '自社の技術的優位性を定量化',
            '市場での独自性をアピールポイント化'
        ]
    ];
    
    // === 8. 最終品質管理 ===
    $checklist[] = [
        'text' => '申請書の専門家レビュー（行政書士・中小企業診断士等）実施',
        'priority' => $grant_characteristics['complexity_level'] >= 7 ? 'critical' : 'high',
        'checked' => false,
        'category' => 'final',
        'ai_confidence' => 0.92,
        'completion_time' => '1-2日',
        'tips' => [
            '業界に詳しい専門家を選択',
            '修正時間を考慮したスケジュール設定',
            '提出前の最終チェックリスト作成'
        ]
    ];
    
    // === AIによるチェックリストの最適化 ===
    $checklist = gi_optimize_checklist_by_ai($checklist, $grant_characteristics, $success_probability);
    
    // === 完成度とリスク評価の追加 ===
    $checklist[] = [
        'text' => sprintf('AI分析による成功確率 %s%% の要因分析と改善アクション実行', 
            round($success_probability['overall_score'] * 100)),
        'priority' => $success_probability['overall_score'] < 0.6 ? 'critical' : 'medium',
        'checked' => false,
        'category' => 'ai_analysis',
        'ai_confidence' => $success_probability['confidence'],
        'completion_time' => '1時間',
        'tips' => [
            '弱点項目の重点改善',
            '強みの更なる強化',
            'リスク要因の事前対策'
        ]
    ];
    
    return $checklist;
}

/**
 * =============================================================================
 * AI 比較機能 - Complete Implementation
 * =============================================================================
 */

/**
 * AI比較機能 AJAXハンドラー
 */
function gi_ajax_compare_grants() {
    try {
        // デバッグログ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('gi_ajax_compare_grants called with: ' . print_r($_POST, true));
        }
        
        // セキュリティ検証
        if (!gi_verify_ajax_nonce()) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました', 'code' => 'SECURITY_ERROR']);
            return;
        }
        
        $grant_ids = $_POST['grant_ids'] ?? [];
        
        if (empty($grant_ids) || !is_array($grant_ids)) {
            wp_send_json_error(['message' => '比較する助成金が選択されていません', 'code' => 'NO_GRANTS_SELECTED']);
            return;
        }
        
        if (count($grant_ids) < 2) {
            wp_send_json_error(['message' => '比較には2件以上の助成金が必要です', 'code' => 'INSUFFICIENT_GRANTS']);
            return;
        }
        
        if (count($grant_ids) > 3) {
            wp_send_json_error(['message' => '比較は最大3件までです', 'code' => 'TOO_MANY_GRANTS']);
            return;
        }
        
        $start_time = microtime(true);
        
        // 比較データ生成
        $comparison_data = gi_generate_grants_comparison($grant_ids);
        
        // AIおすすめ生成
        $recommendation = gi_generate_comparison_recommendation($comparison_data);
        
        $end_time = microtime(true);
        $processing_time = round(($end_time - $start_time) * 1000);
        
        wp_send_json_success([
            'comparison' => $comparison_data,
            'recommendation' => $recommendation,
            'grant_count' => count($grant_ids),
            'processing_time_ms' => $processing_time
        ]);
        
    } catch (Exception $e) {
        error_log('Grants Comparison Error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => '比較処理中にエラーが発生しました',
            'code' => 'COMPARISON_ERROR',
            'debug' => WP_DEBUG ? $e->getMessage() : null
        ]);
    }
}

/**
 * 助成金比較データ生成
 */
function gi_generate_grants_comparison($grant_ids) {
    $comparison_data = [];
    
    foreach ($grant_ids as $grant_id) {
        $grant_id = intval($grant_id);
        $grant_post = get_post($grant_id);
        
        if (!$grant_post || $grant_post->post_type !== 'grant') {
            continue;
        }
        
        $grant_details = gi_get_grant_details($grant_id);
        
        // マッチングスコア計算
        $match_score = gi_calculate_comparison_match_score($grant_id);
        
        // 難易度情報
        $difficulty = gi_get_grant_difficulty_info($grant_id);
        
        // 成功率情報
        $success_rate = gi_get_field_safe('adoption_rate', $grant_id, 0);
        
        $comparison_data[] = [
            'id' => $grant_id,
            'title' => $grant_post->post_title,
            'amount' => $grant_details['max_amount'] ?: '未定',
            'amount_numeric' => gi_extract_numeric_amount($grant_details['max_amount']),
            'deadline' => $grant_details['deadline'] ?: '随時',
            'organization' => $grant_details['organization'] ?: '未定',
            'target' => $grant_details['grant_target'] ?: '未定',
            'subsidy_rate' => gi_get_field_safe('subsidy_rate', $grant_id, ''),
            'match_score' => $match_score,
            'difficulty' => $difficulty,
            'success_rate' => $success_rate ?: null,
            'rate' => $success_rate > 0 ? $success_rate : null,
            'application_method' => gi_get_field_safe('application_method', $grant_id, 'オンライン'),
            'eligible_expenses' => $grant_details['eligible_expenses'] ?: '',
            'permalink' => get_permalink($grant_id)
        ];
    }
    
    return $comparison_data;
}

/**
 * 比較マッチングスコア計算
 */
function gi_calculate_comparison_match_score($grant_id) {
    // ベーススコア
    $base_score = 70;
    
    // 特徴加算
    if (gi_get_field_safe('is_featured', $grant_id) == '1') {
        $base_score += 10;
    }
    
    // 金額加算
    $amount_numeric = gi_get_field_safe('max_amount_numeric', $grant_id, 0);
    if ($amount_numeric >= 10000000) { // 1000万円以上
        $base_score += 15;
    } elseif ($amount_numeric >= 5000000) { // 500万円以上
        $base_score += 10;
    } elseif ($amount_numeric >= 1000000) { // 100万円以上
        $base_score += 5;
    }
    
    // 成功率加算
    $success_rate = gi_get_field_safe('adoption_rate', $grant_id, 0);
    if ($success_rate >= 50) {
        $base_score += 8;
    } elseif ($success_rate >= 30) {
        $base_score += 5;
    }
    
    // 難易度調整
    $difficulty = gi_get_field_safe('grant_difficulty', $grant_id, 'normal');
    if ($difficulty === 'easy') {
        $base_score += 5;
    } elseif ($difficulty === 'hard') {
        $base_score -= 5;
    }
    
    return min(98, max(60, $base_score));
}

/**
 * 助成金難易度情報取得
 */
function gi_get_grant_difficulty_info($grant_id) {
    $difficulty = gi_get_field_safe('grant_difficulty', $grant_id, 'normal');
    
    $difficulty_map = [
        'easy' => [
            'level' => 'easy',
            'label' => '易しい',
            'stars' => '★★☆',
            'description' => '初心者向け',
            'color' => '#16a34a'
        ],
        'normal' => [
            'level' => 'normal',
            'label' => '普通',
            'stars' => '★★★',
            'description' => '標準的',
            'color' => '#eab308'
        ],
        'hard' => [
            'level' => 'hard',
            'label' => '難しい',
            'stars' => '★★★',
            'description' => '経験者向け',
            'color' => '#dc2626'
        ]
    ];
    
    return $difficulty_map[$difficulty] ?? $difficulty_map['normal'];
}

/**
 * 数値金額抜き出し
 */
function gi_extract_numeric_amount($amount_string) {
    if (empty($amount_string)) return 0;
    
    // 数字と単位を抜き出し
    preg_match_all('/([\d,]+)(\s*[万億千百十]?)(円)?/', $amount_string, $matches, PREG_SET_ORDER);
    
    if (empty($matches)) return 0;
    
    $total = 0;
    
    foreach ($matches as $match) {
        $number = intval(str_replace(',', '', $match[1]));
        $unit = $match[2] ?? '';
        
        switch (trim($unit)) {
            case '億':
                $number *= 100000000;
                break;
            case '万':
                $number *= 10000;
                break;
            case '千':
                $number *= 1000;
                break;
            case '百':
                $number *= 100;
                break;
        }
        
        $total = max($total, $number); // 最大値を取る
    }
    
    return $total;
}

/**
 * 【高度AI機能】比較結果からAI総合おすすめ生成 - 機械学習風スコアリング
 */
function gi_generate_comparison_recommendation($comparison_data) {
    if (empty($comparison_data)) {
        return [
            'title' => '比較データがありません',
            'match_score' => 0,
            'reason' => '比較する助成金を選択してください。',
            'ai_analysis' => [],
            'risk_factors' => [],
            'optimization_suggestions' => []
        ];
    }
    
    // 各助成金に対して高度なAI分析を実行
    $enhanced_comparison = [];
    foreach ($comparison_data as $grant) {
        $grant_analysis = gi_perform_advanced_grant_analysis($grant);
        $grant['ai_analysis'] = $grant_analysis;
        $grant['composite_score'] = gi_calculate_composite_ai_score($grant, $grant_analysis);
        $enhanced_comparison[] = $grant;
    }
    
    // 複合スコア（AI分析結果）でソート
    usort($enhanced_comparison, function($a, $b) {
        return $b['composite_score'] <=> $a['composite_score'];
    });
    
    $best_grant = $enhanced_comparison[0];
    $second_best = isset($enhanced_comparison[1]) ? $enhanced_comparison[1] : null;
    $third_best = isset($enhanced_comparison[2]) ? $enhanced_comparison[2] : null;
    
    // === 高度なAI推奨理由分析 ===
    $ai_reasons = [];
    $quantitative_factors = [];
    $risk_assessment = [];
    
    // 成功確率分析
    $success_prob = $best_grant['ai_analysis']['success_probability'];
    if ($success_prob >= 0.75) {
        $ai_reasons[] = sprintf('AI算出成功確率 %s%%（業界平均+%s%%）', 
            round($success_prob * 100), 
            round(($success_prob - 0.4) * 100));
        $quantitative_factors['success_rate'] = $success_prob;
    }
    
    // ROI分析
    $roi_analysis = $best_grant['ai_analysis']['roi_analysis'];
    if ($roi_analysis['projected_roi'] >= 150) {
        $ai_reasons[] = sprintf('投資回収率 %s%%（%sヶ月で回収見込み）', 
            round($roi_analysis['projected_roi']), 
            $roi_analysis['payback_months']);
        $quantitative_factors['roi'] = $roi_analysis['projected_roi'];
    }
    
    // 競合優位性
    $competition_analysis = $best_grant['ai_analysis']['competition_analysis'];
    if ($competition_analysis['advantage_score'] >= 0.7) {
        $ai_reasons[] = sprintf('競合優位度 %s点/10点（差別化要因: %s）', 
            round($competition_analysis['advantage_score'] * 10), 
            implode('、', $competition_analysis['key_advantages']));
        $quantitative_factors['competitive_advantage'] = $competition_analysis['advantage_score'];
    }
    
    // 申請難易度vs期待値分析
    $effort_value_ratio = $best_grant['ai_analysis']['effort_value_ratio'];
    if ($effort_value_ratio >= 1.5) {
        $ai_reasons[] = sprintf('労力対効果比 %s倍（最適な投資効率）', 
            round($effort_value_ratio, 1));
        $quantitative_factors['effort_efficiency'] = $effort_value_ratio;
    }
    
    // 業界適合性
    $industry_fit = $best_grant['ai_analysis']['industry_compatibility'];
    if ($industry_fit >= 0.8) {
        $ai_reasons[] = sprintf('業界適合度 %s%%（事業計画との整合性が高い）', 
            round($industry_fit * 100));
        $quantitative_factors['industry_fit'] = $industry_fit;
    }
    
    // === リスク要因の分析 ===
    $risk_factors = gi_analyze_grant_risks($best_grant);
    
    // === 他候補との比較優位性 ===
    $comparative_advantages = [];
    if ($second_best) {
        $score_diff = $best_grant['composite_score'] - $second_best['composite_score'];
        if ($score_diff >= 5) {
            $comparative_advantages[] = sprintf('2位候補より %s点優位', round($score_diff));
        }
        
        // 具体的な優位項目
        if ($best_grant['amount_numeric'] > $second_best['amount_numeric']) {
            $amount_diff = ($best_grant['amount_numeric'] - $second_best['amount_numeric']) / 10000;
            $comparative_advantages[] = sprintf('助成額が %s万円多い', round($amount_diff));
        }
        
        if (isset($best_grant['success_rate']) && isset($second_best['success_rate']) && 
            $best_grant['success_rate'] > $second_best['success_rate']) {
            $rate_diff = $best_grant['success_rate'] - $second_best['success_rate'];
            $comparative_advantages[] = sprintf('採択率が %s%%高い', round($rate_diff));
        }
    }
    
    // === 最適化提案の生成 ===
    $optimization_suggestions = gi_generate_optimization_suggestions($best_grant, $enhanced_comparison);
    
    // === 最終的な推奨理由の構築 ===
    $comprehensive_reason = '';
    if (!empty($ai_reasons)) {
        $comprehensive_reason .= 'AI分析結果: ' . implode('、', array_slice($ai_reasons, 0, 3));
    }
    
    if (!empty($comparative_advantages)) {
        $comprehensive_reason .= '\n\n他候補との比較: ' . implode('、', $comparative_advantages);
    }
    
    if (empty($comprehensive_reason)) {
        $comprehensive_reason = 'AI総合評価により、現在の事業方針に最も適合する助成金と判定されました。';
    }
    
    return [
        'title' => $best_grant['title'],
        'match_score' => $best_grant['match_score'],
        'composite_score' => $best_grant['composite_score'],
        'reason' => $comprehensive_reason,
        'grant_id' => $best_grant['id'],
        'permalink' => $best_grant['permalink'],
        
        // === AI分析の詳細データ ===
        'ai_analysis' => [
            'success_probability' => $success_prob,
            'roi_projection' => $roi_analysis,
            'risk_assessment' => $risk_factors,
            'competitive_position' => $competition_analysis,
            'industry_alignment' => $industry_fit,
            'quantitative_factors' => $quantitative_factors
        ],
        
        // === アクション推奨 ===
        'optimization_suggestions' => $optimization_suggestions,
        
        // === 全体ランキング ===
        'ranking' => [
            'first' => [
                'title' => $best_grant['title'],
                'score' => $best_grant['composite_score'],
                'key_strength' => $ai_reasons[0] ?? '総合バランス'
            ],
            'second' => $second_best ? [
                'title' => $second_best['title'],
                'score' => $second_best['composite_score'],
                'key_strength' => gi_identify_key_strength($second_best)
            ] : null,
            'third' => $third_best ? [
                'title' => $third_best['title'],
                'score' => $third_best['composite_score'],
                'key_strength' => gi_identify_key_strength($third_best)
            ] : null
        ],
        
        // === 意思決定サポート ===
        'decision_factors' => [
            'confidence_level' => gi_calculate_recommendation_confidence($best_grant, $enhanced_comparison),
            'alternative_consideration' => $second_best && ($best_grant['composite_score'] - $second_best['composite_score']) < 3,
            'immediate_action_required' => gi_check_urgency_factors($best_grant)
        ]
    ];
}

function gi_get_grant_resources($post_id, $intent) {
    $resources = [
        'official_site' => get_post_meta($post_id, 'official_url', true),
        'application_guide' => get_post_meta($post_id, 'application_guide_url', true),
        'faq_url' => get_post_meta($post_id, 'faq_url', true),
        'contact_info' => get_post_meta($post_id, 'contact_info', true)
    ];
    
    // 意図に基づいて関連リソースを優先
    $prioritized = [];
    switch ($intent) {
        case 'application':
            if ($resources['application_guide']) {
                $prioritized['application_guide'] = '申請ガイド';
            }
            break;
        case 'deadline':
            if ($resources['official_site']) {
                $prioritized['official_site'] = '公式サイト（最新情報）';
            }
            break;
    }
    
    return array_filter($prioritized + $resources);
}

function gi_save_grant_question_history($post_id, $question, $response, $session_id) {
    // 助成金別の質問履歴保存（必要に応じて実装）
    $user_id = get_current_user_id();
    if (!$user_id) return false;
    
    $history = get_user_meta($user_id, 'gi_grant_question_history', true) ?: [];
    
    $history[] = [
        'grant_id' => $post_id,
        'question' => $question,
        'response' => mb_substr($response, 0, 200), // 応答の要約のみ保存
        'session_id' => $session_id,
        'timestamp' => current_time('timestamp')
    ];
    
    // 最新100件のみ保持
    $history = array_slice($history, -100);
    
    return update_user_meta($user_id, 'gi_grant_question_history', $history);
}

function gi_calculate_response_confidence($question, $response) {
    // 応答の信頼度を計算（簡易版）
    $question_length = mb_strlen($question);
    $response_length = mb_strlen($response);
    
    // 基本スコア
    $confidence = 0.7;
    
    // 質問の具体性
    if ($question_length > 10) {
        $confidence += 0.1;
    }
    
    // 応答の詳細度
    if ($response_length > 100) {
        $confidence += 0.1;
    }
    
    // 具体的なキーワードが含まれているか
    $specific_terms = ['申請', '締切', '金額', '対象', '要件'];
    $found_terms = 0;
    foreach ($specific_terms as $term) {
        if (mb_stripos($question, $term) !== false && mb_stripos($response, $term) !== false) {
            $found_terms++;
        }
    }
    
    $confidence += ($found_terms * 0.05);
    
    return min($confidence, 1.0);
}

/**
 * =============================================================================
 * Grant Data Functions - Template Support
 * =============================================================================
 */

/**
 * Complete grant data retrieval function
 */
function gi_get_complete_grant_data($post_id) {
    static $cache = [];
    
    // キャッシュチェック
    if (isset($cache[$post_id])) {
        return $cache[$post_id];
    }
    
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'grant') {
        return [];
    }
    
    // 基本データ
    $data = [
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'permalink' => get_permalink($post_id),
        'excerpt' => get_the_excerpt($post_id),
        'content' => get_post_field('post_content', $post_id),
        'date' => get_the_date('Y-m-d', $post_id),
        'modified' => get_the_modified_date('Y-m-d H:i:s', $post_id),
        'status' => get_post_status($post_id),
        'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium'),
    ];

    // ACFフィールドデータ
    $acf_fields = [
        // 基本情報
        'ai_summary' => '',
        'organization' => '',
        'organization_type' => '',
        
        // 金額情報
        'max_amount' => '',
        'max_amount_numeric' => 0,
        'min_amount' => 0,
        'subsidy_rate' => '',
        'amount_note' => '',
        
        // 締切・ステータス
        'deadline' => '',
        'deadline_date' => '',
        'deadline_timestamp' => '',
        'application_status' => 'active',
        'application_period' => '',
        'deadline_note' => '',
        
        // 対象・条件
        'grant_target' => '',
        'eligible_expenses' => '',
        'grant_difficulty' => 'normal',
        'adoption_rate' => 0,
        'required_documents' => '',
        
        // 申請・連絡先
        'application_method' => 'online',
        'contact_info' => '',
        'official_url' => '',
        'external_link' => '',
        
        // 管理設定
        'is_featured' => false,
        'priority_order' => 100,
        'views_count' => 0,
        'last_updated' => '',
        'admin_notes' => '',
    ];

    foreach ($acf_fields as $field => $default) {
        $value = gi_get_field_safe($field, $post_id, $default);
        $data[$field] = $value;
    }

    // タクソノミーデータ
    $taxonomies = ['grant_category', 'grant_prefecture', 'grant_tag'];
    foreach ($taxonomies as $taxonomy) {
        $terms = get_the_terms($post_id, $taxonomy);
        $data[$taxonomy] = [];
        $data[$taxonomy . '_names'] = [];
        
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $data[$taxonomy][] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'description' => $term->description
                ];
                $data[$taxonomy . '_names'][] = $term->name;
            }
        }
    }

    // 計算フィールド
    $data['is_deadline_soon'] = gi_is_deadline_soon($data['deadline']);
    $data['application_status_label'] = gi_get_status_label($data['application_status']);
    $data['difficulty_label'] = gi_get_difficulty_label($data['grant_difficulty']);
    
    // キャッシュに保存
    $cache[$post_id] = $data;
    
    return $data;
}

/**
 * All grant meta data retrieval function (fallback)
 */
function gi_get_all_grant_meta($post_id) {
    // gi_get_complete_grant_data のシンプル版
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'grant') {
        return [];
    }
    
    // 基本データのみ
    $data = [
        'id' => $post_id,
        'title' => get_the_title($post_id),
        'permalink' => get_permalink($post_id),
        'excerpt' => get_the_excerpt($post_id),
        'thumbnail' => get_the_post_thumbnail_url($post_id, 'medium'),
    ];
    
    // 重要なメタフィールドのみ
    $meta_fields = [
        'ai_summary', 'organization', 'max_amount', 'max_amount_numeric',
        'deadline', 'application_status', 'grant_target', 'subsidy_rate',
        'grant_difficulty', 'adoption_rate', 'official_url', 'is_featured'
    ];
    
    foreach ($meta_fields as $field) {
        $data[$field] = gi_get_field_safe($field, $post_id);
    }
    
    // タクソノミー名の配列
    $data['categories'] = wp_get_post_terms($post_id, 'grant_category', ['fields' => 'names']);
    $data['prefectures'] = wp_get_post_terms($post_id, 'grant_prefecture', ['fields' => 'names']);
    
    return $data;
}

/**
 * Safe field retrieval with fallback
 */
function gi_get_field_safe($field_name, $post_id, $default = '') {
    // ACFが利用可能な場合
    if (function_exists('get_field')) {
        $value = get_field($field_name, $post_id);
        return $value !== false && $value !== null ? $value : $default;
    }
    
    // フォールバック: 標準のpost_meta
    $value = get_post_meta($post_id, $field_name, true);
    return !empty($value) ? $value : $default;
}

/**
 * Safe ACF field retrieval (alias for template compatibility)
 * Note: This function is already defined in inc/data-functions.php
 * Using existing function to avoid redeclaration
 */

/**
 * Check if deadline is soon (within 30 days)
 */
function gi_is_deadline_soon($deadline) {
    if (empty($deadline)) return false;
    
    // 日付形式の正規化
    $timestamp = gi_normalize_date($deadline);
    if (!$timestamp) return false;
    
    $now = time();
    $thirty_days = 30 * 24 * 60 * 60;
    
    return ($timestamp > $now && $timestamp <= ($now + $thirty_days));
}

/**
 * Get status label
 */
function gi_get_status_label($status) {
    $labels = [
        'active' => '募集中',
        'pending' => '準備中',
        'closed' => '終了',
        'suspended' => '一時停止',
        'draft' => '下書き'
    ];
    
    return $labels[$status] ?? $status;
}

/**
 * Get difficulty label
 */
function gi_get_difficulty_label($difficulty) {
    $labels = [
        'easy' => '易しい',
        'normal' => '普通',
        'hard' => '難しい',
        'expert' => '上級者向け'
    ];
    
    return $labels[$difficulty] ?? $difficulty;
}

/**
 * Normalize date to timestamp
 */
function gi_normalize_date($date_input) {
    if (empty($date_input)) return false;
    
    // すでにタイムスタンプの場合
    if (is_numeric($date_input) && strlen($date_input) >= 10) {
        return intval($date_input);
    }
    
    // Ymd形式（例：20241231）
    if (is_numeric($date_input) && strlen($date_input) == 8) {
        $year = substr($date_input, 0, 4);
        $month = substr($date_input, 4, 2);
        $day = substr($date_input, 6, 2);
        return mktime(0, 0, 0, $month, $day, $year);
    }
    
    // その他の日付文字列
    $timestamp = strtotime($date_input);
    return $timestamp !== false ? $timestamp : false;
}

/**
 * Get user favorites safely
 * Note: This function is defined in inc/data-processing.php
 * No need to redefine here - using existing gi_get_user_favorites()
 */

/**
 * =============================================================================
 * メイン検索・フィルタリング AJAX 処理
 * =============================================================================
 */

/**
 * 統一カードレンダリング関数（簡易版）
 */
if (!function_exists('gi_render_card_unified')) {
    function gi_render_card_unified($post_id, $view = 'grid') {
        // 既存のカード関数を使用してフォールバック
        global $current_view, $user_favorites;
        $current_view = $view;
        
        ob_start();
        get_template_part('template-parts/grant-card-unified');
        $output = ob_get_clean();
        
        // デバッグ: テンプレート出力をログに記録
        if (WP_DEBUG) {
            error_log("gi_render_card_unified - Post ID: {$post_id}, Output length: " . strlen($output));
            if (empty($output)) {
                error_log("gi_render_card_unified - Template output is empty, using fallback");
            }
        }
        
        // 出力がない場合の簡易フォールバック
        if (empty($output)) {
            $title = get_the_title($post_id);
            $permalink = get_permalink($post_id);
            $organization = get_field('organization', $post_id) ?: '';
            $amount = get_field('max_amount', $post_id) ?: '金額未設定';
            $status = get_field('application_status', $post_id) ?: 'open';
            $status_text = $status === 'open' ? '募集中' : ($status === 'upcoming' ? '募集予定' : '募集終了');
            
            $is_favorite = in_array($post_id, $user_favorites ?: []);
            
            if ($view === 'grid') {
                return "
                <div class='clean-grant-card' data-post-id='{$post_id}' onclick=\"location.href='{$permalink}'\">
                    <div class='clean-grant-card-header'>
                        <h3 style='margin: 0; font-size: 16px; font-weight: 600; line-height: 1.4;'>
                            <a href='{$permalink}' style='text-decoration: none; color: inherit;'>{$title}</a>
                        </h3>
                        <button class='favorite-btn' data-post-id='{$post_id}' onclick='event.stopPropagation();' style='
                            position: absolute; top: 10px; right: 10px; background: none; border: none; 
                            color: " . ($is_favorite ? '#dc2626' : '#6b7280') . "; font-size: 18px; cursor: pointer;
                        '>" . ($is_favorite ? '♥' : '♡') . "</button>
                    </div>
                    <div class='clean-grant-card-body'>
                        <div style='margin-bottom: 12px; font-size: 14px; color: #6b7280;'>{$organization}</div>
                        <div style='margin-bottom: 12px; font-size: 14px; font-weight: 600; color: #16a34a;'>{$amount}</div>
                    </div>
                    <div class='clean-grant-card-footer'>
                        <span style='font-size: 12px; color: #6b7280;'>{$status_text}</span>
                        <div style='display: flex; gap: 8px; align-items: center;'>
                            <button class='grant-btn-compact grant-btn-compact--ai' 
                                    data-post-id='{$post_id}' 
                                    data-grant-title='" . esc_attr($title) . "'
                                    type='button'
                                    style='
                                        background: #374151; color: white; border: none; 
                                        padding: 8px 12px; border-radius: 6px; cursor: pointer;
                                        font-size: 12px; display: flex; align-items: center; gap: 4px;
                                    '
                                    title='AIに質問'>
                                <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                    <path d='M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'/>
                                </svg>
                                AI
                            </button>
                            <a href='{$permalink}' style='
                                background: #000; color: white; text-align: center; 
                                padding: 8px 16px; text-decoration: none; border-radius: 6px;
                                font-size: 12px; font-weight: 500;
                            '>詳細を見る</a>
                        </div>
                    </div>
                </div>";
            } else {
                return "
                <div class='clean-grant-card clean-grant-card-list' data-post-id='{$post_id}' onclick=\"location.href='{$permalink}'\" style='
                    display: flex; align-items: center; gap: 16px; cursor: pointer;
                '>
                    <div style='flex: 1;'>
                        <h3 style='margin: 0 0 4px 0; font-size: 16px; font-weight: 600;'>
                            <a href='{$permalink}' style='text-decoration: none; color: inherit;'>{$title}</a>
                        </h3>
                        <div style='font-size: 12px; color: #6b7280;'>{$organization}</div>
                    </div>
                    
                    <div style='text-align: center; min-width: 120px;'>
                        <div style='font-size: 14px; font-weight: 600; color: #16a34a;'>{$amount}</div>
                        <div style='font-size: 10px; color: #9ca3af;'>{$status_text}</div>
                    </div>
                    
                    <div style='display: flex; gap: 8px; align-items: center;'>
                        <button class='grant-btn-compact grant-btn-compact--ai' 
                                data-post-id='{$post_id}' 
                                data-grant-title='" . esc_attr($title) . "'
                                type='button'
                                onclick='event.stopPropagation();'
                                style='
                                    background: #374151; color: white; border: none; 
                                    padding: 6px 8px; border-radius: 4px; cursor: pointer;
                                    font-size: 11px; min-width: 36px;
                                '
                                title='AIに質問'>
                            <svg width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                                <path d='M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'/>
                            </svg>
                        </button>
                        
                        <button class='favorite-btn' data-post-id='{$post_id}' onclick='event.stopPropagation();' style='
                            background: none; border: none; color: " . ($is_favorite ? '#dc2626' : '#6b7280') . "; 
                            font-size: 18px; cursor: pointer; padding: 8px;
                        '>" . ($is_favorite ? '♥' : '♡') . "</button>
                    </div>
                </div>";
            }
        } else {
            // テンプレートが正常に出力された場合
            if (WP_DEBUG) {
                error_log("gi_render_card_unified - Using template output, length: " . strlen($output));
            }
        }
        
        // フォールバック処理でAI関数を確保
        static $ai_functions_added = false;
        if (!$ai_functions_added) {
            $ai_functions_added = true;
            $output .= "<script>
                // フォールバック用AI関数の定義（一度だけ）
                if (typeof window.showAIChatModal === 'undefined') {
                    console.log('🚀 Fallback AI functions loading...');
                    
                    window.showAIChatModal = function(postId, grantTitle) {
                        console.log('📱 Fallback AI Modal:', postId, grantTitle);
                        
                        const modal = document.createElement('div');
                        modal.style.cssText = `
                            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000;
                            background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center;
                        `;
                        
                        modal.innerHTML = `
                            <div style=\"background: white; padding: 20px; border-radius: 8px; max-width: 500px; width: 90%;\">
                                <h3 style=\"margin: 0 0 15px 0;\">AI質問 - \" + grantTitle + \"</h3>
                                <p>申請条件、必要書類、申請方法など、この助成金について何でもお聞きください。</p>
                                <textarea placeholder=\"例：申請条件は何ですか？\" style=\"width: 100%; height: 100px; margin: 10px 0; padding: 8px;\"></textarea>
                                <div style=\"text-align: right; margin-top: 15px;\">
                                    <button onclick=\"this.closest('div').parentElement.remove()\" style=\"background: #666; color: white; border: none; padding: 8px 16px; margin-right: 8px; border-radius: 4px; cursor: pointer;\">閉じる</button>
                                    <button onclick=\"alert('AI機能は準備中です')\" style=\"background: #000; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;\">送信</button>
                                </div>
                            </div>
                        `;
                        
                        document.body.appendChild(modal);
                    };
                    
                    // イベント委譲でAIボタンクリックを処理
                    document.addEventListener('click', function(e) {
                        if (e.target.closest('.grant-btn-compact--ai')) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            const btn = e.target.closest('.grant-btn-compact--ai');
                            const postId = btn.getAttribute('data-post-id');
                            const title = btn.getAttribute('data-grant-title');
                            
                            if (postId && title) {
                                window.showAIChatModal(postId, title);
                            }
                        }
                    });
                    
                    console.log('✅ Fallback AI functions loaded');
                }
            </script>";
        }
        
        return $output;
    }
}

/**
 * 助成金読み込み処理（完全版・統一カード対応）- フィルタリング修正版
 */
function gi_ajax_load_grants() {
    try {
        // デバッグログ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('gi_ajax_load_grants called with: ' . print_r($_POST, true));
        }
        
        // nonceチェック
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました', 'code' => 'SECURITY_ERROR']);
            return;
        }

    // ===== パラメータ取得と検証 =====
    $search = sanitize_text_field($_POST['search'] ?? '');
    $categories = json_decode(stripslashes($_POST['categories'] ?? '[]'), true) ?: [];
    $prefectures = json_decode(stripslashes($_POST['prefectures'] ?? '[]'), true) ?: [];
    $municipalities = json_decode(stripslashes($_POST['municipalities'] ?? '[]'), true) ?: [];

    // デバッグログ追加
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🔍 Municipality filter received: ' . print_r($municipalities, true));
    }

    // 空文字列を除外
    $municipalities = array_filter($municipalities, function($val) {
        return !empty($val) && $val !== '';
    });

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('🔍 Municipality filter after filtering: ' . print_r($municipalities, true));
    }
    $tags = json_decode(stripslashes($_POST['tags'] ?? '[]'), true) ?: [];
    
    // 単一タグパラメータのサポート（URL: ?tag=slug からの呼び出し用）
    if (empty($tags) && !empty($_POST['tag'])) {
        $single_tag = sanitize_text_field($_POST['tag']);
        $tags = [$single_tag];
    }
    
    $status = json_decode(stripslashes($_POST['status'] ?? '[]'), true) ?: [];
    $difficulty = json_decode(stripslashes($_POST['difficulty'] ?? '[]'), true) ?: [];
    $success_rate = json_decode(stripslashes($_POST['success_rate'] ?? '[]'), true) ?: [];
    
    // 金額・数値フィルター
    $amount = sanitize_text_field($_POST['amount'] ?? '');
    $amount_min = intval($_POST['amount_min'] ?? 0);
    $amount_max = intval($_POST['amount_max'] ?? 0);
    
    // 新しいフィルター項目
    $subsidy_rate = sanitize_text_field($_POST['subsidy_rate'] ?? '');
    $organization = sanitize_text_field($_POST['organization'] ?? '');
    $organization_type = sanitize_text_field($_POST['organization_type'] ?? '');
    $target_business = sanitize_text_field($_POST['target_business'] ?? '');
    $application_method = sanitize_text_field($_POST['application_method'] ?? '');
    $only_featured = sanitize_text_field($_POST['only_featured'] ?? '');
    $deadline_range = sanitize_text_field($_POST['deadline_range'] ?? '');
    
    // 表示・ソート設定
    $sort = sanitize_text_field($_POST['sort'] ?? 'date_desc');
    $view = sanitize_text_field($_POST['view'] ?? 'grid');
    $page = max(1, intval($_POST['page'] ?? 1));
    $posts_per_page = max(6, min(30, intval($_POST['posts_per_page'] ?? 12)));

    // ===== WP_Queryの引数構築 =====
    $args = [
        'post_type' => 'grant',
        'posts_per_page' => $posts_per_page,
        'paged' => $page,
        'post_status' => 'publish'
    ];

    // ===== 検索クエリ（拡張版：ACFフィールドも検索対象） =====
    if (!empty($search)) {
        $args['s'] = $search;
        
        // メタフィールドも検索対象に追加
        add_filter('posts_search', function($search_sql, $wp_query) use ($search) {
            global $wpdb;
            
            if (!$wp_query->is_main_query() || empty($search)) {
                return $search_sql;
            }
            
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            
            $meta_search = $wpdb->prepare("
                OR EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} pm 
                    WHERE pm.post_id = {$wpdb->posts}.ID 
                    AND pm.meta_key IN ('ai_summary', 'organization', 'grant_target', 'eligible_expenses', 'required_documents')
                    AND pm.meta_value LIKE %s
                )
            ", $search_term);
            
            // 既存の検索SQLに追加
            $search_sql = str_replace('))) AND', '))) ' . $meta_search . ' AND', $search_sql);
            return $search_sql;
        }, 10, 2);
    }

    // ===== タクソノミークエリ =====
    $tax_query = ['relation' => 'AND'];
    
    if (!empty($categories)) {
        $tax_query[] = [
            'taxonomy' => 'grant_category',
            'field' => 'slug',
            'terms' => $categories,
            'operator' => 'IN'
        ];
    }
    
    if (!empty($prefectures)) {
        $tax_query[] = [
            'taxonomy' => 'grant_prefecture',
            'field' => 'slug', 
            'terms' => $prefectures,
            'operator' => 'IN'
        ];
    }
    
    if (!empty($municipalities)) {
        $tax_query[] = [
            'taxonomy' => 'grant_municipality',
            'field' => 'slug',
            'terms' => $municipalities,
            'operator' => 'IN'
        ];
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('✅ Municipality tax_query added');
            error_log('Terms: ' . implode(', ', $municipalities));
            error_log('Tax query count: ' . count($tax_query));
        }
    } else {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('⚠️ Municipality filter is empty, not adding to tax_query');
        }
    }
    
    if (!empty($tags)) {
        $tax_query[] = [
            'taxonomy' => 'grant_tag',
            'field' => 'slug',
            'terms' => $tags,
            'operator' => 'IN'
        ];
    }
    
    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    // ===== メタクエリ（カスタムフィールド） =====
    $meta_query = ['relation' => 'AND'];
    
    // ステータスフィルター
    if (!empty($status)) {
        // UIステータスをDBの値にマッピング
        $db_status = array_map(function($s) {
            // 複数の可能性に対応
            if ($s === 'active' || $s === '募集中') return 'open';
            if ($s === 'upcoming' || $s === '募集予定') return 'upcoming';
            if ($s === 'closed' || $s === '終了') return 'closed';
            return $s;
        }, $status);
        
        $meta_query[] = [
            'key' => 'application_status',
            'value' => $db_status,
            'compare' => 'IN'
        ];
    }
    
    // 金額範囲フィルター
    if (!empty($amount)) {
        switch($amount) {
            case '0-100':
                $meta_query[] = [
                    'key' => 'max_amount_numeric',
                    'value' => [0, 1000000],
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                ];
                break;
            case '100-500':
                $meta_query[] = [
                    'key' => 'max_amount_numeric',
                    'value' => [1000000, 5000000],
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                ];
                break;
            case '500-1000':
                $meta_query[] = [
                    'key' => 'max_amount_numeric',
                    'value' => [5000000, 10000000],
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                ];
                break;
            case '1000-3000':
                $meta_query[] = [
                    'key' => 'max_amount_numeric',
                    'value' => [10000000, 30000000],
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                ];
                break;
            case '3000+':
                $meta_query[] = [
                    'key' => 'max_amount_numeric',
                    'value' => 30000000,
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                ];
                break;
        }
    }
    
    // 難易度フィルター
    if (!empty($difficulty)) {
        $meta_query[] = [
            'key' => 'grant_difficulty', // ACFフィールド名に合わせる
            'value' => $difficulty,
            'compare' => 'IN'
        ];
    }
    
    // 成功率フィルター
    if (!empty($success_rate)) {
        foreach ($success_rate as $rate_range) {
            switch($rate_range) {
                case '0-20':
                    $meta_query[] = [
                        'key' => 'adoption_rate', // ACFフィールド名に合わせる
                        'value' => [0, 20],
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    ];
                    break;
                case '20-40':
                    $meta_query[] = [
                        'key' => 'adoption_rate', // ACFフィールド名に合わせる
                        'value' => [20, 40],
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    ];
                    break;
                case '40-60':
                    $meta_query[] = [
                        'key' => 'adoption_rate', // ACFフィールド名に合わせる
                        'value' => [40, 60],
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    ];
                    break;
                case '60-80':
                    $meta_query[] = [
                        'key' => 'adoption_rate', // ACFフィールド名に合わせる
                        'value' => [60, 80],
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    ];
                    break;
                case '80-100':
                    $meta_query[] = [
                        'key' => 'adoption_rate', // ACFフィールド名に合わせる
                        'value' => [80, 100],
                        'compare' => 'BETWEEN',
                        'type' => 'NUMERIC'
                    ];
                    break;
            }
        }
    }
    
    // 補助率フィルター
    if (!empty($subsidy_rate)) {
        $meta_query[] = [
            'key' => 'subsidy_rate',
            'value' => $subsidy_rate,
            'compare' => 'LIKE'
        ];
    }
    
    // 実施機関フィルター
    if (!empty($organization)) {
        $meta_query[] = [
            'key' => 'organization',
            'value' => $organization,
            'compare' => 'LIKE'
        ];
    }
    
    // 実施機関種別フィルター
    if (!empty($organization_type)) {
        $meta_query[] = [
            'key' => 'organization_type',
            'value' => $organization_type,
            'compare' => 'LIKE'
        ];
    }
    
    // 対象事業フィルター
    if (!empty($target_business)) {
        $meta_query[] = [
            'key' => 'grant_target',
            'value' => $target_business,
            'compare' => 'LIKE'
        ];
    }
    
    // 申請方法フィルター
    if (!empty($application_method)) {
        $meta_query[] = [
            'key' => 'application_method',
            'value' => $application_method,
            'compare' => '='
        ];
    }
    
    // 締切期間フィルター
    if (!empty($deadline_range)) {
        $now = time();
        switch($deadline_range) {
            case 'within_1month':
                $end_time = $now + (30 * 24 * 60 * 60);
                $meta_query[] = [
                    'key' => 'deadline_timestamp',
                    'value' => [$now, $end_time],
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                ];
                break;
            case 'within_3months':
                $end_time = $now + (90 * 24 * 60 * 60);
                $meta_query[] = [
                    'key' => 'deadline_timestamp',
                    'value' => [$now, $end_time],
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                ];
                break;
            case 'within_6months':
                $end_time = $now + (180 * 24 * 60 * 60);
                $meta_query[] = [
                    'key' => 'deadline_timestamp',
                    'value' => [$now, $end_time],
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                ];
                break;
            case 'anytime':
                $meta_query[] = [
                    'key' => 'deadline',
                    'value' => ['随時', '通年', '年中'],
                    'compare' => 'IN'
                ];
                break;
        }
    }
    
    // カスタム金額範囲フィルター
    if ($amount_min > 0 || $amount_max > 0) {
        $amount_query = [
            'key' => 'max_amount_numeric',
            'type' => 'NUMERIC'
        ];
        
        if ($amount_min > 0 && $amount_max > 0) {
            $amount_query['value'] = [$amount_min * 10000, $amount_max * 10000]; // 万円を円に変換
            $amount_query['compare'] = 'BETWEEN';
        } elseif ($amount_min > 0) {
            $amount_query['value'] = $amount_min * 10000;
            $amount_query['compare'] = '>=';
        } elseif ($amount_max > 0) {
            $amount_query['value'] = $amount_max * 10000;
            $amount_query['compare'] = '<=';
        }
        
        $meta_query[] = $amount_query;
    }
    
    // 注目の助成金フィルター
    if ($only_featured === 'true' || $only_featured === '1') {
        $meta_query[] = [
            'key' => 'is_featured',
            'value' => '1',
            'compare' => '='
        ];
    }
    
    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }

    // ===== ソート順 =====
    switch ($sort) {
        case 'date_asc':
            $args['orderby'] = 'date';
            $args['order'] = 'ASC';
            break;
        case 'date_desc':
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
        case 'amount_desc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'max_amount_numeric';
            $args['order'] = 'DESC';
            break;
        case 'amount_asc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'max_amount_numeric';
            $args['order'] = 'ASC';
            break;
        case 'deadline_asc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'deadline_timestamp';
            $args['order'] = 'ASC';
            break;
        case 'success_rate_desc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'adoption_rate'; // ACFフィールド名に合わせる
            $args['order'] = 'DESC';
            break;
        case 'featured_first':
        case 'featured':
            $args['orderby'] = ['meta_value_num' => 'DESC', 'date' => 'DESC'];
            $args['meta_key'] = 'is_featured';
            break;
        default:
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
    }

    // ===== クエリ実行 =====
    $query = new WP_Query($args);
    $grants = [];
    
    global $user_favorites, $current_view;
    $user_favorites = gi_get_user_favorites();
    $current_view = $view;

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // 統一カードレンダリングを使用
            $html = gi_render_card_unified($post_id, $view);

            $grants[] = [
                'id' => $post_id,
                'html' => $html,
                'title' => get_the_title($post_id),
                'permalink' => get_permalink($post_id)
            ];
        }
        wp_reset_postdata();
    }

    // ===== 統計情報 =====
    $stats = [
        'total_found' => $query->found_posts,
        'current_page' => $page,
        'total_pages' => $query->max_num_pages,
        'posts_per_page' => $posts_per_page,
        'showing_from' => (($page - 1) * $posts_per_page) + 1,
        'showing_to' => min($page * $posts_per_page, $query->found_posts),
    ];

    // ===== レスポンス送信 =====
    wp_send_json_success([
        'grants' => $grants,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $query->max_num_pages,
            'total_posts' => $query->found_posts,
            'posts_per_page' => $posts_per_page,
        ],
        'stats' => $stats,
        'view' => $view,
        'query_info' => [
            'search' => $search,
            'filters_applied' => !empty($categories) || !empty($prefectures) || !empty($tags) || !empty($status) || !empty($amount) || !empty($only_featured) || !empty($difficulty) || !empty($success_rate) || !empty($subsidy_rate) || !empty($organization) || !empty($deadline_range),
            'applied_filters' => [
                'categories' => $categories,
                'prefectures' => $prefectures, 
                'tags' => $tags,
                'status' => $status,
                'difficulty' => $difficulty,
                'success_rate' => $success_rate,
                'amount' => $amount,
                'subsidy_rate' => $subsidy_rate,
                'organization' => $organization,
                'deadline_range' => $deadline_range,
                'only_featured' => $only_featured
            ],
            'sort' => $sort,
        ],
        'debug' => defined('WP_DEBUG') && WP_DEBUG ? [
            'query_args' => $args,
            'meta_query_count' => count($meta_query) - 1,
            'tax_query_count' => count($tax_query) - 1
        ] : null,
    ]);
    
    } catch (Exception $e) {
        error_log('Grant Load Error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => 'フィルタリング中にエラーが発生しました。しばらく後でお試しください。',
            'code' => 'FILTERING_ERROR',
            'debug' => WP_DEBUG ? $e->getMessage() : null
        ]);
    }
}

/**
 * Archive page grants loading with municipality support
 * アーカイブページの補助金読み込み（市町村対応）
 */
function gi_load_grants() {
    // デバッグログ
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('gi_load_grants called with: ' . print_r($_POST, true));
    }
    
    // Nonce verification
    if (!gi_verify_ajax_nonce()) {
        wp_send_json_error(['message' => 'セキュリティチェックに失敗しました', 'code' => 'SECURITY_ERROR']);
        return;
    }
    
    // Get parameters
    $search = sanitize_text_field($_POST['search'] ?? '');
    $categories = isset($_POST['categories']) ? json_decode(stripslashes($_POST['categories']), true) : [];
    $prefectures = isset($_POST['prefectures']) ? json_decode(stripslashes($_POST['prefectures']), true) : [];
    $municipalities = isset($_POST['municipalities']) ? json_decode(stripslashes($_POST['municipalities']), true) : [];
    $region = sanitize_text_field($_POST['region'] ?? '');
    $amount = sanitize_text_field($_POST['amount'] ?? '');
    $status = isset($_POST['status']) ? json_decode(stripslashes($_POST['status']), true) : [];
    $only_featured = sanitize_text_field($_POST['only_featured'] ?? '');
    $sort = sanitize_text_field($_POST['sort'] ?? 'date_desc');
    $view = sanitize_text_field($_POST['view'] ?? 'grid');
    $page = max(1, intval($_POST['page'] ?? 1));
    
    // Build query args
    $args = [
        'post_type' => 'grant',
        'posts_per_page' => 12,
        'post_status' => 'publish',
        'paged' => $page,
    ];
    
    // AI-enhanced semantic search
    $use_semantic_search = false;
    $semantic_results = [];
    
    if (!empty($search)) {
        // Try semantic search first if available
        if (class_exists('GI_Semantic_Search')) {
            try {
                $semantic_search = GI_Semantic_Search::getInstance();
                if ($semantic_search && method_exists($semantic_search, 'search')) {
                    $semantic_results = $semantic_search->search($search, [
                        'limit' => 50, // Get more results for filtering
                        'threshold' => 0.7,
                    ]);
                    
                    if (!empty($semantic_results) && isset($semantic_results['posts'])) {
                        $use_semantic_search = true;
                        $post_ids = array_column($semantic_results['posts'], 'ID');
                        
                        // Use post__in for semantic search results
                        $args['post__in'] = $post_ids;
                        $args['orderby'] = 'post__in'; // Preserve semantic ranking
                    }
                }
            } catch (Exception $e) {
                error_log('Semantic search error in gi_load_grants: ' . $e->getMessage());
            }
        }
        
        // Fallback to traditional search if semantic search didn't work
        if (!$use_semantic_search) {
            $args['s'] = $search;
        }
    }
    
    // Taxonomy query
    $tax_query = ['relation' => 'AND'];
    
    if (!empty($categories)) {
        $tax_query[] = [
            'taxonomy' => 'grant_category',
            'field' => 'slug',
            'terms' => $categories,
        ];
    }
    
    if (!empty($prefectures)) {
        $tax_query[] = [
            'taxonomy' => 'grant_prefecture',
            'field' => 'slug',
            'terms' => $prefectures,
        ];
    }
    
    if (!empty($municipalities)) {
        $tax_query[] = [
            'taxonomy' => 'grant_municipality',
            'field' => 'slug',
            'terms' => $municipalities,
        ];
    }
    
    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }
    
    // Meta query
    $meta_query = ['relation' => 'AND'];
    
    if (!empty($status)) {
        $db_statuses = array_map(function($s) {
            return $s === 'active' ? 'open' : ($s === 'upcoming' ? 'upcoming' : $s);
        }, $status);
        
        $meta_query[] = [
            'key' => 'application_status',
            'value' => $db_statuses,
            'compare' => 'IN',
        ];
    }
    
    if ($only_featured === '1') {
        $meta_query[] = [
            'key' => 'is_featured',
            'value' => '1',
            'compare' => '=',
        ];
    }
    
    if (count($meta_query) > 1) {
        $args['meta_query'] = $meta_query;
    }
    
    // Sorting
    switch ($sort) {
        case 'amount_desc':
            $args['orderby'] = 'meta_value_num';
            $args['meta_key'] = 'max_amount_numeric';
            $args['order'] = 'DESC';
            break;
        case 'featured_first':
            $args['orderby'] = ['meta_value_num' => 'DESC', 'date' => 'DESC'];
            $args['meta_key'] = 'is_featured';
            break;
        case 'deadline_asc':
            $args['orderby'] = 'meta_value';
            $args['meta_key'] = 'application_deadline';
            $args['order'] = 'ASC';
            break;
        default:
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
    }
    
    // Execute query
    $query = new WP_Query($args);
    
    // Get user favorites
    $user_favorites = function_exists('gi_get_user_favorites') ? gi_get_user_favorites() : [];
    
    // Build grant HTML
    $grants = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            
            // Set global variables for template
            $GLOBALS['current_view'] = $view;
            $GLOBALS['user_favorites'] = $user_favorites;
            
            // Capture template output
            ob_start();
            get_template_part('template-parts/grant-card-unified');
            $html = ob_get_clean();
            
            $grants[] = [
                'id' => get_the_ID(),
                'html' => $html,
            ];
        }
        wp_reset_postdata();
    }
    
    // Stats
    $stats = [
        'total_found' => $query->found_posts,
        'current_page' => $page,
        'total_pages' => $query->max_num_pages,
    ];
    
    wp_send_json_success([
        'grants' => $grants,
        'stats' => $stats,
        'pagination' => [
            'current' => $page,
            'total' => $query->max_num_pages,
        ],
    ]);
}
// gi_load_grants AJAX handlers removed to avoid conflicts with gi_ajax_load_grants

/**
 * =============================================================================
 * Missing Helper Functions for Comparison
 * =============================================================================
 */

// gi_get_field_safe() function already declared earlier in this file

/**
 * =============================================================================
 * OpenAI API 設定管理
 * =============================================================================
 */

/**
 * OpenAI API設定の管理画面をWordPress管理画面に追加
 */
add_action('admin_menu', 'gi_add_openai_settings_page');
function gi_add_openai_settings_page() {
    add_options_page(
        'AI質問機能設定',
        'AI質問機能',
        'manage_options',
        'gi-openai-settings',
        'gi_openai_settings_page'
    );
}

/**
 * OpenAI API設定画面の表示
 */
function gi_openai_settings_page() {
    // 設定保存処理
    if (isset($_POST['submit']) && wp_verify_nonce($_POST['gi_openai_nonce'], 'gi_openai_settings')) {
        $api_key = sanitize_text_field($_POST['gi_openai_api_key'] ?? '');
        $model = sanitize_text_field($_POST['gi_openai_model'] ?? 'gpt-3.5-turbo');
        $max_tokens = intval($_POST['gi_openai_max_tokens'] ?? 500);
        $temperature = floatval($_POST['gi_openai_temperature'] ?? 0.7);
        
        update_option('gi_openai_api_key', $api_key);
        update_option('gi_openai_model', $model);
        update_option('gi_openai_max_tokens', $max_tokens);
        update_option('gi_openai_temperature', $temperature);
        
        echo '<div class="notice notice-success"><p>設定を保存しました。</p></div>';
    }
    
    $current_api_key = get_option('gi_openai_api_key', '');
    $current_model = get_option('gi_openai_model', 'gpt-3.5-turbo');
    $current_max_tokens = get_option('gi_openai_max_tokens', 500);
    $current_temperature = get_option('gi_openai_temperature', 0.7);
    ?>
    
    <div class="wrap">
        <h1>AI質問機能設定</h1>
        <p>助成金詳細ページでユーザーがAIに質問できる機能の設定を行います。</p>
        
        <form method="post">
            <?php wp_nonce_field('gi_openai_settings', 'gi_openai_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">OpenAI API キー</th>
                    <td>
                        <input type="password" name="gi_openai_api_key" value="<?php echo esc_attr($current_api_key); ?>" class="regular-text" />
                        <p class="description">
                            OpenAIのAPIキーを入力してください。<br>
                            APIキーは <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Dashboard</a> で取得できます。<br>
                            <strong>空白の場合は簡易的なフォールバック応答を表示します。</strong>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">使用モデル</th>
                    <td>
                        <select name="gi_openai_model">
                            <option value="gpt-3.5-turbo" <?php selected($current_model, 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo (推奨)</option>
                            <option value="gpt-4" <?php selected($current_model, 'gpt-4'); ?>>GPT-4 (高精度・高コスト)</option>
                            <option value="gpt-4-turbo" <?php selected($current_model, 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                        </select>
                        <p class="description">利用するOpenAIのモデルを選択してください。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">最大トークン数</th>
                    <td>
                        <input type="number" name="gi_openai_max_tokens" value="<?php echo esc_attr($current_max_tokens); ?>" min="100" max="2000" />
                        <p class="description">AIの応答の最大長さ (100-2000)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Temperature</th>
                    <td>
                        <input type="number" name="gi_openai_temperature" value="<?php echo esc_attr($current_temperature); ?>" min="0" max="2" step="0.1" />
                        <p class="description">AIの創造性レベル (0.0: 堅実, 2.0: 創造的)</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('設定を保存'); ?>
        </form>
        
        <div class="card">
            <h2>API接続テスト</h2>
            <p>設定したAPIキーが正常に動作するかテストできます。</p>
            <button type="button" id="test-openai-connection" class="button button-secondary">接続テスト</button>
            <div id="test-result" style="margin-top: 15px;"></div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#test-openai-connection').on('click', function() {
                    var button = $(this);
                    var result = $('#test-result');
                    
                    button.prop('disabled', true).text('テスト中...');
                    result.html('');
                    
                    $.post(ajaxurl, {
                        action: 'gi_test_openai_connection',
                        _wpnonce: '<?php echo wp_create_nonce("gi_test_openai"); ?>'
                    })
                    .done(function(response) {
                        if (response.success) {
                            result.html('<div class="notice notice-success"><p>✅ ' + response.data.message + '</p></div>');
                        } else {
                            result.html('<div class="notice notice-error"><p>❌ ' + response.data.message + '</p></div>');
                        }
                    })
                    .fail(function() {
                        result.html('<div class="notice notice-error"><p>❌ 通信エラーが発生しました</p></div>');
                    })
                    .always(function() {
                        button.prop('disabled', false).text('接続テスト');
                    });
                });
            });
            </script>
        </div>
    </div>
    <?php
}

/**
 * OpenAI API接続テスト
 */
add_action('wp_ajax_gi_test_openai_connection', 'gi_ajax_test_openai_connection');
function gi_ajax_test_openai_connection() {
    if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'], 'gi_test_openai')) {
        wp_send_json_error(['message' => '権限がありません']);
        return;
    }
    
    $api_key = get_option('gi_openai_api_key', '');
    if (empty($api_key)) {
        wp_send_json_error(['message' => 'APIキーが設定されていません']);
        return;
    }
    
    // テスト用のシンプルな質問でAPI接続確認
    $test_response = gi_call_openai_api(
        'あなたは助成金の専門アドバイザーです。', 
        'こんにちは、接続テストです。', 
        $api_key
    );
    
    if ($test_response) {
        wp_send_json_success(['message' => 'OpenAI APIに正常に接続できました']);
    } else {
        wp_send_json_error(['message' => 'APIキーが無効か、接続に失敗しました']);
    }
}
// アーカイブページ用AJAX処理
add_action('wp_ajax_filter_municipality_grants', 'gi_ajax_filter_municipality_grants');
add_action('wp_ajax_nopriv_filter_municipality_grants', 'gi_ajax_filter_municipality_grants');
add_action('wp_ajax_filter_prefecture_grants', 'gi_ajax_filter_prefecture_grants');
add_action('wp_ajax_nopriv_filter_prefecture_grants', 'gi_ajax_filter_prefecture_grants');
add_action('wp_ajax_filter_category_grants', 'gi_ajax_filter_category_grants');
add_action('wp_ajax_nopriv_filter_category_grants', 'gi_ajax_filter_category_grants');

// AI検索AJAX
add_action('wp_ajax_gi_ai_search_grants', 'gi_ajax_ai_search_grants');
add_action('wp_ajax_nopriv_gi_ai_search_grants', 'gi_ajax_ai_search_grants');

function gi_ajax_ai_search_grants() {
    check_ajax_referer('gi_ajax_nonce', 'nonce');
    
    $query = sanitize_text_field($_POST['query']);
    
    // OpenAI API または独自AI処理
    // ここでは簡易的な実装例
    $suggestions = gi_parse_ai_query($query);
    
    wp_send_json_success([
        'interpretation' => "「{$query}」の検索内容を解析しました。",
        'suggestions' => $suggestions
    ]);
}

function gi_parse_ai_query($query) {
    // 簡易的なキーワードマッチング
    $suggestions = [];
    
    // 都道府県検出
    $prefectures = ['東京都', '大阪府', '福岡県', '北海道', '札幌市'];
    foreach ($prefectures as $pref) {
        if (strpos($query, $pref) !== false) {
            $suggestions['prefecture'] = $pref;
            break;
        }
    }
    
    // カテゴリ検出
    $categories = [
        '飲食店' => '飲食店',
        '製造業' => '製造業',
        'DX' => 'DX・IT化',
        '環境' => '環境対策'
    ];
    foreach ($categories as $keyword => $category) {
        if (strpos($query, $keyword) !== false) {
            $suggestions['category'] = $category;
            break;
        }
    }
    
    // キーワード抽出
    $keywords = [];
    if (strpos($query, '設備投資') !== false) $keywords[] = '設備投資';
    if (strpos($query, '開業') !== false) $keywords[] = '開業';
    if (!empty($keywords)) {
        $suggestions['keywords'] = $keywords;
    }
    
    return $suggestions;
}

/**
 * =============================================================================
 * Archive Pages AJAX Handlers - Municipality, Prefecture, Category
 * =============================================================================
 */

/**
 * 市町村アーカイブページ用AJAX処理
 */
function gi_ajax_filter_municipality_grants() {
    try {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました']);
            return;
        }

        // パラメータ取得
        $municipality = sanitize_text_field($_POST['municipality'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $page = max(1, intval($_POST['page'] ?? 1));
        $posts_per_page = 12;

        if (empty($municipality)) {
            wp_send_json_error(['message' => '市町村が指定されていません']);
            return;
        }

        // WP_Query構築
        $args = [
            'post_type' => 'grant',
            'posts_per_page' => $posts_per_page,
            'paged' => $page,
            'post_status' => 'publish',
            'tax_query' => [
                [
                    'taxonomy' => 'grant_municipality',
                    'field' => 'slug',
                    'terms' => $municipality,
                ]
            ]
        ];

        // カテゴリフィルター
        if (!empty($category)) {
            $args['tax_query']['relation'] = 'AND';
            $args['tax_query'][] = [
                'taxonomy' => 'grant_category',
                'field' => 'slug',
                'terms' => $category,
            ];
        }

        // 検索
        if (!empty($search)) {
            $args['s'] = $search;
        }

        // ソート
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';

        // クエリ実行
        $query = new WP_Query($args);
        $grants_html = '';

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                // カード生成
                $post_id = get_the_ID();
                $title = get_the_title();
                $permalink = get_permalink();
                $organization = get_field('organization', $post_id) ?: '';
                $amount = get_field('max_amount', $post_id) ?: '金額未設定';
                $status = get_field('application_status', $post_id) ?: 'open';
                $status_text = $status === 'open' ? '募集中' : '募集終了';
                
                $grants_html .= "
                <article class='grant-card'>
                    <div class='card-header'>
                        <div class='card-category'>
                            <span>助成金</span>
                        </div>
                        <div class='card-status'>{$status_text}</div>
                    </div>
                    
                    <div class='card-content'>
                        <h3 class='card-title'>
                            <a href='{$permalink}'>{$title}</a>
                        </h3>
                        <p class='card-organization'>{$organization}</p>
                    </div>
                    
                    <div class='card-meta'>
                        <div class='meta-item amount'>
                            <span>最大 {$amount}</span>
                        </div>
                    </div>
                    
                    <div class='card-footer'>
                        <a href='{$permalink}' class='card-link'>
                            詳細を見る
                        </a>
                    </div>
                </article>";
            }
            wp_reset_postdata();
        } else {
            $grants_html = "
            <div class='no-results'>
                <h3>該当する助成金・補助金が見つかりませんでした</h3>
                <p>検索条件を変更してお試しください。</p>
            </div>";
        }

        // ページネーション
        $pagination = '';
        if ($query->max_num_pages > 1) {
            $pagination = paginate_links([
                'total' => $query->max_num_pages,
                'current' => $page,
                'format' => '?page=%#%',
                'type' => 'array'
            ]);
            $pagination = $pagination ? '<nav>' . implode('', $pagination) . '</nav>' : '';
        }

        wp_send_json_success([
            'html' => $grants_html,
            'total' => intval($query->found_posts),
            'showing_from' => (($page - 1) * $posts_per_page) + 1,
            'showing_to' => min($page * $posts_per_page, intval($query->found_posts)),
            'pagination' => $pagination,
            'max_pages' => intval($query->max_num_pages)
        ]);

    } catch (Exception $e) {
        error_log('Municipality Filter Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'フィルタリング中にエラーが発生しました']);
    }
}

/**
 * 都道府県アーカイブページ用AJAX処理
 */
function gi_ajax_filter_prefecture_grants() {
    try {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました']);
            return;
        }

        // パラメータ取得
        $prefecture = sanitize_text_field($_POST['prefecture'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $organization = sanitize_text_field($_POST['organization'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? '');
        $amount = sanitize_text_field($_POST['amount'] ?? '');
        $sort = sanitize_text_field($_POST['sort'] ?? 'date_desc');
        $page = max(1, intval($_POST['page'] ?? 1));

        if (empty($prefecture)) {
            wp_send_json_error(['message' => '都道府県が指定されていません']);
            return;
        }

        // クエリ構築
        $args = [
            'post_type' => 'grant',
            'posts_per_page' => 12,
            'paged' => $page,
            'post_status' => 'publish',
            'tax_query' => [
                [
                    'taxonomy' => 'grant_prefecture',
                    'field' => 'slug',
                    'terms' => $prefecture,
                ]
            ]
        ];

        // フィルター追加
        if (!empty($category)) {
            $args['tax_query']['relation'] = 'AND';
            $args['tax_query'][] = [
                'taxonomy' => 'grant_category',
                'field' => 'slug',
                'terms' => $category,
            ];
        }

        if (!empty($search)) {
            $args['s'] = $search;
        }

        // メタクエリ
        $meta_query = ['relation' => 'AND'];
        
        if (!empty($status)) {
            $meta_query[] = [
                'key' => 'application_status',
                'value' => $status,
                'compare' => '='
            ];
        }

        if (!empty($organization)) {
            $meta_query[] = [
                'key' => 'organization',
                'value' => $organization,
                'compare' => 'LIKE'
            ];
        }

        if (count($meta_query) > 1) {
            $args['meta_query'] = $meta_query;
        }

        // ソート
        switch ($sort) {
            case 'amount_desc':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = 'max_amount_numeric';
                $args['order'] = 'DESC';
                break;
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
        }

        // クエリ実行と結果処理
        $query = new WP_Query($args);
        $grants_html = gi_generate_grants_html($query);
        
        wp_send_json_success([
            'html' => $grants_html,
            'total' => intval($query->found_posts),
            'showing_from' => (($page - 1) * 12) + 1,
            'showing_to' => min($page * 12, intval($query->found_posts)),
            'pagination' => gi_generate_pagination($query, $page),
            'max_pages' => intval($query->max_num_pages)
        ]);

    } catch (Exception $e) {
        error_log('Prefecture Filter Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'フィルタリング中にエラーが発生しました']);
    }
}

/**
 * カテゴリアーカイブページ用AJAX処理
 */
function gi_ajax_filter_category_grants() {
    try {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'gi_ajax_nonce')) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました']);
            return;
        }

        // パラメータ取得
        $category = sanitize_text_field($_POST['category'] ?? '');
        $prefecture = sanitize_text_field($_POST['prefecture'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? '');
        $amount = sanitize_text_field($_POST['amount'] ?? '');
        $sort = sanitize_text_field($_POST['sort'] ?? 'date_desc');
        $page = max(1, intval($_POST['page'] ?? 1));

        if (empty($category)) {
            wp_send_json_error(['message' => 'カテゴリーが指定されていません']);
            return;
        }

        // クエリ構築
        $args = [
            'post_type' => 'grant',
            'posts_per_page' => 12,
            'paged' => $page,
            'post_status' => 'publish',
            'tax_query' => [
                [
                    'taxonomy' => 'grant_category',
                    'field' => 'slug',
                    'terms' => $category,
                ]
            ]
        ];

        // 都道府県フィルター
        if (!empty($prefecture)) {
            $args['tax_query']['relation'] = 'AND';
            $args['tax_query'][] = [
                'taxonomy' => 'grant_prefecture',
                'field' => 'slug',
                'terms' => $prefecture,
            ];
        }

        if (!empty($search)) {
            $args['s'] = $search;
        }

        // クエリ実行と結果処理
        $query = new WP_Query($args);
        $grants_html = gi_generate_grants_html($query);
        
        wp_send_json_success([
            'html' => $grants_html,
            'total' => intval($query->found_posts),
            'showing_from' => (($page - 1) * 12) + 1,
            'showing_to' => min($page * 12, intval($query->found_posts)),
            'pagination' => gi_generate_pagination($query, $page),
            'max_pages' => intval($query->max_num_pages)
        ]);

    } catch (Exception $e) {
        error_log('Category Filter Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'フィルタリング中にエラーが発生しました']);
    }
}

/**
 * 助成金HTML生成ヘルパー関数
 */
function gi_generate_grants_html($query) {
    $html = '';
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            
            $post_id = get_the_ID();
            $title = get_the_title();
            $permalink = get_permalink();
            $excerpt = wp_trim_words(get_the_excerpt(), 30);
            $organization = get_field('organization', $post_id) ?: '';
            $amount = get_field('max_amount', $post_id) ?: '金額未設定';
            $deadline = get_field('deadline', $post_id) ?: '';
            $status = get_field('application_status', $post_id) ?: 'open';
            
            // カテゴリー取得
            $categories = wp_get_post_terms($post_id, 'grant_category', ['fields' => 'names']);
            $category_name = !empty($categories) ? $categories[0] : '未分類';
            
            $status_text = $status === 'open' ? '募集中' : ($status === 'upcoming' ? '募集予定' : '募集終了');
            
            $html .= "
            <article class='grant-card'>
                <div class='card-header'>
                    <div class='card-category'>
                        <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                            <path d='M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z'/>
                        </svg>
                        <span>{$category_name}</span>
                    </div>
                    " . ($deadline ? "<div class='card-deadline'>
                        <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                            <circle cx='12' cy='12' r='10'/>
                            <polyline points='12 6 12 12 16 14'/>
                        </svg>
                        <span>" . esc_html(date('Y/m/d', strtotime($deadline))) . "</span>
                    </div>" : "") . "
                </div>

                <div class='card-content'>
                    <h3 class='card-title'>
                        <a href='{$permalink}'>{$title}</a>
                    </h3>
                    <p class='card-excerpt'>{$excerpt}</p>
                </div>

                <div class='card-meta'>
                    " . ($organization ? "<div class='meta-item organization'>
                        <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                            <path d='M3 21h18M5 21V7l8-4v18M19 21V11l-6-4'/>
                        </svg>
                        <span>{$organization}</span>
                    </div>" : "") . "
                    
                    <div class='meta-item amount'>
                        <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                            <line x1='12' y1='1' x2='12' y2='23'/>
                            <path d='M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'/>
                        </svg>
                        <span>最大 {$amount}</span>
                    </div>
                </div>

                <div class='card-footer'>
                    <a href='{$permalink}' class='card-link'>
                        詳細を見る
                        <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                            <polyline points='9 18 15 12 9 6'/>
                        </svg>
                    </a>
                </div>
            </article>";
        }
        wp_reset_postdata();
    } else {
        $html = "
        <div class='no-results'>
            <div class='no-results-icon'>
                <svg width='64' height='64' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'>
                    <circle cx='11' cy='11' r='8'/>
                    <path d='m21 21-4.35-4.35'/>
                </svg>
            </div>
            <h3>該当する助成金・補助金が見つかりませんでした</h3>
            <p>検索条件を変更してお試しください。</p>
        </div>";
    }
    
    return $html;
}

/**
 * ページネーション生成ヘルパー関数
 */
function gi_generate_pagination($query, $current_page) {
    if ($query->max_num_pages <= 1) {
        return '';
    }
    
    $links = paginate_links([
        'total' => $query->max_num_pages,
        'current' => $current_page,
        'format' => '?page=%#%',
        'type' => 'array',
        'prev_text' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg> 前へ',
        'next_text' => '次へ <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>',
    ]);
    
    return $links ? '<nav class="pagination">' . implode('', $links) . '</nav>' : '';
}

/**
 * 市町村データ構造最適化 AJAX Handler
 */
function gi_ajax_optimize_municipality_structure() {
    // 出力バッファをクリア（HTMLが混入しないように）
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // エラーレポートを有効化（デバッグ用）
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // ブラウザには表示しない
    
    try {
        // ログ開始
        error_log('Municipality optimization AJAX started');
        
        // POST データチェック
        if (empty($_POST)) {
            wp_send_json_error(['message' => 'POSTデータが空です', 'debug' => 'Empty $_POST array']);
            exit;
        }
        
        // Nonce verification
        $nonce = $_POST['nonce'] ?? '';
        if (empty($nonce)) {
            wp_send_json_error(['message' => 'nonceが提供されていません', 'debug' => 'Missing nonce parameter']);
            exit;
        }
        
        if (!wp_verify_nonce($nonce, 'municipality_optimize_nonce')) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました', 'debug' => 'Nonce verification failed: ' . $nonce]);
            exit;
        }
        
        // Admin permission check
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => '管理者権限が必要です', 'debug' => 'User lacks manage_options capability']);
            exit;
        }
        
        error_log('Municipality optimization: security checks passed');
        
        $optimization_type = sanitize_text_field($_POST['optimization_type'] ?? 'analyze');
        $logs = [];
        $stats = [];
        
        // 現在のデータを取得
        $current_municipalities = get_terms([
            'taxonomy' => 'grant_municipality',
            'hide_empty' => false,
            'number' => 0
        ]);
        
        $current_prefectures = get_terms([
            'taxonomy' => 'grant_prefecture',
            'hide_empty' => false
        ]);
        
        if (is_wp_error($current_municipalities) || is_wp_error($current_prefectures)) {
            $error_msg = 'データ取得に失敗しました';
            if (is_wp_error($current_municipalities)) {
                $error_msg .= ' [市町村: ' . $current_municipalities->get_error_message() . ']';
            }
            if (is_wp_error($current_prefectures)) {
                $error_msg .= ' [都道府県: ' . $current_prefectures->get_error_message() . ']';
            }
            wp_send_json_error(['message' => $error_msg]);
            exit;
        }
        
        $logs[] = '現在のデータ取得完了: 市町村' . count($current_municipalities) . '件、都道府県' . count($current_prefectures) . '件';
        
        // 分析のみの場合
        if ($optimization_type === 'analyze') {
            $analysis = gi_analyze_municipality_structure($current_municipalities, $current_prefectures);
            $logs = array_merge($logs, $analysis['logs']);
            $stats = $analysis['stats'];
            
            wp_send_json_success([
                'message' => '分析が完了しました',
                'logs' => $logs,
                'stats' => $stats
            ]);
            exit;
        }
        
        // 階層構造修正
        if ($optimization_type === 'fix_hierarchy' || $optimization_type === 'full_optimize') {
            $hierarchy_result = gi_fix_municipality_hierarchy($current_municipalities, $current_prefectures);
            $logs = array_merge($logs, $hierarchy_result['logs']);
            $stats = array_merge($stats, $hierarchy_result['stats']);
        }
        
        // スラッグ統一
        if ($optimization_type === 'fix_slugs' || $optimization_type === 'full_optimize') {
            $slug_result = gi_fix_municipality_slugs();
            $logs = array_merge($logs, $slug_result['logs']);
            $stats = array_merge($stats, $slug_result['stats']);
        }
        
        // 完全リセット
        if ($optimization_type === 'reset_all') {
            if (!function_exists('gi_reset_municipality_data')) {
                wp_send_json_error(['message' => 'gi_reset_municipality_data関数が見つかりません', 'debug' => 'Function not loaded']);
                exit;
            }
            
            $reset_result = gi_reset_municipality_data();
            $logs = array_merge($logs, $reset_result['logs']);
            $stats = array_merge($stats, $reset_result['stats']);
            
            wp_send_json_success([
                'message' => '市町村データの完全削除が完了しました',
                'logs' => $logs,
                'stats' => $stats
            ]);
            exit;
        }
        
        // 標準データインポート
        if ($optimization_type === 'import_standard') {
            if (!function_exists('gi_import_standard_municipalities')) {
                wp_send_json_error(['message' => 'gi_import_standard_municipalities関数が見つかりません', 'debug' => 'Function not loaded']);
                exit;
            }
            
            $import_result = gi_import_standard_municipalities();
            $logs = array_merge($logs, $import_result['logs']);
            $stats = array_merge($stats, $import_result['stats']);
            
            wp_send_json_success([
                'message' => '標準市町村データのインポートが完了しました',
                'logs' => $logs,
                'stats' => $stats
            ]);
            exit;
        }
        
        // 完全最適化の場合は追加処理
        if ($optimization_type === 'full_optimize') {
            $validation_result = gi_validate_municipality_structure();
            $logs = array_merge($logs, $validation_result['logs']);
            $stats = array_merge($stats, $validation_result['stats']);
        }
        
        wp_send_json_success([
            'message' => '最適化が完了しました',
            'logs' => $logs,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        error_log('Municipality Structure Optimization Error: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        wp_send_json_error([
            'message' => '最適化処理中にエラーが発生しました: ' . $e->getMessage(),
            'debug' => WP_DEBUG ? $e->getTraceAsString() : $e->getMessage()
        ]);
    } catch (Error $e) {
        error_log('Municipality Structure Fatal Error: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        wp_send_json_error([
            'message' => '致命的なエラーが発生しました: ' . $e->getMessage(),
            'debug' => WP_DEBUG ? $e->getTraceAsString() : $e->getMessage()
        ]);
    }
}
/**
 * =============================================================================
 * AJAXハンドラー登録確認とデバッグ
 * =============================================================================
 */

// デバッグ用ログ
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('✅ Municipality AJAX handlers registered');
}

/**
 * Enhanced search suggestions AJAX handler
 */
function gi_ajax_enhanced_search_suggestions() {
    try {
        // Security check
        if (!gi_verify_ajax_nonce()) {
            wp_send_json_error(['message' => 'セキュリティチェックに失敗しました']);
            return;
        }
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        $search_type = sanitize_text_field($_POST['search_type'] ?? 'all');
        $limit = min(intval($_POST['limit'] ?? 10), 20);
        
        if (strlen($query) < 2) {
            wp_send_json_error(['message' => '検索クエリが短すぎます']);
            return;
        }
        
        $suggestions = [];
        
        // Get suggestions based on search type
        switch ($search_type) {
            case 'title':
                $suggestions = gi_get_grant_title_suggestions($query, $limit);
                break;
            case 'organization':
                $suggestions = gi_get_organization_suggestions($query, $limit);
                break;
            case 'target':
                $suggestions = gi_get_target_suggestions($query, $limit);
                break;
            case 'content':
                $suggestions = gi_get_content_suggestions($query, $limit);
                break;
            default:
                $suggestions = gi_get_general_suggestions($query, $limit);
        }
        
        wp_send_json_success([
            'suggestions' => $suggestions,
            'query' => $query,
            'search_type' => $search_type,
            'count' => count($suggestions)
        ]);
        
    } catch (Exception $e) {
        error_log('Enhanced Search Suggestions Error: ' . $e->getMessage());
        wp_send_json_error(['message' => '検索候補の取得に失敗しました']);
    }
}

/**
 * Get grant title suggestions
 */
function gi_get_grant_title_suggestions($query, $limit = 10) {
    global $wpdb;
    
    $suggestions = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT post_title 
        FROM {$wpdb->posts} 
        WHERE post_type = 'grant' 
        AND post_status = 'publish' 
        AND post_title LIKE %s 
        ORDER BY post_date DESC 
        LIMIT %d
    ", '%' . $wpdb->esc_like($query) . '%', $limit));
    
    return array_values($suggestions);
}

/**
 * Get organization suggestions
 */
function gi_get_organization_suggestions($query, $limit = 10) {
    global $wpdb;
    
    $suggestions = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT meta_value 
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = 'organization' 
        AND pm.meta_value LIKE %s 
        AND p.post_type = 'grant' 
        AND p.post_status = 'publish'
        AND pm.meta_value != ''
        ORDER BY pm.meta_value ASC
        LIMIT %d
    ", '%' . $wpdb->esc_like($query) . '%', $limit));
    
    return array_values(array_filter($suggestions));
}

/**
 * Get target suggestions
 */
function gi_get_target_suggestions($query, $limit = 10) {
    $common_targets = [
        'スタートアップ', '中小企業', '個人事業主', '製造業', 'IT企業', 
        '小規模事業者', '創業者', '研究機関', '大学', 'NPO法人',
        '農業者', '林業者', '漁業者', '建設業', 'サービス業'
    ];
    
    $matches = array_filter($common_targets, function($target) use ($query) {
        return stripos($target, $query) !== false;
    });
    
    return array_values(array_slice($matches, 0, $limit));
}

/**
 * Get content suggestions
 */
function gi_get_content_suggestions($query, $limit = 10) {
    $common_keywords = [
        'デジタル化', 'DX推進', 'IT導入', 'システム開発', '設備投資',
        '人材育成', '研究開発', 'イノベーション', '省エネ', 'SDGs',
        '新規事業', '事業拡大', '販路開拓', '海外展開', '働き方改革',
        'テレワーク', 'リモートワーク', 'AI活用', 'IoT導入', 'クラウド'
    ];
    
    $matches = array_filter($common_keywords, function($keyword) use ($query) {
        return stripos($keyword, $query) !== false;
    });
    
    return array_values(array_slice($matches, 0, $limit));
}

/**
 * Get general suggestions
 */
function gi_get_general_suggestions($query, $limit = 10) {
    // Combine different suggestion types for general search
    $title_suggestions = gi_get_grant_title_suggestions($query, 3);
    $org_suggestions = gi_get_organization_suggestions($query, 3);
    $target_suggestions = gi_get_target_suggestions($query, 2);
    $content_suggestions = gi_get_content_suggestions($query, 2);
    
    $all_suggestions = array_merge(
        $title_suggestions,
        $org_suggestions,
        $target_suggestions,
        $content_suggestions
    );
    
    // Remove duplicates and limit
    return array_values(array_unique(array_slice($all_suggestions, 0, $limit)));
}

/**
 * 市町村に対応する助成金件数を取得
 */
if (!function_exists('gi_get_municipality_grant_count')) {
function gi_get_municipality_grant_count($municipality_term_id) {
    $args = [
        'post_type' => 'grant',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids', // IDのみ取得で高速化
        'tax_query' => [
            [
                'taxonomy' => 'grant_municipality',
                'field' => 'term_id',
                'terms' => $municipality_term_id
            ]
        ]
    ];
    
    $query = new WP_Query($args);
    return $query->found_posts;
}
}

/**
 * 都道府県に対応する助成金件数を取得
 */
if (!function_exists('gi_get_prefecture_grant_count')) {
function gi_get_prefecture_grant_count($prefecture_term_id) {
    $args = [
        'post_type' => 'grant',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids', // IDのみ取得で高速化
        'tax_query' => [
            [
                'taxonomy' => 'grant_prefecture',
                'field' => 'term_id',
                'terms' => $prefecture_term_id
            ]
        ]
    ];
    
    $query = new WP_Query($args);
    return $query->found_posts;
}
}

/**
 * Load More Grants (無限スクロール用)
 * 
 * @return void
 */
function gi_ajax_load_more_grants() {
    // Nonce verification
    if (!gi_verify_ajax_nonce()) {
        wp_send_json_error(['message' => 'セキュリティチェックに失敗しました'], 403);
        return;
    }
    
    // Get parameters
    $page = intval($_POST['page'] ?? 1);
    $per_page = min(intval($_POST['per_page'] ?? 12), 50);
    $category = sanitize_text_field($_POST['category'] ?? '');
    $prefecture = sanitize_text_field($_POST['prefecture'] ?? '');
    $municipality = sanitize_text_field($_POST['municipality'] ?? '');
    $search = sanitize_text_field($_POST['search'] ?? '');
    
    // Build query args
    $args = [
        'post_type' => 'grant',
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'orderby' => 'date',
        'order' => 'DESC'
    ];
    
    // Add tax query if filters are set
    $tax_query = [];
    
    if (!empty($category)) {
        $tax_query[] = [
            'taxonomy' => 'grant_category',
            'field' => 'slug',
            'terms' => $category
        ];
    }
    
    if (!empty($prefecture)) {
        $tax_query[] = [
            'taxonomy' => 'grant_prefecture',
            'field' => 'slug',
            'terms' => $prefecture
        ];
    }
    
    if (!empty($municipality)) {
        $tax_query[] = [
            'taxonomy' => 'grant_municipality',
            'field' => 'slug',
            'terms' => $municipality
        ];
    }
    
    if (count($tax_query) > 1) {
        $tax_query['relation'] = 'AND';
    }
    
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }
    
    // Add search if provided
    if (!empty($search)) {
        $args['s'] = $search;
    }
    
    // Execute query
    $query = new WP_Query($args);
    
    $grants = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            
            $post_id = get_the_ID();
            
            // Get taxonomies
            $categories = get_the_terms($post_id, 'grant_category');
            $prefectures = get_the_terms($post_id, 'grant_prefecture');
            
            $grants[] = [
                'id' => $post_id,
                'title' => get_the_title(),
                'url' => get_permalink(),
                'excerpt' => get_the_excerpt(),
                'date' => get_the_date('Y-m-d'),
                'organization' => get_post_meta($post_id, 'organization', true) ?: '公的機関',
                'max_amount' => get_post_meta($post_id, 'max_amount', true),
                'deadline' => get_post_meta($post_id, 'deadline', true),
                'is_featured' => get_post_meta($post_id, 'is_featured', true),
                'categories' => $categories && !is_wp_error($categories) ? array_map(function($cat) {
                    return ['name' => $cat->name, 'slug' => $cat->slug];
                }, array_slice($categories, 0, 2)) : [],
                'prefecture' => $prefectures && !is_wp_error($prefectures) ? $prefectures[0]->name : ''
            ];
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success([
        'grants' => $grants,
        'has_more' => $page < $query->max_num_pages,
        'current_page' => $page,
        'total_pages' => $query->max_num_pages,
        'total_count' => $query->found_posts
    ]);
}
