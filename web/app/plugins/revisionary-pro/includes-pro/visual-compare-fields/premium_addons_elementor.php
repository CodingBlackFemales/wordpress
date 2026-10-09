<?php

return [
    'premium_addons_elementor' => [
        'label' => 'Premium Addons for Elementor',
        'caption_prefixes' => ['pa_'],
        'fields' => [
            'pa_megamenu_item_meta' => RevisionaryVisualCompareFieldProviders::field('Mega menu settings', 'Mega Menu'),
        ],
        'blacklist' => ['pa_mega_content_temp'],
    ],
];
