#!/bin/bash
# WordPress全データ同期スクリプト（8000件）

echo "🚀 Starting WordPress sync for 8000+ grants..."
echo "⏱️  This will take approximately 15-20 minutes..."
echo ""

total_synced=0

for start_page in 1 11 21 31 41 51 61 71; do
  end_page=$((start_page + 9))
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
  echo "📥 Syncing pages ${start_page}-${end_page}..."
  
  response=$(curl -s "https://matching-public.pages.dev/api/wordpress/sync?page=${start_page}&per_page=100&max_pages=10")
  
  # Parse response
  success=$(echo "$response" | jq -r '.success')
  synced=$(echo "$response" | jq -r '.synced_count')
  errors=$(echo "$response" | jq -r '.error_count')
  
  if [ "$success" = "true" ]; then
    total_synced=$((total_synced + synced))
    echo "✅ Synced: $synced grants (Errors: $errors)"
    echo "📊 Total so far: $total_synced grants"
  else
    echo "❌ Error: $response"
  fi
  
  echo "⏳ Waiting 3 seconds before next batch..."
  sleep 3
  echo ""
done

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎉 Sync complete!"
echo "📊 Total synced: $total_synced grants"
echo ""
echo "🔍 Verifying database..."
curl -s "https://matching-public.pages.dev/api/test/db-status" | jq '.data.total_grants'
echo ""
echo "✅ Done! Your application is ready at:"
echo "   https://matching-public.pages.dev"
