<?php declare(strict_types=1);

namespace StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Concerns;

use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\RequestHeaderCollection;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsLedgerResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsPoolsResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsQuotasResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\EntitlementsResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\LicensesResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\ProductsResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\TokensResource;

/**
 * Provides immutable request-header rebinding for resource views.
 *
 * @mixin CreditsLedgerResource
 * @mixin CreditsPoolsResource
 * @mixin CreditsQuotasResource
 * @mixin CreditsResource
 * @mixin EntitlementsResource
 * @mixin LicensesResource
 * @mixin ProductsResource
 * @mixin TokensResource
 */
trait RebindsRequestHeaderCollection
{
	public function withRequestHeaderCollection(RequestHeaderCollection $requestHeaderCollection): self {
		if ($this->requestHeaderCollection === $requestHeaderCollection) {
			return $this;
		}

		return $this->rebindWithRequestHeaderCollection($requestHeaderCollection);
	}

	abstract protected function rebindWithRequestHeaderCollection(RequestHeaderCollection $requestHeaderCollection): self;
}
