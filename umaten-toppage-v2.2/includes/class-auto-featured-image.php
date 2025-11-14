<?php
/**
 * アイキャッチ画像自動設定クラス
 */

// 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

class Umaten_Toppage_Auto_Featured_Image {

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
        // 投稿保存時にアイキャッチ画像を自動設定
        add_action('save_post', array($this, 'auto_set_featured_image'), 10, 2);

        // 既存の投稿にアイキャッチを設定するためのフック（オプション）
        add_action('admin_init', array($this, 'maybe_add_admin_notice'));
    }

    /**
     * 投稿保存時にアイキャッチ画像を自動設定
     */
    public function auto_set_featured_image($post_id, $post) {
        // 自動保存時はスキップ
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // 投稿タイプが post でない場合はスキップ
        if ($post->post_type !== 'post') {
            return;
        }

        // 既にアイキャッチ画像が設定されている場合はスキップ
        if (has_post_thumbnail($post_id)) {
            return;
        }

        // 本文から画像を抽出してアイキャッチに設定
        $this->extract_and_set_featured_image($post_id, $post->post_content);
    }

    /**
     * 本文から画像を抽出してアイキャッチに設定
     */
    private function extract_and_set_featured_image($post_id, $content) {
        // 優先順位1: restaurant-hero-imageクラスを持つ画像
        if (preg_match('/<img[^>]+class=["\'][^"\']*restaurant-hero-image[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
            $image_url = $matches[1];
            $this->set_featured_image_from_url($post_id, $image_url);
            return true;
        }

        // 優先順位2: ls-is-cached lazyloadedクラスを持つ画像
        if (preg_match('/<img[^>]+class=["\'][^"\']*ls-is-cached[^"\']*lazyloaded[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
            $image_url = $matches[1];
            $this->set_featured_image_from_url($post_id, $image_url);
            return true;
        }

        // 優先順位3: data-src属性を持つ画像（Lazy Load用）
        if (preg_match('/<img[^>]+data-src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
            $image_url = $matches[1];
            $this->set_featured_image_from_url($post_id, $image_url);
            return true;
        }

        // 優先順位4: 最初の通常の画像
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
            $image_url = $matches[1];
            $this->set_featured_image_from_url($post_id, $image_url);
            return true;
        }

        return false;
    }

    /**
     * URLから画像をアップロードしてアイキャッチに設定
     */
    private function set_featured_image_from_url($post_id, $image_url) {
        // 相対URLを絶対URLに変換
        if (strpos($image_url, 'http') !== 0) {
            $image_url = site_url($image_url);
        }

        // すでにメディアライブラリに存在するか確認
        $attachment_id = $this->get_attachment_id_from_url($image_url);

        if ($attachment_id) {
            // 既存の添付ファイルを使用
            set_post_thumbnail($post_id, $attachment_id);
            return $attachment_id;
        }

        // 外部URLの場合はダウンロードしてアップロード
        if (strpos($image_url, site_url()) !== 0) {
            $attachment_id = $this->upload_image_from_url($post_id, $image_url);
            if ($attachment_id) {
                set_post_thumbnail($post_id, $attachment_id);
                return $attachment_id;
            }
        } else {
            // サイト内の画像URLをメディアライブラリに追加
            $upload_dir = wp_upload_dir();
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $image_url);

            if (file_exists($file_path)) {
                $attachment_id = $this->create_attachment_from_file($file_path, $post_id);
                if ($attachment_id) {
                    set_post_thumbnail($post_id, $attachment_id);
                    return $attachment_id;
                }
            }
        }

        return false;
    }

    /**
     * URLから添付ファイルIDを取得
     */
    private function get_attachment_id_from_url($image_url) {
        global $wpdb;

        $attachment = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE guid='%s';",
            $image_url
        ));

        return !empty($attachment) ? $attachment[0] : null;
    }

    /**
     * URLから画像をダウンロードしてアップロード
     */
    private function upload_image_from_url($post_id, $image_url) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($image_url);

        if (is_wp_error($tmp)) {
            return false;
        }

        $file_array = array(
            'name' => basename($image_url),
            'tmp_name' => $tmp
        );

        $id = media_handle_sideload($file_array, $post_id);

        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }

        return $id;
    }

    /**
     * ファイルパスから添付ファイルを作成
     */
    private function create_attachment_from_file($file_path, $post_id) {
        $filetype = wp_check_filetype(basename($file_path), null);

        $attachment = array(
            'guid' => $file_path,
            'post_mime_type' => $filetype['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($file_path)),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);

        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
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
        if (isset($_GET['umaten_bulk_featured_image']) && $_GET['umaten_bulk_featured_image'] === 'done') {
            add_action('admin_notices', array($this, 'bulk_process_notice'));
        }
    }

    /**
     * 一括処理完了通知
     */
    public function bulk_process_notice() {
        $processed = isset($_GET['processed']) ? intval($_GET['processed']) : 0;
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . sprintf(__('%d件の投稿にアイキャッチ画像を設定しました。', 'umaten-toppage'), $processed) . '</p>';
        echo '</div>';
    }

    /**
     * 既存投稿に一括でアイキャッチ画像を設定（管理画面から実行）
     */
    public static function bulk_set_featured_images() {
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
                    'key' => '_thumbnail_id',
                    'compare' => 'NOT EXISTS'
                )
            )
        );

        $posts = get_posts($args);
        $processed = 0;

        $instance = self::get_instance();

        foreach ($posts as $post) {
            if ($instance->extract_and_set_featured_image($post->ID, $post->post_content)) {
                $processed++;
            }
        }

        // リダイレクト
        wp_redirect(add_query_arg(array(
            'umaten_bulk_featured_image' => 'done',
            'processed' => $processed
        ), admin_url('edit.php')));
        exit;
    }
}
