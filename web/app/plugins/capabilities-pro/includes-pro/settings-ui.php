<?php
namespace PublishPress\Capabilities;

/*
 * PublishPress Capabilities Pro
 *
 * Plugin settings UI
 *
 */

class Pro_Settings_UI {
    public function __construct() {
        $this->loadScripts();
        add_action('pp_capabilities_settings_before_menu_list', [$this, 'licenseTab']);
        add_action('pp_capabilities_settings_after_menu_list', [$this, 'otherTabs']);
        add_action('pp_capabilities_settings_before_menu_content', [$this, 'TabsContent']);
        add_action('pp_capabilities_settings_after_capabilities_content', [$this, 'CapabilitiesTabsContent']);
    }

    public function loadScripts() {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
        wp_enqueue_script('publishpress-caps-pro-settings', plugins_url('', PUBLISHPRESS_CAPS_PRO_FILE) . "/includes-pro/settings-pro{$suffix}.js", ['jquery', 'jquery-form'], PUBLISHPRESS_CAPS_VERSION, true);
    }

    public function licenseTab() {
        $default_tab = (!empty($_REQUEST['pp_tab'])) ? sanitize_key($_REQUEST['pp_tab']) : '';

        ?>
        <li class="nav-tab <?php if (in_array($default_tab, ['', 'license'])) echo 'nav-tab-active';?>"><a href="#ppcs-tab-general"><?php esc_html_e('License', 'capabilities-pro');?></a></li>
        <?php
    }

    public function otherTabs() {
        ?>
        <li class="nav-tab"><a href="#ppcs-tab-admin-menus"><?php esc_html_e('Admin Menus', 'capabilities-pro');?></a></li>
        <?php
    }

    private function footerScripts($activated, $expired)
    {
        $vars = [
            'activated' => ($activated || !empty($expired)) ? true : false,
            'expired' => !empty($expired),
            'activateCaption' => __('Activate Key', 'capabilities-pro'),
            'deactivateCaption' => __('Deactivate Key', 'capabilities-pro'),
            'connectingCaption' => __('Connecting to publishpress.com server...', 'capabilities-pro'),
            'noConnectCaption' => __('The request could not be processed due to a connection failure.', 'capabilities-pro'),
            'noEntryCaption' => __('Please enter the license key shown on your order receipt.', 'capabilities-pro'),
            'errCaption' => __('An unidentified error occurred.', 'capabilities-pro'),
            'keyStatus' => json_encode([
                'deactivated' => __('The key has been deactivated.', 'capabilities-pro'),
                'valid' => __('The key has been activated.', 'capabilities-pro'),
                'expired' => __('The key has expired.', 'capabilities-pro'),
                'invalid' => __('The key is invalid.', 'capabilities-pro'),
                '-100' => __('An unknown activation error occurred.', 'capabilities-pro'),
                '-101' => __('The key provided is not valid. Please double-check your entry.', 'capabilities-pro'),
                '-102' => __('This site is not valid to activate the key.', 'capabilities-pro'),
                '-103' => __('The key provided could not be validated by publishpress.com.', 'capabilities-pro'),
                '-104' => __('The key provided is already active on another site.', 'capabilities-pro'),
                '-105' => __('The key has already been activated on the allowed number of sites.', 'capabilities-pro'),
                '-200' => __('An unknown deactivation error occurred.', 'capabilities-pro'),
                '-201' => __('Unable to deactivate because the provided key is not valid.', 'capabilities-pro'),
                '-202' => __('This site is not valid to deactivate the key.', 'capabilities-pro'),
                '-203' => __('The key provided could not be validated by publishpress.com.', 'capabilities-pro'),
                '-204' => __('The key provided is not active on the specified site.', 'capabilities-pro'),
            ]),
            'activateURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_activate_key'),
            'deactivateURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_deactivate_key'),
            'refreshURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_refresh_version'),
            'activationHelp' => sprintf(__('If this is incorrect, <a href="%s">request activation help</a>.', 'capabilities-pro'), 'https://publishpress.com/contact/'),
            'supportOptChanged' => __('Please save settings before uploading site configuration.', 'capabilities-pro'),
        ];

        wp_localize_script('publishpress-caps-pro-settings', 'ppCapabilitiesSettings', $vars);
    }

    public function CapabilitiesTabsContent() {
        ?>
        <tr>
            <?php
            if (defined('PUBLISHPRESS_STATUSES_VERSION') || (defined('PUBLISHPRESS_VERSION') && class_exists('PP_Custom_Status'))) :

                if (Pro::presspermitStatusControlActive()) {
                    $status_control_id = '';
                    $status_postmetacap_id = '';
                    $title = __("(Locked on by Statuses Pro)", 'capabilities-pro');
                    $disabled = 'disabled';
                } else {
                    $status_control_id = 'cme_custom_status_control';
                    $status_postmetacap_id = 'cme_custom_status_postmeta_caps';
                    $title = '';
                    $disabled = '';
                }

                $checked = checked(!empty(get_option('cme_custom_status_control')) || Pro::presspermitStatusControlActive(), true, false);
                ?>
                <th scope="row"><?php esc_html_e('Control custom statuses', 'capabilities-pro'); ?></th>
                <td>
                    <label for="">
                        <input type="checkbox" name="cme_custom_status_control" id="<?php echo esc_attr($status_control_id);?>" title="<?php echo esc_attr($title);?>" autocomplete="off" value="1" <?php echo esc_attr($checked);?> <?php echo esc_attr($disabled);?>>
                        <span class="description">
                            <?php printf(
                                esc_html__('Control selection of custom post statuses. %s', 'capabilities-pro'),
                                $title
                            );
                            ?>
                        </span>
                    </label>
                    <br><br>

                    <?php if (Pro::customStatusPostMetaPermissions('', '', ['ignore_capabilities_option' => true])) :
                        // Disable postmeta caps checkbox if status control is disabled
                        $disabled = (!$disabled && $checked) ? '' : ' disabled';

                        $checked = checked(Pro::customStatusPostMetaPermissions() || Pro::presspermitStatusControlActive(), true, false);
                        ?>
                        <label for="">
                            <input type="checkbox" name="cme_custom_status_postmeta_caps" id="<?php echo esc_attr($status_postmetacap_id);?>" title="<?php echo esc_attr($title);?>" autocomplete="off" value="1" <?php echo esc_attr($checked);?> <?php echo esc_attr($disabled);?>>
                            <span class="description">
                                <?php esc_html_e('Apply status-specific capabilities for post editing, deletion.', 'capabilities-pro');
                                ?>
                            </span>
                        </label>

                        <script type="text/javascript">
                        /* <![CDATA[ */
                        jQuery(document).ready( function($) {
                            $('#cme_custom_status_control').click(function(e) {
                                $('#cme_custom_status_postmeta_caps').attr('disabled', !$('#cme_custom_status_control').prop('checked'));

                                if (!$('#cme_custom_status_control').prop('checked')) {
                                    $('#cme_custom_status_postmeta_caps').removeAttr('checked');
                                }
                            });
                        });
                        /* ]]> */
                        </script>
                    <?php endif;


                    if (defined('PRESSPERMIT_PRO_VERSION')) :?>
                        <br>
                        <br><br>
                        <?php if (class_exists('PublishPress\Permissions\Statuses') || defined('PUBLISHPRESS_STATUSES_PRO_VERSION')) :
                            $style = '';
                            $disabled = '';
                            $checked = checked(Pro::customPrivacyStatusesAvailable(), true, false);
                            $title = '';
                            ?>
                            <input type="hidden" name="presspermit_privacy_statuses_enabled" value="0" />
                            <label>
                                <input type="checkbox" name="presspermit_privacy_statuses_enabled" id="presspermit_privacy_statuses_enabled" title = "<?php echo esc_attr($title);?>" autocomplete="off" value="1" style="<?php echo esc_attr($style);?>" <?php echo esc_attr($checked);?> <?php echo esc_attr($disabled);?>>
                                <span class="description">
                                    <?php esc_html_e('Enable Custom Visibility Statuses', 'capabilities-pro');
                                    ?>
                                </span>
                            </label>
                        <?php elseif (defined('PRESSPERMIT_PRO_VERSION')) :?>
                            <span class="description">
                                <?php printf(
                                    // Translators: %1$s and %2$s is link markup
                                    esc_html__('For Visibility Statuses, %1$senable the Status Control module%2$s in Permissions Pro', 'capabilities-pro'),
                                    '<a href="' . esc_url(self_admin_url('admin.php?page=presspermit-settings&pp_tab=modules')) . '">',
                                    '</a>'
                                    );
                                ?>
                            </span>
                        <?php endif;

                        $style = (Pro::customPrivacyStatusesAvailable()) ? '' : 'display:none;';
                        $disabled = (defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS_LOCKED') || defined('PP_SUPPRESS_PRIVACY_EDIT_CAPS')) ? ' disabled' : '';

                        $checked = checked(
                            (defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS') && PPS_CUSTOM_PRIVACY_EDIT_CAPS)
                            || (!defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS_LOCKED') && !defined('PP_SUPPRESS_PRIVACY_EDIT_CAPS') && get_option('presspermit_custom_privacy_edit_caps'))
                        , true, false);
                        ?>
                        <br><br>
                        <input type="hidden" name="presspermit_custom_privacy_edit_caps" value="0" />
                        <label style="<?php echo esc_attr($style);?>">
                            <input type="checkbox" name="presspermit_custom_privacy_edit_caps" id="presspermit_custom_privacy_edit_caps" title = "<?php echo esc_attr($title);?>" autocomplete="off" value="1" <?php echo esc_attr($checked);?> <?php echo esc_attr($disabled);?>>
                            <span class="description">
                                <?php esc_html_e('Apply status-specific, type-specific editing capabilities for Visibility Statuses', 'capabilities-pro');
                                ?>
                            </span>
                        </label>

                        <script type="text/javascript">
                        /* <![CDATA[ */
                        jQuery(document).ready( function($) {
                            $('#presspermit_privacy_statuses_enabled').click(function(e) {
                                $('#presspermit_custom_privacy_edit_caps').parent('label').toggle($('#presspermit_privacy_statuses_enabled').prop('checked'));

                                if (!$('#presspermit_privacy_statuses_enabled').prop('checked')) {
                                    $('#presspermit_custom_privacy_edit_caps').removeAttr('checked');
                                }
                            });
                        });
                        /* ]]> */
                        </script>
                    <?php else:?>


                    <?php endif;?>
                </td>
            <?php else:?>
                <th scope="row"><?php esc_html_e('Control custom statuses', 'capabilities-pro'); ?></th>

                <td>
                <div class="pp-notice">
                    <?php
                    $statuses_info = function_exists('pp_capabilities_statuses_info') ? pp_capabilities_statuses_info() : [];

                    $info_url = (!empty($statuses_info['info_url'])) ? $statuses_info['info_url'] : 'https://wordpress.org/plugins/publishpress-statuses';

                    if (!empty($statuses_info['statuses_installed'])) {
                        printf(
                            esc_html__('To control custom post statuses, activate the %1$sPublishPress Statuses%2$s plugin. %3$sLearn More.%4$s', 'publishpress'),
                            '<a href="' . $info_url . '">',
                            '</a>',
                            '<a href="https://publishpress.com/blog/publishpress-statuses/statuses-available/" target="_blank">',
                            '</a>'
                        );
                    } else {
                        printf(
                            esc_html__('To control custom post statuses, install the %1$sPublishPress Statuses%2$s plugin. %3$sLearn More.%4$s', 'publishpress'),
                            '<a href="' . $info_url . '" class="thickbox" target="_blank">',
                            '</a>',
                            '<a href="https://publishpress.com/blog/publishpress-statuses/statuses-available/" target="_blank">',
                            '</a>'
                        );
                    }
                    ?>
                </div>
                </td>
            <?php endif;?>
        </tr>
    <?php
    }

    public function TabsContent() {
        $default_tab = (!empty($_REQUEST['pp_tab'])) ? sanitize_key($_REQUEST['pp_tab']) : '';
        ?>
        <table class="form-table" role="presentation" id="ppcs-tab-general" style="<?php if (!in_array($default_tab, ['', 'license'])) echo 'display: none';?>">
            <tbody>
                <tr>
                    <th scope="row">
                        <?php esc_html_e('License Key Activation', 'capabilities-pro'); ?>
                    </th>
                    <td>
                        <div class="capsman-key-activation">
                            <div class="pp-key-wrap">
                            <?php
                            require_once(PUBLISHPRESS_CAPS_PRO_ABSPATH . '/includes-pro/library/Factory.php');
                            $container      = \PublishPress\Capabilities\Factory::get_container();
                            $licenseManager = $container['edd_container']['license_manager'];

                            global $activated;

                            $id = 'edd_key';

                            if (!get_transient('publishpress-caps-refresh-update-info')) {
                                publishpress_caps_pro()->keyStatus(true);
                                set_transient('publishpress-caps-refresh-update-info', true, 60 * 60 * 24 * 14);  // Force key status query only once every 2 weeks. This mechanism will be improved soon.
                            }

                            $opt_val = get_option("cme_edd_key");

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
                                $date = new \DateTime(date('Y-m-d H:i:s', strtotime($opt_val['expire_date'])), new \DateTimezone('UTC'));
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
                                    esc_html__('Your PublishPress license key has expired. For continued priority support, <a href="%s">please renew</a>.', 'capabilities-pro'),
                                    'https://publishpress.com/my-downloads/'
                                );
                            } elseif (!empty($opt_val['expire_date'])) {
                                $class = 'activating';
                                if ($expire_days < 30) {
                                    $is_err = true;
                                }

                                if ($expire_days == 1) {
                                    $msg = sprintf(
                                        esc_html__('Your PublishPress license key will expire today. For updates and priority support, <a href="%s">please renew</a>.', 'capabilities-pro'),
                                        'https://publishpress.com/my-downloads/'
                                    );
                                } elseif ($expire_days < 30) {
                                    $msg = sprintf(
                                        _n(
                                            'Your PublishPress license key will expire in %d day. For updates and priority support, <a href="%s">please renew</a>.',
                                            'Your PublishPress license key (for plugin updates) will expire in %d days. For updates and priority support, <a href="%s">please renew</a>.',
                                            $expire_days,
                                            'capabilities-pro'
                                        ),
                                        $expire_days,
                                        'https://publishpress.com/my-downloads/'
                                    );
                                } else {
                                    $class = "activating hidden";
                                }
                            } elseif (!$activated) {
                                $class = 'activating';
                            } else {
                                $class = "activating hidden";
                                $msg = '';
                            }
                            ?>

                            <?php if ($expired && (!empty($key))) : ?>
                                                <div class="pp-key-label">
                                <span class="pp-key-expired"><?php esc_html_e("Key Expired", 'capabilities-pro') ?></span>
                                                </div>
                                                <div class="pp-key-license">
                                <input name="<?php echo(esc_attr($id)); ?>" type="text" id="<?php echo(esc_attr($id)); ?>" style="display:none"/>
                                <button type="button" id="activation-button" name="activation-button"
                                        class="button-secondary"><?php esc_html_e('Deactivate', 'capabilities-pro'); ?></button>
                                                </div>
                            <?php else : ?>
                                <div class="pp-key-label">
                                    <span class="pp-key-active" <?php if (!$activated) echo 'style="display:none;"';?>><?php esc_html_e("Activated", 'capabilities-pro') ?></span>
                                </div>
                                                <div class="pp-key-license">
                                                    <input name="<?php echo(esc_attr($id)); ?>" type="text" placeholder="<?php esc_attr_e('Enter your license key', 'capabilities-pro');?>" id="<?php echo(esc_attr($id)); ?>"
                                    <?php echo ($activated) ? ' style="display:none"' : ''; ?> />
                                <button type="button" id="activation-button" name="activation-button"
                                    class="button-secondary"><?php if (!$activated) echo esc_html__('Activate', 'capabilities-pro'); else echo esc_html__('Deactivate', 'capabilities-pro'); ?></button>
                                                </div>
                            <?php endif; ?>

                            <img id="pp_support_waiting" class="waiting" style="display:none;position:relative" src="<?php echo esc_url_raw(admin_url('images/wpspin_light.gif')) ?>" alt=""/>

                            <?php
                            $update_info = [];

                            $info_link = '';

                            if (empty($suppress_updates)) {
                                $wp_plugin_updates = get_site_transient('update_plugins');
                                if (
                                    $wp_plugin_updates && isset($wp_plugin_updates->response[plugin_basename(PUBLISHPRESS_CAPS_PRO_FILE)])
                                    && !empty($wp_plugin_updates->response[plugin_basename(PUBLISHPRESS_CAPS_PRO_FILE)]->new_version)
                                    && version_compare($wp_plugin_updates->response[plugin_basename(PUBLISHPRESS_CAPS_PRO_FILE)]->new_version, PUBLISHPRESS_CAPS_VERSION, '>')
                                ) {
                                    $update_available = true;

                                    $slug = 'capabilities-pro';

                                    $_url = "plugin-install.php?tab=plugin-information&plugin=$slug&section=changelog&TB_iframe=true&width=600&height=800";
                                    $info_url = (!empty($use_network_admin)) ? network_admin_url($_url) : admin_url($_url);

                                    $info_link = "&nbsp;<span class='update-message'> &bull;&nbsp;&nbsp;<a href='$info_url' class='thickbox'>"
                                        . sprintf(esc_html__('view %s&nbsp;details', 'capabilities-pro'), $wp_plugin_updates->response[plugin_basename(PUBLISHPRESS_CAPS_PRO_FILE)]->new_version)
                                        . '</a></span>';
                                }
                            }
                            ?>

                            <div class="edd-key-links">
                                <div id="activation-status" class="<?php echo esc_attr($class)?>"></div>

                                <?php if (!empty($update_available)):?>
                                    <a href="<?php echo esc_url_raw(admin_url('update-core.php'));?>"><?php esc_html_e('Update&nbsp;Available', 'capabilities-pro'); ?></a>

                                    &nbsp;&bull;&nbsp;
                                <?php elseif (current_user_can('activate_plugins')):?>
                                    <?php
                                    $url = admin_url('admin.php?page=pp-capabilities-settings&publishpress_caps_refresh_updates=1');
                                    ?>
                                    <a href="<?php echo esc_url_raw(wp_nonce_url($url, 'publishpress_caps_refresh_updates'));?>"><?php esc_html_e('Update&nbsp;Check', 'capabilities-pro'); ?></a>
                                    &nbsp;&bull;&nbsp;
                                <?php endif;?>

                                <span class="pp-key-refresh">
                                <a href="https://publishpress.com/checkout/purchase-history/" target="_blank">
                                <?php esc_html_e('Account', 'capabilities-pro');?>
                                </a>
                                </span>

                                <?php if (!$activated) : ?>
                                    &nbsp;&bull;&nbsp;
                                    <span><?php printf('<a href="%s" target="_blank">%s</a>', 'https://publishpress.com/pricing/', esc_html__('Pricing', 'capabilities-pro')); ?></span>
                                <?php endif;?>
                            </div>

                            <?php if (!empty($is_err)) : ?>
                                <div id="activation-error" class="error"><?php echo esc_html($msg); ?></div>
                            <?php endif; ?>

                        </div>
                    </div>
                <?php
                $this->footerScripts($activated, $expired);
                ?>
                </td>
            </tr>

            <tr>
                <?php $checked = checked(!empty(get_option('cme_display_branding', 1)), true, false); ?>
                <th scope="row"> <?php esc_html_e('Display PublishPress Branding', 'capabilities-pro'); ?></th>
                <td>
                    <label for="" title="<?php esc_attr_e('Hide the PublishPress footer and other branding.', 'capabilities-pro');?>">
                        <input type="checkbox" name="cme_display_branding" id="cme_display_branding" autocomplete="off" value="1" <?php echo $checked;?>>
                    </label>
                    <br>
                </td>
            </tr>
            </tbody>
        </table>
            </td>
        </tr>
    </table>



    <table class="form-table" role="presentation" id="ppcs-tab-admin-menus" style="display:none;">
        <tbody>
            <tr>
                <?php $checked = checked(!empty(get_option('cme_admin_menus_restriction_priority', 1)), true, false); ?>
                <th scope="row"> <?php esc_html_e('Admin Menu', 'capabilities-pro'); ?></th>
                <td>
                    <label for="" title="<?php esc_attr_e('Admin Menus: treatment of multiple roles', 'capabilities-pro');?>">
                        <select name="cme_admin_menus_restriction_priority" id="cme_admin_menus_restriction_priority" autocomplete="off">
                            <option value="0"<?php echo ($checked) ? '' : ' selected';?>>
                                <?php esc_html_e('Most permissive: Any non-restricted user role allows access', 'capabilities-pro');?>
                            </option>
                            <option value="1"<?php echo ($checked) ? ' selected' : '';?>>
                                <?php esc_html_e('Most restrictive: Any restricted user role prevents access', 'capabilities-pro');?>
                            </option>
                        </select>
                        <div class='cme-subtext'>
                            <?php esc_html_e('This controls whether the most permissive or restrictive permissions are applied if a user has multiple roles.', 'capabilities-pro');?>
                        </div>
                    </label>
                    <br>
                </td>
            </tr>
        </tbody>
    </table>

    <?php
    }
} // end class
