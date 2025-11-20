# 🚀 Cloudflare Pages Deployment Checklist

## ✅ Status Overview

- [x] Code pushed to GitHub
- [x] wrangler.toml fixed (removed invalid database_id)
- [ ] D1 Database binding configured in Cloudflare Pages Dashboard
- [ ] Database migrations applied to production D1
- [ ] Environment variables set in Cloudflare Pages
- [ ] Deployment successful
- [ ] Application tested in production

---

## 📋 Required Manual Steps

### Step 1: Configure D1 Database Binding in Cloudflare Pages

**Time Required**: 2 minutes

1. Go to [Cloudflare Pages Dashboard](https://dash.cloudflare.com/)
2. Select your project: **matching-public**
3. Navigate to **Settings → Functions**
4. Scroll to **D1 database bindings** section
5. Click **Add binding**
6. Configure:
   - **Variable name**: `DB`
   - **D1 database**: Select `grants-db`
7. Click **Save**

**⚠️ Important**: This step is REQUIRED for the deployment to succeed. The error you saw ("Database UUID (local-grants-db) が無効です") occurs because the D1 binding wasn't configured in the dashboard.

---

### Step 2: Apply Database Migrations to Production

**Time Required**: 5 minutes

You need to apply two migrations to your production D1 database:

#### Migration 1: Add ACF Fields (0004_add_acf_fields.sql)

1. Go to **Cloudflare Dashboard → D1**
2. Select database: **grants-db**
3. Open **Console** tab
4. Copy and paste this SQL:

```sql
-- Add missing ACF fields to grants table
ALTER TABLE grants ADD COLUMN url TEXT;
ALTER TABLE grants ADD COLUMN eligible_expenses TEXT;
ALTER TABLE grants ADD COLUMN required_documents TEXT;
ALTER TABLE grants ADD COLUMN adoption_rate TEXT;
ALTER TABLE grants ADD COLUMN difficulty_level TEXT;
ALTER TABLE grants ADD COLUMN area_notes TEXT;
ALTER TABLE grants ADD COLUMN subsidy_rate_detailed TEXT;

-- Create indexes for commonly queried fields
CREATE INDEX IF NOT EXISTS idx_grants_organization ON grants(organization);
CREATE INDEX IF NOT EXISTS idx_grants_url ON grants(url);
```

5. Click **Execute**

#### Migration 2: Add answer_label Column (0005_add_answer_label.sql)

1. In the same Console tab, paste this SQL:

```sql
-- Add answer_label column to conversation_history table
ALTER TABLE conversation_history ADD COLUMN answer_label TEXT;
```

2. Click **Execute**

---

### Step 3: Set Environment Variables

**Time Required**: 1 minute

1. In **matching-public** project settings
2. Navigate to **Settings → Environment variables**
3. Click **Add variable**
4. Add:
   - **Variable name**: `GEMINI_API_KEY`
   - **Value**: `AIzaSyDjq1BQdjccRj0FZIAFhRPzyLJbu1wScDI`
   - **Environment**: Select **Production** and **Preview**
5. Click **Save**

---

### Step 4: Trigger Redeployment

**Time Required**: 2-3 minutes

After completing Steps 1-3:

1. Go to **Deployments** tab in matching-public project
2. Find the latest deployment
3. Click **⋯ (three dots)** → **Retry deployment**

OR simply push a new commit to GitHub (automatic deployment will trigger).

---

## 🧪 Testing Checklist

After successful deployment, test the following:

### 1. Basic Functionality
- [ ] Access https://matching-public.pages.dev/
- [ ] Page loads without errors
- [ ] UI renders correctly (no white screen)

### 2. Session Creation
- [ ] Click "助成金診断を始める" button
- [ ] Session ID is created
- [ ] First question appears

### 3. Question Flow
- [ ] Answer Q001 (事業分野)
- [ ] Answer Q002 (地域)
- [ ] Answer Q003 (事業段階)
- [ ] Answer Q004 (対象者)
- [ ] Question Q005 (予算) should NOT appear ✅
- [ ] Recommendations load automatically

### 4. AI Recommendations
- [ ] Grant cards display with ranking badges
- [ ] **AI reasoning appears at TOP of each card** ✅
- [ ] Only fields with data are shown (no "記載なし" labels) ✅
- [ ] Grant details are clickable
- [ ] Match scores are displayed

### 5. Database Connectivity
- [ ] Check browser console for errors
- [ ] Verify API calls to `/api/` endpoints succeed
- [ ] Check Cloudflare Workers logs for any issues

---

## 🔍 Troubleshooting

### Issue: "Database UUID is invalid" error persists

**Solution**: 
- Ensure D1 binding is configured in Cloudflare Pages Dashboard (Step 1)
- Verify binding name is exactly `DB` (case-sensitive)
- Retry deployment after configuring binding

### Issue: "Column not found" errors in logs

**Solution**:
- Apply database migrations (Step 2)
- Verify migrations were executed successfully in D1 Console
- Check D1 Console → Schema tab to confirm columns exist

### Issue: AI recommendations not generating

**Solution**:
- Verify GEMINI_API_KEY is set in environment variables (Step 3)
- Check Cloudflare Workers logs for API errors
- Test API key validity: https://generativelanguage.googleapis.com/v1beta/models?key=YOUR_KEY

### Issue: Grants have no detailed information

**Solution**:
- This is expected - WordPress ACF fields are not yet exposed via REST API
- WordPress-side configuration needed (separate task)
- For now, application will work with available data

---

## 📊 Current Database Status

- **Total grants**: 6,001
- **ACF fields status**: All null (WordPress REST API not configured)
- **Available fields**: title, excerpt, categories, tags
- **Missing fields**: organization, amount, deadline, eligibility, etc.

**Next step**: Configure WordPress to expose ACF fields via REST API, then resync data.

---

## 🎯 Success Criteria

Deployment is successful when:

1. ✅ https://matching-public.pages.dev/ loads without errors
2. ✅ Complete question flow (Q001 → Q004, skipping Q005)
3. ✅ AI recommendations generate with reasoning at top
4. ✅ No "記載なし" labels appear
5. ✅ Grant cards display with available data
6. ✅ No console errors or API failures

---

## 📝 Notes

- **Local development**: Use `wrangler.toml.local` with local database ID
- **Production**: D1 binding configured via Cloudflare Dashboard
- **Gemini API**: Confirmed working locally with 95% match scores ✅
- **UI redesign**: Completed - reasoning at top, conditional field display ✅

---

**Last Updated**: 2025-11-20
**Deployment Target**: https://matching-public.pages.dev/
**Status**: Awaiting manual Cloudflare configuration (Steps 1-3)
