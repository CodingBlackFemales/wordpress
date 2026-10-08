<?php

/**
 * Handles all configuration values for the library.
 *
 * @since 1.0.0
 *
 * @package StellarWP\Templates
 */
namespace LearnDash\Zapier\StellarWP\Templates;

use RuntimeException;
/**
 * A configuration class for setting up the library.
 *
 * @since 1.0.0
 *
 * @package \StellarWP\Templates
 */
class Config
{
    /**
     * The prefix to apply to all library hooks.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected static string $hook_prefix = '';
    /**
     * The root path to use for all templates.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected static string $root_path = '';
    /**
     * Gets the hook prefix.
     *
     * @throws RuntimeException When a hook prefix is not set.
     *
     * @return string
     */
    public static function get_hook_prefix(): string
    {
        if (static::$hook_prefix === '') {
            $class = __CLASS__;
            throw new RuntimeException("You must specify a hook prefix for your project with {$class}::set_hook_prefix()");
        }
        return static::$hook_prefix;
    }
    /**
     * Gets the root path of the project.
     *
     * @throws RuntimeException When a root template path is not set.
     *
     * @return string
     */
    public static function get_path(): string
    {
        if (static::$root_path === '') {
            $class = __CLASS__;
            throw new RuntimeException("You must specify a path to the root of you project with {$class}::set_path()");
        }
        return static::$root_path;
    }
    /**
     * Resets this class back to the defaults.
     */
    public static function reset(): void
    {
        static::$hook_prefix = '';
        static::$root_path = '';
    }
    /**
     * Sets the hook prefix.
     *
     * @param string $prefix The prefix to add to hooks.
     *
     * @return void
     */
    public static function set_hook_prefix(string $prefix): void
    {
        static::$hook_prefix = $prefix;
    }
    /**
     * Sets the root path of the project.
     *
     * @param string $path The root path of the project.
     *
     * @return void
     */
    public static function set_path(string $path): void
    {
        static::$root_path = trailingslashit($path);
    }
}