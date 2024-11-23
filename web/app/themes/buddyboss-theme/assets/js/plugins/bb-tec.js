( function ( $ ) {

    "use strict";

    window.BuddyBossThemeTec = {
        init: function () {
            this.eventsCalendar();
        },

        eventsCalendar: function() {
            function iCalBtnText() {
                var iCal = $( '#tribe-events-content a.tribe-events-ical' );
                var newiCal = iCal.text().replace( '+', '' );
                iCal.text( newiCal );
            }
            function igCalBtnText() {
                var iCal = $( 'a.tribe-events-gcal' );
                var newiCal = iCal.text().replace( '+', '' );
                iCal.text( newiCal );
            }
            iCalBtnText();
            igCalBtnText();

            $( document ).ajaxComplete( function () {
                iCalBtnText();
            } );

            $( '.bs-week-header span' ).html( function ( i, v ) {
                return $.trim( v ).replace( /(\w+)/g, '<span class="br-week-title">$1</span>' );
            } );

            var last_visible_filter = $( '.tribe-events-filters-vertical #tribe_events_filters_form > div:visible:last' );
            last_visible_filter.addClass( 'bs-last-filter' );

            function organizerImgHeight() {
                var fiHeight = $( '.bs-organize-sq-fi' ).height();
                var wrHeight = $( '.bs-organize-sq-wr' ).height();

                if ( fiHeight > wrHeight ) {
                    $( '.bs-organize-sq-fi' ).css( {
                        'margin-bottom': '0'
                    } );
                } else {
                    $( '.bs-organize-sq-fi' ).css( {
                        'margin-bottom': '20px'
                    } );
                }
            }
            organizerImgHeight();

            $( window ).on( 'resize', function () {
                organizerImgHeight();
            } );

            function prevNextSingleText() {
                var sNext = $( '.tribe-events-single #tribe-events-footer .tribe-events-nav-next a' );
                var sPrev = $( '.tribe-events-single #tribe-events-footer .tribe-events-nav-previous a' );
                sNext.text( buddyboss_theme_tec_js.next_event_string );
                sPrev.text( buddyboss_theme_tec_js.prev_event_string );
            }
            prevNextSingleText();

            function checkForNotice() {
                if ( $( '#tribe-events-content h2.tribe-events-page-title' ).next().is( '.tribe-events-notices' ) ) {
                    $( '#tribe-events-content h2.tribe-events-page-title' ).addClass( 'has-notice' );
                } else {
                    $( '#tribe-events-content h2.tribe-events-page-title' ).removeClass( 'has-notice' );
                }
            }
            checkForNotice();

            $( document ).ajaxComplete( function () {
                checkForNotice();
            } );

            $( '#tribe-bar-date' ).attr( 'autocomplete', 'off' );
        },

    };

    $( document ).on( 'ready', function () {
        BuddyBossThemeTec.init();
    } );

} )( jQuery );
