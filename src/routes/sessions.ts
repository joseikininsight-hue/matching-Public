import { Hono } from 'hono';
import { v4 as uuidv4 } from 'uuid';
import { Env, ApiResponse, UserSession } from '../types';
import { insert, fetchOne, update } from '../utils/db.utils';
import { baseQuestions } from '../config/questions';

const sessions = new Hono<{ Bindings: Env }>();

// 新規セッション作成
sessions.post('/', async (c) => {
  try {
    const sessionId = uuidv4();
    const ipAddress = c.req.header('cf-connecting-ip') || c.req.header('x-forwarded-for') || 'unknown';
    const userAgent = c.req.header('user-agent') || 'unknown';
    
    await insert(c.env.DB, 'user_sessions', {
      session_id: sessionId,
      ip_address: ipAddress,
      user_agent: userAgent
    });
    
    // 最初の質問を返す
    const firstQuestion = baseQuestions[0];
    
    const response: ApiResponse = {
      success: true,
      data: {
        session_id: sessionId,
        first_question: firstQuestion
      }
    };
    
    return c.json(response);
  } catch (error) {
    console.error('Session creation error:', error);
    return c.json({ success: false, error: 'セッション作成に失敗しました' }, 500);
  }
});

// セッション情報取得
sessions.get('/:sessionId', async (c) => {
  try {
    const sessionId = c.req.param('sessionId');
    
    const session = await fetchOne<UserSession>(
      c.env.DB,
      'SELECT * FROM user_sessions WHERE session_id = ?',
      [sessionId]
    );
    
    if (!session) {
      return c.json({ success: false, error: 'セッションが見つかりません' }, 404);
    }
    
    const conversations = await c.env.DB.prepare(
      'SELECT * FROM conversation_history WHERE session_id = ? ORDER BY timestamp'
    ).bind(sessionId).all();
    
    const response: ApiResponse = {
      success: true,
      data: {
        session,
        conversation_history: conversations.results
      }
    };
    
    return c.json(response);
  } catch (error) {
    console.error('Session fetch error:', error);
    return c.json({ success: false, error: 'セッション取得に失敗しました' }, 500);
  }
});

// セッション削除（テスト用）
sessions.delete('/:sessionId', async (c) => {
  try {
    const sessionId = c.req.param('sessionId');
    
    // 関連データも削除される（CASCADE設定）
    await c.env.DB.prepare(
      'DELETE FROM user_sessions WHERE session_id = ?'
    ).bind(sessionId).run();
    
    return c.json({ success: true, message: 'セッションを削除しました' });
  } catch (error) {
    console.error('Session delete error:', error);
    return c.json({ success: false, error: 'セッション削除に失敗しました' }, 500);
  }
});

export default sessions;
