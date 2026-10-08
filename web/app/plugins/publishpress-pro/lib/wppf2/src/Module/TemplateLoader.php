<?php
/**
 * @package     WPPF2
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.4.8
 */

namespace WPPF2\Module;

use WPPF2\BufferInterface;
use WPPF2\HooksAbstract;
use WPPF2\WP\Filesystem\Storage\StorageInterface;
use WPPF2\WP\HooksHandlerInterface;

class TemplateLoader implements TemplateLoaderInterface
{
    /**
     * @var StorageInterface
     */
    private $filesystem;

    /**
     * @var BufferInterface
     */
    private $buffer;

    /**
     * @var HooksHandlerInterface
     */
    private $hooksHandler;

    /**
     * @var string
     */
    private $pluginName;

    /**
     * @var string
     */
    private $modulesPath;

    /**
     * @var string
     */
    private $activeThemeStylesheetPath;

    /**
     * @var string
     */
    private $activeThemePath;

    /**
     * TemplateLoader constructor.
     *
     * @param StorageInterface      $filesystem
     * @param BufferInterface       $buffer
     * @param HooksHandlerInterface $hooksHandler
     * @param string                $pluginName
     * @param string                $modulesPath
     * @param string                $activeThemeStylesheetPath
     * @param string                $activeThemePath
     */
    public function __construct(
        StorageInterface $filesystem,
        BufferInterface $buffer,
        HooksHandlerInterface $hooksHandler,
        $pluginName,
        $modulesPath,
        $activeThemeStylesheetPath,
        $activeThemePath
    ) {
        $this->filesystem                = $filesystem;
        $this->buffer                    = $buffer;
        $this->hooksHandler              = $hooksHandler;
        $this->pluginName                = $pluginName;
        $this->modulesPath               = $modulesPath;
        $this->activeThemeStylesheetPath = $activeThemeStylesheetPath;
        $this->activeThemePath           = $activeThemePath;
    }

    /**
     * Load template for modules.
     *
     * @param       $moduleName
     * @param       $templateName
     * @param array $context
     * @param bool  $return
     *
     * @return false|string
     * @throws TemplateNotFoundException
     */
    private function load($moduleName, $templateName, $context = [], $return = false)
    {
        $templatePath = $this->locateTemplateFileForModule($moduleName, $templateName);
        $context      = (array)$context;

        if ( ! empty($templatePath)) {
            if ($return) {
                $this->buffer->start();
            }

            require $templatePath;

            if ($return) {
                return $this->buffer->getClean();
            }
        } else {
            throw new TemplateNotFoundException('Template file not found');
        }

        return false;
    }

    /**
     * Locate template for modules.
     *
     * @param $moduleName
     * @param $templateName
     *
     * @return string
     */
    public function locateTemplateFileForModule($moduleName, $templateName)
    {
        $located = '';

        $paths = [
            $this->activeThemeStylesheetPath . DIRECTORY_SEPARATOR . $this->pluginName . DIRECTORY_SEPARATOR . $moduleName,
            $this->activeThemePath . DIRECTORY_SEPARATOR . $this->pluginName . DIRECTORY_SEPARATOR . $moduleName,
            $this->modulesPath . DIRECTORY_SEPARATOR . $moduleName . '/templates',
        ];

        $paths = $this->hooksHandler->addFilter(HooksAbstract::FILTER_TEMPLATE_PATHS, $paths);

        foreach ($paths as $path) {
            $templatePath = $path . DIRECTORY_SEPARATOR . $templateName . '.php';

            if ($this->filesystem->exists($templatePath)) {
                $located = $templatePath;

                break;
            }
        }

        return $located;
    }

    /**
     * Load template for modules.
     *
     * @param string $moduleName
     * @param string $templateName
     * @param array  $context
     *
     * @throws TemplateNotFoundException
     */
    public function displayOutput($moduleName, $templateName, $context = [])
    {
        echo $this->load($moduleName, $templateName, $context, false);
    }

    /**
     * Load template for modules.
     *
     * @param string $moduleName
     * @param string $templateName
     * @param array  $context
     *
     * @return false|string
     *
     * @throws TemplateNotFoundException
     */
    public function returnOutput($moduleName, $templateName, $context = [])
    {
        echo $this->load($moduleName, $templateName, $context, true);
    }
}
