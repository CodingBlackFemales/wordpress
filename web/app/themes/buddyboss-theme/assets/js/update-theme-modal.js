/**
 * Loads for Update theme modal.
 *
 * @since [BBVERSION]
 */
( function ( $ ) {
    $( document ).ready( function () {
        // Trigger the update if the user accepts the modal's warning.
        $( document ).on( 'click', '#bb-update-theme', function ( evt ) {
            evt.preventDefault();
            $( '#theme-hello-backdrop' ).show();
            $( '#theme-hello-container' ).show();
            return false;
        } );
        
        $( document ).on( 'click', '#update-theme', function ( evt ) {
            evt.preventDefault();
            $( '#theme-hello-backdrop' ).remove();
            $( '#theme-hello-container' ).remove();
        } );
        
        $( document ).on( 'click', '#bb-skip-now', function ( evt ) {
            evt.preventDefault();
            $( '#theme-hello-backdrop' ).hide();
            $( '#theme-hello-container' ).hide();
            $( 'input[value="buddyboss-theme"]' ).prop( 'checked', false );
        } );
        
        $( document ).on( 'change', 'input[value="buddyboss-theme"]', function ( evt ) {
            if ( this.checked ) {
                $( '#theme-hello-backdrop' ).show();
                $( '#theme-hello-container' ).show();
                if ( $( '#update-theme' ).length ) {
                    $( '#update-theme' ).attr( 'href', 'javascript:void(0);' );
                    $( '#update-theme' ).attr( 'id', 'bb-update-theme-yes' );
                }
            }
        } );
        
        $( document ).on( 'click', '#bb-update-theme-yes', function ( evt ) {
            evt.preventDefault();
            $( '#theme-hello-backdrop' ).hide();
            $( '#theme-hello-container' ).hide();
            $( 'input[value="buddyboss-theme"]' ).prop( 'checked', true );
        } );
    
        $( document ).on( 'change', '#themes-select-all', function ( evt ) {
            if ( this.checked && $( 'input[value="buddyboss-theme"]' ).prop( 'checked', true ) ) {
                $( '#theme-hello-backdrop' ).show();
                $( '#theme-hello-container' ).show();
                if ( $( '#update-theme' ).length ) {
                    $( '#update-theme' ).attr( 'href', 'javascript:void(0);' );
                    $( '#update-theme' ).attr( 'id', 'bb-update-theme-yes' );
                }
            }
        } );
    } );
}( jQuery ) );

