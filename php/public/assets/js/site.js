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

        // Auto-dismiss flash messages after 5 seconds.
        setTimeout(function () {
            $('.flash').slideUp(300, function () { $(this).remove(); });
        }, 5000);
    });
})(jQuery);
