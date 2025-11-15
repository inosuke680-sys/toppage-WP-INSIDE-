<?php
/**
 * Umaten プラグイン問題診断スクリプト
 *
 * 使用方法:
 * 1. このファイルをWordPressルートディレクトリにアップロード
 * 2. SSH経由で実行: php debug-umaten-issues.php
 */

// WordPressを読み込み
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/wp-load.php');

echo "\n";
echo "========================================\n";
echo "Umaten プラグイン問題診断スクリプト\n";
echo "========================================\n\n";

// 問題1: ヒーロー画像の調査
echo "【問題1】ヒーロー画像が表示されない問題の診断\n";
echo "----------------------------------------\n\n";

// 最新の投稿を5件取得
$posts = get_posts(array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
));

foreach ($posts as $post) {
    echo "投稿ID: {$post->ID}\n";
    echo "タイトル: {$post->post_title}\n";
    echo "スラッグ: {$post->post_name}\n";

    // アイキャッチ画像の確認
    $thumbnail_id = get_post_thumbnail_id($post->ID);
    echo "アイキャッチID (_thumbnail_id): " . ($thumbnail_id ? $thumbnail_id : '未設定') . "\n";

    if ($thumbnail_id) {
        $thumbnail_url = wp_get_attachment_url($thumbnail_id);
        echo "アイキャッチURL: {$thumbnail_url}\n";
    }

    // メタデータの確認
    $hero_image_url = get_post_meta($post->ID, '_umaten_hero_image_url', true);
    echo "ヒーロー画像URL (_umaten_hero_image_url): " . ($hero_image_url ? $hero_image_url : '未設定') . "\n";

    // 本文から画像を抽出
    $content = $post->post_content;
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
        $first_image_url = $matches[1];
        echo "本文の最初の画像URL: {$first_image_url}\n";

        // この画像がメディアライブラリに存在するか確認
        $attachment_id = attachment_url_to_postid($first_image_url);
        echo "メディアライブラリ検索結果 (attachment_url_to_postid): " . ($attachment_id ? "ID {$attachment_id}" : '見つからない') . "\n";

        // データベースで直接検索
        global $wpdb;
        $file = basename($first_image_url);
        $db_attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_wp_attached_file'
            AND meta_value LIKE %s
            LIMIT 1",
            '%' . $wpdb->esc_like($file)
        ));
        echo "データベース検索結果 (_wp_attached_file): " . ($db_attachment_id ? "ID {$db_attachment_id}" : '見つからない') . "\n";

    } else {
        echo "本文に画像が見つかりません\n";
    }

    echo "\n";
}

// 問題2: カテゴリとタグの調査
echo "\n【問題2】「すべてのジャンル」リダイレクト問題の診断\n";
echo "----------------------------------------\n\n";

// 主要なカテゴリを確認
$parent_categories = get_categories(array(
    'parent' => 0,
    'hide_empty' => false
));

echo "親カテゴリ一覧:\n";
foreach ($parent_categories as $parent_cat) {
    echo "  - {$parent_cat->name} (slug: {$parent_cat->slug}, ID: {$parent_cat->term_id})\n";

    // 子カテゴリを取得
    $child_categories = get_categories(array(
        'parent' => $parent_cat->term_id,
        'hide_empty' => false
    ));

    foreach ($child_categories as $child_cat) {
        echo "    - {$child_cat->name} (slug: {$child_cat->slug}, ID: {$child_cat->term_id})\n";

        // このカテゴリのURLをテスト
        $test_url = "/{$parent_cat->slug}/{$child_cat->slug}/";
        echo "      テストURL: {$test_url}\n";

        // get_term_by()でカテゴリを取得できるか確認
        $parent_term = get_term_by('slug', $parent_cat->slug, 'category');
        $child_term = get_term_by('slug', $child_cat->slug, 'category');

        echo "      get_term_by()結果: 親=" . ($parent_term ? 'OK' : 'NG') . ", 子=" . ($child_term ? 'OK' : 'NG') . "\n";

        // タグを取得
        $tags = get_tags(array(
            'hide_empty' => false,
            'number' => 3
        ));

        echo "      関連タグ（サンプル）:\n";
        foreach (array_slice($tags, 0, 3) as $tag) {
            echo "        - {$tag->name} (slug: {$tag->slug}, ID: {$tag->term_id})\n";
            $tag_url = "/{$parent_cat->slug}/{$child_cat->slug}/{$tag->slug}/";
            echo "          タグURL: {$tag_url}\n";
        }
    }
    echo "\n";
}

// プラグインの有効化状態を確認
echo "\n【プラグイン情報】\n";
echo "----------------------------------------\n";
$active_plugins = get_option('active_plugins');
$umaten_plugins = array_filter($active_plugins, function($plugin) {
    return strpos($plugin, 'umaten-toppage') !== false;
});

if (empty($umaten_plugins)) {
    echo "⚠️ Umaten トップページプラグインが有効化されていません！\n";
} else {
    foreach ($umaten_plugins as $plugin) {
        echo "✓ 有効化されているプラグイン: {$plugin}\n";
    }
}

// テーマ情報
echo "\n【テーマ情報】\n";
echo "----------------------------------------\n";
$theme = wp_get_theme();
echo "テーマ名: {$theme->get('Name')}\n";
echo "テーマバージョン: {$theme->get('Version')}\n";

// パーマリンク設定
echo "\n【パーマリンク設定】\n";
echo "----------------------------------------\n";
$permalink_structure = get_option('permalink_structure');
echo "パーマリンク構造: " . ($permalink_structure ? $permalink_structure : 'デフォルト') . "\n";

// wp-config.phpのデバッグ設定
echo "\n【デバッグ設定】\n";
echo "----------------------------------------\n";
echo "WP_DEBUG: " . (defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false') . "\n";
echo "WP_DEBUG_LOG: " . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'true' : 'false') . "\n";
echo "WP_DEBUG_DISPLAY: " . (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'true' : 'false') . "\n";

echo "\n========================================\n";
echo "診断完了\n";
echo "========================================\n\n";
