<?php

return [
    'elementor' => [
        'label' => 'Elementor',
        'caption_prefixes' => ['_elementor_'],
        'fields' => [
            '_elementor_data' => RevisionaryVisualCompareFieldProviders::field('Layout data', 'Content'),
            '_elementor_page_settings' => RevisionaryVisualCompareFieldProviders::field('Page settings', 'Settings'),
            '_elementor_template_type' => RevisionaryVisualCompareFieldProviders::field('Template type', 'Template'),
            '_elementor_conditions' => RevisionaryVisualCompareFieldProviders::field('Display conditions', 'Template'),
            '_elementor_edit_mode' => RevisionaryVisualCompareFieldProviders::field('Editing mode', 'Settings', ['builder' => 'Elementor', 'default' => 'WordPress']),
        ],
        'blacklist' => [
            '_elementor_css', '_elementor_page_assets', '_elementor_controls_usage',
            '_elementor_oembed_cache', '_elementor_source_image_hash',
        ],
    ],
];
