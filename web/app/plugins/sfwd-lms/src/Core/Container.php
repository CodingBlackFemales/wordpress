<?php
/**
 * LearnDash Container class.
 *
 * @since 4.5.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core;

use StellarWP\Learndash\lucatume\DI52\Container as DI52Container;
use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\StellarWP\ContainerContract\ContainerInterface;

/**
 * LearnDash Container class.
 *
 * @since 4.5.0
 *
 * @method void register( $serviceProviderClass, ...$alias ) Registers a service provider implementation.
 */
class Container implements ContainerInterface {
	/**
	 * Container object.
	 *
	 * @since 4.5.0
	 *
	 * @var DI52Container
	 */
	protected $container;

	/**
	 * Container constructor.
	 *
	 * @since 4.5.0
	 */
	public function __construct() {
		$this->container = new DI52Container();
	}

	/**
	 * Binds an interface, a class or a string slug to an implementation.
	 *
	 * @since 4.5.0
	 *
	 * Existing implementations are replaced.
	 *
	 * @param string             $id                  A class or interface fully qualified name or a string slug.
	 * @param mixed              $implementation      The implementation that should be bound to the alias(es); can be a
	 *                                                class name, an object or a closure.
	 * @param array<string>|null $after_build_methods An array of methods that should be called on the built
	 *                                                implementation after resolving it.
	 *
	 * @return void The method does not return any value.
	 * @throws ContainerException      If there's an issue while trying to bind the implementation.
	 */
	public function bind( string $id, $implementation = null, array $after_build_methods = null ) {
		$this->container->bind( $id, $implementation, $after_build_methods );
	}

	/**
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @since 4.5.0
	 *
	 * @param string $id A fully qualified class or interface name or an already built object.
	 *
	 * @return mixed The entry for an id.
	 *
	 * @throws ContainerException Error while retrieving the entry.
	 */
	public function get( string $id ) {
		return $this->container->get( $id );
	}

	/**
	 * Returns true if the container can return an entry for the given identifier.
	 * Returns false otherwise.
	 *
	 * @since 4.5.0
	 *
	 * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
	 * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return bool Whether the container contains a binding for an id or not.
	 */
	public function has( string $id ) {
		return $this->container->has( $id );
	}

	/**
	 * Binds an interface a class or a string slug to an implementation and will always return the same instance.
	 *
	 * @since 4.5.0
	 *
	 * @param string             $id                  A class or interface fully qualified name or a string slug.
	 * @param mixed              $implementation      The implementation that should be bound to the alias(es); can be a
	 *                                                class name, an object or a closure.
	 * @param array<string>|null $after_build_methods An array of methods that should be called on the built
	 *                                                implementation after resolving it.
	 *
	 * @return void This method does not return any value.
	 * @throws ContainerException If there's any issue reflecting on the class, interface or the implementation.
	 */
	public function singleton( string $id, $implementation = null, array $after_build_methods = null ) {
		$this->container->singleton( $id, $implementation, $after_build_methods );
	}

	/**
	 * Defer all other calls to the container object.
	 *
	 * @since 4.5.0
	 *
	 * @param string       $name Method name.
	 * @param array<mixed> $args Method arguments.
	 *
	 * @return mixed
	 */
	public function __call( $name, $args ) {
		return $this->container->{$name}( ...$args );
	}
}
