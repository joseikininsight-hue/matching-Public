import { Hono } from 'hono';
import { Env, ApiResponse, MatchingResult } from '../types';
import { insert, executeQuery, update } from '../utils/db.utils';
import { MatchingService } from '../services/matching.service';

const recommendations = new Hono<{ Bindings: Env }>();

// 推薦理由の要約を抽出（最初の3つのポイントのみ）
function extractReasoningSummary(reasoning: string): string {
  if (!reasoning) return '';
  
  // マークダウンの箇条書きを抽出
  const lines = reasoning.split('\n');
  const bulletPoints: string[] = [];
  
  for (const line of lines) {
    const trimmed = line.trim();
    // 数字付きリスト or 箇条書き
    if (trimmed.match(/^[\d]+\.\s+/) || trimmed.match(/^[*-]\s+/)) {
      bulletPoints.push(trimmed.replace(/^[\d]+\.\s+/, '').replace(/^[*-]\s+/, ''));
      if (bulletPoints.length >= 3) break;
    }
  }
  
  if (bulletPoints.length > 0) {
    return bulletPoints.join('\n');
  }
  
  // 箇条書きがない場合、最初の段落を返す
  const firstParagraph = reasoning.split('\n\n')[0];
  return firstParagraph.substring(0, 200) + (firstParagraph.length > 200 ? '...' : '');
}

// 推薦取得
recommendations.get('/:sessionId', async (c) => {
  try {
    const sessionId = c.req.param('sessionId');
    
    // 既存の推薦結果チェック
    const existing = await executeQuery(
      c.env.DB,
      `SELECT mr.*, g.*
       FROM matching_results mr
       JOIN grants g ON mr.grant_id = g.id
       WHERE mr.session_id = ?
       ORDER BY mr.ranking`,
      [sessionId]
    );
    
    if (existing.length > 0) {
      // ユーザープロファイル要約を生成
      const matchingService = new MatchingService(c.env.DB, c.env.GEMINI_API_KEY);
      const userProfile = await matchingService.getUserProfile(sessionId);
      
      // ラベルを含めたプロファイルサマリーを生成
      const getValueText = (answerObj: any): string => {
        if (!answerObj) return '';
        const val = answerObj.value;
        if (typeof val === 'string') return val;
        if (val?.value) return val.value;
        if (Array.isArray(val)) return val.join('、');
        return String(val);
      };
      
      const profileSummary = {
        user_type: userProfile.user_type === 'corporate' ? '法人' : '個人',
        prefecture: getValueText(userProfile.answers['Q002']),
        municipality: getValueText(userProfile.answers['Q003']),
        purposes: getValueText(userProfile.answers['Q004']),
        amount_range: getValueText(userProfile.answers['Q005']),
        deadline: getValueText(userProfile.answers['Q006']),
        ai_message: getValueText(userProfile.answers['Q010'])
      };
      
      // URLのバリデーション関数
      const isValidUrl = (url: string | null): boolean => {
        if (!url || url === 'null') return false;
        // prefecture_only, nationwide, municipality_only は無効なURL
        if (['prefecture_only', 'nationwide', 'municipality_only'].includes(url)) return false;
        // http/httpsで始まるURLのみ有効
        return url.startsWith('http://') || url.startsWith('https://');
      };
      
      // タイトルから詳細ページURLを生成（joseikin-insight.comの25文字制限に合わせる）
      const generateDetailUrl = (title: string): string => {
        // タイトルを25文字にカット
        const truncatedTitle = title.slice(0, 25);
        // 末尾の記号を削除（？、！、。など）
        const cleanedTitle = truncatedTitle.replace(/[？?！!。、，,\s]+$/, '');
        // URLエンコード
        return `https://joseikin-insight.com/grants/${encodeURIComponent(cleanedTitle)}/`;
      };
      
      // キャッシュされた結果を返す
      const recommendations = existing.map(row => ({
        grant: {
          id: row.grant_id,
          title: row.title,
          url: isValidUrl(row.official_url) ? row.official_url : generateDetailUrl(row.title),
          max_amount_display: row.max_amount_display,
          deadline_display: row.deadline_display,
          organization: row.organization,
          prefecture_name: row.prefecture_name,
          target_municipality: row.target_municipality,
          grant_target: row.grant_target,
          content: row.content,
          excerpt: row.excerpt,
          official_url: row.official_url,
          categories: row.categories,
          tags: row.tags
        },
        matching_score: row.matching_score,
        reasoning: row.reasoning,
        reasoning_summary: extractReasoningSummary(row.reasoning),
        ranking: row.ranking
      }));
      
      const response: ApiResponse = {
        success: true,
        data: {
          recommendations,
          profile_summary: profileSummary,
          cached: true
        }
      };
      
      return c.json(response);
    }
    
    // 新規マッチング実行
    const matchingService = new MatchingService(c.env.DB, c.env.GEMINI_API_KEY);
    const newRecommendations = await matchingService.matchGrants(sessionId);
    
    if (newRecommendations.length === 0) {
      return c.json({
        success: true,
        data: {
          recommendations: [],
          message: '条件に合う補助金が見つかりませんでした。条件を変更してもう一度お試しください。'
        }
      });
    }
    
    // 結果をDBに保存
    for (let i = 0; i < newRecommendations.length; i++) {
      const rec = newRecommendations[i];
      await insert(c.env.DB, 'matching_results', {
        session_id: sessionId,
        grant_id: rec.grant.id,
        matching_score: rec.matching_score,
        reasoning: rec.reasoning,
        ranking: i + 1
      });
    }
    
    // セッション完了フラグ
    await update(
      c.env.DB,
      'user_sessions',
      { completed: 1 },
      'session_id = ?',
      [sessionId]
    );
    
    // ユーザープロファイル要約を生成（既存のmatchingServiceを再利用）
    const userProfile = await matchingService.getUserProfile(sessionId);
    
    // ラベルを含めたプロファイルサマリーを生成
    const getValueText = (answerObj: any): string => {
      if (!answerObj) return '';
      const val = answerObj.value;
      if (typeof val === 'string') return val;
      if (val?.value) return val.value;
      if (Array.isArray(val)) return val.join('、');
      return String(val);
    };
    
    const profileSummary = {
      user_type: userProfile.user_type === 'corporate' ? '法人' : '個人',
      prefecture: getValueText(userProfile.answers['Q002']),
      municipality: getValueText(userProfile.answers['Q003']),
      purposes: getValueText(userProfile.answers['Q004']),
      amount_range: getValueText(userProfile.answers['Q005']),
      deadline: getValueText(userProfile.answers['Q006']),
      ai_message: getValueText(userProfile.answers['Q010'])
    };
    
    // URLのバリデーション関数
    const isValidUrl = (url: string | null): boolean => {
      if (!url || url === 'null') return false;
      // prefecture_only, nationwide, municipality_only は無効なURL
      if (['prefecture_only', 'nationwide', 'municipality_only'].includes(url)) return false;
      // http/httpsで始まるURLのみ有効
      return url.startsWith('http://') || url.startsWith('https://');
    };
    
    // タイトルから詳細ページURLを生成（joseikin-insight.comの25文字制限に合わせる）
    const generateDetailUrl = (title: string): string => {
      // タイトルを25文字にカット
      const truncatedTitle = title.slice(0, 25);
      // 末尾の記号を削除（？、！、。など）
      const cleanedTitle = truncatedTitle.replace(/[？?！!。、，,\s]+$/, '');
      // URLエンコード
      return `https://joseikin-insight.com/grants/${encodeURIComponent(cleanedTitle)}/`;
    };
    
    const response: ApiResponse = {
      success: true,
      data: {
        recommendations: newRecommendations.map(rec => ({
          grant: {
            id: rec.grant.id,
            title: rec.grant.title,
            url: isValidUrl(rec.grant.official_url) ? rec.grant.official_url : generateDetailUrl(rec.grant.title),
            max_amount_display: rec.grant.max_amount_display,
            deadline_display: rec.grant.deadline_display,
            organization: rec.grant.organization,
            prefecture_name: rec.grant.prefecture_name,
            target_municipality: rec.grant.target_municipality,
            grant_target: rec.grant.grant_target,
            content: rec.grant.content,
            excerpt: rec.grant.excerpt,
            official_url: rec.grant.official_url,
            categories: rec.grant.categories,
            tags: rec.grant.tags
          },
          matching_score: rec.matching_score,
          reasoning: rec.reasoning,
          reasoning_summary: extractReasoningSummary(rec.reasoning),
          ranking: rec.ranking
        })),
        profile_summary: profileSummary,
        cached: false
      }
    };
    
    return c.json(response);
    
  } catch (error) {
    console.error('Recommendation error:', error);
    return c.json({ 
      success: false, 
      error: '推薦の生成に失敗しました',
      details: error instanceof Error ? error.message : String(error)
    }, 500);
  }
});

// フィードバック送信
recommendations.post('/:sessionId/feedback', async (c) => {
  try {
    const sessionId = c.req.param('sessionId');
    const body = await c.req.json();
    const { grant_id, rating, feedback_text, is_helpful } = body;
    
    // フィードバック保存
    await update(
      c.env.DB,
      'matching_results',
      {
        user_feedback: rating,
        feedback_text: feedback_text || null,
        is_helpful: is_helpful ? 1 : 0
      },
      'session_id = ? AND grant_id = ?',
      [sessionId, grant_id]
    );
    
    // 低評価の場合、追加質問を生成
    if (rating <= 2 || is_helpful === false) {
      const matchingService = new MatchingService(c.env.DB, c.env.GEMINI_API_KEY);
      const userProfile = await matchingService.getUserProfile(sessionId);
      
      try {
        const geminiService = matchingService['geminiService'];
        const clarificationQuestions = await geminiService.generateClarificationQuestions(
          userProfile,
          feedback_text || '期待と異なる結果でした'
        );
        
        return c.json({
          success: true,
          data: {
            message: 'フィードバックありがとうございます',
            needs_clarification: true,
            additional_questions: clarificationQuestions.questions
          }
        });
      } catch (error) {
        console.error('Clarification generation error:', error);
      }
    }
    
    const response: ApiResponse = {
      success: true,
      data: {
        message: 'フィードバックありがとうございます',
        needs_clarification: false
      }
    };
    
    return c.json(response);
    
  } catch (error) {
    console.error('Feedback error:', error);
    return c.json({ success: false, error: 'フィードバックの保存に失敗しました' }, 500);
  }
});

// 再マッチング（フィードバック後）
recommendations.post('/:sessionId/rematch', async (c) => {
  try {
    const sessionId = c.req.param('sessionId');
    
    // 既存の推薦結果を削除
    await c.env.DB.prepare(
      'DELETE FROM matching_results WHERE session_id = ?'
    ).bind(sessionId).run();
    
    // 新規マッチング実行
    const matchingService = new MatchingService(c.env.DB, c.env.GEMINI_API_KEY);
    const newRecommendations = await matchingService.matchGrants(sessionId);
    
    // 結果をDBに保存
    for (let i = 0; i < newRecommendations.length; i++) {
      const rec = newRecommendations[i];
      await insert(c.env.DB, 'matching_results', {
        session_id: sessionId,
        grant_id: rec.grant.id,
        matching_score: rec.matching_score,
        reasoning: rec.reasoning,
        ranking: i + 1
      });
    }
    
    // ユーザープロファイル要約を生成
    const userProfile = await matchingService.getUserProfile(sessionId);
    
    // ラベルを含めたプロファイルサマリーを生成
    const getValueText = (answerObj: any): string => {
      if (!answerObj) return '';
      const val = answerObj.value;
      if (typeof val === 'string') return val;
      if (val?.value) return val.value;
      if (Array.isArray(val)) return val.join('、');
      return String(val);
    };
    
    const profileSummary = {
      user_type: userProfile.user_type === 'corporate' ? '法人' : '個人',
      prefecture: getValueText(userProfile.answers['Q002']),
      municipality: getValueText(userProfile.answers['Q003']),
      purposes: getValueText(userProfile.answers['Q004']),
      amount_range: getValueText(userProfile.answers['Q005']),
      deadline: getValueText(userProfile.answers['Q006']),
      ai_message: getValueText(userProfile.answers['Q010'])
    };
    
    return c.json({
      success: true,
      data: {
        recommendations: newRecommendations.map(rec => ({
          grant: {
            id: rec.grant.id,
            title: rec.grant.title,
            url: rec.grant.official_url && rec.grant.official_url !== 'null' ? rec.grant.official_url : `https://joseikin-insight.com/grants/${encodeURIComponent(rec.grant.title)}/`,
            max_amount_display: rec.grant.max_amount_display,
            deadline_display: rec.grant.deadline_display,
            organization: rec.grant.organization,
            prefecture_name: rec.grant.prefecture_name,
            grant_target: rec.grant.grant_target,
            content: rec.grant.content,
            excerpt: rec.grant.excerpt,
            official_url: rec.grant.official_url,
            categories: rec.grant.categories,
            tags: rec.grant.tags
          },
          matching_score: rec.matching_score,
          reasoning: rec.reasoning,
          reasoning_summary: extractReasoningSummary(rec.reasoning),
          ranking: rec.ranking
        })),
        profile_summary: profileSummary,
        message: '新しい推薦を生成しました'
      }
    });
    
  } catch (error) {
    console.error('Rematch error:', error);
    return c.json({ success: false, error: '再マッチングに失敗しました' }, 500);
  }
});

export default recommendations;
