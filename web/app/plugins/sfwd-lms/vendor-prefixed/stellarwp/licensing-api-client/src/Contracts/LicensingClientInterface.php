<?php declare(strict_types=1);

namespace StellarWP\Learndash\LiquidWeb\LicensingApiClient\Contracts;

use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Contracts\CreditsResourceInterface;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Contracts\EntitlementsResourceInterface;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Contracts\LicensesResourceInterface;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Contracts\ProductsResourceInterface;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Resources\Contracts\TokensResourceInterface;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Tracing\TraceContext;
use StellarWP\Learndash\LiquidWeb\LicensingApiClient\Tracing\TraceParent;

/**
 * Defines the root entrypoint for the Licensing API client.
 */
interface LicensingClientInterface
{
	public function entitlements(): EntitlementsResourceInterface;

	public function licenses(): LicensesResourceInterface;

	public function products(): ProductsResourceInterface;

	public function credits(): CreditsResourceInterface;

	public function tokens(): TokensResourceInterface;

	public function withoutAuth(): self;

	public function withConfiguredToken(): self;

	public function withToken(string $token): self;

	/**
	 * @param array<string, string|int|float|bool> $headers
	 */
	public function withHeaders(array $headers): self;

	public function withTraceParent(TraceParent $traceParent): self;

	public function withTraceContext(TraceContext $traceContext): self;

	public function withoutHeaders(): self;
}
