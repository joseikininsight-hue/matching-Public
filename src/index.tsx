import { Hono } from 'hono';
import { cors } from 'hono/cors';
import { serveStatic } from 'hono/cloudflare-workers';
import { renderer } from './renderer';
import { Env } from './types';

// ルートのインポート
import sessions from './routes/sessions';
import answers from './routes/answers';
import recommendations from './routes/recommendations';
import admin from './routes/admin';
import test from './routes/test';

const app = new Hono<{ Bindings: Env }>();

// CORS設定（APIエンドポイント用）
app.use('/api/*', cors({
  origin: '*',
  allowMethods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
  allowHeaders: ['Content-Type', 'Authorization'],
  maxAge: 86400
}));

// 静的ファイルの配信
app.use('/static/*', serveStatic({ root: './public' }));

// 管理画面
app.get('/admin', async (c) => {
  const html = `<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理画面 - AI補助金マッチング</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Noto Sans JP', sans-serif; background-color: #f5f5f5; }
        .brutalist-border { border: 8px solid #000000; }
        .brutalist-shadow { box-shadow: 12px 12px 0px 0px rgba(0, 0, 0, 1); }
        .btn-primary { background-color: #00FF00; color: #000000; font-weight: bold; border: 4px solid #000000; padding: 12px 24px; cursor: pointer; transition: all 0.2s; }
        .btn-primary:hover { background-color: #FFFF00; transform: translate(2px, 2px); box-shadow: 8px 8px 0px 0px rgba(0, 0, 0, 1); }
        .btn-secondary { background-color: #FFFFFF; color: #000000; font-weight: bold; border: 4px solid #000000; padding: 12px 24px; cursor: pointer; transition: all 0.2s; }
        .btn-secondary:hover { background-color: #f0f0f0; }
        .file-upload-area { border: 4px dashed #000000; background-color: #ffffff; padding: 40px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .file-upload-area:hover { background-color: #00FF00; }
        .file-upload-area.dragover { background-color: #FFFF00; border-style: solid; }
        .progress-bar { height: 30px; background-color: #e0e0e0; border: 4px solid #000000; position: relative; overflow: hidden; }
        .progress-fill { height: 100%; background-color: #00FF00; transition: width 0.3s; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .result-box { background-color: #ffffff; border: 4px solid #000000; padding: 20px; margin-top: 20px; }
        .error-box { background-color: #ffebee; border: 4px solid #f44336; padding: 20px; margin-top: 20px; }
        .success-box { background-color: #e8f5e9; border: 4px solid #4caf50; padding: 20px; margin-top: 20px; }
    </style>
</head>
<body class="p-8">
    <div class="max-w-4xl mx-auto">
        <div class="brutalist-border brutalist-shadow bg-white p-8 mb-8">
            <h1 class="text-4xl font-bold mb-4">📊 管理画面</h1>
            <p class="text-gray-700">補助金データのインポート・管理</p>
            <p class="text-sm text-gray-500 mt-2"><a href="/" class="underline">← トップページに戻る</a></p>
        </div>

        <div class="brutalist-border bg-white p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">🔑 APIキー設定</h2>
            <div class="mb-4">
                <label class="block font-bold mb-2">JWT Secret (管理者認証用)</label>
                <input type="text" id="jwtSecret" class="w-full border-4 border-black p-3 font-mono" value="your_jwt_secret_key_here">
                <p class="text-sm text-gray-600 mt-2">
                    ※ .dev.vars ファイルのJWT_SECRET（デフォルト: <code class="bg-gray-200 px-1">your_jwt_secret_key_here</code>）<br>
                    現在の値: <span id="currentJwtValue" class="font-mono bg-yellow-100 px-1">読み込み中...</span>
                </p>
                <div class="flex gap-2 mt-2">
                    <button class="btn-secondary text-xs" onclick="showCurrentValue()">🔄 現在値を表示</button>
                    <button class="btn-secondary text-xs" onclick="testAuth()">🔍 認証テスト</button>
                </div>
                <div id="authTestResult" class="hidden mt-2 p-2 border-2 border-black text-sm"></div>
            </div>
        </div>

        <div class="brutalist-border bg-white p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">📁 ファイルアップロード</h2>
            <div class="mb-6">
                <label class="block font-bold mb-2">ファイル形式</label>
                <select id="fileType" class="border-4 border-black p-3 font-bold">
                    <option value="csv">CSV (.csv)</option>
                    <option value="excel">Excel (.xlsx, .xls)</option>
                </select>
            </div>
            <div id="dropZone" class="file-upload-area mb-4">
                <input type="file" id="fileInput" accept=".csv,.xlsx,.xls" style="display: none;">
                <div>
                    <p class="text-3xl mb-2">📤</p>
                    <p class="font-bold text-lg mb-2">ファイルをドラッグ＆ドロップ</p>
                    <p class="text-gray-600 mb-4">または</p>
                    <button class="btn-secondary" onclick="document.getElementById('fileInput').click()">ファイルを選択</button>
                    <p class="text-sm text-gray-600 mt-4">対応形式: CSV, Excel (.xlsx, .xls) / 最大サイズ: 10MB</p>
                </div>
            </div>
            <div id="fileInfo" class="hidden mb-4 p-4 bg-gray-100 border-2 border-black">
                <p><strong>選択されたファイル:</strong> <span id="fileName"></span></p>
                <p><strong>ファイルサイズ:</strong> <span id="fileSize"></span></p>
            </div>
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" id="skipDuplicates" class="mr-2 w-5 h-5">
                    <span class="font-bold">既存データをスキップ（重複回避）</span>
                </label>
            </div>
            <button id="uploadBtn" class="btn-primary w-full" disabled>アップロード開始</button>
        </div>

        <div id="progressSection" class="hidden mb-6">
            <div class="brutalist-border bg-white p-6">
                <h3 class="text-xl font-bold mb-4">⏳ 処理中...</h3>
                <div class="progress-bar">
                    <div id="progressFill" class="progress-fill" style="width: 0%">
                        <span id="progressText">0%</span>
                    </div>
                </div>
                <p id="progressMessage" class="text-center mt-4 font-bold"></p>
            </div>
        </div>

        <div id="resultSection" class="hidden"></div>

        <div class="brutalist-border bg-white p-6">
            <h2 class="text-2xl font-bold mb-4">📄 CSVテンプレート</h2>
            <p class="mb-4">以下のカラム名を使用してCSV/Excelファイルを作成してください：</p>
            <div class="bg-gray-100 p-4 border-2 border-black font-mono text-sm overflow-x-auto">
                <p class="mb-2"><strong>カラム順序（重要）:</strong></p>
                <ol class="list-decimal ml-6 space-y-1">
                    <li>ID - 記事ID（必須）</li>
                    <li>Title - タイトル（必須）</li>
                    <li>Content - 内容・詳細</li>
                    <li>Excerpt - 要約・抜粋</li>
                    <li>Permalink - パーマリンク（完全URL）</li>
                    <li>admin_notes - 管理メモ</li>
                    <li>deadline_date - 申請期限（YYYY-MM-DD形式）</li>
                    <li>max_amount_numeric - 補助上限額（数値のみ）</li>
                    <li>助成金カテゴリー - カンマ区切り</li>
                    <li>対象都道府県 - 都道府県名</li>
                    <li>助成金タグ - カンマ区切り</li>
                    <li>対象市町村 - 市区町村名</li>
                </ol>
            </div>
            <button class="btn-secondary mt-4" onclick="downloadSampleCSV()">サンプルCSVをダウンロード</button>
        </div>

        <!-- 補助金データ一覧・管理セクション -->
        <div class="brutalist-border bg-white p-6 mt-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold">📋 補助金データ一覧</h2>
                <button class="btn-secondary" onclick="loadGrantsList()">🔄 再読み込み</button>
            </div>
            
            <!-- 検索・フィルター -->
            <div class="mb-4 p-4 bg-gray-50 border-2 border-black">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block font-bold mb-2">キーワード検索</label>
                        <input type="text" id="searchKeyword" class="w-full border-2 border-black p-2" placeholder="タイトル、組織名で検索...">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">都道府県</label>
                        <select id="filterPrefecture" class="w-full border-2 border-black p-2">
                            <option value="">すべて</option>
                            <option value="全国">全国</option>
                            <option value="北海道">北海道</option>
                            <option value="東京都">東京都</option>
                            <option value="大阪府">大阪府</option>
                            <option value="愛知県">愛知県</option>
                            <option value="神奈川県">神奈川県</option>
                            <option value="埼玉県">埼玉県</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold mb-2">表示件数</label>
                        <select id="limitCount" class="w-full border-2 border-black p-2">
                            <option value="10">10件</option>
                            <option value="20" selected>20件</option>
                            <option value="50">50件</option>
                            <option value="100">100件</option>
                        </select>
                    </div>
                </div>
                <button class="btn-primary mt-4" onclick="searchGrants()">🔍 検索</button>
            </div>

            <!-- データ統計 -->
            <div id="grantsStats" class="mb-4 p-4 bg-yellow-50 border-2 border-black">
                <p class="font-bold">📊 データ統計: <span id="totalCount">読み込み中...</span></p>
            </div>

            <!-- データ一覧テーブル -->
            <div id="grantsListContainer" class="overflow-x-auto">
                <p class="text-center py-8 text-gray-500">「再読み込み」または「検索」ボタンを押してください</p>
            </div>

            <!-- 一括操作 -->
            <div class="mt-4 p-4 bg-red-50 border-2 border-red-600">
                <h3 class="font-bold text-red-600 mb-2">⚠️ 危険な操作</h3>
                <button class="btn-secondary bg-red-600 text-white border-red-800" onclick="confirmDeleteAll()">🗑️ 全データ削除</button>
                <p class="text-sm text-gray-600 mt-2">※ この操作は取り消せません</p>
            </div>
        </div>
    </div>

    <script>
        const API_BASE_URL = window.location.origin + '/api/admin';
        let selectedFile = null;
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');

        // 現在値を表示
        function showCurrentValue() {
            const value = document.getElementById('jwtSecret').value;
            document.getElementById('currentJwtValue').textContent = value;
            console.log('JWT Secret現在値:', value);
            console.log('JWT Secret長さ:', value.length);
            console.log('JWT Secret文字コード:', Array.from(value).map(c => c.charCodeAt(0)));
        }
        
        // ページ読み込み時に現在値を表示
        window.addEventListener('load', () => {
            showCurrentValue();
        });

        // 認証テスト機能
        async function testAuth() {
            const jwtSecret = document.getElementById('jwtSecret').value.trim();
            const resultDiv = document.getElementById('authTestResult');
            
            console.log('テスト開始 - JWT Secret:', jwtSecret);
            console.log('Authorization Header:', 'Bearer ' + jwtSecret);
            
            if (!jwtSecret) {
                resultDiv.className = 'mt-2 p-2 border-2 border-red-600 text-sm bg-red-50';
                resultDiv.textContent = '❌ JWT Secretを入力してください';
                resultDiv.classList.remove('hidden');
                return;
            }
            
            try {
                const url = API_BASE_URL + '/config/gemini';
                console.log('リクエストURL:', url);
                
                const response = await fetch(url, {
                    headers: { 'Authorization': 'Bearer ' + jwtSecret }
                });
                
                console.log('レスポンスステータス:', response.status);
                console.log('レスポンスOK:', response.ok);
                
                const responseData = await response.clone().json();
                console.log('レスポンスデータ:', responseData);
                
                if (response.ok) {
                    resultDiv.className = 'mt-2 p-2 border-2 border-green-600 text-sm bg-green-50';
                    resultDiv.textContent = '✅ 認証成功！このJWT Secretは有効です。入力値: "' + jwtSecret + '"';
                } else if (response.status === 401) {
                    resultDiv.className = 'mt-2 p-2 border-2 border-red-600 text-sm bg-red-50';
                    resultDiv.innerHTML = '❌ 認証失敗：JWT Secretが正しくありません。<br>' +
                        '入力値: "' + jwtSecret + '"<br>' +
                        '期待値: .dev.vars ファイルのJWT_SECRET<br>' +
                        'エラー: ' + (responseData.error || '不明');
                } else {
                    resultDiv.className = 'mt-2 p-2 border-2 border-yellow-600 text-sm bg-yellow-50';
                    resultDiv.textContent = '⚠️ エラー: ' + response.status + ' ' + response.statusText;
                }
            } catch (error) {
                console.error('テストエラー:', error);
                resultDiv.className = 'mt-2 p-2 border-2 border-red-600 text-sm bg-red-50';
                resultDiv.textContent = '❌ エラー: ' + error.message;
            }
            
            resultDiv.classList.remove('hidden');
        }

        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) handleFileSelect(e.dataTransfer.files[0]);
        });
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) handleFileSelect(e.target.files[0]);
        });

        function handleFileSelect(file) {
            selectedFile = file;
            document.getElementById('fileInfo').classList.remove('hidden');
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = (file.size / 1024).toFixed(2) + ' KB';
            document.getElementById('uploadBtn').disabled = false;
        }

        document.getElementById('uploadBtn').addEventListener('click', async () => {
            if (!selectedFile) { alert('ファイルを選択してください'); return; }
            const jwtSecret = document.getElementById('jwtSecret').value.trim();
            if (!jwtSecret) { alert('JWT Secretを入力してください'); return; }

            const fileType = document.getElementById('fileType').value;
            const skipDuplicates = document.getElementById('skipDuplicates').checked;

            document.getElementById('progressSection').classList.remove('hidden');
            document.getElementById('resultSection').classList.add('hidden');
            document.getElementById('uploadBtn').disabled = true;

            try {
                const formData = new FormData();
                formData.append('file', selectedFile);
                formData.append('skip_duplicates', skipDuplicates);
                formData.append('batch_size', '100');

                const endpoint = fileType === 'csv' ? API_BASE_URL + '/import/grants-csv' : API_BASE_URL + '/import/grants-excel';
                updateProgress(50, 'アップロード中...');

                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + jwtSecret },
                    body: formData
                });

                updateProgress(100, '処理完了');
                const result = await response.json();
                
                // 認証エラーの場合は特別なメッセージを表示
                if (!response.ok && response.status === 401) {
                    showResult({ 
                        error: '認証エラー: JWT Secretが正しくありません。.dev.vars ファイルの JWT_SECRET を確認してください。',
                        details: result.error || 'Authorization: Bearer your_jwt_secret_key_here'
                    }, 'error');
                    return;
                }
                
                result.success ? showResult(result, 'success') : showResult(result, 'error');

            } catch (error) {
                showResult({ error: 'エラーが発生しました: ' + error.message }, 'error');
            } finally {
                document.getElementById('progressSection').classList.add('hidden');
                document.getElementById('uploadBtn').disabled = false;
            }
        });

        function updateProgress(percent, message) {
            document.getElementById('progressFill').style.width = percent + '%';
            document.getElementById('progressText').textContent = percent + '%';
            document.getElementById('progressMessage').textContent = message;
        }

        function showResult(result, type) {
            const resultSection = document.getElementById('resultSection');
            resultSection.classList.remove('hidden');
            if (type === 'success') {
                resultSection.innerHTML = '<div class="success-box"><h3 class="text-2xl font-bold mb-4">✅ インポート成功</h3><div class="grid grid-cols-2 gap-4">' +
                    '<div><p class="font-bold">ファイル名:</p><p>' + result.data.summary.filename + '</p></div>' +
                    '<div><p class="font-bold">総件数:</p><p>' + result.data.stats.total + '件</p></div>' +
                    '<div><p class="font-bold">新規追加:</p><p class="text-green-600 font-bold">' + result.data.stats.inserted + '件</p></div>' +
                    '<div><p class="font-bold">更新:</p><p class="text-blue-600 font-bold">' + result.data.stats.updated + '件</p></div>' +
                    '<div><p class="font-bold">成功率:</p><p>' + result.data.summary.success_rate + '</p></div>' +
                    '<div><p class="font-bold">処理時間:</p><p>' + result.data.summary.processing_time + '</p></div>' +
                    '</div></div>';
            } else {
                resultSection.innerHTML = '<div class="error-box"><h3 class="text-2xl font-bold mb-4">❌ エラー</h3><p class="font-bold">' + (result.error || 'インポートに失敗しました') + '</p></div>';
            }
        }

        function downloadSampleCSV() {
            const csv = 'ID,Title,Content,Excerpt,Permalink,admin_notes,deadline_date,max_amount_numeric,助成金カテゴリー,対象都道府県,助成金タグ,対象市町村\\n' +
                '1001,DX推進補助金,デジタル化を支援します。IoT・AI・クラウド等の導入費用を補助,中小企業のDX推進を支援,https://joseikin-insight.com/grants/DX推進補助金/,重要案件,2025-12-31,5000000,"dx_digital,it_software",全国,"DX,デジタル化",\\n' +
                '1002,設備投資促進補助金,製造設備の導入を支援します。最新の製造機械導入費用を補助,製造業の設備投資を促進,https://joseikin-insight.com/grants/設備投資促進補助金/,,2025-11-30,10000000,"equipment,manufacturing",東京都,"設備投資,製造業",千代田区\\n' +
                '1003,創業支援補助金,起業・創業を支援します。事業立ち上げに必要な経費を補助,スタートアップ企業向け創業支援,https://joseikin-insight.com/grants/創業支援補助金/,優先度高,2025-10-31,3000000,"startup,innovation",大阪府,"起業,創業",大阪市';
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'sample_grants.csv';
            link.click();
        }

        // 補助金データ一覧を読み込み
        async function loadGrantsList() {
            const jwtSecret = document.getElementById('jwtSecret').value.trim();
            if (!jwtSecret) {
                alert('JWT Secretを入力してください');
                return;
            }

            const limit = document.getElementById('limitCount').value;
            const url = API_BASE_URL + '/grants?limit=' + limit;

            try {
                const response = await fetch(url, {
                    headers: { 'Authorization': 'Bearer ' + jwtSecret }
                });
                const result = await response.json();

                if (result.success) {
                    displayGrantsList(result.data);
                    const count = (result.data.grants || result.data.data || []).length;
                    document.getElementById('totalCount').textContent = count + '件表示 (総数: ' + result.data.total + ')';
                } else {
                    alert('エラー: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('データの読み込みに失敗しました');
            }
        }

        // 検索実行
        async function searchGrants() {
            const jwtSecret = document.getElementById('jwtSecret').value.trim();
            if (!jwtSecret) {
                alert('JWT Secretを入力してください');
                return;
            }

            const keyword = document.getElementById('searchKeyword').value;
            const prefecture = document.getElementById('filterPrefecture').value;
            const limit = document.getElementById('limitCount').value;

            let url = API_BASE_URL + '/grants?limit=' + limit;
            if (keyword) url += '&keyword=' + encodeURIComponent(keyword);
            if (prefecture) url += '&prefecture=' + encodeURIComponent(prefecture);

            try {
                const response = await fetch(url, {
                    headers: { 'Authorization': 'Bearer ' + jwtSecret }
                });
                const result = await response.json();

                if (result.success) {
                    displayGrantsList(result.data);
                    const count = (result.data.grants || result.data.data || []).length;
                    document.getElementById('totalCount').textContent = count + '件表示 (総数: ' + result.data.total + ')';
                } else {
                    alert('エラー: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('検索に失敗しました');
            }
        }

        // データ一覧を表示
        function displayGrantsList(grantsData) {
            const container = document.getElementById('grantsListContainer');
            
            // APIレスポンスから実際のgrants配列を取得
            // data.grants または data.data のどちらかの形式に対応
            const grants = grantsData.grants || grantsData.data || grantsData;
            
            if (!grants || grants.length === 0) {
                container.innerHTML = '<p class="text-center py-8 text-gray-500">データがありません</p>';
                return;
            }

            let html = '<table class="w-full border-4 border-black"><thead class="bg-yellow-200"><tr class="border-2 border-black">';
            html += '<th class="border-2 border-black p-2">ID</th>';
            html += '<th class="border-2 border-black p-2">タイトル</th>';
            html += '<th class="border-2 border-black p-2">組織</th>';
            html += '<th class="border-2 border-black p-2">都道府県</th>';
            html += '<th class="border-2 border-black p-2">上限額</th>';
            html += '<th class="border-2 border-black p-2">操作</th>';
            html += '</tr></thead><tbody>';

            grants.forEach(grant => {
                html += '<tr class="border-2 border-black hover:bg-gray-50">';
                html += '<td class="border-2 border-black p-2 text-center">' + grant.wordpress_id + '</td>';
                html += '<td class="border-2 border-black p-2"><strong>' + (grant.title || '無題') + '</strong></td>';
                html += '<td class="border-2 border-black p-2">' + (grant.organization || '-') + '</td>';
                html += '<td class="border-2 border-black p-2">' + (grant.prefecture_name || '-') + '</td>';
                html += '<td class="border-2 border-black p-2 text-right">' + (grant.max_amount_numeric ? grant.max_amount_numeric.toLocaleString() + '円' : '-') + '</td>';
                html += '<td class="border-2 border-black p-2 text-center">';
                html += '<button class="bg-red-600 text-white px-3 py-1 border-2 border-black font-bold hover:bg-red-700" onclick="deleteGrant(' + grant.wordpress_id + ')">🗑️ 削除</button>';
                html += '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        }

        // 個別削除
        async function deleteGrant(wordpress_id) {
            if (!confirm('ID ' + wordpress_id + ' のデータを削除しますか？\\nこの操作は取り消せません。')) {
                return;
            }

            const jwtSecret = document.getElementById('jwtSecret').value.trim();
            if (!jwtSecret) {
                alert('JWT Secretを入力してください');
                return;
            }

            try {
                const response = await fetch(API_BASE_URL + '/grants/' + wordpress_id, {
                    method: 'DELETE',
                    headers: { 'Authorization': 'Bearer ' + jwtSecret }
                });
                const result = await response.json();

                if (result.success) {
                    alert('✅ 削除しました');
                    loadGrantsList(); // 一覧を再読み込み
                } else {
                    alert('エラー: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('削除に失敗しました');
            }
        }

        // 全データ削除
        async function confirmDeleteAll() {
            if (!confirm('⚠️ 警告: すべてのデータを削除します\\n本当によろしいですか？')) {
                return;
            }
            if (!confirm('⚠️⚠️ 最終確認\\nこの操作は取り消せません。本当に削除しますか？')) {
                return;
            }

            const jwtSecret = document.getElementById('jwtSecret').value.trim();
            if (!jwtSecret) {
                alert('JWT Secretを入力してください');
                return;
            }

            try {
                const response = await fetch(API_BASE_URL + '/grants/bulk-delete', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + jwtSecret,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        all: true,
                        confirm_token: 'DELETE_ALL_GRANTS_CONFIRMED'
                    })
                });
                const result = await response.json();

                if (result.success) {
                    alert('✅ ' + result.data.deleted_count + '件のデータを削除しました');
                    loadGrantsList(); // 一覧を再読み込み
                } else {
                    alert('エラー: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('削除に失敗しました');
            }
        }
    </script>
</body>
</html>`;
  return c.html(html);
});

// APIルート
app.route('/api/sessions', sessions);
app.route('/api/sessions', answers); // /api/sessions/:sessionId/answers
app.route('/api/recommendations', recommendations);
app.route('/api/admin', admin);
app.route('/api/test', test); // テスト用エンドポイント

// ヘルスチェック
app.get('/api/health', (c) => {
  return c.json({
    status: 'ok',
    timestamp: new Date().toISOString(),
    version: '1.0.0'
  });
});

// フロントエンドのレンダリング
app.use(renderer);

app.get('/', (c) => {
  return c.render(
    <div class="min-h-screen bg-white">
      {/* ヘッダー */}
      <header class="border-b-4 border-black p-6">
        <div class="max-w-4xl mx-auto">
          <h1 class="text-3xl font-bold">💡 AI補助金マッチング</h1>
          <p class="text-gray-600 mt-2">あなたに最適な補助金を見つけます</p>
        </div>
      </header>
      
      {/* メインコンテンツ */}
      <main class="max-w-4xl mx-auto p-6">
        <div id="app"></div>
      </main>
      
      {/* フッター */}
      <footer class="border-t-4 border-black p-6 mt-12">
        <div class="max-w-4xl mx-auto text-center text-gray-600">
          <p>© 2025 AI補助金マッチング</p>
        </div>
      </footer>
      
      {/* アプリケーションスクリプト */}
      <script src="/static/app.js"></script>
    </div>
  );
});

// 管理者ダッシュボード
app.get('/admin', (c) => {
  return c.render(
    <div>
      <h1>🔐 管理者ダッシュボード</h1>
      <div id="admin-app"></div>
    </div>
  );
});

// 404ハンドラー
app.notFound((c) => {
  return c.json({ error: 'Not Found' }, 404);
});

// エラーハンドラー
app.onError((err, c) => {
  console.error('Application error:', err);
  return c.json({
    error: 'Internal Server Error',
    message: err.message,
    details: process.env.NODE_ENV === 'development' ? err.stack : undefined
  }, 500);
});

export default app;
