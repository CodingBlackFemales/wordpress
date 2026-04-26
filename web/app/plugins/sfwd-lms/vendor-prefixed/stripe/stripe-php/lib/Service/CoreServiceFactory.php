<?php

namespace StellarWP\Learndash\Stripe\Service;

/**
 * Service factory class for API resources in the root namespace.
 *
 * @property OAuthService $oauth
 * // Doc: The beginning of the section generated from our OpenAPI spec
 * @property AccountLinkService $accountLinks
 * @property AccountService $accounts
 * @property AccountSessionService $accountSessions
 * @property ApplePayDomainService $applePayDomains
 * @property ApplicationFeeService $applicationFees
 * @property Apps\AppsServiceFactory $apps
 * @property BalanceService $balance
 * @property BalanceTransactionService $balanceTransactions
 * @property Billing\BillingServiceFactory $billing
 * @property BillingPortal\BillingPortalServiceFactory $billingPortal
 * @property ChargeService $charges
 * @property Checkout\CheckoutServiceFactory $checkout
 * @property Climate\ClimateServiceFactory $climate
 * @property ConfirmationTokenService $confirmationTokens
 * @property CountrySpecService $countrySpecs
 * @property CouponService $coupons
 * @property CreditNoteService $creditNotes
 * @property CustomerService $customers
 * @property CustomerSessionService $customerSessions
 * @property DisputeService $disputes
 * @property Entitlements\EntitlementsServiceFactory $entitlements
 * @property EphemeralKeyService $ephemeralKeys
 * @property EventService $events
 * @property ExchangeRateService $exchangeRates
 * @property FileLinkService $fileLinks
 * @property FileService $files
 * @property FinancialConnections\FinancialConnectionsServiceFactory $financialConnections
 * @property Forwarding\ForwardingServiceFactory $forwarding
 * @property Identity\IdentityServiceFactory $identity
 * @property InvoiceItemService $invoiceItems
 * @property InvoiceService $invoices
 * @property Issuing\IssuingServiceFactory $issuing
 * @property MandateService $mandates
 * @property PaymentIntentService $paymentIntents
 * @property PaymentLinkService $paymentLinks
 * @property PaymentMethodConfigurationService $paymentMethodConfigurations
 * @property PaymentMethodDomainService $paymentMethodDomains
 * @property PaymentMethodService $paymentMethods
 * @property PayoutService $payouts
 * @property PlanService $plans
 * @property PriceService $prices
 * @property ProductService $products
 * @property PromotionCodeService $promotionCodes
 * @property QuoteService $quotes
 * @property Radar\RadarServiceFactory $radar
 * @property RefundService $refunds
 * @property Reporting\ReportingServiceFactory $reporting
 * @property ReviewService $reviews
 * @property SetupAttemptService $setupAttempts
 * @property SetupIntentService $setupIntents
 * @property ShippingRateService $shippingRates
 * @property Sigma\SigmaServiceFactory $sigma
 * @property SourceService $sources
 * @property SubscriptionItemService $subscriptionItems
 * @property SubscriptionService $subscriptions
 * @property SubscriptionScheduleService $subscriptionSchedules
 * @property Tax\TaxServiceFactory $tax
 * @property TaxCodeService $taxCodes
 * @property TaxIdService $taxIds
 * @property TaxRateService $taxRates
 * @property Terminal\TerminalServiceFactory $terminal
 * @property TestHelpers\TestHelpersServiceFactory $testHelpers
 * @property TokenService $tokens
 * @property TopupService $topups
 * @property TransferService $transfers
 * @property Treasury\TreasuryServiceFactory $treasury
 * @property WebhookEndpointService $webhookEndpoints
 * // Doc: The end of the section generated from our OpenAPI spec
 */
class CoreServiceFactory extends \StellarWP\Learndash\Stripe\Service\AbstractServiceFactory
{
    /**
     * @var array<string, string>
     */
    private static $classMap = [
        'oauth' => OAuthService::class,
        // Class Map: The beginning of the section generated from our OpenAPI spec
        'accountLinks' => AccountLinkService::class,
        'accounts' => AccountService::class,
        'accountSessions' => AccountSessionService::class,
        'applePayDomains' => ApplePayDomainService::class,
        'applicationFees' => ApplicationFeeService::class,
        'apps' => \StellarWP\Learndash\Stripe\Service\Apps\AppsServiceFactory::class,
        'balance' => BalanceService::class,
        'balanceTransactions' => BalanceTransactionService::class,
        'billing' => \StellarWP\Learndash\Stripe\Service\Billing\BillingServiceFactory::class,
        'billingPortal' => \StellarWP\Learndash\Stripe\Service\BillingPortal\BillingPortalServiceFactory::class,
        'charges' => ChargeService::class,
        'checkout' => \StellarWP\Learndash\Stripe\Service\Checkout\CheckoutServiceFactory::class,
        'climate' => \StellarWP\Learndash\Stripe\Service\Climate\ClimateServiceFactory::class,
        'confirmationTokens' => ConfirmationTokenService::class,
        'countrySpecs' => CountrySpecService::class,
        'coupons' => CouponService::class,
        'creditNotes' => CreditNoteService::class,
        'customers' => CustomerService::class,
        'customerSessions' => CustomerSessionService::class,
        'disputes' => DisputeService::class,
        'entitlements' => \StellarWP\Learndash\Stripe\Service\Entitlements\EntitlementsServiceFactory::class,
        'ephemeralKeys' => EphemeralKeyService::class,
        'events' => EventService::class,
        'exchangeRates' => ExchangeRateService::class,
        'fileLinks' => FileLinkService::class,
        'files' => FileService::class,
        'financialConnections' => \StellarWP\Learndash\Stripe\Service\FinancialConnections\FinancialConnectionsServiceFactory::class,
        'forwarding' => \StellarWP\Learndash\Stripe\Service\Forwarding\ForwardingServiceFactory::class,
        'identity' => \StellarWP\Learndash\Stripe\Service\Identity\IdentityServiceFactory::class,
        'invoiceItems' => InvoiceItemService::class,
        'invoices' => InvoiceService::class,
        'issuing' => \StellarWP\Learndash\Stripe\Service\Issuing\IssuingServiceFactory::class,
        'mandates' => MandateService::class,
        'paymentIntents' => PaymentIntentService::class,
        'paymentLinks' => PaymentLinkService::class,
        'paymentMethodConfigurations' => PaymentMethodConfigurationService::class,
        'paymentMethodDomains' => PaymentMethodDomainService::class,
        'paymentMethods' => PaymentMethodService::class,
        'payouts' => PayoutService::class,
        'plans' => PlanService::class,
        'prices' => PriceService::class,
        'products' => ProductService::class,
        'promotionCodes' => PromotionCodeService::class,
        'quotes' => QuoteService::class,
        'radar' => \StellarWP\Learndash\Stripe\Service\Radar\RadarServiceFactory::class,
        'refunds' => RefundService::class,
        'reporting' => \StellarWP\Learndash\Stripe\Service\Reporting\ReportingServiceFactory::class,
        'reviews' => ReviewService::class,
        'setupAttempts' => SetupAttemptService::class,
        'setupIntents' => SetupIntentService::class,
        'shippingRates' => ShippingRateService::class,
        'sigma' => \StellarWP\Learndash\Stripe\Service\Sigma\SigmaServiceFactory::class,
        'sources' => SourceService::class,
        'subscriptionItems' => SubscriptionItemService::class,
        'subscriptions' => SubscriptionService::class,
        'subscriptionSchedules' => SubscriptionScheduleService::class,
        'tax' => \StellarWP\Learndash\Stripe\Service\Tax\TaxServiceFactory::class,
        'taxCodes' => TaxCodeService::class,
        'taxIds' => TaxIdService::class,
        'taxRates' => TaxRateService::class,
        'terminal' => \StellarWP\Learndash\Stripe\Service\Terminal\TerminalServiceFactory::class,
        'testHelpers' => \StellarWP\Learndash\Stripe\Service\TestHelpers\TestHelpersServiceFactory::class,
        'tokens' => TokenService::class,
        'topups' => TopupService::class,
        'transfers' => TransferService::class,
        'treasury' => \StellarWP\Learndash\Stripe\Service\Treasury\TreasuryServiceFactory::class,
        'webhookEndpoints' => WebhookEndpointService::class,
    ];
    protected function getServiceClass($name)
    {
        return \array_key_exists($name, self::$classMap) ? self::$classMap[$name] : null;
    }
}