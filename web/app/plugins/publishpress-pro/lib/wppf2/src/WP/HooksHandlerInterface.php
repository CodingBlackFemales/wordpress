<?php
/**
 * @package     WPPF2
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace WPPF2\WP;


interface HooksHandlerInterface
{
    /**
     * @param          $tag
     * @param callable $function
     * @param int      $priority
     * @param int      $acceptedArgs
     */
    public function addAction($tag, callable $function, $priority = 10, $acceptedArgs = 1);

    /**
     * @param string $tag
     * @param array  $args
     */
    public function doAction($tag, $args = []);

    /**
     * @param          $tag
     * @param callable $function
     * @param int      $priority
     * @param int      $acceptedArgs
     */
    public function addFilter($tag, callable $function, $priority = 10, $acceptedArgs = 1);

    /**
     * @param       $tag
     * @param       $value
     * @param array $args
     *
     * @return mixed
     */
    public function applyFilters($tag, $value, $args = []);
}
