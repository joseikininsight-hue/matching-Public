# WordPress ACFフィールド公開設定ガイド

## 🔍 現在の問題

D1データベースには **6,001件** のデータが同期されていますが、すべてのACFフィールドが `null` です：

```json
{
  "organization": null,
  "max_amount_display": null,
  "deadline_display": null,
  "official_url": null
}
```

**原因**: WordPressのACFフィールドがREST APIで公開されていません。

## ✅ 解決方法（所要時間: 5分）

### 方法1: functions.phpに1行追加（最も簡単）

#### ステップ1: WordPress管理画面にログイン

1. https://joseikin-insight.com/wp-admin にアクセス
2. 管理者アカウントでログイン

#### ステップ2: テーマエディターを開く

1. 外観 → テーマファイルエディター
2. 右側のファイル一覧から「functions.php」を選択

#### ステップ3: コードを追加

`functions.php` の**一番下**に以下のコードを追加：

```php
// ACFフィールドをREST APIで公開
add_filter('acf/settings/rest_api_enabled', '__return_true');
```

#### ステップ4: 保存

「ファイルを更新」ボタンをクリック

---

### 方法2: より詳細な設定（推奨）

`wordpress_acf_rest_api_fix.php` ファイルの内容を `functions.php` に追加してください。

特に以下のセクションをコピー：

```php
// ========================================
// 推奨: 方法1と方法3を組み合わせる（最も確実）
// ========================================

// ステップ1: ACF REST APIを全体的に有効化
add_filter('acf/settings/rest_api_enabled', '__return_true');

// ステップ2: grantカスタム投稿タイプで確実にACFフィールドを返す
add_action('rest_api_init', function() {
    register_rest_field('grant', 'acf', array(
        'get_callback' => function($post) {
            $fields = get_fields($post['id']);
            
            // 空の場合は空の配列ではなくオブジェクトを返す
            if (empty($fields) || !is_array($fields)) {
                return new stdClass();
            }
            
            return $fields;
        },
        'update_callback' => null,
        'schema' => array(
            'description' => 'Advanced Custom Fields',
            'type' => 'object',
        ),
    ));
});
```

---

## 🧪 動作確認

### テスト1: ブラウザで確認

以下のURLにアクセス：

```
https://joseikin-insight.com/wp-json/wp/v2/grants?per_page=1
```

**期待される結果**:

```json
{
  "id": 130564,
  "title": {...},
  "acf": {
    "organization": "観音寺市",
    "max_amount": "548,000円",
    "deadline": "予算がなくなり次第終了",
    "official_url": "https://..."
  }
}
```

**問題がある場合**:

```json
{
  "acf": []  ← これは問題！
}
```

### テスト2: コマンドラインで確認

```bash
curl "https://joseikin-insight.com/wp-json/wp/v2/grants?per_page=1" | grep -o '"acf":{[^}]*}'
```

**正常な場合**: ACFフィールドが表示される
**問題がある場合**: `"acf":[]` または `"acf":{}` が表示される

---

## 🔄 データ再同期手順

WordPress設定完了後、D1データベースにデータを再同期します。

### 方法1: 自動同期スクリプト（推奨）

```bash
cd /home/user/webapp
bash resync_with_acf_fields.sh
```

約30分で全7,949件を同期します。

### 方法2: 手動同期（小規模テスト）

```bash
# 最初の100件だけ同期（テスト用）
curl "https://matching-public.pages.dev/api/wordpress/sync?page=1&per_page=100&max_pages=1"
```

### 方法3: ブラウザから同期

1. https://matching-public.pages.dev/api/wordpress/sync?page=1&per_page=100&max_pages=10 にアクセス
2. レスポンスを確認
3. 必要に応じて `max_pages` の値を変更して再実行

---

## 📊 同期完了後の確認

### 1. D1データベースの確認

```bash
curl "https://matching-public.pages.dev/api/grants?limit=3" | python3 -m json.tool
```

**期待される結果**:

```json
{
  "organization": "観音寺市",
  "max_amount_display": "548,000円",
  "deadline_display": "予算がなくなり次第終了",
  "official_url": "https://..."
}
```

### 2. マッチングアプリの確認

1. https://matching-public.pages.dev にアクセス
2. 質問に回答してマッチング実行
3. 結果画面で以下を確認：
   - ✅ Amount: **「548,000円」などの実際の値**
   - ✅ Deadline: **「予算がなくなり次第終了」などの実際の値**
   - ✅ Organization: **「観音寺市」などの実際の値**
   - ❌ 「記載なし」が減っている

---

## 🚨 トラブルシューティング

### 問題1: functions.phpの編集後にサイトが表示されない

**原因**: PHPの構文エラー

**解決策**:
1. FTPでサーバーにアクセス
2. `/wp-content/themes/[テーマ名]/functions.php` を開く
3. 追加したコードを削除
4. サイトが復旧することを確認
5. コードを再度確認して追加

### 問題2: ACFフィールドがまだ空

**確認項目**:
1. ACFプラグインが有効化されているか
2. 投稿にACFフィールドの値が入力されているか
3. WordPressのキャッシュをクリア
4. ブラウザのキャッシュをクリア

**デバッグ用URL**:
```
https://joseikin-insight.com/?test_acf=1&post_id=130564
```

管理者でログイン後、このURLにアクセスするとACFフィールドの値が表示されます。

### 問題3: 一部のフィールドだけ空

**原因**: WordPress投稿で値が入力されていない

**解決策**:
1. WordPress管理画面 → 投稿 → 助成金
2. 該当する投稿を編集
3. ACFフィールドに値を入力
4. 更新
5. 再度データ同期を実行

---

## 📝 チェックリスト

- [ ] WordPress管理画面にログイン
- [ ] functions.phpにコード追加
- [ ] ファイルを更新
- [ ] テストURL（`/wp-json/wp/v2/grants?per_page=1`）で確認
- [ ] ACFフィールドが表示されることを確認
- [ ] データ再同期スクリプトを実行
- [ ] D1データベースのデータを確認
- [ ] マッチングアプリで結果を確認
- [ ] 「記載なし」が減っていることを確認

---

## 🎯 期待される最終結果

### 修正前:
```
Amount: 記載なし
Deadline: 記載なし
Organization: 記載なし
```

### 修正後:
```
Amount: 548,000円
Deadline: 予算がなくなり次第終了
Organization: 観音寺市
```

---

## 📞 サポート

問題が解決しない場合は、以下の情報を確認してください：

1. WordPressのバージョン
2. ACFプラグインのバージョン
3. エラーメッセージの全文
4. テストURLのレスポンス全体

---

**最終更新**: 2025-11-07
**対応バージョン**: WordPress 6.x, ACF 6.x
