# Umaten トップページプラグイン v2.8.1 - 安定版

## バージョン情報

- **現在のバージョン**: v2.8.1
- **ベースバージョン**: v2.8.0（安定版）
- **リリース日**: 2025年11月15日

## v2.8.1の変更点

v2.8.0の安定性を維持しつつ、最小限の改善のみを実施：

- v2.8.0のコードベースをそのまま維持
- バージョン番号の更新のみ
- シンプルな一括処理スクリプトを追加

## v2.8.0の機能（そのまま維持）

### タグ・投稿判定の修正

```
/hokkaido/hakodate/cafe/ にアクセス
 ↓
タグ「cafe」の存在を確認
 ↓ 存在する
タグアーカイブページを表示 ✓
```

### ヒーロー画像メタデータ保存

- 本文から画像を抽出
- `_umaten_hero_image_url` メタデータとして保存
- シンプルで安定した動作

## インストール方法

### 自動デプロイ

```bash
curl -o /tmp/deploy-v2.8.1.sh https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj/deploy-production-v2.8.1.sh
chmod +x /tmp/deploy-v2.8.1.sh
sudo /tmp/deploy-v2.8.1.sh
```

### プラグイン有効化

WordPress管理画面 → プラグイン:
1. 既存のUmatenプラグインを無効化
2. 「Umaten トップページ v2.8.1」を有効化
3. 設定 → パーマリンク → 「変更を保存」

### 既存投稿へのヒーロー画像設定

#### シンプルなPHPスクリプト（推奨）

```bash
# WordPressルートディレクトリに移動
cd /home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot

# スクリプトをダウンロード
curl -o bulk-set-hero-images-simple.php https://raw.githubusercontent.com/inosuke680-sys/toppage-WP-INSIDE-/claude/plugin-v2.6.0-upgrade-015K3j6rBvErzVhU5LxoG5Vj/bulk-set-hero-images-simple.php

# 実行
php bulk-set-hero-images-simple.php
```

このスクリプトは：
- WP-CLIに依存しない
- シンプルで動作が確実
- 本文から画像URLを抽出してメタデータに保存

## デバッグログ確認

```bash
tail -f /home/kusanagi/45515055731ac663c7c3ad4c/DocumentRoot/wp-content/debug.log | grep "Umaten"
```

## トラブルシューティング

### タグページが投稿ページに飛ぶ場合

パーマリンク設定を再保存：
WordPress管理画面 → 設定 → パーマリンク → 「変更を保存」

### ヒーロー画像が表示されない場合

1. ブラウザのキャッシュをクリア
2. WordPressのキャッシュをクリア
3. テーマ側でメタデータを読み込む設定が必要な場合があります

## v2.8.1の設計思想

**シンプルさと安定性を最優先**

- v2.8.0の安定したコードベースを維持
- 複雑な機能は追加しない
- 動作が確実なシンプルな実装

## ライセンス

GPL v2 or later

## 開発者

Umaten - https://umaten.jp
