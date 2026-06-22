/* global lsbAdmin, jQuery */
(function ($) {
    'use strict';
    $(function () {
        $('#lsb-add-package').on('click', function () {
            var index = $('#lsb-package-list .lsb-package-row').length;
            var html = ''
                + '<div class="lsb-package-row">'
                + '<label><span>Package name</span><input type="text" name="package_name[]" value=""></label>'
                + '<label><span>Days</span><input type="number" min="1" name="package_days[]" value="30"></label>'
                + '<label><span>Price label</span><input type="text" name="package_price[]" value="" placeholder="Example: BDT 500"></label>'
                + '<label><span>Description</span><textarea name="package_description[]" rows="2"></textarea></label>'
                + '<div class="lsb-package-row-actions">'
                + '<label class="lsb-checkbox-row"><input type="checkbox" name="package_active[' + index + ']" value="1" checked><span>Active</span></label>'
                + '<button type="button" class="button button-link-delete lsb-remove-package">Remove</button>'
                + '</div>'
                + '</div>';
            $('#lsb-package-list').append(html);
        });

        $(document).on('click', '.lsb-remove-package', function () {
            $(this).closest('.lsb-package-row').remove();
        });

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
