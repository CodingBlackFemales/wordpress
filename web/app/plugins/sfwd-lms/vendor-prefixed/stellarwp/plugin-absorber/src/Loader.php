<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Contracts\Activator_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Notices\Contracts\Writer_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Registry\Reader;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Traits\Guards_Hook_Prefix;
use Throwable;
/**
 * The load pass: every registered sub-plugin, in registration order, gated one at a time.
 *
 * Separate from `Absorber` because it runs rather than configures.
 *
 * @since 1.0.0
 */
class Loader
{
    use Guards_Hook_Prefix;
    /**
     * @since 1.0.0
     *
     * @var Reader
     */
    private $registry;
    /**
     * @since 1.0.0
     *
     * @var Writer_Interface
     */
    private $notices;
    /**
     * @since 1.0.0
     *
     * @var Activator_Interface
     */
    private $activator;
    /**
     * @since 1.0.0
     *
     * @param Reader              $registry  Which sub-plugins are registered.
     * @param Writer_Interface    $notices   Where a sub-plugin that could not load says so.
     * @param Activator_Interface $activator Runs the activation callback of one that did.
     */
    public function __construct(Reader $registry, Writer_Interface $notices, Activator_Interface $activator)
    {
        $this->registry = $registry;
        $this->notices = $notices;
        $this->activator = $activator;
    }
    /**
     * @since 1.0.0
     *
     * @return void
     */
    public function load_all(): void
    {
        // No prefix, no should_load filter and no notice store; the trait reports it.
        if (!self::has_hook_prefix()) {
            return;
        }
        // The reader rather than the registrar directly: it drains the registrations still buffered
        // on the facade before it reads, and a registrar asked on its own would miss anything
        // registered since the last read. Unguarded, because the read answers with whatever the
        // registrar legitimately holds: a duplicate slug is refused and reported where it is found,
        // so the sub-plugins around it still reach this loop rather than a host's one mistaken
        // registration costing the site every bundled plugin it has.
        foreach ($this->registry->all() as $sub_plugin) {
            // Everything past this line is somebody else's code: the enabled and dependency_check
            // callables, the host's should_load filter, and the bundled file itself, which a require
            // runs from top to bottom. Any of it may throw, and this loop runs inside plugins_loaded
            // on every request a site serves -- so an escaping throw is a white screen on the front
            // end and in wp-admin alike, over one sub-plugin, and it takes every sub-plugin behind it
            // in the registration order with it. The developer is told which sub-plugin, and the
            // loop carries on with the next.
            //
            // A re-declaration is the one failure this cannot catch, because PHP does not raise it as
            // a Throwable -- which is what the guard constant, checked before any of this and checked
            // again once the require has happened, is for.
            //
            // The loaded and skipped actions run host code too, but they catch their own throws
            // rather than falling to this one: by the time `loaded` fires the require has happened,
            // the guard constant has been checked and the activation callback has run, so a listener's
            // throw arriving here would report a sub-plugin that is loaded and healthy as one that
            // was abandoned -- on the channel a host built its log line on.
            try {
                $this->load($sub_plugin);
            } catch (Throwable $thrown) {
                _doing_it_wrong(self::class . '::load_all', sprintf('The sub-plugin "%s" threw while loading, so it was abandoned: %s', $sub_plugin->get_slug(), $thrown->getMessage()), '1.0.0');
            }
        }
    }
    /**
     * Load one sub-plugin, cheapest and most decisive check first.
     *
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin to load.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    private function load(Sub_Plugin $sub_plugin): void
    {
        if (!$sub_plugin->is_enabled()) {
            $this->announce_skip($sub_plugin, Skip_Reason::DISABLED);
            return;
        }
        // Ahead of the dependency check, never after: it carries the whole re-declaration guarantee,
        // and warning of unmet requirements for a plugin already running sends the admin after the
        // wrong problem.
        if ($sub_plugin->is_already_loaded()) {
            $this->announce_skip($sub_plugin, Skip_Reason::ALREADY_LOADED);
            return;
        }
        if (!$sub_plugin->are_dependencies_met()) {
            $this->notices->queue_dependency_notice($sub_plugin);
            $this->announce_skip($sub_plugin, Skip_Reason::DEPENDENCIES_UNMET);
            return;
        }
        // Not file_exists(): that is true for a directory and for an unreadable file, and
        // require_once fatals on both. A broken build reports to the developer, not a site owner.
        $file = $sub_plugin->get_bundled_plugin_file();
        if (!is_file($file) || !is_readable($file)) {
            // Reported and announced both, because this gate is two things at once: a build the
            // host has to fix, which is what `_doing_it_wrong()` carries, and a sub-plugin that is
            // not going to be there, which is what anything watching `skipped` is counting. A host
            // watching both sees this one twice, and that is said plainly in the docs rather than
            // solved by picking one.
            _doing_it_wrong(self::class . '::load', sprintf('The bundled plugin file for "%s" is missing or unreadable: %s', $sub_plugin->get_slug(), $file), '1.0.0');
            $this->announce_skip($sub_plugin, Skip_Reason::FILE_UNREADABLE);
            return;
        }
        // Truthiness, not a cast: only a falsy return vetoes, so an object or a non-empty array here
        // loads rather than fataling on the way to a string.
        $should_load = apply_filters(Config::get_hook_name('should_load'), true, $sub_plugin);
        if (!$should_load) {
            $this->announce_skip($sub_plugin, Skip_Reason::FILTERED);
            return;
        }
        // The last point before the require, and the only one where a host can put something in place
        // that the bundled file needs at its own file scope. `should_load` is not that point:
        // `Conflict\Detector` applies the same filter a priority earlier, when a standalone copy is
        // in the way and this one is about to be turned away.
        //
        // Not through announce(): that swallows a listener's throw, which is wrong with the require
        // still ahead. `load_all()` catches it and abandons the sub-plugin, which is accurate here.
        do_action_ref_array(Config::get_hook_name('loading'), [$sub_plugin]);
        // An include takes the scope of the line it sits on, so top-level assignments in the bundled
        // file are function-local where wp-settings.php would have made them global. Not fixable.
        require_once $file;
        // The same defined() the second gate asked, on the other side of the require: in front of it
        // the question is whether any copy is loaded, behind it whether this require kept the guard's
        // promise. Reported and not skipped -- the require cannot be undone, so the callback and
        // `loaded` still have to happen, and a broken host build is nothing a site owner's screen can
        // fix. `Sub_Plugin` still owns the name, so nothing here spells a constant for Strauss to
        // rewrite.
        $guard_constant = $sub_plugin->get_plugin_loaded_constant();
        if (!defined($guard_constant)) {
            _doing_it_wrong(self::class . '::load', sprintf('The bundled plugin "%s" was required and left %s undefined, so nothing stands a' . ' standalone copy down. Define the guard constant at file scope, or correct' . ' plugin_loaded_constant.', $sub_plugin->get_slug(), $guard_constant), '1.0.0');
        }
        // Last, and only after a require that happened: register_activation_hook() never fires for
        // an included plugin, so this stands in for it with the sub-plugin's code in memory, and
        // creating its tables for code that is not loaded would spend the once-ever record before
        // the first real load.
        $this->activator->maybe_run($sub_plugin);
        // Behind the activation callback, not in front of it. A listener here is host code that will
        // reach into the sub-plugin it was just told about, and on the first-ever load the tables and
        // options that code expects are the activation callback's work -- so announcing first would
        // hand a host a plugin that is loaded but not yet set up. It also keeps a throwing listener
        // off the callback: from here announce() catches the throw with the require and the
        // activation already done, where from in front of it the throw would skip the callback
        // entirely and leave it to be retried, silently, on every request for ever.
        $this->announce(Config::get_hook_name('loaded'), [$sub_plugin], $sub_plugin);
    }
    /**
     * Say that a gate turned this sub-plugin away, and which gate it was.
     *
     * The hook name is built in one place for the reason `Config` gives for owning the prefix at all.
     *
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin that was skipped.
     * @param string     $reason     One of the `Skip_Reason` constants.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    private function announce_skip(Sub_Plugin $sub_plugin, string $reason): void
    {
        $this->announce(Config::get_hook_name('skipped'), [$sub_plugin, $reason], $sub_plugin);
    }
    /**
     * Fire one lifecycle action, and keep what a listener throws out of the load pass's own report.
     *
     * The throw is caught here rather than a frame up, because `load_all()`'s per-sub-plugin catch
     * has exactly one sentence and it is "threw while loading, so it was abandoned". For a listener
     * on `loaded` that sentence is false in both halves: the require happened, the guard constant has
     * been checked and the activation callback has already run, so the sub-plugin is loaded and
     * nothing about it was abandoned. A host would get the `loaded` announcement and a report of a
     * failed load for the same sub-plugin in the same pass, and the log line it keeps would read a
     * successful load as a broken one. The same is true of a listener on `skipped`, which reports a
     * skip that really did happen.
     *
     * So the failure is reported as what it is -- somebody's listener, named by the hook it is on and
     * the sub-plugin it was announcing. It still costs nothing behind it: the sub-plugin is finished
     * with either way, and the loop moves on to the next.
     *
     * @since 1.0.0
     *
     * @param string           $hook       Fully qualified hook name.
     * @param array<int,mixed> $arguments  Arguments to pass to the listeners.
     * @param Sub_Plugin       $sub_plugin Sub-plugin the announcement is about.
     *
     * @return void
     */
    private function announce(string $hook, array $arguments, Sub_Plugin $sub_plugin): void
    {
        try {
            do_action_ref_array($hook, $arguments);
        } catch (Throwable $thrown) {
            _doing_it_wrong(self::class . '::announce', sprintf('A listener on %s threw for "%s", and was abandoned: %s', $hook, $sub_plugin->get_slug(), $thrown->getMessage()), '1.0.0');
        }
    }
}