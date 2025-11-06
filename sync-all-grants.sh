#!/bin/bash

# WordPress 全助成金データ同期スクリプト
# 使用方法: ./sync-all-grants.sh [API_URL]

API_URL="${1:-http://localhost:3000}"
PER_PAGE=100
MAX_PAGES=10
TOTAL_PAGES=80  # 8000件 ÷ 100件/ページ = 80ページ

echo "========================================="
echo "WordPress 全データ同期スクリプト"
echo "========================================="
echo "API URL: $API_URL"
echo "予想総件数: 8000件（80ページ）"
echo "バッチサイズ: ${MAX_PAGES}ページ/回"
echo "========================================="
echo ""

START_PAGE=1
BATCH_COUNT=0
TOTAL_SYNCED=0

while [ $START_PAGE -le $TOTAL_PAGES ]; do
    BATCH_COUNT=$((BATCH_COUNT + 1))
    echo "🔄 バッチ ${BATCH_COUNT}: ページ ${START_PAGE}-$((START_PAGE + MAX_PAGES - 1)) を同期中..."
    
    RESPONSE=$(curl -s "${API_URL}/api/wordpress/sync?page=${START_PAGE}&max_pages=${MAX_PAGES}")
    
    # JSONから結果を抽出
    SYNCED=$(echo "$RESPONSE" | jq -r '.synced_count // 0')
    ERRORS=$(echo "$RESPONSE" | jq -r '.error_count // 0')
    NEXT_PAGE=$(echo "$RESPONSE" | jq -r '.next_page // null')
    HAS_MORE=$(echo "$RESPONSE" | jq -r '.has_more // false')
    
    TOTAL_SYNCED=$((TOTAL_SYNCED + SYNCED))
    
    echo "   ✅ 同期完了: ${SYNCED}件 (エラー: ${ERRORS}件)"
    echo "   📊 累計: ${TOTAL_SYNCED}件"
    
    if [ "$HAS_MORE" == "false" ] || [ "$NEXT_PAGE" == "null" ]; then
        echo ""
        echo "✅ 全データの同期が完了しました！"
        break
    fi
    
    START_PAGE=$NEXT_PAGE
    
    # APIへの負荷を軽減するため、少し待機
    echo "   ⏳ 5秒待機..."
    sleep 5
    echo ""
done

echo ""
echo "========================================="
echo "同期完了サマリー"
echo "========================================="
echo "処理バッチ数: ${BATCH_COUNT}"
echo "同期件数: ${TOTAL_SYNCED}件"
echo ""

# 最終的な同期状態を確認
echo "📊 データベースの状態を確認中..."
curl -s "${API_URL}/api/wordpress/sync-status" | jq '.'

echo ""
echo "========================================="
echo "完了！"
echo "========================================="
