<?php

// File generated from our OpenAPI spec

namespace StellarWP\Learndash\Stripe\Service\FinancialConnections;

/**
 * @phpstan-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
/**
 * @psalm-import-type RequestOptionsArray from \Stripe\Util\RequestOptions
 */
class TransactionService extends \StellarWP\Learndash\Stripe\Service\AbstractService
{
    /**
     * Returns a list of Financial Connections <code>Transaction</code> objects.
     *
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarWP\Learndash\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarWP\Learndash\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarWP\Learndash\Stripe\Collection<\StellarWP\Learndash\Stripe\FinancialConnections\Transaction>
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/financial_connections/transactions', $params, $opts);
    }

    /**
     * Retrieves the details of a Financial Connections <code>Transaction</code>.
     *
     * @param string $id
     * @param null|array $params
     * @param null|RequestOptionsArray|\StellarWP\Learndash\Stripe\Util\RequestOptions $opts
     *
     * @throws \StellarWP\Learndash\Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \StellarWP\Learndash\Stripe\FinancialConnections\Transaction
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/financial_connections/transactions/%s', $id), $params, $opts);
    }
}
