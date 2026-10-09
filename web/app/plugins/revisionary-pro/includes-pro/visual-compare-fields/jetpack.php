<?php

return [
    'jetpack' => [
        'label' => 'Jetpack',
        'caption_prefixes' => ['_jetpack_', 'jetpack_', 'videopress_', 'spay_'],
        'fields' => [
            '_jetpack_dont_email_post_to_subs' => RevisionaryVisualCompareFieldProviders::field('Do not email subscribers', 'Subscriptions', RevisionaryVisualCompareFieldProviders::yesNo()),
            '_jetpack_newsletter_access' => RevisionaryVisualCompareFieldProviders::field('Newsletter access', 'Subscriptions', [
                'everybody' => 'Everyone', 'subscribers' => 'Subscribers', 'paid_subscribers' => 'Paid subscribers',
            ]),
            'sharing_disabled' => RevisionaryVisualCompareFieldProviders::field('Disable sharing buttons', 'Sharing', RevisionaryVisualCompareFieldProviders::yesNo()),
            '_jetpack_featured_image' => RevisionaryVisualCompareFieldProviders::field('Jetpack featured image', 'Media'),
            '_jetpack_post_thumbnail' => RevisionaryVisualCompareFieldProviders::field('Jetpack post thumbnail', 'Media', [], 'image'),
            'videopress_poster_image' => RevisionaryVisualCompareFieldProviders::field('VideoPress poster image', 'VideoPress', [], 'image'),
            'videopress_privacy_setting' => RevisionaryVisualCompareFieldProviders::field('VideoPress privacy', 'VideoPress'),
            'videopress_rating' => RevisionaryVisualCompareFieldProviders::field('VideoPress rating', 'VideoPress'),
            'spay_cta' => RevisionaryVisualCompareFieldProviders::field('Payment button text', 'Payments'),
            'spay_currency' => RevisionaryVisualCompareFieldProviders::field('Currency', 'Payments'),
            'spay_multiple' => RevisionaryVisualCompareFieldProviders::field('Allow multiple purchases', 'Payments', RevisionaryVisualCompareFieldProviders::yesNo()),
            'spay_price' => RevisionaryVisualCompareFieldProviders::field('Price', 'Payments', [], 'number'),
            'jetpack_memberships_product_id' => RevisionaryVisualCompareFieldProviders::field('Membership product', 'Payments'),
        ],
        'blacklist' => [
            '_jetpack_amp_permalink', '_jetpack_post_was_ever_published',
            '_jetpack_blogging_prompt_key', '_jetpack_forms_webhook_error',
            '_jetpack_forms_webhook_response', '_wpcom_newsletter_stats_on_email_send',
            'publicize_results', '_rest_api_client_id', '_rest_api_published',
            'blogging_prompts_attribution', 'email_notification',
        ],
        'classifications' => [
            'spay_email' => 'sensitive',
            '_feedback_email' => 'sensitive',
            '_feedback_extra_fields' => 'sensitive',
            '_feedback_akismet_values' => 'sensitive',
        ],
    ],
];
