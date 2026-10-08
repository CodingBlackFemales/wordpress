<?php

namespace PublishPress_Statuses;

use PublishPressInstanceProtection\Config;
use PublishPressInstanceProtection\InstanceChecker;

class LibInstanceProtection
{
    public function __construct()
    {
        if (! class_exists('PublishPressInstanceProtection\\Config')) {
            $includeFile = __DIR__ . '/lib/vendor/publishpress/instance-protection/include.php';

            if (is_file($includeFile) && is_readable($includeFile)) {
                // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
                require_once $includeFile;
            }
        }

        if (! class_exists('PublishPressInstanceProtection\\Config')) {
            return null;
        }

        $pluginCheckerConfig             = new Config();
        $pluginCheckerConfig->pluginSlug = 'publishpress-statuses';
        $pluginCheckerConfig->pluginName = 'PublishPress Statuses';

        new InstanceChecker($pluginCheckerConfig);
    }
}
