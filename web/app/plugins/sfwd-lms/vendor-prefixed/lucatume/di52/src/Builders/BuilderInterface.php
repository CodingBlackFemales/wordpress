<?php
/**
 * The API provided by each builder.
 *
 * @package lucatume\DI52
 *
 * @license GPL-3.0
 * Modified by learndash on 21-June-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace StellarWP\Learndash\lucatume\DI52\Builders;

/**
 * Interface BuilderInterface
 *
 * @package StellarWP\Learndash\lucatume\DI52\Builders
 */
interface BuilderInterface
{
    /**
     * Builds and returns the implementation handled by the builder.
     *
     * @return mixed The implementation provided by the builder.
     */
    public function build();
}
