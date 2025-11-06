/**
 * Google Sheets Admin JavaScript
 * スプレッドシート同期管理画面の機能
 */

(function($) {
    'use strict';

    /**
     * Google Sheets Admin Controller
     */
    const GISheetsAdmin = {
        /**
         * 初期化
         */
        init() {
            console.log('[GI Sheets Admin] Initializing...');
            
            if (typeof giSheetsAdmin === 'undefined') {
                console.error('[GI Sheets Admin] giSheetsAdmin object not found');
                return;
            }
            
            this.bindEvents();
            console.log('[GI Sheets Admin] Initialized successfully');
        },

        /**
         * イベントバインディング
         */
        bindEvents() {
            // 接続テストボタン
            $('#gi-test-connection').on('click', (e) => {
                e.preventDefault();
                this.testConnection();
            });

            // WP to Sheets 同期ボタン
            $('#gi-sync-wp-to-sheets').on('click', (e) => {
                e.preventDefault();
                this.syncData('wp_to_sheets');
            });

            // Sheets to WP 同期ボタン
            $('#gi-sync-sheets-to-wp').on('click', (e) => {
                e.preventDefault();
                this.syncData('sheets_to_wp');
            });
            
            // 都道府県データ検証・エクスポートボタン
            $('#export-invalid-prefectures').on('click', (e) => {
                e.preventDefault();
                this.exportInvalidPrefectures();
            });
            
            // タクソノミーエクスポートボタン
            $('#export-taxonomies').on('click', (e) => {
                e.preventDefault();
                this.exportTaxonomies();
            });
            
            // タクソノミーインポートボタン
            $('#import-taxonomies').on('click', (e) => {
                e.preventDefault();
                this.importTaxonomies();
            });
        },

        /**
         * 接続テスト
         */
        testConnection() {
            console.log('[GI Sheets Admin] Testing connection...');
            
            const $button = $('#gi-test-connection');
            const $result = $('#gi-test-result');
            
            // ボタンを無効化
            $button.prop('disabled', true);
            $button.html('<span class="gi-loading-spinner"></span> ' + giSheetsAdmin.strings.testing);
            
            // 結果エリアをクリア
            $result.removeClass('show gi-test-result-success gi-test-result-error').text('');
            
            // AJAX リクエスト
            $.ajax({
                url: giSheetsAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'gi_test_sheets_connection',
                    nonce: giSheetsAdmin.nonce
                },
                success: (response) => {
                    console.log('[GI Sheets Admin] Connection test response:', response);
                    
                    if (response.success) {
                        $result
                            .addClass('show gi-test-result-success')
                            .html('<strong>✓ ' + giSheetsAdmin.strings.success + '</strong><br>' + response.data.message);
                    } else {
                        $result
                            .addClass('show gi-test-result-error')
                            .html('<strong>✗ ' + giSheetsAdmin.strings.error + '</strong><br>' + response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('[GI Sheets Admin] Connection test error:', error);
                    $result
                        .addClass('show gi-test-result-error')
                        .html('<strong>✗ ' + giSheetsAdmin.strings.error + '</strong><br>AJAX エラー: ' + error);
                },
                complete: () => {
                    // ボタンを再有効化
                    $button.prop('disabled', false);
                    $button.text('接続をテスト');
                }
            });
        },

        /**
         * データ同期
         */
        syncData(direction) {
            console.log('[GI Sheets Admin] Starting sync:', direction);
            
            // 確認ダイアログ
            if (!confirm(giSheetsAdmin.strings.confirm_sync)) {
                return;
            }
            
            const $button = direction === 'wp_to_sheets' 
                ? $('#gi-sync-wp-to-sheets') 
                : $('#gi-sync-sheets-to-wp');
            const $progressContainer = $('#gi-progress-container');
            const $progressBar = $('#gi-progress-fill');
            const $progressText = $('#gi-progress-text');
            const $logContainer = $('#gi-log-messages');
            
            // ボタンを無効化
            $button.prop('disabled', true);
            $button.html('<span class="gi-loading-spinner"></span> ' + giSheetsAdmin.strings.syncing);
            
            // プログレスバーを表示
            $progressContainer.show();
            $progressBar.css('width', '0%');
            $progressText.text('0%');
            
            // ログをクリア
            $logContainer.empty();
            
            // AJAX リクエスト
            $.ajax({
                url: giSheetsAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'gi_manual_sheets_sync',
                    direction: direction,
                    nonce: giSheetsAdmin.nonce
                },
                success: (response) => {
                    console.log('[GI Sheets Admin] Sync response:', response);
                    
                    if (response.success) {
                        // 成功
                        $progressBar.css('width', '100%');
                        $progressText.text('100%');
                        
                        this.addLogEntry('success', response.data.message);
                        
                        if (response.data.details) {
                            this.addLogEntry('info', '詳細: ' + JSON.stringify(response.data.details));
                        }
                        
                        // 3秒後にプログレスバーを非表示
                        setTimeout(() => {
                            $progressContainer.fadeOut();
                        }, 3000);
                    } else {
                        // エラー
                        $progressBar.css('width', '100%');
                        $progressText.text('エラー');
                        $progressBar.css('background', '#d63638');
                        
                        this.addLogEntry('error', response.data.message || '同期に失敗しました');
                        
                        if (response.data.details) {
                            this.addLogEntry('error', '詳細: ' + JSON.stringify(response.data.details));
                        }
                    }
                },
                error: (xhr, status, error) => {
                    console.error('[GI Sheets Admin] Sync error:', error);
                    
                    $progressBar.css('width', '100%');
                    $progressText.text('エラー');
                    $progressBar.css('background', '#d63638');
                    
                    this.addLogEntry('error', 'AJAX エラー: ' + error);
                    
                    if (xhr.responseText) {
                        this.addLogEntry('error', 'レスポンス: ' + xhr.responseText);
                    }
                },
                complete: () => {
                    // ボタンを再有効化
                    $button.prop('disabled', false);
                    
                    if (direction === 'wp_to_sheets') {
                        $button.html('<i class="dashicons dashicons-upload"></i> WP → Sheets 同期');
                    } else {
                        $button.html('<i class="dashicons dashicons-download"></i> Sheets → WP 同期');
                    }
                }
            });
        },

        /**
         * 都道府県データ検証・エクスポート
         */
        exportInvalidPrefectures() {
            console.log('[GI Sheets Admin] Exporting invalid prefectures...');
            console.log('[GI Sheets Admin] AJAX URL:', giSheetsAdmin.ajaxurl);
            console.log('[GI Sheets Admin] Nonce:', giSheetsAdmin.nonce);
            
            if (!confirm('都道府県データの検証を実行し、問題のある投稿を「都道府県」シートにエクスポートします。よろしいですか？')) {
                console.log('[GI Sheets Admin] User cancelled');
                return;
            }
            
            const $button = $('#export-invalid-prefectures');
            const $result = $('#sync-result');
            const $message = $('#sync-message');
            
            // ボタンを無効化
            $button.prop('disabled', true).text('処理中...');
            
            // 結果エリアをクリア
            $result.hide();
            $message.text('');
            
            console.log('[GI Sheets Admin] Sending AJAX request...');
            
            // AJAX リクエスト
            $.ajax({
                url: giSheetsAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'gi_export_invalid_prefectures',
                    nonce: giSheetsAdmin.nonce
                },
                beforeSend: function() {
                    console.log('[GI Sheets Admin] AJAX request started');
                },
                success: (response) => {
                    console.log('[GI Sheets Admin] SUCCESS - Response:', response);
                    console.log('[GI Sheets Admin] Response type:', typeof response);
                    console.log('[GI Sheets Admin] Response.success:', response.success);
                    console.log('[GI Sheets Admin] Response.data:', response.data);
                    
                    if (response.success) {
                        $result.removeClass('notice-error').addClass('notice-success');
                        let message = response.data.message || response.data;
                        if (response.data.count) {
                            message += '<br>エクスポート件数: ' + response.data.count + '件';
                        }
                        if (response.data.spreadsheet_id) {
                            message += '<br><a href="https://docs.google.com/spreadsheets/d/' + response.data.spreadsheet_id + '/edit#gid=0" target="_blank">スプレッドシートを開く</a>';
                        }
                        $message.html(message);
                        console.log('[GI Sheets Admin] Success message displayed');
                    } else {
                        $result.removeClass('notice-success').addClass('notice-error');
                        $message.text(response.data || 'エクスポートに失敗しました');
                        console.log('[GI Sheets Admin] Error message displayed:', response.data);
                    }
                    
                    $result.show();
                },
                error: (xhr, status, error) => {
                    console.error('[GI Sheets Admin] ERROR - Status:', status);
                    console.error('[GI Sheets Admin] ERROR - Error:', error);
                    console.error('[GI Sheets Admin] ERROR - XHR:', xhr);
                    console.error('[GI Sheets Admin] ERROR - Response Text:', xhr.responseText);
                    console.error('[GI Sheets Admin] ERROR - Status Code:', xhr.status);
                    console.error('[GI Sheets Admin] ERROR - Status Text:', xhr.statusText);
                    
                    // レスポンステキストをパースしてみる
                    try {
                        const parsedResponse = JSON.parse(xhr.responseText);
                        console.error('[GI Sheets Admin] ERROR - Parsed Response:', parsedResponse);
                    } catch (e) {
                        console.error('[GI Sheets Admin] ERROR - Could not parse response as JSON');
                        console.error('[GI Sheets Admin] ERROR - Raw response (first 500 chars):', xhr.responseText.substring(0, 500));
                    }
                    
                    $result.removeClass('notice-success').addClass('notice-error');
                    
                    let errorMessage = 'エラーが発生しました: ' + error;
                    if (xhr.status === 500) {
                        errorMessage += '<br>サーバーエラー (500): PHPのエラーログを確認してください';
                        if (xhr.responseText) {
                            errorMessage += '<br>詳細: ' + xhr.responseText.substring(0, 200);
                        }
                    }
                    
                    $message.html(errorMessage);
                    $result.show();
                },
                complete: () => {
                    console.log('[GI Sheets Admin] AJAX request completed');
                    // ボタンを再有効化
                    $button.prop('disabled', false).text('🗾 都道府県データ検証・エクスポート');
                }
            });
        },

        /**
         * タクソノミーエクスポート
         */
        exportTaxonomies() {
            console.log('[GI Sheets Admin] Exporting taxonomies...');
            
            if (!confirm('カテゴリ、都道府県、市町村、タグのマスタデータをエクスポートします。よろしいですか？')) {
                console.log('[GI Sheets Admin] User cancelled');
                return;
            }
            
            const $button = $('#export-taxonomies');
            const $result = $('#sync-result');
            const $message = $('#sync-message');
            
            // ボタンを無効化
            $button.prop('disabled', true).text('エクスポート中...');
            
            // 結果エリアをクリア
            $result.hide();
            $message.html('');
            
            console.log('[GI Sheets Admin] Sending AJAX request...');
            
            // AJAX リクエスト
            $.ajax({
                url: giSheetsAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'gi_export_taxonomies',
                    nonce: giSheetsAdmin.nonce
                },
                beforeSend: function() {
                    console.log('[GI Sheets Admin] Export taxonomies AJAX started');
                },
                success: (response) => {
                    console.log('[GI Sheets Admin] SUCCESS - Response:', response);
                    
                    if (response.success) {
                        $result.removeClass('notice-error').addClass('notice-success');
                        
                        let message = '<strong>' + response.data.message + '</strong><br><br>';
                        
                        if (response.data.results && response.data.results.length > 0) {
                            message += '<table style="width: 100%; border-collapse: collapse;">';
                            message += '<thead><tr style="background: #f0f0f0;">';
                            message += '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">タクソノミー</th>';
                            message += '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">シート名</th>';
                            message += '<th style="padding: 8px; text-align: center; border: 1px solid #ddd;">件数</th>';
                            message += '<th style="padding: 8px; text-align: center; border: 1px solid #ddd;">ステータス</th>';
                            message += '</tr></thead><tbody>';
                            
                            response.data.results.forEach((result) => {
                                const status = result.success ? '✅ 成功' : '❌ 失敗';
                                const statusColor = result.success ? '#00a32a' : '#d63638';
                                message += '<tr>';
                                message += '<td style="padding: 8px; border: 1px solid #ddd;">' + result.taxonomy + '</td>';
                                message += '<td style="padding: 8px; border: 1px solid #ddd;">' + result.sheet_name + '</td>';
                                message += '<td style="padding: 8px; text-align: center; border: 1px solid #ddd;">' + result.count + '</td>';
                                message += '<td style="padding: 8px; text-align: center; border: 1px solid #ddd; color: ' + statusColor + ';"><strong>' + status + '</strong></td>';
                                message += '</tr>';
                                
                                if (result.error) {
                                    message += '<tr><td colspan="4" style="padding: 8px; border: 1px solid #ddd; color: #d63638;">エラー: ' + result.error + '</td></tr>';
                                }
                            });
                            
                            message += '</tbody></table>';
                        }
                        
                        $message.html(message);
                    } else {
                        $result.removeClass('notice-success').addClass('notice-error');
                        
                        let errorMsg = response.data.message || 'エクスポートに失敗しました';
                        
                        if (response.data.results) {
                            errorMsg += '<br><br><strong>詳細:</strong><br>';
                            response.data.results.forEach((result) => {
                                errorMsg += '- ' + result.taxonomy + ': ' + (result.error || '不明なエラー') + '<br>';
                            });
                        }
                        
                        $message.html(errorMsg);
                    }
                    
                    $result.show();
                },
                error: (xhr, status, error) => {
                    console.error('[GI Sheets Admin] ERROR - XHR:', xhr);
                    console.error('[GI Sheets Admin] ERROR - Status:', status);
                    console.error('[GI Sheets Admin] ERROR - Error:', error);
                    
                    $result.removeClass('notice-success').addClass('notice-error');
                    $message.html('エラーが発生しました: ' + error);
                    $result.show();
                },
                complete: () => {
                    console.log('[GI Sheets Admin] Export taxonomies completed');
                    $button.prop('disabled', false).text('📊 タクソノミーをエクスポート');
                }
            });
        },

        /**
         * タクソノミーインポート
         */
        importTaxonomies() {
            console.log('[GI Sheets Admin] Importing taxonomies...');
            
            if (!confirm('スプレッドシートからタクソノミーをインポートします。\n\n⚠️ 注意: 既存のタクソノミーが更新される可能性があります。\n削除する場合は名前列に「DELETE」または「削除」と入力してください。\n\nよろしいですか？')) {
                console.log('[GI Sheets Admin] User cancelled');
                return;
            }
            
            const $button = $('#import-taxonomies');
            const $result = $('#sync-result');
            const $message = $('#sync-message');
            
            // ボタンを無効化
            $button.prop('disabled', true).text('インポート中...');
            
            // 結果エリアをクリア
            $result.hide();
            $message.html('');
            
            console.log('[GI Sheets Admin] Sending AJAX request...');
            
            // AJAX リクエスト
            $.ajax({
                url: giSheetsAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'gi_import_taxonomies',
                    nonce: giSheetsAdmin.nonce
                },
                beforeSend: function() {
                    console.log('[GI Sheets Admin] Import taxonomies AJAX started');
                },
                success: (response) => {
                    console.log('[GI Sheets Admin] SUCCESS - Response:', response);
                    
                    if (response.success) {
                        $result.removeClass('notice-error').addClass('notice-success');
                        
                        let message = '<strong>' + response.data.message + '</strong><br><br>';
                        
                        if (response.data.results && response.data.results.length > 0) {
                            message += '<table style="width: 100%; border-collapse: collapse;">';
                            message += '<thead><tr style="background: #f0f0f0;">';
                            message += '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">タクソノミー</th>';
                            message += '<th style="padding: 8px; text-align: left; border: 1px solid #ddd;">シート名</th>';
                            message += '<th style="padding: 8px; text-align: center; border: 1px solid #ddd;">新規作成</th>';
                            message += '<th style="padding: 8px; text-align: center; border: 1px solid #ddd;">更新</th>';
                            message += '<th style="padding: 8px; text-align: center; border: 1px solid #ddd;">削除</th>';
                            message += '<th style="padding: 8px; text-align: center; border: 1px solid #ddd;">スキップ</th>';
                            message += '</tr></thead><tbody>';
                            
                            response.data.results.forEach((result) => {
                                message += '<tr>';
                                message += '<td style="padding: 8px; border: 1px solid #ddd;">' + result.taxonomy + '</td>';
                                message += '<td style="padding: 8px; border: 1px solid #ddd;">' + result.sheet_name + '</td>';
                                message += '<td style="padding: 8px; text-align: center; border: 1px solid #ddd; color: #00a32a;"><strong>' + result.created + '</strong></td>';
                                message += '<td style="padding: 8px; text-align: center; border: 1px solid #ddd; color: #0073aa;"><strong>' + result.updated + '</strong></td>';
                                message += '<td style="padding: 8px; text-align: center; border: 1px solid #ddd; color: #d63638;"><strong>' + result.deleted + '</strong></td>';
                                message += '<td style="padding: 8px; text-align: center; border: 1px solid #ddd; color: #999;"><strong>' + result.skipped + '</strong></td>';
                                message += '</tr>';
                                
                                if (result.errors && result.errors.length > 0) {
                                    message += '<tr><td colspan="6" style="padding: 8px; border: 1px solid #ddd; color: #d63638;">';
                                    message += '<strong>エラー:</strong><br>';
                                    result.errors.forEach((err) => {
                                        message += '- ' + err + '<br>';
                                    });
                                    message += '</td></tr>';
                                }
                                
                                if (result.error) {
                                    message += '<tr><td colspan="6" style="padding: 8px; border: 1px solid #ddd; color: #d63638;">エラー: ' + result.error + '</td></tr>';
                                }
                            });
                            
                            message += '</tbody></table>';
                        }
                        
                        $message.html(message);
                    } else {
                        $result.removeClass('notice-success').addClass('notice-error');
                        $message.html(response.data || 'インポートに失敗しました');
                    }
                    
                    $result.show();
                },
                error: (xhr, status, error) => {
                    console.error('[GI Sheets Admin] ERROR - XHR:', xhr);
                    console.error('[GI Sheets Admin] ERROR - Status:', status);
                    console.error('[GI Sheets Admin] ERROR - Error:', error);
                    
                    $result.removeClass('notice-success').addClass('notice-error');
                    $message.html('エラーが発生しました: ' + error);
                    $result.show();
                },
                complete: () => {
                    console.log('[GI Sheets Admin] Import taxonomies completed');
                    $button.prop('disabled', false).text('📥 タクソノミーをインポート');
                }
            });
        },
        
        /**
         * ログエントリーを追加
         */
        addLogEntry(type, message) {
            const $logContainer = $('#gi-log-messages');
            const timestamp = new Date().toLocaleTimeString('ja-JP');
            
            let typeClass = '';
            let typeIcon = '';
            
            switch(type) {
                case 'success':
                    typeClass = 'gi-log-success';
                    typeIcon = '✓';
                    break;
                case 'error':
                    typeClass = 'gi-log-error';
                    typeIcon = '✗';
                    break;
                case 'warning':
                    typeClass = 'gi-log-warning';
                    typeIcon = '⚠';
                    break;
                default:
                    typeClass = 'gi-log-message';
                    typeIcon = 'ℹ';
            }
            
            const $entry = $('<div class="gi-log-entry">')
                .html(
                    '<span class="gi-log-timestamp">[' + timestamp + ']</span>' +
                    '<span class="' + typeClass + '">' + typeIcon + ' ' + message + '</span>'
                );
            
            $logContainer.prepend($entry);
            
            // 最大50エントリーまで保持
            if ($logContainer.children().length > 50) {
                $logContainer.children().last().remove();
            }
        }
    };

    // ドキュメント読み込み完了時に初期化
    $(document).ready(() => {
        GISheetsAdmin.init();
    });

})(jQuery);
