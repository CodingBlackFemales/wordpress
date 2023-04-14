<?php 

/**
 * Events-Calendar Helper Functions
 * 
 * @since 1.6.6
 */

namespace BuddyBossTheme;

if ( !class_exists( '\BuddyBossTheme\EventsCalendarHelper' ) ) {

    Class EventsCalendarHelper {

        protected $_is_active = false;

        /**
         * Constructor
         */
        public function __construct () {

	        /**
	         * Add body class
	         */
	        add_filter( 'body_class', array( $this, 'add_body_class' ) );

        }

	    /**
	     *
	     * @param $classes
	     *
	     */
	    public function add_body_class( $classes ) {
			if ( function_exists('tribe_events_views_v2_is_enabled') && tribe_events_views_v2_is_enabled() ) {
				$classes[] = 'bb-tribe-events-views-v2';
			}

			return $classes;
	    }

    }

}