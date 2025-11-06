-- 補助金情報テーブル
CREATE TABLE IF NOT EXISTS grants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    wordpress_id INTEGER UNIQUE NOT NULL,
    title TEXT NOT NULL,
    content TEXT,
    excerpt TEXT,
    status TEXT DEFAULT 'publish',
    created_at TEXT,
    updated_at TEXT,
    
    -- 金額情報
    max_amount_display TEXT,
    max_amount_numeric INTEGER,
    
    -- 期限情報
    deadline_display TEXT,
    deadline_date TEXT,
    
    -- 組織情報
    organization TEXT,
    organization_type TEXT,
    
    -- 対象・方法
    grant_target TEXT,
    application_method TEXT,
    contact_info TEXT,
    official_url TEXT,
    
    -- 地域情報
    target_prefecture_code TEXT,
    prefecture_name TEXT,
    target_municipality TEXT,
    regional_limitation TEXT,
    
    -- ステータス
    application_status TEXT,
    
    -- 分類（JSON配列）
    categories TEXT,
    tags TEXT,
    
    -- システム日時
    created_system_at TEXT DEFAULT (datetime('now')),
    updated_system_at TEXT DEFAULT (datetime('now'))
);

-- インデックス
CREATE INDEX IF NOT EXISTS idx_grants_status ON grants(status);
CREATE INDEX IF NOT EXISTS idx_grants_prefecture ON grants(target_prefecture_code);
CREATE INDEX IF NOT EXISTS idx_grants_deadline ON grants(deadline_date);

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
    question_id TEXT,
    question_text TEXT,
    answer_type TEXT,
    answer_value TEXT,
    answer_text TEXT,
    ai_interpretation TEXT,
    timestamp TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (session_id) REFERENCES user_sessions(session_id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_conversation_session ON conversation_history(session_id);

-- マッチング結果テーブル
CREATE TABLE IF NOT EXISTS matching_results (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL,
    grant_id INTEGER NOT NULL,
    matching_score REAL,
    reasoning TEXT,
    ranking INTEGER,
    user_feedback INTEGER,
    feedback_text TEXT,
    is_helpful INTEGER,
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (session_id) REFERENCES user_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (grant_id) REFERENCES grants(id)
);

CREATE INDEX IF NOT EXISTS idx_matching_session ON matching_results(session_id);
CREATE INDEX IF NOT EXISTS idx_matching_grant ON matching_results(grant_id);

-- 学習データテーブル
CREATE TABLE IF NOT EXISTS training_data (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT,
    user_profile TEXT,
    conversation_flow TEXT,
    selected_grants TEXT,
    feedback_scores TEXT,
    avg_feedback_score REAL,
    exported_to_jsonl INTEGER DEFAULT 0,
    export_timestamp TEXT,
    quality_score REAL,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_training_export ON training_data(exported_to_jsonl);

-- 管理者テーブル
CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    last_login TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

-- 初期管理者作成（パスワード: keishi0804）
-- bcryptハッシュ: $2b$10$rZJ5K7Y8rYBZLZ8Z5QZqJeLZ8Z5QZqJeLZ8Z5QZqJeLZ8Z5QZq
INSERT OR IGNORE INTO admin_users (username, password_hash) 
VALUES ('admin', '$2b$10$rZJ5K7Y8rYBZLZ8Z5QZqJeLZ8Z5QZqJeLZ8Z5QZqJeLZ8Z5QZq');

-- システムログテーブル
CREATE TABLE IF NOT EXISTS system_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    log_type TEXT,
    message TEXT,
    details TEXT,
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_logs_type ON system_logs(log_type);
CREATE INDEX IF NOT EXISTS idx_logs_created ON system_logs(created_at);
