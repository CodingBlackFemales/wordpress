<?php
if (!defined('ABSPATH')) {
    exit;
}

return new class {
    public function available() {
        return defined('FL_BUILDER_VERSION') && class_exists('FLBuilder') && class_exists('FLBuilderModel');
    }
    public function optInConstant() { return 'REVISIONARY_BEAVER_VISUAL_COMPARE'; }
    public function label() { return 'Beaver Builder'; }
    public function register() {
        add_filter('revisionary_compare_meta_from', [$this, 'comparisonMetadata'], 20, 2);
    }
    public function comparisonMetadata($meta, $post_id) {
        // The native Content canvas renders these snapshots directly. Do not
        // repeat them as fields or remove them from storage/revision triggers.
        unset($meta['_fl_builder_data'], $meta['_fl_builder_draft']);
        if (rvy_in_revision_workflow($post_id) && isset($meta['_fl_builder_draft_settings'])) {
            $meta['_fl_builder_data_settings'] = $meta['_fl_builder_draft_settings'];
        }
        foreach (['_fl_builder_data_settings', '_fl_builder_draft_settings'] as $key) {
            if (!isset($meta[$key])) {
                continue;
            }
            $meta[$key] = array_map(function ($value) {
                return $this->normalize(maybe_unserialize($value));
            }, (array) $meta[$key]);
        }
        return $meta;
    }
    private function normalize($value, $depth = 0) {
        if (20 < $depth) {
            return '[Nested layout]';
        }
        if (is_object($value)) {
            $value = get_object_vars($value);
        }
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->normalize($item, $depth + 1);
            }
        }
        return $value;
    }
    public function uses($post) {
        return (bool) get_post_meta($post->ID, '_fl_builder_enabled', true);
    }
    public function render($post) {
        if (metadata_exists('post', $post->ID, '_fl_builder_enabled') && !$this->uses($post)) {
            return RevisionaryBuilderCompare::plain($post);
        }
        $key = rvy_in_revision_workflow($post) && metadata_exists('post', $post->ID, '_fl_builder_draft')
            ? '_fl_builder_draft' : '_fl_builder_data';
        if (!metadata_exists('post', $post->ID, $key)) {
            return wp_is_post_revision($post) ? RevisionaryBuilderCompare::missing() : RevisionaryBuilderCompare::plain($post);
        }
        $data = get_post_meta($post->ID, $key, true);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid Beaver Builder layout snapshot');
        }
        $nodes = [];
        foreach ($data as $node) {
            if (is_object($node) && !empty($node->node)) {
                $nodes[(string) $node->node] = [
                    'label' => ucwords(str_replace(['-', '_'], ' ', $node->settings->type ?? $node->type ?? 'Node')),
                    'attributes' => (array) ($node->settings ?? []),
                ];
            }
        }
        $layout = static function ($value, $status, $id) use ($post, $data) {
            return (int) $id === (int) $post->ID ? $data : $value;
        };
        $settings_key = '_fl_builder_draft' === $key ? '_fl_builder_draft_settings' : '_fl_builder_data_settings';
        $nodes['__root__'] = ['label' => 'Layout settings', 'attributes' => (array) get_post_meta($post->ID, $settings_key, true)];
        $settings = static function ($value, $status, $id) use ($post, $settings_key) {
            return (int) $id === (int) $post->ID && metadata_exists('post', $id, $settings_key)
                ? (object) array_merge((array) $value, (array) get_post_meta($id, $settings_key, true)) : $value;
        };
        add_filter('fl_builder_layout_data', $layout, PHP_INT_MAX, 3);
        add_filter('fl_builder_layout_settings', $settings, PHP_INT_MAX, 3);
        add_filter('fl_builder_render_assets_inline', '__return_true', PHP_INT_MAX);
        FLBuilderModel::set_post_id($post->ID);
        // Native CSS compilation deduplicates across the request. Each iframe
        // snapshot needs complete CSS, including styles shared with another one.
        $css_state = [];
        $isolated_state = [
            'FLBuilder' => ['enqueued_global_assets' => [], 'enqueued_module_css_assets' => []],
            'FLBuilderEnqueue' => ['registered' => false],
            'FLBuilderFonts' => ['fonts' => [], 'wp_fonts' => []],
        ];
        foreach ($isolated_state as $class => $properties) {
            foreach ($properties as $name => $initial) {
                if (class_exists($class) && property_exists($class, $name)) {
                    $property = new ReflectionProperty($class, $name);
                    $property->setAccessible(true);
                    $css_state[] = [$property, $property->getValue()];
                    $property->setValue(null, $initial);
                }
            }
        }
        try {
            // Compare runs on an admin screen, where Beaver deliberately skips
            // registering frontend libraries. Register before modules request
            // their icon, font and other native stylesheet dependencies.
            if (class_exists('FLBuilderEnqueue') && method_exists('FLBuilderEnqueue', 'register_layout_libraries')) {
                FLBuilderEnqueue::register_layout_libraries();
            }
            ob_start();
            // Use native node rendering inside our own balanced context stack.
            // render_content_by_id() does not unwind its stack on exceptions.
            do_action('fl_builder_before_render_content');
            echo '<div class="' . esc_attr(FLBuilder::render_content_classes()) . '">';
            FLBuilder::render_nodes();
            echo '</div>';
            do_action('fl_builder_after_render_content');
            $html = ob_get_clean();
            $html = wp_filter_content_tags(do_shortcode($html));
            // Inline mode compiles CSS without writing a cache file.
            $css = FLBuilder::render_css();
            // These native dependencies (icons, fonts, module libraries) are
            // enqueued separately from the compiled layout CSS on the frontend.
            if (method_exists('FLBuilderModel', 'get_categorized_nodes') && method_exists('FLBuilder', 'enqueue_module_layout_styles_scripts')) {
                $categorized = FLBuilderModel::get_categorized_nodes();
                foreach ($categorized['modules'] as $module) {
                    if (is_object($module)) {
                        FLBuilder::enqueue_module_layout_styles_scripts($module);
                    }
                }
            }
            if (class_exists('FLBuilderFonts') && method_exists('FLBuilderFonts', 'enqueue_google_fonts')) {
                FLBuilderFonts::enqueue_google_fonts();
            }
            if (class_exists('FLBuilderFonts') && method_exists('FLBuilderFonts', 'enqueue_wp_fonts')) {
                // Native WP Font Library faces are printed rather than queued.
                // Capture their CSS for the iframe instead of the admin page.
                ob_start();
                FLBuilderFonts::enqueue_wp_fonts();
                $faces = ob_get_clean();
                if (preg_match_all('#<style\b[^>]*>(.*?)</style>#is', $faces, $matches)) {
                    $css .= "\n" . implode("\n", $matches[1]);
                }
            }
            return array_merge(RevisionaryBuilderCompare::document($html, $css, $nodes, RevisionaryBuilderCompare::styleUrls([]), 'fl-builder'), [
                'selector' => '[data-node]', 'idAttribute' => 'data-node', 'identity' => 'stable',
                'signature' => md5(wp_json_encode([$data, get_post_meta($post->ID, $settings_key, true)])),
            ]);
        } finally {
            foreach ($css_state as $state) {
                $state[0]->setValue(null, $state[1]);
            }
            FLBuilderModel::reset_post_id();
            remove_filter('fl_builder_layout_data', $layout, PHP_INT_MAX);
            remove_filter('fl_builder_layout_settings', $settings, PHP_INT_MAX);
            remove_filter('fl_builder_render_assets_inline', '__return_true', PHP_INT_MAX);
        }
    }
};
