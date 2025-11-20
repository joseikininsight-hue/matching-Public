import { Hono } from 'hono';
import { Env, ApiResponse, Grant } from '../types';
import { insert, executeQuery, paginate, fetchOne } from '../utils/db.utils';
import { GoogleGenerativeAI } from '@google/generative-ai';

// NOTE: CSV/Excel import features are disabled in production
// Cloudflare Workers doesn't support Node.js modules (papaparse, xlsx)
// Use WordPress REST API sync instead: POST /api/wordpress/sync

const admin = new Hono<{ Bindings: Env }>();

// 簡易認証ミドルウェア
admin.use('/*', async (c, next) => {
  const authHeader = c.req.header('Authorization');
  const apiKey = authHeader?.replace('Bearer ', '');
  
  // 開発環境では簡易チェック（本番では適切な認証を実装）
  const validApiKey = c.env.JWT_SECRET || 'dev-secret-key';
  
  if (apiKey !== validApiKey) {
    return c.json({ success: false, error: '認証が必要です' }, 401);
  }
  
  await next();
});

// ========================================
// ファイルアップロードは無効化（Cloudflare Workers非対応）
// ========================================

admin.post('/import/grants-csv', async (c) => {
  return c.json({ 
    success: false, 
    error: 'CSVインポートは本番環境では無効です。WordPressとの同期をご利用ください: POST /api/wordpress/sync' 
  }, 501);
});

admin.post('/import/grants-excel', async (c) => {
  return c.json({ 
    success: false, 
    error: 'Excelインポートは本番環境では無効です。WordPressとの同期をご利用ください: POST /api/wordpress/sync' 
  }, 501);
});

admin.get('/grants', async (c) => {
  try {
    const page = parseInt(c.req.query('page') || '1');
    const limit = parseInt(c.req.query('limit') || '20');
    const status = c.req.query('status') || 'publish';
    const keyword = c.req.query('keyword') || '';
    const prefecture = c.req.query('prefecture') || '';
    const wordpress_id = c.req.query('wordpress_id') || '';
    
    // 条件構築
    const conditions: string[] = ['status = ?'];
    const params: any[] = [status];
    
    if (keyword) {
      conditions.push('(title LIKE ? OR organization LIKE ? OR content LIKE ?)');
      const searchTerm = `%${keyword}%`;
      params.push(searchTerm, searchTerm, searchTerm);
    }
    
    if (prefecture) {
      conditions.push('prefecture_name = ?');
      params.push(prefecture);
    }
    
    if (wordpress_id) {
      conditions.push('wordpress_id = ?');
      params.push(parseInt(wordpress_id));
    }
    
    const whereClause = conditions.join(' AND ');
    
    const result = await paginate<Grant>(
      c.env.DB,
      `SELECT * FROM grants WHERE ${whereClause} ORDER BY created_system_at DESC`,
      `SELECT COUNT(*) as count FROM grants WHERE ${whereClause}`,
      params,
      { page, limit }
    );
    
    // フロントエンドとの整合性: grantsフィールドでデータを返す
    return c.json({ 
      success: true, 
      data: {
        grants: result.data,  // result.dataは配列
        total: result.total,
        page: result.page,
        limit: result.limit,
        totalPages: result.totalPages
      }
    });
    
  } catch (error) {
    console.error('Grants fetch error:', error);
    return c.json({ success: false, error: '補助金一覧の取得に失敗しました' }, 500);
  }
});

// 統計情報取得
admin.get('/stats', async (c) => {
  try {
    const days = parseInt(c.req.query('days') || '30');
    const since = new Date();
    since.setDate(since.getDate() - days);
    const sinceStr = since.toISOString();
    
    // セッション統計
    const sessionStats = await fetchOne(
      c.env.DB,
      `SELECT 
        COUNT(*) as total_sessions,
        SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_sessions,
        AVG(total_questions_answered) as avg_questions_answered
      FROM user_sessions
      WHERE started_at >= ?`,
      [sinceStr]
    );
    
    // 補助金統計
    const grantStats = await fetchOne(
      c.env.DB,
      `SELECT 
        COUNT(*) as total_grants,
        SUM(CASE WHEN status = 'publish' THEN 1 ELSE 0 END) as published_grants,
        SUM(CASE WHEN deadline_date >= date('now') OR deadline_date IS NULL THEN 1 ELSE 0 END) as active_grants
      FROM grants`
    );
    
    // マッチング統計
    const matchingStats = await fetchOne(
      c.env.DB,
      `SELECT 
        COUNT(*) as total_matches,
        AVG(user_feedback) as avg_feedback_score,
        SUM(CASE WHEN user_feedback >= 4 THEN 1 ELSE 0 END) as positive_feedback_count
      FROM matching_results
      WHERE created_at >= ?`,
      [sinceStr]
    );
    
    const response: ApiResponse = {
      success: true,
      data: {
        period: { days, since: sinceStr },
        sessions: sessionStats,
        grants: grantStats,
        matching: matchingStats
      }
    };
    
    return c.json(response);
    
  } catch (error) {
    console.error('Stats error:', error);
    return c.json({ success: false, error: '統計の取得に失敗しました' }, 500);
  }
});

// 最近のセッション一覧
admin.get('/sessions', async (c) => {
  try {
    const limit = parseInt(c.req.query('limit') || '50');
    
    const sessions = await executeQuery(
      c.env.DB,
      `SELECT 
        us.*,
        (SELECT COUNT(*) FROM conversation_history WHERE session_id = us.session_id) as answer_count,
        (SELECT COUNT(*) FROM matching_results WHERE session_id = us.session_id) as match_count
      FROM user_sessions us
      ORDER BY us.started_at DESC
      LIMIT ?`,
      [limit]
    );
    
    return c.json({ success: true, data: { sessions } });
    
  } catch (error) {
    console.error('Sessions fetch error:', error);
    return c.json({ success: false, error: 'セッション一覧の取得に失敗しました' }, 500);
  }
});

// システムログ取得
admin.get('/logs', async (c) => {
  try {
    const limit = parseInt(c.req.query('limit') || '100');
    const logType = c.req.query('type');
    
    let query = 'SELECT * FROM system_logs';
    const params: any[] = [];
    
    if (logType) {
      query += ' WHERE log_type = ?';
      params.push(logType);
    }
    
    query += ' ORDER BY created_at DESC LIMIT ?';
    params.push(limit);
    
    const logs = await executeQuery(c.env.DB, query, params);
    
    return c.json({ success: true, data: { logs } });
    
  } catch (error) {
    console.error('Logs fetch error:', error);
    return c.json({ success: false, error: 'ログの取得に失敗しました' }, 500);
  }
});

// ========================================
// データ削除機能
// ========================================

// 補助金を個別削除（wordpress_id指定）
admin.delete('/grants/:wordpress_id', async (c) => {
  try {
    const wordpress_id = parseInt(c.req.param('wordpress_id'));
    
    if (!wordpress_id) {
      return c.json({ success: false, error: '有効なwordpress_idを指定してください' }, 400);
    }
    
    // 存在チェック
    const existing = await fetchOne(
      c.env.DB,
      'SELECT id, title FROM grants WHERE wordpress_id = ?',
      [wordpress_id]
    );
    
    if (!existing) {
      return c.json({ success: false, error: '指定された補助金が見つかりません' }, 404);
    }
    
    // 関連データも削除（外部キー制約対応）
    // 1. matching_resultsから削除
    await c.env.DB.prepare('DELETE FROM matching_results WHERE grant_id IN (SELECT id FROM grants WHERE wordpress_id = ?)')
      .bind(wordpress_id)
      .run();
    
    // 2. grantsから削除
    await c.env.DB.prepare('DELETE FROM grants WHERE wordpress_id = ?')
      .bind(wordpress_id)
      .run();
    
    // ログ保存
    await insert(c.env.DB, 'system_logs', {
      log_type: 'delete',
      message: `Grant deleted: ${existing.title}`,
      details: JSON.stringify({ wordpress_id, title: existing.title })
    });
    
    return c.json({ 
      success: true, 
      data: { 
        deleted_grant: existing,
        message: '補助金を削除しました' 
      } 
    });
    
  } catch (error) {
    console.error('Delete error:', error);
    return c.json({ 
      success: false, 
      error: '削除に失敗しました',
      details: error instanceof Error ? error.message : String(error)
    }, 500);
  }
});

// 補助金を一括削除（条件指定）
admin.post('/grants/bulk-delete', async (c) => {
  try {
    const body = await c.req.json();
    const { wordpress_ids, category, prefecture_code, status, all } = body;
    
    let query = 'DELETE FROM grants WHERE ';
    const conditions: string[] = [];
    const params: any[] = [];
    
    // 全削除フラグ（危険なので確認必須）
    if (all === true) {
      const confirmToken = body.confirm_token;
      if (confirmToken !== 'DELETE_ALL_GRANTS_CONFIRMED') {
        return c.json({ 
          success: false, 
          error: '全削除には確認トークンが必要です。confirm_token: "DELETE_ALL_GRANTS_CONFIRMED" を指定してください' 
        }, 400);
      }
      
      const count = await fetchOne(c.env.DB, 'SELECT COUNT(*) as count FROM grants');
      
      // 外部キー制約対応: 関連データを先に削除
      await c.env.DB.prepare('DELETE FROM matching_results').run();
      await c.env.DB.prepare('DELETE FROM grants').run();
      
      await insert(c.env.DB, 'system_logs', {
        log_type: 'bulk_delete',
        message: `All grants deleted (${count.count} records)`,
        details: JSON.stringify({ count: count.count })
      });
      
      return c.json({ 
        success: true, 
        data: { 
          deleted_count: count.count,
          message: '全ての補助金を削除しました' 
        } 
      });
    }
    
    // wordpress_ids配列による削除
    if (wordpress_ids && Array.isArray(wordpress_ids) && wordpress_ids.length > 0) {
      const placeholders = wordpress_ids.map(() => '?').join(',');
      conditions.push(`wordpress_id IN (${placeholders})`);
      params.push(...wordpress_ids);
    }
    
    // カテゴリーによる削除
    if (category) {
      conditions.push(`categories LIKE ?`);
      params.push(`%"${category}"%`);
    }
    
    // 都道府県による削除
    if (prefecture_code) {
      conditions.push(`target_prefecture_code = ?`);
      params.push(prefecture_code);
    }
    
    // ステータスによる削除
    if (status) {
      conditions.push(`status = ?`);
      params.push(status);
    }
    
    if (conditions.length === 0) {
      return c.json({ 
        success: false, 
        error: '削除条件を指定してください（wordpress_ids, category, prefecture_code, status, または all）' 
      }, 400);
    }
    
    query += conditions.join(' AND ');
    
    // 削除前に件数確認
    const countQuery = 'SELECT COUNT(*) as count FROM grants WHERE ' + conditions.join(' AND ');
    const countResult = await fetchOne(c.env.DB, countQuery, params);
    
    if (countResult.count === 0) {
      return c.json({ 
        success: false, 
        error: '指定された条件に一致する補助金が見つかりません' 
      }, 404);
    }
    
    // 関連データも削除（外部キー制約対応）
    // 1. matching_resultsから削除
    const grantIdsQuery = 'SELECT id FROM grants WHERE ' + conditions.join(' AND ');
    const grantIds = await executeQuery(c.env.DB, grantIdsQuery, params);
    
    if (grantIds.length > 0) {
      const ids = grantIds.map((g: any) => g.id);
      const placeholders = ids.map(() => '?').join(',');
      await c.env.DB.prepare(`DELETE FROM matching_results WHERE grant_id IN (${placeholders})`)
        .bind(...ids)
        .run();
    }
    
    // 2. grantsから削除
    await c.env.DB.prepare(query).bind(...params).run();
    
    // ログ保存
    await insert(c.env.DB, 'system_logs', {
      log_type: 'bulk_delete',
      message: `Bulk delete: ${countResult.count} grants`,
      details: JSON.stringify({ count: countResult.count, conditions: body })
    });
    
    return c.json({ 
      success: true, 
      data: { 
        deleted_count: countResult.count,
        message: `${countResult.count}件の補助金を削除しました` 
      } 
    });
    
  } catch (error) {
    console.error('Bulk delete error:', error);
    return c.json({ 
      success: false, 
      error: '一括削除に失敗しました',
      details: error instanceof Error ? error.message : String(error)
    }, 500);
  }
});

// ========================================
// Gemini API Key 管理
// ========================================

// 現在のAPI Key設定を確認（マスク表示）
admin.get('/config/gemini', async (c) => {
  try {
    const apiKey = c.env.GEMINI_API_KEY;
    
    if (!apiKey) {
      return c.json({ 
        success: true, 
        data: { 
          configured: false,
          message: 'Gemini API Keyが設定されていません' 
        } 
      });
    }
    
    // APIキーの最初と最後の4文字だけ表示
    const maskedKey = apiKey.length > 8 
      ? `${apiKey.substring(0, 4)}${'*'.repeat(apiKey.length - 8)}${apiKey.substring(apiKey.length - 4)}`
      : '****';
    
    return c.json({ 
      success: true, 
      data: { 
        configured: true,
        masked_key: maskedKey,
        key_length: apiKey.length,
        message: 'Gemini API Keyが設定されています' 
      } 
    });
    
  } catch (error) {
    console.error('Config fetch error:', error);
    return c.json({ success: false, error: '設定の取得に失敗しました' }, 500);
  }
});

// Gemini API Keyをテスト
admin.post('/config/gemini/test', async (c) => {
  try {
    const body = await c.req.json();
    const testKey = body.api_key || c.env.GEMINI_API_KEY;
    
    if (!testKey) {
      return c.json({ 
        success: false, 
        error: 'API Keyが指定されていません' 
      }, 400);
    }
    
    try {
      const genAI = new GoogleGenerativeAI(testKey);
      const model = genAI.getGenerativeModel({ model: 'gemini-2.0-flash-exp' });
      
      // 簡単なテストプロンプト
      const result = await model.generateContent('こんにちは。これはAPIキーのテストです。「OK」とだけ返答してください。');
      const response = await result.response;
      const text = response.text();
      
      return c.json({ 
        success: true, 
        data: { 
          valid: true,
          test_response: text.substring(0, 100), // 最初の100文字だけ
          message: 'API Keyは有効です' 
        } 
      });
      
    } catch (apiError) {
      return c.json({ 
        success: false, 
        data: {
          valid: false,
          message: 'API Keyが無効です',
          error: apiError instanceof Error ? apiError.message : String(apiError)
        }
      }, 400);
    }
    
  } catch (error) {
    console.error('API test error:', error);
    return c.json({ 
      success: false, 
      error: 'APIテストに失敗しました',
      details: error instanceof Error ? error.message : String(error)
    }, 500);
  }
});

// .dev.vars ファイル更新の説明エンドポイント
admin.get('/config/guide', async (c) => {
  return c.json({
    success: true,
    data: {
      message: 'Gemini API Keyの設定方法',
      development: {
        title: '開発環境（ローカル）',
        steps: [
          {
            step: 1,
            title: '.dev.vars ファイルを編集',
            description: 'プロジェクトルートの .dev.vars ファイルを開き、以下の形式で設定してください',
            example: 'GEMINI_API_KEY=AIzaSy...(your-actual-api-key)'
          },
          {
            step: 2,
            title: 'サーバーを再起動',
            description: 'PM2を再起動して環境変数を読み込みます',
            command: 'pm2 restart webapp'
          },
          {
            step: 3,
            title: 'APIキーをテスト',
            description: '以下のエンドポイントで設定を確認してください',
            endpoints: [
              'GET /api/admin/config/gemini - 設定確認',
              'POST /api/admin/config/gemini/test - APIキーテスト'
            ]
          }
        ]
      },
      production: {
        title: '本番環境（Cloudflare Pages）',
        steps: [
          {
            step: 1,
            title: 'Cloudflare Secretsを使用',
            description: 'wranglerコマンドでシークレットを設定します',
            command: 'wrangler secret put GEMINI_API_KEY --project-name webapp'
          },
          {
            step: 2,
            title: '再デプロイ',
            description: 'アプリケーションを再デプロイします',
            command: 'npm run deploy:prod'
          }
        ]
      },
      api_key_acquisition: {
        title: 'Gemini API Keyの取得方法',
        url: 'https://aistudio.google.com/app/apikey',
        steps: [
          '1. Google AI Studioにアクセス',
          '2. Googleアカウントでログイン',
          '3. "Get API Key"をクリック',
          '4. 新しいAPIキーを作成',
          '5. 生成されたキーをコピー'
        ]
      }
    }
  });
});

// ========================================
// 環境変数管理（開発環境用）
// ========================================

// 現在の環境変数一覧を取得（マスク表示）
admin.get('/config/env', async (c) => {
  try {
    const env = c.env;
    
    const envVars = {
      GEMINI_API_KEY: env.GEMINI_API_KEY ? 'configured' : 'not_configured',
      GEMINI_API_KEY_MASKED: env.GEMINI_API_KEY 
        ? `${env.GEMINI_API_KEY.substring(0, 8)}...${env.GEMINI_API_KEY.substring(env.GEMINI_API_KEY.length - 4)}`
        : null,
      JWT_SECRET: env.JWT_SECRET ? 'configured' : 'not_configured',
      DB: env.DB ? 'configured' : 'not_configured'
    };
    
    return c.json({
      success: true,
      data: {
        environment: 'development',
        variables: envVars,
        message: '環境変数の設定状況です。実際の値は .dev.vars ファイルで管理してください。'
      }
    });
    
  } catch (error) {
    console.error('Env fetch error:', error);
    return c.json({ 
      success: false, 
      error: '環境変数の取得に失敗しました' 
    }, 500);
  }
});

export default admin;
