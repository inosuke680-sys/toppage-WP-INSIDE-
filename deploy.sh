#!/bin/bash
# v1.6.0 デプロイスクリプト

echo "=== Umaten トップページ v1.6.0 デプロイ ==="
echo ""

# 設定
REMOTE_USER="kusanagi"
REMOTE_HOST="umaten.jp"
REMOTE_PATH="/home/kusanagi/umaten/DocumentRoot/wp-content/plugins/umaten-toppage"
LOCAL_PATH="umaten-toppage"

echo "デプロイ方法を選択してください："
echo "1) SCP（個別ファイル転送 - 更新ファイルのみ）"
echo "2) RSYNC（フォルダ同期 - 推奨）"
echo "3) SSH経由でGit pull"
read -p "選択 (1-3): " choice

case $choice in
  1)
    echo ""
    echo "SCPで更新ファイルを転送中..."
    scp ${LOCAL_PATH}/umaten-toppage.php ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/
    scp ${LOCAL_PATH}/includes/class-url-rewrite.php ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/includes/
    scp ${LOCAL_PATH}/includes/class-seo-meta.php ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/includes/
    ;;
  2)
    echo ""
    echo "RSYNCでプラグインフォルダを同期中..."
    rsync -avz --progress ${LOCAL_PATH}/ ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH}/
    ;;
  3)
    echo ""
    echo "SSH経由でGit pullを実行します..."
    ssh ${REMOTE_USER}@${REMOTE_HOST} << 'ENDSSH'
cd /home/kusanagi/umaten/DocumentRoot/
echo "Git fetchを実行中..."
git fetch origin claude/optimize-hokkaido-navigation-011CV5rKKYN42TG7uvzSEga4
echo "Git pullを実行中..."
git pull origin claude/optimize-hokkaido-navigation-011CV5rKKYN42TG7uvzSEga4
ENDSSH
    ;;
  *)
    echo "無効な選択です。終了します。"
    exit 1
    ;;
esac

if [ $? -eq 0 ]; then
  echo ""
  echo "✓ ファイル転送完了"
  echo ""
  echo "パーミッション修正とPHP-FPM再起動を実行中..."
  ssh ${REMOTE_USER}@${REMOTE_HOST} << 'ENDSSH'
sudo chown -R kusanagi:www /home/kusanagi/umaten/DocumentRoot/wp-content/plugins/umaten-toppage/
sudo find /home/kusanagi/umaten/DocumentRoot/wp-content/plugins/umaten-toppage -type d -exec chmod 755 {} \;
sudo find /home/kusanagi/umaten/DocumentRoot/wp-content/plugins/umaten-toppage -type f -exec chmod 644 {} \;
sudo systemctl restart php-fpm
echo ""
echo "✓ パーミッション修正とPHP-FPM再起動完了"
ENDSSH

  echo ""
  echo "=== デプロイ完了 ==="
  echo ""
  echo "【重要】次のステップを実行してください："
  echo "1. WordPress管理画面にログイン: https://umaten.jp/wp-admin/"
  echo "2. 設定 > パーマリンク設定 を開く"
  echo "3. 何も変更せずに「変更を保存」をクリック（リライトルールのフラッシュ）"
  echo ""
  echo "動作確認："
  echo "- 投稿ページが正常に表示されるか"
  echo "- サイドバーが表示されるか"
  echo "- 検索結果ページが正常に動作するか"
  echo ""
else
  echo ""
  echo "✗ デプロイに失敗しました"
  exit 1
fi
