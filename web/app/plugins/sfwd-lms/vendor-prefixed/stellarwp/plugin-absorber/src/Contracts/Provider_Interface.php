<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Contracts;

/**
 * Teaches a container how to build this library's collaborators.
 *
 * It binds and nothing else: when things run is `Boot\Scheduler`'s subject, and a provider that
 * also hooked would have to be replaced wholesale by a host wanting one binding changed.
 *
 * @since 1.0.0
 */
interface Provider_Interface
{
    /**
     * Bind this library's collaborators.
     *
     * Must be safe to call again: `boot()` is idempotent, and another code path may register too.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register(): void;
}