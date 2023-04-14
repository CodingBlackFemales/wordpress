var BBLMS_Sidebar;
( function ( $ ) {
    var getTopicAjaxXhr = null;
    BBLMS_Sidebar = {

        init: function ()  {

            //this.setupNavigationLinks();
            //this.setupMarkComplete();
            this.lessonExpand();
            this.groupShift();
            //this.membersExpand();
            //this.learnDashNextPrevData();
            this.setupLdSidebarGroupCookie();
            this.scrollToLesson();

            // $( document ).on( 'click', '.lms-topic-sidebar-wrapper .lms-lesson-item>a,.lms-topic-sidebar-wrapper .lms-topic-item>a,.lms-topic-sidebar-wrapper .lms-quiz-item>a', function ( e ) {
            //     e.preventDefault();
            //     fetchContent( e );
            // } );
        },
        setupMarkComplete: function() {
            // $( document ).on( 'click', '#learndash_mark_complete_button', function ( e ) {
            //     e.preventDefault();
            //     $( "#sfwd-mark-complete" ).submit();
            // } );


            // $( document ).on( 'submit', '#sfwd-mark-complete', function ( e ) {
            //     e.preventDefault();
            //
            //     var xhr = new XMLHttpRequest();
            //     $.ajax( {
            //         type: "POST",
            //         url: window.location.href,
            //         data: $( "#sfwd-mark-complete" ).serialize(),
            //         dataType: 'html',
            //         beforeSend: function() {showLoading()},
            //         xhr: function () {
            //             return xhr;
            //         }
            //     } ).done( function ( data ) {
            //         var response = $( '<div />' ).append( data );
            //         var result = response.find( '#learndash-content' );
            //         removeLoading();
            //         if ( typeof result !== 'undefined' ) {
            //             var title = response.find( 'title' ).text();
            //             window.history.pushState( '', title, xhr.responseURL );
            //             document.title = title;
            //             $( "#learndash-content" ).html( result.html() );
            //             reloadScriptsfromResponse( response );
            //             fetchDataBalloon();
            //         } else {
            //             if ( $( "#learndash-page-content" ).length ) {
            //                 $( "#learndash-page-content" ).html( '<p>Error!</p>' );
            //             }
            //         }
            //
            //     } );
            //
            //     fetchDataBalloon();
            // } );
        },
        lessonExpand: function() {
            $( document ).on( 'click', '.lms-toggle-lesson', function ( e ) {
                var $this = $( this ),
                    thisLesson = $this.closest( '.lms-lesson-item' ).not( '.bb-lesson-item-no-topics' ),
                    thisContent = thisLesson.find( '.lms-lesson-content' );

                thisLesson.toggleClass( 'lms-lesson-turnover' );
                thisContent.slideToggle( '200' );
            } );

            $( '.bb-lessons-list-item:not(.bb-lesson-item-no-topics) .lms-toggle-lesson' ).each( function () {
                var $this = $( this ),
                    thisLesson = $this.closest( '.bb-lessons-list-item' ),
                    thisList = $this.closest( '.bb-lessons-list' ),
                    thisListFirstItem = thisList.find( 'li.bb-lessons-list-item:first' ),
                    thisContent = thisLesson.find( '.lms-lesson-content' );

                thisLesson.addClass( 'lms-lesson-turnover' );
                thisListFirstItem.removeClass( 'lms-lesson-turnover' );
                thisContent.hide();
                thisListFirstItem.find( '.lms-lesson-content' ).slideDown( '200' );

                $this.on( 'click', function () {
                    var $this = $( this ),
                        thisLesson = $this.closest( '.bb-lessons-list-item' ),
                        thisContent = thisLesson.find( '.lms-lesson-content' );

                    thisLesson.toggleClass( 'lms-lesson-turnover' );
                    thisContent.slideToggle( '200' );
                } );

            } );
        },
        groupShift: function() {
            $( document ).on( 'click', '.flag-group-exp', function ( e ) {
                var $this = $( this ),
                    thisList = $this.closest( '.lms-group-flag' ),
                    thisExtra = thisList.find( '.course-group-list' );

                thisList.toggleClass( 'expanded' );
                thisExtra.slideToggle( '200' );
            } );
        },
        membersExpand: function() {
            $( document ).on( 'click', '.list-members-extra', function ( e ) {
                e.preventDefault();
                var $this = $( this ),
                    thisList = $this.closest( '.bb-course-member-wrap' ),
                    thisExtra = thisList.find( '.course-members-list-extra' );

                thisList.toggleClass( 'expanded' );
                thisExtra.slideToggle( '200' );
            } );
        },
        // learnDashNextPrevData: function() {
        //     fetchDataBalloon();
        // },
        // setupNavigationLinks: function() {
        //     $( document ).on( 'click', '#learndash-page-content .next-link, #learndash-page-content .prev-link, #learndash-page-content .lesson-topic-link', function ( e ) {
        //         e.preventDefault();
        //         fetchContent( e );
        //     } );
        // },
        setupLdSidebarGroupCookie: function() {
            $( document ).on( 'click', '.ld-set-cookie', function ( e ) {
                var dataGroupId  = $(this).attr('data-group-id');
                var dataCourseId = $(this).attr('data-course-id');
                $.cookie('bp-ld-active-course-groups-'+dataCourseId, dataGroupId,{ path: '/'});
            } );
        },
        scrollToLesson: function() {
            setTimeout(function(){
                var activeTopic = $('.lms-topic-sidebar-wrapper .lms-lessions-list .bb-lessons-list .lms-topic-item.current' );
                var activeLesson = $('.lms-topic-sidebar-wrapper .lms-lessions-list .bb-lessons-list .lms-lesson-item.current ' );
                var activeQuiz = $('.lms-topic-sidebar-wrapper .lms-course-quizzes-list .lms-quiz-item.current ' );
                var activeQuizinLesson = $('.lms-topic-sidebar-wrapper .lms-lessions-list .bb-lessons-list .lms-quiz-item.current ' );
                var headerStickyHeight = 0;
                var adminbarHeight = 0;
                if( $('body').hasClass('sticky-header') ) {
                    headerStickyHeight = $('header.site-header').height();
                } else {
                    if( $( window ).scrollTop() > $('header.site-header').height() ) {
                        headerStickyHeight = 0;
                    } else {
                        headerStickyHeight = $('header.site-header').height();
                    }
                }
                if( $('#wpadminbar').length ) {
                    adminbarHeight = $('#wpadminbar').height();
                }

                //Scroll to active Lesson, Topic or Quiz
                if( activeTopic.length ) {
                    $( '.lms-topic-sidebar-wrapper .lms-topic-sidebar-data' ).animate({ scrollTop: activeTopic.offset().top - ( headerStickyHeight + adminbarHeight ) });
                } else if( activeLesson.length ){
                    $( '.lms-topic-sidebar-wrapper .lms-topic-sidebar-data' ).animate({ scrollTop: activeLesson.offset().top - ( headerStickyHeight + adminbarHeight ) });
                } else if( activeQuiz.length ){
                    $( '.lms-topic-sidebar-wrapper .lms-topic-sidebar-data' ).animate({ scrollTop: activeQuiz.offset().top - ( headerStickyHeight + adminbarHeight ) });
                } else if ( activeQuizinLesson.length ) {
                    $( '.lms-topic-sidebar-wrapper .lms-topic-sidebar-data' ).animate({ scrollTop: activeQuizinLesson.offset().top - ( headerStickyHeight + adminbarHeight ) });
                }
            },100);
            
        },
    }

    function fetchContent(e){
        if ( getTopicAjaxXhr != null ) {
            getTopicAjaxXhr.abort();
        }
        var target = $(e.currentTarget);
        var url = e.currentTarget.href;
        getTopicAjaxXhr = $.ajax( {
            type: "GET",
            url: url,
            beforeSend: function() {showLoading()},
            dataType: 'html'
        } ).done( function ( data ) {
            var response = $( '<div />' ).append( data );
            var result = response.find( '#learndash-page-content' );
            removeLoading();
            if ( typeof result !== 'undefined' ) {
                var title = response.find( 'title' ).text();
                var heading = response.find( '#learndash-page-content #learndash-course-header h1' ).text();
                window.history.pushState( '', title, url );
                document.title = title;
                $('.lms-lessions-list li').removeClass('current');
                $('.lms-course-quizzes-list li').removeClass('current');
                target.closest('li').addClass("current");
                $( "#learndash-page-content" ).html( result.html() );
                window.BBLMS.quizDetails();
                reloadScriptsfromResponse( response );

                $( '.lms-topic-sidebar-data .bb-lms-title' ).each( function () {
                    var menuItem = $( this ).text();

                    if ( menuItem == heading ) {
                        $( this ).closest('li').addClass('current');
                    }
                } );

                $( '.lms-topic-sidebar-data .bb-lesson-title' ).each( function () {
                    var menuLessonItem = $( this ).text();

                    if ( menuLessonItem == heading ) {
                        $( this ).closest('li').addClass('current');
                    }
                } );

                $( 'input.wpProQuiz_upload_essay[type=file]' ).each( function () {
                    var $fileInput = $( this );
                    var $fileInputFor = $fileInput.attr( 'id' );
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

                fetchDataBalloon();
            } else {
                if ( $( "#learndash-page-content" ).length ) {
                    $( "#learndash-page-content" ).html( '<p>Error!</p>' );
                }
            }
        } );
    }

    function reloadScriptsfromResponse( response ){
        var scriptElements = response.find( "script" );
        var i;

        jQuery( "script" ).each( function () {
            if ( this.hasAttribute( "src" ) ) {
                var oldScript = this.getAttribute( "src" );
                if ( oldScript.indexOf( '/sfwd-lms/' ) != -1 ) {
                    jQuery( this ).remove();
                }
            } else if ( !this.hasAttribute( "id" ) && !this.hasAttribute( "src" ) ) {
                jQuery( this ).remove();
            }
        } );

        for ( i = 0; i < scriptElements.length; i++ ) {
            if ( scriptElements[i].hasAttribute( "src" ) ) {
                var oldScript = scriptElements[i].getAttribute( "src" );
                if ( oldScript.indexOf( '/sfwd-lms/' ) != -1 || oldScript.indexOf( '/jquery/ui/' ) != -1 ) {
                    var newScript;
                    newScript = document.createElement( 'script' );
                    newScript.type = 'text/javascript';
                    newScript.src = oldScript;
                    document.body.appendChild( newScript );
                }
            }
        }

        setTimeout( function () {
            for ( i = 0; i < scriptElements.length; i++ ) {
                if ( !scriptElements[i].hasAttribute( 'id' ) && !scriptElements[i].hasAttribute( "src" ) ) {
                    var newScript;
                    newScript = document.createElement( 'script' );
                    newScript.type = 'text/javascript';
                    newScript.innerHTML = scriptElements[i].innerHTML;
                    document.body.appendChild( newScript );
                }
            }
        }, 3000 );
    }

    function fetchDataBalloon(){
        $('.learndash_next_prev_link a.prev-link .meta-nav').attr('data-balloon-pos', 'up');
        $('.learndash_next_prev_link a.prev-link .meta-nav').attr('data-balloon', 'Previous');
        $('.learndash_next_prev_link a.next-link .meta-nav').attr('data-balloon-pos', 'up');
        $('.learndash_next_prev_link a.next-link .meta-nav').attr('data-balloon', 'Next');
    }

    function showLoading() {
        $( '#learndash-page-content' ).addClass( 'loading' );
    }

    function removeLoading() {
        $( '#learndash-page-content' ).removeClass( 'loading' );
    }

    $( document ).ready( function () {
        BBLMS_Sidebar.init();
    } );

} )( jQuery );
