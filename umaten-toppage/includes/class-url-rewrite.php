<?php
/**
 * URLリライトルールクラス (v1.6.0 改善版)
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
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'template_redirect'));
    }

    /**
     * リライトルールを追加
     */
    public function add_rewrite_rules() {
        // パターン1: /親カテゴリ/子カテゴリ/タグ/ (3段階)
        add_rewrite_rule(
            '^([^/]+)/([^/]+)/([^/]+)/?$',
            'index.php?umaten_parent=$matches[1]&umaten_child=$matches[2]&umaten_tag=$matches[3]',
            'top'
        );

        // パターン2: /親カテゴリ/子カテゴリ/ (2段階)
        add_rewrite_rule(
            '^([^/]+)/([^/]+)/?$',
            'index.php?umaten_parent=$matches[1]&umaten_child=$matches[2]',
            'top'
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
     * テンプレートリダイレクト
     */
    public function template_redirect() {
        global $wp_query;

        // 既に投稿が見つかっている場合は何もしない（通常のWordPress処理に任せる）
        if (is_singular()) {
            return;
        }

        // 404の場合のみカスタム処理を試みる、またはカスタムクエリ変数がある場合
        $parent_slug = get_query_var('umaten_parent');
        $child_slug = get_query_var('umaten_child');
        $tag_slug = get_query_var('umaten_tag');

        // カスタムクエリがない場合は通常処理
        if (empty($parent_slug) && empty($child_slug)) {
            return;
        }

        // 投稿が存在する可能性をチェック
        // 例: /hokkaido/article-slug/ の場合、article-slugが投稿スラッグの可能性がある
        if ($this->is_post_exists($parent_slug, $child_slug, $tag_slug)) {
            // 投稿が存在する場合は、リライトルールをスキップして通常のWordPress処理に任せる
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
     * 投稿が存在するかチェック
     */
    private function is_post_exists($parent_slug, $child_slug, $tag_slug) {
        // 2段階の場合: /category/post-slug/
        if (!empty($parent_slug) && !empty($child_slug) && empty($tag_slug)) {
            $post = get_page_by_path($child_slug, OBJECT, 'post');
            if ($post && $post->post_status === 'publish') {
                // カテゴリもチェック
                $categories = get_the_category($post->ID);
                foreach ($categories as $cat) {
                    if ($cat->slug === $parent_slug || $cat->parent && get_term($cat->parent)->slug === $parent_slug) {
                        return true;
                    }
                }
            }
        }

        // 3段階の場合: /category/subcategory/post-slug/
        if (!empty($parent_slug) && !empty($child_slug) && !empty($tag_slug)) {
            $post = get_page_by_path($tag_slug, OBJECT, 'post');
            if ($post && $post->post_status === 'publish') {
                return true;
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

        // カスタムテンプレート変数を設定（SEOクラスで使用）
        set_query_var('umaten_parent_term', $parent_term);
        set_query_var('umaten_child_term', $child_term);
        set_query_var('umaten_tag_term', $tag_term);
        set_query_var('umaten_is_archive', true); // アーカイブページであることを示すフラグ

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
