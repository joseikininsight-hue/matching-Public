# WordPress連携ガイド

このドキュメントでは、WordPressカスタム投稿タイプ「補助金」とD1データベースを連携させる方法を説明します。

## 概要

WordPressで管理している補助金情報を、Cloudflare Workers + D1データベースで動作する本アプリケーションと連携できます。

### 連携方法

1. **手動同期**: REST APIを使ってWordPressからデータを取得
2. **自動同期**: Webhookを使ってリアルタイムで更新を反映

## 前提条件

### WordPress側の要件

- WordPress 5.0以上
- REST API が有効になっている
- カスタム投稿タイプ「subsidy」が登録されている
- Advanced Custom Fields (ACF) プラグインがインストールされている（推奨）

### 必要なWordPress APIトークン

WordPress REST APIにアクセスするためのトークンが必要です。以下のいずれかの方法で取得できます：

1. **Application Passwords（推奨）**
   - WordPress 5.6以降で利用可能
   - ユーザープロフィール画面から生成

2. **JWT認証プラグイン**
   - より高度な認証が必要な場合

## セットアップ手順

### 1. WordPress側の設定

#### 1.1 カスタム投稿タイプの登録

**✅ 既に設定済みです！**

リポジトリの `functions.php` と `inc/theme-foundation.php` に既に設定されています：

```php
// カスタム投稿タイプ: 'grant' (補助金)
register_post_type('grant', array(
    'label' => '助成金',
    'public' => true,
    'show_in_rest' => true,  // REST API で利用可能
    'rest_base' => 'grant',  // REST APIエンドポイント: /wp-json/wp/v2/grant
    'supports' => array('title', 'editor', 'custom-fields'),
    'menu_icon' => 'dashicons-money-alt',
));
```

#### 1.2 カスタムフィールドの設定

**✅ 既に設定済みです！**

リポジトリの `inc/acf-fields.php` に既に定義されています：

| フィールド名 | タイプ | 説明 | 必須 |
|------------|--------|------|------|
| `organization` | テキスト | 実施組織名 | ✅ |
| `max_amount` | テキスト | 最大助成額（表示用） | ✅ |
| `max_amount_numeric` | 数値 | 最大助成額（数値/検索用） | ✅ |
| `deadline` | テキスト | 締切（表示用） | - |
| `deadline_date` | 日付 | 締切日（ソート用） | - |
| `application_status` | 選択 | 申請ステータス（募集中/募集予定/募集終了） | ✅ |
| `grant_target` | WYSIWYG | 対象者・対象事業 | ✅ |
| `eligible_expenses` | WYSIWYG | 対象経費 | - |
| `required_documents` | WYSIWYG | 必要書類 | - |
| `official_url` | URL | 公式サイトURL | - |
| `adoption_rate` | 数値 | 採択率（%） | - |
| `difficulty_level` | 選択 | 申請難易度 | - |
| `area_notes` | テキストエリア | 地域に関する備考 | - |
| `subsidy_rate_detailed` | テキスト | 補助率（詳細） | - |

**タクソノミー（カテゴリー）**:
- `grant_category`: 助成金カテゴリー
- `prefecture`: 都道府県
- `municipality`: 市町村

**重要**: ACFフィールドは既に「REST APIで表示」が有効化されています。

#### 1.3 REST API の確認

ブラウザまたはcurlで以下のURLにアクセスして、データが取得できることを確認：

```bash
# 助成金一覧を取得
curl https://your-wordpress-site.com/wp-json/wp/v2/grant

# タクソノミー込みで取得
curl https://your-wordpress-site.com/wp-json/wp/v2/grant?_embed=true

# 特定の助成金を取得（IDを123と仮定）
curl https://your-wordpress-site.com/wp-json/wp/v2/grant/123?_embed=true
```

### 2. アプリケーション側の設定

#### 2.1 環境変数の設定

`.dev.vars` ファイル（ローカル開発）または Cloudflare Workers の環境変数に以下を設定：

```bash
# WordPress サイトのURL（末尾のスラッシュなし）
WORDPRESS_SITE_URL=https://your-wordpress-site.com

# WordPress REST API トークン（Application Passwords等）
WORDPRESS_API_TOKEN=your_wordpress_api_token_here

# Webhook認証用のシークレット（任意の文字列）
WORDPRESS_WEBHOOK_SECRET=your_secure_random_string_here
```

#### 2.2 本番環境への設定

Cloudflare Dashboard から環境変数を設定：

```bash
wrangler secret put WORDPRESS_SITE_URL
wrangler secret put WORDPRESS_API_TOKEN
wrangler secret put WORDPRESS_WEBHOOK_SECRET
```

または、Cloudflare Dashboardの「Workers & Pages」→ 対象プロジェクト → 「Settings」→「Variables」から設定。

#### 2.3 データベースマイグレーション

WordPress連携用のカラムを追加：

```bash
# ローカル環境
npm run db:migrate:local

# 本番環境
npm run db:migrate
```

## 使い方

### 手動同期

#### 全データの同期

WordPressの全投稿を取得してD1データベースに同期：

```bash
curl http://localhost:3000/api/wordpress/sync
```

レスポンス例：
```json
{
  "success": true,
  "message": "WordPress sync completed: 25 synced, 0 errors",
  "synced_count": 25,
  "error_count": 0,
  "total": 25
}
```

#### 特定の投稿を取得

WordPress投稿ID（例: 123）を指定して取得：

```bash
curl http://localhost:3000/api/wordpress/posts/123
```

#### 同期状態の確認

現在の同期状態を確認：

```bash
curl http://localhost:3000/api/wordpress/sync-status
```

レスポンス例：
```json
{
  "success": true,
  "data": {
    "total_grants": 50,
    "wp_synced_grants": 25,
    "last_sync": "2025-11-06 08:45:30"
  }
}
```

### 自動同期（Webhook）

#### 3.1 WordPressにWebhookプラグインをインストール

推奨プラグイン：
- **WP Webhooks** (無料)
- **Webhook Pro** (有料)
- **WP REST API Controller** (無料)

#### 3.2 Webhookの設定

WP Webhooks プラグインを使用する場合：

1. WordPress管理画面で「WP Webhooks」→「Send Data」に移動
2. 新しいWebhookを追加：
   - **トリガー**: `post_created` または `post_updated`
   - **投稿タイプ**: `subsidy`
   - **Webhook URL**: `https://your-app.com/api/wordpress/webhook`
   - **カスタムヘッダー**: 
     ```
     X-WP-Webhook-Secret: your_webhook_secret_here
     ```
3. ペイロードに投稿データ全体を含めるよう設定

#### 3.3 Webhookのテスト

WordPress側でテスト送信を実行するか、投稿を作成/更新して動作確認。

アプリケーション側のログで確認：
```bash
pm2 logs webapp
```

正常に同期された場合：
```
WordPress post synced successfully (post_id: 123)
```

## データマッピング

WordPressのカスタムフィールドとD1データベースのカラムのマッピング：

| WordPress フィールド | D1 カラム | 説明 |
|----------------------|-----------|------|
| `post.title.rendered` | `title` | 補助金タイトル |
| `acf.organization` | `organization` | 実施組織 |
| `acf.max_amount` | `max_amount_display` | 金額（表示用テキスト） |
| `acf.max_amount_numeric` | `max_amount_numeric` | 金額（数値/検索用） |
| `acf.deadline` | `deadline_display` | 締切（表示用） |
| `acf.deadline_date` | `deadline_date` | 締切日（日付） |
| `acf.official_url` | `url` | 公式サイトURL |
| `_embedded.wp:term[prefecture][0].name` | `prefecture_name` | 都道府県名 |
| `_embedded.wp:term[prefecture][0].slug` | `prefecture_code` | 都道府県コード |
| `post.content.rendered` | `description` | 説明文 |
| `acf.grant_target` | `grant_target` | 対象者・対象事業 |
| `acf.eligible_expenses` | `eligible_expenses` | 対象経費 |
| `acf.application_status` | `application_status` | 申請ステータス |
| `post.id` | `wordpress_id` | WordPress投稿ID（主） |
| `post.id` | `wp_post_id` | WordPress投稿ID（互換性） |
| - | `wp_sync_status` | 同期ステータス（synced/pending） |
| - | `last_wp_sync` | 最終同期日時 |

## トラブルシューティング

### エラー: "WordPress API error: Unauthorized"

**原因**: APIトークンが無効または未設定

**解決方法**:
1. `WORDPRESS_API_TOKEN` が正しく設定されているか確認
2. WordPressのApplication Passwordsが有効か確認
3. トークンの権限が十分か確認

### エラー: "Invalid webhook secret"

**原因**: Webhook認証シークレットが一致しない

**解決方法**:
1. WordPress側のカスタムヘッダー `X-WP-Webhook-Secret` が設定されているか確認
2. 環境変数 `WORDPRESS_WEBHOOK_SECRET` と一致しているか確認

### データが同期されない

**原因**: カスタムフィールドがREST APIで公開されていない

**解決方法**:
1. ACFの設定で「REST APIで表示」が有効になっているか確認
2. カスタム投稿タイプの `show_in_rest` が `true` になっているか確認
3. ブラウザでREST APIのURLを直接開いて、データが取得できるか確認

### 同期が遅い

**原因**: 一度に大量のデータを同期している

**解決方法**:
1. WordPress REST APIの `per_page` パラメータを調整（現在は100件/回）
2. バッチ処理を複数回に分けて実行
3. Cloudflare Workersの実行時間制限（CPUタイム10ms/30ms）に注意

## セキュリティのベストプラクティス

1. **APIトークンの保護**
   - 環境変数を使用（ハードコードしない）
   - 定期的にトークンをローテーション

2. **Webhook認証**
   - 必ずシークレットキーを設定
   - ランダムで推測困難な文字列を使用

3. **CORS設定**
   - 必要に応じてアクセス元を制限

4. **レート制限**
   - 同期APIの呼び出し頻度を制限（将来実装予定）

## パフォーマンス最適化

1. **インクリメンタル同期**
   - 全件同期ではなく、更新された投稿のみを同期（将来実装予定）
   - `last_wp_sync` カラムを使用して差分を検出

2. **キャッシュ戦略**
   - Cloudflare KVを使ってWordPressデータをキャッシュ（将来実装予定）

3. **バッチサイズの調整**
   - 一度に同期する件数を調整

## よくある質問 (FAQ)

### Q1: 既存のデータを削除せずに同期できますか？

A: はい。`INSERT OR REPLACE` を使用しているため、WordPress投稿IDが一致する場合は更新、新規の場合は追加されます。既存のデータは保持されます。

### Q2: WordPress側でACFを使わずに標準のカスタムフィールドを使えますか？

A: はい、可能です。ただし、REST APIでカスタムフィールドを公開するための追加設定が必要です。ACFを使う方が簡単です。

### Q3: 複数のWordPressサイトから同期できますか？

A: 現在の実装では1つのWordPressサイトのみサポートしています。複数サイト対応は将来の機能として検討中です。

### Q4: Webhookが受信されたか確認する方法は？

A: PM2ログまたはCloudflare Workersのログで確認できます：
```bash
pm2 logs webapp --lines 50
```

## サポート

問題が発生した場合は、以下の情報とともにGitHub Issueを作成してください：

- エラーメッセージ
- WordPress バージョン
- 使用しているプラグイン一覧
- 環境（開発/本番）
- 実行したコマンドとレスポンス

---

**最終更新日**: 2025-11-06
**対応バージョン**: v1.0.0
