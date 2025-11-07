#!/bin/bash

# WordPress補助金データの完全同期スクリプト (8000件対応)
# カテゴリー、都道府県、タグ情報を含む完全なデータ取得

BASE_URL="https://matching-public.pages.dev/api/wordpress/sync"
TOTAL_PAGES=80  # 7949件 ÷ 100 = 80ページ
BATCH_SIZE=10   # 一度に10ページずつ処理
DELAY=3         # バッチ間の待機時間(秒)

echo "🚀 WordPress補助金データ完全同期開始"
echo "📊 予想件数: 約7,949件"
echo "📄 総ページ数: ${TOTAL_PAGES}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

total_synced=0
total_errors=0

# ページをバッチで処理
for ((start_page=1; start_page<=TOTAL_PAGES; start_page+=BATCH_SIZE)); do
    end_page=$((start_page + BATCH_SIZE - 1))
    if [ $end_page -gt $TOTAL_PAGES ]; then
        end_page=$TOTAL_PAGES
    fi
    
    echo "📥 Syncing pages ${start_page}-${end_page}..."
    
    # APIリクエスト実行
    response=$(curl -s "${BASE_URL}?page=${start_page}&per_page=100&max_pages=${BATCH_SIZE}")
    
    # レスポンスを解析
    synced=$(echo "$response" | jq -r '.synced_count // 0')
    errors=$(echo "$response" | jq -r '.error_count // 0')
    success=$(echo "$response" | jq -r '.success // false')
    
    if [ "$success" = "true" ]; then
        total_synced=$((total_synced + synced))
        total_errors=$((total_errors + errors))
        
        echo "✅ Synced: ${synced} grants (Errors: ${errors})"
        echo "📊 Total so far: ${total_synced} grants"
    else
        error_msg=$(echo "$response" | jq -r '.error // "Unknown error"')
        echo "❌ Batch failed: ${error_msg}"
        total_errors=$((total_errors + BATCH_SIZE * 100))
    fi
    
    # バッチ間の待機（APIレート制限対策）
    if [ $end_page -lt $TOTAL_PAGES ]; then
        echo "⏳ Waiting ${DELAY} seconds before next batch..."
        echo ""
        sleep $DELAY
    fi
done

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎉 同期完了！"
echo "✅ 成功: ${total_synced} grants"
echo "❌ エラー: ${total_errors} grants"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📊 データベースの統計を確認:"
curl -s "https://matching-public.pages.dev/api/grants/stats/summary" | jq '.'
