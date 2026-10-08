jQuery(document).ready(function ($) {
    var ppCapabilitiesHideSpinners = function () {
        $(".publishpress-caps-manage .waiting").hide();
        $(".publishpress-caps-manage .button-secondary").prop('disabled', false);
    }

    var ppCapabilitiesRedrawActStatus = function (data, txtStatus) {
        ppCapabilitiesHideSpinners();

        var msg = '';
        var captions = jQuery.parseJSON(ppCapabilitiesSettings.keyStatus.replace(/&quot;/g, '"'));

        if (typeof data != 'object' || typeof data['license'] == 'undefined') {
            msg = ppCapabilitiesSettings.errCaption;
            $(".publishpress-caps-manage .pp-key-active").hide();
            $(".publishpress-caps-manage .pp-key-expired").hide();
        } else if (!jQuery.inArray(data['license'], captions)) {
            msg = ppCapabilitiesSettings.errCaption;
        } else {
            msg = captions[data['license']];

            if (('valid' == data['license'])) {
                ppCapabilitiesSettings.activated = 1;
                $(".publishpress-caps-manage #activation-button").html(ppCapabilitiesSettings.deactivateCaption);
                $(".publishpress-caps-manage #edd_key").hide();
                $(".publishpress-caps-manage .pp-key-inactive").hide();
                $(".publishpress-caps-manage .pp-key-active").show();
                $(".publishpress-caps-manage .pp-key-expired").hide();
            } else if ('expired' == data['license']) {
                ppCapabilitiesSettings.activated = 1;
                ppCapabilitiesSettings.expired = 1;
                $(".publishpress-caps-manage #activation-button").html(ppCapabilitiesSettings.deactivateCaption);
                $(".publishpress-caps-manage #edd_key").show();
                $(".publishpress-caps-manage .pp-key-active").hide();
                $(".publishpress-caps-manage .pp-key-expired").show();
                $(".publishpress-caps-manage .pp-key-inactive").show();
            } else {
                ppCapabilitiesSettings.activated = 0;
                $(".publishpress-caps-manage #activation-button").html(ppCapabilitiesSettings.activateCaption);
                $(".publishpress-caps-manage #edd_key").show();
                $(".publishpress-caps-manage #edd_key").val('');
                $("span.pp-key-active").hide();
                $("span.pp-key-expired").hide();
                $(".publishpress-caps-manage .pp-key-inactive").show();
            }
        }

        $(".publishpress-caps-manage #activation-status").html(msg).show();

        if ('valid' == data['license'])
            $(".publishpress-caps-manage #activation-reload").show();
    }

    var ppCapabilitiesAjaxConnectFailure = function (data, txtStatus) {
        ppCapabilitiesHideSpinners();
        $(".publishpress-caps-manage #activation-status").html(ppCapabilitiesSettings.noConnectCaption);
        return;
    }

    // click handlers for activate / deactivate button
    $('.publishpress-caps-manage #activation-button').bind('click', function (e) {
        $(this).closest('td').find('.waiting').show();
        $(this).prop('disabled', true);

        e.preventDefault();
        e.stopPropagation();

        if (1 == ppCapabilitiesSettings.activated) {
            var data = {'publishpress_caps_ajax_settings': 'deactivate_key'};
            $.ajax({
                url: ppCapabilitiesSettings.deactivateURL,
                data: data,
                dataType: "json",
                cache: false,
                success: ppCapabilitiesRedrawActStatus,
                error: ppCapabilitiesAjaxConnectFailure
            });
        } else {
            var key = jQuery.trim($(".publishpress-caps-manage #edd_key").val());

            if (!key) {
                $(".publishpress-caps-manage #activation-status").html(ppCapabilitiesSettings.noEntryCaption);
                ppCapabilitiesHideSpinners();
                return;
            }

            var data = {'publishpress_caps_ajax_settings': 'activate_key', 'key': key};
            $.ajax({
                url: ppCapabilitiesSettings.activateURL,
                data: data,
                dataType: "json",
                cache: false,
                success: ppCapabilitiesRedrawActStatus,
                error: ppCapabilitiesAjaxConnectFailure
            });
        }
    });
});