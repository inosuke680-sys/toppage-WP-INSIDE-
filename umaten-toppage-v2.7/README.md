# Umaten トップページ プラグイン v2.7.0

## 概要

動的なカテゴリ・タグ表示を備えたトップページ用WordPressプラグインです。

## v2.7.0 の主な改善点（v2.6.0からの完全修正）

### 1. ヒーロー画像のSWELLテーマ完全対応 ✅

**問題点（v2.6.0）：**
- メタデータとして保存していたが、SWELLテーマの一覧ページで表示されない
- `has_post_thumbnail()`や`get_the_post_thumbnail_url()`が認識できない
- 結果：no_img.pngが表示される

**解決策（v2.7.0）：**
- WordPressフィルターフックを使用してSWELLテーマと完全互換
- `post_thumbnail_html`フィルター - サムネイルHTMLを生成
- `get_post_metadata`フィルター - `_thumbnail_id`を疑似的に返す
- `wp_get_attachment_image_src`フィルター - ヒーロー画像URLを返す
- **標準のWordPress関数でもヒーロー画像を取得可能に**

### 2. 記事ページ表示の完全修正 ✅

**問題点（v2.6.0）：**
- カテゴリチェックが厳しすぎて、投稿が見つかってもアーカイブページとして処理される
- 結果：記事ページに飛ぶとトップページにリダイレクトされる

**解決策（v2.7.0）：**
- **投稿が見つかった場合は、カテゴリチェックを完全にスキップして必ず表示**
- デバッグログを大幅に強化（どのステップで何が起きているか明確に）
- リダイレクト問題を完全に解決

### 3. デバッグ機能の強化 ✅

- URLパースの詳細ログ
- 投稿検索の詳細ログ
- クエリセットアップの詳細ログ
- テンプレートロードの詳細ログ
- 問題の特定が容易に

## 機能

- **3ステップナビゲーション**: 親カテゴリ → 子カテゴリ → ジャンル（タグ）
- **SEO最適化**: メタタグ、OGPタグの自動生成
- **URLリライト**: カスタムURL構造のサポート
- **ヒーロー画像メタデータ保存**: 記事本文に影響を与えない画像管理（SWELL完全対応）
- **検索結果ページ**: モダンなUIでの検索結果表示
- **独自アクセスカウント**: 投稿のビュー数トラッキング
- **全エリア対応**: 北海道から九州・沖縄まで8エリア対応

## インストール

### 自動デプロイ（推奨）

```bash
# SSHで本番サーバーに接続してから実行
curl -o /tmp/deploy-v2.7.0.sh https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj/deploy-production-v2.7.0.sh
chmod +x /tmp/deploy-v2.7.0.sh
sudo /tmp/deploy-v2.7.0.sh
```

### 手動インストール

1. `umaten-toppage-v2.7` フォルダを `/wp-content/plugins/` にアップロード
2. WordPress管理画面の「プラグイン」メニューから「Umaten トップページ」を有効化
3. 「設定」→「Umaten トップページ」でエリア設定を確認

## 使用方法

### ショートコード

トップページに以下のショートコードを配置：

```php
[umaten_toppage]
```

### 検索結果ページ

検索結果ページまたはアーカイブページに以下のショートコードを配置：

```php
[umaten_search_results]
```

### ヒーロー画像の取得

**v2.7.0では標準のWordPress関数で取得可能：**

```php
<?php
// SWELLテーマや他のテーマでも動作
if (has_post_thumbnail()) {
    the_post_thumbnail('large');
}

// または
$thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'large');
if ($thumbnail_url) {
    echo '<img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr(get_the_title()) . '">';
}
?>
```

**プラグイン独自の関数でも取得可能：**

```php
<?php
$hero_image_url = Umaten_Toppage_Hero_Image::get_hero_image_url(get_the_ID());
if ($hero_image_url) {
    echo '<img src="' . esc_url($hero_image_url) . '" alt="' . esc_attr(get_the_title()) . '">';
}
?>
```

## v2.6.0 からのアップグレード

1. v2.6.0 を無効化（削除は不要）
2. v2.7.0 をインストール・有効化
3. 既存の投稿にヒーロー画像メタデータを設定する場合：
   - 管理画面で `Umaten_Toppage_Hero_Image::bulk_set_hero_images()` を実行（オプション）
4. **WP_DEBUGを有効にして動作確認（推奨）**
   - `wp-config.php` で `define('WP_DEBUG', true);` を設定
   - エラーログで詳細な動作を確認可能

## トラブルシューティング

### 一覧ページでヒーロー画像が表示されない場合

1. WP_DEBUGを有効にして、エラーログを確認
2. 投稿本文に画像が含まれているか確認
3. プラグインが正しく有効化されているか確認
4. ブラウザのキャッシュをクリア
5. WordPressのキャッシュプラグインをクリア

### 記事ページにアクセスすると404になる場合

1. WP_DEBUGを有効にして、エラーログを確認
   - "Found post by slug" のログが出ているか確認
   - "Setting up single post query" のログが出ているか確認
2. パーマリンク設定を保存し直す（設定 → パーマリンク → 変更を保存）
3. プラグインを一度無効化して再度有効化

### デバッグログの確認方法

```bash
# Kusanagi環境の場合
tail -f /var/log/php-fpm/www-error.log

# または
tail -f /home/kusanagi/[site_name]/log/nginx/error.log
```

ログには以下のような情報が出力されます：
- `Umaten Toppage v2.7.0: Processing 404 for path: hokkaido/hakodate/post-slug`
- `Umaten Toppage v2.7.0: Found post by slug 'post-slug' (ID: 123, Title: Post Title)`
- `Umaten Toppage v2.7.0: Setting up single post query for post ID 123`
- `Umaten Toppage v2.7.0: Post loaded successfully`

## 技術仕様

- **WordPress バージョン**: 5.0 以上
- **PHP バージョン**: 7.4 以上
- **データベース**: カスタムテーブル不要（既存のメタデータテーブルを使用）
- **互換性**: SWELL、その他標準的なテーマと互換

## ファイル構成

```
umaten-toppage-v2.7/
├── umaten-toppage.php          # メインプラグインファイル
├── includes/
│   ├── class-admin-settings.php    # 管理画面設定
│   ├── class-ajax-handler.php      # AJAX処理
│   ├── class-hero-image.php        # ヒーロー画像（v2.7.0完全書き直し）
│   ├── class-search-results.php    # 検索結果ページ
│   ├── class-seo-meta.php          # SEOメタタグ
│   ├── class-shortcode.php         # ショートコード
│   ├── class-url-rewrite.php       # URLリライト（v2.7.0完全修正）
│   └── class-view-counter.php      # ビューカウンター
└── assets/
    ├── css/
    │   └── toppage.css             # スタイルシート
    └── js/
        └── toppage.js              # JavaScript
```

## 変更履歴

### v2.7.0 (2025-11-15)
- 🎉 ヒーロー画像のSWELLテーマ完全対応（WordPressフィルターフック使用）
- 🎉 記事ページ表示の完全修正（カテゴリチェック完全スキップ）
- 🔧 デバッグログの大幅強化
- 🐛 リダイレクト問題の完全解決
- ✨ 標準WordPress関数での画像取得に完全対応

### v2.6.0 (2025-11-15)
- ✨ ヒーロー画像メタデータ保存機能の実装（アイキャッチ非登録）
- 🐛 記事ページ表示の修正（カテゴリチェック緩和）
- 🐛 トップページへの自動リダイレクト問題の修正（一部）
- 🔧 デバッグログの強化

### v2.5.0 (2025-11-XX)
- REST API/AJAX完全セーフ実装
- 投稿保存500エラーの修正

## サポート

- GitHub Issues: https://github.com/inosuke680-sys/toppage-WP-INSIDE-/issues
- 公式サイト: https://umaten.jp

## ライセンス

GPL v2 or later

## 作者

Umaten (https://umaten.jp)
