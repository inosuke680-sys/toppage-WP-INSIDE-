<?php
/**
 * URLリライトルールクラス (v2.2.0 投稿存在チェック強化版)
 * 404時のカスタム処理で投稿の存在を厳密にチェック
 */

// 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

class Umaten_Toppage_URL_Rewrite {

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
        // 404時のみカスタム処理を実行（優先度を999に設定）
        add_action('template_redirect', array($this, 'handle_404_redirect'), 999);
    }

    /**
     * 404エラー時のカスタムリダイレクト処理
     */
    public function handle_404_redirect() {
        // 404でない場合は何もしない
        if (!is_404()) {
            return;
        }

        // 投稿やページが見つかっている場合は何もしない
        if (is_singular() || is_page() || is_single()) {
            return;
        }

        global $wp;
        $current_path = trim($wp->request, '/');

        // パスが空の場合は処理しない
        if (empty($current_path)) {
            return;
        }

        // URLパスを分解
        $parts = explode('/', $current_path);

        // 2段階または3段階のパスのみ処理
        if (count($parts) < 2 || count($parts) > 3) {
            return;
        }

        $parent_slug = isset($parts[0]) ? $parts[0] : '';
        $child_slug = isset($parts[1]) ? $parts[1] : '';
        $tag_slug = isset($parts[2]) ? $parts[2] : '';

        // 【v2.2.0 重要改善】投稿の存在を厳密にチェック
        // 2段階URL（/親/投稿名/）の場合
        if (count($parts) == 2) {
            $post = $this->find_post_by_slug($child_slug);
            if ($post) {
                // 投稿が存在する場合は、カテゴリチェックも行う
                // 親スラッグがカテゴリかどうか確認
                $parent_category = get_term_by('slug', $parent_slug, 'category');
                if ($parent_category) {
                    // 投稿がそのカテゴリに属しているか確認
                    if (has_category($parent_category->term_id, $post)) {
                        // 正しい投稿URLなので何もしない（WordPressに任せる）
                        // しかし、404になっている場合はリダイレクトが必要
                        $post_link = get_permalink($post);
                        if ($post_link !== site_url($current_path . '/')) {
                            // パーマリンク設定が異なる可能性があるため、ログ記録のみ
                            error_log("Umaten Toppage v2.2.0: Post found but URL mismatch - " . $current_path);
                        }
                        return;
                    }
                }
            }
        }

        // 3段階URL（/親/子/投稿名またはタグ/）の場合
        if (count($parts) == 3) {
            // まず投稿として判定
            $post = $this->find_post_by_slug($tag_slug);
            if ($post) {
                // 親・子がカテゴリで、投稿がそれに属しているか確認
                $parent_category = get_term_by('slug', $parent_slug, 'category');
                $child_category = get_term_by('slug', $child_slug, 'category');

                if ($parent_category || $child_category) {
                    $target_cat = $child_category ? $child_category : $parent_category;
                    if (has_category($target_cat->term_id, $post)) {
                        // 正しい投稿URLなので何もしない
                        return;
                    }
                }
            }
        }

        // 投稿が存在しない、または該当カテゴリに属していない場合
        // カテゴリやタグの存在を確認
        $parent_term = get_term_by('slug', $parent_slug, 'category');
        $child_term = get_term_by('slug', $child_slug, 'category');
        $tag_term = !empty($tag_slug) ? get_term_by('slug', $tag_slug, 'post_tag') : null;

        // カテゴリまたはタグが存在しない場合は通常の404を返す
        if (!$parent_term && !$child_term && !$tag_term) {
            return;
        }

        // カテゴリ/タグが存在する場合、アーカイブページとして処理
        $this->setup_archive_query($parent_term, $child_term, $tag_term);
    }

    /**
     * 投稿スラッグから投稿を検索
     *
     * @param string $slug 投稿スラッグ
     * @return WP_Post|null 投稿オブジェクトまたはnull
     */
    private function find_post_by_slug($slug) {
        global $wpdb;

        // 投稿を検索
        $post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
            WHERE post_name = %s
            AND post_type = 'post'
            AND post_status = 'publish'
            LIMIT 1",
            $slug
        ));

        if (!$post_id) {
            return null;
        }

        return get_post($post_id);
    }

    /**
     * アーカイブクエリをセットアップ
     */
    private function setup_archive_query($parent_term, $child_term, $tag_term) {
        global $wp_query;

        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 12,
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1
        );

        $tax_query = array('relation' => 'AND');

        // 子カテゴリ優先
        if ($child_term) {
            $tax_query[] = array(
                'taxonomy' => 'category',
                'field' => 'term_id',
                'terms' => $child_term->term_id
            );
        } elseif ($parent_term) {
            $tax_query[] = array(
                'taxonomy' => 'category',
                'field' => 'term_id',
                'terms' => $parent_term->term_id
            );
        }

        // タグで絞り込み
        if ($tag_term) {
            $tax_query[] = array(
                'taxonomy' => 'post_tag',
                'field' => 'term_id',
                'terms' => $tag_term->term_id
            );
        }

        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query;
        }

        // 新しいクエリで上書き
        $wp_query = new WP_Query($args);

        // 404状態を解除
        $wp_query->is_404 = false;
        $wp_query->is_archive = true;
        status_header(200);

        // カスタムテンプレート変数を設定
        set_query_var('umaten_parent_term', $parent_term);
        set_query_var('umaten_child_term', $child_term);
        set_query_var('umaten_tag_term', $tag_term);
        set_query_var('umaten_is_archive', true);

        // テンプレートをロード
        add_filter('template_include', array($this, 'load_custom_template'));
    }

    /**
     * カスタムテンプレートをロード
     */
    public function load_custom_template($template) {
        // umaten_is_archiveフラグがある場合のみカスタムテンプレートを使用
        if (!get_query_var('umaten_is_archive')) {
            return $template;
        }

        // アーカイブテンプレートとして扱う
        $custom_template = locate_template(array('archive.php', 'index.php'));

        if ($custom_template) {
            return $custom_template;
        }

        return $template;
    }

    /**
     * リライトルールをフラッシュ（プラグイン有効化時）
     */
    public static function flush_rewrite_rules() {
        // v2.2.0では特別なリライトルールを追加しないため、
        // 通常のフラッシュのみ実行
        flush_rewrite_rules();
    }
}
