<?php

return [
    'publishpress_authors' => [
        'label' => 'PublishPress Authors',
        'caption_prefixes' => ['ppma_', 'cap-'],
        'fields' => [
            'ppma_disable_author_box' => RevisionaryVisualCompareFieldProviders::field('Disable author box', 'Author box', RevisionaryVisualCompareFieldProviders::yesNo()),
            'ppma_selected_author_box' => RevisionaryVisualCompareFieldProviders::field('Selected author box', 'Author box'),
            'cap-display_name' => RevisionaryVisualCompareFieldProviders::field('Display name', 'Guest author'),
            'cap-first_name' => RevisionaryVisualCompareFieldProviders::field('First name', 'Guest author'),
            'cap-last_name' => RevisionaryVisualCompareFieldProviders::field('Last name', 'Guest author'),
            'cap-description' => RevisionaryVisualCompareFieldProviders::field('Biographical information', 'Guest author'),
            'cap-website' => RevisionaryVisualCompareFieldProviders::field('Website', 'Guest author'),
        ],
        'blacklist' => ['ppma_post_migrated', 'ppmacf_schema_property', 'ppmacf_show_in_rest', 'ppmacf_social_profile', 'ppmacf_type'],
        'classifications' => ['cap-user_email' => 'sensitive'],
    ],
];
