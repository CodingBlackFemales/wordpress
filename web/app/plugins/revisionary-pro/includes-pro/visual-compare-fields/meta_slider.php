<?php

return [
    'meta_slider' => [
        'label' => 'MetaSlider',
        'prefixes' => ['ml-slider_', '_meta_slider_slide_'],
        'caption_prefixes' => ['ml-slider_', '_meta_slider_slide_'],
        'prefix_group' => 'Slide settings',
        'fields' => [
            'metaslider_slideshow_theme' => RevisionaryVisualCompareFieldProviders::field('Slideshow theme', 'Slideshow settings'),
        ],
        'blacklist' => ['metaslider_copy_of'],
    ],
];
