# Umaten トップページプラグイン v2.3.0

WordPressのトップページ用プラグインです。動的なカテゴリ・タグ表示機能を備え、全エリア対応の3ステップナビゲーションで内部リンク最適化を実現しています。

**バージョン**: 2.3.0

## 🎉 v2.3.0の重要な改善点

### 1. 投稿積極表示機能の実装 ⭐⭐⭐
- **根本的な解決**: v2.2.0で残っていた「投稿を検出しても表示されない」問題を完全修正
- **新アプローチ**: 投稿を検出したら、積極的に投稿ページとして表示
- **実装内容**:
  - `setup_single_post_query()` メソッドを追加
  - グローバル`$wp_query`を投稿クエリで上書き
  - `is_single()`, `is_singular()`を正しく設定
  - `global $post`を設定し、`setup_postdata()`を呼び出し
  - `single.php`テンプレートを明示的にロード
  - HTTP 200ステータスを返す

### 2. v2.2.0の問題分析

**症状**:
```
下書きからプレビュー → 正常に表示される ✅
公開状態でアクセス → 投稿リストのカードが1件表示される ❌
```

**原因**:
```php
// v2.2.0のコード
if ($post) {
    if (has_category($parent_category->term_id, $post)) {
        // 正しい投稿URLなので何もしない
        return;  // ← ここが問題！
    }
}
```

- 投稿を検出することには成功
- しかし「何もしない」だけでは不十分
- WordPressはすでに404を返しているため、404ページが表示される
- その404ページがカスタマイズされていて、1件の投稿リストが表示されていた

### 3. v2.3.0の解決策

```php
// v2.3.0のコード
if ($post) {
    if (has_category($parent_category->term_id, $post)) {
        // 【v2.3.0 新機能】投稿として積極的に表示
        $this->setup_single_post_query($post);
        return;
    }
}

private function setup_single_post_query($post) {
    global $wp_query;

    // 投稿クエリを作成
    $args = array(
        'p' => $post->ID,
        'post_type' => 'post',
        'post_status' => 'publish'
    );

    // 新しいクエリで上書き
    $wp_query = new WP_Query($args);

    // 404状態を解除し、投稿ページとして設定
    $wp_query->is_404 = false;
    $wp_query->is_single = true;
    $wp_query->is_singular = true;
    $wp_query->is_archive = false;
    status_header(200);

    // グローバル変数を設定
    global $post;
    $post = get_post($args['p']);
    setup_postdata($post);

    // テンプレートをロード
    add_filter('template_include', array($this, 'load_single_template'));
}
```

### 4. 処理フローの改善

```
v2.2.0の失敗パターン：
/hokkaido/curry-ya-jack-hakodate-nakajima/
↓
WordPressがデフォルト処理 → 404
↓
プラグインが介入 → 投稿を検出
↓
カテゴリ所属確認 → OK
↓
「何もしない」→ return
↓
404ページが表示される（カスタマイズされた404ページに投稿リスト）

v2.3.0の成功パターン：
/hokkaido/curry-ya-jack-hakodate-nakajima/
↓
WordPressがデフォルト処理 → 404
↓
プラグインが介入 → 投稿を検出
↓
カテゴリ所属確認 → OK
↓
【新機能】setup_single_post_query()を呼び出し
  - $wp_queryを投稿クエリで上書き
  - is_404 = false, is_single = true
  - global $postを設定
  - setup_postdata()呼び出し
  - status_header(200)
  - single.phpテンプレートをロード
↓
投稿ページが正しく表示される ✅
```

## v2.2.0からの主な違い

| 項目 | v2.2.0 | v2.3.0 |
|------|--------|--------|
| 投稿存在チェック | ✅ あり | ✅ あり |
| カテゴリ所属確認 | ✅ あり | ✅ あり |
| 投稿検出後の処理 | ❌ return（何もしない） | ✅ setup_single_post_query() |
| $wp_query上書き | ❌ なし | ✅ あり |
| is_single設定 | ❌ なし | ✅ あり |
| setup_postdata() | ❌ なし | ✅ あり |
| テンプレートロード | ❌ なし | ✅ single.php |
| 投稿ページ表示 | ❌ 失敗 | ✅ 成功 |

## 主な機能

- ✅ 全エリア対応の3ステップナビゲーション（親→子カテゴリ→ジャンル/タグ）
- ✅ URLリライト（投稿積極表示機能実装）
- ✅ 投稿とカテゴリの完全な区別
- ✅ 投稿ページの正常表示
- ✅ SEO最適化（canonical URL、OGP、Twitter Card）
- ✅ アイキャッチ画像自動設定
- ✅ 検索結果ページ（モダンUI）
- ✅ 独自アクセスカウント機能
- ✅ カテゴリ・タグによる高度な絞り込み
- ✅ ページネーション対応
- ✅ モバイルファースト設計

## インストール

1. プラグインフォルダをWordPressの`wp-content/plugins/`にアップロード
2. WordPress管理画面でプラグインを有効化
3. **重要**: 「設定」→「パーマリンク設定」→「変更を保存」をクリック

## 使用方法

### URL構造

- **投稿ページ**: `/hokkaido/curry-ya-jack-hakodate-nakajima/`
  → v2.3.0で正常に投稿として表示 ✅
  → サイドバー、パンくずリスト、記事本文すべて正しく表示

- **カテゴリページ**: `/hokkaido/hakodate/`
  → 函館のすべての投稿を表示

- **タグ絞り込み**: `/hokkaido/hakodate/ramen/`
  → 函館のラーメン投稿を表示（ramenがタグの場合）
  → ラーメン店の投稿を表示（ramenが投稿スラッグの場合）

## v2.3.0で修正された問題

### 問題: 投稿ページが投稿リストカードとして表示される（v2.2.0）

**症状**:
```
URL: https://umaten.jp/hokkaido/curry-ya-jack-hakodate-nakajima/
↓
表示: 投稿リストのカード1件（クリックすると同じURLにリンク）
期待: 投稿本文が表示される

特徴:
- 下書きからプレビュー → 正常に表示される
- 公開状態でアクセス → 投稿リストカードになる
```

**原因**:
- v2.2.0は投稿を検出することには成功
- しかし「何もしない」だけでは、WordPressはすでに404を返している
- カスタマイズされた404ページに投稿リストが表示されていた
- プレビューURLは`/?p=3799&preview=true`形式で、これはWordPressが認識できる

**解決（v2.3.0）**:
- 投稿を検出したら、`setup_single_post_query()`で積極的に表示
- `$wp_query`を投稿クエリで上書き
- `is_single()`, `is_singular()`を正しく設定
- `global $post`を設定し、`setup_postdata()`呼び出し
- `single.php`テンプレートを明示的にロード
- 投稿ページが正しく表示される

## 技術詳細

### setup_single_post_query() メソッド

```php
private function setup_single_post_query($post) {
    global $wp_query;

    // 投稿クエリを作成
    $args = array(
        'p' => $post->ID,
        'post_type' => 'post',
        'post_status' => 'publish'
    );

    // 新しいクエリで上書き
    $wp_query = new WP_Query($args);

    // 404状態を解除し、投稿ページとして設定
    $wp_query->is_404 = false;
    $wp_query->is_single = true;
    $wp_query->is_singular = true;
    $wp_query->is_archive = false;
    status_header(200);

    // グローバル変数を設定
    global $post;
    $post = get_post($args['p']);
    setup_postdata($post);

    // カスタムテンプレート変数を設定
    set_query_var('umaten_is_single_post', true);
    set_query_var('umaten_post_id', $post->ID);

    // テンプレートをロード
    add_filter('template_include', array($this, 'load_single_template'));
}
```

### load_single_template() メソッド

```php
public function load_single_template($template) {
    if (!get_query_var('umaten_is_single_post')) {
        return $template;
    }

    // 投稿テンプレートとして扱う
    $single_template = locate_template(array('single.php', 'singular.php', 'index.php'));

    if ($single_template) {
        return $single_template;
    }

    return $template;
}
```

## アップグレード方法（v2.2.0 → v2.3.0）

1. WordPress管理画面で「Umaten トップページ」プラグインを無効化
2. 古いプラグインフォルダ（umaten-toppage）を削除またはバックアップ
3. 新しいv2.3.0フォルダをアップロード
4. プラグインを有効化
5. **必須**: 「設定」→「パーマリンク設定」→「変更を保存」をクリック
6. キャッシュをクリア（Kusanagiの場合: cache clear）
7. **動作確認**: `/hokkaido/curry-ya-jack-hakodate-nakajima/`にアクセス
   - ✅ 記事本文が表示される
   - ✅ サイドバーが表示される
   - ✅ パンくずリストが正しい
   - ❌ 投稿リストカードにならない

## トラブルシューティング

### 投稿ページが404になる、または投稿リストになる

**確認事項**:
1. v2.3.0が正しくインストールされているか
   ```bash
   head -10 /path/to/plugins/umaten-toppage/umaten-toppage.php | grep "Version:"
   # Version: 2.3.0 と表示されるはず
   ```

2. パーマリンク設定がフラッシュされているか
   ```
   WordPress管理画面 > 設定 > パーマリンク設定 > 変更を保存
   ```

3. キャッシュがクリアされているか
   ```bash
   # Kusanagiサーバーで
   kusanagi cache clear

   # または管理バーの「cache clear」をクリック
   ```

4. 投稿が「公開」状態か
   ```
   WordPress管理画面 > 投稿 > 該当投稿 > ステータス確認
   ```

### エラーログの確認

```bash
# エラーログの場所（環境により異なる）
/var/log/php-fpm/error.log
/var/log/httpd/error_log

# ログの確認
tail -f /var/log/php-fpm/error.log | grep "Umaten Toppage"
```

### デバッグモードで確認

```php
// wp-config.phpに追記
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// ログの場所
/path/to/wp-content/debug.log
```

## パフォーマンス

- データベースクエリは最小限（投稿検索時のみ）
- カテゴリアーカイブページでは従来通り高速
- 投稿ページでは$wp_queryの上書きが1回のみ
- テンプレートロードは標準的なWordPressの仕組みを使用

## 互換性

- WordPress 5.0以上
- PHP 7.0以上
- Kusanagi環境で動作確認済み
- SWELL テーマで動作確認済み

## 変更履歴

### v2.3.0 (2025-11-14)
- **重要**: 投稿積極表示機能を実装
- `setup_single_post_query()`メソッドを追加
- `load_single_template()`メソッドを追加
- 投稿検出後、$wp_queryを投稿クエリで上書き
- is_single、is_singularを正しく設定
- global $postを設定し、setup_postdata()呼び出し
- single.phpテンプレートを明示的にロード
- 投稿ページの表示問題を完全修正

### v2.2.0 (2025-11-14)
- 投稿存在チェック機能を追加
- カテゴリ所属確認機能を追加
- find_post_by_slug()メソッドを実装
- （問題あり：投稿検出後に何もしない）

### v2.1.0 (2025-11-14)
- URLリライトロジックを完全再設計
- リライトルールを廃止し、404ベース処理に変更
- （問題あり：投稿存在チェックがない）

### v2.0.0 (2025-11-13)
- URLリライト優先度を調整
- SEOメタタグを強化
- （問題あり：投稿URLの認識が不安定）

### v1.6.0 (2025-11-12)
- 初期リリース

## ライセンス

GPL v2 or later

## 作者

Umaten
https://umaten.jp
