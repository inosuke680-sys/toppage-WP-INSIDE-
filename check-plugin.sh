#!/bin/bash

################################################################################
# ウマ店トップページプラグイン 分析スクリプト
#
# 使用方法:
#   ./check-plugin.sh [PLUGIN_PATH]
#
# 例:
#   ./check-plugin.sh /var/www/html/wp-content/plugins/umaten-toppage
################################################################################

# 色付け用の定数
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# プラグインディレクトリのパス
if [ -z "$1" ]; then
    echo -e "${YELLOW}プラグインディレクトリのパスを入力してください:${NC}"
    read -p "> " PLUGIN_DIR
else
    PLUGIN_DIR="$1"
fi

# パスの検証
if [ ! -d "$PLUGIN_DIR" ]; then
    echo -e "${RED}エラー: ディレクトリが存在しません: $PLUGIN_DIR${NC}"
    exit 1
fi

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}プラグイン分析レポート${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# 1. ディレクトリ情報
echo -e "${CYAN}[1] ディレクトリ情報${NC}"
echo -e "パス: ${GREEN}$PLUGIN_DIR${NC}"
echo -e "所有者: $(ls -ld "$PLUGIN_DIR" | awk '{print $3":"$4}')"
echo -e "権限: $(ls -ld "$PLUGIN_DIR" | awk '{print $1}')"
echo ""

# 2. ファイル一覧と詳細
echo -e "${CYAN}[2] ファイル一覧${NC}"
REQUIRED_FILES=(
    "umaten-toppage-plugin.php"
    "umaten-toppage.html"
    "README.md"
)

ALL_FILES_OK=true
for file in "${REQUIRED_FILES[@]}"; do
    FILE_PATH="$PLUGIN_DIR/$file"
    if [ -f "$FILE_PATH" ]; then
        SIZE=$(stat -f%z "$FILE_PATH" 2>/dev/null || stat -c%s "$FILE_PATH" 2>/dev/null)
        MODIFIED=$(stat -f "%Sm" -t "%Y-%m-%d %H:%M:%S" "$FILE_PATH" 2>/dev/null || stat -c "%y" "$FILE_PATH" 2>/dev/null | cut -d. -f1)
        PERMS=$(ls -l "$FILE_PATH" | awk '{print $1}')
        echo -e "  ${GREEN}✓${NC} $file"
        echo -e "    サイズ: ${SIZE} bytes"
        echo -e "    更新日時: ${MODIFIED}"
        echo -e "    権限: ${PERMS}"
    else
        echo -e "  ${RED}✗${NC} $file ${RED}(見つかりません)${NC}"
        ALL_FILES_OK=false
    fi
done
echo ""

# 3. プラグインバージョン確認
echo -e "${CYAN}[3] プラグインバージョン情報${NC}"
PLUGIN_FILE="$PLUGIN_DIR/umaten-toppage-plugin.php"
if [ -f "$PLUGIN_FILE" ]; then
    VERSION=$(grep "Version:" "$PLUGIN_FILE" | head -1 | sed 's/.*Version: *//' | tr -d '\r')
    PLUGIN_NAME=$(grep "Plugin Name:" "$PLUGIN_FILE" | head -1 | sed 's/.*Plugin Name: *//' | tr -d '\r')
    echo -e "  プラグイン名: ${GREEN}$PLUGIN_NAME${NC}"
    echo -e "  バージョン: ${GREEN}$VERSION${NC}"

    if [ "$VERSION" = "1.1.0" ]; then
        echo -e "  ${GREEN}✓ 最新バージョンです${NC}"
    else
        echo -e "  ${YELLOW}⚠ バージョンが異なります (期待値: 1.1.0)${NC}"
    fi
else
    echo -e "  ${RED}✗ プラグインファイルが見つかりません${NC}"
fi
echo ""

# 4. HTMLファイルの重要な修正箇所をチェック
echo -e "${CYAN}[4] スマホ対応修正の確認${NC}"
HTML_FILE="$PLUGIN_DIR/umaten-toppage.html"
if [ -f "$HTML_FILE" ]; then
    # 修正1: !importantが追加されているか
    if grep -q "\.meshimap-area-content.active.*display: block !important" "$HTML_FILE"; then
        echo -e "  ${GREEN}✓${NC} CSS修正1: .meshimap-area-content.active に !important が追加されています"
    else
        echo -e "  ${RED}✗${NC} CSS修正1: .meshimap-area-content.active の修正が見つかりません"
    fi

    # 修正2: スマホ用のgrid設定
    if grep -q "grid-template-columns: 1fr !important" "$HTML_FILE"; then
        echo -e "  ${GREEN}✓${NC} CSS修正2: スマホ用のgrid-template-columns設定があります"
    else
        echo -e "  ${RED}✗${NC} CSS修正2: スマホ用のgrid設定が見つかりません"
    fi

    # 修正3: JavaScript初期化処理
    if grep -q "function showInitialArea()" "$HTML_FILE"; then
        echo -e "  ${GREEN}✓${NC} JS修正1: showInitialArea関数が存在します"
    else
        echo -e "  ${RED}✗${NC} JS修正1: showInitialArea関数が見つかりません"
    fi

    # 修正4: style.display の設定
    if grep -q "content\.style\.display = 'none'" "$HTML_FILE"; then
        echo -e "  ${GREEN}✓${NC} JS修正2: インラインスタイルによる表示制御があります"
    else
        echo -e "  ${RED}✗${NC} JS修正2: インラインスタイル制御が見つかりません"
    fi

    # 修正5: コンソールログ
    if grep -q "console\.log('北海道エリアを表示しました')" "$HTML_FILE"; then
        echo -e "  ${GREEN}✓${NC} JS修正3: デバッグ用コンソールログがあります"
    else
        echo -e "  ${YELLOW}⚠${NC} JS修正3: デバッグ用ログが見つかりません"
    fi

    # HTMLファイルの行数
    LINE_COUNT=$(wc -l < "$HTML_FILE")
    echo -e "  HTMLファイル行数: ${LINE_COUNT} 行"

    # 期待される行数（修正版は約935行）
    if [ "$LINE_COUNT" -gt 900 ]; then
        echo -e "  ${GREEN}✓${NC} 行数が期待値の範囲内です (900行以上)"
    else
        echo -e "  ${YELLOW}⚠${NC} 行数が少ない可能性があります (期待値: 935行前後)"
    fi
else
    echo -e "  ${RED}✗ HTMLファイルが見つかりません${NC}"
fi
echo ""

# 5. デプロイ情報
echo -e "${CYAN}[5] デプロイ情報${NC}"
DEPLOY_INFO="$PLUGIN_DIR/.deploy-info"
if [ -f "$DEPLOY_INFO" ]; then
    cat "$DEPLOY_INFO" | while IFS= read -r line; do
        echo -e "  $line"
    done
else
    echo -e "  ${YELLOW}デプロイ情報ファイルが見つかりません${NC}"
    echo -e "  ${YELLOW}(初回インストールまたは手動コピーの可能性があります)${NC}"
fi
echo ""

# 6. 総合評価
echo -e "${CYAN}[6] 総合評価${NC}"
ISSUES_COUNT=0

# ファイルチェック
if [ "$ALL_FILES_OK" = false ]; then
    echo -e "  ${RED}✗${NC} 必須ファイルが不足しています"
    ISSUES_COUNT=$((ISSUES_COUNT + 1))
fi

# バージョンチェック
if [ -f "$PLUGIN_FILE" ]; then
    VERSION=$(grep "Version:" "$PLUGIN_FILE" | head -1 | sed 's/.*Version: *//' | tr -d '\r')
    if [ "$VERSION" != "1.1.0" ]; then
        echo -e "  ${YELLOW}⚠${NC} バージョンが最新ではありません"
        ISSUES_COUNT=$((ISSUES_COUNT + 1))
    fi
fi

# HTMLファイルチェック
if [ -f "$HTML_FILE" ]; then
    if ! grep -q "display: block !important" "$HTML_FILE"; then
        echo -e "  ${RED}✗${NC} 重要なCSS修正が適用されていません"
        ISSUES_COUNT=$((ISSUES_COUNT + 1))
    fi

    if ! grep -q "function showInitialArea()" "$HTML_FILE"; then
        echo -e "  ${RED}✗${NC} 重要なJavaScript修正が適用されていません"
        ISSUES_COUNT=$((ISSUES_COUNT + 1))
    fi
fi

echo ""
if [ $ISSUES_COUNT -eq 0 ]; then
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}✓ プラグインは正常に更新されています！${NC}"
    echo -e "${GREEN}========================================${NC}"
else
    echo -e "${RED}========================================${NC}"
    echo -e "${RED}✗ $ISSUES_COUNT 個の問題が見つかりました${NC}"
    echo -e "${RED}プラグインを再デプロイしてください${NC}"
    echo -e "${RED}========================================${NC}"
fi

echo ""
echo -e "${YELLOW}推奨アクション:${NC}"
if [ $ISSUES_COUNT -eq 0 ]; then
    echo -e "1. WordPressダッシュボードでプラグインを再有効化"
    echo -e "2. ブラウザのキャッシュをクリア"
    echo -e "3. スマホでアクセスして都道府県カードが表示されるか確認"
    echo -e "4. ブラウザの開発者ツールでコンソールログを確認"
else
    echo -e "1. ./deploy.sh を実行してプラグインを再デプロイ"
    echo -e "2. このスクリプトを再度実行して問題が解決したか確認"
fi
echo ""

# 7. ファイル比較（オプション）
echo -e "${CYAN}[7] ファイル差分チェック（オプション）${NC}"
echo -e "${YELLOW}Gitリポジトリと比較しますか? (y/n)${NC}"
read -p "> " DO_DIFF

if [ "$DO_DIFF" = "y" ] || [ "$DO_DIFF" = "Y" ]; then
    REPO_DIR=$(dirname "$(readlink -f "$0")")
    for file in "${REQUIRED_FILES[@]}"; do
        if [ -f "$PLUGIN_DIR/$file" ] && [ -f "$REPO_DIR/$file" ]; then
            echo -e "${BLUE}比較中: $file${NC}"
            DIFF_OUTPUT=$(diff "$REPO_DIR/$file" "$PLUGIN_DIR/$file" 2>&1)
            if [ -z "$DIFF_OUTPUT" ]; then
                echo -e "  ${GREEN}✓ ファイルは同一です${NC}"
            else
                echo -e "  ${YELLOW}⚠ ファイルに差分があります:${NC}"
                echo "$DIFF_OUTPUT" | head -20
                echo -e "  ${YELLOW}...（最初の20行のみ表示）${NC}"
            fi
        fi
    done
fi

echo ""
echo -e "${BLUE}分析完了${NC}"
