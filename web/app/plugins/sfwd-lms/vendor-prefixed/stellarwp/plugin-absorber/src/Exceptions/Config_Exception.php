<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions;

use RuntimeException;
/**
 * Thrown when the library is configured incorrectly.
 *
 * Extends RuntimeException so callers may catch either type.
 *
 * @since 1.0.0
 */
class Config_Exception extends RuntimeException
{
}