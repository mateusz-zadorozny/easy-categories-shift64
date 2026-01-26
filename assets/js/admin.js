/**
 * Easy Categories Shift64 - Admin JavaScript
 */

(function ($) {
    'use strict';

    const ECS64 = {
        categories: [],
        childlessIds: [],
        isLoading: false,
        parentOnlyMode: false,

        /**
         * Initialize the plugin
         */
        init: function () {
            this.categories = ecs64Data.categories || [];
            this.childlessIds = ecs64Data.childlessIds || [];

            this.renderTree();
            this.bindEvents();
            this.initSortable();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function () {
            const self = this;

            // Parent only filter
            $('#ecs64-parent-only').on('change', function () {
                const $tree = $('#ecs64-tree');
                self.parentOnlyMode = $(this).is(':checked');

                if (self.parentOnlyMode) {
                    $tree.addClass('ecs64-parent-only');
                    // Wyłącz drag & drop w trybie parent-only
                    self.destroySortable();
                } else {
                    $tree.removeClass('ecs64-parent-only');
                    // Włącz drag & drop z powrotem
                    self.initSortable();
                }
            });

            // Highlight childless filter
            $('#ecs64-highlight-childless').on('change', function () {
                const $tree = $('#ecs64-tree');
                if ($(this).is(':checked')) {
                    $tree.addClass('ecs64-highlight-childless');
                } else {
                    $tree.removeClass('ecs64-highlight-childless');
                }
            });

            // Set default state
            $('#ecs64-tree').addClass('ecs64-highlight-childless');

            // Move buttons
            $(document).on('click', '.ecs64-move-btn', function (e) {
                e.preventDefault();
                if ($(this).prop('disabled') || self.isLoading) return;

                const $item = $(this).closest('.ecs64-category-item');
                const categoryId = parseInt($item.data('id'), 10);
                const action = $(this).data('action');

                self.moveCategory(categoryId, action);
            });
        },

        /**
         * Render category tree
         */
        renderTree: function () {
            const $container = $('#ecs64-tree');
            $container.html(this.buildTreeHTML(this.categories, 0));
        },

        /**
         * Build HTML for category tree
         *
         * @param {Array} categories
         * @param {number} depth
         * @returns {string}
         */
        buildTreeHTML: function (categories, depth) {
            if (!categories || categories.length === 0) {
                if (depth === 0) {
                    return '<p style="padding: 20px; color: #646970;">' +
                        'Brak kategorii do wyświetlenia.</p>';
                }
                return '';
            }

            let html = '<ul class="ecs64-category-list" data-depth="' + depth + '">';

            categories.forEach((cat, index) => {
                const isFirst = index === 0;
                const isLast = index === categories.length - 1;
                const childlessClass = cat.is_childless ? ' ecs64-no-children' : '';
                const depthDashes = this.getDepthIndicator(depth);

                html += `
                    <li class="ecs64-category-item${childlessClass}" 
                        data-id="${cat.id}" 
                        data-parent="${cat.parent}"
                        data-order="${cat.order}">
                        <div class="ecs64-category-row">
                            <span class="ecs64-drag-handle" title="Przeciągnij aby zmienić kolejność">☰</span>
                            
                            <div class="ecs64-category-info">
                                ${depthDashes}
                                <span class="ecs64-category-name">${this.escapeHtml(cat.name)}</span>
                                <span class="ecs64-category-id">(ID: ${cat.id})</span>
                                <span class="ecs64-category-count">${cat.count} ${ecs64Data.i18n.products}</span>
                            </div>

                            <div class="ecs64-position-buttons">
                                <button type="button"
                                        class="ecs64-position-btn"
                                        data-position="left"
                                        title="${ecs64Data.i18n.positionLeft}">L</button>
                                <button type="button"
                                        class="ecs64-position-btn"
                                        data-position="right"
                                        title="${ecs64Data.i18n.positionRight}">R</button>
                                <button type="button"
                                        class="ecs64-position-btn"
                                        data-position="none"
                                        title="${ecs64Data.i18n.positionNone}">-</button>
                            </div>
                            
                            <div class="ecs64-move-buttons">
                                <button type="button" 
                                        class="ecs64-move-btn" 
                                        data-action="move_left"
                                        title="${ecs64Data.i18n.moveLeft}"
                                        ${cat.parent === 0 ? 'disabled' : ''}>◀</button>
                                <button type="button" 
                                        class="ecs64-move-btn" 
                                        data-action="move_up"
                                        title="${ecs64Data.i18n.moveUp}"
                                        ${isFirst ? 'disabled' : ''}>▲</button>
                                <button type="button" 
                                        class="ecs64-move-btn" 
                                        data-action="move_down"
                                        title="${ecs64Data.i18n.moveDown}"
                                        ${isLast ? 'disabled' : ''}>▼</button>
                                <button type="button" 
                                        class="ecs64-move-btn" 
                                        data-action="move_right"
                                        title="${ecs64Data.i18n.moveRight}"
                                        ${isFirst ? 'disabled' : ''}>▶</button>
                            </div>
                        </div>
                        ${this.buildTreeHTML(cat.children, depth + 1)}
                    </li>
                `;
            });

            html += '</ul>';
            return html;
        },

        /**
         * Get depth indicator dashes
         *
         * @param {number} depth
         * @returns {string}
         */
        getDepthIndicator: function (depth) {
            if (depth === 0) return '';

            let dashes = '<span class="ecs64-depth-indicator">';
            for (let i = 0; i < depth; i++) {
                dashes += '<span class="ecs64-depth-dash">—</span>';
            }
            dashes += '</span>';
            return dashes;
        },

        /**
         * Initialize jQuery UI Sortable
         */
        initSortable: function () {
            const self = this;

            // Nie inicjalizuj sortable w trybie parent-only
            if (this.parentOnlyMode) {
                return;
            }

            $('.ecs64-category-list').sortable({
                handle: '.ecs64-drag-handle',
                items: '> .ecs64-category-item',
                placeholder: 'ui-sortable-placeholder',
                connectWith: '.ecs64-category-list',
                tolerance: 'pointer',
                cursor: 'grabbing',
                opacity: 0.8,
                revert: 200,

                start: function (event, ui) {
                    ui.placeholder.height(ui.item.find('.ecs64-category-row').outerHeight());
                },

                stop: function (event, ui) {
                    self.handleSortStop(ui.item);
                },

                receive: function (event, ui) {
                    // Category moved to different parent
                    const $item = ui.item;
                    const $newParentList = $(event.target);
                    const $newParentItem = $newParentList.closest('.ecs64-category-item');
                    const newParentId = $newParentItem.length ? parseInt($newParentItem.data('id'), 10) : 0;

                    $item.attr('data-parent', newParentId);
                }
            });
        },

        /**
         * Destroy jQuery UI Sortable
         */
        destroySortable: function () {
            const $lists = $('.ecs64-category-list');
            if ($lists.length && $lists.sortable('instance')) {
                $lists.sortable('destroy');
            }
        },

        /**
         * Handle sort stop event
         *
         * @param {jQuery} $item
         */
        handleSortStop: function ($item) {
            const self = this;
            const categoryId = parseInt($item.data('id'), 10);
            const $parentList = $item.parent();
            const $parentItem = $parentList.closest('.ecs64-category-item');
            const newParentId = $parentItem.length ? parseInt($parentItem.data('id'), 10) : 0;
            const newOrder = $item.index();

            // Update all siblings order
            const orderData = [];
            $parentList.children('.ecs64-category-item').each(function (index) {
                orderData.push({
                    id: parseInt($(this).data('id'), 10),
                    parent: newParentId,
                    order: index
                });
            });

            // Just update the moved item
            this.updateOrder(categoryId, 'set_parent', newOrder, newParentId);
        },

        /**
         * Move category via button click
         *
         * @param {number} categoryId
         * @param {string} action
         */
        moveCategory: function (categoryId, action) {
            this.updateOrder(categoryId, action);
        },

        /**
         * Update category order via REST API
         *
         * @param {number} categoryId
         * @param {string} action
         * @param {number} newOrder
         * @param {number} newParent
         */
        updateOrder: function (categoryId, action, newOrder = null, newParent = null) {
            const self = this;

            if (this.isLoading) return;
            this.isLoading = true;

            this.showStatus('saving', ecs64Data.i18n.saving);

            const data = {
                category_id: categoryId,
                action: action
            };

            if (newOrder !== null) {
                data.new_order = newOrder;
            }

            if (newParent !== null) {
                data.new_parent = newParent;
            }

            $.ajax({
                url: ecs64Data.restUrl + 'update-order',
                method: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', ecs64Data.nonce);
                },
                data: data,
                success: function (response) {
                    if (response.success) {
                        self.categories = response.categories;
                        self.renderTree();
                        self.initSortable();
                        self.showStatus('saved', ecs64Data.i18n.saved);
                    } else {
                        self.showStatus('error', response.message || ecs64Data.i18n.error);
                    }
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || ecs64Data.i18n.error;
                    self.showStatus('error', message);
                },
                complete: function () {
                    self.isLoading = false;
                }
            });
        },

        /**
         * Show status message
         *
         * @param {string} type - 'saving', 'saved', 'error'
         * @param {string} message
         */
        showStatus: function (type, message) {
            const $status = $('#ecs64-status');

            $status
                .removeClass('saving saved error visible')
                .addClass(type)
                .text(message)
                .addClass('visible');

            if (type === 'saved') {
                setTimeout(function () {
                    $status.removeClass('visible');
                }, 2000);
            }
        },

        /**
         * Escape HTML entities
         *
         * @param {string} text
         * @returns {string}
         */
        escapeHtml: function (text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // Initialize when document is ready
    $(document).ready(function () {
        if (typeof ecs64Data !== 'undefined') {
            ECS64.init();
        }
    });

})(jQuery);
