<?php

/**
 * @package \Nexcess\PluginAbsorber
 */
declare (strict_types=1);
namespace StellarWP\Learndash\Nexcess\PluginAbsorber;

use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
/**
 * One registered sub-plugin: its configuration, and every answer it can give without a
 * container-bound collaborator.
 *
 * The line is dependency direction, not "configuration alone": resolving a Checker_Interface to ask
 * whether the standalone is active would put a container read in front of Absorber::register(),
 * which performs none so the container may arrive any time before boot.
 *
 * @since 1.0.0
 *
 * @phpstan-type Sub_Plugin_Config array{
 *     slug: string,
 *     bundled_plugin_file: string,
 *     plugin_loaded_constant: string,
 *     enabled?: bool|callable,
 *     conflict_policy?: string|callable,
 *     standalone_plugin_basename?: string,
 *     dependency_check?: callable,
 *     conflict_notice_message?: callable,
 *     dependency_notice_message?: callable,
 *     activation_callback?: callable
 * }
 */
class Sub_Plugin
{
    /**
     * @since 1.0.0
     *
     * @var string[]
     */
    private const REQUIRED_KEYS = ['slug', 'bundled_plugin_file', 'plugin_loaded_constant'];
    /**
     * @since 1.0.0
     *
     * @var string[]
     */
    private const CALLABLE_KEYS = ['dependency_check', 'activation_callback'];
    /**
     * @since 1.0.0
     *
     * @var string[]
     */
    private const STRING_KEYS = ['standalone_plugin_basename'];
    /**
     * Keys taking a string, or something to call for one.
     *
     * @since 1.0.0
     *
     * @var string[]
     */
    private const DEFERRABLE_KEYS = ['conflict_policy'];
    /**
     * Keys carrying user-facing text, which hold something to call for it, never the text.
     *
     * @since 1.0.0
     *
     * @var string[]
     */
    private const MESSAGE_KEYS = ['conflict_notice_message', 'dependency_notice_message'];
    /**
     * Validated configuration, keyed as the constructor accepts it.
     *
     * @since 1.0.0
     *
     * @var Sub_Plugin_Config
     */
    private $config;
    /**
     * @since 1.0.0
     *
     * @param array<string,mixed> $config Sub-plugin configuration.
     *
     * @throws Config_Exception When a required key is missing, empty or not a string, a callable-only
     *                          key cannot be called, or a message key holds a string.
     */
    public function __construct(array $config)
    {
        foreach (self::REQUIRED_KEYS as $required) {
            if (!isset($config[$required])) {
                throw new Config_Exception(sprintf('Sub-plugin config %1$s: required key "%2$s" is missing.', $this->describe_config_entry($config), $required));
            }
            // is_string(), not truthiness: an array passes truthiness, then casts to the string
            // "Array", which every sub-plugin making the mistake would share as its key.
            if (!is_string($config[$required]) || $config[$required] === '') {
                throw new Config_Exception(sprintf('Sub-plugin config %1$s: key "%2$s" must be a non-empty string, %3$s given.', $this->describe_config_entry($config), $required, $this->describe_value($config[$required])));
            }
        }
        // Rejected at registration, not at read time, where "not configured" and "configured but
        // uncallable" collapse and a typo'd dependency_check would report dependencies met.
        foreach (self::CALLABLE_KEYS as $key) {
            if (isset($config[$key]) && !is_callable($config[$key])) {
                throw new Config_Exception(sprintf('Sub-plugin config %1$s: key "%2$s" must be callable, %3$s given.', $this->describe_config_entry($config), $key, $this->describe_value($config[$key])));
            }
        }
        // A basename names a file already on disk, so there is nothing here to defer.
        foreach (self::STRING_KEYS as $key) {
            if (isset($config[$key]) && !is_string($config[$key])) {
                throw new Config_Exception(sprintf('Sub-plugin config %1$s: key "%2$s" must be a string, %3$s given.', $this->describe_config_entry($config), $key, $this->describe_value($config[$key])));
            }
        }
        // Either form: a policy is usually a constant with nothing to defer, and is never text.
        foreach (self::DEFERRABLE_KEYS as $key) {
            if (!isset($config[$key])) {
                continue;
            }
            if (!is_string($config[$key]) && !is_callable($config[$key])) {
                throw new Config_Exception(sprintf('Sub-plugin config %1$s: key "%2$s" must be a policy string or a non-string' . ' callable, %3$s given.', $this->describe_config_entry($config), $key, $this->describe_value($config[$key])));
            }
        }
        // Any string is refused, not just a string callable: a config array is built before init, so
        // text here can only have been translated too early, and the value cannot say it was not.
        foreach (self::MESSAGE_KEYS as $key) {
            if (!isset($config[$key])) {
                continue;
            }
            if (is_string($config[$key]) || !is_callable($config[$key])) {
                throw new Config_Exception(sprintf('Sub-plugin config %1$s: key "%2$s" must be a non-string callable, %3$s' . ' given. Wrap the text in a callable, e.g.' . " static function () { return __( 'text' ); }, so that it is" . ' translated after init rather than while the config array is built.', $this->describe_config_entry($config), $key, $this->describe_value($config[$key])));
            }
        }
        // `enabled` is the one key with no type behind it: it is read as a bool when it is not
        // callable, so an array or an object there counts as enabled.
        /** @var Sub_Plugin_Config $config */
        $this->config = $config;
    }
    /**
     * @since 1.0.0
     *
     * @return string
     */
    public function get_slug(): string
    {
        return (string) $this->config['slug'];
    }
    /**
     * Absolute path to the bundled plugin's main file — the file we require_once.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_bundled_plugin_file(): string
    {
        return (string) $this->config['bundled_plugin_file'];
    }
    /**
     * Constant both the bundled copy and the standalone define when they load.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_plugin_loaded_constant(): string
    {
        return (string) $this->config['plugin_loaded_constant'];
    }
    /**
     * The configured policy, after the filter has had the final say.
     *
     * The result is deliberately not validated here — rejecting a filter's return would hide the
     * override rather than report it. Callers that dispatch on it use Conflict_Policy::is_valid().
     *
     * @since 1.0.0
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return string
     */
    public function get_conflict_policy(): string
    {
        $policy = $this->config['conflict_policy'] ?? Conflict_Policy::default();
        $policy = $this->as_string($this->resolve_deferred($policy));
        /**
         * Filters the policy applied when a standalone copy of a sub-plugin is found active.
         *
         * The dynamic portion, `$hook_prefix`, is the prefix given to Config::set_hook_prefix().
         *
         * Fires last, after the configured value and its default, so a host can decide per request. A
         * return that is not a Conflict_Policy constant is not consent to deactivate.
         *
         * @since 1.0.0
         *
         * @param string     $policy     One of the Conflict_Policy constants.
         * @param Sub_Plugin $sub_plugin The sub-plugin the policy applies to.
         */
        $policy = apply_filters(Config::get_hook_name('conflict_policy'), $policy, $this);
        // An uncastable filter return becomes '', which matches no policy, so callers stay put.
        return $this->as_string($policy);
    }
    /**
     * @since 1.0.0
     *
     * @return bool
     */
    public function is_enabled(): bool
    {
        $enabled = $this->config['enabled'] ?? true;
        // No string form here, so a plain function name is called too, deliberately, on every read.
        return (bool) (is_callable($enabled) ? $enabled($this) : $enabled);
    }
    /**
     * True when the plugin's code is already present, from either copy. The fatal guard.
     *
     * Only sound when the constant is defined at file scope: a standalone defining it from a hook
     * past the load pass has not defined it yet when this is asked.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function is_already_loaded(): bool
    {
        return defined($this->get_plugin_loaded_constant());
    }
    /**
     * @since 1.0.0
     *
     * @return bool
     */
    public function has_standalone_plugin(): bool
    {
        return !empty($this->config['standalone_plugin_basename']);
    }
    /**
     * @since 1.0.0
     *
     * @return string
     */
    public function get_standalone_plugin_basename(): string
    {
        return (string) ($this->config['standalone_plugin_basename'] ?? '');
    }
    /**
     * @since 1.0.0
     *
     * @return bool
     */
    public function are_dependencies_met(): bool
    {
        $check = $this->config['dependency_check'] ?? null;
        if ($check === null) {
            return true;
        }
        return (bool) $check($this);
    }
    /**
     * Shown when the standalone is auto-deactivated, and when the user tries to re-activate it.
     *
     * The fallback is a parameter because those two contexts want different wording.
     *
     * @since 1.0.0
     *
     * @param string $default Used when nothing is configured.
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return string
     */
    public function get_conflict_notice_message(string $default = ''): string
    {
        $configured = $this->config['conflict_notice_message'] ?? '';
        $message = $this->as_string($this->resolve_deferred($configured));
        if ($message === '') {
            $message = $default;
        }
        /**
         * Filters the notice shown when a standalone copy is deactivated, and when the user tries
         * to activate it again.
         *
         * The dynamic portion, `$hook_prefix`, is the prefix given to Config::set_hook_prefix().
         *
         * Fires last, when the message is asked for rather than at registration, so the textdomain
         * is loaded and the text can be translated here.
         *
         * @since 1.0.0
         *
         * @param string     $message    The configured message, or the caller's fallback.
         * @param Sub_Plugin $sub_plugin The sub-plugin in conflict.
         */
        $message = apply_filters(Config::get_hook_name('conflict_notice_message'), $message, $this);
        return $this->as_string($message);
    }
    /**
     * Shown when a dependency_check fails, falling back to a generic, untranslated sentence.
     *
     * @since 1.0.0
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return string
     */
    public function get_dependency_notice_message(): string
    {
        $configured = $this->config['dependency_notice_message'] ?? '';
        $message = $this->as_string($this->resolve_deferred($configured));
        if ($message === '') {
            $message = sprintf('%s could not be loaded because its requirements are not met.', $this->get_slug());
        }
        /**
         * Filters the notice shown when a sub-plugin's dependency_check fails.
         *
         * The dynamic portion, `$hook_prefix`, is the prefix given to Config::set_hook_prefix().
         *
         * Fires when the message is asked for, so the default can be translated here.
         *
         * @since 1.0.0
         *
         * @param string     $message    The configured message, or a generic untranslated one.
         * @param Sub_Plugin $sub_plugin The sub-plugin whose dependencies are unmet.
         */
        $message = apply_filters(Config::get_hook_name('dependency_notice_message'), $message, $this);
        return $this->as_string($message);
    }
    /**
     * Shown when a network-active standalone is left active to avoid stranding sites.
     *
     * Deactivating it network-wide would take it from the sites a host that is not network-activated
     * never reaches. There is no config key -- the filter below is the seam for rewording it.
     *
     * @since 1.0.0
     *
     * @throws Config_Exception When no hook prefix has been set.
     *
     * @return string
     */
    public function get_stranding_notice_message(): string
    {
        $message = sprintf('%1$s was left active. Its bundled copy loads only where the host plugin is active, and ' . 'the host plugin is not network-activated, so deactivating %1$s across the network would ' . 'leave the sites without the host plugin with no copy of it at all. To finish absorbing ' . "it, either network-activate the host plugin, or deactivate %1\$s yourself from the " . "Network Admin's Plugins screen.", $this->get_slug());
        /**
         * Filters the notice shown when a network-active standalone is left active.
         *
         * The dynamic portion, `$hook_prefix`, is the prefix given to Config::set_hook_prefix().
         *
         * Fires when the message is asked for, so the default can be translated here.
         *
         * @since 1.0.0
         *
         * @param string     $message    The generic, untranslated default.
         * @param Sub_Plugin $sub_plugin The sub-plugin left active.
         */
        $message = apply_filters(Config::get_hook_name('stranding_notice_message'), $message, $this);
        return $this->as_string($message);
    }
    /**
     * @since 1.0.0
     *
     * @return callable|null
     */
    public function get_activation_callback(): ?callable
    {
        $callback = $this->config['activation_callback'] ?? null;
        return is_callable($callback) ? $callback : null;
    }
    /**
     * Take a configured value as it stands, or call it for one.
     *
     * A string is always the value itself, however real a function of that name happens to be:
     * `date`, `flush` and `key` are all functions and all plausible configured values. Every other
     * callable form says "call me", which is what lets a host defer __() until the text is wanted.
     *
     * @since 1.0.0
     *
     * @param mixed $value Configured value, proved a string or a callable at registration.
     *
     * @return mixed
     */
    private function resolve_deferred($value)
    {
        // Proved by the constructor already, but kept: calling something uncallable is a fatal.
        if (is_string($value) || !is_callable($value)) {
            return $value;
        }
        return $value($this);
    }
    /**
     * Reduce whatever a filter or a configured callable returned to a string.
     *
     * Only the cast is shared -- each filter stays applied at the method that owns it, so every hook
     * keeps a documented call site. An array or an object would fatal on the cast, so it reads as
     * nothing come back, and what nothing means is each caller's to say.
     *
     * @since 1.0.0
     *
     * @param mixed $value Whatever came back from apply_filters() or from a configured callable.
     *
     * @return string
     */
    private function as_string($value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
    /**
     * Name the entry a rejection is about, for a host registering several from one loop.
     *
     * A host calling Absorber::register() in a loop gets the same file and line in the stack trace
     * whichever entry was rejected. Both are read defensively: either may be the key at fault.
     *
     * @since 1.0.0
     *
     * @param array<string,mixed> $config Configuration exactly as the host supplied it.
     *
     * @return string
     */
    private function describe_config_entry(array $config): string
    {
        foreach (['slug', 'bundled_plugin_file'] as $identifier) {
            $value = $config[$identifier] ?? null;
            if (is_string($value) && $value !== '') {
                return sprintf('for "%s"', $value);
            }
        }
        return 'with no usable slug or bundled_plugin_file';
    }
    /**
     * Name the type a key was given, so that a stray value can be found rather than hunted for.
     *
     * An object reports its class, since "object given" leaves a Closure and a WP_Error looking the
     * same, and the empty string is named as one, since "string given" under a key that must be a
     * non-empty string says nothing.
     *
     * @since 1.0.0
     *
     * @param mixed $value Value the host put under the key.
     *
     * @return string
     */
    private function describe_value($value): string
    {
        if (is_object($value)) {
            return get_class($value);
        }
        if ($value === '') {
            return 'empty string';
        }
        return gettype($value);
    }
}