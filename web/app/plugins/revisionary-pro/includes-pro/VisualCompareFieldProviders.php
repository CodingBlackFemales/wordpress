<?php

/**
 * Declarative field definitions for third-party plugin post metadata.
 */
class RevisionaryVisualCompareFieldProviders {
    public static function providers() {
        static $providers = null;

        if (null !== $providers) {
            return $providers;
        }

        $loaders = [
            [
                'plugin' => 'elementor/elementor.php',
                'marker_type' => 'constant',
                'marker' => 'ELEMENTOR_VERSION',
                'files' => ['elementor.php'],
            ],
            [
                'plugin' => 'contact-form-7/wp-contact-form-7.php',
                'marker_type' => 'constant',
                'marker' => 'WPCF7_VERSION',
                'files' => ['contact_form_7.php'],
            ],
            [
                'plugin' => 'google-site-kit/google-site-kit.php',
                'marker_type' => 'constant',
                'marker' => 'GOOGLESITEKIT_VERSION',
                'files' => ['site_kit-site_kit_email_log.php'],
            ],
            [
                'plugin' => 'seo-by-rank-math/rank-math.php',
                'marker_type' => 'constant',
                'marker' => 'RANK_MATH_VERSION',
                'files' => ['rank_math.php'],
            ],
            [
                'plugin' => 'jetpack/jetpack.php',
                'marker_type' => 'constant',
                'marker' => 'JETPACK__VERSION',
                'files' => ['jetpack.php'],
            ],
            [
                'plugin' => 'essential-addons-for-elementor-lite/essential_adons_elementor.php',
                'marker_type' => 'constant',
                'marker' => 'EAEL_PLUGIN_VERSION',
                'files' => ['essential_addons_elementor.php'],
            ],
            [
                'plugin' => 'stackable-ultimate-gutenberg-blocks/plugin.php',
                'marker_type' => 'constant',
                'marker' => 'STACKABLE_VERSION',
                'files' => ['stackable.php'],
            ],
            [
                'plugin' => 'ultimate-addons-for-gutenberg/ultimate-addons-for-gutenberg.php',
                'marker_type' => 'constant',
                'marker' => 'UAGB_VER',
                'files' => ['spectra.php'],
            ],
            [
                'plugin' => 'premium-addons-for-elementor/premium-addons-for-elementor.php',
                'marker_type' => 'constant',
                'marker' => 'PREMIUM_ADDONS_VERSION',
                'files' => ['premium_addons_elementor.php'],
            ],
            [
                'plugin' => 'the-events-calendar/the-events-calendar.php',
                'marker_type' => 'class',
                'marker' => 'Tribe__Events__Main',
                'files' => ['the_events_calendar.php'],
            ],
            [
                'plugin' => 'page-links-to/page-links-to.php',
                'marker_type' => 'class',
                'marker' => 'CWS_PageLinksTo',
                'files' => ['page_links_to.php'],
            ],
            [
                'plugin' => 'simple-tags/simple-tags.php',
                'marker_type' => 'constant',
                'marker' => 'STAGS_VERSION',
                'files' => ['taxopress.php'],
            ],
            [
                'plugin' => 'publishpress/publishpress.php',
                'marker_type' => 'constant',
                'marker' => 'PUBLISHPRESS_VERSION',
                'files' => ['publishpress_planner.php'],
            ],
            [
                'plugin' => 'publishpress-authors/publishpress-authors.php',
                'marker_type' => 'constant',
                'marker' => 'PP_AUTHORS_VERSION',
                'files' => ['publishpress_authors.php'],
            ],
            [
                'plugin' => 'post-expirator/post-expirator.php',
                'marker_type' => 'constant',
                'marker' => 'PUBLISHPRESS_FUTURE_VERSION',
                'files' => ['publishpress_future.php'],
            ],
            [
                'plugin' => 'advanced-gutenberg/advanced-gutenberg.php',
                'marker_type' => 'constant',
                'marker' => 'ADVANCED_GUTENBERG_VERSION',
                'files' => ['publishpress_blocks.php'],
            ],
            [
                'plugin' => 'ml-slider/ml-slider.php',
                'marker_type' => 'constant',
                'marker' => 'METASLIDER_VERSION',
                'files' => ['meta_slider.php'],
            ],
            [
                'plugin' => 'nextgen-gallery/nggallery.php',
                'marker_type' => 'constant',
                'marker' => 'NGG_PLUGIN_VERSION',
                'files' => ['nextgen_gallery.php'],
            ],
            [
                'plugin' => 'royal-elementor-addons/wpr-addons.php',
                'marker_type' => 'constant',
                'marker' => 'WPR_ADDONS_VERSION',
                'files' => ['royal_addons_elementor.php'],
            ],
            [
                'plugin' => 'beaver-builder-lite-version/fl-builder.php',
                'marker_type' => 'constant',
                'marker' => 'FL_BUILDER_VERSION',
                'files' => ['beaver_builder.php'],
            ],
            ['plugin' => 'all-in-one-seo-pack/all_in_one_seo_pack.php', 'marker_type' => 'constant', 'marker' => 'AIOSEO_VERSION', 'files' => ['all_in_one_seo.php']],
            ['plugin' => 'fluentform/fluentform.php', 'marker_type' => 'constant', 'marker' => 'FLUENTFORM_VERSION', 'files' => ['fluent_forms.php']],
            ['plugin' => 'forminator/forminator.php', 'marker_type' => 'constant', 'marker' => 'FORMINATOR_VERSION', 'files' => ['forminator.php']],
            ['plugin' => 'polylang/polylang.php', 'marker_type' => 'constant', 'marker' => 'POLYLANG_VERSION', 'files' => ['polylang.php']],
            ['plugin' => 'header-footer-elementor/header-footer-elementor.php', 'marker_type' => 'constant', 'marker' => 'HFE_VER', 'files' => ['ultimate_addons_elementor.php']],
            ['plugin' => 'akismet/akismet.php', 'marker_type' => 'constant', 'marker' => 'AKISMET_VERSION', 'files' => ['akismet.php']],
            ['plugin' => 'wpforms-lite/wpforms.php', 'marker_type' => 'constant', 'marker' => 'WPFORMS_VERSION', 'files' => ['wpforms.php']],
            ['plugin' => 'duplicate-post/duplicate-post.php', 'marker_type' => 'constant', 'marker' => 'DUPLICATE_POST_CURRENT_VERSION', 'files' => ['yoast_duplicate_post.php']],
            ['plugin' => 'insert-headers-and-footers/ihaf.php', 'marker_type' => 'constant', 'marker' => 'WPCODE_VERSION', 'files' => ['wpcode.php']],
            ['plugin' => 'astra-sites/astra-sites.php', 'marker_type' => 'constant', 'marker' => 'ASTRA_SITES_VER', 'files' => ['starter_templates.php']],
            ['plugin' => 'sg-cachepress/sg-cachepress.php', 'marker_type' => 'constant', 'marker' => 'SiteGround_Optimizer\\VERSION', 'files' => ['speed_optimizer.php']],
            ['plugin' => 'complianz-gdpr/complianz-gpdr.php', 'marker_type' => 'constant', 'marker' => 'CMPLZ_VERSION', 'files' => ['complianz.php']],
            ['plugin' => 'sg-ai-studio/sg-ai-studio.php', 'marker_type' => 'constant', 'marker' => 'SiteGround_AI\\VERSION', 'files' => ['siteground_ai.php']],
            ['plugin' => 'optinmonster/optin-monster-wp-api.php', 'marker_type' => 'constant', 'marker' => 'OMAPI_VERSION', 'files' => ['optinmonster.php']],
            ['plugin' => 'wp-optimize/wp-optimize.php', 'marker_type' => 'constant', 'marker' => 'WPO_VERSION', 'files' => ['wp_optimize.php']],
            ['plugin' => 'worker/init.php', 'marker_type' => 'constant', 'marker' => 'MWP_WORKER_VERSION', 'files' => ['managewp_worker.php']],
            ['plugin' => 'all-in-one-wp-security-and-firewall/wp-security.php', 'marker_type' => 'constant', 'marker' => 'AIO_WP_SECURITY_VERSION', 'files' => ['all_in_one_security.php']],
            ['plugin' => 'one-click-demo-import/one-click-demo-import.php', 'marker_type' => 'constant', 'marker' => 'PT_OCDI_VERSION', 'files' => ['one_click_demo_import.php']],
            ['plugin' => 'imagify/imagify.php', 'marker_type' => 'constant', 'marker' => 'IMAGIFY_VERSION', 'files' => ['imagify.php']],
            ['plugin' => 'wp-smushit/wp-smush.php', 'marker_type' => 'constant', 'marker' => 'WP_SMUSH_VERSION', 'files' => ['smush.php']],
            ['plugin' => 'w3-total-cache/w3-total-cache.php', 'marker_type' => 'constant', 'marker' => 'W3TC_VERSION', 'files' => ['w3_total_cache.php']],
            ['plugin' => 'flamingo/flamingo.php', 'marker_type' => 'constant', 'marker' => 'FLAMINGO_VERSION', 'files' => ['flamingo.php']],
            ['plugin' => 'woocommerce-payments/woocommerce-payments.php', 'marker_type' => 'constant', 'marker' => 'WCPAY_VERSION_NUMBER', 'files' => ['woocommerce_payments.php']],
            ['plugin' => 'woocommerce-paypal-payments/woocommerce-paypal-payments.php', 'marker_type' => 'constant', 'marker' => 'PPCP_VERSION', 'files' => ['woocommerce_paypal.php']],
            ['plugin' => 'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php', 'marker_type' => 'constant', 'marker' => 'WC_STRIPE_VERSION', 'files' => ['woocommerce_stripe.php']],
            ['plugin' => 'mainwp-child/mainwp-child.php', 'marker_type' => 'constant', 'marker' => 'MAINWP_CHILD_PLUGIN_VERSION', 'files' => ['mainwp_child.php']],
            ['plugin' => 'popup-maker/popup-maker.php', 'marker_type' => 'constant', 'marker' => 'POPMAKE_VERSION', 'files' => ['popup_maker.php']],
            ['plugin' => 'coming-soon/coming-soon.php', 'marker_type' => 'constant', 'marker' => 'SEEDPROD_VERSION', 'files' => ['seedprod.php']],
            ['plugin' => 'enable-media-replace/enable-media-replace.php', 'marker_type' => 'constant', 'marker' => 'EMR_VERSION', 'files' => ['enable_media_replace.php']],
            ['plugin' => 'google-analytics-for-wordpress/googleanalytics.php', 'marker_type' => 'constant', 'marker' => 'MONSTERINSIGHTS_VERSION', 'files' => ['monsterinsights.php']],
            ['plugin' => 'press-permit-core/press-permit-core.php', 'marker_type' => 'constant', 'marker' => 'PRESSPERMIT_VERSION', 'files' => ['publishpress_permissions.php']],
            ['plugin' => 'tinypress/tinypress.php', 'marker_type' => 'constant', 'marker' => 'TINYPRESS_PLUGIN_VERSION', 'files' => ['publishpress_shortlinks.php']],
            ['plugin' => 'publishpress-statuses/publishpress-statuses.php', 'marker_type' => 'constant', 'marker' => 'PUBLISHPRESS_STATUSES_VERSION', 'files' => ['publishpress_statuses.php']],
        ];
        $providers = [];

        foreach ($loaders as $loader) {
            if (!self::pluginActive($loader['plugin'], $loader['marker_type'], $loader['marker'])) {
                continue;
            }

            foreach ($loader['files'] as $file) {
                $loaded = require REVISIONARY_PRO_ABSPATH . '/includes-pro/visual-compare-fields/' . $file;
                if (is_array($loaded)) {
                    $providers = array_merge($providers, $loaded);
                }
            }
        }

        $theme_loaders = [
            'astra' => 'themes/astra.php',
            'kadence' => 'themes/kadence.php',
            'oceanwp' => 'themes/oceanwp.php',
            'blocksy' => 'themes/blocksy.php',
            'generatepress' => 'themes/generatepress.php',
            'neve' => 'themes/neve.php',
            'popularfx' => 'themes/popularfx.php',
            'envo-royal' => 'themes/envo_royal.php',
            'inspiro' => 'themes/inspiro.php',
            'envo-one' => 'themes/envo_one.php',
            'lightning' => 'themes/lightning.php',
            'kubio' => 'themes/kubio.php',
            'hestia' => 'themes/hestia.php',
            'storefront' => 'themes/storefront.php',
            'go' => 'themes/go.php',
            'sydney' => 'themes/sydney.php',
            'bloghash' => 'themes/bloghash.php',
            'denovr-lite' => 'themes/denovr_lite.php',
            'tourze-lite' => 'themes/tourze_lite.php',
        ];

        foreach ($theme_loaders as $theme_slug => $file) {
            if (!self::themeActive($theme_slug)) {
                continue;
            }

            $loaded = require REVISIONARY_PRO_ABSPATH . '/includes-pro/visual-compare-fields/' . $file;
            if (is_array($loaded)) {
                $providers = array_merge($providers, $loaded);
            }
        }

        return $providers;
    }

    private static function pluginActive($plugin_file, $marker_type = '', $marker = '') {
        static $active_plugins = null;

        if (null === $active_plugins) {
            $active_plugins = array_fill_keys((array) get_option('active_plugins', []), true);
            if (is_multisite()) {
                $active_plugins += (array) get_site_option('active_sitewide_plugins', []);
            }
        }

        if (isset($active_plugins[$plugin_file])) {
            return true;
        }

        if ('constant' === $marker_type) {
            return defined($marker);
        }
        if ('class' === $marker_type) {
            return class_exists($marker, false);
        }
        if ('function' === $marker_type) {
            return function_exists($marker);
        }

        return false;
    }

    private static function themeActive($theme_slug) {
        static $active_themes = null;

        if (null === $active_themes) {
            $active_themes = array_unique(array_filter([get_stylesheet(), get_template()]));
        }

        return in_array($theme_slug, $active_themes, true);
    }



    public static function field($label, $group, $choices = [], $type = '') {
        return compact('label', 'group', 'choices', 'type');
    }

    public static function yesNo() {
        return ['1' => 'Yes', '0' => 'No', 'yes' => 'Yes', 'no' => 'No', 'on' => 'Yes', 'off' => 'No'];
    }

    public static function enabledDisabled() {
        return ['1' => 'Enabled', '0' => 'Disabled', 'yes' => 'Enabled', 'no' => 'Disabled', 'on' => 'Enabled', 'off' => 'Disabled'];
    }

    public static function eventsCalendarFields() {
        return [
            '_EventStartDate' => self::field('Start date and time', 'Date and time'),
            '_EventEndDate' => self::field('End date and time', 'Date and time'),
            '_EventAllDay' => self::field('All-day event', 'Date and time', self::yesNo()),
            '_EventTimezone' => self::field('Timezone', 'Date and time'),
            '_EventCost' => self::field('Cost', 'Cost'),
            '_EventCurrencySymbol' => self::field('Currency symbol', 'Cost'),
            '_EventCurrencyCode' => self::field('Currency code', 'Cost'),
            '_EventCurrencyPosition' => self::field('Currency position', 'Cost', ['prefix' => 'Before cost', 'suffix' => 'After cost']),
            '_EventURL' => self::field('Event website', 'Event details'),
            '_EventVenueID' => self::field('Venue', 'Event details'),
            '_EventOrganizerID' => self::field('Organizer', 'Event details'),
            '_EventShowMap' => self::field('Show map', 'Event details', self::yesNo()),
            '_EventShowMapLink' => self::field('Show map link', 'Event details', self::yesNo()),
            '_Venue' => self::field('Venue name', 'Venue'),
            '_VenueAddress' => self::field('Address', 'Venue'),
            '_VenueCity' => self::field('City', 'Venue'),
            '_VenueCountry' => self::field('Country', 'Venue'),
            '_VenueProvince' => self::field('Province', 'Venue'),
            '_VenueState' => self::field('State', 'Venue'),
            '_VenueZip' => self::field('Postal code', 'Venue'),
            '_VenuePhone' => self::field('Phone', 'Venue'),
            '_VenueURL' => self::field('Website', 'Venue'),
            '_VenueWebsite' => self::field('Website', 'Venue'),
            '_VenueShowMap' => self::field('Show map', 'Venue', self::yesNo()),
            '_VenueShowMapLink' => self::field('Show map link', 'Venue', self::yesNo()),
            '_Organizer' => self::field('Organizer name', 'Organizer'),
            '_OrganizerPhone' => self::field('Phone', 'Organizer'),
            '_OrganizerWebsite' => self::field('Website', 'Organizer'),
        ];
    }
}
