<?php
/**
 * @package     WPPF2
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace WPPF2\WP;


interface SettingsHandlerInterface
{
    /**
     * @param        $id
     * @param        $title
     * @param        $callback
     * @param        $page
     * @param string $section
     * @param array  $args
     *
     * @return mixed
     */
    public function addField($id, $title, $callback, $page, $section = 'default', $args = []);
}
