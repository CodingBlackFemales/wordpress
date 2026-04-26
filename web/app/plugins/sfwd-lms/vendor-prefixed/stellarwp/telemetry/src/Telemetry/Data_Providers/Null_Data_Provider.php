<?php

/**
 * A data provider that provides no data, used for testing.
 *
 * @since   2.1.0
 *
 * @package StellarWP\Learndash\StellarWP\Telemetry\Data_Providers;
 */
namespace StellarWP\Learndash\StellarWP\Telemetry\Data_Providers;

use StellarWP\Learndash\StellarWP\Telemetry\Contracts\Data_Provider;
/**
 * Class Null_Data_Provider.
 *
 * @since   2.1.0
 *
 * @package \StellarWP\Learndash\StellarWP\Telemetry\Data_Providers;
 */
class Null_Data_Provider implements Data_Provider
{
    /**
     * {@inheritDoc}
     *
     * @since   2.1.0
     */
    public function get_data(): array
    {
        return [];
    }
}