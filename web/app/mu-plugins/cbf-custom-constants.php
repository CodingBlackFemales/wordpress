<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LEARNDASH_TEMPLATE_CONTENT_METHOD', 'template' );

add_filter( 'file_mod_allowed', 'allow_object_cache_dropin_file_mods', 10, 2 );
function allow_object_cache_dropin_file_mods( $allowed, $context ) {
	return ( $context === 'object_cache_dropin' ) || $allowed;
}
