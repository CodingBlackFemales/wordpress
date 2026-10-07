<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Notices;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Config;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
/**
 * Where the notice queue lives: one option, keyed `slug:type`.
 *
 * An option rather than a transient: with an external object cache a transient never reaches the
 * database, so a routine `wp_cache_flush()` destroys it — and the merge notice is raised exactly
 * once, so losing it means the site owner is never told.
 *
 * @since 1.0.0
 */
class Store
{
    /**
     * The option name backing the queue. Read it from here rather than composing it, since the hook
     * prefix is normalised on its way into a storage key.
     *
     * @since 1.0.0
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return string
     */
    public function option_name(): string
    {
        return Config::get_option_name('notices');
    }
    /**
     * Every queued message, with anything that is not one dropped.
     *
     * @since 1.0.0
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return array<string,string>
     */
    public function all(): array
    {
        // Outside multisite the `*_site_option()` family is the `*_option()` one, so the reads and
        // writes here cover both.
        $queue = get_site_option($this->option_name(), []);
        if (!is_array($queue)) {
            return [];
        }
        // put() writes the filtered array back, so a corrupted entry heals itself.
        return array_filter($queue, 'is_string');
    }
    /**
     * Store one message, replacing any it already holds under the same key.
     *
     * @since 1.0.0
     *
     * @param string $key     Queue key, `slug:type`.
     * @param string $message Resolved message.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    public function put(string $key, string $message): void
    {
        $queue = $this->all();
        $queue[$key] = $message;
        // Autoload off, which is what this queue wants: empty on almost every request, read only in
        // admin.
        update_site_option($this->option_name(), $queue);
    }
    /**
     * @since 1.0.0
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    public function clear(): void
    {
        delete_site_option($this->option_name());
    }
}