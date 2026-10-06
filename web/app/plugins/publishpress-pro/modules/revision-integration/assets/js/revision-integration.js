jQuery(document).ready(function ($) {

    $(document).on('click', '.pp-show-revision-btn', function (e) {
        if ($('.pp-content-calendar-manage').length < 1) {
            e.preventDefault();
            
            var new_value = '';
            if ($(this).hasClass('active-filter')) {
                new_value = 1;
            } else {
                new_value = 0;
            }

            $('#pp-content-filters #pp_hide_revision_input').val(new_value);
            $('#pp-content-filters').trigger('submit');
        }
    });
});