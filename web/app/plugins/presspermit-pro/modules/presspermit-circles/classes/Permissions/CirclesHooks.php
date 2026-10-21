<?php
namespace PublishPress\Permissions;

class CirclesHooks
{
    function __construct() 
    {
        require_once(PRESSPERMIT_CIRCLES_ABSPATH . '/db-config.php');

        add_filter('presspermit_default_options', [$this, 'default_options']);

        add_filter('presspermit_append_query_clause', [$this, 'fltAppendQueryClause'], 10, 4);

        add_filter('presspermit_get_pp_groups_for_user', [$this, 'fltGetGroupsForUser'], 10, 4);
        add_action('presspermit_pre_init', [$this, 'actVersionCheck']);

        add_filter('presspermit_exclude_arbitrary_caps', [$this, 'fltExcludeArbitraryCaps']);
        add_filter('presspermit_group_circles', [$this, 'fltGroupCircles'], 10, 4);

        // Gutenberg: filter users selection UI
        add_action('enqueue_block_editor_assets', [$this, 'actLogPostID']);
        add_filter('rest_user_query', [$this, 'fltRestUserQuery'], 10, 2);

        // Classic: filter users selection UI
        add_action('pre_get_users', [$this, 'fltAvailableAuthors'], 5);
        add_action('pre_get_users', [$this, 'fltListUsers'], 5);
        add_filter('pre_count_users', [$this, 'fltCountUsers'], 10, 3);
        add_filter('presspermit_select_author_ids', [$this, 'fltSelectAuthorIDs'], 10, 2);

        add_filter('presspermit_block_user_edit', [$this, 'fltBlockUserEdit'], 10, 2);

        // Post Update: filter user selection
        add_filter('wp_insert_post_data', [$this, 'fltPostData'], 50, 2);
    }

    function default_options($def)
    {
        $new = [
            'access_circles_limit_revisions' => 0,
        ];

        return array_merge($def, $new);
    }

    function actLogPostID() {
        global $current_user, $post;

        if (!empty($post) && !empty($post->ID)) {
            set_transient("ppcc_editing_post_{$current_user->ID}", $post->ID, 10000);
        }
    }

    function fltRestUserQuery($query_args, $request) {
        global $current_user;

        if (!empty($query_args['who']) && ('authors' == $query_args['who'])) {
            if (current_user_can('pp_exempt_edit_circle')) {
                return $query_args;
            }

            if ($post_id = get_transient("ppcc_editing_post_{$current_user->ID}")) {
                $post_type = get_post_field('post_type', $post_id);

                if ($user_circles = Circles::getCircleMembers('edit')) {
                    if (!empty($user_circles[$post_type])) {
                        $stored_author = get_post_field('post_author', $post_id);

                        if (in_array($stored_author, $user_circles[$post_type])) {
                            $query_args['include'] = $user_circles[$post_type];
                        }
                    }
                }

                delete_transient("ppcc_editing_post_{$current_user->ID}");
            }
        }

        return $query_args;
    }

    // filter post author selection UI
    function fltAvailableAuthors($query_obj) {
        if (!empty($query_obj->query_vars['who']) && ('authors' == $query_obj->query_vars['who'])) {
            if (current_user_can('pp_exempt_edit_circle')) {
                return $query_obj;
            }
            
            if ($post_id = PWP::getPostID()) {
                $post_type = get_post_field('post_type', $post_id);

                if ($user_circles = Circles::getCircleMembers('edit')) {
                    if (!empty($user_circles[$post_type])) {
                        $stored_author = get_post_field('post_author', $post_id);

                        if (in_array($stored_author, $user_circles[$post_type])) {
                            $query_obj->query_vars['include'] = $user_circles[$post_type];
                        }
                    }
                }
            }
        }

        return $query_obj;
    }

    // filter Users listing
    function fltListUsers($query_obj) {
        global $current_user, $pagenow;

        if (('users.php' == $pagenow) && (empty($query_obj->query_vars['who']) || ('' == $query_obj->query_vars['who']))) {
            if (current_user_can('pp_exempt_read_circle')) {
                return $query_obj;
            }

            if ($user_circles = Circles::getCircleMembers('read')) {
                if (!empty($user_circles['user'])) {
                    if (in_array($current_user->ID, $user_circles['user'])) {
                        $query_obj->query_vars['include'] = $user_circles['user'];
                    }
                }
            }
        }

        return $query_obj;
    }

    function fltCountUsers($return, $strategy, $site_id) {
        global $current_user, $pagenow, $wpdb;

        if (('users.php' != $pagenow) || !empty($query_obj->query_vars['who']) || current_user_can('pp_exempt_read_circle')) {
            return $return;
        }

        $user_circles = Circles::getCircleMembers('read');

        if (empty($user_circles['user']) || !in_array($current_user->ID, $user_circles['user'])) {
            return $return;
        }
    
        $blog_prefix = $wpdb->get_blog_prefix( $site_id );
        $result      = array();
    
        if ( 'time' === $strategy ) {
            if ( is_multisite() && get_current_blog_id() != $site_id ) {
                switch_to_blog( $site_id );
                $avail_roles = wp_roles()->get_names();
                restore_current_blog();
            } else {
                $avail_roles = wp_roles()->get_names();
            }
    
            // Build a CPU-intensive query that will return concise information.
            $select_count = array();
            foreach ( $avail_roles as $this_role => $name ) {
                $select_count[] = $wpdb->prepare( 'COUNT(NULLIF(`meta_value` LIKE %s, false))', '%' . $wpdb->esc_like( '"' . $this_role . '"' ) . '%' );
            }
            $select_count[] = "COUNT(NULLIF(`meta_value` = 'a:0:{}', false))";
            $select_count   = implode( ', ', $select_count );
    
            $user_id_csv = implode("','", array_map('intval', $user_circles['user']));

            // Add the meta_value index to the selection list, then run the query.

            // phpcs Note: Direct query of users tables because the count_users() function must be filtered by results, not by query clause

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $row = $wpdb->get_row(
                $wpdb->prepare(                         
                    // phpcs Note: query clauses constructed and sanitized above
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users
                    "SELECT {$select_count}, COUNT(*) FROM {$wpdb->usermeta} INNER JOIN {$wpdb->users} ON user_id = ID WHERE meta_key = %s AND $wpdb->users.ID IN ('$user_id_csv')",  

                    $wpdb->get_blog_prefix($site_id) . 'capabilities'
                ),
                ARRAY_N
            );
    
            // Run the previous loop again to associate results with role names.
            $col         = 0;
            $role_counts = array();
            foreach ( $avail_roles as $this_role => $name ) {
                $count = (int) $row[ $col++ ];
                if ( $count > 0 ) {
                    $role_counts[ $this_role ] = $count;
                }
            }
    
            $role_counts['none'] = (int) $row[ $col++ ];
    
            // Get the meta_value index from the end of the result set.
            $total_users = (int) $row[ $col ];
    
            $result['total_users'] = $total_users;
            $result['avail_roles'] =& $role_counts;
        } else {
            $avail_roles = array(
                'none' => 0,
            );
    
            // phpcs Note: Direct query of users tables because the count_users() function must be filtered by results, not by query clause

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $users_of_blog = $wpdb->get_col(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users
                    "SELECT meta_value FROM {$wpdb->usermeta} INNER JOIN {$wpdb->users} ON user_id = ID WHERE meta_key = %s AND $wpdb->users.ID IN ('$user_id_csv')",

                    $wpdb->get_blog_prefix($site_id) . 'capabilities'
                )
            );
    
            foreach ( $users_of_blog as $caps_meta ) {
                $b_roles = maybe_unserialize( $caps_meta );
                if ( ! is_array( $b_roles ) ) {
                    continue;
                }
                if ( empty( $b_roles ) ) {
                    $avail_roles['none']++;
                }
                foreach ( $b_roles as $b_role => $val ) {
                    if ( isset( $avail_roles[ $b_role ] ) ) {
                        $avail_roles[ $b_role ]++;
                    } else {
                        $avail_roles[ $b_role ] = 1;
                    }
                }
            }
    
            $result['total_users'] = count( $users_of_blog );
            $result['avail_roles'] =& $avail_roles;
        }
    
        return $result;
    }

    // filter user selection on post update
    function fltPostData($data, $postarr) {
        if (current_user_can('pp_exempt_edit_circle')) {
            return $data;
        }
        
        if ($user_circles = Circles::getCircleMembers('edit')) {
            $post_type = $postarr['post_type'];

            if (!empty($user_circles[$post_type]) && !in_array($data['post_author'], $user_circles[$post_type])) {
                $stored_author = get_post_field('post_author', $postarr['ID']);

                if (in_array($stored_author, $user_circles[$post_type])) {
                    $data['post_author'] = $stored_author;
                }
            }
        }

        return $data;
    }

    function fltSelectAuthorIDs($ids, $post_type) {
        global $wpdb;

        if (current_user_can('pp_exempt_edit_circle')) {
            return $ids;
        }

        if ($user_circles = Circles::getCircleMembers('edit')) {
            if (!empty($user_circles[$post_type])) {
                $ids = array_map('intval', $user_circles[$post_type]);
            }
        }

        return $ids;
    }

    function fltBlockUserEdit($block, $target_user_id) {
        if ($user_circles = Circles::getCircleMembers('edit')) {
            if (!empty($user_circles['user'])) {
                if (!in_array($target_user_id, $user_circles['user'])) {
                    $block = true;
                }
            }
        }

        return $block;
    }

    function fltExcludeArbitraryCaps($caps)
    {
        return array_merge($caps, ['pp_exempt_read_circle', 'pp_exempt_edit_circle']);
    }

    function fltAppendQueryClause($append, $object_type, $required_operation, $args)
    {
        $circle_type = ('read' == $required_operation) ? 'read' : 'edit';

        if (!presspermit()->isContentAdministrator() && !current_user_can("pp_exempt_{$circle_type}_circle")) {
            if (is_single() && !is_admin()) {
                global $post;

                if (!empty($post) && !empty($post->post_type) && ('attachment' == $post->post_type)) {
                    $circle_type = 'read';
                    $object_type = 'attachment';
                }
            }

            $circle_members = Circles::getCircleMembers($circle_type);

            if (!empty($circle_members[$object_type])) {
                global $wpdb;
                $src_table = (!empty($args['src_table'])) ? $args['src_table'] : $wpdb->posts;

                if (defined('PUBLISHPRESS_REVISIONS_VERSION') && !presspermit()->getOption('access_circles_limit_revisions')) {
                    $revisions_clause = "OR $src_table.post_mime_type IN ('" . implode("','", apply_filters('rvy_revision_statuses', ['draft-revision', 'pending-revision', 'future-revision'])) . "')";

                } elseif (defined('REVISIONARY_VERSION') && !presspermit()->getOption('access_circles_limit_revisions')) {
                    $revisions_clause = "OR $src_table.post_status IN ('" . implode("','", apply_filters('rvy_revision_statuses', ['pending-revision', 'future-revision'])) . "')";
                } else {
                    $revisions_clause = '';
                }

                if (defined('PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION')) {	// (or use an alternate join without user_id in ON clause?)
                    $circle_members_login = [];
                    
                    foreach($circle_members[$object_type] as $user_id) {
                    	if ($user = new \WP_User($user_id)) {
                    		$circle_members_login []= $user->user_login;	
                    	}
                	}
                
                	$circle_members_login_csv = implode("','", $circle_members_login);
                    
                    // phpcs Note: Direct subquery on term_relationships table to avoid the complexity of risk of ensuring a join table upstream in the calling code's logical flow

                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $subquery = "SELECT object_id FROM $wpdb->term_relationships AS tr "
                    		  . " INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id"
                    		  . " INNER JOIN $wpdb->terms AS t ON t.term_id = tt.term_id"
                    		  . " WHERE t.name IN ('$circle_members_login_csv')";

                    $append .= " AND ( $src_table.post_author IN ('" . implode("','", $circle_members[$object_type]) . "') $revisions_clause";
                    $append .= " OR ( $src_table.ID IN ($subquery) ) )";
                } else {
                	$append .= " AND $src_table.post_author IN ('" . implode("','", $circle_members[$object_type]) . "') $revisions_clause";
                }
            }
        }

        return $append;
    }

    // join clause for circles was appended to query.  Now reprocess results, creating a circles property for each group.
    function fltGetGroupsForUser($user_groups, $results, $user_id, $args = [])
    {
        if (!empty($args['circle_type'])) {
            foreach ($results as $row) {
                if (!isset($user_groups[$row->group_id]->circles)) {
                    $user_groups[$row->group_id]->circles = [];
                }

                $user_groups[$row->group_id]->circles[$row->circle_type][$row->post_type] = true;

                // since we are aggregating circle data from multiple rows, avoid confusion in calling function
                unset($user_groups[$row->group_id]->circle_type);
                unset($user_groups[$row->group_id]->post_type);
            }
        }

        return $user_groups;
    }

    function actVersionCheck()
    {
        $ver = get_option('ppcc_version');
        $pp_ver = get_option('presspermit_version');

        if (!is_array($ver) || empty($ver['db_version']) || version_compare(PRESSPERMIT_CIRCLES_DB_VERSION, $ver['db_version'], '!=') 
        || ($pp_ver && version_compare($pp_ver['version'], '3.2.7', '<'))
        ) {
            require_once(PRESSPERMIT_CIRCLES_CLASSPATH . '/DB/DatabaseSetup.php');
            $db_ver = (is_array($ver) && isset($ver['db_version'])) ? $ver['db_version'] : '';
            new Circles\DB\DatabaseSetup($db_ver);
            update_option('ppcc_version', ['version' => PRESSPERMIT_CIRCLES_VERSION, 'db_version' => PRESSPERMIT_CIRCLES_DB_VERSION]);
        }

        if (!empty($ver['version'])) {
            // These maintenance operations only apply when a previous version of PPCC was installed 
            if (version_compare(PRESSPERMIT_CIRCLES_VERSION, $ver['version'], '!=')) {
                require_once(PRESSPERMIT_CIRCLES_CLASSPATH . '/Updated.php');
                new Circles\Updated($ver['version']);
                update_option('ppcc_version', ['version' => PRESSPERMIT_CIRCLES_VERSION, 'db_version' => PRESSPERMIT_CIRCLES_DB_VERSION]);
            }
        } else {
            // first execution after install
            if (!get_option('ppperm_added_ppcc_role_caps_10beta')) {
                require_once(PRESSPERMIT_CIRCLES_CLASSPATH . '/Updated.php');
                Circles\Updated::populateRoles(true);
            }
        }
    }

    function fltGroupCircles($circles, $group_type, $group_id, $circle_type) {
        return array_merge((array)$circles, Circles::getGroupCircles($group_type, $group_id, $circle_type));
    }
}
