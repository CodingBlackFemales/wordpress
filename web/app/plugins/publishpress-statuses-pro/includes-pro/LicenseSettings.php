<?php
namespace PublishPress\StatusesPro;

class LicenseSettings {
    function __construct() {
        add_action('publishpress_statuses_add_settings_field', [$this, 'actAddSettingsField']);
        add_action('publishpress_statuses_settings_ui', [$this, 'actSettingsUI']);

        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
    }

    function admin_scripts() {
		wp_enqueue_style('pp-statuses-pro-settings', plugins_url('', PUBLISHPRESS_STATUSES_PRO_FILE) . '/includes-pro/settings-pro.css', [], PUBLISHPRESS_STATUSES_PRO_VERSION);

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
		wp_enqueue_script('pp-statuses-pro-settings', plugins_url('', PUBLISHPRESS_STATUSES_PRO_FILE) . "/includes-pro/settings-pro{$suffix}.js", ['jquery', 'jquery-form'], PUBLISHPRESS_STATUSES_PRO_VERSION, true);
 	
        add_thickbox();
    }

    function actAddSettingsField($group_name) {
        add_settings_field(
            'license',
            __('License Key:', 'publishpress-statuses-pro'),
            [$this, 'actSettingsUI'],
            $group_name,
            $group_name . '_general',
            ['class' => 'pp-settings-license']
        );
    }

    function actSettingsUI() {
        $this->display();        
    }

    function display() {
        require_once(PUBLISHPRESS_STATUSES_PRO_ABSPATH . '/includes-pro/library/Factory.php');
        $container      = \PublishPress\Statuses\Factory::get_container();
        $licenseManager = $container['edd_container']['license_manager'];

        $use_network_admin = false;
        $suppress_updates = false;

        // --- UPDATE KEY SECTION ---
        $id = 'publishpress_statuses_edd_key';

        if (!get_transient('publishpress-statuses-pro-refresh-update-info')) {
            \PublishPress_Statuses_Pro::instance()->keyStatus(true);
            set_transient('publishpress-statuses-pro-refresh-update-info', true, 86400);
        }

        $opt_val = get_option($id);

        if (!is_array($opt_val) || count($opt_val) < 2) {
            $activated = false;
            $expired = false;
            $key = '';
            $opt_val = [];
        } else {
            $activated = !empty($opt_val['license_status']) && ('valid' == $opt_val['license_status']);
            $expired = $opt_val['license_status'] && ('expired' == $opt_val['license_status']);
        }

        if (isset($opt_val['expire_date']) && is_date($opt_val['expire_date'])) {
            $date = new \DateTime(gmdate('Y-m-d H:i:s', strtotime($opt_val['expire_date'])), new \DateTimezone('UTC'));
            $date->setTimezone(new \DateTimezone('America/New_York'));
            $expire_date_gmt = $date->format("Y-m-d H:i:s");
            $expire_days = intval((strtotime($expire_date_gmt) - time()) / 86400);
        } else {
            unset($opt_val['expire_date']);
        }

        $msg = '';

        if ($expired) {
            $msg = sprintf(
                esc_html__('%sYour license key has expired%s. For continued priority support, %splease renew%s.', 'publishpress-statuses-pro'),
                '<span style="font-weight:600">',
                '</span>',
                '<a href="https://publishpress.com/my-downloads/" target="_blank">',
                '</a>'
            );
        } elseif (!empty($opt_val['expire_date'])) {
            if ($expire_days == 1) {
                $msg = sprintf(
                    esc_html__('%sYour license key will expire today%s. For updates and priority support, %splease renew%s.', 'publishpress-statuses-pro'),
                    '<span style="font-weight:600">',
                    '</span>',
                    '<a href="https://publishpress.com/my-downloads/" target="_blank">',
                    '</a>'
                );
            } elseif ($expire_days < 30) {
                $msg = sprintf(
                    esc_html__('%sYour license key (for plugin updates) will expire in %d days%s. For updates and priority support, %splease renew%s.', 'publishpress-statuses-pro'),
                    '<span style="font-weight:600">',
                    $expire_days,
                    '</span>',
                    '<a href="https://publishpress.com/my-downloads/" target="_blank">',
                    '</a>'
                );
            } else {
                $class = "activating hidden";
            }
        } elseif (!$activated) {
            $class = 'activating';
            $msg = sprintf(
                esc_html__('For updates to PublishPress Statuses Pro, activate your %sPublishPress license key%s.', 'publishpress-statuses-pro'),
                '<a href="https://publishpress.com/pricing/" target="_blank">',
                '</a>'
            );
        } else {
            $class = "activating hidden";
            $msg = '';
        }
        ?>

    <div id="pp_statuses_license">
        <div class="pp-key-wrap">
            <?php if ($expired && (!empty($key))) : ?>
                <input name="edd_key" type="text" id="edd_key" style="display:none"/>
                <button type="button" id="activation-button" name="activation-button"
                        class="button-secondary"><?php esc_html_e('Deactivate Key', 'publishpress-statuses-pro'); ?></button>

                <span>
                <img id="pp_support_waiting" class="waiting" style="display:none;position:relative"
                        src="<?php echo esc_url(admin_url('images/wpspin_light.gif')) ?>" alt=""/>
                </span>
                
                <span class="pp-key-expired"><?php esc_html_e("Key Expired", 'publishpress-statuses-pro') ?></span>
            <?php else : ?>
                <input name="edd_key" type="text" placeholder="<?php echo esc_attr('(please enter publishpress.com key)', 'publishpress-statuses-pro');?>" id="edd_key"
                        <?php echo ($activated) ? ' style="display:none"' : ''; ?> />

                <button type="button" id="activation-button" name="activation-button"
                        class="button-secondary"><?php if (!$activated) esc_html_e('Activate Key', 'publishpress-statuses-pro'); else esc_html_e('Deactivate Key', 'publishpress-statuses-pro'); ?></button>
            
                <span>
                <img id="pp_support_waiting" class="waiting" style="display:none;position:relative"
                        src="<?php echo esc_url(admin_url('images/wpspin_light.gif')) ?>" alt=""/>
                </span>

                <div class="pp-key-label">
                    <span class="pp-key-active" <?php if (!$activated) echo 'style="display:none;"';?>><?php esc_html_e("Key Activated", 'publishpress-statuses-pro') ?></span>
                    <span class="pp-key-inactive"></span>
                </div>
            <?php endif; ?>

            <?php if (!$expired) : ?>
                <div class="pp-key-hint">
                    <span class="rvy-subtext"> </span>
                </div>
            <?php endif ?>

            <div id="activation-status" class="<?php echo esc_attr($class) ?>"><?php 
            /* output variables escaped upstream */ 
            echo $msg;                                                  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?></div>
        
    </div>
        <?php

        self::footer_js($activated, $expired);

        // --- VERSION SECTION ---
        $update_info = [];

        $info_link = '';

        if (!$suppress_updates) {
            $wp_plugin_updates = get_site_transient('update_plugins');
            if (
                $wp_plugin_updates && isset($wp_plugin_updates->response[plugin_basename(PUBLISHPRESS_STATUSES_PRO_FILE)])
                && !empty($wp_plugin_updates->response[plugin_basename(PUBLISHPRESS_STATUSES_PRO_FILE)]->new_version)
                && version_compare($wp_plugin_updates->response[plugin_basename(PUBLISHPRESS_STATUSES_PRO_FILE)]->new_version, PUBLISHPRESS_STATUSES_PRO_VERSION, '>')
            ) {
                $slug = 'publishpress-statuses-pro';

                $_url = "plugin-install.php?tab=plugin-information&plugin=$slug&section=changelog&TB_iframe=true&width=600&height=800";
                $info_url = self_admin_url($_url);

                $do_info_link = true;
            }
        }
        ?>
            <div id="pp_installed_version">
                <div class="pp-key-label">
                    <?php printf(
                        esc_html__('%1$s PublishPress Statuses Pro %2$s %3$s', 'publishpress-statuses-pro'), 
                        sprintf(esc_html__('%1$sInstalled Version:%2$s', 'publishpress-statuses-pro'), '<span class="pp-installed-caption">', '</span>'), 
                        esc_html(PUBLISHPRESS_STATUSES_PRO_VERSION), 
                        ''
                    ); 
                    
                    if (!empty($do_info_link)) {
                        echo "&nbsp;<span class='update-message'> &bull;&nbsp;&nbsp;<a href='" . esc_url($info_url) . "' class='thickbox'>"
                            . sprintf(esc_html__('view %s&nbsp;details', 'publishpress-statuses-pro'), esc_html($wp_plugin_updates->response[plugin_basename(PUBLISHPRESS_STATUSES_PRO_FILE)]->new_version))
                            . '</a></span>';
                    }

                    if (!empty($_SERVER['REQUEST_URI'])) {
                        $uri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']));
                    } else {
                        $uri = '';
                    }
                    ?>
                    
                    &nbsp;&nbsp;&bull;&nbsp;&nbsp;<a href="<?php echo esc_url(add_query_arg('publishpress_statuses_refresh_updates', 1, $uri));?>"><?php esc_html_e('update check / install', 'publishpress-statuses-pro'); ?></a>
                    
                </div>
            </div>
        </div>
    <?php
    }

    private static function footer_js($activated, $expired)
    {
        $vars = [
            'activated' => ($activated || !empty($expired)) ? true : false,
            'expired' => !empty($expired),
            'activateCaption' => esc_html__('Activate Key', 'publishpress-statuses-pro'),
            'deactivateCaption' => esc_html__('Deactivate Key', 'publishpress-statuses-pro'),
            'connectingCaption' => esc_html__('Connecting to publishpress.com server...', 'publishpress-statuses-pro'),
            'noConnectCaption' => esc_html__('The request could not be processed due to a connection failure.', 'publishpress-statuses-pro'),
            'noEntryCaption' => esc_html__('Please enter the license key shown on your order receipt.', 'publishpress-statuses-pro'),
            'errCaption' => esc_html__('An unidentified error occurred.', 'publishpress-statuses-pro'),
            'keyStatus' => wp_json_encode([
                'deactivated' => esc_html__('The key has been deactivated.', 'publishpress-statuses-pro'),
                'valid' => esc_html__('The key has been activated.', 'publishpress-statuses-pro'),
                'expired' => esc_html__('The key has expired.', 'publishpress-statuses-pro'),
                'invalid' => esc_html__('The key is invalid.', 'publishpress-statuses-pro'),
                '-100' => esc_html__('An unknown activation error occurred.', 'publishpress-statuses-pro'),
                '-101' => esc_html__('The key provided is not valid. Please double-check your entry.', 'publishpress-statuses-pro'),
                '-102' => esc_html__('This site is not valid to activate the key.', 'publishpress-statuses-pro'),
                '-103' => esc_html__('The key provided could not be validated by publishpress.com.', 'publishpress-statuses-pro'),
                '-104' => esc_html__('The key provided is already active on another site.', 'publishpress-statuses-pro'),
                '-105' => esc_html__('The key has already been activated on the allowed number of sites.', 'publishpress-statuses-pro'),
                '-200' => esc_html__('An unknown deactivation error occurred.', 'publishpress-statuses-pro'),
                '-201' => esc_html__('Unable to deactivate because the provided key is not valid.', 'publishpress-statuses-pro'),
                '-202' => esc_html__('This site is not valid to deactivate the key.', 'publishpress-statuses-pro'),
                '-203' => esc_html__('The key provided could not be validated by publishpress.com.', 'publishpress-statuses-pro'),
                '-204' => esc_html__('The key provided is not active on the specified site.', 'publishpress-statuses-pro'),
            ]),
            'activateURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_activate_key'),
            'deactivateURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_deactivate_key'),
            'refreshURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_refresh_version'),
        ];

        wp_localize_script('pp-statuses-pro-settings', 'statusesProSettings', $vars);
    }

} // end class
