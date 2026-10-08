<?php
namespace PublishPress\StatusesPro;

class LicenseActivationAjax {
    function __construct() {
        if (isset($_GET['publishpress_statuses_ajax_settings'])) {
            switch ($_GET['publishpress_statuses_ajax_settings']) {
                case 'activate_key':
                    check_admin_referer('wp_ajax_pp_activate_key');
                    if (is_multisite() && !is_super_admin() && (self::isNetworkActivated() || self::isMuPlugin())) {
                        return;
                    }
            
                    if (empty($_GET['key'])) {
                        return;
                    }
            
                    $request_vars = [
                        'edd_action' => "activate_license",
                        'item_id' => PUBLISHPRESS_STATUSES_EDD_ITEM_ID,
                        'license' => sanitize_key($_GET['key']),
                        'url' => site_url(''),
                    ];
            
                    $response = self::callHome('activate_license', $request_vars);
            
                    $result = json_decode($response);
                    if (is_object($result) && ('valid' == $result->license)) {
                        $setting = ['license_status' => $result->license, 'license_key' => sanitize_key($_GET['key']), 'expire_date' => $result->expires];
                        update_option('publishpress_statuses_edd_key', $setting);
                    }
            
                    echo $response;                                     //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    exit();
            
                    break;
            
                case 'deactivate_key':
                    check_admin_referer('wp_ajax_pp_deactivate_key');
                    if (
                        is_multisite() && !is_super_admin() && (self::isNetworkActivated() || self::isMuPlugin())
                    ) {
                        return;
                    }
            
                    $support_key = (array) get_option('publishpress_statuses_edd_key');
                    $request_vars = [
                        'edd_action' => "deactivate_license",
                        'item_id' => PUBLISHPRESS_STATUSES_EDD_ITEM_ID,
                        'license' => isset($support_key['license_key']) ? $support_key['license_key'] : '',
                        'url' => site_url(''),
                    ];
            
                    $response = self::callHome('deactivate_license', $request_vars);
            
                    $result = json_decode($response);
                    if (is_object($result) && $result->license != 'valid') {
                        delete_option('publishpress_statuses_edd_key');
                    }
            
                    echo $response;                                     //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    exit();
            
                    break;
            }
        }
    }

    private static function callHome($request_topic, $request_vars = [], $post_vars = false)
    {
        $request_vars = array_merge((array)$request_vars, ['PPServerRequest' => $request_topic]);

        $args = [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded; charset=' . get_option('blog_charset'),
                'User-Agent' => 'WordPress/' . get_bloginfo("version"),
                'Referer' => get_bloginfo("url")
            ],
        ];

        $timeout = in_array($request_topic, ['update-check', 'changelog'], true) ? 8 : 30;
        
        $body = (false !== $post_vars) ? $post_vars : array_merge($request_vars, ['url' => site_url()]);

        try {
        $server_response = wp_remote_post(
            'https://publishpress.com/',
            [
            'timeout'   => $timeout,
            'sslverify' => true,
            'body'      => $body,
            ]
        );

        // Is the response an error?
        if (is_wp_error($server_response) || 200 !== wp_remote_retrieve_response_code($server_response)) {
            if (is_wp_error($server_response)) {
                $message = $server_response->get_error_message();
            }

            if (empty($message)) {
                throw new \Exception(esc_html__('An error occurred. Please, try again or contact the support team.',
                    'allex'));
            } else {
            throw new \Exception($message, 'allex');
            }
        }

        $json_response = wp_remote_retrieve_body($server_response);

        // Convert data response to an object.
        $data = json_decode($json_response);

        // Do we have empty data? Throw an error.
        if (empty($data) || ! is_object($data)) {
            throw new \Exception(esc_html__('An error occurred. Please, try again or contact the support team.',
                'allex'));
        }
        } catch (\Exception $e) {
        return $e->getMessage();
        }

        return $json_response;
    }

    public static function isMuPlugin($plugin_path = '')
    {
        if ( ! $plugin_path && defined('PUBLISHPRESS_STATUSES_PRO_FILE') ) {
            $plugin_path = PUBLISHPRESS_STATUSES_PRO_FILE;
        }
        return (defined('WPMU_PLUGIN_DIR') && (false !== strpos($plugin_path, WPMU_PLUGIN_DIR)));
    }

    public static function isNetworkActivated($plugin_file = '')
    {
        if (!$plugin_file) {
            $plugin_file = plugin_basename(PUBLISHPRESS_STATUSES_PRO_FILE);
        }
        
        return (array_key_exists($plugin_file, (array)maybe_unserialize(get_site_option('active_sitewide_plugins'))));
    }
}
