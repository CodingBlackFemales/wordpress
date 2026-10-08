<?php
/**
 * @package     WPPF2
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace WPPF2\WP;


abstract class HooksAbstract
{
    const ACTION_INIT = 'init';

    const ACTION_PLUGINS_LOADED = 'plugins_loaded';

    const ACTION_ADMIN_INIT = 'admin_init';
}
