<?php
if (!defined('ABSPATH')) {
    exit;
}

return new class {
    public function available() {
        return defined('ET_BUILDER_VERSION') && version_compare(ET_BUILDER_VERSION, '5.0', '<')
            && function_exists('et_builder_render_layout') && class_exists('ET_Builder_Element');
    }
    public function optInConstant() { return 'REVISIONARY_DIVI_VISUAL_COMPARE'; }
    public function label() { return 'Divi 4'; }
    public function uses($post) {
        return 'on' === get_post_meta($post->ID, '_et_pb_use_builder', true)
            || false !== strpos($post->post_content, '[et_pb_');
    }
    public function render($post) {
        if (!$this->uses($post)) {
            return RevisionaryBuilderCompare::plain($post);
        }
        // REST requests do not fire the frontend `wp` action on which Divi
        // normally registers its module shortcodes. Bootstrap them only here.
        if (!shortcode_exists('et_pb_section')) {
            et_builder_init_global_settings();
            et_builder_add_main_elements();
        }
        $nodes = ['__root__' => ['label' => 'Page settings', 'attributes' => []]];
        foreach (['_et_pb_page_layout', '_et_pb_custom_css', '_et_pb_side_nav', '_et_pb_post_hide_nav'] as $key) {
            if (metadata_exists('post', $post->ID, $key)) {
                $nodes['__root__']['attributes'][$key] = get_post_meta($post->ID, $key, true);
            }
        }
        $capture = static function ($output, $slug, $module) use (&$nodes) {
            $id = 'divi-' . count($nodes);
            $nodes[$id] = ['label' => ucwords(str_replace('_', ' ', preg_replace('/^et_pb_/', '', $slug))), 'attributes' => (array) $module->props];
            // Preserve native wrappers and CSS selectors. Divi 4 has no durable
            // module UUID: matching uses settings/signatures before positions.
            return preg_replace('/<(div|section|article|li|tr|td|span|h[1-6])(?=[\s>])/i', '<$1 data-rvy-builder-id="' . esc_attr($id) . '"', $output, 1);
        };
        ET_Builder_Element::reset_element_indexes('', true);
        ET_Builder_Element::reset_styles();
        add_filter('et_module_shortcode_output', $capture, PHP_INT_MAX, 3);
        try {
            $html = et_builder_render_layout($post->post_content);
            $css = et_pb_get_page_custom_css($post->ID) . ET_Builder_Element::get_style() . ET_Builder_Element::get_style(true);
            $styles = RevisionaryBuilderCompare::styleUrls([]);
            if ('divi' === strtolower(get_template())) {
                $styles[] = get_template_directory_uri() . '/style-static.min.css';
            }
            return array_merge(RevisionaryBuilderCompare::document($html, $css, $nodes, $styles, 'et_divi_theme et_pb_pagebuilder_layout et_pb_gutters3'), [
                'ignoredAttributes' => ['content', 'Content', 'title'],
                'selector' => '[data-rvy-builder-id]', 'idAttribute' => 'data-rvy-builder-id', 'identity' => 'positional',
                'signature' => md5(wp_json_encode([$post->post_content, $nodes['__root__']['attributes']])),
            ]);
        } finally {
            remove_filter('et_module_shortcode_output', $capture, PHP_INT_MAX);
            ET_Builder_Element::reset_element_indexes('', true);
            ET_Builder_Element::reset_styles();
        }
    }
};
