#!/bin/bash

##############################################################################
# Umaten トップページ プラグイン v2.6.0 本番環境デプロイスクリプト
#
# 使用方法:
#   curl -o /tmp/deploy-v2.6.0.sh https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj/deploy-production.sh
#   chmod +x /tmp/deploy-v2.6.0.sh
#   sudo /tmp/deploy-v2.6.0.sh
##############################################################################

set -e  # エラーが発生したら即座に終了

# 色付きログ出力用の関数
log_info() {
    echo -e "\e[34m[INFO]\e[0m $1"
}

log_success() {
    echo -e "\e[32m[SUCCESS]\e[0m $1"
}

log_error() {
    echo -e "\e[31m[ERROR]\e[0m $1"
}

log_warning() {
    echo -e "\e[33m[WARNING]\e[0m $1"
}

# 定数定義
PLUGIN_VERSION="2.6.0"
PLUGIN_NAME="umaten-toppage-v2.6"
GITHUB_REPO="inosuke680-sys/toppage-WP-INSIDE-"
GITHUB_BRANCH="claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj"
GITHUB_RAW_URL="https://raw.githubusercontent.com/${GITHUB_REPO}/${GITHUB_BRANCH}"

# WordPressプラグインディレクトリのパスを検出
detect_wordpress_path() {
    log_info "WordPressのインストールパスを検出中..."

    # 一般的なWordPressインストールパスを検索
    local possible_paths=(
        "/var/www/html/wp-content/plugins"
        "/var/www/wordpress/wp-content/plugins"
        "/usr/share/nginx/html/wp-content/plugins"
        "/home/*/public_html/wp-content/plugins"
        "/var/www/*/public_html/wp-content/plugins"
    )

    for path_pattern in "${possible_paths[@]}"; do
        for path in $path_pattern; do
            if [ -d "$path" ]; then
                WP_PLUGINS_DIR="$path"
                log_success "WordPressプラグインディレクトリを検出: $WP_PLUGINS_DIR"
                return 0
            fi
        done
    done

    log_error "WordPressプラグインディレクトリが見つかりません"
    read -p "プラグインディレクトリのパスを入力してください: " WP_PLUGINS_DIR

    if [ ! -d "$WP_PLUGINS_DIR" ]; then
        log_error "指定されたパスが存在しません: $WP_PLUGINS_DIR"
        exit 1
    fi
}

# バックアップの作成
create_backup() {
    log_info "既存プラグインのバックアップを作成中..."

    BACKUP_DIR="/tmp/umaten-toppage-backup-$(date +%Y%m%d-%H%M%S)"
    mkdir -p "$BACKUP_DIR"

    # v2.5.0のバックアップ
    if [ -d "$WP_PLUGINS_DIR/umaten-toppage-v2.5" ]; then
        cp -r "$WP_PLUGINS_DIR/umaten-toppage-v2.5" "$BACKUP_DIR/"
        log_success "v2.5.0 をバックアップしました: $BACKUP_DIR/umaten-toppage-v2.5"
    fi

    # v2.6.0が既に存在する場合もバックアップ
    if [ -d "$WP_PLUGINS_DIR/$PLUGIN_NAME" ]; then
        cp -r "$WP_PLUGINS_DIR/$PLUGIN_NAME" "$BACKUP_DIR/"
        log_success "既存の $PLUGIN_NAME をバックアップしました: $BACKUP_DIR/$PLUGIN_NAME"
    fi
}

# プラグインファイルのダウンロード
download_plugin_files() {
    log_info "プラグインファイルをダウンロード中..."

    TEMP_DIR="/tmp/$PLUGIN_NAME-download-$$"
    mkdir -p "$TEMP_DIR/$PLUGIN_NAME"
    mkdir -p "$TEMP_DIR/$PLUGIN_NAME/includes"
    mkdir -p "$TEMP_DIR/$PLUGIN_NAME/assets/css"
    mkdir -p "$TEMP_DIR/$PLUGIN_NAME/assets/js"

    # メインファイル
    log_info "メインファイルをダウンロード..."
    curl -fsSL "${GITHUB_RAW_URL}/${PLUGIN_NAME}/umaten-toppage.php" \
        -o "$TEMP_DIR/$PLUGIN_NAME/umaten-toppage.php" || {
        log_error "メインファイルのダウンロードに失敗しました"
        exit 1
    }

    # includesディレクトリ
    log_info "includesファイルをダウンロード..."
    local includes_files=(
        "class-admin-settings.php"
        "class-ajax-handler.php"
        "class-hero-image.php"
        "class-search-results.php"
        "class-seo-meta.php"
        "class-shortcode.php"
        "class-url-rewrite.php"
        "class-view-counter.php"
    )

    for file in "${includes_files[@]}"; do
        curl -fsSL "${GITHUB_RAW_URL}/${PLUGIN_NAME}/includes/${file}" \
            -o "$TEMP_DIR/$PLUGIN_NAME/includes/${file}" || {
            log_warning "includes/${file} のダウンロードに失敗（スキップ）"
        }
    done

    # assetsディレクトリ
    log_info "assetsファイルをダウンロード..."
    curl -fsSL "${GITHUB_RAW_URL}/${PLUGIN_NAME}/assets/css/toppage.css" \
        -o "$TEMP_DIR/$PLUGIN_NAME/assets/css/toppage.css" || {
        log_warning "assets/css/toppage.css のダウンロードに失敗（スキップ）"
    }

    curl -fsSL "${GITHUB_RAW_URL}/${PLUGIN_NAME}/assets/js/toppage.js" \
        -o "$TEMP_DIR/$PLUGIN_NAME/assets/js/toppage.js" || {
        log_warning "assets/js/toppage.js のダウンロードに失敗（スキップ）"
    }

    # README
    curl -fsSL "${GITHUB_RAW_URL}/${PLUGIN_NAME}/README.md" \
        -o "$TEMP_DIR/$PLUGIN_NAME/README.md" || {
        log_warning "README.md のダウンロードに失敗（スキップ）"
    }

    log_success "全てのファイルのダウンロードが完了しました"
}

# プラグインのインストール
install_plugin() {
    log_info "プラグインをインストール中..."

    # 既存のv2.6.0がある場合は削除
    if [ -d "$WP_PLUGINS_DIR/$PLUGIN_NAME" ]; then
        log_warning "既存の $PLUGIN_NAME を削除します..."
        rm -rf "$WP_PLUGINS_DIR/$PLUGIN_NAME"
    fi

    # 新しいプラグインをコピー
    cp -r "$TEMP_DIR/$PLUGIN_NAME" "$WP_PLUGINS_DIR/"

    # パーミッションの設定
    chown -R www-data:www-data "$WP_PLUGINS_DIR/$PLUGIN_NAME" 2>/dev/null || {
        log_warning "www-data ユーザーが存在しないため、パーミッション設定をスキップします"
        chown -R nginx:nginx "$WP_PLUGINS_DIR/$PLUGIN_NAME" 2>/dev/null || {
            log_warning "nginx ユーザーも存在しないため、現在のユーザーのまま続行します"
        }
    }

    chmod -R 755 "$WP_PLUGINS_DIR/$PLUGIN_NAME"

    log_success "プラグインのインストールが完了しました: $WP_PLUGINS_DIR/$PLUGIN_NAME"
}

# クリーンアップ
cleanup() {
    log_info "一時ファイルをクリーンアップ中..."
    rm -rf "$TEMP_DIR"
    log_success "クリーンアップが完了しました"
}

# WP-CLIでプラグインを有効化（オプション）
activate_plugin() {
    log_info "WP-CLIでプラグインを有効化しますか？ (y/n)"
    read -r response

    if [[ "$response" =~ ^[Yy]$ ]]; then
        if command -v wp &> /dev/null; then
            # WP-CLIが利用可能な場合
            cd "$(dirname "$WP_PLUGINS_DIR")" || exit 1
            cd ..  # WordPressルートディレクトリに移動

            # v2.5.0を無効化
            wp plugin deactivate umaten-toppage-v2.5 --allow-root 2>/dev/null || true

            # v2.6.0を有効化
            wp plugin activate "$PLUGIN_NAME" --allow-root || {
                log_error "プラグインの有効化に失敗しました"
                exit 1
            }

            log_success "プラグインが有効化されました"
        else
            log_warning "WP-CLIが見つかりません。手動で有効化してください。"
        fi
    else
        log_info "手動でWordPress管理画面からプラグインを有効化してください"
    fi
}

# メイン処理
main() {
    log_info "==================================================================="
    log_info "  Umaten トップページ プラグイン v${PLUGIN_VERSION} デプロイ開始"
    log_info "==================================================================="

    detect_wordpress_path
    create_backup
    download_plugin_files
    install_plugin
    cleanup
    activate_plugin

    log_success "==================================================================="
    log_success "  デプロイが正常に完了しました！"
    log_success "==================================================================="
    log_info ""
    log_info "次のステップ："
    log_info "1. WordPress管理画面にログイン"
    log_info "2. プラグイン → インストール済みプラグイン"
    log_info "3. 「Umaten トップページ v2.5.0」を無効化（必要に応じて）"
    log_info "4. 「Umaten トップページ v2.6.0」を有効化"
    log_info "5. 設定 → Umaten トップページ で設定を確認"
    log_info ""
    log_info "バックアップの場所: $BACKUP_DIR"
    log_info ""
}

# スクリプト実行
main
