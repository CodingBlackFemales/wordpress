( function ( $ ) {

    "use strict";

    window.BuddyBossThemeGami = {
        init: function () {
            this.wpautopFix();
            this.tableWrap();
        },

        wpautopFix: function() {
            $( '.gamipress-rank-excerpt p:empty' ).remove();
            $( '.gamipress-achievement-excerpt p:empty' ).remove();
        },

        tableWrap: function() {
            $( ".gamipress_leaderboard_widget .gamipress-leaderboard-table" ).wrap( "<div class='table-responsive'></div>" );
        }

    };

    $( document ).on( 'ready', function () {
        BuddyBossThemeGami.init();
    } );

} )( jQuery );
