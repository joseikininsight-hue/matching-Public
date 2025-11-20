# 本番環境セットアップガイド

## 🎯 現在の状況

### ✅ 完了している作業

1. **コードのデプロイ**: GitHubへプッシュ完了（commit: 6105b58）
2. **UIデザイン更新**: AI推薦理由を目立たせる変更済み
3. **ローカル開発環境**: 完全動作確認済み
4. **Gemini APIキー**: 新しいキー取得済み

### ⚠️ 本番環境で必要な作業

## 📋 本番環境セットアップ手順

### 1. Cloudflare Pages 環境変数の設定

Cloudflareダッシュボードで以下の環境変数を設定してください：

#### アクセス方法:
1. https://dash.cloudflare.com にアクセス
2. Pagesセクション → `matching-public` プロジェクトを選択
3. Settings → Environment variables

#### 設定する環境変数:

```bash
# Gemini AI API Key (必須)
GEMINI_API_KEY=AIzaSyDjq1BQdjccRj0FZIAFhRPzyLJbu1wScDI

# WordPress設定
WORDPRESS_SITE_URL=https://joseikin-insight.com
WORDPRESS_API_TOKEN=(必要に応じて)
WORDPRESS_WEBHOOK_SECRET=(必要に応じて)

# JWT Secret
JWT_SECRET=(ランダムな文字列を生成)
```

**重要**: `GEMINI_API_KEY`は必須です。これがないとAI推薦が動作しません。

### 2. D1データベースのマイグレーション適用

#### オプションA: Cloudflareダッシュボード経由（推奨）

1. https://dash.cloudflare.com にアクセス
2. Workers & Pages → D1
3. `grants-db` データベースを選択
4. Console タブを開く
5. 以下のSQLを順番に実行：

**Migration 0004: ACF Fields**
```sql
-- Add missing ACF fields to grants table
ALTER TABLE grants ADD COLUMN url TEXT;
ALTER TABLE grants ADD COLUMN eligible_expenses TEXT;
ALTER TABLE grants ADD COLUMN required_documents TEXT;
ALTER TABLE grants ADD COLUMN adoption_rate TEXT;
ALTER TABLE grants ADD COLUMN difficulty_level TEXT;
ALTER TABLE grants ADD COLUMN area_notes TEXT;
ALTER TABLE grants ADD COLUMN subsidy_rate_detailed TEXT;

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_grants_organization ON grants(organization);
CREATE INDEX IF NOT EXISTS idx_grants_url ON grants(url);
```

**Migration 0005: Answer Label**
```sql
-- Add answer_label column to conversation_history table
ALTER TABLE conversation_history ADD COLUMN answer_label TEXT;
```

#### オプションB: Wrangler CLI経由

Cloudflare APIトークンを取得後：

```bash
export CLOUDFLARE_API_TOKEN=your_token_here
cd /home/user/webapp
wrangler d1 migrations apply grants-db --remote
```

### 3. デプロイの確認

Cloudflare Pagesは自動的にGitHubからデプロイします：

1. https://dash.cloudflare.com → Pages → matching-public
2. Deploymentsタブで最新のデプロイを確認
3. Statusが "Success" になるまで待つ（通常1-3分）

### 4. 動作確認

#### テスト1: APIエンドポイント
```bash
curl https://matching-public.pages.dev/api/grants/stats/summary
```

期待される結果: `{"success": true, "data": {...}}`

#### テスト2: セッション作成
```bash
curl -X POST https://matching-public.pages.dev/api/sessions
```

期待される結果: `{"success": true, "data": {"session_id": "..."}}`

#### テスト3: フルフロー
1. https://matching-public.pages.dev にアクセス
2. 質問に回答
3. AI推薦が表示されることを確認
4. AI推薦理由が青い背景で目立つことを確認
5. 「記載なし」が不要な場所に表示されないことを確認

### 5. トラブルシューティング

#### 問題: AI推薦が動作しない

**症状**: マッチング完了後にエラーが表示される

**原因**: GEMINI_API_KEYが設定されていない

**解決策**:
1. Cloudflare Pages → Settings → Environment variables
2. `GEMINI_API_KEY`を追加
3. 値: `AIzaSyDjq1BQdjccRj0FZIAFhRPzyLJbu1wScDI`
4. Redeploy ボタンをクリック

#### 問題: データベースエラー

**症状**: "table has no column named ..." エラー

**原因**: マイグレーションが適用されていない

**解決策**:
1. D1コンソールで上記のSQLを実行
2. または wrangler CLI でマイグレーション実行

#### 問題: 古いデザインが表示される

**症状**: AI推薦理由が小さい、「記載なし」が表示される

**原因**: キャッシュが残っている

**解決策**:
1. ブラウザのキャッシュをクリア（Ctrl+Shift+R / Cmd+Shift+R）
2. シークレットモードで確認
3. 数分待ってCloudflare CDNのキャッシュが更新されるのを待つ

---

## 📊 現在のデータ状況

### 本番D1データベース
- **総件数**: 6,001件
- **都道府県データ**: あり
- **カテゴリデータ**: あり
- **ACFフィールド**: すべてnull（WordPress側の設定待ち）

### WordPress側の対応待ち
- ACF REST API有効化（WORDPRESS_FIX_GUIDE.md参照）
- データ再同期

---

## 🎨 最新のUI変更

### デザイン改善内容
1. **AI推薦理由を最上部に配置**
   - 青い背景（#f0f8ff）+ 黒枠
   - ロボット絵文字（🤖）
   - テキストサイズ拡大
   - 「AIがこの助成金を選んだ理由」見出し

2. **「記載なし」ラベルの削除**
   - データがない項目は非表示
   - クリーンで読みやすいカードデザイン

3. **ボタンテキストの日本語化**
   - More → 詳しく見る →
   - Close → 閉じる
   - 詳細 → 詳細を見る →

---

## ✅ チェックリスト

- [ ] Cloudflare Pages環境変数を設定（GEMINI_API_KEY）
- [ ] D1データベースマイグレーション適用
- [ ] デプロイ完了を確認
- [ ] 本番環境でセッション作成をテスト
- [ ] AI推薦が動作することを確認
- [ ] 新しいUIデザインが表示されることを確認
- [ ] WordPress ACF設定（WORDPRESS_FIX_GUIDE.md参照）
- [ ] データ再同期

---

**最終更新**: 2025-11-20
**デプロイURL**: https://matching-public.pages.dev
**コミット**: 6105b58
