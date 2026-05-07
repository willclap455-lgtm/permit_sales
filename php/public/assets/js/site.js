/* PermitSales — small jQuery layer for progressive enhancement. */
(function ($) {
    'use strict';

    $(function () {
        // Toggle hidden forms (e.g. "+ Add" buttons in the dashboard).
        $('[data-toggle]').on('click', function () {
            var name = $(this).data('toggle');
            var $form = $('[data-form="' + name + '"]');
            $form.prop('hidden', !$form.prop('hidden'));
            if (!$form.prop('hidden')) {
                $form.find('input, select, textarea').first().trigger('focus');
            }
        });

        // Confirm destructive actions before submitting.
        $('form[data-confirm]').on('submit', function (e) {
            var msg = $(this).data('confirm') || 'Are you sure?';
            if (!window.confirm(msg)) {
                e.preventDefault();
            }
        });

        // Visual selection state for permit-tier radios.
        $('.permit-tier--select input[type="radio"]').on('change', function () {
            var name = $(this).attr('name');
            $('input[name="' + name + '"]').each(function () {
                $(this).closest('.permit-tier--select').toggleClass('is-checked', this.checked);
            });
        }).filter(':checked').trigger('change');

        // Format card numbers as the user types: 4-4-4-4 grouping.
        $('input[name="card_number"]').on('input', function () {
            var digits = this.value.replace(/\D+/g, '').slice(0, 19);
            this.value = digits.replace(/(.{4})/g, '$1 ').trim();
        });

        // Light client-side validation feedback for the contact form.
        $('#contact-form').on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $status = $('#contact-status');
            var name = $form.find('[name="name"]').val().trim();
            var email = $form.find('[name="email"]').val().trim();
            var msg = $form.find('[name="message"]').val().trim();
            if (!name || !email || !msg) {
                $status.removeClass().addClass('form-status is-error').text('Please fill out name, email, and message.');
                return;
            }
            if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
                $status.removeClass().addClass('form-status is-error').text('Please enter a valid email.');
                return;
            }
            $status.removeClass().addClass('form-status').text('Thanks, ' + name.split(' ')[0] + '. A specialist will be in touch shortly.');
            $form[0].reset();
        });

        // Mailing address: collapse the form behind a saved-address
        // summary + Edit button so customers fill it out once and reuse
        // it on every subsequent order.
        $('[data-mailing-edit]').on('click', function () {
            var $fieldset = $(this).closest('[data-mailing-address]');
            $fieldset.find('[data-mailing-summary]').attr('hidden', true);
            $fieldset.find('[data-mailing-fields]').removeAttr('hidden')
                .find('input').first().trigger('focus');
        });

        // Clickable table rows: any <tr> with [data-href] navigates to
        // that URL when clicked or activated via keyboard. Used in the
        // admin Clients table so the whole row is the edit affordance.
        $(document).on('click', '[data-href]', function (e) {
            // Don't hijack clicks on links/buttons/forms inside the row.
            var $target = $(e.target);
            if ($target.closest('a, button, input, select, textarea, label, form').length) {
                return;
            }
            var href = $(this).data('href');
            if (href) {
                window.location.href = href;
            }
        });
        $(document).on('keydown', '[data-href]', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                var $target = $(e.target);
                if ($target.closest('a, button, input, select, textarea').length) {
                    return;
                }
                e.preventDefault();
                var href = $(this).data('href');
                if (href) {
                    window.location.href = href;
                }
            }
        });

        // Live customer search on the admin console.
        //
        // The Customers section ships with a normal GET form that
        // works without JavaScript. With jQuery available, we hijack
        // the search input so each keystroke fires `$.ajax()` against
        // the JSON endpoint at /admin/customers and re-renders the
        // results table without a full page reload.
        var $customerSearch = $('[data-customer-search]');
        if ($customerSearch.length) {
            var $form    = $customerSearch.find('[data-customer-search-form]');
            var $input   = $customerSearch.find('[data-customer-search-input]');
            var $tbody   = $customerSearch.find('[data-customer-search-results]');
            var $summary = $customerSearch.find('[data-customer-search-summary]');
            var $clear   = $customerSearch.find('[data-customer-search-clear]');

            // Track the in-flight request so a slow response can never
            // overwrite a newer one.
            var pendingXhr = null;
            var debounceTimer = null;

            function escapeHtml(value) {
                if (value === null || typeof value === 'undefined') {
                    return '';
                }
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function renderRows(results, query) {
                if (!results.length) {
                    var emptyMsg = query
                        ? 'No customers match that search.'
                        : 'No customers yet.';
                    $tbody.html(
                        '<tr><td colspan="6" class="entity-list__empty">' +
                        escapeHtml(emptyMsg) +
                        '</td></tr>'
                    );
                    return;
                }
                var rows = results.map(function (u) {
                    var phone = u.phone
                        ? escapeHtml(u.phone)
                        : '<span class="muted small">—</span>';
                    var role = escapeHtml(u.role || '');
                    var lastLogin = u.last_login_at
                        ? escapeHtml(u.last_login_at)
                        : 'never';
                    return '' +
                        '<tr>' +
                            '<td><strong>' + escapeHtml(u.full_name) + '</strong></td>' +
                            '<td>' + escapeHtml(u.email) + '</td>' +
                            '<td>' + phone + '</td>' +
                            '<td><span class="pill pill--' + role + '">' + role + '</span></td>' +
                            '<td class="muted">' + lastLogin + '</td>' +
                            '<td class="muted">' + escapeHtml(u.created_at) + '</td>' +
                        '</tr>';
                });
                $tbody.html(rows.join(''));
            }

            function renderSummary(count, query) {
                var text;
                if (query) {
                    text = count + ' match' + (count === 1 ? '' : 'es') +
                           ' for \u201C' + query + '\u201D';
                } else {
                    text = 'Showing the most recent ' + count;
                }
                $summary.text(text);
            }

            function runSearch(query) {
                if (pendingXhr && pendingXhr.readyState !== 4) {
                    pendingXhr.abort();
                }
                pendingXhr = $.ajax({
                    url: '/admin/customers',
                    method: 'GET',
                    dataType: 'json',
                    cache: false,
                    data: { customer_q: query },
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).done(function (payload) {
                    if (!payload || !$.isArray(payload.results)) {
                        return;
                    }
                    renderRows(payload.results, payload.query || '');
                    renderSummary(payload.count || 0, payload.query || '');
                    $clear.prop('hidden', !query);
                }).fail(function (jqXhr, status) {
                    if (status === 'abort') {
                        return;
                    }
                    $summary.text('Search failed — please try again.');
                });
            }

            // Trigger an AJAX search whenever the admin types. We
            // listen on `keyup` (and `search`/`input` for paste +
            // clear-button affordances) and debounce slightly so we
            // don't fire one request per character on fast typists.
            $input.on('keyup search input', function () {
                var query = $(this).val();
                if (typeof query !== 'string') {
                    query = '';
                }
                query = query.replace(/^\s+|\s+$/g, '');

                if (debounceTimer) {
                    window.clearTimeout(debounceTimer);
                }
                debounceTimer = window.setTimeout(function () {
                    runSearch(query);
                }, 150);
            });

            // Submitting the form (Enter key, "Search" button) should
            // run the AJAX search instead of doing a full page reload.
            $form.on('submit', function (e) {
                e.preventDefault();
                if (debounceTimer) {
                    window.clearTimeout(debounceTimer);
                    debounceTimer = null;
                }
                var query = ($input.val() || '').replace(/^\s+|\s+$/g, '');
                runSearch(query);
            });

            // The Clear link normally navigates to /admin#customers;
            // intercept it so we can reset the input + table inline.
            $clear.on('click', function (e) {
                e.preventDefault();
                $input.val('').trigger('focus');
                runSearch('');
            });
        }

        // Auto-dismiss flash messages after 5 seconds.
        setTimeout(function () {
            $('.flash').slideUp(300, function () { $(this).remove(); });
        }, 5000);
    });
})(jQuery);
