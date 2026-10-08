<?php
namespace PublishPress_Statuses;

class Workarounds {

    function __construct() {
        // These seven-ish methods are temporary fixes for solving bugs in WordPress core
        add_filter('preview_post_link', [$this, 'fix_preview_link_part_one']);
        add_filter('post_link', [$this, 'fix_preview_link_part_two'], 10, 3);
        add_filter('page_link', [$this, 'fix_preview_link_part_two'], 10, 3);
        add_filter('post_type_link', [$this, 'fix_preview_link_part_two'], 10, 3);
        add_filter('get_sample_permalink', [$this, 'fix_get_sample_permalink'], 10, 5);
        add_filter('get_sample_permalink_html', [$this, 'fix_get_sample_permalink_html'], 9, 5);
        add_filter('post_row_actions', [$this, 'fix_post_row_actions'], 10, 2);
        add_filter('page_row_actions', [$this, 'fix_post_row_actions'], 10, 2);

        add_filter('wp_insert_post_data', [$this, 'filter_insert_post_data'], 10, 2);
    }

    /**
     * Filters slashed post data just before it is inserted into the database.
     *
     * @param array $data An array of slashed post data.
     * @param array $postarr An array of sanitized, but otherwise unmodified post data.
     *
     * @return array
     */
    public function filter_insert_post_data($data, $postarr)
    {
        // Check if we have a post type which this module is activated, before continue.
        if (! in_array($data['post_type'], \PublishPress_Statuses::getEnabledPostTypes())) {
            return $data;
        }

        /*
        * If status is different from draft, auto-draft, and pending, WordPress will automatically
        * set the post_date_gmt to the current date time, like it was being published. But since
        * we provide other post statuses, this produces wrong date for posts not published yet.
        * They should have the post_date_gmt empty, so they are kept as "publish immediately".
        *
        * As of WordPress 5.3, we can opt out of the date setting by setting date_floating on
        * custom statuses instead.
        */
        if (version_compare(get_bloginfo('version'), '5.3', '<')) {
            if (! in_array($data['post_status'], ['publish', 'future'])) {
                // Check if the dates are the same, indicating they were auto-set.
                if (get_gmt_from_date(
                        $data['post_date']
                    ) === $data['post_date_gmt'] && $data['post_modified'] === $data['post_date']) {
                    // Reset the date
                    $data['post_date_gmt'] = '0000-00-00 00:00:00';
                }
            }
        }

        return $data;
    }

    /**
     * Another temporary fix until core better supports custom statuses
     *
     * @since 0.7.4
     *
     * The preview link for an unpublished post should always be ?p=
     */
    public function fix_preview_link_part_one($preview_link)
    {
        global $pagenow;

        $post = get_post(get_the_ID());

        // Only modify if we're using a pre-publish status on a supported custom post type
        $status_slugs = \PublishPress_Statuses::getCustomStatuses([], 'names');
        if (! $post
            || ! is_admin()
            || 'post.php' != $pagenow
            || ! in_array($post->post_status, $status_slugs)
            || ! in_array($post->post_type, \PublishPress_Statuses::getEnabledPostTypes())
            || strpos($preview_link, 'preview_id') !== false
            || $post->filter == 'sample') {
            return $preview_link;
        }

        return $this->get_preview_link($post);
    }

    /**
     * Another temporary fix until core better supports custom statuses
     *
     * @param $permalink
     * @param $post
     * @param bool $sample
     * @return string
     * @since 0.7.4
     *
     * The preview link for an unpublished post should always be ?p=
     * The code used to trigger a post preview doesn't also apply the 'preview_post_link' filter
     * So we can't do a targeted filter. Instead, we can even more hackily filter get_permalink
     * @see   http://core.trac.wordpress.org/ticket/19378
     */
    public function fix_preview_link_part_two($permalink, $post, $sample = false)
    {
        global $pagenow;

        if (is_int($post)) {
            $postId = $post;
            $post = get_post($post);
        }

        if (!is_object($post) || !isset($post->post_type)) {
            return $permalink;
        }

        if (! in_array($post->post_type, \PublishPress_Statuses::getEnabledPostTypes())) {
            return $permalink;
        }

        //Is this published?
        if ($status_obj = get_post_status_object($post->post_status)) {
            if (! empty($status_obj->public) || ! empty($status_obj->private)) {
                return $permalink;
            }
        }

        //Are we overriding the permalink? Don't do anything
        if ('sample-permalink' === \PP_Statuses_Functions::POST_key('action')) {
            return $permalink;
        }

        //Are we previewing the post from the normal post screen?
        if (($pagenow == 'post.php' || $pagenow == 'post-new.php')
        && !\PP_Statuses_Functions::is_POST('wp-preview')) {
            return $permalink;
        }

        // If it's a scheduled post, we don't add the preview link
        if ($post->post_status === 'future') {
            return $permalink;
        }

        //If it's a sample permalink, not a preview
        if ($sample) {
            return $permalink;
        }

        return $this->get_preview_link($post);
    }

    /**
     * Fix get_sample_permalink. Previosuly the 'editable_slug' filter was leveraged
     * to correct the sample permalink a user could edit on post.php. Since 4.4.40
     * the `get_sample_permalink` filter was added which allows greater flexibility in
     * manipulating the slug. Critical for cases like editing the sample permalink on
     * hierarchical post types.
     *
     * @param string $permalink Sample permalink
     * @param int $post_id Post ID
     * @param string $title Post title
     * @param string $name Post name (slug)
     * @param WP_Post $post Post object
     *
     * @return string $link Direct link to complete the action
     * @since 0.8.2
     *
     */
    public function fix_get_sample_permalink($permalink, $post_id, $title, $name, $post)
    {
        //Should we be doing anything at all?
        if (! in_array($post->post_type, \PublishPress_Statuses::getEnabledPostTypes())) {
            return $permalink;
        }

        //Is this published?
        $status_object = get_post_status_object($post->post_status);

        if (in_array($post->post_status, ['publish', 'private']) || !empty($status_object->public) || !empty($status_object->private)) {
            return $permalink;
        }

        //Are we overriding the permalink? Don't do anything
        if ('sample-permalink' === \PP_Statuses_Functions::POST_key('action')) {
            return $permalink;
        }

        list($permalink, $post_name) = $permalink;

        $post_name = $post->post_name ? $post->post_name : sanitize_title($post->post_title);

        // If the post name is still empty, we can't use it to fix the permalink. So, don't do anything.
        if (empty($post_name)) {
            return $permalink;
        }

        // Apply the fix
        $post->post_name = $post_name;

        $ptype = get_post_type_object($post->post_type);

        if ($ptype->hierarchical && !in_array($post->post_status, array( 'draft', 'auto-draft'))) {
            $post->filter = 'sample';

            $uri = get_page_uri($post->ID) . $post_name;

            if ($uri) {
                $uri = untrailingslashit($uri);
                $uri = strrev(stristr(strrev($uri), '/'));
                $uri = untrailingslashit($uri);
            }

            /** This filter is documented in wp-admin/edit-tag-form.php */
            $uri = apply_filters('editable_slug', $uri, $post);

            if (! empty($uri)) {
                $uri .= '/';
            }

            $permalink = str_replace('%pagename%', "{$uri}%pagename%", $permalink);
        }

        unset($post->post_name);

        return [$permalink, $post_name];
    }

    /**
     * Temporary fix to work around post status check in get_sample_permalink_html
     *
     *
     * The get_sample_permalink_html checks the status of the post and if it's
     * a draft generates a certain permalink structure.
     * We need to do the same work it's doing for custom statuses in order
     * to support this link
     *
     * @see   https://core.trac.wordpress.org/browser/tags/4.5.2/src/wp-admin/includes/post.php#L1296
     *
     * @since 0.8.2
     *
     * @param string $return Sample permalink HTML markup
     * @param int $post_id Post ID
     * @param string $new_title New sample permalink title
     * @param string $new_slug New sample permalink kslug
     * @param WP_Post $post Post object
     */
    public function fix_get_sample_permalink_html($return, $post_id, $new_title, $new_slug, $post)
    {
        $status_slugs = \PublishPress_Statuses::getCustomStatuses([], 'names');

        // Remove publish status
        $publishKey = array_search('publish', $status_slugs);
        if (false !== $publishKey) {
            unset($status_slugs[$publishKey]);
        }

        list($permalink, $post_name) = get_sample_permalink($post->ID, $new_title, $new_slug);

        $view_link = false;
        $preview_target = '';

        if (current_user_can('read_post', $post_id)) {
            if (in_array($post->post_status, $status_slugs)) {
                $view_link = $this->get_preview_link($post);
                $postId = esc_attr($post->ID);
                $preview_target = " target='wp-preview-{$postId}'";
            } else {
                if ('publish' === $post->post_status || 'attachment' === $post->post_type) {
                    $view_link = get_permalink($post);
                } else {
                    // Allow non-published (private, future) to be viewed at a pretty permalink.
                    $view_link = str_replace(['%pagename%', '%postname%'], $post->post_name, $permalink);
                }
            }
        }

        // Permalinks without a post/page name placeholder don't have anything to edit
        if (false === strpos($permalink, '%postname%') && false === strpos($permalink, '%pagename%')) {
            $return = '<strong>' . __('Permalink:') . "</strong>\n";

            if (false !== $view_link) {
                $display_link = urldecode($view_link);
                $return .= '<a id="sample-permalink" href="' . esc_url(
                        $view_link
                    ) . '"' . $preview_target . '>' . $display_link . "</a>\n";
            } else {
                $return .= '<span id="sample-permalink">' . $permalink . "</span>\n";
            }

            // Encourage a pretty permalink setting
            if ('' == get_option('permalink_structure') && current_user_can(
                    'manage_options'
                ) && ! ('page' == get_option('show_on_front') && $post_id == get_option('page_on_front'))) {
                $return .= '<span id="change-permalinks"><a href="options-permalink.php" class="button button-small" target="_blank">' . __(
                        'Change Permalinks'
                    ) . "</a></span>\n";
            }
        } else {
            if (function_exists('mb_strlen')) {
                if (mb_strlen($post_name) > 34) {
                    $post_name_abridged = mb_substr($post_name, 0, 16) . '&hellip;' . mb_substr($post_name, -16);
                } else {
                    $post_name_abridged = $post_name;
                }
            } else {
                if (strlen($post_name) > 34) {
                    $post_name_abridged = substr($post_name, 0, 16) . '&hellip;' . substr($post_name, -16);
                } else {
                    $post_name_abridged = $post_name;
                }
            }

            $post_name_html = '<span id="editable-post-name">' . $post_name_abridged . '</span>';
            $display_link = str_replace(['%pagename%', '%postname%'], $post_name_html, urldecode($permalink));

            $return = '<strong>' . __('Permalink:') . "</strong>\n";
            $return .= '<span id="sample-permalink"><a href="' . esc_url(
                    $view_link
                ) . '"' . $preview_target . '>' . $display_link . "</a></span>\n";
            $return .= '&lrm;'; // Fix bi-directional text display defect in RTL languages.
            $return .= '<span id="edit-slug-buttons"><button type="button" class="edit-slug button button-small hide-if-no-js" aria-label="' . __(
                    'Edit permalink'
                ) . '">' . __('Edit') . "</button></span>\n";
            $return .= '<span id="editable-post-name-full">' . $post_name . "</span>\n";
        }

        return $return;
    }

    /**
     * Another temporary fix until core better supports custom statuses
     *
     * @since 0.7.4
     *
     * The preview link for an unpublished post should always be ?p=, even in the list table
     * @see   http://core.trac.wordpress.org/ticket/19378
     */
    public function fix_post_row_actions($actions, $post)
    {
        global $pagenow;

        // Only modify if we're using a pre-publish status on a supported custom post type
        $status_slugs = \PublishPress_Statuses::getCustomStatuses([], 'names');
        if ('edit.php' != $pagenow
            || ! in_array($post->post_status, $status_slugs)
            || ! in_array($post->post_type, \PublishPress_Statuses::getEnabledPostTypes())
            || in_array($post->post_status, ['publish'])) {
            return $actions;
        }

        // 'view' is only set if the user has permission to post
        if (empty($actions['view'])) {
            return $actions;
        }

        if ('page' == $post->post_type) {
            $args = [
                'page_id' => $post->ID,
            ];
        } elseif ('post' == $post->post_type) {
            $args = [
                'p' => $post->ID,
            ];
        } else {
            $args = [
                'p' => $post->ID,
                'post_type' => $post->post_type,
            ];
        }
        $args['preview'] = 'true';
        $preview_link = add_query_arg($args, home_url());

        $actions['view'] = '<a href="' . esc_url($preview_link) . '" title="' . esc_attr(
                sprintf(
                    __('Preview &#8220;%s&#8221;'),
                    $post->post_title
                )
            ) . '" rel="permalink">' . __('Preview') . '</a>';

        return $actions;
    }

    /**
     * Get the proper preview link for a post
     *
     * @since 0.8
     */
    private function get_preview_link($post)
    {
        if ('page' == $post->post_type) {
            $args = [
                'page_id' => $post->ID,
            ];
        } elseif ('post' == $post->post_type) {
            $args = [
                'p' => $post->ID,
                'preview' => 'true',
            ];
        } else {
            $args = [
                'p' => $post->ID,
                'post_type' => $post->post_type,
            ];
        }

        $args['preview_id'] = $post->ID;


        return add_query_arg($args, trailingslashit(home_url()));
    }
}
