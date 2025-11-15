# Umaten トップページ プラグイン v2.6.0

## 概要

動的なカテゴリ・タグ表示を備えたトップページ用WordPressプラグインです。

## v2.6.0 の主な改善点

### 1. ヒーロー画像メタデータ保存機能（アイキャッチ非登録）

**問題点（v2.5.0）：**
- アイキャッチ画像が自動で設定され、記事本文にも表示されてしまう
- 本文の画像とアイキャッチが重複表示される

**解決策（v2.6.0）：**
- アイキャッチとして登録せず、メタデータとして `_umaten_hero_image_url` に保存
- 一覧ページでは `Umaten_Toppage_Hero_Image::get_hero_image_url()` でヒーロー画像を取得
- 記事本文には影響を与えず、一覧ページでのみヒーロー画像を表示

### 2. 記事ページ表示の完全修正

**問題点（v2.5.0）：**
- 記事ページに飛ぶとトップページにリダイレクトされる
- カテゴリチェックが厳格すぎて、投稿が表示されないケースがある

**解決策（v2.6.0）：**
- カテゴリチェックを緩和し、投稿が見つかった場合は必ず表示
- `is_category` と `is_tag` フラグを明示的に false に設定
- デバッグログを強化して問題の特定を容易に

### 3. 安定性の向上

- リビジョン保存時のスキップ処理を追加
- 後方互換性の確保（アイキャッチ画像がある場合はそちらを優先）
- デバッグログの強化

## 機能

- **3ステップナビゲーション**: 親カテゴリ → 子カテゴリ → ジャンル（タグ）
- **SEO最適化**: メタタグ、OGPタグの自動生成
- **URLリライト**: カスタムURL構造のサポート
- **ヒーロー画像メタデータ保存**: 記事本文に影響を与えない画像管理
- **検索結果ページ**: モダンなUIでの検索結果表示
- **独自アクセスカウント**: 投稿のビュー数トラッキング
- **全エリア対応**: 北海道から九州・沖縄まで8エリア対応

## インストール

### 自動デプロイ（推奨）

```bash
# SSHで本番サーバーに接続してから実行
curl -o /tmp/deploy-v2.6.0.sh https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj/deploy-production.sh
chmod +x /tmp/deploy-v2.6.0.sh
sudo /tmp/deploy-v2.6.0.sh
```

### 手動インストール

1. `umaten-toppage-v2.6` フォルダを `/wp-content/plugins/` にアップロード
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

テーマファイルでヒーロー画像を取得する場合：

```php
<?php
$hero_image_url = Umaten_Toppage_Hero_Image::get_hero_image_url(get_the_ID());
if ($hero_image_url) {
    echo '<img src="' . esc_url($hero_image_url) . '" alt="' . esc_attr(get_the_title()) . '">';
}
?>
```

## v2.5.0 からのアップグレード

1. v2.5.0 を無効化（削除は不要）
2. v2.6.0 をインストール・有効化
3. 既存の投稿にヒーロー画像メタデータを設定する場合：
   - 管理画面で `Umaten_Toppage_Hero_Image::bulk_set_hero_images()` を実行（オプション）

## 技術仕様

- **WordPress バージョン**: 5.0 以上
- **PHP バージョン**: 7.4 以上
- **データベース**: カスタムテーブル不要（既存のメタデータテーブルを使用）

## ファイル構成

```
umaten-toppage-v2.6/
├── umaten-toppage.php          # メインプラグインファイル
├── includes/
│   ├── class-admin-settings.php    # 管理画面設定
│   ├── class-ajax-handler.php      # AJAX処理
│   ├── class-hero-image.php        # ヒーロー画像メタデータ保存（v2.6.0新規）
│   ├── class-search-results.php    # 検索結果ページ
│   ├── class-seo-meta.php          # SEOメタタグ
│   ├── class-shortcode.php         # ショートコード
│   ├── class-url-rewrite.php       # URLリライト（v2.6.0改善）
│   └── class-view-counter.php      # ビューカウンター
└── assets/
    ├── css/
    │   └── toppage.css             # スタイルシート
    └── js/
        └── toppage.js              # JavaScript
```

## 変更履歴

### v2.6.0 (2025-11-15)
- ✨ ヒーロー画像メタデータ保存機能の実装（アイキャッチ非登録）
- 🐛 記事ページ表示の完全修正（カテゴリチェック緩和）
- 🐛 トップページへの自動リダイレクト問題の修正
- 🔧 デバッグログの強化
- 🔧 安定性の向上

### v2.5.0 (2025-11-XX)
- REST API/AJAX完全セーフ実装
- 投稿保存500エラーの修正

### v2.4.0 (2025-11-XX)
- 管理画面での投稿更新エラーを解決

## サポート

- GitHub Issues: https://github.com/inosuke680-sys/toppage-WP-INSIDE-/issues
- 公式サイト: https://umaten.jp

## ライセンス

GPL v2 or later

## 作者

Umaten (https://umaten.jp)
