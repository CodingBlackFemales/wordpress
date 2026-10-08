<?php declare(strict_types=1);

namespace StellarWP\Learndash\LiquidWeb\LicensingApiClient;

use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\ApiVersion;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\AuthContext;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\AuthState;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\Factories\ApiUriFactory;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\Factories\ResponseExceptionFactory;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\JsonDecoder;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\RequestBuilder;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\RequestExecutor;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\RequestHeaderCollection;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsLedgerResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsPoolsResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsQuotasResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\EntitlementsResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\LicensesResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\ProductsResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\TokensResource;
use StellarWP\Learndash\Psr\Http\Client\ClientInterface as HttpClient;
use StellarWP\Learndash\Psr\Http\Message\RequestFactoryInterface;
use StellarWP\Learndash\Psr\Http\Message\StreamFactoryInterface;

/**
 * Builds a fully-wired API client from the transport dependencies.
 *
 * Use this if your application is not using a container to build dependencies.
 */
final class ApiBuilder
{
	private HttpClient $httpClient;

	private RequestFactoryInterface $requestFactory;

	private StreamFactoryInterface $streamFactory;

	private Config $config;

	public function __construct(
		HttpClient $httpClient,
		RequestFactoryInterface $requestFactory,
		StreamFactoryInterface $streamFactory,
		Config $config
	) {
		$this->httpClient     = $httpClient;
		$this->requestFactory = $requestFactory;
		$this->streamFactory  = $streamFactory;
		$this->config         = $config;
	}

	public function build(): Api {
		$authState               = new AuthState(new AuthContext(), $this->config->configuredToken);
		$requestHeaderCollection = new RequestHeaderCollection();
		$apiUriFactory           = new ApiUriFactory($this->config, ApiVersion::default());
		$requestExecutor         = $this->buildRequestExecutor();
		$creditsPools            = new CreditsPoolsResource($requestExecutor, $apiUriFactory, $authState, $requestHeaderCollection);
		$creditsQuotas           = new CreditsQuotasResource($requestExecutor, $apiUriFactory, $authState, $requestHeaderCollection);
		$creditsLedger           = new CreditsLedgerResource(
			$requestExecutor,
			$apiUriFactory,
			$authState,
			$requestHeaderCollection
		);

		return new Api(
			$authState,
			$requestHeaderCollection,
			new LicensesResource($requestExecutor, $apiUriFactory, $authState, $requestHeaderCollection),
			new ProductsResource($requestExecutor, $apiUriFactory, $authState, $requestHeaderCollection),
			new CreditsResource(
				$requestExecutor,
				$apiUriFactory,
				$authState,
				$requestHeaderCollection,
				$creditsPools,
				$creditsQuotas,
				$creditsLedger
			),
			new EntitlementsResource($requestExecutor, $apiUriFactory, $authState, $requestHeaderCollection),
			new TokensResource($requestExecutor, $apiUriFactory, $authState, $requestHeaderCollection)
		);
	}

	private function buildRequestExecutor(): RequestExecutor {
		$jsonDecoder = new JsonDecoder();

		return new RequestExecutor(
			$this->httpClient,
			new RequestBuilder(
				$this->requestFactory,
				$this->streamFactory
			),
			$jsonDecoder,
			new ResponseExceptionFactory($jsonDecoder)
		);
	}
}
