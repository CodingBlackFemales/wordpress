<?php

// File generated from our OpenAPI spec

namespace StellarWP\Learndash\Stripe\Service\TestHelpers\Treasury;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
/**
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
class ReceivedCreditService extends \StellarWP\Learndash\Stripe\Service\AbstractService
{
    /**
     * Use this endpoint to simulate a test mode ReceivedCredit initiated by a third
     * party. In live mode, you can’t directly create ReceivedCredits initiated by
     * third parties.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarWP\Learndash\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarWP\Learndash\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarWP\Learndash\Stripe\Treasury\ReceivedCredit
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/test_helpers/treasury/received_credits', $params, $opts);
    }
}
