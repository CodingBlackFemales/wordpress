<?php
define( 'BEAVER_BB__DIR', plugin_dir_path( __FILE__ ) );
define( 'BEAVER_BB__URL', plugins_url( '/', __FILE__ ) );

/**
 * Custom modules
 */
function bb_fl_load_custom_modules() {
	if ( class_exists( 'FLBuilder' ) ) {
		require_once __DIR__ . '/header-bar/header-bar.php';
	}
}

add_action( 'init', 'bb_fl_load_custom_modules' );
