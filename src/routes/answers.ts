import { Hono } from 'hono';
import { Env, Answer, ApiResponse, UserSession, ConversationHistory } from '../types';
import { insert, fetchOne, update, executeQuery, toJsonString } from '../utils/db.utils';
import { 
  getQuestionById, 
  baseQuestions, 
  corporateQuestions, 
  individualQuestions,
  detailedQuestions,
  prefectures
} from '../config/questions';
import { GeminiService } from '../services/gemini.service';

const answers = new Hono<{ Bindings: Env }>();

// 回答送信
answers.post('/:sessionId/answers', async (c) => {
  try {
    const sessionId = c.req.param('sessionId');
    const body = await c.req.json();
    const { question_id, value, answer } = body as { question_id: string; value?: any; answer?: Answer };
    
    // answer または value から回答データを取得
    const answerData: Answer = answer || { type: 'select', value: value };
    
    // セッション存在確認
    const session = await fetchOne<UserSession>(
      c.env.DB,
      'SELECT * FROM user_sessions WHERE session_id = ?',
      [sessionId]
    );
    
    if (!session) {
      return c.json({ success: false, error: 'セッションが見つかりません' }, 404);
    }
    
    // 質問情報取得
    const question = getQuestionById(question_id);
    if (!question) {
      return c.json({ success: false, error: '無効な質問IDです' }, 400);
    }
    
    // 選択肢の解決（動的選択肢の場合）
    let options = question.options;
    if (options === 'prefectures') {
      options = prefectures;
    } else if (options === 'categories') {
      // カテゴリマスタから取得
      const categories = await executeQuery(
        c.env.DB,
        'SELECT code, name, icon FROM grant_categories WHERE is_active = 1 ORDER BY display_order'
      );
      options = categories.map(cat => ({
        value: cat.code,
        label: cat.name,
        icon: cat.icon
      }));
    }
    
    let processedAnswer = answerData;
    let aiInterpretation = null;
    
    // 自然言語回答の場合、Geminiで解釈
    if (answerData.type === 'text' && question.type !== 'long_text' && Array.isArray(options)) {
      const geminiService = new GeminiService(c.env.GEMINI_API_KEY);
      aiInterpretation = await geminiService.interpretNaturalLanguageAnswer(
        question_id,
        question.text,
        answerData.value as string,
        options
      );
      
      processedAnswer = {
        type: 'interpreted' as any,
        value: aiInterpretation.matched_options
      };
    }
    
    // 回答を保存
    await insert(c.env.DB, 'conversation_history', {
      session_id: sessionId,
      question_id,
      question_text: question.text,
      answer_value: toJsonString(processedAnswer),
      answer_label: answerData.type === 'text' ? answerData.value : (processedAnswer.value?.label || processedAnswer.value)
    });
    
    // セッション更新
    await update(
      c.env.DB,
      'user_sessions',
      {
        last_activity: new Date().toISOString(),
        total_questions_answered: session.total_questions_answered + 1
      },
      'session_id = ?',
      [sessionId]
    );
    
    // Q001の回答の場合、user_typeを更新
    if (question_id === 'Q001' && typeof answerData.value === 'string') {
      await update(
        c.env.DB,
        'user_sessions',
        { user_type: answerData.value },
        'session_id = ?',
        [sessionId]
      );
    }
    
    // 次の質問を決定
    const nextQuestion = await determineNextQuestion(c.env.DB, sessionId, question_id);
    
    if (!nextQuestion) {
      // 質問終了、マッチング開始
      const response: ApiResponse = {
        success: true,
        data: {
          completed: true,
          message: '回答ありがとうございました。最適な補助金を検索しています...'
        }
      };
      return c.json(response);
    }
    
    // 進捗率計算
    const totalAnswered = session.total_questions_answered + 1;
    const estimatedTotal = 10; // 基本質問数の目安
    const progress = Math.min(totalAnswered / estimatedTotal, 0.95);
    
    // 選択肢の解決（次の質問用）
    let nextOptions = nextQuestion.options;
    if (nextOptions === 'prefectures') {
      nextOptions = prefectures;
    } else if (nextOptions === 'categories') {
      const categories = await executeQuery(
        c.env.DB,
        'SELECT code, name, icon FROM grant_categories WHERE is_active = 1 ORDER BY display_order'
      );
      nextOptions = categories.map(cat => ({
        value: cat.code,
        label: cat.name,
        icon: cat.icon
      }));
    }
    
    const response: ApiResponse = {
      success: true,
      data: {
        next_question: {
          ...nextQuestion,
          options: nextOptions
        },
        progress,
        can_request_more_details: totalAnswered >= 6
      }
    };
    
    return c.json(response);
    
  } catch (error) {
    console.error('Answer submission error:', error);
    return c.json({ success: false, error: '回答の保存に失敗しました' }, 500);
  }
});

// 次の質問を決定する関数
async function determineNextQuestion(db: D1Database, sessionId: string, currentQuestionId: string) {
  // 会話履歴取得
  const conversations = await executeQuery<ConversationHistory>(
    db,
    'SELECT * FROM conversation_history WHERE session_id = ? ORDER BY timestamp',
    [sessionId]
  );
  
  const answeredQuestions = conversations.map(c => c.question_id);
  
  // Q001の回答に基づいて分岐
  const userTypeAnswer = conversations.find(c => c.question_id === 'Q001');
  let userType: 'corporate' | 'individual' | undefined;
  
  if (userTypeAnswer) {
    try {
      const answerValue = JSON.parse(userTypeAnswer.answer_value);
      userType = answerValue.value || answerValue;
    } catch {
      // パース失敗時はスキップ
    }
  }
  
  // 質問リストを構築
  let questionPool = [...baseQuestions];
  
  if (userType === 'corporate') {
    questionPool = [...questionPool, ...corporateQuestions];
  } else if (userType === 'individual') {
    questionPool = [...questionPool, ...individualQuestions];
  }
  
  // 未回答の必須質問を優先
  const unansweredRequired = questionPool.find(
    q => q.required && !answeredQuestions.includes(q.id)
  );
  
  if (unansweredRequired) {
    return unansweredRequired;
  }
  
  // 未回答の任意質問
  const unansweredOptional = questionPool.find(
    q => !answeredQuestions.includes(q.id)
  );
  
  if (unansweredOptional) {
    return unansweredOptional;
  }
  
  // 基本質問完了
  return null;
}

// 詳細絞り込みリクエスト
answers.post('/:sessionId/request-more-details', async (c) => {
  try {
    const sessionId = c.req.param('sessionId');
    
    // 未回答の詳細質問を返す
    const conversations = await executeQuery(
      c.env.DB,
      'SELECT question_id FROM conversation_history WHERE session_id = ?',
      [sessionId]
    );
    
    const answeredIds = conversations.map(c => c.question_id);
    const unansweredDetailed = detailedQuestions.filter(
      q => !answeredIds.includes(q.id)
    );
    
    if (unansweredDetailed.length === 0) {
      return c.json({
        success: true,
        data: {
          message: 'すべての詳細質問に回答済みです',
          has_more_questions: false
        }
      });
    }
    
    const response: ApiResponse = {
      success: true,
      data: {
        has_more_questions: true,
        next_question: unansweredDetailed[0]
      }
    };
    
    return c.json(response);
    
  } catch (error) {
    console.error('More details request error:', error);
    return c.json({ success: false, error: 'リクエストの処理に失敗しました' }, 500);
  }
});

export default answers;
