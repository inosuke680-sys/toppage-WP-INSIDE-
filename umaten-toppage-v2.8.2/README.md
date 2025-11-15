# Umaten トップページプラグイン v2.8.2

## バージョン情報

- **現在のバージョン**: v2.8.2
- **ベースバージョン**: v2.8.1（安定版）
- **リリース日**: 2025年11月15日

## v2.8.2の変更点

v2.8.1の安定性を維持しつつ、ヒーロー画像の自動設定機能を追加：

### ヒーロー画像の自動アイキャッチ設定

本文から抽出したヒーロー画像を、WordPressの標準アイキャッチ画像として自動設定します。

```
投稿の保存時
 ↓
本文から画像を抽出（restaurant-hero-imageクラス優先）
 ↓
メディアライブラリでattachment IDを検索
 ↓
set_post_thumbnail()でアイキャッチとして設定 ✓
 ↓
一覧ページでSWELLテーマが自動的に表示 ✓
```

### 記事ページでの重複回避

記事ページではアイキャッチを自動的に非表示にして、本文中のヒーロー画像のみを表示します。

```php
// post_thumbnail_htmlフィルターで自動制御
if (is_single() || is_singular('post')) {
    return ''; // アイキャッチを非表示
}
return $html; // 一覧ページでは表示
```

### シンプルな実装

- v2.8.1のコードベースを維持
- `attachment_url_to_postid()`でattachment ID取得
- `set_post_thumbnail()`でアイキャッチ設定
- 複雑な処理は追加せず、シンプルで確実な動作を実現

## インストール方法

### 自動デプロイ

```bash
curl -o /tmp/deploy-v2.8.2.sh https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj/deploy-production-v2.8.2.sh
chmod +x /tmp/deploy-v2.8.2.sh
sudo /tmp/deploy-v2.8.2.sh
```

### プラグイン有効化

WordPress管理画面 → プラグイン:
1. 既存のUmatenプラグイン（v2.8.1など）を無効化
2. 「Umaten トップページ v2.8.2」を有効化
3. **重要**: 設定 → パーマリンク → 「変更を保存」

### 既存投稿へのアイキャッチ設定

新規投稿は自動的にアイキャッチが設定されますが、既存の投稿には一括設定スクリプトを使用します：

```bash
# WordPressルートディレクトリに移動
cd /home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot

# スクリプトをダウンロードして実行
curl -o bulk-set-hero-images-v2.8.2.php https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj/bulk-set-hero-images-v2.8.2.php && \
php bulk-set-hero-images-v2.8.2.php
```

このスクリプトは：
- アイキャッチ未設定の投稿を自動検出
- 本文から画像URLを抽出
- メディアライブラリでattachment IDを検索
- WordPressアイキャッチとして自動設定
- メタデータ（_umaten_hero_image_url）も保存（バックアップ）

## デバッグログ確認

```bash
tail -f /home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot/wp-content/debug.log | grep "Umaten"
```

v2.8.2では以下のログが出力されます：
- `Set thumbnail for post XXX: attachment ID YYY` - アイキャッチ設定成功
- `Saved hero image URL for post XXX` - メタデータ保存

## トラブルシューティング

### 一覧ページでアイキャッチが表示されない場合

1. ブラウザのキャッシュをクリア
2. WordPressのキャッシュをクリア
3. 一括設定スクリプトを実行して既存投稿にアイキャッチを設定

### 記事ページでアイキャッチが重複表示される場合

v2.8.2では自動的に非表示になりますが、テーマ側で独自に表示している場合は、テーマのカスタマイズが必要です。

### attachment IDが見つからない場合

メディアライブラリに画像が登録されていない可能性があります：
1. WordPress管理画面 → メディア で画像が存在するか確認
2. 画像のURLがWordPressのアップロードディレクトリ（wp-content/uploads/）にあるか確認

## v2.8.2の設計思想

**シンプルさと安定性を最優先**

- v2.8.1の安定したコードベースを維持
- WordPressの標準機能（set_post_thumbnail）を使用
- 複雑な処理は追加せず、確実に動作する実装
- SWELLテーマとの完全な互換性

## 技術仕様

### 画像抽出の優先順位

1. `restaurant-hero-image` クラスを持つ画像
2. `ls-is-cached lazyloaded` クラスを持つ画像
3. `data-src` 属性を持つ画像（Lazy Load）
4. 最初の通常の画像

### 保存されるデータ

- `_thumbnail_id` - WordPressアイキャッチID（新規）
- `_umaten_hero_image_url` - 画像URL（v2.8.1から継続、バックアップ用）

### フィルター

- `post_thumbnail_html` - 記事ページでアイキャッチを非表示

## ライセンス

GPL v2 or later

## 開発者

Umaten - https://umaten.jp
