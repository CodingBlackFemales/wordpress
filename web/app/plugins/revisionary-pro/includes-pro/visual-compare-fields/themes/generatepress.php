<?php

return [
    'theme_generatepress' => [
        'label' => 'GeneratePress',
        'fields' => [
            '_generate-sidebar-layout-meta' => RevisionaryVisualCompareFieldProviders::field('Sidebar layout', 'Layout', ['' => 'Default', 'right-sidebar' => 'Right sidebar', 'left-sidebar' => 'Left sidebar', 'no-sidebar' => 'No sidebars', 'both-sidebars' => 'Both sidebars', 'both-left' => 'Both sidebars on left', 'both-right' => 'Both sidebars on right']),
            '_generate-footer-widget-meta' => RevisionaryVisualCompareFieldProviders::field('Footer widgets', 'Footer', ['' => 'Default', '0' => '0 widgets', '1' => '1 widget', '2' => '2 widgets', '3' => '3 widgets', '4' => '4 widgets', '5' => '5 widgets']),
            '_generate-full-width-content' => RevisionaryVisualCompareFieldProviders::field('Content container', 'Layout', ['' => 'Default', 'true' => 'Full width', 'contained' => 'Contained']),
            '_generate-disable-headline' => RevisionaryVisualCompareFieldProviders::field('Disable content title', 'Content visibility', ['true' => 'Disabled', '' => 'Enabled']),
        ],
    ],
];
