<?php

return [
    'taxopress' => [
        'label' => 'TaxoPress',
        'caption_prefixes' => ['_taxopress_'],
        'fields' => [
            '_exclude_autolinks' => RevisionaryVisualCompareFieldProviders::field('Exclude from automatic links', 'Automatic Links', RevisionaryVisualCompareFieldProviders::yesNo()),
            '_taxopress_autolinks_status' => RevisionaryVisualCompareFieldProviders::field('Automatic Links status', 'Automatic Links', RevisionaryVisualCompareFieldProviders::enabledDisabled()),
        ],
        'blacklist' => [
            '_taxopress_autotermed', '_taxopress_log_action', '_taxopress_log_component',
            '_taxopress_log_option_id', '_taxopress_log_options', '_taxopress_log_post_id',
            '_taxopress_log_post_type', '_taxopress_log_status', '_taxopress_log_status_message',
            '_taxopress_log_taxonomy', '_taxopress_log_terms',
        ],
    ],
];
