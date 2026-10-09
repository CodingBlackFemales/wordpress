<?php

return [
    'theme_inspiro' => [
        'label' => 'Inspiro',
        'fields' => [
            'inspiro_hide_title' => RevisionaryVisualCompareFieldProviders::field('Hide title', 'Content visibility', RevisionaryVisualCompareFieldProviders::yesNo()),
            'inspiro_hide_featured_image' => RevisionaryVisualCompareFieldProviders::field('Hide featured image', 'Content visibility', RevisionaryVisualCompareFieldProviders::yesNo()),
        ],
        'classifications' => ['_inspiro_starter_content' => 'internal'],
    ],
];
