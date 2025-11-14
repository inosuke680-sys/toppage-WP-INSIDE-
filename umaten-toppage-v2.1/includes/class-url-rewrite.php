<?php
/**
 * URLリライトルールクラス (v2.1.0 完全新設計)
 * WordPressのデフォルトルールを完全に優先し、404時のみカスタム処理
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
        // 404時のみカスタム処理を実行
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
        // v2.1.0では特別なリライトルールを追加しないため、
        // 通常のフラッシュのみ実行
        flush_rewrite_rules();
    }
}
