#!/bin/bash

# WordPress データを ACF フィールドを含めて再同期
# 使用方法: bash resync_with_acf_fields.sh

BASE_URL="https://matching-public.pages.dev/api/wordpress/sync"
TOTAL_PAGES=80  # 約8,000件（100件/ページ × 80ページ）
BATCH_SIZE=10   # 一度に10ページずつ処理
DELAY=3         # リクエスト間隔（秒）

echo "🔄 Starting WordPress ACF data resync..."
echo "📊 Total pages: $TOTAL_PAGES"
echo "📦 Batch size: $BATCH_SIZE pages"
echo "⏱️  Delay between batches: ${DELAY}s"
echo ""

synced_total=0
error_count=0

for ((start_page=1; start_page<=TOTAL_PAGES; start_page+=BATCH_SIZE)); do
  echo "📥 Processing batch: pages $start_page to $((start_page + BATCH_SIZE - 1))"
  
  response=$(curl -s "${BASE_URL}?page=${start_page}&per_page=100&max_pages=${BATCH_SIZE}")
  
  # レスポンスから同期数を抽出
  synced=$(echo "$response" | grep -o '"synced_count":[0-9]*' | head -1 | cut -d':' -f2)
  errors=$(echo "$response" | grep -o '"error_count":[0-9]*' | head -1 | cut -d':' -f2)
  
  if [ ! -z "$synced" ]; then
    synced_total=$((synced_total + synced))
    echo "✅ Synced: $synced grants (Total: $synced_total)"
  fi
  
  if [ ! -z "$errors" ] && [ "$errors" -gt 0 ]; then
    error_count=$((error_count + errors))
    echo "⚠️  Errors: $errors"
  fi
  
  # 進捗表示
  progress=$((start_page * 100 / TOTAL_PAGES))
  echo "📊 Progress: ${progress}% ($start_page / $TOTAL_PAGES pages)"
  echo ""
  
  # 最後のバッチ以外は待機
  if [ $start_page -lt $TOTAL_PAGES ]; then
    sleep $DELAY
  fi
done

echo ""
echo "🎉 Resync complete!"
echo "✅ Total grants synced: $synced_total"
if [ $error_count -gt 0 ]; then
  echo "⚠️  Total errors: $error_count"
fi
echo ""
echo "🔍 Note: Check logs for ACF field extraction details"
echo "🌐 Visit: https://matching-public.pages.dev"
