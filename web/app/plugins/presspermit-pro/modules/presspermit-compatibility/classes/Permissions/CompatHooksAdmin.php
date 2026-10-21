<?php
namespace PublishPress\Permissions;

use PublishPress\Permissions\UI\SettingsAdmin;

class CompatHooksAdmin
{
    var $netwide_groups;

    var $preserve_post_parent = [];

    function __construct()
    {
        if (defined('CMS_TPV_VERSION') && defined('PRESSPERMIT_COLLAB_VERSION') && $this->isModuleEnabled('cms_tree_view_compatibility')) {
            require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/CMSTreeView.php');
            new Compat\CMSTreeView();
        }

        if (defined('CPTUI_VERSION')) {
            require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/CPTUI.php');
            new Compat\CPTUI();
        }

        add_action('presspermit_options_ui', [$this, 'optionsUI']);
        add_action('presspermit_teaser_settings_ui', [$this, 'teaserSettingsUI']);

        add_action('admin_enqueue_scripts', [$this, 'act_scripts']);

        if (is_multisite()) {
            if (did_action('init'))
                $this->load_options();
            else
                add_action('init', [$this, 'load_options']);
        }

        add_filter('cme_presspermit_capabilities', [$this, 'fltFlagPermissionsCapabilities'], 3);
        add_filter('presspermit_cap_descriptions', [$this, 'flt_cap_descriptions']);

        add_filter('presspermit_user_has_group_cap', [$this, 'flt_has_group_cap'], 10, 4);

        add_filter('presspermit_unfiltered_ajax_actions', [$this, 'fltUnfilteredAjaxActions']);

        if (!defined('PRESSPERMIT_NO_RELEVANSSI_FILTERS') && function_exists('relevanssi_init') && $this->isModuleEnabled('relevanssi_compatibility')) {
        	add_action('init', [$this, 'relevanssi_init']);
        }

        if (class_exists('Basepress')) {
            add_filter('presspermit_add_term_parent_arg', [$this, 'fltBasePressTermParentArg'], 10, 2);
        }

        if (class_exists('NestedPages') && $this->isModuleEnabled('nested_pages_compatibility')) {
            add_filter('nestedpages_listing_statuses', [$this, 'fltNestedPagesAddFilter']);

            if (!PWP::empty_REQUEST('page') && ('nestedpages' == PWP::REQUEST_key('page'))) {
                add_action('admin_print_footer_scripts', [$this, 'actNestedPagesScripts'], 20);
            }

            add_action('wp_ajax_npsort',
                function() {
                    global $wpdb;

                    if (!empty($_REQUEST['list'])) {        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        $list = (array) $_REQUEST['list'];  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended

                        $top_level_ids = [];

                        foreach (array_keys($list) as $k) {
                            if (!empty($list[$k]['id'])) {
                                $top_level_ids []= intval($list[$k]['id']);
                            }
                        }

                        $id_csv = implode("','", $top_level_ids);

                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                        $results = $wpdb->get_results("SELECT ID, post_parent FROM $wpdb->posts WHERE post_parent > 0 AND ID IN ('$id_csv')");

                        foreach ($results as $row) {
                            $this->preserve_post_parent[$row->ID] = $row->post_parent;
                        }
                    }
                }, 5
            );

            add_action('nestedpages_posts_order_updated',
                function($posts, $parent) {
                    global $wpdb;

                    foreach ($this->preserve_post_parent as $post_id => $post_parent) {
                        $wpdb->update($wpdb->posts, ['post_parent' => $post_parent], ['ID' => $post_id]);  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                        wp_cache_delete( $post_id, 'posts' );
                    }

                    return $posts;
                },
                10, 2
            );
        }
    }
    
    /**
     * Check if a specific integration module is enabled
     * @param string $module_id The module ID to check
     * @return bool True if module is enabled, false otherwise
     */
    private function isModuleEnabled($module_id)
    {
        $safe_module_id = preg_replace('/[^A-Z0-9_]/', '', strtoupper(str_replace('-', '_', $module_id)));
        $const_name = 'PRESSPERMIT_NO_' . $safe_module_id;
        return !defined($const_name);
    }

    function fltFlagPermissionsCapabilities($caps) {
        if (is_multisite()) {
            $caps = array_merge($caps, ['pp_create_network_groups', 'pp_manage_network_members']);
        }
       
        return $caps;
    }

    function flt_cap_descriptions($pp_caps)
    {
        if (is_multisite()) {
            $pp_caps['pp_create_network_groups'] = SettingsAdmin::getStr('cap_pp_create_network_groups'); 
            $pp_caps['pp_manage_network_members'] = SettingsAdmin::getStr('cap_pp_manage_network_members');
        }

        return $pp_caps;
    }

    function fltNestedPagesAddFilter($var) {
        add_filter('posts_results', [$this, 'fltNestedPagesFilterPosts'], 10, 2);
        return $var;
    }

    function actNestedPagesScripts() {
        $disable = [];

        if (current_user_can('pp_administer_content') || current_user_can('pp_nested_pages_unfiltered')) {
            return;
        }

        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /*
        define('PRESSPERMIT_NESTED_PAGES_LIMIT_ADD_CHILD_LINK', true);
        define('PRESSPERMIT_NESTED_PAGES_LIMIT_ADD_CHILD_PAGE', true);
        define('PRESSPERMIT_NESTED_PAGES_LIMIT_INSERT_PAGE_BEFORE', true);
        define('PRESSPERMIT_NESTED_PAGES_LIMIT_INSERT_PAGE_AFTER', true);
        define('PRESSPERMIT_NESTED_PAGES_LIMIT_PUSH_TO_TOP', true);
        define('PRESSPERMIT_NESTED_PAGES_LIMIT_PUSH_TO_BOTTOM', true);
        define('PRESSPERMIT_NESTED_PAGES_LIMIT_CLONE', true);
        */

        if (defined('PRESSPERMIT_NESTED_PAGES_LIMIT_ADD_CHILD_LINK') && PRESSPERMIT_NESTED_PAGES_LIMIT_ADD_CHILD_LINK) {
            $disable[] = 'a.open-redirect-modal';
        }

        if (defined('PRESSPERMIT_NESTED_PAGES_LIMIT_ADD_CHILD_PAGE') && PRESSPERMIT_NESTED_PAGES_LIMIT_ADD_CHILD_PAGE) {
            $disable[] = 'a.add-new-child';
        }

        if (defined('PRESSPERMIT_NESTED_PAGES_LIMIT_INSERT_PAGE_BEFORE') && PRESSPERMIT_NESTED_PAGES_LIMIT_INSERT_PAGE_BEFORE) {
            $disable[] = 'a[data-insert-before]';
        }

        if (defined('PRESSPERMIT_NESTED_PAGES_LIMIT_INSERT_PAGE_AFTER') && PRESSPERMIT_NESTED_PAGES_LIMIT_INSERT_PAGE_AFTER) {
            $disable[] = 'a[data-insert-after]';
        }

        if (defined('PRESSPERMIT_NESTED_PAGES_LIMIT_PUSH_TO_TOP') && PRESSPERMIT_NESTED_PAGES_LIMIT_PUSH_TO_TOP) {
            $disable[] = 'a[data-push-to-top]';
        }

        if (defined('PRESSPERMIT_NESTED_PAGES_LIMIT_PUSH_TO_BOTTOM') && PRESSPERMIT_NESTED_PAGES_LIMIT_PUSH_TO_BOTTOM) {
            $disable[] = 'a[data-push-to-bottom]';
        }

        if (defined('PRESSPERMIT_NESTED_PAGES_LIMIT_CLONE') && PRESSPERMIT_NESTED_PAGES_LIMIT_CLONE) {
            $disable[] = 'a.clone-post';
        }

        if (!empty($disable)) :?>
             <script type="text/javascript">
            /* <![CDATA[ */
            if (typeof(jQuery) != 'undefined') {
                jQuery(document).ready(function ($) {
                    <?php foreach($disable as $sel):?>
                        $('ul.nestedpages-dropdown-content <?php echo esc_html($sel);?>').remove();
                    <?php endforeach;?>
                });
            }
            /* ]]> */
            </script>
        <?php endif;
    }

    function fltNestedPagesFilterPosts($results, $query_obj) {
        $page_arg = (!PWP::empty_REQUEST('page')) ? PWP::REQUEST_key('page') : '';

        if (false === strpos($page_arg, 'nestedpages')) {
            return $results;
        }

        // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInTernaryCondition
        $post_type = ($dashpos = strpos($page_arg, '-')) 
        ? substr($page_arg, $dashpos+1)
        : 'page';

        require_once(PRESSPERMIT_CLASSPATH_COMMON . '/Ancestry.php');
        
        $ancestors = \PressShack\Ancestry::getPageAncestors(0, $post_type); // array of all ancestor IDs for keyed page_id, with direct parent first
        
        \PressShack\Ancestry::remapTree($results, $ancestors);

        remove_filter('posts_results', [$this, 'fltPostsResults'], 10, 2);

        return $results;
    }

    function optionsUI()
    {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/UI/Settings.php');
        new Compat\UI\Settings();
    }

    function teaserSettingsUI()
    {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/UI/Settings.php');
        new Compat\UI\Settings();
    }

    function fltUnfilteredAjaxActions($actions) {
        $actions = array_merge(
            $actions, 
            [
            	// @todo: specify plugin-specific ajax actions which need a post query filtering bypass
            ]
        );

        return $actions;
    }

    function relevanssi_init()
    {
        if (function_exists('relevanssi_query')) {  // wait until init action for this check
            // make sure posts with custom privacy are included in index
            require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/Relevanssi/HooksAdmin.php');
            new Compat\Relevanssi\HooksAdmin();
        }
    }

    function act_scripts()
    {
        if (in_array(presspermitPluginPage(), ['presspermit-settings', 'presspermit-posts-teaser'])) {
            $urlpath = plugins_url('', PRESSPERMIT_COMPAT_FILE);
            wp_enqueue_style('presspermit-compat-settings', $urlpath . '/common/css/settings.css', [], PRESSPERMIT_COMPAT_VERSION);

            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
            wp_enqueue_script('presspermit-compat-settings', $urlpath . "/common/js/settings{$suffix}.js", ['jquery'], PRESSPERMIT_COMPAT_VERSION);
        }
    }

    function load_options()
    {
        if (!is_multisite()) {
            return;
        }

        if ($this->netwide_groups = get_site_option('presspermit_netwide_groups')) {
            add_filter('presspermit_list_group_types', [$this, 'netwide_list_group_types']);
            add_filter('presspermit_query_group_type', [$this, 'netwide_query_agent_type']);
            add_filter('presspermit_query_group_variant', [$this, 'netwide_query_agent_type']);
            add_filter('presspermit_editable_group_types', [$this, 'netwide_editable_group_types'], 10, 3);
            add_action('delete_user', [$this, 'actDeleteUsers']);

            add_filter('presspermit_user_search_site_only', [$this, 'user_search_site_only'], 10, 2);
            add_filter('presspermit_agents_selection_ui_args', [$this, 'agents_selection_ui_args'], 50, 3);
        } else {
            if (is_network_admin()) {
                add_filter('presspermit_editable_group_types', [$this, 'netwide_no_groups'], 10, 3);
            }
        }
    }

    function agents_selection_ui_args($args, $agent_type, $id_suffix)
    {
        if (('user' == $agent_type) && PWP::is_REQUEST('agent_type', 'pp_net_group')) {
            $args['context'] = 'pp_net_group';
        }

        return $args;
    }

    // when user search ajax is used on multisite, should we limit the results set to users registered for the current site?
    function user_search_site_only($site_only, $args)
    {
        if (('pp_net_group' == $args['context']) && !defined('PP_NETWORK_GROUPS_SITE_USERS_ONLY') 
        && (is_super_admin() || current_user_can('pp_manage_network_members')) 
        && (!is_main_site() || defined('PP_NETWORK_GROUPS_MAIN_SITE_ALL_USERS'))
        ) {
            return false;
        }

        return $site_only;
    }

    function flt_has_group_cap($has_sitewide, $cap_name, $group_id, $group_type)
    {
        if (is_multisite() && ('pp_net_group' == $group_type)) {
            switch ($cap_name) {
                case 'pp_manage_members' :
                    return is_super_admin() || current_user_can('pp_manage_network_members');
                    break;

                case 'pp_create_groups' :
                    return is_super_admin() || current_user_can('pp_create_network_groups');
                    break;
            }
        }

        return $has_sitewide;
    }

    public function fltBasePressTermParentArg($parent_arg, $taxonomy) {
        if ('knowledgebase_cat' == $taxonomy) {
            if (PWP::empty_REQUEST('section')) {
                $parent_arg = 'product';
            } else {
                $parent_arg = 'section';
            }
        }

        return $parent_arg;
    }

    function netwide_editable_group_types($types)
    {
        global $pagenow;
        
        return (is_network_admin() || ('user-edit.php' == $pagenow)) 
        ? array_diff(array_merge($types, ['pp_net_group']), ['pp_group']) 
        : array_merge($types, ['pp_net_group']);
    }

    function netwide_no_groups($types)
    {
        return [];
    }

    function actDeleteUsers($user_ids)
    {
        global $wpdb;
        $id_csv = implode("','", array_map('intval', (array)$user_ids));

        // phpcs Note: Direct subquery on plugin table for admin operation

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            "DELETE FROM $wpdb->pp_group_members_netwide WHERE user_id IN '$id_csv'"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );
    }

    function netwide_membership_editable($editable, $agent_type, $agent)
    {
        if ('pp_net_group' == $agent_type)
            return true;

        return $editable;
    }

    function netwide_query_agent_type($agent_type)
    {
        if (!$agent_type)
            $agent_type = 'pp_net_group';

        return $agent_type;
    }

    function netwide_list_group_types($group_types)
    { 
        if (is_main_site()) {
            unset($group_types['pp_group']);
        }

        return $group_types;
    }
}
