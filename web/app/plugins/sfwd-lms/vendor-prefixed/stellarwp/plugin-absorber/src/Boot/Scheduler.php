<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Boot;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Absorber;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict\Contracts\Resolver_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict\Detector;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict\Gatekeeper;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Loader;
use StellarWP\Learndash\StellarWP\ContainerContract\ContainerInterface;
use Throwable;
use WP_Hook;
/**
 * Which hook this library's work runs on, at which priority, and what to do when it is too late.
 *
 * Its own class because the provider says how collaborators are built and this says when they run.
 *
 * @since 1.0.0
 */
class Scheduler
{
    /**
     * plugins_loaded priority the load pass runs at.
     *
     * Every priority below this one is a band of the bundled plugin's *own* plugins_loaded callbacks
     * that silently never fire, which is why this number moves down when in doubt and not up.
     *
     * @since 1.0.0
     *
     * @var int
     */
    private const LOAD_PRIORITY = 6;
    /**
     * plugins_loaded priority conflict resolution runs at, one ahead of the load pass.
     *
     * A surviving standalone defines the guard constant as it loads and the load pass has to see
     * that. Also the number a host is measured against, being the first step in the sequence — 5
     * rather than 1 because LearnDash and MemberDash already wire Harbor's `set_container()` at
     * priority 1, so a host copying that habit would land on the barrier.
     *
     * @since 1.0.0
     *
     * @var int
     */
    private const RESOLVE_PRIORITY = 5;
    /**
     * @since 1.0.0
     *
     * @var ContainerInterface
     */
    private $container;
    /**
     * @since 1.0.0
     *
     * @param ContainerInterface $container Container every step resolves from when it runs.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    /**
     * Wire the WordPress hooks.
     *
     * Nothing is resolved here: every callback asks the container for its collaborator when the hook
     * fires, so a host may still rebind one after boot() and a binding nothing reaches is never
     * built. Called too late, the steps run inline instead — and may then not return.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function wire(): void
    {
        if (is_admin()) {
            // all_admin_notices, not admin_notices: the latter's three branches are mutually exclusive,
            // so a superadmin in the network admin would never see the queue rendered.
            add_action('all_admin_notices', [Absorber::class, 'render_notices']);
            // Named rather than a closure: remove_filter() is a host's only way back to core's wording.
            add_filter('wp_admin_notice_markup', [Absorber::class, 'filter_activation_error_markup']);
        }
        // A callback added at a priority the dispatch has already passed is accepted and never fires,
        // so a host that boots too late would otherwise get a healthy-looking site that loads nothing.
        if ($this->wiring_window_has_closed()) {
            _doing_it_wrong(Absorber::class . '::boot', 'Absorber::boot() must run before plugins_loaded priority 5. Resolving and loading inline instead.', '1.0.0');
            // In the order the hooks would have run them.
            foreach ($this->sequence() as $step) {
                $run = $step['run'];
                $run();
            }
            return;
        }
        foreach ($this->sequence() as $step) {
            add_action('plugins_loaded', $step['run'], $step['priority']);
        }
    }
    /**
     * Whether plugins_loaded has already carried the dispatch past the load pass, so that a
     * registration made now is one the load pass will not see.
     *
     * Here rather than in `Registry\Reader`, which is what asks. What the answer turns on is this
     * library's own priorities and how far the hook it lives on has got — the two facts
     * `wiring_window_has_closed()` weighs a few lines below, off the same measurement. A registry
     * that read the hook for itself would hold a second copy of a rule that moves every time a
     * priority here does, and the copy that was not updated would be the one a host heard from.
     *
     * Static, because registration is. `Absorber::register()` resolves nothing, so the question it
     * asks on the way past cannot need a container answered first.
     *
     * Measured against the load pass because that is the last step in the sequence and the last read
     * of the registry there is; the wiring window is measured against the first. A step added behind
     * the load pass is the one change that would make this number the wrong one.
     *
     * The comparison is exclusive where the wiring window's is inclusive, because the two are not
     * the same question. A callback appended to the priority being dispatched lands on an array the
     * running loop already copied, so it can never fire whatever else sits in that priority. A
     * registration is read by a callback already in that priority — the load pass — and whether it
     * has run yet is its position within the priority, which nothing exposes. Where the answer
     * cannot be known, this says nothing rather than warning about a sub-plugin that loaded.
     *
     * It says nothing outside the dispatch either, and that is the deliberate limit of it. Before
     * plugins_loaded every registration is early. After it, a host that has not booted yet is not
     * late — `wire()` finds the window shut and runs the whole sequence inline, and that pass reads
     * the buffer like any other — and nothing here can tell that host from one whose load pass ran
     * five priorities ago.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public static function registration_window_has_closed(): bool
    {
        $position = self::plugins_loaded_position();
        return $position !== null && $position > self::LOAD_PRIORITY;
    }
    /**
     * The plugins_loaded steps, in run order, as priority and callback.
     *
     * Stated once because wire() expresses this order twice — as hook priorities, and as straight
     * calls when it is too late to wire — and iterating one list cannot drift.
     *
     * @since 1.0.0
     *
     * @return non-empty-array<int,array{priority:int,run:callable():void}>
     */
    private function sequence(): array
    {
        $container = $this->container;
        return [['priority' => self::RESOLVE_PRIORITY, 'run' => static function () use ($container): void {
            self::resolve_conflicts($container);
        }], ['priority' => self::LOAD_PRIORITY, 'run' => static function () use ($container): void {
            self::load($container);
        }]];
    }
    /**
     * The conflict step: the two gates, the probe between them, and the resolve behind all three.
     *
     * @since 1.0.0
     *
     * @param ContainerInterface $container Container each collaborator is resolved from.
     *
     * @return void
     */
    private static function resolve_conflicts(ContainerInterface $container): void
    {
        try {
            $gatekeeper = $container->get(Gatekeeper::class);
            // Request shape first: cron, WP-CLI, POSTs and front-end views turn back with no user
            // resolved and no resolver built.
            if (!$gatekeeper->request_may_resolve()) {
                return;
            }
            // Then whether there is a conflict at all, before anyone asks who is signed in:
            // current_user_can() caches the current user, and at priority 5 that would settle it ahead
            // of an SSO plugin's own plugins_loaded filter, on requests with nothing to resolve.
            if (!$container->get(Detector::class)->has_conflict()) {
                return;
            }
            if (!$gatekeeper->user_may_resolve()) {
                return;
            }
            // Both gates and the probe live here, not in the resolver, so a host binding its own cannot
            // drop one by omission.
            $container->get(Resolver_Interface::class)->resolve_all();
        } catch (Throwable $thrown) {
            // Nothing reached from a hook may throw: plugins_loaded fires on every request a site
            // serves, so a throw out of a step is a white screen on all of them. Report it and abandon
            // this step alone -- the passes guard their own per-sub-plugin loops.
            self::report_a_step_that_threw('conflict pass', 'no conflict was resolved', $thrown);
        }
    }
    /**
     * The load step.
     *
     * @since 1.0.0
     *
     * @param ContainerInterface $container Container the load pass is resolved from.
     *
     * @return void
     */
    private static function load(ContainerInterface $container): void
    {
        // Guarded like the conflict step; `Loader::load_all()` already reports per sub-plugin, so what
        // is left to catch is a container that cannot build the pass at all.
        try {
            $container->get(Loader::class)->load_all();
        } catch (Throwable $thrown) {
            self::report_a_step_that_threw('load pass', 'no sub-plugin was loaded', $thrown);
        }
    }
    /**
     * Tell the developer which step was abandoned, and why.
     *
     * @since 1.0.0
     *
     * @param string    $step        Step that threw, named as the sequence names it.
     * @param string    $consequence What the site got instead.
     * @param Throwable $thrown      What came out of the step.
     *
     * @return void
     */
    private static function report_a_step_that_threw(string $step, string $consequence, Throwable $thrown): void
    {
        _doing_it_wrong(self::class . '::report_a_step_that_threw', sprintf('The %s threw, so %s: %s', $step, $consequence, $thrown->getMessage()), '1.0.0');
    }
    /**
     * Whether it is already too late to wire the first step of the sequence.
     *
     * Measured against the earliest priority in sequence(), read rather than restated: with resolution
     * at 5 and the load at 6, booting between the two would otherwise wire the load and silently lose
     * the conflict pass. Inclusive, because a callback added at the priority dispatching never runs.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    private function wiring_window_has_closed(): bool
    {
        if (!did_action('plugins_loaded')) {
            return false;
        }
        if (!doing_action('plugins_loaded')) {
            return true;
        }
        $position = self::plugins_loaded_position();
        return $position !== null && $position >= min(array_column($this->sequence(), 'priority'));
    }
    /**
     * The plugins_loaded priority being dispatched, or null when the hook is not dispatching at all.
     *
     * The one place this library reads how far the hook has got, so that the two windows either side
     * of it differ in the priority they measure and in the comparison they make, and in nothing
     * else. Both used to reach into `$GLOBALS['wp_filter']` for themselves, which is a second
     * dialect of the same reading.
     *
     * `WP_Hook::current_priority()` answers `false` while the hook is not iterating, and that
     * covers both "not yet" and "over" — a caller that has to tell those two apart asks
     * `did_action()` as well.
     *
     * @since 1.0.0
     *
     * @return int|null
     */
    private static function plugins_loaded_position(): ?int
    {
        $hook = $GLOBALS['wp_filter']['plugins_loaded'] ?? null;
        $priority = $hook instanceof WP_Hook ? $hook->current_priority() : false;
        return is_int($priority) ? $priority : null;
    }
}