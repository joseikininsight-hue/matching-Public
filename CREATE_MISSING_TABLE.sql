-- Create wp_sync_log table if it doesn't exist
-- Copy and paste this into Cloudflare D1 Console

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
