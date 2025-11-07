import { Hono } from 'hono';
import { Env } from '../types';

const wordpress = new Hono<{ Bindings: Env }>();

/**
 * WordPressのカスタム投稿タイプ「補助金」の定義:
 * 
 * - 投稿タイプ: grant (補助金)
 * - ACFカスタムフィールド（acf-fields.php より）:
 *   - organization: 実施組織
 *   - max_amount: 最大助成額（テキスト表示用）
 *   - max_amount_numeric: 最大助成額（数値）
 *   - deadline: 締切（表示用）
 *   - deadline_date: 締切日（日付）
 *   - application_status: 申請ステータス
 *   - grant_target: 対象者・対象事業
 *   - eligible_expenses: 対象経費
 *   - required_documents: 必要書類
 *   - official_url: 公式URL
 *   - adoption_rate: 採択率（%）
 *   - difficulty_level: 申請難易度
 *   - area_notes: 地域に関する備考
 *   - subsidy_rate_detailed: 補助率（詳細）
 * - タクソノミー:
 *   - grant_category: カテゴリー
 *   - prefecture: 都道府県
 *   - municipality: 市町村
 */

// WordPress REST APIから補助金データを取得（バッチ処理対応）
wordpress.get('/sync', async (c) => {
  try {
    const wpSiteUrl = c.env.WORDPRESS_SITE_URL || 'https://joseikin-insight.com';
    const db = c.env.DB;
    
    // クエリパラメータからページ番号を取得（デフォルト: 1）
    const page = parseInt(c.req.query('page') || '1');
    const perPage = parseInt(c.req.query('per_page') || '100');
    const maxPages = parseInt(c.req.query('max_pages') || '10'); // 一度に処理する最大ページ数
    
    let allPosts: any[] = [];
    let currentPage = page;
    let pagesProcessed = 0;
    let hasMore = true;
    
    console.log(`🔵 Starting WordPress sync from page ${page} (max ${maxPages} pages)`);
    
    // 指定されたページから最大max_pagesまで取得
    while (hasMore && pagesProcessed < maxPages) {
      const wpApiUrl = `${wpSiteUrl}/wp-json/wp/v2/grants?per_page=${perPage}&page=${currentPage}&_embed=true`;
      
      console.log(`🔵 Fetching page ${currentPage}:`, wpApiUrl);
      
      const response = await fetch(wpApiUrl, {
        headers: {
          'Authorization': `Bearer ${c.env.WORDPRESS_API_TOKEN || ''}`,
        },
      });

      if (!response.ok) {
        if (response.status === 400) {
          console.log('🔵 Reached end of pages');
          hasMore = false;
          break;
        }
        throw new Error(`WordPress API error: ${response.statusText}`);
      }

      const wpPosts = await response.json();
      
      if (wpPosts.length === 0) {
        hasMore = false;
        break;
      }
      
      allPosts = allPosts.concat(wpPosts);
      pagesProcessed++;
      console.log(`✅ Fetched page ${currentPage}: ${wpPosts.length} posts (Total so far: ${allPosts.length})`);
      
      // 次のページがあるかチェック
      const totalPages = response.headers.get('X-WP-TotalPages');
      const totalPosts = response.headers.get('X-WP-Total');
      
      if (totalPages && currentPage >= parseInt(totalPages)) {
        hasMore = false;
      } else if (wpPosts.length < perPage) {
        hasMore = false;
      } else {
        currentPage++;
      }
    }
    
    console.log(`🎉 Batch complete: ${allPosts.length} posts fetched (${pagesProcessed} pages)`);

    // WordPressデータをD1データベースに同期
    let syncedCount = 0;
    let errorCount = 0;
    const errors: string[] = [];

    for (const post of allPosts) {
      try {
        // カスタムフィールドの取得（ACF fields）
        // 注意: 現在ACFフィールドがREST APIで公開されていない可能性あり
        const acf = post.acf || {};
        
        console.log(`🔵 Processing post ${post.id}:`, post.title?.rendered);
        console.log('🔵 ACF fields:', Object.keys(acf).length > 0 ? acf : 'EMPTY');
        
        // タクソノミーからデータ取得
        const embeddedTerms = post._embedded?.['wp:term'] || [];
        const allTerms = embeddedTerms.flat();
        
        // タクソノミー別に分類（実際のタクソノミー名を使用）
        const categories = allTerms.filter((t: any) => t.taxonomy === 'grant_category');
        const prefectures = allTerms.filter((t: any) => t.taxonomy === 'grant_prefecture');
        const municipalities = allTerms.filter((t: any) => t.taxonomy === 'grant_municipality');
        const tags = allTerms.filter((t: any) => t.taxonomy === 'grant_tag');
        
        // 都道府県名の取得（最初の都道府県タームを使用）
        const prefectureName = prefectures.length > 0 ? prefectures[0].name : '全国';
        const prefectureSlug = prefectures.length > 0 ? prefectures[0].slug : 'nationwide';
        
        console.log('🔵 Taxonomies:', {
          categories: categories.length,
          prefectures: prefectures.length,
          municipalities: municipalities.length,
          tags: tags.length
        });
        
        // タイトルと説明文（HTMLタグを除去）
        const title = post.title?.rendered || '';
        const excerpt = post.excerpt?.rendered?.replace(/<[^>]*>/g, '') || '';
        const contentHtml = post.content?.rendered || '';
        
        // カテゴリー名とタグ名を配列から抽出
        const categoryNames = categories.map((c: any) => c.name).join(', ');
        const tagNames = tags.map((t: any) => t.name).join(', ');
        const municipalityNames = municipalities.map((m: any) => m.name).join(', ');
        
        // D1データベースに挿入または更新
        await db.prepare(`
          INSERT OR REPLACE INTO grants (
            wordpress_id,
            title,
            content,
            excerpt,
            status,
            prefecture_name,
            categories,
            tags,
            target_municipality,
            updated_at
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
        `).bind(
          post.id,                                    // wordpress_id
          title,                                      // title
          contentHtml,                                // content (HTML)
          excerpt,                                    // excerpt (plain text)
          post.status || 'publish',                   // status
          prefectureName,                             // prefecture_name
          categoryNames,                              // categories
          tagNames,                                   // tags
          municipalityNames                           // target_municipality
        ).run();
        
        console.log(`✅ Synced post ${post.id}: ${title}`);

        syncedCount++;
      } catch (error) {
        const errorMsg = error instanceof Error ? error.message : String(error);
        console.error(`❌ Error syncing post ${post.id}:`, errorMsg);
        console.error('Error details:', error);
        errors.push(`Post ${post.id}: ${errorMsg}`);
        errorCount++;
      }
    }

    // 同期ログをwp_sync_logテーブルに記録（テーブルが存在する場合のみ）
    try {
      await db.prepare(`
        INSERT INTO wp_sync_log (
          sync_type,
          synced_count,
          error_count,
          status,
          started_at,
          completed_at
        ) VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))
      `).bind(
        'full',
        syncedCount,
        errorCount,
        errorCount === 0 ? 'success' : 'partial'
      ).run();
    } catch (logError) {
      console.warn('Could not write to wp_sync_log table:', logError);
      // テーブルが存在しない場合は無視
    }

    return c.json({
      success: true,
      message: `WordPress sync completed: ${syncedCount} synced, ${errorCount} errors (batch: pages ${page}-${currentPage - 1})`,
      synced_count: syncedCount,
      error_count: errorCount,
      total_in_batch: allPosts.length,
      pages_processed: pagesProcessed,
      start_page: page,
      next_page: hasMore ? currentPage : null,
      has_more: hasMore,
      errors: errors.slice(0, 10), // 最初の10個のエラーメッセージ
    });
  } catch (error) {
    console.error('WordPress sync error:', error);
    return c.json({
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error',
    }, 500);
  }
});

// 特定のWordPress投稿を取得
wordpress.get('/posts/:id', async (c) => {
  try {
    const postId = c.req.param('id');
    const wpSiteUrl = c.env.WORDPRESS_SITE_URL || 'https://joseikin-insight.com';
    const wpApiUrl = `${wpSiteUrl}/wp-json/wp/v2/grants/${postId}?_embed=true`;
    
    const response = await fetch(wpApiUrl, {
      headers: {
        'Authorization': `Bearer ${c.env.WORDPRESS_API_TOKEN || ''}`,
      },
    });

    if (!response.ok) {
      throw new Error(`WordPress API error: ${response.statusText}`);
    }

    const post = await response.json();

    return c.json({
      success: true,
      data: post,
    });
  } catch (error) {
    console.error('WordPress fetch error:', error);
    return c.json({
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error',
    }, 500);
  }
});

// WordPressのWebhookエンドポイント（投稿更新時に自動同期）
wordpress.post('/webhook', async (c) => {
  try {
    const payload = await c.req.json();
    const db = c.env.DB;

    // Webhookのセキュリティチェック
    const webhookSecret = c.req.header('X-WP-Webhook-Secret');
    if (webhookSecret !== c.env.WORDPRESS_WEBHOOK_SECRET) {
      return c.json({ success: false, error: 'Invalid webhook secret' }, 401);
    }

    // 投稿データの取得
    const post = payload.post || payload;
    const acf = post.acf || {};

    // タクソノミーからデータ取得（オプション）
    const categories = post._embedded?.['wp:term']?.flat() || [];
    const prefectures = categories.filter((t: any) => t.taxonomy === 'prefecture');
    const prefectureName = prefectures.length > 0 ? prefectures[0].name : '';
    const prefectureSlug = prefectures.length > 0 ? prefectures[0].slug : '';

    // データ抽出
    const title = post.title?.rendered || '';
    const contentHtml = post.content?.rendered || '';
    const excerpt = post.excerpt?.rendered?.replace(/<[^>]*>/g, '') || '';

    // D1データベースに同期
    // 最小限のカラムのみ使用
    await db.prepare(`
      INSERT OR REPLACE INTO grants (
        wordpress_id,
        title,
        content,
        excerpt,
        status
      ) VALUES (?, ?, ?, ?, ?)
    `).bind(
      post.id,
      title,
      contentHtml,
      excerpt,
      post.status || 'publish'
    ).run();

    // Webhook同期ログを記録（テーブルが存在する場合のみ）
    try {
      await db.prepare(`
        INSERT INTO wp_sync_log (
          sync_type,
          synced_count,
          error_count,
          status,
          started_at,
          completed_at
        ) VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))
      `).bind('webhook', 1, 0, 'success').run();
    } catch (logError) {
      console.warn('Could not write to wp_sync_log table:', logError);
      // テーブルが存在しない場合は無視
    }

    return c.json({
      success: true,
      message: 'WordPress post synced successfully',
      post_id: post.id,
    });
  } catch (error) {
    console.error('WordPress webhook error:', error);
    return c.json({
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error',
    }, 500);
  }
});

// 同期状態の確認
wordpress.get('/sync-status', async (c) => {
  try {
    const db = c.env.DB;

    // D1データベースのWordPress連携データを確認
    const result = await db.prepare(`
      SELECT 
        COUNT(*) as total_grants,
        COUNT(CASE WHEN wp_post_id IS NOT NULL THEN 1 END) as wp_synced_grants,
        MAX(updated_at) as last_sync
      FROM grants
    `).first();

    return c.json({
      success: true,
      data: result,
    });
  } catch (error) {
    console.error('Sync status error:', error);
    return c.json({
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error',
    }, 500);
  }
});

export default wordpress;
