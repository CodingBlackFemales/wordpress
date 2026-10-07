<?php declare(strict_types=1);

namespace StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources;

use JsonException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\Contracts\ApiErrorExceptionInterface;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\MissingAuthenticationException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Exceptions\UnexpectedResponseException;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\AuthState;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\Factories\ApiUriFactory;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\RequestExecutor;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\RequestHeaderCollection;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Requests\Token\Create as CreateRequest;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Requests\Token\Revoke as RevokeRequest;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Concerns\RebindsAuthState;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Concerns\RebindsRequestHeaderCollection;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Contracts\TokensResourceInterface;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Token\Auth;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Token\TokenList;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Responses\Token\ValueObjects\TokenItem;
use StellarWP\Learndash\Psr\Http\Client\ClientExceptionInterface;

/**
 * Provides operations for the tokens API resource.
 *
 * @phpstan-import-type CreateTokenPayload from CreateRequest
 * @phpstan-import-type RevokeTokenPayload from RevokeRequest
 * @phpstan-import-type TokenItemPayload from TokenItem
 * @phpstan-type CreateResponsePayload TokenItemPayload
 * @phpstan-type RevokeResponsePayload TokenItemPayload
 * @phpstan-type AuthResponsePayload array{authorized: bool}
 * @phpstan-type TokenListPayload array{
 *     tokens: list<array{
 *         id: int,
 *         token: string,
 *         license_id: int,
 *         domain: string,
 *         is_revoked: bool,
 *         created_at: string,
 *         updated_at: string
 *     }>
 * }
 */
final class TokensResource implements TokensResourceInterface
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
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function list(string $licenseKey): TokenList {
		$result = $this->requestExecutor->executeJson(
			'GET',
			$this->apiUriFactory->make('/tokens'),
			['license_key' => $licenseKey],
			null,
			$this->authState->requiredToken(),
			$this->requestHeaderCollection->all()
		);

		/** @var TokenListPayload $result */
		return TokenList::from($result);
	}

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function create(CreateRequest $request): TokenItem {
		/** @var CreateTokenPayload $body */
		$body = $request->toArray();

		$result = $this->requestExecutor->executeJson(
			'POST',
			$this->apiUriFactory->make('/tokens/create'),
			[],
			$body,
			$this->authState->requiredToken(),
			$this->requestHeaderCollection->all()
		);

		/** @var CreateResponsePayload $result */
		return TokenItem::from($result);
	}

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws MissingAuthenticationException
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function revoke(RevokeRequest $request): TokenItem {
		/** @var RevokeTokenPayload $body */
		$body = $request->toArray();

		$result = $this->requestExecutor->executeJson(
			'PUT',
			$this->apiUriFactory->make('/tokens/revoke'),
			[],
			$body,
			$this->authState->requiredToken(),
			$this->requestHeaderCollection->all()
		);

		/** @var RevokeResponsePayload $result */
		return TokenItem::from($result);
	}

	/**
	 * @throws ApiErrorExceptionInterface
	 * @throws UnexpectedResponseException
	 * @throws ClientExceptionInterface
	 * @throws JsonException
	 */
	public function auth(string $licenseKey, string $token, string $domain): Auth {
		$result = $this->requestExecutor->executeJson(
			'GET',
			$this->apiUriFactory->make('/tokens/auth'),
			[
				'license_key' => $licenseKey,
				'token'       => $token,
				'domain'      => $domain,
			],
			null,
			$this->authState->optionalToken(),
			$this->requestHeaderCollection->all()
		);

		/** @var AuthResponsePayload $result */
		return Auth::from($result);
	}

	protected function rebindWithAuthState(AuthState $authState): self {
		return new self($this->requestExecutor, $this->apiUriFactory, $authState, $this->requestHeaderCollection);
	}

	protected function rebindWithRequestHeaderCollection(RequestHeaderCollection $requestHeaderCollection): self {
		return new self($this->requestExecutor, $this->apiUriFactory, $this->authState, $requestHeaderCollection);
	}
}
