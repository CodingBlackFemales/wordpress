<?php declare( strict_types=1 );

namespace StellarWP\Learndash\LiquidWeb\Harbor\Http;

use StellarWP\Learndash\LiquidWeb\LicensingApiClientWordPress\Http\WordPressHttpClient;
use StellarWP\Learndash\Nyholm\Psr7\Factory\Psr17Factory;
use StellarWP\Learndash\Psr\Http\Client\ClientInterface;
use StellarWP\Learndash\Psr\Http\Message\RequestFactoryInterface;
use StellarWP\Learndash\Psr\Http\Message\StreamFactoryInterface;
use StellarWP\Learndash\LiquidWeb\Harbor\Contracts\Abstract_Provider;

/**
 * Registers shared PSR-17 HTTP message factories in the DI container.
 *
 * @since 1.0.0
 */
final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->container->singleton( WordPressHttpClient::class );
		$this->container->singleton( ClientInterface::class, WordPressHttpClient::class );
		$this->container->singleton( Psr17Factory::class );
		$this->container->singleton( RequestFactoryInterface::class, Psr17Factory::class );
		$this->container->singleton( StreamFactoryInterface::class, Psr17Factory::class );
	}
}
