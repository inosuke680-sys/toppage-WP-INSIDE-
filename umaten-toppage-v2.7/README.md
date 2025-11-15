# Umaten トップページプラグイン v2.7.0

## バージョン情報

- **現在のバージョン**: v2.7.0
- **ベースバージョン**: v2.6.0
- **リリース日**: 2025年11月15日

## v2.7.0 変更点

### 完全修正

1. **記事ページリダイレクト問題の完全解決**
   - 投稿が見つかった場合、カテゴリチェックを完全に削除して無条件に表示
   - `class-url-rewrite.php`の82-88行、96-102行を修正
   - 記事ページがトップページにリダイレクトされる問題を完全解決

2. **ショートコード表示問題の修正**
   - `[umaten_toppage]` が記事ページに表示される問題を修正
   - `class-shortcode.php`の69-72行で記事ページでは空文字を返すように修正

3. **ヒーロー画像の安定化**
   - v2.6.0のシンプルな実装を維持（フィルターフックなし）
   - メタデータ保存のみに特化することで、SWELLテーマとの互換性問題を回避
   - 公開API: `Umaten_Toppage_Hero_Image::get_hero_image_url($post_id)`

### v2.6.0からの改善点

| 項目 | v2.6.0 | v2.7.0 |
|------|--------|--------|
| 記事ページ表示 | カテゴリチェック有り（一部リダイレクト発生） | カテゴリチェック無し（完全表示） |
| ショートコード | 記事ページにも表示 | 記事ページでは非表示 |
| ヒーロー画像 | シンプル実装 | シンプル実装（v2.6.0と同じ） |
| 安定性 | 安定 | 安定（v2.6.0ベース） |

### 動作確認済み環境

- WordPress: 6.4以降
- PHP: 7.4以降
- テーマ: SWELL
- サーバー: Kusanagi

## 機能概要

### 1. URL リライト機能

カスタムURL構造で投稿とアーカイブページを処理：

```
/親カテゴリ/子カテゴリ/           → アーカイブページ
/親カテゴリ/子カテゴリ/タグ/      → タグ付きアーカイブページ
/親カテゴリ/投稿スラッグ/          → 投稿ページ
/親カテゴリ/子カテゴリ/投稿スラッグ/ → 投稿ページ
```

### 2. ヒーロー画像メタデータ保存

- 投稿本文から自動的にヒーロー画像を抽出
- `_umaten_hero_image_url` メタデータとして保存
- アイキャッチとしては登録しない（記事本文への影響を回避）

優先順位：
1. `restaurant-hero-image` クラスの画像
2. `ls-is-cached lazyloaded` クラスの画像
3. `data-src` 属性の画像
4. 最初の通常画像

### 3. ショートコード `[umaten_toppage]`

トップページ用の動的コンテンツを表示：
- ヒーローセクション
- 統計バー（掲載店舗数、口コミ数、月間アクセス数）
- 人気ジャンル
- エリアから探す

**v2.7.0新機能**: 記事ページでは自動的に非表示

### 4. 検索結果ページ

モダンUIの検索結果ページを提供

### 5. ビューカウンター

投稿ごとの閲覧数をカウント

## インストール方法

### SSH経由での自動デプロイ

```bash
curl -o /tmp/deploy-v2.7.0.sh https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj/deploy-production-v2.7.0.sh
chmod +x /tmp/deploy-v2.7.0.sh
sudo /tmp/deploy-v2.7.0.sh
```

### 手動インストール

1. `umaten-toppage-v2.7` フォルダを `/wp-content/plugins/` にアップロード
2. WordPress管理画面でプラグインを有効化
3. 必要に応じて設定を調整

## 既存投稿のヒーロー画像一括設定（オプション）

既存の投稿にヒーロー画像メタデータを一括設定する場合：

```bash
# スクリプトをダウンロード
curl -o /tmp/bulk-set-hero-images.php https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj/bulk-set-hero-images.php

# DocumentRootに移動
cd /home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot

# 一括設定スクリプトを実行
sudo -u kusanagi /opt/kusanagi/php/bin/php /opt/kusanagi/bin/wp eval-file /tmp/bulk-set-hero-images.php
```

## ショートコード削除スクリプト（オプション）

既存の投稿本文から `[umaten_toppage]` を削除する場合：

```bash
# スクリプトをダウンロード
curl -o /tmp/remove-shortcode-from-posts.php https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj/remove-shortcode-from-posts.php

# DocumentRootに移動して実行
cd /home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot
sudo -u kusanagi /opt/kusanagi/php/bin/php /opt/kusanagi/bin/wp eval-file /tmp/remove-shortcode-from-posts.php
```

## トラブルシューティング

### 記事ページが表示されない場合

1. **パーマリンク設定をフラッシュ**
   - WordPres管理画面 → 設定 → パーマリンク → 「変更を保存」をクリック

2. **デバッグモードを有効化**
   ```php
   // wp-config.php に追加
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

3. **ログを確認**
   ```bash
   tail -f /home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot/wp-content/debug.log
   ```

### ヒーロー画像が表示されない場合

1. **メタデータを確認**
   ```bash
   cd /home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot
   sudo -u kusanagi /opt/kusanagi/php/bin/php /opt/kusanagi/bin/wp post meta get [投稿ID] _umaten_hero_image_url
   ```

2. **一括設定スクリプトを実行**
   （上記「既存投稿のヒーロー画像一括設定」参照）

3. **テーマのテンプレートを確認**
   - archive.phpで `Umaten_Toppage_Hero_Image::get_hero_image_url(get_the_ID())` を使用してヒーロー画像を取得

## 設定

### エリア設定

WordPress管理画面 → Umaten トップページ → エリア設定

各エリアのステータス：
- **Published**: 公開（ユーザーに表示）
- **Coming Soon**: 準備中（「準備中」バッジ付きで表示）
- **Hidden**: 非表示

## API

### ヒーロー画像URL取得

```php
$hero_image_url = Umaten_Toppage_Hero_Image::get_hero_image_url($post_id);

if ($hero_image_url) {
    echo '<img src="' . esc_url($hero_image_url) . '" alt="' . get_the_title($post_id) . '">';
}
```

## サポート

問題が発生した場合は、以下の情報を含めてご連絡ください：

1. WordPressバージョン
2. PHPバージョン
3. 使用テーマ
4. エラーログ（`wp-content/debug.log`）
5. 具体的な問題の内容

## ライセンス

GPL v2 or later

## 開発者

Umaten - https://umaten.jp
