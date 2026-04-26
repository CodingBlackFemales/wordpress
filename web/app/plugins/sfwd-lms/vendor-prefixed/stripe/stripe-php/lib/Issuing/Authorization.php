<?php

// File generated from our OpenAPI spec
namespace StellarWP\Learndash\Stripe\Issuing;

/**
 * When an <a href="https://stripe.com/docs/issuing">issued card</a> is used to make a purchase, an Issuing <code>Authorization</code>
 * object is created. <a href="https://stripe.com/docs/issuing/purchases/authorizations">Authorizations</a> must be approved for the
 * purchase to be completed successfully.
 *
 * Related guide: <a href="https://stripe.com/docs/issuing/purchases/authorizations">Issued card authorizations</a>
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $amount The total amount that was authorized or rejected. This amount is in <code>currency</code> and in the <a href="https://stripe.com/docs/currencies#zero-decimal">smallest currency unit</a>. <code>amount</code> should be the same as <code>merchant_amount</code>, unless <code>currency</code> and <code>merchant_currency</code> are different.
 * @property null|\StellarWP\Learndash\Stripe\StripeObject $amount_details Detailed breakdown of amount components. These amounts are denominated in <code>currency</code> and in the <a href="https://stripe.com/docs/currencies#zero-decimal">smallest currency unit</a>.
 * @property bool $approved Whether the authorization has been approved.
 * @property string $authorization_method How the card details were provided.
 * @property \StellarWP\Learndash\Stripe\BalanceTransaction[] $balance_transactions List of balance transactions associated with this authorization.
 * @property \StellarWP\Learndash\Stripe\Issuing\Card $card You can <a href="https://stripe.com/docs/issuing/cards">create physical or virtual cards</a> that are issued to cardholders.
 * @property null|string|\StellarWP\Learndash\Stripe\Issuing\Cardholder $cardholder The cardholder to whom this authorization belongs.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $currency The currency of the cardholder. This currency can be different from the currency presented at authorization and the <code>merchant_currency</code> field on this authorization. Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property int $merchant_amount The total amount that was authorized or rejected. This amount is in the <code>merchant_currency</code> and in the <a href="https://stripe.com/docs/currencies#zero-decimal">smallest currency unit</a>. <code>merchant_amount</code> should be the same as <code>amount</code>, unless <code>merchant_currency</code> and <code>currency</code> are different.
 * @property string $merchant_currency The local currency that was presented to the cardholder for the authorization. This currency can be different from the cardholder currency and the <code>currency</code> field on this authorization. Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property \StellarWP\Learndash\Stripe\StripeObject $merchant_data
 * @property \StellarWP\Learndash\Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|\StellarWP\Learndash\Stripe\StripeObject $network_data Details about the authorization, such as identifiers, set by the card network.
 * @property null|\StellarWP\Learndash\Stripe\StripeObject $pending_request The pending authorization request. This field will only be non-null during an <code>issuing_authorization.request</code> webhook.
 * @property \StellarWP\Learndash\Stripe\StripeObject[] $request_history History of every time a <code>pending_request</code> authorization was approved/declined, either by you directly or by Stripe (e.g. based on your spending_controls). If the merchant changes the authorization by performing an incremental authorization, you can look at this field to see the previous requests for the authorization. This field can be helpful in determining why a given authorization was approved/declined.
 * @property string $status The current status of the authorization in its lifecycle.
 * @property null|string|\StellarWP\Learndash\Stripe\Issuing\Token $token <a href="https://stripe.com/docs/api/issuing/tokens/object">Token</a> object used for this authorization. If a network token was not used for this authorization, this field will be null.
 * @property \StellarWP\Learndash\Stripe\Issuing\Transaction[] $transactions List of <a href="https://stripe.com/docs/api/issuing/transactions">transactions</a> associated with this authorization.
 * @property null|\StellarWP\Learndash\Stripe\StripeObject $treasury <a href="https://stripe.com/docs/api/treasury">Treasury</a> details related to this authorization if it was created on a <a href="https://stripe.com/docs/api/treasury/financial_accounts">FinancialAccount</a>.
 * @property \StellarWP\Learndash\Stripe\StripeObject $verification_data
 * @property null|string $wallet The digital wallet used for this transaction. One of <code>apple_pay</code>, <code>google_pay</code>, or <code>samsung_pay</code>. Will populate as <code>null</code> when no digital wallet was utilized.
 */
class Authorization extends \StellarWP\Learndash\Stripe\ApiResource
{
    const OBJECT_NAME = 'issuing.authorization';
    use \StellarWP\Learndash\Stripe\ApiOperations\All;
    use \StellarWP\Learndash\Stripe\ApiOperations\Retrieve;
    use \StellarWP\Learndash\Stripe\ApiOperations\Update;
    const AUTHORIZATION_METHOD_CHIP = 'chip';
    const AUTHORIZATION_METHOD_CONTACTLESS = 'contactless';
    const AUTHORIZATION_METHOD_KEYED_IN = 'keyed_in';
    const AUTHORIZATION_METHOD_ONLINE = 'online';
    const AUTHORIZATION_METHOD_SWIPE = 'swipe';
    const STATUS_CLOSED = 'closed';
    const STATUS_PENDING = 'pending';
    const STATUS_REVERSED = 'reversed';
    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \StellarWP\Learndash\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarWP\Learndash\Stripe\Issuing\Authorization the approved authorization
     */
    public function approve($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/approve';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }
    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \StellarWP\Learndash\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarWP\Learndash\Stripe\Issuing\Authorization the declined authorization
     */
    public function decline($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/decline';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }
}