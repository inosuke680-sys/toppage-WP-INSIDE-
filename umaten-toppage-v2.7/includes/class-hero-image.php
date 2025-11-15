<?php
/**
 * ヒーロー画像メタデータ保存クラス (v2.7.0 緊急修正版 - 無限ループ解消)
 *
 * アイキャッチとして登録せず、メタデータとして保存することで、
 * 記事本文には影響を与えず、一覧ページでのみヒーロー画像を表示できるようにします。
 *
 * v2.7.0緊急修正：
 * - 無限ループの完全解消（get_post_metaの直接呼び出しを排除）
 * - データベース直接クエリによる安全なメタデータ取得
 * - 記事ページではアイキャッチを表示しない（本文のヒーロー画像と重複防止）
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
     * 疑似サムネイルIDのプレフィックス
     */
    const PSEUDO_THUMBNAIL_PREFIX = 'umaten_hero_';

    /**
     * 再帰防止フラグ
     */
    private $processing_thumbnail_id = false;

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

        // 【v2.7.0修正版】WordPressフィルターフックを使用してSWELLテーマと互換性を実現
        // wpアクションの後に初期化（クエリ関数が利用可能になってから）
        add_action('wp', array($this, 'init_theme_compatibility_hooks'), 1);
    }

    /**
     * 【v2.7.0修正版】テーマ互換性フックの初期化
     */
    public function init_theme_compatibility_hooks() {
        // post_thumbnail_htmlフィルター - サムネイルHTMLを生成
        add_filter('post_thumbnail_html', array($this, 'filter_post_thumbnail_html'), 10, 5);

        // get_post_metadataフィルター - _thumbnail_idを疑似的に返す
        add_filter('get_post_metadata', array($this, 'filter_thumbnail_id'), 10, 4);

        // wp_get_attachment_image_srcフィルター - ヒーロー画像URLを返す
        add_filter('wp_get_attachment_image_src', array($this, 'filter_attachment_image_src'), 10, 4);

        // wp_get_attachment_image_attributesフィルター - 画像属性を設定
        add_filter('wp_get_attachment_image_attributes', array($this, 'filter_attachment_image_attributes'), 10, 3);
    }

    /**
     * 【v2.7.0修正版】一覧ページかどうかを判定（安全版）
     * 記事ページ（single.php）ではアイキャッチを表示しない
     */
    private function should_show_thumbnail() {
        // グローバルクエリが準備されていない場合は安全にfalseを返す
        global $wp_query;
        if (!isset($wp_query) || !is_object($wp_query)) {
            return false;
        }

        // 記事ページでは表示しない（本文にヒーロー画像があるため重複防止）
        if (is_single() || is_singular('post')) {
            return false;
        }

        // 管理画面では表示しない
        if (is_admin()) {
            return false;
        }

        // 一覧ページ、アーカイブページ、検索結果ページでは表示
        return true;
    }

    /**
     * 【v2.7.0修正版】データベースから直接メタデータを取得（無限ループ防止）
     */
    private function get_meta_directly($post_id, $meta_key) {
        global $wpdb;

        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
            $post_id,
            $meta_key
        ));

        return $value;
    }

    /**
     * 【v2.7.0新機能】post_thumbnail_htmlフィルター - サムネイルHTMLを生成
     */
    public function filter_post_thumbnail_html($html, $post_id, $post_thumbnail_id, $size, $attr) {
        // 【v2.7.0改善】記事ページでは無効化
        if (!$this->should_show_thumbnail()) {
            return $html;
        }

        // 既にHTMLがある場合（実際のアイキャッチ画像がある場合）はそのまま返す
        if (!empty($html)) {
            return $html;
        }

        // ヒーロー画像URLを直接取得（無限ループ防止）
        $hero_image_url = $this->get_meta_directly($post_id, '_umaten_hero_image_url');

        if (empty($hero_image_url)) {
            return $html;
        }

        // デバッグログ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Umaten Toppage v2.7.0 (修正版): Generating thumbnail HTML for post {$post_id} from hero image URL: {$hero_image_url}");
        }

        // サムネイルHTMLを生成
        $attr = wp_parse_args($attr, array(
            'alt' => get_the_title($post_id),
            'class' => 'attachment-' . $size . ' size-' . $size . ' wp-post-image',
        ));

        $attr_string = '';
        foreach ($attr as $name => $value) {
            $attr_string .= sprintf(' %s="%s"', $name, esc_attr($value));
        }

        $html = sprintf(
            '<img src="%s"%s>',
            esc_url($hero_image_url),
            $attr_string
        );

        return $html;
    }

    /**
     * 【v2.7.0修正版】get_post_metadataフィルター - _thumbnail_idを疑似的に返す（無限ループ防止）
     */
    public function filter_thumbnail_id($value, $object_id, $meta_key, $single) {
        // _thumbnail_id以外は処理しない
        if ($meta_key !== '_thumbnail_id') {
            return $value;
        }

        // 再帰防止
        if ($this->processing_thumbnail_id) {
            return $value;
        }

        // 【v2.7.0改善】記事ページでは無効化
        if (!$this->should_show_thumbnail()) {
            return $value;
        }

        // 再帰防止フラグをオン
        $this->processing_thumbnail_id = true;

        // 既にサムネイルIDがある場合はそのまま返す（データベース直接クエリ）
        $existing_thumbnail_id = $this->get_meta_directly($object_id, '_thumbnail_id');
        if (!empty($existing_thumbnail_id)) {
            $this->processing_thumbnail_id = false;
            return $value;
        }

        // ヒーロー画像URLを取得（データベース直接クエリ）
        $hero_image_url = $this->get_meta_directly($object_id, '_umaten_hero_image_url');

        // 再帰防止フラグをオフ
        $this->processing_thumbnail_id = false;

        if (empty($hero_image_url)) {
            return $value;
        }

        // 疑似サムネイルIDを返す（文字列形式）
        $pseudo_id = self::PSEUDO_THUMBNAIL_PREFIX . $object_id;

        // デバッグログ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Umaten Toppage v2.7.0 (修正版): Returning pseudo thumbnail ID '{$pseudo_id}' for post {$object_id}");
        }

        // singleがtrueの場合は文字列、falseの場合は配列で返す
        return $single ? $pseudo_id : array($pseudo_id);
    }

    /**
     * 【v2.7.0新機能】wp_get_attachment_image_srcフィルター - ヒーロー画像URLを返す
     */
    public function filter_attachment_image_src($image, $attachment_id, $size, $icon) {
        // 疑似サムネイルIDでない場合は処理しない
        if (!is_string($attachment_id) || strpos($attachment_id, self::PSEUDO_THUMBNAIL_PREFIX) !== 0) {
            return $image;
        }

        // 【v2.7.0改善】記事ページでは無効化
        if (!$this->should_show_thumbnail()) {
            return $image;
        }

        // 投稿IDを抽出
        $post_id = str_replace(self::PSEUDO_THUMBNAIL_PREFIX, '', $attachment_id);

        // ヒーロー画像URLを取得（データベース直接クエリ）
        $hero_image_url = $this->get_meta_directly($post_id, '_umaten_hero_image_url');

        if (empty($hero_image_url)) {
            return $image;
        }

        // デバッグログ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Umaten Toppage v2.7.0 (修正版): Returning hero image URL for pseudo attachment ID '{$attachment_id}': {$hero_image_url}");
        }

        // 画像情報を返す（URL、幅、高さ、isIntermediateサイズ）
        // 幅と高さは仮の値（実際の画像サイズを取得する場合は追加処理が必要）
        return array($hero_image_url, 800, 600, false);
    }

    /**
     * 【v2.7.0新機能】wp_get_attachment_image_attributesフィルター - 画像属性を設定
     */
    public function filter_attachment_image_attributes($attr, $attachment, $size) {
        // 疑似サムネイルIDでない場合は処理しない
        if (!is_object($attachment) || !isset($attachment->ID)) {
            if (is_string($attachment) && strpos($attachment, self::PSEUDO_THUMBNAIL_PREFIX) === 0) {
                // 疑似サムネイルIDの場合は投稿IDを抽出
                $post_id = str_replace(self::PSEUDO_THUMBNAIL_PREFIX, '', $attachment);
                $attr['alt'] = get_the_title($post_id);
            }
        }

        return $attr;
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
        $existing_hero_image = $this->get_meta_directly($post_id, '_umaten_hero_image_url');
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
                error_log("Umaten Toppage v2.7.0 (修正版): Saved hero image URL for post {$post_id}: {$image_url}");
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
        $instance = self::get_instance();

        // まずメタデータから取得（データベース直接クエリ）
        $hero_image_url = $instance->get_meta_directly($post_id, '_umaten_hero_image_url');

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
