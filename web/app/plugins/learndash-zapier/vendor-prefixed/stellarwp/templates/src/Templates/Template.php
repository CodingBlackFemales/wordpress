<?php

/**
 * Handles all functionality for templates.
 *
 * @since 1.0.0
 *
 * @package StellarWP\Templates
 */
namespace LearnDash\Zapier\StellarWP\Templates;

use InvalidArgumentException;
use LearnDash\Zapier\StellarWP\Arrays\Arr;
use LearnDash\Zapier\StellarWP\Templates\Utils\Conditions;
use LearnDash\Zapier\StellarWP\Templates\Utils\Paths;
use LearnDash\Zapier\StellarWP\Templates\Utils\Strings;
/**
 * Handles all functionality for templates.
 *
 * @since 1.0.0
 *
 * @package \StellarWP\Templates
 */
class Template
{
    /**
     * The origin class for the plugin where the template lives
     *
     * @since 1.0.0
     *
     * @var object
     */
    public $origin;
    /**
     * The folders into which we will look for the template.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected array $folder = [];
    /**
     * The local context for templates, mutable on every self::template() call
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected array $context = [];
    /**
     * The global context for this instance of templates
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected array $global = [];
    /**
     * Used for finding templates for public templates on themes inside of a folder.
     *
     * @since 1.0.0
     *
     * @var string[]
     */
    protected array $template_origin_base_folder = ['src', 'views'];
    /**
     * Allow changing if class will extract data from the local context
     *
     * @since 1.0.0
     *
     * @var boolean
     */
    protected bool $template_context_extract = false;
    /**
     * Current template hook name.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected string $template_current_hook_name = '';
    /**
     * Base template for where to look for template
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected array $template_base_path = [];
    /**
     * Should we use a lookup into the list of folders to try to find the file
     *
     * @since 1.0.0
     *
     * @var  bool
     */
    protected bool $template_folder_lookup = false;
    /**
     * Create a class variable for the include path, to avoid conflicting with extract.
     *
     * @since 1.0.0
     *
     * @var  string
     */
    protected string $template_current_file_path = '';
    /**
     * A map of aliases to add a rewritten version of the paths to the template lists.
     * The map has format `original => alias`.
     *
     * @since 1.0.0
     *
     * @var array<string,string>
     */
    protected array $aliases = [];
    /**
     * Configures the class origin plugin path
     *
     * @since 1.0.0
     *
     * @param object|string $origin The base origin for the templates.
     *
     * @throws InvalidArgumentException When an origin class is not valid.
     *
     * @return static
     */
    public function set_template_origin($origin = null): self
    {
        if (empty($origin)) {
            $origin = $this->origin;
        }
        if (is_string($origin)) {
            // Origin needs to be a class with a `instance` method.
            if (class_exists($origin) && method_exists($origin, 'instance')) {
                $origin = call_user_func([$origin, 'instance']);
                // @phpstan-ignore-line If we get here, we know the object is an object.
            }
        }
        if (empty($origin->plugin_path) && empty($origin->pluginPath) && !is_dir($origin)) {
            throw new InvalidArgumentException('Invalid Origin Class for Template Instance');
        }
        if (is_string($origin)) {
            $this->template_base_path = array_filter((array) explode('/', untrailingslashit($origin)));
        } else {
            $this->origin = $origin;
            $this->template_base_path = [untrailingslashit(Config::get_path())];
        }
        return $this;
    }
    /**
     * Configures the class with the base folder in relation to the Origin
     *
     * @since 1.0.0
     *
     * @param array|string $folder Which folder we are going to look for templates in.
     *
     * @return self
     */
    public function set_template_folder($folder = null): self
    {
        // Allows configuring a already set class.
        if (!isset($folder)) {
            $folder = $this->folder;
        }
        // If Folder is String make it an Array.
        if (is_string($folder)) {
            $folder = explode('/', $folder);
        }
        // Cast as Array and save.
        $this->folder = Arr::wrap($folder);
        return $this;
    }
    /**
     * Returns the array for which folder this template instance is looking into.
     *
     * @since 1.0.0
     *
     * @return array Current folder we are looking for templates.
     */
    public function get_template_folder()
    {
        return $this->folder;
    }
    /**
     * Configures the class with the base folder in relation to the Origin
     *
     * @since 1.0.0
     *
     * @param mixed $value Should we look for template files in the list of folders.
     *
     * @return self
     */
    public function set_template_folder_lookup($value = true): self
    {
        $this->template_folder_lookup = Conditions::is_truthy($value);
        return $this;
    }
    /**
     * Gets in this instance of the template engine whether we are looking public folders like themes.
     *
     * @since 1.0.0
     *
     * @return bool Whether we are looking into theme folders.
     */
    public function get_template_folder_lookup()
    {
        return $this->template_folder_lookup;
    }
    /**
     * Configures the class global context
     *
     * @since 1.0.0
     *
     * @param array $context The default global context.
     *
     * @return self
     */
    public function add_template_globals($context = [])
    {
        // Cast as Array merge and save.
        $this->global = wp_parse_args(Arr::wrap($context), $this->global);
        return $this;
    }
    /**
     * Configures if the class will extract context for template
     *
     * @since 1.0.0
     *
     * @param bool $value Should we extract context for templates.
     *
     * @return self
     */
    public function set_template_context_extract($value = false)
    {
        // Cast as bool and save.
        $this->template_context_extract = Conditions::is_truthy($value);
        return $this;
    }
    /**
     * Set the current hook name for the template include.
     *
     * @since 1.0.0
     *
     * @param string $value Which value will be saved as the current hook name.
     *
     * @return self  Allow daisy-chaining.
     */
    public function set_template_current_hook_name($value)
    {
        $this->template_current_hook_name = (string) $value;
        return $this;
    }
    /**
     * Gets the hook name for the current template setup.
     *
     * @since 1.0.0
     *
     * @return string Hook name currently set on the class.
     */
    public function get_template_current_hook_name()
    {
        return $this->template_current_hook_name;
    }
    /**
     * Sets an Index inside of the global or local context.
     * Final to prevent extending the class when the `get` already exists on the child class.
     *
     * @see    Arr::set()
     *
     * @since  1.0.0
     *
     * @param array|string $index    Specify each nested index in order (e.g. [ 'lvl1', 'lvl2' ]).
     * @param mixed        $default  Default value if the search finds nothing.
     * @param boolean      $is_local Use the Local or Global context.
     *
     * @return mixed The value of the specified index or the default if not found.
     */
    final public function get($index, $default = null, $is_local = true)
    {
        // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound
        $hook_prefix = Config::get_hook_prefix();
        $context = $this->get_global_values();
        if (true === $is_local) {
            $context = $this->get_local_values();
        }
        /**
         * Allows filtering the the getting of Context variables, also short circuiting
         * Following the same structure as WP Core
         *
         * @since 1.0.0
         *
         * @param mixed        $value    The value that will be filtered.
         * @param array|string $index    Specify each nested index in order (e.g. [ 'lvl1', 'lvl2' ]).
         * @param mixed        $default  Default value if the search finds nothing.
         * @param boolean      $is_local Use the Local or Global context.
         * @param self         $template Current instance of the self.
         */
        $value = apply_filters("stellarwp/templates/{$hook_prefix}/template_context_get", null, $index, $default, $is_local, $this);
        if (null !== $value) {
            return $value;
        }
        return Arr::get($context, $index, $default);
    }
    /**
     * Sets a Index inside of the global or local context
     * Final to prevent extending the class when the `set` already exists on the child class
     *
     * @see    Arr::set
     *
     * @since  1.0.0
     *
     * @param string|array $index       To set a key nested multiple levels deep pass an array specifying each key in order as a value (e.g. [ 'lvl1', 'lvl2', 'lvl3' ]).
     * @param mixed        $value       The value.
     * @param boolean      $is_local    Use the Local or Global context.
     *
     * @return array Full array with the key set to the specified value.
     */
    final public function set($index, $value = null, $is_local = true)
    {
        if (true === $is_local) {
            $this->context = Arr::set($this->context, $index, $value);
            return $this->context;
        }
        $this->global = Arr::set($this->global, $index, $value);
        return $this->global;
    }
    /**
     * Merges local and global context, and saves it locally.
     *
     * @since 1.0.0
     *
     * @param array|null $context Local Context array of data.
     * @param string     $file    Complete path to include the PHP File.
     * @param array      $name    Template name.
     *
     * @return array
     */
    public function merge_context($context = [], $file = null, $name = null)
    {
        $hook_prefix = Config::get_hook_prefix();
        // Allow for simple null usage as well as array() for nothing.
        if (is_null($context)) {
            $context = [];
        }
        // Applies new local context on top of Global + Previous local.
        $context = wp_parse_args(Arr::wrap($context), $this->get_values());
        /**
         * Allows filtering the Local context.
         *
         * @since 1.0.0
         *
         * @param array  $context  Local Context array of data.
         * @param string $file     Complete path to include the PHP File.
         * @param array  $name     Template name.
         * @param self   $template Current instance of the self.
         */
        $this->context = apply_filters("stellarwp/templates/{$hook_prefix}/template_context", $context, $file, $name, $this);
        $hook_name = $this->get_template_current_hook_name();
        /**
         * Allows filtering the Local context specifically to the template with the hook name passed to the method.
         *
         * @since 1.0.0
         *
         * @param array  $context  Local Context array of data.
         * @param string $file     Complete path to include the PHP File.
         * @param array  $name     Template name.
         * @param self   $template Current instance of the self.
         */
        $this->context = apply_filters("stellarwp/templates/{$hook_prefix}/template_context:{$hook_name}", $this->context, $file, $name, $this);
        return $this->context;
    }
    /**
     * Fetches which base folder we look for templates in the origin plugin.
     *
     * @since 1.0.0
     *
     * @return array The base folders we look for templates in the origin plugin.
     */
    public function get_template_origin_base_folder()
    {
        $hook_prefix = Config::get_hook_prefix();
        /**
         * Allows filtering of the base path for templates.
         *
         * @since 1.0.0
         *
         * @param array $namespace Which is the base folder we will look for files in the plugin.
         * @param self  $template  Current instance of the self.
         */
        return apply_filters("stellarwp/templates/{$hook_prefix}/template_origin_base_folder", $this->template_origin_base_folder, $this);
    }
    /**
     * Tries to locate the correct file we want to load based on the Template class
     * configuration and it's list of folders
     *
     * @since 1.0.0
     *
     * @param mixed $name File name we are looking for.
     *
     * @return string|false
     */
    public function get_template_file($name)
    {
        $hook_prefix = Config::get_hook_prefix();
        // If name is String make it an Array.
        if (is_string($name)) {
            $name = (array) explode('/', $name);
        }
        $folders = $this->get_template_path_list();
        $found_file = false;
        $namespace = false;
        foreach ($folders as $folder) {
            if (empty($folder['path'])) {
                continue;
            }
            // Build the File Path.
            $file = Paths::merge($folder['path'], $name);
            // Append the Extension to the file path.
            $file .= '.php';
            // Skip non-existent files.
            if (file_exists($file)) {
                $found_file = $file;
                $namespace = !empty($folder['namespace']) ? $folder['namespace'] : false;
                break;
            }
        }
        if ($this->get_template_folder_lookup()) {
            $theme_folders = $this->get_template_theme_path_list($namespace);
            foreach ($theme_folders as $folder) {
                if (empty($folder['path'])) {
                    continue;
                }
                // Build the File Path.
                $file = implode(DIRECTORY_SEPARATOR, array_merge(Arr::wrap($folder['path']), $name));
                // Append the Extension to the file path.
                $file .= '.php';
                // Skip non-existent files.
                if (file_exists($file)) {
                    $found_file = $file;
                    break;
                }
            }
        }
        if ($found_file) {
            /**
             * A more Specific Filter that will include the template name
             *
             * @since 1.0.0
             * @since 1.0.0
             *
             * @param string $file     Complete path to include the PHP File
             * @param array  $name     Template name
             * @param self   $template Current instance of the self.
             */
            return apply_filters("stellarwp/templates/{$hook_prefix}/template_file", $found_file, $name, $this);
        }
        // Couldn't find a template on the Stack.
        return false;
    }
    /**
     * Runs the entry point hooks and filters.
     *
     * @param string  $entry_point_name The name of the entry point.
     * @param boolean $echo             If we should also print the entry point content.
     *
     * @return null|string `null` if an entry point is disabled or the entry point HTML.
     */
    public function do_entry_point($entry_point_name, $echo = true)
    {
        // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.echoFound
        $hook_prefix = Config::get_hook_prefix();
        $hook_name = $this->get_template_current_hook_name();
        /**
         * Filter if the entry points are enabled.
         *
         * @since 1.0.0
         *
         * @param boolean $is_enabled       Is entry_point enabled.
         * @param string  $hook_name        For which template include this entry point belongs.
         * @param string  $entry_point_name Which entry point specifically we are triggering.
         * @param self    $template         Current instance of the template class doing this entry point.
         */
        $is_entry_point_enabled = apply_filters("stellarwp/templates/{$hook_prefix}/template_entry_point_is_enabled", true, $hook_name, $entry_point_name, $this);
        if (!$is_entry_point_enabled) {
            return null;
        }
        ob_start();
        if (has_action("stellarwp/templates/{$hook_prefix}/template_entry_point:{$hook_name}")) {
            /**
             * Generic entry point action for the current template.
             *
             * @since 1.0.0
             *
             * @param string $hook_name        For which template include this entry point belongs.
             * @param string $entry_point_name Which entry point specifically we are triggering.
             * @param self   $template         Current instance of the template class doing this entry point.
             */
            do_action("stellarwp/templates/{$hook_prefix}/template_entry_point:{$hook_name}", $hook_name, $entry_point_name, $this);
        }
        if (has_action("stellarwp/templates/{$hook_prefix}/template_entry_point:{$hook_name}:{$entry_point_name}")) {
            /**
             * Specific named entry point action called.
             *
             * @since 1.0.0
             *
             * @param string $hook_name        For which template include this entry point belongs.
             * @param string $entry_point_name Which entry point specifically we are triggering.
             * @param self   $template         Current instance of the template class doing this entry point.
             */
            do_action("stellarwp/templates/{$hook_prefix}/template_entry_point:{$hook_name}:{$entry_point_name}", $hook_name, $entry_point_name, $this);
        }
        $html = ob_get_clean();
        if (has_filter("stellarwp/templates/{$hook_prefix}/template_entry_point_html:{$hook_name}")) {
            /**
             * Generic entry point action for the current template.
             *
             * @since 1.0.0
             *
             * @param string $html             HTML returned and/or echoed for this for this entry point.
             * @param string $hook_name        For which template include this entry point belongs.
             * @param string $entry_point_name Which entry point specifically we are triggering.
             * @param self   $template         Current instance of the template class doing this entry point.
             */
            $html = apply_filters("stellarwp/templates/{$hook_prefix}/template_entry_point_html:{$hook_name}", $html, $hook_name, $entry_point_name, $this);
        }
        if (has_filter("stellarwp/templates/{$hook_prefix}/template_entry_point_html:{$hook_name}:{$entry_point_name}")) {
            /**
             * Specific named entry point action called.
             *
             * @since 1.0.0
             *
             * @param string $html             HTML returned and/or echoed for this for this entry point.
             * @param string $hook_name        For which template include this entry point belongs.
             * @param string $entry_point_name Which entry point specifically we are triggering.
             * @param self   $template         Current instance of the template class doing this entry point.
             */
            $html = apply_filters("stellarwp/templates/{$hook_prefix}/template_entry_point_html:{$hook_name}:{$entry_point_name}", $html, $hook_name, $entry_point_name, $this);
        }
        if ($echo) {
            echo $html;
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,StellarWP.XSS.EscapeOutput.OutputNotEscaped
        }
        return $html;
    }
    /**
     * A very simple method to include a Template, allowing filtering and additions using hooks.
     *
     * @since 1.0.0
     *
     * @param string|array $name    Which file we are talking about including.
     *                              If an array, each item will add a directory separator to get to the single template.
     * @param array        $context Any context data you need to expose to this file.
     * @param boolean      $echo    If we should also print the Template.
     *
     * @return string|false Either the final content HTML or `false` if no template could be found.
     */
    public function template($name, $context = [], $echo = true)
    {
        // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.echoFound,Generic.NamingConventions.ConstructorName.OldStyle
        static $file_exists = [];
        static $files = [];
        static $template_names = [];
        $hook_prefix = Config::get_hook_prefix();
        /**
         * Allow users to disable templates before rendering it by returning empty string.
         *
         * @since 1.0.0
         *
         * @param string|null $content Whether to continue displaying the template or not.
         * @param array       $name    Template name.
         * @param array       $context Any context data you need to expose to this file.
         * @param boolean     $echo    If we should also print the Template.
         */
        $done = apply_filters("stellarwp/templates/{$hook_prefix}/template_done", null, $name, $context, $echo);
        if (null !== $done) {
            return false;
        }
        // Key we'll use for in-memory caching of expensive operations.
        $cache_name_key = is_array($name) ? implode('/', $name) : $name;
        // Cache template name massaging so we don't have to repeat these actions.
        if (!isset($template_names[$cache_name_key])) {
            // If name is String make it an Array.
            if (is_string($name)) {
                $name = (array) explode('/', $name);
            }
            // Clean this Variable.
            $name = array_map('sanitize_title_with_dashes', $name);
            $template_names[$cache_name_key] = $name;
        }
        // Cache file location and existence.
        if (!isset($file_exists[$cache_name_key]) || !isset($files[$cache_name_key])) {
            // Check if the file exists.
            $file = $this->get_template_file($name);
            $files[$cache_name_key] = $this->get_template_file($name);
            // Check if it's a valid variable.
            if (!$file) {
                $file_exists[$cache_name_key] = false;
                return $file_exists[$cache_name_key];
            }
            // Before we load the file we check if it exists.
            if (!file_exists($file)) {
                $file_exists[$cache_name_key] = false;
                return $file_exists[$cache_name_key];
            }
            $file_exists[$cache_name_key] = true;
        }
        // If the file doesn't exist, bail.
        if (!$file_exists[$cache_name_key]) {
            return false;
        }
        // Use filename stored in cache.
        $file = $files[$cache_name_key];
        $name = $template_names[$cache_name_key];
        $origin_folder_appendix = array_diff($this->folder, $this->template_origin_base_folder);
        $origin_namespace = $this->template_get_origin_namespace($file);
        if ($origin_namespace) {
            $legacy_namespace = array_merge(Arr::wrap($origin_namespace), $name);
            $namespace = array_merge(Arr::wrap($origin_namespace), $origin_folder_appendix, $name);
        } else {
            $legacy_namespace = $name;
            $namespace = array_merge($origin_folder_appendix, $legacy_namespace);
        }
        // Setup the Hook name.
        $legacy_hook_name = implode('/', $legacy_namespace);
        $hook_name = implode('/', $namespace);
        $prev_hook_name = $this->get_template_current_hook_name();
        // Store the current hook name for the purposes of entry-points.
        $this->set_template_current_hook_name($hook_name);
        /**
         * Allow users to filter the HTML before rendering
         *
         * @since 1.0.0
         *
         * @param string|null $html     The initial HTML
         * @param string      $file     Complete path to include the PHP File
         * @param array       $name     Template name
         * @param self        $template Current instance of the self.
         */
        $pre_html = apply_filters("stellarwp/templates/{$hook_prefix}/template_pre_html", null, $file, $name, $this);
        /**
         * Allow users to filter the HTML by the name before rendering
         *
         * E.g.:
         *    `stellarwp/templates/{$hook_prefix}/template_pre_html:events/blocks/parts/details`
         *    `stellarwp/templates/{$hook_prefix}/template_pre_html:events/embed`
         *    `stellarwp/templates/{$hook_prefix}/template_pre_html:tickets/login-to-purchase`
         *
         * @since 1.0.0
         *
         * @param string|null $html     The initial HTML
         * @param string      $file     Complete path to include the PHP File
         * @param array       $name     Template name
         * @param self        $template Current instance of the self.
         */
        $pre_html = apply_filters("stellarwp/templates/{$hook_prefix}template_pre_html:{$hook_name}", $pre_html, $file, $name, $this);
        if (null !== $pre_html) {
            return $pre_html;
        }
        // Merges the local data passed to template to the global scope.
        $this->merge_context($context, $file, $name);
        $before_include_html = $this->actions_before_template($file, $name, $hook_name);
        $before_include_html = $this->filter_template_before_include_html($before_include_html, $file, $name, $hook_name);
        $include_html = $this->template_safe_include($file);
        $include_html = $this->filter_template_include_html($include_html, $file, $name, $hook_name);
        $after_include_html = $this->actions_after_template($file, $name, $hook_name);
        $after_include_html = $this->filter_template_after_include_html($after_include_html, $file, $name, $hook_name);
        // Only fetch the contents after the action.
        $html = $before_include_html . $include_html . $after_include_html;
        $html = $this->filter_template_html($html, $file, $name, $hook_name);
        // Tries to hook container entry points in the HTML.
        $html = $this->template_hook_container_entry_points($html);
        if ($echo) {
            echo $html;
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,StellarWP.XSS.EscapeOutput.OutputNotEscaped
        }
        // Revert the current hook name.
        $this->set_template_current_hook_name($prev_hook_name);
        return $html;
    }
    /**
     * Based on a path it determines what is the namespace that should be used.
     *
     * @since 1.0.0
     *
     * @param string $path Which file we are going to load.
     *
     * @return string|false The found namespace for that path or false.
     */
    public function template_get_origin_namespace($path)
    {
        $hook_prefix = Config::get_hook_prefix();
        $matching_namespace = false;
        /**
         * Allows more namespaces to be added based on the path of the file we are loading.
         *
         * @since 1.0.0
         *
         * @param array  $namespace_map Indexed array containing the namespace as the key and path to `strpos`.
         * @param string $path          Path we will do the `strpos` to validate a given namespace.
         * @param self   $template      Current instance of the template class.
         */
        $namespace_map = (array) apply_filters("stellarwp/templates/{$hook_prefix}/template_origin_namespace_map", [], $path, $this);
        foreach ($namespace_map as $namespace => $contains_string) {
            // Normalize the trailing slash to the current OS directory separator.
            $contains_string = rtrim($contains_string, '\/') . DIRECTORY_SEPARATOR;
            // Skip when we don't have the namespace path.
            if (false === strpos($path, $contains_string)) {
                continue;
            }
            $matching_namespace = $namespace;
            // Once the first namespace is found it breaks out.
            break;
        }
        if (empty($matching_namespace) && !empty($this->origin->template_namespace)) {
            $matching_namespace = $this->origin->template_namespace;
        }
        return $matching_namespace;
    }
    /**
     * Includes a give PHP inside of a safe context.
     *
     * This method is required to prevent template files messing with local variables used inside of the
     * `self::template` method. Also shelters the template loading from any possible variables that could
     * be overwritten by the context.
     *
     * @since 1.0.0
     *
     * @param string $file Which file will be included with safe context.
     *
     * @return string Contents of the included file.
     */
    public function template_safe_include($file)
    {
        ob_start();
        // We use this instance variable to prevent collisions.
        $this->template_current_file_path = $file;
        unset($file);
        // Only do this if really needed (by default it won't).
        if (true === $this->template_context_extract && !empty($this->context)) {
            // Make any provided variables available in the template variable scope.
            extract($this->context);
            // @phpcs:ignore
        }
        include $this->template_current_file_path;
        // After the include we reset the variable.
        unset($this->template_current_file_path);
        return ob_get_clean();
    }
    /**
     * Sets a number of values at the same time.
     *
     * @see   static::set()
     * @since 1.0.0
     *
     * @param array $values   An associative key/value array of the values to set.
     * @param bool  $is_local Whether to set the values as global or local (defaults to local as the `set` method does).
     *
     * @return void
     */
    public function set_values(array $values = [], $is_local = true)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $is_local);
        }
    }
    /**
     * Returns the Template global context.
     *
     * @since 1.0.0
     *
     * @return array An associative key/value array of the Template global context.
     */
    public function get_global_values()
    {
        return $this->global;
    }
    /**
     * Returns the Template local context.
     *
     * @since 1.0.0
     *
     * @return array An associative key/value array of the Template local context.
     */
    public function get_local_values()
    {
        return $this->context;
    }
    /**
     * Returns the Template global and local context values.
     *
     * Local values will override the template global context values.
     *
     * @since 1.0.0
     *
     * @return array An associative key/value array of the Template global and local context.
     */
    public function get_values()
    {
        return array_merge($this->get_global_values(), $this->get_local_values());
    }
    /**
     * Sets the aliases the template should use.
     *
     * @since 1.0.0
     *
     * @param array<string,string> $aliases A map of aliases that should be used to add lookup locations, in the format [ original => alias ].
     *
     * @return static This instance, for method chaining.
     */
    public function set_aliases(array $aliases = [])
    {
        $this->aliases = $aliases;
        return $this;
    }
    /**
     * Fetches the path for locating files in the Plugin Folder
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected function get_template_plugin_path()
    {
        $hook_prefix = Config::get_hook_prefix();
        // Craft the plugin Path.
        $path = array_merge($this->template_base_path, $this->folder);
        // Implode to avoid Window Problems.
        $path = implode(DIRECTORY_SEPARATOR, $path);
        /**
         * Allows filtering of the base path for templates
         *
         * @since 1.0.0
         *
         * @param string $path     Complete path to include the base plugin folder
         * @param self   $template Current instance of the self.
         */
        return apply_filters("stellarwp/templates/{$hook_prefix}/template_plugin_path", $path, $this);
    }
    /**
     * Fetches the Namespace for the public paths, normally folders to look for
     * in the theme's directory.
     *
     * @since 1.0.0
     * @since 1.0.0
     *
     * @param string $plugin_namespace Overwrite the origin namespace with a given one.
     *
     * @return array Namespace where we to look for templates.
     */
    protected function get_template_public_namespace($plugin_namespace)
    {
        $hook_prefix = Config::get_hook_prefix();
        $namespace = [$hook_prefix];
        if (!empty($plugin_namespace)) {
            $namespace[] = $plugin_namespace;
        } elseif (!empty($this->origin->template_namespace)) {
            $namespace[] = $this->origin->template_namespace;
        }
        /**
         * Allows filtering of the base path for templates
         *
         * @since 1.0.0
         *
         * @param array $namespace Which is the namespace we will look for files in the theme
         * @param self  $template  Current instance of the self.
         */
        return apply_filters("stellarwp/templates/{$hook_prefix}/template_public_namespace", $namespace, $this);
    }
    /**
     * Fetches the path for locating files given a base folder normally theme related.
     *
     * @since 1.0.0
     * @since 1.0.0
     *
     * @param mixed  $base      Base path to look into.
     * @param string $namespace Adds the plugin namespace to the path returned.
     *
     * @return string  The public path for a given base.˙˙
     */
    protected function get_template_public_path($base, $namespace)
    {
        // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.namespaceFound
        $hook_prefix = Config::get_hook_prefix();
        // Craft the plugin Path.
        $path = array_merge(Arr::wrap($base), $this->get_template_public_namespace($namespace));
        // Pick up if the folder needs to be added to the public template path.
        $folder = array_diff($this->folder, $this->get_template_origin_base_folder());
        if (!empty($folder)) {
            $path = array_merge($path, $folder);
        }
        // Implode to avoid Window Problems.
        $path = implode(DIRECTORY_SEPARATOR, $path);
        /**
         * Allows filtering of the base path for templates
         *
         * @since 1.0.0
         *
         * @param string $path     Complete path to include the base public folder
         * @param self   $template Current instance of the self.
         */
        return apply_filters("stellarwp/templates/{$hook_prefix}/template_public_path", $path, $this);
    }
    /**
     * Fetches the folders in which we will look for a given file
     *
     * @since 1.0.0
     * @since 1.0.0
     *
     * @return array<string,array> A list of possible locations for the template file.
     */
    protected function get_template_path_list()
    {
        $hook_prefix = Config::get_hook_prefix();
        $folders = [];
        $folders['plugin'] = ['id' => 'plugin', 'priority' => 20, 'path' => $this->get_template_plugin_path()];
        $folders = array_merge($folders, $this->apply_aliases($folders));
        /**
         * Allows filtering of the list of folders in which we will look for the
         * template given.
         *
         * @since 1.0.0
         *
         * @param array $folders  Complete path to include the base public folder
         * @param self  $template Current instance of the self.
         */
        $folders = apply_filters("stellarwp/templates/{$hook_prefix}/template_path_list", $folders, $this);
        $folders = Arr::wrap($folders);
        Arr::sort_by_priority($folders);
        return $folders;
    }
    /**
     * Get the list of theme related folders we will look up for the template.
     *
     * @since 1.0.0
     *
     * @param string $namespace Which plugin namespace we are looking for.
     *
     * @return array
     */
    protected function get_template_theme_path_list($namespace)
    {
        // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.namespaceFound
        $hook_prefix = Config::get_hook_prefix();
        $folders = [];
        $folders['child-theme'] = ['id' => 'child-theme', 'priority' => 10, 'path' => $this->get_template_public_path(get_stylesheet_directory(), $namespace)];
        $folders['parent-theme'] = ['id' => 'parent-theme', 'priority' => 15, 'path' => $this->get_template_public_path(get_template_directory(), $namespace)];
        /**
         * Allows filtering of the list of theme folders in which we will look for the template.
         *
         * @since 1.0.0
         *
         * @param array  $folders   Complete path to include the base public folder.
         * @param string $namespace Loads the files from a specified folder from the themes.
         * @param self   $template  Current instance of the self.
         */
        $folders = apply_filters("stellarwp/templates/{$hook_prefix}/template_theme_path_list", $folders, $namespace, $this);
        $folders = Arr::wrap($folders);
        Arr::sort_by_priority($folders);
        return $folders;
    }
    /**
     * Fetches the path for locating files in the Common folder part of the plugin that is currently providing it.
     *
     * Note: the Common path will be dependent on the version that is loaded from the plugin that is bundling it.
     * E.g. if both TEC and ET are active (both will bundle Common) and the ET version of Common has been loaded as
     * most recent and the ET version of Common does not have a template file, then the template file will not be found.
     * This will allow versioning the existence and nature of the template files part of common.
     *
     * @since 1.0.0
     *
     * @return string The absolute path, with no guarantee of its existence, to the Common version of the template file.
     */
    protected function get_template_common_path()
    {
        $hook_prefix = Config::get_hook_prefix();
        // As base path use the current location of Common, remove the trailing slash.
        $common_abs_path = untrailingslashit(Config::get_path());
        $path = array_merge((array) $common_abs_path, $this->folder);
        // Implode to avoid problems on Windows hosts.
        $path = implode(DIRECTORY_SEPARATOR, $path);
        /**
         * Allows filtering the path to a template provided by Common.
         *
         * @since 1.0.0
         *
         * @param string $path     Complete path to include the base folder of common part of the plugin.
         * @param self   $template Current instance of the self.
         */
        return apply_filters("stellarwp/templates/{$hook_prefix}/template_common_path", $path, $this);
    }
    /**
     * Applies the template path aliases, if any, to a list of folders.
     *
     * @since 1.0.0
     *
     * @param array<string,array> $folders The list of folder to apply the aliases to, if any.
     *
     * @return array<string,array> The list of new folder entries to add to the folders, in the same input format of the
     *                             folders.
     */
    protected function apply_aliases(array $folders)
    {
        $new_folders = [];
        if (!empty($this->aliases)) {
            foreach ($folders as $folder_name => $folder) {
                $original_path = $folder['path'];
                foreach ($this->aliases as $original => $alias) {
                    // Since an alias could be a path, we take care to handle it with the current directory separator.
                    [$normalized_original, $normalized_alias] = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, [$original, $alias]);
                    if (false === strpos($original_path, $normalized_original)) {
                        continue;
                    }
                    $alias_path = str_replace($normalized_original, $normalized_alias, $original_path);
                    $new = $folder;
                    $new['path'] = $alias_path;
                    $new['priority'] = (int) $new['priority'] + 1;
                    $new_folders[$folder_name . '_' . $alias] = $new;
                }
            }
        }
        return $new_folders;
    }
    /**
     * Filters the full HTML for the template.
     *
     * @since 1.0.0
     *
     * @param string $html      The final HTML.
     * @param string $file      Complete path to include the PHP File.
     * @param array  $name      Template name.
     * @param string $hook_name The hook used to create the filter by name.
     *
     * @return string HTML after filtering.
     */
    protected function filter_template_html($html, $file, $name, $hook_name)
    {
        $hook_prefix = Config::get_hook_prefix();
        /**
         * Allow users to filter the final HTML.
         *
         * @since 1.0.0
         * @since 1.0.0
         *
         * @param string $html     The final HTML.
         * @param string $file     Complete path to include the PHP File.
         * @param array  $name     Template name.
         * @param self   $template Current instance of the self.
         */
        $html = apply_filters("stellarwp/templates/{$hook_prefix}/template_html", $html, $file, $name, $this);
        /**
         * Allow users to filter the final HTML by the name.
         *
         * E.g.:
         *    `stellarwp/templates/{$hook_prefix}/template_html:events/blocks/parts/details`
         *    `stellarwp/templates/{$hook_prefix}/template_html:events/embed`
         *    `stellarwp/templates/{$hook_prefix}/template_html:tickets/login-to-purchase`
         *
         * @since 1.0.0
         *
         * @param string $html     The final HTML.
         * @param string $file     Complete path to include the PHP File.
         * @param array  $name     Template name.
         * @param self   $template Current instance of the self.
         */
        $html = apply_filters("stellarwp/templates/{$hook_prefix}/template_html:{$hook_name}", $html, $file, $name, $this);
        return $html;
    }
    /**
     * Filters the HTML for the Before include actions.
     *
     * @since 1.0.0
     *
     * @param string $html      The final HTML.
     * @param string $file      Complete path to include the PHP File.
     * @param array  $name      Template name.
     * @param string $hook_name The hook used to create the filter by name.
     *
     * @return string HTML after filtering.
     */
    protected function filter_template_before_include_html($html, $file, $name, $hook_name)
    {
        $hook_prefix = Config::get_hook_prefix();
        /**
         * Allow users to filter the Before include actions.
         *
         * @since 1.0.0
         *
         * @param string $html     The final HTML.
         * @param string $file     Complete path to include the PHP File.
         * @param array  $name     Template name.
         * @param self   $template Current instance of the self.
         */
        $html = apply_filters("stellarwp/templates/{$hook_prefix}/template_before_include_html", $html, $file, $name, $this);
        /**
         * Allow users to filter the Before include actions by name.
         *
         * E.g.:
         *    `stellarwp/templates/{$hook_prefix}/template_before_include_html:events/blocks/parts/details`
         *    `stellarwp/templates/{$hook_prefix}/template_before_include_html:events/embed`
         *    `stellarwp/templates/{$hook_prefix}/template_before_include_html:tickets/login-to-purchase`
         *
         * @since 1.0.0
         *
         * @param string $html     The final HTML.
         * @param string $file     Complete path to include the PHP File.
         * @param array  $name     Template name.
         * @param self   $template Current instance of the self.
         */
        $html = apply_filters("stellarwp/templates/{$hook_prefix}/template_before_include_html:{$hook_name}", $html, $file, $name, $this);
        return $html;
    }
    /**
     * Filters the HTML for the PHP safe include.
     *
     * @since 1.0.0
     *
     * @param string $html      The final HTML.
     * @param string $file      Complete path to include the PHP File.
     * @param array  $name      Template name.
     * @param string $hook_name The hook used to create the filter by name.
     *
     * @return string HTML after filtering.
     */
    protected function filter_template_include_html($html, $file, $name, $hook_name)
    {
        $hook_prefix = Config::get_hook_prefix();
        /**
         * Allow users to filter the PHP template include actions.
         *
         * @since 1.0.0
         *
         * @param string $html     The final HTML.
         * @param string $file     Complete path to include the PHP File.
         * @param array  $name     Template name.
         * @param self   $template Current instance of the self.
         */
        $html = apply_filters("stellarwp/templates/{$hook_prefix}/template_include_html", $html, $file, $name, $this);
        /**
         * Allow users to filter the PHP template include actions by name.
         *
         * E.g.:
         *    `stellarwp/templates/{$hook_prefix}/template_include_html:events/blocks/parts/details`
         *    `stellarwp/templates/{$hook_prefix}/template_include_html:events/embed`
         *    `stellarwp/templates/{$hook_prefix}/template_include_html:tickets/login-to-purchase`
         *
         * @since 1.0.0
         *
         * @param string $html     The final HTML.
         * @param string $file     Complete path to include the PHP File.
         * @param array  $name     Template name.
         * @param self   $template Current instance of the self.
         */
        $html = apply_filters("stellarwp/templates/{$hook_prefix}/template_include_html:{$hook_name}", $html, $file, $name, $this);
        return $html;
    }
    /**
     * Filters the HTML for the after include actions.
     *
     * @since 1.0.0
     *
     * @param string $html      The final HTML.
     * @param string $file      Complete path to include the PHP File.
     * @param array  $name      Template name.
     * @param string $hook_name The hook used to create the filter by name.
     *
     * @return string HTML after filtering.
     */
    protected function filter_template_after_include_html($html, $file, $name, $hook_name)
    {
        $hook_prefix = Config::get_hook_prefix();
        /**
         * Allow users to filter the after include actions.
         *
         * @since 1.0.0
         *
         * @param string $html     The final HTML.
         * @param string $file     Complete path to include the PHP File.
         * @param array  $name     Template name.
         * @param self   $template Current instance of the self.
         */
        $html = apply_filters("stellarwp/templates/{$hook_prefix}/template_after_include_html", $html, $file, $name, $this);
        /**
         * Allow users to filter the after include actions by name.
         *
         * E.g.:
         *    `stellarwp/templates/{$hook_prefix}/template_after_include_html:events/blocks/parts/details`
         *    `stellarwp/templates/{$hook_prefix}/template_after_include_html:events/embed`
         *    `stellarwp/templates/{$hook_prefix}/template_after_include_html:tickets/login-to-purchase`
         *
         * @since 1.0.0
         *
         * @param string $html     The final HTML.
         * @param string $file     Complete path to include the PHP File.
         * @param array  $name     Template name.
         * @param self   $template Current instance of the self.
         */
        $html = apply_filters("stellarwp/templates/{$hook_prefix}/template_after_include_html:{$hook_name}", $html, $file, $name, $this);
        return $html;
    }
    /**
     * Fires of actions before including the template.
     *
     * @since 1.0.0
     *
     * @param string $file      Complete path to include the PHP File.
     * @param array  $name      Template name.
     * @param string $hook_name The hook used to create the filter by name.
     *
     * @return string HTML printed by the before actions.
     */
    protected function actions_before_template($file, $name, $hook_name)
    {
        $hook_prefix = Config::get_hook_prefix();
        ob_start();
        /**
         * Fires an Action before including the template file
         *
         * @since 1.0.0
         *
         * @param string $file     Complete path to include the PHP File
         * @param array  $name     Template name
         * @param self   $template Current instance of the self.
         */
        do_action("stellarwp/templates/{$hook_prefix}/template_before_include", $file, $name, $this);
        /**
         * Fires an Action for a given template name before including the template file,
         *
         * E.g.:
         *    `stellarwp/templates/{$hook_prefix}/template_before_include:events/blocks/parts/details`
         *    `stellarwp/templates/{$hook_prefix}/template_before_include:events/embed`
         *    `stellarwp/templates/{$hook_prefix}/template_before_include:tickets/login-to-purchase`
         *
         * @since 1.0.0
         *
         * @param string $file     Complete path to include the PHP File.
         * @param array  $name     Template name.
         * @param self   $template Current instance of the self.
         */
        do_action("stellarwp/templates/{$hook_prefix}/template_before_include:{$hook_name}", $file, $name, $this);
        return ob_get_clean();
    }
    /**
     * Fires of actions after including the template.
     *
     * @since 1.0.0
     *
     * @param string $file      Complete path to include the PHP File.
     * @param array  $name      Template name.
     * @param string $hook_name The hook used to create the filter by name.
     *
     * @return string HTML printed by the after actions.
     */
    protected function actions_after_template($file, $name, $hook_name)
    {
        $hook_prefix = Config::get_hook_prefix();
        ob_start();
        /**
         * Fires an Action after including the template file.
         *
         * @since 1.0.0
         *
         * @param string $file     Complete path to include the PHP File.
         * @param array  $name     Template name.
         * @param self   $template Current instance of the self.
         */
        do_action("stellarwp/templates/{$hook_prefix}/template_after_include", $file, $name, $this);
        /**
         * Fires an Action for a given template name after including the template file.
         *
         * E.g.:
         *    `stellarwp/templates/{$hook_prefix}/template_after_include:events/blocks/parts/details`
         *    `stellarwp/templates/{$hook_prefix}/template_after_include:events/embed`
         *    `stellarwp/templates/{$hook_prefix}/template_after_include:tickets/login-to-purchase`
         *
         * @since 1.0.0
         *
         * @param string $file     Complete path to include the PHP File.
         * @param array  $name     Template name.
         * @param self   $template Current instance of the self.
         */
        do_action("stellarwp/templates/{$hook_prefix}/template_after_include:{$hook_name}", $file, $name, $this);
        return ob_get_clean();
    }
    /**
     * Run the hooks for the container entry points.
     *
     * @since 1.0.0
     *
     * @param string $html The html of the current template.
     *
     * @return string Either the final entry point content HTML or `false` if no entry point could be found or set to false.
     */
    private function template_hook_container_entry_points($html)
    {
        $matches = $this->get_entry_point_matches($html);
        $html_matches = $matches[0];
        if (0 === count($html_matches)) {
            return $html;
        }
        $html_tags = $matches['tag'];
        $html_tags_ends = $matches['is_end'];
        // Get first and last tags.
        $first_tag = reset($html_tags);
        $last_tag = end($html_tags);
        // Determine if first last tags are tag ends.
        $first_tag_is_end = '/' === reset($html_tags_ends);
        $last_tag_is_end = '/' === end($html_tags_ends);
        // When first and last tag are not the same, bail.
        if ($first_tag !== $last_tag) {
            return $html;
        }
        // If the first tag is a html tag end, bail.
        if ($first_tag_is_end) {
            return $html;
        }
        // If the last tag is not and html tag end, bail.
        if (!$last_tag_is_end) {
            return $html;
        }
        $first_tag_html = reset($html_matches);
        $last_tag_html = end($html_matches);
        $open_container_entry_point_html = $this->do_entry_point('after_container_open', false);
        $close_container_entry_point_html = $this->do_entry_point('before_container_close', false);
        $html = Strings::replace_first($first_tag_html, $first_tag_html . $open_container_entry_point_html, $html);
        $html = Strings::replace_last($last_tag_html, $close_container_entry_point_html . $last_tag_html, $html);
        return $html;
    }
    /**
     * Get the Entry Point Matches.
     *
     * @since 1.0.0
     *
     * @param string $html The html of the current template.
     *
     * @return array An array of matches from the regular expression.
     */
    private function get_entry_point_matches($html)
    {
        $regexp = '/<(?<is_end>\/)*(?<tag>[A-Z0-9]*)(?:\b)*[^>]*>/mi';
        preg_match_all($regexp, $html, $matches);
        return $matches;
    }
}