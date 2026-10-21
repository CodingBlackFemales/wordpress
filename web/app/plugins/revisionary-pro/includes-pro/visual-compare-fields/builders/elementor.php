<?php
if (!defined('ABSPATH')) {
    exit;
}

return new class {
    public function available() {
        return defined('ELEMENTOR_VERSION') && class_exists('Elementor\\Plugin') && !defined('RVY_DISABLE_ELEMENTOR_INTEGRATION');
    }
    public function optInConstant() { return 'REVISIONARY_ELEMENTOR_VISUAL_COMPARE'; }
    public function label() { return 'Elementor'; }
    public function register() {
        add_filter('revisionary_compare_meta_from', [$this, 'comparisonMetadata'], 20, 2);
    }
    public function comparisonMetadata($meta, $post_id) {
        // Layout content and structure belong to the native Content canvas.
        // Filter comparison copies only; keep storage and revision triggers intact.
        unset($meta['_elementor_data'], $meta['_elementor_page_settings']);
        return $meta;
    }
    public function uses($post) {
        if (metadata_exists('post', $post->ID, '_elementor_edit_mode')) {
            return 'builder' === get_post_meta($post->ID, '_elementor_edit_mode', true);
        }
        return (bool) get_post_meta($post->ID, '_elementor_data', true);
    }
    public function render($post) {
        if (metadata_exists('post', $post->ID, '_elementor_edit_mode') && !$this->uses($post)) {
            return RevisionaryBuilderCompare::plain($post);
        }
        if (!metadata_exists('post', $post->ID, '_elementor_data')) {
            return wp_is_post_revision($post) ? RevisionaryBuilderCompare::missing() : RevisionaryBuilderCompare::plain($post);
        }
        $raw = get_post_meta($post->ID, '_elementor_data', true);
        $data = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($data)) {
            throw new RuntimeException('Invalid Elementor layout snapshot');
        }
        $plugin = \Elementor\Plugin::$instance;
        $editor_mode = $plugin->editor->is_edit_mode();
        $document = $plugin->documents->get($post->ID);
        if (!$document) {
            throw new RuntimeException('Elementor document unavailable');
        }
        // Document settings are not selectable content elements.
        $nodes = [];
        $walk = static function ($items) use (&$walk, &$nodes) {
            foreach ($items as $item) {
                if (!empty($item['id'])) {
                    $nodes[(string) $item['id']] = [
                        'label' => ucwords(str_replace(['-', '_'], ' ', $item['widgetType'] ?? $item['elType'] ?? 'Element')),
                        'type' => (string) ($item['widgetType'] ?? $item['elType'] ?? ''),
                        'attributes' => (array) ($item['settings'] ?? []),
                    ];
                }
                $walk((array) ($item['elements'] ?? []));
            }
        };
        $walk($data);
        $plugin->editor->set_edit_mode(false);
        $plugin->documents->switch_to_document($document);
        // Bypass Elementor's persistent HTML cache and shortcode placeholders.
        // Each side must be rendered from its own saved element data.
        $disable_cache = static function () { return 'disable'; };
        add_filter('pre_option_elementor_element_cache_ttl', $disable_cache, PHP_INT_MAX);
        add_filter('elementor/element/should_render_shortcode', '__return_false', PHP_INT_MAX);
        try {
            // Render the saved data directly. Frontend preview helpers may select
            // a newer autosave; this comparison must use the requested snapshot.
            ob_start();
            $document->print_elements_with_wrapper($data);
            $html = ob_get_clean();
            // A past revision's wrapper uses the parent ID, while its CSS uses
            // the snapshot ID. Give only the outer wrapper the matching class.
            $html = preg_replace('/class="elementor /', 'class="elementor elementor-' . (int) $post->ID . ' ', $html, 1);
            $css = (new \Elementor\Core\Files\CSS\Post($post->ID))->get_content();
            $body_class = 'elementor-default';
            $kit = $plugin->kits_manager->get_active_kit();
            if ($kit && $kit->get_id()) {
                $css = (new \Elementor\Core\Files\CSS\Post($kit->get_id()))->get_content() . $css;
                $body_class .= ' elementor-kit-' . (int) $kit->get_main_id();
            }
            // Direct snapshot rendering bypasses Elementor's normal content
            // path and its has_elementor_in_page flag. Request native frontend
            // configuration and widget dependencies explicitly.
            if (RevisionaryBuilderCompare::javascriptEnabled()) {
                $plugin->frontend->enqueue_scripts();
            }
            $plugin->frontend->register_styles();
            $styles = RevisionaryBuilderCompare::styleUrls(['elementor-frontend']);
            return array_merge(RevisionaryBuilderCompare::document($html, $css, $nodes, $styles, $body_class), [
                'selector' => '.elementor-element[data-id]', 'idAttribute' => 'data-id', 'identity' => 'stable',
                'signature' => md5(wp_json_encode($data)),
                'ignoredNodes' => ['__root__'],
            ]);
        } finally {
            remove_filter('pre_option_elementor_element_cache_ttl', $disable_cache, PHP_INT_MAX);
            remove_filter('elementor/element/should_render_shortcode', '__return_false', PHP_INT_MAX);
            $plugin->documents->restore_document();
            $plugin->editor->set_edit_mode($editor_mode);
        }
    }
};
