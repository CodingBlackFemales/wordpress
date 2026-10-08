<?php declare(strict_types=1);

namespace StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Contracts;

use JsonException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\Contracts\ApiErrorExceptionInterface;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\MissingAuthenticationException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\UnexpectedResponseException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Requests\Entitlement\SwitchTier;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Requests\Entitlement\Upsert;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Entitlement\Cancel;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Entitlement\Delete;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Entitlement\Suspend;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Entitlement\SwitchTier as SwitchTierResponse;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Entitlement\Unsuspend;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Entitlement\Upsert as UpsertResponse;
use StellarWP\Learndash\Psr\Http\Client\ClientExceptionInterface;

/**
 * Defines the entitlements resource surface.
 */
interface EntitlementsResourceInterface
{
	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function upsert(Upsert $request): UpsertResponse;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function switchTier(SwitchTier $request): SwitchTierResponse;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function suspend(string $licenseKey, string $productSlug, string $tier): Suspend;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function unsuspend(string $licenseKey, string $productSlug, string $tier): Unsuspend;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function cancel(string $licenseKey, string $productSlug, string $tier, ?string $reason = null): Cancel;

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function delete(string $licenseKey, string $productSlug, string $tier): Delete;
}
