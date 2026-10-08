jQuery(document).ready(function ($) {

    var content_overview_posts = PPContentOverviewPosts.posts;
    var content_overview_tax   = PPContentOverviewPosts.taxonomies;

    /**
     * Event listener for opening modal on post click
     */
    $(document).on('click', '.content-overview-table-wrap .column-content .post-title', function (e) {
        e.preventDefault();
        open_post_modal($(this).closest('tr').attr('data-post_id'));
    });

    /**
     * Save post update
     */
    $(document).on('click', '.pp-content-overview-general-modal-container .modal-content-right .save-post-changes:not(.disabled)', function (e) {
        e.preventDefault();
        var button      = $(this);
        var modal_form  = button.closest('.modal-content-right');
        
        button.addClass('disabled');

        var post_id         = button.attr('data-post_id');
        var post_title      = modal_form.find('.title-area').val();
        var post_date       = modal_form.find('.content_board_post_date_hidden').val();
        var post_author     = modal_form.find('.pp-modal-form-author').val();
        var post_status     = modal_form.find('.pp-modal-form-post-status').val();
        var post_status_text = modal_form.find('.pp-modal-form-post-status option:selected').text();
        var target_post        = $('table.content-overview tbody tr#post-' + post_id);
        var post_taxonomies = {};
        modal_form.find('.pp-modal-form-post-taxonomy').each(function () {
            var tax_html = $(this);
            post_taxonomies[tax_html.attr('data-taxonomy')] = tax_html.val();
        });
        var data = {
            action: "publishpress_content_overview_update_post",
            post_id: post_id,
            post_title: post_title,
            post_date: post_date,
            post_author: post_author,
            post_status: post_status,
            post_taxonomies: post_taxonomies,
            nonce: PPContentOverviewPosts.nonce
        };
        
        $.post(ajaxurl, data, function (response) {
            if (response.status == 'success') {
                // update card and post global data
                var post_index = content_overview_posts.findIndex(function(p) {
                    return Number(p.post_id) === Number(post_id);
                });
                var post        = content_overview_posts[post_index];
                var taxonomies  = content_overview_tax[post_id];
                var can_edit_post = Number(post.can_edit_post) > 0
            
                
                var old_post_status = target_post.closest('.status-content.board-main-content').attr('data-slug');

                // update post global card title 
                if (can_edit_post) {
                    target_post.find('.post-title a').html(post_title);
                } else {
                    target_post.find('.post-title').html(post_title);
                }
                if (target_post.find('.column-content.column-content-post_author').length > 0) {
                    target_post.find('.column-content.column-content-post_author').html(response.author_name);
                }

                if (target_post.find('.column-content.column-content-post_date').length > 0) {
                    target_post.find('.column-content.column-content-post_date').html(response.post_date);
                }

                if (target_post.find('.column-content.column-content-post_modified').length > 0) {
                    target_post.find('.column-content.column-content-post_modified').html(response.post_modified);
                }

                if (target_post.find('.column-content.column-content-post_status').length > 0) {
                    target_post.find('.column-content.column-content-post_status').html(post_status_text);
                }

                post.post_title     = post_title;
                post.raw_title      = post_title;
                post.post_status    = post_status;
                post.author_markup  = response.author_markup;
                post.date_markup    = response.date_markup;
                content_overview_posts[post_index] = post;
                // update post taxonomies global and card tax
                var response_taxonomies = response.taxonomy_terms;
                for (var taxonomyKey in response_taxonomies) {
                    if (response_taxonomies.hasOwnProperty(taxonomyKey)) {
                        var taxonomyData = response_taxonomies[taxonomyKey];
                        taxonomies[taxonomyKey].terms = taxonomyData;
                        if (target_post.find('.column-content.column-content-' + taxonomyKey).length > 0) {
                            var term_html = ' ';
                            if (taxonomyData.length> 0) {
                                var taxonomy_terms_name = taxonomyData.map(function(term) {
                                    return term.name;
                                });
                                term_html = '<div class="taxonomy-terms">' + taxonomy_terms_name.join(", ") + '</div>';
                            }
                            target_post.find('.column-content.column-content-' + taxonomyKey + '').html(term_html);
                        }
                    }
                }
                content_overview_tax[post_id] = taxonomies;

            }

            // enable button
            button.removeClass('disabled');
            // show status message
            ppcTimerStatus(response.status, response.content);
        });
    });
        

    /**
     * Event listener for previous and next navigation links
     */
    $(document).on('click', '.pp-popup-modal-header .modal-nav-prev, .pp-popup-modal-header .modal-nav-next', function (e) {
        e.preventDefault();
        var post_id = $(this).attr('data-post_id');
        open_post_modal(post_id);
    });

    /**
     * Adjust textarea height on input
     */
    $(document).on('input', '.pp-content-overview-general-modal-container .modal-post-title .title-area', function (e) {
        adjust_textarea_height($(this));
    });

    /**
     * 
     */
    function adjust_textarea_height(textarea) {
        // Reset the height so that it can shrink on deleting content
        textarea.css('height', 'auto');
        // Set the height to the scroll height of the content
        textarea.css('height', textarea[0].scrollHeight + 'px');
    }

    /**
     * Function to open the modal
     */
    function open_post_modal(post_id) {

        var post_index = content_overview_posts.findIndex(function(p) {
            return Number(p.post_id) === Number(post_id);
        });

        if (post_index === -1) {
            console.error('Post with id ' + post_id + ' not found');
            console.log(content_overview_posts);
            return;
        }

        var post = content_overview_posts[post_index];

        var post_status     = post.post_status;
        var status_title    = post.status_label;
        var action_links    = post.action_links;

        // post details
        var previous_post = content_overview_posts[post_index - 1] || content_overview_posts[content_overview_posts.length - 1];
        var next_post = content_overview_posts[post_index + 1] || content_overview_posts[0];

        var post_taxonomies = content_overview_tax[post_id] || null;

        var can_edit_post = Number(post.can_edit_post) > 0;

        // build header
        var popup_header = '<div class="pp-popup-modal-header">';

            if (previous_post.post_id != post.post_id) {
                popup_header += '<div class="pp-modal-navigation-prev">';
                popup_header += '<a title="' + PPContentOverviewPosts.prev_label + '" href="#" class="modal-nav-prev" data-post_id="' + previous_post.post_id + '"><span class="dashicons dashicons-arrow-left-alt"></span> ' + previous_post.filtered_title + '</a>';
                popup_header += '</div>';
            }

            popup_header += '<div class="pp-modal-navigation-current">';
            popup_header += '<span class="modal-nav-current"> ' + post.filtered_title + '</span>';
            popup_header += '</div>';

            if (next_post.post_id != post.post_id) {
                popup_header += '<div class="pp-modal-navigation-next">';
                popup_header += '<a title="' + PPContentOverviewPosts.next_label + '" href="#" class="modal-nav-next" data-post_id="' + next_post.post_id + '">' + next_post.filtered_title + ' <span class="dashicons dashicons-arrow-right-alt"></span></a>';
                popup_header += '</div>';
            }

            // add post edit link meta
            if (action_links.edit !== '') {
                popup_header += '<div class="meta post-edit"><span class="meta-title"><span><a href="' + action_links.edit + '">' + PPContentOverviewPosts.edit_label + '</a></span></span></div>';
            }
            // add post trash meta
            if (action_links.trash !== '') {
                popup_header += '<div class="meta post-delete"><span class="meta-title"><span><a href="' + action_links.trash + '">' + PPContentOverviewPosts.delete_label + '</a></span></span></div>';
            }
            // add post view/preview meta
            if (action_links.previewpost !== '') {
                popup_header += '<div class="meta post-preview"><span class="meta-title"><span><a href="' + action_links.previewpost + '">' + PPContentOverviewPosts.preview_label + '</a></span></span></div>';
            } else if (action_links.view !== '') {
                popup_header += '<div class="meta post-view"><span class="meta-title"><span><a href="' + action_links.view + '">' + PPContentOverviewPosts.view_label + '</a></span></span></div>';
            }

            popup_header += '</div>';

        // build content
        var popup_content = '<div class="pp-popup-modal-content">';

            popup_content = '<div class="modal-content-left">';

            popup_content += '<div class="main-post-content">';
            popup_content += post.post_content;
            popup_content += '</div>';

            popup_content += '</div>';

            popup_content += '<div class="modal-content-right">';

            popup_content += '<div class="scrollable-content">';

            // add post title
            if (can_edit_post) {
                popup_content += '<div class="modal-post-title"><textarea class="title-area">' + post.raw_title + '</textarea></div>';
            } else {
                popup_content += '<div class="modal-post-title"><div>' + post.raw_title + '</div></div>';
            }
            
            // add post date meta
            popup_content += '<div class="modal-taxonomy-info post-date"><span class="info-item">' + PPContentOverviewPosts.post_date_label + '</span><span class="info-item">' + post.date_markup + '</span></div>';
            // add post author meta
            popup_content += '<div class="modal-taxonomy-info post-author"><span class="info-item">' + PPContentOverviewPosts.post_author_label + '</span><span class="info-item">' + post.author_markup + '</span></div>';
            // add post status meta
            popup_content += '<div class="modal-taxonomy-info post-modified"><span class="info-item">' + post.status_field_label + '</span>';
            popup_content += '<span class="info-item">';
            if (can_edit_post) {
                popup_content += '<select class="pp-modal-form-post-status">';
                $.each(post.status_options, function(index, status) {
                    var selected = status.value == post_status ? 'selected' : '';
                    popup_content += '<option value="' + status.value + '" ' + selected + '>' + status.text + '</option>';
                });
                popup_content += '</select>';
            } else {
                popup_content += status_title;
            }
            popup_content += '</span>';
            popup_content += '</div>';

            // add taxonomies
            if (post_taxonomies !== null) {
                var taxonomy_terms_name = '';
                for (var key in post_taxonomies) {
                    if (post_taxonomies.hasOwnProperty(key)) {
                        var taxonomy = post_taxonomies[key];
                        popup_content += '<div class="modal-taxonomy-info">';
                        popup_content += '<span class="info-item">' + taxonomy.taxonomy_label + '</span>';
                        if (can_edit_post) {
                            popup_content += '<span class="info-item">';
                            popup_content += '<select class="pp-modal-form-post-taxonomy" data-placeholder="' + taxonomy.taxonomy_placeholder + '" data-taxonomy="' + taxonomy.taxonomy + '" multiple>';
                            if (taxonomy.terms.length> 0) {
                                $.each(taxonomy.terms, function(index, term) {
                                    popup_content += '<option value="' + term.slug + '" selected>' + term.name + '</option>';
                                });
                            }
                            popup_content += '</select>';
                            popup_content += '</span>';
                        } else {
                            if (taxonomy.terms.length> 0) {
                                taxonomy_terms_name = taxonomy.terms.map(function(term) {
                                    return term.name;
                                });
                                popup_content += '<span class="info-item">' + taxonomy_terms_name.join(", ") + '</span>';
                            } else {
                                popup_content += '<span class="description pp-modal-description">' + PPContentOverviewPosts.empty_term + '</span>';
                            }
                        }
                        popup_content += '</div>';
                    }
                };
            }

            popup_content += '</div>';

            popup_content += '<div class="fixed-footer">';
            if (can_edit_post) {
                popup_content += '<div class="save-post-changes" data-post_id="' + post.post_id + '"><span class="spinner is-active"></span> ' + PPContentOverviewPosts.update_label + '</div>';
            }
            popup_content += '</div>';

            popup_content += '</div>';

            popup_content += '</div>';

        $('#pp-content-overview-general-modal-container').html(popup_content);
        
        var height = Math.round(window.innerHeight * 0.78);
  
        tb_show(popup_header, '#TB_inline?width=600&height=' + height + '&inlineId=pp-content-overview-general-modal');
        var modal_height = $('body.pp-content-overview-page #TB_window').css('height');
        if (modal_height) {
            // update inner content height for scroll bar
            var inner_height = parseInt(modal_height, 10) - 55;

            $('.pp-content-overview-general-modal-container .modal-content-right .scrollable-content').css('height', inner_height - 60 + 'px');
            $('.pp-content-overview-general-modal-container .modal-content-left').css('height', inner_height + 'px');
            $('body.pp-content-overview-page #TB_ajaxContent').css('height', inner_height + 'px');

            // adjust textarea height
            var textarea = $('.pp-content-overview-general-modal-container .modal-post-title .title-area');
            if (textarea.length > 0) {
                adjust_textarea_height(textarea);
            }
        }

        // init date picker
        init_date_time_picker();
        // init select2
        initFormSelect2();
        
    }
    
    function initFormSelect2() {
        $('.pp-modal-form-author').pp_select2({
            allowClear: false,
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 0,
                data: function (params) {
                    return {
                        action: 'publishpress_content_overview_search_authors',
                        nonce: PPContentOverview.nonce,
                        q: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: false
            }
        });

        $('.pp-modal-form-post-taxonomy').pp_select2({
            allowClear: true,
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 0,
            data: function (params) {
                    return {
                        action: 'publishpress_content_overview_search_categories',
                        taxonomy: $(this).attr('data-taxonomy'),
                        nonce: PPContentOverview.nonce,
                        q: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: false
            }
        });

        $('.pp-modal-form-post-status').pp_select2({
            allowClear: false
        });
    }

    function init_date_time_picker() {
        $('.pp-content-overview-general-modal-container .modal-content-right .date-time-pick').each(function () {
            var self = $(this);
            var options = getOptions(self, {
            alwaysSetTime: false,
            controlType: 'select',
            altFieldTimeOnly: false
            });
            if (self.hasClass('future-date')) {
            options.minDate = new Date();
            }
            self.datetimepicker(options);
        });
    }

    function getOptions (self, custom_options) {
      var default_options = {
        dateFormat: PPContentOverviewPosts.date_format,
        firstDay: PPContentOverviewPosts.week_first_day
      };
  
      var options = $.extend({}, default_options, custom_options);
      var altFieldName = self.attr('data-alt-field');
  
      if ((!altFieldName) || typeof altFieldName == 'undefined' || altFieldName.length == 0) {
        return options;
      }
  
      return $.extend({}, options, {
        altField: 'input[name="'+ altFieldName +'"]',
        altFormat: self.attr('data-alt-format'),
      });
    }

    function ppcTimerStatus(type = "success", message = '') {
        setTimeout(function () {
            var uniqueClass = "pp-floating-msg-" + Math.round(new Date().getTime() + Math.random() * 100);
            var instances = $(".pp-floating-status").length;
            $("#wpbody-content").after('<span class="pp-floating-status pp-floating-status--' + type + " " + uniqueClass + '">' + message + "</span>");
            $("." + uniqueClass)
                .css("bottom", instances * 45)
                .fadeIn(1e3)
                .delay(1e4)
                .fadeOut(1e3, function () {
                    $(this).remove();
                });
        }, 500);
    }
    
});