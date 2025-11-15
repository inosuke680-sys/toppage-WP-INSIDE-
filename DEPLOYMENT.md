# デプロイメントガイド

WP Hero Image Manager v2.8.4 を本番環境にデプロイする方法を説明します。

## 目次

1. [クイックスタート](#クイックスタート)
2. [詳細な手順](#詳細な手順)
3. [手動デプロイ](#手動デプロイ)
4. [トラブルシューティング](#トラブルシューティング)
5. [ロールバック](#ロールバック)

---

## クイックスタート

### 自動デプロイ（推奨）

本番サーバーにSSHで接続し、以下のコマンドを実行してください：

```bash
# デプロイスクリプトをダウンロード
curl -o /tmp/deploy-wp-hero-image.sh https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/fix-duplicate-hero-image-01Ja5EAk1knXE9Hf1Bv33uEm/deploy-production.sh

# 実行権限を付与
chmod +x /tmp/deploy-wp-hero-image.sh

# スクリプトを実行（root権限が必要）
sudo /tmp/deploy-wp-hero-image.sh
```

### 実行されること

1. ✅ WordPressインストールの自動検出
2. ✅ 既存プラグインのバックアップ作成
3. ✅ 最新版のダウンロード
4. ✅ パーミッション設定
5. ✅ インストール検証
6. ✅ 次の手順の表示

---

## 詳細な手順

### 前提条件

#### 必須要件

- **サーバーアクセス**: SSH接続可能
- **権限**: root または sudo 権限
- **WordPress**: 既にインストール済み（5.0以上）
- **PHP**: 7.2 以上
- **ツール**: curl, bash

#### サーバー環境の確認

```bash
# PHPバージョン確認
php -v

# curlの確認
which curl

# WordPressの確認
ls -la /var/www/html/wp-config.php
```

### ステップ 1: サーバーに接続

```bash
# SSHで本番サーバーに接続
ssh user@your-server.com
```

### ステップ 2: デプロイスクリプトの実行

```bash
# スクリプトをダウンロード
curl -o /tmp/deploy-wp-hero-image.sh \
  https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/fix-duplicate-hero-image-01Ja5EAk1knXE9Hf1Bv33uEm/deploy-production.sh

# 内容を確認（オプション）
less /tmp/deploy-wp-hero-image.sh

# 実行権限を付与
chmod +x /tmp/deploy-wp-hero-image.sh

# 実行（対話的にWordPressパスを確認します）
sudo /tmp/deploy-wp-hero-image.sh
```

### ステップ 3: WordPress管理画面で有効化

デプロイが完了したら：

1. WordPress管理画面にログイン
2. **プラグイン → インストール済みプラグイン** に移動
3. **「WP Hero Image Manager」** を探す
4. **「有効化」** をクリック

### ステップ 4: 動作確認

#### 管理画面の確認

1. 投稿編集画面を開く
2. 右サイドバーに **「ヒーロー画像設定」** メタボックスが表示されることを確認

#### フロントエンドの確認

1. **テスト投稿を作成**:
   - 「ヒーロー画像を使用する」にチェック
   - ヒーロー画像を選択
   - 「記事内でアイキャッチ画像を非表示にする」にチェック
   - 投稿を公開

2. **表示を確認**:
   - 記事ページ: ヒーロー画像のみ表示（アイキャッチは非表示）
   - カテゴリページ: アイキャッチ画像が表示
   - アーカイブページ: アイキャッチ画像が表示

---

## 手動デプロイ

自動スクリプトを使用しない場合の手順です。

### 方法1: GitHubから直接クローン

```bash
# WordPressプラグインディレクトリに移動
cd /var/www/html/wp-content/plugins/

# リポジトリをクローン
sudo git clone https://github.com/inosuke680-sys/toppage-WP-INSIDE-.git wp-hero-image-manager

# ブランチを切り替え
cd wp-hero-image-manager
sudo git checkout claude/fix-duplicate-hero-image-01Ja5EAk1knXE9Hf1Bv33uEm

# 不要なファイルを削除（オプション）
sudo rm -rf .git

# パーミッション設定
sudo chown -R www-data:www-data /var/www/html/wp-content/plugins/wp-hero-image-manager
sudo find /var/www/html/wp-content/plugins/wp-hero-image-manager -type d -exec chmod 755 {} \;
sudo find /var/www/html/wp-content/plugins/wp-hero-image-manager -type f -exec chmod 644 {} \;
```

### 方法2: 個別ファイルのダウンロード

```bash
# プラグインディレクトリを作成
sudo mkdir -p /var/www/html/wp-content/plugins/wp-hero-image-manager/assets/css
sudo mkdir -p /var/www/html/wp-content/plugins/wp-hero-image-manager/assets/js

# ベースURL
BASE_URL="https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/fix-duplicate-hero-image-01Ja5EAk1knXE9Hf1Bv33uEm"

# メインファイル
sudo curl -o /var/www/html/wp-content/plugins/wp-hero-image-manager/wp-hero-image-manager.php \
  "${BASE_URL}/wp-hero-image-manager.php"

# CSSファイル
sudo curl -o /var/www/html/wp-content/plugins/wp-hero-image-manager/assets/css/admin.css \
  "${BASE_URL}/assets/css/admin.css"

sudo curl -o /var/www/html/wp-content/plugins/wp-hero-image-manager/assets/css/frontend.css \
  "${BASE_URL}/assets/css/frontend.css"

# JavaScriptファイル
sudo curl -o /var/www/html/wp-content/plugins/wp-hero-image-manager/assets/js/admin.js \
  "${BASE_URL}/assets/js/admin.js"

# パーミッション設定
sudo chown -R www-data:www-data /var/www/html/wp-content/plugins/wp-hero-image-manager
sudo find /var/www/html/wp-content/plugins/wp-hero-image-manager -type d -exec chmod 755 {} \;
sudo find /var/www/html/wp-content/plugins/wp-hero-image-manager -type f -exec chmod 644 {} \;
```

### 方法3: FTP経由

1. **ローカルでリポジトリをクローン**:
   ```bash
   git clone https://github.com/inosuke680-sys/toppage-WP-INSIDE-.git
   cd toppage-WP-INSIDE-
   git checkout claude/fix-duplicate-hero-image-01Ja5EAk1knXE9Hf1Bv33uEm
   ```

2. **必要なファイルをZIPにまとめる**:
   ```bash
   mkdir wp-hero-image-manager-deploy
   cp wp-hero-image-manager.php wp-hero-image-manager-deploy/
   cp -r assets wp-hero-image-manager-deploy/
   zip -r wp-hero-image-manager.zip wp-hero-image-manager-deploy/
   ```

3. **FTPクライアントでアップロード**:
   - `/wp-content/plugins/wp-hero-image-manager/` にアップロード

4. **パーミッション設定**（FTPクライアントまたはSSH）:
   - ディレクトリ: 755
   - ファイル: 644

---

## トラブルシューティング

### WordPress パスが見つからない

**エラー**: "WordPressのパスを自動検出できませんでした"

**解決策**:
```bash
# WordPressの場所を手動で検索
find / -name "wp-config.php" 2>/dev/null

# 見つかったパスを入力
# 例: /var/www/html
```

### パーミッションエラー

**エラー**: "Permission denied"

**解決策**:
```bash
# Webサーバーのユーザーを確認
ps aux | grep -E 'apache|nginx' | head -1

# パーミッションを修正（www-dataまたはapache）
sudo chown -R www-data:www-data /var/www/html/wp-content/plugins/wp-hero-image-manager
```

### ダウンロード失敗

**エラー**: "ダウンロード失敗: ..."

**解決策**:
```bash
# インターネット接続を確認
ping -c 3 github.com

# curlを確認
curl -I https://github.com

# ファイアウォール設定を確認
sudo iptables -L | grep -i drop
```

### プラグインが表示されない

**原因**: ファイルが正しい場所にない、またはファイルが壊れている

**解決策**:
```bash
# ファイルを確認
ls -la /var/www/html/wp-content/plugins/wp-hero-image-manager/

# メインファイルを確認
cat /var/www/html/wp-content/plugins/wp-hero-image-manager/wp-hero-image-manager.php | head -20

# 再ダウンロード
sudo rm -rf /var/www/html/wp-content/plugins/wp-hero-image-manager
# その後、再度デプロイ
```

---

## ロールバック

デプロイ後に問題が発生した場合、以前のバージョンに戻すことができます。

### 自動バックアップを使用

デプロイスクリプトは自動的にバックアップを作成します：

```bash
# バックアップの場所
ls -la /tmp/wp-plugin-backups/

# 最新のバックアップを確認
LATEST_BACKUP=$(ls -t /tmp/wp-plugin-backups/ | head -1)
echo $LATEST_BACKUP

# ロールバック
sudo rm -rf /var/www/html/wp-content/plugins/wp-hero-image-manager
sudo cp -r /tmp/wp-plugin-backups/$LATEST_BACKUP /var/www/html/wp-content/plugins/wp-hero-image-manager

# パーミッション修正
sudo chown -R www-data:www-data /var/www/html/wp-content/plugins/wp-hero-image-manager
```

### 手動バックアップから復元

```bash
# 事前にバックアップを作成していた場合
sudo cp -r /path/to/backup/wp-hero-image-manager /var/www/html/wp-content/plugins/

# パーミッション修正
sudo chown -R www-data:www-data /var/www/html/wp-content/plugins/wp-hero-image-manager
```

### 完全削除

プラグインを完全に削除する場合：

```bash
# プラグインを無効化（WordPress管理画面で）

# ファイルを削除
sudo rm -rf /var/www/html/wp-content/plugins/wp-hero-image-manager

# データベースのメタデータを削除（オプション）
# wp-cli を使用する場合
wp db query "DELETE FROM wp_postmeta WHERE meta_key LIKE '_hero_image%'"
wp db query "DELETE FROM wp_postmeta WHERE meta_key IN ('_use_hero_image', '_hide_featured_image')"
wp db query "DELETE FROM wp_options WHERE option_name = 'wp_hero_image_manager_version'"
```

---

## チェックリスト

デプロイ前の確認事項：

### 事前準備
- [ ] WordPressのバックアップを取得
- [ ] データベースのバックアップを取得
- [ ] 現在のプラグインのバックアップを作成
- [ ] メンテナンスモードを有効化（オプション）

### デプロイ実行
- [ ] デプロイスクリプトの実行
- [ ] エラーがないことを確認
- [ ] ファイルが正しく配置されているか確認
- [ ] パーミッションが正しいか確認

### デプロイ後
- [ ] WordPress管理画面でプラグインを有効化
- [ ] 管理画面でメタボックスが表示されることを確認
- [ ] テスト投稿を作成
- [ ] フロントエンドの表示を確認
- [ ] カテゴリページの表示を確認
- [ ] アーカイブページの表示を確認
- [ ] エラーログを確認

### トラブル発生時
- [ ] エラーログを確認
- [ ] ロールバックを検討
- [ ] サポートに連絡（GitHub Issues）

---

## サポート環境

### テスト済み環境

| 項目 | バージョン |
|------|----------|
| WordPress | 5.0, 5.9, 6.0, 6.4 |
| PHP | 7.2, 7.4, 8.0, 8.1, 8.2 |
| MySQL | 5.6, 5.7, 8.0 |
| MariaDB | 10.2, 10.5 |
| Apache | 2.4 |
| Nginx | 1.18, 1.20 |

### OS

- Ubuntu 20.04, 22.04
- Debian 10, 11
- CentOS 7, 8
- Amazon Linux 2

---

## セキュリティ

### デプロイスクリプトのセキュリティ

1. **スクリプトの検証**:
   ```bash
   # スクリプトの内容を確認
   curl -s https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/fix-duplicate-hero-image-01Ja5EAk1knXE9Hf1Bv33uEm/deploy-production.sh | less
   ```

2. **HTTPS経由でのダウンロード**: 必ずHTTPSを使用

3. **root権限**: 必要な場合のみsudoを使用

### プラグインのセキュリティ

- ✅ Nonce検証
- ✅ 権限チェック
- ✅ データサニタイゼーション
- ✅ XSS対策
- ✅ CSRF対策

---

## よくある質問

### Q: デプロイにどのくらい時間がかかりますか？

A: 通常1〜2分程度です。ネットワーク速度に依存します。

### Q: ダウンタイムは発生しますか？

A: プラグインの上書き中、数秒程度のダウンタイムが発生する可能性があります。メンテナンスモードの使用を推奨します。

### Q: 既存の設定は保持されますか？

A: はい、データベースに保存されている設定は保持されます。

### Q: 複数サイト（マルチサイト）に対応していますか？

A: はい、WordPressマルチサイト環境でも動作します。

---

## サポート

問題が発生した場合：

- **GitHub Issues**: https://github.com/inosuke680-sys/toppage-WP-INSIDE-/issues
- **ドキュメント**: README.md, PLUGIN-README.md, INSTALL.md

---

**最終更新**: 2025-11-15
**バージョン**: 2.8.4
