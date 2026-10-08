<?php
/**
 * @package     WPPF2
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace WPPF2\Plugin;

abstract class ServicesAbstract
{
    const PLUGIN_NAME = 'pluginName';

    const PLUGIN_TITLE = 'pluginTitle';

    const PLUGIN_VERSION = 'pluginVersion';

    const PLUGIN_FILE = 'pluginFile';

    const PLUGIN_DIR_PATH = 'pluginDirPath';

    const MODULES_DIR_PATH = 'modulesDirPath';

    const TEXT_DOMAIN = 'textDomain';

    const TRANSLATOR = 'translator';

    const HOOKS_HANDLER = 'hooksLoader';

    const PLUGINS_HANDLER = 'pluginsLoader';

    const PLUGIN_INITIALIZER = 'pluginInitializer';

    const TEMPLATE_LOADER = 'templateLoader';

    const ACTIVE_STYLE_SHEET_PATH = 'activeStyleSheetPath';

    const ACTIVE_THEME_PATH = 'activeThemePath';

    const FILESYSTEM = 'filesystem';

    const BUFFER = 'buffer';

    const EDD_CONNECTOR = 'eddConnector';

    const LICENSE_KEY = 'licenseKey';

    const LICENSE_STATUS = 'licenseStatus';

    const LEGACY_PLUGIN = 'legacyPlugin';

    const MAIN_MODULE = 'mainModule';

    const MATH_HELPER = 'mathHelper';

    const SETTINGS_HANDLER = 'settingsHandler';

    const DISPLAY_BRANDING = 'display_branding';

    const SETTINGS = 'settings';
}
