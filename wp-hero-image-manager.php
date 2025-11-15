<?php
/**
 * Plugin Name: WP Hero Image Manager
 * Plugin URI: https://github.com/inosuke680-sys/toppage-WP-INSIDE-
 * Description: ヒーロー画像とアイキャッチ画像の重複を防ぎ、投稿ページとアーカイブページで適切に表示を管理します。
 * Version: 2.8.4
 * Author: inosuke680-sys
 * Author URI: https://github.com/inosuke680-sys
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-hero-image-manager
 * Domain Path: /languages
 *
 * @package WP_Hero_Image_Manager
 */

// セキュリティ: 直接アクセスを防止
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// プラグインのバージョン定義
define( 'WP_HERO_IMAGE_MANAGER_VERSION', '2.8.4' );
define( 'WP_HERO_IMAGE_MANAGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_HERO_IMAGE_MANAGER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * WP Hero Image Manager メインクラス
 */
class WP_Hero_Image_Manager {

    /**
     * シングルトンインスタンス
     *
     * @var WP_Hero_Image_Manager
     */
    private static $instance = null;

    /**
     * ヒーロー画像のメタキー
     *
     * @var string
     */
    private $hero_image_meta_key = '_hero_image_id';

    /**
     * ヒーロー画像使用フラグのメタキー
     *
     * @var string
     */
    private $use_hero_image_meta_key = '_use_hero_image';

    /**
     * アイキャッチ非表示フラグのメタキー
     *
     * @var string
     */
    private $hide_featured_image_meta_key = '_hide_featured_image';

    /**
     * シングルトンインスタンスの取得
     *
     * @return WP_Hero_Image_Manager
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * コンストラクタ
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * フックの初期化
     */
    private function init_hooks() {
        // 管理画面の初期化
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_hero_image_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_hero_image_meta' ), 10, 2 );

        // フロントエンドの表示制御
        add_filter( 'the_content', array( $this, 'add_hero_image_to_content' ), 5 );
        add_filter( 'post_thumbnail_html', array( $this, 'filter_featured_image' ), 10, 5 );

        // スタイルとスクリプトの読み込み
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // プラグイン有効化時の処理
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
    }

    /**
     * 管理画面の初期化
     */
    public function admin_init() {
        // 必要に応じて設定を追加
    }

    /**
     * ヒーロー画像メタボックスの追加
     */
    public function add_hero_image_meta_box() {
        $post_types = get_post_types( array( 'public' => true ), 'names' );

        foreach ( $post_types as $post_type ) {
            add_meta_box(
                'wp_hero_image_manager_meta_box',
                'ヒーロー画像設定',
                array( $this, 'render_hero_image_meta_box' ),
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * ヒーロー画像メタボックスの表示
     *
     * @param WP_Post $post 投稿オブジェクト
     */
    public function render_hero_image_meta_box( $post ) {
        wp_nonce_field( 'wp_hero_image_manager_nonce', 'wp_hero_image_manager_nonce' );

        $use_hero_image = get_post_meta( $post->ID, $this->use_hero_image_meta_key, true );
        $hero_image_id = get_post_meta( $post->ID, $this->hero_image_meta_key, true );
        $hide_featured_image = get_post_meta( $post->ID, $this->hide_featured_image_meta_key, true );

        ?>
        <div class="wp-hero-image-manager-meta-box">
            <p>
                <label>
                    <input type="checkbox"
                           name="use_hero_image"
                           value="1"
                           <?php checked( $use_hero_image, '1' ); ?>>
                    ヒーロー画像を使用する
                </label>
            </p>

            <div class="hero-image-container" style="<?php echo $use_hero_image ? '' : 'display:none;'; ?>">
                <div class="hero-image-preview" style="margin-bottom: 10px;">
                    <?php
                    if ( $hero_image_id ) {
                        echo wp_get_attachment_image( $hero_image_id, 'medium', false, array( 'style' => 'max-width: 100%; height: auto;' ) );
                    }
                    ?>
                </div>

                <p>
                    <input type="hidden"
                           name="hero_image_id"
                           id="hero_image_id"
                           value="<?php echo esc_attr( $hero_image_id ); ?>">
                    <button type="button"
                            class="button button-secondary"
                            id="select_hero_image_button">
                        ヒーロー画像を選択
                    </button>
                    <button type="button"
                            class="button button-secondary"
                            id="remove_hero_image_button"
                            style="<?php echo $hero_image_id ? '' : 'display:none;'; ?>">
                        画像を削除
                    </button>
                </p>

                <p>
                    <label>
                        <input type="checkbox"
                               name="hide_featured_image"
                               value="1"
                               <?php checked( $hide_featured_image, '1' ); ?>>
                        記事内でアイキャッチ画像を非表示にする（重複防止）
                    </label>
                </p>

                <p class="description">
                    ヒーロー画像を使用する場合、記事内でのアイキャッチ画像の重複表示を防ぐため、
                    上記のチェックボックスを有効にすることを推奨します。
                    カテゴリページやアーカイブページでは、アイキャッチ画像が正常に表示されます。
                </p>
            </div>
        </div>

        <style>
            .wp-hero-image-manager-meta-box .hero-image-preview img {
                border: 1px solid #ddd;
                padding: 5px;
                background: #fff;
            }
        </style>
        <?php
    }

    /**
     * ヒーロー画像メタデータの保存
     *
     * @param int     $post_id 投稿ID
     * @param WP_Post $post    投稿オブジェクト
     */
    public function save_hero_image_meta( $post_id, $post ) {
        // 自動保存時は処理しない
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Nonceの検証
        if ( ! isset( $_POST['wp_hero_image_manager_nonce'] ) ||
             ! wp_verify_nonce( $_POST['wp_hero_image_manager_nonce'], 'wp_hero_image_manager_nonce' ) ) {
            return;
        }

        // 権限チェック
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // ヒーロー画像使用フラグの保存
        $use_hero_image = isset( $_POST['use_hero_image'] ) ? '1' : '0';
        update_post_meta( $post_id, $this->use_hero_image_meta_key, $use_hero_image );

        // ヒーロー画像IDの保存
        if ( isset( $_POST['hero_image_id'] ) ) {
            $hero_image_id = absint( $_POST['hero_image_id'] );
            update_post_meta( $post_id, $this->hero_image_meta_key, $hero_image_id );
        }

        // アイキャッチ非表示フラグの保存
        $hide_featured_image = isset( $_POST['hide_featured_image'] ) ? '1' : '0';
        update_post_meta( $post_id, $this->hide_featured_image_meta_key, $hide_featured_image );
    }

    /**
     * コンテンツにヒーロー画像を追加
     *
     * @param string $content 投稿コンテンツ
     * @return string 修正されたコンテンツ
     */
    public function add_hero_image_to_content( $content ) {
        // シングルページのみ処理
        if ( ! is_singular() ) {
            return $content;
        }

        global $post;

        $use_hero_image = get_post_meta( $post->ID, $this->use_hero_image_meta_key, true );
        $hero_image_id = get_post_meta( $post->ID, $this->hero_image_meta_key, true );

        // ヒーロー画像が有効で、画像IDが存在する場合
        if ( $use_hero_image === '1' && $hero_image_id ) {
            $hero_image_html = $this->get_hero_image_html( $hero_image_id );

            if ( $hero_image_html ) {
                $content = $hero_image_html . $content;
            }
        }

        return $content;
    }

    /**
     * ヒーロー画像のHTMLを取得
     *
     * @param int $image_id 画像ID
     * @return string ヒーロー画像のHTML
     */
    private function get_hero_image_html( $image_id ) {
        $image = wp_get_attachment_image(
            $image_id,
            'full',
            false,
            array(
                'class' => 'wp-hero-image',
                'loading' => 'eager'
            )
        );

        if ( ! $image ) {
            return '';
        }

        $html = '<div class="wp-hero-image-container">';
        $html .= $image;
        $html .= '</div>';

        return $html;
    }

    /**
     * アイキャッチ画像のフィルター（重複防止）
     *
     * @param string       $html              アイキャッチ画像のHTML
     * @param int          $post_id           投稿ID
     * @param int          $post_thumbnail_id アイキャッチ画像ID
     * @param string|array $size              画像サイズ
     * @param string|array $attr              画像属性
     * @return string フィルター後のHTML
     */
    public function filter_featured_image( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
        // シングルページでのみ処理
        if ( ! is_singular() ) {
            return $html;
        }

        $use_hero_image = get_post_meta( $post_id, $this->use_hero_image_meta_key, true );
        $hide_featured_image = get_post_meta( $post_id, $this->hide_featured_image_meta_key, true );

        // ヒーロー画像を使用し、アイキャッチ非表示が有効な場合
        if ( $use_hero_image === '1' && $hide_featured_image === '1' ) {
            return '';
        }

        return $html;
    }

    /**
     * フロントエンドアセットの読み込み
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'wp-hero-image-manager-frontend',
            WP_HERO_IMAGE_MANAGER_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WP_HERO_IMAGE_MANAGER_VERSION
        );
    }

    /**
     * 管理画面アセットの読み込み
     *
     * @param string $hook 現在の管理画面ページ
     */
    public function enqueue_admin_assets( $hook ) {
        // 投稿編集画面のみ
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
            return;
        }

        // メディアアップローダー
        wp_enqueue_media();

        // カスタムスクリプト
        wp_enqueue_script(
            'wp-hero-image-manager-admin',
            WP_HERO_IMAGE_MANAGER_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            WP_HERO_IMAGE_MANAGER_VERSION,
            true
        );

        wp_localize_script(
            'wp-hero-image-manager-admin',
            'wpHeroImageManager',
            array(
                'selectImageTitle' => 'ヒーロー画像を選択',
                'selectImageButton' => '画像を使用',
            )
        );

        wp_enqueue_style(
            'wp-hero-image-manager-admin',
            WP_HERO_IMAGE_MANAGER_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WP_HERO_IMAGE_MANAGER_VERSION
        );
    }

    /**
     * プラグイン有効化時の処理
     */
    public function activate() {
        // バージョン情報を保存
        update_option( 'wp_hero_image_manager_version', WP_HERO_IMAGE_MANAGER_VERSION );

        // 必要に応じてデータベーステーブルの作成などを行う
        flush_rewrite_rules();
    }

    /**
     * ヘルパー: ヒーロー画像IDを取得
     *
     * @param int $post_id 投稿ID
     * @return int|false ヒーロー画像ID、または false
     */
    public static function get_hero_image_id( $post_id = null ) {
        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }

        $instance = self::get_instance();
        $use_hero_image = get_post_meta( $post_id, $instance->use_hero_image_meta_key, true );

        if ( $use_hero_image !== '1' ) {
            return false;
        }

        $hero_image_id = get_post_meta( $post_id, $instance->hero_image_meta_key, true );
        return $hero_image_id ? absint( $hero_image_id ) : false;
    }

    /**
     * ヘルパー: ヒーロー画像が有効かチェック
     *
     * @param int $post_id 投稿ID
     * @return bool
     */
    public static function has_hero_image( $post_id = null ) {
        return self::get_hero_image_id( $post_id ) !== false;
    }
}

// プラグインの初期化
function wp_hero_image_manager_init() {
    return WP_Hero_Image_Manager::get_instance();
}

// WordPress読み込み後に初期化
add_action( 'plugins_loaded', 'wp_hero_image_manager_init' );

// テンプレート用ヘルパー関数
if ( ! function_exists( 'get_hero_image_id' ) ) {
    /**
     * ヒーロー画像IDを取得
     *
     * @param int $post_id 投稿ID
     * @return int|false
     */
    function get_hero_image_id( $post_id = null ) {
        return WP_Hero_Image_Manager::get_hero_image_id( $post_id );
    }
}

if ( ! function_exists( 'has_hero_image' ) ) {
    /**
     * ヒーロー画像が存在するかチェック
     *
     * @param int $post_id 投稿ID
     * @return bool
     */
    function has_hero_image( $post_id = null ) {
        return WP_Hero_Image_Manager::has_hero_image( $post_id );
    }
}

if ( ! function_exists( 'the_hero_image' ) ) {
    /**
     * ヒーロー画像を表示
     *
     * @param int          $post_id 投稿ID
     * @param string|array $size    画像サイズ
     * @param array        $attr    画像属性
     */
    function the_hero_image( $post_id = null, $size = 'full', $attr = array() ) {
        $hero_image_id = get_hero_image_id( $post_id );

        if ( $hero_image_id ) {
            echo wp_get_attachment_image( $hero_image_id, $size, false, $attr );
        }
    }
}
