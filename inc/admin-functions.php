<?php
/**
 * Grant Insight Perfect - Admin Functions (Consolidated)
 * 
 * Consolidated admin functionality including customization, metaboxes, and admin UI.
 * 
 * @package Grant_Insight_Perfect  
 * @version 9.0.0 (Consolidated Edition)
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

/**
 * =============================================================================
 * 1. 管理画面カスタマイズ（基本機能）
 * =============================================================================
 */

/**
 * 管理画面カスタマイズ（強化版）
 */
function gi_admin_init() {
    // 管理画面でのjQuery読み込み
    add_action('admin_enqueue_scripts', function() {
        wp_enqueue_script('jquery');
    });
    
    // 管理画面スタイル
    add_action('admin_head', function() {
        echo '<style>
        .gi-admin-notice {
            border-left: 4px solid #10b981;
            background: #ecfdf5;
            padding: 12px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .gi-admin-notice h3 {
            color: #047857;
            margin: 0 0 8px 0;
            font-size: 16px;
        }
        .gi-admin-notice p {
            color: #065f46;
            margin: 0;
        }
        .notice.inline {
            margin: 15px 0;
        }
        .gi-progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .gi-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            transition: width 0.3s ease;
        }
        </style>';
    });
    
    // 投稿一覧カラム追加
    add_filter('manage_grant_posts_columns', 'gi_add_grant_columns');
    add_action('manage_grant_posts_custom_column', 'gi_grant_column_content', 10, 2);
}
add_action('admin_init', 'gi_admin_init');

/**
 * 助成金一覧にカスタムカラムを追加
 */
function gi_add_grant_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['gi_prefecture'] = '都道府県';
            $new_columns['gi_amount'] = '金額';
            $new_columns['gi_organization'] = '実施組織';
            $new_columns['gi_status'] = 'ステータス';
        }
    }
    return $new_columns;
}

/**
 * カスタムカラムに内容を表示
 */
function gi_grant_column_content($column, $post_id) {
    switch ($column) {
        case 'gi_prefecture':
            $prefecture_terms = get_the_terms($post_id, 'grant_prefecture');
            if ($prefecture_terms && !is_wp_error($prefecture_terms)) {
                echo gi_safe_escape($prefecture_terms[0]->name);
            } else {
                echo '－';
            }
            break;
        case 'gi_amount':
            $amount = gi_safe_get_meta($post_id, 'max_amount');
            echo $amount ? gi_safe_escape($amount) . '万円' : '－';
            break;
        case 'gi_organization':
            echo gi_safe_escape(gi_safe_get_meta($post_id, 'organization', '－'));
            break;
        case 'gi_status':
            $status = gi_map_application_status_ui(gi_safe_get_meta($post_id, 'application_status', 'open'));
            $status_labels = array(
                'active' => '<span style="color: #059669;">募集中</span>',
                'upcoming' => '<span style="color: #d97706;">募集予定</span>',
                'closed' => '<span style="color: #dc2626;">募集終了</span>'
            );
            echo $status_labels[$status] ?? $status;
            break;
    }
}



/**
 * =============================================================================
 * 4. 管理メニューの追加
 * =============================================================================
 */

/**
 * 管理メニューの追加（修正版）
 */
function gi_add_admin_menu() {
    
    // AI設定メニュー追加
    add_menu_page(
        'Enhanced AI Settings',
        'AI Settings',
        'manage_options',
        'gi-ai-settings',
        'gi_ai_settings_page',
        'dashicons-superhero-alt',
        30
    );
    
    // AI検索統計サブメニュー
    add_submenu_page(
        'gi-ai-settings',
        'AI検索統計',
        '統計・レポート',
        'manage_options',
        'gi-ai-statistics',
        'gi_ai_statistics_page'
    );
}
add_action('admin_menu', 'gi_add_admin_menu');

/**
 * Prefecture Debug Menu（修正版）
 */
function gi_add_prefecture_debug_menu() {
    add_submenu_page(
        'edit.php?post_type=grant',
        '都道府県デバッグ',
        '都道府県デバッグ',
        'manage_options',
        'gi-prefecture-debug',
        'gi_prefecture_debug_page'
    );
    
    // Excel管理とGoogle Sheets連携機能は完全削除済み
}
add_action('admin_menu', 'gi_add_prefecture_debug_menu');

/**
 * =============================================================================
 * 5. Prefecture Debug Page
 * =============================================================================
 */

/**
 * Prefecture Debug Page
 */
function gi_prefecture_debug_page() {
    if (!current_user_can('manage_options')) {
        wp_die('権限がありません。');
    }
    
    // Actions
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'refresh_counts' && wp_verify_nonce($_POST['_wpnonce'], 'gi_prefecture_debug')) {
            delete_transient('gi_prefecture_counts_v2');
            echo '<div class="notice notice-success"><p>カウンターキャッシュをクリアしました。</p></div>';
        }
        
        if ($_POST['action'] === 'ensure_terms' && wp_verify_nonce($_POST['_wpnonce'], 'gi_prefecture_debug')) {
            $missing_count = gi_ensure_prefecture_terms();
            if ($missing_count > 0) {
                echo "<div class='notice notice-success'><p>{$missing_count}個の都道府県タームを作成しました。</p></div>";
            } else {
                echo '<div class="notice notice-info"><p>すべての都道府県タームが存在します。</p></div>';
            }
        }
    }
    
    // Get data
    $prefecture_counts = gi_get_prefecture_counts();
    $assignment_stats = gi_check_grant_prefecture_assignments();
    
    ?>
    <div class="wrap">
        <h1>都道府県デバッグツール</h1>
        
        <div class="gi-admin-notice">
            <h3>統計情報</h3>
            <p><strong>総助成金投稿:</strong> <?php echo $assignment_stats['total_grants']; ?>件</p>
            <p><strong>都道府県設定済み:</strong> <?php echo $assignment_stats['assigned_grants']; ?>件 (<?php echo $assignment_stats['assignment_ratio']; ?>%)</p>
            <p><strong>都道府県未設定:</strong> <?php echo $assignment_stats['unassigned_grants']; ?>件</p>
        </div>
        
        <div class="postbox">
            <h2 class="hndle">🔧 管理ツール</h2>
            <div class="inside">
                <form method="post" style="display:inline-block;margin-right:10px;">
                    <?php wp_nonce_field('gi_prefecture_debug'); ?>
                    <input type="hidden" name="action" value="refresh_counts">
                    <input type="submit" class="button button-primary" value="🔄 カウンターを再計算">
                </form>
                
                <form method="post" style="display:inline-block;">
                    <?php wp_nonce_field('gi_prefecture_debug'); ?>
                    <input type="hidden" name="action" value="ensure_terms">
                    <input type="submit" class="button button-secondary" value="🏷️ 都道府県タームを確認・作成">
                </form>
            </div>
        </div>
        
        <?php if ($assignment_stats['assigned_grants'] > 0) : ?>
        <div class="postbox">
            <h2 class="hndle">都道府県別投稿数</h2>
            <div class="inside">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width:150px;">都道府県</th>
                            <th style="width:100px;">投稿数</th>
                            <th style="width:100px;">地域</th>
                            <th>アクション</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $all_prefectures = gi_get_all_prefectures();
                        foreach ($all_prefectures as $pref) :
                            $count = isset($prefecture_counts[$pref['slug']]) ? $prefecture_counts[$pref['slug']] : 0;
                            if ($count > 0) :
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($pref['name']); ?></strong></td>
                            <td>
                                <span class="badge" style="background:#007cba;color:white;padding:2px 6px;border-radius:3px;font-size:12px;">
                                    <?php echo $count; ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(ucfirst($pref['region'])); ?></td>
                            <td>
                                <?php
                                $prefecture_url = add_query_arg(
                                    array(
                                        'post_type' => 'grant',
                                        'grant_prefecture' => $pref['slug']
                                    ),
                                    admin_url('edit.php')
                                );
                                ?>
                                <a href="<?php echo esc_url($prefecture_url); ?>" class="button button-small">投稿を表示</a>
                            </td>
                        </tr>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else : ?>
        <div class="notice notice-warning">
            <h3>都道府県設定が必要です</h3>
            <p>助成金投稿に都道府県が設定されていません。以下の方法で設定してください：</p>
            <ol>
                <li><strong>手動設定:</strong> <a href="<?php echo admin_url('edit.php?post_type=grant'); ?>">助成金投稿一覧</a> で各投稿を編集し、都道府県を選択</li>
                <li><strong>一括編集:</strong> 投稿一覧で複数選択して一括編集機能を使用</li>
                <li><strong>インポート修正:</strong> インポート機能を使用している場合は、都道府県マッピングを確認</li>
            </ol>
        </div>
        <?php endif; ?>
        
        <div class="postbox">
            <h2 class="hndle">デバッグ情報</h2>
            <div class="inside">
                <p><strong>キャッシュ状態:</strong> <?php echo get_transient('gi_prefecture_counts_v2') !== false ? '有効' : '無効'; ?></p>
                <p><strong>都道府県タクソノミー:</strong> <?php echo taxonomy_exists('grant_prefecture') ? '存在' : '不存在'; ?></p>
                <p><strong>grant投稿タイプ:</strong> <?php echo post_type_exists('grant') ? '存在' : '不存在'; ?></p>
                <p><strong>Debug Mode:</strong> <?php echo defined('WP_DEBUG') && WP_DEBUG ? 'ON' : 'OFF'; ?></p>
            </div>
        </div>
    </div>
    <?php
}



/**
 * =============================================================================
 * 7. AI設定ページ
 * =============================================================================
 */

/**
 * Enhanced AI設定ページ（Multi-AI対応版）
 */
function gi_ai_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Get AI manager instance
    $ai_manager = GI_Multi_AI_Manager::getInstance();
    $available_providers = $ai_manager->get_available_providers();
    
    // 設定の保存処理
    if (isset($_POST['save_ai_settings']) && wp_verify_nonce($_POST['ai_settings_nonce'], 'gi_ai_settings')) {
        $settings = [
            'enable_ai_search' => isset($_POST['enable_ai_search']) ? 1 : 0,
            'enable_voice_input' => isset($_POST['enable_voice_input']) ? 1 : 0,
            'enable_ai_chat' => isset($_POST['enable_ai_chat']) ? 1 : 0,
            'enable_streaming' => isset($_POST['enable_streaming']) ? 1 : 0,
            'enable_enhanced_processing' => isset($_POST['enable_enhanced_processing']) ? 1 : 0,
            'preferred_ai_provider' => sanitize_text_field($_POST['preferred_ai_provider'] ?? 'openai'),
            'preferred_ai_model' => sanitize_text_field($_POST['preferred_ai_model'] ?? 'auto'),
            'enable_intent_analysis' => isset($_POST['enable_intent_analysis']) ? 1 : 0,
            'enable_rag' => isset($_POST['enable_rag']) ? 1 : 0
        ];
        
        update_option('gi_ai_settings', $settings);
        
        // OpenAI APIキーの保存
        if (isset($_POST['openai_api_key'])) {
            $api_key = sanitize_text_field($_POST['openai_api_key']);
            update_option('gi_openai_api_key', $api_key);
        }
        
        // Gemini APIキーの保存
        if (isset($_POST['gemini_api_key'])) {
            $api_key = sanitize_text_field($_POST['gemini_api_key']);
            update_option('gi_gemini_api_key', $api_key);
        }
        
        echo '<div class="notice notice-success"><p>設定を保存しました。</p></div>';
    }
    
    // API接続テスト
    $test_results = [];
    if (isset($_POST['test_all_connections']) && wp_verify_nonce($_POST['ai_settings_nonce'], 'gi_ai_settings')) {
        $test_results = $ai_manager->test_all_connections();
    }
    
    // サンプルデータ作成処理
    if (isset($_POST['create_sample_data']) && wp_verify_nonce($_POST['ai_settings_nonce'], 'gi_ai_settings')) {
        require_once(get_template_directory() . '/sample-data-creator.php');
        
        $grants_created = create_sample_grant_data();
        $faqs_created = create_sample_faq_data();
        
        echo '<div class="notice notice-success"><p>✅ サンプルデータを作成しました: 助成金 ' . $grants_created . '件、FAQ ' . $faqs_created . '件</p></div>';
    }
    
    // 現在の設定を取得
    $settings = get_option('gi_ai_settings', [
        'enable_ai_search' => 1,
        'enable_voice_input' => 1,
        'enable_ai_chat' => 1,
        'enable_streaming' => 1,
        'enable_enhanced_processing' => 1,
        'preferred_ai_provider' => 'openai',
        'preferred_ai_model' => 'auto',
        'enable_intent_analysis' => 1,
        'enable_rag' => 1
    ]);
    
    // APIキーを取得
    $openai_key = get_option('gi_openai_api_key', '');
    $gemini_key = get_option('gi_gemini_api_key', '');
    $openai_key_display = !empty($openai_key) ? str_repeat('*', 20) . substr($openai_key, -4) : '';
    $gemini_key_display = !empty($gemini_key) ? str_repeat('*', 20) . substr($gemini_key, -4) : '';
    
    // Get system status
    $knowledge_status = gi_get_knowledge_system_status();
    ?>
    <div class="wrap">
        <h1>🚀 Enhanced AI System Settings</h1>
        <p class="description">2025年版総合AIシステムの設定。OpenAI + Gemini API対応、意図分析、RAG、ストリーミング表示を統合管理。</p>
        
        <?php if (!empty($test_results)): ?>
        <div id="connection-test-results">
            <?php foreach ($test_results as $provider => $result): ?>
                <div class="notice <?php echo $result['success'] ? 'notice-success' : 'notice-error'; ?>">
                    <p><strong><?php echo ucfirst($provider); ?>:</strong> <?php echo esc_html($result['message']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <form method="post" action="" id="ai-settings-form">
            <?php wp_nonce_field('gi_ai_settings', 'ai_settings_nonce'); ?>
            
            <div class="postbox">
                <h2 class="hndle">🤖 AI Provider Configuration</h2>
                <div class="inside">
                    <!-- OpenAI API設定 -->
                    <h3>OpenAI API</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="openai_api_key">OpenAI APIキー</label>
                            </th>
                            <td>
                                <input type="password" id="openai_api_key" name="openai_api_key" 
                                       value="<?php echo esc_attr($openai_key); ?>" 
                                       class="regular-text" 
                                       placeholder="sk-..." />
                                <p class="description">
                                    GPT-3.5-turbo, GPT-4, GPT-4-turboに対応
                                    <?php if (!empty($openai_key_display)): ?>
                                        <br><strong>現在の設定:</strong> <code><?php echo esc_html($openai_key_display); ?></code>
                                    <?php endif; ?>
                                    <br><a href="https://platform.openai.com/api-keys" target="_blank">APIキーを取得 →</a>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- Gemini API設定 -->
                    <h3>Google Gemini API</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="gemini_api_key">Gemini APIキー</label>
                            </th>
                            <td>
                                <input type="password" id="gemini_api_key" name="gemini_api_key" 
                                       value="<?php echo esc_attr($gemini_key); ?>" 
                                       class="regular-text" 
                                       placeholder="AI..." />
                                <p class="description">
                                    Gemini 2.5 Pro, Gemini 1.5 Pro, Gemini 1.5 Flashに対応
                                    <?php if (!empty($gemini_key_display)): ?>
                                        <br><strong>現在の設定:</strong> <code><?php echo esc_html($gemini_key_display); ?></code>
                                    <?php endif; ?>
                                    <br><a href="https://ai.google.dev/gemini-api/docs" target="_blank">APIキーを取得 →</a>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- Provider選択 -->
                    <h3>Primary AI Provider</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">優先AIプロバイダー</th>
                            <td>
                                <select name="preferred_ai_provider" id="preferred_ai_provider">
                                    <option value="openai" <?php selected($settings['preferred_ai_provider'], 'openai'); ?>>OpenAI (GPT)</option>
                                    <option value="gemini" <?php selected($settings['preferred_ai_provider'], 'gemini'); ?>>Google Gemini</option>
                                </select>
                                <p class="description">メインで使用するAIプロバイダーを選択してください。自動フォールバック機能付き。</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">モデル選択</th>
                            <td>
                                <select name="preferred_ai_model" id="preferred_ai_model">
                                    <option value="auto" <?php selected($settings['preferred_ai_model'], 'auto'); ?>>自動選択（タスクに最適）</option>
                                    <option value="fast" <?php selected($settings['preferred_ai_model'], 'fast'); ?>>高速モード（簡単な質問）</option>
                                    <option value="advanced" <?php selected($settings['preferred_ai_model'], 'advanced'); ?>>高性能モード（複雑な分析）</option>
                                </select>
                                <p class="description">処理内容に応じて最適なモデルを自動選択するか、固定モードを選択できます。</p>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- Connection Test - Moved to submit section -->
                </div>
            </div>
            
            <div class="postbox">
                <h2 class="hndle">⚙️ Enhanced AI Features</h2>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row">基本AI機能</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="enable_ai_search" value="1" 
                                            <?php checked($settings['enable_ai_search'], 1); ?>>
                                        🔍 AI検索機能（セマンティック検索）
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="enable_voice_input" value="1" 
                                            <?php checked($settings['enable_voice_input'], 1); ?>>
                                        🎤 音声入力（Whisper API）
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="enable_ai_chat" value="1" 
                                            <?php checked($settings['enable_ai_chat'], 1); ?>>
                                        💬 AIチャットアシスタント
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Enhanced Features (2025)</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="enable_intent_analysis" value="1" 
                                            <?php checked($settings['enable_intent_analysis'], 1); ?>>
                                        🎯 <strong>意図分析システム</strong>（質問タイプの自動分類）
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="enable_enhanced_processing" value="1" 
                                            <?php checked($settings['enable_enhanced_processing'], 1); ?>>
                                        🧠 <strong>動的処理エンジン</strong>（意図に基づく最適化処理）
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="enable_streaming" value="1" 
                                            <?php checked($settings['enable_streaming'], 1); ?>>
                                        ⚡ <strong>ストリーミング表示</strong>（ChatGPT風のリアルタイム表示）
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="enable_rag" value="1" 
                                            <?php checked($settings['enable_rag'], 1); ?>>
                                        📚 <strong>RAG機能</strong>（外部文書検索・統合）
                                    </label>
                                </fieldset>
                                <p class="description">2025年版の高度なAI機能。複雑な質問や比較分析に対応します。</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="postbox">
                <h2 class="hndle">📊 Knowledge System Status</h2>
                <div class="inside">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Component</th>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Grant Database</strong></td>
                                <td><?php echo gi_status_badge($knowledge_status['grants_count'] > 0); ?></td>
                                <td><?php echo number_format($knowledge_status['grants_count']); ?></td>
                                <td>助成金データベース</td>
                            </tr>
                            <tr>
                                <td><strong>FAQ System</strong></td>
                                <td><?php echo gi_status_badge($knowledge_status['faqs_count'] > 0); ?></td>
                                <td><?php echo number_format($knowledge_status['faqs_count']); ?></td>
                                <td>よくある質問</td>
                            </tr>
                            <tr>
                                <td><strong>RAG Documents</strong></td>
                                <td><?php echo gi_status_badge($knowledge_status['rag_documents'] > 0); ?></td>
                                <td><?php echo number_format($knowledge_status['rag_documents']); ?></td>
                                <td>外部文書（PDF等）</td>
                            </tr>
                            <tr>
                                <td><strong>Semantic Search</strong></td>
                                <td><?php echo gi_status_badge($knowledge_status['semantic_search_enabled']); ?></td>
                                <td>-</td>
                                <td>ベクトル検索機能</td>
                            </tr>
                            <tr>
                                <td><strong>OpenAI API</strong></td>
                                <td><?php echo gi_status_badge($available_providers['openai']['configured']); ?></td>
                                <td>-</td>
                                <td>GPTモデル連携</td>
                            </tr>
                            <tr>
                                <td><strong>Gemini API</strong></td>
                                <td><?php echo gi_status_badge($available_providers['gemini']['configured']); ?></td>
                                <td>-</td>
                                <td>Geminiモデル連携</td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="description">
                        <strong>緑色</strong>: 正常動作中 | <strong>赤色</strong>: 要設定・要確認
                    </p>
                </div>
            </div>
            
            <p class="submit">
                <input type="submit" name="save_ai_settings" class="button-primary" value="💾 設定を保存">
                <input type="submit" name="test_all_connections" class="button-secondary" value="🔍 全API接続テスト" style="margin-left: 10px;">
                <input type="submit" name="create_sample_data" class="button-secondary" value="📊 サンプルデータ作成" style="margin-left: 10px;" onclick="return confirm('テスト用のサンプルデータ（助成金・FAQ）を作成します。既存データは影響を受けません。続行しますか？');">
            </p>
        </form>
        
        <!-- Enhanced Usage Guide -->
        <div class="postbox" style="margin-top: 20px;">
            <h2 class="hndle">📖 2025年版 Enhanced AI System ガイド</h2>
            <div class="inside">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h4>🚀 クイックセットアップ</h4>
                        <ol>
                            <li><strong>APIキー設定:</strong> OpenAI または Gemini のAPIキーを設定</li>
                            <li><strong>接続テスト:</strong> 「全API接続テスト」で動作確認</li>
                            <li><strong>機能有効化:</strong> Enhanced Features をONに設定</li>
                            <li><strong>動作確認:</strong> フロントページでAI検索をテスト</li>
                        </ol>
                    </div>
                    <div>
                        <h4>⭐ 新機能ハイライト</h4>
                        <ul>
                            <li><strong>意図分析:</strong> 探索・比較・推奨など質問タイプを自動識別</li>
                            <li><strong>マルチAI:</strong> OpenAI + Gemini 2.5 Pro のベストミックス</li>
                            <li><strong>ストリーミング:</strong> ChatGPT風のリアルタイム表示</li>
                            <li><strong>RAG機能:</strong> PDF等の外部文書も検索対象に</li>
                            <li><strong>JSON UI:</strong> 動的コンポーネントで見やすい表示</li>
                        </ul>
                    </div>
                </div>
                
                <div class="gi-admin-notice" style="margin-top: 15px; background: #e8f4fd; border-left-color: #2271b1;">
                    <h4 style="color: #2271b1; margin-top: 0;">💡 推奨設定</h4>
                    <p><strong>高性能構成:</strong> Gemini 2.5 Pro（複雑な分析） + OpenAI（音声・埋め込み）<br>
                    <strong>コスト重視:</strong> OpenAI GPT-3.5-turbo（メイン） + 必要に応じてGemini Flash<br>
                    <strong>最新機能:</strong> 全Enhanced Features をONにして最先端のAI体験を提供</p>
                </div>
            </div>
        </div>
        
        <!-- AJAX接続テスト用JavaScript（Enhanced） -->
        <script>
        jQuery(document).ready(function($) {
            // Enhanced connection test
            $('input[name="test_all_connections"]').click(function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var $resultsDiv = $('#connection-test-results');
                
                // Show loading state
                $button.val('🔄 テスト実行中...').prop('disabled', true);
                $resultsDiv.html('<div class="notice notice-info"><p>APIプロバイダーをテスト中です...</p></div>');
                
                // AJAX test execution
                $.post(ajaxurl, {
                    action: 'gi_test_ai_providers',
                    nonce: '<?php echo wp_create_nonce("gi_admin_nonce"); ?>'
                }, function(response) {
                    $button.val('🔍 全API接続テスト').prop('disabled', false);
                    
                    if (response.success && response.data.test_results) {
                        var html = '';
                        $.each(response.data.test_results, function(provider, result) {
                            var statusClass = result.success ? 'notice-success' : 'notice-error';
                            var statusIcon = result.success ? '✅' : '❌';
                            html += '<div class="notice ' + statusClass + '">';
                            html += '<p><strong>' + statusIcon + ' ' + provider.toUpperCase() + ':</strong> ' + result.message + '</p>';
                            html += '</div>';
                        });
                        $resultsDiv.html(html);
                    } else {
                        $resultsDiv.html('<div class="notice notice-error"><p>❌ テストに失敗しました: ' + (response.data || 'Unknown error') + '</p></div>');
                    }
                }).fail(function() {
                    $button.val('🔍 全API接続テスト').prop('disabled', false);
                    $resultsDiv.html('<div class="notice notice-error"><p>❌ 通信エラーが発生しました。</p></div>');
                });
            });
            
            // Provider selection handler
            $('#preferred_ai_provider').change(function() {
                var provider = $(this).val();
                var $modelSelect = $('#preferred_ai_model');
                
                // Update model options based on provider
                if (provider === 'gemini') {
                    $modelSelect.find('option[value="auto"]').text('自動選択（Gemini 2.5 Pro / Flash）');
                } else {
                    $modelSelect.find('option[value="auto"]').text('自動選択（GPT-4 / 3.5-turbo）');
                }
            });
            
            // API key masking
            $('.form-table input[type="password"]').focus(function() {
                if ($(this).val().indexOf('*') === 0) {
                    $(this).val('');
                }
            });
            
            // Enhanced features dependency
            $('input[name="enable_enhanced_processing"]').change(function() {
                var $intentAnalysis = $('input[name="enable_intent_analysis"]');
                if ($(this).is(':checked')) {
                    $intentAnalysis.prop('checked', true);
                }
            });
        });
        </script>
    </div>
    <?php
}

/**
 * Get knowledge system status
 */
function gi_get_knowledge_system_status() {
    return [
        'grants_count' => wp_count_posts('grant')->publish ?? 0,
        'faqs_count' => wp_count_posts('faq')->publish ?? 0,
        'procedures_count' => wp_count_posts('procedure')->publish ?? 0,
        'rag_documents' => count(get_posts([
            'post_type' => 'attachment',
            'meta_key' => '_rag_document',
            'meta_value' => '1',
            'numberposts' => -1
        ])),
        'semantic_search_enabled' => class_exists('GI_Semantic_Search')
    ];
}

/**
 * Generate status badge
 */
function gi_status_badge($is_active) {
    if ($is_active) {
        return '<span style="color: #10b981; font-weight: bold;">✅ Active</span>';
    } else {
        return '<span style="color: #dc2626; font-weight: bold;">❌ Inactive</span>';
    }
}

/**
 * Enhanced AI capabilities check
 */
function gi_check_enhanced_ai_capabilities() {
    $openai = GI_OpenAI_Integration::getInstance();
    $gemini = GI_Gemini_Integration::getInstance();
    $settings = get_option('gi_ai_settings', []);
    
    return [
        'openai_configured' => $openai->is_configured(),
        'gemini_configured' => $gemini->is_configured(),
        'any_ai_configured' => $openai->is_configured() || $gemini->is_configured(),
        'enhanced_processing_enabled' => !empty($settings['enable_enhanced_processing']),
        'intent_analysis_enabled' => !empty($settings['enable_intent_analysis']),
        'streaming_enabled' => !empty($settings['enable_streaming']),
        'rag_enabled' => !empty($settings['enable_rag']),
        'semantic_search_available' => class_exists('GI_Semantic_Search'),
        'multi_ai_available' => $openai->is_configured() && $gemini->is_configured()
    ];
}

/**
 * =============================================================================
 * 8. AI統計ページ
 * =============================================================================
 */

/**
 * AI統計ページ（簡易版）
 */
function gi_ai_statistics_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    global $wpdb;
    
    // テーブルが存在するかチェック
    $search_table = $wpdb->prefix . 'gi_search_history';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$search_table'") === $search_table;
    
    if (!$table_exists) {
        ?>
        <div class="wrap">
            <h1>AI検索統計</h1>
            <div class="notice notice-info">
                <p>統計データテーブルがまだ作成されていません。初回の検索実行時に自動的に作成されます。</p>
            </div>
        </div>
        <?php
        return;
    }
    
    // 統計データの取得
    $total_searches = $wpdb->get_var("SELECT COUNT(*) FROM $search_table") ?: 0;
    
    // チャット履歴テーブル
    $chat_table = $wpdb->prefix . 'gi_chat_history';
    $chat_exists = $wpdb->get_var("SHOW TABLES LIKE '$chat_table'") === $chat_table;
    $total_chats = $chat_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $chat_table WHERE message_type = 'user'") : 0;
    
    // 人気の検索キーワード（直近30日）
    $popular_searches = $wpdb->get_results("
        SELECT search_query, COUNT(*) as count 
        FROM $search_table 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY search_query 
        ORDER BY count DESC 
        LIMIT 10
    ");
    
    // 時間帯別利用状況（直近7日）
    $hourly_stats = $wpdb->get_results("
        SELECT HOUR(created_at) as hour, COUNT(*) as count 
        FROM $search_table 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY HOUR(created_at) 
        ORDER BY hour
    ");
    
    // 日別利用状況（直近30日）
    $daily_stats = $wpdb->get_results("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM $search_table 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at) 
        ORDER BY date DESC
    ");
    
    // 平均検索結果数
    $avg_results = $wpdb->get_var("
        SELECT AVG(results_count) 
        FROM $search_table 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
    ") ?: 0;
    
    ?>
    <div class="wrap">
        <h1>AI検索統計</h1>
        
        <!-- 統計サマリー -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                <h3 style="margin-top: 0; color: #333; font-size: 14px;">総検索数</h3>
                <p style="font-size: 32px; font-weight: bold; color: #10b981; margin: 10px 0;">
                    <?php echo number_format($total_searches); ?>
                </p>
                <p style="color: #666; font-size: 12px;">全期間</p>
            </div>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                <h3 style="margin-top: 0; color: #333; font-size: 14px;">チャット数</h3>
                <p style="font-size: 32px; font-weight: bold; color: #3b82f6; margin: 10px 0;">
                    <?php echo number_format($total_chats); ?>
                </p>
                <p style="color: #666; font-size: 12px;">AIとの対話数</p>
            </div>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                <h3 style="margin-top: 0; color: #333; font-size: 14px;">平均検索結果</h3>
                <p style="font-size: 32px; font-weight: bold; color: #f59e0b; margin: 10px 0;">
                    <?php echo number_format($avg_results, 1); ?>
                </p>
                <p style="color: #666; font-size: 12px;">件/検索</p>
            </div>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                <h3 style="margin-top: 0; color: #333; font-size: 14px;">本日の検索</h3>
                <p style="font-size: 32px; font-weight: bold; color: #8b5cf6; margin: 10px 0;">
                    <?php 
                    $today_searches = $wpdb->get_var("
                        SELECT COUNT(*) FROM $search_table 
                        WHERE DATE(created_at) = CURDATE()
                    ") ?: 0;
                    echo number_format($today_searches);
                    ?>
                </p>
                <p style="color: #666; font-size: 12px;"><?php echo date('Y年m月d日'); ?></p>
            </div>
        </div>
        
        <!-- 人気検索キーワード -->
        <?php if (!empty($popular_searches)): ?>
        <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px;">
            <h2 style="font-size: 18px; margin-top: 0;">人気の検索キーワード（過去30日）</h2>
            <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th style="width: 50px;">順位</th>
                        <th>検索キーワード</th>
                        <th style="width: 100px;">検索回数</th>
                        <th style="width: 120px;">割合</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_month = array_sum(array_column($popular_searches, 'count'));
                    foreach ($popular_searches as $index => $search): 
                        $percentage = ($search->count / $total_month) * 100;
                    ?>
                    <tr>
                        <td><strong><?php echo $index + 1; ?></strong></td>
                        <td>
                            <?php echo esc_html($search->search_query); ?>
                            <?php if ($index < 3): ?>
                                <span style="color: #f59e0b;">[HOT]</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($search->count); ?>回</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <div style="background: #e5e5e5; height: 20px; flex: 1; border-radius: 3px; overflow: hidden;">
                                    <div style="background: #10b981; height: 100%; width: <?php echo $percentage; ?>%;"></div>
                                </div>
                                <span style="font-size: 12px;"><?php echo number_format($percentage, 1); ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- 時間帯別利用状況 -->
        <?php if (!empty($hourly_stats)): ?>
        <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px;">
            <h2 style="font-size: 18px; margin-top: 0;">時間帯別利用状況（過去7日間）</h2>
            <div style="display: flex; align-items: flex-end; height: 200px; gap: 2px; margin-top: 20px;">
                <?php 
                $max_hour = max(array_column($hourly_stats, 'count'));
                for ($h = 0; $h < 24; $h++):
                    $count = 0;
                    foreach ($hourly_stats as $stat) {
                        if ($stat->hour == $h) {
                            $count = $stat->count;
                            break;
                        }
                    }
                    $height = $max_hour > 0 ? ($count / $max_hour) * 100 : 0;
                ?>
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                    <div style="background: <?php echo $height > 0 ? '#3b82f6' : '#e5e5e5'; ?>; 
                                width: 100%; 
                                height: <?php echo max($height, 2); ?>%; 
                                border-radius: 2px 2px 0 0;"
                         title="<?php echo $h; ?>時: <?php echo $count; ?>件"></div>
                    <?php if ($h % 3 == 0): ?>
                    <span style="font-size: 10px; margin-top: 5px;"><?php echo $h; ?>時</span>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- アクション -->
        <div style="margin-top: 30px;">
            <a href="<?php echo admin_url('admin.php?page=gi-ai-settings'); ?>" class="button button-primary">
                AI設定を確認
            </a>
            <button type="button" class="button" onclick="if(confirm('統計データをリセットしますか？')) location.href='?page=gi-ai-statistics&action=reset&nonce=<?php echo wp_create_nonce('reset_stats'); ?>'">
                統計をリセット
            </button>
        </div>
    </div>
    <?php
    
    // リセット処理
    if (isset($_GET['action']) && $_GET['action'] === 'reset' && wp_verify_nonce($_GET['nonce'], 'reset_stats')) {
        $wpdb->query("TRUNCATE TABLE $search_table");
        if ($chat_exists) {
            $wpdb->query("TRUNCATE TABLE $chat_table");
        }
        echo '<div class="notice notice-success"><p>統計データをリセットしました。</p></div>';
        echo '<script>setTimeout(function(){ location.href="?page=gi-ai-statistics"; }, 2000);</script>';
    }
}

/**
 * =============================================================================
 * 9. POST METABOXES - Custom Fields for Grant Posts
 * =============================================================================
 */

class GrantPostMetaboxes {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_grant_metaboxes'));
        add_action('save_post', array($this, 'save_grant_metadata'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_metabox_scripts'));
    }
    
    /**
     * 助成金投稿用メタボックスを追加
     */
    public function add_grant_metaboxes() {
        // WordPress標準のタクソノミーメタボックスを置き換え
        remove_meta_box('grant_categorydiv', 'grant', 'side');
        remove_meta_box('grant_prefecturediv', 'grant', 'side');
        remove_meta_box('grant_municipalitydiv', 'grant', 'side');
        
        // カスタムタクソノミーメタボックス
        add_meta_box(
            'grant-category-metabox',
            '📂 助成金カテゴリー',
            array($this, 'render_category_metabox'),
            'grant',
            'side',
            'high'
        );
        
        add_meta_box(
            'grant-prefecture-metabox',
            '対象都道府県',
            array($this, 'render_prefecture_metabox'),
            'grant',
            'side',
            'high'
        );
        
        add_meta_box(
            'grant-municipality-metabox',
            '🏛️ 対象市町村',
            array($this, 'render_municipality_metabox'),
            'grant',
            'side',
            'high'
        );
    }
    
    /**
     * 助成金カテゴリーメタボックス
     */
    public function render_category_metabox($post) {
        wp_nonce_field('grant_taxonomy_nonce', 'grant_taxonomy_nonce_field');
        
        $categories = get_terms(array(
            'taxonomy' => 'grant_category',
            'hide_empty' => false
        ));
        
        $post_categories = wp_get_post_terms($post->ID, 'grant_category', array('fields' => 'ids'));
        
        ?>
        <div class="grant-metabox-content">
            <div id="grant-category-selection">
                <?php if (!empty($categories) && !is_wp_error($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <label style="display: block; margin-bottom: 8px;">
                            <input type="checkbox" 
                                   name="grant_categories[]" 
                                   value="<?php echo esc_attr($category->term_id); ?>"
                                   <?php checked(in_array($category->term_id, $post_categories)); ?>>
                            <?php echo esc_html($category->name); ?>
                            <span style="color: #666;">（<?php echo $category->count; ?>件）</span>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666;">カテゴリーがありません。</p>
                <?php endif; ?>
                
                <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd;">
                    <input type="text" id="new_grant_category" placeholder="新しいカテゴリー名" style="width: 70%;">
                    <button type="button" id="add_grant_category" class="button button-small">追加</button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * 対象都道府県メタボックス
     */
    public function render_prefecture_metabox($post) {
        $prefectures = get_terms(array(
            'taxonomy' => 'grant_prefecture',
            'hide_empty' => false,
            'orderby' => 'name'
        ));
        
        $post_prefectures = wp_get_post_terms($post->ID, 'grant_prefecture', array('fields' => 'ids'));
        
        ?>
        <div class="grant-metabox-content">
            <div id="grant-prefecture-selection" style="max-height: 300px; overflow-y: auto;">
                <p>
                    <label>
                        <input type="checkbox" id="select_all_prefectures"> 
                        <strong>全国対象（全て選択）</strong>
                    </label>
                </p>
                <div style="border-top: 1px solid #ddd; padding-top: 8px; margin-top: 8px;">
                    <?php if (!empty($prefectures) && !is_wp_error($prefectures)): ?>
                        <?php foreach ($prefectures as $prefecture): ?>
                            <label style="display: block; margin-bottom: 6px;">
                                <input type="checkbox" 
                                       name="grant_prefectures[]" 
                                       value="<?php echo esc_attr($prefecture->term_id); ?>"
                                       class="prefecture-checkbox"
                                       <?php checked(in_array($prefecture->term_id, $post_prefectures)); ?>>
                                <?php echo esc_html($prefecture->name); ?>
                                <span style="color: #666;">（<?php echo $prefecture->count; ?>件）</span>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #666;">都道府県データがありません。</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * 対象市町村メタボックス（強化版）
     */
    public function render_municipality_metabox($post) {
        // 地域制限タイプを取得
        $regional_limitation = get_post_meta($post->ID, 'regional_limitation', true);
        $selected_prefectures = wp_get_post_terms($post->ID, 'grant_prefecture', array('fields' => 'slugs'));
        $post_municipalities = wp_get_post_terms($post->ID, 'grant_municipality', array('fields' => 'ids'));
        
        ?>
        <div class="grant-metabox-content">
            <div class="municipality-type-selector" style="margin-bottom: 15px; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                <h4 style="margin: 0 0 10px 0;">地域制限タイプ</h4>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="radio" name="municipality_selection_type" value="prefecture_level" 
                           <?php checked($regional_limitation !== 'municipality_only'); ?>>
                    都道府県レベル（自動設定）
                </label>
                <label style="display: block;">
                    <input type="radio" name="municipality_selection_type" value="municipality_level" 
                           <?php checked($regional_limitation === 'municipality_only'); ?>>
                    市町村レベル（手動選択）
                </label>
            </div>
            
            <div id="prefecture-level-info" style="margin-bottom: 15px; padding: 10px; background: #e8f5e8; border-radius: 4px; display: <?php echo $regional_limitation !== 'municipality_only' ? 'block' : 'none'; ?>;">
                <p style="margin: 0; font-size: 13px;">
                    <strong>📍 都道府県レベル:</strong> 選択した都道府県全体が対象の助成金です。市町村は自動で設定されます。
                </p>
            </div>
            
            <div id="municipality-level-controls" style="display: <?php echo $regional_limitation === 'municipality_only' ? 'block' : 'none'; ?>;">
                <div class="prefecture-filter" style="margin-bottom: 10px;">
                    <label for="prefecture_filter" style="font-weight: bold;">都道府県で絞り込み:</label>
                    <select id="prefecture_filter" style="width: 100%; margin-top: 5px;">
                        <option value="">-- すべての都道府県 --</option>
                        <?php
                        $prefectures = gi_get_all_prefectures();
                        foreach ($prefectures as $pref):
                        ?>
                        <option value="<?php echo esc_attr($pref['slug']); ?>">
                            <?php echo esc_html($pref['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="margin-bottom: 10px;">
                    <input type="text" id="municipality_search" placeholder="市町村を検索..." style="width: 100%;">
                </div>
                
                <div id="grant-municipality-selection" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: white;">
                    <?php
                    // 階層構造で市町村を表示
                    $prefectures = gi_get_all_prefectures();
                    foreach ($prefectures as $pref):
                        $pref_municipalities = get_terms(array(
                            'taxonomy' => 'grant_municipality',
                            'hide_empty' => false,
                            'meta_query' => array(
                                array(
                                    'key' => 'prefecture_slug',
                                    'value' => $pref['slug'],
                                    'compare' => '='
                                )
                            )
                        ));
                        
                        // 都道府県スラッグで市町村を取得（新しい方法）
                        if (empty($pref_municipalities)) {
                            $pref_municipalities = get_terms(array(
                                'taxonomy' => 'grant_municipality',
                                'hide_empty' => false,
                                'search' => $pref['name']
                            ));
                        }
                        
                        if (!empty($pref_municipalities) && !is_wp_error($pref_municipalities)):
                    ?>
                    <div class="prefecture-group" data-prefecture="<?php echo esc_attr($pref['slug']); ?>" style="margin-bottom: 20px;">
                        <h5 style="margin: 0 0 8px 0; padding: 5px 10px; background: #f0f0f0; border-left: 3px solid #0073aa; font-size: 14px;">
                            <?php echo esc_html($pref['name']); ?>
                        </h5>
                        <div class="municipality-list" style="margin-left: 15px;">
                            <?php foreach ($pref_municipalities as $municipality): ?>
                                <label style="display: block; margin-bottom: 4px; font-size: 13px;" class="municipality-option" data-prefecture="<?php echo esc_attr($pref['slug']); ?>">
                                    <input type="checkbox" 
                                           name="grant_municipalities[]" 
                                           value="<?php echo esc_attr($municipality->term_id); ?>"
                                           <?php checked(in_array($municipality->term_id, $post_municipalities)); ?>>
                                    <?php echo esc_html($municipality->name); ?>
                                    <span style="color: #666; font-size: 12px;">（<?php echo $municipality->count; ?>件）</span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                
                <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd;">
                    <div style="display: flex; gap: 5px;">
                        <select id="new_municipality_prefecture" style="width: 30%;">
                            <option value="">都道府県選択</option>
                            <?php foreach ($prefectures as $pref): ?>
                            <option value="<?php echo esc_attr($pref['slug']); ?>">
                                <?php echo esc_html($pref['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" id="new_municipality" placeholder="新しい市町村名" style="width: 45%;">
                        <button type="button" id="add_municipality" class="button button-small" style="width: 20%;">追加</button>
                    </div>
                </div>
            </div>
            
            <div id="auto-municipality-info" style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 4px; display: <?php echo $regional_limitation !== 'municipality_only' ? 'block' : 'none'; ?>;">
                <p style="margin: 0; font-size: 13px;">
                    <strong>ℹ️ 自動設定:</strong> 都道府県を選択すると、該当する市町村が自動で設定されます。
                </p>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // 地域制限タイプの切り替え
            $('input[name="municipality_selection_type"]').change(function() {
                var selectedType = $(this).val();
                
                if (selectedType === 'prefecture_level') {
                    $('#prefecture-level-info, #auto-municipality-info').show();
                    $('#municipality-level-controls').hide();
                    
                    // 地域制限フィールドを更新
                    $('select[name="acf[field_regional_limitation]"], input[name="regional_limitation"]').val('prefecture_only');
                } else {
                    $('#prefecture-level-info, #auto-municipality-info').hide();
                    $('#municipality-level-controls').show();
                    
                    // 地域制限フィールドを更新
                    $('select[name="acf[field_regional_limitation]"], input[name="regional_limitation"]').val('municipality_only');
                }
            });
            
            // 都道府県フィルター
            $('#prefecture_filter').change(function() {
                var selectedPref = $(this).val();
                
                $('.prefecture-group').each(function() {
                    var prefSlug = $(this).data('prefecture');
                    
                    if (!selectedPref || prefSlug === selectedPref) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
            
            // 市町村検索
            $('#municipality_search').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                
                $('.municipality-option').each(function() {
                    var municipalityName = $(this).text().toLowerCase();
                    
                    if (!searchTerm || municipalityName.indexOf(searchTerm) !== -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * メタボックス用のスクリプトを読み込み
     */
    public function enqueue_metabox_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        global $post_type;
        if ($post_type !== 'grant') {
            return;
        }
        
        wp_enqueue_script('grant-metaboxes', get_template_directory_uri() . '/assets/js/grant-metaboxes.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('grant-metaboxes', get_template_directory_uri() . '/assets/css/admin-metaboxes.css', array(), '1.0.0');
        
        wp_localize_script('grant-metaboxes', 'grantMetaboxes', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('grant_metaboxes_nonce')
        ));
    }
    
    /**
     * メタデータとタクソノミーの保存
     */
    public function save_grant_metadata($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if (get_post_type($post_id) !== 'grant') return;
        
        if (!isset($_POST['grant_taxonomy_nonce_field']) || 
            !wp_verify_nonce($_POST['grant_taxonomy_nonce_field'], 'grant_taxonomy_nonce')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) return;
        
        // タクソノミーの保存
        if (isset($_POST['grant_categories'])) {
            $categories = array_map('intval', $_POST['grant_categories']);
            wp_set_post_terms($post_id, $categories, 'grant_category');
        } else {
            wp_set_post_terms($post_id, array(), 'grant_category');
        }
        
        if (isset($_POST['grant_prefectures'])) {
            $prefectures = array_map('intval', $_POST['grant_prefectures']);
            wp_set_post_terms($post_id, $prefectures, 'grant_prefecture');
        } else {
            wp_set_post_terms($post_id, array(), 'grant_prefecture');
        }
        
        if (isset($_POST['grant_municipalities'])) {
            $municipalities = array_map('intval', $_POST['grant_municipalities']);
            wp_set_post_terms($post_id, $municipalities, 'grant_municipality');
        } else {
            wp_set_post_terms($post_id, array(), 'grant_municipality');
        }
    }
}

// タクソノミータームを追加するAJAXハンドラー
add_action('wp_ajax_gi_add_taxonomy_term', function() {
    check_ajax_referer('grant_metaboxes_nonce', 'nonce');
    
    if (!current_user_can('manage_categories')) {
        wp_send_json_error('権限がありません');
        return;
    }
    
    $taxonomy = sanitize_text_field($_POST['taxonomy']);
    $term_name = sanitize_text_field($_POST['term_name']);
    
    $allowed_taxonomies = array('grant_category', 'grant_municipality', 'grant_prefecture');
    if (!in_array($taxonomy, $allowed_taxonomies)) {
        wp_send_json_error('無効なタクソノミーです');
        return;
    }
    
    if (empty($term_name)) {
        wp_send_json_error('タerm名が入力されていません');
        return;
    }
    
    $existing_term = term_exists($term_name, $taxonomy);
    if ($existing_term) {
        wp_send_json_error('このタームは既に存在します');
        return;
    }
    
    $result = wp_insert_term($term_name, $taxonomy);
    
    if (is_wp_error($result)) {
        wp_send_json_error('タームの作成に失敗しました: ' . $result->get_error_message());
        return;
    }
    
    wp_send_json_success(array(
        'term_id' => $result['term_id'],
        'name' => $term_name,
        'taxonomy' => $taxonomy
    ));
});

// Initialize metaboxes
function gi_init_grant_metaboxes() {
    return GrantPostMetaboxes::getInstance();
}
add_action('init', 'gi_init_grant_metaboxes');



/**
 * =============================================================================
 * 11. デバッグ・ログ出力
 * =============================================================================
 */

// デバッグ情報の出力
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('admin_footer', function() {
        echo '<!-- Admin Customization: Clean version loaded successfully -->';
        echo '<!-- Current User ID: ' . get_current_user_id() . ' -->';
    });
}


/**
 * =============================================================================
 * 12. お問い合わせフォーム処理
 * =============================================================================
 */

/**
 * =============================================================================
 * 13. 重複タイトルエクスポート機能
 * =============================================================================
 */
class Grant_Duplicate_Exporter {
    
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // 1. WordPress管理画面にメニューを追加
        add_action('admin_menu', array($this, 'add_admin_menu_page'));
        
        // 2. 必要なJSに変数を渡す
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // 3. AJAXリクエストの受け口を登録
        add_action('wp_ajax_export_duplicate_titles', array($this, 'ajax_export_duplicates'));
    }

    /**
     * 「助成金」配下にサブメニューを追加
     */
    public function add_admin_menu_page() {
        add_submenu_page(
            'edit.php?post_type=grant',
            '重複タイトルエクスポート',
            '重複エクスポート',
            'manage_options',
            'grant-duplicate-export',
            array($this, 'render_admin_page')
        );
    }

    /**
     * 管理画面のHTMLをレンダリング
     */
    public function render_admin_page() {
        ?>
        <div class="wrap" id="grant-duplicate-exporter">
            <h1><span class="dashicons dashicons-google"></span> 重複タイトル エクスポート</h1>
            <p>サイト内の「助成金」投稿（公開済み、下書き、レビュー待ちを含む）で、タイトルが重複しているものを検索し、Googleスプレッドシートにエクスポートします。</p>
            <p>この機能は、コンテンツの品質管理やSEO対策（重複コンテンツの特定）に役立ちます。</p>
            
            <!-- デバッグ情報 -->
            <div class="notice notice-info" style="margin: 15px 0;">
                <p><strong>デバッグ情報:</strong></p>
                <ul>
                    <li>現在のフック: <code><?php echo esc_html(current_filter()); ?></code></li>
                    <li>Hook パラメータ: <code><?php global $hook_suffix; echo esc_html($hook_suffix); ?></code></li>
                    <li>duplicateExport オブジェクト: <span id="debug-duplicate-export">読み込み中...</span></li>
                    <li>jQuery バージョン: <span id="debug-jquery">読み込み中...</span></li>
                </ul>
            </div>
            
            <div class="card">
                <button type="button" id="export-duplicates-btn" class="button button-primary button-hero">
                    <span class="dashicons dashicons-controls-play"></span>
                    重複タイトルをGoogleスプレッドシートにエクスポート
                </button>
                <div id="export-progress" style="display:none; margin-top: 15px;">
                    <p class="spinner is-active" style="float: left; margin-top: 0;"></p>
                    <p style="margin: 0 0 0 30px;">重複データを検索・エクスポート中です。投稿数により数分かかる場合があります...</p>
                </div>
                <div id="export-results" style="margin-top: 15px;"></div>
            </div>
        </div>
        
        <script type="text/javascript">
        // 重複タイトルエクスポート機能（インライン実装）
        jQuery(document).ready(function($) {
            console.log('[Duplicate Exporter] Inline script executed');
            console.log('[Duplicate Exporter] jQuery version:', $.fn.jquery);
            console.log('[Duplicate Exporter] duplicateExport:', typeof duplicateExport !== 'undefined' ? duplicateExport : 'UNDEFINED');
            console.log('[Duplicate Exporter] #grant-duplicate-exporter exists:', $('#grant-duplicate-exporter').length > 0);
            console.log('[Duplicate Exporter] #export-duplicates-btn exists:', $('#export-duplicates-btn').length > 0);
            
            // デバッグ情報を画面に表示
            $('#debug-jquery').text($.fn.jquery);
            $('#debug-duplicate-export').text(typeof duplicateExport !== 'undefined' ? JSON.stringify(duplicateExport) : 'UNDEFINED - wp_localize_script が実行されていません');
            
            // duplicateExport が存在しない場合はエラー
            if (typeof duplicateExport === 'undefined') {
                console.error('[Duplicate Exporter] duplicateExport object is missing!');
                return;
            }
            
            var $button = $('#export-duplicates-btn');
            var $progress = $('#export-progress');
            var $results = $('#export-results');
            
            console.log('[Duplicate Exporter] Setting up click handler...');
            
            // ボタンクリックハンドラ
            $button.on('click', function(e) {
                console.log('[Duplicate Exporter] Button clicked!');
                
                if ($button.is('.disabled')) {
                    console.log('[Duplicate Exporter] Button is disabled, ignoring click');
                    return;
                }

                // ボタンを無効化し、プログレス表示
                console.log('[Duplicate Exporter] Disabling button and showing progress');
                $button.addClass('disabled').prop('disabled', true);
                $progress.slideDown();
                $results.html('').removeClass('notice notice-success notice-error');

                console.log('[Duplicate Exporter] Sending AJAX request...');
                console.log('[Duplicate Exporter] AJAX URL:', duplicateExport.ajax_url);
                console.log('[Duplicate Exporter] Nonce:', duplicateExport.nonce);
                
                // AJAXリクエストを送信
                $.post(duplicateExport.ajax_url, {
                    action: 'export_duplicate_titles',
                    nonce: duplicateExport.nonce
                })
                .done(function(response) {
                    console.log('[Duplicate Exporter] AJAX response received:', response);
                    
                    if (response.success) {
                        // 成功
                        console.log('[Duplicate Exporter] Export successful!');
                        var html = '<p>' + response.data.message + '</p>';
                        if (response.data.sheetUrl) {
                            html += '<a href="' + response.data.sheetUrl + '" target="_blank" class="button button-secondary">スプレッドシートを開く</a>';
                        }
                        $results.html(html).addClass('notice notice-success is-dismissible');
                    } else {
                        // 失敗
                        console.error('[Duplicate Exporter] Export failed:', response.data);
                        console.error('[Duplicate Exporter] Full error object:', JSON.stringify(response.data, null, 2));
                        
                        var errorHtml = '<p><strong>エラー:</strong> ' + (response.data.message || 'Unknown error') + '</p>';
                        
                        // 詳細情報がある場合は表示
                        if (response.data.detail) {
                            errorHtml += '<p><strong>詳細:</strong> ' + response.data.detail + '</p>';
                        }
                        if (response.data.help) {
                            errorHtml += '<p><strong>ヒント:</strong> ' + response.data.help + '</p>';
                        }
                        if (response.data.spreadsheet_id) {
                            errorHtml += '<p><strong>スプレッドシートID:</strong> <code>' + response.data.spreadsheet_id + '</code></p>';
                        }
                        if (response.data.sheet_name) {
                            errorHtml += '<p><strong>シート名:</strong> ' + response.data.sheet_name + '</p>';
                        }
                        
                        errorHtml += '<p style="margin-top: 10px;"><em>詳細なエラー情報はブラウザのコンソールとWordPressのエラーログを確認してください。</em></p>';
                        
                        // デバッグ用：全エラーデータを展開可能な要素として追加
                        errorHtml += '<details style="margin-top: 15px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;"><summary style="cursor: pointer; font-weight: bold;">デバッグ情報を表示</summary><pre style="margin-top: 10px; padding: 10px; background: #fff; border: 1px solid #ccc; overflow: auto;">' + JSON.stringify(response.data, null, 2) + '</pre></details>';
                        
                        $results.html(errorHtml).addClass('notice notice-error is-dismissible');
                    }
                })
                .fail(function(xhr, status, error) {
                    // 通信エラー
                    console.error('[Duplicate Exporter] AJAX request failed');
                    console.error('[Duplicate Exporter] Status:', status);
                    console.error('[Duplicate Exporter] Error:', error);
                    console.error('[Duplicate Exporter] Response:', xhr.responseText);
                    $results.html('<p>エラー: サーバーとの通信に失敗しました。詳細はコンソールをご確認ください。</p>').addClass('notice notice-error is-dismissible');
                })
                .always(function() {
                    // ボタンを有効化し、プログレス非表示
                    console.log('[Duplicate Exporter] Request completed, re-enabling button');
                    $button.removeClass('disabled').prop('disabled', false);
                    $progress.slideUp();
                });
            });
            
            console.log('[Duplicate Exporter] Initialization complete');
        });
        </script>
        <?php
    }

    /**
     * 既存の管理画面用JSにAJAX用の変数を渡す
     */
    public function enqueue_admin_scripts($hook) {
        // デバッグ: 現在のフックを記録
        error_log('[Duplicate Exporter] enqueue_admin_scripts called with hook: ' . $hook);
        
        // 登録した管理ページ以外では動作しない
        if ('grant_page_grant-duplicate-export' !== $hook) {
            error_log('[Duplicate Exporter] Hook does not match. Expected: grant_page_grant-duplicate-export, Got: ' . $hook);
            return;
        }

        error_log('[Duplicate Exporter] Hook matched! Localizing script...');

        // 既存の 'gi-admin-consolidated' ハンドル（テーマで使われているJSハンドル名）に対して変数を渡す
        wp_localize_script('gi-admin-consolidated', 'duplicateExport', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('duplicate_export_nonce')
        ));
        
        error_log('[Duplicate Exporter] wp_localize_script executed');
    }

    /**
     * AJAXリクエストの本体
     */
    public function ajax_export_duplicates() {
        // 1. セキュリティチェック
        check_ajax_referer('duplicate_export_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => '権限がありません。']);
        }

        // 2. 既存のGoogle Sheets連携クラスのインスタンスを取得
        if (!class_exists('GoogleSheetsSync')) {
            wp_send_json_error(['message' => 'Google Sheets連携機能 (GoogleSheetsSync) が見つかりません。']);
        }
        $integration = GoogleSheetsSync::getInstance();
        $spreadsheet_id = $integration->get_spreadsheet_id(); // 既存の関数を使用
        if (empty($spreadsheet_id)) {
            wp_send_json_error(['message' => 'GoogleスプレッドシートIDが設定されていません。']);
        }

        // 3. 重複データを検索・フォーマット
        $duplicate_posts = $this->find_duplicate_posts_db();
        if (empty($duplicate_posts)) {
            wp_send_json_success(['message' => '重複したタイトルは見つかりませんでした。']);
        }

        $data_to_export = $this->format_data_for_sheets($duplicate_posts);
        $total_duplicates = count($data_to_export) - 1; // ヘッダー行を除く

        try {
            // 4. 「重複」という名前のシートタブを作成または使用する
            $duplicate_sheet_name = '重複';
            
            error_log('[Duplicate Exporter] Target sheet name: ' . $duplicate_sheet_name);
            error_log('[Duplicate Exporter] Spreadsheet ID: ' . $spreadsheet_id);
            
            // 既存のシートを確認し、なければ作成を試みる
            $new_sheet = $integration->create_new_sheet($spreadsheet_id, $duplicate_sheet_name);
            
            if ($new_sheet) {
                error_log('[Duplicate Exporter] Sheet created successfully: ' . $new_sheet['title']);
            } else {
                error_log('[Duplicate Exporter] Could not create new sheet (may already exist or permission issue)');
                // シートがすでに存在する可能性があるので、そのまま続行
            }
            
            // 5. セパレータ行を追加（タイムスタンプ付き）
            $timestamp = date('Y-m-d H:i:s');
            $separator = array(
                array('', '', '', '', ''),
                array('=== 重複レポート (' . $timestamp . ') ===', '', '', '', ''),
                array('', '', '', '', '')
            );
            
            // 6. セパレータとデータを結合
            $full_data = array_merge($separator, $data_to_export);
            
            error_log('[Duplicate Exporter] Appending data to sheet: ' . $duplicate_sheet_name);
            error_log('[Duplicate Exporter] Total rows to append: ' . count($full_data));
            
            // 7. データを一括追記（新しいappend_to_specific_sheetメソッドを使用）
            $append_success = $integration->append_to_specific_sheet(
                $duplicate_sheet_name,
                $full_data,
                'USER_ENTERED'
            );

            error_log('[Duplicate Exporter] Append result: ' . ($append_success ? 'SUCCESS' : 'FAILED'));

            if (!$append_success) {
                // エラーの詳細をログに記録
                error_log('[Duplicate Exporter] Append failed for sheet: ' . $duplicate_sheet_name);
                error_log('[Duplicate Exporter] Data row count: ' . count($full_data));
                error_log('[Duplicate Exporter] First row sample: ' . print_r($full_data[0], true));
                
                wp_send_json_error([
                    'message' => 'スプレッドシート「' . $duplicate_sheet_name . '」へのデータ追記に失敗しました。',
                    'detail' => 'Google Sheets APIへのリクエストが失敗しました。',
                    'help' => 'シート「' . $duplicate_sheet_name . '」が存在するか、サービスアカウントに編集権限があるか確認してください。',
                    'sheet_name' => $duplicate_sheet_name,
                    'spreadsheet_id' => $spreadsheet_id,
                    'rows' => count($full_data)
                ]);
            }

            // 8. 成功レスポンス（スプレッドシートへのURL）を返す
            $sheet_url = 'https://docs.google.com/spreadsheets/d/' . $spreadsheet_id . '/edit#gid=0';

            error_log('[Duplicate Exporter] Export successful! Sheet URL: ' . $sheet_url);

            wp_send_json_success([
                'message' => '✅ ' . $total_duplicates . '件の重複投稿を「' . $duplicate_sheet_name . '」シートにエクスポートしました。',
                'sheetUrl' => $sheet_url,
                'sheet_name' => $duplicate_sheet_name,
                'timestamp' => $timestamp,
                'rows_exported' => count($full_data)
            ]);

        } catch (Exception $e) {
            error_log('[Duplicate Exporter] Exception caught: ' . $e->getMessage());
            error_log('[Duplicate Exporter] Stack trace: ' . $e->getTraceAsString());
            
            wp_send_json_error([
                'message' => 'Google APIエラー: ' . $e->getMessage(),
                'detail' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * DBから重複タイトルを検索 (SQL)
     */
    private function find_duplicate_posts_db() {
        global $wpdb;
        
        $post_statuses = "('publish', 'draft', 'pending', 'future')";
        
        $sql = $wpdb->prepare(
            "SELECT post_title, COUNT(*) as count, GROUP_CONCAT(ID) as post_ids
             FROM {$wpdb->posts}
             WHERE post_type = %s AND post_status IN {$post_statuses}
             GROUP BY post_title
             HAVING count > 1
             ORDER BY count DESC, post_title ASC",
            'grant'
        );
        
        return $wpdb->get_results($sql);
    }

    /**
     * 検索結果をGoogle Sheets APIが要求する2D配列にフォーマット
     */
    private function format_data_for_sheets($duplicates) {
        $data = [];
        // ヘッダー行
        $data[] = ['重複タイトル', '件数', '投稿ID', '投稿ステータス', '投稿URL'];

        foreach ($duplicates as $group) {
            $ids = explode(',', $group->post_ids);
            foreach ($ids as $post_id) {
                $post_id = intval($post_id);
                $data[] = [
                    $group->post_title,
                    $group->count . '件',
                    $post_id,
                    get_post_status($post_id),
                    get_permalink($post_id)
                ];
            }
        }
        return $data;
    }
}

// クラスを初期化
Grant_Duplicate_Exporter::get_instance();


/**
 * お問い合わせフォーム送信処理（ログインユーザー用）
 */
function gi_handle_contact_form_submission() {
    // Nonceチェック
    if (!isset($_POST['contact_form_nonce']) || !wp_verify_nonce($_POST['contact_form_nonce'], 'contact_form_submit')) {
        wp_die('セキュリティチェックに失敗しました。もう一度お試しください。');
    }
    
    // バリデーション
    $errors = array();
    
    // 必須項目チェック
    $required_fields = array(
        'inquiry_type' => 'お問い合わせ種別',
        'name' => 'お名前',
        'email' => 'メールアドレス',
        'subject' => '件名',
        'message' => 'お問い合わせ内容'
    );
    
    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field])) {
            $errors[] = $label . 'を入力してください。';
        }
    }
    
    // メールアドレスの形式チェック
    if (!empty($_POST['email']) && !is_email($_POST['email'])) {
        $errors[] = '有効なメールアドレスを入力してください。';
    }
    
    // プライバシーポリシー同意チェック
    if (!isset($_POST['privacy_agree'])) {
        $errors[] = '個人情報の取り扱いに同意してください。';
    }
    
    // エラーがある場合はリダイレクト
    if (!empty($errors)) {
        $error_msg = implode('|', $errors);
        wp_redirect(add_query_arg(array(
            'contact_error' => '1',
            'error_msg' => urlencode($error_msg)
        ), home_url('/contact/')));
        exit;
    }
    
    // フォームデータの取得
    $inquiry_type = sanitize_text_field($_POST['inquiry_type']);
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $phone = !empty($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $company = !empty($_POST['company']) ? sanitize_text_field($_POST['company']) : '';
    $industry = !empty($_POST['industry']) ? sanitize_text_field($_POST['industry']) : '';
    $employees = !empty($_POST['employees']) ? sanitize_text_field($_POST['employees']) : '';
    $subject = sanitize_text_field($_POST['subject']);
    $message = sanitize_textarea_field($_POST['message']);
    $contact_method = !empty($_POST['contact_method']) ? sanitize_text_field($_POST['contact_method']) : 'email';
    $contact_time = !empty($_POST['contact_time']) ? array_map('sanitize_text_field', $_POST['contact_time']) : array();
    
    // お問い合わせ種別の日本語変換
    $inquiry_type_labels = array(
        'usage' => 'サイトの使い方について',
        'grant-info' => '補助金・助成金の制度について',
        'update' => '掲載情報の修正・更新',
        'media' => '媒体掲載・取材依頼',
        'technical' => '技術的な問題・不具合',
        'other' => 'その他'
    );
    $inquiry_type_label = isset($inquiry_type_labels[$inquiry_type]) ? $inquiry_type_labels[$inquiry_type] : $inquiry_type;
    
    // 管理者宛メール作成
    $admin_email = get_option('admin_email');
    $admin_subject = '【補助金インサイト】お問い合わせを受け付けました';
    
    $admin_message = "新しいお問い合わせを受け付けました。\n\n";
    $admin_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $admin_message .= "■ お問い合わせ情報\n";
    $admin_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    $admin_message .= "お問い合わせ種別: " . $inquiry_type_label . "\n";
    $admin_message .= "お名前: " . $name . "\n";
    $admin_message .= "メールアドレス: " . $email . "\n";
    
    if (!empty($phone)) {
        $admin_message .= "電話番号: " . $phone . "\n";
    }
    if (!empty($company)) {
        $admin_message .= "会社名・団体名: " . $company . "\n";
    }
    if (!empty($industry)) {
        $admin_message .= "業種: " . $industry . "\n";
    }
    if (!empty($employees)) {
        $admin_message .= "従業員数: " . $employees . "\n";
    }
    
    $admin_message .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $admin_message .= "■ お問い合わせ内容\n";
    $admin_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    $admin_message .= "件名: " . $subject . "\n\n";
    $admin_message .= "内容:\n" . $message . "\n\n";
    
    $admin_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $admin_message .= "■ ご希望の連絡方法\n";
    $admin_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    $admin_message .= "連絡方法: " . $contact_method . "\n";
    
    if (!empty($contact_time)) {
        $admin_message .= "希望時間帯: " . implode(', ', $contact_time) . "\n";
    }
    
    $admin_message .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $admin_message .= "送信日時: " . current_time('Y年n月j日 H:i:s') . "\n";
    $admin_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // 送信者への自動返信メール
    $user_subject = '【補助金インサイト】お問い合わせを受け付けました';
    
    $user_message = $name . " 様\n\n";
    $user_message .= "この度は、補助金インサイトへお問い合わせいただき、誠にありがとうございます。\n";
    $user_message .= "以下の内容でお問い合わせを受け付けました。\n\n";
    $user_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $user_message .= "■ お問い合わせ内容\n";
    $user_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    $user_message .= "お問い合わせ種別: " . $inquiry_type_label . "\n";
    $user_message .= "件名: " . $subject . "\n\n";
    $user_message .= "内容:\n" . $message . "\n\n";
    $user_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    $user_message .= "内容を確認の上、2営業日以内に担当者よりご連絡させていただきます。\n";
    $user_message .= "今しばらくお待ちくださいますよう、お願い申し上げます。\n\n";
    $user_message .= "※このメールは自動送信されています。\n";
    $user_message .= "※ご返信いただいてもお答えできませんので、ご了承ください。\n\n";
    $user_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $user_message .= "補助金インサイト\n";
    $user_message .= "https://joseikin-insight.com/\n";
    $user_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // メールヘッダー設定
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: 補助金インサイト <info@joseikin-insight.com>'
    );
    
    // メール送信
    $admin_sent = wp_mail($admin_email, $admin_subject, $admin_message, $headers);
    $user_sent = wp_mail($email, $user_subject, $user_message, $headers);
    
    // ログ記録（オプション）
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Contact form submitted - Admin email: ' . ($admin_sent ? 'sent' : 'failed'));
        error_log('Contact form submitted - User email: ' . ($user_sent ? 'sent' : 'failed'));
    }
    
    // 成功メッセージを表示するためにリダイレクト
    wp_redirect(add_query_arg('contact_sent', '1', home_url('/contact/')));
    exit;
}
add_action('admin_post_contact_form', 'gi_handle_contact_form_submission');
add_action('admin_post_nopriv_contact_form', 'gi_handle_contact_form_submission');
