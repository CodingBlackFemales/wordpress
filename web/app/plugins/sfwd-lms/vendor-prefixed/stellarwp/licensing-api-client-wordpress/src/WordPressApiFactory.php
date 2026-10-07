<?php declare(strict_types=1);

namespace StellarWP\Learndash\LiquidWeb\LicensingApiClientWordPress;

use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Api;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\ApiBuilder;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Config;
use StellarWP\Learndash\Psr\Http\Client\ClientInterface;
use StellarWP\Learndash\Psr\Http\Message\RequestFactoryInterface;
use StellarWP\Learndash\Psr\Http\Message\StreamFactoryInterface;

/**
 * Builds the core licensing API client with WordPress-compatible transport dependencies.
 *
 * @note Use this when you don't have a DI Container to build out the dependency tree.
 */
final class WordPressApiFactory
{
	private ClientInterface $httpClient;

	private RequestFactoryInterface $requestFactory;

	private StreamFactoryInterface $streamFactory;

	public function __construct(
		ClientInterface $httpClient,
		RequestFactoryInterface $requestFactory,
		StreamFactoryInterface $streamFactory
	) {
		$this->httpClient     = $httpClient;
		$this->requestFactory = $requestFactory;
		$this->streamFactory  = $streamFactory;
	}

	public function make(Config $config): Api {
		return (new ApiBuilder(
			$this->httpClient,
			$this->requestFactory,
			$this->streamFactory,
			$config
		))->build();
	}
}
