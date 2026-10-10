<?php

return [
    'contact_form_7' => [
        'label' => 'Contact Form 7',
        'post_types' => ['wpcf7_contact_form'],
        'fields' => [
            '_form' => RevisionaryVisualCompareFieldProviders::field('Form template', 'Form'),
            '_mail' => RevisionaryVisualCompareFieldProviders::field('Primary mail settings', 'Mail'),
            '_mail_2' => RevisionaryVisualCompareFieldProviders::field('Secondary mail settings', 'Mail'),
            '_messages' => RevisionaryVisualCompareFieldProviders::field('Messages', 'Messages'),
            '_additional_settings' => RevisionaryVisualCompareFieldProviders::field('Additional settings', 'Settings'),
            '_locale' => RevisionaryVisualCompareFieldProviders::field('Locale', 'Settings'),
        ],
        'blacklist' => ['_hash', '_config_errors', '_config_validation', '_old_cf7_unit_id', '_flamingo'],
    ],
];
