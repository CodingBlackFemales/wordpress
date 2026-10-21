<?php
/** Native builder previews. Only the transport and extension hooks are shared. */
if (!defined('ABSPATH')) {
    exit;
}

final class RevisionaryBuilderCompare {
    private static $adapters = [];

    public static function init() {
        foreach (['elementor', 'beaver', 'divi'] as $name) {
            if (isset(self::$adapters[$name])) {
                continue;
            }
            $adapter = require __DIR__ . '/builders/' . $name . '.php';
            if ($adapter && $adapter->available()) {
                self::$adapters[$name] = $adapter;
                if (is_callable([$adapter, 'register'])) {
                    $adapter->register();
                }
                // Honor an explicit administrator opt-out.
                if (!defined($adapter->optInConstant())) {
                    define($adapter->optInConstant(), true);
                }
            }
        }
        if (self::$adapters) {
            require_once __DIR__ . '/FrontendPreview.php';
            RevisionaryBuilderFrontendPreview::init();
            add_action('revisionary_option_ui_builder_compare', [__CLASS__, 'settings'], 10, 3);
            add_filter('visual_post_compare_comparison_payload', [__CLASS__, 'payload'], 20, 3);
            add_filter('visual_post_compare_script_dependencies', [__CLASS__, 'scripts'], 20);
        }
    }

    public static function javascriptEnabled() {
        return function_exists('rvy_get_option') && (bool) rvy_get_option('visual_compare_javascript');
    }

    public static function diviThemeActive() {
        // get_template() identifies the parent theme for child themes as well.
        return function_exists('get_template') && 'divi' === strtolower(get_template());
    }

    public static function externalRequestsEnabled() {
        return !self::diviThemeActive() && function_exists('rvy_get_option') && (bool) rvy_get_option('builder_compare_external_requests');
    }

    public static function settings($ui, $tab, $section) {
        if (!self::$adapters || self::diviThemeActive()) { return; }
        $ui->option_captions['builder_compare_external_requests'] = __('Page builder comparisons load external page requests', 'revisionary-pro');
        $ui->form_options[$tab][$section][] = 'builder_compare_external_requests';
        $ui->option_checkbox('builder_compare_external_requests', $tab, $section, __('This may provide more accurate styling, possibly with performance drawbacks.', 'revisionary-pro'));
    }

    public static function frontendAdapter($name) {
        $adapter = self::$adapters[$name] ?? null;
        return $adapter && constant($adapter->optInConstant()) ? $adapter : null;
    }

    public static function scripts($dependencies) {
        wp_register_script('revisionary-elementor-compare-runtime', plugins_url('includes-pro/visual-compare-fields/builders/elementor-runtime.js', REVISIONARY_PRO_FILE), ['wp-hooks'], PUBLISHPRESS_REVISIONS_PRO_VERSION, true);
        wp_register_script('revisionary-builder-compare', plugins_url('includes-pro/visual-compare-fields/builders/compare.js', REVISIONARY_PRO_FILE), ['wp-element', 'wp-hooks', 'wp-i18n', 'revisionary-elementor-compare-runtime'], PUBLISHPRESS_REVISIONS_PRO_VERSION, true);
        $dependencies[] = 'revisionary-builder-compare';
        return array_values(array_unique($dependencies));
    }

    public static function payload($payload, $current_post, $slider_posts) {
        if (!is_array($payload) || !$current_post instanceof WP_Post) {
            return $payload;
        }
        $authorized = \PublishPress\Visual_Post_Compare::authorized_comparison((int) ($payload['authorizationRevisionId'] ?? 0));
        if (is_wp_error($authorized) || (int) $authorized['current_post']->ID !== (int) $current_post->ID) {
            return $payload;
        }
        $adapter = null;
        $builder_name = '';
        $selected = get_post((int) ($payload['revision']['id'] ?? 0));
        foreach (self::$adapters as $name => $candidate) {
            if (constant($candidate->optInConstant()) && ($candidate->uses($current_post) || ($selected && $candidate->uses($selected)))) {
                $adapter = $candidate;
                $builder_name = $name;
                break;
            }
        }
        if (!$adapter) {
            return $payload;
        }
        $ids = [];
        foreach (array_merge((array) ($payload['posts'] ?? []), [$payload['current'], $payload['revision'], $payload['comparisonBase'] ?? []]) as $item) {
            // Historical slider stubs are rendered on demand, not eagerly.
            if (isset($item['content'], $item['id'])) {
                $ids[(int) $item['id']] = true;
            }
        }
        $documents = [];
        foreach (array_keys($ids) as $id) {
            $post = get_post($id);
            if (!$post || !current_user_can('read_post', $id)) {
                continue;
            }
            $parent = wp_is_post_revision($post) ?: (rvy_in_revision_workflow($post) ? rvy_post_id($post) : $id);
            if ((int) $parent !== (int) $current_post->ID) {
                continue;
            }
            try {
                $documents[(string) $id] = self::inPostContext($post, static function () use ($adapter, $post) {
                    return $adapter->render($post);
                });
                if (self::externalRequestsEnabled() && empty($documents[(string) $id]['error'])) {
                    $documents[(string) $id]['frontendUrl'] = RevisionaryBuilderFrontendPreview::url($id, (int) $payload['authorizationRevisionId'], $builder_name, $current_post->ID);
                }
            } catch (\Throwable $error) {
                // Never misrepresent a failed native render as unchanged content.
                $documents[(string) $id] = ['error' => __('The builder preview could not be rendered. Use the classic comparison or revision preview.', 'revisionary-pro')];
            }
        }
        $payload['builderCompare'] = ['name' => $builder_name, 'label' => $adapter->label(), 'documents' => $documents];
        return $payload;
    }

    public static function inPostContext($post, $callback) {
        $names = ['post', 'wp_query', 'wp_the_query', 'wp_styles', 'wp_scripts'];
        $saved = [];
        foreach ($names as $name) {
            $saved[$name] = [array_key_exists($name, $GLOBALS), $GLOBALS[$name] ?? null];
        }
        foreach (['wp_styles', 'wp_scripts'] as $name) {
            if (isset($GLOBALS[$name]) && is_object($GLOBALS[$name])) {
                $GLOBALS[$name] = clone $GLOBALS[$name];
                if ('wp_scripts' === $name) {
                    // The iframe has its own runtime: admin scripts already
                    // printed in the parent are not available inside it.
                    $GLOBALS[$name]->queue = [];
                    $GLOBALS[$name]->to_do = [];
                    $GLOBALS[$name]->done = [];
                }
            }
        }
        $query = new WP_Query();
        $query->post = $post;
        $query->posts = [$post];
        $query->post_count = 1;
        $query->queried_object = $post;
        $query->queried_object_id = $post->ID;
        $query->is_singular = true;
        $parent = wp_is_post_revision($post) ? get_post($post->post_parent) : null;
        $query->is_page = 'page' === ($parent ? $parent->post_type : $post->post_type);
        $query->is_single = !$query->is_page;
        $GLOBALS['post'] = $post;
        $GLOBALS['wp_query'] = $query;
        $GLOBALS['wp_the_query'] = $query;
        $level = ob_get_level();
        // Native renderers may populate postmeta caches while reading a layout.
        // A comparison must neither change a source snapshot nor its parent.
        $read_only = static function () { return true; };
        foreach (['add_post_metadata', 'update_post_metadata', 'delete_post_metadata'] as $hook) {
            add_filter($hook, $read_only, PHP_INT_MAX);
        }
        try {
            // Some third-party modules echo while returning their markup.
            ob_start();
            $theme_css = '';
            if (!self::externalRequestsEnabled() && function_exists('wp_enqueue_scripts')) {
                // Standard frontend enqueue callbacks, isolated from the admin
                // registry. Themes with explicit admin guards may still skip.
                ob_start();
                wp_enqueue_scripts();
                $printed = ob_get_clean();
                if (preg_match_all('#<style\b[^>]*>(.*?)</style>#is', $printed, $matches)) {
                    $theme_css = implode("\n", $matches[1]);
                }
            }
            $result = $callback();
            if (is_array($result) && !self::externalRequestsEnabled() && self::javascriptEnabled() && function_exists('wp_print_head_scripts')) {
                ob_start();
                wp_print_head_scripts();
                wp_print_footer_scripts();
                $result['scripts'] = ob_get_clean();
            }
            if (is_array($result) && $theme_css) {
                $result['inline_styles'] = $theme_css . "\n" . ($result['inline_styles'] ?? '');
            }
            return $result;
        } finally {
            foreach (['add_post_metadata', 'update_post_metadata', 'delete_post_metadata'] as $hook) {
                remove_filter($hook, $read_only, PHP_INT_MAX);
            }
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            foreach ($saved as $name => $value) {
                if ($value[0]) {
                    $GLOBALS[$name] = $value[1];
                } else {
                    unset($GLOBALS[$name]);
                }
            }
        }
    }

    public static function document($html, $css, $nodes, $styles = [], $body_class = '') {
        $signature = md5($nodes ? wp_json_encode($nodes) : $html);
        $baseUrl = home_url('/');
        // Registered inline styles include global theme styles and native
        // module styles that have no external stylesheet URL.
        $inline_styles = '';
        if (function_exists('wp_styles')) {
            $registry = wp_styles();
            $seen = [];
            $visit = static function ($handle) use (&$visit, &$seen, &$inline_styles, $registry) {
                if (isset($seen[$handle]) || empty($registry->registered[$handle]) || preg_match('/^(?:wp-admin|dashicons|revisionary|visual-post-compare)/', $handle)) {
                    return;
                }
                $seen[$handle] = true;
                $style = $registry->registered[$handle];
                foreach ($style->deps as $dependency) {
                    $visit($dependency);
                }
                $inline_styles .= implode("\n", (array) ($style->extra['after'] ?? [])) . "\n";
            };
            foreach ((array) $registry->queue as $handle) {
                $visit($handle);
            }
        }
        if (function_exists('get_body_class')) {
            $body_class = implode(' ', array_unique(array_merge(explode(' ', $body_class), get_body_class())));
        }
        $javascript = self::javascriptEnabled();
        return compact('html', 'css', 'inline_styles', 'nodes', 'styles', 'body_class', 'signature', 'baseUrl', 'javascript');
    }

    public static function styleUrls($handles) {
        $styles = wp_styles();
        $handles = array_merge($handles, (array) $styles->queue);
        $urls = [];
        $seen = [];
        $visit = static function ($handle) use (&$visit, &$seen, &$urls, $styles) {
            if (isset($seen[$handle]) || empty($styles->registered[$handle])) {
                return;
            }
            $seen[$handle] = true;
            $style = $styles->registered[$handle];
            foreach ($style->deps as $dependency) {
                $visit($dependency);
            }
            if ($style->src && !preg_match('#(?:^|/)wp-admin/#', $style->src) && !preg_match('/^(?:wp-admin|dashicons|revisionary|visual-post-compare)/', $handle)) {
                $src = $style->src;
                if (0 === strpos($src, '//')) {
                    $src = set_url_scheme($src);
                } elseif (0 === strpos($src, '/')) {
                    $src = home_url($src);
                } elseif (!preg_match('#^https?://#', $src)) {
                    $src = $styles->base_url . $src;
                }
                $urls[] = esc_url_raw($src);
            }
        };
        foreach ($handles as $handle) {
            $visit($handle);
        }
        return array_values(array_unique($urls));
    }

    public static function missing() {
        return ['error' => __('This revision has no saved builder layout. Its visual changes cannot be reconstructed.', 'revisionary-pro')];
    }

    public static function plain($post) {
        return self::document(wpautop(do_shortcode($post->post_content)), '', [], [], '');
    }
}
