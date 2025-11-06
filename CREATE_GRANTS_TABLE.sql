-- grantsテーブルの再作成
-- これをCloudflare D1 Consoleで実行してください

CREATE TABLE IF NOT EXISTS grants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    wordpress_id INTEGER UNIQUE NOT NULL,
    title TEXT NOT NULL,
    content TEXT,
    excerpt TEXT,
    status TEXT DEFAULT 'publish',
    created_at TEXT,
    updated_at TEXT,
    max_amount_display TEXT,
    max_amount_numeric INTEGER,
    deadline_display TEXT,
    deadline_date TEXT,
    organization TEXT,
    organization_type TEXT,
    grant_target TEXT,
    application_method TEXT,
    contact_info TEXT,
    official_url TEXT,
    target_prefecture_code TEXT,
    prefecture_name TEXT,
    target_municipality TEXT,
    regional_limitation TEXT,
    application_status TEXT,
    categories TEXT,
    tags TEXT,
    created_system_at TEXT DEFAULT (datetime('now')),
    updated_system_at TEXT DEFAULT (datetime('now')),
    admin_notes TEXT,
    wp_post_id INTEGER,
    wp_sync_status TEXT DEFAULT 'pending',
    last_wp_sync DATETIME
);

CREATE INDEX IF NOT EXISTS idx_grants_status ON grants(status);
CREATE INDEX IF NOT EXISTS idx_grants_prefecture ON grants(target_prefecture_code);
CREATE INDEX IF NOT EXISTS idx_grants_deadline ON grants(deadline_date);
CREATE INDEX IF NOT EXISTS idx_grants_wp_post_id ON grants(wp_post_id);
