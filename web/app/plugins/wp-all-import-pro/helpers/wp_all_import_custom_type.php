<?php
if ( ! function_exists( 'wp_all_import_custom_type' ) ) {
    function wp_all_import_custom_type( $type = '' ) {
        $custom_types = apply_filters( 'pmxi_custom_types', [], 'custom_types' );

        if (isset($custom_types[$type])) {
            return $custom_types[$type];
        }

        return get_post_type_object( $type );
    }
}
