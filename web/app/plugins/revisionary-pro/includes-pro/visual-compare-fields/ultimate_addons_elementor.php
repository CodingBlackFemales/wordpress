<?php
return [
    'ultimate_addons_elementor' => [
        'label' => 'Ultimate Addons for Elementor',
        'fields' => [
            'ehf_template_type' => RevisionaryVisualCompareFieldProviders::field('Template type', 'Template'),
            'ehf_target_include_locations' => RevisionaryVisualCompareFieldProviders::field('Display on', 'Display rules'),
            'ehf_target_exclude_locations' => RevisionaryVisualCompareFieldProviders::field('Do not display on', 'Display rules'),
            'ehf_target_user_roles' => RevisionaryVisualCompareFieldProviders::field('User roles', 'Display rules'),
            'display-on-canvas-template' => RevisionaryVisualCompareFieldProviders::field('Display on Elementor Canvas', 'Template', RevisionaryVisualCompareFieldProviders::yesNo()),
        ],
        'classifications' => ['uae_learn' => 'internal'],
    ],
];
