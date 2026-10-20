<?php
/**
 * @package     PublishPress\Permissions
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2024 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 */

namespace PublishPress\Permissions;

if (!PWP::empty_REQUEST('autoupdater') || !PWP::empty_REQUEST('autoupdater_nonce') || defined('PRESSPERMIT_PRO_DISABLE_PP_SERVICES')) {
    return;
}

use PublishPress\Pimple\Container as Pimple;
use PublishPress\Pimple\ServiceProviderInterface;
use \PublishPress\Permissions;
use PublishPress\WordPressEDDLicense\Container as EDDContainer;
use PublishPress\WordPressEDDLicense\Services as EDDServices;
use PublishPress\WordPressEDDLicense\ServicesConfig as EDDServicesConfig;

defined('ABSPATH') or die('No direct script access allowed.');

/**
 * Class Services
 */
class Services implements ServiceProviderInterface
{
    protected $module;

    /**
     * Services constructor.
     */
    public function __construct(\PublishPress\Permissions $module)
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
     */
    public function register(Pimple $container)
    {
        $container['module'] = function ($c) {
            return $this->module;
        };

        $container['LICENSE_KEY'] = function ($c) {
            $key = '';
            $arr = (array) presspermit()->getOption('edd_key');

            return (isset($arr['license_key'])) ? $arr['license_key'] : '';
        };

        $container['LICENSE_STATUS'] = function ($c) {
            return presspermit()->keyStatus();
        };

		if (class_exists('PublishPress\\WordPressEDDLicense\\ServicesConfig')) {
	        $container['edd_container'] = function ($c) {
	            $config = new EDDServicesConfig();
	            $config->setApiUrl('https://publishpress.com');
	            $config->setLicenseKey($c['LICENSE_KEY']);
	            $config->setLicenseStatus($c['LICENSE_STATUS']);
	            $config->setPluginVersion(PRESSPERMIT_PRO_VERSION);
	            $config->setEddItemId(PRESSPERMIT_EDD_ITEM_ID);
	            $config->setPluginAuthor('PublishPress');
	            $config->setPluginFile(PRESSPERMIT_PRO_FILE);
	
	            $services = new EDDServices($config);
	
	            $eddContainer = new EDDContainer();
	            $eddContainer->register($services);
	
	            return $eddContainer;
	        };
	 	} else {
	 		$container['edd_container'] = false;	
	 	} 
    }
}
