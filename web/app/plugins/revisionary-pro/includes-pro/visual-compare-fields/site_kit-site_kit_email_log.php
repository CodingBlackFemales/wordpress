<?php

return [
    'site_kit' => [
        'label' => 'Site Kit by Google',
        'prefixes' => ['googlesitekit_rrm_'],
        'caption_prefixes' => ['googlesitekit_rrm_'],
        'prefix_group' => 'Reader Revenue Manager',
        'prefix_label_suffixes' => [':productID' => 'Product ID'],
        'fields' => [],
    ],
    'site_kit_email_log' => [
        'label' => 'Site Kit by Google',
        'post_types' => ['googlesitekit_email'],
        'fields' => [],
        'blacklist' => [
            '_report_frequency', '_batch_id', '_send_attempts', '_error_details',
            '_report_reference_dates', '_site_id', '_template_type', '_admin_notified',
        ],
    ],
];
