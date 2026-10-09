<?php

return [
    'publishpress_future' => [
        'label' => 'PublishPress Future',
        'caption_prefixes' => ['_expiration-date-'],
        'fields' => [
            '_expiration-date' => RevisionaryVisualCompareFieldProviders::field('Expiration date', 'Expiration'),
            '_expiration-date-status' => RevisionaryVisualCompareFieldProviders::field('Expiration enabled', 'Expiration', RevisionaryVisualCompareFieldProviders::enabledDisabled()),
            '_expiration-date-type' => RevisionaryVisualCompareFieldProviders::field('Expiration action', 'Expiration', [
                'draft' => 'Move to Draft', 'delete' => 'Delete', 'trash' => 'Move to Trash',
                'private' => 'Change to Private', 'stick' => 'Stick to blog', 'unstick' => 'Unstick from blog',
                'category' => 'Replace taxonomy terms', 'category-add' => 'Add taxonomy terms',
                'category-remove' => 'Remove taxonomy terms',
            ]),
            '_expiration-date-post-status' => RevisionaryVisualCompareFieldProviders::field('Expiration post status', 'Expiration'),
            '_expiration-date-categories' => RevisionaryVisualCompareFieldProviders::field('Expiration taxonomy terms', 'Expiration'),
            '_expiration-date-taxonomy' => RevisionaryVisualCompareFieldProviders::field('Expiration taxonomy', 'Expiration'),
        ],
        'blacklist' => ['_expiration-date-options', 'expiration_log'],
        'blacklist_prefixes' => ['_pp_workflow_debug_', '_pp_workflow_manually_triggered_', '_pp_workflow_step_'],
    ],
];
