<?php
namespace PublishPress\Permissions\FileAccess;

class AttachmentTemplate
{
    public static function regulateReadAccess()
    {
        if (defined('PP_ATTACHMENT_TEMPLATE_PASSTHRU') && PP_ATTACHMENT_TEMPLATE_PASSTHRU)
            return;

        global $post, $wp_query, $wpdb;

        $_post = (!empty($post)) ? $post : false;

        if (empty($_post)) {
            if (!empty($wp_query->query_vars['attachment_id'])) {
                // phpcs Note: Direct query on posts table during low-level filtering operation to query based on attachment_id without further filtering

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $_post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND ID = %d", $wp_query->query_vars['attachment_id']));

            } elseif (!empty($wp_query->query_vars['attachment'])) {
                // phpcs Note: Direct query on posts table during low-level filtering operation to query based on attachment_id without further filtering

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $_post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND post_name = %d", $wp_query->query_vars['attachment']));
            }
        }

        if (empty($_post->ID) || (!current_user_can('read_post', $_post->ID) && !current_user_can('edit_post', $_post->ID))) {
            $wp_query->is_single = false;
            $wp_query->is_page = false;

            if (defined('PPFF_STATUS_CODE') && is_numeric(constant('PPFF_STATUS_CODE'))) {
                $code = (!empty($_post)) ? constant('PPFF_STATUS_CODE') : 404;
                status_header($code);
            } else
                $code = 404;

            if (404 == $code)
                $wp_query->is_404 = true;

            if (403 == $code)
                $wp_query->is_403 = true;

            if ($template = get_query_template($code)) {
                include($template);
            }

            exit();
        }
    }

    // Filter attachment page content prior to display by attachment template.
    // Note: teaser-subject direct file URL requests also land here
    public static function teaserFilter()
    {
        global $post, $wpdb;

        $pp = presspermit();

        $teaser_applied = false;

        $_post = (!empty($post)) ? $post : false;

        if (empty($_post)) {
            global $wp_query;

            if (!empty($wp_query->query_vars['attachment_id'])) {
                // phpcs Note: Direct query on posts table during low-level filtering operation to query based on attachment_id without further filtering

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $_post = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND ID = %d",
                        $wp_query->query_vars['attachment_id']
                    )
                );

            } elseif (!empty($wp_query->query_vars['attachment'])) {
                // phpcs Note: Direct query on posts table during low-level filtering operation to query based on attachment_id without further filtering

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $_post = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND post_name = %s",
                        $wp_query->query_vars['attachment']
                    )
                );
            }
        }

        if (!empty($_post)) {
            $object_type = $_post->post_type;

            // default to 'post' object type if retrieval failed for some reason
            if (empty($object_type)) {
                $object_type = 'post';
            }

            if (defined('PRESSPERMIT_TEASER_VERSION') && !current_user_can('read_post', $_post->ID) && !current_user_can('edit_post', $_post->ID)) {
                if ($use_teaser_type = $pp->getTypeOption('tease_post_types', $object_type)) {
                    self::imposePostsTeaser($_post, $object_type, $use_teaser_type);
                    $teaser_applied = true;
                } else {
                    unset($_post); // WordPress generates 404 if teaser is not enabled
                }
            }
        }

        return $teaser_applied;
    }

    static function imposePostsTeaser(&$object, $post_type, $use_teaser_type = 'fixed')
    {
        global $current_user, $wp_query;

        do_action('presspermit_teaser_init_template');

        $teaser_replace = [];
        $teaser_prepend = [];
        $teaser_append = [];

        $teaser_replace[$post_type]['post_content'] = apply_filters('presspermit_get_teaser_text', '', 'replace', 'content', 'post', $post_type, $current_user);

        $teaser_replace[$post_type]['post_excerpt'] = apply_filters('presspermit_get_teaser_text', '', 'replace', 'excerpt', 'post', $post_type, $current_user);
        $teaser_prepend[$post_type]['post_excerpt'] = apply_filters('presspermit_get_teaser_text', '', 'prepend', 'excerpt', 'post', $post_type, $current_user);
        $teaser_append[$post_type]['post_excerpt'] = apply_filters('presspermit_get_teaser_text', '', 'append', 'excerpt', 'post', $post_type, $current_user);

        $teaser_prepend[$post_type]['post_name'] = apply_filters('presspermit_get_teaser_text', '', 'prepend', 'name', 'post', $post_type, $current_user);
        $teaser_append[$post_type]['post_name'] = apply_filters('presspermit_get_teaser_text', '', 'append', 'name', 'post', $post_type, $current_user);

        $force_excerpt = [];
        $force_excerpt[$post_type] = ('excerpt' == $use_teaser_type);

        $args = [
            'teaser_prepend' => $teaser_prepend,
            'teaser_append' => $teaser_append,
            'teaser_replace' => $teaser_replace,
            'force_excerpt' => $force_excerpt
        ];

        \PublishPress\Permissions\Teaser\PostsTeaser::applyTeaser($object, 'post', $post_type, $args);

        $wp_query->is_404 = false;
        $wp_query->is_attachment = true;
        $wp_query->is_single = true;
        $wp_query->is_singular = true;
        $object->ancestors = [$object->post_parent];

        $wp_query->post_count = 1;
        $wp_query->posts[] = $object;
        $wp_query->queried_object = $object;
        $wp_query->queried_object_id = $object->ID;

        if (isset($wp_query->query_vars['error']))
            unset($wp_query->query_vars['error']);

        if (isset($wp_query->query['error']))
            $wp_query->query['error'] = '';
    }
}
