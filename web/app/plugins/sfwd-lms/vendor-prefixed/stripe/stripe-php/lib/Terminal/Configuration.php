<?php

// File generated from our OpenAPI spec
namespace StellarWP\Learndash\Stripe\Terminal;

/**
 * A Configurations object represents how features should be configured for terminal readers.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|\StellarWP\Learndash\Stripe\StripeObject $bbpos_wisepos_e
 * @property null|bool $is_account_default Whether this Configuration is the default for your account
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|string $name String indicating the name of the Configuration object, set by the user
 * @property null|\StellarWP\Learndash\Stripe\StripeObject $offline
 * @property null|\StellarWP\Learndash\Stripe\StripeObject $tipping
 * @property null|\StellarWP\Learndash\Stripe\StripeObject $verifone_p400
 */
class Configuration extends \StellarWP\Learndash\Stripe\ApiResource
{
    const OBJECT_NAME = 'terminal.configuration';
    use \StellarWP\Learndash\Stripe\ApiOperations\All;
    use \StellarWP\Learndash\Stripe\ApiOperations\Create;
    use \StellarWP\Learndash\Stripe\ApiOperations\Delete;
    use \StellarWP\Learndash\Stripe\ApiOperations\Retrieve;
    use \StellarWP\Learndash\Stripe\ApiOperations\Update;
}