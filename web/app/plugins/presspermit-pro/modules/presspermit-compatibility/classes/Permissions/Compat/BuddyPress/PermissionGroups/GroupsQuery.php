<?php
namespace PublishPress\Permissions\Compat\BuddyPress\PermissionGroups;

class GroupsQuery
{

    /**
     * List of found group ids
     *
     * @access private
     * @var array
     */
    private $results;

    /**
     * Total number of found groups for the current query
     *
     * @access private
     * @var int
     */
    private $total_groups = 0;

    public $query_vars;

    /**
     * PHP5 constructor
     *
     * @param string|array $args The query variables
     * @return WP_Group_Query
     */
    function __construct($query = null)
    {
        if (!empty($query)) {
            global $blog_id;

            // phpcs Note: exclude arg of Groups query has nothing to do with Posts query

            $this->query_vars = wp_parse_args($query, [
                'blog_id' => $blog_id,
                'include' => [],
                'exclude' => [],  // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
                'search' => '',
                'orderby' => 'login',
                'order' => 'ASC',
                'offset' => '', 'number' => '',
                'count_total' => true,
                'fields' => 'all',
            ]);

            $this->prepare_query();
            $this->query();
        }
    }

    function prepare_query()
    {
    }

    /**
     * Execute the query, with the current variables
     *
     * @since 3.1.0
     * @access private
     */
    function query()
    {
        $limit = (int) $this->query_vars['number'];

        $page = (PWP::is_REQUEST('paged')) ? PWP::REQUEST_int('paged') : 1;
        $user_id = false;

        // phpcs Note: Nonce verification unnecessary for search parameter of Groups listing

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        $search_terms = (!empty($_REQUEST['s'])) ? sanitize_text_field($_REQUEST['s']) : '';

        $populate_extras = false;

        if (version_compare(BP_VERSION, '1.5-dev', '<')) {
            if (!method_exists('BP_Groups_Group', 'get_active'))
                return;

            $_groups = \BP_Groups_Group::get_active($limit, $page, $user_id, $search_terms, $populate_extras);
        } else {
            if (!function_exists('groups_get_groups'))
                return;

            add_filter('bp_groups_get_paged_groups_sql', [$this, 'limit_groups_listing'], 10, 2);
            $args = ['type' => 'active', 'show_hidden' => true, 'search_terms' => $search_terms, 'populate_extras' => false];
            $_groups = groups_get_groups($args);
            remove_filter('bp_groups_get_paged_groups_sql', [$this, 'limit_groups_listing'], 10, 2);
        }

        $this->results = $_groups['groups'];

        if ($this->query_vars['count_total']) {
            $this->total_groups = $_groups['total'];
        }

        if (!$this->results)
            return;
    }

    function limit_groups_listing($query, $arr_sql)
    {
        if (is_multisite() && !is_super_admin()) {
            global $wpdb, $current_user;

            if (strpos($query, ' WHERE '))
                $query = str_replace(' WHERE ', " WHERE 1=1 AND creator_id = '$current_user->ID' AND ", $query);
            else
                $query = str_replace(" FROM {$wpdb->base_prefix}bp_groups ", " FROM {$wpdb->base_prefix}bp_groups WHERE creator_id = '$current_user->ID' ", $query);
        }
        return $query;
    }

    // obsolete
    function get_search_sql($string, $cols, $wild = false)
    {
		return '';
    }

    /**
     * Return the list of groups
     *
     * @access public
     *
     * @return array
     */
    function get_results()
    {
        return $this->results;
    }

    /**
     * Return the total number of groups for the current query
     *
     * @access public
     *
     * @return array
     */
    function get_total()
    {
        return $this->total_groups;
    }
}
