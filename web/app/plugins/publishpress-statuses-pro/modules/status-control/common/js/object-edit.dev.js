jQuery(document).ready(function ($) {
    var orig_visibility_html = $('#post-visibility-display').html();

    // to retain last OK'd selection:
    //var selected_vis = $('#hidden-post-visibility').val(), selected_password = $('#hidden_post_password').val(), selected_sticky = $('#hidden-post-sticky').is(':checked');

    function ppRefreshVisibilityUI() {
        var pvSelect = $('#post-visibility-select');

        if ($('input:radio:checked', pvSelect).val() != 'public') {
            $('#sticky').prop('checked', false);
            $('#sticky-span').hide();
        } else {
            $('#sticky-span').show();
        }

        if ($('input:radio:checked', pvSelect).val() != 'password') {
            $('#password-span').hide();
        } else {
            $('#password-span').show();
        }
    }

    $('.cancel-post-visibility', '#post-visibility-select').on('click', function (e) {
        $('#post-visibility-select').slideUp("fast");

        $('#visibility-radio-' + $('#hidden-post-visibility').val()).prop('checked', true);

        $('#post_password').val($('#hidden_post_password').val());

        $('#sticky').prop('checked', $('#hidden_post_sticky').val());

        $('#post-visibility-display').html(orig_visibility_html);

        $('#post_status').val($('#hidden_post_status').val());

        $('.edit-visibility', '#visibility').show();
        $('.visibility-customize').hide();

        //ppUpdateText();
        updateStatusCaptions();

        updateStatusDropdownElements();

        e.preventDefault();

        return false;
    });

    $('.save-post-visibility', '#post-visibility-select').on('click', function (e) {
        var selected_vis = $('#post-visibility-select input[type="radio"]:checked').val();

        selected_sticky = $('#sticky').is(':checked');
        selected_password = $('#post_password').val();

        $('#post-visibility-select').slideUp("fast");
        $('.edit-visibility', '#visibility').show();

        if (selected_sticky) {
            var sticky = 'Sticky';
        } else {
            var sticky = '';
        }

        if ((typeof postL10n != 'undefined') && (postL10n.published != '')) {
            $('#post-visibility-display').html(postL10n[selected_vis + sticky]);
            //$('.visibility-customize').hide();
        } else {
            var __ = wp.i18n.__;
            var visLabel = '';

            switch ( selected_vis ) {
                case 'public':
                    visLabel = $( '#sticky' ).prop( 'checked' ) ? __( 'Public, Sticky' ) : __( 'Public' );
                    break;
                case 'private':
                    visLabel = __( 'Private' );
                    break;
                case 'password':
                    visLabel = __( 'Password Protected' );
                    break;
                default:
                    visLabel = $('#post-visibility-select input[type="radio"]:checked').next('label').html();
            }

            $('#post-visibility-display').text( visLabel );
        }

        updateStatusDropdownElements();
        updateStatusCaptions();

        if ($('#pp-propagate-privacy').is(':checked')) {
            if ('public' == selected_vis)
                selected_vis = '';

            var optid = $('#ch_post-visibility-select input[name="ch_visibility"][value="' + selected_vis + '"]').attr('id');
            if (optid) {
                $('#' + optid).prop('checked', true);
                $('#ch_post-visibility-display').html($('#' + optid).next('label').html().trim());
                $('#ch_visibility').show();
            }
        }

        e.preventDefault();

        return false;
    });

    $('.ch_edit-visibility', '#ch_visibility').on('click', function () {
        if ($('#ch_post-visibility-select').is(":hidden")) {
            $('#ch_post-visibility-select').slideDown("fast");
            $(this).hide();
        }
        return false;
    });

    $('.ch_save-post-visibility', '#ch_post-visibility-select').on('click', function () {
        var pvSelect = $('#ch_post-visibility-select');

        var status = $('input[type="radio"]:checked', pvSelect).val();
        if (!status)
            status = '_manual';

        pvSelect.slideUp("fast");

        var statusCaption;

        if ((typeof postL10n[status] != 'undefined') && (postL10n[status] != '')) {
            statusCaption = postL10n[status];
        } else {
            if (status == '_manual') {
                statusCaption = '(manual)'; // @todo: translate
            } else {
                statusCaption = $('#ch_post-visibility-select input[value="' + status + '"]').next('label').html().trim();
            }
        }

        $('#ch_post-visibility-display').html(statusCaption);

        $('.ch_edit-visibility', '#ch_visibility').show();

        return false;
    });

    $('input:radio', '#post-visibility-select').on('change', function () {
        ppRefreshVisibilityUI();
    });

    $('.pp-edit-ch-visibility').on('click', function () {
        $('#ch_visibility').show();
        $('.ch_edit-visibility').click();
        return false;
    });

    $('.edit-visibility', '#visibility').on('click', function () {
        if ($('#post-visibility-select').is(":hidden")) {
            ppRefreshVisibilityUI();
            $('#post-visibility-select').slideDown('fast');
            $(this).hide();
        }
        return false;
    });

    $('input[name="ch_visibility"]').on('click', function () {
        $('input[name="pp_propagate_visibility"]').parent().toggle($(this).val() != '');
    });
});