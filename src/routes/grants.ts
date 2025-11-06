import { Hono } from 'hono';
import { Env } from '../types';
import { executeQuery } from '../utils/db.utils';

const grants = new Hono<{ Bindings: Env }>();

// 補助金一覧取得
grants.get('/', async (c) => {
  try {
    const db = c.env.DB;
    
    // クエリパラメータ
    const page = parseInt(c.req.query('page') || '1');
    const limit = parseInt(c.req.query('limit') || '20');
    const prefecture = c.req.query('prefecture');
    const category = c.req.query('category');
    const search = c.req.query('search');
    
    const offset = (page - 1) * limit;
    
    // WHERE条件の構築
    const conditions: string[] = [];
    const params: any[] = [];
    
    if (prefecture) {
      conditions.push('prefecture_name LIKE ?');
      params.push(`%${prefecture}%`);
    }
    
    if (category) {
      conditions.push('categories LIKE ?');
      params.push(`%${category}%`);
    }
    
    if (search) {
      conditions.push('(title LIKE ? OR content LIKE ? OR excerpt LIKE ?)');
      params.push(`%${search}%`, `%${search}%`, `%${search}%`);
    }
    
    const whereClause = conditions.length > 0 ? `WHERE ${conditions.join(' AND ')}` : '';
    
    // 総数取得
    const countQuery = `SELECT COUNT(*) as total FROM grants ${whereClause}`;
    const countResult = await executeQuery(db, countQuery, params);
    const total = countResult[0]?.total || 0;
    
    // データ取得
    const dataQuery = `
      SELECT 
        id,
        wordpress_id,
        title,
        excerpt,
        organization,
        max_amount_display,
        max_amount_numeric,
        deadline_display,
        deadline_date,
        official_url,
        prefecture_name,
        target_municipality,
        categories,
        tags,
        application_status,
        created_at,
        updated_at
      FROM grants 
      ${whereClause}
      ORDER BY updated_at DESC
      LIMIT ? OFFSET ?
    `;
    
    const grantsData = await executeQuery(db, dataQuery, [...params, limit, offset]);
    
    return c.json({
      success: true,
      data: {
        grants: grantsData,
        pagination: {
          page,
          limit,
          total,
          total_pages: Math.ceil(total / limit)
        }
      }
    });
  } catch (error) {
    console.error('Grants fetch error:', error);
    return c.json({
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error'
    }, 500);
  }
});

// 補助金詳細取得
grants.get('/:id', async (c) => {
  try {
    const db = c.env.DB;
    const id = c.req.param('id');
    
    const query = `
      SELECT * FROM grants WHERE id = ? OR wordpress_id = ?
    `;
    
    const result = await executeQuery(db, query, [id, id]);
    
    if (result.length === 0) {
      return c.json({
        success: false,
        error: 'Grant not found'
      }, 404);
    }
    
    return c.json({
      success: true,
      data: result[0]
    });
  } catch (error) {
    console.error('Grant fetch error:', error);
    return c.json({
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error'
    }, 500);
  }
});

// 補助金統計情報
grants.get('/stats/summary', async (c) => {
  try {
    const db = c.env.DB;
    
    // 総数
    const totalResult = await executeQuery(db, 'SELECT COUNT(*) as count FROM grants');
    const total = totalResult[0]?.count || 0;
    
    // 都道府県別カウント
    const prefectureResult = await executeQuery(db, `
      SELECT prefecture_name, COUNT(*) as count 
      FROM grants 
      GROUP BY prefecture_name 
      ORDER BY count DESC 
      LIMIT 10
    `);
    
    // カテゴリ別カウント（categoriesカラムはカンマ区切りの文字列）
    const categoryResult = await executeQuery(db, `
      SELECT categories, COUNT(*) as count 
      FROM grants 
      WHERE categories IS NOT NULL AND categories != ''
      GROUP BY categories 
      ORDER BY count DESC 
      LIMIT 10
    `);
    
    return c.json({
      success: true,
      data: {
        total_grants: total,
        by_prefecture: prefectureResult,
        by_category: categoryResult
      }
    });
  } catch (error) {
    console.error('Stats fetch error:', error);
    return c.json({
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error'
    }, 500);
  }
});

export default grants;
