<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Notices;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Traits\Guards_Plugin_Capability;
/**
 * Who may consume the queue, and what happens when they do.
 *
 * The queue is single-consumer: rendering consumes it for everybody, network-wide on multisite. The
 * capability check lives here rather than in `Renderer` because it guards the clearing as much as
 * the drawing — decided apart, a user who may not see the queue could still destroy it.
 *
 * @since 1.0.0
 */
class Presenter
{
    use Guards_Plugin_Capability;
    /**
     * @since 1.0.0
     *
     * @var Store
     */
    private $store;
    /**
     * @since 1.0.0
     *
     * @var Renderer
     */
    private $renderer;
    /**
     * @since 1.0.0
     *
     * @param Store    $store    Where the queue is kept.
     * @param Renderer $renderer How a queued notice is drawn.
     */
    public function __construct(Store $store, Renderer $renderer)
    {
        $this->store = $store;
        $this->renderer = $renderer;
    }
    /**
     * Draw the queue, then consume it.
     *
     * Reads the store, not the bound writer: a host binding its own writer has taken over where
     * notices live along with what they say.
     *
     * @since 1.0.0
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    public function render(): void
    {
        // Rendering clears the queue, so a subscriber loading their profile page would otherwise
        // swallow the only warning an administrator was ever going to get.
        if (!self::user_may_manage_plugins()) {
            return;
        }
        $queue = $this->store->all();
        if ($queue === []) {
            return;
        }
        $this->renderer->render($queue);
        $this->store->clear();
    }
}