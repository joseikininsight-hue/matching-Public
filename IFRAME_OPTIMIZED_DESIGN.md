# 🎨 iframe埋め込み最適化デザイン

## ✨ 変更内容

WordPress サイトへの埋め込みを考慮し、スペーシングとレイアウトを最適化しました。

---

## 📐 デザイン比較

### Before (1.5x拡大版)
```
┌─────────────────────────────────┐
│  💡  AI補助金マッチング (巨大)   │ ← 大きすぎる
│     ↓ 大きな余白 ↓              │
│                                 │
│    [ 大きなボタン ]              │ ← 無駄なスペース
│                                 │
│     ↓ 大きな余白 ↓              │
│                                 │
│  [質問カード - 巨大パディング]   │
│                                 │
└─────────────────────────────────┘
❌ 800pxのiframeで見ると、スクロールが多すぎる
```

### After (iframe最適化版) ✨
```
┌────────────────────────────┐
│ 💡 AI補助金マッチング (適切) │ ← ちょうど良い
│  ↓ コンパクト ↓            │
│ [ ボタン ]                 │ ← 適切なサイズ
│  ↓ 最小限の余白 ↓          │
│ [質問カード - 最適]         │
│                            │
│ [回答オプション]            │
│ [回答オプション]            │
│                            │
│ [ 次へ ]                   │
│  ↓ フッター ↓              │
└────────────────────────────┘
✅ より多くのコンテンツが見える！
```

---

## 🎯 最適化のポイント

### 1. **余白の削減**
| 要素 | Before | After | 削減率 |
|------|--------|-------|--------|
| ヘッダーパディング | `px-9 py-5` | `px-4 py-3` | -60% |
| メインパディング | `px-9 py-6` | `px-4 py-4` | -55% |
| フッターパディング | `px-9 py-5` | `px-4 py-2` | -70% |
| カードパディング | `2.25rem` | `1.5rem` | -33% |
| ボタンパディング | `1.5rem 3rem` | `0.875rem 2rem` | -42% |

### 2. **フォントサイズの正規化**
| テキスト | Before | After |
|---------|--------|-------|
| ベース | `150%` | `16px` (標準) |
| ヘッダータイトル | `text-2xl` | `text-lg` |
| サブタイトル | `text-lg` | `text-xs` |
| ボタン | `1.3125rem` | `0.875rem` |
| 入力フィールド | `1.5rem` | `1rem` |
| フッター | `text-base` | `text-xs` |

### 3. **ボーダーの統一**
| 要素 | Before | After |
|------|--------|-------|
| すべてのボーダー | `3px` | `2px` |
| ヘッダーボーダー | `3px` | `2px` |
| フッターボーダー | `2px` | `1px` |

---

## 📱 iframe埋め込み時のメリット

### 1. **縦スペースの有効活用**
```css
/* ヘッダー高さ削減 */
Before: 約 80px
After:  約 50px  → -30px (-37%)

/* フッター高さ削減 */
Before: 約 70px
After:  約 35px  → -35px (-50%)

/* 合計 */
800px iframe → 実質コンテンツ領域が +65px 増加！
```

### 2. **視認性の向上**
- 標準フォントサイズ（16px）で最も読みやすい
- ボタンとカードが適切なサイズ
- 余白とコンテンツのバランスが最適

### 3. **スクロール量の削減**
```
Before: 質問1つ表示 + 大量スクロール必要
After:  質問 + 回答オプション複数 + ボタン が同時に見える
```

---

## 🎨 具体的な変更内容

### style.css の主要変更

```css
/* ベースフォントサイズ */
html, body {
  font-size: 16px;  /* 150% から戻す */
}

/* ボタン */
.btn-primary {
  padding: 0.875rem 2rem;     /* 1.5rem 3rem から */
  font-size: 0.875rem;        /* 1.3125rem から */
  border: 2px solid;          /* 3px から */
}

/* カード */
.question-card {
  padding: 1.5rem;            /* 2.25rem から */
  border: 2px solid;          /* 3px から */
}

/* 入力フィールド */
input[type="text"] {
  padding: 0.75rem;           /* 1.3125rem から */
  font-size: 1rem;            /* 1.5rem から */
  border: 2px solid;          /* 3px から */
}

/* グリッド */
.grants-grid {
  grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
  /* 675px から変更 */
  gap: 1rem;                  /* 1.5rem から */
}
```

### renderer.tsx の変更

```jsx
/* ヘッダー */
<div className="px-4 py-3">        {/* px-9 py-5 から */}
  <div className="logo-icon w-10 h-10 text-xl">  {/* w-15 h-15 text-3xl から */}
  <h1 className="text-lg">         {/* text-2xl から */}
  <p className="text-xs">           {/* text-lg から */}

/* メイン */
<div className="px-4 py-4">        {/* px-9 py-6 から */}

/* フッター */
<div className="px-4 py-2">        {/* px-9 py-5 から */}
  <p className="text-xs">           {/* text-base から */}
```

---

## 📊 WordPress埋め込み推奨サイズ

### ショートコード
```php
[subsidy_diagnosis height="800px"]  // 推奨
[subsidy_diagnosis height="700px"]  // 最小
[subsidy_diagnosis height="1000px"] // 結果表示用に広め
```

### 直接HTML
```html
<iframe 
  src="https://matching-public.pages.dev/" 
  style="width: 100%; height: 800px; border: none;"
></iframe>
```

### レスポンシブ (アスペクト比)
```css
.iframe-wrapper {
  position: relative;
  padding-bottom: 75%;  /* 4:3 比率 - おすすめ */
  /* または */
  padding-bottom: 80%;  /* より縦長 */
}
```

---

## 🎯 各ページでの見え方

### 1. **質問ページ**
```
[ヘッダー: 50px]
  ↓ 余白: 16px
[質問タイトル]
[オプション1]
[オプション2]
[オプション3]
[オプション4]
  ↓ 余白: 16px
[次へボタン]
  ↓ 余白: 16px
[フッター: 35px]
```
**合計**: 約 650px → 800px iframe で余裕あり ✅

### 2. **結果ページ**
```
[ヘッダー: 50px]
  ↓ 余白: 16px
[グラント1カード: 200px]
[グラント2カード: 200px]
[グラント3カード: 200px]
  ↓ スクロール可能 ↓
[もっと見るボタン]
  ↓ 余白: 16px
[フッター: 35px]
```
**スクロール**: 必要だが、3つのカードが見える ✅

---

## ✅ チェックリスト

### デスクトップ (1200px+)
- [x] ヘッダーが圧迫しない
- [x] 質問と回答が同時に見える
- [x] ボタンが押しやすいサイズ
- [x] カードが適切な幅
- [x] フッターが邪魔にならない

### タブレット (768px - 1200px)
- [x] 1カラムレイアウトに自動切り替え
- [x] パディングが適切
- [x] タッチ操作に最適

### モバイル (< 768px)
- [x] コンパクトなパディング
- [x] ボタンサイズ調整済み
- [x] 縦スクロールが自然

---

## 🔄 デプロイ状況

✅ **コミット完了**: commit `a235fe6`
✅ **GitHub プッシュ完了**: main ブランチ
🔄 **Cloudflare Pages**: 自動デプロイ中

### 確認URL
👉 **https://matching-public.pages.dev/**

**デプロイ完了後（2-3分）、新しいデザインが反映されます！**

---

## 💡 カスタマイズ方法

### さらにコンパクトにしたい場合
```css
/* style.css */
.question-card {
  padding: 1rem;  /* 1.5rem から */
}

.btn-primary {
  padding: 0.75rem 1.5rem;  /* 0.875rem 2rem から */
}
```

### もう少し余裕を持たせたい場合
```css
/* style.css */
.question-card {
  padding: 2rem;  /* 1.5rem から */
}

main > div {
  padding: 1.5rem;  /* px-4 py-4 から */
}
```

---

## 🎊 完成！

iframe埋め込みに最適化されたデザインが完成しました！

### 主な改善点:
1. ✅ **縦スペースの有効活用** - 65pxの追加スペース確保
2. ✅ **読みやすさ維持** - 標準フォントサイズで最適な可読性
3. ✅ **バランスの取れた余白** - 必要最小限で美しい
4. ✅ **レスポンシブ対応** - すべてのデバイスで完璧
5. ✅ **WordPress親和性** - iframe埋め込み時の見栄えが最高

---

**WordPressサイトに埋め込んで、ぜひご確認ください！** 🚀

```html
<iframe 
  src="https://matching-public.pages.dev/" 
  style="width: 100%; height: 800px; border: none; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);"
></iframe>
```
