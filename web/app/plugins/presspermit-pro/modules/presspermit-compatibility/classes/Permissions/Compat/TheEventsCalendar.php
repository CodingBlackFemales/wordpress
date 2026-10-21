<?php
namespace PublishPress\Permissions\Compat;

class TheEventsCalendar {
    function __construct() {
        add_filter('presspermit_has_post_cap_vars', [$this, 'fltHasPostCapVars'], 10, 4);
        add_filter('presspermit_teaser_readable_posts', [$this, 'fltTeaserReadablePosts'], 10, 4);
        add_filter('presspermit_supported_private_types', [$this, 'supportedPrivateTypes']);
        add_filter('presspermit_enabled_post_types', [$this, 'enabledPostTypes']);
        add_filter('presspermit_unfiltered', [$this, 'fltPressPermitUnfiltered'], 10, 2);
    }

    private function normalizeEventIds($event_ids) {
        $event_ids = array_map('intval', (array) $event_ids);

        return array_values(array_filter($event_ids));
    }

    public function supportedPrivateTypes($types) {
        if (post_type_exists('tribe_event_series')) {
            $types[] = 'tribe_event_series';
        }

        return array_values(array_unique(array_filter((array) $types)));
    }

    public function enabledPostTypes($types) {
        if (post_type_exists('tribe_event_series') && in_array('tribe_events', (array) $types, true)) {
            $types['tribe_event_series'] = 'tribe_event_series';
        }

        return $types;
    }

    public function fltPressPermitUnfiltered($unfiltered, $args) {
        if ($unfiltered || empty($args['query_obj']) || !is_object($args['query_obj'])) {
            return $unfiltered;
        }

        $query_obj = $args['query_obj'];

        if (!empty($query_obj->tribe_is_event_venue)) {
            return true;
        }

        $post_types = [];

        if (!empty($query_obj->query_vars['post_type'])) {
            $post_types = array_filter((array) $query_obj->query_vars['post_type']);
        } elseif (!empty($query_obj->queried_object) && is_object($query_obj->queried_object) && !empty($query_obj->queried_object->post_type)) {
            $post_types = [$query_obj->queried_object->post_type];
        }

        if (array_intersect($post_types, ['tribe_event_series'])) {
            return true;
        }

        return $unfiltered;
    }

    function fltHasPostCapVars($modify_vars, $wp_sitecaps, $pp_reqd_caps, $post_cap_args) {
        global $wpdb;
        
        if (empty($post_cap_args['post_id']) || !is_scalar($post_cap_args['post_id'])) {
            return $modify_vars;
        }

        $post_id = $post_cap_args['post_id'];
        
        // The Events Calendar Pro recurring instances can pass a pseudo event ID.
        if ('tribe_events' == \PublishPress\Permissions\PostFilters::postTypeFromCaps($post_cap_args['orig_reqd_caps'])) {
            $_post = get_post($post_id);

            if ($_post && is_object($_post) && is_a($_post, 'WP_Post') && !empty($_post->post_type) && ('tribe_events' == $_post->post_type) && !empty($_post->guid)) {
                static $event_actual_ids;

                if (!isset($event_actual_ids)) {
                    $event_actual_ids = [];

                    if (!empty(presspermit()->listed_ids['tribe_events'])) {
                        $queried_event_ids = $this->normalizeEventIds(presspermit()->listed_ids['tribe_events']);
                    } else {
                        if ($post_id > 1000000) {
                            // phpcs Note: Direct query under special conditions to work around a The Events Calendar issue (without additional filter applications)

                            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                            if ($actual_post_id = $wpdb->get_var(
                                $wpdb->prepare(
                                    "SELECT ID FROM $wpdb->posts WHERE guid = %s",
                                    $_post->guid
                                )
                            )) {
                                $event_actual_ids[$_post->guid] = (int) $actual_post_id;
                                $modify_vars['post_id'] = $actual_post_id;
                                return $modify_vars;
                            }
                        }

                        $queried_event_ids = $this->normalizeEventIds([$post_id]);
                    }

                    if (!$queried_event_ids) {
                        return $modify_vars;
                    }

                    $id_csv = implode("','", $queried_event_ids);

                    // phpcs Note: Direct query under special conditions to work around a The Events Calendar issue (without additional filter applications)

                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $results = $wpdb->get_results(
                        "SELECT ID, guid FROM $wpdb->posts WHERE ID IN ('$id_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    );

                    foreach($results as $row) {
                        $event_actual_ids[$row->guid] = $row->ID;
                    }
                }

                if (empty($event_actual_ids[$_post->guid]) && ($post_id > 1000000)) {
                    // Rebuild the pseudo-ID mapping if an earlier request path initialized the cache
                    // before populating this recurring event's GUID entry.
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    if ($actual_post_id = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT ID FROM $wpdb->posts WHERE guid = %s",
                            $_post->guid
                        )
                    )) {
                        $event_actual_ids[$_post->guid] = (int) $actual_post_id;
                    }
                }

                if (!in_array($post_id, $event_actual_ids, true) && isset($event_actual_ids[$_post->guid])) {
                    $modify_vars['post_id'] = $event_actual_ids[$_post->guid];
                }
            }
        }

        return $modify_vars;
    }

    function fltTeaserReadablePosts($readable_posts, $request, $filtered_request, $args) {
        global $wpdb;
        
        $post_ids = (!empty($args['post_ids'])) ? $args['post_ids'] : [];
        $post_ids = $this->normalizeEventIds($post_ids);

        if (!$readable_posts_fake_ids = array_diff($readable_posts, $post_ids)) {
            return $readable_posts;
        }

        if (!$post_ids) {
            return $readable_posts;
        }

        $id_csv = implode("','", $post_ids);

        // phpcs Note: Direct query to support permissions filtering of The Events Calendar posts (without additional filter applications)

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            "SELECT ID, guid FROM $wpdb->posts WHERE ID IN ('$id_csv') AND post_type = 'tribe_events'"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );

        $event_actual_ids = [];

        foreach($results as $row) {
            $event_actual_ids[$row->guid] = $row->ID;
        }

        foreach($readable_posts_fake_ids as $fake_id) {
            if ($_post = get_post($fake_id)) {
                if (!empty($event_actual_ids[$_post->guid])) {
                    $readable_posts []= $event_actual_ids[$_post->guid];
                    $readable_posts = array_diff($readable_posts, [$fake_id]);
                }
            }
        }

        return $readable_posts;
    }
}
