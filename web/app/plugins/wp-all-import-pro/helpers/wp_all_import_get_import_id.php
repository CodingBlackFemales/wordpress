<?php

if ( ! function_exists( 'wp_all_import_get_import_id' ) ) {
    function wp_all_import_get_import_id() {
        global $argv;
        $import_id = 'new';
            
        if ( ! empty( $argv ) ) {

			// First check for the ID set by the WP_CLI code.
			$temp_id = apply_filters('wp_all_import_cli_import_id', false);

			if($temp_id !== false && is_numeric($temp_id)){
				$import_id = $temp_id;
			}else {

				// Try to get the ID from the CLI arguments if it's not found otherwise.
				$import_id_arr = array_filter( $argv, function ( $a ) {
					return ( is_numeric( $a ) ) ? true : false;
				} );

				if ( ! empty( $import_id_arr ) ) {
					$import_id = reset( $import_id_arr );
				}
			}
        }
    
        if ( $import_id == 'new' ) {
            if ( isset( $_GET['import_id'] ) ) {
                $import_id = $_GET['import_id'];
            } elseif ( isset( $_GET['id'] ) ) {
                $import_id = $_GET['id'];
            }
        }

        return $import_id;
    }
}