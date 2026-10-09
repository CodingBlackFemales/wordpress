<?php

return [
    'theme_lightning' => [
        'label' => 'Lightning',
        'fields' => [
            '_lightning_design_setting' => RevisionaryVisualCompareFieldProviders::field('Design settings', 'Layout', [
                'default' => 'Use common settings',
                'col-two' => '2 columns',
                'col-one-no-subsection' => '1 column',
                'col-one' => '1 column with sidebar element',
                'true' => 'Enabled',
                'false' => 'Disabled',
            ]),
        ],
    ],
];
