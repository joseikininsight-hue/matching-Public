/*!
 * Grant Insight Perfect - 統合管理画面JavaScript
 * 管理画面専用スクリプト（メタボックス + Google Sheets管理）
 * 
 * @version 1.0.0
 * @date 2025-10-05
 */

/**
 * =============================================================================
 * GRANT INSIGHT ADMIN - 管理画面名前空間
 * =============================================================================
 */
const GrantInsightAdmin = {
    // バージョン情報
    version: '1.0.0',
    
    // 設定
    config: {
        ajaxTimeout: 60000,
        autoSaveDelay: 2000,
        noticeDisplayTime: 5000
    },

    // 初期化フラグ
    initialized: false,

    /**
     * 初期化
     */
    init() {
        if (this.initialized) return;
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupAll());
        } else {
            this.setupAll();
        }
    },

    /**
     * 全機能のセットアップ
     */
    setupAll() {
        try {
            this.setupMetaboxes();
            this.setupSheetsAdmin();
            this.setupUtils();
            
            this.initialized = true;
            console.log('[Grant Insight Admin] Initialized successfully');
        } catch (error) {
            console.error('[Grant Insight Admin] Initialization error:', error);
        }
    },

    /**
     * ==========================================================================
     * メタボックス機能
     * ==========================================================================
     */
    setupMetaboxes() {
        this.setupTaxonomyMetaboxes();
        this.setupFieldTracking();
    },

    /**
     * タクソノミーメタボックス
     */
    setupTaxonomyMetaboxes() {
        // 都道府県：全国対象チェックボックス
        const selectAllPrefectures = document.getElementById('select_all_prefectures');
        if (selectAllPrefectures) {
            selectAllPrefectures.addEventListener('change', (e) => {
                const isChecked = e.target.checked;
                document.querySelectorAll('.prefecture-checkbox').forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
            });
        }
        
        // 都道府県：個別チェックボックス変更時
        document.querySelectorAll('.prefecture-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                const totalPrefectures = document.querySelectorAll('.prefecture-checkbox').length;
                const checkedPrefectures = document.querySelectorAll('.prefecture-checkbox:checked').length;
                if (selectAllPrefectures) {
                    selectAllPrefectures.checked = totalPrefectures === checkedPrefectures;
                }
            });
        });
        
        // 市町村：検索機能（強化版）
        const municipalitySearch = document.getElementById('municipality_search');
        if (municipalitySearch) {
            municipalitySearch.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                document.querySelectorAll('.municipality-option').forEach(option => {
                    const text = option.textContent.toLowerCase();
                    option.style.display = text.includes(searchTerm) ? 'block' : 'none';
                });
                
                // 都道府県グループの表示/非表示も制御
                document.querySelectorAll('.prefecture-group').forEach(group => {
                    const visibleMunicipalities = group.querySelectorAll('.municipality-option[style*="block"], .municipality-option:not([style*="none"])');
                    group.style.display = visibleMunicipalities.length > 0 ? 'block' : 'none';
                });
            });
        }
        
        // 都道府県選択による市町村の自動更新
        this.setupPrefectureMunicipalitySync();
        
        // 新規タームの追加
        this.setupNewTermAddition();
        
        // 初期選択状態チェック
        this.checkInitialSelections();
    },

    /**
     * 新規ターム追加機能
     */
    setupNewTermAddition() {
        // カテゴリー追加
        const addCategoryBtn = document.getElementById('add_grant_category');
        const newCategoryInput = document.getElementById('new_grant_category');
        
        if (addCategoryBtn && newCategoryInput) {
            addCategoryBtn.addEventListener('click', () => {
                const categoryName = newCategoryInput.value.trim();
                if (categoryName) {
                    this.addNewTaxonomyTerm('grant_category', categoryName, 'category');
                }
            });
            
            newCategoryInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addCategoryBtn.click();
                }
            });
        }
        
        // 市町村追加
        const addMunicipalityBtn = document.getElementById('add_municipality');
        const newMunicipalityInput = document.getElementById('new_municipality');
        
        if (addMunicipalityBtn && newMunicipalityInput) {
            addMunicipalityBtn.addEventListener('click', () => {
                const municipalityName = newMunicipalityInput.value.trim();
                if (municipalityName) {
                    this.addNewTaxonomyTerm('grant_municipality', municipalityName, 'municipality');
                }
            });
            
            newMunicipalityInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addMunicipalityBtn.click();
                }
            });
        }
    },

    /**
     * 都道府県と市町村の同期機能
     */
    setupPrefectureMunicipalitySync() {
        // 都道府県チェックボックスの変更を監視
        document.querySelectorAll('.prefecture-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.updateAvailableMunicipalities();
            });
        });
        
        // 地域制限タイプの変更を監視
        document.querySelectorAll('input[name="municipality_selection_type"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.handleRegionalLimitationChange(e.target.value);
            });
        });
        
        // 都道府県フィルターの変更を監視
        const prefectureFilter = document.getElementById('prefecture_filter');
        if (prefectureFilter) {
            prefectureFilter.addEventListener('change', (e) => {
                this.filterMunicipalitiesByPrefecture(e.target.value);
            });
        }
    },
    
    /**
     * 選択された都道府県に基づいて利用可能な市町村を更新
     */
    updateAvailableMunicipalities() {
        const selectedPrefectures = Array.from(document.querySelectorAll('.prefecture-checkbox:checked'))
            .map(cb => cb.dataset.prefectureSlug || cb.value);
        
        // 各都道府県グループの表示/非表示を制御
        document.querySelectorAll('.prefecture-group').forEach(group => {
            const prefectureSlug = group.dataset.prefecture;
            
            if (selectedPrefectures.length === 0 || selectedPrefectures.includes(prefectureSlug)) {
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
                
                // 非表示の都道府県の市町村チェックを外す
                group.querySelectorAll('.municipality-option input[type="checkbox"]:checked').forEach(cb => {
                    cb.checked = false;
                });
            }
        });
        
        // 都道府県フィルターを更新
        const prefectureFilter = document.getElementById('prefecture_filter');
        if (prefectureFilter && selectedPrefectures.length > 0) {
            // 最初に選択された都道府県をデフォルトに設定
            prefectureFilter.value = selectedPrefectures[0];
            this.filterMunicipalitiesByPrefecture(selectedPrefectures[0]);
        }
    },
    
    /**
     * 地域制限タイプ変更の処理
     */
    handleRegionalLimitationChange(limitationType) {
        const prefectureLevelInfo = document.getElementById('prefecture-level-info');
        const municipalityLevelControls = document.getElementById('municipality-level-controls');
        const autoMunicipalityInfo = document.getElementById('auto-municipality-info');
        
        if (limitationType === 'prefecture_level') {
            if (prefectureLevelInfo) prefectureLevelInfo.style.display = 'block';
            if (municipalityLevelControls) municipalityLevelControls.style.display = 'none';
            if (autoMunicipalityInfo) autoMunicipalityInfo.style.display = 'block';
            
            // ACFフィールドの地域制限を更新
            this.updateRegionalLimitationField('prefecture_only');
            
        } else if (limitationType === 'municipality_level') {
            if (prefectureLevelInfo) prefectureLevelInfo.style.display = 'none';
            if (municipalityLevelControls) municipalityLevelControls.style.display = 'block';
            if (autoMunicipalityInfo) autoMunicipalityInfo.style.display = 'none';
            
            // ACFフィールドの地域制限を更新
            this.updateRegionalLimitationField('municipality_only');
        }
    },
    
    /**
     * 地域制限フィールドの更新
     */
    updateRegionalLimitationField(value) {
        // ACFフィールドまたは標準フィールドを更新
        const regionalLimitationField = document.querySelector('select[name*="regional_limitation"], input[name="regional_limitation"]');
        if (regionalLimitationField) {
            regionalLimitationField.value = value;
            
            // changeイベントを発火してACFの処理をトリガー
            regionalLimitationField.dispatchEvent(new Event('change', { bubbles: true }));
        }
    },
    
    /**
     * 都道府県による市町村フィルタリング
     */
    filterMunicipalitiesByPrefecture(prefectureSlug) {
        document.querySelectorAll('.prefecture-group').forEach(group => {
            const groupPrefecture = group.dataset.prefecture;
            
            if (!prefectureSlug || groupPrefecture === prefectureSlug) {
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
            }
        });
    },

    /**
     * 新しいタクソノミータームを追加
     */
    addNewTaxonomyTerm(taxonomy, termName, type) {
        const ajaxData = {
            action: 'gi_add_taxonomy_term',
            taxonomy: taxonomy,
            term_name: termName,
            nonce: window.grantMetaboxes?.nonce
        };

        this.ajax(ajaxData)
            .then(response => {
                if (response.success) {
                    this.addTermToUI(response.data, type);
                    this.showNotice('success', `「${response.data.name}」を追加しました。`);
                } else {
                    this.showNotice('error', `追加に失敗しました: ${response.data}`);
                }
            })
            .catch(error => {
                console.error('Add term error:', error);
                this.showNotice('error', '通信エラーが発生しました。');
            });
    },

    /**
     * UIに新しいタームを追加
     */
    addTermToUI(termData, type) {
        const termId = termData.term_id;
        const termName = termData.name;
        
        let targetContainer = '';
        let inputName = '';
        let inputId = '';
        
        if (type === 'category') {
            targetContainer = '#grant-category-selection';
            inputName = 'grant_categories[]';
            inputId = 'new_grant_category';
        } else if (type === 'municipality') {
            targetContainer = '#grant-municipality-selection';
            inputName = 'grant_municipalities[]';
            inputId = 'new_municipality';
        }
        
        const container = document.querySelector(targetContainer);
        const input = document.getElementById(inputId);
        
        if (container) {
            const newOption = document.createElement('label');
            newOption.style.display = 'block';
            newOption.style.marginBottom = '6px';
            if (type === 'municipality') {
                newOption.classList.add('municipality-option');
            }
            
            newOption.innerHTML = `
                <input type="checkbox" 
                       name="${inputName}" 
                       value="${termId}"
                       checked>
                ${this.escapeHtml(termName)}
                <span style="color: #666;">（0件）</span>
            `;
            
            // 追加ボタンの直前に挿入
            const addButtonContainer = container.querySelector('> div:last-child');
            if (addButtonContainer) {
                container.insertBefore(newOption, addButtonContainer);
            } else {
                container.appendChild(newOption);
            }
        }
        
        // 入力フィールドをクリア
        if (input) {
            input.value = '';
        }
    },

    /**
     * 初期選択状態をチェック
     */
    checkInitialSelections() {
        const selectAllPrefectures = document.getElementById('select_all_prefectures');
        if (selectAllPrefectures) {
            const totalPrefectures = document.querySelectorAll('.prefecture-checkbox').length;
            const checkedPrefectures = document.querySelectorAll('.prefecture-checkbox:checked').length;
            selectAllPrefectures.checked = totalPrefectures === checkedPrefectures && totalPrefectures > 0;
        }
    },

    /**
     * フィールド変更の追跡
     */
    setupFieldTracking() {
        // タクソノミーの変更を検知
        const taxonomyInputs = document.querySelectorAll(
            'input[name="grant_categories[]"], input[name="grant_prefectures[]"], input[name="grant_municipalities[]"]'
        );
        
        taxonomyInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                // 変更を視覚的に表示
                const metaboxContent = e.target.closest('.grant-metabox-content');
                if (metaboxContent) {
                    metaboxContent.style.borderLeft = '3px solid #00a0d2';
                    setTimeout(() => {
                        metaboxContent.style.borderLeft = '';
                    }, 2000);
                }
            });
        });
    },

    /**
     * ==========================================================================
     * Google Sheets管理機能
     * ==========================================================================
     */
    setupSheetsAdmin() {
        this.setupConnectionTest();
        this.setupSyncButtons();
        this.setupLogManagement();
        this.setupSheetOperations();
        this.setupFieldOperations();
        this.setupFormHandling();
        
        // 初回接続テスト
        setTimeout(() => {
            if (document.getElementById('test-connection')) {
                this.testConnection();
            }
        }, 1000);
    },

    /**
     * 接続テスト機能
     */
    setupConnectionTest() {
        const testBtn = document.getElementById('test-connection');
        if (testBtn) {
            testBtn.addEventListener('click', () => this.testConnection());
        }
    },

    /**
     * 接続テスト実行
     */
    testConnection() {
        const btn = document.getElementById('test-connection');
        const status = document.getElementById('connection-status');
        
        if (!btn || !status) return;
        
        // ボタンを無効化
        btn.disabled = true;
        btn.textContent = window.giSheetsAdmin?.strings?.testing || 'テスト中...';
        
        // ステータスを更新中に設定
        this.updateConnectionStatus('testing', 'テスト中...');
        
        const ajaxData = {
            action: 'gi_test_sheets_connection',
            nonce: window.giSheetsAdmin?.nonce
        };

        this.ajax(ajaxData)
            .then(response => {
                if (response.success) {
                    this.updateConnectionStatus('connected', response.data);
                    this.showNotice('success', response.data);
                } else {
                    this.updateConnectionStatus('error', response.data || 'エラーが発生しました');
                    this.showNotice('error', response.data || 'エラーが発生しました');
                }
            })
            .catch(error => {
                console.error('Connection test error:', error);
                const message = 'ネットワークエラー: ' + error.message;
                this.updateConnectionStatus('error', message);
                this.showNotice('error', message);
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = '接続をテスト';
            });
    },

    /**
     * 接続ステータス更新
     */
    updateConnectionStatus(status, message) {
        const statusElement = document.getElementById('connection-status');
        if (!statusElement) return;
        
        const textElement = statusElement.querySelector('.gi-status-text');
        
        // クラスをリセット
        statusElement.className = 'gi-connection-status';
        
        // 新しいクラスを追加
        statusElement.classList.add(`gi-status-${status}`);
        
        // テキストを更新
        if (textElement) {
            textElement.textContent = message;
        } else {
            statusElement.textContent = message;
        }
    },

    /**
     * 同期ボタンの設定
     */
    setupSyncButtons() {
        document.querySelectorAll('.gi-sync-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleManualSync(e));
        });
    },

    /**
     * 手動同期処理
     */
    handleManualSync(event) {
        const btn = event.target;
        const direction = btn.dataset.direction;
        const originalText = btn.textContent;
        
        // 確認ダイアログ
        const confirmMessage = window.giSheetsAdmin?.strings?.confirm_sync || 
                              '同期を実行しますか？この操作には時間がかかる場合があります。';
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // ボタンを無効化
        btn.disabled = true;
        btn.textContent = window.giSheetsAdmin?.strings?.syncing || '同期中...';
        document.querySelectorAll('.gi-sync-btn').forEach(b => b.disabled = true);
        
        // 結果エリアを初期化
        const syncResult = document.getElementById('sync-result');
        if (syncResult) {
            syncResult.style.display = 'none';
        }
        
        const ajaxData = {
            action: 'gi_manual_sheets_sync',
            direction: direction,
            nonce: window.giSheetsAdmin?.nonce
        };

        this.ajax(ajaxData, { timeout: 120000 }) // 2分タイムアウト
            .then(response => {
                if (response.success) {
                    this.showSyncResult('success', response.data);
                    this.showNotice('success', response.data);
                } else {
                    this.showSyncResult('error', response.data || '同期に失敗しました');
                    this.showNotice('error', response.data || '同期に失敗しました');
                }
            })
            .catch(error => {
                console.error('Sync error details:', {
                    message: error.message,
                    stack: error.stack,
                    error: error
                });
                
                // より詳細なエラーメッセージ
                let message = 'ネットワークエラー: ' + error.message;
                
                // HTTP 500エラーの場合
                if (error.message.includes('HTTP 500')) {
                    message = 'サーバーエラー (HTTP 500): PHPエラーが発生しました。WordPressのエラーログを確認してください。';
                    console.error('HTTP 500 エラー詳細: admin-ajax.phpでPHPの致命的エラーが発生している可能性があります。');
                    console.error('考えられる原因:');
                    console.error('1. Google Sheets APIの認証エラー');
                    console.error('2. メモリ不足またはタイムアウト');
                    console.error('3. 未定義の関数または変数へのアクセス');
                }
                
                this.showSyncResult('error', message);
                this.showNotice('error', message);
            })
            .finally(() => {
                // ボタンを復元
                document.querySelectorAll('.gi-sync-btn').forEach(b => b.disabled = false);
                btn.textContent = originalText;
                
                // ログを自動更新
                setTimeout(() => this.refreshLog(), 2000);
            });
    },

    /**
     * 同期結果表示
     */
    showSyncResult(type, message) {
        const result = document.getElementById('sync-result');
        if (!result) return;
        
        const notice = result.querySelector('.notice');
        const messageElement = document.getElementById('sync-message');
        
        if (notice) {
            // クラスをリセット
            notice.classList.remove('notice-success', 'notice-error');
            
            // 新しいクラスを追加
            notice.classList.add(type === 'success' ? 'notice-success' : 'notice-error');
        }
        
        // メッセージを設定
        if (messageElement) {
            messageElement.textContent = message;
        }
        
        // 表示
        result.style.display = 'block';
        
        // 5秒後に自動で隠す
        setTimeout(() => {
            result.style.display = 'none';
        }, this.config.noticeDisplayTime);
    },

    /**
     * ログ管理の設定
     */
    setupLogManagement() {
        const refreshBtn = document.getElementById('refresh-log');
        const clearBtn = document.getElementById('clear-log');
        
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshLog());
        }
        
        if (clearBtn) {
            clearBtn.addEventListener('click', () => this.clearLog());
        }
    },

    /**
     * ログ更新
     */
    refreshLog() {
        const btn = document.getElementById('refresh-log');
        if (!btn) return;
        
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = '更新中...';
        
        // シンプルにページをリロード
        setTimeout(() => {
            window.location.reload();
        }, 500);
    },

    /**
     * ログクリア
     */
    clearLog() {
        if (!confirm('ログをクリアしますか？この操作は取り消せません。')) {
            return;
        }
        
        const btn = document.getElementById('clear-log');
        if (!btn) return;
        
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'クリア中...';
        
        const ajaxData = {
            action: 'gi_clear_sheets_log',
            nonce: window.giSheetsAdmin?.nonce
        };

        this.ajax(ajaxData)
            .then(response => {
                if (response.success) {
                    this.showNotice('success', response.data);
                    
                    // ログエリアをクリア
                    const logElement = document.getElementById('sync-log');
                    if (logElement) {
                        logElement.innerHTML = '<p>まだログがありません。</p>';
                    }
                } else {
                    this.showNotice('error', response.data || 'ログのクリアに失敗しました');
                }
            })
            .catch(error => {
                console.error('Clear log error:', error);
                this.showNotice('error', 'ネットワークエラー: ' + error.message);
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = originalText;
            });
    },

    /**
     * シート操作の設定
     */
    setupSheetOperations() {
        const initializeBtn = document.getElementById('initialize-sheet');
        const exportAllBtn = document.getElementById('export-all-posts');
        const clearSheetBtn = document.getElementById('clear-sheet');
        
        if (initializeBtn) {
            initializeBtn.addEventListener('click', () => this.initializeSheet());
        }
        
        if (exportAllBtn) {
            exportAllBtn.addEventListener('click', () => this.exportAllPosts());
        }
        
        if (clearSheetBtn) {
            clearSheetBtn.addEventListener('click', () => this.clearSheet());
        }
    },

    /**
     * シート初期化
     */
    initializeSheet() {
        if (!confirm('スプレッドシートを初期化しますか？ヘッダー行と既存投稿がエクスポートされます。')) {
            return;
        }
        
        this.executeSheetOperation('initialize-sheet', 'gi_initialize_sheet', '初期化中...');
    },

    /**
     * 全投稿エクスポート
     */
    exportAllPosts() {
        if (!confirm('全投稿をスプレッドシートにエクスポートしますか？')) {
            return;
        }
        
        this.executeSheetOperation('export-all-posts', 'gi_export_all_posts', 'エクスポート中...');
    },

    /**
     * シートクリア
     */
    clearSheet() {
        if (!confirm('⚠️ 注意：スプレッドシートの全データが削除されます。\nこの操作は取り消せません。本当に実行しますか？')) {
            return;
        }
        
        this.executeSheetOperation('clear-sheet', 'gi_clear_sheet', 'クリア中...');
    },

    /**
     * シート操作実行のヘルパー
     */
    executeSheetOperation(btnId, action, loadingText) {
        const btn = document.getElementById(btnId);
        if (!btn) return;
        
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = loadingText;
        
        const ajaxData = {
            action: action,
            nonce: window.giSheetsAdmin?.nonce
        };

        this.ajax(ajaxData, { timeout: 120000 })
            .then(response => {
                if (response.success) {
                    this.showNotice('success', response.data);
                } else {
                    this.showNotice('error', response.data || '操作に失敗しました');
                }
            })
            .catch(error => {
                console.error(`${action} error:`, error);
                this.showNotice('error', 'ネットワークエラー: ' + error.message);
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = originalText;
            });
    },

    /**
     * フィールド操作の設定
     */
    setupFieldOperations() {
        const setupValidationBtn = document.getElementById('setup-field-validation');
        const testFieldsBtn = document.getElementById('test-specific-fields');
        
        if (setupValidationBtn) {
            setupValidationBtn.addEventListener('click', () => this.setupFieldValidation());
        }
        
        if (testFieldsBtn) {
            testFieldsBtn.addEventListener('click', () => this.testSpecificFields());
        }
    },

    /**
     * フィールドバリデーション設定
     */
    setupFieldValidation() {
        const btn = document.getElementById('setup-field-validation');
        const result = document.getElementById('validation-result');
        const message = document.getElementById('validation-message');
        
        if (!btn) return;
        
        btn.disabled = true;
        btn.innerHTML = '🔧 設定準備中...';
        
        if (result) result.style.display = 'none';
        
        const ajaxData = {
            action: 'gi_setup_field_validation',
            nonce: window.giSheetsAdmin?.nonce
        };

        this.ajax(ajaxData, { timeout: this.config.ajaxTimeout })
            .then(response => {
                if (response.success) {
                    const data = response.data;
                    const html = `
                        <strong>✅ フィールドバリデーション情報の準備が完了しました</strong><br>
                        ${data.message}<br><br>
                        <strong>📋 次の手順でスプレッドシートにプルダウンを設定してください：</strong><br>
                        ${Object.values(data.next_steps || {}).map((step, index) => `${index + 1}. ${step}`).join('<br>')}
                        <br><br>
                        <em>設定後は、選択肢フィールド（E、M、O、R、U、V列）の背景が薄い青色になり、プルダウンメニューから正しい値を選択できるようになります。</em>
                    `;
                    
                    if (message) message.innerHTML = html;
                    if (result) {
                        result.classList.remove('notice-error', 'notice-warning');
                        result.classList.add('notice-success');
                        result.style.display = 'block';
                    }
                } else {
                    const errorHtml = '❌ フィールドバリデーション設定の準備に失敗しました: ' + (response.data || '不明なエラー');
                    if (message) message.innerHTML = errorHtml;
                    if (result) {
                        result.classList.remove('notice-success', 'notice-warning');
                        result.classList.add('notice-error');
                        result.style.display = 'block';
                    }
                }
            })
            .catch(error => {
                console.error('Field validation setup error:', error);
                const errorHtml = '❌ フィールドバリデーション設定中にエラーが発生しました: ' + error.message;
                if (message) message.innerHTML = errorHtml;
                if (result) {
                    result.classList.remove('notice-success', 'notice-warning');
                    result.classList.add('notice-error');
                    result.style.display = 'block';
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '🔧 フィールドバリデーション設定を準備';
            });
    },

    /**
     * 特定フィールドテスト
     */
    testSpecificFields() {
        const btn = document.getElementById('test-specific-fields');
        const result = document.getElementById('field-test-result');
        const content = document.getElementById('field-test-content');
        
        if (!btn) return;
        
        btn.disabled = true;
        btn.textContent = '🔍 テスト実行中...';
        
        if (result) result.style.display = 'none';
        
        const ajaxData = {
            action: 'gi_test_specific_fields',
            nonce: window.giSheetsAdmin?.nonce
        };

        this.ajax(ajaxData, { timeout: 30000 })
            .then(response => {
                if (response.success) {
                    const html = this.buildFieldTestResultHtml(response.data);
                    
                    if (content) content.innerHTML = html;
                    if (result) {
                        result.classList.remove('notice-error');
                        result.classList.add('notice-success');
                        result.style.display = 'block';
                    }
                } else {
                    const errorHtml = '❌ フィールドテストに失敗しました: ' + (response.data || '不明なエラー');
                    if (content) content.innerHTML = errorHtml;
                    if (result) {
                        result.classList.remove('notice-success');
                        result.classList.add('notice-error');
                        result.style.display = 'block';
                    }
                }
            })
            .catch(error => {
                console.error('Field test error:', error);
                const errorHtml = '❌ フィールドテスト中にエラーが発生しました: ' + error.message;
                if (content) content.innerHTML = errorHtml;
                if (result) {
                    result.classList.remove('notice-success');
                    result.classList.add('notice-error');
                    result.style.display = 'block';
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = '🔍 フィールド同期テスト';
            });
    },

    /**
     * フィールドテスト結果HTMLの構築
     */
    buildFieldTestResultHtml(data) {
        let html = `
            <strong>🔍 フィールド同期テスト結果</strong><br>
            <strong>テスト対象行:</strong> ${data.total_rows || 0}行（最初の5行をテスト）<br><br>
        `;
        
        if (!data.test_results || data.test_results.length === 0) {
            html += '<div style="background:#fff3cd;padding:10px;border-radius:3px;margin:5px 0;">⚠️ テスト可能な投稿が見つかりませんでした。スプレッドシートにWordPress投稿IDが設定された行があることを確認してください。</div>';
            return html;
        }
        
        let hasMismatches = false;
        
        data.test_results.forEach(test => {
            html += `
                <div style="border:1px solid #ddd;padding:10px;margin:10px 0;border-radius:5px;">
                    <strong>📝 投稿: ${this.escapeHtml(test.post_title)} (ID: ${test.post_id}, 行: ${test.sheet_row})</strong><br><br>
                    <table style="width:100%;border-collapse:collapse;font-size:12px;">
                        <tr style="background:#f2f2f2;">
                            <th style="border:1px solid #ddd;padding:5px;">フィールド</th>
                            <th style="border:1px solid #ddd;padding:5px;">列</th>
                            <th style="border:1px solid #ddd;padding:5px;">スプレッドシート値</th>
                            <th style="border:1px solid #ddd;padding:5px;">WordPress値</th>
                            <th style="border:1px solid #ddd;padding:5px;">同期状況</th>
                        </tr>
            `;
            
            Object.keys(test.fields).forEach(fieldKey => {
                const field = test.fields[fieldKey];
                const statusColor = field.matches ? '#d4edda' : '#f8d7da';
                const statusText = field.matches ? '✅ 一致' : '❌ 不一致';
                
                if (!field.matches) {
                    hasMismatches = true;
                }
                
                html += `
                    <tr style="background:${statusColor};">
                        <td style="border:1px solid #ddd;padding:5px;">${this.escapeHtml(fieldKey)}</td>
                        <td style="border:1px solid #ddd;padding:5px;">${this.escapeHtml(field.column || '')}</td>
                        <td style="border:1px solid #ddd;padding:5px;">${this.escapeHtml(field.sheet_value || '(空)')}</td>
                        <td style="border:1px solid #ddd;padding:5px;">${this.escapeHtml(field.wp_value || '(空)')}</td>
                        <td style="border:1px solid #ddd;padding:5px;">${statusText}</td>
                    </tr>
                `;
            });
            
            html += '</table></div>';
        });
        
        if (hasMismatches) {
            html += `
                <div style="background:#f8d7da;color:#721c24;padding:10px;border-radius:3px;margin:10px 0;">
                    <strong>⚠️ 同期の不一致が検出されました</strong><br>
                    上記の表で「❌ 不一致」となっているフィールドは、スプレッドシートとWordPressで値が異なります。<br>
                    「Sheets → WordPress」同期を実行して修正することをお勧めします。
                </div>
            `;
        } else {
            html += `
                <div style="background:#d4edda;color:#155724;padding:10px;border-radius:3px;margin:10px 0;">
                    <strong>✅ すべてのフィールドが正常に同期されています</strong><br>
                    都道府県、カテゴリ、対象市町村のフィールドは正しく同期されています。
                </div>
            `;
        }
        
        return html;
    },

    /**
     * フォーム処理の設定
     */
    setupFormHandling() {
        // 設定フォームの送信処理
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', () => {
                const submitBtn = form.querySelector('input[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.value = '保存中...';
                    
                    // フォーム送信後にボタンを復元
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.value = '設定を保存';
                    }, 3000);
                }
            });
        });
        
        // コピーボタンの処理
        document.querySelectorAll('.gi-copy-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleCopyButton(e));
        });
    },

    /**
     * コピーボタンの処理
     */
    handleCopyButton(event) {
        const btn = event.target;
        const textToCopy = btn.dataset.copy;
        const originalText = btn.textContent;
        
        if (!textToCopy) return;
        
        this.copyToClipboard(textToCopy)
            .then(() => {
                btn.textContent = 'コピー済み';
                btn.classList.add('gi-copied');
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.classList.remove('gi-copied');
                }, 2000);
            })
            .catch(error => {
                console.error('Copy error:', error);
                this.showNotice('error', 'コピーに失敗しました');
            });
    },

    /**
     * ==========================================================================
     * ユーティリティ関数
     * ==========================================================================
     */

    /**
     * AJAX関数
     */
    ajax(data, options = {}) {
        const url = options.url || window.giSheetsAdmin?.ajaxurl || '/wp-admin/admin-ajax.php';
        const timeout = options.timeout || this.config.ajaxTimeout;
        
        const requestData = {
            ...data,
            nonce: data.nonce || window.giSheetsAdmin?.nonce
        };

        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                ...options.headers
            },
            body: new URLSearchParams(requestData).toString(),
            signal: AbortSignal.timeout(timeout)
        }).then(async response => {
            if (!response.ok) {
                // エラーレスポンスのボディも取得
                const errorBody = await response.text().catch(() => 'No response body');
                console.error('HTTP Error Response Body:', errorBody);
                
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        });
    },

    /**
     * HTMLエスケープ
     */
    escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    },

    /**
     * 通知表示
     */
    showNotice(type, message) {
        // 既存の通知を削除
        document.querySelectorAll('.gi-admin-notice').forEach(notice => notice.remove());
        
        // 新しい通知を作成
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const notice = document.createElement('div');
        notice.className = `notice ${noticeClass} is-dismissible gi-admin-notice`;
        notice.innerHTML = `
            <p>${this.escapeHtml(message)}</p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">この通知を閉じる</span>
            </button>
        `;
        
        // 通知を挿入
        const wrap = document.querySelector('.wrap h1');
        if (wrap) {
            wrap.parentNode.insertBefore(notice, wrap.nextSibling);
        } else {
            document.body.insertBefore(notice, document.body.firstChild);
        }
        
        // 自動で消す
        setTimeout(() => {
            notice.style.opacity = '0';
            setTimeout(() => notice.remove(), 300);
        }, this.config.noticeDisplayTime);
        
        // 閉じるボタンの処理
        notice.querySelector('.notice-dismiss').addEventListener('click', () => {
            notice.style.opacity = '0';
            setTimeout(() => notice.remove(), 300);
        });
    },

    /**
     * クリップボードにコピー
     */
    async copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            // モダンブラウザ
            return await navigator.clipboard.writeText(text);
        } else {
            // フォールバック
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (!successful) {
                    throw new Error('Copy command failed');
                }
            } finally {
                document.body.removeChild(textarea);
            }
        }
    },

    /**
     * ==========================================================================
     * ユーティリティ機能
     * ==========================================================================
     */
    setupUtils() {
        // ユーティリティ機能の初期化
        // 現時点では特に初期化する内容はないが、将来の拡張のために残しておく
        console.log('[Grant Insight Admin] Utils setup completed');
    }
};

/**
 * =============================================================================
 * 自動初期化・互換性維持
 * =============================================================================
 */

// jQuery互換性ラッパー（既存コードとの互換性のため）
if (typeof jQuery !== 'undefined') {
    (function($) {
        'use strict';
        
        $(document).ready(function() {
            GrantInsightAdmin.init();
            console.log('✅ Grant Insight Admin (jQuery compatible) initialized');
        });
        
    })(jQuery);
} else {
    // Vanilla JS初期化
    GrantInsightAdmin.init();
}

// グローバルアクセス用
window.GrantInsightAdmin = GrantInsightAdmin;

/**
 * =============================================================================
 * 後方互換性サポート
 * =============================================================================
 */

// 既存の変数名をサポート
if (typeof grantMetaboxes === 'undefined' && typeof window.grantMetaboxes !== 'undefined') {
    window.grantMetaboxes = window.grantMetaboxes;
}

if (typeof giSheetsAdmin === 'undefined' && typeof window.giSheetsAdmin !== 'undefined') {
    window.giSheetsAdmin = window.giSheetsAdmin;
}

/**
 * =============================================================================
 * 重複タイトルエクスポート機能
 * =============================================================================
 */
(function($) {
    'use strict';

    $(function() {
        console.log('[Duplicate Exporter] Script loaded');
        
        // #grant-duplicate-exporter IDがページに存在する場合のみ実行
        const $exporter = $('#grant-duplicate-exporter');
        console.log('[Duplicate Exporter] Exporter element found:', $exporter.length > 0);
        
        if (!$exporter.length) {
            console.log('[Duplicate Exporter] Exporter page not detected, skipping initialization');
            return;
        }

        const $button = $('#export-duplicates-btn');
        const $progress = $('#export-progress');
        const $results = $('#export-results');
        
        console.log('[Duplicate Exporter] Button found:', $button.length > 0);
        console.log('[Duplicate Exporter] Progress found:', $progress.length > 0);
        console.log('[Duplicate Exporter] Results found:', $results.length > 0);

        // wp_localize_scriptで渡された 'duplicateExport' オブジェクトがあるか確認
        if (typeof duplicateExport === 'undefined') {
            console.error('[Duplicate Exporter] duplicateExport object is missing!');
            console.error('[Duplicate Exporter] This means wp_localize_script did not run properly');
            return;
        }
        
        console.log('[Duplicate Exporter] duplicateExport object:', duplicateExport);
        console.log('[Duplicate Exporter] AJAX URL:', duplicateExport.ajax_url);
        console.log('[Duplicate Exporter] Nonce:', duplicateExport.nonce);

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
                    let html = '<p>' + response.data.message + '</p>';
                    if (response.data.sheetUrl) {
                        html += '<a href="' + response.data.sheetUrl + '" target="_blank" class="button button-secondary">スプレッドシートを開く</a>';
                    }
                    $results.html(html).addClass('notice notice-success is-dismissible');
                } else {
                    // 失敗
                    console.error('[Duplicate Exporter] Export failed:', response.data);
                    $results.html('<p>エラー: ' + response.data.message + '</p>').addClass('notice notice-error is-dismissible');
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

})(jQuery);