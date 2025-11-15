#!/bin/bash

################################################################################
# WP Hero Image Manager v2.8.4 - Production Deployment Script
#
# このスクリプトは本番環境にプラグインを安全にデプロイします
#
# 使用方法:
#   curl -o /tmp/deploy-wp-hero-image.sh https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/fix-duplicate-hero-image-01Ja5EAk1knXE9Hf1Bv33uEm/deploy-production.sh
#   chmod +x /tmp/deploy-wp-hero-image.sh
#   sudo /tmp/deploy-wp-hero-image.sh
#
################################################################################

set -e  # エラー時に即座に終了

# カラー出力
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ログ関数
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 設定
PLUGIN_NAME="wp-hero-image-manager"
PLUGIN_VERSION="2.8.4"
GITHUB_REPO="inosuke680-sys/toppage-WP-INSIDE-"
GITHUB_BRANCH="claude/fix-duplicate-hero-image-01Ja5EAk1knXE9Hf1Bv33uEm"
GITHUB_RAW_BASE="https://raw.githubusercontent.com/${GITHUB_REPO}/${GITHUB_BRANCH}"

# WordPressのパスを検出
WORDPRESS_PATH=""
POSSIBLE_PATHS=(
    "/var/www/html"
    "/var/www/wordpress"
    "/usr/share/nginx/html"
    "/home/*/public_html"
    "/var/www/vhosts/*/httpdocs"
    "/home/kusanagi/*/DocumentRoot"
)

# バナー表示
print_banner() {
    echo ""
    echo "╔═══════════════════════════════════════════════════════════╗"
    echo "║                                                           ║"
    echo "║        WP Hero Image Manager v${PLUGIN_VERSION}                  ║"
    echo "║        Production Deployment Script                      ║"
    echo "║                                                           ║"
    echo "╚═══════════════════════════════════════════════════════════╝"
    echo ""
}

# root権限チェック
check_root() {
    if [ "$EUID" -ne 0 ]; then
        log_error "このスクリプトはroot権限で実行する必要があります"
        log_info "sudo $0 を使用してください"
        exit 1
    fi
}

# WordPressのパスを検出
detect_wordpress_path() {
    log_info "WordPressインストールを検索中..."

    for path_pattern in "${POSSIBLE_PATHS[@]}"; do
        for path in $path_pattern; do
            if [ -f "$path/wp-config.php" ]; then
                WORDPRESS_PATH="$path"
                log_success "WordPressが見つかりました: $WORDPRESS_PATH"
                return 0
            fi
        done
    done

    # 見つからない場合は手動入力
    log_warning "WordPressのパスを自動検出できませんでした"
    read -p "WordPressのパスを入力してください (例: /var/www/html): " WORDPRESS_PATH

    if [ ! -f "$WORDPRESS_PATH/wp-config.php" ]; then
        log_error "指定されたパスにWordPressが見つかりません: $WORDPRESS_PATH"
        exit 1
    fi
}

# プラグインディレクトリのパス
get_plugin_path() {
    echo "$WORDPRESS_PATH/wp-content/plugins/$PLUGIN_NAME"
}

# バックアップ作成
create_backup() {
    local plugin_path=$(get_plugin_path)

    if [ -d "$plugin_path" ]; then
        local backup_dir="/tmp/wp-plugin-backups"
        local timestamp=$(date +%Y%m%d_%H%M%S)
        local backup_path="${backup_dir}/${PLUGIN_NAME}_${timestamp}"

        log_info "既存のプラグインをバックアップ中..."
        mkdir -p "$backup_dir"
        cp -r "$plugin_path" "$backup_path"
        log_success "バックアップ完了: $backup_path"
        echo "$backup_path"
    else
        log_info "既存のプラグインが見つかりません（新規インストール）"
        echo ""
    fi
}

# プラグインディレクトリの作成
create_plugin_directory() {
    local plugin_path=$(get_plugin_path)

    log_info "プラグインディレクトリを作成中: $plugin_path"
    mkdir -p "$plugin_path"
    mkdir -p "$plugin_path/assets/css"
    mkdir -p "$plugin_path/assets/js"
    log_success "ディレクトリ作成完了"
}

# ファイルのダウンロード
download_file() {
    local file_path=$1
    local dest_path=$2
    local url="${GITHUB_RAW_BASE}/${file_path}"

    log_info "ダウンロード中: $file_path"

    if curl -f -s -L -o "$dest_path" "$url"; then
        log_success "ダウンロード完了: $file_path"
        return 0
    else
        log_error "ダウンロード失敗: $file_path"
        return 1
    fi
}

# 全ファイルのダウンロード
download_plugin_files() {
    local plugin_path=$(get_plugin_path)

    log_info "プラグインファイルをダウンロード中..."

    # メインPHPファイル
    download_file "wp-hero-image-manager.php" "$plugin_path/wp-hero-image-manager.php" || exit 1

    # CSSファイル
    download_file "assets/css/admin.css" "$plugin_path/assets/css/admin.css" || exit 1
    download_file "assets/css/frontend.css" "$plugin_path/assets/css/frontend.css" || exit 1

    # JavaScriptファイル
    download_file "assets/js/admin.js" "$plugin_path/assets/js/admin.js" || exit 1

    # ドキュメント（オプション）
    download_file "PLUGIN-README.md" "$plugin_path/README.md" || log_warning "README.mdのダウンロードをスキップ"

    log_success "全ファイルのダウンロード完了"
}

# パーミッション設定
set_permissions() {
    local plugin_path=$(get_plugin_path)
    local web_user="www-data"  # Debian/Ubuntu

    # Apacheユーザーの検出
    if id "apache" &>/dev/null; then
        web_user="apache"  # CentOS/RHEL
    elif id "nginx" &>/dev/null; then
        web_user="nginx"   # Nginx
    fi

    log_info "パーミッションを設定中（所有者: $web_user）..."

    # 所有者を設定
    chown -R "$web_user:$web_user" "$plugin_path"

    # ディレクトリ: 755
    find "$plugin_path" -type d -exec chmod 755 {} \;

    # ファイル: 644
    find "$plugin_path" -type f -exec chmod 644 {} \;

    log_success "パーミッション設定完了"
}

# インストール確認
verify_installation() {
    local plugin_path=$(get_plugin_path)
    local main_file="$plugin_path/wp-hero-image-manager.php"

    log_info "インストールを検証中..."

    if [ ! -f "$main_file" ]; then
        log_error "メインファイルが見つかりません: $main_file"
        return 1
    fi

    # バージョンチェック
    if grep -q "Version: $PLUGIN_VERSION" "$main_file"; then
        log_success "バージョン確認: v$PLUGIN_VERSION"
    else
        log_warning "バージョン情報を確認できませんでした"
    fi

    # 必須ファイルのチェック
    local required_files=(
        "wp-hero-image-manager.php"
        "assets/css/admin.css"
        "assets/css/frontend.css"
        "assets/js/admin.js"
    )

    for file in "${required_files[@]}"; do
        if [ -f "$plugin_path/$file" ]; then
            log_success "✓ $file"
        else
            log_error "✗ $file が見つかりません"
            return 1
        fi
    done

    log_success "インストール検証完了"
    return 0
}

# デプロイ後の手順を表示
print_next_steps() {
    echo ""
    echo "╔═══════════════════════════════════════════════════════════╗"
    echo "║                                                           ║"
    echo "║              デプロイが完了しました！                     ║"
    echo "║                                                           ║"
    echo "╚═══════════════════════════════════════════════════════════╝"
    echo ""
    log_info "次の手順:"
    echo ""
    echo "  1. WordPress管理画面にログイン"
    echo "     URL: ${WORDPRESS_PATH}/wp-admin/"
    echo ""
    echo "  2. プラグイン → インストール済みプラグイン に移動"
    echo ""
    echo "  3. 「WP Hero Image Manager」を探して有効化"
    echo ""
    echo "  4. 投稿編集画面で「ヒーロー画像設定」メタボックスを確認"
    echo ""
    echo "  5. テスト投稿を作成して動作確認:"
    echo "     - ヒーロー画像を設定"
    echo "     - 「記事内でアイキャッチ画像を非表示にする」をチェック"
    echo "     - 記事ページで重複がないことを確認"
    echo "     - カテゴリページでアイキャッチが表示されることを確認"
    echo ""
    log_success "プラグインパス: $(get_plugin_path)"
    echo ""
}

# ロールバック
rollback() {
    local backup_path=$1

    if [ -z "$backup_path" ] || [ ! -d "$backup_path" ]; then
        log_error "ロールバック用のバックアップが見つかりません"
        return 1
    fi

    log_warning "ロールバックを実行中..."
    local plugin_path=$(get_plugin_path)

    rm -rf "$plugin_path"
    cp -r "$backup_path" "$plugin_path"

    log_success "ロールバック完了"
}

# メイン処理
main() {
    print_banner

    # 前提条件チェック
    check_root

    # WordPress検出
    detect_wordpress_path

    # 確認プロンプト
    echo ""
    log_warning "以下の設定でデプロイを実行します:"
    echo "  WordPress: $WORDPRESS_PATH"
    echo "  プラグイン: $(get_plugin_path)"
    echo "  バージョン: v$PLUGIN_VERSION"
    echo ""
    read -p "続行しますか？ (y/N): " -n 1 -r
    echo ""

    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log_info "デプロイをキャンセルしました"
        exit 0
    fi

    # バックアップ作成
    BACKUP_PATH=$(create_backup)

    # エラー時のロールバック設定
    trap 'log_error "デプロイ中にエラーが発生しました"; rollback "$BACKUP_PATH"; exit 1' ERR

    # デプロイ実行
    create_plugin_directory
    download_plugin_files
    set_permissions

    # 検証
    if verify_installation; then
        print_next_steps

        if [ -n "$BACKUP_PATH" ]; then
            echo ""
            log_info "バックアップは以下に保存されています:"
            echo "  $BACKUP_PATH"
            echo ""
            log_info "問題がなければ、バックアップは削除しても構いません"
        fi
    else
        log_error "インストール検証に失敗しました"
        rollback "$BACKUP_PATH"
        exit 1
    fi

    echo ""
    log_success "デプロイが正常に完了しました！"
    echo ""
}

# スクリプト実行
main "$@"
