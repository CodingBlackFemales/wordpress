<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber;

/**
 * What to do when a sub-plugin's standalone counterpart is still active.
 *
 * @since 1.0.0
 */
final class Conflict_Policy
{
    /**
     * Deactivate the standalone, notify, and redirect. The bundled copy loads next request, since the
     * standalone has already defined the guard constant on this one.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public const DEACTIVATE = 'deactivate';
    /**
     * Leave the standalone alone and let it win. The load guard stands the bundled copy down.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public const DEFER = 'defer';
    /**
     * Leave the standalone active but ask the user to deactivate it.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public const NOTICE_ONLY = 'notice_only';
    /**
     * The policy that applies when a sub-plugin configures none.
     *
     * Deactivating, because two copies of the same plugin are the failure this library exists to
     * prevent. Configuring nothing accepts that; an unrecognised policy does not, so callers must
     * not reach this default for one — it would act on a typo.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public static function default(): string
    {
        return self::DEACTIVATE;
    }
    /**
     * Whether a policy string is one this library understands.
     *
     * A filter may return anything, and an unknown value must not fall through a `switch` into a
     * deactivation nobody asked for.
     *
     * @since 1.0.0
     *
     * @param string $policy Policy to check.
     *
     * @return bool
     */
    public static function is_valid(string $policy): bool
    {
        return in_array($policy, self::all(), true);
    }
    /**
     * Every policy this library understands.
     *
     * @since 1.0.0
     *
     * @return string[]
     */
    private static function all(): array
    {
        return [self::DEACTIVATE, self::DEFER, self::NOTICE_ONLY];
    }
}