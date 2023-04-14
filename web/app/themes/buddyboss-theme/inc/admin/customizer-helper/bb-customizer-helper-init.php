<?php
defined( 'ABSPATH' ) || exit;

/**
 * Function will load required file after redux action call.
 *
 * @since 1.8.4
 *
 * @param object $core Return object of main redux class.
 */
if ( ! function_exists( 'bb_customizer_helper_callback' ) ) {
	function bb_customizer_helper_callback( $core ) {
		$path         = dirname( __FILE__ );
		$helper_class = 'BB_Customizer_Helper';
		if ( ! class_exists( $helper_class ) ) {
			// In case you wanted override your override, hah.
			$class_file = $path . '/bb-customizer-helper.php';
			if ( $class_file ) {
				require_once( $class_file );
			}
		}
		new $helper_class( $core );
	}
	
	add_action( "redux/extensions/before", 'bb_customizer_helper_callback', 1 );
}
