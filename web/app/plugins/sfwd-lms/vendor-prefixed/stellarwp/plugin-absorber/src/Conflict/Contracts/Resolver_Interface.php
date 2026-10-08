<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Conflict\Contracts;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
/**
 * Decides what happens when a bundled sub-plugin's standalone counterpart is still active.
 *
 * Bind a replacement to change what happens about a conflict. One method, because finding the
 * conflict belongs to `Conflict\Detector` and deciding who may have one resolved to
 * `Conflict\Gatekeeper` -- both asked before an implementation of this is built.
 *
 * @since 1.0.0
 */
interface Resolver_Interface
{
    /**
     * Resolve the conflict for every registered sub-plugin whose standalone is active.
     *
     * @since 1.0.0
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    public function resolve_all(): void;
}