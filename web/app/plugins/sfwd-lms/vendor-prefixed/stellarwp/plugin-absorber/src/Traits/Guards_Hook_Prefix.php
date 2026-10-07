<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Traits;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Config;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
/**
 * Turns "no hook prefix has been set" into something a developer sees and a request survives.
 *
 * Every entry point needing the prefix is reached from a core hook, where throwing would take the
 * whole site down over a bootstrap mistake — so each asks this first and stands down on a no. A
 * trait, not a collaborator: the answer comes from `Config` either way, only the report is shared.
 *
 * @since 1.0.0
 */
trait Guards_Hook_Prefix
{
    /**
     * Whether a hook prefix has been set, reporting to the developer when it has not.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    private static function has_hook_prefix(): bool
    {
        try {
            Config::get_hook_prefix();
        } catch (Config_Exception $exception) {
            _doing_it_wrong(self::class . '::has_hook_prefix', $exception->getMessage(), '1.0.0');
            return false;
        }
        return true;
    }
}