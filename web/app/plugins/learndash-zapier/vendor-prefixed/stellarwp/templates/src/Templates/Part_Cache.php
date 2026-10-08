<?php

/**
 * A file for caching template parts.
 *
 * @since 1.0.0
 *
 * @package StellarWP\Templates
 */
namespace LearnDash\Zapier\StellarWP\Templates;

/**
 * A class to handle caching template parts.
 *
 * @since 1.0.0
 *
 * @package \StellarWP\Templates
 */
class Part_Cache
{
    /**
     * Which template in the views directory is being cached (relative path).
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected string $template;
    /**
     * Expiration time for the cached fragment.
     *
     * @since 1.0.0
     *
     * @var int
     */
    protected int $expiration;
    /**
     * WordPress hook to expire on.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected string $expiration_trigger;
    /**
     * The cached rendered template.
     *
     * @since 1.0.0
     *
     * @var string|bool|null
     */
    protected $html;
    /**
     * The key to save the cache with.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected string $key;
    /**
     * The class constructor.
     *
     * @since 1.0.0
     *
     * @param string $template           Which template in the views directory is being cached (relative path).
     * @param string $id                 A unique identifier for this fragment.
     * @param int    $expiration         Expiration time for the cached fragment.
     * @param string $expiration_trigger WordPress hook to expire on.
     */
    public function __construct($template, $id, $expiration, $expiration_trigger)
    {
        $this->template = $template;
        $this->key = $template . '_' . $id;
        $this->expiration = $expiration;
        $this->expiration_trigger = $expiration_trigger;
        $this->add_hooks();
    }
    /**
     * Hook in to show cached content and bypass queries where needed
     */
    public function add_hooks(): void
    {
        $hook_prefix = Config::get_hook_prefix();
        // Set the cached html in transients after the template part is included.
        add_filter("stellarwp/templates/{$hook_prefix}/get_template_part_content", [$this, 'set'], 10, 2);
        // Get the cached html right before the setup_view runs so it's available for bypassing any view logic.
        add_action("stellarwp/templates/{$hook_prefix}/before_view", [$this, 'get'], 9, 1);
        // When the specified template part is included, show the cached html instead.
        add_filter("stellarwp/templates/get_template_part_path_{$this->template}", [$this, 'display']);
    }
    /**
     * Checks if there is a cached html fragment in the transients, if it's there,
     * don't include the requested file path. If not, just return the file path like normal
     *
     * @param string $path File path to the month view template part.
     *
     * @return string
     */
    public function display($path): string
    {
        if ($this->html !== false) {
            echo esc_html($this->html);
            return '';
        }
        return (string) $path;
    }
    /**
     * Set cached html in transients.
     *
     * @param string $html     The rendered HTML to cache.
     * @param string $template The template file path.
     *
     * @return string
     */
    public function set($html, $template): string
    {
        if ($template == $this->template) {
            set_transient("{$this->key}|{$this->expiration_trigger}", $html, $this->expiration);
        }
        return $html;
    }
    /**
     * Retrieve the cached html from transients, set class property.
     *
     * @return void
     */
    public function get()
    {
        if (!isset($this->html)) {
            $this->html = get_transient("{$this->key}|{$this->expiration_trigger}");
        }
        echo esc_html($this->html);
    }
}