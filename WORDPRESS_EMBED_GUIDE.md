# WordPressにAI補助金マッチングを埋め込む方法

## 方法1: iframeで埋め込む（最も簡単）

### 手順

1. WordPress管理画面にログイン
2. **固定ページ** → **新規追加**（または既存ページを編集）
3. **カスタムHTML**ブロックを追加
4. 以下のコードを貼り付け

```html
<div style="width: 100%; max-width: 1200px; margin: 0 auto;">
  <iframe 
    src="https://matching-public.pages.dev/" 
    style="width: 100%; height: 800px; border: none; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"
    title="AI補助金マッチング"
    loading="lazy">
  </iframe>
</div>
```

### レスポンシブ対応版（推奨）

```html
<div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 20px;">
  <div style="position: relative; padding-bottom: 80%; height: 0; overflow: hidden;">
    <iframe 
      src="https://matching-public.pages.dev/" 
      style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"
      title="AI補助金マッチング"
      loading="lazy">
    </iframe>
  </div>
</div>
```

---

## 方法2: ショートコードで埋め込む（高度）

### functions.phpに追加

```php
// AI補助金マッチング埋め込みショートコード
function joseikin_matching_shortcode($atts) {
    $atts = shortcode_atts(array(
        'height' => '800px',
        'width' => '100%',
    ), $atts);
    
    $output = '<div class="joseikin-matching-wrapper" style="width: ' . esc_attr($atts['width']) . '; max-width: 1200px; margin: 0 auto;">';
    $output .= '<iframe ';
    $output .= 'src="https://matching-public.pages.dev/" ';
    $output .= 'style="width: 100%; height: ' . esc_attr($atts['height']) . '; border: none; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);" ';
    $output .= 'title="AI補助金マッチング" ';
    $output .= 'loading="lazy">';
    $output .= '</iframe>';
    $output .= '</div>';
    
    return $output;
}
add_shortcode('joseikin_matching', 'joseikin_matching_shortcode');
```

### 使い方

固定ページやブロックエディタで以下を記述：

```
[joseikin_matching]
```

または高さを指定：

```
[joseikin_matching height="1000px"]
```

---

## 方法3: Elementor/他のページビルダー

### Elementorの場合

1. **HTMLウィジェット**をドラッグ
2. 以下のコードを貼り付け

```html
<iframe 
  src="https://matching-public.pages.dev/" 
  style="width: 100%; height: 800px; border: none; border-radius: 8px;"
  title="AI補助金マッチング">
</iframe>
```

### Gutenberg（ブロックエディタ）

1. **/html** と入力して「カスタムHTML」ブロックを追加
2. 上記のiframeコードを貼り付け

---

## 方法4: 専用の固定ページテンプレート（最も統合的）

### 1. テーマフォルダに新しいテンプレートを作成

`wp-content/themes/your-theme/template-matching.php`:

```php
<?php
/**
 * Template Name: AI補助金マッチング
 */

get_header(); ?>

<style>
.matching-fullwidth {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}
.matching-iframe {
    width: 100%;
    height: calc(100vh - 200px);
    min-height: 800px;
    border: none;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style>

<div class="matching-fullwidth">
    <iframe 
        src="https://matching-public.pages.dev/" 
        class="matching-iframe"
        title="AI補助金マッチング"
        loading="eager">
    </iframe>
</div>

<?php get_footer(); ?>
```

### 2. 固定ページで使用

1. 新しい固定ページを作成
2. **ページ属性** → **テンプレート** → **AI補助金マッチング** を選択
3. 公開

---

## 方法5: セクション埋め込み（記事内に配置）

### ブロック間に挿入

```html
<div style="margin: 40px 0; padding: 20px; background: #f5f5f5; border-radius: 12px;">
  <h3 style="text-align: center; margin-bottom: 20px;">あなたに最適な補助金を見つける</h3>
  <iframe 
    src="https://matching-public.pages.dev/" 
    style="width: 100%; height: 700px; border: none; border-radius: 8px; background: white;"
    title="AI補助金マッチング">
  </iframe>
</div>
```

---

## セキュリティとパフォーマンス

### Content Security Policy (CSP)

iframeを使用する場合、WordPressのセキュリティ設定で許可する必要があります。

`functions.php`に追加：

```php
add_filter('wp_headers', function($headers) {
    $headers['Content-Security-Policy'] = "frame-src 'self' https://matching-public.pages.dev;";
    return $headers;
});
```

### 遅延読み込み

ページ速度を改善するため、`loading="lazy"` を使用（上記の例ではすでに含まれています）

---

## カスタマイズオプション

### 高さを自動調整

親ページとiframe間の通信を使用：

```html
<script>
window.addEventListener('message', function(e) {
    if (e.origin === 'https://matching-public.pages.dev') {
        if (e.data.height) {
            document.getElementById('matching-iframe').style.height = e.data.height + 'px';
        }
    }
});
</script>

<iframe 
  id="matching-iframe"
  src="https://matching-public.pages.dev/" 
  style="width: 100%; border: none;">
</iframe>
```

### テーマに合わせたスタイリング

```html
<style>
.joseikin-matching-container {
    background: var(--wp--preset--color--background);
    padding: 40px 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.joseikin-matching-title {
    font-size: 2rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 30px;
    color: var(--wp--preset--color--primary);
}
</style>

<div class="joseikin-matching-container">
    <h2 class="joseikin-matching-title">💡 AI補助金マッチング</h2>
    <iframe 
        src="https://matching-public.pages.dev/" 
        style="width: 100%; height: 800px; border: none; border-radius: 8px;">
    </iframe>
</div>
```

---

## トラブルシューティング

### iframeが表示されない

1. **X-Frame-Options**: Cloudflare Pagesの設定でiframe埋め込みを許可
2. **HTTPS**: WordPressもHTTPSである必要があります
3. **ブラウザのセキュリティ設定**: クッキーやJavaScriptを許可

### 高さが合わない

- `height: auto` は使えません
- 固定値（例: `800px`）または `calc(100vh - 200px)` を使用
- または上記の自動調整スクリプトを使用

---

## 推奨設定

**一番おすすめ**: 方法1の**レスポンシブ対応版**を使用

```html
<div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 20px;">
  <div style="position: relative; padding-bottom: 80%; height: 0; overflow: hidden;">
    <iframe 
      src="https://matching-public.pages.dev/" 
      style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"
      title="AI補助金マッチング"
      loading="lazy">
    </iframe>
  </div>
</div>
```

これで完璧に動作します！🎉
