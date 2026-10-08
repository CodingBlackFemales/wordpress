<?php declare(strict_types=1);

namespace StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Concerns;

use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Http\AuthState;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsLedgerResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsPoolsResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsQuotasResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Credit\CreditsResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\EntitlementsResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\LicensesResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\ProductsResource;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\TokensResource;

/**
 * Provides immutable auth-state rebinding for auth-bound resource views.
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
trait RebindsAuthState
{
	/**
	 * Returns the current resource when the auth state is unchanged, or a rebound
	 * resource view when a different auth state is requested.
	 */
	public function withAuthState(AuthState $authState): self {
		if ($this->authState === $authState) {
			return $this;
		}

		return $this->rebindWithAuthState($authState);
	}

	/**
	 * Rebuilds the concrete resource with the provided auth state.
	 */
	abstract protected function rebindWithAuthState(AuthState $authState): self;
}
