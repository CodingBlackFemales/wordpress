<?php
namespace PublishPress\Permissions\Compat;

class CMSTreeView 
{
    function __construct() {
        add_filter('presspermit_get_pages_args', [$this, 'get_pages_args']);
        add_filter('get_pages', [$this, 'filter_posts'], 50, 2);
        add_filter('presspermit_get_pages_parent', [$this, 'parent_clause'], 10, 2);

        add_action('wp_ajax_cms_tpv_move_page', [$this, 'move_page'], 5);
        add_action('wp_ajax_cms_tpv_add_page', [$this, 'add_page'], 5);
        add_action('wp_ajax_cms_tpv_add_pages', [$this, 'add_pages'], 5);

        add_action('admin_print_scripts', [$this, 'no_add']);

        if (PWP::is_REQUEST('page', 'cms-tpv-page-page')) {
            add_action('pre_get_posts', [$this, 'pre_get_posts']);
        }
    }

    // Prevent PublishPress Revisions statuses from confusing the page listing
    function pre_get_posts($wp_query) {
        $stati = array_diff(get_post_stati(), apply_filters('revisionary_cmstpv_omit_statuses', ['pending-revision', 'future-revision'], PWP::findPostType()));
        $wp_query->query['post_status'] = $stati;
        $wp_query->query_vars['post_status'] = $stati;
    }

    function add_page()
    {
        if (!$page_id = PWP::REQUEST_int('pageID')) {
            return;
        }

        if (current_user_can('pp_administer_content')) {
            return;
        }

        if (!$ref_post = get_post($page_id)) {
            return;
        }

        $parent_id = (PWP::is_REQUEST('type', 'inside')) ? $ref_post->ID : $ref_post->post_parent;

        $_post_type = PWP::REQUEST_key('post_type');

        $this->limit_parent($parent_id, $_post_type);
    }

    function add_pages()
    {
        if (!$ref_post_id = PWP::empty_REQUEST('ref_post_id')) {
            return;
        }

        if (current_user_can('pp_administer_content')) {
            return;
        }

        if (!$ref_post = get_post($ref_post_id)) {
            return;
        }

        $parent_id = (PWP::is_REQUEST('inside', 'cms_tpv_add_type')) ? $ref_post->ID : $ref_post->post_parent;

        $this->limit_parent($parent_id, $ref_post->post_type);
    }

    function move_page()
    {
        if (!$ref_node_id = PWP::REQUEST_int('ref_node_id')) {
            return;
        }

        if (current_user_can('pp_administer_content')) {
            return;
        }

        if (!$ref_post = get_post($ref_node_id)) {
            return;
        }

        $parent_id = (PWP::is_REQUEST('type', 'inside')) ? $ref_post->ID : $ref_post->post_parent;

        $this->limit_parent($parent_id, $ref_post->post_type);
    }

    function limit_parent($parent_id, $post_type)
    {
        $user = presspermit()->getUser();

        $include_ids = $user->getExceptionPosts('associate', 'include', $post_type);
        $exclude_ids = $user->getExceptionPosts('associate', 'exclude', $post_type);
        $additional_ids = $user->getExceptionPosts('associate', 'additional', $post_type);

        if (in_array($parent_id, $additional_ids) || ($parent_id && in_array($parent_id, $include_ids))) {
            return;  // user has an additional or include exception for requested parent.  

            // note: CMS Tree UI Add inside/after links not filtered, but site-wide 
            // constants PP_CMS_TREE_NO_ADD, PP_CMS_TREE_NO_ADD_PAGE, PP_CMS_TREE_NO_ADD_THINGY etc. 
            // are applied when user has any page association exceptions
        }

        if (((count($include_ids) || in_array($parent_id, $exclude_ids)) && !in_array($parent_id, $include_ids))
            || (defined('PRESSPERMIT_COLLAB_VERSION') && !$parent_id && !Collab::userCanAssociateMain($post_type))
        ) {
            // user has an exclude exception for requested parent OR an include exception for "(none)"

            if (function_exists('_default_wp_die_handler')) {
                $function = apply_filters('wp_die_handler', '_default_wp_die_handler');
                $msg = '<p>' 
                . esc_html__('Page creation was blocked because you are not allowed to select that parent page.', 'presspermit-pro') 
                . '</p><p><a href="' . admin_url('') . '">' . esc_html__('Back to Dashboard', 'presspermit-pro') . '</a></p>';
               
                call_user_func($function, $msg, esc_html__('Permission Denied', 'presspermit-pro'));
            } else
                wp_die(esc_html__('You are not allowed to select that parent page.', 'presspermit-pro'));
        }
    }

    function get_pages_args($args)
    {
        // CMS Tree Page View passes orderby arg with multiple values separated by spaces
        if (!empty($args['orderby']) && is_scalar($args['orderby'])) {
            $args['sort_column'] = str_replace(' ', ',', $args['orderby']);
        }

        return $args;
    }

    function filter_posts($posts, $args)
    {
        if (isset($args['xsuppress_filters']) && isset($args['post_type']) && ('post' == $args['post_type'])) {
            global $wpdb;

            $post_ids = [];
            foreach (array_keys($posts) as $i)
                $post_ids[] = $posts[$i]->ID;

            $id_csv = implode("','", array_map('intval', $post_ids));

            // phpcs Note: Direct query on posts table to support permissions filtering of CMS Tree View output

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $ok_ids = $wpdb->get_col(
                apply_filters(                      // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    'presspermit_posts_request', 
                    "SELECT ID FROM $wpdb->posts WHERE post_type = 'post' AND ID IN ('$id_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                )
            );
            
            $altered = false;

            foreach (array_keys($posts) as $i) {
                if (!in_array($posts[$i]->ID, $ok_ids)) {
                    unset($posts[$i]);
                    $altered = true;
                }
            }

            if ($altered)
                $posts = array_values($posts);
        }

        return $posts;
    }

    // otherwise, pages editable via exception are not displayed if they have an uneditable parent
    function parent_clause($parent_clause, $args = [])
    {
        $user = presspermit()->getUser();

        if ($page_exceptions = array_merge(
            $user->getExceptionPosts('edit', 'additional', $args['post_type']), 
            $user->getExceptionPosts('edit', 'include', $args['post_type'])
        )) {
            if (empty($args['parent'])) {
                $id_csv = implode("','", $page_exceptions);
                $parent_clause = "( ( $parent_clause ) OR ( ID IN ( '$id_csv' ) AND post_parent NOT IN ( '$id_csv' ) ) )";
            }
        }

        return $parent_clause;
    }

    function no_add()
    {
        if (current_user_can('pp_administer_content')) {
            return;
        }
        ?>
        <style type="text/css">
            <?php foreach( presspermit()->getEnabledPostTypes() as $post_type ):
                if ( defined( 'PP_CMS_TREE_NO_ADD' ) || defined( 'PP_CMS_TREE_NO_ADD_' . strtoupper( $post_type ) ) ) {
                    global $current_screen;
                
                    $user = presspermit()->getUser();
            
                    $include_ids = $user->getExceptionPosts( 'associate', 'include', $post_type );
                    $exclude_ids = $user->getExceptionPosts( 'associate', 'exclude', $post_type );
                    
                    if ( count($include_ids) || count($exclude_ids) || ( defined( 'PRESSPERMIT_COLLAB_VERSION' ) 
                    && ! Collab::userCanAssociateMain( $post_type ) ) 
                    ) {
                        echo "a.cms_tpv_action_add_{" . esc_attr($post_type) , "_after, a.cms_tpv_action_add_" . esc_attr($post_type) . "_inside, span.cms_tpv_action_add_" . esc_attr($post_type) . " { display:none }";
                    }
                }
            endforeach;?>
        </style>
        <?php
    }
}
