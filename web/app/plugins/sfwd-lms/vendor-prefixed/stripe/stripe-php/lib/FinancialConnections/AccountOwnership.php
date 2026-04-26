<?php

// File generated from our OpenAPI spec

namespace StellarWP\Learndash\Stripe\FinancialConnections;

/**
 * Describes a snapshot of the owners of an account at a particular point in time.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property \StellarWP\Learndash\Stripe\Collection<\StellarWP\Learndash\Stripe\FinancialConnections\AccountOwner> $owners A paginated list of owners for this account.
 */
class AccountOwnership extends \StellarWP\Learndash\Stripe\ApiResource
{
    const OBJECT_NAME = 'financial_connections.account_ownership';
}
