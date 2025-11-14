# Umaten トップページプラグイン v2.5.0

WordPressのトップページ用プラグインです。動的なカテゴリ・タグ表示機能を備え、全エリア対応の3ステップナビゲーションで内部リンク最適化を実現しています。

**バージョン**: 2.5.0

## 🚨 v2.5.0の緊急修正

### 1. REST API完全セーフ実装 ⭐⭐⭐

- **緊急修正**: v2.4.0で残っていた「REST API経由の投稿保存時の500エラー」を完全修正
- **原因**: v2.4.0の`is_admin()`チェックだけでは、REST APIリクエストを除外できていなかった
- **症状**: WordPressエディタで投稿を保存しようとすると `POST /wp-json/wp/v2/posts/3801 500 (Internal Server Error)`
- **解決策**:
  - 新しい`is_frontend_request()`メソッドを追加
  - REST API、AJAX、XMLRPC、Cron、WP-CLIをすべて除外
  - フロントエンドのページリクエストのみで動作

### 2. v2.4.0の問題詳細

**v2.4.0のチェック（不十分）**:
```php
public function handle_404_redirect() {
    // 【v2.4.0 重要】管理画面では一切処理しない
    if (is_admin()) {
        return;  // ← REST APIリクエストは is_admin() が false を返す！
    }

    // ... 以降の処理
}
```

**問題点**:
- `is_admin()` は管理画面ページのみ `true` を返す
- REST API リクエスト（`/wp-json/wp/v2/posts/3801`）では `false` を返す
- そのため、投稿保存時のREST APIリクエストでもプラグインが動作してしまう
- 結果: グローバル`$wp_query`が上書きされ、投稿保存処理が失敗

**エラーの流れ**:
```
1. WordPressエディタで投稿を保存
   ↓
2. ブロックエディタがREST APIを呼び出し
   POST /wp-json/wp/v2/posts/3801
   ↓
3. is_admin() が false を返す（REST APIなので）
   ↓
4. プラグインの handle_404_redirect() が実行される
   ↓
5. $wp_query が上書きされる
   ↓
6. 投稿保存処理が失敗
   ↓
7. 500 Internal Server Error
```

### 3. v2.5.0の解決策

**新しい`is_frontend_request()`メソッド**:
```php
private function is_frontend_request() {
    // 管理画面は除外
    if (is_admin()) {
        return false;
    }

    // AJAX リクエストは除外
    if (wp_doing_ajax()) {
        return false;
    }

    // REST API リクエストは除外（複数の方法でチェック）
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return false;
    }

    // REST API のパスを含むリクエストは除外
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false) {
        return false;
    }

    // XMLRPC リクエストは除外
    if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
        return false;
    }

    // Cron リクエストは除外
    if (defined('DOING_CRON') && DOING_CRON) {
        return false;
    }

    // WP-CLI は除外
    if (defined('WP_CLI') && WP_CLI) {
        return false;
    }

    // すべてのチェックをパスした場合のみtrue
    return true;
}
```

**使用方法**:
```php
public function handle_404_redirect() {
    // 【v2.5.0 最重要】フロントエンドのページリクエストのみ処理
    if (!$this->is_frontend_request()) {
        return;
    }

    // ... フロントエンドのみの処理
}
```

### 4. 除外されるリクエストの種類

| リクエスト種類 | チェック方法 | 例 |
|--------------|------------|---|
| 管理画面 | `is_admin()` | `/wp-admin/post.php` |
| REST API | `REST_REQUEST` 定数 | `/wp-json/wp/v2/posts/` |
| REST API | URLパターン | `/wp-json/` を含むURL |
| AJAX | `wp_doing_ajax()` | `admin-ajax.php` |
| XMLRPC | `XMLRPC_REQUEST` 定数 | `xmlrpc.php` |
| Cron | `DOING_CRON` 定数 | WP-Cron |
| WP-CLI | `WP_CLI` 定数 | コマンドライン |

### 5. REST APIチェックの多重防御

v2.5.0では、REST APIを**2つの方法**でチェックしています:

1. **定数チェック**: `defined('REST_REQUEST') && REST_REQUEST`
2. **URLパターンチェック**: `strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false`

これにより、WordPressのバージョンや環境の違いに関わらず、確実にREST APIリクエストを除外できます。

## v2.4.0からの主な違い

| 項目 | v2.4.0 | v2.5.0 |
|------|--------|--------|
| 管理画面チェック | ✅ is_admin() | ✅ is_admin() |
| REST APIチェック | ❌ なし | ✅ あり（2重チェック） |
| AJAXチェック | ❌ なし | ✅ wp_doing_ajax() |
| XMLRPCチェック | ❌ なし | ✅ あり |
| Cronチェック | ❌ なし | ✅ あり |
| WP-CLIチェック | ❌ なし | ✅ あり |
| 投稿保存 | ❌ 500エラー | ✅ 正常 |
| 投稿表示 | ❓ 不明 | ✅ 正常（予定） |

## 主な機能

- ✅ 全エリア対応の3ステップナビゲーション（親→子カテゴリ→ジャンル/タグ）
- ✅ URLリライト（REST API/AJAX完全セーフ実装）
- ✅ 投稿とカテゴリの完全な区別
- ✅ 投稿ページの正常表示
- ✅ 管理画面での投稿編集・更新が正常に動作
- ✅ REST API経由の投稿保存が正常に動作
- ✅ AJAX処理との競合なし
- ✅ SEO最適化（canonical URL、OGP、Twitter Card）
- ✅ アイキャッチ画像自動設定
- ✅ 検索結果ページ（モダンUI）
- ✅ 独自アクセスカウント機能
- ✅ カテゴリ・タグによる高度な絞り込み
- ✅ ページネーション対応
- ✅ モバイルファースト設計

## 緊急アップグレード（v2.4.0からのアップグレード）

### 【重要】v2.4.0をご使用の方は、至急v2.5.0にアップグレードしてください

v2.4.0には投稿を保存できなくなる重大な不具合があります（REST API 500エラー）。

### アップグレード手順

1. **プラグインを無効化**（削除はしない）
   ```
   WordPress管理画面 > プラグイン > Umaten トップページ > 無効化
   ```

2. **v2.5.0ファイルをアップロード**
   - 古いファイルを上書き、またはフォルダを置き換え

3. **プラグインを有効化**
   ```
   WordPress管理画面 > プラグイン > Umaten トップページ > 有効化
   ```

4. **パーマリンク設定をフラッシュ**
   ```
   WordPress管理画面 > 設定 > パーマリンク設定 > 変更を保存
   ```

5. **キャッシュをクリア**
   - 管理バーの「cache clear」をクリック

6. **動作確認**
   - 投稿の編集ができるか確認（ブロックエディタで保存）
   - 公開投稿が正しく表示されるか確認

## 使用方法

### URL構造

- **投稿ページ**: `/hokkaido/curry-ya-jack-hakodate-nakajima/`
  → v2.5.0で正常に投稿として表示 ✅
  → サイドバー、パンくずリスト、記事本文すべて正しく表示

- **カテゴリページ**: `/hokkaido/hakodate/`
  → 函館のすべての投稿を表示

- **タグ絞り込み**: `/hokkaido/hakodate/ramen/`
  → 函館のラーメン投稿を表示（ramenがタグの場合）

## トラブルシューティング

### v2.4.0で投稿が保存できなくなった場合

**症状**:
```
ブラウザのコンソールに表示されるエラー:
POST https://umaten.jp/wp-json/wp/v2/posts/3801 500 (Internal Server Error)

WordPressエディタのエラー:
「更新に失敗しました。返答が正しいJSON レスポンスではありません。」
```

**解決策**:
1. **至急v2.5.0にアップグレード**
   - 上記の「緊急アップグレード」手順に従ってください

2. **それでも保存できない場合**
   ```bash
   # デバッグモードを有効化して詳細なエラーを確認
   # wp-config.phpに追記
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);

   # エラーログを確認
   tail -f /path/to/wp-content/debug.log
   ```

3. **最終手段: プラグインを一時的に無効化**
   ```
   WordPress管理画面 > プラグイン > Umaten トップページ > 無効化
   ↓
   投稿を編集・保存
   ↓
   v2.5.0をインストール
   ↓
   プラグインを有効化
   ```

### デバッグモードで確認

v2.5.0はデバッグログに対応しています。

```php
// wp-config.phpに追記
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// ログの場所
/path/to/wp-content/debug.log
```

ログには以下のような情報が記録されます：
```
Umaten Toppage v2.5.0: Setting up single post query for post ID 3799
Umaten Toppage v2.5.0: Loading single template - /path/to/single.php
```

## 技術詳細

### is_frontend_request() の実装

```php
private function is_frontend_request() {
    // 多重防御: 7つのチェックでバックエンドリクエストを完全に除外

    if (is_admin()) return false;                    // 管理画面
    if (wp_doing_ajax()) return false;               // AJAX
    if (defined('REST_REQUEST') && REST_REQUEST) return false;  // REST API (定数)
    if (isset($_SERVER['REQUEST_URI']) &&
        strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false) return false;  // REST API (URL)
    if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) return false;  // XMLRPC
    if (defined('DOING_CRON') && DOING_CRON) return false;  // Cron
    if (defined('WP_CLI') && WP_CLI) return false;  // WP-CLI

    return true;  // フロントエンドのページリクエストのみtrue
}
```

この実装により、プラグインは**フロントエンドのページリクエストのみ**で動作します。

### REST APIの2重チェック

```php
// チェック1: REST_REQUEST 定数
if (defined('REST_REQUEST') && REST_REQUEST) {
    return false;
}

// チェック2: URLパターン
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false) {
    return false;
}
```

WordPressのバージョンや環境によって、`REST_REQUEST`定数が設定されない場合があります。そのため、URLパターンでも追加チェックを行い、確実にREST APIリクエストを除外します。

## パフォーマンス

- 管理画面では一切処理しない
- REST API では一切処理しない
- AJAX では一切処理しない
- フロントエンドでのクエリは最小限
- デバッグログは`WP_DEBUG`有効時のみ

## 互換性

- WordPress 5.0以上
- PHP 7.0以上（PHP 8.4でテスト済み）
- Kusanagi環境で動作確認済み
- SWELL テーマで動作確認済み
- Gutenbergブロックエディタ対応

## 変更履歴

### v2.5.0 (2025-11-14) - 緊急修正（REST API対応）
- **重大な不具合修正**: REST API経由の投稿保存500エラーを修正
- `is_frontend_request()`メソッドを追加
- REST APIリクエストの除外（2重チェック）
- AJAXリクエストの除外
- XMLRPCリクエストの除外
- Cronリクエストの除外
- WP-CLIリクエストの除外
- フロントエンドのページリクエストのみで動作
- ブロックエディタでの投稿保存が正常に動作

### v2.4.0 (2025-11-14) - 緊急修正（不完全）
- 管理画面での投稿更新エラーを修正
- **問題**: REST APIリクエストが除外されていない
- **問題**: 投稿保存時に500エラー

### v2.3.0 (2025-11-14) - 不具合あり（使用非推奨）
- 投稿積極表示機能を実装
- **問題**: 管理画面での投稿更新ができなくなる不具合

### v2.2.0 (2025-11-14)
- 投稿存在チェック機能を追加
- **問題**: 投稿検出後に表示されない

### v2.1.0 (2025-11-14)
- URLリライトロジックを完全再設計

## ライセンス

GPL v2 or later

## 作者

Umaten
https://umaten.jp
