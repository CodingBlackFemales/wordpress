<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Traits;

/**
 * The one answer to "may this user act on a plugin, at the reach this library's acts have".
 *
 * `Conflict\Gatekeeper` asks before a standalone is deactivated, `Notices\Presenter` before the
 * queue reporting it is printed and cleared for everybody. They have to agree by construction: a
 * looser consume gate hands the only copy of a notice to somebody who cannot act on it.
 *
 * Network-scoped wherever a network exists, because that is how far the act reaches.
 * `activate_plugins` cannot be relied on to imply the network capability: core widens it only while
 * the `menu_items` site option keeps the Plugins menu off, and a network may turn that menu on.
 *
 * @since 1.0.0
 */
trait Guards_Plugin_Capability
{
    /**
     * Whether the current user may manage plugins at the scope this library acts on.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    private static function user_may_manage_plugins(): bool
    {
        return current_user_can(is_multisite() ? 'manage_network_plugins' : 'activate_plugins');
    }
}