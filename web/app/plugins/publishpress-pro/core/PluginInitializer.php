<?php
/**
 * @package     PublishPressPro
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPressPro;

use PublishPressPro\HooksAbstract as PPProHooksAbstract;
use WPPF2\Plugin\PluginInitializerInterface;
use WPPF2\WP\HooksAbstract as WPHooksAbstract;
use WPPF2\WP\HooksHandlerInterface;
use WPPF2\WP\TranslatorInterface;

class PluginInitializer implements PluginInitializerInterface
{
    const FREE_PLUGIN_NAME = 'publishpress';

    /**
     * @var HooksHandlerInterface
     */
    private $hooksHandler;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $modulesDirPath;

    /**
     * @inheritDoc
     */
    public function __construct(
        HooksHandlerInterface $hooksHandler,
        TranslatorInterface $translator,
        $modulesDirPath
    ) {
        $this->hooksHandler   = $hooksHandler;
        $this->translator     = $translator;
        $this->modulesDirPath = $modulesDirPath;
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->setHooks();
    }

    private function setHooks()
    {
        $this->hooksHandler->addAction(WPHooksAbstract::ACTION_PLUGINS_LOADED, [$this, 'loadTextDomain']);
        $this->hooksHandler->addFilter(PPProHooksAbstract::FILTER_MODULES_DIRS, [$this, 'filterModulesDirs']);
    }

    public function loadTextDomain()
    {
        $this->translator->loadTextDomain();
    }

    private function isElementorInstalled()
    {
        return defined('ELEMENTOR_VERSION') || defined('ELEMENTOR_PRO_VERSION');
    }

    private function isRevisionInstalled()
    {
        return defined('PUBLISHPRESS_REVISIONS_VERSION') || defined('PUBLISHPRESS_REVISIONS_PRO_VERSION');
    }

    /**
     * @param array $dirs
     *
     * @return array
     */
    public function filterModulesDirs($dirs)
    {
        $modulesParentDir = rtrim($this->modulesDirPath, '/\\');

        $dirs['slack']              = $modulesParentDir;
        $dirs['reminders']          = $modulesParentDir;
        $dirs['prosettings']        = $modulesParentDir;
        $dirs['notifynetworkadmin'] = $modulesParentDir;
        $dirs['content-overview-pro'] = $modulesParentDir;
        $dirs['content-board-pro']  = $modulesParentDir;
        $dirs['calendar-pro']       = $modulesParentDir;

        if ($this->isElementorInstalled()) {
            $dirs['elementor'] = $modulesParentDir;
        }

        if ($this->isRevisionInstalled()) {
            if (defined('PUBLISHPRESS_REVISIONS_VERSION') && version_compare(PUBLISHPRESS_REVISIONS_VERSION, '3.6.0-rc', '>=')) {
                $dirs['revision-integration'] = $modulesParentDir;
            }
        }

        return $dirs;
    }
}
