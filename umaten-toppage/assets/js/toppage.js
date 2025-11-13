(function($) {
    'use strict';

    /**
     * Umaten トップページ JS
     */
    const UmatenToppage = {
        currentParentSlug: '',
        currentChildSlug: '',

        /**
         * 初期化
         */
        init: function() {
            this.loadAreaSettings();
            this.bindEvents();
        },

        /**
         * イベントバインド
         */
        bindEvents: function() {
            const self = this;

            // モーダルクローズボタン
            $(document).on('click', '#modal-close-btn, #tag-modal-close-btn', function() {
                self.closeModal('#child-category-modal');
                self.closeModal('#tag-modal');
            });

            // モーダル外側クリックで閉じる
            $(document).on('click', '.umaten-modal', function(e) {
                if ($(e.target).hasClass('umaten-modal')) {
                    self.closeModal($(this).attr('id'));
                }
            });

            // ESCキーでモーダルを閉じる
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    self.closeModal('#child-category-modal');
                    self.closeModal('#tag-modal');
                }
            });
        },

        /**
         * エリア設定をロード
         */
        loadAreaSettings: function() {
            const self = this;

            $.ajax({
                url: umatenToppage.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'umaten_get_area_settings',
                    nonce: umatenToppage.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.renderAreaTabs(response.data.areas);
                    } else {
                        console.error('エリア設定の取得に失敗しました。');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX エラー:', error);
                }
            });
        },

        /**
         * エリアタブをレンダリング
         */
        renderAreaTabs: function(areas) {
            const self = this;
            const $tabsContainer = $('#area-tabs-container');
            const $contentContainer = $('#area-content-container');

            $tabsContainer.empty();
            $contentContainer.empty();

            let firstPublishedArea = null;

            $.each(areas, function(areaKey, areaData) {
                if (areaData.status === 'hidden') {
                    return; // 非表示の場合はスキップ
                }

                const isComingSoon = areaData.status === 'coming_soon';
                const isPublished = areaData.status === 'published';
                const comingSoonText = isComingSoon ? ' <span style="font-size: 11px; opacity: 0.8;">（準備中）</span>' : '';

                // タブボタンを作成
                const $tab = $('<a>')
                    .attr('href', '#')
                    .addClass('meshimap-area-tab')
                    .attr('data-area', areaKey)
                    .html(areaData.label + comingSoonText);

                if (isComingSoon) {
                    $tab.addClass('coming-soon');
                } else if (isPublished) {
                    if (!firstPublishedArea) {
                        firstPublishedArea = areaKey;
                        $tab.addClass('active');
                    }
                }

                $tabsContainer.append($tab);

                // コンテンツエリアを作成
                const $content = $('<div>')
                    .addClass('meshimap-area-content')
                    .attr('id', 'area-' + areaKey);

                if (isPublished && areaKey === firstPublishedArea) {
                    $content.addClass('active');
                }

                if (isComingSoon) {
                    // 準備中メッセージ
                    $content.html(`
                        <div class="meshimap-coming-soon">
                            <div class="meshimap-coming-soon-icon">&#128679;</div>
                            <h3 class="meshimap-coming-soon-title">${areaData.label}エリア 準備中</h3>
                            <p class="meshimap-coming-soon-text">
                                現在、${areaData.label}エリアの店舗情報を準備中です。<br>
                                近日公開予定ですので、今しばらくお待ちください。
                            </p>
                        </div>
                    `);
                } else if (isPublished) {
                    // 北海道の場合はカードを表示
                    if (areaKey === 'hokkaido') {
                        const defaultImage = 'https://umaten.jp/wp-content/uploads/2025/11/fuji-san-pagoda-view.webp';
                        $content.html(`
                            <div class="meshimap-category-grid">
                                <a href="#" class="meshimap-category-card" data-parent-slug="${areaKey}">
                                    <img src="${defaultImage}" alt="${areaData.label}" class="meshimap-category-image">
                                    <div class="meshimap-category-overlay">
                                        <div class="meshimap-category-name">${areaData.label}</div>
                                    </div>
                                </a>
                            </div>
                        `);
                    }
                }

                $contentContainer.append($content);
            });

            // タブクリックイベント
            $tabsContainer.on('click', '.meshimap-area-tab', function(e) {
                e.preventDefault();

                if ($(this).hasClass('coming-soon')) {
                    return; // 準備中の場合は何もしない
                }

                // アクティブクラスの切り替え
                $('.meshimap-area-tab').removeClass('active');
                $(this).addClass('active');

                // コンテンツの切り替え
                const targetArea = $(this).data('area');
                $('.meshimap-area-content').removeClass('active');
                $('#area-' + targetArea).addClass('active');
            });

            // カテゴリカードクリックイベント
            $contentContainer.on('click', '.meshimap-category-card', function(e) {
                e.preventDefault();
                const parentSlug = $(this).data('parent-slug');
                self.loadChildCategories(parentSlug);
            });
        },

        /**
         * 子カテゴリを読み込み
         */
        loadChildCategories: function(parentSlug) {
            const self = this;
            self.currentParentSlug = parentSlug;

            // モーダルを表示
            self.openModal('#child-category-modal');

            // ローディング表示
            $('#child-categories-grid').html(`
                <div class="umaten-loading">
                    <div class="umaten-spinner"></div>
                    <p style="margin-top: 16px; color: #666;">子カテゴリを読み込み中...</p>
                </div>
            `);

            $.ajax({
                url: umatenToppage.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'umaten_get_child_categories',
                    nonce: umatenToppage.nonce,
                    parent_slug: parentSlug
                },
                success: function(response) {
                    if (response.success) {
                        const categories = response.data.categories;
                        const parentName = response.data.parent_name;

                        $('#modal-title').text(parentName + ' のエリアを選択');
                        self.renderChildCategories(categories);
                    } else {
                        $('#child-categories-grid').html(`
                            <div class="meshimap-coming-soon">
                                <div class="meshimap-coming-soon-icon">&#9888;</div>
                                <h3 class="meshimap-coming-soon-title">子カテゴリが見つかりません</h3>
                                <p class="meshimap-coming-soon-text">${response.data.message || 'エラーが発生しました。'}</p>
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX エラー:', error);
                    $('#child-categories-grid').html(`
                        <div class="meshimap-coming-soon">
                            <div class="meshimap-coming-soon-icon">&#9888;</div>
                            <h3 class="meshimap-coming-soon-title">エラー</h3>
                            <p class="meshimap-coming-soon-text">子カテゴリの読み込みに失敗しました。</p>
                        </div>
                    `);
                }
            });
        },

        /**
         * 子カテゴリをレンダリング
         */
        renderChildCategories: function(categories) {
            const self = this;
            const $grid = $('#child-categories-grid');
            $grid.empty();

            if (categories.length === 0) {
                $grid.html(`
                    <div class="meshimap-coming-soon">
                        <div class="meshimap-coming-soon-icon">&#128679;</div>
                        <h3 class="meshimap-coming-soon-title">子カテゴリがありません</h3>
                        <p class="meshimap-coming-soon-text">このエリアにはまだ子カテゴリが登録されていません。</p>
                    </div>
                `);
                return;
            }

            $.each(categories, function(index, category) {
                const $card = $('<a>')
                    .attr('href', '#')
                    .addClass('meshimap-category-card')
                    .attr('data-child-slug', category.slug)
                    .html(`
                        <img src="${category.thumbnail}" alt="${category.name}" class="meshimap-category-image">
                        <div class="meshimap-category-overlay">
                            <div class="meshimap-category-name">${category.name}</div>
                        </div>
                    `);

                $card.on('click', function(e) {
                    e.preventDefault();
                    self.currentChildSlug = category.slug;
                    self.closeModal('#child-category-modal');
                    self.loadTags();
                });

                $grid.append($card);
            });
        },

        /**
         * タグを読み込み
         */
        loadTags: function() {
            const self = this;

            // タグモーダルを表示
            self.openModal('#tag-modal');

            // ローディング表示
            $('#tags-grid').html(`
                <div class="umaten-loading">
                    <div class="umaten-spinner"></div>
                    <p style="margin-top: 16px; color: #666;">ジャンルを読み込み中...</p>
                </div>
            `);

            $.ajax({
                url: umatenToppage.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'umaten_get_tags',
                    nonce: umatenToppage.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const tags = response.data.tags;
                        $('#tag-modal-title').text('ジャンルを選択');
                        self.renderTags(tags);
                    } else {
                        $('#tags-grid').html(`
                            <div class="meshimap-coming-soon">
                                <div class="meshimap-coming-soon-icon">&#9888;</div>
                                <h3 class="meshimap-coming-soon-title">ジャンルが見つかりません</h3>
                                <p class="meshimap-coming-soon-text">${response.data.message || 'エラーが発生しました。'}</p>
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX エラー:', error);
                    $('#tags-grid').html(`
                        <div class="meshimap-coming-soon">
                            <div class="meshimap-coming-soon-icon">&#9888;</div>
                            <h3 class="meshimap-coming-soon-title">エラー</h3>
                            <p class="meshimap-coming-soon-text">ジャンルの読み込みに失敗しました。</p>
                        </div>
                    `);
                }
            });
        },

        /**
         * タグをレンダリング
         */
        renderTags: function(tags) {
            const self = this;
            const $grid = $('#tags-grid');
            $grid.empty();

            if (tags.length === 0) {
                $grid.html(`
                    <div class="meshimap-coming-soon">
                        <div class="meshimap-coming-soon-icon">&#128679;</div>
                        <h3 class="meshimap-coming-soon-title">ジャンルがありません</h3>
                        <p class="meshimap-coming-soon-text">ジャンル（タグ）が登録されていません。</p>
                    </div>
                `);
                return;
            }

            $.each(tags, function(index, tag) {
                const $tagItem = $('<a>')
                    .attr('href', '#')
                    .addClass('meshimap-tag-item')
                    .text(tag.name)
                    .attr('data-tag-slug', tag.slug);

                $tagItem.on('click', function(e) {
                    e.preventDefault();
                    const tagSlug = tag.slug;
                    self.navigateToFinalUrl(tagSlug);
                });

                $grid.append($tagItem);
            });
        },

        /**
         * 最終URLに遷移
         */
        navigateToFinalUrl: function(tagSlug) {
            const self = this;

            // URL形式: umaten.jp/hokkaido/子カテゴリ/ジャンル/
            const finalUrl = umatenToppage.siteUrl + '/' +
                             self.currentParentSlug + '/' +
                             self.currentChildSlug + '/' +
                             tagSlug + '/';

            // URLに遷移
            window.location.href = finalUrl;
        },

        /**
         * モーダルを開く
         */
        openModal: function(modalId) {
            $(modalId).addClass('active');
            $('body').css('overflow', 'hidden');
        },

        /**
         * モーダルを閉じる
         */
        closeModal: function(modalId) {
            $(modalId).removeClass('active');
            $('body').css('overflow', 'auto');
        }
    };

    /**
     * DOM読み込み完了時に初期化
     */
    $(document).ready(function() {
        if ($('.meshimap-wrapper').length > 0) {
            UmatenToppage.init();
        }
    });

})(jQuery);
