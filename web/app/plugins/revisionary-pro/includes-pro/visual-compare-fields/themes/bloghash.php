<?php

$disabled = ['1' => 'Disabled', '0' => 'Enabled', 'on' => 'Disabled', 'off' => 'Enabled'];

return [
    'theme_bloghash' => [
        'label' => 'BlogHash',
        'fields' => [
            'bloghash_sidebar_position' => RevisionaryVisualCompareFieldProviders::field('Sidebar', 'Layout', ['' => 'Default from Customizer', 'no-sidebar' => 'No sidebar', 'left-sidebar' => 'Left sidebar', 'right-sidebar' => 'Right sidebar']),
            'bloghash_content_layout' => RevisionaryVisualCompareFieldProviders::field('Content layout', 'Layout', ['' => 'Default from Customizer', 'fw-contained' => 'Full width: contained', 'fw-stretched' => 'Full width: stretched']),
            'bloghash_transparent_header' => RevisionaryVisualCompareFieldProviders::field('Transparent header', 'Header', ['enable' => 'Enabled', 'disable' => 'Disabled', '' => 'Default']),
            'bloghash_disable_topbar' => RevisionaryVisualCompareFieldProviders::field('Disable top bar', 'Content visibility', $disabled),
            'bloghash_disable_header' => RevisionaryVisualCompareFieldProviders::field('Disable main header', 'Content visibility', $disabled),
            'bloghash_disable_page_title' => RevisionaryVisualCompareFieldProviders::field('Disable page title', 'Content visibility', $disabled),
            'bloghash_disable_breadcrumbs' => RevisionaryVisualCompareFieldProviders::field('Disable breadcrumbs', 'Content visibility', $disabled),
            'bloghash_disable_thumbnail' => RevisionaryVisualCompareFieldProviders::field('Disable featured image', 'Content visibility', $disabled),
            'bloghash_disable_footer' => RevisionaryVisualCompareFieldProviders::field('Disable main footer', 'Content visibility', $disabled),
            'bloghash_disable_copyright' => RevisionaryVisualCompareFieldProviders::field('Disable copyright bar', 'Content visibility', $disabled),
            'bloghash_disable_blog_card_border' => RevisionaryVisualCompareFieldProviders::field('Disable blog card border', 'Content visibility', $disabled),
        ],
        'classifications' => ['_bloghash_page_builder_setup' => 'internal'],
    ],
];
