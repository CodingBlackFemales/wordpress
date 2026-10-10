<?php

return [
    'theme_blocksy' => [
        'label' => 'Blocksy',
        'fields' => [
            'blocksy_post_meta_options' => RevisionaryVisualCompareFieldProviders::field('Post options', 'Page settings', RevisionaryVisualCompareFieldProviders::enabledDisabled()),
            'blocksy_media_video' => RevisionaryVisualCompareFieldProviders::field('Featured video', 'Featured media'),
        ],
    ],
];
