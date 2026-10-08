<?php declare(strict_types=1);

namespace StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Contracts;

use JsonException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\Contracts\ApiErrorExceptionInterface;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\MissingAuthenticationException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\UnexpectedResponseException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Requests\Token\Create as CreateRequest;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Requests\Token\Revoke as RevokeRequest;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Token\Auth;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Token\TokenList;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Token\ValueObjects\TokenItem;
use StellarWP\Learndash\Psr\Http\Client\ClientExceptionInterface;

/**
 * Defines the tokens resource surface.
 */
interface TokensResourceInterface
{
	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function list(string $licenseKey): TokenList;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function create(CreateRequest $request): TokenItem;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function revoke(RevokeRequest $request): TokenItem;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function auth(string $licenseKey, string $token, string $domain): Auth;
}
