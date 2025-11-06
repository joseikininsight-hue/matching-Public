-- Add WordPress integration columns to grants table
ALTER TABLE grants ADD COLUMN wp_post_id INTEGER;
ALTER TABLE grants ADD COLUMN wp_sync_status TEXT DEFAULT 'pending';
ALTER TABLE grants ADD COLUMN last_wp_sync DATETIME;

-- Create index for faster WordPress post lookups
CREATE INDEX IF NOT EXISTS idx_grants_wp_post_id ON grants(wp_post_id);

-- Create WordPress sync log table
CREATE TABLE IF NOT EXISTS wp_sync_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  sync_type TEXT NOT NULL, -- 'full', 'incremental', 'webhook'
  synced_count INTEGER DEFAULT 0,
  error_count INTEGER DEFAULT 0,
  status TEXT NOT NULL, -- 'success', 'partial', 'failed'
  error_message TEXT,
  started_at DATETIME NOT NULL,
  completed_at DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create index for sync log queries
CREATE INDEX IF NOT EXISTS idx_wp_sync_log_created_at ON wp_sync_log(created_at DESC);
