<?php

return [
    'page_links_to' => [
        'label' => 'Page Links To',
        'fields' => [
            '_links_to' => RevisionaryVisualCompareFieldProviders::field('Custom URL', 'Link settings'),
            '_links_to_target' => RevisionaryVisualCompareFieldProviders::field('Link target', 'Link settings', [
                '_self' => 'Current window', '_blank' => 'New window',
            ]),
        ],
        'blacklist' => ['_links_to_type'],
    ],
];
