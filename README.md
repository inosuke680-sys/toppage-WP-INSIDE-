# WP Hero Image Manager

[![Version](https://img.shields.io/badge/version-2.8.4-blue.svg)](https://github.com/inosuke680-sys/toppage-WP-INSIDE-)
[![License](https://img.shields.io/badge/license-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)

WordPressの投稿にヒーロー画像を追加し、アイキャッチ画像との重複表示を防ぐプラグインです。

## 🎯 主な特徴

- ✅ **重複表示の完全防止** - 記事内でアイキャッチとヒーロー画像が重複しない
- ✅ **柔軟な表示制御** - カテゴリページではアイキャッチ、記事ページではヒーロー画像
- ✅ **直感的なUI** - WordPressメディアライブラリと完全統合
- ✅ **レスポンシブ対応** - モバイル、タブレット、デスクトップに最適化
- ✅ **開発者フレンドリー** - テンプレート用ヘルパー関数を提供
- ✅ **高い互換性** - WordPress 5.0以上、PHP 7.2以上に対応

## 📋 解決する問題

### バージョン 2.8.4 で修正された問題

**問題**: カテゴリページ等ではアイキャッチ画像が正常に表示されるが、記事内ではアイキャッチとヒーロー画像が重複して表示される。

**解決策**:
- シングルページでのアイキャッチ画像表示を制御するオプションを追加
- カテゴリ・アーカイブページでは正常にアイキャッチを表示
- ヒーロー画像使用時の重複を完全に防止

## 🚀 インストール方法

### 方法1: GitHubから直接インストール

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/inosuke680-sys/toppage-WP-INSIDE- wp-hero-image-manager
```

### 方法2: ZIPファイルでインストール

1. このリポジトリをZIPファイルとしてダウンロード
2. WordPress管理画面 → プラグイン → 新規追加 → プラグインのアップロード
3. ZIPファイルを選択してインストール
4. プラグインを有効化

### 方法3: 手動インストール

1. ファイルを `wp-content/plugins/wp-hero-image-manager/` にアップロード
2. WordPress管理画面でプラグインを有効化

## 📖 使い方

### 基本的な使用方法

1. **投稿編集画面を開く**
2. **サイドバーの「ヒーロー画像設定」を見つける**
3. **「ヒーロー画像を使用する」にチェック**
4. **「ヒーロー画像を選択」ボタンをクリック**
5. **メディアライブラリから画像を選択**
6. **「記事内でアイキャッチ画像を非表示にする（重複防止）」にチェック**
7. **投稿を保存・公開**

### 表示の仕組み

| ページタイプ | アイキャッチ画像 | ヒーロー画像 |
|------------|----------------|------------|
| カテゴリページ | ✅ 表示 | ❌ 非表示 |
| アーカイブページ | ✅ 表示 | ❌ 非表示 |
| 記事ページ（ヒーロー無効） | ✅ 表示 | ❌ 非表示 |
| 記事ページ（ヒーロー有効） | ❌ 非表示 | ✅ 表示 |

### テンプレートでの使用

```php
<?php
// ヒーロー画像が存在するかチェック
if ( has_hero_image() ) {
    // ヒーロー画像を表示
    the_hero_image();
}

// ヒーロー画像IDを取得
$hero_image_id = get_hero_image_id();
if ( $hero_image_id ) {
    // カスタムサイズで表示
    echo wp_get_attachment_image( $hero_image_id, 'large' );
}
?>
```

## 📁 ファイル構成

```
wp-hero-image-manager/
├── wp-hero-image-manager.php    # メインプラグインファイル
├── assets/
│   ├── css/
│   │   ├── admin.css            # 管理画面用CSS
│   │   └── frontend.css         # フロントエンド用CSS
│   └── js/
│       └── admin.js             # 管理画面用JavaScript
├── PLUGIN-README.md             # プラグイン詳細ドキュメント
└── README.md                    # このファイル
```

## 🔧 技術仕様

### システム要件

- **WordPress**: 5.0 以上
- **PHP**: 7.2 以上
- **MySQL**: 5.6 以上

### 使用技術

- WordPress Plugin API
- WordPress Media Library API
- jQuery
- CSS3 (レスポンシブデザイン)
- HTML5

### セキュリティ対策

- ✅ Nonce検証によるCSRF保護
- ✅ データサニタイゼーション
- ✅ エスケープ処理
- ✅ 権限チェック
- ✅ 直接アクセス防止

## 🎨 カスタマイズ

### CSSのカスタマイズ例

```css
/* テーマのstyle.cssに追加 */
.wp-hero-image-container {
    margin-bottom: 3em;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.wp-hero-image {
    border-radius: 8px;
    transition: transform 0.3s ease;
}

.wp-hero-image:hover {
    transform: scale(1.02);
}
```

### テーマでの統合例

```php
// single.php または content-single.php
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
    </header>

    <?php
    // ヒーロー画像がない場合のみアイキャッチを表示
    if ( ! has_hero_image() && has_post_thumbnail() ) {
        the_post_thumbnail( 'large' );
    }
    ?>

    <div class="entry-content">
        <?php the_content(); ?>
    </div>
</article>
```

## 📝 変更履歴

### v2.8.4 (2025-11-15)

- 🐛 **修正**: 記事内でアイキャッチ画像とヒーロー画像が重複する問題を解決
- ✨ **追加**: 「記事内でアイキャッチ画像を非表示にする」オプション
- 🚀 **改善**: シングルページとアーカイブページでの表示制御を最適化
- 🔒 **強化**: セキュリティとエラーハンドリングの改善
- 🎨 **最適化**: フロントエンドCSSの改良とレスポンシブ対応強化
- ✅ **互換性**: WordPress 6.x との完全互換性確認

## 🤝 サポート

問題が発生した場合や機能リクエストがある場合は、以下のリンクから報告してください:

- **GitHub Issues**: [https://github.com/inosuke680-sys/toppage-WP-INSIDE-/issues](https://github.com/inosuke680-sys/toppage-WP-INSIDE-/issues)

## 📄 ライセンス

このプラグインは [GPL v2 またはそれ以降](https://www.gnu.org/licenses/gpl-2.0.html) のライセンスの下で配布されています。

## 👤 作者

**inosuke680-sys**

- GitHub: [@inosuke680-sys](https://github.com/inosuke680-sys)

## 🙏 貢献

プルリクエストを歓迎します！大きな変更の場合は、まずissueを開いて変更内容を議論してください。

---

Made with ❤️ for WordPress