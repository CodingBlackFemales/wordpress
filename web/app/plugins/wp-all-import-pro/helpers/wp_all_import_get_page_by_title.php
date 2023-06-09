<?php

if ( ! function_exists( 'wp_all_import_get_page_by_title' ) ) {

    function wp_all_import_get_page_by_title( $title, $post_type = 'page' ) {

        $posts = get_posts(
            array(
                'post_type'              => $post_type,
                'title'                  => $title,
                'post_status'            => 'all',
                'numberposts'            => 1,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
                'orderby'                => 'post_date ID',
                'order'                  => 'ASC',
            )
        );

        if ( ! empty( $posts ) ) {
            $page = $posts[0];
        } else {
            $page = null;
        }

        return $page;

    }
}
