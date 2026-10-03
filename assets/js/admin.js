/**
 * Linktrade Monitor - Admin JavaScript
 *
 * @package Linktrade_Monitor
 * @version 1.4.0
 */

(function($) {
    'use strict';

    const t = linktrade.strings;

    const Linktrade = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initCategoryToggle();
        },

        /**
         * Bind all events
         */
        bindEvents: function() {
            $(document).on('click', '#linktrade-add-link', this.showAddForm);
            $(document).on('submit', '#linktrade-add-form', this.saveLink);
            $(document).on('click', '.linktrade-delete', this.deleteLink);
            $(document).on('click', '.linktrade-edit', this.editLink);
            $(document).on('submit', '#linktrade-edit-form', this.updateLink);
            $(document).on('click', '.linktrade-check-now', this.checkNow);
            $(document).on('click', '.linktrade-history', this.showHistory);
            $(document).on('click', '.linktrade-message', this.showMessage);
            $(document).on('click', '.linktrade-copy', this.copyText);
            $(document).on('click', '.linktrade-find-backlink', this.findBacklink);
            $(document).on('click', '.linktrade-use-backlink', this.useBacklink);
            $(document).on('click', '#linktrade-export-csv', this.exportCSV);
            $(document).on('submit', '#linktrade-import-form', this.importCSV);
            $(document).on('click', '#linktrade-test-mail', this.testMail);

            // Close modal: button, click on the backdrop, Escape key.
            $(document).on('click', '.linktrade-modal-close', this.closeModal);
            $(document).on('click', '.linktrade-modal', function(e) {
                if ($(e.target).hasClass('linktrade-modal')) {
                    Linktrade.closeModal();
                }
            });
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    Linktrade.closeModal();
                }
                Linktrade.trapFocus(e);
            });

            // Category change (show/hide exchange fields)
            $(document).on('change', '#category, #edit_category', this.toggleExchangeFields);

            // Selection and actions for several links
            $(document).on('change', '#linktrade-select-all', function() {
                $('.linktrade-row-check').prop('checked', $(this).prop('checked'));
            });
            $(document).on('click', '#linktrade-bulk-apply', this.bulkApply);

            // Outgoing links of the whole site
            $(document).on('click', '#linktrade-scan-outgoing', this.scanOutgoing);

            // Review hint
            $(document).on('click', '#linktrade-review-dismiss', function() {
                $('#linktrade-review').remove();
                $.post(linktrade.ajax_url, { action: 'linktrade_dismiss_review', nonce: linktrade.nonce });
            });
        },

        /**
         * One AJAX helper for every request, so no click ends in silence.
         */
        request: function(data, onSuccess, onDone) {
            data.nonce = linktrade.nonce;

            return $.ajax({
                url: linktrade.ajax_url,
                type: 'POST',
                data: data
            }).done(function(response) {
                if (response && response.success) {
                    onSuccess(response.data || {});
                } else {
                    Linktrade.showNotice((response && response.data && response.data.message) || t.error, 'error');
                }
            }).fail(function() {
                Linktrade.showNotice(t.session, 'error');
            }).always(function() {
                if (onDone) {
                    onDone();
                }
            });
        },

        formData: function($form) {
            return $form.serializeArray().reduce(function(obj, item) {
                obj[item.name] = item.value;
                return obj;
            }, {});
        },

        initCategoryToggle: function() {
            const $category = $('#category');
            if ($category.length && $category.val() === 'exchange') {
                $category.closest('form').addClass('show-exchange-fields');
            }
        },

        toggleExchangeFields: function() {
            $(this).closest('form').toggleClass('show-exchange-fields', $(this).val() === 'exchange');

            // A directory entry or mention rarely promises a followed link.
            if (this.id === 'category') {
                $('#follow_agreed').prop('checked', $(this).val() !== 'free');
            }
        },

        showAddForm: function(e) {
            e.preventDefault();
            window.location.href = linktrade.add_url;
        },

        /**
         * Save new link
         */
        saveLink: function(e) {
            e.preventDefault();

            const $form = $(this);
            const $button = $form.find('button[type="submit"]');
            const originalText = $button.text();
            let saved = false;

            $button.prop('disabled', true).text(t.saving);

            const data = Linktrade.formData($form);
            data.action = 'linktrade_save_link';

            Linktrade.request(data, function(result) {
                saved = true;
                Linktrade.showNotice(result.message, 'success', true);
                setTimeout(function() {
                    window.location.href = linktrade.links_url;
                }, 2500);
            }, function() {
                if (!saved) {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Edit link - load data into modal
         */
        editLink: function(e) {
            e.preventDefault();
            Linktrade.lastFocus = this;

            Linktrade.request({ action: 'linktrade_get_link', id: $(this).data('id') }, function(result) {
                Linktrade.populateEditForm(result.link);
                $('#linktrade-edit-modal').show();
                $('#edit_partner_name').trigger('focus');
            });
        },

        field: function(id, name, label, value, type, extra) {
            return '<div class="form-row">' +
                '<label for="' + id + '">' + Linktrade.escapeHtml(label) + '</label>' +
                '<input type="' + (type || 'text') + '" id="' + id + '" name="' + name + '" value="' + Linktrade.escapeHtml(value == null ? '' : String(value)) + '" ' + (extra || '') + '>' +
                '</div>';
        },

        /**
         * Populate edit form with link data
         */
        populateEditForm: function(link) {
            const $form = $('#linktrade-edit-form');
            const esc = Linktrade.escapeHtml;
            const f = Linktrade.field;
            const option = function(value, label) {
                return '<option value="' + value + '"' + (link.category === value ? ' selected' : '') + '>' + esc(label) + '</option>';
            };
            const date = function(value) {
                return (value && value !== '0000-00-00') ? value : '';
            };

            const html =
                '<input type="hidden" id="edit_id" name="id" value="' + parseInt(link.id, 10) + '">' +

                '<div class="form-section"><h4>' + esc(t.partner_info) + '</h4>' +
                    f('edit_partner_name', 'partner_name', t.partner_name + ' *', link.partner_name, 'text', 'required') +
                    f('edit_partner_contact', 'partner_contact', t.partner_contact, link.partner_contact, 'email') +
                    '<div class="form-row"><label for="edit_category">' + esc(t.category) + ' *</label>' +
                        '<select id="edit_category" name="category" required>' +
                            option('exchange', t.cat_exchange) + option('paid', t.cat_paid) + option('free', t.cat_free) +
                        '</select></div>' +
                '</div>' +

                '<div class="form-section"><h4>' + esc(t.incoming) + '</h4>' +
                    f('edit_partner_url', 'partner_url', t.partner_url + ' *', link.partner_url, 'url', 'required') +
                    f('edit_target_url', 'target_url', t.target_url + ' *', link.target_url, 'url', 'required') +
                    f('edit_anchor_text', 'anchor_text', t.agreed_anchor, link.anchor_text) +
                    '<div class="form-row checkbox-row"><label><input type="checkbox" name="follow_agreed" value="1"' +
                        ((link.follow_agreed === undefined || link.follow_agreed === null || parseInt(link.follow_agreed, 10) === 1) ? ' checked' : '') + '> ' + esc(t.follow_agreed) + '</label></div>' +
                '</div>' +

                '<div class="form-section exchange-fields"><h4>' + esc(t.outgoing) + '</h4>' +
                    f('edit_backlink_url', 'backlink_url', t.backlink_url, link.backlink_url, 'url') +
                    f('edit_backlink_target', 'backlink_target', t.backlink_target, link.backlink_target, 'url') +
                    f('edit_backlink_anchor', 'backlink_anchor', t.anchor, link.backlink_anchor) +
                '</div>' +

                '<div class="form-section"><h4>' + esc(t.more) + '</h4>' +
                    f('edit_start_date', 'start_date', t.start_date, date(link.start_date), 'date') +
                    f('edit_end_date', 'end_date', t.end_date, date(link.end_date), 'date') +
                    '<div class="form-row-grid">' +
                        f('edit_domain_rating', 'domain_rating', t.partner_dr, parseInt(link.domain_rating, 10) || '', 'number', 'min="0" max="100"') +
                        f('edit_my_domain_rating', 'my_domain_rating', t.my_dr, parseInt(link.my_domain_rating, 10) || '', 'number', 'min="0" max="100"') +
                    '</div>' +
                    '<div class="form-row"><label for="edit_notes">' + esc(t.notes) + '</label>' +
                        '<textarea id="edit_notes" name="notes" rows="3">' + esc(link.notes || '') + '</textarea></div>' +
                '</div>' +

                '<div class="form-actions"><button type="submit" class="button button-primary">' + esc(t.save_changes) + '</button></div>';

            $form.html(html).toggleClass('show-exchange-fields', link.category === 'exchange');
        },

        /**
         * Update link
         */
        updateLink: function(e) {
            e.preventDefault();

            const $form = $(this);
            const $button = $form.find('button[type="submit"]');
            let saved = false;

            $button.prop('disabled', true).text(t.saving);

            const data = Linktrade.formData($form);
            data.action = 'linktrade_save_link';

            Linktrade.request(data, function(result) {
                saved = true;
                Linktrade.closeModal();
                Linktrade.showNotice(result.message, 'success', true);
                setTimeout(function() {
                    location.reload();
                }, 2500);
            }, function() {
                if (!saved) {
                    $button.prop('disabled', false).text(t.save_changes);
                }
            });
        },

        /**
         * Delete link
         */
        deleteLink: function(e) {
            e.preventDefault();

            if (!confirm(t.confirm_delete)) {
                return;
            }

            const $row = $(this).closest('tr');

            Linktrade.request({ action: 'linktrade_delete_link', id: $(this).data('id') }, function(result) {
                $row.fadeOut(300, function() {
                    $(this).remove();
                });
                Linktrade.showNotice(result.message, 'success');
            });
        },

        /**
         * Check one link now
         */
        checkNow: function(e) {
            e.preventDefault();

            const $button = $(this);
            if ($button.prop('disabled')) {
                return;
            }
            $button.prop('disabled', true).addClass('is-busy');
            Linktrade.showNotice(t.checking, 'info');

            Linktrade.request({ action: 'linktrade_check_now', id: $button.data('id') }, function(result) {
                Linktrade.showNotice(result.message, 'success', true);
                setTimeout(function() {
                    location.reload();
                }, 3000);
            }, function() {
                $button.prop('disabled', false).removeClass('is-busy');
            });
        },

        table: function(headers, rows) {
            const esc = Linktrade.escapeHtml;
            let html = '<div class="linktrade-table-scroll"><table class="linktrade-table">';
            if (headers) {
                html += '<thead><tr>';
                headers.forEach(function(h) {
                    html += '<th>' + esc(h) + '</th>';
                });
                html += '</tr></thead>';
            }
            html += '<tbody>';
            rows.forEach(function(row) {
                html += '<tr>';
                row.forEach(function(cell) {
                    html += '<td>' + esc(String(cell == null ? '' : cell)) + '</td>';
                });
                html += '</tr>';
            });
            return html + '</tbody></table></div>';
        },

        openInfo: function(title, html) {
            Linktrade.lastFocus = document.activeElement;
            $('#linktrade-info-title').text(title);
            $('#linktrade-info-body').html(html);
            $('#linktrade-info-modal').show().find('.linktrade-modal-close').trigger('focus');
        },

        /**
         * History of one link
         */
        showHistory: function(e) {
            e.preventDefault();

            Linktrade.request({ action: 'linktrade_get_history', id: $(this).data('id') }, function(result) {
                const esc = Linktrade.escapeHtml;
                let html = '<h4>' + esc(t.changes) + '</h4>';

                if (result.changes.length) {
                    html += Linktrade.table(null, result.changes.map(function(c) {
                        return [c.date, c.what, c.from + ' → ' + c.to];
                    }));
                } else {
                    html += '<p class="description">' + esc(t.no_changes) + '</p>';
                }

                html += '<h4>' + esc(t.checks) + '</h4>';
                if (result.checks.length) {
                    html += Linktrade.table(null, result.checks.map(function(c) {
                        return [c.date, c.what, c.code || '', c.flags, c.note];
                    }));
                } else {
                    html += '<p class="description">' + esc(t.no_checks) + '</p>';
                }

                if (result.proof) {
                    html += '<h4>' + esc(t.proof) + '</h4><p class="description">' + esc(t.proof_hint) + '</p>' +
                        '<textarea class="linktrade-message-text" rows="7" readonly>' + esc(result.proof) + '</textarea>' +
                        '<p><button type="button" class="button linktrade-copy">' + esc(t.copy) + '</button></p>';
                }

                Linktrade.openInfo(t.history_for + ': ' + result.partner, html);
            });
        },

        /**
         * Message to the partner, for copying
         */
        showMessage: function(e) {
            e.preventDefault();

            Linktrade.request({ action: 'linktrade_get_message', id: $(this).data('id') }, function(result) {
                const esc = Linktrade.escapeHtml;
                let html = '<p class="description">' + esc(t.message_hint) + '</p>';

                if (result.contact) {
                    html += '<p><strong>' + esc(t.contact) + ':</strong> ' + esc(result.contact) + '</p>';
                }
                html += '<textarea class="linktrade-message-text" rows="12">' + esc(result.message) + '</textarea>';
                html += '<p><button type="button" class="button button-primary linktrade-copy">' + esc(t.copy) + '</button></p>';

                Linktrade.openInfo(t.message_for + ': ' + result.partner, html);
            });
        },

        copyText: function(e) {
            e.preventDefault();

            const $button = $(this);
            const area = $button.closest('.linktrade-modal-body').find('.linktrade-message-text')[0];
            const done = function() {
                $button.text(t.copied);
                setTimeout(function() {
                    $button.text(t.copy);
                }, 1500);
            };

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(area.value).then(done);
            } else {
                area.select();
                document.execCommand('copy');
                done();
            }
        },

        /**
         * Find links to the partner in the site's own content
         */
        findBacklink: function(e) {
            e.preventDefault();

            const $button = $(this);
            const $form = $button.closest('form');
            const $result = $form.find('.linktrade-find-result');
            const originalText = $button.text();

            $button.prop('disabled', true).text(t.searching);
            $result.empty();

            Linktrade.request({
                action: 'linktrade_find_backlink',
                partner_url: $form.find('[name="partner_url"]').val()
            }, function(result) {
                const esc = Linktrade.escapeHtml;
                let html = '<ul class="linktrade-find-list">';
                result.links.forEach(function(link) {
                    html += '<li><span><strong>' + esc(link.title) + '</strong><br><small>' + esc(link.page) + ' → ' + esc(link.target) + '</small></span>' +
                        '<button type="button" class="button button-small linktrade-use-backlink" data-page="' + esc(link.page) + '" data-target="' + esc(link.target) + '">' + esc(t.use_this) + '</button></li>';
                });
                $result.html(html + '</ul>');
            }, function() {
                $button.prop('disabled', false).text(originalText);
            });
        },

        useBacklink: function(e) {
            e.preventDefault();

            const $form = $(this).closest('form');
            $form.find('[name="backlink_url"]').val($(this).data('page'));
            $form.find('[name="backlink_target"]').val($(this).data('target'));
            $form.find('.linktrade-find-result').empty();
        },

        /**
         * Apply an action to the selected links
         */
        bulkApply: function(e) {
            e.preventDefault();

            const action = $('#linktrade-bulk-action').val();
            const ids = $('.linktrade-row-check:checked').map(function() {
                return $(this).val();
            }).get();

            if (!ids.length) {
                Linktrade.showNotice(t.none_selected, 'error');
                return;
            }
            if (!action) {
                Linktrade.showNotice(t.choose_action, 'error');
                return;
            }

            const $button = $(this).prop('disabled', true);

            if (action === 'delete') {
                if (!confirm(t.confirm_bulk.replace('%d', ids.length))) {
                    $button.prop('disabled', false);
                    return;
                }
                Linktrade.request({ action: 'linktrade_bulk_delete', ids: ids }, function(result) {
                    Linktrade.showNotice(result.message, 'success', true);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                }, function() {
                    $button.prop('disabled', false);
                });
                return;
            }

            // Check one after the other, so no request runs into a time limit.
            let index = 0;
            const next = function() {
                if (index >= ids.length) {
                    Linktrade.showNotice(t.bulk_done, 'success', true);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                    return;
                }
                Linktrade.showNotice(t.bulk_progress.replace('%1$d', index + 1).replace('%2$d', ids.length), 'info', true);
                $.post(linktrade.ajax_url, { action: 'linktrade_check_now', nonce: linktrade.nonce, id: ids[index] })
                    .always(function() {
                        index++;
                        next();
                    });
            };
            next();
        },

        /**
         * Search the whole site for links to other websites
         */
        scanOutgoing: function(e) {
            e.preventDefault();

            const $button = $(this).prop('disabled', true);
            const $progress = $('#linktrade-scan-progress');
            const $result = $('#linktrade-scan-result').empty();
            const domains = {};

            const finish = function() {
                $button.prop('disabled', false);
                $progress.text('');

                const list = Object.keys(domains).map(function(host) {
                    return domains[host];
                }).sort(function(a, b) {
                    return b.count - a.count;
                });

                if (!list.length) {
                    $result.append($('<p class="description"></p>').text(t.scan_none));
                    return;
                }

                const esc = Linktrade.escapeHtml;
                let html = '<div class="linktrade-table-scroll"><table class="linktrade-table"><thead><tr><th>' + esc(t.scan_domain) + '</th><th>' + esc(t.scan_count) + '</th><th>' + esc(t.scan_example) + '</th><th></th></tr></thead><tbody>';
                list.slice(0, 200).forEach(function(d) {
                    const url = linktrade.add_url + '&lt_name=' + encodeURIComponent(d.host) + '&lt_page=' + encodeURIComponent(d.page) + '&lt_target=' + encodeURIComponent(d.target);
                    html += '<tr><td><strong>' + esc(d.host) + '</strong></td><td>' + parseInt(d.count, 10) + '</td><td><small>' + esc(d.title) + '<br>' + esc(d.target) + '</small></td>' +
                        '<td><a class="button button-small" href="' + esc(url) + '">' + esc(t.scan_add) + '</a></td></tr>';
                });
                $result.html(html + '</tbody></table></div>');
            };

            const run = function(batch) {
                $.post(linktrade.ajax_url, { action: 'linktrade_scan_outgoing', nonce: linktrade.nonce, batch: batch }).done(function(response) {
                    if (!response || !response.success) {
                        Linktrade.showNotice((response && response.data && response.data.message) || t.error, 'error');
                        $button.prop('disabled', false);
                        $progress.text('');
                        return;
                    }
                    response.data.domains.forEach(function(d) {
                        if (domains[d.host]) {
                            domains[d.host].count += d.count;
                        } else {
                            domains[d.host] = d;
                        }
                    });
                    $progress.text(t.scan_progress.replace('%1$d', response.data.batch).replace('%2$d', Math.max(1, response.data.pages)));
                    if (response.data.more && batch < 200) {
                        run(batch + 1);
                    } else {
                        finish();
                    }
                }).fail(function() {
                    Linktrade.showNotice(t.session, 'error');
                    $button.prop('disabled', false);
                    $progress.text('');
                });
            };

            run(1);
        },

        closeModal: function() {
            const wasOpen = $('.linktrade-modal:visible').length > 0;
            $('.linktrade-modal').hide();

            // Hand the keyboard focus back to where the window was opened from.
            if (wasOpen && Linktrade.lastFocus && document.body.contains(Linktrade.lastFocus)) {
                Linktrade.lastFocus.focus();
            }
            Linktrade.lastFocus = null;
        },

        /**
         * Keep the Tab key inside an open window.
         */
        trapFocus: function(e) {
            if (e.key !== 'Tab') {
                return;
            }
            const $modal = $('.linktrade-modal:visible');
            if (!$modal.length) {
                return;
            }
            const $items = $modal.find('a[href], button:not([disabled]), input:not([type="hidden"]):not([disabled]), select, textarea').filter(':visible');
            if (!$items.length) {
                return;
            }
            const first = $items[0];
            const last = $items[$items.length - 1];

            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            } else if (!$modal[0].contains(document.activeElement)) {
                e.preventDefault();
                first.focus();
            }
        },

        /**
         * Show notice. Text only: server messages are never treated as HTML.
         */
        showNotice: function(message, type, keep) {
            $('.linktrade-notice.is-temp').remove();

            const $notice = $('<div class="linktrade-notice is-temp notice-' + type + '" role="status"><p></p></div>');
            $notice.find('p').text(message);
            $('.linktrade-content').prepend($notice);

            if ($notice[0].scrollIntoView && $notice[0].getBoundingClientRect().top < 0) {
                $notice[0].scrollIntoView({ block: 'center' });
            }

            if (type === 'error' || keep) {
                return;
            }
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 4000);
        },

        escapeHtml: function(text) {
            if (!text) {
                return '';
            }
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text).replace(/[&<>"']/g, function(m) {
                return map[m];
            });
        },

        debounce: function(func, wait) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        },

        /**
         * Export links to CSV
         */
        exportCSV: function(e) {
            e.preventDefault();

            const $button = $(this);
            const originalHtml = $button.html();

            $button.prop('disabled', true).text(t.exporting);

            Linktrade.request({ action: 'linktrade_export_csv' }, function(result) {
                // The byte order mark makes spreadsheet programs read umlauts correctly.
                const blob = new Blob(['﻿' + result.csv], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = result.filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);

                Linktrade.showNotice(t.exported.replace('%d', result.count), 'success');
            }, function() {
                $button.prop('disabled', false).html(originalHtml);
            });
        },

        /**
         * Import links from CSV: first a preview, then the import itself.
         */
        importCSV: function(e) {
            e.preventDefault();

            const $form = $(this);
            const $button = $form.find('button[type="submit"]');
            const originalHtml = $button.html();
            const $result = $('#linktrade-import-result');
            const fileInput = document.getElementById('import_file');
            const esc = Linktrade.escapeHtml;

            if (!fileInput.files.length) {
                Linktrade.showNotice(t.select_file, 'error');
                return;
            }

            const box = function(type, message, problems) {
                const $box = $('<div class="linktrade-notice notice-' + type + '"><p></p></div>');
                $box.find('p').text(message);
                if (problems && problems.length) {
                    const $list = $('<ul class="linktrade-import-problems"></ul>');
                    problems.forEach(function(problem) {
                        $list.append($('<li></li>').text(problem));
                    });
                    $box.append($list);
                }
                $result.append($box);
            };

            const send = function(preview, done) {
                const formData = new FormData($form[0]);
                formData.append('action', 'linktrade_import_csv');
                if (preview) {
                    formData.append('preview', '1');
                }
                $button.prop('disabled', true).text(preview ? t.checking_file : t.importing);

                $.ajax({
                    url: linktrade.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false
                }).done(function(response) {
                    if (response && response.success) {
                        done(response.data);
                    } else {
                        box('error', (response && response.data && response.data.message) || t.import_failed);
                    }
                }).fail(function() {
                    box('error', t.import_failed);
                }).always(function() {
                    $button.prop('disabled', false).html(originalHtml);
                });
            };

            $result.empty();

            send(true, function(data) {
                box(data.errors > 0 ? 'warning' : 'info', data.message, data.problems);

                if (!data.imported) {
                    $result.append($('<p class="description"></p>').text(t.import_nothing));
                    return;
                }

                let html = '<h4>' + esc(t.preview_title) + '</h4>' + Linktrade.table(
                    [t.partner_name, t.partner_url, t.target_url, t.category],
                    data.rows.map(function(row) {
                        return [row.name, row.page, row.target, row.category];
                    })
                );
                html += '<p><button type="button" class="button button-primary" id="linktrade-import-confirm">' + esc(t.import_now) + ' (' + parseInt(data.imported, 10) + ')</button> ' +
                    '<button type="button" class="button" id="linktrade-import-cancel">' + esc(t.import_cancel) + '</button></p>';
                $result.append(html);

                $('#linktrade-import-cancel').one('click', function() {
                    $result.empty();
                    fileInput.value = '';
                });

                $('#linktrade-import-confirm').one('click', function() {
                    $result.empty();
                    send(false, function(result) {
                        box(result.errors > 0 ? 'warning' : 'success', result.message, result.problems);
                        fileInput.value = '';
                        // With problems the list stays on screen. Without, show the result.
                        if (result.imported > 0 && !result.errors) {
                            setTimeout(function() {
                                window.location.href = linktrade.links_url;
                            }, 2500);
                        }
                    });
                });
            });
        },

        /**
         * Send a test mail to the notification address
         */
        testMail: function(e) {
            e.preventDefault();

            const $button = $(this);
            const originalText = $button.text();
            $button.prop('disabled', true).text(t.sending);

            Linktrade.request({ action: 'linktrade_test_mail' }, function(result) {
                Linktrade.showNotice(result.message, 'success', true);
            }, function() {
                $button.prop('disabled', false).text(originalText);
            });
        }
    };

    $(document).ready(function() {
        Linktrade.init();
    });

})(jQuery);
