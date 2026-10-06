<?php
/**
 * @package     WPPF2
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace WPPF2\WP;


class HooksHandler implements HooksHandlerInterface
{
    /**
     * @param          $tag
     * @param callable $function
     * @param int      $priority
     * @param int      $acceptedArgs
     */
    public function addAction($tag, callable $function, $priority = 10, $acceptedArgs = 1)
    {
        add_action($tag, $function, $priority, $acceptedArgs);
    }

    /**
     * @param string $tag
     * @param array  $args
     */
    public function doAction($tag, $args = [])
    {
        do_action($tag, ...$args);
    }

    /**
     * @param          $tag
     * @param callable $function
     * @param int      $priority
     * @param int      $acceptedArgs
     */
    public function addFilter($tag, callable $function, $priority = 10, $acceptedArgs = 1)
    {
        add_filter($tag, $function, $priority, $acceptedArgs);
    }

    /**
     * @param       $tag
     * @param       $value
     * @param array $args
     *
     * @return mixed
     */
    public function applyFilters($tag, $value, $args = [])
    {
        return apply_filters($tag, $value, ...$args);
    }
}
