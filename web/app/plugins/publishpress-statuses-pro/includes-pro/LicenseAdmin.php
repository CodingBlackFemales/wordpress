<?php
namespace PublishPress\StatusesPro;

class LicenseAdmin {
    function __construct() {
        global $pagenow;

        if (('admin.php' == $pagenow) && !empty($_REQUEST['page']) && ('publishpress-statuses-settings' == $_REQUEST['page'])   // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        || (('plugin-install.php' == $pagenow) && !empty($_REQUEST['tab']) && ('plugin-information' == $_REQUEST['tab']))       // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ) {
            require_once(PUBLISHPRESS_STATUSES_PRO_ABSPATH . '/includes-pro/LicenseSettings.php');
            new \PublishPress\StatusesPro\LicenseSettings();
        }

        if (defined('PUBLISHPRESS_STATUSES_PRO_VERSION') && !empty($_REQUEST['publishpress_statuses_ajax_settings'])) {         // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            include_once(PUBLISHPRESS_STATUSES_PRO_ABSPATH . '/includes-pro/LicenseActivationAjax.php');
            new \PublishPress\StatusesPro\LicenseActivationAjax();
        }
    
        if (defined('PUBLISHPRESS_STATUSES_PRO_VERSION') && !empty($_REQUEST['publishpress_statuses_refresh_updates']) && current_user_can('update_plugins')) {       // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $this->clear_edd_cache();
        }
    
        if (!empty($_REQUEST['publishpress_statuses_refresh_done']) && empty($_POST)) {                                         // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            if (current_user_can('activate_plugins')) {
                $url = admin_url('update-core.php?force-check=1');
                wp_redirect(esc_url($url));
                exit;
            }
        }
    }

    function clear_edd_cache() {
        \PublishPress_Statuses_Pro::instance()->keyStatus(true);
        set_transient('publishpress-statuses-pro-refresh-update-info', true, 86400);

        delete_site_transient('update_plugins');
        delete_option('_site_transient_update_plugins');

        $opt_val = get_option('publishpress_statuses_edd_key');

        if (is_array($opt_val) && !empty($opt_val['license_key'])) {
            $plugin_slug = basename(PUBLISHPRESS_STATUSES_PRO_FILE, '.php');
            $plugin_relpath = basename(dirname(PUBLISHPRESS_STATUSES_PRO_FILE)) . '/' . basename(PUBLISHPRESS_STATUSES_PRO_FILE);
            $license_key = $opt_val['license_key'];
            $beta = false;

            delete_option(md5(serialize($plugin_slug . $license_key . $beta)));                                 // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
            delete_option('edd_api_request_' . md5(serialize($plugin_slug . $license_key . $beta)));            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
            delete_option(md5('edd_plugin_' . sanitize_key($plugin_relpath) . '_' . $beta . '_version_info'));

            $cache_key = 'edd_sl_' . md5( serialize( 'publishpress-statuses-pro' . $opt_val['license_key'] . $beta ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
            delete_option($cache_key);
        }

        wp_update_plugins();

        if (current_user_can('update_plugins') && !empty($_SERVER['REQUEST_URI']) && !empty($_SERVER['HTTP_HOST'])) {
            $url = remove_query_arg('publishpress_statuses_refresh_updates', esc_url(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))));
            $url = add_query_arg('publishpress_statuses_refresh_done', 1, $url);

            $https = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';
            $url = esc_url_raw(wp_unslash($_SERVER['HTTP_HOST'])) . $url;
            wp_safe_redirect($url);
            exit;
        }
    }
}
