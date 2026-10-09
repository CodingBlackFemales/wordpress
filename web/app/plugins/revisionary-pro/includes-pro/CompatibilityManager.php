<?php
namespace PublishPress\Revisions;

class CompatibilityManager
{
    public function __construct()
    {
        add_action('init', [$this, 'registerIntegrations'], 5);
        add_action('init', [$this, 'loadEnabledIntegrations'], 10);
        add_action('wp_ajax_pp_toggle_integration', [$this, 'handleToggleIntegration']);
    }

    public function registerIntegrations()
    {
        $integrations = [
            [
                'id' => 'acf_compatibility',
                'title' => esc_html__('Advanced Custom Fields', 'revisionary'),
                'description' => esc_html__('Compatibility with ACF custom fields.', 'revisionary'),
                'icon_class' => 'acf',
                'categories' => ['all', 'fields'],
                'features' => [
                    esc_html__('Store custom fields with revisions', 'revisionary'),
                    esc_html__('Display custom field changes', 'revisionary'),
                    esc_html__('Update custom fields on revision publication', 'revisionary'),
                ],
                'enabled' => false,
                'available' => function_exists('acf'),
                'learn_more_url' => 'https://publishpress.com/knowledge-base/acf-publishpress-revisions/'
            ],
            [
                'id' => 'acfe_compatibility',
                'title' => esc_html__('ACF Extended', 'revisionary'),
                'description' => esc_html__('Support ACFE data model.', 'revisionary'),
                'icon_class' => 'acf',
                'categories' => ['all', 'fields'],
                'features' => [
                    esc_html__('Support ACFE single_meta data type', 'revisionary'),
                    esc_html__('Correct field display on Compare screen', 'revisionary'),
                ],
                'enabled' => false,
                'available' => class_exists('ACFE'),
                'learn_more_url' => 'https://publishpress.com/knowledge-base/acfe-publishpress-revisions/'
            ],
            [
                'id' => 'beaver_compatibility',
                'title' => esc_html__('Beaver Builder', 'revisionary'),
                'description' => esc_html__('Integration with Beaver Builder\'s front end editor.', 'revisionary'),
                'icon_class' => 'beaver-builder',
                'categories' => ['all', 'builder'],
                'features' => [
                    esc_html__('Front end Revision submission', 'revisionary'),
                    esc_html__('Front end Revision editing', 'revisionary'),
                    esc_html__('Revision preview and redirects', 'revisionary'),
                ],
                'enabled' => false,
                'available' => defined('FL_BUILDER_VERSION'),
                'learn_more_url' => 'https://publishpress.com/knowledge-base/beaver-builder-publishpress-revisions/'
            ],
            [
                'id' => 'divi_compatibility',
                'title' => esc_html__('Divi', 'revisionary'),
                'description' => esc_html__('Integration with Divi 4 Theme and Builder', 'revisionary'),
                'icon_class' => 'divi',
                'categories' => ['all', 'builder'],
                'features' => [
                    esc_html__('Front end Revision submission', 'revisionary'),
                    esc_html__('Front end Revision editing', 'revisionary'),
                    esc_html__('Revision preview and redirects', 'revisionary'),
                ],
                'enabled' => false,
                'available' => defined('ET_BUILDER_PLUGIN_VERSION') || (false !== stripos(get_template(), 'divi')),
                'learn_more_url' => 'https://publishpress.com/knowledge-base/divi-publishpress-revisions/',
            ],
            [
                'id' => 'elementor_compatibility',
                'title' => esc_html__('Elementor', 'revisionary'),
                'description' => esc_html__('Integration with Elementor\'s front end editor.', 'revisionary'),
                'icon_class' => 'elementor',
                'categories' => ['all', 'builder'],
                'features' => [
                    esc_html__('Front end Revision submission', 'revisionary'),
                    esc_html__('Front end Revision editing', 'revisionary'),
                    esc_html__('Revision preview and redirects', 'revisionary'),
                ],
                'enabled' => false,
                'available' => defined('ELEMENTOR_VERSION'),
                'learn_more_url' => 'https://publishpress.com/knowledge-base/elementor-publishpress-revisions/',
            ],
            [
                'id' => 'nitropack_compatibility',
                'title' => esc_html__('Nitro Pack', 'revisionary'),
                'description' => esc_html__('Compatibility with Nitro Pack cache.', 'revisionary'),
                'icon_class' => 'nitropack',
                'categories' => ['all', 'cache'],
                'features' => [
                    esc_html__('Clear cache on revision publication', 'revisionary'),
                    esc_html__('Support immediate and scheduled publication', 'revisionary'),
                ],
                'enabled' => false,
                'available' => defined('NITROPACK_VERSION'),
                'learn_more_url' => 'https://publishpress.com/knowledge-base/nitropack-publishpress-revisions/'
            ],
            [
                'id' => 'pods_compatibility',
                'title' => esc_html__('Pods', 'revisionary'),
                'description' => esc_html__('Compatibility with Pods custom fields.', 'revisionary'),
                'icon_class' => 'pods',
                'categories' => ['all', 'fields'],
                'features' => [
                    esc_html__('Store custom fields with revisions', 'revisionary'),
                    esc_html__('Display custom field changes', 'revisionary'),
                    esc_html__('Update custom fields on revision publication', 'revisionary'),
                ],
                'enabled' => false,
                'available' => defined('PODS_VERSION'),
                'learn_more_url' => 'https://publishpress.com/knowledge-base/pods-publishpress-revisions/'
            ],
            [
                'id' => 'polylang_compatibility',
                'title' => esc_html__('Polylang', 'revisionary'),
                'description' => esc_html__('Compatibility with Polylang translation.', 'revisionary'),
                'icon_class' => 'polylang',
                'categories' => ['all', 'multilingual'],
                'features' => [
                    esc_html__('Revisions carry over Polylang data', 'revisionary'),
                    esc_html__('Translation retained on Revision approval', 'revisionary'),
                ],
                'enabled' => false,
                'available' => defined('POLYLANG_VERSION'),
                'learn_more_url' => 'https://publishpress.com/knowledge-base/polylang-publishpress-revisions/'
            ],
            [
                'id' => 'planner_compatibility',
                'title' => esc_html__('PublishPress Planner', 'revisionary'),
                'description' => esc_html__('PublishPress Planner Integration.', 'revisionary'),
                'icon_class' => 'planner',
                'categories' => ['all'],
                'features' => [
                    esc_html__('Planner Notifications for revision actions', 'revisionary'),
                    esc_html__('Revision schedule shown in Calendar', 'revisionary'),
                ],
                'enabled' => false,
                'available' => defined('PUBLISHPRESS_VERSION'),
                'learn_more_url' => 'https://publishpress.com/knowledge-base/planner-publishpress-revisions/'
            ],
            [
                'id' => 'woocommerce_compatibility',
                'title' => esc_html__('WooCommerce', 'revisionary'),
                'description' => esc_html__('Advanced permissions for products, orders, and customer data.', 'revisionary'),
                'icon_class' => 'woocommerce',
                'categories' => ['all', 'ecommerce'],
                'features' => [
                    esc_html__('Product permissions', 'revisionary'),
                    esc_html__('Order management controls', 'revisionary'),
                    esc_html__('Customer data access', 'revisionary')
                ],
                'enabled' => false,
                'available' => class_exists('WooCommerce'),
                'learn_more_url' => 'https://publishpress.com/knowledge-base/woocommerce-publishpress-permissions/'
            ],
            [
                'id' => 'wpml_compatibility',
                'title' => esc_html__('WPML', 'revisionary'),
                'description' => esc_html__('Multilingual revisioning with WPML.', 'revisionary'),
                'icon_class' => 'wpml',
                'categories' => ['all', 'multilingual'],
                'features' => [
                    esc_html__('Language-specific revisions', 'revisionary'),
                    esc_html__('Translation Management integration', 'revisionary')
                ],
                'enabled' => false,
                'available' => defined('ICL_SITEPRESS_VERSION'),
                'learn_more_url' => 'https://publishpress.com/knowledge-base/wpml-publishpress-revisions/'
            ],
            [
                'id' => 'yoast_seo_compatibility',
                'title' => esc_html__('Yoast SEO', 'revisionary'),
                'description' => esc_html__('.', 'revisionary'),
                'icon_class' => 'yoast',
                'categories' => ['all', 'seo'],
                'features' => [
                    esc_html__('Prevent indexing of revisions', 'revisionary'),
                    esc_html__('Compatibility for Yoast SEO + Elementor', 'revisionary'),
                    esc_html__('Compare revisions to Yoast SEO fields', 'revisionary'),
                ],
                'enabled' => false,
                'available' => defined('WPSEO_VERSION'),
                'learn_more_url' => 'https://publishpress.com/knowledge-base/yoast-seo-publishpress-revisions/'
            ],
        ];

        ksort($integrations);

        foreach ($integrations as $integration_id => $args) {
            CompatibilityRegistry::register($integration_id, $args);
        }

        // Allow other plugins to register integrations
        do_action('revisionary_register_integrations');
    }

    public function loadEnabledIntegrations()
    {
        $enabled_integrations = get_option('revisionary_enabled_integrations', []);

        foreach ($enabled_integrations as $integration_id => $enabled) {
            if ($enabled && CompatibilityRegistry::isAvailable($integration_id)) {
                CompatibilityRegistry::enableIntegration($integration_id);
            }
        }
    }

    public function handleToggleIntegration()
    {
        check_ajax_referer('pp_toggle_integration', 'nonce');

        if (!current_user_can('pp_manage_settings')) {
            wp_die(esc_html__('Insufficient permissions', 'presspermit-pro'));
        }

        $integration_id = (isset($_POST['integration_id'])) ? sanitize_text_field(wp_unslash($_POST['integration_id'])) : 0;
        $enabled = !empty($_POST['enabled']);

        if ($enabled) {
            $success = CompatibilityRegistry::enableIntegration($integration_id);
        } else {
            $success = CompatibilityRegistry::disableIntegration($integration_id);
        }

        wp_send_json_success(['enabled' => $enabled, 'success' => $success]);
    }

    /**
     * Get available integrations for admin display
     */
    public function getAvailableIntegrations()
    {
        return CompatibilityRegistry::getIntegrations();
    }

    /**
     * Check if a specific integration is enabled and available
     */
    public function isIntegrationActive($integration_id)
    {
        return CompatibilityRegistry::isEnabled($integration_id) &&
            CompatibilityRegistry::isAvailable($integration_id);
    }

    /**
     * Get integration statistics for admin dashboard
     */
    public function getIntegrationStats()
    {
        $integrations = CompatibilityRegistry::getIntegrations();
        $stats = [
            'total' => count($integrations),
            'enabled' => 0,
            'available' => 0,
            'active' => 0
        ];

        foreach ($integrations as $integration) {
            if ($integration['available']) {
                $stats['available']++;
            }
            if ($integration['enabled']) {
                $stats['enabled']++;
            }
            if ($integration['enabled'] && $integration['available']) {
                $stats['active']++;
            }
        }

        return $stats;
    }
}
