import { D1Database } from '@cloudflare/workers-types';

// JSONカラムのパース
export function parseJsonColumn(value: string | null): any {
  if (!value) return null;
  try {
    return JSON.parse(value);
  } catch {
    return null;
  }
}

// JSON配列のパース（categories, tagsなど）
export function parseJsonArray(value: string | null): string[] {
  if (!value) return [];
  try {
    const parsed = JSON.parse(value);
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    // カンマ区切りの場合
    return value.split(',').map(s => s.trim()).filter(Boolean);
  }
}

// JSONへの変換
export function toJsonString(value: any): string {
  if (value === null || value === undefined) return '';
  if (typeof value === 'string') return value;
  return JSON.stringify(value);
}

// D1クエリのエラーハンドリング
export async function executeQuery<T = any>(
  db: D1Database,
  query: string,
  params: any[] = []
): Promise<T[]> {
  try {
    const stmt = db.prepare(query);
    const result = await stmt.bind(...params).all();
    return result.results as T[];
  } catch (error) {
    console.error('Database query error:', error);
    throw new Error(`Database query failed: ${error}`);
  }
}

// 単一行取得
export async function fetchOne<T = any>(
  db: D1Database,
  query: string,
  params: any[] = []
): Promise<T | null> {
  const results = await executeQuery<T>(db, query, params);
  return results.length > 0 ? results[0] : null;
}

// 挿入実行
export async function insert(
  db: D1Database,
  table: string,
  data: Record<string, any>
): Promise<number> {
  const columns = Object.keys(data);
  const placeholders = columns.map(() => '?').join(', ');
  // undefined を null に変換（D1は undefined 非対応）
  const values = columns.map(col => data[col] === undefined ? null : data[col]);
  
  const query = `INSERT INTO ${table} (${columns.join(', ')}) VALUES (${placeholders})`;
  
  try {
    const stmt = db.prepare(query);
    const result = await stmt.bind(...values).run();
    return result.meta.last_row_id || 0;
  } catch (error) {
    console.error('Database insert error:', error);
    throw new Error(`Database insert failed: ${error}`);
  }
}

// 更新実行
export async function update(
  db: D1Database,
  table: string,
  data: Record<string, any>,
  where: string,
  whereParams: any[] = []
): Promise<number> {
  const columns = Object.keys(data);
  const setClause = columns.map(col => `${col} = ?`).join(', ');
  // undefined を null に変換（D1は undefined 非対応）
  const values = [...columns.map(col => data[col] === undefined ? null : data[col]), ...whereParams.map(p => p === undefined ? null : p)];
  
  const query = `UPDATE ${table} SET ${setClause} WHERE ${where}`;
  
  try {
    const stmt = db.prepare(query);
    const result = await stmt.bind(...values).run();
    return result.meta.changes || 0;
  } catch (error) {
    console.error('Database update error:', error);
    throw new Error(`Database update failed: ${error}`);
  }
}

// トランザクション実行（D1は未対応だが将来のために定義）
export async function transaction(
  db: D1Database,
  queries: Array<{ query: string; params: any[] }>
): Promise<void> {
  // D1では現在トランザクションは未サポート
  // 各クエリを順次実行
  for (const { query, params } of queries) {
    await executeQuery(db, query, params);
  }
}

// ページネーション
export interface PaginationOptions {
  page?: number;
  limit?: number;
}

export interface PaginatedResult<T> {
  data: T[];
  page: number;
  limit: number;
  total: number;
  totalPages: number;
}

export async function paginate<T = any>(
  db: D1Database,
  baseQuery: string,
  countQuery: string,
  params: any[] = [],
  options: PaginationOptions = {}
): Promise<PaginatedResult<T>> {
  const page = Math.max(1, options.page || 1);
  const limit = Math.max(1, Math.min(100, options.limit || 20));
  const offset = (page - 1) * limit;
  
  // 総数取得
  const countResult = await fetchOne<{ count: number }>(db, countQuery, params);
  const total = countResult?.count || 0;
  
  // データ取得
  const dataQuery = `${baseQuery} LIMIT ? OFFSET ?`;
  const data = await executeQuery<T>(db, dataQuery, [...params, limit, offset]);
  
  return {
    data,
    page,
    limit,
    total,
    totalPages: Math.ceil(total / limit)
  };
}

// 日付フォーマット（SQLite用）
export function formatDateForSql(date: Date): string {
  return date.toISOString().slice(0, 19).replace('T', ' ');
}

// SQLite日付のパース
export function parseSqlDate(sqlDate: string | null): Date | null {
  if (!sqlDate) return null;
  try {
    return new Date(sqlDate);
  } catch {
    return null;
  }
}

// LIKE検索のエスケープ
export function escapeLike(value: string): string {
  return value.replace(/[%_]/g, '\\$&');
}

// IN句のプレースホルダー生成
export function createInPlaceholders(count: number): string {
  return Array(count).fill('?').join(', ');
}
