#!/bin/bash

################################################################################
# ウマ店トップページプラグイン デプロイスクリプト
#
# 使用方法:
#   ./deploy.sh [WordPress_PATH]
#
# 例:
#   ./deploy.sh /var/www/html/wp-content/plugins
#   ./deploy.sh ~/public_html/wp-content/plugins
################################################################################

set -e  # エラーが発生したら即座に終了

# 色付け用の定数
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# プラグイン名
PLUGIN_NAME="umaten-toppage"
PLUGIN_VERSION="1.1.0"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}ウマ店トップページプラグイン デプロイ${NC}"
echo -e "${BLUE}バージョン: ${PLUGIN_VERSION}${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# WordPressプラグインディレクトリのパスを取得
if [ -z "$1" ]; then
    echo -e "${YELLOW}WordPressプラグインディレクトリのパスを入力してください:${NC}"
    echo -e "${YELLOW}例: /var/www/html/wp-content/plugins${NC}"
    read -p "> " WP_PLUGINS_DIR
else
    WP_PLUGINS_DIR="$1"
fi

# パスの検証
if [ ! -d "$WP_PLUGINS_DIR" ]; then
    echo -e "${RED}エラー: ディレクトリが存在しません: $WP_PLUGINS_DIR${NC}"
    echo -e "${YELLOW}WordPressのプラグインディレクトリのパスを確認してください${NC}"
    exit 1
fi

# プラグインディレクトリのパス
PLUGIN_DIR="$WP_PLUGINS_DIR/$PLUGIN_NAME"

echo -e "${GREEN}[1/6] 最新のコードを取得中...${NC}"
git fetch origin
git pull origin claude/wordpress-plugin-pc-012SVzq5LAueetp4FLmDVPBE

echo ""
echo -e "${GREEN}[2/6] プラグインディレクトリを作成中...${NC}"
if [ -d "$PLUGIN_DIR" ]; then
    echo -e "${YELLOW}既存のプラグインディレクトリが見つかりました${NC}"
    echo -e "${YELLOW}バックアップを作成します...${NC}"
    BACKUP_DIR="${PLUGIN_DIR}_backup_$(date +%Y%m%d_%H%M%S)"
    cp -r "$PLUGIN_DIR" "$BACKUP_DIR"
    echo -e "${GREEN}バックアップ作成完了: $BACKUP_DIR${NC}"
else
    echo -e "${BLUE}新規にプラグインディレクトリを作成します${NC}"
    mkdir -p "$PLUGIN_DIR"
fi

echo ""
echo -e "${GREEN}[3/6] プラグインファイルをコピー中...${NC}"

# 必須ファイルのリスト
FILES_TO_COPY=(
    "umaten-toppage-plugin.php"
    "umaten-toppage.html"
    "README.md"
)

# ファイルをコピー
for file in "${FILES_TO_COPY[@]}"; do
    if [ -f "$file" ]; then
        cp "$file" "$PLUGIN_DIR/"
        echo -e "  ${GREEN}✓${NC} $file をコピーしました"
    else
        echo -e "  ${RED}✗${NC} $file が見つかりません"
        exit 1
    fi
done

echo ""
echo -e "${GREEN}[4/6] ファイルの権限を設定中...${NC}"
# WordPressが読み取れるように権限を設定
chmod 644 "$PLUGIN_DIR"/*.php
chmod 644 "$PLUGIN_DIR"/*.html
chmod 644 "$PLUGIN_DIR"/*.md
echo -e "${GREEN}権限設定完了${NC}"

echo ""
echo -e "${GREEN}[5/6] デプロイ情報を保存中...${NC}"
# デプロイ情報をファイルに保存
cat > "$PLUGIN_DIR/.deploy-info" <<EOF
デプロイ日時: $(date '+%Y-%m-%d %H:%M:%S')
バージョン: $PLUGIN_VERSION
Git コミット: $(git rev-parse HEAD)
Git ブランチ: $(git rev-parse --abbrev-ref HEAD)
デプロイ先: $PLUGIN_DIR
EOF
echo -e "${GREEN}デプロイ情報を保存しました${NC}"

echo ""
echo -e "${GREEN}[6/6] デプロイ完了確認中...${NC}"

# ファイルの存在確認
ALL_FILES_OK=true
for file in "${FILES_TO_COPY[@]}"; do
    if [ -f "$PLUGIN_DIR/$file" ]; then
        SIZE=$(stat -f%z "$PLUGIN_DIR/$file" 2>/dev/null || stat -c%s "$PLUGIN_DIR/$file" 2>/dev/null)
        echo -e "  ${GREEN}✓${NC} $file (${SIZE} bytes)"
    else
        echo -e "  ${RED}✗${NC} $file が見つかりません"
        ALL_FILES_OK=false
    fi
done

echo ""
echo -e "${BLUE}========================================${NC}"
if [ "$ALL_FILES_OK" = true ]; then
    echo -e "${GREEN}✓ デプロイが正常に完了しました！${NC}"
else
    echo -e "${RED}✗ デプロイ中にエラーが発生しました${NC}"
    exit 1
fi
echo -e "${BLUE}========================================${NC}"

echo ""
echo -e "${YELLOW}次のステップ:${NC}"
echo -e "1. WordPressダッシュボードにログイン"
echo -e "2. 「プラグイン」メニューを開く"
echo -e "3. 「ウマ店トップページ」プラグインを探す"
echo -e "4. すでに有効化されている場合は、一度無効化して再度有効化"
echo -e "5. 固定ページに ${GREEN}[umaten_toppage]${NC} ショートコードを追加"
echo ""
echo -e "${BLUE}プラグインディレクトリ: $PLUGIN_DIR${NC}"
echo -e "${BLUE}分析スクリプト実行: ./check-plugin.sh $PLUGIN_DIR${NC}"
echo ""
