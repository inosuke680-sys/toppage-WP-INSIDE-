<?php
/**
 * Plugin Name: ウマ店トップページ
 * Plugin URI: https://umaten.jp
 * Description: グルメポータルサイトのトップページコンテンツを表示するプラグイン（スマホ対応版）
 * Version: 1.1.0
 * Author: Umaten Team
 * Author URI: https://umaten.jp
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: umaten-toppage
 */

// 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

/**
 * メインのショートコード関数
 * 使用方法: [umaten_toppage]
 */
function umaten_toppage_shortcode($atts) {
    // 属性のデフォルト値を設定
    $atts = shortcode_atts(array(
        'class' => '',
    ), $atts, 'umaten_toppage');

    // バッファリング開始
    ob_start();

    // HTMLファイルのパスを取得
    $html_file = plugin_dir_path(__FILE__) . 'umaten-toppage.html';

    // HTMLファイルが存在する場合は読み込む
    if (file_exists($html_file)) {
        include($html_file);
    } else {
        echo '<div class="error">トップページのHTMLファイルが見つかりません。</div>';
    }

    // バッファの内容を取得して返す
    return ob_get_clean();
}

// ショートコードを登録
add_shortcode('umaten_toppage', 'umaten_toppage_shortcode');

/**
 * プラグイン有効化時の処理
 */
function umaten_toppage_activate() {
    // 必要に応じて初期化処理をここに追加
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'umaten_toppage_activate');

/**
 * プラグイン無効化時の処理
 */
function umaten_toppage_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'umaten_toppage_deactivate');
