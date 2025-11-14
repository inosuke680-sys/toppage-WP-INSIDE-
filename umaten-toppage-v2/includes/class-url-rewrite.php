<?php
/**
 * URLリライトルールクラス (v2.0.0 完全改善版)
 * 投稿URLとカテゴリアーカイブURLを完全に区別
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
        // リライトルールの追加（優先度を下げる）
        add_action('init', array($this, 'add_rewrite_rules'), 99);
        add_filter('query_vars', array($this, 'add_query_vars'));

        // parse_requestフックで早期判定
        add_action('parse_request', array($this, 'parse_custom_request'), 1);
        add_action('template_redirect', array($this, 'template_redirect'));
    }

    /**
     * リライトルールを追加（WordPressのデフォルトルールの後に処理）
     */
    public function add_rewrite_rules() {
        // パターン1: /親カテゴリ/子カテゴリ/タグ/ (3段階)
        add_rewrite_rule(
            '^([^/]+)/([^/]+)/([^/]+)/?$',
            'index.php?umaten_parent=$matches[1]&umaten_child=$matches[2]&umaten_tag=$matches[3]',
            'bottom'  // WordPressのデフォルトルールの後に処理
        );

        // パターン2: /親カテゴリ/子カテゴリ/ (2段階)
        add_rewrite_rule(
            '^([^/]+)/([^/]+)/?$',
            'index.php?umaten_parent=$matches[1]&umaten_child=$matches[2]',
            'bottom'  // WordPressのデフォルトルールの後に処理
        );
    }

    /**
     * クエリ変数を追加
     */
    public function add_query_vars($vars) {
        $vars[] = 'umaten_parent';
        $vars[] = 'umaten_child';
        $vars[] = 'umaten_tag';
        return $vars;
    }

    /**
     * リクエストを早期に解析して投稿を判定
     */
    public function parse_custom_request($wp) {
        // カスタムクエリ変数が設定されている場合のみ処理
        if (empty($wp->query_vars['umaten_parent']) && empty($wp->query_vars['umaten_child'])) {
            return;
        }

        $parent_slug = isset($wp->query_vars['umaten_parent']) ? $wp->query_vars['umaten_parent'] : '';
        $child_slug = isset($wp->query_vars['umaten_child']) ? $wp->query_vars['umaten_child'] : '';
        $tag_slug = isset($wp->query_vars['umaten_tag']) ? $wp->query_vars['umaten_tag'] : '';

        // 投稿が存在するかチェック
        $post_id = $this->find_post_by_url_pattern($parent_slug, $child_slug, $tag_slug);

        if ($post_id) {
            // 投稿が見つかった場合、カスタムクエリ変数をクリアしてWordPressに処理を任せる
            unset($wp->query_vars['umaten_parent']);
            unset($wp->query_vars['umaten_child']);
            unset($wp->query_vars['umaten_tag']);

            // 投稿名でクエリ変数を設定
            $post = get_post($post_id);
            $wp->query_vars['name'] = $post->post_name;
            $wp->query_vars['post_type'] = 'post';
        }
    }

    /**
     * テンプレートリダイレクト
     */
    public function template_redirect() {
        global $wp_query;

        // 既に投稿が見つかっている場合は何もしない
        if (is_singular()) {
            return;
        }

        // カスタムクエリ変数を取得
        $parent_slug = get_query_var('umaten_parent');
        $child_slug = get_query_var('umaten_child');
        $tag_slug = get_query_var('umaten_tag');

        // カスタムクエリがない場合は通常処理
        if (empty($parent_slug) && empty($child_slug)) {
            return;
        }

        // カテゴリやタグが存在するか確認
        $parent_term = get_term_by('slug', $parent_slug, 'category');
        $child_term = get_term_by('slug', $child_slug, 'category');
        $tag_term = !empty($tag_slug) ? get_term_by('slug', $tag_slug, 'post_tag') : null;

        // カテゴリ/タグが存在しない場合は404
        if (!$parent_term && !$child_term && !$tag_term) {
            $wp_query->set_404();
            status_header(404);
            return;
        }

        // カテゴリ/タグページとして処理
        $this->setup_archive_query($parent_term, $child_term, $tag_term);
    }

    /**
     * URLパターンから投稿を検索（改善版）
     */
    private function find_post_by_url_pattern($parent_slug, $child_slug, $tag_slug) {
        global $wpdb;

        // 2段階の場合: /category/post-slug/
        if (!empty($parent_slug) && !empty($child_slug) && empty($tag_slug)) {
            // child_slugが投稿スラッグの可能性
            $post_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'post' AND post_status = 'publish'",
                $child_slug
            ));

            if ($post_id) {
                // カテゴリチェック（parent_slugがカテゴリに関連しているか）
                $categories = wp_get_post_categories($post_id, array('fields' => 'all'));
                foreach ($categories as $cat) {
                    // 直接カテゴリまたは親カテゴリがマッチするか
                    if ($cat->slug === $parent_slug) {
                        return $post_id;
                    }
                    // 親カテゴリをチェック
                    if ($cat->parent) {
                        $parent_cat = get_term($cat->parent, 'category');
                        if ($parent_cat && $parent_cat->slug === $parent_slug) {
                            return $post_id;
                        }
                    }
                }
            }
        }

        // 3段階の場合: /category/subcategory/post-slug/
        if (!empty($parent_slug) && !empty($child_slug) && !empty($tag_slug)) {
            // tag_slugが投稿スラッグの可能性
            $post_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'post' AND post_status = 'publish'",
                $tag_slug
            ));

            if ($post_id) {
                return $post_id;
            }
        }

        return false;
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
        $instance = self::get_instance();
        $instance->add_rewrite_rules();
        flush_rewrite_rules();
    }
}
