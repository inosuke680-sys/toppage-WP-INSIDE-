# Umaten トップページ v2.5.0 デプロイガイド

## 📋 概要

v2.5.0は、v2.4.0で残っていた**REST API経由の投稿保存時の500エラー**を修正した緊急リリースです。

## 🐛 v2.4.0の問題

### 症状
```
ブラウザコンソールエラー:
POST https://umaten.jp/wp-json/wp/v2/posts/3801 500 (Internal Server Error)

WordPressエディタエラー:
「更新に失敗しました。返答が正しいJSON レスポンスではありません。」
```

### 原因

v2.4.0では `is_admin()` チェックのみでバックエンド処理を除外していましたが、これでは不十分でした:

```php
// v2.4.0のコード（問題あり）
public function handle_404_redirect() {
    if (is_admin()) {  // ← REST APIリクエストでは false を返す！
        return;
    }
    // ... 以降の処理が実行されてしまう
}
```

**問題点**:
- `is_admin()` は管理画面ページでのみ `true`
- REST APIリクエスト（`/wp-json/wp/v2/posts/`）では `false` を返す
- そのため、投稿保存時のREST APIでもプラグインが動作
- グローバル`$wp_query`が上書きされ、投稿保存処理が失敗

## ✅ v2.5.0の修正

### 新機能: `is_frontend_request()` メソッド

7つのチェックで完全な多重防御を実装:

```php
private function is_frontend_request() {
    if (is_admin()) return false;                    // 管理画面
    if (wp_doing_ajax()) return false;               // AJAX
    if (defined('REST_REQUEST') && REST_REQUEST) return false;  // REST API
    if (strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false) return false;  // REST API (URL)
    if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) return false;  // XMLRPC
    if (defined('DOING_CRON') && DOING_CRON) return false;  // Cron
    if (defined('WP_CLI') && WP_CLI) return false;  // WP-CLI

    return true;  // フロントエンドのページリクエストのみtrue
}
```

### REST APIの2重チェック

WordPress環境によって `REST_REQUEST` 定数が設定されない場合があるため、URLパターンでも追加チェック:

```php
// チェック1: 定数
if (defined('REST_REQUEST') && REST_REQUEST) {
    return false;
}

// チェック2: URLパターン
if (strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false) {
    return false;
}
```

## 📦 デプロイ手順

### 前提条件
- 本番サーバーへのSSHアクセス権限
- GitHubリポジトリへのアクセス権限
- sudoまたはroot権限

### ステップ1: 本番サーバーにSSH接続

```bash
ssh your-user@your-server.com
```

### ステップ2: デプロイスクリプトを実行

```bash
cd /path/to/deployment/scripts
sudo bash deploy-production.sh
```

デプロイスクリプトは以下を自動実行します:
1. GitHubから最新版をクローン（ブランチ: `claude/optimize-hokkaido-navigation-011CV5rKKYN42TG7uvzSEga4`）
2. 本番環境にファイルをコピー
3. パーミッション設定
4. PHP-FPM再起動
5. キャッシュクリア
6. バージョン確認

### ステップ3: WordPress管理画面でパーマリンクをフラッシュ

1. WordPress管理画面にログイン: `https://umaten.jp/wp-admin/`
2. 「設定」→「パーマリンク設定」を開く
3. **何も変更せずに**「変更を保存」をクリック

これにより、リライトルールがフラッシュされます。

### ステップ4: 動作確認

#### 1. 投稿保存のテスト
- WordPress管理画面で既存の投稿を開く
- ブロックエディタで何か変更を加える
- 「更新」ボタンをクリック
- ✅ 500エラーが出ずに保存できることを確認
- ❌ 以前は「更新に失敗しました」エラーが表示されていた

#### 2. ブラウザコンソールの確認
- F12を押して開発者ツールを開く
- 投稿を保存
- ✅ コンソールにエラーが表示されないことを確認
- ❌ 以前は `POST /wp-json/wp/v2/posts/3801 500` エラーが表示されていた

#### 3. 投稿ページの表示確認
- 公開投稿にアクセス: `https://umaten.jp/hokkaido/curry-ya-jack-hakodate-nakajima/`
- ✅ 記事本文が正しく表示される
- ✅ サイドバーが表示される
- ✅ パンくずリストが正しい
- ❌ 投稿リストカードとして表示されない

#### 4. カテゴリアーカイブの確認
- カテゴリページにアクセス: `https://umaten.jp/hokkaido/hakodate/`
- ✅ 函館の投稿一覧が表示される
- ✅ ページネーションが動作する

## 🔍 トラブルシューティング

### デプロイスクリプトでエラーが出る

#### エラー: Gitクローンに失敗
```bash
# ブランチ名を確認
git ls-remote --heads https://github.com/inosuke680-sys/toppage-WP-INSIDE-.git

# 手動でクローン
git clone -b claude/optimize-hokkaido-navigation-011CV5rKKYN42TG7uvzSEga4 \
  https://github.com/inosuke680-sys/toppage-WP-INSIDE-.git \
  /tmp/manual-clone
```

#### エラー: パーミッション設定に失敗
```bash
# 手動でパーミッション設定
sudo chown -R kusanagi:www /home/kusanagi/.../umaten-toppage
sudo find /home/kusanagi/.../umaten-toppage -type d -exec chmod 755 {} \;
sudo find /home/kusanagi/.../umaten-toppage -type f -exec chmod 644 {} \;
```

### 投稿保存が失敗する（v2.5.0デプロイ後）

#### 1. バージョン確認
```bash
head -10 /home/kusanagi/.../umaten-toppage/umaten-toppage.php | grep Version
# 出力: Version: 2.5.0
```

#### 2. PHP-FPM再起動
```bash
sudo systemctl restart php-fpm
```

#### 3. デバッグログを確認
```bash
# wp-config.phpでデバッグモードを有効化
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

# ログ確認
tail -f /path/to/wp-content/debug.log
```

期待されるログ:
```
Umaten Toppage v2.5.0: Setting up single post query for post ID 3799
Umaten Toppage v2.5.0: Loading single template - /path/to/single.php
```

### 投稿ページが表示されない

#### 1. キャッシュクリア
```bash
# Kusanagiサーバーの場合
sudo kusanagi cache clear

# または管理バーの「cache clear」をクリック
```

#### 2. パーマリンク再フラッシュ
- WordPress管理画面 > 設定 > パーマリンク設定 > 変更を保存

#### 3. プラグイン再有効化
- WordPress管理画面 > プラグイン > Umaten トップページ > 無効化
- もう一度有効化

## 📊 バージョン比較表

| 項目 | v2.4.0 | v2.5.0 |
|------|--------|--------|
| 管理画面チェック | ✅ | ✅ |
| REST APIチェック | ❌ | ✅ (2重チェック) |
| AJAXチェック | ❌ | ✅ |
| XMLRPCチェック | ❌ | ✅ |
| Cronチェック | ❌ | ✅ |
| WP-CLIチェック | ❌ | ✅ |
| 投稿保存 | ❌ 500エラー | ✅ 正常 |
| 投稿表示 | ❓ | ✅ 正常 |

## 🎯 期待される効果

### v2.5.0デプロイ後
- ✅ ブロックエディタで投稿を保存できる（500エラーなし）
- ✅ REST API経由の操作がすべて正常に動作
- ✅ AJAX処理との競合なし
- ✅ 管理画面の投稿編集が正常に動作
- ✅ フロントエンドの投稿ページが正常に表示
- ✅ カテゴリアーカイブが正常に表示

## 📝 変更履歴

### v2.5.0 (2025-11-14)
- **重大な不具合修正**: REST API経由の投稿保存500エラーを修正
- `is_frontend_request()`メソッドを追加
- REST APIリクエストの除外（2重チェック）
- AJAX/XMLRPC/Cron/WP-CLIリクエストの除外
- フロントエンドのページリクエストのみで動作

### v2.4.0 (2025-11-14)
- 管理画面での投稿更新エラーを修正
- **問題**: REST APIリクエストが除外されていない → v2.5.0で修正

### v2.3.0 (2025-11-14)
- 投稿積極表示機能を実装
- **問題**: 管理画面での投稿更新不可 → v2.4.0で修正

### v2.2.0 (2025-11-14)
- 投稿存在チェック機能を追加
- **問題**: 投稿が表示されない → v2.3.0で修正

## 🆘 サポート

デプロイ後に問題が発生した場合:

1. **エラーログを確認**
   ```bash
   tail -100 /var/log/php-fpm/error.log
   tail -100 /path/to/wp-content/debug.log
   ```

2. **ブラウザコンソールを確認**
   - F12を押して開発者ツールを開く
   - 「Console」タブでエラーを確認

3. **プラグインを一時的に無効化してテスト**
   - WordPress管理画面 > プラグイン > Umaten トップページ > 無効化
   - 投稿保存をテスト
   - 問題が解決すれば、プラグインが原因

4. **ロールバック（最終手段）**
   ```bash
   # v2.4.0に戻す
   cd /home/kusanagi/.../umaten-toppage/
   git checkout v2.4.0
   ```

---

**作成日**: 2025-11-14
**対象バージョン**: v2.5.0
**前提バージョン**: v2.4.0から のアップグレード
