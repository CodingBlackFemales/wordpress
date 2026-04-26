<?php

namespace StellarWP\Learndash\Stripe;

/**
 * Client used to send requests to Stripe's API.
 *
 * @property \StellarWP\Learndash\Stripe\Service\OAuthService $oauth
 * // The beginning of the section generated from our OpenAPI spec
 * @property \StellarWP\Learndash\Stripe\Service\AccountLinkService $accountLinks
 * @property \StellarWP\Learndash\Stripe\Service\AccountService $accounts
 * @property \StellarWP\Learndash\Stripe\Service\AccountSessionService $accountSessions
 * @property \StellarWP\Learndash\Stripe\Service\ApplePayDomainService $applePayDomains
 * @property \StellarWP\Learndash\Stripe\Service\ApplicationFeeService $applicationFees
 * @property \StellarWP\Learndash\Stripe\Service\Apps\AppsServiceFactory $apps
 * @property \StellarWP\Learndash\Stripe\Service\BalanceService $balance
 * @property \StellarWP\Learndash\Stripe\Service\BalanceTransactionService $balanceTransactions
 * @property \StellarWP\Learndash\Stripe\Service\Billing\BillingServiceFactory $billing
 * @property \StellarWP\Learndash\Stripe\Service\BillingPortal\BillingPortalServiceFactory $billingPortal
 * @property \StellarWP\Learndash\Stripe\Service\ChargeService $charges
 * @property \StellarWP\Learndash\Stripe\Service\Checkout\CheckoutServiceFactory $checkout
 * @property \StellarWP\Learndash\Stripe\Service\Climate\ClimateServiceFactory $climate
 * @property \StellarWP\Learndash\Stripe\Service\ConfirmationTokenService $confirmationTokens
 * @property \StellarWP\Learndash\Stripe\Service\CountrySpecService $countrySpecs
 * @property \StellarWP\Learndash\Stripe\Service\CouponService $coupons
 * @property \StellarWP\Learndash\Stripe\Service\CreditNoteService $creditNotes
 * @property \StellarWP\Learndash\Stripe\Service\CustomerService $customers
 * @property \StellarWP\Learndash\Stripe\Service\CustomerSessionService $customerSessions
 * @property \StellarWP\Learndash\Stripe\Service\DisputeService $disputes
 * @property \StellarWP\Learndash\Stripe\Service\Entitlements\EntitlementsServiceFactory $entitlements
 * @property \StellarWP\Learndash\Stripe\Service\EphemeralKeyService $ephemeralKeys
 * @property \StellarWP\Learndash\Stripe\Service\EventService $events
 * @property \StellarWP\Learndash\Stripe\Service\ExchangeRateService $exchangeRates
 * @property \StellarWP\Learndash\Stripe\Service\FileLinkService $fileLinks
 * @property \StellarWP\Learndash\Stripe\Service\FileService $files
 * @property \StellarWP\Learndash\Stripe\Service\FinancialConnections\FinancialConnectionsServiceFactory $financialConnections
 * @property \StellarWP\Learndash\Stripe\Service\Forwarding\ForwardingServiceFactory $forwarding
 * @property \StellarWP\Learndash\Stripe\Service\Identity\IdentityServiceFactory $identity
 * @property \StellarWP\Learndash\Stripe\Service\InvoiceItemService $invoiceItems
 * @property \StellarWP\Learndash\Stripe\Service\InvoiceService $invoices
 * @property \StellarWP\Learndash\Stripe\Service\Issuing\IssuingServiceFactory $issuing
 * @property \StellarWP\Learndash\Stripe\Service\MandateService $mandates
 * @property \StellarWP\Learndash\Stripe\Service\PaymentIntentService $paymentIntents
 * @property \StellarWP\Learndash\Stripe\Service\PaymentLinkService $paymentLinks
 * @property \StellarWP\Learndash\Stripe\Service\PaymentMethodConfigurationService $paymentMethodConfigurations
 * @property \StellarWP\Learndash\Stripe\Service\PaymentMethodDomainService $paymentMethodDomains
 * @property \StellarWP\Learndash\Stripe\Service\PaymentMethodService $paymentMethods
 * @property \StellarWP\Learndash\Stripe\Service\PayoutService $payouts
 * @property \StellarWP\Learndash\Stripe\Service\PlanService $plans
 * @property \StellarWP\Learndash\Stripe\Service\PriceService $prices
 * @property \StellarWP\Learndash\Stripe\Service\ProductService $products
 * @property \StellarWP\Learndash\Stripe\Service\PromotionCodeService $promotionCodes
 * @property \StellarWP\Learndash\Stripe\Service\QuoteService $quotes
 * @property \StellarWP\Learndash\Stripe\Service\Radar\RadarServiceFactory $radar
 * @property \StellarWP\Learndash\Stripe\Service\RefundService $refunds
 * @property \StellarWP\Learndash\Stripe\Service\Reporting\ReportingServiceFactory $reporting
 * @property \StellarWP\Learndash\Stripe\Service\ReviewService $reviews
 * @property \StellarWP\Learndash\Stripe\Service\SetupAttemptService $setupAttempts
 * @property \StellarWP\Learndash\Stripe\Service\SetupIntentService $setupIntents
 * @property \StellarWP\Learndash\Stripe\Service\ShippingRateService $shippingRates
 * @property \StellarWP\Learndash\Stripe\Service\Sigma\SigmaServiceFactory $sigma
 * @property \StellarWP\Learndash\Stripe\Service\SourceService $sources
 * @property \StellarWP\Learndash\Stripe\Service\SubscriptionItemService $subscriptionItems
 * @property \StellarWP\Learndash\Stripe\Service\SubscriptionService $subscriptions
 * @property \StellarWP\Learndash\Stripe\Service\SubscriptionScheduleService $subscriptionSchedules
 * @property \StellarWP\Learndash\Stripe\Service\Tax\TaxServiceFactory $tax
 * @property \StellarWP\Learndash\Stripe\Service\TaxCodeService $taxCodes
 * @property \StellarWP\Learndash\Stripe\Service\TaxIdService $taxIds
 * @property \StellarWP\Learndash\Stripe\Service\TaxRateService $taxRates
 * @property \StellarWP\Learndash\Stripe\Service\Terminal\TerminalServiceFactory $terminal
 * @property \StellarWP\Learndash\Stripe\Service\TestHelpers\TestHelpersServiceFactory $testHelpers
 * @property \StellarWP\Learndash\Stripe\Service\TokenService $tokens
 * @property \StellarWP\Learndash\Stripe\Service\TopupService $topups
 * @property \StellarWP\Learndash\Stripe\Service\TransferService $transfers
 * @property \StellarWP\Learndash\Stripe\Service\Treasury\TreasuryServiceFactory $treasury
 * @property \StellarWP\Learndash\Stripe\Service\WebhookEndpointService $webhookEndpoints
 * // The end of the section generated from our OpenAPI spec
 */
class StripeClient extends BaseStripeClient
{
    /**
     * @var \StellarWP\Learndash\Stripe\Service\CoreServiceFactory
     */
    private $coreServiceFactory;

    public function __get($name)
    {
        return $this->getService($name);
    }

    public function getService($name)
    {
        if (null === $this->coreServiceFactory) {
            $this->coreServiceFactory = new \StellarWP\Learndash\Stripe\Service\CoreServiceFactory($this);
        }

        return $this->coreServiceFactory->getService($name);
    }
}
