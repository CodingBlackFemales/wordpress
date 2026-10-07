<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber\Notices;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Notices\Contracts\Writer_Interface;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Sub_Plugin;
/**
 * What a notice says, and under which key it is kept.
 *
 * Wording only: `Store` keeps the queue, `Renderer` draws it, `Presenter` decides who consumes it.
 *
 * @since 1.0.0
 */
class Writer implements Writer_Interface
{
    /**
     * Notice types. Public because an entry is stored under `slug:type`, so reading the queue
     * yourself means matching against these.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public const TYPE_MERGE = 'merge';
    /**
     * @since 1.0.0
     *
     * @var string
     */
    public const TYPE_CONFLICT = 'conflict';
    /**
     * @since 1.0.0
     *
     * @var string
     */
    public const TYPE_DEPENDENCY = 'dependency';
    /**
     * @since 1.0.0
     *
     * @var string
     */
    public const TYPE_STRANDING = 'stranding';
    /**
     * @since 1.0.0
     *
     * @var Store
     */
    private $store;
    /**
     * @since 1.0.0
     *
     * @param Store $store Where the queue is kept.
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }
    /**
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin concerned.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    public function queue_merge_notice(Sub_Plugin $sub_plugin): void
    {
        $this->queue($sub_plugin, self::TYPE_MERGE, $sub_plugin->get_conflict_notice_message(sprintf('%s has been deactivated because it is now bundled and loaded automatically.', $sub_plugin->get_slug())));
    }
    /**
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin concerned.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    public function queue_conflict_notice(Sub_Plugin $sub_plugin): void
    {
        $this->queue($sub_plugin, self::TYPE_CONFLICT, $sub_plugin->get_conflict_notice_message(sprintf('%s is now bundled and loaded automatically. You can safely deactivate the standalone plugin.', $sub_plugin->get_slug())));
    }
    /**
     * Its own type and default because it must not carry the conflict notice's "you can safely
     * deactivate the standalone" -- here that deactivation is what would strand the sites.
     *
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin concerned.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    public function queue_stranding_notice(Sub_Plugin $sub_plugin): void
    {
        $this->queue($sub_plugin, self::TYPE_STRANDING, $sub_plugin->get_stranding_notice_message());
    }
    /**
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin concerned.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    public function queue_dependency_notice(Sub_Plugin $sub_plugin): void
    {
        $this->queue($sub_plugin, self::TYPE_DEPENDENCY, $sub_plugin->get_dependency_notice_message());
    }
    /**
     * @since 1.0.0
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return string
     */
    public function option_name(): string
    {
        return $this->store->option_name();
    }
    /**
     * Store one notice, keyed `slug:type`.
     *
     * One sub-plugin can earn a merge notice and a dependency notice in the same request; keying by
     * slug alone would silently drop one of them.
     *
     * @since 1.0.0
     *
     * @param Sub_Plugin $sub_plugin Sub-plugin concerned.
     * @param string     $type       Notice type.
     * @param string     $message    Resolved message.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return void
     */
    private function queue(Sub_Plugin $sub_plugin, string $type, string $message): void
    {
        $this->store->put($sub_plugin->get_slug() . ':' . $type, $message);
    }
}