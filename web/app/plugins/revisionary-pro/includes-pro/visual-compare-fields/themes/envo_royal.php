<?php

return [
    'theme_envo_royal' => [
        'label' => 'Envo Royal',
        'fields' => [
            'envo_extra_hide_sidebar' => RevisionaryVisualCompareFieldProviders::field('Hide sidebar', 'Content visibility', ['on' => 'Hidden', '' => 'Visible']),
            'envo_extra_hide_title' => RevisionaryVisualCompareFieldProviders::field('Hide title', 'Content visibility', ['on' => 'Hidden', '' => 'Visible']),
        ],
    ],
];
