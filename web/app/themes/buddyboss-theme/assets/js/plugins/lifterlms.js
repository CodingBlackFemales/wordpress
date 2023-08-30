//import learndash_sidebar from "./learndash-sidebar";

( function ( $ ) {

    "use strict";

    window.BBLMS = {
        init: function () {
            this.toggleTheme();
            this.expandQuizzes();
            this.learnDashSidePanel();
            this.lms_user_profile_js();
            //this.lms_course_single_js();
            this.lms_single_course();
            this.course_archive_js();
            //this.quiz_progress();
            this.quizDetails();
            //this.ajaxCompleteProcess();
            this.quizUpload();
            //this.setElementorSpacing();
            this.courseViewCookie();
            this.bbStickyLifterSidebar();
            this.StyleInputQuestion();
            this.singleLesson();
            this.singleTopic();
            this.singleQuiz();
            this.collapseTrial();
            this.toPricingTable();
            this.dashboardNav();
            this.showMoreParticipants();
            this.progressIndicatorRound();
            this.switchLdGridList();
        },

        switchLdGridList: function() {

            var courseLoopSelector = $( 'body .course-dir-list .bb-course-items:not(.is-cover)' );
            if ( window.sessionStorage ) {

                if ( $( 'body' ).hasClass( 'post-type-archive-llms_membership' ) ) {
                    var getView = sessionStorage.getItem( 'llms-membership-view' );
                    if ( typeof getView === 'undefined' || getView === null ) {
                        sessionStorage.setItem( 'llms-membership-view', 'grid' );
                        getView = sessionStorage.getItem( 'llms-membership-view' );
                    }
                } else {
                    var getView = sessionStorage.getItem( 'course-view' );
                    if ( typeof getView === 'undefined' || getView === null ) {
                        sessionStorage.setItem( 'course-view', 'grid' );
                        getView = sessionStorage.getItem( 'course-view' );
                    }
                }


                $( '.layout-view-course' ).removeClass( 'active' );
                courseLoopSelector.removeClass( 'grid-view' );
                courseLoopSelector.removeClass( 'bb-grid' );
                courseLoopSelector.removeClass( 'list-view' );
                courseLoopSelector.removeClass( 'bb-list' );

                if ( 'grid' === getView ) {
                    $( '.layout-view-course.layout-grid-view' ).addClass( 'active' );
                    courseLoopSelector.addClass( 'grid-view' );
                    courseLoopSelector.addClass( 'bb-grid' );
                } else {
                    $( '.layout-view-course.layout-list-view' ).addClass( 'active' );
                    courseLoopSelector.addClass( 'list-view' );
                    courseLoopSelector.addClass( 'bb-list' );
                }
            }
            $( document ).on('click', '.grid-filters .layout-view-course', function(e) {
                e.preventDefault();

                courseLoopSelector = $( 'body .course-dir-list .bb-course-items:not(.is-cover)' );
                if ( $(this).hasClass('layout-list-view') ) {
                    if ( window.sessionStorage ) {
                        if ( $( 'body' ).hasClass( 'post-type-archive-llms_membership' ) ) {
                            sessionStorage.setItem('llms-membership-view', 'list');
                        } else {
                            sessionStorage.setItem('course-view', 'list');
                        }
                    }
                    $( '.layout-view-course' ).removeClass( 'active' );
                    courseLoopSelector.removeClass( 'grid-view' );
                    courseLoopSelector.removeClass( 'bb-grid' );
                    courseLoopSelector.removeClass( 'list-view' );
                    courseLoopSelector.removeClass( 'bb-list' );
                    $( '.layout-view-course.layout-list-view' ).addClass( 'active' );
                    courseLoopSelector.addClass( 'list-view' );
                    courseLoopSelector.addClass( 'bb-list' );
                    if ( $( 'body' ).hasClass( 'post-type-archive-llms_membership' ) ) {
                        $.ajax({
                            method  : 'GET',
                            url     : bs_data.ajaxurl,
                            data    : 'action=buddyboss_llms_save_view&option=bb_theme_lifter_membership_grid_list&type=list',
                            success : function ( response ) {
                            }
                        });
                    } else {
                        $.ajax({
                            method: 'GET',
                            url: bs_data.ajaxurl,
                            data: 'action=buddyboss_llms_save_view&option=bb_theme_lifter_course_grid_list&type=list',
                            success: function (response) {
                            }
                        });
                    }
                } else {
                    if ( window.sessionStorage ) {
                        if ( $( 'body' ).hasClass( 'post-type-archive-llms_membership' ) ) {
                            sessionStorage.setItem('llms-membership-view', 'grid');
                        } else {
                            sessionStorage.setItem('course-view', 'grid');
                        }
                    }
                    $( '.layout-view-course' ).removeClass( 'active' );
                    courseLoopSelector.removeClass( 'grid-view' );
                    courseLoopSelector.removeClass( 'bb-grid' );
                    courseLoopSelector.removeClass( 'list-view' );
                    courseLoopSelector.removeClass( 'bb-list' );
                    $( '.layout-view-course.layout-grid-view' ).addClass( 'active' );
                    courseLoopSelector.addClass( 'grid-view' );
                    courseLoopSelector.addClass( 'bb-grid' );
                    if ( $( 'body' ).hasClass( 'post-type-archive-llms_membership' ) ) {
                        $.ajax({
                            method  : 'GET',
                            url     : bs_data.ajaxurl,
                            data    : 'action=buddyboss_llms_save_view&option=bb_theme_lifter_membership_grid_list&type=grid',
                            success : function ( response ) {
                            }
                        });
                    } else {
                        $.ajax({
                            method: 'GET',
                            url: bs_data.ajaxurl,
                            data: 'action=buddyboss_llms_save_view&option=bb_theme_lifter_course_grid_list&type=grid',
                            success: function (response) {
                            }
                        });
                    }
                }
            });
        },

        showMoreParticipants: function() {

            var total            = $( '.llms-course-members-list .llms-course-sidebar-heading .llms-count' ).text();
            var paged            = 2;
            var course           = $( '.llms-course-members-list #buddyboss_theme_llms_course_participants_course_id' ).val();
            var spinnerSelector  = $( '.llms-course-members-list .bb-course-member-wrap .lme-more--llms i' );
            var viewMoreSelector = $( '.llms-course-members-list .bb-course-member-wrap .lme-more--llms' );

            $( '.llms-course-members-list' ).on( 'click', '.bb-course-member-wrap .lme-more--llms', function( e ) {
                e.preventDefault();

                if ( $( this ).hasClass( 'loading-members' ) ) {
                    return;
                }

                $( this ).addClass( 'loading-members' );

                spinnerSelector.removeClass( 'bb-icon-angle-down' );
                spinnerSelector.addClass( 'bb-icon-spin' );
                spinnerSelector.addClass( 'animate-spin' );

                $.ajax({
                    method  : 'GET',
                    url     : bs_data.ajaxurl,
                    data    : 'action=buddyboss_llms_get_course_participants&_wpnonce=' + bs_data.lifterlms.nonce_get_courses + '&total=' + total + '&page=' + paged + '&course=' + course,
                    success : function ( response ) {
                        $( '.llms-course-members-list .bb-course-member-wrap .course-members-list.course-members-list-extra' ).show();
                        $( '.llms-course-members-list .bb-course-member-wrap .course-members-list-extra' ).append( response.data.html );
                        $(window).trigger('resize');
                        if ( 'false' === response.data.show_more ) {
                            $( '.llms-course-members-list .bb-course-member-wrap .lme-more--llms' ).remove();
                        }
                        paged = response.data.page;
                        $( 'html, body' ).animate({ scrollTop: $(window).scrollTop() + 150 }, 300);
                        spinnerSelector.addClass( 'bb-icon-angle-down' );
                        spinnerSelector.removeClass( 'bb-icon-spin' );
                        spinnerSelector.removeClass( 'animate-spin' );
                        viewMoreSelector.removeClass( 'loading-members' );
                    }
                });
            } );

        },

        fetchCourses: function() {
            var $form = $( '#bb-courses-directory-form' );

            var reset_pagination = false;

            //reset pagintion if categories or instructor filters are changed.
            //reset pagination if search term has changed
            var resetting_fields = [ 'filter-categories', 'filter-instructors', 'search' ];
            for ( var i = 0; i < resetting_fields.length; i++ ) {
                var prev_val = BBGetUrlParameter( window.location.search, resetting_fields[ i ] );
                var new_val = $form.find('[name="'+ resetting_fields[ i ] +'"]').val();

                if ( prev_val !== new_val ) {

                    switch ( resetting_fields[ i ] ) {
                        case 'filter-categories':
                        case 'filter-instructors':
                            if ( !prev_val && new_val === 'all' ) {
                                //hasn't really changed
                            } else {
                                reset_pagination = true;
                            }
                            break;
                        default:
                            reset_pagination = true;
                            break;
                    }

                }

                if ( reset_pagination ) {
                    break;
                }
            }

            if ( reset_pagination ) {
                $form.find( '[name="current_page"]' ).val(1);
            }

            var data = $form.serialize();
    
            if ( bs_data.lifterlms.course_category_id > 0 ) {
                //update url.
                var new_url = bs_data.lifterlms.course_category_url;
            } else {
                //update url.
                var new_url = bs_data.lifterlms.course_archive_url;
            }

            if ( $form.hasClass('bb-elementor-widget') ) {
                new_url = $form.data('current_page_url');
            }

            //view
            var view = 'grid';
            if ( $form.find( '.layout-list-view' ).hasClass( 'active' ) ) {
                view = 'list';
            }
            data += '&view=' + view + '&request_url=' + encodeURIComponent( new_url );

            $.ajax({
                method  : 'GET',
                url     : bs_data.ajaxurl,
                data    : data + '&action=buddyboss_lms_get_courses&_wpnonce=' + bs_data.lifterlms.nonce_get_courses + '&course_category_url=' + bs_data.lifterlms.course_category_url + '&is_course_category=' + bs_data.lifterlms.is_course_category + '&course_category_id=' +  bs_data.lifterlms.course_category_id + '&course_category_name=' +  bs_data.lifterlms.course_category_name,
                success : function ( response ) {



                    var current_page = $form.find( '[name="current_page"]' ).val();
                    if ( isNaN( current_page ) ) {
                        current_page = 1;
                    }
                    if ( current_page > 1 ) {
                        new_url += 'page/' + current_page + '/';
                    }

                    new_url += '?' + data;

                    window.history.pushState( { 'bblms_has_changes' : true, 'courses_html' : response.data.html, 'type' : $form.find( '[name="type"]' ).val() }, "", new_url );

                    //update html
                    $form.find('.bs-dir-list').html( response.data.html );
                    //update count
                    $form.find('li.selected a span').text( response.data.count );

                    if ( response.data.scopes ) {
                        for (var i in response.data.scopes) {
                            $form.find('li#courses-' + i + ' a span').text( response.data.scopes[i] ).show();
                        }
                    }

                    $('.courses-nav').find('.bb-icon-loader').remove();
                }
            });

            return false;
            //$( '#bb-courses-directory-form' ).submit();
        },

        fetchMemberships: function() {
            var $form = $( '#bb-membership-directory-form' );

            var reset_pagination = false;

            //reset pagintion if categories or instructor filters are changed.
            //reset pagination if search term has changed
            var resetting_fields = [ 'filter-categories', 'filter-instructors', 'search' ];
            for ( var i = 0; i < resetting_fields.length; i++ ) {
                var prev_val = BBGetUrlParameter( window.location.search, resetting_fields[ i ] );
                var new_val = $form.find('[name="'+ resetting_fields[ i ] +'"]').val();

                if ( prev_val !== new_val ) {

                    switch ( resetting_fields[ i ] ) {
                        case 'filter-categories':
                        case 'filter-instructors':
                            if ( !prev_val && new_val === 'all' ) {
                                //hasn't really changed
                            } else {
                                reset_pagination = true;
                            }
                            break;
                        default:
                            reset_pagination = true;
                            break;
                    }

                }

                if ( reset_pagination ) {
                    break;
                }
            }

            if ( reset_pagination ) {
                $form.find( '[name="current_page"]' ).val(1);
            }

            var data = $form.serialize();

            //view
            var view = 'grid';
            if ( $form.find( '.layout-list-view' ).hasClass( 'active' ) ) {
                view = 'list';
            }
            data += '&view=' + view;

            $.ajax({
                method  : 'GET',
                url     : bs_data.ajaxurl,
                data    : data + '&action=buddyboss_lms_get_memberships&_wpnonce=' + bs_data.lifterlms.nonce_get_memberships,
                success : function ( response ) {
                    //update url
                    var new_url = bs_data.lifterlms.course_membership_url;

                    var current_page = $form.find( '[name="current_page"]' ).val();
                    if ( isNaN( current_page ) ) {
                        current_page = 1;
                    }
                    if ( current_page > 1 ) {
                        new_url += 'page/' + current_page + '/';
                    }

                    new_url += '?' + data;

                    window.history.pushState( { 'bblms_has_changes' : true, 'courses_html' : response.data.html, 'type' : $form.find( '[name="type"]' ).val() }, "", new_url );

                    //update html
                    $form.find('.bs-dir-list').html( response.data.html );
                    //update count
                    $form.find('li.selected a span').text( response.data.count );

                    if ( response.data.scopes ) {
                        for (var i in response.data.scopes) {
                            $form.find('li#courses-' + i + ' a span').text( response.data.scopes[i] ).show();
                        }
                    }

                    $('.courses-nav').find('.bb-icon-loader').remove();
                }
            });

            return false;
            //$( '#bb-courses-directory-form' ).submit();
        },

        fetchCoursesPagination: function() {
            var $form = $( '#bb-courses-directory-form' );
            var data = $form.serialize();

            if ( bs_data.lifterlms.course_category_id > 0 ) {
                //update url.
                var new_url = bs_data.lifterlms.course_category_url;
            } else {
                //update url.
                var new_url = bs_data.lifterlms.course_archive_url;
            }

            if ( $form.hasClass('bb-elementor-widget') ) {
                new_url = $form.data('current_page_url');
            }

            //view
            var view = 'list';
            if ( $form.find( '.layout-grid-view' ).hasClass( 'active' ) ) {
                view = 'grid';
            }

            data += '&view=' + view + '&request_url=' + encodeURIComponent( new_url );

            $.ajax({
                method  : 'GET',
                url     : bs_data.ajaxurl,
                data    : data + '&action=buddyboss_lms_get_courses&_wpnonce=' + bs_data.lifterlms.nonce_get_courses + '&course_category_url=' + bs_data.lifterlms.course_category_url + '&is_course_category=' + bs_data.lifterlms.is_course_category + '&course_category_id=' +  bs_data.lifterlms.course_category_id + '&course_category_name=' +  bs_data.lifterlms.course_category_name,
                success : function ( response ) {



                    var current_page = $form.find( '[name="current_page"]' ).val();
                    if ( isNaN( current_page ) ) {
                        current_page = 1;
                    }
                    if ( current_page > 1 ) {
                        new_url += 'page/' + current_page + '/';
                    }

                    new_url += '?' + data;

                    window.history.pushState( { 'bblms_has_changes' : true, 'courses_html' : response.data.html, 'type' : $form.find( '[name="type"]' ).val() }, "", new_url );

                    //update html
                    $form.find('.bs-dir-list').html( response.data.html );

                    //update count
                    $form.find('li.selected a span').text( response.data.count );

                    if ( response.data.scopes ) {
                        for (var i in response.data.scopes) {
                            $form.find('li#courses-' + i + ' a span').text( response.data.scopes[i] ).show();
                        }
                    }
                    $('.courses-nav').find('.bb-icon-loader').remove();
                }
            });

            return false;
            //$( '#bb-courses-directory-form' ).submit();
        },

        course_archive_js: function() {

            $( document ).on( 'change', '#bb-courses-directory-form input[type=checkbox]', function ( e ) {
                e.preventDefault();
                window.BBLMS.fetchCourses();
            } );

            window.onpopstate = function(e) {
                if ( !e.state ) {
                    return;
                }

                var has_changes = e.state.hasOwnProperty( 'bblms_has_changes' ) ? e.state.bblms_has_changes : false;
                if ( has_changes ) {
                    var $form = $('#bb-courses-directory-form');

                    //update courses html
                    $form.find( '.bs-dir-list' ).html( e.state.courses_html );

                    //highlight correct nav
                    $form.find( '[name="type"]' ).val( e.state.type );

                    $form.find( '.component-navigation > li').each(function(){
                        $(this).removeClass('selected');
                        var type = BBGetUrlParameter( $(this).find(' > a').attr('href'), 'type' );
                        if ( type === e.state.type ) {
                            $(this).addClass('selected');
                        }
                    });
                }
            };

            $( document ).on( 'change', '#bb-courses-directory-form [name=\'orderby\'], #bb-courses-directory-form [name=\'filter-categories\'], #bb-courses-directory-form [name=\'filter-instructors\']', function ( e ) {
                e.preventDefault();
                window.BBLMS.fetchCourses();
            } );

            $( document ).on( 'click', '#bb-courses-directory-form .bs-sort-button', function ( e ) {
                e.preventDefault();
                e.currentTarget.classList.toggle( 'active' );
                $( '#bs-courses-order-by' ).toggleClass( 'open' );
            } );

            $( document ).on( 'click', '#bb-courses-directory-form .bb-lms-pagination a.page-numbers', function ( e ) {
                e.preventDefault();
                var page_number = 1;
                var url_parts = $(this).attr('href').split('/');
                if ( url_parts.length > 0 ) {
                    for ( var i = 0; i < url_parts.length; i++ ) {
                        if ( 'page' === url_parts[i] ) {
                            page_number = url_parts[ i + 1 ];
                            break;
                        }
                    }
                }

                $(this).closest( 'form' ).find( '[name="current_page"]' ).val( page_number );
                window.BBLMS.fetchCoursesPagination();
            } );

            $( document ).on( 'click', '#bb-courses-directory-form .component-navigation a:not(.more-button)', function ( e ) {
                e.preventDefault();

                $(this).closest( '.component-navigation').find( '> li' ).removeClass('selected');
                $(this).closest( '.component-navigation').find( '> li a span' ).hide();
                $(this).closest( '.component-navigation').find( '> li a span' ).text('');
                $(this).closest('li').addClass('selected').append('<i class="bb-icon-loader animate-spin"></i>');
                $(this).closest('li').find( '> a span' ).text('');
                $(this).closest('li').find( '> a span' ).show();

                var type = BBGetUrlParameter( $(this).attr('href'), 'type' );
                $(this).closest( 'form' ).find( '[name="type"]' ).val( type );

                //resetting the page number if important, as sometimes 'all courses' can have more items than 'my courses'
                $(this).closest( 'form' ).find( '[name="current_page"]' ).val( 1 );
                window.BBLMS.fetchCourses();
            } );

            document.addEventListener( 'click', function ( e ) {
                var openFilterDropdown = $( '#course-order-dropdown' );
                var target = e.target;
                if ( openFilterDropdown === target && openFilterDropdown.contains( target ) ) {
                    return false;
                }

                var dropdowns = $( '#bb-courses-directory-form .bs-dropdown' );
                var download_link = $( '#bb-courses-directory-form .bs-dropdown-link' );

                for ( var i = 0; i < download_link.length; i++ ) {
                    if ( download_link[i] !== target && !download_link[i].contains( target ) ) {
                        download_link[i].classList.remove( 'active' );
                    }
                }

                for ( var i = 0; i < dropdowns.length; i++ ) {
                    if ( dropdowns[i] != target.parentElement.nextElementSibling ) {
                        dropdowns[i].classList.remove( 'open' );
                    }
                }
            } );

            $('#bb-membership-directory-form').on( 'submit', function(e){
                window.BBLMS.fetchMemberships();
                return false;
            } );

            $('#bb-courses-directory-form').on( 'submit', function(e){
                window.BBLMS.fetchCourses();
                return false;
            } );

        },

        toggleTheme: function() {

            $( document ).on( 'click', '#bb-toggle-theme', function ( e ) {
                e.preventDefault();
                var color = '';
                if ( !$( 'body' ).hasClass( 'bb-dark-theme' ) ) {
                    $.cookie( 'bbtheme', 'dark', { path: '/' });
                    $( 'body' ).addClass( 'bb-dark-theme' );
                    color = 'dark';
                } else {
                    $.removeCookie('bbtheme', { path: '/' });
                    $( 'body' ).removeClass( 'bb-dark-theme' );
                }

                if ( typeof( toggle_theme_ajax ) != 'undefined' && toggle_theme_ajax != null ) {
                    toggle_theme_ajax.abort();
                }

                var data = {
                    'action': 'buddyboss_lms_toggle_theme_color',
                    'color': color
                };

                // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                if ( typeof( toggle_theme_ajax ) != 'undefined' && toggle_theme_ajax != null ) {
                    toggle_theme_ajax = $.post( ajaxurl, data, function ( response ) {} );
                }
            } );
        },


        expandQuizzes: function() {

            $( document ).on( 'click', '.llms-lesson-preview .ld-item-details', function ( ) {

                var expand=$(this).find('.ld-expand-button');

                if (expand.hasClass('ld-expanded')){
                    expand.removeClass('ld-expanded');
                    $(this).closest('.llms-lesson-preview').find('.quizzes_section_holder').slideUp();
                }else{
                    expand.addClass('ld-expanded');
                    $(this).closest('.llms-lesson-preview').find('.quizzes_section_holder').slideDown();
                }
            } );

            $( document ).on( 'click', '.llms-quiz-attempt-results .llms-quiz-attempt-question .llms-quiz-attempt-question-header', function ( ) {

                $(this).parent().siblings('.llms-quiz-attempt-question').find('.llms-quiz-attempt-question-header').removeClass('expand-result');

                if ($(this).hasClass('expand-result')){
                    $(this).removeClass('expand-result');
                }else{
                    $(this).addClass('expand-result');
                }

            } );





        },

        learnDashSidePanel: function() {

            $( document ).on( 'click', '.header-maximize-link', function ( e ) {
                e.preventDefault();
                $( 'body' ).addClass( 'lms-side-panel-close' );
                $( '.lifter-topic-sidebar-wrapper' ).addClass( 'lms-topic-sidebar-close' );
                $.cookie( 'lessonpanel', 'closed', { path: '/' });
            } );

            $( document ).on( 'click', '.header-minimize-link', function ( e ) {
                e.preventDefault();
                $( 'body' ).removeClass( 'lms-side-panel-close' );
                $( '.lifter-topic-sidebar-wrapper' ).removeClass( 'lms-topic-sidebar-close' );
                $.removeCookie('lessonpanel', { path: '/' });
            } );

            if ( $( window ).width() < 768 ) {
                $( 'body' ).addClass( 'lms-side-panel-close' );
                $( '.lifter-topic-sidebar-wrapper' ).addClass( 'lms-topic-sidebar-close' );

                $( document ).on( 'click', '.header-minimize-link', function ( e ) {
                    e.preventDefault();
                    $( 'body' ).addClass( 'lms-side-panel-close-sm' );
                } );

                $( document ).click( function(e) {
                    var container = $( '.header-minimize-link' );
                    if ( !container.is( e.target ) && container.has( e.target ).length === 0 ) {
                        $( 'body' ).removeClass( 'lms-side-panel-close-sm' );
                    }
                } );
            }

            $( window ).on( 'resize', function () {
                if ( $( window ).width() < 768 ) {
                    $( document ).click( function(e) {
                        var container = $( '.header-minimize-link' );
                        if ( !container.is( e.target ) && container.has( e.target ).length === 0 ) {
                            $( 'body' ).removeClass( 'lms-side-panel-close-sm' );
                        }
                    } );
                }
            } );
        },

        lms_course_single_js: function() {
            $( document ).on( 'click', '.learndash-course-single-nav', function ( e ) {
                e.preventDefault();
                $( 'ul.learndash-course-single-main-nav' ).find( 'li.current.selected' ).removeClass( 'current' ).removeClass( 'selected' );

                var li = $( e.currentTarget ).closest( 'li' );
                li.addClass( 'current' );
                li.addClass( 'selected' );

                var currentTab = $( e.currentTarget );
                var tab_to_change = currentTab.data( 'tab' );

                var content_elems = $( '.learndash-course-single-tab-content' );
                $.each( content_elems, function ( elem ) {
                    var _this = $( this );
                    if ( _this.data( 'tab' ) == tab_to_change ) {
                        _this.removeClass( 'hide' );
                    } else {
                        _this.addClass( 'hide' );
                    }
                } );

            } );
        },

        lms_single_course: function() {
            $( '.bb-course-video-overlay' ).magnificPopup( {
                fixedBgPos: true,
                fixedContentPos: true,
                items: {
                    src: '.bb_course_video_details',
                    type: 'inline'
                },
                callbacks: {
                    open: function () {
                        // Pause the video if someone click on the close button of the magnificPopup.
                        $.magnificPopup.instance.close = function () {
                            if ($('.mfp-container .mfp-content .bb_course_video_details video'.length)) {
                                $('video').trigger('pause');
                                $.magnificPopup.proto.close.call(this);
                            }
                        };
                    }
                }
            } );

            function sideBarPosition() {
                var courseBannerHeight = $( '.bb-llms-banner' ).height();
                var courseBannerVideo = $( '.bb-thumbnail-preview .bb-preview-course-link-wrap' );
                if ( courseBannerVideo.length ) {
                    var thumbnailContainerHeight = courseBannerVideo.height();
                } else {
                    var thumbnailContainerHeight = 0;
                }
                var sidebarOffset = ( courseBannerHeight/2 ) + ( thumbnailContainerHeight/2 );
                if ( $(window).width() > 820 ) {
                    $( '.bb-single-course-sidebar.bb-preview-wrap' ).css( { 'margin-top' : '-' + sidebarOffset + 'px' } );
                }
            }

            function courseBanner() {
                var mainWidth = $( '#main' ).width();
                $( '.bb-llms-banner .bb-course-banner-info.container' ).width( mainWidth );
            }

            sideBarPosition();
            courseBanner();

            $( window ).on( 'resize', function () {
                courseBanner();
                sideBarPosition();
            } );

            $( '.bb-toggle-panel' ).on( 'click', function(e) {
                e.preventDefault();

                setTimeout(function(){
                    courseBanner();
                },300);
                setTimeout(function(){
                    courseBanner();
                },600);
            } );
        },

        lms_user_profile_js: function() {
            $( document ).on( 'click', '.bb-lms-user-profile-tab', function ( e ) {
                e.preventDefault();
                $( 'ul.bb-lms-user-profile-tabs' ).find( 'li.current.selected' ).removeClass( 'current' ).removeClass( 'selected' );

                var li = $( e.currentTarget ).closest( 'li' );
                li.addClass( 'current' );
                li.addClass( 'selected' );

                var currentTab = $( e.currentTarget );
                var tab_to_change = currentTab.data( 'tab' );

                var content_elems = $( '.bb-lms-user-profile-tab-content' );
                $.each( content_elems, function ( elem ) {
                    var _this = $( this );
                    if ( _this.data( 'tab' ) == tab_to_change ) {
                        _this.removeClass( 'hide' );
                    } else {
                        _this.addClass( 'hide' );
                    }
                } );

            } );
        },

        quizDetails: function() {

            if ( $( '#bb-lms-quiz-id' ).length ) {
                var quiz_id = $('#bb-lms-quiz-id').val();

                $( 'div.quiz_progress_container' ).insertBefore( $( '.wpProQuiz_results .wpProQuiz_resultTable' ) );

                $('#wpProQuiz_' + quiz_id).on('learndash-quiz-init', function () {
                    // $( document ).find( 'input[name="startQuiz"]' ).click( function () {
                    //     BBLMS.showQuizNavigation();
                    // } );
                    //
                    // $( document ).on( 'click', '.bb-lms-quiz-questions', function ( e ) {
                    //     e.preventDefault();
                    //     var index = $( e.currentTarget ).data( 'index' );
                    //     if ( typeof index !== 'undefined' ) {
                    //         $( '#wpProQuiz_' + quiz_id ).data( "wpProQuizFront" ).methode.showQuestion( index );
                    //     }
                    // } );
                });
            }
        },

        ajaxCompleteProcess: function() {
            $( document ).ajaxComplete(function( event, request, settings ) {
                if (settings.data && settings.data != '') {
                    var splitted = settings.data.split('&');
                    var action = '';
                    for (var i in splitted) {
                        if (splitted[i].indexOf('action') != -1) {
                            action = splitted[i].split('=');
                            action = typeof action[1] !== 'undefined' ? action[1] : '';
                            break;
                        }
                    }
                    if (action != '' && action == 'wp_pro_quiz_load_quiz_data') {
                        if( $( '.wpProQuiz_resultTable' ).length ) {
                            $( '.bb_avg_progress' ).show();

                            var pathAvg = new ProgressBar.Path("#bb_avg_shape", {
                                duration: 3000,
                                from: {
                                    color: "#ECCBFF",
                                    width: 8
                                },
                                to: {
                                    color: "#ECCBFF",
                                    width: 8
                                },
                                easing: "easeInOut",
                                step: function(state, shape) {
                                    shape.path.setAttribute("stroke", state.color);
                                    shape.path.setAttribute("stroke-width", state.width);
                                }
                            });

                            var avarage = request.responseJSON.averageResult;
                            if( avarage > 0 ) {
                                avarage = -avarage/100;
                            } else {
                                avarage = 0;
                            }
                            pathAvg.animate(avarage);
                        }
                    }
                    if (action != '' && action == 'wp_pro_quiz_completed_quiz') {
                        if( $( '.wpProQuiz_resultTable' ).length ) {

                            var data = decodeURIComponent(settings.data);
                            var splitted = data.split('&');
                            var result = '';
                            for (var i in splitted) {
                                if (splitted[i].indexOf('results') != -1) {
                                    result = splitted[i].split('=');
                                    result = typeof result[1] !== 'undefined' ? result[1] : '';
                                    result = JSON.parse(result);
                                    break;
                                }
                            }

                            if ( typeof result.comp.result !== 'undefined' ) {
                                result = result.comp.result;
                            }

                            var path = new ProgressBar.Path("#quiz_shape_progress", {
                                duration: 3000,
                                from: {
                                    color: "#00A2FF",
                                    width: 8
                                },
                                to: {
                                    color: "#7FE0FF",
                                    width: 8
                                },
                                easing: "easeInOut",
                                step: function(state, shape) {
                                    shape.path.setAttribute("stroke", state.color);
                                    shape.path.setAttribute("stroke-width", state.width);
                                }
                            });

                            jQuery('.bb_progressbar_label').text(result+'%');
                            jQuery('.bb_progressbar_points').text(jQuery('.wpProQuiz_points').text());

                            if( result > 0 ) {
                                result = -result/100;
                            }

                            path.animate(result);
                        }
                    }
                }
            });
        },

        quizUpload: function() {
            function inputFileStyle() {
                $( 'input.wpProQuiz_upload_essay[type=file]:not(.styled)' ).each( function () {
                    var $fileInput = $( this );
                    var $fileInputFor = $fileInput.attr( 'id' );
                    $fileInput.addClass('styled');
                    $fileInput.after( '<label for="' + $fileInputFor + '">' + bs_data.translation.choose_a_file_label + '</label>' );
                } );

                $( 'input.wpProQuiz_upload_essay[type=file]' ).change( function ( e ) {
                    var $in = $( this );
                    var $inval = $in.next().html( $in.val() );
                    if ( $in.val().length === 0 ) {
                        $in.next().html( bs_data.translation.choose_a_file_label );
                    } else {
                        $in.next().html( $in.val().replace( /C:\\fakepath\\/i, '' ) );
                    }
                } );
            }

            inputFileStyle();
            $( document ).ajaxComplete(function() {
                inputFileStyle();
            });
        },

        courseViewCookie: function () {
            $( '#bb-courses-directory-form .layout-grid-view' ).click( function () {
                $.cookie( 'courseview', 'grid' );
            } );

            $( '#bb-courses-directory-form .layout-list-view' ).click( function () {
                $.cookie( 'courseview', 'list' );
            } );
        },

        bbStickyLifterSidebar: function () {
            var bbHeaderHeight = $('#masthead').outerHeight();

            if ( $(window).width() > 820 && $('.bb-llms-sticky-sidebar .lifter-sidebar-widgets').length == 0 ) {
                $('.bb-llms-sticky-sidebar').stick_in_parent({offset_top: bbHeaderHeight + 45});

                if( $('body').hasClass('sticky-header') ) {
                    $('.lifter-topic-sidebar-data').stick_in_parent({offset_top: bbHeaderHeight + 30 });
                } else {
                    $('.lifter-topic-sidebar-data').stick_in_parent({offset_top: 30});
                }
            }

            $(window).on('resize', function () {
                if ( $(window).width() > 820 && $('.bb-llms-sticky-sidebar .lifter-sidebar-widgets').length == 0 ) {
                    $('.bb-llms-sticky-sidebar').stick_in_parent({offset_top: bbHeaderHeight + 45});
                } else {
                    $('.bb-llms-sticky-sidebar').trigger("sticky_kit:detach");
                    $('.lifter-topic-sidebar-data').trigger("sticky_kit:detach");
                }
            });
        },

        setElementorSpacing: function() {
            if ( $('.elementor-location-header').length > 0 ) {
                var setHeight = $('.elementor-location-header').outerHeight();
                $('.lifter-topic-sidebar-wrapper, #lifterlms-page-content').css({'min-height': 'calc(100vh - '+setHeight+'px)'});
            }
        },

        showQuizNavigation: function() {
            var question_list = $( '.wpProQuiz_list' ).find( '.wpProQuiz_listItem' );
            var nav_wrapper = $( '#bb-lms-quiz-navigation' );
            if ( question_list.length && nav_wrapper.length ) {
                var str = '<ul>';
                for ( var i = 1; i <= question_list.length; i++ ) {
                    str += '<li><a href="#" class="bb-lms-quiz-questions" data-index="' + ( i - 1 ) + '">' + i + '</a></li>';
                }
                str += '</ul>';
                nav_wrapper.html( str );
            }
        },

        StyleInputQuestion: function() {
            function styleInputs() {
                $( '.wpProQuiz_questionInput' ).each(function() {
                    if( ! $(this).hasClass('bbstyled') ) {
                        $(this).addClass('bbstyled').after('<span class="input-style"></span>');
                    }
                });
            }

            styleInputs();

            $( document ).ajaxComplete( function() {
                styleInputs();
            });
        },

        singleLesson: function() {
            var lsPageContent = document.getElementById('learndash-page-content');
            if( lsPageContent ) {
                $( '#learndash-page-content' ).scroll(function() {
                    $( window ).trigger('resize');
                });
            }
        },

        singleTopic: function() {
            var lsPageContent = $( 'body.single-sfwd-topic .lms-topic-item.current' );
            if( lsPageContent.length ) {
                lsPageContent.closest( 'div' ).show();
                lsPageContent.parents().closest( 'li' ).removeClass( 'lms-lesson-turnover' );
            }
        },

        singleQuiz: function() {
            var lsPageContent = $( 'body.single-sfwd-quiz .lms-quiz-item.current' );
            if( lsPageContent.length ) {
                lsPageContent.closest( 'div' ).show();
                lsPageContent.parents().closest( 'li' ).removeClass( 'lms-lesson-turnover' );
            }
        },

        collapseTrial: function() {
            $( '.llms-access-plan-pricing.trial' ).each( function( i ) {
                var trial = $( this ).html();
                $( this ).html( trial.replace( '&nbsp;', '' ) );
            } );
        },

        toPricingTable: function() {
            $( '.link-to-llms-access-plans' ).click( function( e ) {
                e.preventDefault();
                var $sectionPlans = $( 'section.llms-access-plans' );
                if ( $sectionPlans.is( ':visible' ) ) {
                    $( 'html, body' ).animate( {scrollTop:$( $sectionPlans ).position().top - 50 }, 'fast' );
                }
            } );
        },

        dashboardNav: function() {
            $( document ).on( 'click', '.llms-sd-header .llms-sd-title', function ( event ) {
                event.preventDefault();

                var self = $( this );
                var navContainer = $( this ).closest( '.llms-sd-header' );
                navContainer.find( 'nav.llms-sd-nav' ).slideToggle();
            } );
        },

        progressIndicatorRound: function() {
            $( '.wp-block-llms-course-progress .progress__indicator' ).each( function( i ) {
                var progress = $( this ).text();
                var num = parseFloat( progress );
                var round_num = Math.round( num ) + '%';
                $( this ).text( round_num );
            } );
        },

    };

    $( document ).ready( function () {
        window.BBLMS.init();
    } );

} )( jQuery );