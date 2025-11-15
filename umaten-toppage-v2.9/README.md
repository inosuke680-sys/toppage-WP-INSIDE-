# Umaten トップページプラグイン v2.9.0

## バージョン情報

- **現在のバージョン**: v2.9.0
- **ベースバージョン**: v2.8.0
- **リリース日**: 2025年11月15日

## v2.9.0 重要な修正

### 修正1：ヒーロー画像がアイキャッチとして表示されない問題を完全解決

**問題の詳細:**
- 一覧ページでヒーロー画像が `no_img.png` のまま表示されない
- v2.8.0ではメタデータ `_umaten_hero_image_url` に保存するだけで、SWELLテーマが認識する`_thumbnail_id`として設定していなかった

**v2.9.0の解決策:**
1. 本文から抽出した画像URLをメディアライブラリから検索
2. attachment IDを取得し、`set_post_thumbnail()`でアイキャッチとして設定
3. 記事ページでは`post_thumbnail_html`フィルターで非表示（重複回避）

**結果:**
- ✅ 一覧ページ・アーカイブページでヒーロー画像が正しく表示される
- ✅ 記事ページではヒーロー画像が重複しない（本文にのみ表示）
- ✅ SWELLテーマと完全互換

### 修正2：「すべてのジャンル」ボタンの問題調査・デバッグ強化

**デバッグログの大幅強化:**
- 2段階URL処理（`/親/子/`）のログを詳細化
- カテゴリ存在チェック、投稿検索のフローを完全に追跡可能

## v2.8.0からの変更点

### ヒーロー画像処理（class-hero-image.php）

**v2.8.0（問題あり）:**
```php
// メタデータにURLを保存するだけ
update_post_meta($post_id, '_umaten_hero_image_url', $image_url);
// → SWELLテーマが認識せず、no_img.pngが表示される
```

**v2.9.0（修正後）:**
```php
// 1. 画像URLからattachment IDを取得
$attachment_id = $this->get_attachment_id_from_url($image_url);

// 2. アイキャッチとして設定
set_post_thumbnail($post_id, $attachment_id);

// 3. 記事ページでは非表示にするフィルター追加
add_filter('post_thumbnail_html', array($this, 'hide_thumbnail_on_single'));
```

### URL処理の改善（class-url-rewrite.php）

- v2.8.0のタグ・投稿判定ロジックを維持
- デバッグログをさらに強化

## インストール方法

### SSH経由での自動デプロイ

```bash
curl -o /tmp/deploy-v2.9.0.sh https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj/deploy-production-v2.9.0.sh
chmod +x /tmp/deploy-v2.9.0.sh
sudo /tmp/deploy-v2.9.0.sh
```

### デバッグモードの有効化（推奨）

```php
// wp-config.php に追加
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
```

### ログの確認方法

```bash
# Kusanagi環境の場合
tail -f /home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot/wp-content/debug.log
```

## デプロイ後の手順

1. WordPress管理画面にログイン
2. プラグイン → インストール済みプラグイン
3. 「Umaten トップページ v2.8.0」を無効化
4. 「Umaten トップページ v2.9.0」を有効化
5. 設定 → パーマリンク設定 → 「変更を保存」をクリック（重要）
6. ブラウザのキャッシュをクリア
7. 一覧ページでヒーロー画像が表示されることを確認

## 既存投稿のヒーロー画像を一括設定

すべての既存投稿にヒーロー画像を設定する場合:

```bash
# SSH経由でWordPressルートディレクトリに移動
cd /home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot

# WP-CLIで一括処理を実行
# （管理画面からも実行可能）
```

または、管理画面から数件の投稿を「更新」ボタンを押すだけで、ヒーロー画像が自動設定されます。

## トラブルシューティング

### ヒーロー画像が表示されない場合

1. デバッグログを確認：
   ```
   tail -f /home/kusanagi/.../debug.log | grep "Umaten Toppage v2.9.0"
   ```

2. ブラウザとWordPressのキャッシュをクリア

3. 投稿を再保存（「更新」ボタンをクリック）

### 記事ページでヒーロー画像が重複する場合

- v2.9.0では`post_thumbnail_html`フィルターで自動的に非表示にします
- もし表示される場合は、テーマのカスタマイズを確認

## ライセンス

GPL v2 or later

## 開発者

Umaten - https://umaten.jp
