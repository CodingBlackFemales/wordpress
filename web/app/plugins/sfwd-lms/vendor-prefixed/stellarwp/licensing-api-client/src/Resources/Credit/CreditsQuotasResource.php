<?php declare(strict_types=1);

namespace StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Credit;

use JsonException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\ApiResponseException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\MissingAuthenticationException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\UnexpectedResponseException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\AuthState;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\Factories\ApiUriFactory;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\RequestExecutor;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\RequestHeaderCollection;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Requests\Credit\SetQuota;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Concerns\RebindsAuthState;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Concerns\RebindsRequestHeaderCollection;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Contracts\CreditsQuotasResourceInterface;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Credit\DeleteQuota;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Credit\QuotaCollection;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Credit\ValueObjects\SiteQuota;
use StellarWP\Learndash\Psr\Http\Client\ClientExceptionInterface;

/**
 * Provides operations for the credits quotas API resource.
 *
 * @phpstan-import-type SetQuotaPayload from \LiquidWeb\LicensingApiClient\Requests\Credit\SetQuota
 * @phpstan-type SiteQuotaPayload array{
 *     domain: string,
 *     credit_type: string,
 *     quota: ?int,
 *     period: string,
 *     first_period_start: ?string,
 *     is_blocked: bool,
 *     is_uncapped: bool
 * }
 * @phpstan-type QuotaCollectionPayload array{
 *     quotas: list<SiteQuotaPayload>
 * }
 * @phpstan-type DeleteQuotaPayload array{
 *     deleted: bool
 * }
 */
final class CreditsQuotasResource implements CreditsQuotasResourceInterface
{
	use RebindsAuthState;
	use RebindsRequestHeaderCollection;

	private RequestExecutor $requestExecutor;

	private ApiUriFactory $apiUriFactory;

	private AuthState $authState;

	private RequestHeaderCollection $requestHeaderCollection;

	public function __construct(
		RequestExecutor $requestExecutor,
		ApiUriFactory $apiUriFactory,
		AuthState $authState,
		RequestHeaderCollection $requestHeaderCollection
	) {
		$this->requestExecutor         = $requestExecutor;
		$this->apiUriFactory           = $apiUriFactory;
		$this->authState               = $authState;
		$this->requestHeaderCollection = $requestHeaderCollection;
	}

	/**
	 * @throws ApiResponseException
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function list(string $licenseKey): QuotaCollection {
		$result = $this->requestExecutor->executeJson(
			'GET',
			$this->apiUriFactory->make('/credits/quotas'),
			[
				'license_key' => $licenseKey,
			],
			null,
			$this->authState->requiredToken(),
			$this->requestHeaderCollection->all()
		);

		/** @var QuotaCollectionPayload $result */
		return QuotaCollection::from($result);
	}

	/**
	 * @throws ApiResponseException
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function set(SetQuota $request): SiteQuota {
		/** @var SetQuotaPayload $body */
		$body = $request->toArray();

		$result = $this->requestExecutor->executeJson(
			'POST',
			$this->apiUriFactory->make('/credits/quotas'),
			[],
			$body,
			$this->authState->requiredToken(),
			$this->requestHeaderCollection->all()
		);

		/** @var SiteQuotaPayload $result */
		return SiteQuota::from($result);
	}

	/**
	 * @throws ApiResponseException
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function delete(string $licenseKey, string $domain, string $creditType): DeleteQuota {
		$result = $this->requestExecutor->executeJson(
			'DELETE',
			$this->apiUriFactory->make('/credits/quotas'),
			[],
			[
				'license_key' => $licenseKey,
				'domain'      => $domain,
				'credit_type' => $creditType,
			],
			$this->authState->requiredToken(),
			$this->requestHeaderCollection->all()
		);

		/** @var DeleteQuotaPayload $result */
		return DeleteQuota::from($result);
	}

	protected function rebindWithAuthState(AuthState $authState): self {
		return new self($this->requestExecutor, $this->apiUriFactory, $authState, $this->requestHeaderCollection);
	}

	protected function rebindWithRequestHeaderCollection(RequestHeaderCollection $requestHeaderCollection): self {
		return new self($this->requestExecutor, $this->apiUriFactory, $this->authState, $requestHeaderCollection);
	}
}
