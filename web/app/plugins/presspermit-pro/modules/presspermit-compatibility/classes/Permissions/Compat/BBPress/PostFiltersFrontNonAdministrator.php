<?php
namespace PublishPress\Permissions\Compat\BBPress;

class PostFiltersFrontNonAdministrator 
{
    function __construct() {
        add_filter('posts_request', [$this, 'flt_include_topic'], 50, 2);

        if (defined('PRESSPERMIT_TEASER_VERSION') && presspermit()->getTypeOption('tease_post_types', 'forum')) {
            require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BBPress/Teaser.php');
            new Teaser();
        }
    }

    function flt_include_topic($request, $query)
    {
        if (is_array($query->query_vars['post_type']) && function_exists('bbp_get_version')) {
            require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BBPress/Helper.php');
            $request = Helper::flt_include_topic($request, $query);
        }
        return $request;
    }
}
