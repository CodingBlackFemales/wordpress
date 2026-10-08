<?php declare(strict_types=1);

namespace StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Contracts;

use JsonException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\Contracts\ApiErrorExceptionInterface;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\MissingAuthenticationException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\UnexpectedResponseException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Requests\Credit\RecordUsage as RecordUsageRequest;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Requests\Credit\Refund as RefundRequest;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Credit\BalanceCollection;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Credit\RecordUsage;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Credit\Refund;
use StellarWP\Learndash\Psr\Http\Client\ClientExceptionInterface;

/**
 * Defines the root credits resource surface.
 */
interface CreditsResourceInterface
{
	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function balance(string $licenseKey, string $domain, ?string $creditType = null, ?string $sort = null): BalanceCollection;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function recordUsage(RecordUsageRequest $request): RecordUsage;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function refund(RefundRequest $request): Refund;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function pools(): CreditsPoolsResourceInterface;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function quotas(): CreditsQuotasResourceInterface;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function ledger(): CreditsLedgerResourceInterface;
}
