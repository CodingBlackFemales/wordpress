<?php

return [
    'rank_math' => [
        'label' => 'Rank Math SEO',
        'prefixes' => ['rank_math_primary_', 'rank_math_schema_'],
        'caption_prefixes' => ['rank_math_'],
        'prefix_groups' => [
            'rank_math_primary_' => 'Advanced',
            'rank_math_schema_' => 'Schema',
        ],
        'fields' => [
            'rank_math_title' => RevisionaryVisualCompareFieldProviders::field('SEO title', 'General'),
            'rank_math_description' => RevisionaryVisualCompareFieldProviders::field('SEO description', 'General'),
            'rank_math_focus_keyword' => RevisionaryVisualCompareFieldProviders::field('Focus keyword', 'General'),
            'rank_math_robots' => RevisionaryVisualCompareFieldProviders::field('Robots meta', 'Advanced'),
            'rank_math_advanced_robots' => RevisionaryVisualCompareFieldProviders::field('Advanced robots meta', 'Advanced'),
            'rank_math_canonical_url' => RevisionaryVisualCompareFieldProviders::field('Canonical URL', 'Advanced'),
            'rank_math_breadcrumb_title' => RevisionaryVisualCompareFieldProviders::field('Breadcrumb title', 'Advanced'),
            'rank_math_pillar_content' => RevisionaryVisualCompareFieldProviders::field('Pillar content', 'Advanced', ['on' => 'Yes', 'off' => 'No', '1' => 'Yes', '0' => 'No']),
            'rank_math_facebook_title' => RevisionaryVisualCompareFieldProviders::field('Facebook title', 'Social'),
            'rank_math_facebook_description' => RevisionaryVisualCompareFieldProviders::field('Facebook description', 'Social'),
            'rank_math_facebook_image' => RevisionaryVisualCompareFieldProviders::field('Facebook image', 'Social'),
            'rank_math_facebook_image_id' => RevisionaryVisualCompareFieldProviders::field('Facebook image', 'Social', [], 'image'),
            'rank_math_twitter_title' => RevisionaryVisualCompareFieldProviders::field('X title', 'Social'),
            'rank_math_twitter_description' => RevisionaryVisualCompareFieldProviders::field('X description', 'Social'),
            'rank_math_twitter_image' => RevisionaryVisualCompareFieldProviders::field('X image', 'Social'),
            'rank_math_twitter_image_id' => RevisionaryVisualCompareFieldProviders::field('X image', 'Social', [], 'image'),
        ],
        'blacklist' => [
            'rank_math_seo_score', 'rank_math_internal_links_processed',
            'rank_math_analytic_object_id', 'rank_math_dont_show_seo_score',
            'rank_math_ca_keyword', 'rank_math_news_sitemap_robots',
        ],
    ],
];
