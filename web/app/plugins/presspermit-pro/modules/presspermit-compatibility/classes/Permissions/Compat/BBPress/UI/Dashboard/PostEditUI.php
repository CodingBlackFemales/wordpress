<?php
namespace PublishPress\Permissions\Compat\BBPress\UI\Dashboard;

class PostEditUI
{
    function __construct()
    {
        if (function_exists('bbp_get_version')) {
            add_filter('query', [$this, 'flt_dropdown_private_topics']);
        }
    }

    function flt_dropdown_private_topics($query)
    {
        if (strpos($query, "post_type = 'topic' AND")) {
            require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BBPress/UI/Dashboard/Helper.php');
            $query = Helper::dropdown_include_private_topics($query);
        }
        return $query;
    }
}
