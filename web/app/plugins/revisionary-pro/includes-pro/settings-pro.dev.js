jQuery(document).ready(function ($) {
    var revisionaryHideSpinners = function () {
        $("#rvy-features .waiting").hide();
        $("#rvy-features .button-secondary").prop('disabled', false);
        $("#rvy-features div.pp-key-hint span").hide();
    }

    var revisionaryRedrawActStatus = function (data, txtStatus) {
        revisionaryHideSpinners();

        var msg = '';
        var captions = jQuery.parseJSON(revisionarySettings.keyStatus.replace(/&quot;/g, '"'));

        if (typeof data != 'object' || typeof data['license'] == 'undefined') {
            msg = revisionarySettings.errCaption;
            $("#rvy-features .pp-key-active").hide();
            $("#rvy-features .pp-key-expired").hide();
        } else if (!jQuery.inArray(data['license'], captions)) {
            msg = revisionarySettings.errCaption;
        } else {
            msg = captions[data['license']];

            if (('valid' == data['license'])) {
                revisionarySettings.activated = 1;
                $("#rvy-features #activation-button").html(revisionarySettings.deactivateCaption);
                $("#rvy-features #renewal-button").hide();
                $("#rvy-features #edd_key").hide();
                $("#rvy-features .pp-key-inactive").hide();
                $("#rvy-features .pp-key-active").show();
                $("#rvy-features .pp-key-expired").hide();
                $("#rvy-features .pp-update-link").show();
            } else if ('expired' == data['license']) {
                revisionarySettings.activated = 1;
                revisionarySettings.expired = 1;
                $("#rvy-features #activation-button").html(revisionarySettings.deactivateCaption);
                $("#rvy-features #renewal-button").show();
                $("#rvy-features #edd_key").show();
                $("#rvy-features .pp-key-active").hide();
                $("#rvy-features .pp-key-expired").show();
                $("#rvy-features .pp-update-link").show();
                $("#rvy-features .pp-key-inactive").show();
            } else {
                revisionarySettings.activated = 0;
                $("#rvy-features #activation-button").html(revisionarySettings.activateCaption);
                $("#rvy-features #edd_key").show();
                $("#rvy-features #edd_key").val('');
                $("span.pp-key-active").hide();
                $("span.pp-key-expired").hide();
                $("span.pp-key-warning").hide();
                $("span.pp-update-link").hide();
                $("#rvy-features .pp-key-inactive").show();
            }
        }

        $("#rvy-features #activation-status").html(msg).show();

        if ('valid' == data['license'])
            $("#rvy-features #activation-reload").show();
    }

    var revisionaryAjaxConnectFailure = function (data, txtStatus) {
        revisionaryHideSpinners();
        $("#rvy-features #activation-status").html(revisionarySettings.noConnectCaption);
        return;
    }

    // click handlers for activate / deactivate button
    $('#rvy-features #activation-button').on('click', function (e) {
        $(this).closest('td').find('.waiting').show();
        $(this).prop('disabled', true);

        e.preventDefault();
        e.stopPropagation();

        if (1 == revisionarySettings.activated) {
            var data = {'rvy_ajax_settings': 'deactivate_key'};
            $.ajax({
                url: revisionarySettings.deactivateURL,
                data: data,
                dataType: "json",
                cache: false,
                success: revisionaryRedrawActStatus,
                error: revisionaryAjaxConnectFailure
            });
        } else {
            var key = jQuery.trim($("#rvy-features #edd_key").val());

            if (!key) {
                $("#rvy-features #activation-status").html(revisionarySettings.noEntryCaption);
                revisionaryHideSpinners();
                return;
            }

            var data = {'rvy_ajax_settings': 'activate_key', 'key': key};
            $.ajax({
                url: revisionarySettings.activateURL,
                data: data,
                dataType: "json",
                cache: false,
                success: revisionaryRedrawActStatus,
                error: revisionaryAjaxConnectFailure
            });
        }
    });
});