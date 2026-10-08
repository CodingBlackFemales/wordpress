<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Boot\Scheduler;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict\Contracts\Resolver_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict\Rewriter;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Contracts\Provider_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Notices\Contracts\Writer_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Notices\Presenter;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Registry\Contracts\Registrar_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Registry\Reader;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Traits\Guards_Hook_Prefix;
use Throwable;
/**
 * Static facade: registration, and the one call that starts everything.
 *
 * `final` because every member is static and every internal call is `self::` — a subclass could
 * override nothing, and would silently change nothing.
 *
 * @since 1.0.0
 */
final class Absorber
{
    use Guards_Hook_Prefix;
    /**
     * @var bool
     */
    private static $booted = false;
    /**
     * The registrar, holding every registration made so far.
     *
     * Drained before it is handed back, as `all()` is on its way to the list and for the same
     * reason: registration is buffered until something reads it, so a registrar handed over as it is
     * holds nothing at all until the first pass reads at plugins_loaded priority 5 — which is after
     * every point a host bootstrap gets to ask. The two public reads of the registry would then
     * disagree, one of them against the contract `Registrar_Interface::all()` states, and neither
     * would say so.
     *
     * Two resolutions, and not two registrars: every binding `Provider` makes is a singleton, so the
     * instance handed back here is the one `Registry\Reader` was constructed with and the one
     * `flush()` drains into. A host that binds `Registrar_Interface` transiently breaks that — and
     * the answer is still not to drain into the instance resolved here, which would empty the buffer
     * into an object nothing else holds and leave `all()`, the load pass and the conflict pass
     * reading a registrar those registrations never reached.
     *
     * Drained *after* the binding has been resolved and checked, not before. `Registry\Reader` takes
     * a registrar as a constructor argument, so a registrar bound to the wrong class is a reader
     * that cannot be built either — and a drain in front would report the reader, a collaborator the
     * host never bound, in place of the one binding it did get wrong.
     *
     * @since 1.0.0
     *
     * @throws Config_Exception When the container is unset, or its binding unusable.
     *
     * @return Registrar_Interface
     */
    public static function registrar(): Registrar_Interface
    {
        // Resolved before the drain, not after. The ordering is the paragraph above: the reader is
        // built from this binding, so asking for it first is what names the binding at fault.
        $registrar = self::collaborator(Registrar_Interface::class);
        self::collaborator(Reader::class)->flush();
        return $registrar;
    }
    /**
     * @since 1.0.0
     *
     * @throws Config_Exception When the container is unset, or its binding unusable.
     *
     * @return Writer_Interface
     */
    public static function notices(): Writer_Interface
    {
        return self::collaborator(Writer_Interface::class);
    }
    /**
     * @since 1.0.0
     *
     * @throws Config_Exception When the container is unset, or its binding unusable.
     *
     * @return Resolver_Interface
     */
    public static function resolver(): Resolver_Interface
    {
        return self::collaborator(Resolver_Interface::class);
    }
    /**
     * Register one bundled sub-plugin. Call once per sub-plugin, before boot().
     *
     * Buffered rather than handed to the registrar, so registration resolves nothing — not even the
     * container, which a host may set after this call. `Sub_Plugin` still validates the config here,
     * in the host's own stack trace.
     *
     * @since 1.0.0
     *
     * @param array<string,mixed> $config Sub-plugin configuration.
     *
     * @throws Config_Exception When the configuration is unusable.
     *
     * @return void
     */
    public static function register(array $config): void
    {
        Reader::buffer(new Sub_Plugin($config));
    }
    /**
     * Every registered sub-plugin, keyed by slug, in registration order.
     *
     * The passes read the registry through the reader they were handed, never back through here: a
     * facade sits in front of its collaborators, never underneath them.
     *
     * @since 1.0.0
     *
     * @throws Config_Exception When no container has been set, or its binding is unusable.
     *
     * @return array<string,Sub_Plugin>
     */
    public static function all(): array
    {
        return self::collaborator(Reader::class)->all();
    }
    /**
     * Bind the collaborators, then let the scheduler decide when they run. Idempotent.
     *
     * Set the container before booting: the scheduler closes over the one it finds here. The provider
     * is bound only when nothing answers to `Provider_Interface` already, so a host may replace the
     * whole set of bindings.
     *
     * @since 1.0.0
     *
     * @throws Config_Exception When no container has been set.
     *
     * @return void
     */
    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        $container = Config::get_container();
        if (!$container->has(Provider_Interface::class)) {
            $container->singleton(Provider_Interface::class, new Provider($container));
        }
        $container->get(Provider_Interface::class)->register();
        $container->get(Scheduler::class)->wire();
        // Last, not first: a boot that threw part-way through wired nothing, so calling again after
        // the fix must give a working library rather than a no-op.
        self::$booted = true;
    }
    /**
     * @since 1.0.0
     *
     * @throws Config_Exception When no container has been set.
     *
     * @return void
     */
    public static function render_notices(): void
    {
        if (!self::has_hook_prefix()) {
            return;
        }
        // Notice wording comes from host callables, on a hook every admin screen fires: the notice is
        // worth less than the wp-admin a throw would white-screen.
        try {
            self::collaborator(Presenter::class)->render();
        } catch (Throwable $thrown) {
            _doing_it_wrong(self::class . '::render_notices', sprintf('The notices could not be rendered: %s', $thrown->getMessage()), '1.0.0');
        }
    }
    /**
     * Rewrite the activation-error notice for a standalone this library has absorbed.
     *
     * The parameter is untyped deliberately: a filter receives whatever the filter before it
     * returned, and a `string` declaration would raise a TypeError from the screen least able to
     * afford one.
     *
     * @since 1.0.0
     *
     * @param mixed $markup Notice markup WordPress is about to print.
     *
     * @return string
     */
    public static function filter_activation_error_markup($markup): string
    {
        $markup = is_string($markup) ? $markup : '';
        if (!self::has_hook_prefix()) {
            return $markup;
        }
        // This draws the screen that reports a fatal: a throw would replace the error the admin came
        // to read with one of ours.
        try {
            return self::collaborator(Rewriter::class)->rewrite($markup);
        } catch (Throwable $thrown) {
            _doing_it_wrong(self::class . '::filter_activation_error_markup', sprintf('The activation error notice could not be rewritten: %s', $thrown->getMessage()), '1.0.0');
            return $markup;
        }
    }
    /**
     * The object bound to a collaborator interface, checked before it is handed on.
     *
     * A host that bound the wrong class would otherwise surface as a TypeError inside
     * `plugins_loaded` reading as a bug here; naming the interface and the class that failed it
     * makes it an instruction instead.
     *
     * @since 1.0.0
     *
     * @template T of object
     *
     * @param class-string<T> $interface Collaborator interface to resolve.
     *
     * @throws Config_Exception When no container has been set, when building the binding throws, or
     *                          when the binding does not implement the interface.
     *
     * @return T
     */
    private static function collaborator(string $interface): object
    {
        // Outside the try: a missing container is already this library's own error, in its own words,
        // and re-wrapping would bury that sentence an exception deeper.
        $container = Config::get_container();
        // A host factory may throw, and so does the container asked for an interface nothing has
        // bound yet. Uncaught, either fatals from a vendor namespace naming neither this library
        // nor the binding at fault.
        try {
            $collaborator = $container->get($interface);
        } catch (Throwable $thrown) {
            throw new Config_Exception(sprintf('The container failed to build the binding for %s: %s', $interface, $thrown->getMessage()), 0, $thrown);
        }
        if (!$collaborator instanceof $interface) {
            throw new Config_Exception(sprintf('The container binding for %s returned %s, which does not implement it.', $interface, is_object($collaborator) ? get_class($collaborator) : gettype($collaborator)));
        }
        return $collaborator;
    }
}