 jQuery( document ).ready( function( $ ) {
    var LD_Zapier = {
        init: function() {
            this.toggle();
            this.add_legacy_message();
            this.add_current_class_to_submenu_item();
        },
        toggle: function() {
            var $events = {
                'course': [ 
                    'enrolled_into_course',
                    'course_completed',
                ],
                'lesson': [
                    'lesson_completed',
                ],
                'topic': [
                    'topic_completed',
                ],
                'quiz': [
                    'quiz_passed',
                    'quiz_failed',
                    'quiz_completed',
                ],
            };

            $.each( $events, function( $index, $array ) {
                $( '.zapier_trigger' ).change( function( e ) {
                    var $value = $( this ).val();
                    if ( $.inArray( $value, $array ) != -1 ) {
                        $( '.zapier_trigger_' + $index ).show();
                    } else {
                        $( '.zapier_trigger_' + $index ).hide();
                    }
                } );  

                $( window ).load( function() {
                    var $value = $( '.zapier_trigger' ).val();
                    if ( $.inArray( $value, $array ) != -1 ) {
                        $( '.zapier_trigger_' + $index ).show();
                    } else {
                        $( '.zapier_trigger_' + $index ).hide();
                    }
                } );   
            } );
        },
        add_legacy_message: function() {
            if ( $( '.post-type-sfwd-zapier' ).length > 0 ) {
                $( '.wp-header-end' ).after( '<p>' + LD_Zapier_Params.webhook_message + '</p>' );
            }
        },
        add_current_class_to_submenu_item: function() {
            $( 'a[href="admin.php?page=learndash-zapier-settings"]' ).addClass( 'current' ).parent( 'li' ).addClass( 'current' );
        }
    };

    LD_Zapier.init();
} );