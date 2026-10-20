<?php
namespace PublishPress\Permissions\Compat;

class YoastSEO
{
    private $omit_post_ids;

    function __construct()
    {
        add_filter('wpseo_exclude_from_sitemap_by_post_ids', [$this, 'sitemap_exclusions']);
    }

    function sitemap_exclusions($post_ids)
    {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/Analyst.php');

        if (!isset($this->omit_post_ids)) {
            $this->omit_post_ids = Analyst::identify_restricted_posts(['identify_private_posts' => false]);
        }

        return $this->omit_post_ids;
    }
}
