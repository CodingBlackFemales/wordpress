function presspermitLoadAgentsJS(id_sfx, agent_type, context, agent_id, selection_only) {
    jQuery(document).ready(function ($) {
        $("#select_agents_" + id_sfx).on('click', function (e) {
            e.preventDefault();
            presspermitSelectAgents(id_sfx, agent_type);
        });

        $("#agent_results_" + id_sfx).on('dblclick', function (e) {
            e.preventDefault();
            presspermitSelectAgents(id_sfx, agent_type);
        });

        if (selection_only != true) {
            $("#unselect_agents_" + id_sfx).on('click', function (event) {
                presspermitUnselectListAgents(id_sfx);
            });
            $("#" + id_sfx).on('dblclick', function (event) {
                presspermitUnselectListAgents(id_sfx);
            });

            $("#agent_results_" + id_sfx).DynamicListbox({
                ajaxurl: PPAgentSelect.ajaxurl,
                agent_type: agent_type,
                search_id: 'agent_search_text_' + id_sfx,
                results_id: 'agent_results_' + id_sfx,
                button_id: 'agent_submit_' + id_sfx,
                ajaxhandler: PPAgentSelect.ajaxhandler,
                pp_context: context,
                topic: id_sfx,
                agent_id: agent_id
            });
        }

        if ('member' == id_sfx) {
            $("#pp_member_start").datepicker({dateFormat: "yy/mm/dd", minDate: new Date()});
            $("#pp_member_end").datepicker({dateFormat: "yy/mm/dd", minDate: new Date()});
        }
    });
}

function presspermitBuildSelectionCSV(list_id, $) {
    var s = '';

    $("#" + list_id + " option").each(function () {
        var startdate, enddate;
        ($(this).attr("data-startdate")) ? startdate = $(this).attr("data-startdate") : startdate = '';
        ($(this).attr("data-enddate")) ? enddate = $(this).attr("data-enddate") : enddate = '';

        s = s + $(this).attr("value") + '|' + startdate + '|' + enddate + ',';
    });

    $("#" + list_id + "_csv").attr("value", s);
}

function presspermitSelectAgents(id_sfx, select_into, hierarchical) {
    jQuery(document).ready(function ($) {
        var selector = '#' + CSS.escape('v2_agent_search_text_' + id_sfx + '::' + select_into);
        if ($(selector).length) {
            $(selector).select2('data').forEach(function (item) {
                if ($("#" + id_sfx + " option[value='" + item.id + "']").length === 0) {
                    var startdate = $('input[name="pp_member_start"]').datepicker("getDate");
                    startdate = startdate ? $.datepicker.formatDate('yy-mm-dd', startdate) : '';

                    var enddate = $('input[name="pp_member_end"]').datepicker("getDate");
                    enddate = enddate ? $.datepicker.formatDate('yy-mm-dd', enddate) : '';

                    var caption = item.text;
                    if (startdate || enddate) {
                        caption += ' (' + (startdate || '') + ' - ' + (enddate || '') + ')';
                    }

                    var attribs = '';
                    if (startdate) attribs += ' data-startdate="' + startdate + '"';
                    if (enddate) attribs += ' data-enddate="' + enddate + '"';

                    $("#" + id_sfx).append('<option value="' + item.id + '" title="' + caption + '" class="pp-new-selection"' + attribs + '>' + caption + '</option>');
                }
            });
            $(selector).val(null).trigger('change');
        } else {
            $("#agent_results_" + id_sfx + " option:selected").each(function (i) {
                if ($("#" + id_sfx + " option[value='" + $(this).attr("value") + "']").length == 0) {
                    var startdate = $('input[name="pp_member_start"]').datepicker("getDate");
                    startdate = $.datepicker.formatDate('yy-mm-dd', startdate);

                    var enddate = $('input[name="pp_member_end"]').datepicker("getDate");
                    enddate = $.datepicker.formatDate('yy-mm-dd', enddate);

                    if (startdate || enddate) {
                        var caption = $(this).html() + ' (' + startdate + ' - ' + enddate + ')';

                        var attribs = '';
                        if (startdate)
                            attribs += ' data-startdate="' + startdate + '"';

                        if (enddate)
                            attribs += ' data-enddate="' + enddate + '"';

                        $("#" + id_sfx).append('<option value="' + $(this).attr("value") + '" title="' + caption + '" class="pp-new-selection"' + attribs + '>' + caption + '</option>');  // todo: translated "[start] to [end]" caption
                        $(this).remove();
                    } else {
                        $("#" + id_sfx).append('<option value="' + $(this).attr("value") + '" title="' + $(this).html() + '" class="pp-new-selection">' + $(this).html() + '</option>');
                        $(this).remove();
                    }
                }
            });
        }

        presspermitBuildSelectionCSV(id_sfx, $);
    });
}

function presspermitUnselectListAgents(id_sfx) {
    jQuery(document).ready(function ($) {
        $("#" + id_sfx + " option:selected").each(function () {
            var caption = $(this).html();
            var arr = caption.split(' (');
            if (arr.length > 1)
                caption = arr[0];

            $("#agent_results_" + id_sfx).append('<option value="' + $(this).attr("value") + '" class="pp-new-selection">' + caption + '</option>');
            $(this).remove();
        });

        presspermitBuildSelectionCSV(id_sfx, $);
    });
}

