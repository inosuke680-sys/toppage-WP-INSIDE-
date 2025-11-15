#!/bin/bash

##############################################################################
# Umaten ヒーロー画像一括設定スクリプト (WP-CLI)
#
# 使用方法:
#   1. SSH経由でWordPressルートディレクトリに移動:
#      cd /home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot
#
#   2. このスクリプトをダウンロード:
#      curl -o bulk-set-hero-images.sh https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj/bulk-set-hero-images.sh
#      chmod +x bulk-set-hero-images.sh
#
#   3. 実行（アイキャッチ未設定の投稿のみ）:
#      ./bulk-set-hero-images.sh
#
#   4. 強制更新（すべての投稿）:
#      ./bulk-set-hero-images.sh --force
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

echo ""
echo "==========================================="
echo "  Umaten ヒーロー画像一括設定スクリプト"
echo "==========================================="
echo ""

# WP-CLIが利用可能か確認
if ! command -v wp &> /dev/null; then
    log_error "WP-CLIがインストールされていません"
    log_info "WP-CLIのインストール方法: https://wp-cli.org/"
    exit 1
fi

log_success "WP-CLIが見つかりました"

# WordPressルートディレクトリにいるか確認
if [ ! -f "wp-config.php" ]; then
    log_error "wp-config.phpが見つかりません"
    log_info "WordPressルートディレクトリで実行してください"
    exit 1
fi

log_success "WordPressルートディレクトリを確認しました"

# プラグインが有効化されているか確認
log_info "プラグインの有効化状態を確認中..."
PLUGIN_ACTIVE=$(wp plugin list --status=active --format=csv | grep "umaten-toppage-v2.10" || echo "")

if [ -z "$PLUGIN_ACTIVE" ]; then
    log_warning "Umaten トップページ v2.10 が有効化されていません"
    log_info "v2.9でも実行可能ですが、v2.10の使用を推奨します"
fi

# オプションの解析
FORCE_FLAG=""
if [ "$1" == "--force" ]; then
    FORCE_FLAG="--force"
    log_warning "強制更新モード: すべての投稿を処理します"
else
    log_info "通常モード: アイキャッチ未設定の投稿のみ処理します"
fi

# 処理対象の投稿数を確認
log_info "処理対象の投稿数を確認中..."

if [ -z "$FORCE_FLAG" ]; then
    # アイキャッチ未設定の投稿をカウント
    POST_COUNT=$(wp post list --post_type=post --post_status=publish --meta_query='[{"key":"_thumbnail_id","compare":"NOT EXISTS"}]' --format=count 2>/dev/null || echo "0")
else
    # すべての投稿をカウント
    POST_COUNT=$(wp post list --post_type=post --post_status=publish --format=count 2>/dev/null || echo "0")
fi

log_info "処理対象: ${POST_COUNT}件の投稿"

if [ "$POST_COUNT" == "0" ]; then
    log_success "処理対象の投稿がありません"
    exit 0
fi

# 実行確認
echo ""
read -p "$(echo -e "\e[33m${POST_COUNT}件の投稿を処理します。続行しますか？ (y/n): \e[0m")" -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    log_info "処理をキャンセルしました"
    exit 0
fi

echo ""
log_info "ヒーロー画像の一括設定を開始します..."
echo ""

# WP-CLIコマンドを実行
if [ -z "$FORCE_FLAG" ]; then
    wp umaten hero-images
else
    wp umaten hero-images --force
fi

# 完了メッセージ
echo ""
log_success "==========================================="
log_success "  ヒーロー画像の一括設定が完了しました"
log_success "==========================================="
echo ""
log_info "次のステップ:"
log_info "1. 一覧ページでヒーロー画像が表示されることを確認"
log_info "2. 記事ページでヒーロー画像が重複していないことを確認"
log_info "3. ブラウザのキャッシュをクリア"
echo ""
