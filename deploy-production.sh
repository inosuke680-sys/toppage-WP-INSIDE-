#!/bin/bash
# v2.2.0 本番環境デプロイスクリプト
# GitHubから一時ディレクトリにクローンして本番環境に反映

set -e  # エラーが発生したら即座に終了

echo "=========================================="
echo "  Umaten トップページ v2.2.0 デプロイ"
echo "=========================================="
echo ""

# 設定
BRANCH="claude/optimize-hokkaido-navigation-011CV5rKKYN42TG7uvzSEga4"
REPO_URL="https://github.com/inosuke680-sys/toppage-WP-INSIDE-.git"
TEMP_DIR="/tmp/temp-toppage-update-v2.2.0"
PROD_PATH="/home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot/wp-content/plugins/umaten-toppage"
CACHE_PATH="/home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot/wp-content/cache"

echo "📦 ステップ1: 一時ディレクトリに最新版をクローン"
echo "----------------------------------------"
# 既存の一時ディレクトリを削除
if [ -d "$TEMP_DIR" ]; then
    echo "既存の一時ディレクトリを削除中..."
    rm -rf "$TEMP_DIR"
fi

# GitHubからクローン
echo "GitHubからクローン中... (ブランチ: $BRANCH)"
git clone -b "$BRANCH" --depth 1 "$REPO_URL" "$TEMP_DIR"

if [ $? -ne 0 ]; then
    echo "❌ エラー: Gitクローンに失敗しました"
    exit 1
fi
echo "✓ クローン完了"
echo ""

echo "📁 ステップ2: 本番環境のファイルを更新"
echo "----------------------------------------"
# 更新するファイルのリスト
FILES=(
    "umaten-toppage.php"
    "includes/class-search-results.php"
    "includes/class-url-rewrite.php"
    "includes/class-seo-meta.php"
    "includes/class-auto-featured-image.php"
    "includes/class-admin-settings.php"
    "includes/class-ajax-handler.php"
    "includes/class-shortcode.php"
    "includes/class-view-counter.php"
    "assets/js/toppage.js"
    "assets/css/toppage.css"
    "README.md"
)

for file in "${FILES[@]}"; do
    SOURCE="$TEMP_DIR/umaten-toppage-v2.2/$file"
    DEST="$PROD_PATH/$file"

    if [ -f "$SOURCE" ]; then
        echo "コピー中: $file"
        cp "$SOURCE" "$DEST"
    else
        echo "⚠️  警告: $file が見つかりません（スキップ）"
    fi
done
echo "✓ ファイル更新完了"
echo ""

echo "🔒 ステップ3: パーミッション設定"
echo "----------------------------------------"
chown -R kusanagi:www "$PROD_PATH"
find "$PROD_PATH" -type d -exec chmod 755 {} \;
find "$PROD_PATH" -type f -exec chmod 644 {} \;
echo "✓ パーミッション設定完了"
echo ""

echo "🗑️  ステップ4: 一時ファイルを削除"
echo "----------------------------------------"
rm -rf "$TEMP_DIR"
echo "✓ 一時ファイル削除完了"
echo ""

echo "🔄 ステップ5: PHP-FPMとキャッシュをクリア"
echo "----------------------------------------"
systemctl restart php-fpm
echo "✓ PHP-FPM再起動完了"

if [ -d "$CACHE_PATH" ]; then
    rm -rf "$CACHE_PATH"/*
    echo "✓ キャッシュクリア完了"
else
    echo "⚠️  キャッシュディレクトリが見つかりません（スキップ）"
fi
echo ""

echo "✅ ステップ6: バージョン確認"
echo "----------------------------------------"
VERSION=$(head -10 "$PROD_PATH/umaten-toppage.php" | grep "Version:" | sed 's/.*Version: //')
echo "デプロイされたバージョン: $VERSION"
echo ""

if [ "$VERSION" == "2.2.0" ]; then
    echo "=========================================="
    echo "  ✅ デプロイ成功！v2.2.0が反映されました"
    echo "=========================================="
else
    echo "=========================================="
    echo "  ⚠️  警告: バージョンが2.2.0ではありません"
    echo "=========================================="
fi

echo ""
echo "📝 【重要】次のステップを実行してください："
echo "1. WordPress管理画面にログイン: https://umaten.jp/wp-admin/"
echo "2. 設定 > パーマリンク設定 を開く"
echo "3. 何も変更せずに「変更を保存」をクリック（リライトルールのフラッシュ）"
echo ""
echo "🔍 動作確認項目："
echo "- 投稿ページが正常に表示されるか（サイドバー含む）"
echo "- 検索結果ページが正常に動作するか"
echo "- SEOメタタグが正しく出力されているか"
echo ""
