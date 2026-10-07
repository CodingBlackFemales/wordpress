<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Notices\Contracts;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Sub_Plugin;
/**
 * What the absorber has to say about a sub-plugin. Bind a replacement to word or keep it your own
 * way.
 *
 * The only seam in this folder, because nothing dispatches on how a notice reaches the screen: a
 * host that wants no rendering of ours takes the `all_admin_notices` callback off instead.
 *
 * @since 1.0.0
 */
interface Writer_Interface
{
    /**
     * Queue the "we deactivated the standalone for you" notice.
     *
     * Raised exactly once, so an implementation that drops it drops the only warning the site owner
     * gets — and it must survive the request that wrote it, since the resolver then redirects.
     *
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin concerned.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    public function queue_merge_notice(Sub_Plugin $sub_plugin): void;
    /**
     * Queue the "please deactivate the standalone yourself" notice.
     *
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin concerned.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    public function queue_conflict_notice(Sub_Plugin $sub_plugin): void;
    /**
     * Queue the "we left the standalone active to avoid stranding sites" notice.
     *
     * Raised in one topology only: a network-active standalone whose host is not network-activated,
     * where a network-wide deactivation would remove it from the sites the host never reached. So
     * its wording must not tell the user to deactivate the standalone.
     *
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin concerned.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    public function queue_stranding_notice(Sub_Plugin $sub_plugin): void;
    /**
     * Queue the "requirements not met" notice.
     *
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin concerned.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    public function queue_dependency_notice(Sub_Plugin $sub_plugin): void;
    /**
     * Where these notices are kept, so a host can render them itself without replacing the writer.
     *
     * An instance method on the contract because the answer depends on which writer a site runs: a
     * name read off the default class would name an option nothing writes to.
     *
     * @since 1.0.0
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return string
     */
    public function option_name(): string;
}