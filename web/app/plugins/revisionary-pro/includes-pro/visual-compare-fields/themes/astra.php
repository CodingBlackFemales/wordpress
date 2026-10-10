<?php

$disabled = ['disabled' => 'Disabled', '' => 'Enabled'];
$default_toggle = ['default' => 'Customizer setting', 'disabled' => 'Disabled', '' => 'Enabled'];

return [
    'theme_astra' => [
        'label' => 'Astra',
        'fields' => [
            'ast-site-content-layout' => RevisionaryVisualCompareFieldProviders::field('Container layout', 'Layout', ['default' => 'Customizer setting', 'normal-width-container' => 'Normal', 'narrow-width-container' => 'Narrow', 'full-width-container' => 'Full width']),
            'site-content-style' => RevisionaryVisualCompareFieldProviders::field('Container style', 'Layout', ['default' => 'Customizer setting', 'unboxed' => 'Unboxed', 'boxed' => 'Boxed']),
            'site-sidebar-layout' => RevisionaryVisualCompareFieldProviders::field('Sidebar layout', 'Layout', ['default' => 'Customizer setting', 'left-sidebar' => 'Left sidebar', 'right-sidebar' => 'Right sidebar', 'no-sidebar' => 'No sidebar']),
            'site-sidebar-style' => RevisionaryVisualCompareFieldProviders::field('Sidebar style', 'Layout', ['default' => 'Customizer setting', 'unboxed' => 'Unboxed', 'boxed' => 'Boxed']),
            'ast-global-header-display' => RevisionaryVisualCompareFieldProviders::field('Disable header', 'Header', $default_toggle),
            'ast-hfb-above-header-display' => RevisionaryVisualCompareFieldProviders::field('Disable above header', 'Header', $default_toggle),
            'ast-main-header-display' => RevisionaryVisualCompareFieldProviders::field('Disable primary header', 'Header', $disabled),
            'ast-hfb-below-header-display' => RevisionaryVisualCompareFieldProviders::field('Disable below header', 'Header', $default_toggle),
            'ast-hfb-mobile-header-display' => RevisionaryVisualCompareFieldProviders::field('Disable mobile header', 'Header', $default_toggle),
            'theme-transparent-header-meta' => RevisionaryVisualCompareFieldProviders::field('Transparent header', 'Header', ['default' => 'Inherit', 'enabled' => 'Enabled', 'disabled' => 'Disabled']),
            'site-post-title' => RevisionaryVisualCompareFieldProviders::field('Disable title', 'Content visibility', $disabled),
            'ast-banner-title-visibility' => RevisionaryVisualCompareFieldProviders::field('Disable banner area', 'Content visibility', $disabled),
            'ast-breadcrumbs-content' => RevisionaryVisualCompareFieldProviders::field('Disable breadcrumb', 'Content visibility', $disabled),
            'ast-featured-img' => RevisionaryVisualCompareFieldProviders::field('Disable featured image', 'Content visibility', $disabled),
            'ast-disable-related-posts' => RevisionaryVisualCompareFieldProviders::field('Disable related posts', 'Content visibility', $disabled),
            'footer-adv-display' => RevisionaryVisualCompareFieldProviders::field('Disable footer widgets', 'Footer', $disabled),
            'footer-sml-layout' => RevisionaryVisualCompareFieldProviders::field('Disable footer bar', 'Footer', $disabled),
            'ast-page-background-enabled' => RevisionaryVisualCompareFieldProviders::field('Custom page background', 'Background', ['default' => 'Customizer setting', 'enabled' => 'Enabled', 'disabled' => 'Disabled']),
            'ast-page-background-meta' => RevisionaryVisualCompareFieldProviders::field('Page background settings', 'Background'),
        ],
        'classifications' => [
            '_astra_content_layout_flag' => 'internal',
            'astra-migrate-meta-layouts' => 'internal',
        ],
    ],
];
