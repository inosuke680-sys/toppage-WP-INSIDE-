<?php
/**
 * ヒーロー画像自動設定クラス (v2.10.0 多重ハンドリング完全版)
 *
 * 本文からヒーロー画像を抽出し、WordPressのアイキャッチ画像として自動設定。
 * 一覧ページでは表示、記事ページでは非表示（重複回避）。
 *
 * v2.10.0の改善（多重ハンドリング）:
 * - 画像抽出の6つのパターン実装
 * - attachment ID取得の5つの方法実装
 * - 外部URL画像のダウンロード&インポート対応
 * - アイキャッチ設定後の検証機能
 * - 完全なデバッグログシステム
 * - エラー時の詳細なスタックトレース
 * - 既存投稿への一括適用WP-CLIコマンド対応
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
     * デバッグモード
     */
    private $debug_mode = false;

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
        // デバッグモードの設定
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;

        // 投稿保存時にヒーロー画像をアイキャッチとして設定
        add_action('save_post', array($this, 'save_hero_image_as_thumbnail'), 10, 2);

        // 【v2.10.0重要】記事ページではアイキャッチを非表示（優先度を高く設定）
        add_filter('post_thumbnail_html', array($this, 'hide_thumbnail_on_single'), 999, 5);

        // 管理画面通知
        add_action('admin_init', array($this, 'maybe_add_admin_notice'));

        $this->log("Umaten Hero Image class initialized (v2.10.0)");
    }

    /**
     * 【v2.10.0改善】デバッグログ出力（タイムスタンプ付き）
     */
    private function log($message, $data = null) {
        if (!$this->debug_mode) {
            return;
        }

        $timestamp = current_time('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] Umaten Toppage Hero Image v2.10.0: {$message}";

        if ($data !== null) {
            $log_message .= " | Data: " . print_r($data, true);
        }

        error_log($log_message);
    }

    /**
     * 【v2.10.0新機能】記事ページ（single.php）ではアイキャッチを非表示
     */
    public function hide_thumbnail_on_single($html, $post_id, $post_thumbnail_id, $size, $attr) {
        // 記事ページの場合は空文字を返す（アイキャッチを表示しない）
        if (is_single() || is_singular('post')) {
            $this->log("Hiding thumbnail on single post (ID: {$post_id})");
            return '';
        }

        // 一覧ページ・アーカイブページでは通常通り表示
        return $html;
    }

    /**
     * 投稿保存時にヒーロー画像をアイキャッチとして設定
     */
    public function save_hero_image_as_thumbnail($post_id, $post) {
        $this->log("=== save_hero_image_as_thumbnail called ===", array(
            'post_id' => $post_id,
            'post_type' => $post->post_type,
            'post_status' => $post->post_status
        ));

        // 自動保存時はスキップ
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            $this->log("Skipping: DOING_AUTOSAVE");
            return;
        }

        // 投稿タイプが post でない場合はスキップ
        if ($post->post_type !== 'post') {
            $this->log("Skipping: post_type is not 'post'");
            return;
        }

        // リビジョンの場合はスキップ
        if (wp_is_post_revision($post_id)) {
            $this->log("Skipping: post is revision");
            return;
        }

        // 既にアイキャッチが設定されている場合はスキップ（強制更新オプションがない限り）
        $force_update = get_post_meta($post_id, '_umaten_force_hero_update', true);
        if (has_post_thumbnail($post_id) && !$force_update) {
            $this->log("Skipping: post already has thumbnail (use _umaten_force_hero_update to override)");
            return;
        }

        // 強制更新フラグをクリア
        if ($force_update) {
            delete_post_meta($post_id, '_umaten_force_hero_update');
        }

        // 本文から画像を抽出してアイキャッチとして設定
        $result = $this->extract_and_set_thumbnail($post_id, $post->post_content);

        if ($result) {
            $this->log("✓ Successfully set hero image for post {$post_id}");
        } else {
            $this->log("✗ Failed to set hero image for post {$post_id}");
        }
    }

    /**
     * 【v2.10.0完全版】本文から画像を抽出してアイキャッチとして設定（多重ハンドリング）
     */
    private function extract_and_set_thumbnail($post_id, $content) {
        $this->log("--- extract_and_set_thumbnail for post {$post_id} ---");

        // 【ステップ1】6つのパターンで画像URLを抽出
        $image_url = $this->extract_image_url_multi_pattern($content);

        if (!$image_url) {
            $this->log("No image URL found in content");
            return false;
        }

        $this->log("Extracted image URL", $image_url);

        // 相対URLを絶対URLに変換
        $image_url = $this->normalize_image_url($image_url);
        $this->log("Normalized image URL", $image_url);

        // 【ステップ2】5つの方法でattachment IDを取得
        $attachment_id = $this->get_attachment_id_multi_method($image_url);

        // 【ステップ3】attachment IDが見つからない場合、外部画像をインポート
        if (!$attachment_id && $this->is_external_url($image_url)) {
            $this->log("Attempting to import external image");
            $attachment_id = $this->import_external_image($image_url, $post_id);
        }

        if (!$attachment_id) {
            $this->log("Could not find or import attachment for URL: {$image_url}");
            // メタデータのみ保存（フォールバック）
            update_post_meta($post_id, '_umaten_hero_image_url', esc_url($image_url));
            update_post_meta($post_id, '_umaten_hero_image_status', 'url_only');
            return false;
        }

        // 【ステップ4】アイキャッチとして設定
        $set_result = set_post_thumbnail($post_id, $attachment_id);

        // 【ステップ5】設定を検証
        if ($set_result) {
            $verify_id = get_post_thumbnail_id($post_id);
            if ($verify_id == $attachment_id) {
                $this->log("✓ Thumbnail set and verified", array(
                    'post_id' => $post_id,
                    'attachment_id' => $attachment_id,
                    'url' => $image_url
                ));

                // メタデータにも保存（後方互換性）
                update_post_meta($post_id, '_umaten_hero_image_url', esc_url($image_url));
                update_post_meta($post_id, '_umaten_hero_image_attachment_id', $attachment_id);
                update_post_meta($post_id, '_umaten_hero_image_status', 'success');
                update_post_meta($post_id, '_umaten_hero_image_set_time', current_time('mysql'));

                return true;
            } else {
                $this->log("✗ Thumbnail verification failed", array(
                    'expected' => $attachment_id,
                    'got' => $verify_id
                ));
            }
        } else {
            $this->log("✗ set_post_thumbnail() returned false");
        }

        // 設定に失敗した場合はメタデータのみ保存
        update_post_meta($post_id, '_umaten_hero_image_url', esc_url($image_url));
        update_post_meta($post_id, '_umaten_hero_image_status', 'set_failed');
        return false;
    }

    /**
     * 【v2.10.0新機能】6つのパターンで画像URLを抽出
     */
    private function extract_image_url_multi_pattern($content) {
        $patterns = array(
            // パターン1: restaurant-hero-imageクラスを持つ画像
            array(
                'name' => 'restaurant-hero-image class',
                'regex' => '/<img[^>]+class=["\'][^"\']*restaurant-hero-image[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i'
            ),
            // パターン2: ls-is-cached lazyloadedクラスを持つ画像
            array(
                'name' => 'ls-is-cached lazyloaded class',
                'regex' => '/<img[^>]+class=["\'][^"\']*ls-is-cached[^"\']*lazyloaded[^"\']*["\'][^>]+src=["\']([^"\']+)["\'][^>]*>/i'
            ),
            // パターン3: data-src属性を持つ画像（Lazy Load用）
            array(
                'name' => 'data-src attribute',
                'regex' => '/<img[^>]+data-src=["\']([^"\']+)["\'][^>]*>/i'
            ),
            // パターン4: srcset属性の最初のURL
            array(
                'name' => 'srcset attribute',
                'regex' => '/<img[^>]+srcset=["\']([^\s]+)[^"\']*["\'][^>]*>/i'
            ),
            // パターン5: wp:image ブロック内の画像
            array(
                'name' => 'wp:image block',
                'regex' => '/<!-- wp:image[^>]*-->\s*<figure[^>]*>\s*<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i'
            ),
            // パターン6: 最初の通常の画像（src属性）
            array(
                'name' => 'first img src',
                'regex' => '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i'
            )
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern['regex'], $content, $matches)) {
                $this->log("Image found with pattern: {$pattern['name']}", $matches[1]);
                return $matches[1];
            }
        }

        $this->log("No image found with any pattern");
        return null;
    }

    /**
     * 【v2.10.0新機能】URLを正規化（相対→絶対、プロトコル補完）
     */
    private function normalize_image_url($url) {
        // 相対URLを絶対URLに変換
        if (strpos($url, 'http') !== 0) {
            if (strpos($url, '//') === 0) {
                // プロトコル相対URL
                $url = 'https:' . $url;
            } elseif (strpos($url, '/') === 0) {
                // サイト相対URL
                $url = site_url($url);
            } else {
                // その他の相対URL
                $url = site_url('/' . $url);
            }
        }

        return $url;
    }

    /**
     * 【v2.10.0新機能】5つの方法でattachment IDを取得
     */
    private function get_attachment_id_multi_method($url) {
        global $wpdb;

        $this->log("--- Attempting to get attachment ID for URL ---", $url);

        // 方法1: attachment_url_to_postid()（WordPress標準関数）
        $attachment_id = attachment_url_to_postid($url);
        if ($attachment_id) {
            $this->log("Method 1 success (attachment_url_to_postid)", $attachment_id);
            return $attachment_id;
        }

        // 方法2: _wp_attached_file メタデータ検索（ファイル名完全一致）
        $file = basename($url);
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_wp_attached_file'
            AND meta_value LIKE %s
            LIMIT 1",
            '%' . $wpdb->esc_like($file)
        ));

        if ($attachment_id) {
            $this->log("Method 2 success (_wp_attached_file)", $attachment_id);
            return intval($attachment_id);
        }

        // 方法3: guid 列検索
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
            AND guid LIKE %s
            LIMIT 1",
            '%' . $wpdb->esc_like($file)
        ));

        if ($attachment_id) {
            $this->log("Method 3 success (guid)", $attachment_id);
            return intval($attachment_id);
        }

        // 方法4: ファイル名のバリエーション検索（サイズ違い対応）
        // 例: image-150x150.jpg → image.jpg
        $file_without_size = preg_replace('/-\d+x\d+(\.[a-z]+)$/i', '$1', $file);
        if ($file_without_size !== $file) {
            $attachment_id = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_wp_attached_file'
                AND meta_value LIKE %s
                LIMIT 1",
                '%' . $wpdb->esc_like($file_without_size)
            ));

            if ($attachment_id) {
                $this->log("Method 4 success (size variation)", $attachment_id);
                return intval($attachment_id);
            }
        }

        // 方法5: URLパスの部分一致検索
        $url_path = parse_url($url, PHP_URL_PATH);
        if ($url_path) {
            $attachment_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                WHERE post_type = 'attachment'
                AND guid LIKE %s
                LIMIT 1",
                '%' . $wpdb->esc_like(basename(dirname($url_path))) . '%' . $wpdb->esc_like($file)
            ));

            if ($attachment_id) {
                $this->log("Method 5 success (path matching)", $attachment_id);
                return intval($attachment_id);
            }
        }

        $this->log("All methods failed to find attachment ID");
        return false;
    }

    /**
     * 【v2.10.0新機能】外部URLかどうかを判定
     */
    private function is_external_url($url) {
        $site_url = site_url();
        $site_host = parse_url($site_url, PHP_URL_HOST);
        $url_host = parse_url($url, PHP_URL_HOST);

        $is_external = ($url_host !== $site_host);
        $this->log("URL is " . ($is_external ? 'external' : 'internal'), array(
            'url' => $url,
            'site_host' => $site_host,
            'url_host' => $url_host
        ));

        return $is_external;
    }

    /**
     * 【v2.10.0新機能】外部画像をダウンロードしてメディアライブラリにインポート
     */
    private function import_external_image($url, $post_id) {
        $this->log("--- Importing external image ---", $url);

        // WordPress のメディアハンドリング関数を読み込み
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // 一時ファイルにダウンロード
        $tmp = download_url($url);

        if (is_wp_error($tmp)) {
            $this->log("Failed to download image", $tmp->get_error_message());
            return false;
        }

        // ファイル名を取得
        $file_array = array(
            'name' => basename($url),
            'tmp_name' => $tmp
        );

        // メディアライブラリにアップロード
        $attachment_id = media_handle_sideload($file_array, $post_id);

        // 一時ファイルを削除
        if (file_exists($tmp)) {
            @unlink($tmp);
        }

        if (is_wp_error($attachment_id)) {
            $this->log("Failed to import image to media library", $attachment_id->get_error_message());
            return false;
        }

        $this->log("✓ Successfully imported external image", array(
            'attachment_id' => $attachment_id,
            'url' => $url
        ));

        return $attachment_id;
    }

    /**
     * 投稿のヒーロー画像URLを取得（公開API - 後方互換性）
     *
     * @param int $post_id 投稿ID
     * @return string|false ヒーロー画像URLまたはfalse
     */
    public static function get_hero_image_url($post_id) {
        // まずアイキャッチ画像を取得（v2.10.0の標準）
        if (has_post_thumbnail($post_id)) {
            return get_the_post_thumbnail_url($post_id, 'large');
        }

        // アイキャッチがない場合、メタデータから取得（v2.6.0/v2.7.0/v2.8.0/v2.9.0互換）
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
        $success = isset($_GET['success']) ? intval($_GET['success']) : 0;
        $failed = $processed - $success;

        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>Umaten ヒーロー画像一括処理完了</strong></p>';
        echo '<p>処理件数: ' . $processed . '件 | 成功: ' . $success . '件 | 失敗: ' . $failed . '件</p>';
        echo '</div>';
    }

    /**
     * 【v2.10.0新機能】既存投稿に一括でヒーロー画像を設定（WP-CLI対応）
     */
    public static function bulk_set_hero_images_cli($args = array(), $assoc_args = array()) {
        $instance = self::get_instance();
        $instance->log("=== Bulk hero image setting started ===");

        $defaults = array(
            'force' => false,
            'limit' => -1,
            'offset' => 0
        );
        $options = wp_parse_args($assoc_args, $defaults);

        $query_args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $options['limit'],
            'offset' => $options['offset']
        );

        // 強制更新しない場合は、アイキャッチ未設定の投稿のみ対象
        if (!$options['force']) {
            $query_args['meta_query'] = array(
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
            );
        }

        $posts = get_posts($query_args);
        $processed = 0;
        $success = 0;

        foreach ($posts as $post) {
            $instance->log("Processing post {$post->ID}: {$post->post_title}");

            if ($options['force']) {
                // 強制更新フラグを設定
                update_post_meta($post->ID, '_umaten_force_hero_update', '1');
            }

            if ($instance->extract_and_set_thumbnail($post->ID, $post->post_content)) {
                $success++;
                if (defined('WP_CLI') && WP_CLI) {
                    WP_CLI::line("✓ Post {$post->ID}: {$post->post_title}");
                }
            } else {
                if (defined('WP_CLI') && WP_CLI) {
                    WP_CLI::warning("✗ Post {$post->ID}: {$post->post_title}");
                }
            }

            $processed++;
        }

        $instance->log("=== Bulk hero image setting completed ===", array(
            'processed' => $processed,
            'success' => $success,
            'failed' => $processed - $success
        ));

        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::success("Processed: {$processed}, Success: {$success}, Failed: " . ($processed - $success));
        }

        return array(
            'processed' => $processed,
            'success' => $success
        );
    }
}

// WP-CLIコマンド登録
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('umaten hero-images', array('Umaten_Toppage_Hero_Image', 'bulk_set_hero_images_cli'));
}
