<?php

$toggle = ['1' => 'Enabled', '0' => 'Disabled', 'on' => 'Enabled', 'off' => 'Disabled'];

return [
    'theme_sydney' => [
        'label' => 'Sydney',
        'fields' => [
            '_sydney_transparent_menu' => RevisionaryVisualCompareFieldProviders::field('Transparent menu bar', 'Header', $toggle),
            '_sydney_page_disable_sidebar' => RevisionaryVisualCompareFieldProviders::field('Disable sidebar', 'Content visibility', ['1' => 'Disabled', '0' => 'Enabled']),
            '_sydney_page_disable_title' => RevisionaryVisualCompareFieldProviders::field('Disable title', 'Content visibility', ['1' => 'Disabled', '0' => 'Enabled']),
            '_sydney_page_disable_post_featured' => RevisionaryVisualCompareFieldProviders::field('Disable post featured image', 'Content visibility', ['1' => 'Disabled', '0' => 'Enabled']),
            '_sydney_page_enable_featured' => RevisionaryVisualCompareFieldProviders::field('Page featured image', 'Content visibility', $toggle),
            'wpcf-service-icon' => RevisionaryVisualCompareFieldProviders::field('Service icon', 'Service'),
            'wpcf-service-link' => RevisionaryVisualCompareFieldProviders::field('Service link', 'Service'),
            'wpcf-photo' => RevisionaryVisualCompareFieldProviders::field('Employee photo', 'Employee'),
            'wpcf-position' => RevisionaryVisualCompareFieldProviders::field('Employee position', 'Employee'),
            'wpcf-facebook' => RevisionaryVisualCompareFieldProviders::field('Facebook URL', 'Employee'),
            'wpcf-twitter' => RevisionaryVisualCompareFieldProviders::field('X / Twitter URL', 'Employee'),
            'wpcf-google-plus' => RevisionaryVisualCompareFieldProviders::field('Google Plus URL', 'Employee'),
            'wpcf-custom-link' => RevisionaryVisualCompareFieldProviders::field('Custom profile link', 'Employee'),
            'wpcf-project-link' => RevisionaryVisualCompareFieldProviders::field('Project link', 'Project'),
            'wpcf-client-link' => RevisionaryVisualCompareFieldProviders::field('Client link', 'Client'),
            'wpcf-client-function' => RevisionaryVisualCompareFieldProviders::field('Client function', 'Client'),
        ],
    ],
];
