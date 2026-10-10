<?php
namespace PublishPress\Revisions;

class CompatibilityRegistry
{
    private static $integrations = [];
    private static $loaded_from_db = false;
    
    public static function register($id, $config)
    {
        self::$integrations[$id] = array_merge([
            'id' => $id,
            'enabled' => false,
            'available' => false,
            'class' => null,
            'instance' => null,
            'title' => '',
            'description' => '',
            'categories' => ['all'],
            'features' => [],
            'plugin_check' => null,
            'learn_more_url' => '',
            'icon_class' => '',
        ], $config);
        
        // Load enabled state from database
        self::loadEnabledStateFromDB($id);
    }
    
    public static function getIntegrations()
    {
        self::ensureLoadedFromDB();
        return self::$integrations;
    }
    
    public static function getIntegration($id)
    {
        self::ensureLoadedFromDB();
        return isset(self::$integrations[$id]) ? self::$integrations[$id] : null;
    }
    
    public static function isEnabled($id)
    {
        self::ensureLoadedFromDB();
        return !empty(self::$integrations[$id]['enabled']);
    }
    
    /**
     * Check if a specific integration module is enabled
     * This is a centralized method that can be used by both CompatHooks and CompatHooksAdmin
     * @param string $module_id The module ID to check
     * @return bool True if module is enabled, false otherwise
     */
    public static function isModuleEnabled($module_id)
    {
        // Check if CompatibilityRegistry class exists and has the integration registered
        if (class_exists('\\PublishPress\\Permissions\\CompatibilityRegistry') && self::getIntegration($module_id)) {
            return self::isEnabled($module_id);
        }
        
        // Fallback to old option-based checking for backward compatibility
        $enabled_integrations = get_option('revisionary_enabled_integrations', []);
        return !empty($enabled_integrations[$module_id]);
    }
    
    public static function isAvailable($id)
    {
        $integration = self::getIntegration($id);
        if (!$integration) return false;
        
        // Check if plugin is active
        if ($integration['plugin_check'] && is_callable($integration['plugin_check'])) {
            return call_user_func($integration['plugin_check']);
        }
        
        return $integration['available'];
    }
    
    public static function enableIntegration($id)
    {
        if (!isset(self::$integrations[$id])) return false;
        
        self::$integrations[$id]['enabled'] = true;
        
        // Instantiate the integration class
        if (self::$integrations[$id]['class'] && !self::$integrations[$id]['instance']) {
            $class = self::$integrations[$id]['class'];
            if (class_exists($class)) {
                self::$integrations[$id]['instance'] = new $class();
            }
        }
        
        // Save to database
        self::saveIntegrationConfig($id, ['enabled' => true]);
        
        return true;
    }
    
    public static function disableIntegration($id)
    {
        if (!isset(self::$integrations[$id])) return false;
        
        self::$integrations[$id]['enabled'] = false;
        self::$integrations[$id]['instance'] = null;
        
        // Save to database
        self::saveIntegrationConfig($id, ['enabled' => false]);
        
        return true;
    }
    
    /**
     * Save integration configuration to database
     */
    private static function saveIntegrationConfig($id, $config)
    {
        $all_configs = get_option('revisionary_integration_configs', []);
        
        if (!isset($all_configs[$id])) {
            $all_configs[$id] = [];
        }
        
        $all_configs[$id] = array_merge($all_configs[$id], $config);
        $all_configs[$id]['updated_at'] = current_time('mysql');
        
        update_option('revisionary_integration_configs', $all_configs);
        
        // Also maintain backward compatibility with old option
        if (isset($config['enabled'])) {
            $enabled_integrations = get_option('revisionary_enabled_integrations', []);
            if ($config['enabled']) {
                $enabled_integrations[$id] = true;
            } else {
                unset($enabled_integrations[$id]);
            }
            update_option('revisionary_enabled_integrations', $enabled_integrations);
        }
    }
    
    /**
     * Load integration configuration from database
     */
    private static function loadIntegrationConfig($id)
    {
        $all_configs = get_option('revisionary_integration_configs', []);
        return isset($all_configs[$id]) ? $all_configs[$id] : [];
    }
    
    /**
     * Load enabled state from database for a specific integration
     */
    private static function loadEnabledStateFromDB($id)
    {
        if (!isset(self::$integrations[$id])) return;
        
        $config = self::loadIntegrationConfig($id);
        
        if (isset($config['enabled'])) {
            self::$integrations[$id]['enabled'] = $config['enabled'];
        }
    }
    
    /**
     * Ensure all integrations have loaded their state from database
     */
    private static function ensureLoadedFromDB()
    {
        if (self::$loaded_from_db) return;
        
        foreach (array_keys(self::$integrations) as $id) {
            self::loadEnabledStateFromDB($id);
        }
        
        self::$loaded_from_db = true;
    }
    
    /**
     * Get integration configuration from database
     */
    public static function getIntegrationConfig($id)
    {
        return self::loadIntegrationConfig($id);
    }
    
    /**
     * Update integration configuration in database
     */
    public static function updateIntegrationConfig($id, $config)
    {
        if (!isset(self::$integrations[$id])) return false;
        
        self::saveIntegrationConfig($id, $config);
        
        // Update in-memory state
        foreach ($config as $key => $value) {
            if (array_key_exists($key, self::$integrations[$id])) {
                self::$integrations[$id][$key] = $value;
            }
        }
        
        return true;
    }
    
    /**
     * Get all integration configurations from database
     */
    public static function getAllIntegrationConfigs()
    {
        return get_option('revisionary_integration_configs', []);
    }
    
    /**
     * Reset integration configuration to defaults
     */
    public static function resetIntegrationConfig($id)
    {
        if (!isset(self::$integrations[$id])) return false;
        
        $all_configs = get_option('revisionary_integration_configs', []);
        unset($all_configs[$id]);
        update_option('revisionary_integration_configs', $all_configs);
        
        // Reset enabled state
        self::disableIntegration($id);
        
        return true;
    }
    
    /**
     * Bulk update integration configurations
     */
    public static function bulkUpdateConfigs($configs)
    {
        $all_configs = get_option('revisionary_integration_configs', []);
        
        foreach ($configs as $id => $config) {
            if (isset(self::$integrations[$id])) {
                if (!isset($all_configs[$id])) {
                    $all_configs[$id] = [];
                }
                $all_configs[$id] = array_merge($all_configs[$id], $config);
                $all_configs[$id]['updated_at'] = current_time('mysql');
                
                // Update in-memory state
                foreach ($config as $key => $value) {
                    if (array_key_exists($key, self::$integrations[$id])) {
                        self::$integrations[$id][$key] = $value;
                    }
                }
            }
        }
        
        update_option('revisionary_integration_configs', $all_configs);
        return true;
    }
    
    /**
     * Export integration configurations
     */
    public static function exportConfigs()
    {
        return [
            'configs' => self::getAllIntegrationConfigs(),
            'exported_at' => current_time('mysql'),
            'version' => defined('PRESSPERMIT_COMPAT_VERSION') ? PRESSPERMIT_COMPAT_VERSION : '1.0'
        ];
    }
    
    /**
     * Import integration configurations
     */
    public static function importConfigs($data)
    {
        if (!isset($data['configs']) || !is_array($data['configs'])) {
            return false;
        }
        
        update_option('revisionary_integration_configs', $data['configs']);
        
        // Reload from database
        self::$loaded_from_db = false;
        self::ensureLoadedFromDB();
        
        return true;
    }
}