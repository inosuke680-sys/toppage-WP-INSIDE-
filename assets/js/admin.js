/**
 * WP Hero Image Manager - Admin JavaScript
 * Version: 2.8.4
 *
 * @package WP_Hero_Image_Manager
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        var mediaUploader;
        var $useHeroImageCheckbox = $('input[name="use_hero_image"]');
        var $heroImageContainer = $('.hero-image-container');
        var $heroImageIdInput = $('#hero_image_id');
        var $heroImagePreview = $('.hero-image-preview');
        var $selectButton = $('#select_hero_image_button');
        var $removeButton = $('#remove_hero_image_button');

        /**
         * ヒーロー画像使用チェックボックスの変更イベント
         */
        $useHeroImageCheckbox.on('change', function() {
            if ($(this).is(':checked')) {
                $heroImageContainer.slideDown(200);
            } else {
                $heroImageContainer.slideUp(200);
            }
        });

        /**
         * ヒーロー画像選択ボタンのクリックイベント
         */
        $selectButton.on('click', function(e) {
            e.preventDefault();

            // メディアアップローダーが既に存在する場合は再利用
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            // メディアアップローダーの作成
            mediaUploader = wp.media({
                title: wpHeroImageManager.selectImageTitle || 'ヒーロー画像を選択',
                button: {
                    text: wpHeroImageManager.selectImageButton || '画像を使用'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            // 画像選択時の処理
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();

                // 画像IDを保存
                $heroImageIdInput.val(attachment.id);

                // プレビューを更新
                updatePreview(attachment);

                // 削除ボタンを表示
                $removeButton.show();
            });

            mediaUploader.open();
        });

        /**
         * ヒーロー画像削除ボタンのクリックイベント
         */
        $removeButton.on('click', function(e) {
            e.preventDefault();

            if (confirm('ヒーロー画像を削除してもよろしいですか?')) {
                // 画像IDをクリア
                $heroImageIdInput.val('');

                // プレビューをクリア
                $heroImagePreview.empty();

                // 削除ボタンを非表示
                $(this).hide();
            }
        });

        /**
         * プレビュー画像の更新
         *
         * @param {Object} attachment 添付ファイルオブジェクト
         */
        function updatePreview(attachment) {
            var imageUrl = attachment.sizes && attachment.sizes.medium
                ? attachment.sizes.medium.url
                : attachment.url;

            var $img = $('<img>', {
                src: imageUrl,
                alt: attachment.alt || '',
                style: 'max-width: 100%; height: auto; border: 1px solid #ddd; padding: 5px; background: #fff;'
            });

            $heroImagePreview.html($img);
        }

        /**
         * 初期化: ページ読み込み時の状態設定
         */
        function init() {
            // チェックボックスの状態に応じてコンテナの表示/非表示を設定
            if ($useHeroImageCheckbox.is(':checked')) {
                $heroImageContainer.show();
            } else {
                $heroImageContainer.hide();
            }
        }

        // 初期化実行
        init();
    });

})(jQuery);
