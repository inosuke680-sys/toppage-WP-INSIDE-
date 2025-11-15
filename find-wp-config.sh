#!/bin/bash
# wp-config.php の場所を見つけるスクリプト

echo "=== wp-config.php を検索中... ==="
echo ""

# Kusanagi環境のデフォルトパス
KUSANAGI_PATH="/home/kusanagi/45515055731ac663c7c3ad4c"

if [ -f "${KUSANAGI_PATH}/wp-config.php" ]; then
    echo "✓ 見つかりました: ${KUSANAGI_PATH}/wp-config.php"
    echo ""
    echo "デバッグモードを有効にするには、以下のコマンドを実行してください："
    echo ""
    echo "sudo nano ${KUSANAGI_PATH}/wp-config.php"
    echo ""
    echo "そして、以下の行を追加してください（「編集が必要なのはここまでです」の上）："
    echo ""
    echo "define('WP_DEBUG', true);"
    echo "define('WP_DEBUG_LOG', true);"
    echo "define('WP_DEBUG_DISPLAY', false);"
    exit 0
fi

# DocumentRoot内を検索
if [ -f "${KUSANAGI_PATH}/DocumentRoot/wp-config.php" ]; then
    echo "✓ 見つかりました: ${KUSANAGI_PATH}/DocumentRoot/wp-config.php"
    echo ""
    echo "デバッグモードを有効にするには、以下のコマンドを実行してください："
    echo ""
    echo "sudo nano ${KUSANAGI_PATH}/DocumentRoot/wp-config.php"
    echo ""
    echo "そして、以下の行を追加してください（「編集が必要なのはここまでです」の上）："
    echo ""
    echo "define('WP_DEBUG', true);"
    echo "define('WP_DEBUG_LOG', true);"
    echo "define('WP_DEBUG_DISPLAY', false);"
    exit 0
fi

# 見つからない場合は検索
echo "デフォルトパスに見つかりませんでした。検索中..."
WP_CONFIG=$(find /home/kusanagi -name "wp-config.php" 2>/dev/null | grep -v "wp-staging" | grep -v "duplicator" | head -1)

if [ -n "$WP_CONFIG" ]; then
    echo "✓ 見つかりました: $WP_CONFIG"
    echo ""
    echo "デバッグモードを有効にするには、以下のコマンドを実行してください："
    echo ""
    echo "sudo nano $WP_CONFIG"
else
    echo "✗ wp-config.php が見つかりませんでした"
    exit 1
fi
