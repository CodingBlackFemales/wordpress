<?php
return [
    'flamingo' => [
        'label' => 'Flamingo',
        'post_types' => ['flamingo_contact', 'flamingo_inbound'],
        'classifications' => array_merge(
            array_fill_keys(['_hash', '_last_contacted', '_spam_meta_time', '_submission_status'], 'internal'),
            array_fill_keys(['_email', '_from', '_from_email', '_from_name', '_name', '_subject', '_fields', '_meta', '_props', '_recaptcha', '_consent', '_akismet', '_spam_log'], 'sensitive')
        ),
    ],
];
