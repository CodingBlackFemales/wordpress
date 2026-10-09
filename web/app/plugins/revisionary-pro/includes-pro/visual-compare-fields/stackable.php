<?php

return [
    'stackable' => [
        'label' => 'Stackable',
        'post_types' => ['stackable_temp_post'],
        'caption_prefixes' => ['stk_'],
        'fields' => [
            'stk_block_name' => RevisionaryVisualCompareFieldProviders::field('Block name', 'Custom block style'),
            'stk_block_title' => RevisionaryVisualCompareFieldProviders::field('Block title', 'Custom block style'),
            'stk_style_slug' => RevisionaryVisualCompareFieldProviders::field('Style slug', 'Custom block style'),
        ],
        'blacklist' => ['stackable_optimized_css', 'stackable_optimized_css_raw', 'stackable_css_hash'],
    ],
];
