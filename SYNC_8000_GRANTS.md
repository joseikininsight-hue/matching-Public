# 📥 8000件の補助金データを同期する方法

## 現在の状況

- ✅ アプリケーション起動成功！
- ✅ データベーステーブル作成完了
- ⚠️ 補助金データ: 0件 → 8000件必要

## 方法1: 自動同期スクリプト（最も簡単）

以下のコマンドを**順番に**実行してください。各コマンドは約2分かかります。

### ステップ1: ページ1-10（1000件）

```bash
curl "https://matching-public.pages.dev/api/wordpress/sync?per_page=100&max_pages=10"
```

### ステップ2: ページ11-20（1000件）

```bash
curl "https://matching-public.pages.dev/api/wordpress/sync?page=11&per_page=100&max_pages=10"
```

### ステップ3: ページ21-30（1000件）

```bash
curl "https://matching-public.pages.dev/api/wordpress/sync?page=21&per_page=100&max_pages=10"
```

### ステップ4: ページ31-40（1000件）

```bash
curl "https://matching-public.pages.dev/api/wordpress/sync?page=31&per_page=100&max_pages=10"
```

### ステップ5: ページ41-50（1000件）

```bash
curl "https://matching-public.pages.dev/api/wordpress/sync?page=41&per_page=100&max_pages=10"
```

### ステップ6: ページ51-60（1000件）

```bash
curl "https://matching-public.pages.dev/api/wordpress/sync?page=51&per_page=100&max_pages=10"
```

### ステップ7: ページ61-70（1000件）

```bash
curl "https://matching-public.pages.dev/api/wordpress/sync?page=61&per_page=100&max_pages=10"
```

### ステップ8: ページ71-80（1000件）

```bash
curl "https://matching-public.pages.dev/api/wordpress/sync?page=71&per_page=100&max_pages=10"
```

**合計**: 8000件のデータを取得

---

## 方法2: 自動化スクリプト（1つのコマンドで完了）

以下のスクリプトをコピーして、ターミナルで実行してください：

```bash
#!/bin/bash
# WordPress全データ同期スクリプト

echo "🚀 Starting WordPress sync for 8000+ grants..."

for page in 1 11 21 31 41 51 61 71 81; do
  echo "📥 Syncing pages ${page}-$((page+9))..."
  response=$(curl -s "https://matching-public.pages.dev/api/wordpress/sync?page=${page}&per_page=100&max_pages=10")
  echo "✅ Response: $response"
  sleep 5
done

echo "🎉 Sync complete! Checking total..."
curl -s "https://matching-public.pages.dev/api/test/db-status" | jq '.data.total_grants'
```

**使い方:**
1. 上記をコピーして `sync_all.sh` として保存
2. `chmod +x sync_all.sh` で実行権限を付与
3. `./sync_all.sh` で実行

---

## 方法3: Cloudflare Dashboard経由（手動）

Cloudflare Dashboardからも同期できます：

1. https://dash.cloudflare.com → Workers & Pages
2. matching-public プロジェクトを開く
3. Functions タブで実行

---

## 確認方法

同期後、以下のコマンドでデータ件数を確認：

```bash
curl "https://matching-public.pages.dev/api/test/db-status"
```

期待される結果:
```json
{
  "success": true,
  "data": {
    "total_grants": 8000
  }
}
```

---

## 注意事項

### ACFフィールドについて

現在、WordPressのACF（Advanced Custom Fields）がREST APIで公開されていないため、以下の情報のみ取得されます：

- ✅ タイトル（title）
- ✅ 本文（content）
- ✅ 抜粋（excerpt）
- ✅ WordPress ID
- ✅ カテゴリ・タクソノミー
- ❌ ACFカスタムフィールド（組織名、金額、締切など）

### ACFフィールドを有効にする方法

WordPress管理画面で：

1. **プラグイン** → **新規追加**
2. 「**ACF to REST API**」を検索してインストール
3. または、ACFフィールドグループ設定で「**Show in REST API**」を有効化

これにより、次回の同期で完全なデータが取得されます。

---

## トラブルシューティング

### エラー: "WordPress API error"

- WordPress側が一時的にダウンしている可能性
- 5分待ってから再試行

### エラー: "Database insert failed"

- D1データベースの容量制限に達した可能性
- Cloudflare Dashboardで確認

### 同期が遅い

- 正常です。8000件は時間がかかります
- バックグラウンドで実行させてください

---

## 次のステップ

データ同期が完了したら：

1. ✅ アプリケーションをリロード
2. ✅ 質問に答える
3. ✅ 補助金推薦を受ける

**完璧に動作するはずです！** 🎉
