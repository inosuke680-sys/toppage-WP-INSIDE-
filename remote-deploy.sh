#!/bin/bash
# 本番サーバーにデプロイスクリプトを転送して実行

REMOTE_USER="kusanagi"
REMOTE_HOST="umaten.jp"
SCRIPT_NAME="deploy-production.sh"

echo "=========================================="
echo "  リモートデプロイ実行"
echo "=========================================="
echo ""

# スクリプトを本番サーバーに転送
echo "📤 デプロイスクリプトをサーバーに転送中..."
scp "$SCRIPT_NAME" ${REMOTE_USER}@${REMOTE_HOST}:/tmp/

if [ $? -ne 0 ]; then
    echo "❌ スクリプト転送に失敗しました"
    exit 1
fi

echo "✓ 転送完了"
echo ""

# SSH接続してスクリプトを実行
echo "🚀 本番サーバーでデプロイを実行中..."
echo "----------------------------------------"
ssh ${REMOTE_USER}@${REMOTE_HOST} "chmod +x /tmp/$SCRIPT_NAME && sudo /tmp/$SCRIPT_NAME"

if [ $? -eq 0 ]; then
    echo ""
    echo "=========================================="
    echo "  ✅ リモートデプロイ完了"
    echo "=========================================="
else
    echo ""
    echo "=========================================="
    echo "  ❌ デプロイに失敗しました"
    echo "=========================================="
    exit 1
fi
