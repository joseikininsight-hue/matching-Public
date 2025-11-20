# 🚀 クイックフィックス: デプロイエラーの解決

## ✅ 最新の状況 (2025-11-20 14:45)

**最新のエラーは修正済みです！**
- ❌ 以前のエラー: `エラー 8000022: データベース UUID (local-grants-db) が無効です` → ✅ 修正済み
- ❌ 以前のエラー: `No such module "node:stream"` → ✅ 修正済み

最新のコミット (db178e9) で以下を実施:
- Node.js依存パッケージ (xlsx, papaparse, uuid) を削除
- Cloudflare Workers互換のコードに変更
- ビルドサイズを543KBから171KBに削減

**次回デプロイでは成功するはずです！** ただし、以下の手動設定は必要です：

## ❌ 以前のエラー (修正済み)
```
エラー 8000022: データベース UUID (local-grants-db) が無効です
エラー: No such module "node:stream"
```

## ✅ 解決方法 (3ステップ)

### ステップ1: D1データベースをバインド (2分) 🔧

1. [Cloudflare Pagesダッシュボード](https://dash.cloudflare.com/)を開く
2. **matching-public** プロジェクトを選択
3. **Settings → Functions** に移動
4. **D1 database bindings** セクションまでスクロール
5. **Add binding** をクリック
6. 設定:
   - Variable name: `DB`
   - D1 database: `grants-db` を選択
7. **Save** をクリック

### ステップ2: 環境変数を設定 (1分) 🔑

1. **Settings → Environment variables** に移動
2. **Add variable** をクリック
3. 設定:
   - Variable name: `GEMINI_API_KEY`
   - Value: `AIzaSyDjq1BQdjccRj0FZIAFhRPzyLJbu1wScDI`
   - Environment: **Production** と **Preview** を両方選択
4. **Save** をクリック

### ステップ3: データベースマイグレーションを実行 (3分) 💾

1. **Cloudflare Dashboard → D1** に移動
2. データベース **grants-db** を選択
3. **Console** タブを開く
4. 下記のSQLを実行:

```sql
-- Migration 1: ACFフィールドを追加
ALTER TABLE grants ADD COLUMN url TEXT;
ALTER TABLE grants ADD COLUMN eligible_expenses TEXT;
ALTER TABLE grants ADD COLUMN required_documents TEXT;
ALTER TABLE grants ADD COLUMN adoption_rate TEXT;
ALTER TABLE grants ADD COLUMN difficulty_level TEXT;
ALTER TABLE grants ADD COLUMN area_notes TEXT;
ALTER TABLE grants ADD COLUMN subsidy_rate_detailed TEXT;

CREATE INDEX IF NOT EXISTS idx_grants_organization ON grants(organization);
CREATE INDEX IF NOT EXISTS idx_grants_url ON grants(url);
```

5. **Execute** をクリック
6. 次に下記のSQLを実行:

```sql
-- Migration 2: answer_labelカラムを追加
ALTER TABLE conversation_history ADD COLUMN answer_label TEXT;
```

7. **Execute** をクリック

---

## 🔄 再デプロイ

上記の3ステップ完了後:

**Deployments** タブ → 最新のデプロイメント → **⋯** → **Retry deployment**

または、GitHubに新しいコミットをプッシュすれば自動的に再デプロイされます（今回は**既にプッシュ済み**です！）

---

## ✅ テスト

デプロイ成功後、以下を確認:

1. https://matching-public.pages.dev/ にアクセス
2. 「助成金診断を始める」をクリック
3. Q001〜Q004に回答（Q005は表示されないはず）
4. AI推薦が表示される
5. **AIの推薦理由がカードの上部に表示される** ✅
6. **"記載なし"ラベルが表示されない** ✅

---

## 📝 変更内容

今回の修正で:
- ✅ `wrangler.toml` から無効な `database_id` を削除
- ✅ Cloudflare Pagesダッシュボードでの手動設定に変更
- ✅ ローカル開発用に `wrangler.toml.local` を作成
- ✅ デプロイメント手順を詳細に文書化

---

**詳細ガイド**: `DEPLOYMENT_CHECKLIST.md` を参照してください。
