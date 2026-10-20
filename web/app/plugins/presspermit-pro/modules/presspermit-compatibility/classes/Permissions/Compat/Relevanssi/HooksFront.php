<?php
namespace PublishPress\Permissions\Compat\Relevanssi;

class HooksFront
{
    var $valid_stati = [];
    var $relevanssi_results = [];

    function __construct()
    {
        global $relevanssi_variables, $relevanssi_free_plugin_version, $relevanssi_plugin_version;

        $this->valid_stati = get_post_stati(['public' => true, 'private' => true], 'names', 'OR');

        if (!empty($relevanssi_variables) || !empty($relevanssi_free_plugin_version) || !empty($relevanssi_plugin_version)) {
            remove_filter('relevanssi_post_ok', 'relevanssi_default_post_ok', 10, 2);
            add_filter('relevanssi_post_ok', [$this, 'relevanssi_post_ok'], 10, 2);
        } else {
            remove_filter('relevanssi_post_ok', 'relevanssi_default_post_ok');
            add_filter('relevanssi_post_ok', [$this, 'relevanssi_post_ok_legacy']);
        }

        if (defined('PRESSPERMIT_TEASER_VERSION')) {
            if (function_exists('relevanssi_query') && has_filter('posts_pre_query', 'relevanssi_query')) { 
                remove_filter('posts_pre_query', 'relevanssi_query', 99, 2);
                add_filter('the_posts', 'relevanssi_query', 99, 2);
            }
        }

        add_filter('relevanssi_results', [$this, 'relevanssi_log_results']);
    }

    function relevanssi_log_results($arr)
    {
        if (is_array($arr)) {
            $this->relevanssi_results = $arr;

            global $wpdb;

            $id_csv = implode("','", array_map('intval', array_keys($arr)));

            // phpcs Note: Direct query on posts table for Relevanssi compat (@todo: include other fields in wp_cache_add() ?)

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $results = $wpdb->get_results(
                "SELECT ID, post_name, post_type, post_status, post_author, post_parent FROM $wpdb->posts "
                . " WHERE 1=1 AND ID IN ('$id_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            );

            foreach ($results as $row) {
                wp_cache_add($row->ID, $row, 'posts');
            }
        }

        return $arr;
    }

    // Premium 1.8 or higher and free 2.9.15 or higher will have the two-parameter version. 
    // Older versions don't actually even have the version parameter.
    function relevanssi_post_ok_legacy($doc)
    {
        return $this->relevanssi_post_ok(false, $doc);
    }

    function relevanssi_post_ok($post_ok, $doc)
    {
        static $set_listed_ids = false;

        if ((0 === strpos($doc, 't_')) || (0 === strpos($doc, 'u_')))
            return (function_exists('relevanssi_default_post_ok')) ? relevanssi_default_post_ok($post_ok, $doc) : $post_ok;

        if (!$set_listed_ids) {
            $pp = presspermit();

            $set_listed_ids = true;
            foreach ($pp->getEnabledPostTypes() as $post_type) {  // since we don't know post types...
                $pp->listed_ids[$post_type] = array_fill_keys(array_keys($this->relevanssi_results), true);
            }
        }
        if (function_exists('relevanssi_s2member_level')) {
            // back compat with relevanssi_default_post_ok, in case somebody is also running s2member
            if (relevanssi_s2member_level($doc) == 0) return false;
        }

        $status = relevanssi_get_post_status($doc);

        $teasing_post = false;

        if ($tease_types = apply_filters('presspermit_teased_post_types', [], 'post', [], [])) {
            if ($_post = get_post($doc)) {
                if (in_array($_post->post_type, $tease_types, true)) {
                    $teasing_post = true;
                }
            }
        }

        if (in_array($status, $this->valid_stati, true))
            $post_ok = $teasing_post || current_user_can('read_post', $doc);
        else
            $post_ok = false;

        return $post_ok;
    }
}
