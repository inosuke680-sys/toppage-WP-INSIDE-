<?php
/**
 * ヒーロー画像一括設定スクリプト
 * 
 * 使用方法:
 * wp eval-file bulk-set-hero-images.php --allow-root
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
    require_once(ABSPATH . 'wp-load.php');
}

echo "=================================================================\n";
echo "  ヒーロー画像一括設定スクリプト v2.7.0\n";
echo "=================================================================\n\n";

// 統計情報を取得
$total_posts = wp_count_posts('post')->publish;
echo "公開済み投稿総数: {$total_posts}\n\n";

// ヒーロー画像未設定の投稿を取得
$args = array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_umaten_hero_image_url',
            'compare' => 'NOT EXISTS'
        )
    )
);

$posts = get_posts($args);
$total = count($posts);
echo "ヒーロー画像未設定の投稿: {$total}件\n\n";

if ($total == 0) {
    echo "すべての投稿にヒーロー画像が設定されています。\n";
    exit(0);
}

echo "処理を開始します...\n\n";

$processed = 0;
$success = 0;
$failed = 0;

foreach ($posts as $post) {
    $processed++;
    $image_url = null;
    $content = $post->post_content;

    // 優先順位1: restaurant-hero-imageクラスを持つ画像
    if (preg_match('/<img[^>]+class=["\'][^"\']*restaurant-hero-image[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
        $image_url = $matches[1];
    }
    // 優先順位2: ls-is-cached lazyloadedクラスを持つ画像
    elseif (preg_match('/<img[^>]+class=["\'][^"\']*ls-is-cached[^"\']*lazyloaded[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
        $image_url = $matches[1];
    }
    // 優先順位3: data-src属性を持つ画像
    elseif (preg_match('/<img[^>]+data-src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
        $image_url = $matches[1];
    }
    // 優先順位4: 最初の通常の画像
    elseif (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
        $image_url = $matches[1];
    }

    if ($image_url) {
        // 相対URLを絶対URLに変換
        if (strpos($image_url, 'http') !== 0) {
            $image_url = site_url($image_url);
        }

        update_post_meta($post->ID, '_umaten_hero_image_url', esc_url($image_url));
        $success++;
        
        echo sprintf("[%d/%d] ✓ ID:%d - %s\n", $processed, $total, $post->ID, mb_substr($post->post_title, 0, 40));
        echo sprintf("        画像: %s\n", substr($image_url, 0, 80) . (strlen($image_url) > 80 ? '...' : ''));
    } else {
        $failed++;
        echo sprintf("[%d/%d] ✗ ID:%d - %s (画像が見つかりませんでした)\n", $processed, $total, $post->ID, mb_substr($post->post_title, 0, 40));
    }

    // 10件ごとに進捗を表示
    if ($processed % 10 == 0) {
        echo "\n進捗: {$processed}/{$total} 件処理済み (成功: {$success}, 失敗: {$failed})\n\n";
    }
}

echo "\n=================================================================\n";
echo "  処理完了\n";
echo "=================================================================\n";
echo "処理件数: {$processed}件\n";
echo "成功: {$success}件\n";
echo "失敗: {$failed}件\n";
echo "\n";

// 最終統計を表示
$with_hero = get_posts(array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_umaten_hero_image_url',
            'compare' => 'EXISTS'
        )
    ),
    'fields' => 'ids'
));
$with_hero_count = count($with_hero);

echo "最終統計:\n";
echo "  公開済み投稿総数: {$total_posts}件\n";
echo "  ヒーロー画像設定済み: {$with_hero_count}件";
if ($total_posts > 0) {
    echo " (" . round($with_hero_count / $total_posts * 100, 1) . "%)";
}
echo "\n";
echo "  未設定: " . ($total_posts - $with_hero_count) . "件\n";
echo "\n";
echo "処理が完了しました。ブラウザとWordPressのキャッシュをクリアしてください。\n";
