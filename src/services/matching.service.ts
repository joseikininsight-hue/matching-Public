import { D1Database } from '@cloudflare/workers-types';
import { 
  UserProfile, 
  Grant, 
  Recommendation, 
  ConversationHistory 
} from '../types';
import { executeQuery, fetchOne, parseJsonArray, parseJsonColumn } from '../utils/db.utils';
import { GeminiService } from './gemini.service';

export class MatchingService {
  private db: D1Database;
  private geminiService: GeminiService;

  constructor(db: D1Database, geminiApiKey: string) {
    this.db = db;
    this.geminiService = new GeminiService(geminiApiKey);
  }

  // メインマッチング処理
  async matchGrants(sessionId: string): Promise<Recommendation[]> {
    // ユーザープロファイル取得
    const userProfile = await this.getUserProfile(sessionId);
    
    // ルールベースフィルタリング
    const filtered = await this.applyRuleBasedFilters(userProfile);
    
    if (filtered.length === 0) {
      // フィルタ条件を緩めて再検索
      const relaxed = await this.relaxedSearch(userProfile);
      return this.geminiRanking(relaxed, userProfile, 5);
    }
    
    // Geminiによる精密評価
    const ranked = await this.geminiRanking(filtered, userProfile, 5);
    
    return ranked;
  }

  // ユーザープロファイル構築
  async getUserProfile(sessionId: string): Promise<UserProfile> {
    const session = await fetchOne(
      this.db,
      'SELECT * FROM user_sessions WHERE session_id = ?',
      [sessionId]
    );
    
    const conversations = await executeQuery<ConversationHistory>(
      this.db,
      'SELECT * FROM conversation_history WHERE session_id = ? ORDER BY timestamp',
      [sessionId]
    );
    
    // 回答をプロファイルに変換
    const profile: UserProfile = {
      session_id: sessionId,
      user_type: session?.user_type,
      answers: {}
    };
    
    for (const conv of conversations) {
      profile.answers[conv.question_id] = {
        value: parseJsonColumn(conv.answer_value),
        text: conv.answer_text || undefined,
        interpretation: parseJsonColumn(conv.ai_interpretation)
      };
    }
    
    // Geminiで意図抽出
    if (conversations.length > 0) {
      try {
        const intent = await this.geminiService.extractUserIntent(conversations);
        profile.extracted_intent = intent;
      } catch (error) {
        console.error('Intent extraction error:', error);
      }
    }
    
    return profile;
  }

  // ルールベースフィルタリング（地域は厳しく、他は柔軟に）
  async applyRuleBasedFilters(userProfile: UserProfile): Promise<Grant[]> {
    let query = 'SELECT * FROM grants WHERE status = ?';
    const params: any[] = ['publish'];
    
    // 期限切れは除外（これだけは厳しく）
    const deadlinePreference = userProfile.answers['Q006']?.value?.value || userProfile.answers['Q006']?.value;
    if (deadlinePreference && deadlinePreference !== 'anytime') {
      const deadlineDate = this.getDeadlineDate(deadlinePreference);
      query += ' AND (deadline_date IS NULL OR deadline_date >= ?)';
      params.push(deadlineDate.toISOString().split('T')[0]);
    } else {
      const today = new Date().toISOString().split('T')[0];
      query += ' AND (deadline_date IS NULL OR deadline_date >= ?)';
      params.push(today);
    }
    
    const municipality = userProfile.answers['Q003']?.value?.value || userProfile.answers['Q003']?.value;
    const prefectureCode = userProfile.answers['Q002']?.value?.value || userProfile.answers['Q002']?.value;
    const categories = userProfile.answers['Q004']?.value?.value || userProfile.answers['Q004']?.value;
    const amountRange = userProfile.answers['Q005']?.value?.value || userProfile.answers['Q005']?.value;
    
    // 地域フィルタリング（厳しく）- 指定された地域 OR 全国対象のみ
    // 地域フィルタリング（複数パターン対応）
    let locationPatterns: string[] = [];
    if (prefectureCode && prefectureCode !== 'all') {
      locationPatterns = this.getPrefectureMatchPatterns(prefectureCode);
      if (locationPatterns.length > 0) {
        // 指定地域のいずれかにマッチ OR 全国対象
        const locationConditions = locationPatterns.map(() => 'prefecture_name LIKE ?').join(' OR ');
        query += ` AND (${locationConditions} OR prefecture_name IS NULL OR prefecture_name = '')`;
        locationPatterns.forEach(pattern => params.push(`%${pattern}%`));
      }
    }
    
    // スコアリング用のケース文を構築
    // SQLインジェクション回避のため、カスタム関数を使用
    const buildScoreCase = (condition: string, points: number) => `CASE WHEN ${condition} THEN ${points} ELSE 0 END`;
    
    let orderBy = 'ORDER BY (';
    const scoreParts: string[] = [];
    
    // 地域マッチスコア（最優先 - 圧倒的な重み付け）
    // 市区町村が指定されている場合は最優先（1000点）
    if (municipality && typeof municipality === 'string' && municipality.trim() !== '') {
      const muni = municipality.trim().replace(/'/g, "''");
      scoreParts.push(buildScoreCase(`target_municipality LIKE '%${muni}%'`, 1000));
    }
    
    // 都道府県マッチ（複数パターン対応）
    // 市区町村より低いが、全国より圧倒的に高い（200点）
    if (prefectureCode && prefectureCode !== 'all' && locationPatterns.length > 0) {
      // すべてのパターンでOR条件を作成
      const locationScoreConditions = locationPatterns.map(pattern => {
        const escaped = pattern.replace(/'/g, "''");
        return `prefecture_name LIKE '%${escaped}%'`;
      }).join(' OR ');
      scoreParts.push(`CASE WHEN (${locationScoreConditions}) THEN 200 ELSE 0 END`);
    }
    
    // 全国対象の補助金（最低スコア - 1点のみ）
    scoreParts.push(buildScoreCase('prefecture_name IS NULL OR prefecture_name = ""', 1));
    
    // カテゴリマッチスコア
    if (Array.isArray(categories) && categories.length > 0) {
      const categoryKeywords = await this.getCategoryKeywords(categories);
      for (const keyword of categoryKeywords) {
        const escaped = keyword.replace(/'/g, "''"); // SQLエスケープ
        scoreParts.push(buildScoreCase(`categories LIKE '%${escaped}%'`, 5));
      }
    }
    
    // 金額範囲マッチスコア
    if (amountRange && amountRange !== 'any') {
      const [min, max] = this.getAmountRange(amountRange);
      if (max) {
        scoreParts.push(`CASE WHEN max_amount_numeric IS NULL THEN 1 WHEN max_amount_numeric >= ${min} AND max_amount_numeric <= ${max} THEN 3 ELSE 0 END`);
      } else {
        scoreParts.push(`CASE WHEN max_amount_numeric IS NULL THEN 1 WHEN max_amount_numeric >= ${min} THEN 3 ELSE 0 END`);
      }
    }
    
    // スコアリング式を完成（地域スコアを分離して最優先）
    let locationScorePart = '';
    let otherScoreParts: string[] = [];
    
    // 地域スコアとその他を分離
    // 市区町村が最優先（1000点）
    if (municipality && typeof municipality === 'string' && municipality.trim() !== '') {
      const muni = municipality.trim().replace(/'/g, "''");
      locationScorePart = buildScoreCase(`prefecture_name LIKE '%${muni}%'`, 1000);
    } else if (prefectureCode && prefectureCode !== 'all' && locationPatterns.length > 0) {
      // 都道府県（200点）
      const locationConditions = locationPatterns.map(pattern => {
        const escaped = pattern.replace(/'/g, "''");
        return `prefecture_name LIKE '%${escaped}%'`;
      }).join(' OR ');
      locationScorePart = `CASE WHEN (${locationConditions}) THEN 200 ELSE 0 END`;
    }
    
    // その他のスコア要素（カテゴリ、金額など）
    otherScoreParts = scoreParts.filter(part => 
      !part.includes('prefecture_name')
    );
    
    // ORDER BY: 地域スコアを第1キー、その他を第2キーとして完全に分離
    if (locationScorePart) {
      if (otherScoreParts.length > 0) {
        orderBy = `ORDER BY (${locationScorePart}) DESC, (${otherScoreParts.join(' + ')}) DESC, created_system_at DESC`;
      } else {
        orderBy = `ORDER BY (${locationScorePart}) DESC, created_system_at DESC`;
      }
    } else if (scoreParts.length > 0) {
      orderBy += scoreParts.join(' + ') + ') DESC, created_system_at DESC';
    } else {
      orderBy = 'ORDER BY created_system_at DESC';
    }
    
    query += ' ' + orderBy + ' LIMIT 100';
    
    // デバッグログ
    console.log('=== Rule-based filter query (location-strict) ===');
    console.log('Query:', query);
    console.log('Params:', JSON.stringify(params, null, 2));
    
    const grants = await executeQuery<Grant>(this.db, query, params);
    console.log(`Filtered grants count: ${grants.length}`);
    
    return grants;
  }

  // 条件を緩めた検索
  async relaxedSearch(userProfile: UserProfile): Promise<Grant[]> {
    let query = 'SELECT * FROM grants WHERE status = ?';
    const params: any[] = ['publish'];
    
    // 期限切れ除外のみ
    const today = new Date().toISOString().split('T')[0];
    query += ' AND (deadline_date IS NULL OR deadline_date >= ?)';
    params.push(today);
    
    query += ' ORDER BY created_system_at DESC LIMIT 50';
    
    const grants = await executeQuery<Grant>(this.db, query, params);
    return grants;
  }

  // Geminiによるランキング
  async geminiRanking(
    candidates: Grant[], 
    userProfile: UserProfile,
    topK: number
  ): Promise<Recommendation[]> {
    if (candidates.length === 0) {
      console.log('No candidates for Gemini ranking');
      return [];
    }
    
    console.log(`Starting Gemini ranking for ${candidates.length} candidates, topK=${topK}`);
    
    // デバッグ: 送信される候補の地域分布を確認
    const locationCounts = candidates.slice(0, 20).reduce((acc, g) => {
      const loc = g.prefecture_name || 'nationwide';
      acc[loc] = (acc[loc] || 0) + 1;
      return acc;
    }, {} as Record<string, number>);
    console.log('🔍 Top 20 candidates location distribution:', locationCounts);
    
    // デバッグ: 東京の補助金があるか確認
    const tokyoGrants = candidates.slice(0, 20).filter(g => 
      g.prefecture_name && g.prefecture_name.includes('東京')
    );
    console.log(`🗼 Tokyo grants in top 20: ${tokyoGrants.length}`);
    if (tokyoGrants.length > 0) {
      console.log('Tokyo grant samples:', tokyoGrants.slice(0, 3).map(g => ({
        title: g.title,
        prefecture: g.prefecture_name?.substring(0, 50) + '...'
      })));
    }
    
    try {
      const rankings = await this.geminiService.generateBatchRanking(
        userProfile,
        candidates,
        topK
      );
      
      console.log(`Gemini returned ${rankings.rankings.length} rankings`);
      
      // 詳細な推薦理由を生成
      const recommendations: Recommendation[] = [];
      
      for (const ranking of rankings.rankings.slice(0, topK)) {
        // grant_idの型チェックを追加
        if (!ranking || typeof ranking.grant_id !== 'number') {
          console.warn(`Invalid ranking data:`, ranking);
          continue;
        }
        
        const grant = candidates.find(g => g.id === ranking.grant_id);
        if (!grant) {
          console.warn(`Grant not found for ranking: ${ranking.grant_id}`);
          continue;
        }
        
        let detailedReasoning = ranking.reasoning_summary || '';
        
        // 詳細な理由を生成（非同期で実行）
        try {
          detailedReasoning = await this.geminiService.generateMatchingReasoning(
            userProfile,
            grant,
            ranking.score
          );
        } catch (error) {
          console.error('Reasoning generation error:', error);
          detailedReasoning = ranking.reasoning_summary || 'この補助金はあなたの条件に合致しています。';
        }
        
        recommendations.push({
          grant,
          matching_score: ranking.score,
          reasoning: detailedReasoning,
          ranking: ranking.rank
        });
      }
      
      console.log(`Generated ${recommendations.length} recommendations`);
      return recommendations.sort((a, b) => a.ranking - b.ranking);
      
    } catch (error) {
      console.error('Gemini ranking error:', error);
      console.error('Error details:', error instanceof Error ? error.message : String(error));
      
      // フォールバック：シンプルなスコアリング
      console.log('Using fallback ranking...');
      return this.fallbackRanking(candidates, userProfile, topK);
    }
  }

  // フォールバックランキング（Gemini失敗時）
  private fallbackRanking(
    candidates: Grant[],
    userProfile: UserProfile,
    topK: number
  ): Recommendation[] {
    const scoredGrants = candidates.map(grant => {
      let score = 0.5; // ベーススコア
      
      // カテゴリマッチ
      const userCategories = userProfile.answers['Q004']?.value || [];
      const grantCategories = parseJsonArray(grant.categories);
      if (Array.isArray(userCategories) && grantCategories.length > 0) {
        const matchCount = userCategories.filter(uc => 
          grantCategories.includes(uc)
        ).length;
        score += matchCount * 0.1;
      }
      
      // 地域マッチ
      const userPrefecture = userProfile.answers['Q002']?.value?.value || userProfile.answers['Q002']?.value;
      if (userPrefecture && (grant.target_prefecture_code === userPrefecture || !grant.target_prefecture_code)) {
        score += 0.15;
      }
      
      // 新しい補助金を優先
      const daysOld = Math.floor(
        (Date.now() - new Date(grant.created_system_at || 0).getTime()) / (1000 * 60 * 60 * 24)
      );
      if (daysOld < 30) score += 0.1;
      
      return {
        grant,
        matching_score: Math.min(score, 1.0),
        reasoning: 'この補助金はあなたの条件に合致しています。詳細は公式サイトでご確認ください。',
        ranking: 0
      };
    });
    
    // スコア順にソート
    scoredGrants.sort((a, b) => b.matching_score - a.matching_score);
    
    // ランキング付与
    return scoredGrants.slice(0, topK).map((item, index) => ({
      ...item,
      ranking: index + 1
    }));
  }

  // 金額範囲の取得
  private getAmountRange(rangeCode: string): [number, number | null] {
    const ranges: Record<string, [number, number | null]> = {
      'under_100k': [0, 100000],
      '100k_500k': [100000, 500000],
      'under_500k': [0, 500000],
      '500k_1m': [500000, 1000000],
      '1m_3m': [1000000, 3000000],
      '3m_5m': [3000000, 5000000],
      '5m_10m': [5000000, 10000000],
      '10m_30m': [10000000, 30000000],
      'over_30m': [30000000, null]
    };
    return ranges[rangeCode] || [0, null];
  }
  
  // 都道府県コードから名前に変換
  private getPrefectureName(code: string): string | null {
    const prefectures: Record<string, string> = {
      '01': '北海道', '02': '青森県', '03': '岩手県', '04': '宮城県', '05': '秋田県',
      '06': '山形県', '07': '福島県', '08': '茨城県', '09': '栃木県', '10': '群馬県',
      '11': '埼玉県', '12': '千葉県', '13': '東京都', '14': '神奈川県', '15': '新潟県',
      '16': '富山県', '17': '石川県', '18': '福井県', '19': '山梨県', '20': '長野県',
      '21': '岐阜県', '22': '静岡県', '23': '愛知県', '24': '三重県', '25': '滋賀県',
      '26': '京都府', '27': '大阪府', '28': '兵庫県', '29': '奈良県', '30': '和歌山県',
      '31': '鳥取県', '32': '島根県', '33': '岡山県', '34': '広島県', '35': '山口県',
      '36': '徳島県', '37': '香川県', '38': '愛媛県', '39': '高知県', '40': '福岡県',
      '41': '佐賀県', '42': '長崎県', '43': '熊本県', '44': '大分県', '45': '宮崎県',
      '46': '鹿児島県', '47': '沖縄県'
    };
    return prefectures[code] || null;
  }
  
  // 都道府県コードから地域マッチング用のパターンを取得
  private getPrefectureMatchPatterns(code: string): string[] {
    const patterns: string[] = [];
    const prefName = this.getPrefectureName(code);
    
    if (prefName) {
      patterns.push(prefName);
      
      // 東京都の場合は、23区を追加
      if (code === '13') {
        patterns.push('千代田区', '中央区', '港区', '新宿区', '文京区', '台東区', 
                      '墨田区', '江東区', '品川区', '目黒区', '大田区', '世田谷区',
                      '渋谷区', '中野区', '杉並区', '豊島区', '北区', '荒川区', 
                      '板橋区', '練馬区', '足立区', '葛飾区', '江戸川区');
      }
    }
    
    return patterns;
  }

  // カテゴリコードからキーワードに変換
  private async getCategoryKeywords(categoryCodes: string[]): Promise<string[]> {
    // カテゴリマスタから名前を取得
    try {
      const placeholders = categoryCodes.map(() => '?').join(',');
      const categories = await executeQuery(
        this.db,
        `SELECT code, name FROM grant_categories WHERE code IN (${placeholders})`,
        categoryCodes
      );
      
      // カテゴリ名を取得
      const keywords: string[] = [];
      for (const cat of categories) {
        // 「防災・減災」→「防災」のように最初の単語を抽出
        const mainKeyword = cat.name.split('・')[0].split('、')[0];
        keywords.push(mainKeyword);
        
        // 特定のカテゴリには追加キーワードを追加
        if (cat.code === 'disaster') {
          keywords.push('BCP', '事業継続', '減災');
        }
      }
      
      return keywords;
    } catch (error) {
      console.error('Category keyword lookup error:', error);
      // エラー時はコードをそのまま返す
      return categoryCodes;
    }
  }
  
  // 期限日付の取得
  private getDeadlineDate(preference: string): Date {
    const now = new Date();
    const months: Record<string, number> = {
      'urgent': 1,
      '1_3months': 3,
      '3_6months': 6,
      '6_12months': 12
    };
    now.setMonth(now.getMonth() + (months[preference] || 12));
    return now;
  }

  // プロファイルをテキスト化
  profileToText(userProfile: UserProfile): string {
    const parts: string[] = [];
    
    parts.push(`ユーザータイプ: ${userProfile.user_type === 'corporate' ? '企業' : '個人'}`);
    
    const prefecture = userProfile.answers['Q002']?.value;
    if (prefecture) {
      parts.push(`所在地: ${prefecture}`);
    }
    
    const purposes = userProfile.answers['Q004']?.value;
    if (purposes) {
      parts.push(`目的: ${Array.isArray(purposes) ? purposes.join(', ') : purposes}`);
    }
    
    // Q10: AIに伝えたいこと（最優先で反映）
    const q10Message = userProfile.answers['Q010']?.value;
    if (q10Message && typeof q10Message === 'string' && q10Message.trim() !== '') {
      parts.push(`\n【重要】ユーザーからの追加要望:\n${q10Message}`);
    }
    
    if (userProfile.extracted_intent) {
      parts.push(`主要ニーズ: ${userProfile.extracted_intent.primary_needs.join(', ')}`);
    }
    
    return parts.join('\n');
  }
}
