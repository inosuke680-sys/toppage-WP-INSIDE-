# Umaten トップページプラグイン v2.10.0 - 多重ハンドリング完全版

## バージョン情報

- **現在のバージョン**: v2.10.0
- **ベースバージョン**: v2.9.0
- **リリース日**: 2025年11月15日
- **重要度**: 最高（すべての問題を徹底的に解決）

## v2.10.0で実装した多重ハンドリング

v2.9.0で問題が解決しなかったため、考えうるすべての原因を洗い出し、多重のエラーハンドリングを実装しました。

### 問題1: ヒーロー画像が表示されない → 6+5+1の多重ハンドリング

#### 1. 画像抽出の6つのパターン実装（優先順位付き）

```php
// パターン1: restaurant-hero-imageクラス
// パターン2: ls-is-cached lazyloadedクラス
// パターン3: data-src属性（Lazy Load）
// パターン4: srcset属性
// パターン5: wp:image ブロック
// パターン6: 通常のsrc属性
```

#### 2. attachment ID取得の5つの方法実装

```php
// 方法1: attachment_url_to_postid()（WordPress標準関数）
// 方法2: _wp_attached_file メタデータ検索
// 方法3: guid 列検索
// 方法4: ファイル名のバリエーション検索（サイズ違い対応）
// 方法5: URLパスの部分一致検索
```

#### 3. 外部URL画像の自動インポート機能

メディアライブラリに画像が見つからない場合、自動的にダウンロードしてインポートします。

```php
// 外部URLの場合、自動的にdownload_url()でダウンロード
// media_handle_sideload()でメディアライブラリに登録
```

#### 4. アイキャッチ設定後の検証機能

```php
// set_post_thumbnail()実行後、get_post_thumbnail_id()で検証
// 失敗した場合はメタデータのみ保存（フォールバック）
```

#### 5. 完全なデバッグログシステム

すべてのステップで詳細なログを出力します：
- タイムスタンプ付きログ
- データ構造の完全な出力
- 成功/失敗の明示的な表示（✓/✗）

### 問題2: 「すべてのジャンル」リダイレクト → 多重防御実装

#### 1. template_redirectフックの優先度を1に変更

```php
// 優先度999 → 1に変更（最優先で実行）
add_action('template_redirect', array($this, 'handle_404_redirect'), 1);
```

#### 2. redirect_canonicalフィルターでリダイレクト防止

```php
// WordPressの自動リダイレクトを無効化
add_filter('redirect_canonical', array($this, 'prevent_canonical_redirect'), 10, 2);
```

#### 3. 2段階URL処理の詳細ログ強化

```
/hokkaido/hakodate/ にアクセス
 ↓
ログ: "2-stage URL detected"
 ↓
ログ: "Child category 'hakodate' exists - displaying as archive"
 ↓
ログ: "Setting up archive query"
```

## インストール方法

### 1. 自動デプロイ（推奨）

```bash
curl -o /tmp/deploy-v2.10.0.sh https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj/deploy-production-v2.10.0.sh
chmod +x /tmp/deploy-v2.10.0.sh
sudo /tmp/deploy-v2.10.0.sh
```

### 2. プラグインを有効化

WordPress管理画面 → プラグイン:
1. 「Umaten トップページ v2.9.0」を無効化
2. 「Umaten トップページ v2.10.0」を有効化
3. 設定 → パーマリンク → 「変更を保存」（重要）

### 3. 既存投稿へのヒーロー画像一括設定

#### WP-CLIを使用（推奨）

```bash
# WordPressルートディレクトリに移動
cd /home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot

# スクリプトをダウンロード
curl -o bulk-set-hero-images.sh https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj/bulk-set-hero-images.sh
chmod +x bulk-set-hero-images.sh

# 実行（アイキャッチ未設定の投稿のみ）
./bulk-set-hero-images.sh

# 強制更新（すべての投稿）
./bulk-set-hero-images.sh --force
```

#### WP-CLIコマンド直接実行

```bash
# アイキャッチ未設定の投稿のみ
wp umaten hero-images

# すべての投稿を強制更新
wp umaten hero-images --force

# 処理数を制限
wp umaten hero-images --limit=100

# オフセット指定
wp umaten hero-images --offset=100 --limit=100
```

### 4. デバッグログ確認

```bash
# リアルタイムでログを監視
tail -f /home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot/wp-content/debug.log | grep "Umaten Toppage"

# v2.10.0のログのみ表示
tail -f /home/kusanagi/.../debug.log | grep "v2.10.0"

# ヒーロー画像のログのみ表示
tail -f /home/kusanagi/.../debug.log | grep "Hero Image"
```

## トラブルシューティング

### ヒーロー画像が表示されない場合

**ステップ1: デバッグログを確認**

```bash
tail -n 100 /home/kusanagi/.../debug.log | grep "Hero Image"
```

以下を確認：
- 「Extracted image URL」があるか？
- 「Method X success」があるか？
- 「Thumbnail set and verified」があるか？

**ステップ2: 診断スクリプト実行**

```bash
# WordPressルートディレクトリに移動
cd /home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot

# 診断スクリプトをダウンロード
curl -o debug-umaten-issues.php https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj/debug-umaten-issues.php

# 実行
php debug-umaten-issues.php
```

診断結果を確認：
- アイキャッチID (_thumbnail_id) が設定されているか？
- 本文に画像が存在するか？
- メディアライブラリ検索で見つかるか？

**ステップ3: 手動で再設定**

```bash
# 特定の投稿のみ強制更新
wp post meta update <POST_ID> _umaten_force_hero_update 1
wp post update <POST_ID> --post_content="$(wp post get <POST_ID> --field=post_content)"
```

### 「すべてのジャンル」でトップページにリダイレクトされる場合

**ステップ1: デバッグログを確認**

```bash
tail -f /home/kusanagi/.../debug.log | grep "2-stage URL"
```

以下を確認：
- 「2-stage URL detected」が表示されるか？
- 「Child category 'xxx' exists」が表示されるか？
- 「Setting up archive query」が表示されるか？

**ステップ2: カテゴリが存在するか確認**

```bash
wp term list category --fields=term_id,name,slug
```

**ステップ3: パーマリンク再保存**

WordPress管理画面 → 設定 → パーマリンク → 「変更を保存」

**ステップ4: リダイレクトキャッシュをクリア**

```bash
# Nginxの場合
sudo systemctl reload nginx

# Apacheの場合
sudo systemctl reload httpd
```

### WP-CLIコマンドが動作しない場合

```bash
# WP-CLIがインストールされているか確認
wp --version

# プラグインが有効化されているか確認
wp plugin list --status=active | grep umaten

# WordPressルートディレクトリで実行しているか確認
pwd
ls wp-config.php
```

## デバッグ設定（推奨）

wp-config.phpに追加：

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
```

## v2.10.0の技術詳細

### ヒーロー画像処理フロー

```
投稿保存
 ↓
[ステップ1] 6パターンで画像URL抽出
 ↓ 成功
[ステップ2] URLを正規化（相対→絶対）
 ↓
[ステップ3] 5つの方法でattachment ID取得
 ↓ 失敗
[ステップ4] 外部URLの場合、ダウンロード&インポート
 ↓ 成功
[ステップ5] set_post_thumbnail()でアイキャッチ設定
 ↓
[ステップ6] 設定を検証（get_post_thumbnail_id()）
 ↓ 成功
[完了] メタデータ保存 + ログ出力
```

### URL処理フロー

```
URLリクエスト (/hokkaido/hakodate/)
 ↓
[優先度1] template_redirect フック発火
 ↓
is_404() チェック
 ↓ Yes
パスを分解（/親/子/）
 ↓
get_term_by('slug', 'hakodate', 'category')
 ↓ 成功
setup_archive_query() でアーカイブページ設定
 ↓
redirect_canonical フィルターでリダイレクト防止
 ↓
[完了] アーカイブページ表示
```

## ライセンス

GPL v2 or later

## 開発者

Umaten - https://umaten.jp

## サポート

問題が解決しない場合は、デバッグログと診断スクリプトの結果を添えてお問い合わせください。
