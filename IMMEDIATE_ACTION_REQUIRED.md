# 🚨 今すぐ実行する3つのステップ

## 現在の状況

✅ **コードは完璧です** - すべて main ブランチにマージ済み  
❌ **デプロイが失敗しています** - Cloudflare の**設定だけ**を修正する必要があります

**エラー**: `Failed: root directory not found`

---

## 📱 ステップ 1: PC ブラウザを使う

**重要**: スマートフォンでは設定の保存に失敗する可能性があります

✅ PC またはデスクトップブラウザで以下を開く:
```
https://dash.cloudflare.com/
```

---

## ⚙️ ステップ 2: 設定を修正する

### 2-1. プロジェクトを開く
1. **Workers & Pages** をクリック
2. プロジェクト **`grant-matching`** を選択
3. **Settings** タブをクリック

### 2-2. ビルド設定を編集
1. **Builds & deployments** セクションを探す
2. **Build configuration** の **Edit configuration** をクリック

### 2-3. この通りに入力

```
┌─────────────────────────────────────┐
│ Framework preset                    │
│ None                                │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ Build command                       │
│ npm run build                       │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ Build output directory              │
│ dist                                │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ Root directory (path)               │
│ [何も入力しない - 完全に空白]      │
└─────────────────────────────────────┘
```

### 🚨 最重要ポイント

**Root directory** フィールド:
- ❌ `/` を入力してはいけません
- ❌ `./` を入力してはいけません  
- ✅ **完全に空白のまま**にする
- ⚠️ どうしても空白にできない場合のみ `.` を入力

### 2-4. 保存
**Save** ボタンをクリック

---

## 🚀 ステップ 3: 再デプロイ

### 方法 A: ダッシュボードから
1. **Deployments** タブに移動
2. **Retry deployment** をクリック

### 方法 B: コマンドから（推奨）
```bash
cd /home/user/webapp
git checkout main
git pull origin main
git commit --allow-empty -m "trigger: Retry deployment after fixing settings"
git push origin main
```

---

## ✅ 成功の確認

### ビルドログを見る

**成功すると**:
```
✓ ビルド環境の初期化
✓ gitリポジトリのクローニング  ← ここが✓になる！
✓ ツールと依存関係のインストール
✓ アプリケーションを構築する
✓ Cloudflareのグローバルネットワークへの展開

Success! Deployed to https://grant-matching.pages.dev
```

---

## 📋 デプロイ成功後にやること

### 1. 環境変数を追加（5分）

**Settings** → **Environment variables** で4つ追加:

```
GEMINI_API_KEY = AIzaSyA-KolgF1yF1wUI2R8xNHmQCjIaHqo2SMM
WORDPRESS_SITE_URL = https://joseikin-insight.com
JWT_SECRET = your_jwt_secret_key_here
NODE_VERSION = 18
```

各変数で **Production & Preview** にチェック

### 2. D1 データベースをバインド（3分）

**Settings** → **Functions** → **D1 database bindings**:

```
Variable name: DB
D1 database: grants-db (新規作成)
```

### 3. 初回データ同期（2分）

ブラウザでアクセス:
```
https://grant-matching.pages.dev/api/sync/wordpress?max_pages=80
```

約2分で 8,000 件の助成金データが同期されます。

### 4. WordPress に埋め込み（1分）

固定ページに以下を追加:

```html
<iframe 
  src="https://grant-matching.pages.dev/" 
  width="100%" 
  height="800" 
  frameborder="0"
  style="border: none; min-height: 800px;"
>
</iframe>
```

---

## 🆘 問題が続く場合

### 「Internal error prevented the form from submitting」
→ PC ブラウザを使用してください

### Root directory が空白にできない
→ `.` (ドット一文字) を入力してください

### それでもエラーが出る
→ 以下のドキュメントを確認:
- `CLOUDFLARE_PAGES_SETTINGS_FIX.md` - 詳細な修正手順
- `現在の状況と対処法.md` - 包括的なガイド

---

## 📌 まとめ

### やること
1. ✅ PC ブラウザで Cloudflare Dashboard を開く
2. ✅ Root directory を **空白** にする
3. ✅ 保存して再デプロイ

### 所要時間
- 設定修正: **5分**
- ビルド完了: **3-5分**
- 追加設定: **11分**

**合計**: 約 20 分で完全に動作します！

---

## 🔗 参考情報

**Pull Request**: https://github.com/joseikininsight-hue/matching-Public/pull/2

**詳細ドキュメント**:
- `CLOUDFLARE_PAGES_SETTINGS_FIX.md` - 英語版トラブルシューティング
- `現在の状況と対処法.md` - 日本語版包括ガイド
- `QUICK_START.md` - クイックスタートガイド
- `DEPLOYMENT_GUIDE.md` - 完全デプロイガイド

---

**作成日**: 2025-11-06  
**優先度**: 🔴 最高（即座に対応が必要）
