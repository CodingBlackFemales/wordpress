<?php
namespace PublishPress\Permissions\Compat\BBPress;

class HooksFront
{
    function __construct() {
        add_action('presspermit_post_filters_front_non_administrator', [$this, 'actPostFiltersFrontNonAdministrator']);

        add_filter('bbp_get_forum_subforum_count', [$this, 'flt_count_private_subforums'], 10, 2);
        add_filter('presspermit_options', [$this, 'enable_topic_teaser']);
        add_filter('posts_join', [$this, 'handle_search_join'], 10, 2);

        if (class_exists('GDATTCore')) { // GD bbPress Attachments
            add_filter('presspermit_posts_clauses_intercept', [$this, 'fltPostsClausesIntercept'], 10, 4);
        }
    }

    function fltPostsClausesIntercept($intercept, $clauses, $wp_query, $args)
    {
        // GD bbPress Attachments executes a secondary query which we don't filter correctly. There's really no need to filter it, 
        // since this is the attachments query for topics or replies already determined to be readable.  If the query does somehow 
        // return attachments which have access blocked, File Access will block the image or file directly.
        if ( in_array(PWP::findPostType(), ['topic', 'reply']) && PWP::empty_POST() && PWP::isFront()
             && !empty($wp_query) && !empty($wp_query->query_vars) && is_array($wp_query->query_vars)
             && (!empty($wp_query->query_vars['post_type']) && ('attachment' == $wp_query->query_vars['post_type']))
             && !empty($wp_query->query_vars['post_parent']) 
			 && (empty($wp_query->query_vars['orderby']) || ('ID' == $wp_query->query_vars['orderby'])) 
             && (empty($clauses['where']) || (false === strpos($clauses['where'], 'meta_value IN')))
        ) {
            return $clauses;
        }

        return $intercept;
    }

    function actPostFiltersFrontNonAdministrator()
    {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BBPress/PostFiltersFrontNonAdministrator.php');
        new PostFiltersFrontNonAdministrator();
    }

    // force BBpress to include private subforums in the count so we have a chance to filter them into the list (or not) 
    // based on supplemental role assignment
    function flt_count_private_subforums($forum_count, $forum_id)
    {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BBPress/Helper.php');
        return Helper::flt_count_private_subforums($forum_count, $forum_id);
    }

    public function enable_topic_teaser($options)
    {
        if (isset($options['presspermit_tease_post_types'])) {
            $tease_post_types = maybe_unserialize($options['presspermit_tease_post_types']);
            if (!empty($tease_post_types['forum'])) {
                $tease_post_types['topic'] = '1';                                         // phpcs Note: values sanitized on retrieval
                $options['presspermit_tease_post_types'] = serialize($tease_post_types);  // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
            }
        }

        return $options;
    }
    
    public function handle_search_join($join, $query_obj)
    {
        if (!empty($query_obj->bbp_is_search) && !strpos($join, 'AS pp_bbpf')) {
            global $wpdb;
            $join .= " INNER JOIN $wpdb->postmeta AS pp_bbpf ON pp_bbpf.post_id = $wpdb->posts.ID AND pp_bbpf.meta_key = '_bbp_forum_id'";

            add_filter('presspermit_adjust_posts_where_clause', [$this, 'flt_search_where_clause'], 10, 4);
            add_filter('posts_results', [$this, 'remove_search_where_filter']);
        } else {
            remove_filter( 'presspermit_adjust_posts_where_clause', [$this, 'flt_search_where_clause'], 10, 4 );
        }

        return $join;
    }

	public function flt_search_where_clause( $alternate_where, $type_where, $post_type, $args ) {
        static $busy;

        if (!empty($busy)) {
            return $alternate_where;
        }

        $busy = true;

        if ( in_array( $post_type, ['forum', 'topic', 'reply'], true) && ( 'read' == $args['required_operation'] ) ) {
            $alternate_where = $type_where . " AND pp_bbpf.meta_value IN ("
            . " SELECT ID FROM {$args['src_table']} WHERE 1=1 " 
            . \PublishPress\Permissions\PostFilters::instance()->getPostsWhere(['post_types' => 'forum', 'required_operation' => 'read']) 
            . " )";
		}

        $busy = false;
		return $alternate_where;
    }
    
    public function remove_search_where_filter( $results ) {
		remove_filter( 'presspermit_adjust_posts_where_clause', [$this, 'flt_search_where_clause'], 10, 4 );
		return $results;
	}
}
