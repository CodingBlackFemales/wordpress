<?php

return [
    'spectra' => [
        'label' => 'Spectra Legacy',
        'caption_prefixes' => ['spectra-popup-', '_uag_', '_uagb_'],
        'fields' => [
            '_uag_custom_page_level_css' => RevisionaryVisualCompareFieldProviders::field('Custom CSS', 'Page settings'),
            'spectra-popup-type' => RevisionaryVisualCompareFieldProviders::field('Popup type', 'Popup settings', [
                'popup' => 'Popup', 'info-bar' => 'Info bar',
            ]),
            'spectra-popup-enabled' => RevisionaryVisualCompareFieldProviders::field('Popup enabled', 'Popup settings', RevisionaryVisualCompareFieldProviders::yesNo()),
            'spectra-popup-repetition' => RevisionaryVisualCompareFieldProviders::field('Popup repetition', 'Popup settings', [], 'number'),
        ],
        'blacklist' => [
            '_uag_css_file_name', '_uag_js_file_name', '_uag_page_assets',
            '_uag_migration_processed', '_uagb_previous_block_counts', '_uagb_toc_options',
            '_ast_block_templates_image_hash',
        ],
    ],
];
