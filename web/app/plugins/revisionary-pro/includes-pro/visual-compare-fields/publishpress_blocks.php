<?php

return [
    'publishpress_blocks' => [
        'label' => 'PublishPress Blocks',
        'prefixes' => ['_advgb_'],
        'caption_prefixes' => ['_advgb_', 'advgb_blocks_'],
        'prefix_group' => 'Automatic insertion',
        'fields' => [
            'advgb_blocks_editor_width' => RevisionaryVisualCompareFieldProviders::field('Editor width', 'Editor settings'),
            'advgb_blocks_columns_visual_guide' => RevisionaryVisualCompareFieldProviders::field('Columns visual guide', 'Editor settings', RevisionaryVisualCompareFieldProviders::enabledDisabled()),
        ],
    ],
];
