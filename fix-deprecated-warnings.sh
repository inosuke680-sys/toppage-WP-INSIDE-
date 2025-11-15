#!/bin/bash
# WordPress非推奨警告を非表示にするスクリプト

echo "=== WordPress 非推奨警告を非表示にします ==="
echo ""

# wp-config.phpの場所を検索
WP_CONFIG="/home/kusanagi/45515055731ac663c7c3ad4c/wp-config.php"

if [ ! -f "$WP_CONFIG" ]; then
    echo "✗ wp-config.php が見つかりません: $WP_CONFIG"
    exit 1
fi

echo "✓ wp-config.php を見つけました: $WP_CONFIG"
echo ""

# バックアップを作成
BACKUP="${WP_CONFIG}.backup.$(date +%Y%m%d_%H%M%S)"
sudo cp "$WP_CONFIG" "$BACKUP"
echo "✓ バックアップを作成しました: $BACKUP"
echo ""

# 現在の設定を確認
echo "現在のデバッグ設定:"
sudo grep -E "WP_DEBUG|display_errors" "$WP_CONFIG" || echo "  デバッグ設定が見つかりません"
echo ""

echo "以下の設定を wp-config.php に追加する必要があります："
echo ""
echo "define('WP_DEBUG', true);"
echo "define('WP_DEBUG_LOG', true);"
echo "define('WP_DEBUG_DISPLAY', false);"
echo "@ini_set('display_errors', 0);"
echo ""
echo "手動で編集してください："
echo "sudo nano $WP_CONFIG"
echo ""
echo "または、以下のコマンドで自動的に追加できます（既存の設定がある場合は手動で確認してください）："
echo ""
cat << 'EOFCMD'
sudo sed -i "/\/\* That's all, stop editing/i \\
// デバッグ設定（非推奨警告を非表示）\\
define('WP_DEBUG', true);\\
define('WP_DEBUG_LOG', true);\\
define('WP_DEBUG_DISPLAY', false);\\
@ini_set('display_errors', 0);\\
" /home/kusanagi/45515055731ac663c7c3ad4c/wp-config.php
EOFCMD
