/**
 * 変換済みJSONからSQL INSERTファイルを生成
 */

const fs = require('fs');

function escapeString(value) {
  if (value === null || value === undefined || value === 'null') {
    return 'NULL';
  }
  // SQLエスケープ: シングルクォートを2つにする
  const escaped = String(value).replace(/'/g, "''");
  return `'${escaped}'`;
}

function generateSQL(grants) {
  const statements = grants.map(grant => {
    const values = [
      grant.wordpress_id || 'NULL',
      escapeString(grant.title),
      escapeString(grant.content),
      escapeString(grant.excerpt),
      escapeString(grant.status || 'publish'),
      escapeString(grant.created_at),
      escapeString(grant.updated_at),
      escapeString(grant.max_amount_display),
      grant.max_amount_numeric || 'NULL',
      escapeString(grant.deadline_display),
      escapeString(grant.deadline_date),
      escapeString(grant.organization),
      escapeString(grant.organization_type),
      escapeString(grant.grant_target),
      escapeString(grant.application_method),
      escapeString(grant.contact_info),
      escapeString(grant.official_url),
      escapeString(grant.target_prefecture_code),
      escapeString(grant.prefecture_name),
      escapeString(grant.target_municipality),
      escapeString(grant.regional_limitation),
      escapeString(grant.application_status),
      escapeString(grant.categories),
      escapeString(grant.tags)
    ];
    
    return `INSERT INTO grants (
  wordpress_id, title, content, excerpt, status,
  created_at, updated_at,
  max_amount_display, max_amount_numeric,
  deadline_display, deadline_date,
  organization, organization_type,
  grant_target, application_method, contact_info, official_url,
  target_prefecture_code, prefecture_name, target_municipality,
  regional_limitation, application_status,
  categories, tags
) VALUES (${values.join(', ')});`;
  });
  
  return statements.join('\n\n');
}

// メイン処理
if (require.main === module) {
  const jsonFile = process.argv[2] || './grants_transformed.json';
  const sqlFile = process.argv[3] || './grants_import.sql';
  
  console.log(`Reading from: ${jsonFile}`);
  const grants = JSON.parse(fs.readFileSync(jsonFile, 'utf-8'));
  
  console.log(`Generating SQL for ${grants.length} grants...`);
  const sql = generateSQL(grants);
  
  console.log(`Writing to: ${sqlFile}`);
  fs.writeFileSync(sqlFile, sql, 'utf-8');
  
  console.log(`✅ SQL file generated! (${Math.round(sql.length / 1024 / 1024 * 100) / 100} MB)`);
}
