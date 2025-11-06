const fs = require('fs');
const { execSync } = require('child_process');

const data = JSON.parse(fs.readFileSync('grants_transformed.json', 'utf8'));
const batchSize = 50;

console.log(`Total records: ${data.length}`);

for (let i = 0; i < data.length; i += batchSize) {
  const batch = data.slice(i, i + batchSize);
  console.log(`\nProcessing batch ${Math.floor(i/batchSize) + 1}/${Math.ceil(data.length/batchSize)} (records ${i+1}-${Math.min(i+batchSize, data.length)})...`);
  
  const values = batch.map(grant => {
    const escape = (val) => {
      if (val === null || val === undefined) return 'NULL';
      if (typeof val === 'number') return val;
      if (Array.isArray(val)) val = JSON.stringify(val);
      return "'" + String(val).replace(/'/g, "''") + "'";
    };
    
    return `(${grant.wordpress_id}, ${escape(grant.title)}, ${escape(grant.content)}, ${escape(grant.excerpt)}, ${escape(grant.status)}, ${escape(grant.created_at)}, ${escape(grant.updated_at)}, ${escape(grant.max_amount_display)}, ${grant.max_amount_numeric || 'NULL'}, ${escape(grant.deadline_display)}, ${escape(grant.deadline_date)}, ${escape(grant.organization)}, ${escape(grant.organization_type)}, ${escape(grant.grant_target)}, ${escape(grant.application_method)}, ${escape(grant.contact_info)}, ${escape(grant.official_url)}, ${escape(grant.target_prefecture_code)}, ${escape(grant.prefecture_name)}, ${escape(grant.target_municipality)}, ${escape(grant.regional_limitation)}, ${escape(grant.application_status)}, ${escape(JSON.stringify(grant.categories))}, ${escape(JSON.stringify(grant.tags))})`;
  }).join(',\n');
  
  const sql = `INSERT OR REPLACE INTO grants (wordpress_id, title, content, excerpt, status, created_at, updated_at, max_amount_display, max_amount_numeric, deadline_display, deadline_date, organization, organization_type, grant_target, application_method, contact_info, official_url, target_prefecture_code, prefecture_name, target_municipality, regional_limitation, application_status, categories, tags) VALUES ${values}`;
  
  fs.writeFileSync('batch_temp.sql', sql);
  
  try {
    execSync('npx wrangler d1 execute grants-db --local --file=./batch_temp.sql', {stdio: 'inherit'});
    console.log(`✅ Batch ${Math.floor(i/batchSize) + 1} completed`);
  } catch (error) {
    console.error(`❌ Batch ${Math.floor(i/batchSize) + 1} failed:`, error.message);
  }
}

fs.unlinkSync('batch_temp.sql');
console.log('\n✅ Import completed!');
