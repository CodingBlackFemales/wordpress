<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Boot\Scheduler;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict\Contracts\Resolver_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict\Detector;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict\Gatekeeper;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict\Redirector;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict\Resolver;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict\Rewriter;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Contracts\Activator_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Contracts\Provider_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Loader;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Notices\Contracts\Writer_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Notices\Presenter;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Notices\Renderer;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Notices\Store;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Notices\Writer;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Plugin\Checker;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Plugin\Contracts\Checker_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Plugin\Contracts\Deactivator_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Plugin\Deactivator;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Registry\Contracts\Registrar_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Registry\Reader;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Registry\Registrar;
use StellarWP\Learndash\StellarWP\ContainerContract\ContainerInterface;
/**
 * Teaches the host's container how to build every collaborator this library uses.
 *
 * Binding only, and nothing here resolves: `register()` runs at boot, when a host may still be
 * binding, so building an object then would pin whichever implementation was bound first. `final`
 * because a binding is changed by binding it yourself before boot, not by inheriting the list.
 *
 * @since 1.0.0
 */
final class Provider implements Provider_Interface
{
    /**
     * @since 1.0.0
     *
     * @var ContainerInterface
     */
    private $container;
    /**
     * @since 1.0.0
     *
     * @param ContainerInterface $container Container to bind into.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    /**
     * @since 1.0.0
     *
     * @return void
     */
    public function register(): void
    {
        $container = $this->container;
        // The container under its own contract, first of all, so that a container which builds unbound
        // classes reflectively can still satisfy the collaborators below that take one.
        $this->bind_once(ContainerInterface::class, $container);
        $this->bind_once(Registrar_Interface::class, Registrar::class);
        $this->bind_once(Checker_Interface::class, Checker::class);
        $this->bind_once(Deactivator_Interface::class, Deactivator::class);
        $this->bind_once(Activator_Interface::class, Activator::class);
        $this->bind_once(Store::class);
        $this->bind_once(Renderer::class);
        $this->bind_once(Redirector::class);
        $this->bind_once(Gatekeeper::class);
        // Explicit factories for everything with a constructor argument: container-contract promises
        // nothing about autowiring, so a container that resolves nothing by reflection has to be told.
        $this->bind_once(Writer_Interface::class, static function () use ($container): Writer {
            return new Writer($container->get(Store::class));
        });
        $this->bind_once(Presenter::class, static function () use ($container): Presenter {
            return new Presenter($container->get(Store::class), $container->get(Renderer::class));
        });
        $this->bind_once(Reader::class, static function () use ($container): Reader {
            return new Reader($container->get(Registrar_Interface::class));
        });
        $this->bind_once(Rewriter::class, static function () use ($container): Rewriter {
            return new Rewriter($container->get(Reader::class));
        });
        $this->bind_once(Detector::class, static function () use ($container): Detector {
            return new Detector($container->get(Reader::class), $container->get(Checker_Interface::class));
        });
        $this->bind_once(Resolver_Interface::class, static function () use ($container): Resolver {
            return new Resolver($container->get(Reader::class), $container->get(Detector::class), $container->get(Deactivator_Interface::class), $container->get(Writer_Interface::class), $container->get(Redirector::class));
        });
        $this->bind_once(Loader::class, static function () use ($container): Loader {
            return new Loader($container->get(Reader::class), $container->get(Writer_Interface::class), $container->get(Activator_Interface::class));
        });
        $this->bind_once(Scheduler::class, static function () use ($container): Scheduler {
            return new Scheduler($container);
        });
    }
    /**
     * Bind as a singleton -- every binding here is one -- unless the host bound this id first.
     *
     * The guard stands down on interface ids only: `has()` means "can return an entry", not "the host
     * bound this" -- di52 answers it with `isBound() || class_exists()` -- so dropping that half would
     * stand down every concrete binding above. A host replacing a concrete worker binds after boot.
     *
     * @since 1.0.0
     *
     * @param string $id             Interface or class to bind.
     * @param mixed  $implementation Class name, instance or factory closure; `null` to have the
     *                               container build `$id` itself.
     *
     * @return void
     */
    private function bind_once(string $id, $implementation = null): void
    {
        if (!class_exists($id) && $this->container->has($id)) {
            return;
        }
        $this->container->singleton($id, $implementation);
    }
}