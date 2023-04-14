( function ( $ ) {
    $.fn.BossSocialMenu = function ( reduceWidth ) {
        $( this ).each( function () {
            //alignMenu( this );
            var elem = this,
                $elem = $( this );

            window.addEventListener( 'resize', run_alignMenu );
            window.addEventListener( 'load', run_alignMenu );

            function run_alignMenu() {
                $elem.append( $( $( $elem.children( 'li.hideshow' ) ).children( 'ul' ) ).html() );
                $elem.children( 'li.hideshow' ).remove();
                alignMenu( elem );
            }

            function alignMenu( obj ) {
                var self = $( obj ),
                    w = 0,
                    i = -1,
                    menuhtml = '',
                    mw = self.width() - reduceWidth;

                $.each( self.children(), function () {
                    i++;
                    w += $( this ).outerWidth( true );
                    if ( mw < w ) {
                        menuhtml += $( '<div>' ).append( $( this ).clone() ).html();
                        $( this ).remove();
                    }
                } );

                self.append( '<li class="hideshow menu-item-has-children1"><a class="more-button" href="#"><i class="bb-icon-f bb-icon-ellipsis-h"></i></a><ul class="sub-menu">' + menuhtml + '</ul></li>' );

                if ( self.find( 'li.hideshow' ).find( 'li' ).length > 0 ) {
                    self.find( 'li.hideshow' ).show();
                } else {
                    self.find( 'li.hideshow' ).hide();
                }
            }

            //Vertical nav condition
            function checkVerticalMenu() {

                if( $( window ).width() > 748 && $elem.parent().hasClass( 'vertical' ) ) {

                    if( $elem.find( 'li.hideshow' ).length ) {

                        var verticalmenuhtml = '';

                        $.each( $elem.find( 'li.hideshow ul' ).children(), function () {
                            verticalmenuhtml +=  $( this ).wrap('<p/>').parent().html();
                            $( this ).parent().remove();
                        } );

                        $elem.append( verticalmenuhtml );
                        $elem.append( $( $( $elem.children( 'li.hideshow' ) ).children( 'ul' ) ).html() );
                        $elem.children( 'li.hideshow' ).remove();

                    } else {
                        return;
                    }

                }

            }

            window.addEventListener( 'resize', checkVerticalMenu );
            window.addEventListener( 'load', checkVerticalMenu );

        } );
    }
}( jQuery ) );