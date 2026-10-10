<?php

return [
    'royal_addons_elementor' => [
        'label' => 'Royal Addons for Elementor',
        'caption_prefixes' => ['wpr_', '_wpr_'],
        'fields' => [
            'wpr_featured_video_source' => RevisionaryVisualCompareFieldProviders::field('Featured video source', 'Featured video'),
            'wpr_featured_video_url' => RevisionaryVisualCompareFieldProviders::field('Featured video URL', 'Featured video'),
            'wpr_featured_video_id' => RevisionaryVisualCompareFieldProviders::field('Featured video', 'Featured video'),
            'wpr_secondary_image_id' => RevisionaryVisualCompareFieldProviders::field('Secondary image', 'Media', [], 'image'),
            'wpr_header_show_on_canvas' => RevisionaryVisualCompareFieldProviders::field('Show header on canvas', 'Canvas', RevisionaryVisualCompareFieldProviders::yesNo()),
            'wpr_footer_show_on_canvas' => RevisionaryVisualCompareFieldProviders::field('Show footer on canvas', 'Canvas', RevisionaryVisualCompareFieldProviders::yesNo()),
            'wpr-mega-menu-item' => RevisionaryVisualCompareFieldProviders::field('Mega menu item', 'Mega Menu'),
            'wpr-mega-menu-settings' => RevisionaryVisualCompareFieldProviders::field('Mega menu settings', 'Mega Menu'),
        ],
        'blacklist' => [
            '_post_like_count', '_post_like_modified', '_elementor_controls_usage',
            '_wpr_demo_import_item', 'wpr_form_id', 'wpr_form_name', 'wpr_form_page',
            'wpr_form_page_id', 'wpr_user_agent',
        ],
        'classifications' => [
            '_wpr_submission_action_secret' => 'sensitive',
            'wpr_user_ip' => 'sensitive',
            '_user_IP' => 'sensitive',
        ],
    ],
];
