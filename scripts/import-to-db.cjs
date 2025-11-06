/**
 * 変換済みデータをD1データベースに直接インポート
 */

const fs = require('fs');
const { spawn } = require('child_process');

async function importToDatabase(jsonFile) {
  console.log(`Reading transformed data from: ${jsonFile}`);
  const grants = JSON.parse(fs.readFileSync(jsonFile, 'utf-8'));
  
  console.log(`Total grants to import: ${grants.length}`);
  
  // バッチサイズ
  const BATCH_SIZE = 50;
  let imported = 0;
  let failed = 0;
  
  for (let i = 0; i < grants.length; i += BATCH_SIZE) {
    const batch = grants.slice(i, i + BATCH_SIZE);
    console.log(`\nProcessing batch ${Math.floor(i / BATCH_SIZE) + 1}/${Math.ceil(grants.length / BATCH_SIZE)} (${batch.length} grants)...`);
    
    // INSERT文を生成
    const insertStatements = batch.map(grant => {
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
    }).join('\n');
    
    // wranglerで実行
    try {
      await executeSQL(insertStatements);
      imported += batch.length;
      console.log(`✅ Batch imported successfully (total: ${imported}/${grants.length})`);
    } catch (error) {
      console.error(`❌ Batch failed:`, error.message);
      failed += batch.length;
    }
  }
  
  console.log(`\n=== Import Complete ===`);
  console.log(`Total: ${grants.length}`);
  console.log(`Imported: ${imported}`);
  console.log(`Failed: ${failed}`);
}

function escapeString(value) {
  if (value === null || value === undefined || value === 'null') {
    return 'NULL';
  }
  // SQLエスケープ: シングルクォートを2つにする
  const escaped = String(value).replace(/'/g, "''");
  return `'${escaped}'`;
}

function executeSQL(sql) {
  return new Promise((resolve, reject) => {
    const wrangler = spawn('npx', [
      'wrangler', 'd1', 'execute', 'grants-db',
      '--local',
      '--command', sql
    ], {
      cwd: '/home/user/webapp'
    });
    
    let stdout = '';
    let stderr = '';
    
    wrangler.stdout.on('data', (data) => {
      stdout += data.toString();
    });
    
    wrangler.stderr.on('data', (data) => {
      stderr += data.toString();
    });
    
    wrangler.on('close', (code) => {
      if (code === 0) {
        resolve(stdout);
      } else {
        reject(new Error(`Wrangler exited with code ${code}: ${stderr}`));
      }
    });
  });
}

// メイン処理
if (require.main === module) {
  const jsonFile = process.argv[2] || './grants_transformed.json';
  importToDatabase(jsonFile).catch(error => {
    console.error('Import failed:', error);
    process.exit(1);
  });
}
