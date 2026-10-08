<?php declare(strict_types=1);

namespace StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http;

use JsonException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\ApiResponseException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\DecodingException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\UnexpectedResponseException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\Factories\ResponseExceptionFactory;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Value\AuthToken;
use StellarWP\Learndash\Psr\Http\Client\ClientExceptionInterface;
use StellarWP\Learndash\Psr\Http\Client\ClientInterface as HttpClient;
use StellarWP\Learndash\Psr\Http\Message\ResponseInterface;

/**
 * Executes API requests and delegates response error mapping.
 *
 * @phpstan-import-type QueryValue from RequestBuilder
 * @phpstan-import-type HeaderValue from RequestBuilder
 * @phpstan-import-type JsonObject from RequestBuilder
 */
final class RequestExecutor
{
	private HttpClient $httpClient;

	private RequestBuilder $requestBuilder;

	private JsonDecoder $jsonDecoder;

	private ResponseExceptionFactory $responseExceptionFactory;

	public function __construct(
		HttpClient $httpClient,
		RequestBuilder $requestBuilder,
		JsonDecoder $jsonDecoder,
		ResponseExceptionFactory $responseExceptionFactory
	) {
		$this->httpClient               = $httpClient;
		$this->requestBuilder           = $requestBuilder;
		$this->jsonDecoder              = $jsonDecoder;
		$this->responseExceptionFactory = $responseExceptionFactory;
	}

	/**
	 * @param array<string, QueryValue> $query
	 * @param JsonObject|null           $body
	 * @param array<string, HeaderValue> $headers
	 *
	 * @throws ApiResponseException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function execute(
		string $method,
		ApiUri $uri,
		array $query = [],
		?array $body = null,
		?AuthToken $token = null,
		array $headers = []
	): ResponseInterface {
		$request    = $this->requestBuilder->build($method, $uri, $query, $body, $token, $headers);
		$response   = $this->httpClient->sendRequest($request);
		$statusCode = $response->getStatusCode();

		if ($statusCode >= 200 && $statusCode < 400) {
			return $response;
		}

		if ($statusCode >= 400) {
			throw $this->responseExceptionFactory->make($response);
		}

		throw new UnexpectedResponseException('Unexpected response status code.', $statusCode);
	}

	/**
	 * @param array<string, QueryValue> $query
	 * @param JsonObject|null           $body
	 * @param array<string, HeaderValue> $headers
	 *
	 * @throws ApiResponseException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 *
	 * @return array<array-key, mixed>
	 */
	public function executeJson(
		string $method,
		ApiUri $uri,
		array $query = [],
		?array $body = null,
		?AuthToken $token = null,
		array $headers = []
	): array {
		$response = $this->execute($method, $uri, $query, $body, $token, $headers);

		$body = (string) $response->getBody();

		try {
			return $this->jsonDecoder->decode($body);
		} catch (DecodingException $exception) {
			throw new UnexpectedResponseException('Unable to decode JSON response.', 0, $exception);
		}
	}
}
