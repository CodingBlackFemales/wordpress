<?php

return [
    'beaver_builder' => [
        'label' => 'Beaver Builder',
        'caption_prefixes' => ['_fl_builder_', '_fl_theme_'],
        'fields' => [
            '_fl_builder_data' => RevisionaryVisualCompareFieldProviders::field('Layout data', 'Layout'),
            '_fl_builder_data_settings' => RevisionaryVisualCompareFieldProviders::field('Layout settings', 'Layout'),
            '_fl_builder_enabled' => RevisionaryVisualCompareFieldProviders::field('Builder enabled', 'Settings', RevisionaryVisualCompareFieldProviders::enabledDisabled()),
            '_fl_builder_template_type' => RevisionaryVisualCompareFieldProviders::field('Template type', 'Template'),
            '_fl_builder_template_global' => RevisionaryVisualCompareFieldProviders::field('Global template', 'Template', RevisionaryVisualCompareFieldProviders::yesNo()),
            '_fl_builder_template_dynamic_editing' => RevisionaryVisualCompareFieldProviders::field('Dynamic editing', 'Template', RevisionaryVisualCompareFieldProviders::enabledDisabled()),
            '_fl_theme_builder_locations' => RevisionaryVisualCompareFieldProviders::field('Theme layout locations', 'Theme Builder'),
            '_fl_theme_layout_hook' => RevisionaryVisualCompareFieldProviders::field('Theme layout hook', 'Theme Builder'),
            '_fl_theme_layout_type' => RevisionaryVisualCompareFieldProviders::field('Theme layout type', 'Theme Builder'),
        ],
        'blacklist' => [
            '_fl_builder_draft', '_fl_builder_draft_settings', '_fl_builder_history_data',
            '_fl_builder_history_position', '_fl_builder_layout', '_fl_builder_layout_post_id',
            '_fl_builder_site_editor_temp',
        ],
        'blacklist_prefixes' => ['_fl_builder_history_state_'],
    ],
];
