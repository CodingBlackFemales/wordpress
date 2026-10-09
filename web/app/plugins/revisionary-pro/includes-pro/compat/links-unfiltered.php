<?php

class RevisionaryUnfilteredLinks {
    function home_url( $path = '', $scheme = null ) {
        return $this->get_home_url( null, $path, $scheme );
    }
    
    function get_home_url( $blog_id = null, $path = '', $scheme = null ) {
        $orig_scheme = $scheme;
        
        global $wpdb;
        
        static $home_vals;
        
        if (!isset($home_vals)) {
            $home_vals = [];
        }
        
        $_blog_id = (empty($blog_id) || ! is_multisite()) ? 0 : $blog_id;
        
        if (!empty($home_vals[$_blog_id])) {
            $url = $home_vals[$_blog_id];
        } else {
            if ( empty( $blog_id ) || ! is_multisite() ) {
                $url = $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'home'");        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            } else {
                switch_to_blog( $blog_id );
                $url = $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'home'");        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                restore_current_blog();
            }
            
            $home_vals[$_blog_id] = $url;
        }
    
        if ( ! in_array( $scheme, array( 'http', 'https', 'relative' ), true ) ) {
            if ( is_ssl() ) {
                $scheme = 'https';
            } else {
                $scheme = wp_parse_url( $url, PHP_URL_SCHEME );
            }
        }
    
        $url = set_url_scheme( $url, $scheme );
    
        if ( $path && is_string( $path ) ) {
            $url .= '/' . ltrim( $path, '/' );
        }
    
        return $url;
    }
    
    function get_page_uri( $page = 0 ) {
        if ( ! $page instanceof WP_Post ) {
            $page = get_post( $page );
        }
    
        if ( ! $page ) {
            return false;
        }
    
        $uri = $page->post_name;
    
        foreach ( $page->ancestors as $parent ) {
            $parent = get_post( $parent );
            if ( $parent && $parent->post_name ) {
                $uri = $parent->post_name . '/' . $uri;
            }
        }
    
        return $uri;
    }
    
    function get_post_permalink( $post = 0, $leavename = false, $sample = false ) {
        global $wp_rewrite;
    
        $post = get_post( $post );
    
        if ( ! $post ) {
            return false;
        }
    
        $post_link = $wp_rewrite->get_extra_permastruct( $post->post_type );
    
        $slug = $post->post_name;
    
        $force_plain_link = wp_force_plain_post_permalink( $post );
    
        $post_type = get_post_type_object( $post->post_type );
    
        if ( $post_type->hierarchical ) {
            $slug = $this->get_page_uri( $post );
        }
    
        if ( ! empty( $post_link ) && ( ! $force_plain_link || $sample ) ) {
            if ( ! $leavename ) {
                $post_link = str_replace( "%$post->post_type%", $slug, $post_link );
            }
            $post_link = $this->home_url( user_trailingslashit( $post_link ) );
        } else {
            if ( $post_type->query_var && ( isset( $post->post_status ) && ! $force_plain_link ) ) {
                $post_link = add_query_arg( $post_type->query_var, $slug, '' );
            } else {
                $post_link = add_query_arg(
                    array(
                        'post_type' => $post->post_type,
                        'p'         => $post->ID,
                    ),
                    ''
                );
            }
            $post_link = $this->home_url( $post_link );
        }
    
        return $post_link;
    }

    function _get_page_link( $post = false, $leavename = false, $sample = false ) {
        global $wp_rewrite;
    
        $post = get_post( $post );
    
        $force_plain_link = wp_force_plain_post_permalink( $post );
    
        $link = $wp_rewrite->get_page_permastruct();
    
        if ( ! empty( $link ) && ( ( isset( $post->post_status ) && ! $force_plain_link ) || $sample ) ) {
            if ( ! $leavename ) {
                $link = str_replace( '%pagename%', get_page_uri( $post ), $link );
            }
    
            $link = $this->home_url( $link );
            $link = user_trailingslashit( $link, 'page' );
        } else {
            $link = $this->home_url( '?page_id=' . $post->ID );
        }
    
        return $link;
    }

    function get_page_link( $post = false, $leavename = false, $sample = false ) {
        $post = get_post( $post );
    
        if ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) == $post->ID ) {
            $link = $this->home_url( '/' );
        } else {
            $link = $this->_get_page_link( $post, $leavename, $sample );
        }
    
        return $link;
    }

    function get_attachment_link( $post = null, $leavename = false ) {
        global $wp_rewrite;
    
        $link = false;
    
        $post             = get_post( $post );
        $force_plain_link = wp_force_plain_post_permalink( $post );
        $parent_id        = $post->post_parent;
        $parent           = $parent_id ? get_post( $parent_id ) : false;
        $parent_valid     = true; // This is the default for no parent.
        if (
            $parent_id &&
            (
                $post->post_parent === $post->ID ||
                ! $parent ||
                ! is_post_type_viewable( get_post_type( $parent ) )
            )
        ) {
            // Post is either its own parent or parent post unavailable.
            $parent_valid = false;
        }
    
        if ( $force_plain_link || ! $parent_valid ) {
            $link = false;
        } elseif ( $wp_rewrite->using_permalinks() && $parent ) {
            if ( 'page' === $parent->post_type ) {
                $parentlink = $this->_get_page_link( $post->post_parent ); // Ignores page_on_front.
            } else {
                $parentlink = $this->get_permalink( $post->post_parent );
            }
    
            if ( is_numeric( $post->post_name ) || str_contains( get_option( 'permalink_structure' ), '%category%' ) ) {
                $name = 'attachment/' . $post->post_name; // <permalink>/<int>/ is paged so we use the explicit attachment marker.
            } else {
                $name = $post->post_name;
            }
    
            if ( ! str_contains( $parentlink, '?' ) ) {
                $link = user_trailingslashit( trailingslashit( $parentlink ) . '%postname%' );
            }
    
            if ( ! $leavename ) {
                $link = str_replace( '%postname%', $name, $link );
            }
        } elseif ( $wp_rewrite->using_permalinks() && ! $leavename ) {
            $link = $this->home_url( user_trailingslashit( $post->post_name ) );
        }
    
        if ( ! $link ) {
            $link = $this->home_url( '/?attachment_id=' . $post->ID );
        }
    
        return $link;
    }

    function get_permalink( $post = 0, $leavename = false ) {
        $rewritecode = array(
            '%year%',
            '%monthnum%',
            '%day%',
            '%hour%',
            '%minute%',
            '%second%',
            $leavename ? '' : '%postname%',
            '%post_id%',
            '%category%',
            '%author%',
            $leavename ? '' : '%pagename%',
        );
    
        if ( is_object( $post ) && isset( $post->filter ) && 'sample' === $post->filter ) {
            $sample = true;
        } else {
            $post   = get_post( $post );
            $sample = false;
        }
    
        if ( empty( $post->ID ) ) {
            return false;
        }
    
        if ( 'page' === $post->post_type ) {
            return $this->get_page_link( $post, $leavename, $sample );

        } elseif ( 'attachment' === $post->post_type ) {
            return $this->get_attachment_link( $post, $leavename );
            
        } elseif ( in_array( $post->post_type, get_post_types( array( '_builtin' => false ) ), true ) ) {
            return $this->get_post_permalink( $post, $leavename, $sample );
        }
    
        $permalink = get_option( 'permalink_structure' );
    
        if (
            $permalink &&
            ! wp_force_plain_post_permalink( $post )
        ) {
    
            $category = '';
            if ( str_contains( $permalink, '%category%' ) ) {
                $cats = get_the_category( $post->ID );
                if ( $cats ) {
                    $cats = wp_list_sort(
                        $cats,
                        array(
                            'term_id' => 'ASC',
                        )
                    );
    
                    /**
                     * Filters the category that gets used in the %category% permalink token.
                     *
                     * @since 3.5.0
                     *
                     * @param WP_Term  $cat  The category to use in the permalink.
                     * @param array    $cats Array of all categories (WP_Term objects) associated with the post.
                     * @param WP_Post  $post The post in question.
                     */
                    $category_object = apply_filters( 'post_link_category', $cats[0], $cats, $post );
    
                    $category_object = get_term( $category_object, 'category' );
                    $category        = $category_object->slug;
                    if ( $category_object->parent ) {
                        $category = get_category_parents( $category_object->parent, false, '/', true ) . $category;
                    }
                }
                /*
                 * Show default category in permalinks,
                 * without having to assign it explicitly.
                 */
                if ( empty( $category ) ) {
                    $default_category = get_term( get_option( 'default_category' ), 'category' );
                    if ( $default_category && ! is_wp_error( $default_category ) ) {
                        $category = $default_category->slug;
                    }
                }
            }
    
            $author = '';
            if ( str_contains( $permalink, '%author%' ) ) {
                $authordata = get_userdata( $post->post_author );
                $author     = $authordata->user_nicename;
            }
    
            /*
             * This is not an API call because the permalink is based on the stored post_date value,
             * which should be parsed as local time regardless of the default PHP timezone.
             */
            $date = explode( ' ', str_replace( array( '-', ':' ), ' ', $post->post_date ) );
    
            $rewritereplace = array(
                $date[0],
                $date[1],
                $date[2],
                $date[3],
                $date[4],
                $date[5],
                $post->post_name,
                $post->ID,
                $category,
                $author,
                $post->post_name,
            );
    
            $permalink = $this->home_url( str_replace( $rewritecode, $rewritereplace, $permalink ) );
            $permalink = user_trailingslashit( $permalink, 'single' );
    
        } else { // If they're not using the fancy permalink option.
            $permalink = $this->home_url( '?p=' . $post->ID );
        }
    
        return $permalink;
    }
    
    function preview_url_unfiltered($preview_url, $revision, $args) {
        static $busy;

        if (!empty($busy)) {
            return $preview_url;
        }

        $busy = true;
        
        if (is_scalar($revision)) {
            $revision = get_post($revision);
        }
    
        $defaults = ['post_type' => $revision->post_type];  // support preview url for past revisions, which are stored with post_type = 'revision'
        foreach(array_keys($defaults) as $var) {
            $$var = (!empty($args[$var])) ? $args[$var] : $defaults[$var]; 
        }
    
        if ('revision' == $post_type) {
            $post_type = get_post_field('post_type', $revision->post_parent);
        } else {
            if ($post_type_obj = get_post_type_object($revision->post_type)) {
                if (empty($post_type_obj->public) && !defined('FL_BUILDER_VERSION') && !apply_filters('revisionary_private_type_use_preview_url', false, $revision)) { // For non-public types, preview is not available so default to Compare Revisions screen
                    $url = apply_filters('revisionary_preview_url', rvy_admin_url("revision.php?revision=$revision->ID"), $revision, $args);

                    $busy = false;
                    return $url;
                }
            }
        }
        $post_type = sanitize_key($post_type);
    
        $link_type = apply_filters(
            'revisionary_preview_link_type',
            rvy_get_option('preview_link_type'),
            $revision
        );
    
        $status_obj = get_post_status_object(get_post_field('post_status', rvy_post_id($revision->ID)));
        $post_is_published = $status_obj && (!empty($status_obj->public) || !empty($status_obj->private));
    
        $preview_arg = (defined('RVY_PREVIEW_ARG')) ? sanitize_key(constant('RVY_PREVIEW_ARG')) : 'rv_preview';
    
        if ('id_only' == $link_type) {
            // support using ids only if theme or plugins do not tolerate published post url and do not require standard format with revision slug
            $preview_url = add_query_arg($preview_arg, true, $this->get_post_permalink($revision));
    
            if ('page' == $post_type) {
                $preview_url = str_replace('p=', "page_id=", $preview_url);
                $id_arg = 'page_id';
            } else {
                $id_arg = 'p';
            }
        } elseif (('revision_slug' == $link_type) || !$post_is_published) {
            $use_revision_slug = true;
            
        } else { // 'published_slug'
            $published_post_id = rvy_post_id($revision->ID);
            
            if (('page' === get_option('show_on_front')) && in_array(get_option('page_on_front'), [$published_post_id, $revision->ID]) && !defined('REVISIONARY_NORMAL_HOME_REVISION_PREVIEW')) {
                $use_revision_slug = true;
                $home_id_arg = 'page__id';
            } else {
                $id_arg = 'page_id';
            }

            // default to published post url, appended with 'preview' and page_id args
            $preview_url = add_query_arg($preview_arg, true, $this->get_permalink($published_post_id));
        }

        if (!empty($use_revision_slug)) {
            // support using actual revision slug in case theme or plugins do not tolerate published post url
            $preview_url = add_query_arg($preview_arg, true, $this->get_permalink($revision));
    
            if ('page' == $post_type) {
                $preview_url = str_replace('p=', "page_id=", $preview_url);
                $id_arg = 'page_id';
            } else {
                $id_arg = 'p';
            }
        }
    
        if (strpos($preview_url, "{$id_arg}=")) {
            $preview_url = remove_query_arg($id_arg, $preview_url);
        }
        
        $preview_url = add_query_arg($id_arg, $revision->ID, $preview_url);
    
        if (!empty($home_id_arg)) {
            $preview_url = add_query_arg($home_id_arg, $revision->ID, $preview_url);
        }

        if (!strpos($preview_url, "post_type=")) {
            $preview_url = add_query_arg('post_type', $post_type, $preview_url);
        }
    
        if (!defined('REVISIONARY_PREVIEW_NO_CACHEBUST')) {
            $preview_url = rvy_nc_url($preview_url);
        }
    
        $preview_url = apply_filters('revisionary_unfiltered_preview_url', $preview_url, $revision, $args);
        $preview_url = remove_query_arg('preview_id', $preview_url);
        
        $busy = false;
        return $preview_url;
    }
}
