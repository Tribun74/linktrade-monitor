/**
 * Linktrade Monitor - Admin JavaScript
 */
(function($) {
    'use strict';

    const Linktrade = {
        /**
         * Initialisierung
         */
        init: function() {
            this.bindEvents();
            this.initCategoryToggle();
        },

        /**
         * Events binden
         */
        bindEvents: function() {
            // Neuer Link Button
            $(document).on('click', '#linktrade-add-link', this.openAddModal.bind(this));

            // Modal schließen
            $(document).on('click', '.linktrade-modal-close', this.closeModal.bind(this));
            $(document).on('click', '.linktrade-modal', function(e) {
                if ($(e.target).hasClass('linktrade-modal')) {
                    Linktrade.closeModal();
                }
            });

            // ESC zum Schließen
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    Linktrade.closeModal();
                }
            });

            // Formular absenden
            $(document).on('submit', '#linktrade-link-form', this.saveLink.bind(this));

            // Speichern & weiteren anlegen
            $(document).on('click', '#linktrade-save-and-new', this.saveAndNew.bind(this));

            // Link bearbeiten
            $(document).on('click', '.linktrade-edit', this.editLink.bind(this));

            // Link löschen
            $(document).on('click', '.linktrade-delete', this.deleteLink.bind(this));

            // Backlink-Code kopieren
            $(document).on('click', '.copy-backlink-code', this.copyBacklinkCode.bind(this));

            // Link prüfen
            $(document).on('click', '.linktrade-check', this.checkLink.bind(this));

            // Filter
            $(document).on('change', '#linktrade-filter-category, #linktrade-filter-status', this.filterLinks.bind(this));
            $(document).on('keyup', '#linktrade-search', this.debounce(this.filterLinks.bind(this), 300));

            // Kategorie-Toggle für Formular
            $(document).on('change', '#category', this.toggleCategoryFields.bind(this));

            // CSV Export
            $(document).on('click', '#linktrade-export-csv', this.exportCSV.bind(this));

            // Alle Links prüfen
            $(document).on('click', '#linktrade-check-all', this.checkAllLinks.bind(this));
        },

        /**
         * Kategorie-Felder Toggle initialisieren
         */
        initCategoryToggle: function() {
            this.toggleCategoryFields();
        },

        /**
         * Kategorie-abhängige Felder ein/ausblenden
         */
        toggleCategoryFields: function() {
            const category = $('#category').val();
            const $tauschFields = $('.tausch-fields');
            const $kaufFields = $('.kauf-fields');

            if (category === 'tausch') {
                $tauschFields.slideDown(200);
            } else {
                $tauschFields.slideUp(200);
            }

            if (category === 'kauf') {
                $kaufFields.slideDown(200);
            } else {
                $kaufFields.slideUp(200);
            }
        },

        /**
         * Modal für neuen Link öffnen
         */
        openAddModal: function(e) {
            e.preventDefault();
            this.resetForm();
            $('#linktrade-modal-title').text('Neuen Link eintragen');
            $('#linktrade-modal').fadeIn(200);
            $('#partner_name').focus();
        },

        /**
         * Modal schließen
         */
        closeModal: function() {
            $('#linktrade-modal').fadeOut(200);
            this.resetForm();
        },

        /**
         * Formular zurücksetzen
         */
        resetForm: function() {
            const $form = $('#linktrade-link-form');
            $form[0].reset();
            $form.find('input[name="id"]').val('');
            $form.find('input[name="start_date"]').val(new Date().toISOString().split('T')[0]);
            this.toggleCategoryFields();
        },

        /**
         * Link speichern
         */
        saveLink: function(e) {
            e.preventDefault();

            const $form = $('#linktrade-link-form');
            const $button = $form.find('button[type="submit"]');
            const originalText = $button.text();

            $button.prop('disabled', true).text(linktrade.strings.saving);

            $.ajax({
                url: linktrade.ajax_url,
                type: 'POST',
                data: {
                    action: 'linktrade_save_link',
                    nonce: linktrade.nonce,
                    ...$form.serializeObject()
                },
                success: function(response) {
                    if (response.success) {
                        Linktrade.showNotice(response.data.message, 'success');
                        Linktrade.closeModal();
                        location.reload();
                    } else {
                        Linktrade.showNotice(response.data.message || linktrade.strings.error, 'error');
                    }
                },
                error: function() {
                    Linktrade.showNotice(linktrade.strings.error, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Speichern & weiteren Link anlegen
         */
        saveAndNew: function(e) {
            e.preventDefault();

            const $form = $('#linktrade-link-form');
            const $button = $(e.currentTarget);
            const originalText = $button.text();

            $button.prop('disabled', true).text(linktrade.strings.saving);

            $.ajax({
                url: linktrade.ajax_url,
                type: 'POST',
                data: {
                    action: 'linktrade_save_link',
                    nonce: linktrade.nonce,
                    ...$form.serializeObject()
                },
                success: function(response) {
                    if (response.success) {
                        Linktrade.showNotice(response.data.message, 'success');
                        Linktrade.resetForm();
                        $('#partner_name').focus();
                    } else {
                        Linktrade.showNotice(response.data.message || linktrade.strings.error, 'error');
                    }
                },
                error: function() {
                    Linktrade.showNotice(linktrade.strings.error, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Link bearbeiten
         */
        editLink: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $row = $button.closest('tr');
            const id = $button.data('id') || $row.data('id');

            if (!id) {
                Linktrade.showNotice('Keine Link-ID gefunden.', 'error');
                return;
            }

            // Link-Daten per AJAX laden
            $.ajax({
                url: linktrade.ajax_url,
                type: 'POST',
                data: {
                    action: 'linktrade_get_link',
                    nonce: linktrade.nonce,
                    id: id
                },
                beforeSend: function() {
                    $button.addClass('linktrade-loading');
                },
                success: function(response) {
                    if (response.success && response.data.link) {
                        Linktrade.populateForm(response.data.link);
                        $('#linktrade-modal-title').text('Link bearbeiten');
                        $('#linktrade-modal').fadeIn(200);
                    } else {
                        Linktrade.showNotice(response.data?.message || 'Link nicht gefunden.', 'error');
                    }
                },
                error: function() {
                    Linktrade.showNotice('Fehler beim Laden der Link-Daten.', 'error');
                },
                complete: function() {
                    $button.removeClass('linktrade-loading');
                }
            });
        },

        /**
         * Formular mit Link-Daten befüllen
         */
        populateForm: function(link) {
            const $form = $('#linktrade-link-form');

            // Alle Felder zurücksetzen
            $form[0].reset();

            // ID setzen
            $form.find('input[name="id"]').val(link.id);

            // Partner-Grunddaten
            $form.find('input[name="partner_name"]').val(link.partner_name || '');
            $form.find('input[name="partner_contact"]').val(link.partner_contact || '');
            $form.find('select[name="category"]').val(link.category || 'tausch').trigger('change');

            // Link-Details
            $form.find('input[name="partner_url"]').val(link.partner_url || '');
            $form.find('input[name="target_url"]').val(link.target_url || '');
            $form.find('input[name="anchor_text"]').val(link.anchor_text || '');

            // Gegenlink (bei Tausch)
            $form.find('input[name="backlink_url"]').val(link.backlink_url || '');
            $form.find('input[name="backlink_target"]').val(link.backlink_target || '');
            $form.find('input[name="backlink_anchor"]').val(link.backlink_anchor || '');

            // Partner-Bewertung
            $form.find('input[name="domain_rating"]').val(link.domain_rating || '');
            $form.find('input[name="domain_authority"]').val(link.domain_authority || '');
            $form.find('input[name="monthly_traffic"]').val(link.monthly_traffic || '');
            $form.find('input[name="spam_score"]').val(link.spam_score || '');
            $form.find('input[name="niche"]').val(link.niche || '');
            $form.find('input[name="relevance_score"]').val(link.relevance_score || 50);

            // Laufzeit & Kosten
            $form.find('input[name="start_date"]').val(link.start_date || '');
            $form.find('input[name="end_date"]').val(link.end_date || '');
            $form.find('input[name="price"]').val(link.price || '');
            $form.find('select[name="price_period"]').val(link.price_period || 'once');

            // Notizen
            $form.find('textarea[name="notes"]').val(link.notes || '');
        },

        /**
         * Link löschen
         */
        deleteLink: function(e) {
            e.preventDefault();

            if (!confirm(linktrade.strings.confirm_delete)) {
                return;
            }

            const $row = $(e.currentTarget).closest('tr');
            const id = $row.data('id');
            const $button = $(e.currentTarget);

            $button.addClass('linktrade-loading');

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
                },
                error: function() {
                    Linktrade.showNotice(linktrade.strings.error, 'error');
                },
                complete: function() {
                    $button.removeClass('linktrade-loading');
                }
            });
        },

        /**
         * Backlink-Code kopieren
         */
        copyBacklinkCode: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $input = $button.siblings('input');
            const code = $input.val();

            navigator.clipboard.writeText(code).then(function() {
                $button.addClass('copied');
                $button.find('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');

                setTimeout(function() {
                    $button.removeClass('copied');
                    $button.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-clipboard');
                }, 2000);
            }).catch(function() {
                // Fallback für ältere Browser
                $input.select();
                document.execCommand('copy');
                $button.addClass('copied');
                setTimeout(function() {
                    $button.removeClass('copied');
                }, 2000);
            });
        },

        /**
         * Einzelnen Link prüfen
         */
        checkLink: function(e) {
            e.preventDefault();

            const $row = $(e.currentTarget).closest('tr');
            const id = $row.data('id');
            const $button = $(e.currentTarget);
            const $statusCell = $row.find('.status').parent();

            $button.find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-update linktrade-spin');

            $.ajax({
                url: linktrade.ajax_url,
                type: 'POST',
                data: {
                    action: 'linktrade_check_link',
                    nonce: linktrade.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        const result = response.data.result;
                        const statusClass = result.status;
                        let statusText = result.status.charAt(0).toUpperCase() + result.status.slice(1);

                        if (result.status === 'warning' && result.is_nofollow) {
                            statusText = 'nofollow';
                        } else if (result.status === 'warning' && result.is_noindex) {
                            statusText = 'noindex';
                        }

                        $statusCell.html(
                            '<span class="status ' + statusClass + '">' +
                            '<span class="status-dot"></span>' + statusText +
                            '</span>'
                        );

                        Linktrade.showNotice(response.data.message, 'success');
                    } else {
                        Linktrade.showNotice(response.data.message || linktrade.strings.error, 'error');
                    }
                },
                error: function() {
                    Linktrade.showNotice(linktrade.strings.error, 'error');
                },
                complete: function() {
                    $button.find('.dashicons').removeClass('dashicons-update linktrade-spin').addClass('dashicons-visibility');
                }
            });
        },

        /**
         * Links filtern
         */
        filterLinks: function() {
            const search = $('#linktrade-search').val();
            const category = $('#linktrade-filter-category').val();
            const status = $('#linktrade-filter-status').val();

            // Client-seitiges Filtern für bessere UX
            $('.linktrade-table tbody tr').each(function() {
                const $row = $(this);
                const text = $row.text().toLowerCase();
                const rowCategory = $row.find('.category-tag').attr('class')?.replace('category-tag', '').trim() || '';
                const rowStatus = $row.find('.status').attr('class')?.replace('status', '').replace('status-dot', '').trim() || '';

                let show = true;

                if (search && text.indexOf(search.toLowerCase()) === -1) {
                    show = false;
                }

                if (category && rowCategory !== category) {
                    show = false;
                }

                if (status && rowStatus.indexOf(status) === -1) {
                    show = false;
                }

                $row.toggle(show);
            });
        },

        /**
         * CSV Export
         */
        exportCSV: function(e) {
            e.preventDefault();
            // TODO: Implementieren
            alert('CSV Export wird in einer späteren Version implementiert.');
        },

        /**
         * Alle Links prüfen
         */
        checkAllLinks: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);

            if (!confirm('Alle Links prüfen? Dies kann einige Zeit dauern.')) {
                return;
            }

            $button.prop('disabled', true).find('.dashicons').addClass('linktrade-spin');

            // TODO: Batch-Check implementieren
            setTimeout(function() {
                $button.prop('disabled', false).find('.dashicons').removeClass('linktrade-spin');
                alert('Batch-Check wird in einer späteren Version implementiert.');
            }, 1000);
        },

        /**
         * Benachrichtigung anzeigen
         */
        showNotice: function(message, type) {
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.linktrade-content').prepend($notice);

            // Auto-dismiss nach 5 Sekunden
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Debounce Helper
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    /**
     * jQuery serializeObject Plugin
     */
    $.fn.serializeObject = function() {
        const obj = {};
        const arr = this.serializeArray();
        $.each(arr, function() {
            if (obj[this.name]) {
                if (!obj[this.name].push) {
                    obj[this.name] = [obj[this.name]];
                }
                obj[this.name].push(this.value || '');
            } else {
                obj[this.name] = this.value || '';
            }
        });
        return obj;
    };

    /**
     * CSS für Spinner Animation (inline hinzufügen)
     */
    $('<style>')
        .text('.linktrade-spin { animation: linktrade-spin 1s linear infinite; }')
        .appendTo('head');

    // Init
    $(document).ready(function() {
        Linktrade.init();
    });

})(jQuery);
