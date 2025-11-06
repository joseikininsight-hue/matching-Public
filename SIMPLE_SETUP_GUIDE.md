# 🚀 超簡単セットアップガイド（コピペだけ！）

## 📋 現在の状況

✅ コードは完璧  
✅ Cloudflare Pages プロジェクト作成済み  
⏳ D1 データベースだけ未設定  

---

# 🎯 3ステップで完了（所要時間: 5分）

## ステップ 1: デプロイ完了を確認

### Cloudflare Dashboard を開く

https://dash.cloudflare.com/

1. **Workers & Pages** をクリック
2. プロジェクト **matching-public** を開く
3. **Deployments** タブを確認

### 成功していれば

```
✅ Latest deployment: Success
URL: https://matching-public-xxx.pages.dev
```

このURLをメモしてください！

---

## ステップ 2: D1 データベースを作成

### 2-1. D1 セクションに移動

1. Cloudflare Dashboard トップに戻る
2. 左メニューから **Workers & Pages** を探す
3. その中の **D1** をクリック

### 2-2. 新しいデータベースを作成

1. **Create database** ボタンをクリック
2. Database name: `grants-db` と入力
3. **Create** をクリック

### 2-3. Database ID をコピー

作成されたデータベースをクリックすると、以下のような画面が表示されます：

```
Database ID: 12345678-abcd-1234-efgh-123456789abc
```

この **Database ID** を**コピー**してください（重要！）

---

## ステップ 3: D1 をプロジェクトにバインド

### 3-1. Pages プロジェクトに戻る

1. **Workers & Pages** に戻る
2. プロジェクト **matching-public** を開く
3. **Settings** タブをクリック

### 3-2. D1 バインディングを追加

1. **Functions** セクションを探す
2. **D1 database bindings** を見つける
3. **Add binding** ボタンをクリック

### 3-3. 設定を入力

```
┌────────────────────────────────┐
│ Variable name                  │
│ DB                             │
└────────────────────────────────┘

┌────────────────────────────────┐
│ D1 database                    │
│ grants-db を選択               │
└────────────────────────────────┘
```

4. **Save** をクリック

---

# 🎉 完了！

## アプリケーションにアクセス

デプロイ URL を開く：

```
https://matching-public-xxx.pages.dev
```

---

# 📊 初回データ同期（WordPress から 8,000 件）

## 同期URLにアクセス

ブラウザで以下の URL を開く：

```
https://matching-public-xxx.pages.dev/api/sync/wordpress?max_pages=80
```

**処理時間**: 約 2-3 分  
**同期件数**: 約 8,000 件の助成金データ

### 成功した場合

```json
{
  "success": true,
  "total_synced": 7949,
  "pages_processed": 80,
  "time_taken": "2.3 minutes"
}
```

---

# ✅ 動作確認

## ヘルスチェック

```
https://matching-public-xxx.pages.dev/api/health
```

**期待される応答**:
```json
{
  "status": "healthy",
  "timestamp": "2025-11-06T12:00:00.000Z",
  "database": "connected",
  "wordpress": "connected"
}
```

## 助成金一覧

```
https://matching-public-xxx.pages.dev/api/grants
```

**期待される応答**:
```json
{
  "data": [...],
  "total": 7949,
  "page": 1
}
```

---

# 🌐 WordPress に埋め込み

## iframe コード

```html
<iframe 
  src="https://matching-public-xxx.pages.dev/" 
  width="100%" 
  height="800" 
  frameborder="0"
  style="border: none; min-height: 800px;"
  title="助成金マッチングシステム"
>
</iframe>
```

## 埋め込み手順

1. WordPress 管理画面にログイン
2. 固定ページを作成または編集
3. **カスタム HTML** ブロックを追加
4. 上記の iframe コードを貼り付け
5. URL を実際のデプロイ URL に置き換え
6. ページを公開

---

# 🆘 トラブルシューティング

## Q1: D1 バインディングが追加できない

**症状**: 「このプロジェクトのバインディングは wrangler.toml で管理されています」

**解決策**: 
- 最新のデプロイが完了していない可能性
- **Deployments** タブで最新のデプロイが成功しているか確認
- 失敗している場合は、ビルドログを確認

## Q2: データ同期でエラー

**症状**: `Database not configured` エラー

**原因**: D1 バインディングが正しく設定されていない

**解決策**:
1. Settings → Functions → D1 database bindings を確認
2. Variable name が `DB` (大文字) になっているか確認
3. Database が `grants-db` にバインドされているか確認

## Q3: WordPress データが同期されない

**症状**: `Failed to fetch from WordPress` エラー

**確認事項**:
1. WordPress サイトが稼働しているか
2. REST API が有効か（以下で確認）:
   ```
   https://joseikin-insight.com/wp-json/wp/v2/grants
   ```
3. 環境変数 `WORDPRESS_SITE_URL` が正しく設定されているか

---

# 📚 次のステップ

## カスタマイズ

- デザインの調整: `src/routes/index.html` を編集
- ロジックの変更: `src/routes/*.ts` を編集
- データベーススキーマ: `migrations/*.sql` を編集

## 管理機能

- 管理画面: `/admin` (ユーザー名: admin, パスワード: keishi0804)
- ログ確認: Cloudflare Dashboard → プロジェクト → Logs
- データベース管理: Cloudflare Dashboard → D1 → grants-db

---

# 🎯 まとめ

| ステップ | 所要時間 | ステータス |
|---------|---------|----------|
| 1. デプロイ確認 | 1分 | ⏳ 待機中 |
| 2. D1 作成 | 2分 | ⏹️ 未実施 |
| 3. バインディング | 1分 | ⏹️ 未実施 |
| 4. データ同期 | 3分 | ⏹️ 未実施 |

**合計**: 約 7 分

---

**これだけで完全に動作します！** 🚀✨

**作成日**: 2025-11-06  
**対象プロジェクト**: matching-public
