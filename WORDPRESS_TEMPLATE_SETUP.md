# 📄 WordPressテンプレート設置ガイド

## 🎯 page-subsidy-diagnosis.php の使い方

### ステップ1: ファイルをアップロード

#### FTP/SFTPでアップロード

1. **page-subsidy-diagnosis.php** をダウンロード
2. FTPクライアント（FileZilla等）でWordPressサーバーに接続
3. `/wp-content/themes/your-theme/` にアップロード

#### WordPress管理画面からアップロード（推奨）

1. WordPressダッシュボード → **外観** → **テーマファイルエディター**
2. 右側の「テーマファイル」から **新規ファイル**
3. ファイル名: `page-subsidy-diagnosis.php`
4. 内容をコピー＆ペースト
5. **ファイルを更新**

---

### ステップ2: 固定ページを作成

1. **固定ページ** → **新規追加**
2. **タイトル**: 「補助金診断」（任意）
3. **右側のパネル** → **ページ属性** → **テンプレート**
4. **「補助金診断ページ」** を選択
5. **公開**

---

### ステップ3: 完了！

作成したページにアクセスすると、美しいデザインでAI補助金マッチングが表示されます！

**例**: `https://joseikin-insight.com/subsidy-diagnosis/`

---

## 🎨 テンプレートの特徴

### 1. 美しいヒーローセクション
- グラデーション背景
- キャッチーなタイトルと説明文
- アイコン：💡

### 2. 特徴カード（4つ）
- 🤖 **AI診断**: 8,000件以上のデータから最適選定
- ⚡ **最短3分**: 簡単な質問に答えるだけ
- 🎯 **高精度マッチング**: 事業内容に合わせた提案
- 🆓 **完全無料**: 登録不要、何度でも利用可能

### 3. レスポンシブデザイン
- PC、タブレット、スマホに完全対応
- 80%の高さでアプリを表示
- スマホでは100%に調整

### 4. ローディングアニメーション
- iframe読み込み中にスピナー表示
- 読み込み完了で自動非表示

### 5. スムーススクロール
- CTAボタンクリックで診断エリアへ移動
- 滑らかなアニメーション

### 6. エラーハンドリング
- iframe読み込み失敗時に代替リンク表示
- ユーザーフレンドリーなエラーメッセージ

---

## ⚙️ カスタマイズ方法

### 色を変更

```css
/* ヒーローセクションの背景色 */
.subsidy-diagnosis-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    /* お好みの色に変更 */
}

/* CTAボタンの色 */
.cta-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    /* お好みの色に変更 */
}
```

### タイトルを変更

```php
<h1>💡 AI補助金マッチング</h1>
<!-- ↓ 変更例 -->
<h1>🎯 あなたに最適な補助金を見つけよう</h1>
```

### 特徴カードを変更

```php
<div class="feature-card">
    <div class="feature-icon">🤖</div>
    <h3 class="feature-title">AI診断</h3>
    <p class="feature-description">説明文</p>
</div>
```

アイコンや説明文を自由に変更できます。

### iframe高さを調整

```css
.subsidy-diagnosis-iframe-wrapper {
    padding-bottom: 80%; /* 高さの割合 */
    /* 70%にすると低く、90%にすると高くなります */
}
```

---

## 🔧 高度なカスタマイズ

### フルスクリーンボタンを追加

テンプレートのCTAセクションに追加：

```html
<button onclick="toggleFullscreen()" class="cta-button" style="margin-left: 10px;">
    フルスクリーン表示
</button>
```

すでにJavaScript関数は実装済みです。

### 別ウィンドウで開くボタン

```html
<a href="https://matching-public.pages.dev/" target="_blank" class="cta-button">
    新しいタブで開く
</a>
```

### アナリティクス追跡

Google Analytics用：

```javascript
// iframe内のイベントを追跡
window.addEventListener('message', function(e) {
    if (e.origin === 'https://matching-public.pages.dev') {
        if (e.data.event === 'session_complete') {
            gtag('event', 'subsidy_diagnosis_complete', {
                'event_category': 'subsidy',
                'event_label': 'completion'
            });
        }
    }
});
```

---

## 📱 モバイル最適化

テンプレートは既にモバイル対応済みですが、さらに最適化するには：

```css
@media (max-width: 480px) {
    .subsidy-diagnosis-hero h1 {
        font-size: 1.5rem;
    }
    
    .subsidy-diagnosis-iframe-wrapper {
        padding-bottom: 120%; /* スマホでさらに高く */
    }
}
```

---

## 🎨 他のテーマとの統合

### Twenty Twenty-Fourテーマ

```php
// get_header()の後に追加
<div class="wp-block-group">
    <div class="wp-block-group__inner-container">
```

### Astraテーマ

```php
// ヒーローセクションの前に追加
<?php do_action('astra_content_before'); ?>
```

### Elementor Pro

Elementorでページを作成し、**カスタムコード**ウィジェットで使用：

```html
<iframe 
  src="https://matching-public.pages.dev/" 
  style="width: 100%; height: 800px; border: none;">
</iframe>
```

---

## 🐛 トラブルシューティング

### テンプレートが選択肢に表示されない

1. ファイル名が `page-subsidy-diagnosis.php` であることを確認
2. ファイルの先頭に `Template Name:` があることを確認
3. テーマを再度有効化

### iframeが表示されない

1. HTTPSで動作していることを確認
2. ブラウザのコンソールでエラーをチェック
3. `_headers` ファイルが正しく設定されているか確認

### スタイルが崩れる

テーマのCSSと競合している可能性があります：

```css
/* より強い優先度で上書き */
.subsidy-diagnosis-container * {
    box-sizing: border-box !important;
}
```

---

## 📊 パフォーマンス最適化

### 遅延読み込み

```html
<iframe 
  loading="lazy"  <!-- 既に実装済み -->
  src="https://matching-public.pages.dev/">
</iframe>
```

### キャッシュ設定

WordPressキャッシュプラグイン（WP Super Cache等）で、このページをキャッシュから除外：

```
/subsidy-diagnosis/
```

---

## ✅ 完了チェックリスト

- [ ] `page-subsidy-diagnosis.php` をテーマフォルダにアップロード
- [ ] 固定ページを作成
- [ ] テンプレート「補助金診断ページ」を選択
- [ ] ページを公開
- [ ] 実際にアクセスして動作確認
- [ ] スマホでも表示確認
- [ ] 診断を実際に試してみる

---

## 🎉 完成！

これで、WordPressサイトに美しいAI補助金マッチングページが完成しました！

**何か問題があれば、WORDPRESS_EMBED_GUIDE.md も参照してください。**
