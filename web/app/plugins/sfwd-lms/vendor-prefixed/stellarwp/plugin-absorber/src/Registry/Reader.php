<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Registry;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Boot\Scheduler;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Registry\Contracts\Registrar_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Sub_Plugin;
/**
 * Every registered sub-plugin, as something a pass can be handed rather than reach for.
 *
 * The buffer registrations land in is static because it has to be: `Absorber::register()` is a
 * static call a host makes at plugin-file scope, before there is a container to resolve a registrar
 * from. It lives here, not on the facade, so the dependency on `Absorber` runs one way.
 *
 * @since 1.0.0
 */
class Reader
{
    /**
     * Sub-plugins registered but not yet handed to the registrar.
     *
     * @since 1.0.0
     *
     * @var Sub_Plugin[]
     */
    private static $pending = [];
    /**
     * @since 1.0.0
     *
     * @var Registrar_Interface
     */
    private $registrar;
    /**
     * @since 1.0.0
     *
     * @param Registrar_Interface $registrar Where the registrations are kept.
     */
    public function __construct(Registrar_Interface $registrar)
    {
        $this->registrar = $registrar;
    }
    /**
     * Hold a registration until something reads.
     *
     * It stores rather than registers because `Absorber::register()` must resolve nothing: a host
     * container may still be *replaced* at `plugins_loaded` priority 0, so a registration that
     * reached a registrar earlier would land in the one being thrown away.
     *
     * A registration that arrives after the load pass has gone by is buffered like any other and
     * reported, because a buffer nothing reads again leaves next to nothing behind to go on: no
     * notice, no skip, no missing file — a sub-plugin that simply is not there. The report is a
     * `_doing_it_wrong()`, which is the reach every other report in this library has and no further:
     * it prints where a site is debugging, and fires core's `doing_it_wrong_run` wherever it is not,
     * for a host that listens. `Absorber::boot()` has had a barrier for the same mistake since it was
     * written, and boot is the call a host is *less* likely to misplace: registration is what a
     * service provider tends to carry, and a provider runs whenever the host's bootstrap happens to
     * run it.
     *
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin to hold.
     *
     * @return void
     */
    public static function buffer(Sub_Plugin $sub_plugin): void
    {
        self::$pending[] = $sub_plugin;
        if (!Scheduler::registration_window_has_closed()) {
            return;
        }
        // Reported, and the report is the whole of the remedy. `boot()` can offer an inline fallback
        // because what it was late for had not happened yet: the sequence was still there to be run
        // by hand. Nothing is left to run here. The load pass has been and gone, this library has
        // nothing further on `plugins_loaded`, and requiring the file from a registration instead
        // would be a load pass of one that skipped every gate the real one applies and ran behind the
        // conflict step that decides whether a bundled copy may load at all. It would land on top of
        // a standalone nobody stood down, which is the re-declaration fatal this library exists to
        // prevent.
        //
        // Buffered first, and buffered regardless: this is a report, not a refusal. `Absorber::all()`
        // still answers with the registration, and a host whose own `boot()` is late enough to run
        // the sequence inline reads it from there -- with a report of its own about the boot.
        _doing_it_wrong(self::class . '::buffer', sprintf('Absorber::register() ran after plugins_loaded had gone past the load pass, so "%s"' . ' arrived too late to be read. Register at plugin-file scope, or no later than' . ' plugins_loaded priority 5.', $sub_plugin->get_slug()), '1.0.0');
    }
    /**
     * Every registered sub-plugin, keyed by slug, in registration order.
     *
     * The buffer is drained on the way past, which is why a pass is handed this rather than the
     * registrar it could resolve for itself: a registrar asked directly would miss everything
     * registered since the last read.
     *
     * A read always answers with what the registrar legitimately holds. A duplicate slug is refused
     * and reported as it drains, never raised out of here: every caller is inside `plugins_loaded`,
     * and one host bootstrap mistake about one sub-plugin must not stand down a pass that had every
     * other sub-plugin to get on with.
     *
     * @since 1.0.0
     *
     * @return array<string,Sub_Plugin>
     */
    public function all(): array
    {
        $this->flush();
        // A host may bind a registrar returning anything, and PHP 7.4 cannot say array<string,Sub_Plugin>
        // in the interface signature -- so narrow once here, where the untrusted value enters.
        return array_filter($this->registrar->all(), static function ($sub_plugin): bool {
            return $sub_plugin instanceof Sub_Plugin;
        });
    }
    /**
     * Hand every buffered registration to the registrar, which stays the single source of truth.
     *
     * Public because the drain is wanted without the read: `Absorber::registrar()` hands back a
     * registrar that is empty until something drains into it, and a rebound reader owes both halves.
     *
     * The buffer is emptied before the loop, so a second read cannot re-register what the registrar
     * already holds and trip its duplicate-slug guard. Nothing has to empty it *after* a failure
     * either: the registrar is a constructor argument, so a container that cannot build one fails
     * while this object is being built, with the registrations still buffered for the read that comes
     * after the host has fixed its bindings.
     *
     * A collision the registrar refuses is reported here, with the discarded registration named, and
     * goes no further. Throwing it on made one mistaken registration decide what a whole pass did: the
     * first pass to read caught it and stood down — the load pass loading nothing at all on the front
     * end, the conflict pass resolving nothing in wp-admin — while the registry it was standing down
     * over was intact and readable the entire time. A slug registered twice is one sub-plugin's
     * problem, and the sub-plugins around it still have to load.
     *
     * Reported as it is discovered, which is once per process and therefore once per request, since
     * registration runs at plugin-file scope on every one: the host sees it in the log for as long as
     * the duplicate exists, and the load pass does not repeat a sentence the conflict pass has
     * already printed a priority earlier in the same request. A registration that arrives after a
     * read — a host module registering from its own `plugins_loaded` callback — is checked when it
     * drains, so a later collision still reports.
     *
     * Every collision is reported, not just the first. They are separate mistakes naming separate
     * slugs, and hiding the second behind the first only means the host fixes one and gets the next
     * on the following request.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function flush(): void
    {
        if (self::$pending === []) {
            return;
        }
        $pending = self::$pending;
        self::$pending = [];
        foreach ($pending as $sub_plugin) {
            try {
                $this->registrar->register($sub_plugin);
            } catch (Config_Exception $exception) {
                // The registrar's own sentence, unwrapped: it names the slug and both bundled files,
                // which is the whole of what the host has to go and correct. One clause is added,
                // because the registrar refuses a registration without saying what became of it, and
                // what became of it is now the consequence -- the site runs one of those two files
                // and silently does not run the other. Every other report in this library says what
                // the outcome was; this one has to as well. The clause names the loser as "the
                // duplicate" rather than by path alone, because the two registrations may well name
                // the same file, and a bare path then reads as if the surviving one went too.
                _doing_it_wrong(self::class . '::flush', sprintf('%1$s The original registration was kept; the duplicate %2$s was discarded.', $exception->getMessage(), $sub_plugin->get_bundled_plugin_file()), '1.0.0');
            }
        }
    }
}