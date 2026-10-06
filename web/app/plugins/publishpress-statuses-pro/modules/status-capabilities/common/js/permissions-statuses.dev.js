jQuery(document).ready(function ($) {
    $('li.page-row:not(.disabled-status) div.enabled > a').on('click', function(e) {
        e.preventDefault();
        
        var status_name = $(this).closest('li.page-row').find('td.status_name div').html();

        var params = {
            action: 'pp_statuses_toggle_post_access',
            name: status_name,
            _wpnonce: PPPermissionsStatuses.ppNonce
        };
      
        jQuery.post(PPPermissionsStatuses.ajaxurl, params, function (retval) {
            if (typeof retval['data'] == 'undefined') {
                return;
            }
            
            var data = retval['data'];
      
            if (typeof data['statusName'] == 'undefined' || typeof data['display'] == 'undefined') {
                return;
            }

            $('#the_status_list li.page-row td.status_name div.status_name').filter(function() {
                return $(this).text() === data['statusName'];
            }).closest('tr').find('div.enabled > a').html(data['display']);

        }).fail(function() {
      
        });

        return false;
    });

    var globalSearchTimeout;
    $('#pp-roles-search').on('input', function () {
        clearTimeout(globalSearchTimeout);
        var searchTerm = $(this).val().toLowerCase().trim();

        if (searchTerm.length < 2) {
            clearGlobalSearch();
            return;
        }

        globalSearchTimeout = setTimeout(function () {
            performGlobalSearch(searchTerm);
        }, 300);
    });

    function performGlobalSearch(searchTerm) {
        var searchTerms = searchTerm.split(' ');
        var matchCount = 0;
        var tabContent = $('#pp_edit_status_caps');
        var capabilityText = '';

        // Hide all rows first
        $('#pp_edit_status_caps > tbody > tr').not('.roles-header').hide();
        
        // Search through all capability rows in this tab
        tabContent.find('tr').each(function () {
            // Special handling for revision status tab
            if ($(this).find('th.status-label a').length) {
                capabilityText = $(this).find('th.status-label a').text().toLowerCase();
            } else {
                capabilityText = $(this).find('th.status-label').text().toLowerCase();
            }

            // Skip empty rows and bulk rows
            if (capabilityText.trim() === '') {
                return;
            }

            // Check if all search terms match (with underscore handling)
            var allTermsMatch = true;
            for (var i = 0; i < searchTerms.length; i++) {
                var term = searchTerms[i];
                var termWithSpaces = term.replace(/_/g, ' ');
                var termWithUnderscores = term.replace(/ /g, '_');

                // Check original term, spaces->underscores, and underscores->spaces
                if (capabilityText.indexOf(term) === -1 &&
                    capabilityText.indexOf(termWithSpaces) === -1 &&
                    capabilityText.indexOf(termWithUnderscores) === -1) {
                    allTermsMatch = false;
                    break;
                }
            }

            if (allTermsMatch) {
                $(this).show();
                $(this).next('tr').show();
                matchCount++;
            }
        });

        updateSearchSummary(matchCount, searchTerm);
    }

    function clearGlobalSearch() {
        // Clear search summary
        $('#pp-search-results-summary').text('');

        // Show all rows in all tabs
        $('#pp_edit_status_caps tr').show();
    }

    function updateSearchSummary(totalMatches, searchTerm) {
        var $summary = $('#pp-search-results-summary');

        if (totalMatches > 0) {
            $summary.text('Found ' + totalMatches + ' match' + (totalMatches !== 1 ? 'es' : ''));
        } else {
            $summary.text('No matches found for "' + searchTerm + '"');
        }
    }
});