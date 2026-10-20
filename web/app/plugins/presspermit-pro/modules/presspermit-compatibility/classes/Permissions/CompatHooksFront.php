<?php
namespace PublishPress\Permissions;

class CompatHooksFront 
{
    function __construct() {
        add_action('presspermit_init', [$this, 'actInitFront']);

        if (PWP::isPluginActive('snazzy-archives')) {
            add_filter('query', [$this, 'fltSnazzyArchives']);
        }

        // SearchWP plugin
        if (PWP::isPluginActive('searchwp') && $this->isModuleEnabled('searchwp_compatibility')) {
            add_filter('searchwp_pre_search_terms', [$this, 'fltSearchwpPreSearchTerms']);
        }

        add_action('pre_get_posts', [$this, 'actEnableQueryFilters']);

        // Display Posts plugin
        add_filter('display_posts_shortcode_args', [$this, 'fltDisplayPostsArgs'], 10, 2);

        add_filter('presspermit_nav_menu_ignore_post_types', [$this, 'fltNavMenuSkipPostTypes']);
    }

    private function isModuleEnabled($module_id)
    {
        $const_name = str_replace('-', '_', strtoupper($module_id));
        return !defined($const_name);
    }

    function actInitFront()
    {
        if (presspermit()->filteringEnabled()) {
            if (!PWP::empty_REQUEST('s') && function_exists('relevanssi_query') && !defined('PRESSPERMIT_NO_RELEVANSSI_FILTERS')) {
                require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/Relevanssi/HooksFront.php');
                new Compat\Relevanssi\HooksFront();
            }
        }
    }

    function fltNavMenuSkipPostTypes($ignore_post_types) {
        // Groovy Menu compat
		$ignore_post_types[] = 'gm_menu_block';

        return $ignore_post_types;
    }

    function fltDisplayPostsArgs($args, $orig_args) {
        // Display Posts plugin: Allow normal filtering of privacy statuses
        unset($args['post_status']);
        return $args;
    }

    function fltSnazzyArchives($query)
    {
        if (strpos($query, "posts WHERE post_status = 'publish' AND post_password = '' AND post_type IN (")) {
            return apply_filters(
                'presspermit_posts_request', 
                str_replace("post_status = 'publish' AND ", '', $query)
            );
        }

        return $query;
    }

    function fltSearchwpPreSearchTerms($arg)
    {
        // SearchWP applies its own term filtering, including queries for excluded terms. 
        // Applying PP filters to those queries can defeat SearchWP term exclusion.
        add_filter('presspermit_terms_skip_filtering', [$this, 'fltSearchWPtermsSkipFiltering']);
        return $arg;
    }

    function fltSearchWPtermsSkipFiltering($skip)
    {
        return $skip || !did_action('searchwp_include');
    }

    function actEnableQueryFilters($query_obj)
    {
        $enabled_types = presspermit()->getEnabledPostTypes();

        // default to overriding suppress_filters flag for all enabled post types except post and page
        $filter_types = (defined('PP_FORCE_QUERY_TYPES')) 
        ? array_map('trim', explode(',', PP_FORCE_QUERY_TYPES)) 
        : array_diff(array_keys($enabled_types), ['post', 'page']);
        
        $filter_types = array_intersect($filter_types, $enabled_types);

        if (isset($query_obj->query_vars['post_type']) && in_array($query_obj->query_vars['post_type'], ['notices'], true)) {
            // phpcs:ignore WordPressVIPMinimum.Hooks.PreGetPosts.PreGetPosts
            $query_obj->query_vars['suppress_filters'] = false;
        }
    }
}
