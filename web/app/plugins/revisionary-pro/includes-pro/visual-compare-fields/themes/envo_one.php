<?php

return [
    'theme_envo_one' => [
        'label' => 'Envo One',
        'fields' => [
            'envo_hide_sidebar' => RevisionaryVisualCompareFieldProviders::field('Hide sidebar', 'Content visibility', ['on' => 'Hidden', '' => 'Visible']),
            'envo_hide_title' => RevisionaryVisualCompareFieldProviders::field('Hide title', 'Content visibility', ['on' => 'Hidden', '' => 'Visible']),
        ],
    ],
];
