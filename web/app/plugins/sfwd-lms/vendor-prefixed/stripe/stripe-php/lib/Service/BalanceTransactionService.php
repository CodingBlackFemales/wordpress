<?php

// File generated from our OpenAPI spec

namespace StellarWP\Learndash\Stripe\Service;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
/**
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
class BalanceTransactionService extends \StellarWP\Learndash\Stripe\Service\AbstractService
{
    /**
     * Returns a list of transactions that have contributed to the Stripe account
     * balance (e.g., charges, transfers, and so forth). The transactions are returned
     * in sorted order, with the most recent transactions appearing first.
     *
     * Note that this endpoint was previously called “Balance history” and used the
     * path <code>/v1/balance/history</code>.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarWP\Learndash\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarWP\Learndash\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarWP\Learndash\Stripe\Collection<\StellarWP\Learndash\Stripe\BalanceTransaction>
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/balance_transactions', $params, $opts);
    }

    /**
     * Retrieves the balance transaction with the given ID.
     *
     * Note that this endpoint previously used the path
     * <code>/v1/balance/history/:id</code>.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarWP\Learndash\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarWP\Learndash\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarWP\Learndash\Stripe\BalanceTransaction
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/balance_transactions/%s', $id), $params, $opts);
    }
}
