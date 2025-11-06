const fs = require('fs');
const { execSync } = require('child_process');

const data = JSON.parse(fs.readFileSync('grants_transformed.json', 'utf8'));
const batchSize = 10; // より小さいバッチ

console.log(`Total records: ${data.length}`);
let successCount = 0;
let failCount = 0;

for (let i = 0; i < data.length; i += batchSize) {
  const batch = data.slice(i, i + batchSize);
  console.log(`Processing batch ${Math.floor(i/batchSize) + 1}/${Math.ceil(data.length/batchSize)} (records ${i+1}-${Math.min(i+batchSize, data.length)})...`);
  
  // 各レコードを個別のINSERT文として実行（contentを除外）
  for (const grant of batch) {
    const escape = (val) => {
      if (val === null || val === undefined) return 'NULL';
      if (typeof val === 'number') return val;
      if (Array.isArray(val)) val = JSON.stringify(val);
      return "'" + String(val).replace(/'/g, "''").substring(0, 10000) + "'"; // 最大10000文字
    };
    
    // contentを除外したINSERT
    const sql = `INSERT OR REPLACE INTO grants (
      wordpress_id, title, excerpt, status, 
      created_at, updated_at,
      max_amount_display, max_amount_numeric,
      deadline_display, deadline_date,
      organization, organization_type, grant_target,
      application_method, contact_info, official_url,
      target_prefecture_code, prefecture_name, target_municipality,
      regional_limitation, application_status,
      categories, tags
    ) VALUES (
      ${grant.wordpress_id},
      ${escape(grant.title)},
      ${escape(grant.excerpt)},
      ${escape(grant.status)},
      ${escape(grant.created_at)},
      ${escape(grant.updated_at)},
      ${escape(grant.max_amount_display)},
      ${grant.max_amount_numeric || 'NULL'},
      ${escape(grant.deadline_display)},
      ${escape(grant.deadline_date)},
      ${escape(grant.organization)},
      ${escape(grant.organization_type)},
      ${escape(grant.grant_target)},
      ${escape(grant.application_method)},
      ${escape(grant.contact_info)},
      ${escape(grant.official_url)},
      ${escape(grant.target_prefecture_code)},
      ${escape(grant.prefecture_name)},
      ${escape(grant.target_municipality)},
      ${escape(grant.regional_limitation)},
      ${escape(grant.application_status)},
      ${escape(JSON.stringify(grant.categories))},
      ${escape(JSON.stringify(grant.tags))}
    );`;
    
    fs.writeFileSync('single_temp.sql', sql);
    
    try {
      execSync('npx wrangler d1 execute grants-db --local --file=./single_temp.sql 2>/dev/null', {stdio: 'pipe'});
      successCount++;
      process.stdout.write('.');
    } catch (error) {
      failCount++;
      process.stdout.write('X');
    }
  }
  console.log('');
}

fs.unlinkSync('single_temp.sql');
console.log(`\n✅ Import completed! Success: ${successCount}, Failed: ${failCount}`);
