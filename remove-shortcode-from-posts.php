<?php
/**
 * 投稿本文から [umaten_toppage] ショートコードを削除するスクリプト
 *
 * 使用方法:
 * sudo -u kusanagi wp eval-file /tmp/remove-shortcode-from-posts.php --allow-root
 *
 * または:
 * sudo -u www-data wp eval-file /tmp/remove-shortcode-from-posts.php --allow-root
 */

// WordPressが読み込まれているか確認
if (!defined('ABSPATH')) {
    echo "エラー: このスクリプトはWP-CLI経由で実行してください。\n";
    exit(1);
}

echo "=== 投稿本文から [umaten_toppage] ショートコードを削除します ===\n\n";

// すべての公開投稿を取得
$args = array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'fields' => 'ids'
);

$post_ids = get_posts($args);
$total = count($post_ids);
$updated = 0;

echo "対象投稿数: {$total}件\n\n";

foreach ($post_ids as $post_id) {
    $post = get_post($post_id);
    $original_content = $post->post_content;

    // [umaten_toppage] ショートコードを削除
    $updated_content = preg_replace('/\[umaten_toppage\s*[^\]]*\]/', '', $original_content);

    // 内容が変更された場合のみ更新
    if ($original_content !== $updated_content) {
        $result = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $updated_content
        ), true);

        if (!is_wp_error($result)) {
            $updated++;
            echo "✓ 投稿ID {$post_id} ({$post->post_title}) からショートコードを削除しました\n";
        } else {
            echo "✗ 投稿ID {$post_id} の更新に失敗しました: " . $result->get_error_message() . "\n";
        }
    }
}

echo "\n=== 処理完了 ===\n";
echo "処理した投稿数: {$total}件\n";
echo "更新した投稿数: {$updated}件\n";

if ($updated > 0) {
    echo "\n成功: {$updated}件の投稿から [umaten_toppage] ショートコードを削除しました。\n";
} else {
    echo "\n[umaten_toppage] ショートコードを含む投稿は見つかりませんでした。\n";
}
