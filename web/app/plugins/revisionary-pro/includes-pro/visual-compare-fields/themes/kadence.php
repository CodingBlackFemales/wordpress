<?php

$default_toggle = ['default' => 'Default', 'enable' => 'Enabled', 'disable' => 'Disabled'];

return [
    'theme_kadence' => [
        'label' => 'Kadence',
        'fields' => [
            '_kad_post_transparent' => RevisionaryVisualCompareFieldProviders::field('Transparent header', 'Header', $default_toggle),
            '_kad_post_title' => RevisionaryVisualCompareFieldProviders::field('Display title', 'Title', ['default' => 'Default', 'normal' => 'Enabled', 'above' => 'Enabled above content', 'hide' => 'Disabled']),
            '_kad_post_layout' => RevisionaryVisualCompareFieldProviders::field('Layout', 'Layout', ['default' => 'Default', 'normal' => 'Normal', 'narrow' => 'Narrow', 'fullwidth' => 'Full width', 'left' => 'Sidebar left', 'right' => 'Sidebar right']),
            '_kad_post_sidebar_id' => RevisionaryVisualCompareFieldProviders::field('Sidebar', 'Layout'),
            '_kad_post_content_style' => RevisionaryVisualCompareFieldProviders::field('Content style', 'Layout', ['default' => 'Default', 'boxed' => 'Boxed', 'unboxed' => 'Unboxed']),
            '_kad_post_vertical_padding' => RevisionaryVisualCompareFieldProviders::field('Content vertical padding', 'Layout', ['default' => 'Default', 'show' => 'Enabled', 'hide' => 'Disabled', 'top' => 'Top only', 'bottom' => 'Bottom only']),
            '_kad_post_feature' => RevisionaryVisualCompareFieldProviders::field('Show featured image', 'Featured media', ['default' => 'Default', 'show' => 'Enabled', 'hide' => 'Disabled']),
            '_kad_post_feature_position' => RevisionaryVisualCompareFieldProviders::field('Featured image position', 'Featured media', ['default' => 'Default', 'above' => 'Above', 'below' => 'Below', 'behind' => 'Behind title']),
            '_kad_post_header' => RevisionaryVisualCompareFieldProviders::field('Disable header', 'Header', ['1' => 'Disabled', '0' => 'Enabled']),
            '_kad_post_footer' => RevisionaryVisualCompareFieldProviders::field('Disable footer', 'Footer', ['1' => 'Disabled', '0' => 'Enabled']),
            '_kad_post_classname' => RevisionaryVisualCompareFieldProviders::field('Body CSS class', 'Advanced'),
            '_kad_post_navigation' => RevisionaryVisualCompareFieldProviders::field('Post navigation', 'Navigation', $default_toggle),
        ],
        'classifications' => ['_kad_pagebuilder_layout_flag' => 'internal'],
    ],
];
