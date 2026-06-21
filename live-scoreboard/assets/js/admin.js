/* global lsbAdmin, jQuery */
(function ($) {
    'use strict';
    $(function () {
        $(document).on('click', '.lsb-admin-delete', function () {
            if (!confirm('Delete this match and all its scores?')) return;
            var $btn = $(this);
            $.post(lsbAdmin.ajaxUrl, {
                action: 'lsb_delete_match',
                nonce: lsbAdmin.nonce,
                match_id: $btn.data('id'),
            }).done(function (res) {
                if (res.success) {
                    $btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
                } else {
                    alert('Error: ' + (res.data && res.data.message ? res.data.message : 'Unknown'));
                }
            });
        });
    });
}(jQuery));
