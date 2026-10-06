<?php
/**
 * @package     PublishPressPro
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPressPro;

abstract class HooksAbstract
{
    const ACTION_REGISTER_SETTINGS = 'publishpress_register_settings_before';

    const FILTER_MODULES_DIRS = 'pp_module_dirs';

    const FILTER_POST_TYPE_REQUIREMENTS = 'publishpress_pro_post_type_requirements';

    const FILTER_LOCALIZED_DATA = 'ppchpro_localized_data';

    const FILTER_VALIDATE_MODULE_SETTINGS = 'publishpress_pro_validate_module_settings';

    const FILTER_DISPLAY_BRANDING = 'publishpress_pro_display_branding';
}
