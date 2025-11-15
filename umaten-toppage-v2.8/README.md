# Umaten トップページプラグイン v2.8.0

## バージョン情報

- **現在のバージョン**: v2.8.0
- **ベースバージョン**: v2.7.0
- **リリース日**: 2025年11月15日

## v2.8.0 重要な修正

### 緊急修正：タグページが投稿ページに誤遷移する問題を完全解決

**問題の詳細:**
- `/hokkaido/hakodate/cafe/` （カフェタグの一覧ページ）にアクセスすると
- `cafeteria-morie-hakodate` のような投稿ページに誤遷移していた

**根本原因:**
v2.7.0のURL処理ロジックで、3段階URL（/親/子/第3セグメント/）の処理順序が間違っていました

**v2.8.0の正しい処理順序:**
1. まずタグ（post_tag）として存在するかチェック
2. タグが存在する場合はアーカイブページとして処理（投稿検索しない）
3. タグが存在しない場合のみ、投稿として検索

### v2.8.0の主な改善点

1. **URL処理ロジックの完全修正**
   - タグと投稿の優先順位を修正（タグ優先）
   - 2段階URL: カテゴリ存在確認 → 投稿検索
   - 3段階URL: タグ存在確認 → 投稿検索

2. **デバッグログの大幅強化**
   - 各処理ステップで詳細なログを記録
   - URL処理フローを完全に追跡可能

3. **エラーハンドリングの改善**
   - データベースクエリのエラーチェック追加

## インストール方法

### SSH経由での自動デプロイ

```bash
curl -o /tmp/deploy-v2.8.0.sh https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj/deploy-production-v2.8.0.sh
chmod +x /tmp/deploy-v2.8.0.sh
sudo /tmp/deploy-v2.8.0.sh
```

### デバッグモードの有効化（推奨）

```php
// wp-config.php に追加
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### ログの確認方法

```bash
# Kusanagi環境の場合
tail -f /home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot/wp-content/debug.log
```

## ライセンス

GPL v2 or later

## 開発者

Umaten - https://umaten.jp
