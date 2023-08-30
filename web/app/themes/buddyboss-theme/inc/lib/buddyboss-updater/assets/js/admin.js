jQuery( document ).ready( function( $ ){
    if( $( '.tooltip-persistent-container' ).length > 0 ){
        $( '.tooltip-persistent-container' ).mouseenter(
            function( e ){
                $(this).addClass('hover');
            }
        );

        $( '.tooltip-persistent-container' ).mouseleave(
            function( e ){
                $(this).removeClass('hover');
            }
        );
    }

    jQuery(".license-key-input").on("input", function(){
        jQuery('input[name="license_key"]').val(jQuery(this).val());
    });
    jQuery("#show-license").on("click", function(e){
        e.preventDefault();
        jQuery('.license-key-input').val(jQuery('input[name="license_key"]').val());
        jQuery('#hide-license').show();
        jQuery(this).hide();
    });
    jQuery("#hide-license").on("click", function(e){
        e.preventDefault();
        jQuery('.license-key-input').val(jQuery('.license-key-input').data('value'));
        jQuery('input[name="license_key"]').val(jQuery('input[name="license_key"]').data('value'));
        jQuery('#show-license').show();
        jQuery(this).hide();
    });

    jQuery( '.bb-theme .bb-hide-pw' ).on( 'click', function ( e ) {
        e.preventDefault();
        var $this  = $( this );
        var $input = $this.closest( '.password-toggle' ).find( 'input' );
        var $default_type = $input.data( 'type' ) ? $input.data( 'type' ) : 'text';
        $this.toggleClass( 'bb-show-pass' );
        if ( $this.hasClass( 'bb-show-pass' ) ) {
            $input.attr( 'type', $default_type );
        } else {
            $input.attr( 'type', 'password' );
        }
    } );

    BBOSS_UPDATER_ADMIN.bb_connect.init();
} );

BBOSS_UPDATER_ADMIN.bb_connect = {};
(function(me, window, $) {
    var _l = {};

    me.init = function(){
        if( !me.getElements() )
            return;

        _l.$connector_button.click(function(){
            _l.$overlay_outer.show();
            $('body').addClass('bb_connect_overlay');

            $('.bb_connect .connecting').show();

            var left = Number((screen.width/2)-(390/2));
            var top = Number((screen.height/2)-(555/2));

            _l.win = window.open( BBOSS_UPDATER_ADMIN.connector_url, "Connect to BuddyBoss.com", "width=390,height=555,top="+top+",left="+left+"" );

            var popupTick = setInterval(function() {
                if (_l.win.closed) {
                    clearInterval(popupTick);
                    $('body').removeClass('bb_connect_overlay');
                    _l.$overlay_outer.hide();
                }
            }, 500);
        });
    };

    me.getElements = function(){
        _l.$overlay_outer = $('#bb_connector_overlay_wrapper');
        if( _l.$overlay_outer.length == 0 )
            return false;

        _l.$connector_button = $( '#btn_bb_connect' );
        return true;
    };

    me.receive_message = function( event ){
        var data = event.data;
        if( event.origin != BBOSS_UPDATER_ADMIN.connector_host && data.message_type != 'updater_bb_connect' )
            return false;


        data.action = 'updater_bb_connect_received_message';
        data.nonce = BBOSS_UPDATER_ADMIN.nonce_received_message;
        $.ajax({
            method: 'POST',
            url: ajaxurl,
            data: data,
            success: function( response ){
                $('body').removeClass('bb_connect_overlay');
                _l.$overlay_outer.hide();

                response = $.parseJSON( response );
                if( response.status ){
                    if( response.message ){
                        alert( response.message );
                    }

                    if( response.redirect_to ){
                        window.location.href = response.redirect_to;
                    }
                }
            },
            error: function(){
                $('body').removeClass('bb_connect_overlay');
                _l.$overlay_outer.hide();
                alert( 'Error - Operation Failed.' );
            }
        });
    };

})(BBOSS_UPDATER_ADMIN.bb_connect, window, window.jQuery);

window.addEventListener("message", BBOSS_UPDATER_ADMIN.bb_connect.receive_message, false);
