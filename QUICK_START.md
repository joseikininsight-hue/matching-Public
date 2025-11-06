# 🚀 クイックスタート: Cloudflare Pages デプロイ

## 現在の状況

✅ **完了していること**:
- WordPress から 7,949件の助成金データを同期完了
- Gemini AI APIキー設定完了
- システムは完全に動作中
- GitHub リポジトリにコードをプッシュ済み

⚠️ **問題**: 
- 現在のURL（Sandbox）は一時的で不安定
- 本番環境へのデプロイが必要

---

## 📋 5分でデプロイする手順

### 1️⃣ Cloudflare にログイン（1分）

👉 https://dash.cloudflare.com/

- アカウントがない場合は無料で作成

### 2️⃣ Pages プロジェクト作成（2分）

1. 左サイドバー **「Workers & Pages」** をクリック
2. **「Create Application」** → **「Pages」** タブ
3. **「Connect to Git」** をクリック
4. **GitHub で認証**
5. リポジトリ選択: **「joseikininsight-hue/matching-Public」**

### 3️⃣ ビルド設定（1分）

```
プロジェクト名: grant-matching （好きな名前でOK）
ブランチ: main
ビルドコマンド: npm run build
出力ディレクトリ: dist
```

### 4️⃣ 環境変数を設定（1分）

| 変数名 | 値 |
|--------|-----|
| `GEMINI_API_KEY` | `AIzaSyA-KolgF1yF1wUI2R8xNHmQCjIaHqo2SMM` |
| `WORDPRESS_SITE_URL` | `https://joseikin-insight.com` |
| `JWT_SECRET` | `your_jwt_secret_key_here` |
| `NODE_VERSION` | `18` |

**重要**: Production と Preview の両方にチェック ✅

### 5️⃣ デプロイ実行

**「Save and Deploy」** をクリック → 3-5分待機

---

## 🎯 デプロイ後の作業

### D1 Database の設定（必須）

#### A. データベース作成

1. Cloudflare Dashboard → **「Workers & Pages」** → **「D1」**
2. **「Create database」** → 名前: `grants-db`

#### B. バインディング設定

1. Pages プロジェクト → **「Settings」** → **「Functions」**
2. **「D1 database bindings」** セクション
3. **「Add binding」**:
   - Variable name: `DB`
   - Database: `grants-db`
4. **「Save」**

#### C. マイグレーション実行

**方法1: wrangler CLI（推奨）**
```bash
npx wrangler d1 migrations apply grants-db --remote
```

**方法2: D1 Dashboard から直接実行**
1. D1 Database → `grants-db` を開く
2. **「Console」** タブ
3. `migrations/` フォルダの SQLファイルを順番に実行:
   - `0001_create_tables.sql`
   - `0002_add_indexes.sql`
   - `0003_add_wordpress_integration.sql`

### データ同期

デプロイ完了後、以下のURLにアクセス：

```
https://your-project.pages.dev/api/wordpress/sync?page=1&max_pages=80
```

または自動スクリプト:
```bash
./sync-all-grants.sh https://your-project.pages.dev
```

---

## ✅ 動作確認

### 同期状態チェック

```
https://your-project.pages.dev/api/wordpress/sync-status
```

期待される結果:
```json
{
  "success": true,
  "data": {
    "total_grants": 7949,
    "wp_synced_grants": 7949
  }
}
```

### アプリケーション確認

```
https://your-project.pages.dev
```

→ 質問に答えて助成金推薦が表示されればOK！

---

## 🌐 WordPress への埋め込み

固定URLが取得できたら、WordPress固定ページに追加：

```html
<iframe 
  src="https://your-project.pages.dev" 
  width="100%" 
  height="800px" 
  frameborder="0"
  style="border: none; max-width: 1200px; margin: 0 auto; display: block;"
></iframe>
```

### 高度な埋め込み（レスポンシブ対応）

```html
<div class="grant-matching-container" style="max-width: 1200px; margin: 0 auto;">
  <iframe 
    id="grant-matching-iframe"
    src="https://your-project.pages.dev" 
    width="100%" 
    height="600px"
    frameborder="0"
    style="border: none; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
  ></iframe>
</div>

<script>
// 自動高さ調整
window.addEventListener('message', function(event) {
  if (event.data.type === 'resize') {
    const iframe = document.getElementById('grant-matching-iframe');
    iframe.style.height = event.data.height + 'px';
  }
});
</script>
```

---

## 🔄 今後の更新方法

コードを更新したら、GitHubにプッシュするだけで自動デプロイ：

```bash
git add .
git commit -m "Update feature"
git push origin main
```

→ Cloudflare が自動的にビルド＆デプロイ（CI/CD）

---

## 💡 便利な機能

### カスタムドメイン設定

Pages プロジェクト → **「Custom domains」**

例: `grant.joseikin-insight.com`

- SSL証明書自動発行（無料）
- DNS自動設定

### ブランチプレビュー

`main` 以外のブランチをプッシュすると、自動的にプレビューURLが生成：

```
https://branch-name.grant-matching.pages.dev
```

→ 本番環境に影響なくテストできる

---

## 🆘 トラブルシューティング

### ビルドが失敗する

1. Environment variables に `NODE_VERSION=18` を追加
2. Deployment logs を確認

### D1 に接続できない

1. D1 binding が正しく設定されているか確認
   - Variable name: `DB`
   - Database: `grants-db`
2. マイグレーションが完了しているか確認

### Gemini API エラー

1. `GEMINI_API_KEY` が正しく設定されているか確認
2. Production と Preview の両方にチェックが入っているか確認
3. API キーの有効性を Google AI Studio で確認

---

## 📊 無料プランで十分な理由

Cloudflare Pages 無料プラン:
- ✅ 無制限のリクエスト
- ✅ 無制限の帯域幅
- ✅ 500 ビルド/月
- ✅ グローバル CDN
- ✅ 自動 HTTPS
- ✅ D1: 5GB ストレージ、500万読み取り/日

→ 通常の助成金マッチングサイトには十分すぎるスペック

---

## 📚 詳細なドキュメント

より詳しい情報は `DEPLOYMENT_GUIDE.md` を参照してください。

---

## 🎉 完了！

デプロイが完了したら、固定URLをWordPressに埋め込んで公開できます。

**質問がある場合**:
- GitHub Issues: https://github.com/joseikininsight-hue/matching-Public/issues
- Cloudflare Community: https://community.cloudflare.com/
