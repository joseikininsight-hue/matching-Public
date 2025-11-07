# ACFフィールド同期ステータス

## 🔍 発見された問題

スクリーンショットで確認された問題:
1. **「object Object」表示** - データが正しくパースされていない
2. **「記載なし」が多数** - AMOUNT, DEADLINE, ORGANIZATION フィールドが空
3. **WordPressデータが正しく読み込まれていない**

## 🔧 実施した修正

### 1. WordPress同期コードの修正 (src/routes/wordpress.ts)

**問題**: ACFフィールドを取得していたが、データベースに保存していなかった

**修正内容**:
```typescript
// 修正前: 基本フィールドのみ保存
INSERT INTO grants (wordpress_id, title, content, excerpt, status, prefecture_name, ...)

// 修正後: 全ACFフィールドを含めて保存
INSERT INTO grants (
  wordpress_id, title, content, excerpt, status,
  url, organization, max_amount_display, max_amount_numeric,
  deadline_display, deadline_date, grant_target,
  eligible_expenses, required_documents, application_status,
  adoption_rate, difficulty_level, area_notes,
  subsidy_rate_detailed, prefecture_name, categories, tags, ...
)
```

**追加されたACFフィールド**:
- ✅ `organization` (実施組織)
- ✅ `max_amount_display` (最大助成額 - 表示用)
- ✅ `max_amount_numeric` (最大助成額 - 数値)
- ✅ `deadline_display` (締切 - 表示用)
- ✅ `deadline_date` (締切日 - 日付)
- ✅ `url` (公式URL)
- ✅ `grant_target` (対象者・対象事業)
- ✅ `eligible_expenses` (対象経費)
- ✅ `required_documents` (必要書類)
- ✅ `application_status` (申請ステータス)
- ✅ `adoption_rate` (採択率)
- ✅ `difficulty_level` (申請難易度)
- ✅ `area_notes` (地域に関する備考)
- ✅ `subsidy_rate_detailed` (補助率 - 詳細)

### 2. データベーススキーマの拡張

**新規マイグレーション**: `migrations/0004_add_acf_fields.sql`

```sql
ALTER TABLE grants ADD COLUMN url TEXT;
ALTER TABLE grants ADD COLUMN eligible_expenses TEXT;
ALTER TABLE grants ADD COLUMN required_documents TEXT;
ALTER TABLE grants ADD COLUMN adoption_rate TEXT;
ALTER TABLE grants ADD COLUMN difficulty_level TEXT;
ALTER TABLE grants ADD COLUMN area_notes TEXT;
ALTER TABLE grants ADD COLUMN subsidy_rate_detailed TEXT;
```

### 3. デプロイ済み

- ✅ コード修正完了
- ✅ GitHubへプッシュ完了 (commit: 9c143c8)
- ✅ Cloudflare Pages 自動デプロイ中

## ⚠️ 重要な注意事項

### ACFフィールドがREST APIで公開されているか確認が必要

WordPressのACFフィールドがREST APIで公開されていない可能性があります。

**確認方法**:
```bash
# WordPressの投稿1件を取得してACFフィールドを確認
curl "https://joseikin-insight.com/wp-json/wp/v2/grants/1" | jq '.acf'
```

**もしACFフィールドが空（`{}`）の場合**:
WordPress側でACFフィールドをREST APIに公開する設定が必要です。

**WordPress側の対応方法** (functions.phpに追加):
```php
// ACFフィールドをREST APIで公開
add_filter('acf/rest_api/grants/get_fields', function($data, $request, $post_object) {
    return $data;
}, 10, 3);

// または、すべてのACFフィールドをREST APIで有効化
add_filter('acf/settings/rest_api_enabled', '__return_true');
```

## 🔄 次のステップ

### 1. 即座に実行可能: データ再同期

新しいコードでWordPressデータを再同期する必要があります:

```bash
# 全データを再同期（約8,000件）
cd /home/user/webapp
bash resync_with_acf_fields.sh
```

このスクリプトは:
- 新しい同期コードを使用
- 全ACFフィールドを含めてデータベースに保存
- 80ページ × 100件/ページ = 約8,000件を処理
- 進捗状況を表示

### 2. WordPress側で確認（必要に応じて）

ACFフィールドがREST APIで公開されているか確認:

```bash
# 1件のデータを取得してACFフィールドを確認
curl "https://joseikin-insight.com/wp-json/wp/v2/grants/1" | grep -o '"acf":{[^}]*}'
```

もし `"acf":{}` (空) の場合は、WordPress側の設定が必要です。

### 3. デプロイ後の確認

数分後、以下を確認:
1. https://matching-public.pages.dev にアクセス
2. マッチング結果の助成金カードを確認
3. 「記載なし」が減り、実際のデータが表示されているか確認

## 📊 現在の同期状況

- **バックグラウンド同期**: 実行中
- **同期済み件数**: 約2,846 / 7,949件 (旧コード)
- **必要な作業**: 新コードで再同期

## 🎯 期待される結果

再同期後:
- ✅ 「object Object」 → 実際の値（例: 「青森県」）
- ✅ 「記載なし」 → 実際の値（例: 「最大2,000万円」「記載なし」など）
- ✅ 組織名、締切、金額などが正しく表示される
- ✅ 公式URLへのリンクが正しく機能する

## 🚨 トラブルシューティング

### 問題: 再同期後も「記載なし」が多い

**原因**: WordPress側でACFフィールドがREST APIで公開されていない

**解決策**: WordPress管理画面で以下を確認:
1. ACF設定 → REST API を有効化
2. functions.php に公開設定を追加
3. 再度データ同期を実行

### 問題: 一部のフィールドだけ空

**原因**: WordPress側でそのフィールドに値が入力されていない

**解決策**: WordPressの投稿編集画面で値を入力し、再同期

---

**最終更新**: 2025-11-07 04:00 UTC
**コミット**: 9c143c8
**デプロイステータス**: 自動デプロイ中
