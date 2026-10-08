<?php

namespace StellarWP\Learndash\LiquidWeb\Harbor\Contracts;

use StellarWP\Learndash\StellarWP\ContainerContract\ContainerInterface;
use StellarWP\Learndash\LiquidWeb\Harbor\Config;

abstract class Abstract_Provider implements Provider_Interface {

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * Constructor for the class.
	 *
	 * @param ContainerInterface $container The DI container instance.
	 */
	public function __construct( $container = null ) {
		$this->container = $container ?: Config::get_container();
	}
}
