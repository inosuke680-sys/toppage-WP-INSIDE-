<?php
/**
 * ヒーロー画像自動設定クラス (v2.9.0 完全版)
 *
 * 本文からヒーロー画像を抽出し、WordPressのアイキャッチ画像として自動設定。
 * 一覧ページでは表示、記事ページでは非表示（重複回避）。
 *
 * v2.9.0の改善:
 * - 画像URLからメディアライブラリのattachment IDを取得
 * - set_post_thumbnail()でアイキャッチとして設定
 * - is_single()の時はアイキャッチを非表示にするフィルター追加
 * - SWELLテーマと完全に互換性あり
 * - メタデータとアイキャッチの両方をサポート（後方互換性）
 */

// 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

class Umaten_Toppage_Hero_Image {

    /**
     * シングルトンインスタンス
     */
    private static $instance = null;

    /**
     * シングルトンインスタンスを取得
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * コンストラクタ
     */
    private function __construct() {
        // 投稿保存時にヒーロー画像をアイキャッチとして設定
        add_action('save_post', array($this, 'save_hero_image_as_thumbnail'), 10, 2);

        // 【v2.9.0重要】記事ページではアイキャッチを非表示
        add_filter('post_thumbnail_html', array($this, 'hide_thumbnail_on_single'), 10, 5);

        // 管理画面通知
        add_action('admin_init', array($this, 'maybe_add_admin_notice'));
    }

    /**
     * 【v2.9.0新機能】記事ページ（single.php）ではアイキャッチを非表示
     */
    public function hide_thumbnail_on_single($html, $post_id, $post_thumbnail_id, $size, $attr) {
        // 記事ページの場合は空文字を返す（アイキャッチを表示しない）
        if (is_single() || is_singular('post')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Umaten Toppage v2.9.0: Hiding thumbnail on single post (ID: {$post_id})");
            }
            return '';
        }

        // 一覧ページ・アーカイブページでは通常通り表示
        return $html;
    }

    /**
     * 投稿保存時にヒーロー画像をアイキャッチとして設定
     */
    public function save_hero_image_as_thumbnail($post_id, $post) {
        // 自動保存時はスキップ
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // 投稿タイプが post でない場合はスキップ
        if ($post->post_type !== 'post') {
            return;
        }

        // リビジョンの場合はスキップ
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // 既にアイキャッチが設定されている場合はスキップ
        if (has_post_thumbnail($post_id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Umaten Toppage v2.9.0: Post {$post_id} already has thumbnail, skipping");
            }
            return;
        }

        // 本文から画像を抽出してアイキャッチとして設定
        $this->extract_and_set_thumbnail($post_id, $post->post_content);
    }

    /**
     * 【v2.9.0新機能】本文から画像を抽出してアイキャッチとして設定
     */
    private function extract_and_set_thumbnail($post_id, $content) {
        $image_url = null;

        // 優先順位1: restaurant-hero-imageクラスを持つ画像
        if (preg_match('/<img[^>]+class=["\'][^"\']*restaurant-hero-image[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
            $image_url = $matches[1];
        }
        // 優先順位2: ls-is-cached lazyloadedクラスを持つ画像
        elseif (preg_match('/<img[^>]+class=["\'][^"\']*ls-is-cached[^"\']*lazyloaded[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
            $image_url = $matches[1];
        }
        // 優先順位3: data-src属性を持つ画像（Lazy Load用）
        elseif (preg_match('/<img[^>]+data-src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
            $image_url = $matches[1];
        }
        // 優先順位4: 最初の通常の画像
        elseif (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
            $image_url = $matches[1];
        }

        // 画像URLが見つからない場合は終了
        if (!$image_url) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Umaten Toppage v2.9.0: No hero image found in post {$post_id}");
            }
            return false;
        }

        // 相対URLを絶対URLに変換
        if (strpos($image_url, 'http') !== 0) {
            $image_url = site_url($image_url);
        }

        // デバッグログ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Umaten Toppage v2.9.0: Found hero image URL for post {$post_id}: {$image_url}");
        }

        // 【v2.9.0重要】画像URLからattachment IDを取得
        $attachment_id = $this->get_attachment_id_from_url($image_url);

        if ($attachment_id) {
            // アイキャッチとして設定
            set_post_thumbnail($post_id, $attachment_id);

            // メタデータにも保存（後方互換性）
            update_post_meta($post_id, '_umaten_hero_image_url', esc_url($image_url));

            // デバッグログ
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Umaten Toppage v2.9.0: Set thumbnail for post {$post_id} - Attachment ID: {$attachment_id}, URL: {$image_url}");
            }

            return true;
        } else {
            // メディアライブラリに画像がない場合は、メタデータのみ保存
            update_post_meta($post_id, '_umaten_hero_image_url', esc_url($image_url));

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Umaten Toppage v2.9.0: Could not find attachment ID for URL: {$image_url} - Saved as metadata only");
            }

            return false;
        }
    }

    /**
     * 【v2.9.0新機能】画像URLからメディアライブラリのattachment IDを取得
     *
     * @param string $url 画像URL
     * @return int|false attachment IDまたはfalse
     */
    private function get_attachment_id_from_url($url) {
        global $wpdb;

        // URLをクリーンアップ
        $url = esc_url($url);

        // まず、attachment_url_to_postid()を試す（WordPress標準関数）
        $attachment_id = attachment_url_to_postid($url);

        if ($attachment_id) {
            return $attachment_id;
        }

        // 標準関数で見つからない場合、データベースで直接検索
        // URLのファイル名部分を取得
        $file = basename($url);

        // クエリでattachment IDを検索
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_wp_attached_file'
            AND meta_value LIKE %s
            LIMIT 1",
            '%' . $wpdb->esc_like($file)
        ));

        if ($attachment_id) {
            return intval($attachment_id);
        }

        // それでも見つからない場合、guid列で検索
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
            AND guid LIKE %s
            LIMIT 1",
            '%' . $wpdb->esc_like($file)
        ));

        if ($attachment_id) {
            return intval($attachment_id);
        }

        // 見つからない場合はfalse
        return false;
    }

    /**
     * 投稿のヒーロー画像URLを取得（公開API - 後方互換性）
     *
     * @param int $post_id 投稿ID
     * @return string|false ヒーロー画像URLまたはfalse
     */
    public static function get_hero_image_url($post_id) {
        // まずアイキャッチ画像を取得（v2.9.0の標準）
        if (has_post_thumbnail($post_id)) {
            return get_the_post_thumbnail_url($post_id, 'large');
        }

        // アイキャッチがない場合、メタデータから取得（v2.6.0/v2.7.0/v2.8.0互換）
        $hero_image_url = get_post_meta($post_id, '_umaten_hero_image_url', true);

        if (!empty($hero_image_url)) {
            return esc_url($hero_image_url);
        }

        return false;
    }

    /**
     * 管理画面に通知を表示（既存投稿の一括処理用）
     */
    public function maybe_add_admin_notice() {
        // 管理者のみ
        if (!current_user_can('manage_options')) {
            return;
        }

        // 一括処理が実行されたかチェック
        if (isset($_GET['umaten_bulk_hero_image']) && $_GET['umaten_bulk_hero_image'] === 'done') {
            add_action('admin_notices', array($this, 'bulk_process_notice'));
        }
    }

    /**
     * 一括処理完了通知
     */
    public function bulk_process_notice() {
        $processed = isset($_GET['processed']) ? intval($_GET['processed']) : 0;
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . sprintf(__('%d件の投稿にヒーロー画像をアイキャッチとして設定しました。', 'umaten-toppage'), $processed) . '</p>';
        echo '</div>';
    }

    /**
     * 既存投稿に一括でヒーロー画像をアイキャッチとして設定（管理画面から実行）
     */
    public static function bulk_set_hero_images() {
        // 管理者のみ実行可能
        if (!current_user_can('manage_options')) {
            return;
        }

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
        $processed = 0;

        $instance = self::get_instance();

        foreach ($posts as $post) {
            if ($instance->extract_and_set_thumbnail($post->ID, $post->post_content)) {
                $processed++;
            }
        }

        // リダイレクト
        wp_redirect(add_query_arg(array(
            'umaten_bulk_hero_image' => 'done',
            'processed' => $processed
        ), admin_url('edit.php')));
        exit;
    }
}
