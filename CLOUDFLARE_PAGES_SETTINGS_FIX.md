# 🔧 Cloudflare Pages ビルドエラー修正手順

## 🚨 現在のエラー状況

**エラー**: `Failed: root directory not found`  
**原因**: Cloudflare Pages のダッシュボード設定で「Root directory」に誤った値が入っています

---

## ✅ 修正手順（スマートフォンの場合）

### ステップ1: Cloudflare ダッシュボードにアクセス

1. **PC/デスクトップブラウザの使用を推奨** 
   - スマートフォンブラウザでフォーム送信エラーが発生する可能性があります
   - 可能であれば PC でアクセスしてください

2. Cloudflare Dashboard にログイン
   - URL: https://dash.cloudflare.com/

### ステップ2: プロジェクト設定にアクセス

1. **Workers & Pages** セクションをクリック
2. プロジェクト **`grant-matching`** を選択
3. **Settings**（設定）タブをクリック
4. **Builds & deployments** セクションを探す
5. **Build configuration** の **Edit configuration** ボタンをクリック

### ステップ3: 設定を修正

以下の設定を**正確に**入力してください：

```
┌─────────────────────────────────────────┐
│ Framework preset                        │
│ [None] ← 選択                           │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ Build command                           │
│ npm run build                           │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ Build output directory                  │
│ dist                                    │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ Root directory (path)                   │
│ [完全に空白のまま]                      │
│ ※ "/" や "." も入れないでください      │
└─────────────────────────────────────────┘
```

### ⚠️ 重要なポイント

#### Root directory の設定について:

**❌ 間違い:**
- `/` を入力
- `./` を入力
- 任意のパスを入力

**✅ 正解:**
- **完全に空白** （何も入力しない）
- フィールドが必須で空白にできない場合のみ `.` を入力

#### その他の設定を削除:

以下のフィールドがあれば**空白にしてください**:
- Deploy command → 空白
- Non-production branch deploy command → 空白

### ステップ4: 保存と再デプロイ

1. **Save** ボタンをクリック
2. **Deployments** タブに移動
3. 最新のデプロイメントの **Retry deployment** をクリック

または:

```bash
# 空のコミットでデプロイをトリガー
cd /home/user/webapp
git checkout main
git commit --allow-empty -m "trigger: Retry deployment after fixing root directory"
git push origin main
```

---

## 🔍 ビルドログで確認すべきポイント

再デプロイ後、以下を確認:

### 成功する場合のログ:

```
✓ ビルド環境の初期化
✓ gitリポジトリのクローニング  ← ここでエラーが出ていた
✓ ツールと依存関係のインストール
✓ アプリケーションを構築する
✓ Cloudflareのグローバルネットワークへの展開
```

### まだエラーが出る場合:

1. **Root directory** が本当に空白か再確認
2. ブラウザのキャッシュをクリア
3. PC ブラウザで設定を試す

---

## 📋 次のステップ（ビルド成功後）

### 1. 環境変数の設定

**Settings** → **Environment variables** で以下を追加:

```
Variable name: GEMINI_API_KEY
Value: AIzaSyA-KolgF1yF1wUI2R8xNHmQCjIaHqo2SMM
Environment: Production & Preview

Variable name: WORDPRESS_SITE_URL
Value: https://joseikin-insight.com
Environment: Production & Preview

Variable name: JWT_SECRET
Value: your_jwt_secret_key_here
Environment: Production & Preview

Variable name: NODE_VERSION
Value: 18
Environment: Production & Preview
```

### 2. D1 データベースのバインド

**Settings** → **Functions** → **D1 database bindings** で:

```
Variable name: DB
D1 database: grants-db（新規作成が必要な場合があります）
```

### 3. データベースマイグレーション

ローカルで実行:

```bash
cd /home/user/webapp
npx wrangler d1 migrations apply grants-db --remote
```

### 4. 初回データ同期

デプロイ完了後、以下の URL にアクセス:

```
https://grant-matching.pages.dev/api/sync/wordpress?max_pages=80
```

これで 8,000 件の助成金データが同期されます。

---

## 🆘 トラブルシューティング

### 「Internal error prevented the form from submitting」エラー

- **原因**: スマートフォンブラウザの互換性問題
- **解決策**: PC/デスクトップブラウザを使用

### Root directory フィールドが空白にできない

- **解決策**: `.` （ドット一文字）を入力

### ビルドは成功するが動作しない

- 環境変数が正しく設定されているか確認
- D1 データベースがバインドされているか確認

---

## 📞 サポート情報

**現在のプロジェクト設定:**
- GitHub リポジトリ: https://github.com/[あなたのユーザー名]/webapp
- デプロイブランチ: `main`
- ビルドコマンド: `npm run build`
- 出力ディレクトリ: `dist`
- Root directory: （空白）

**参考ドキュメント:**
- `DEPLOYMENT_GUIDE.md` - 詳細なデプロイメントガイド
- `QUICK_START.md` - 5分間クイックスタートガイド
- `SYSTEM_EXPLANATION.md` - システムアーキテクチャ説明

---

**最終更新**: 2025-11-06  
**バージョン**: v1.0
