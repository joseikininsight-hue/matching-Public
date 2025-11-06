# 🚨 緊急修正: データベーステーブルが不足しています

## 問題の診断

アプリケーションがエラーになる原因:
- ✅ アプリケーションはデプロイされている
- ✅ APIエンドポイントは動作している
- ❌ **データベースに必要なテーブルが不足している**

現在のテーブル:
```
- grants (補助金データ)
- _cf_KV (Cloudflare内部テーブル)
- sqlite_sequence (SQLite内部テーブル)
```

**不足しているテーブル:**
```
- user_sessions (セッション管理) ← これが原因でエラー!
- conversation_history (対話履歴)
- matching_results (マッチング結果)
- grant_categories (カテゴリマスタ)
- questions (質問定義)
- response_options (回答選択肢)
- training_data (学習データ)
- admin_users (管理者)
- system_logs (システムログ)
- wp_sync_log (WordPress同期ログ)
```

## 🔧 修正手順（3分で完了）

### ステップ1: Cloudflare Dashboardにアクセス

1. https://dash.cloudflare.com にアクセス
2. **Workers & Pages** → **D1** をクリック
3. データベース **助成金-db** をクリック

### ステップ2: SQLを実行

1. **Console** タブをクリック
2. 下のSQLを全てコピーして貼り付け
3. **Execute** ボタンをクリック

```sql
-- ユーザーセッションテーブル
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id TEXT PRIMARY KEY,
    user_type TEXT,
    ip_address TEXT,
    user_agent TEXT,
    started_at TEXT DEFAULT (datetime('now')),
    last_activity TEXT DEFAULT (datetime('now')),
    completed INTEGER DEFAULT 0,
    total_questions_answered INTEGER DEFAULT 0,
    metadata TEXT
);

CREATE INDEX IF NOT EXISTS idx_sessions_activity ON user_sessions(last_activity);
CREATE INDEX IF NOT EXISTS idx_sessions_completed ON user_sessions(completed);

-- 対話履歴テーブル
CREATE TABLE IF NOT EXISTS conversation_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL,
    question_id TEXT NOT NULL,
    question_text TEXT,
    answer_value TEXT,
    answer_label TEXT,
    timestamp TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (session_id) REFERENCES user_sessions(session_id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_conversation_session ON conversation_history(session_id);
CREATE INDEX IF NOT EXISTS idx_conversation_timestamp ON conversation_history(timestamp);

-- マッチング結果テーブル
CREATE TABLE IF NOT EXISTS matching_results (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL,
    grant_id INTEGER NOT NULL,
    matching_score REAL NOT NULL,
    reasoning TEXT,
    ranking INTEGER,
    user_feedback INTEGER,
    feedback_text TEXT,
    is_helpful INTEGER DEFAULT NULL,
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (session_id) REFERENCES user_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (grant_id) REFERENCES grants(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_matching_session ON matching_results(session_id);
CREATE INDEX IF NOT EXISTS idx_matching_grant ON matching_results(grant_id);
CREATE INDEX IF NOT EXISTS idx_matching_score ON matching_results(matching_score DESC);

-- カテゴリマスタテーブル
CREATE TABLE IF NOT EXISTS grant_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    icon TEXT,
    description TEXT,
    target_type TEXT,
    display_order INTEGER,
    is_active INTEGER DEFAULT 1
);

-- カテゴリ初期データ
INSERT OR IGNORE INTO grant_categories (code, name, icon, target_type, display_order) VALUES
('dx_digital', 'DX・デジタル化推進', '💻', 'both', 1),
('equipment', '設備投資・機械導入', '🏭', 'corporate', 2),
('rd_innovation', '研究開発・新技術開発', '🔬', 'corporate', 3),
('hiring_training', '人材採用・育成', '👥', 'corporate', 4),
('energy_carbon', '省エネ・ゼロカーボン', '🌱', 'both', 5),
('export_sales', '海外展開・販路拡大', '🌍', 'corporate', 6),
('startup', '創業・起業支援', '🚀', 'both', 7),
('agriculture', '農業・林業・漁業', '🌾', 'both', 8),
('tourism', '観光・地域振興', '🗾', 'both', 9),
('welfare_care', '福祉・介護', '🏥', 'both', 10),
('education', '教育・人材育成', '📚', 'both', 11),
('childcare', '子育て支援', '👶', 'individual', 12),
('housing', '住宅・リフォーム', '🏠', 'individual', 13),
('disaster', '防災・減災', '🛡️', 'both', 14),
('it_software', 'IT・ソフトウェア開発', '⚙️', 'corporate', 15),
('marketing', 'マーケティング・広報', '📢', 'corporate', 16),
('logistics', '物流・配送効率化', '🚚', 'corporate', 17),
('manufacturing', '製造業高度化', '🔧', 'corporate', 18),
('service', 'サービス業支援', '🛎️', 'corporate', 19),
('community', '地域コミュニティ活動', '🤝', 'individual', 20);

-- 質問定義テーブル
CREATE TABLE IF NOT EXISTS questions (
    id TEXT PRIMARY KEY,
    category TEXT NOT NULL,
    text TEXT NOT NULL,
    type TEXT NOT NULL,
    icon TEXT,
    required INTEGER DEFAULT 0,
    skippable INTEGER DEFAULT 0,
    weight REAL DEFAULT 1.0,
    display_order INTEGER,
    conditions TEXT,
    placeholder TEXT,
    max_selections INTEGER,
    allow_text_input INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1
);

-- 回答選択肢テーブル
CREATE TABLE IF NOT EXISTS response_options (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_id TEXT NOT NULL,
    value TEXT NOT NULL,
    label TEXT NOT NULL,
    icon TEXT,
    description TEXT,
    display_order INTEGER,
    is_active INTEGER DEFAULT 1,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_options_question ON response_options(question_id);

-- 学習データテーブル
CREATE TABLE IF NOT EXISTS training_data (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL,
    grant_id INTEGER NOT NULL,
    user_rating INTEGER,
    was_helpful INTEGER,
    user_profile TEXT,
    match_reasoning TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (session_id) REFERENCES user_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (grant_id) REFERENCES grants(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_training_session ON training_data(session_id);
CREATE INDEX IF NOT EXISTS idx_training_grant ON training_data(grant_id);
CREATE INDEX IF NOT EXISTS idx_training_rating ON training_data(user_rating);

-- 管理者ユーザーテーブル
CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    email TEXT,
    role TEXT DEFAULT 'admin',
    is_active INTEGER DEFAULT 1,
    last_login TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

-- システムログテーブル
CREATE TABLE IF NOT EXISTS system_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    level TEXT NOT NULL,
    category TEXT,
    message TEXT NOT NULL,
    details TEXT,
    session_id TEXT,
    user_id INTEGER,
    ip_address TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_logs_level ON system_logs(level);
CREATE INDEX IF NOT EXISTS idx_logs_category ON system_logs(category);
CREATE INDEX IF NOT EXISTS idx_logs_created_at ON system_logs(created_at DESC);

-- WordPress同期ログテーブル
CREATE TABLE IF NOT EXISTS wp_sync_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sync_type TEXT NOT NULL,
    synced_count INTEGER DEFAULT 0,
    error_count INTEGER DEFAULT 0,
    status TEXT,
    error_details TEXT,
    started_at DATETIME,
    completed_at DATETIME,
    created_at DATETIME DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_wp_sync_log_created_at ON wp_sync_log(created_at DESC);
```

### ステップ3: 確認

SQLが正常に実行されたら、アプリケーションを再度開く:

**https://matching-public.pages.dev**

エラーが解消され、正常に動作するはずです！

## ✅ 確認方法

以下のコマンドでテーブルが作成されたか確認できます:

```bash
curl "https://matching-public.pages.dev/api/test/db-tables"
```

**期待される結果:**
```json
{
  "success": true,
  "tables": [
    "_cf_KV",
    "admin_users",
    "conversation_history",
    "grant_categories",
    "grants",
    "matching_results",
    "questions",
    "response_options",
    "sqlite_sequence",
    "system_logs",
    "training_data",
    "user_sessions",
    "wp_sync_log"
  ]
}
```

## 📝 なぜこの問題が発生したか

最初に提供した `D1_COMPLETE_SETUP.sql` が完全には実行されていなかったようです。
おそらく:
1. SQLが途中で切れていた
2. 複数のステートメントを一度に実行できなかった
3. エラーが発生して途中で止まった

今回の修正SQLは、必要最小限のテーブルのみを作成します。

## 🎉 完了後

全てのテーブルが作成されたら:
1. アプリケーションが正常に動作します
2. セッションを開始できます
3. 質問に答えられます
4. ただし、**補助金データがまだ空**なので推薦結果は表示されません

次のステップ: WordPressからデータを同期してください（別のドキュメント参照）

---

**何か問題があれば、エラーメッセージを共有してください！**
