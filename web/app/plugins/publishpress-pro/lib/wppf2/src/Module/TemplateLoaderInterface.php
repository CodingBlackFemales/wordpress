<?php
/**
 * @package     WPPF2
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.4.8
 */

namespace WPPF2\Module;

interface TemplateLoaderInterface
{
    /**
     * Load template for modules.
     *
     * @param string $moduleName
     * @param string $templateName
     * @param array  $context
     */
    public function displayOutput($moduleName, $templateName, $context = []);

    /**
     * Load template for modules.
     *
     * @param string $moduleName
     * @param string $templateName
     * @param array  $context
     *
     * @return false|string
     */
    public function returnOutput($moduleName, $templateName, $context = []);

    /**
     * Locate template for modules.
     *
     * @param $moduleName
     * @param $templateName
     *
     * @return string
     */
    public function locateTemplateFileForModule($moduleName, $templateName);
}
