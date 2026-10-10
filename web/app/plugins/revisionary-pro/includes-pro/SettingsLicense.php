<?php
class RevisionaryLicenseSettings {
    function display($sitewide, $customize_defaults) {
        if ($customize_defaults || (is_multisite() && !$sitewide && rvy_is_network_activated())) {
            return;
        }
        
        $ui = RvyOptionUI::instance(compact('sitewide', 'customize_defaults'));

        $tab = 'features';

        require_once(REVISIONARY_PRO_ABSPATH . '/includes-pro/library/Factory.php');
        $container      = \PublishPress\Revisions\Factory::get_container();
        $licenseManager = $container['edd_container']['license_manager'];

        $use_network_admin = false; !empty($args['use_network_admin']);
        $suppress_updates = false;

        $section = 'license'; // --- UPDATE KEY SECTION ---
        ?>
        <tr>
            <td>
                <?php
                global $activated;

                $id = 'edd_key';

                if (!get_transient('revisionary-pro-refresh-update-info')) {
                    revisionary()->keyStatus(true);
                    set_transient('revisionary-pro-refresh-update-info', true, 86400);
                }

                $opt_val = revisionary()->getOption($id);

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
                    $class = 'activating';
                    $is_err = true;
                    $msg = sprintf(
                        esc_html__('Your license key has expired. For continued priority support, <a href="%s">please renew</a>.', 'revisionary-pro'),
                        'https://publishpress.com/my-downloads/'
                    );
                } elseif (!empty($opt_val['expire_date'])) {
                    $class = 'activating';
                    if ($expire_days < 30) {
                        $is_err = true;
                    }

                    if ($expire_days == 1) {
                        $msg = sprintf(
                            esc_html__('Your license key will expire today. For updates and priority support, <a href="%s">please renew</a>.', 'revisionary-pro'),
                            esc_html($expire_days),
                            'https://publishpress.com/my-downloads/'
                        );
                    } elseif ($expire_days < 30) {
                        $msg = sprintf(
                            esc_html__(
                                'Your license key (for plugin updates) will expire in %d days. For updates and priority support, <a href="%s">please renew</a>.',
                                'revisionary-pro'
                            ),
                            esc_html($expire_days),
                            'https://publishpress.com/my-downloads/'
                        );
                    } else {
                        $class = "activating hidden";
                    }
                } elseif (!$activated) {
                    $class = 'activating';
                    $msg = sprintf(
                        esc_html__('For updates to PublishPress Revisions Pro, activate your %sPublishPress license key%s.', 'revisionary-pro'),
                        '<a href="https://publishpress.com/pricing/">',
                        '</a>'
                    );
                } else {
                    $class = "activating hidden";
                    $msg = '';
                }
                ?>

                <div class="pp-key-wrap">

                <?php if ($expired && (!empty($key))) : ?>
                    <span class="pp-key-expired"><?php esc_html_e("Key Expired", 'revisionary-pro') ?></span>
                    <input name="<?php echo esc_attr($id); ?>" type="text" id="<?php echo esc_attr($id); ?>" style="display:none"/>
                    <button type="button" id="activation-button" name="activation-button"
                            class="button-secondary"><?php esc_html_e('Deactivate Key', 'revisionary-pro'); ?></button>
                <?php else : ?>
                    <div class="pp-key-label" style="float:left">
                        <span class="pp-key-active" <?php if (!$activated) echo 'style="display:none;"';?>><?php esc_html_e("Key Activated", 'revisionary-pro') ?></span>
                        <span class="pp-key-inactive" <?php if ($activated) echo 'style="display:none;"';?>><?php esc_html_e("License Key", 'revisionary-pro') ?></span>
                    </div>

                        <input name="<?php echo esc_attr($id); ?>" type="text" placeholder="<?php echo esc_attr('(please enter publishpress.com key)', 'press-permit-pro');?>" id="<?php echo esc_attr($id); ?>"
                                <?php echo ($activated) ? ' style="display:none"' : ''; ?> />

                        <button type="button" id="activation-button" name="activation-button"
                                class="button-secondary"><?php if (!$activated) esc_html_e('Activate Key', 'revisionary-pro'); else esc_html_e('Deactivate Key', 'revisionary-pro'); ?></button>
                <?php endif; ?>

                    <img id="pp_support_waiting" class="waiting" style="display:none;position:relative"
                            src="<?php echo esc_url(admin_url('images/wpspin_light.gif')) ?>" alt=""/>

                    <div class="pp-key-refresh" style="display:inline">
                        &bull;&nbsp;&nbsp;<a href="https://publishpress.com/checkout/purchase-history/"
                                                    target="_blank"><?php esc_html_e('review your account info', 'revisionary-pro'); ?></a>
                    </div>
                </div>

                <?php if ($activated) : ?>
                    <?php if ($expired) : ?>
                        <div class="pp-key-hint-expired">
                            <span class="pp-key-expired pp-key-warning"> <?php esc_html_e('note: Renewal does not require deactivation. If you do deactivate, re-entry of the license key will be required.', 'revisionary-pro'); ?></span>
                        </div>
                    <?php elseif (revisionary()->getOption('display_hints')) : ?>
                        <div class="pp-key-hint">
                        <span class="rvy-subtext"> <?php esc_html_e('note: If you deactive, re-entry of the license key will be required for re-activation.', 'revisionary-pro'); ?></span>
                    <?php endif; ?>
                    </div>

                <?php elseif (!$expired) : ?>
                    <div class="pp-key-hint">
                        <span class="rvy-subtext"> <?php ?></span>
                    </div>
                <?php endif ?>

                <div id="activation-status" class="<?php echo esc_attr($class) ?>"><?php 
                /* output variables escaped upstream */ 
                echo $msg;                                                  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                ?></div>

                <?php if (!empty($is_err)) : ?>
                    <div id="activation-error" class="error"><?php 
                    /* output variables escaped upstream */ 
                    echo $msg;                                              //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?></div>
                <?php endif; ?>
            </td>
        </tr>
        <?php

        do_action('revisionary_support_key_ui');
        self::footer_js($activated, $expired);

        $section = 'version'; // --- VERSION SECTION ---
        ?>
            <tr>
                <td>

                    <?php
                    $update_info = [];

                    $info_link = '';

                    if (!$suppress_updates) {
                        $wp_plugin_updates = get_site_transient('update_plugins');
                        if (
                            $wp_plugin_updates && isset($wp_plugin_updates->response[plugin_basename(REVISIONARY_PRO_FILE)])
                            && !empty($wp_plugin_updates->response[plugin_basename(REVISIONARY_PRO_FILE)]->new_version)
                            && version_compare($wp_plugin_updates->response[plugin_basename(REVISIONARY_PRO_FILE)]->new_version, PUBLISHPRESS_REVISIONS_PRO_VERSION, '>')
                        ) {
                            $slug = 'revisionary-pro';

                            $_url = "plugin-install.php?tab=plugin-information&plugin=$slug&section=changelog&TB_iframe=true&width=600&height=800";
                            $info_url = ($use_network_admin) ? network_admin_url($_url) : admin_url($_url);

                            $do_info_link = true;
                        }
                    }

                    ?>
                    <div class="pp-key-label " style="float:left">
                        <?php printf(
                            esc_html__('%1$s PublishPress Revisions Pro %2$s %3$s', 'revisionary-pro'), 
                            sprintf(esc_html__('%1$sInstalled Version:%2$s', 'revisionary-pro'), '<span class="pp-key-inactive">', '</span>'), 
                            esc_html(PUBLISHPRESS_REVISIONS_PRO_VERSION), 
                            ''
                        ); 
                        
                        if (!empty($do_info_link)) {
                            echo "&nbsp;<span class='update-message'> &bull;&nbsp;&nbsp;<a href='" . esc_url($info_url) . "' class='thickbox'>"
                                . sprintf(esc_html__('view %s&nbsp;details', 'revisionary-pro'), esc_html($wp_plugin_updates->response[plugin_basename(REVISIONARY_PRO_FILE)]->new_version))
                                . '</a></span>';
                        }

                        if (current_user_can('update_plugins')) :
                            $url = wp_nonce_url(
                                admin_url("admin.php?page=revisionary-settings&rvy_refresh_updates=1"),
                                'rvy_refresh_updates'
                            );
                        ?>
                            &nbsp;&nbsp;&bull;&nbsp;&nbsp;<a href="<?php echo esc_url($url);?>"><?php esc_html_e('update check / install', 'revisionary-pro'); ?></a>
                        <?php endif;
                        ?>
                    </div>
                </td>
            </tr>
    <?php
    }

    private function footer_js($activated, $expired)
    {
        $vars = [
            'activated' => ($activated || !empty($expired)) ? true : false,
            'expired' => !empty($expired),
            'activateCaption' => esc_html__('Activate Key', 'revisionary-pro'),
            'deactivateCaption' => esc_html__('Deactivate Key', 'revisionary-pro'),
            'connectingCaption' => esc_html__('Connecting to publishpress.com server...', 'revisionary-pro'),
            'noConnectCaption' => esc_html__('The request could not be processed due to a connection failure.', 'revisionary-pro'),
            'noEntryCaption' => esc_html__('Please enter the license key shown on your order receipt.', 'revisionary-pro'),
            'errCaption' => esc_html__('An unidentified error occurred.', 'revisionary-pro'),
            'keyStatus' => wp_json_encode([
                'deactivated' => esc_html__('The key has been deactivated.', 'revisionary-pro'),
                'valid' => esc_html__('The key has been activated.', 'revisionary-pro'),
                'expired' => esc_html__('The key has expired.', 'revisionary-pro'),
                'invalid' => esc_html__('The key is invalid.', 'revisionary-pro'),
                '-100' => esc_html__('An unknown activation error occurred.', 'revisionary-pro'),
                '-101' => esc_html__('The key provided is not valid. Please double-check your entry.', 'revisionary-pro'),
                '-102' => esc_html__('This site is not valid to activate the key.', 'revisionary-pro'),
                '-103' => esc_html__('The key provided could not be validated by publishpress.com.', 'revisionary-pro'),
                '-104' => esc_html__('The key provided is already active on another site.', 'revisionary-pro'),
                '-105' => esc_html__('The key has already been activated on the allowed number of sites.', 'revisionary-pro'),
                '-200' => esc_html__('An unknown deactivation error occurred.', 'revisionary-pro'),
                '-201' => esc_html__('Unable to deactivate because the provided key is not valid.', 'revisionary-pro'),
                '-202' => esc_html__('This site is not valid to deactivate the key.', 'revisionary-pro'),
                '-203' => esc_html__('The key provided could not be validated by publishpress.com.', 'revisionary-pro'),
                '-204' => esc_html__('The key provided is not active on the specified site.', 'revisionary-pro'),
            ]),
            'activateURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_activate_key'),
            'deactivateURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_deactivate_key'),
            'refreshURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_refresh_version'),
            'activationHelp' => sprintf(esc_html__('If this is incorrect, <a href="%s">request activation help</a>.', 'revisionary-pro'), 'https://publishpress.com/contact/'),
            'supportOptChanged' => esc_html__('Please save settings before uploading site configuration.', 'revisionary-pro'),
        ];

        wp_localize_script('revisionary-pro-settings', 'revisionarySettings', $vars);
    }
} // end class
