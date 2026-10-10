<?php

$toggle = ['on' => 'Enabled', 'off' => 'Disabled', '' => 'Default'];

return [
    'theme_hestia' => [
        'label' => 'Hestia',
        'fields' => [
            'hestia_layout_select' => RevisionaryVisualCompareFieldProviders::field('Sidebar', 'Layout', ['full-width' => 'Full width', 'sidebar-left' => 'Left sidebar', 'sidebar-right' => 'Right sidebar']),
            'hestia_header_layout' => RevisionaryVisualCompareFieldProviders::field('Header layout', 'Header', ['default' => 'Default', 'no-content' => 'No content', 'classic-blog' => 'Classic blog']),
            'hestia_disable_navigation' => RevisionaryVisualCompareFieldProviders::field('Disable navigation', 'Content visibility', ['on' => 'Disabled', 'off' => 'Enabled']),
            'hestia_disable_footer' => RevisionaryVisualCompareFieldProviders::field('Disable footer', 'Content visibility', ['on' => 'Disabled', 'off' => 'Enabled']),
            'hestia_meta_disable_title' => RevisionaryVisualCompareFieldProviders::field('Disable title', 'Content visibility', ['on' => 'Disabled', 'off' => 'Enabled']),
            'hestia_enable_transparent' => RevisionaryVisualCompareFieldProviders::field('Transparent header', 'Header', $toggle),
        ],
    ],
];
