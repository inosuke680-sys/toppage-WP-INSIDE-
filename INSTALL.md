# インストールガイド

WP Hero Image Manager のインストール方法を詳しく説明します。

## 目次

1. [システム要件](#システム要件)
2. [インストール方法](#インストール方法)
3. [初期設定](#初期設定)
4. [トラブルシューティング](#トラブルシューティング)

## システム要件

### 必須要件

- **WordPress**: 5.0 以上
- **PHP**: 7.2 以上
- **MySQL**: 5.6 以上
- **サーバー**: Apache または Nginx

### 推奨環境

- **WordPress**: 6.0 以上
- **PHP**: 8.0 以上
- **MySQL**: 5.7 以上 または MariaDB 10.2 以上
- **メモリ**: 128MB 以上（推奨: 256MB）

### 確認方法

WordPressのサイトヘルス機能で確認できます:
```
WordPress管理画面 → ツール → サイトヘルス
```

## インストール方法

### 方法1: GitHubから直接インストール（推奨）

#### ステップ1: プラグインディレクトリに移動

```bash
cd /path/to/wordpress/wp-content/plugins/
```

#### ステップ2: リポジトリをクローン

```bash
git clone https://github.com/inosuke680-sys/toppage-WP-INSIDE-.git wp-hero-image-manager
```

#### ステップ3: ディレクトリに移動

```bash
cd wp-hero-image-manager
```

#### ステップ4: パーミッションを設定

```bash
chmod 755 .
chmod 644 *.php
chmod 755 assets
chmod 644 assets/css/*.css
chmod 644 assets/js/*.js
```

#### ステップ5: WordPressでプラグインを有効化

```
WordPress管理画面 → プラグイン → インストール済みプラグイン
→ 「WP Hero Image Manager」を探して「有効化」
```

---

### 方法2: ZIPファイルでインストール

#### ステップ1: ZIPファイルをダウンロード

GitHubのリポジトリページで:
1. 「Code」ボタンをクリック
2. 「Download ZIP」を選択
3. ファイルを保存

#### ステップ2: WordPress管理画面からインストール

```
WordPress管理画面 → プラグイン → 新規追加 → プラグインのアップロード
```

1. 「ファイルを選択」をクリック
2. ダウンロードしたZIPファイルを選択
3. 「今すぐインストール」をクリック
4. 「プラグインを有効化」をクリック

---

### 方法3: 手動インストール（FTP経由）

#### ステップ1: ファイルをダウンロード

GitHubからZIPファイルをダウンロードし、解凍します。

#### ステップ2: FTPでアップロード

FTPクライアント（FileZilla等）を使用:

1. サーバーに接続
2. `/wp-content/plugins/` に移動
3. `wp-hero-image-manager` フォルダを作成
4. 解凍したファイルをアップロード

#### ステップ3: パーミッションを設定

ファイル: `644`
ディレクトリ: `755`

#### ステップ4: WordPressで有効化

```
WordPress管理画面 → プラグイン → インストール済みプラグイン
→ 「WP Hero Image Manager」を有効化
```

---

## 初期設定

### 1. プラグインが正しくインストールされたか確認

```
WordPress管理画面 → プラグイン → インストール済みプラグイン
```

「WP Hero Image Manager」が表示され、バージョン 2.8.4 であることを確認。

### 2. 投稿編集画面を確認

任意の投稿を開き、右サイドバーに「ヒーロー画像設定」メタボックスが表示されることを確認。

### 3. テスト投稿を作成

1. 新規投稿を作成
2. 「ヒーロー画像を使用する」にチェック
3. 「ヒーロー画像を選択」で画像を設定
4. 「記事内でアイキャッチ画像を非表示にする」にチェック
5. 投稿を公開
6. フロントエンドで表示を確認

### 4. 動作確認チェックリスト

- [ ] シングルページでヒーロー画像が表示される
- [ ] シングルページでアイキャッチ画像が非表示になる
- [ ] カテゴリページでアイキャッチ画像が表示される
- [ ] アーカイブページでアイキャッチ画像が表示される
- [ ] 管理画面でメディアライブラリが正常に動作する
- [ ] 画像の削除ボタンが機能する

---

## アップグレード方法

### Git経由でアップグレード

```bash
cd /path/to/wordpress/wp-content/plugins/wp-hero-image-manager
git pull origin main
```

### 手動アップグレード

1. **現在のプラグインをバックアップ**
   ```bash
   cp -r wp-hero-image-manager wp-hero-image-manager-backup
   ```

2. **新しいバージョンをダウンロード**

3. **古いファイルを削除（設定は保持されます）**
   ```bash
   rm -rf wp-hero-image-manager/*
   ```

4. **新しいファイルをアップロード**

5. **WordPress管理画面でプラグインを再度有効化**

---

## トラブルシューティング

### プラグインが表示されない

**原因**: ファイルが正しい場所にない

**解決策**:
```bash
# 正しい場所を確認
ls -la /path/to/wordpress/wp-content/plugins/wp-hero-image-manager/

# メインファイルが存在するか確認
ls -la /path/to/wordpress/wp-content/plugins/wp-hero-image-manager/wp-hero-image-manager.php
```

### 有効化できない / エラーが出る

**原因1**: PHPバージョンが古い

**解決策**: PHPを7.2以上にアップグレード
```bash
php -v  # 現在のバージョンを確認
```

**原因2**: ファイルパーミッションが正しくない

**解決策**:
```bash
cd /path/to/wordpress/wp-content/plugins/wp-hero-image-manager
chmod 644 wp-hero-image-manager.php
```

### メタボックスが表示されない

**原因**: JavaScriptまたはCSSが読み込まれていない

**解決策**:
1. ブラウザのキャッシュをクリア
2. WordPressのキャッシュプラグインを無効化
3. ブラウザのコンソールでエラーを確認（F12キーで開発者ツール）

### 画像選択ボタンが動作しない

**原因**: 他のプラグインとの競合

**解決策**:
1. 他のプラグインを一時的に無効化
2. 一つずつ有効化して競合を特定
3. 問題のあるプラグインを報告

### ヒーロー画像が表示されない

**原因1**: 「ヒーロー画像を使用する」がチェックされていない

**解決策**: 投稿編集画面で設定を確認

**原因2**: テーマとの競合

**解決策**: ブラウザの開発者ツールでCSSを確認
```css
/* テーマのCSSで非表示になっている可能性 */
.wp-hero-image-container { display: block !important; }
```

### アイキャッチ画像が重複して表示される

**原因**: 「記事内でアイキャッチ画像を非表示にする」がチェックされていない

**解決策**:
1. 投稿編集画面を開く
2. 「ヒーロー画像設定」メタボックスを探す
3. 「記事内でアイキャッチ画像を非表示にする（重複防止）」にチェック
4. 投稿を更新

---

## パーミッション設定ガイド

### 推奨パーミッション

```bash
# プラグインディレクトリ
chmod 755 wp-hero-image-manager/

# PHPファイル
chmod 644 wp-hero-image-manager/*.php

# CSSファイル
chmod 644 wp-hero-image-manager/assets/css/*.css

# JavaScriptファイル
chmod 644 wp-hero-image-manager/assets/js/*.js

# assetsディレクトリ
chmod 755 wp-hero-image-manager/assets/
chmod 755 wp-hero-image-manager/assets/css/
chmod 755 wp-hero-image-manager/assets/js/
```

### 一括設定コマンド

```bash
cd /path/to/wordpress/wp-content/plugins/wp-hero-image-manager
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
```

---

## アンインストール方法

### プラグインのみを削除（データは保持）

```
WordPress管理画面 → プラグイン → インストール済みプラグイン
→ 「WP Hero Image Manager」を無効化 → 削除
```

### データベースのデータも削除

プラグインを削除後、以下のSQLを実行:

```sql
-- ヒーロー画像関連のメタデータを削除
DELETE FROM wp_postmeta WHERE meta_key LIKE '_hero_image%';
DELETE FROM wp_postmeta WHERE meta_key = '_use_hero_image';
DELETE FROM wp_postmeta WHERE meta_key = '_hide_featured_image';

-- オプションを削除
DELETE FROM wp_options WHERE option_name = 'wp_hero_image_manager_version';
```

**注意**: データベース操作は慎重に行い、必ずバックアップを取ってください。

---

## サポート

インストールに問題がある場合:

1. **ドキュメントを確認**: `PLUGIN-README.md`, `README.md`
2. **GitHub Issues**: https://github.com/inosuke680-sys/toppage-WP-INSIDE-/issues
3. **既知の問題**: `CHANGELOG.md` を確認

---

## 次のステップ

インストールが完了したら:

1. [README.md](README.md) で基本的な使い方を確認
2. [PLUGIN-README.md](PLUGIN-README.md) で詳細な機能を学習
3. テスト投稿を作成して動作を確認
4. 本番環境で使用開始

---

**注意**: 本番環境にインストールする前に、必ずステージング環境でテストしてください。
