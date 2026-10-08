<?php

namespace PublishPress\Statuses;

class CoreAdmin
{
    function __construct()
    {
        add_action('admin_menu', [$this, 'actAdminMenu'], 22);

        add_action('admin_print_scripts', [$this, 'setUpgradeMenuLink'], 50);

        add_action('publishpress_statuses_settings_sidebar', [$this, 'settingsSidebar']);
        add_filter('publishpress_statuses_settings_sidebar_class', function($class) {return 'has-right-sidebar';});

        if (class_exists('PPVersionNotices\Module\TopNotice\Module')) {
            add_filter(\PPVersionNotices\Module\TopNotice\Module::SETTINGS_FILTER, function ($settings) {
                $settings['publishpress-statuses'] = [
                    'message' => esc_html__("You're using PublishPress Statuses Free. The Pro version has more features and support. %sUpgrade to Pro%s", 'publishpress-statuses'),
                    'link'    => 'https://publishpress.com/links/statuses-banner',
                    'screens' => [
                        ['base' => 'toplevel_page_publishpress-statuses'],
                        ['base' => 'statuses_page_publishpress-statuses-add-new'],
                        ['base' => 'statuses_page_publishpress-statuses-settings'],
                    ]
                ];

                return $settings;
            });
        }
    }

    function actAdminMenu()
    {
        $check_cap = (current_user_can('manage_options')) ? 'read' : 'pp_manage_statuses';

        if (current_user_can($check_cap)) {
            add_submenu_page(
                'publishpress-statuses',
                esc_html__('Upgrade to Pro', 'publishpress-statuses'),
                esc_html__('Upgrade to Pro', 'publishpress-statuses'),
                'read',
                'statuses-pro',
                ['PublishPress\Statuses\CoreAdmin', 'actUpgradeMenu']
            );
        }
    }

    function actUpgradeMenu() 
    {
        return;
    }

    function setUpgradeMenuLink()
    {
        $url = 'https://publishpress.com/links/statuses-menu';
?>
        <style type="text/css">
            #toplevel_page_publishpress-statuses ul li:last-of-type a {
                font-weight: bold !important;
                color: #FEB123 !important;
            }
        </style>

        <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function($) {
                $('#toplevel_page_publishpress-statuses ul li:last a').attr('href', '<?php echo esc_url($url); ?>').attr('target', '_blank').css('font-weight', 'bold').css('color', '#FEB123');
            });
            /* ]]> */
        </script>
        <?php
    }

    function settingsSidebar() {
        wp_enqueue_style(
            'pp-wordpress-banners-style',
            plugin_dir_url(PUBLISHPRESS_STATUSES_FILE) . 'lib/vendor/publishpress/wordpress-banners/assets/css/style.css',
            false,
            PP_WP_BANNERS_VERSION
        );
    }
}
