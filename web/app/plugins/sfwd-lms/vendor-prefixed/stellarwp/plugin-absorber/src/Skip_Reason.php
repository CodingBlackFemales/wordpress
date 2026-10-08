<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber;

/**
 * Why the load pass turned a sub-plugin away.
 *
 * These are the values the `skipped` action's second argument takes, and they are public API from
 * the moment they ship: a host comparing against `Skip_Reason::ALREADY_LOADED` rather than against
 * `'already_loaded'` is the point of naming them at all.
 *
 * A class of their own rather than constants on `Loader`, because what they belong to is the action
 * and not the pass that happens to fire it. `Loader` is bound by class name and a host may replace
 * it outright; a replacement still announces the same vocabulary, and it should not have to import
 * the implementation it swapped out to name one. `Conflict_Policy` sits at the root beside this for
 * the same reason rather than on `Conflict\Resolver`.
 *
 * Nothing here validates a reason, unlike `Conflict_Policy`: the library only ever emits one, so an
 * `is_valid()` would have no caller.
 *
 * @since 1.0.0
 */
final class Skip_Reason
{
    /**
     * The `enabled` key, or the callable behind it, said no.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public const DISABLED = 'disabled';
    /**
     * The guard constant was already defined, so the code is in memory — a standalone copy loaded
     * ahead of the pass, or a second registration of the same plugin.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public const ALREADY_LOADED = 'already_loaded';
    /**
     * The `dependency_check` callable said the requirements are not met. The one skip that also
     * queues a notice for the site owner.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public const DEPENDENCIES_UNMET = 'dependencies_unmet';
    /**
     * `bundled_plugin_file` does not name a readable file. A broken build in the host plugin, so it
     * is reported to the developer as well as announced through `skipped`.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public const FILE_UNREADABLE = 'file_unreadable';
    /**
     * The `should_load` filter returned something falsy.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public const FILTERED = 'filtered';
}