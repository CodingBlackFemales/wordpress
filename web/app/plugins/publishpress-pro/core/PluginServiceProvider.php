<?php
/**
 * @package     PublishPressPro
 * @author      PublishPress <help@publishpress.com>
 * @copyright   copyright (C) 2019 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPressPro;

use PublishPress\Pimple\Container;
use PublishPress\Pimple\ServiceProviderInterface;
use PublishPress\WordPressEDDLicense\Container as EDDContainer;
use PublishPress\WordPressEDDLicense\Services as EDDServices;
use PublishPress\WordPressEDDLicense\ServicesConfig as EDDServicesConfig;
use WPPF2\Buffer;
use WPPF2\BufferInterface;
use WPPF2\Helper\Math;
use WPPF2\Helper\MathInterface;
use WPPF2\Module\TemplateLoader;
use WPPF2\Module\TemplateLoaderInterface;
use WPPF2\Plugin\PluginInitializerInterface;
use WPPF2\Plugin\ServicesAbstract;
use WPPF2\WP\Filesystem\Filesystem;
use WPPF2\WP\Filesystem\Storage\Local;
use WPPF2\WP\Filesystem\Storage\StorageInterface;
use WPPF2\WP\HooksHandler;
use WPPF2\WP\HooksHandlerInterface;
use WPPF2\WP\SettingsHandler;
use WPPF2\WP\SettingsHandlerInterface;
use WPPF2\WP\Translator;
use WPPF2\WP\TranslatorInterface;

class PluginServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        /**
         * @return string
         */
        $container[ServicesAbstract::PLUGIN_NAME] = static function () {
            return 'publishpress-pro';
        };

        /**
         * @param Container $c
         *
         * @return string
         */
        $container[ServicesAbstract::PLUGIN_TITLE] = static function (Container $c) {
            $translator = $c[ServicesAbstract::TRANSLATOR];

            return $translator->getText('PublishPress Pro');
        };

        /**
         * @return string
         */
        $container[ServicesAbstract::PLUGIN_VERSION] = static function () {
            return PUBLISHPRESS_PRO_VERSION;
        };

        /**
         * @param Container $c
         *
         * @return string
         */
        $container[ServicesAbstract::PLUGIN_FILE] = static function (Container $c) {
            return dirname(PUBLISHPRESS_PRO_FILE) . '/' . basename(PUBLISHPRESS_PRO_FILE);
        };

        /**
         * @return string
         */
        $container[ServicesAbstract::PLUGIN_DIR_PATH] = static function () {
            return PUBLISHPRESS_PRO_DIR_PATH;
        };

        /**
         * @param Container $c
         *
         * @return string
         */
        $container[ServicesAbstract::MODULES_DIR_PATH] = static function (Container $c) {
            return $c[ServicesAbstract::PLUGIN_DIR_PATH];
        };

        /**
         * @return string
         */
        $container[ServicesAbstract::TEXT_DOMAIN] = static function () {
            return 'publishpress-pro';
        };

        $container[ServicesAbstract::LEGACY_PLUGIN] = static function (Container $c) {
            global $publishpress;

            return $publishpress;
        };

        /**
         * @param Container $c
         *
         * @return TranslatorInterface
         */
        $container[ServicesAbstract::TRANSLATOR] = static function (Container $c) {
            return new Translator(
                $c[ServicesAbstract::TEXT_DOMAIN],
                basename($c[ServicesAbstract::PLUGIN_DIR_PATH]) . '/languages',
                $c[ServicesAbstract::HOOKS_HANDLER]
            );
        };

        /**
         * @return HooksHandlerInterface
         */
        $container[ServicesAbstract::HOOKS_HANDLER] = static function () {
            return new HooksHandler();
        };

        /**
         * @param Container $c
         *
         * @return PluginInitializerInterface
         */
        $container[ServicesAbstract::PLUGIN_INITIALIZER] = static function (Container $c) {
            return new PluginInitializer(
                $c[ServicesAbstract::HOOKS_HANDLER],
                $c[ServicesAbstract::TRANSLATOR],
                $c[ServicesAbstract::MODULES_DIR_PATH]
            );
        };

        /**
         * @return string
         */
        $container[ServicesAbstract::ACTIVE_STYLE_SHEET_PATH] = static function () {
            return STYLESHEETPATH;
        };

        /**
         * @return string
         */
        $container[ServicesAbstract::ACTIVE_THEME_PATH] = static function () {
            return TEMPLATEPATH;
        };

        /**
         * @return StorageInterface
         */
        $container[ServicesAbstract::FILESYSTEM] = static function () {
            return new Filesystem(new Local());
        };

        /**
         * @return BufferInterface
         */
        $container[ServicesAbstract::BUFFER] = static function () {
            return new Buffer();
        };

        /**
         * @param Container $c
         *
         * @return TemplateLoaderInterface
         */
        $container[ServicesAbstract::TEMPLATE_LOADER] = static function (Container $c) {
            return new TemplateLoader(
                $c[ServicesAbstract::FILESYSTEM],
                $c[ServicesAbstract::BUFFER],
                $c[ServicesAbstract::HOOKS_HANDLER],
                $c[ServicesAbstract::PLUGIN_NAME],
                $c[ServicesAbstract::MODULES_DIR_PATH],
                $c[ServicesAbstract::ACTIVE_STYLE_SHEET_PATH],
                $c[ServicesAbstract::ACTIVE_THEME_PATH]
            );
        };

        $container[ServicesAbstract::SETTINGS] = static function (Container $c) {
            return get_option('publishpress_prosettings_options');
        };

        /**
         * @param Container $c
         *
         * @return string
         */
        $container[ServicesAbstract::LICENSE_KEY] = static function (Container $c) {
            $options = $c[ServicesAbstract::SETTINGS];

            return isset($options->license_key) ? $options->license_key : '';
        };

        /**
         * @param Container $c
         *
         * @return string
         */
        $container[ServicesAbstract::LICENSE_STATUS] = static function (Container $c) {
            $options = $c[ServicesAbstract::SETTINGS];

            return isset($options->license_status) ? $options->license_status : '';
        };

        /**
         * @param Container $c
         *
         * @return string
         */
        $container[ServicesAbstract::DISPLAY_BRANDING] = static function (Container $c) {
            $options = $c[ServicesAbstract::SETTINGS];

            return isset($options->display_branding) ? $options->display_branding === 'on' : true;
        };

        /**
         * @param Container $c
         *
         * @return EDDContainer
         */
        $container[ServicesAbstract::EDD_CONNECTOR] = static function (Container $c) {
            $config = new EDDServicesConfig();
            $config->setApiUrl('https://publishpress.com');
            $config->setLicenseKey($c[ServicesAbstract::LICENSE_KEY]);
            $config->setLicenseStatus($c[ServicesAbstract::LICENSE_STATUS]);
            $config->setPluginVersion($c[ServicesAbstract::PLUGIN_VERSION]);
            $config->setEddItemId(PUBLISHPRESS_PRO_ITEM_ID);
            $config->setPluginAuthor('PublishPress');
            $config->setPluginFile($c[ServicesAbstract::PLUGIN_FILE]);

            $eddContainer = new EDDContainer();
            $eddContainer->register(new EDDServices($config));

            return $eddContainer;
        };

        /**
         * @param Container $c
         *
         * @return SettingsHandlerInterface
         */
        $container[ServicesAbstract::SETTINGS_HANDLER] = static function (Container $c) {
            return new SettingsHandler();
        };
    }
}
