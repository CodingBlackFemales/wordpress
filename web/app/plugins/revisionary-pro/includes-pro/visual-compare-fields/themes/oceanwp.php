<?php

return [
    'theme_oceanwp' => [
        'label' => 'OceanWP',
        'fields' => [
            'ocean_post_layout' => RevisionaryVisualCompareFieldProviders::field('Content layout', 'Layout', ['default' => 'Default', 'right-sidebar' => 'Right sidebar', 'left-sidebar' => 'Left sidebar', 'full-width' => 'Full width', 'full-screen' => 'Full screen', 'both-sidebars' => 'Both sidebars']),
            'ocean_both_sidebars_style' => RevisionaryVisualCompareFieldProviders::field('Both sidebars style', 'Layout', ['scs-style' => 'Sidebar / Content / Sidebar', 'ssc-style' => 'Sidebar / Sidebar / Content', 'css-style' => 'Content / Sidebar / Sidebar']),
            'ocean_link_format' => RevisionaryVisualCompareFieldProviders::field('Link URL', 'Post format'),
            'ocean_link_format_target' => RevisionaryVisualCompareFieldProviders::field('Link target', 'Post format', ['self' => 'Same window', 'blank' => 'New window']),
            'ocean_quote_format' => RevisionaryVisualCompareFieldProviders::field('Quote', 'Post format'),
            'ocean_quote_format_link' => RevisionaryVisualCompareFieldProviders::field('Quote link', 'Post format'),
            'ocean_post_video_embed' => RevisionaryVisualCompareFieldProviders::field('Video embed code', 'Post format'),
            'ocean_post_self_hosted_media' => RevisionaryVisualCompareFieldProviders::field('Self-hosted media URL', 'Post format'),
            'ocean_post_self_hosted_shortcode' => RevisionaryVisualCompareFieldProviders::field('Self-hosted media shortcode', 'Post format'),
            'ocean_post_oembed' => RevisionaryVisualCompareFieldProviders::field('oEmbed URL', 'Post format'),
            'ocean_gallery_id' => RevisionaryVisualCompareFieldProviders::field('Gallery images', 'Post format'),
            'ocean_gallery_link_images' => RevisionaryVisualCompareFieldProviders::field('Link gallery images', 'Post format', RevisionaryVisualCompareFieldProviders::yesNo()),
        ],
        'classifications' => ['_ocean_meta_gallery_id' => 'internal'],
    ],
];
