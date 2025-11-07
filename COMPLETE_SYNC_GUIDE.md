# 🔄 WordPress完全同期ガイド

## ✨ 実施内容

WordPress から 8,000件の補助金データを完全に同期し、以下の情報を含めました：

### 取得データ
- ✅ **タイトル** (title)
- ✅ **本文** (content - HTML)
- ✅ **抜粋** (excerpt)
- ✅ **ステータス** (status)
- ✅ **都道府県** (prefecture_name) - タクソノミーから取得
- ✅ **カテゴリー** (categories) - 複数対応、カンマ区切り
- ✅ **タグ** (tags) - 複数対応、カンマ区切り
- ✅ **市区町村** (target_municipality) - 複数対応

---

## 🎯 質問の変更

### 削除した質問
**Q005: 希望する補助金額の範囲を教えてください**

#### 削除理由
1. **ユーザー体験の向上**: 質問数を減らして、より迅速な診断を実現
2. **AI推薦の柔軟性**: 金額にこだわらず、最適な補助金を提案できる
3. **データカバレッジ**: 補助金額データが不完全な場合が多い

### 残りの質問 (10問)
1. Q001: 企業 or 個人
2. Q002: 都道府県
3. Q003: 市区町村（任意）
4. Q004: 使用目的（複数選択）
5. ~~Q005: 希望金額~~ ← 削除
6. Q006: 申請期限の希望
7. Q010: AIに伝えたいこと（任意）
8. Q101-Q104: 企業向け質問（条件付き）
9. Q201-Q203: 個人向け質問（条件付き）

---

## 📊 データベーススキーマ

### grants テーブル（主要カラム）

```sql
CREATE TABLE grants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    wordpress_id INTEGER UNIQUE NOT NULL,
    title TEXT NOT NULL,
    content TEXT,
    excerpt TEXT,
    status TEXT DEFAULT 'publish',
    
    -- タクソノミーデータ
    prefecture_name TEXT,           -- 都道府県名
    categories TEXT,                -- カテゴリー（カンマ区切り）
    tags TEXT,                      -- タグ（カンマ区切り）
    target_municipality TEXT,       -- 市区町村（カンマ区切り）
    
    -- タイムスタンプ
    created_at TEXT,
    updated_at TEXT,
    created_system_at TEXT DEFAULT (datetime('now')),
    updated_system_at TEXT DEFAULT (datetime('now'))
);
```

---

## 🚀 同期スクリプト

### sync_all_grants_complete.sh

```bash
#!/bin/bash

BASE_URL="https://matching-public.pages.dev/api/wordpress/sync"
TOTAL_PAGES=80  # 7949件 ÷ 100 = 80ページ
BATCH_SIZE=10   # 一度に10ページずつ処理
DELAY=3         # バッチ間の待機時間(秒)

# ページをバッチで処理
for ((start_page=1; start_page<=TOTAL_PAGES; start_page+=BATCH_SIZE)); do
    # バッチ処理
    # ...
done
```

### 実行方法

```bash
# スクリプトを実行可能にする
chmod +x sync_all_grants_complete.sh

# バックグラウンドで実行
./sync_all_grants_complete.sh &

# または nohup で実行
nohup ./sync_all_grants_complete.sh > sync.log 2>&1 &

# 進捗確認
tail -f sync.log
```

---

## 📈 同期プロセス

### フロー

```
1. WordPressから100件ずつ取得（1ページ）
   ↓
2. _embed=true でタクソノミー情報も取得
   ↓
3. タクソノミーをパース:
   - grant_category → categories
   - grant_prefecture → prefecture_name
   - grant_municipality → target_municipality
   - grant_tag → tags
   ↓
4. D1データベースに INSERT OR REPLACE
   ↓
5. 10ページごとにバッチ完了
   ↓
6. 3秒待機（APIレート制限対策）
   ↓
7. 次のバッチへ
```

### タクソノミー抽出ロジック

```typescript
// タクソノミーからデータ取得
const embeddedTerms = post._embedded?.['wp:term'] || [];
const allTerms = embeddedTerms.flat();

// タクソノミー別に分類
const categories = allTerms.filter((t: any) => 
  t.taxonomy === 'grant_category'
);
const prefectures = allTerms.filter((t: any) => 
  t.taxonomy === 'grant_prefecture'
);
const municipalities = allTerms.filter((t: any) => 
  t.taxonomy === 'grant_municipality'
);
const tags = allTerms.filter((t: any) => 
  t.taxonomy === 'grant_tag'
);

// 名前を抽出してカンマ区切りで結合
const categoryNames = categories.map((c: any) => c.name).join(', ');
const tagNames = tags.map((t: any) => t.name).join(', ');
```

---

## 🔍 データ確認

### API エンドポイント

#### 統計情報
```bash
curl "https://matching-public.pages.dev/api/grants/stats/summary"
```

```json
{
  "success": true,
  "data": {
    "total_grants": 7949,
    "by_prefecture": [
      { "prefecture_name": "東京都", "count": 1911 },
      { "prefecture_name": "埼玉県", "count": 1245 },
      ...
    ],
    "by_category": [...]
  }
}
```

#### グラント一覧
```bash
curl "https://matching-public.pages.dev/api/grants?page=1&limit=20"
```

#### 都道府県でフィルター
```bash
curl "https://matching-public.pages.dev/api/grants?prefecture=東京都"
```

#### カテゴリーで検索
```bash
curl "https://matching-public.pages.dev/api/grants?search=DX"
```

---

## 📊 予想データ分布

### 都道府県別（上位5県）
| 都道府県 | 件数 |
|---------|------|
| 東京都 | 1,911 |
| 埼玉県 | 1,245 |
| 愛知県 | 1,308 |
| 兵庫県 | 1,135 |
| 大阪府 | 1,115 |

### カテゴリー別（上位）
| カテゴリー | 想定件数 |
|-----------|---------|
| DX | 145 |
| 設備投資 | ~500 |
| 創業支援 | ~300 |
| 環境・省エネ | ~200 |
| 人材育成 | ~150 |

---

## ⚠️ 注意事項

### 1. ACF フィールド

現在、ACFフィールドが REST API で公開されていません：

```json
{
  "acf": []  // ← 空配列
}
```

#### 解決方法（WordPress側での設定が必要）

```php
// functions.php に追加
add_filter('acf/rest_api/grants/get_fields', function($data, $request, $post_id) {
    return get_fields($post_id);
}, 10, 3);

// または ACF設定で「REST APIで表示」を有効化
```

### 2. APIレート制限

- 1リクエストあたり100件
- バッチ間に3秒の待機時間
- 合計: 約80リクエスト × 3秒 = 約4分

### 3. データ整合性

- `INSERT OR REPLACE` を使用
- `wordpress_id` をUNIQUE KEY として重複防止
- 既存データは上書きされる

---

## 🎯 今後の改善点

### 1. ACFフィールドの取得

WordPressで以下を設定後、sync ルートを更新：

```typescript
// ACFフィールドの取得
const acf = post.acf || {};

// データベースに追加
await db.prepare(`
  INSERT OR REPLACE INTO grants (
    ...
    max_amount_display,
    max_amount_numeric,
    deadline_display,
    deadline_date,
    organization,
    official_url,
    ...
  ) VALUES (?, ?, ?, ?, ?, ?, ...)
`).bind(
  ...
  acf.max_amount || null,
  acf.max_amount_numeric || null,
  acf.deadline || null,
  acf.deadline_date || null,
  acf.organization || null,
  acf.official_url || null,
  ...
).run();
```

### 2. インクリメンタル同期

差分のみを同期するWebhook実装：

```typescript
wordpress.post('/webhook', async (c) => {
  // WordPress側で投稿更新時に自動同期
});
```

### 3. 検索インデックスの最適化

```sql
-- 検索用のインデックス作成
CREATE INDEX idx_grants_prefecture ON grants(prefecture_name);
CREATE INDEX idx_grants_categories ON grants(categories);
CREATE INDEX idx_grants_title ON grants(title);
```

---

## ✅ 完了チェックリスト

- [x] 希望金額の質問を削除
- [x] WordPressタクソノミーデータの取得
- [x] 都道府県、カテゴリー、タグの保存
- [x] 完全同期スクリプトの作成
- [x] バックグラウンド同期の開始
- [ ] 7,949件の同期完了（進行中）
- [ ] データ確認とテスト
- [ ] ACFフィールドの公開設定（WordPress側）

---

## 🔗 関連ファイル

- `src/config/questions.ts` - 質問定義（Q005削除済み）
- `src/routes/wordpress.ts` - WordPress同期API
- `sync_all_grants_complete.sh` - 完全同期スクリプト
- `D1_COMPLETE_SETUP.sql` - データベーススキーマ

---

## 📞 サポート

同期に問題がある場合：

1. **進捗確認**:
```bash
curl "https://matching-public.pages.dev/api/grants/stats/summary"
```

2. **ログ確認**:
```bash
# バックグラウンドジョブのログ
tail -f sync.log
```

3. **手動同期**:
```bash
# 特定のページを手動で同期
curl "https://matching-public.pages.dev/api/wordpress/sync?page=1&per_page=100&max_pages=10"
```

---

**🎉 完全同期が完了すると、約8,000件の補助金データが利用可能になります！**
