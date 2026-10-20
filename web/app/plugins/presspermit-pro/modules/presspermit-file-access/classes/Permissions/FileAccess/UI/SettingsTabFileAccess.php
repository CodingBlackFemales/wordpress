<?php
namespace PublishPress\Permissions\FileAccess\UI;

use \PublishPress\Permissions\UI\SettingsAdmin as SettingsAdmin;

class SettingsTabFileAccess
{
    var $advanced_enabled;

    function __construct()
    {
        $pp = presspermit();

        $this->advanced_enabled = $pp->getOption('advanced_options');

        add_filter('presspermit_option_tabs', [$this, 'optionTabs'], 5);

        add_filter('presspermit_section_captions', [$this, 'sectionCaptions']);
        add_filter('presspermit_option_captions', [$this, 'optionCaptions']);
        add_filter('presspermit_option_sections', [$this, 'optionSections']);

        add_action('presspermit_file_access_options_ui', [$this, 'fileAccessOptionsUi']);

        if (!$pp->getOption('file_filtering_regen_key')) {
            $pp->updateOption('file_filtering_regen_key', substr(md5(rand()), 0, 16));  //phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand
        }
    }

    function optionTabs($tabs)
    {
        $tabs['file_access'] = esc_html__('File Access', 'presspermit-pro');
        return $tabs;
    }

    function sectionCaptions($sections)
    {
        $new = [
            'file_filtering' => esc_html__('File Access', 'presspermit-pro'),
        ];

        $key = 'file_access';
        $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;

        return $sections;
    }

    function optionCaptions($captions)
    {
        $opt = [
            'file_filtering_regen_key' => esc_html__('File Access Reset Key:', 'presspermit-pro'),
            'small_thumbnails_unfiltered' => esc_html__('Small Thumbnails Unfiltered', 'presspermit-pro'),
            'unattached_files_private' => esc_html__('Make Unattached Files Private', 'presspermit-pro'),
            'attached_files_private' => esc_html__('Make Attached Files Private', 'presspermit-pro'),
            'file_access_apply_redirect' => esc_html('Compatibility Mode: Apply extra redirect', 'presspermit-pro'),
        ];

        return array_merge($captions, $opt);
    }

    function optionSections($sections)
    {
        $new = [
            'file_filtering' => ['file_filtering_regen_key', 'unattached_files_private', 'attached_files_private', 'file_access_apply_redirect'],
        ];

        $new['file_filtering'][] = 'small_thumbnails_unfiltered';

        $key = 'file_access';
        $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;

        return $sections;
    }

    private function displayFilteringStatus()
    {
        global $wp_rewrite;
        $ui = \PublishPress\Permissions\UI\SettingsAdmin::instance(); 

        $site_url = untrailingslashit(get_option('siteurl'));
        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRules.php');
        
        $uploads = FileAccess::getUploadInfo();

        if (!got_mod_rewrite()) {
            $content_dir_notice = sprintf(
                __('%1$sNote%2$s: Direct access to uploaded file attachments cannot be filtered because mod_rewrite is not enabled on your server.', 'presspermit-pro'),
                '',
                ''
            );
        } elseif (!\PublishPress\Permissions\FileAccess\RewriteRules::siteConfigSupportsRewrite()) {
            $content_dir_notice = sprintf(
                __('%1$sNote%2$s: Direct access to uploaded file attachments will not be filtered due to your nonstandard UPLOADS path.', 'presspermit-pro'),
                '',
                ''
            );
        }

        elseif (empty($wp_rewrite->permalink_structure)) {
            $content_dir_notice = sprintf(
                __('%1$sNote%2$s: Direct access to uploaded file attachments cannot be filtered because WordPress permalinks are set to default.', 'presspermit-pro'),
                '',
                ''
            );
        } else {
            $attachment_filtering = true;
        }

        ?>
        <div>
            <?php
            SettingsAdmin::echoStr('file_filtering');

            if (!empty($content_dir_notice)) {
                echo '<br /><span class="pp-warning">';
                echo esc_html($content_dir_notice);
                echo '</span>';
            }
            ?>
        </div>

        <?php
        if (is_multisite() && \PublishPress\Permissions\FileAccess\Network::msBlogsRewriting() && is_super_admin()) {
            $network_activated = PWP::isNetworkActivated();

            $default_all_sites = get_site_option('presspermit_last_file_rules_all_sites');

            if (!defined('PP_SUPPRESS_SETTINGS_HTACCESS')) {
                require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRulesNetLegacy.php');
                $rules = \PublishPress\Permissions\FileAccess\RewriteRulesNetLegacy::build_main_rules(
                    ['ms_all_sites' => $default_all_sites, 
                    'current_site_only' => !$ppff_network_activated]
                );
            }

            ?>
            <br/>
            <div class="pp-admin-info">

                <p>
                    <?php
                    echo "<strong>" . esc_html__('Multisite File Access Configuration:', 'presspermit') . "</strong> <br />";

                    printf(
                        esc_html(SettingsAdmin::getStr('ms_blogs_file_filtering_config')),
                        '<strong>',
                        '</strong>'
                    );
                    ?>
                </p>

                <?php
                $suppress_htaccess_display = defined('PP_SUPPRESS_SETTINGS_HTACCESS') || (PWP::empty_REQUEST('pp_show_rules') && strlen($rules) > 1000);

                if (!$suppress_htaccess_display) :
                    ?>
                    <textarea rows='10' cols='110' readonly='readonly'><?php echo esc_html($rules); ?></textarea>
                <?php else : ?>
                    <div>
                        <a href="<?php echo esc_url(admin_url("admin.php?page=presspermit-settings&amp;pp_tab=file_access&amp;pp_show_rules=1")); ?>">
                        <?php esc_html_e('show required rules', 'presspermit-pro'); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <div>
                    <?php
                    if ($ppff_network_activated && !defined('PP_SUPPRESS_SETTINGS_HTACCESS_CHECK')) {
                        if (file_exists(ABSPATH . '/wp-admin/includes/misc.php'))
                            include_once(ABSPATH . '/wp-admin/includes/misc.php');

                        if (file_exists(ABSPATH . '/wp-admin/includes/file.php'))
                            include_once(ABSPATH . '/wp-admin/includes/file.php');

                        $htaccess_path = \PublishPress\Permissions\FileAccess\NetworkLegacy::get_home_path() . '.htaccess';

                        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_is_writable
                        if (!file_exists($htaccess_path) || !is_writable($htaccess_path)) :
                            ?>
                            <br/>
                            <div class="pp-warning">
                                <?php SettingsAdmin::echoStr('ms_blogs_htaccess_missing'); ?>
                            </div>
                        <?php else :
                            $contents = file_get_contents($htaccess_path); //phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

                            if (false === strpos($contents, $rules)) :
                                ?>
                                <br/>
                                <div class="pp-warning">
                                    <?php SettingsAdmin::echoStr('ms_blogs_htaccess_needs_update'); ?>
                                </div>
                            <?php else : ?>
                                <br/>
                                <div class="pp-success">
                                    <?php SettingsAdmin::echoStr('ms_blogs_htaccess_ok'); ?>
                                </div>
                            <?php
                            endif;
                        endif; // .htaccess is writeable
                    }
                    ?>

                    <?php
                    SettingsAdmin::echoStr('ms_blogs_rule_maint');
                    echo ' ';

                    printf(
                        esc_html(SettingsAdmin::getStr('ms_blogs_rule_maint_note')),
                        '<em>',
                        '</em>'
                    );
                    ?>
                </div>

                <?php if ($ppff_network_activated) : ?>
                    <div class="submit" style="padding:4px;padding-bottom:0;text-align:center">
                        <?php
                        $msg = SettingsAdmin::getStr('ms_blogs_network_activated_warning');

                        wp_nonce_field('ppff-admin', '_ppff-nonce');
                        ?>
                        <input type="submit" name="ppff_update_mu_htaccess"
                               value="<?php SettingsAdmin::echoStr('ms_blogs_network_update_htaccess'); ?>" 
                               onclick="<?php echo "javascript:if (confirm('" . esc_attr($msg) . "')) {return true;} else {return false;}"; ?>"/>
                    </div>

                    <?php
                    $name = 'pp_htaccess_all_sites';
                    ?>
                    <div style="text-align:center">
                        <label for='<?php echo esc_attr($name) ?>'><select name='<?php echo esc_attr($name); ?>' id='<?php echo esc_attr($name); ?>' autocomplete='off'>
                                <option value="0"><?php SettingsAdmin::echoStr('ms_blogs_network_update_htaccess_if_files'); ?></option>
                                <option value="1" <?php if ($default_all_sites) echo ' selected=selected"'; ?>><?php SettingsAdmin::echoStr('ms_blogs_network_update_htaccess_all_site'); ?></option>
                                <option value="remove"><?php SettingsAdmin::echoStr('ms_blogs_network_update_htaccess_remove_rules'); ?></option>
                            </select></label>
                    </div>
                <?php else : ?>
                    <br/>
                    <div class="pp-warning">
                        <?php SettingsAdmin::echoStr('ms_blogs_not_network_activated'); ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }
        ?>
        <?php
    }

    function fileAccessOptionsUi()
    {
        $ui = \PublishPress\Permissions\UI\SettingsAdmin::instance(); 
        $tab = 'file_access';

        $section = 'file_filtering';                    // --- MAIN SECTION ---
        if (!empty($ui->form_options[$tab][$section])) : ?>
            <tr>
                <th scope="row"><?php echo esc_html($ui->section_captions[$tab][$section]); ?></th>
                <td>
                    <?php
                    $this->displayFilteringStatus();

                    echo '<br />';
                    $ui->optionCheckbox('unattached_files_private', $tab, 'file_filtering', true, '');

                    if (defined('PP_ATTACHED_FILE_AUTOPRIVACY')) {
                        $ui->optionCheckbox('attached_files_private', $tab, 'file_filtering', true, '');
                        echo '<br />';
                    }

                    if ($this->advanced_enabled) {
                        $ui->optionCheckbox('small_thumbnails_unfiltered', $tab, 'file_filtering', true, '');
                    }
                    
                    echo '<br />';
                    
                    $ui->optionCheckbox('file_access_apply_redirect', $tab, 'file_filtering', true, '');

                    $id = 'file_filtering_regen_key';  // retrieve for link display even if option setting is not enabled
                    $val = get_option("presspermit_{$id}");

                    if ($this->advanced_enabled) :
                        $ui->all_options[] = $id;

                        echo "<br /><div><label for='" . esc_attr($id) . "'>";
                        esc_html_e('File Access Reset Key:', 'presspermit-pro');
                        ?>
                        <div style="width: 300px;">
                            <div class="key-display">
                                <div class="key-value" id="<?php echo esc_attr($id); ?>"><?php echo esc_html($val); ?></div>
                                <button type="button" class="copy-btn" id="copy-btn" onclick="copyFileFilteringKey()">
                                    <?php esc_html_e('Copy', 'presspermit-pro'); ?>
                                </button>
                            </div>
                        </div>
                        <script>
                        function copyFileFilteringKey() {
                            var keyElem = document.getElementById('file_filtering_regen_key');
                            var btnElem = document.getElementById('copy-btn');
                            if (!keyElem || !btnElem) return;
                            var text = keyElem.textContent || keyElem.innerText;
                            var originalText = btnElem.innerHTML;
                            var copiedText = '<?php echo esc_js(__('Copied!', 'presspermit-pro')); ?>';
                            if (navigator.clipboard) {
                                navigator.clipboard.writeText(text).then(function() {
                                    btnElem.innerHTML = copiedText;
                                    setTimeout(function() {
                                        btnElem.innerHTML = originalText;
                                    }, 1500);
                                });
                            } else {
                                // fallback for older browsers
                                var tempInput = document.createElement('input');
                                tempInput.value = text;
                                document.body.appendChild(tempInput);
                                tempInput.select();
                                document.execCommand('copy');
                                document.body.removeChild(tempInput);
                                btnElem.innerHTML = copiedText;
                                setTimeout(function() {
                                    btnElem.innerHTML = originalText;
                                }, 1500);
                            }
                        }
                        </script>
                        </div>
                    <?php endif; ?>
                    <div style="margin-top:10px; margin-bottom: 20px;" class="pp-hint">
                        <?php
                        if ($val) {
                            $url = site_url("index.php?action=presspermit-expire-file-rules&amp;key=$val");
                            echo '<p>';
                            if (is_multisite()) {
                                printf(
                                    esc_html(SettingsAdmin::getStr('file_filtering_regen_multisite')),
                                    '<strong>',
                                    '</strong>',
                                    '<strong>',
                                    '</strong>'
                                );
                            } else {
                                printf(
                                    esc_html(SettingsAdmin::getStr('file_filtering_regen')),
                                    '<strong>',
                                    '</strong>',
                                    '<strong>',
                                    '</strong>'
                                );
                            }
                            echo '</p>';
                            ?>
                            <a href="<?php echo esc_url($url); ?>" class="button-secondary">
                                <?php esc_html_e('Regenerate Access File', 'presspermit-pro'); ?>
                            </a>
                            <?php
                        } else {
                            SettingsAdmin::echoStr('file_filtering_regen_key_prompt');
                        }
                        ?>
                    </div>
                    <div>
                    <p>
                    <?php esc_html_e(SettingsAdmin::getStr('file_filtering_regen_attachment_util')); ?>
                    </p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=presspermit-attachments_utility')); ?>" class="button-secondary" target="_blank"><?php esc_html_e('Attachments Utility', 'presspermit-pro'); ?></a>
                    </div>
                    <div>
                        <?php
                        if (
                            !defined('PP_NGINX_CFG_PATH') 
                            && isset($_SERVER['SERVER_SOFTWARE']) 
                            && stripos(sanitize_text_field($_SERVER['SERVER_SOFTWARE']), 'nginx') !== false
                        ) {
                            printf(
                                esc_html__('For Nginx integration, see %spublishpress.com documentation%s.', 'presspermit-pro'),
                                '<a href="' . 'https://publishpress.com/knowledge-base/file-filtering-nginx/' . '" target="_blank">',
                                '</a>'
                            );
                        }
                        ?>
                    </div>

                </td>
            </tr>
        <?php endif;
    }
} // end class
