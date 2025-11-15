<?php
/**
 * ヒーロー画像メタデータ保存クラス (v2.7.0 シンプル版 - フィルターフック無効化)
 *
 * アイキャッチとして登録せず、メタデータとして保存することで、
 * 記事本文には影響を与えず、一覧ページでのみヒーロー画像を表示できるようにします。
 *
 * v2.7.0診断版：
 * - WordPressフィルターフックを完全に無効化（問題の原因を特定するため）
 * - シンプルなメタデータ保存のみに特化
 * - 公開API: Umaten_Toppage_Hero_Image::get_hero_image_url($post_id)
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
        // 投稿保存時にヒーロー画像URLをメタデータとして保存
        add_action('save_post', array($this, 'save_hero_image_metadata'), 10, 2);

        // 既存の投稿にヒーロー画像メタデータを設定するためのフック（オプション）
        add_action('admin_init', array($this, 'maybe_add_admin_notice'));

        // 【v2.7.0診断版】フィルターフックは完全に無効化
        // SWELLテーマとの互換性問題を回避するため
    }

    /**
     * 投稿保存時にヒーロー画像URLをメタデータとして保存
     */
    public function save_hero_image_metadata($post_id, $post) {
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

        // 既にヒーロー画像メタデータが設定されている場合はスキップ
        $existing_hero_image = get_post_meta($post_id, '_umaten_hero_image_url', true);
        if (!empty($existing_hero_image)) {
            return;
        }

        // 本文から画像を抽出してメタデータとして保存
        $this->extract_and_save_hero_image($post_id, $post->post_content);
    }

    /**
     * 本文から画像を抽出してメタデータとして保存
     */
    private function extract_and_save_hero_image($post_id, $content) {
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

        // 画像URLが見つかった場合、メタデータとして保存
        if ($image_url) {
            // 相対URLを絶対URLに変換
            if (strpos($image_url, 'http') !== 0) {
                $image_url = site_url($image_url);
            }

            // メタデータとして保存（アイキャッチとしては登録しない）
            update_post_meta($post_id, '_umaten_hero_image_url', esc_url($image_url));

            // デバッグログ
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Umaten Toppage v2.7.0 (診断版): Saved hero image URL for post {$post_id}: {$image_url}");
            }

            return true;
        }

        return false;
    }

    /**
     * 投稿のヒーロー画像URLを取得（公開API）
     *
     * @param int $post_id 投稿ID
     * @return string|false ヒーロー画像URLまたはfalse
     */
    public static function get_hero_image_url($post_id) {
        // まずメタデータから取得
        $hero_image_url = get_post_meta($post_id, '_umaten_hero_image_url', true);

        if (!empty($hero_image_url)) {
            return esc_url($hero_image_url);
        }

        // メタデータがない場合、アイキャッチ画像を取得（後方互換性）
        if (has_post_thumbnail($post_id)) {
            return get_the_post_thumbnail_url($post_id, 'large');
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
        echo '<p>' . sprintf(__('%d件の投稿にヒーロー画像メタデータを設定しました。', 'umaten-toppage'), $processed) . '</p>';
        echo '</div>';
    }

    /**
     * 既存投稿に一括でヒーロー画像メタデータを設定（管理画面から実行）
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
                array(
                    'key' => '_umaten_hero_image_url',
                    'compare' => 'NOT EXISTS'
                )
            )
        );

        $posts = get_posts($args);
        $processed = 0;

        $instance = self::get_instance();

        foreach ($posts as $post) {
            if ($instance->extract_and_save_hero_image($post->ID, $post->post_content)) {
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
