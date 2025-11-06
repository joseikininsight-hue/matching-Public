# WordPress → Cloudflare D1 連携セットアップガイド

既存のWordPressサイト（助成金情報）とCloudflare Workers + D1データベースを連携させる完全ガイドです。

---

## 📋 前提条件

### WordPress側
- ✅ **カスタム投稿タイプ 'grant'** が既に登録済み（`inc/theme-foundation.php`）
- ✅ **ACFフィールド** が既に定義済み（`inc/acf-fields.php`）
- ✅ **REST API** が有効化済み
- ✅ **タクソノミー** (prefecture, municipality) が登録済み

### Cloudflare Workers側
- ✅ **WordPress連携ルート** `/api/wordpress/*` が実装済み
- ✅ **データベースマイグレーション** 0003が適用済み
- ✅ **D1データベース** にwp_post_id等のカラムが追加済み

---

## 🚀 セットアップ手順

### ステップ1: WordPress REST API トークンの取得

#### 方法A: Application Passwords（推奨・簡単）

1. WordPress管理画面にログイン
2. **ユーザー** → **プロフィール** を開く
3. 下にスクロールして「**アプリケーションパスワード**」セクションを探す
4. **新しいアプリケーションパスワード名**: `Cloudflare Workers API` と入力
5. **新しいアプリケーションパスワードを追加** をクリック
6. 生成されたパスワード（例: `xxxx xxxx xxxx xxxx xxxx xxxx`）をコピー
7. **スペースを削除**して保存: `xxxxxxxxxxxxxxxxxxxxxxxx`

> **注意**: このパスワードは一度しか表示されません。必ず安全な場所に保存してください。

#### 方法B: JWT認証プラグイン（高度）

より高度な認証が必要な場合は、JWT Authenticationプラグインを使用できます。

```bash
# プラグインをインストール
wp plugin install jwt-authentication-for-wp-rest-api --activate

# シークレットキーを wp-config.php に追加
define('JWT_AUTH_SECRET_KEY', 'your-secret-key-here');
```

### ステップ2: Cloudflare Workers環境変数の設定

#### ローカル開発環境（.dev.vars）

既に作成済みの `.dev.vars` ファイルを編集：

```bash
# WordPress サイトのURL（末尾のスラッシュなし）
WORDPRESS_SITE_URL=https://your-actual-wordpress-site.com

# Application Passwordsで生成したトークン
WORDPRESS_API_TOKEN=your_generated_token_without_spaces

# Webhook認証用のランダム文字列（32文字以上推奨）
WORDPRESS_WEBHOOK_SECRET=your_secure_random_string_32chars

# 既存の設定
JWT_SECRET=your_jwt_secret_key_here
GEMINI_API_KEY=your_gemini_api_key_here
```

#### 本番環境（Cloudflare Dashboard）

```bash
# Wranglerコマンドで設定
cd /home/user/webapp

wrangler secret put WORDPRESS_SITE_URL
# プロンプトでURLを入力: https://your-wordpress-site.com

wrangler secret put WORDPRESS_API_TOKEN
# プロンプトでトークンを入力

wrangler secret put WORDPRESS_WEBHOOK_SECRET
# プロンプトでシークレットを入力
```

または、Cloudflare Dashboard から：
1. Cloudflare Dashboard → **Workers & Pages**
2. 対象プロジェクトを選択
3. **Settings** → **Variables**
4. **Add Variable** をクリックして以下を追加：
   - `WORDPRESS_SITE_URL`
   - `WORDPRESS_API_TOKEN`
   - `WORDPRESS_WEBHOOK_SECRET`
5. **Encrypt** をチェック（APIトークンとシークレットは必須）

### ステップ3: REST APIの動作確認

WordPressのREST APIが正常に動作しているか確認：

```bash
# 助成金一覧を取得（認証なし・公開データ）
curl https://your-wordpress-site.com/wp-json/wp/v2/grant

# 認証付きで取得（Application Passwords）
curl -u "username:your_app_password" \
  https://your-wordpress-site.com/wp-json/wp/v2/grant?_embed=true

# 特定の助成金を取得（IDを123と仮定）
curl https://your-wordpress-site.com/wp-json/wp/v2/grant/123?_embed=true
```

**期待される結果**:
```json
[
  {
    "id": 123,
    "title": {
      "rendered": "DX推進補助金"
    },
    "content": {
      "rendered": "<p>説明文...</p>"
    },
    "acf": {
      "organization": "経済産業省",
      "max_amount": "300万円",
      "max_amount_numeric": 3000000,
      "deadline": "2025年3月31日",
      "deadline_date": "2025-03-31",
      "official_url": "https://example.com",
      "application_status": "open",
      "grant_target": "<p>中小企業...</p>",
      "eligible_expenses": "<p>設備費、人件費...</p>"
    },
    "_embedded": {
      "wp:term": [
        [
          {
            "id": 1,
            "name": "東京都",
            "slug": "tokyo",
            "taxonomy": "prefecture"
          }
        ]
      ]
    }
  }
]
```

### ステップ4: 初回同期の実行

#### ローカル環境でテスト

```bash
# サーバーが起動していることを確認
pm2 status

# 同期を実行
curl -s http://localhost:3000/api/wordpress/sync | jq .
```

#### 本番環境で実行

```bash
# 本番URLに変更
curl -s https://your-cloudflare-workers-domain.workers.dev/api/wordpress/sync | jq .
```

**期待される結果**:
```json
{
  "success": true,
  "message": "WordPress sync completed: 25 synced, 0 errors",
  "synced_count": 25,
  "error_count": 0,
  "total": 25
}
```

#### 同期状態の確認

```bash
curl -s http://localhost:3000/api/wordpress/sync-status | jq .
```

**期待される結果**:
```json
{
  "success": true,
  "data": {
    "total_grants": 25,
    "wp_synced_grants": 25,
    "last_sync": "2025-11-06 09:30:15"
  }
}
```

---

## 🔄 Webhookによる自動同期（オプション）

WordPressで投稿が更新されたときに自動的にCloudflare D1に同期するようにWebhookを設定します。

### ステップ5: WordPressプラグインのインストール

#### 推奨プラグイン: WP Webhooks

```bash
# WP-CLIでインストール（SSHアクセスがある場合）
wp plugin install wp-webhooks --activate

# または、WordPress管理画面から
# プラグイン → 新規追加 → "WP Webhooks" を検索 → インストール → 有効化
```

### ステップ6: Webhookの設定

1. WordPress管理画面で **WP Webhooks** → **Send Data** を開く
2. **Add Webhook Action** をクリック
3. 以下のように設定：

   **基本設定**:
   - **Webhook Name**: `Cloudflare Workers Sync`
   - **Webhook URL**: `https://your-workers-domain.workers.dev/api/wordpress/webhook`
   - **Trigger**: `Post created` と `Post updated` を選択
   - **Post Type**: `grant` を選択

   **認証設定**:
   - **Request Method**: `POST`
   - **Custom Headers**: 以下を追加
     ```
     X-WP-Webhook-Secret: your_webhook_secret_here
     ```
     （`.dev.vars` の `WORDPRESS_WEBHOOK_SECRET` と同じ値を使用）

   **ペイロード設定**:
   - **Data Format**: `JSON`
   - **Include Fields**: 
     - ✅ Post ID
     - ✅ Post Title
     - ✅ Post Content
     - ✅ Post Link
     - ✅ ACF Fields（すべて）
     - ✅ Taxonomies

4. **Save Webhook** をクリック

### ステップ7: Webhookのテスト

1. WordPress管理画面で **助成金** → **新規追加** または既存の助成金を編集
2. 内容を変更して **更新** をクリック
3. WP Webhooks の **Logs** タブで送信結果を確認

   **成功時**:
   ```
   Status: 200 OK
   Response: {"success":true,"message":"WordPress post synced successfully","post_id":123}
   ```

4. Cloudflare Workers側でも確認:
   ```bash
   # PM2ログを確認
   pm2 logs webapp --lines 20
   
   # または同期状態を確認
   curl -s http://localhost:3000/api/wordpress/sync-status | jq .
   ```

---

## 🎯 実際の使用例

### ユースケース1: 定期的な全件同期

```bash
# cron job で1日1回実行
0 3 * * * curl -s https://your-domain.workers.dev/api/wordpress/sync >> /var/log/wp-sync.log 2>&1
```

### ユースケース2: 特定の投稿を手動同期

```bash
# WordPress投稿ID 456 を個別に取得
curl -s http://localhost:3000/api/wordpress/posts/456 | jq .
```

### ユースケース3: 同期エラーの監視

```bash
# 同期ログを確認（D1データベース）
wrangler d1 execute grants-db --local --command="SELECT * FROM wp_sync_log ORDER BY created_at DESC LIMIT 10"
```

---

## 🔍 トラブルシューティング

### エラー1: "WordPress API error: Unauthorized"

**原因**: APIトークンが無効または未設定

**解決方法**:
1. Application Passwordsが正しく生成されているか確認
2. トークンのスペースを削除したか確認
3. `.dev.vars` の `WORDPRESS_API_TOKEN` が正しく設定されているか確認
4. WordPressユーザーに適切な権限があるか確認

```bash
# テスト: curlで直接認証確認
curl -u "username:app_password" https://your-site.com/wp-json/wp/v2/grant
```

### エラー2: "Invalid webhook secret"

**原因**: Webhookシークレットが一致しない

**解決方法**:
1. WordPress側のカスタムヘッダー `X-WP-Webhook-Secret` が設定されているか確認
2. 環境変数 `WORDPRESS_WEBHOOK_SECRET` と一致しているか確認
3. 特殊文字が含まれている場合はエンコードの問題を確認

### エラー3: "acf is undefined" または フィールドが空

**原因**: ACFフィールドがREST APIで公開されていない

**解決方法**:
1. ACFフィールドグループ設定を確認
2. 「REST APIで表示」が有効になっているか確認
3. `?_embed=true` パラメータを使用しているか確認

```bash
# ACFフィールドが含まれているか確認
curl https://your-site.com/wp-json/wp/v2/grant/123 | jq '.acf'
```

### エラー4: タクソノミーデータが取得できない

**原因**: `_embed=true` パラメータが不足

**解決方法**:
- REST API呼び出し時に `?_embed=true` を追加
- wordpress.ts の同期ロジックを確認

```bash
# タクソノミー込みで取得
curl 'https://your-site.com/wp-json/wp/v2/grant/123?_embed=true' | jq '._embedded["wp:term"]'
```

### エラー5: 同期が遅い

**原因**: 一度に大量のデータを同期している

**解決方法**:
1. `per_page` パラメータを調整（現在100件）
2. ページネーションを実装して複数回に分けて同期
3. Cloudflare Workersの実行時間制限（CPUタイム10ms/30ms）に注意

```typescript
// wordpress.ts のper_pageを調整
const wpApiUrl = `${wpSiteUrl}/wp-json/wp/v2/grant?per_page=50&_embed=true`;
```

---

## 📊 データベーススキーマ

### wp_sync_log テーブル

```sql
CREATE TABLE wp_sync_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  sync_type TEXT NOT NULL,          -- 'full', 'incremental', 'webhook'
  synced_count INTEGER DEFAULT 0,
  error_count INTEGER DEFAULT 0,
  status TEXT NOT NULL,              -- 'success', 'partial', 'failed'
  error_message TEXT,
  started_at DATETIME NOT NULL,
  completed_at DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### grants テーブル（WordPress連携カラム）

```sql
-- WordPress連携カラム
wp_post_id INTEGER,              -- WordPress投稿ID
wp_sync_status TEXT DEFAULT 'pending',  -- 同期ステータス
last_wp_sync DATETIME,           -- 最終同期日時

-- インデックス
CREATE INDEX idx_grants_wp_post_id ON grants(wp_post_id);
```

---

## 🎓 次のステップ

1. **増分同期の実装**（将来）: 最終更新日時以降の投稿のみを同期
2. **双方向同期の実装**（将来）: Cloudflare Workers側からWordPressへの更新も反映
3. **キャッシュ戦略の導入**（将来）: Cloudflare KVを使用してデータをキャッシュ

---

## 📞 サポート

問題が発生した場合は、以下の情報を添えてGitHub Issueを作成してください：

- エラーメッセージ
- WordPress バージョン
- 使用しているプラグイン一覧
- 環境（開発/本番）
- 実行したコマンドとレスポンス
- PM2ログまたはCloudflare Workersログ

---

**最終更新日**: 2025-11-06  
**対応バージョン**: v1.0.1
