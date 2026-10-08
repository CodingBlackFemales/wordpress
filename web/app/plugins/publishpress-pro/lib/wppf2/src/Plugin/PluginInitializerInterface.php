<?php
/**
 * @package     WPPF2
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace WPPF2\Plugin;

use WPPF2\WP\HooksHandlerInterface;
use WPPF2\WP\TranslatorInterface;

interface PluginInitializerInterface
{
    /**
     * PluginInitializerInterface constructor.
     *
     * @param HooksHandlerInterface   $hooksHandler
     * @param TranslatorInterface     $textDomainLoader
     * @param string                  $pluginDirPath
     */
    public function __construct(
        HooksHandlerInterface $hooksHandler,
        TranslatorInterface $textDomainLoader,
        $pluginDirPath
    );

    /**
     * @return void
     */
    public function init();
}
