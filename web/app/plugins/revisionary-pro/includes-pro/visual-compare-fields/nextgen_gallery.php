<?php

return [
    'nextgen_gallery' => [
        'label' => 'NextGEN Gallery',
        'post_types' => ['ngg_album', 'ngg_gallery', 'ngg_pictures'],
        'fields' => [
            'pricelist_id' => RevisionaryVisualCompareFieldProviders::field('Pricelist', 'Ecommerce'),
        ],
        'blacklist' => ['_ngg_image_id'],
    ],
];
