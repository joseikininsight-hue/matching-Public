# 🚀 Quick Start Guide - WordPress Page Template

## ⚡ 3 Steps to Launch

### Step 1: Upload Template File
```bash
# Copy this file to your WordPress theme directory:
page-subsidy-diagnosis.php
↓
wp-content/themes/your-theme/page-subsidy-diagnosis.php
```

### Step 2: Create New Page
1. Go to WordPress Admin → Pages → Add New
2. Enter page title: "補助金診断" or "AI補助金マッチング"
3. In the **Template** dropdown (right sidebar), select: **"補助金診断ページ"**
4. Click **Publish**

### Step 3: View Your Page
Visit the page URL and see your embedded AI grant matching app! 🎉

---

## 📱 What You'll Get

### Beautiful Landing Page with:
- 💜 **Gradient Hero Section** - Eye-catching purple gradient
- 🎯 **4 Feature Cards** - Highlighting key benefits
- 📱 **Responsive Design** - Perfect on mobile, tablet, desktop
- ⚡ **Loading Animation** - Professional spinner while loading
- 🎨 **Modern UI** - Clean, professional design

### Full-Featured App:
- 🤖 AI-powered grant matching
- 📊 8,000+ grants in database (sync in progress)
- ✅ Complete Q&A flow (11 questions)
- 🎯 Personalized recommendations

---

## 🎨 Template Features

### Hero Section
```
💡 AI補助金マッチング
あなたの事業に最適な補助金を、AIが最短3分で診断します
```

### Feature Cards
1. 🤖 **AI診断** - Advanced AI technology
2. ⚡ **最短3分** - Quick and easy
3. 🎯 **高精度マッチング** - Accurate results
4. 🆓 **完全無料** - Completely free

### Responsive iframe
- Desktop: 80% aspect ratio
- Mobile: 100% height
- Loading spinner included

---

## ⚙️ Customization (Optional)

### Change Colors
Edit `page-subsidy-diagnosis.php` line 21:
```css
/* Current: Purple gradient */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

/* Change to blue gradient */
background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);

/* Change to green gradient */
background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
```

### Change Feature Cards
Edit lines 238-261 to modify:
- Icons (emojis)
- Titles
- Descriptions

### Adjust iframe Height
Edit line 51:
```css
/* Current: 80% height */
padding-bottom: 80%;

/* For taller iframe */
padding-bottom: 100%;

/* For shorter iframe */
padding-bottom: 60%;
```

---

## 🔍 Testing Checklist

After installation, verify:
- [ ] Page loads without errors
- [ ] Hero section displays correctly
- [ ] 4 feature cards are visible
- [ ] iframe loads the app
- [ ] Loading spinner appears then disappears
- [ ] CTA button scrolls smoothly to iframe
- [ ] Mobile responsive (test on phone)

---

## 🐛 Troubleshooting

### Template Not Appearing?
1. Check file is in correct theme directory
2. Refresh theme cache: Appearance → Themes → (reactivate theme)
3. Check file permissions (644)

### iframe Not Loading?
1. Check browser console for errors
2. Test direct URL: https://matching-public.pages.dev/
3. Verify CORS headers are deployed

### Mobile Layout Issues?
1. Clear browser cache
2. Test in incognito mode
3. Check CSS media queries (line 150)

---

## 📚 More Documentation

- **Complete Setup Guide**: `WORDPRESS_TEMPLATE_SETUP.md`
- **Embedding Options**: `WORDPRESS_EMBED_GUIDE.md`
- **Ready-to-use Code**: `WORDPRESS_EMBED_CODE.html`
- **Project Status**: `PROJECT_STATUS_SUMMARY.md`

---

## 🎯 Current Status

✅ **Application**: Deployed and working  
✅ **Database**: 6,000+ grants loaded (sync in progress)  
✅ **Template**: Created and committed  
✅ **Documentation**: Complete guides available  

🔄 **Next**: Upload template to WordPress and create page!

---

## 🆘 Need Help?

1. Check `WORDPRESS_TEMPLATE_SETUP.md` for detailed instructions
2. Review `PROJECT_STATUS_SUMMARY.md` for complete project info
3. Test direct URL: https://matching-public.pages.dev/

---

**Ready to launch?** Just follow Steps 1-3 above! 🚀
