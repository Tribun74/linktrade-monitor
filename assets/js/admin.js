/**
 * Linktrade Monitor - Admin JavaScript
 *
 * @package Linktrade_Monitor
 * @version 1.1.2
 */

(function($) {
    'use strict';

    const Linktrade = {
        /**
         * Initialize
         */
        init: function() {
            console.log('LinkTrade Admin JS initialized');
            this.bindEvents();
            this.initCategoryToggle();
        },

        /**
         * Bind all events
         */
        bindEvents: function() {
            // Add link button
            $(document).on('click', '#linktrade-add-link', this.showAddForm);

            // Save link (add form)
            $(document).on('submit', '#linktrade-add-form', this.saveLink);

            // Delete link
            $(document).on('click', '.linktrade-delete', this.deleteLink);

            // Edit link
            $(document).on('click', '.linktrade-edit', this.editLink);

            // Save edited link
            $(document).on('submit', '#linktrade-edit-form', this.updateLink);

            // Close modal
            $(document).on('click', '.linktrade-modal-close', this.closeModal);
            $(document).on('click', '.linktrade-modal', function(e) {
                if ($(e.target).hasClass('linktrade-modal')) {
                    Linktrade.closeModal();
                }
            });

            // Category change (show/hide exchange fields)
            $(document).on('change', '#category, #edit_category', this.toggleExchangeFields);

            // Filter and search
            $(document).on('change', '#linktrade-filter-category, #linktrade-filter-status', this.filterLinks);
            $(document).on('keyup', '#linktrade-search', this.debounce(this.searchLinks, 300));
        },

        /**
         * Initialize category toggle for exchange fields
         */
        initCategoryToggle: function() {
            const $category = $('#category');
            if ($category.length && $category.val() === 'exchange') {
                $category.closest('form').addClass('show-exchange-fields');
            }
        },

        /**
         * Toggle exchange fields visibility
         */
        toggleExchangeFields: function() {
            const $form = $(this).closest('form');
            if ($(this).val() === 'exchange') {
                $form.addClass('show-exchange-fields');
            } else {
                $form.removeClass('show-exchange-fields');
            }
        },

        /**
         * Show add form tab
         */
        showAddForm: function(e) {
            e.preventDefault();
            window.location.href = linktrade.ajax_url.replace('admin-ajax.php', 'admin.php?page=linktrade-monitor&tab=add');
        },

        /**
         * Save new link
         */
        saveLink: function(e) {
            e.preventDefault();

            const $form = $(this);
            const $button = $form.find('button[type="submit"]');
            const originalText = $button.text();

            $button.prop('disabled', true).text(linktrade.strings.saving);

            $.ajax({
                url: linktrade.ajax_url,
                type: 'POST',
                data: {
                    action: 'linktrade_save_link',
                    nonce: linktrade.nonce,
                    ...$form.serializeArray().reduce((obj, item) => {
                        obj[item.name] = item.value;
                        return obj;
                    }, {})
                },
                success: function(response) {
                    if (response.success) {
                        Linktrade.showNotice(response.data.message, 'success');
                        setTimeout(function() {
                            window.location.href = linktrade.ajax_url.replace('admin-ajax.php', 'admin.php?page=linktrade-monitor&tab=links');
                        }, 1000);
                    } else {
                        if (response.data.limit_reached) {
                            Linktrade.showNotice(response.data.message, 'error');
                        } else {
                            Linktrade.showNotice(response.data.message || linktrade.strings.error, 'error');
                        }
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    Linktrade.showNotice(linktrade.strings.error, 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Edit link - load data into modal
         */
        editLink: function(e) {
            e.preventDefault();

            const id = $(this).data('id');
            const $modal = $('#linktrade-edit-modal');

            $.ajax({
                url: linktrade.ajax_url,
                type: 'POST',
                data: {
                    action: 'linktrade_get_link',
                    nonce: linktrade.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        const link = response.data.link;
                        Linktrade.populateEditForm(link);
                        $modal.show();
                    } else {
                        Linktrade.showNotice(response.data.message, 'error');
                    }
                }
            });
        },

        /**
         * Populate edit form with link data
         */
        populateEditForm: function(link) {
            const $form = $('#linktrade-edit-form');

            // Build form HTML
            let html = `
                <input type="hidden" id="edit_id" name="id" value="${link.id}">

                <div class="form-section">
                    <h4>Partner Information</h4>
                    <div class="form-row">
                        <label for="edit_partner_name">Partner Name *</label>
                        <input type="text" id="edit_partner_name" name="partner_name" value="${this.escapeHtml(link.partner_name || '')}" required>
                    </div>
                    <div class="form-row">
                        <label for="edit_partner_contact">Contact (Email)</label>
                        <input type="email" id="edit_partner_contact" name="partner_contact" value="${this.escapeHtml(link.partner_contact || '')}">
                    </div>
                    <div class="form-row">
                        <label for="edit_category">Category *</label>
                        <select id="edit_category" name="category" required>
                            <option value="exchange" ${link.category === 'exchange' ? 'selected' : ''}>Link Exchange</option>
                            <option value="paid" ${link.category === 'paid' ? 'selected' : ''}>Paid Link</option>
                            <option value="free" ${link.category === 'free' ? 'selected' : ''}>Free</option>
                        </select>
                    </div>
                </div>

                <div class="form-section">
                    <h4>Incoming Link</h4>
                    <div class="form-row">
                        <label for="edit_partner_url">Page URL *</label>
                        <input type="url" id="edit_partner_url" name="partner_url" value="${this.escapeHtml(link.partner_url || '')}" required>
                    </div>
                    <div class="form-row">
                        <label for="edit_target_url">Your URL *</label>
                        <input type="url" id="edit_target_url" name="target_url" value="${this.escapeHtml(link.target_url || '')}" required>
                    </div>
                    <div class="form-row">
                        <label for="edit_anchor_text">Anchor Text</label>
                        <input type="text" id="edit_anchor_text" name="anchor_text" value="${this.escapeHtml(link.anchor_text || '')}">
                    </div>
                </div>

                <div class="form-section exchange-fields" ${link.category === 'exchange' ? 'style="display:block"' : ''}>
                    <h4>Reciprocal Link</h4>
                    <div class="form-row">
                        <label for="edit_backlink_url">Your Page</label>
                        <input type="url" id="edit_backlink_url" name="backlink_url" value="${this.escapeHtml(link.backlink_url || '')}">
                    </div>
                    <div class="form-row">
                        <label for="edit_backlink_target">Partner URL</label>
                        <input type="url" id="edit_backlink_target" name="backlink_target" value="${this.escapeHtml(link.backlink_target || '')}">
                    </div>
                    <div class="form-row">
                        <label for="edit_backlink_anchor">Anchor Text</label>
                        <input type="text" id="edit_backlink_anchor" name="backlink_anchor" value="${this.escapeHtml(link.backlink_anchor || '')}">
                    </div>
                </div>

                <div class="form-section">
                    <h4>Additional Info</h4>
                    <div class="form-row">
                        <label for="edit_start_date">Start Date</label>
                        <input type="date" id="edit_start_date" name="start_date" value="${link.start_date || ''}">
                    </div>
                    <div class="form-row">
                        <label for="edit_end_date">Expiration Date</label>
                        <input type="date" id="edit_end_date" name="end_date" value="${link.end_date || ''}">
                    </div>
                    <div class="form-row-grid">
                        <div class="form-row">
                            <label for="edit_domain_rating">Partner DR</label>
                            <input type="number" id="edit_domain_rating" name="domain_rating" value="${link.domain_rating || ''}" min="0" max="100">
                        </div>
                        <div class="form-row">
                            <label for="edit_my_domain_rating">My DR</label>
                            <input type="number" id="edit_my_domain_rating" name="my_domain_rating" value="${link.my_domain_rating || ''}" min="0" max="100">
                        </div>
                    </div>
                    <div class="form-row">
                        <label for="edit_notes">Notes</label>
                        <textarea id="edit_notes" name="notes" rows="3">${this.escapeHtml(link.notes || '')}</textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary">Save Changes</button>
                </div>
            `;

            $form.html(html);

            // Apply exchange fields visibility
            if (link.category === 'exchange') {
                $form.addClass('show-exchange-fields');
            }
        },

        /**
         * Update link
         */
        updateLink: function(e) {
            e.preventDefault();

            const $form = $(this);
            const $button = $form.find('button[type="submit"]');

            $button.prop('disabled', true).text(linktrade.strings.saving);

            $.ajax({
                url: linktrade.ajax_url,
                type: 'POST',
                data: {
                    action: 'linktrade_save_link',
                    nonce: linktrade.nonce,
                    ...$form.serializeArray().reduce((obj, item) => {
                        obj[item.name] = item.value;
                        return obj;
                    }, {})
                },
                success: function(response) {
                    if (response.success) {
                        Linktrade.showNotice(response.data.message, 'success');
                        Linktrade.closeModal();
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        Linktrade.showNotice(response.data.message || linktrade.strings.error, 'error');
                        $button.prop('disabled', false).text('Save Changes');
                    }
                },
                error: function() {
                    Linktrade.showNotice(linktrade.strings.error, 'error');
                    $button.prop('disabled', false).text('Save Changes');
                }
            });
        },

        /**
         * Delete link
         */
        deleteLink: function(e) {
            e.preventDefault();

            if (!confirm(linktrade.strings.confirm_delete)) {
                return;
            }

            const $row = $(this).closest('tr');
            const id = $row.data('id');

            $.ajax({
                url: linktrade.ajax_url,
                type: 'POST',
                data: {
                    action: 'linktrade_delete_link',
                    nonce: linktrade.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                        Linktrade.showNotice(response.data.message, 'success');
                    } else {
                        Linktrade.showNotice(response.data.message || linktrade.strings.error, 'error');
                    }
                }
            });
        },

        /**
         * Filter links
         */
        filterLinks: function() {
            const category = $('#linktrade-filter-category').val();
            const status = $('#linktrade-filter-status').val();
            const $rows = $('.linktrade-table tbody tr');

            $rows.each(function() {
                const $row = $(this);
                let show = true;

                if (category) {
                    const rowCategory = $row.find('.category-tag').attr('class');
                    if (rowCategory && !rowCategory.includes(category)) {
                        show = false;
                    }
                }

                if (status) {
                    const rowStatus = $row.find('.status').attr('class');
                    if (rowStatus && !rowStatus.includes(status)) {
                        show = false;
                    }
                }

                $row.toggle(show);
            });
        },

        /**
         * Search links
         */
        searchLinks: function() {
            const query = $('#linktrade-search').val().toLowerCase();
            const $rows = $('.linktrade-table tbody tr');

            $rows.each(function() {
                const $row = $(this);
                const text = $row.text().toLowerCase();
                $row.toggle(text.includes(query));
            });
        },

        /**
         * Close modal
         */
        closeModal: function() {
            $('.linktrade-modal').hide();
        },

        /**
         * Show notice
         */
        showNotice: function(message, type) {
            const $notice = $('<div class="linktrade-notice notice-' + type + '"><p>' + message + '</p></div>');
            $('.linktrade-content').prepend($notice);
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        },

        /**
         * Export links to CSV
         */
        exportCSV: function(e) {
            e.preventDefault();

            const $button = $(this);
            const originalText = $button.html();

            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Exporting...');

            $.ajax({
                url: linktrade.ajax_url,
                type: 'POST',
                data: {
                    action: 'linktrade_export_csv',
                    nonce: linktrade.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create download
                        const blob = new Blob([response.data.csv], { type: 'text/csv;charset=utf-8;' });
                        const url = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = response.data.filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(url);

                        Linktrade.showNotice('Exported ' + response.data.count + ' links.', 'success');
                    } else {
                        Linktrade.showNotice(response.data.message || 'Export failed.', 'error');
                    }
                    $button.prop('disabled', false).html(originalText);
                },
                error: function() {
                    Linktrade.showNotice('Export failed.', 'error');
                    $button.prop('disabled', false).html(originalText);
                }
            });
        },

        /**
         * Import links from CSV
         */
        importCSV: function(e) {
            e.preventDefault();

            const $form = $(this);
            const $button = $form.find('button[type="submit"]');
            const originalText = $button.html();
            const $result = $('#linktrade-import-result');

            const fileInput = document.getElementById('import_file');
            if (!fileInput.files.length) {
                Linktrade.showNotice('Please select a CSV file.', 'error');
                return;
            }

            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Importing...');
            $result.html('');

            const formData = new FormData($form[0]);
            formData.append('action', 'linktrade_import_csv');

            $.ajax({
                url: linktrade.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        let resultClass = 'success';
                        if (response.data.errors > 0) {
                            resultClass = 'warning';
                        }

                        $result.html('<div class="linktrade-notice notice-' + resultClass + '"><p>' + response.data.message + '</p></div>');

                        if (response.data.imported > 0) {
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        }
                    } else {
                        $result.html('<div class="linktrade-notice notice-error"><p>' + response.data.message + '</p></div>');
                    }
                    $button.prop('disabled', false).html(originalText);
                    fileInput.value = '';
                },
                error: function() {
                    $result.html('<div class="linktrade-notice notice-error"><p>Import failed. Please try again.</p></div>');
                    $button.prop('disabled', false).html(originalText);
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        Linktrade.init();

        // Bind export/import events
        $(document).on('click', '#linktrade-export-csv', Linktrade.exportCSV);
        $(document).on('submit', '#linktrade-import-form', Linktrade.importCSV);
    });

})(jQuery);
