<?php
/**
 * Umaten ヒーロー画像一括設定スクリプト v2.8.3
 *
 * 使用方法:
 * 1. このファイルをWordPressルートディレクトリにアップロード
 * 2. SSH経由で実行: php bulk-set-hero-images-v2.8.3.php
 *
 * v2.8.3の変更点:
 * - ヒーロー画像をWordPressアイキャッチ（_thumbnail_id）として設定
 * - メタデータ（_umaten_hero_image_url）も保存（バックアップ）
 */

// WordPressを読み込み
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/wp-load.php');

echo "\n";
echo "========================================\n";
echo "Umaten ヒーロー画像一括設定 v2.8.3\n";
echo "========================================\n\n";

// 管理者権限チェック
if (!function_exists('wp_get_current_user')) {
    echo "エラー: WordPressが正しく読み込まれていません\n";
    exit(1);
}

// アイキャッチ未設定の投稿を取得
$args = array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => '_thumbnail_id',
            'compare' => 'NOT EXISTS'
        ),
        array(
            'key' => '_thumbnail_id',
            'value' => '',
            'compare' => '='
        )
    )
);

$posts = get_posts($args);
$total = count($posts);

echo "処理対象: {$total}件の投稿\n\n";

if ($total == 0) {
    echo "アイキャッチ未設定の投稿はありません。\n";
    exit(0);
}

// 確認
echo "続行しますか？ (y/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim($line) != 'y' && trim($line) != 'Y') {
    echo "キャンセルしました。\n";
    exit(0);
}
fclose($handle);

echo "\n処理を開始します...\n\n";

$processed = 0;
$success = 0;
$failed = 0;

foreach ($posts as $post) {
    $processed++;
    echo "[{$processed}/{$total}] 投稿ID {$post->ID}: {$post->post_title}\n";

    // 本文から画像URLを抽出
    $image_url = extract_hero_image_url($post->post_content);

    if (!$image_url) {
        echo "  ✗ 本文に画像が見つかりません\n";
        $failed++;
        continue;
    }

    echo "  画像URL: {$image_url}\n";

    // attachment IDを取得
    $attachment_id = attachment_url_to_postid($image_url);

    if ($attachment_id) {
        // アイキャッチとして設定
        set_post_thumbnail($post->ID, $attachment_id);
        echo "  ✓ アイキャッチ画像を設定しました (Attachment ID: {$attachment_id})\n";
    } else {
        echo "  ⚠ メディアライブラリに画像が見つかりません（メタデータのみ保存）\n";
    }

    // メタデータとして保存（バックアップ）
    update_post_meta($post->ID, '_umaten_hero_image_url', esc_url($image_url));
    echo "  ✓ ヒーロー画像URLをメタデータに保存しました\n";
    $success++;
}

echo "\n========================================\n";
echo "処理完了\n";
echo "========================================\n";
echo "処理件数: {$processed}件\n";
echo "成功: {$success}件\n";
echo "失敗: {$failed}件\n\n";

/**
 * 本文から画像URLを抽出
 */
function extract_hero_image_url($content) {
    // 優先順位1: restaurant-hero-imageクラスを持つ画像
    if (preg_match('/<img[^>]+class=["\'][^"\']*restaurant-hero-image[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
        return $matches[1];
    }

    // 優先順位2: ls-is-cached lazyloadedクラスを持つ画像
    if (preg_match('/<img[^>]+class=["\'][^"\']*ls-is-cached[^"\']*lazyloaded[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
        return $matches[1];
    }

    // 優先順位3: data-src属性を持つ画像（Lazy Load用）
    if (preg_match('/<img[^>]+data-src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
        return $matches[1];
    }

    // 優先順位4: 最初の通常の画像
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
        return $matches[1];
    }

    return null;
}
