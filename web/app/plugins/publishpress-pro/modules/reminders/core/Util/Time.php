<?php
/**
 * @package     PublishPress\Notifications
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 */

namespace PublishPress\Addon\Reminders\Util;


class Time
{
    /**
     * Get the post date in unixtime format.
     *
     * @param int $postId
     *
     * @return int
     */
    public function getPostDateUnixtime($postId)
    {
        // Get the post's scheduled time
        $post = $this->getPost($postId);

        if (!is_object($post)) {
            return 0;
        }

        return strtotime($post->post_date);
    }

    /**
     * @param $postId
     *
     * @return array|null|\WP_Post
     */
    public function getPost($postId)
    {
        return get_post($postId);
    }
}
