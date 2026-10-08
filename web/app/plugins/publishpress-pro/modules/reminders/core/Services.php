<?php
/**
 * @package     PublishPress\Reminders
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (c) 2022 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.3
 */

namespace PublishPress\Addon\Reminders;

use PublishPress\Pimple\Container as Pimple;
use PublishPress\Pimple\ServiceProviderInterface;
use PP_Reminders;
use PublishPress\Addon\Reminders\Util\Time;
use PublishPress\Core\View;

defined('ABSPATH') or die('No direct script access allowed.');

/**
 * Class Services
 */
class Services implements ServiceProviderInterface
{
    /**
     * @since 1.2.3
     * @var PP_Reminders
     */
    protected $module;

    /**
     * Services constructor.
     *
     * @param PP_Reminders $module
     *
     * @since 1.2.3
     */
    public function __construct(PP_Reminders $module)
    {
        $this->module = $module;
    }

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Pimple $container A container instance
     *
     * @since 1.2.3
     */
    public function register(Pimple $container)
    {
        $container['view'] = function ($c) {
            return new View;
        };

        $container['module'] = function ($c) {
            return $this->module;
        };

        $container['util_time'] = function ($c) {
            return new Time();
        };
    }
}
