<?php
/**
 * URLリライトルールクラス (v2.4.0 管理画面セーフ実装)
 * 投稿を検出したら積極的に表示する（管理画面では無効）
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
        // 【v2.4.0 重要】管理画面では一切処理しない
        if (is_admin()) {
            return;
        }

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

        // 【v2.4.0】投稿の存在を厳密にチェックし、見つかったら表示する
        // 2段階URL（/親/投稿名/）の場合
        if (count($parts) == 2) {
            $post = $this->find_post_by_slug($child_slug);
            if ($post) {
                // 投稿が存在する場合は、カテゴリチェックも行う
                $parent_category = get_term_by('slug', $parent_slug, 'category');
                if ($parent_category) {
                    // 投稿がそのカテゴリに属しているか確認
                    if (has_category($parent_category->term_id, $post)) {
                        // 【v2.4.0】投稿として積極的に表示
                        $this->setup_single_post_query($post);
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
                        // 【v2.4.0】投稿として積極的に表示
                        $this->setup_single_post_query($post);
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
     * 【v2.4.0 改善版】投稿ページとして表示するクエリをセットアップ
     *
     * @param WP_Post $post 投稿オブジェクト
     */
    private function setup_single_post_query($post) {
        global $wp_query, $wp_the_query;

        // デバッグログ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Umaten Toppage v2.4.0: Setting up single post query for post ID " . $post->ID);
        }

        // 投稿クエリを作成
        $args = array(
            'p' => $post->ID,
            'post_type' => 'post',
            'post_status' => 'publish'
        );

        // 新しいクエリで上書き
        $wp_query = new WP_Query($args);

        // 【v2.4.0 重要】メインクエリも同期
        $wp_the_query = $wp_query;

        // 404状態を解除し、投稿ページとして設定
        $wp_query->is_404 = false;
        $wp_query->is_single = true;
        $wp_query->is_singular = true;
        $wp_query->is_archive = false;
        $wp_query->is_home = false;
        status_header(200);

        // 【v2.4.0 改善】グローバル$postの設定を安全に行う
        // the_post()を呼び出して、WordPressの標準的な方法で設定
        if ($wp_query->have_posts()) {
            $wp_query->the_post();
        }

        // カスタムテンプレート変数を設定
        set_query_var('umaten_is_single_post', true);
        set_query_var('umaten_post_id', $post->ID);

        // テンプレートをロード
        add_filter('template_include', array($this, 'load_single_template'), 99);
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
        add_filter('template_include', array($this, 'load_custom_template'), 99);
    }

    /**
     * 【v2.4.0】投稿ページテンプレートをロード
     */
    public function load_single_template($template) {
        // umaten_is_single_postフラグがある場合のみ投稿テンプレートを使用
        if (!get_query_var('umaten_is_single_post')) {
            return $template;
        }

        // 投稿テンプレートとして扱う
        $single_template = locate_template(array('single.php', 'singular.php', 'index.php'));

        if ($single_template) {
            // デバッグログ
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Umaten Toppage v2.4.0: Loading single template - " . $single_template);
            }
            return $single_template;
        }

        return $template;
    }

    /**
     * カスタムテンプレートをロード（アーカイブ用）
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
        // v2.4.0では特別なリライトルールを追加しないため、
        // 通常のフラッシュのみ実行
        flush_rewrite_rules();
    }
}
