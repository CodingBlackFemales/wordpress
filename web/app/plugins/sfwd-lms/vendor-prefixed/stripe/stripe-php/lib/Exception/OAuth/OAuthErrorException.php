<?php

namespace StellarWP\Learndash\Stripe\Exception\OAuth;

/**
 * Implements properties and methods common to all (non-SPL) Stripe OAuth
 * exceptions.
 */
abstract class OAuthErrorException extends \StellarWP\Learndash\Stripe\Exception\ApiErrorException
{
    protected function constructErrorObject()
    {
        if (null === $this->jsonBody) {
            return null;
        }

        return \StellarWP\Learndash\Stripe\OAuthErrorObject::constructFrom($this->jsonBody);
    }
}
