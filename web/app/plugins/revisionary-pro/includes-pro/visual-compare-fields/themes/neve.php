<?php

$toggle = ['on' => 'Enabled', 'off' => 'Disabled', '1' => 'Enabled', '0' => 'Disabled'];

return [
    'theme_neve' => [
        'label' => 'Neve',
        'fields' => [
            'neve_meta_container' => RevisionaryVisualCompareFieldProviders::field('Container', 'Layout', ['default' => 'Customizer setting', 'contained' => 'Contained', 'full-width' => 'Full width']),
            'neve_meta_sidebar' => RevisionaryVisualCompareFieldProviders::field('Sidebar', 'Layout', ['default' => 'Customizer setting', 'left' => 'Left sidebar', 'right' => 'Right sidebar', 'full-width' => 'No sidebar']),
            'neve_meta_enable_content_width' => RevisionaryVisualCompareFieldProviders::field('Individual content width', 'Layout', $toggle),
            'neve_meta_content_width' => RevisionaryVisualCompareFieldProviders::field('Content width (%)', 'Layout', [], 'number'),
            'neve_meta_title_alignment' => RevisionaryVisualCompareFieldProviders::field('Title alignment', 'Title', ['left' => 'Left', 'center' => 'Center', 'right' => 'Right']),
            'neve_meta_author_avatar' => RevisionaryVisualCompareFieldProviders::field('Author avatar', 'Post elements', $toggle),
            'neve_post_elements_order' => RevisionaryVisualCompareFieldProviders::field('Post elements order', 'Post elements'),
            'neve_meta_disable_header' => RevisionaryVisualCompareFieldProviders::field('Disable header', 'Content visibility', ['on' => 'Disabled', 'off' => 'Enabled']),
            'neve_meta_disable_footer' => RevisionaryVisualCompareFieldProviders::field('Disable footer', 'Content visibility', ['on' => 'Disabled', 'off' => 'Enabled']),
            'neve_meta_disable_title' => RevisionaryVisualCompareFieldProviders::field('Disable title', 'Content visibility', ['on' => 'Disabled', 'off' => 'Enabled']),
            'neve_meta_disable_featured_image' => RevisionaryVisualCompareFieldProviders::field('Disable featured image', 'Content visibility', ['on' => 'Disabled', 'off' => 'Enabled']),
        ],
        'classifications' => [
            '_customize_draft_post_name' => 'internal',
        ],
    ],
];
