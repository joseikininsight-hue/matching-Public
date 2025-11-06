import { Hono } from 'hono';
import { Env } from '../types';
import { executeQuery } from '../utils/db.utils';
import { MatchingService } from '../services/matching.service';

const test = new Hono<{ Bindings: Env }>();

// テスト用：データベースのテーブル一覧確認
test.get('/db-tables', async (c) => {
  try {
    const tables = await executeQuery(
      c.env.DB,
      "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"
    );
    
    return c.json({
      success: true,
      tables: tables.map((t: any) => t.name)
    });
  } catch (error) {
    return c.json({ 
      success: false, 
      error: error instanceof Error ? error.message : String(error) 
    }, 500);
  }
});

// テスト用：データベースの状態確認
test.get('/db-status', async (c) => {
  try {
    // 補助金総数
    const totalGrants = await executeQuery(
      c.env.DB,
      'SELECT COUNT(*) as count FROM grants'
    );
    
    // 静岡県の補助金数とサンプル
    const shizuokaGrants = await executeQuery(
      c.env.DB,
      'SELECT COUNT(*) as count FROM grants WHERE prefecture_name LIKE ?',
      ['%静岡%']
    );
    
    // 静岡県のサンプルデータ
    const shizuokaSamples = await executeQuery(
      c.env.DB,
      'SELECT id, title, prefecture_name, target_municipality, categories FROM grants WHERE prefecture_name LIKE ? LIMIT 5',
      ['%静岡%']
    );
    
    // 菊川市の補助金（複数条件で検索）
    const kikugawaGrants = await executeQuery(
      c.env.DB,
      'SELECT id, title, prefecture_name, target_municipality, categories, max_amount_numeric, deadline_date FROM grants WHERE target_municipality LIKE ? OR prefecture_name LIKE ? OR title LIKE ?',
      ['%菊川%', '%菊川%', '%菊川%']
    );
    
    // BCP関連の補助金数
    const bcpGrants = await executeQuery(
      c.env.DB,
      'SELECT COUNT(*) as count FROM grants WHERE categories LIKE ?',
      ['%BCP%']
    );
    
    return c.json({
      success: true,
      data: {
        total_grants: totalGrants[0]?.count || 0,
        shizuoka_grants: shizuokaGrants[0]?.count || 0,
        shizuoka_samples: shizuokaSamples.slice(0, 5),
        bcp_grants: bcpGrants[0]?.count || 0,
        kikugawa_samples: kikugawaGrants.slice(0, 5)
      }
    });
  } catch (error) {
    return c.json({ 
      success: false, 
      error: error instanceof Error ? error.message : String(error) 
    }, 500);
  }
});

// テスト用：フィルタリング処理のデバッグ
test.post('/test-matching', async (c) => {
  try {
    const body = await c.req.json();
    const { sessionId } = body;
    
    if (!sessionId) {
      return c.json({ success: false, error: 'sessionId is required' }, 400);
    }
    
    // MatchingService インスタンス作成
    const matchingService = new MatchingService(c.env.DB, c.env.GEMINI_API_KEY);
    
    // ユーザープロファイル取得
    const userProfile = await matchingService.getUserProfile(sessionId);
    
    // フィルタリング実行（デバッグ用）
    const filtered = await matchingService['applyRuleBasedFilters'](userProfile);
    
    // 個別条件でテスト
    const testResults = {
      all_grants: await executeQuery(c.env.DB, 'SELECT COUNT(*) as count FROM grants'),
      kikugawa_only: await executeQuery(c.env.DB, 'SELECT COUNT(*) as count FROM grants WHERE prefecture_name LIKE ?', ['%菊川市%']),
      bcp_only: await executeQuery(c.env.DB, 'SELECT COUNT(*) as count FROM grants WHERE categories LIKE ?', ['%BCP%']),
      disaster_only: await executeQuery(c.env.DB, 'SELECT COUNT(*) as count FROM grants WHERE categories LIKE ?', ['%防災%']),
      // 複合条件テスト
      kikugawa_and_bcp: await executeQuery(c.env.DB, 'SELECT COUNT(*) as count FROM grants WHERE prefecture_name LIKE ? AND categories LIKE ?', ['%菊川市%', '%BCP%']),
      kikugawa_and_disaster: await executeQuery(c.env.DB, 'SELECT COUNT(*) as count FROM grants WHERE prefecture_name LIKE ? AND categories LIKE ?', ['%菊川市%', '%防災%']),
      kikugawa_and_any_category: await executeQuery(c.env.DB, 'SELECT COUNT(*) as count FROM grants WHERE prefecture_name LIKE ? AND (categories LIKE ? OR categories LIKE ? OR categories LIKE ? OR categories LIKE ?)', ['%菊川市%', '%防災%', '%BCP%', '%事業継続%', '%減災%']),
      // 金額条件付き
      kikugawa_category_amount: await executeQuery(c.env.DB, 'SELECT COUNT(*) as count FROM grants WHERE prefecture_name LIKE ? AND (categories LIKE ? OR categories LIKE ? OR categories LIKE ? OR categories LIKE ?) AND (max_amount_numeric IS NULL OR (max_amount_numeric >= ? AND max_amount_numeric <= ?))', ['%菊川市%', '%防災%', '%BCP%', '%事業継続%', '%減災%', 100000, 500000])
    };
    
    return c.json({
      success: true,
      data: {
        user_profile: {
          session_id: userProfile.session_id,
          user_type: userProfile.user_type,
          prefecture: userProfile.answers['Q002']?.value,
          municipality: userProfile.answers['Q003']?.value,
          purposes: userProfile.answers['Q004']?.value,
          amount_range: userProfile.answers['Q005']?.value,
          deadline: userProfile.answers['Q006']?.value
        },
        filtered_count: filtered.length,
        test_results: {
          all_grants: testResults.all_grants[0]?.count || 0,
          kikugawa_only: testResults.kikugawa_only[0]?.count || 0,
          bcp_only: testResults.bcp_only[0]?.count || 0,
          disaster_only: testResults.disaster_only[0]?.count || 0,
          kikugawa_and_bcp: testResults.kikugawa_and_bcp[0]?.count || 0,
          kikugawa_and_disaster: testResults.kikugawa_and_disaster[0]?.count || 0,
          kikugawa_and_any_category: testResults.kikugawa_and_any_category[0]?.count || 0,
          kikugawa_category_amount: testResults.kikugawa_category_amount[0]?.count || 0
        },
        top_5_grants: filtered.slice(0, 5).map(g => ({
          id: g.id,
          title: g.title,
          prefecture: g.prefecture_name,
          municipality: g.target_municipality,
          categories: g.categories,
          amount: g.max_amount_numeric,
          deadline: g.deadline_date
        }))
      }
    });
  } catch (error) {
    console.error('Test matching error:', error);
    return c.json({ 
      success: false, 
      error: error instanceof Error ? error.message : String(error) 
    }, 500);
  }
});

export default test;
