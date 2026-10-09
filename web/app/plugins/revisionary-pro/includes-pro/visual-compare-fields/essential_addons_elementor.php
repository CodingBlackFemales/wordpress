<?php

return [
    'essential_addons_elementor' => [
        'label' => 'Essential Addons for Elementor',
        'caption_prefixes' => ['_eael_'],
        'fields' => [
            '_eael_custom_js' => RevisionaryVisualCompareFieldProviders::field('Custom JavaScript', 'Custom code'),
            '_eael_checkout_fields_settings' => RevisionaryVisualCompareFieldProviders::field('Checkout field settings', 'WooCommerce'),
        ],
        'blacklist' => ['_eael_post_view_count', '_eael_widget_elements'],
    ],
];
