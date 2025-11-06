# Cloudflare Pages デプロイガイド

## 🚀 デプロイ手順

### 前提条件
- Cloudflare アカウント（無料）
- GitHub アカウント
- このリポジトリへのアクセス権

---

## 📝 ステップバイステップ

### 1. Cloudflare Dashboard にアクセス

1. https://dash.cloudflare.com/ にアクセス
2. ログインまたはアカウント作成（無料）

### 2. Cloudflare Pages プロジェクト作成

1. 左サイドバーから **「Workers & Pages」** をクリック
2. **「Create Application」** ボタンをクリック
3. **「Pages」** タブを選択
4. **「Connect to Git」** をクリック

### 3. GitHub 連携

1. **「Connect GitHub」** をクリック
2. GitHub での認証を完了
3. リポジトリ一覧から **「joseikininsight-hue/matching-Public」** を選択
4. **「Begin setup」** をクリック

### 4. ビルド設定

以下の設定を入力してください：

```
プロジェクト名: grant-matching-system （任意の名前）
プロダクションブランチ: main
ビルドコマンド: npm run build
ビルド出力ディレクトリ: dist
```

**フレームワークプリセット**: None（カスタム設定）

### 5. 環境変数の設定

**「Environment variables」** セクションで以下を追加：

| 変数名 | 値 | 説明 |
|--------|-----|------|
| `GEMINI_API_KEY` | `AIzaSyA-KolgF1yF1wUI2R8xNHmQCjIaHqo2SMM` | Gemini AI APIキー |
| `WORDPRESS_SITE_URL` | `https://joseikin-insight.com` | WordPress サイトURL |
| `JWT_SECRET` | `your_jwt_secret_key_here` | 管理者認証用シークレット |
| `NODE_VERSION` | `18` | Node.jsバージョン |

**重要**: すべての環境変数は **Production** と **Preview** の両方にチェックを入れてください。

### 6. D1 Database の設定

#### 6.1 D1 データベースの作成

1. Cloudflare Dashboard → **「Workers & Pages」** → **「D1」**
2. **「Create database」** をクリック
3. データベース名: `grants-db`
4. **「Create」** をクリック

#### 6.2 Database ID の取得

1. 作成したデータベースをクリック
2. Database ID をコピー（例: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`）

#### 6.3 Pages プロジェクトにバインド

1. Pages プロジェクトの **「Settings」** → **「Functions」**
2. **「D1 database bindings」** セクション
3. **「Add binding」** をクリック
4. Variable name: `DB`
5. D1 database: `grants-db` を選択
6. **「Save」** をクリック

#### 6.4 マイグレーション実行

```bash
# ローカルから実行（wrangler CLIが必要）
npx wrangler d1 migrations apply grants-db --remote

# または、D1 Dashboard から直接SQLを実行
# migrations/ フォルダ内の .sql ファイルを順番に実行
```

### 7. デプロイ実行

1. **「Save and Deploy」** をクリック
2. ビルドとデプロイが自動的に開始されます（3-5分程度）

### 8. デプロイ完了

✅ デプロイが完了すると、以下のようなURLが発行されます：

```
https://grant-matching-system.pages.dev
```

または、カスタムドメインを設定することも可能です。

---

## 🔄 WordPress データの初期同期

デプロイ後、以下のURLにアクセスして初回データ同期を実行してください：

```
https://your-project.pages.dev/api/wordpress/sync?page=1&max_pages=80
```

または、以下のスクリプトを実行：

```bash
./sync-all-grants.sh https://your-project.pages.dev
```

---

## 📊 動作確認

### 同期状態の確認

```
https://your-project.pages.dev/api/wordpress/sync-status
```

期待される結果:
```json
{
  "success": true,
  "data": {
    "total_grants": 7949,
    "wp_synced_grants": 7949,
    "last_sync": "2025-11-06 10:01:07"
  }
}
```

### アプリケーションのテスト

1. トップページにアクセス: `https://your-project.pages.dev`
2. 質問に答えて助成金推薦を取得
3. 管理画面にアクセス: `https://your-project.pages.dev/admin`

---

## 🔧 トラブルシューティング

### ビルドエラーが発生する場合

1. **Node.js バージョン確認**
   - Environment variables に `NODE_VERSION=18` が設定されているか確認

2. **依存関係の問題**
   - `package.json` の dependencies を確認
   - ローカルで `npm install` が成功するか確認

3. **ビルドログの確認**
   - Cloudflare Dashboard → プロジェクト → Deployments → 失敗したデプロイ → View build log

### D1 Database に接続できない場合

1. **バインディング設定の確認**
   - Settings → Functions → D1 database bindings
   - Variable name が `DB` になっているか確認
   - 正しいデータベースが選択されているか確認

2. **マイグレーション実行の確認**
   - D1 Dashboard でテーブルが作成されているか確認
   - `grants`, `user_sessions`, `user_answers` テーブルが存在するか確認

### Gemini API が動作しない場合

1. **環境変数の確認**
   - Settings → Environment variables
   - `GEMINI_API_KEY` が正しく設定されているか確認
   - Production と Preview の両方にチェックが入っているか確認

2. **API キーの有効性確認**
   - Google AI Studio (https://aistudio.google.com/) で API キーが有効か確認
   - 必要に応じて新しい API キーを生成

---

## 🔄 継続的デプロイ（CI/CD）

GitHub にコードをプッシュすると、自動的にデプロイされます：

```bash
git add .
git commit -m "Update feature"
git push origin main
```

### ブランチ戦略

- **main ブランチ**: 本番環境に自動デプロイ
- **その他のブランチ**: プレビュー環境に自動デプロイ
  - 例: `https://branch-name.grant-matching-system.pages.dev`

---

## 🌐 カスタムドメインの設定

### 独自ドメインを使用する場合

1. Cloudflare Dashboard → プロジェクト → **「Custom domains」**
2. **「Set up a custom domain」** をクリック
3. ドメイン名を入力（例: `grant.joseikin-insight.com`）
4. DNS レコードを設定（Cloudflare が自動で案内）
5. SSL証明書が自動で発行されます（無料）

### WordPress への埋め込み

固定URLが取得できたら、WordPress の固定ページに以下のように埋め込めます：

```html
<iframe 
  src="https://your-project.pages.dev" 
  width="100%" 
  height="800px" 
  frameborder="0"
  style="border: none; max-width: 1200px; margin: 0 auto; display: block;"
></iframe>
```

または、より高度な埋め込み：

```html
<div id="grant-matching-app"></div>
<script>
  // 親ウィンドウとの通信を設定
  window.addEventListener('message', function(event) {
    if (event.data.type === 'resize') {
      document.getElementById('grant-matching-app').style.height = event.data.height + 'px';
    }
  });
</script>
<iframe 
  id="grant-iframe"
  src="https://your-project.pages.dev" 
  width="100%" 
  height="600px"
  frameborder="0"
></iframe>
```

---

## 📝 メンテナンス

### WordPress データの定期同期

Cloudflare Workers Cron Triggers を使用して自動同期を設定できます：

1. `wrangler.toml` に以下を追加：

```toml
[triggers]
crons = ["0 */6 * * *"]  # 6時間ごとに実行
```

2. `src/index.tsx` に Cron ハンドラーを追加：

```typescript
export default {
  async scheduled(event, env, ctx) {
    // WordPress 同期を実行
    const response = await fetch('https://your-project.pages.dev/api/wordpress/sync?page=1&max_pages=80');
    console.log('Scheduled sync completed:', await response.json());
  }
}
```

### ログの確認

Cloudflare Dashboard → プロジェクト → **「Logs」** でリアルタイムログを確認できます。

---

## 💡 Tips

### パフォーマンス最適化

- Cloudflare Pages は自動的にグローバル CDN でキャッシュされます
- 静的ファイルは `/static/*` に配置すると最適化されます
- D1 Database のクエリはエッジで実行されるため高速です

### セキュリティ

- 環境変数は暗号化されて保存されます
- HTTPS が自動的に有効化されます
- CORS 設定は `src/index.tsx` で管理できます

### コスト

Cloudflare Pages の無料プランで十分に運用可能：
- 無制限のリクエスト
- 無制限の帯域幅
- 500 ビルド/月
- D1: 5GB ストレージ、1日500万読み取り

---

## 🆘 サポート

問題が発生した場合：

1. **Cloudflare Community**: https://community.cloudflare.com/
2. **Cloudflare Docs**: https://developers.cloudflare.com/pages/
3. **GitHub Issues**: このリポジトリの Issues セクション

---

## ✅ チェックリスト

デプロイ前に確認：

- [ ] Cloudflare アカウント作成済み
- [ ] GitHub リポジトリへのアクセス権あり
- [ ] Gemini API キー取得済み
- [ ] WordPress サイトの REST API が有効

デプロイ後に確認：

- [ ] ビルドが成功
- [ ] D1 Database バインディング設定完了
- [ ] 環境変数が正しく設定されている
- [ ] `/api/wordpress/sync-status` が正常に動作
- [ ] トップページが表示される
- [ ] WordPress からデータが同期できる
- [ ] Gemini AI によるマッチングが動作する

---

🎉 **デプロイ完了おめでとうございます！**
