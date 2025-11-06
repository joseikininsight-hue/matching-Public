// Cloudflare Workers Bindings
export interface Env {
  DB: D1Database;
  GEMINI_API_KEY: string;
  JWT_SECRET: string;
  ADMIN_USERNAME?: string;
  ADMIN_PASSWORD_HASH?: string;
}

// 質問定義の型
export interface QuestionOption {
  value: string;
  label: string;
  icon?: string;
}

export interface Question {
  id: string;
  category?: string;
  text: string;
  type: 'single_select' | 'multi_select' | 'text_input' | 'long_text';
  icon?: string;
  options?: QuestionOption[] | string;
  placeholder?: string;
  required?: boolean;
  skippable?: boolean;
  weight?: number;
  maxSelections?: number;
  allowTextInput?: boolean;
  textInputPlaceholder?: string;
}

// ユーザー回答の型
export interface Answer {
  type: 'select' | 'multi_select' | 'text' | 'skip';
  value?: string | string[];
  custom_text?: string;
}

// セッション情報
export interface UserSession {
  session_id: string;
  user_type?: 'corporate' | 'individual';
  ip_address?: string;
  user_agent?: string;
  started_at: string;
  last_activity: string;
  completed: number;
  total_questions_answered: number;
  metadata?: string;
}

// 対話履歴
export interface ConversationHistory {
  id: number;
  session_id: string;
  question_id: string;
  question_text: string;
  answer_type: string;
  answer_value: string;
  answer_text?: string;
  ai_interpretation?: string;
  timestamp: string;
}

// 補助金データ
export interface Grant {
  id: number;
  wordpress_id: number;
  title: string;
  content?: string;
  excerpt?: string;
  status: string;
  created_at?: string;
  updated_at?: string;
  max_amount_display?: string;
  max_amount_numeric?: number;
  deadline_display?: string;
  deadline_date?: string;
  organization?: string;
  organization_type?: string;
  grant_target?: string;
  application_method?: string;
  contact_info?: string;
  official_url?: string;
  target_prefecture_code?: string;
  prefecture_name?: string;
  target_municipality?: string;
  regional_limitation?: string;
  application_status?: string;
  categories?: string;
  tags?: string;
  created_system_at?: string;
  updated_system_at?: string;
}

// マッチング結果
export interface MatchingResult {
  id?: number;
  session_id: string;
  grant_id: number;
  matching_score: number;
  reasoning: string;
  ranking: number;
  user_feedback?: number;
  feedback_text?: string;
  is_helpful?: number;
  created_at?: string;
}

// カテゴリマスタ
export interface GrantCategory {
  id: number;
  code: string;
  name: string;
  icon?: string;
  description?: string;
  target_type?: string;
  display_order?: number;
  is_active: number;
}

// ユーザープロファイル
export interface UserProfile {
  session_id: string;
  user_type?: 'corporate' | 'individual';
  answers: {
    [questionId: string]: {
      value: any;
      text?: string;
      interpretation?: any;
    };
  };
  extracted_intent?: {
    primary_needs: string[];
    priorities: {
      amount: 'high' | 'medium' | 'low';
      deadline: 'high' | 'medium' | 'low';
      location: 'high' | 'medium' | 'low';
    };
    user_characteristics: string[];
    recommended_focus: string;
  };
}

// Gemini APIレスポンス
export interface GeminiInterpretation {
  matched_options: string[];
  confidence: number;
  interpretation: string;
  extracted_keywords: string[];
}

// 推薦情報
export interface Recommendation {
  grant: Grant;
  matching_score: number;
  reasoning: string;
  ranking: number;
}

// APIレスポンス型
export interface ApiResponse<T = any> {
  success?: boolean;
  data?: T;
  error?: string;
  message?: string;
}
