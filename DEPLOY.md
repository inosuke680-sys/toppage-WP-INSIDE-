# SSH経由でのプラグイン更新手順

このドキュメントでは、SSH経由でウマ店トップページプラグインを更新する方法を説明します。

## 📋 目次

1. [事前準備](#事前準備)
2. [クイックスタート](#クイックスタート)
3. [詳細な手順](#詳細な手順)
4. [更新確認](#更新確認)
5. [トラブルシューティング](#トラブルシューティング)

---

## 事前準備

### 必要な情報

- WordPressのインストールパス（例: `/var/www/html`）
- プラグインディレクトリパス（例: `/var/www/html/wp-content/plugins`）
- SSH接続情報（ホスト、ユーザー名、パスワードまたは秘密鍵）

### 必要な権限

- WordPressプラグインディレクトリへの書き込み権限
- Git操作権限

---

## クイックスタート

最も簡単な更新方法：

```bash
# 1. リポジトリディレクトリに移動
cd /path/to/toppage-WP-INSIDE-

# 2. スクリプトに実行権限を付与
chmod +x deploy.sh check-plugin.sh

# 3. デプロイスクリプトを実行
./deploy.sh /path/to/wp-content/plugins

# 4. 更新確認スクリプトを実行
./check-plugin.sh /path/to/wp-content/plugins/umaten-toppage
```

---

## 詳細な手順

### ステップ 1: SSHでサーバーに接続

```bash
ssh username@your-server.com
```

### ステップ 2: リポジトリをクローン（初回のみ）

```bash
# 作業ディレクトリに移動
cd ~

# リポジトリをクローン
git clone https://github.com/inosuke680-sys/toppage-WP-INSIDE-.git

# リポジトリディレクトリに移動
cd toppage-WP-INSIDE-
```

### ステップ 3: 最新のコードを取得（2回目以降）

```bash
# リポジトリディレクトリに移動
cd ~/toppage-WP-INSIDE-

# 最新のコードを取得
git fetch origin
git pull origin claude/wordpress-plugin-pc-012SVzq5LAueetp4FLmDVPBE
```

### ステップ 4: スクリプトに実行権限を付与

```bash
chmod +x deploy.sh check-plugin.sh
```

### ステップ 5: デプロイスクリプトを実行

**方法1: 対話的に実行**

```bash
./deploy.sh
```

プロンプトが表示されたら、WordPressプラグインディレクトリのパスを入力：

```
WordPressプラグインディレクトリのパスを入力してください:
例: /var/www/html/wp-content/plugins
> /var/www/html/wp-content/plugins
```

**方法2: パスを引数で指定**

```bash
./deploy.sh /var/www/html/wp-content/plugins
```

### ステップ 6: デプロイ結果を確認

デプロイスクリプトは以下の処理を自動で行います：

1. ✅ 最新のコードを取得
2. ✅ プラグインディレクトリを作成（既存の場合はバックアップ）
3. ✅ 必要なファイルをコピー
4. ✅ ファイルの権限を設定
5. ✅ デプロイ情報を保存
6. ✅ デプロイ完了確認

成功すると以下のようなメッセージが表示されます：

```
========================================
✓ デプロイが正常に完了しました！
========================================
```

---

## 更新確認

### 分析スクリプトを実行

```bash
./check-plugin.sh /path/to/wp-content/plugins/umaten-toppage
```

または対話的に実行：

```bash
./check-plugin.sh
```

### 分析レポートの見方

スクリプトは以下の7つの項目をチェックします：

#### [1] ディレクトリ情報
- プラグインディレクトリのパス、所有者、権限を表示

#### [2] ファイル一覧
- 必須ファイルの存在確認
- ファイルサイズ、更新日時、権限を表示

```
✓ umaten-toppage-plugin.php
  サイズ: 1234 bytes
  更新日時: 2024-11-14 12:00:00
  権限: -rw-r--r--
```

#### [3] プラグインバージョン情報
- プラグイン名とバージョンを確認
- 最新バージョン（1.1.0）かチェック

```
プラグイン名: ウマ店トップページ
バージョン: 1.1.0
✓ 最新バージョンです
```

#### [4] スマホ対応修正の確認
- 重要なCSS修正が適用されているか
- JavaScript修正が適用されているか
- HTMLファイルの行数チェック

```
✓ CSS修正1: .meshimap-area-content.active に !important が追加されています
✓ CSS修正2: スマホ用のgrid-template-columns設定があります
✓ JS修正1: showInitialArea関数が存在します
✓ JS修正2: インラインスタイルによる表示制御があります
✓ JS修正3: デバッグ用コンソールログがあります
HTMLファイル行数: 935 行
✓ 行数が期待値の範囲内です (900行以上)
```

#### [5] デプロイ情報
- デプロイ日時、バージョン、Gitコミット情報を表示

#### [6] 総合評価
- すべてのチェック項目の結果をまとめて表示

成功時：
```
========================================
✓ プラグインは正常に更新されています！
========================================
```

問題がある場合：
```
========================================
✗ 3 個の問題が見つかりました
プラグインを再デプロイしてください
========================================
```

#### [7] ファイル差分チェック
- リポジトリのファイルとデプロイされたファイルの差分を確認

---

## WordPress側での操作

### プラグインの有効化・再有効化

1. WordPressダッシュボードにログイン
2. 左メニューから「プラグイン」→「インストール済みプラグイン」を選択
3. 「ウマ店トップページ」を探す
4. すでに有効化されている場合は、一度「無効化」して再度「有効化」
5. 新規の場合は「有効化」をクリック

### ショートコードの設定

1. 「固定ページ」→「新規追加」または既存ページを編集
2. ブロックエディタで「ショートコード」ブロックを追加
3. 以下のショートコードを入力：

```
[umaten_toppage]
```

4. 「更新」または「公開」をクリック

### キャッシュのクリア

プラグインを更新した後は、キャッシュをクリアしてください：

1. **ブラウザキャッシュ**
   - Chrome: Ctrl + Shift + Delete（Windows）/ Cmd + Shift + Delete（Mac）
   - Firefox: Ctrl + Shift + Delete（Windows）/ Cmd + Shift + Delete（Mac）

2. **WordPressキャッシュプラグイン**（使用している場合）
   - WP Super Cache: 「設定」→「WP Super Cache」→「キャッシュ削除」
   - W3 Total Cache: 「Performance」→「Purge All Caches」

3. **サーバーキャッシュ**（該当する場合）
   - .htaccess の設定を確認
   - サーバー管理画面からキャッシュをクリア

---

## トラブルシューティング

### 問題1: 「Permission denied」エラー

**症状:**
```bash
./deploy.sh
bash: ./deploy.sh: Permission denied
```

**解決方法:**
```bash
chmod +x deploy.sh check-plugin.sh
```

### 問題2: WordPressディレクトリが見つからない

**症状:**
```
エラー: ディレクトリが存在しません: /var/www/html/wp-content/plugins
```

**解決方法:**

WordPressの正しいパスを確認：

```bash
# WordPressインストールディレクトリを探す
find / -name "wp-config.php" 2>/dev/null

# プラグインディレクトリを探す
find / -type d -name "plugins" 2>/dev/null | grep wp-content
```

よくあるパス：
- `/var/www/html/wp-content/plugins`
- `/home/username/public_html/wp-content/plugins`
- `/usr/share/nginx/html/wp-content/plugins`

### 問題3: 書き込み権限がない

**症状:**
```
cp: cannot create regular file '/var/www/html/wp-content/plugins/umaten-toppage/...': Permission denied
```

**解決方法:**

sudoで実行するか、所有者を変更：

```bash
# sudoで実行
sudo ./deploy.sh /var/www/html/wp-content/plugins

# または、ディレクトリの所有者を変更
sudo chown -R $USER:$USER /var/www/html/wp-content/plugins/umaten-toppage
```

### 問題4: Git操作ができない

**症状:**
```
fatal: not a git repository
```

**解決方法:**

正しいディレクトリにいるか確認：

```bash
# 現在のディレクトリを確認
pwd

# リポジトリディレクトリに移動
cd ~/toppage-WP-INSIDE-

# Gitリポジトリかチェック
git status
```

### 問題5: プラグインがWordPressに表示されない

**症状:**
WordPressダッシュボードのプラグイン一覧に表示されない

**解決方法:**

1. ファイルが正しい場所にあるか確認：
```bash
ls -la /path/to/wp-content/plugins/umaten-toppage/
```

2. プラグインファイルのヘッダーを確認：
```bash
head -20 /path/to/wp-content/plugins/umaten-toppage/umaten-toppage-plugin.php
```

3. ファイルの所有者と権限を確認：
```bash
ls -l /path/to/wp-content/plugins/umaten-toppage/
```

4. 必要に応じて所有者を変更：
```bash
sudo chown -R www-data:www-data /path/to/wp-content/plugins/umaten-toppage/
```

### 問題6: スマホで都道府県が表示されない

**症状:**
PC版は正常だがスマホで都道府県カードが消える

**解決方法:**

1. ブラウザキャッシュをクリア
2. 開発者ツールでコンソールログを確認：
```javascript
エリアタブを初期化中...
タブ数: 8 コンテンツ数: 8
北海道エリアを表示しました
エリアタブの初期化が完了しました
```

3. 分析スクリプトで修正が適用されているか確認：
```bash
./check-plugin.sh /path/to/wp-content/plugins/umaten-toppage
```

4. HTMLファイルのバージョンを確認：
```bash
grep -c "display: block !important" /path/to/wp-content/plugins/umaten-toppage/umaten-toppage.html
```
（2以上なら修正版が適用されています）

---

## よくある質問（FAQ）

### Q1: デプロイスクリプトは既存のプラグインを上書きしますか？

A: はい、上書きしますが、自動的にバックアップを作成します。バックアップは同じディレクトリに `umaten-toppage_backup_YYYYMMDD_HHMMSS` という名前で保存されます。

### Q2: 複数のWordPressサイトに同時にデプロイできますか？

A: デプロイスクリプトは1つのサイトずつ実行してください。複数サイトの場合は、サイトごとに実行します。

### Q3: デプロイ後、WordPressで何か設定が必要ですか？

A: プラグインを有効化し、固定ページにショートコード `[umaten_toppage]` を追加するだけです。

### Q4: 更新後、古いバージョンに戻すことはできますか？

A: はい、バックアップディレクトリから復元できます：
```bash
cd /path/to/wp-content/plugins
rm -rf umaten-toppage
cp -r umaten-toppage_backup_20241114_120000 umaten-toppage
```

### Q5: 分析スクリプトで問題が見つかった場合は？

A: デプロイスクリプトを再実行してください。それでも解決しない場合は、手動でファイルを確認します。

---

## まとめ

### 基本的な更新フロー

```bash
# 1. リポジトリを更新
cd ~/toppage-WP-INSIDE-
git pull origin claude/wordpress-plugin-pc-012SVzq5LAueetp4FLmDVPBE

# 2. デプロイ
./deploy.sh /path/to/wp-content/plugins

# 3. 確認
./check-plugin.sh /path/to/wp-content/plugins/umaten-toppage

# 4. WordPress側で再有効化とキャッシュクリア
```

### 確認項目チェックリスト

- [ ] デプロイスクリプトが正常に完了した
- [ ] 分析スクリプトで全項目が✓になった
- [ ] WordPressでプラグインが表示される
- [ ] プラグインを再有効化した
- [ ] ブラウザキャッシュをクリアした
- [ ] PCで正常に表示される
- [ ] スマホで都道府県カードが表示される
- [ ] ブラウザコンソールにエラーがない

---

## サポート

問題が解決しない場合は、以下の情報を含めてお問い合わせください：

1. サーバーのOS（`uname -a`の出力）
2. WordPressのバージョン
3. PHPのバージョン（`php -v`の出力）
4. デプロイスクリプトの出力
5. 分析スクリプトの出力
6. エラーメッセージの全文
