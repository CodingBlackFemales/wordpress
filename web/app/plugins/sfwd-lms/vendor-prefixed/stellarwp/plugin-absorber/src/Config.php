<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
use StellarWP\Learndash\StellarWP\ContainerContract\ContainerInterface;
/**
 * Static configuration facade.
 *
 * @since 1.0.0
 */
class Config
{
    /**
     * @var string
     */
    protected static $hook_prefix = '';
    /**
     * @var ContainerInterface|null
     */
    protected static $container = null;
    /**
     * @var string
     */
    protected static $host_plugin_basename = '';
    /**
     * Set the unique per-host slug that keys this library's hooks and options.
     *
     * Stored exactly as given: hook names repeat it verbatim, and only `get_option_name()` folds it.
     *
     * @since 1.0.0
     *
     * @param string $prefix Host slug.
     *
     * @throws Config_Exception When the prefix is empty or contains unsupported characters.
     *
     * @return void
     */
    public static function set_hook_prefix(string $prefix): void
    {
        if ($prefix === '') {
            throw new Config_Exception('The hook prefix cannot be empty.');
        }
        if (preg_match('/[^a-zA-Z0-9_-]/', $prefix)) {
            throw new Config_Exception('Hook prefix must only contain letters, numbers, hyphens, and underscores.');
        }
        self::$hook_prefix = $prefix;
    }
    /**
     * @since 1.0.0
     *
     * @throws Config_Exception When no prefix has been set.
     *
     * @return string
     */
    public static function get_hook_prefix(): string
    {
        if (self::$hook_prefix === '') {
            throw new Config_Exception('You must call Config::set_hook_prefix() before booting the Plugin Absorber.');
        }
        return self::$hook_prefix;
    }
    /**
     * Build the name of one of this library's filters.
     *
     * Nothing else assembles the segment between the host's prefix and the key's own name.
     *
     * @since 1.0.0
     *
     * @param string $name Hook name, less the prefix and this library's namespace.
     *
     * @throws Config_Exception When no prefix has been set.
     *
     * @return string
     */
    public static function get_hook_name(string $name): string
    {
        return self::get_hook_prefix() . '/plugin_absorber/' . $name;
    }
    /**
     * Build the name of one of this library's options.
     *
     * Deliberately does not share `get_hook_name()`'s normalisation, and must not be collapsed into
     * it: a host that passed `Give-Core` hooks that verbatim, while the option folds to
     * `give_core_plugin_absorber_notices`.
     *
     * @since 1.0.0
     *
     * @param string $name Option name, less the prefix and this library's namespace.
     *
     * @throws Config_Exception When no prefix has been set.
     *
     * @return string
     */
    public static function get_option_name(string $name): string
    {
        return self::get_option_prefix() . '_plugin_absorber_' . $name;
    }
    /**
     * Share the host's container. Required, and required before boot().
     *
     * Every collaborator resolves from it, which is what makes each replaceable by binding an
     * interface. There is deliberately no container-less path.
     *
     * @since 1.0.0
     *
     * @param ContainerInterface $container Host container.
     *
     * @return void
     */
    public static function set_container(ContainerInterface $container): void
    {
        self::$container = $container;
    }
    /**
     * @since 1.0.0
     *
     * @throws Config_Exception When no container has been set.
     *
     * @return ContainerInterface
     */
    public static function get_container(): ContainerInterface
    {
        if (self::$container === null) {
            throw new Config_Exception('You must call Config::set_container() before booting the Plugin Absorber.');
        }
        return self::$container;
    }
    /**
     * @since 1.0.0
     *
     * @return bool
     */
    public static function has_container(): bool
    {
        return self::$container !== null;
    }
    /**
     * Tell the library the host plugin's own basename, for the multisite stranding guard.
     *
     * Optional, and read by one thing: the guard that declines to deactivate a network-active
     * standalone whose host is not. Left unset, it stands down. Stored exactly as given -- a
     * basename is a path, not a hook-naming value, so the prefix rules do not apply.
     *
     * @since 1.0.0
     *
     * @param string $basename Host plugin basename, e.g. `plugin_basename( __FILE__ )`.
     *
     * @return void
     */
    public static function set_host_plugin_basename(string $basename): void
    {
        self::$host_plugin_basename = $basename;
    }
    /**
     * The host plugin basename, or an empty string when none was set.
     *
     * Does not throw the way `get_hook_prefix()` and `get_container()` do: an empty string is the
     * honest answer for a host that did not opt in.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function get_host_plugin_basename(): string
    {
        return self::$host_plugin_basename;
    }
    /**
     * The hook prefix folded into the shape a storage key takes.
     *
     * Folded on the way out only — the stored prefix keeps whatever the host's hook names are made
     * of. Two prefixes differing only in case or hyphens collide here, the price of asking a host
     * for one prefix rather than two.
     *
     * @since 1.0.0
     *
     * @throws Config_Exception When no prefix has been set.
     *
     * @return string
     */
    private static function get_option_prefix(): string
    {
        return strtolower(str_replace('-', '_', self::get_hook_prefix()));
    }
}