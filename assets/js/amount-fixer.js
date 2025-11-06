/**
 * Grant Amount Fixer - JavaScript
 * 助成金額修正ツールのフロントエンド処理
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0.0
 */

(function($) {
    'use strict';
    
    // 状態管理
    let scanResults = null;
    let selectedPostIds = [];
    
    /**
     * 初期化
     */
    $(document).ready(function() {
        initEventHandlers();
    });
    
    /**
     * イベントハンドラー初期化
     */
    function initEventHandlers() {
        // スキャンボタン
        $('#gi-scan-btn').on('click', handleScan);
        
        // 修正ボタン
        $('#gi-fix-btn').on('click', handleFix);
        
        // 全選択チェックボックス
        $(document).on('change', '#gi-select-all', handleSelectAll);
        
        // 個別選択チェックボックス
        $(document).on('change', '.gi-post-checkbox', handlePostSelection);
    }
    
    /**
     * スキャン処理
     */
    function handleScan() {
        const $button = $('#gi-scan-btn');
        const $progress = $('#gi-scan-progress');
        const $results = $('#gi-scan-results');
        
        // ボタン無効化
        $button.prop('disabled', true);
        
        // プログレスバー表示
        $progress.show();
        updateProgress($progress, 0, 'スキャン中...');
        
        // 結果エリアをクリア
        $results.hide().empty();
        
        // AJAX実行
        $.ajax({
            url: giAmountFixer.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gi_scan_grant_amounts',
                nonce: giAmountFixer.nonce
            },
            success: function(response) {
                if (response.success) {
                    scanResults = response.data;
                    displayScanResults(response.data);
                    updateProgress($progress, 100, 'スキャン完了');
                    
                    setTimeout(function() {
                        $progress.fadeOut();
                    }, 1000);
                } else {
                    showError('スキャンに失敗しました: ' + (response.data.message || '不明なエラー'));
                }
            },
            error: function(xhr, status, error) {
                showError('通信エラーが発生しました: ' + error);
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    }
    
    /**
     * スキャン結果表示
     */
    function displayScanResults(data) {
        const $results = $('#gi-scan-results');
        
        let html = '<div class="gi-scan-summary">';
        html += '<h3>スキャン結果</h3>';
        html += '<p>スキャンした投稿数: <strong>' + data.total_scanned + '</strong></p>';
        html += '<p>修正が必要な投稿数: <strong class="gi-highlight">' + data.problematic_count + '</strong></p>';
        html += '</div>';
        
        if (data.problematic_count > 0) {
            html += '<div class="gi-post-list">';
            html += '<h4>修正対象の投稿</h4>';
            html += '<div class="gi-select-all-wrapper">';
            html += '<label><input type="checkbox" id="gi-select-all" checked> すべて選択</label>';
            html += '</div>';
            html += '<table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr>';
            html += '<th class="check-column"><input type="checkbox" id="gi-select-all-header" checked></th>';
            html += '<th>投稿タイトル</th>';
            html += '<th>問題のあるフィールド</th>';
            html += '<th>現在の値</th>';
            html += '<th>修正後の値</th>';
            html += '</tr></thead>';
            html += '<tbody>';
            
            $.each(data.problematic_posts, function(postId, postData) {
                const issuesHtml = postData.issues.map(function(issue) {
                    const fieldLabel = getFieldLabel(issue.field);
                    return '<div class="gi-issue">' +
                           '<strong>' + fieldLabel + ':</strong> ' +
                           '<span class="gi-old-value">' + formatNumber(issue.current_value) + '</span> → ' +
                           '<span class="gi-new-value">' + formatNumber(issue.suggested_value) + '</span>' +
                           '</div>';
                }).join('');
                
                html += '<tr>';
                html += '<td class="check-column"><input type="checkbox" class="gi-post-checkbox" value="' + postId + '" checked></td>';
                html += '<td><strong>' + escapeHtml(postData.title) + '</strong><br><small>ID: ' + postId + '</small></td>';
                html += '<td>' + postData.issues.length + '個</td>';
                html += '<td>' + postData.issues.map(i => formatNumber(i.current_value)).join('<br>') + '</td>';
                html += '<td>' + postData.issues.map(i => formatNumber(i.suggested_value)).join('<br>') + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            html += '</div>';
            
            // プレビューボタン
            html += '<div class="gi-action-buttons">';
            html += '<button id="gi-preview-btn" class="button button-primary">修正内容をプレビュー</button>';
            html += '</div>';
        } else {
            html += '<div class="gi-info-box">';
            html += '<p>修正が必要な投稿は見つかりませんでした。すべての金額は正常です。</p>';
            html += '</div>';
        }
        
        $results.html(html).fadeIn();
        
        // 選択状態の初期化
        selectedPostIds = Object.keys(data.problematic_posts).map(id => parseInt(id));
        
        // プレビューボタンのイベント
        $('#gi-preview-btn').on('click', handlePreview);
        
        // ヘッダーチェックボックスのイベント
        $('#gi-select-all-header').on('change', function() {
            $('#gi-select-all').prop('checked', $(this).prop('checked')).trigger('change');
        });
    }
    
    /**
     * プレビュー処理
     */
    function handlePreview() {
        if (selectedPostIds.length === 0) {
            showError('修正する投稿を選択してください');
            return;
        }
        
        const $button = $('#gi-preview-btn');
        $button.prop('disabled', true).text('プレビュー生成中...');
        
        $.ajax({
            url: giAmountFixer.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gi_preview_fix',
                nonce: giAmountFixer.nonce,
                post_ids: selectedPostIds
            },
            success: function(response) {
                if (response.success) {
                    displayPreview(response.data.preview);
                    $('#gi-fix-section').fadeIn();
                    
                    // プレビューセクションまでスクロール
                    $('html, body').animate({
                        scrollTop: $('#gi-preview-section').offset().top - 50
                    }, 500);
                } else {
                    showError('プレビュー生成に失敗しました: ' + (response.data.message || '不明なエラー'));
                }
            },
            error: function(xhr, status, error) {
                showError('通信エラーが発生しました: ' + error);
            },
            complete: function() {
                $button.prop('disabled', false).text('修正内容をプレビュー');
            }
        });
    }
    
    /**
     * プレビュー表示
     */
    function displayPreview(previewData) {
        const $preview = $('#gi-preview-section');
        let html = '<table class="wp-list-table widefat fixed striped gi-preview-table">';
        html += '<thead><tr>';
        html += '<th>投稿タイトル</th>';
        html += '<th>フィールド</th>';
        html += '<th>現在の値</th>';
        html += '<th></th>';
        html += '<th>修正後の値</th>';
        html += '</tr></thead>';
        html += '<tbody>';
        
        $.each(previewData, function(postId, data) {
            let rowspan = Object.keys(data.current).length;
            let first = true;
            
            $.each(data.current, function(field, currentValue) {
                html += '<tr>';
                
                if (first) {
                    html += '<td rowspan="' + rowspan + '"><strong>' + escapeHtml(data.title) + '</strong></td>';
                    first = false;
                }
                
                html += '<td>' + getFieldLabel(field) + '</td>';
                html += '<td class="gi-old-value">' + formatNumber(currentValue) + '</td>';
                html += '<td class="gi-arrow">→</td>';
                html += '<td class="gi-new-value">' + formatNumber(data.fixed[field]) + '</td>';
                html += '</tr>';
            });
        });
        
        html += '</tbody></table>';
        
        $('#gi-preview-results').html(html);
        $preview.fadeIn();
    }
    
    /**
     * 修正実行処理
     */
    function handleFix() {
        if (selectedPostIds.length === 0) {
            showError('修正する投稿を選択してください');
            return;
        }
        
        // 確認ダイアログ
        if (!confirm('選択した ' + selectedPostIds.length + ' 件の投稿を修正します。\n\nこの操作は元に戻せません。実行しますか？')) {
            return;
        }
        
        const $button = $('#gi-fix-btn');
        const $progress = $('#gi-fix-progress');
        const $results = $('#gi-fix-results');
        
        // ボタン無効化
        $button.prop('disabled', true);
        
        // プログレスバー表示
        $progress.show();
        updateProgress($progress, 0, '修正中...');
        
        // 結果エリアをクリア
        $results.hide().empty();
        
        // AJAX実行
        $.ajax({
            url: giAmountFixer.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gi_fix_grant_amounts',
                nonce: giAmountFixer.nonce,
                post_ids: selectedPostIds
            },
            success: function(response) {
                if (response.success) {
                    updateProgress($progress, 100, '修正完了');
                    displayFixResults(response.data);
                    
                    setTimeout(function() {
                        $progress.fadeOut();
                        $('#gi-complete-section').fadeIn();
                        
                        // 完了セクションまでスクロール
                        $('html, body').animate({
                            scrollTop: $('#gi-complete-section').offset().top - 50
                        }, 500);
                    }, 1000);
                } else {
                    showError('修正に失敗しました: ' + (response.data.message || '不明なエラー'));
                }
            },
            error: function(xhr, status, error) {
                showError('通信エラーが発生しました: ' + error);
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    }
    
    /**
     * 修正結果表示
     */
    function displayFixResults(data) {
        const $results = $('#gi-fix-results');
        
        let html = '<div class="gi-fix-summary">';
        html += '<h3>修正結果</h3>';
        html += '<p>成功: <strong class="gi-success">' + data.success_count + '件</strong></p>';
        if (data.error_count > 0) {
            html += '<p>失敗: <strong class="gi-error">' + data.error_count + '件</strong></p>';
        }
        html += '</div>';
        
        html += '<div class="gi-results-detail">';
        html += '<h4>詳細</h4>';
        html += '<ul>';
        
        $.each(data.results, function(postId, result) {
            if (result.success) {
                html += '<li class="gi-success-item">';
                html += '<span class="dashicons dashicons-yes-alt"></span>';
                html += '<strong>' + escapeHtml(result.title) + '</strong> - ';
                html += Object.keys(result.fixed_fields).length + '個のフィールドを修正';
                html += '</li>';
            } else {
                html += '<li class="gi-error-item">';
                html += '<span class="dashicons dashicons-warning"></span>';
                html += '<strong>' + escapeHtml(result.title) + '</strong> - ' + result.error;
                html += '</li>';
            }
        });
        
        html += '</ul>';
        html += '</div>';
        
        $results.html(html).fadeIn();
    }
    
    /**
     * 全選択処理
     */
    function handleSelectAll() {
        const checked = $(this).prop('checked');
        $('.gi-post-checkbox').prop('checked', checked);
        updateSelectedPostIds();
    }
    
    /**
     * 個別選択処理
     */
    function handlePostSelection() {
        updateSelectedPostIds();
        
        // 全選択チェックボックスの状態更新
        const allChecked = $('.gi-post-checkbox').length === $('.gi-post-checkbox:checked').length;
        $('#gi-select-all, #gi-select-all-header').prop('checked', allChecked);
    }
    
    /**
     * 選択投稿ID更新
     */
    function updateSelectedPostIds() {
        selectedPostIds = [];
        $('.gi-post-checkbox:checked').each(function() {
            selectedPostIds.push(parseInt($(this).val()));
        });
    }
    
    /**
     * プログレスバー更新
     */
    function updateProgress($container, percentage, text) {
        $container.find('.gi-progress-fill').css('width', percentage + '%');
        $container.find('.gi-progress-text').text(text);
    }
    
    /**
     * エラー表示
     */
    function showError(message) {
        const $error = $('<div class="notice notice-error is-dismissible"><p>' + escapeHtml(message) + '</p></div>');
        $('.gi-amount-fixer h1').after($error);
        
        // 自動削除
        setTimeout(function() {
            $error.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * フィールドラベル取得
     */
    function getFieldLabel(fieldName) {
        const labels = {
            'grant_amount_max': '助成金額上限',
            'grant_amount_min': '助成金額下限',
            'subsidy_rate_max': '補助率上限',
            'subsidy_rate_min': '補助率下限'
        };
        return labels[fieldName] || fieldName;
    }
    
    /**
     * 数値フォーマット
     */
    function formatNumber(num) {
        if (num === null || num === undefined || num === '') {
            return '-';
        }
        return parseFloat(num).toLocaleString('ja-JP');
    }
    
    /**
     * HTMLエスケープ
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
})(jQuery);
