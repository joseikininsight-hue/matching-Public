-- Create questions table for dynamic question management
CREATE TABLE IF NOT EXISTS questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_id TEXT UNIQUE NOT NULL,
    question_text TEXT NOT NULL,
    question_type TEXT NOT NULL, -- single_select, multi_select, text_input, text_area
    options_json TEXT, -- JSON array of options
    validation_rules_json TEXT, -- JSON object for validation rules
    required INTEGER DEFAULT 1, -- 0=optional, 1=required
    display_order INTEGER NOT NULL,
    help_text TEXT,
    created_at TEXT DEFAULT (datetime('now')),
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Create response_options table
CREATE TABLE IF NOT EXISTS response_options (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_id TEXT NOT NULL,
    option_value TEXT NOT NULL,
    option_label TEXT NOT NULL,
    display_order INTEGER NOT NULL,
    FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE
);

-- Create index
CREATE INDEX IF NOT EXISTS idx_questions_order ON questions(display_order);
CREATE INDEX IF NOT EXISTS idx_response_options_question ON response_options(question_id);

-- Insert Q10: AI message question
INSERT INTO questions (
  question_id,
  question_text,
  question_type,
  options_json,
  validation_rules_json,
  required,
  display_order,
  help_text
) VALUES (
  'Q010',
  'AIに伝えたいこと（任意）',
  'text_area',
  NULL,
  '{"maxLength": 500}',
  0,
  10,
  'その他、AIに伝えたい追加情報があれば自由にご記入ください。例：特定の条件、優先したいこと、懸念事項など'
);
