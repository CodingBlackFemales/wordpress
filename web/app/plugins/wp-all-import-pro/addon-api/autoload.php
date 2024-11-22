<?php

namespace Wpai\AddonAPI;

require WP_ALL_IMPORT_ROOT_DIR . '/addon-api/classes/helpers.php';

class PMXI_Addon_Autoloader {
    use Singleton;

    public function __construct() {
        spl_autoload_register([$this, 'autoload']);

        foreach ($this->modules() as $module) {
            $module::getInstance();
        }
    }

    public function modules() {
        return [PMXI_Addon_Admin::class, PMXI_Addon_Rest::class];
    }

    public function loadIfFound(string $path) {
        $path = WP_ALL_IMPORT_ROOT_DIR . '/addon-api/' . $path . '.php';

        if (file_exists($path)) {
            require_once $path;
        }
    }

    public function autoload($class) {
		if ( strpos( $class, 'PMXI_Addon_' ) === false ) {
			return;
		}

        $parts = explode('\\', $class);
        $className = end($parts);
        $className = str_replace('PMXI_Addon_', '', $className);
        $className = str_replace('_', '-', $className);
        $className = strtolower($className);
        $className = str_replace('-field', '', $className); // E.g. Rename "text-field" to "text"

        $this->loadIfFound('classes/' . $className);
        $this->loadIfFound('fields/' . $className);
    }
}

PMXI_Addon_Autoloader::getInstance();
