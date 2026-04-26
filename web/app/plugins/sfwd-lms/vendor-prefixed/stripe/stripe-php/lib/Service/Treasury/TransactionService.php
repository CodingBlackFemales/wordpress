<?php

// File generated from our OpenAPI spec

namespace StellarWP\Learndash\Stripe\Service\Treasury;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
/**
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
class TransactionService extends \StellarWP\Learndash\Stripe\Service\AbstractService
{
    /**
     * Retrieves a list of Transaction objects.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarWP\Learndash\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarWP\Learndash\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarWP\Learndash\Stripe\Collection<\StellarWP\Learndash\Stripe\Treasury\Transaction>
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/treasury/transactions', $params, $opts);
    }

    /**
     * Retrieves the details of an existing Transaction.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarWP\Learndash\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarWP\Learndash\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarWP\Learndash\Stripe\Treasury\Transaction
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/treasury/transactions/%s', $id), $params, $opts);
    }
}
