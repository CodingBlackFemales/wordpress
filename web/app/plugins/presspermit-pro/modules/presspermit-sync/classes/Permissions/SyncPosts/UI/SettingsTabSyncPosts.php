<?php
namespace PublishPress\Permissions\SyncPosts\UI;

use \PublishPress\Permissions\UI\SettingsAdmin as SettingsAdmin;

/**
 * PressPermit Sync Settings
 *
 * @package PressPermit
 * @author Kevin Behrens
 * @copyright Copyright (c) 2025, PublishPress
 * 
 */

class SettingsTabSyncPosts
{
    var $bbp_teaser_disabled = false;

    function __construct()
    {
        add_filter('presspermit_option_tabs', [$this, 'optionTabs'], 14);

        add_filter('presspermit_section_captions', [$this, 'sectionCaptions']);
        add_filter('presspermit_option_captions', [$this, 'optionCaptions']);
        add_filter('presspermit_option_sections', [$this, 'optionSections'], 20);

        add_action('presspermit_sync_posts_options_ui', [$this, 'optionsUI']);
        add_action('admin_notices', [$this, 'displayValidationErrors']);
    }

    function optionTabs($tabs)
    {
        $tabs['sync_posts'] = esc_html__('User Posts', 'presspermit-pro');
        return $tabs;
    }

    function sectionCaptions($sections)
    {
        // Sync Posts tab
        $new = [
            'sync_posts' =>             esc_html__('Synchronize Posts', 'presspermit-pro'),
        ];

        $key = 'sync_posts';
        $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;

        return $sections;
    }

    function optionCaptions($captions)
    {
        $opt = [];

        $opt['sync_posts_to_users'] =                   esc_html__('Create Posts for Users', 'presspermit-pro');
        $opt['sync_posts_to_users_apply_permissions'] = esc_html__('Grant Author Permissions', 'presspermit-pro');
        $opt['sync_posts_to_users_types'] =             esc_html__('Post Types', 'presspermit-pro');
        $opt['sync_posts_to_users_post_field'] =        esc_html__('Match Post Field', 'presspermit-pro');
        $opt['sync_posts_to_users_user_field'] =        esc_html__('Match User Field', 'presspermit-pro');
        $opt['sync_posts_to_users_role'] =              esc_html__('User Role', 'presspermit-pro');
        $opt['sync_posts_to_users_post_parent'] =       esc_html__('Parent Post', 'presspermit-pro');
        $opt['sync_posts_to_users_quantity'] =          esc_html__('Quantity', 'presspermit-pro');
        $opt['sync_posts_to_users_status'] =            esc_html__('Post Status', 'presspermit-pro');

        return array_merge($captions, $opt);
    }

    function optionSections($sections)
    {
        // Sync Posts tab
        $new = [
            'sync_posts' => [
                'sync_posts_to_users',
                'sync_posts_to_users_apply_permissions',
                'sync_posts_to_users_types',
                'sync_posts_to_users_post_field',
                'sync_posts_to_users_user_field',
                'sync_posts_to_users_role',
                'sync_posts_to_users_post_parent',
                'sync_posts_to_users_quantity',
                'sync_posts_to_users_status'
            ],
        ];

        $tab = 'sync_posts';
        $sections[$tab] = (isset($sections[$tab])) ? array_merge($sections[$tab], $new) : $new;

        return $sections;
    }

    function optionsUI()
    {
        $ui = \PublishPress\Permissions\UI\SettingsAdmin::instance(); 
        $tab = 'sync_posts';

        $pp = presspermit();

        $section = 'sync_posts';                                    // --- SYNC POSTS SECTION ---
        if (!empty($ui->form_options[$tab][$section])) : ?>
                <tr>
                    <td scope="row" colspan="2">
                        <div class="pp-sync-container">
                        <?php $cur_val = presspermit()->getOption('sync_posts_to_users'); ?>
                            <!-- Sync Posts Section -->
                            <div class="pp-sync-section">
                                <div class="pp-sync-section-body">
                                    <div id="sync_posts_to_users_container" class="pp-sync-toggle-container">
                                        <label class="pp-sync-toggle-switch" for="<?php echo esc_attr('sync_posts_to_users'); ?>">
                                            <input type="checkbox" name="<?php echo esc_attr('sync_posts_to_users'); ?>" value="1" <?php checked('1', $cur_val); ?> id="<?php echo esc_attr('sync_posts_to_users'); ?>" autocomplete="off" />
                                            <span class="pp-sync-slider"></span>
                                        </label>
                                        <div>
                                            <strong><?php esc_html_e('Create Posts for Users', 'presspermit-pro'); ?></strong>
                                            <p class="pp-sync-text-muted"><?php echo esc_html(SettingsAdmin::getStr('sync_posts_to_users')); ?></p>
                                        </div>
                                        <?php
                                        // Manually add to all_options since we're not using $ui->optionCheckbox()
                                        $ui->all_options[] = 'sync_posts_to_users';
                                        ?>
                                    </div>
                                    <?php
                                    $style = ($ui->getOption('sync_posts_to_users')) ? '' : 'display:none';
                                    $cur_val = presspermit()->getOption('sync_posts_to_users_apply_permissions');
                                    ?>
                                    <div id="sync_posts_to_users_apply_permissions_container" class="pp-sync-toggle-container" style='<?php echo esc_attr($style); ?>'>
                                        <label class="pp-sync-toggle-switch" for="<?php echo esc_attr('sync_posts_to_users_apply_permissions'); ?>">
                                            <input type="checkbox" name="<?php echo esc_attr('sync_posts_to_users_apply_permissions'); ?>" value="1" <?php checked('1', $cur_val); ?> id="<?php echo esc_attr('sync_posts_to_users_apply_permissions'); ?>" autocomplete="off" />
                                            <span class="pp-sync-slider"></span>
                                        </label>
                                        <div>
                                            <strong><?php esc_html_e('Grant Author Permissions', 'presspermit-pro'); ?></strong>
                                            <p class="pp-sync-text-muted"><?php echo esc_html(SettingsAdmin::getStr('sync_posts_to_users_apply_permissions')); ?></p>
                                        </div>
                                        <?php
                                        // Manually add to all_options since we're not using $ui->optionCheckbox()
                                        $ui->all_options[] = 'sync_posts_to_users_apply_permissions';
                                        ?>
                                    </div>
                        <?php
                        $skip_post_types = apply_filters(
                            'presspermit_disabled_sync_posts_to_users_types', 
                            ['block', 'attachment', 'forum', 'topic', 'reply']
                        );

                        $option_names = [
                            'sync_posts_to_users_types',
                            'sync_posts_to_users_new',
                            'sync_posts_to_users_existing',
                            'sync_posts_to_users_post_field',
                            'sync_posts_to_users_user_field',
                            'sync_posts_to_users_role',
                            'sync_posts_to_users_post_parent',
                            'sync_posts_to_users_quantity',
                            'sync_posts_to_users_status'
                        ];

                        $ui->all_otype_options = array_merge($ui->all_otype_options, $option_names);
                        $opt_values = [];

                        $titles = [];
                        $titles['sync_posts_to_users_post_field'] = '';
                        $titles['sync_posts_to_users_user_field'] = '';
                        $titles['sync_posts_to_users_user_field_text'] = '';
                        $titles['sync_posts_to_users_role'] = '';
                        $titles['sync_posts_to_users_post_parent'] = '';
                        $titles['sync_posts_to_users_quantity'] = '';
                        $titles['sync_posts_to_users_status'] = '';
                        $titles['suggestions'] = '';

                        foreach(array_keys($titles) as $string_id) {
                            $titles[$string_id] = SettingsAdmin::getStr($string_id);
                        }

                        $suggested_values = [];

                        $all_post_types = get_post_types([], 'object');
                        $post_type_objects = $all_post_types; // Preserve original objects

                        // Filter by enabled post types from PressPermit settings
                        $enabled_post_types = presspermit()->getEnabledPostTypes();
                        foreach ($all_post_types as $post_type => $post_type_obj) {
                            if (!in_array($post_type, $enabled_post_types, true)) {
                                unset($all_post_types[$post_type]);
                                unset($post_type_objects[$post_type]);
                            }
                        }

                        $private_types = SyncPosts::getAllowedPrivatePostTypes();
                        foreach ($all_post_types as $post_type => $post_type_obj) {
                            if (is_object($post_type_obj) && empty($post_type_obj->public) && empty($post_type_obj->show_ui) && !in_array($post_type, $private_types, true)) {
                                unset($all_post_types[$post_type]);
                                unset($post_type_objects[$post_type]);
                            }
                        }

                        // retrieve stored values, blending in defaults and stripping out disabled types
                        foreach ($option_names as $option_name) {
                            $defaults = array_fill_keys(array_keys($all_post_types), '');
                            $stored_settings = $ui->getOptionArray($option_name);

                            if (!defined('PPP_DISABLE_METAKEY_SUGGESTIONS')) {
                                if ('sync_posts_to_users_post_field' == $option_name) {
                                    if (!empty($opt_values['sync_posts_to_users_types'])) {
                                        // query suggested postmeta keys
                                        if (defined('presspermit_sync_posts_SHOW_ALL_META_KEYS')) {
                                            $key_like = false;
                                        } else {
                                            $key_like = (array)apply_filters(
                                                'presspermit_sync_posts_to_users_postmeta_keylike', 
                                                [
                                                    '%email%', 
                                                    '%phone%', 
                                                    '%mobile%', 
                                                    '%cell%', 
                                                    '%$skype%', 
                                                    '%twitter%', 
                                                    '%_tlink%', 
                                                    '%facebook%', 
                                                    '%fcbk%', 
                                                    '%linked%', 
                                                    '%youtube%', 
                                                    '%google%', 
                                                    '%instagram%', 
                                                    '%github%', 
                                                    '%user%', 
                                                    '%_id'
                                                ]
                                            );
                                        }

                                        $suggested_values[$option_name] = $this->get_suggested_meta_keys(
                                            array_keys($opt_values['sync_posts_to_users_types']), 
                                            $key_like
                                        );

                                        foreach ($suggested_values[$option_name] as $post_type => $suggestions) {
                                            // re-order suggestions with email keys first
                                            $_email_suggestions = [];
                                            $_twitter_suggestions = [];
                                            $_user_suggestions = [];
                                            $_other_suggestions = [];
                                            foreach ($suggestions as $k => $val) {
                                                if (false !== strpos($k, 'email')) {
                                                    $_email_suggestions[$k] = $val;
                                                } elseif (false !== strpos($k, 'twitter') || false !== strpos($k, '_tlink') 
                                                || false !== strpos($k, 'facebook') || false !== strpos($k, 'fcbk') 
                                                || false !== strpos($k, 'linked') || false !== strpos($k, 'github') 
                                                || false !== strpos($k, 'youtube') || false !== strpos($k, 'google') 
                                                || false !== strpos($k, 'instagram')
                                                ) {
                                                    $_twitter_suggestions[$k] = $val;
                                                } elseif (false !== strpos($k, 'phone') || false !== strpos($k, 'mobile') 
                                                || false !== strpos($k, 'cell') || false !== strpos($k, 'skype') 
                                                || false !== strpos($k, 'user')
                                                ) {
                                                    $_user_suggestions[$k] = $val;
                                                } else {
                                                    $_other_suggestions[$k] = $val;
                                                }
                                            }

                                            $suggested_values[$option_name][$post_type] = array_merge(
                                                $_email_suggestions, $_twitter_suggestions, $_user_suggestions, $_other_suggestions
                                            );
                                            
                                            $suggestions = $suggested_values[$option_name][$post_type];
                                            reset($suggestions);

                                            $first = key($suggestions);

                                            if (false !== strpos($first, 'email')) {
                                                $defaults[$post_type] = $first;
                                            } else {
                                                $defaults[$post_type] = 'post_title';
                                            }

                                            // default cleared value back to first suggestion
                                            if (empty($stored_settings[$post_type])) {
                                                unset($stored_settings[$post_type]);

                                                // ...and store that default immediately
                                                $arr = $pp->getOption($option_name);
                                                $arr[$post_type] = $defaults[$post_type];

                                                $pp->updateOption($option_name, $arr);
                                            }
                                        }
                                    }
                                }
                            }

                            if ('sync_posts_to_users_post_field' == $option_name) {
                                $defaults = array_merge($defaults, [
                                    'jv_team_members' => 'jv_team_email_address',
                                    'staff-member' => '_ikcf_email',
                                    'emd_employee' => 'emd_employee_email',
                                    'staff' => 'staffer_staff_email',
                                    'team' => 'email',
                                    'team_mf' => 'contact_email',
                                    'team_manager' => 'tm_emailid',
                                ]);
                                $defaults = (array)apply_filters('presspermit_sync_posts_to_users_default_post_field', $defaults);

                                foreach ($defaults as $_post_type => $val) {
                                    if (!$val) continue;

                                    $suggested_values['sync_posts_to_users_post_field'][$_post_type][$val] = true;
                                }

                                $post_field_defaults = $defaults;
                            } elseif ('sync_posts_to_users_user_field' == $option_name) {
                                $defaults = (array)apply_filters('presspermit_sync_posts_to_users_default_user_field', []);
                            }

                            // if post field is defaulting to an email metakey, default user field to user_email
                            if ('sync_posts_to_users_user_field' == $option_name) {
                                foreach ($post_field_defaults as $_post_type => $val) {
                                    if ($val && (false !== strpos($val, 'email') || false !== strpos($val, 'e-mail'))) {
                                        $defaults[$_post_type] = 'user_email';
                                    }
                                }
                            }

                            // Set default quantity to 1 post per user
                            if ('sync_posts_to_users_quantity' == $option_name) {
                                $defaults = array_fill_keys(array_keys($all_post_types), '1');
                            }

                            // Set default status to draft
                            if ('sync_posts_to_users_status' == $option_name) {
                                $defaults = array_fill_keys(array_keys($all_post_types), 'draft');
                            }

                            // Set defaults for new users sync (migrate from old sync_posts_to_users_types for backward compatibility)
                            if ('sync_posts_to_users_new' == $option_name) {
                                $defaults = array_fill_keys(array_keys($all_post_types), '');
                                // Migrate from old option if it exists and new option is empty
                                $old_types = $ui->getOptionArray('sync_posts_to_users_types');
                                if (!empty($old_types) && empty($stored_settings)) {
                                    $stored_settings = $old_types;
                                }
                            }

                            // Set defaults for existing users sync (disabled by default)
                            if ('sync_posts_to_users_existing' == $option_name) {
                                $defaults = array_fill_keys(array_keys($all_post_types), '');
                            }

                            // add enabled types whose settings have never been stored
                            $opt_values[$option_name] = array_merge($defaults, $stored_settings);

                            // skip stored types that are not enabled
                            $opt_values[$option_name] = array_intersect_key($opt_values[$option_name], $all_post_types);

                            $opt_values[$option_name] = array_diff_key($opt_values[$option_name], array_fill_keys($skip_post_types, true));
                        }

                        $style = ($ui->getOption('sync_posts_to_users')) ? '' : 'display:none';

                        global $wp_roles;
                        $roles = $wp_roles->get_names();
                        uasort($roles, 'strnatcasecmp');

                        $main_check_title = SettingsAdmin::getStr('sync_title_main_checkbox');
                        
                        $sync_existing_title = SettingsAdmin::getStr('sync_existing_title');

                        $post_field_captions = [
                            'post_title' => __('Post Title', 'presspermit-pro'), 
                            'post_name' => __('Post slug', 'presspermit-pro')
                        ];

                        $user_field_captions = [
                            'display_name' => __('User Display Name', 'presspermit-pro'), 
                            'user_email' => __('User email', 'presspermit-pro'), 
                            'user_login' => __('User login', 'presspermit-pro'), 
                            'user_nicename' => __('User nicename', 'presspermit-pro')
                        ];

                        $any_hierarchical = false;

                        // Create ordered post types - show all enabled post types, not just those with sync enabled
                        $ordered_post_types = [];
                        foreach (array_keys($all_post_types) as $post_type) {
                            $type_obj = get_post_type_object($post_type);
                            if ($type_obj) {
                                $ordered_post_types[$post_type] = (isset($type_obj->labels->name)) 
                                ? $type_obj->labels->name 
                                : $post_type;

                                if (!empty($type_obj->hierarchical)) {
                                    $any_hierarchical = true;
                                }
                            }
                        }
                        ?>
                        <?php if (SyncPosts::userSyncLoaded() && !empty(SyncPosts::userSync()->log)) :
                            $sync_executed = true; ?>
                            <div class="activating pp-sync-results1 pp-sync-note-box">
                                <h3 style="display: flex;align-items: center;justify-content: left;gap: 8px;margin-top: 0;">
                                    <i class="dashicons dashicons-info"></i>
                                    <?php esc_html_e('User Posts Created:', 'presspermit-pro'); ?>
                                </h3>
                                <ul style="margin: 0;">
                                    <?php foreach (SyncPosts::userSync()->log as $entry) : ?>
                                        <li><?php echo esc_html($entry); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <table id="sync_posts_to_users_settings" class='agp-vtight_input1 agp-rlabel1 pp-permissions-table' style='<?php echo esc_attr($style); ?>'>
                            <tr>
                                <th class="pp-expand" style="width: 1% !important;"></th>
                                <th class="pp-posttype" style="width: 1% !important;">
                                    <?php esc_html_e('Post Type', 'presspermit-pro'); ?>
                                    <?php $this->generateTooltip(esc_html__('Select where posts will be created for users.', 'presspermit-pro'),'','top'); ?>
                                </th>
                                <th class="pp-new-users" style="width: 1% !important;">
                                    <?php esc_html_e('New Users', 'presspermit-pro'); ?>
                                    <?php $this->generateTooltip(esc_html__('Create posts for new users.', 'presspermit-pro'),'','top'); ?>
                                </th>
                                <th class="pp-sync-now" style="width: 1% !important;">
                                    <?php esc_html_e('Current Users', 'presspermit-pro'); ?>
                                    <?php $this->generateTooltip(esc_html__('Create posts for existing users.', 'presspermit-pro'),'','top'); ?>
                                </th>
                                <th class="pp-sync-role" style="width: 30% !important;">
                                    <?php esc_html_e('Role', 'presspermit-pro'); ?>
                                    <?php $this->generateTooltip(esc_html__('Create posts for users in these roles.', 'presspermit-pro'),'','top'); ?>
                                </th>
                                <th class="pp-sync-quantity" style="width: 1% !important;">
                                    <?php esc_html_e('Quantity', 'presspermit-pro'); ?>
                                    <?php $this->generateTooltip(esc_html__('Set the number of posts to create for each user.', 'presspermit-pro'),'','top'); ?>
                                </th>
                                <th class="pp-sync-status" style="width: 5% !important;">
                                    <?php esc_html_e('Status', 'presspermit-pro'); ?>
                                    <?php $this->generateTooltip(esc_html__('Set the status for created posts.', 'presspermit-pro'),'','top'); ?>
                                </th>
                            </tr>
                            <?php
                            // Show message if no post types are enabled
                            if (empty($ordered_post_types)) : ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #888;">
                                        <span style="vertical-align: -webkit-baseline-middle"><?php esc_html_e('No post types are enabled for user pages.', 'presspermit-pro'); ?></span>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=presspermit-settings')); ?>" class="button button-primary">
                                            <?php esc_html_e('Go to Permissions Settings', 'presspermit-pro'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endif;
                            foreach ($ordered_post_types as $object_type => $post_type_label) :
                                if ('attachment' == $object_type) continue;

                                // Check if new users sync is enabled for this post type (separate from old sync_posts_to_users_types)
                                if (
                                    isset($_POST['_wpnonce']) &&
                                    wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), 'presspermit-settings') &&
                                    isset($_POST["sync_posts_to_users_new"][$object_type])
                                ) {
                                    $new_users_enabled = !empty($_POST["sync_posts_to_users_new"][$object_type]);
                                } else {
                                    $new_users_enabled = isset($opt_values['sync_posts_to_users_new'][$object_type]) 
                                        ? !empty($opt_values['sync_posts_to_users_new'][$object_type])
                                        : false;
                                }
                                
                                // Check if current users sync is enabled for this post type
                                $existing_users_setting = isset($_POST["sync_posts_to_users_existing"][$object_type]) 
                                    ? !empty($_POST["sync_posts_to_users_existing"][$object_type])
                                    : (isset($opt_values['sync_posts_to_users_existing'][$object_type]) 
                                        ? !empty($opt_values['sync_posts_to_users_existing'][$object_type])
                                        : false);
                                
                                // Row is active if either new users OR existing users is enabled
                                $row_active = $new_users_enabled || $existing_users_setting;
                                // Always show the row - don't hide it when both checkboxes are off
                                $row_style = '';

                                $option_name = 'sync_posts_to_users_new';
                                $id = $option_name . '-' . $object_type;
                                $name = "{$option_name}[$object_type]";
                                ?>
                                <!-- Main row -->
                                <tr class="pp-main-row pp-<?php echo esc_attr($object_type); ?>" data-post-type="<?php echo esc_attr($object_type); ?>" data-row-active="<?php echo esc_attr($row_active ? '1' : '0'); ?>">
                                    <td class="pp-expand">
                                        <span class="pp-expand-icon"
                                              style="cursor: pointer; <?php echo esc_attr($row_active ? '' : 'display:none;'); ?>"
                                              title="<?php esc_attr_e('Click to expand/collapse advanced settings', 'presspermit-pro'); ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" height="32" viewBox="0 0 20 20" width="32"><path d="M1.91075 7.38266C2.28004 6.24053 2.88839 5.19213 3.69109 4.30364C3.82683 4.15339 4.03978 4.09984 4.23044 4.16802L6.14873 4.85392C6.6688 5.03977 7.24107 4.76883 7.42692 4.24875C7.4452 4.19762 7.45927 4.14507 7.469 4.09173L7.83446 2.08573C7.8708 1.88627 8.02398 1.7285 8.22227 1.6863C8.80246 1.5628 9.39734 1.5 10 1.5C10.6023 1.5 11.1968 1.56273 11.7767 1.68607C11.9749 1.72824 12.1281 1.88591 12.1645 2.08529L12.531 4.09165C12.6301 4.63497 13.1509 4.9951 13.6942 4.89601C13.7476 4.88627 13.8002 4.87219 13.8512 4.85395L15.7696 4.16802C15.9602 4.09984 16.1732 4.15339 16.3089 4.30364C17.1116 5.19213 17.72 6.24053 18.0893 7.38266C18.1516 7.57534 18.0915 7.78658 17.9371 7.91764L16.3823 9.23773C15.9613 9.5952 15.9098 10.2263 16.2673 10.6473C16.3024 10.6887 16.3409 10.7271 16.3823 10.7623L17.9371 12.0824C18.0915 12.2134 18.1516 12.4247 18.0893 12.6173C17.72 13.7595 17.1116 14.8079 16.3089 15.6964C16.1732 15.8466 15.9602 15.9002 15.7696 15.832L13.8513 15.1461C13.3312 14.9602 12.759 15.2312 12.5731 15.7512C12.5548 15.8024 12.5408 15.8549 12.531 15.9085L12.1645 17.9147C12.1281 18.1141 11.9749 18.2718 11.7767 18.3139C11.1968 18.4373 10.6023 18.5 10 18.5C9.39734 18.5 8.80246 18.4372 8.22227 18.3137C8.02398 18.2715 7.8708 18.1137 7.83446 17.9143L7.46902 15.9084C7.36993 15.365 6.84916 15.0049 6.30583 15.104C6.25241 15.1137 6.19987 15.1278 6.14881 15.1461L4.23044 15.832C4.03978 15.9002 3.82683 15.8466 3.69109 15.6964C2.88839 14.8079 2.28004 13.7595 1.91075 12.6173C1.84845 12.4247 1.90852 12.2134 2.06289 12.0824L3.61773 10.7623C4.03872 10.4048 4.09021 9.77373 3.73274 9.35274C3.69759 9.31135 3.65913 9.27288 3.61775 9.23775L2.06289 7.91764C1.90852 7.78658 1.84845 7.57534 1.91075 7.38266ZM2.9713 7.37709L4.26499 8.47546C4.34778 8.54576 4.42471 8.62269 4.49501 8.70548C5.20995 9.54746 5.10697 10.8096 4.26497 11.5246L2.9713 12.6229C3.26335 13.4051 3.6848 14.1322 4.21623 14.7751L5.81221 14.2044C5.91449 14.1679 6.01958 14.1397 6.12643 14.1202C7.21307 13.922 8.25462 14.6423 8.45281 15.729L8.75678 17.3975C9.16465 17.4655 9.58 17.5 10 17.5C10.4197 17.5 10.8348 17.4656 11.2424 17.3976L11.5472 15.7289C11.5667 15.6221 11.5949 15.517 11.6314 15.4147C12.0031 14.3746 13.1477 13.8327 14.1879 14.2044L15.7838 14.7751C16.3152 14.1322 16.7367 13.4051 17.0287 12.6229L15.735 11.5245C15.6522 11.4542 15.5753 11.3773 15.505 11.2945C14.7901 10.4525 14.8931 9.1904 15.7351 8.47544L17.0287 7.37709C16.7367 6.59486 16.3152 5.86783 15.7838 5.22494L14.1878 5.79559C14.0855 5.83214 13.9804 5.8603 13.8736 5.87979C12.787 6.07796 11.7454 5.3577 11.5473 4.27119L11.2424 2.60235C10.8348 2.53443 10.4197 2.5 10 2.5C9.58 2.5 9.16465 2.53448 8.75678 2.60249L8.45279 4.27105C8.43331 4.37791 8.40515 4.48299 8.3686 4.58527C7.99689 5.62542 6.85236 6.1673 5.81213 5.79556L4.21623 5.22494C3.6848 5.86783 3.26335 6.59486 2.9713 7.37709ZM7.50001 10C7.50001 8.61929 8.6193 7.5 10 7.5C11.3807 7.5 12.5 8.61929 12.5 10C12.5 11.3807 11.3807 12.5 10 12.5C8.6193 12.5 7.50001 11.3807 7.50001 10ZM8.50001 10C8.50001 10.8284 9.17159 11.5 10 11.5C10.8284 11.5 11.5 10.8284 11.5 10C11.5 9.17157 10.8284 8.5 10 8.5C9.17159 8.5 8.50001 9.17157 8.50001 10Z" fill="#212121"/></svg>
                                        </span>
                                    </td>
                                    <td class="pp-posttype">
                                        <label for='<?php echo esc_attr($id); ?>' title='<?php echo esc_attr($object_type); ?>'><?php echo esc_html($ordered_post_types[$object_type]); ?></label>
                                    </td>
                                    <td class="pp-new-users" style="text-align: center !important;">
                                        <?php
                                        $checked = ($new_users_enabled) ? ' checked ' : '';
                                        ?>
                                        <input name='<?php echo esc_attr($name); ?>' type='hidden' value='0' />
                                        &nbsp;<label class="pp-sync-toggle-switch" for="<?php echo esc_attr($id); ?>">
                                        <input type="checkbox" class="sync-enable-new-users" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="1" <?php echo esc_attr($checked); ?> <?php echo esc_html($main_check_title); ?> />
                                        <span class="pp-sync-slider"></span>
                                        </label>
                                    </td>

                                    <td class="pp-sync-now pp-toggle" style="text-align: center !important;">
                                        <?php
                                        $existing_id = 'sync_posts_to_users_existing' . '-' . $object_type;
                                        $existing_name = "sync_posts_to_users_existing[$object_type]";
                                        $existing_checked = $existing_users_setting ? ' checked ' : '';
                                        ?>
                                        <input name='<?php echo esc_attr($existing_name); ?>' type='hidden' value='0' />
                                        <label class="pp-sync-toggle-switch" for="<?php echo esc_attr($existing_id); ?>">
                                        <input type="checkbox" class="sync-enable-existing-users" id="<?php echo esc_attr($existing_id); ?>" name="<?php echo esc_attr($existing_name); ?>" value="1" <?php echo esc_attr($existing_checked); ?> title='<?php echo esc_attr($sync_existing_title); ?>' />
                                        <span class="pp-sync-slider"></span>
                                        </label>
                                    </td>

                                    <?php
                                        $option_name = 'sync_posts_to_users_role';
                                        $role_id = $option_name . '-' . $object_type;
                                        $role_name = "{$option_name}[$object_type][]"; // Add [] for multiple selection
                                        $disabled = ($row_active) ? '' : ' disabled ';
                                        $title = $titles[$option_name];
                                        
                                        // Handle both single value (legacy) and array (new) for multiple roles
                                        $setting = (isset($opt_values[$option_name][$object_type])) ? $opt_values[$option_name][$object_type] : '';
                                        if (!is_array($setting)) {
                                            $setting = $setting ? [$setting] : [];
                                        }
                                        ?>
                                    <td class="pp-toggle" style='<?php echo esc_attr($row_style); ?>'>
                                        <select name='<?php echo esc_attr($role_name); ?>' id='<?php echo esc_attr($role_id); ?>' class='pp-role-select2' title='<?php echo esc_attr($title); ?>' <?php echo esc_attr($disabled); ?> multiple="multiple" style="width: 100%;">
                                            <option value='(any)' <?php if (in_array('(any)', $setting)) echo ' selected=selected'; ?>><?php esc_html_e('(All Roles)', 'presspermit-pro'); ?></option>
                                            <?php
                                            foreach ($roles as $role_name => $role_display) :
                                                $selected = (in_array($role_name, $setting)) ? ' selected ' : '';
                                                ?>
                                                <option value='<?php echo esc_attr($role_name); ?>' <?php echo esc_attr($selected); ?>><?php echo esc_html($role_display); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <!-- Hidden field to handle empty selections -->
                                        <input type='hidden' name='<?php echo esc_attr($role_name); ?>' value='' />
                                    </td>

                                    <?php
                                    // Quantity field
                                    $quantity_option_name = 'sync_posts_to_users_quantity';
                                    $quantity_id = $quantity_option_name . '-' . $object_type;
                                    $quantity_name = "{$quantity_option_name}[$object_type]";
                                    $quantity_disabled = ($row_active) ? '' : ' disabled ';
                                    $quantity_title = $titles[$quantity_option_name];
                                    $quantity_setting = (!empty($opt_values[$quantity_option_name][$object_type])) ? $opt_values[$quantity_option_name][$object_type] : '1';
                                    ?>
                                    <td class="pp-toggle pp-sync-quantity" style='<?php echo esc_attr($row_style); ?>'>
                                        <input name="<?php echo esc_attr($quantity_name); ?>" type="number" min="1" max="100" class="pp-quantity-field" id="<?php echo esc_attr($quantity_id); ?>" value="<?php echo esc_attr($quantity_setting); ?>" title='<?php echo esc_attr($quantity_title); ?>' <?php echo esc_attr($quantity_disabled); ?> style="width: 60px;" />
                                    </td>

                                    <?php
                                    // Status field
                                    $status_option_name = 'sync_posts_to_users_status';
                                    $status_id = $status_option_name . '-' . $object_type;
                                    $status_name = "{$status_option_name}[$object_type]";
                                    $status_disabled = ($row_active) ? '' : ' disabled ';
                                    $status_title = $titles[$status_option_name];
                                    $status_setting = (!empty($opt_values[$status_option_name][$object_type])) ? $opt_values[$status_option_name][$object_type] : 'publish';
                                    
                                    // Available post statuses
                                    $available_statuses = [
                                        'publish' => __('Published', 'presspermit-pro'),
                                        'draft'   => __('Draft', 'presspermit-pro'),
                                        'pending' => __('Pending Review', 'presspermit-pro'),
                                        'private' => __('Private', 'presspermit-pro')
                                    ];
                                    ?>
                                    <td class="pp-toggle pp-sync-status" style='<?php echo esc_attr($row_style); ?>'>
                                        <select name='<?php echo esc_attr($status_name); ?>' id='<?php echo esc_attr($status_id); ?>' class='pp-status-select2' title='<?php echo esc_attr($status_title); ?>' <?php echo esc_attr($status_disabled); ?>>
                                            <?php foreach ($available_statuses as $status_key => $status_label) : ?>
                                                <option value='<?php echo esc_attr($status_key); ?>' <?php selected($status_setting, $status_key); ?>><?php echo esc_html($status_label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>

                                <!-- Expandable detail row -->
                                <tr class="pp-detail-row pp-detail-<?php echo esc_attr($object_type); ?>" data-post-type="<?php echo esc_attr($object_type); ?>" style="display: none;">
                                    <td colspan="7">
                                        <div class="pp-detail-content">
                                            <h4 style="margin: 10px 0 15px 0; color: #555;">
                                                <?php esc_html_e('Avoid Creating Duplicates', 'presspermit-pro'); ?>
                                                <?php $this->generateTooltip(esc_html__('This setting can prevent the creation of duplicate posts for a user. If the selected fields are a match, a new post will not be created.', 'presspermit-pro'),'','top'); ?>
                                            </h4>
                                            <div class="pp-detail-fields">
                                                <div class="pp-field-group">
                                                    <label class="pp-field-label">
                                                        <?php esc_html_e('Post Match Field', 'presspermit-pro'); ?>
                                                        <?php $this->generateTooltip(esc_html__('Choose which post field to match with users.', 'presspermit-pro'),'','top'); ?>
                                                    </label>
                                                    <div class="pp-field-container">
                                                        <?php
                                                        $post_field_option_name = 'sync_posts_to_users_post_field';
                                                        $post_field_id = $post_field_option_name . '-' . $object_type;
                                                        $post_field_name = "{$post_field_option_name}[$object_type]";
                                                        $post_field_disabled = ($row_active) ? '' : ' disabled ';
                                                        $post_field_title = $titles[$post_field_option_name];

                                                        // default to post_title field
                                                        $post_field_setting = (!empty($opt_values[$post_field_option_name][$object_type])) ? $opt_values[$post_field_option_name][$object_type] : 'post_title';

                                                        $post_field_show_dropdown = (!$post_field_setting || in_array($post_field_setting, ['post_title', 'post_name'], true) || !empty($suggested_values['sync_posts_to_users_post_field'][$object_type][$post_field_setting]));

                                                        $post_field_style = ($post_field_show_dropdown) ? '' : 'display:none;';

                                                        unset($suggested_values[$post_field_option_name][$object_type]['post_title']);
                                                        ?>
                                                        <select class="pp-hint ppp-suggestion pp-no-hide pp-field-select2" title='<?php echo esc_attr($post_field_title); ?>' style='<?php echo esc_attr($post_field_style); ?>width: 100%' autocomplete="off">
                                                            <?php
                                                            if (!empty($suggested_values[$post_field_option_name][$object_type])) :
                                                                foreach (array_keys($suggested_values[$post_field_option_name][$object_type]) as $meta_key) :
                                                                    $selected = ($meta_key == $post_field_setting) ? ' selected ' : '';
                                                                    ?>
                                                                    <option value='<?php echo esc_attr($meta_key); ?>' <?php echo esc_attr($selected); ?>>
                                                                    <?php if (isset($post_field_captions[$meta_key])) echo esc_html($post_field_captions[$meta_key]); else echo esc_html($meta_key);?>
                                                                    </option>
                                                                <?php
                                                            endforeach;
                                                        endif;

                                                        $selected = (('post_title' == $post_field_setting) || !$post_field_setting) ? ' selected ' : '';
                                                        ?>
                                                            <option value='post_title' <?php echo esc_attr($selected); ?>><?php echo esc_html($post_field_captions['post_title']); ?></option>
                                                            <?php $selected = (('post_name' == $post_field_setting)) ? ' selected ' : ''; ?>
                                                            <option value='post_name' <?php echo esc_attr($selected); ?>><?php echo esc_html($post_field_captions['post_name']); ?></option>

                                                            <?php $selected = (empty($suggested_values[$post_field_option_name][$object_type][$post_field_setting]) && !in_array($post_field_setting, array_keys($post_field_captions), true)) ? ' selected ' : ''; ?>
                                                            <option value='(other)' <?php echo esc_attr($selected); ?>>
                                                            <?php esc_html_e('(other)', 'presspermit-pro'); ?>
                                                            </option>
                                                        </select>

                                                        <?php
                                                        $post_field_style = ($post_field_show_dropdown) ? 'display:none;' : '';
                                                        ?>
                                                        <input name="<?php echo esc_attr($post_field_name); ?>" type="text" id="<?php echo esc_attr($post_field_id);?>" class="ppp-text-field" value="<?php echo esc_attr($post_field_setting); ?>" title='<?php echo esc_attr($post_field_title); ?>' style='<?php echo esc_attr($post_field_style); ?>width: 100%;' <?php echo esc_attr($post_field_disabled); ?> />
                                                        <input type='hidden' value='<?php echo esc_attr($post_field_setting); ?>' class='ppp-field-buffer' />
                                                        <?php
                                                        $suggestion_title = $titles['suggestions'];
                                                        ?>
                                                        <a href="javascript:void(0)" class="ppp-suggest" title='<?php echo esc_attr($suggestion_title); ?>' style='<?php echo esc_attr($post_field_style); ?>'><?php esc_html_e('select...', 'presspermit-pro'); ?></a>
                                                        <a href="javascript:void(0)" class="ppp-cancel" style="display:none"><?php esc_html_e('cancel', 'presspermit-pro'); ?></a>
                                                    </div>
                                                </div>

                                                <div class="pp-field-equals">=</div>

                                                <div class="pp-field-group">
                                                    <label class="pp-field-label">
                                                        <?php esc_html_e('User Match Field', 'presspermit-pro'); ?>
                                                        <?php $this->generateTooltip(esc_html__('Select which user field to match with posts.', 'presspermit-pro'),'','top'); ?>
                                                    </label>
                                                    <div class="pp-field-container">
                                                        <?php
                                                        $user_field_option_name = 'sync_posts_to_users_user_field';
                                                        $user_field_id = $user_field_option_name . '-' . $object_type;
                                                        $user_field_name = "{$user_field_option_name}[$object_type]";
                                                        $user_field_disabled = ($row_active) ? '' : ' disabled ';
                                                        $user_field_title = $titles[$user_field_option_name];
                                                        
                                                        $user_field_setting = (!empty($opt_values[$user_field_option_name][$object_type])) 
                                                        ? $opt_values[$user_field_option_name][$object_type] 
                                                        : 'display_name';
                                                        
                                                        $user_field_show_dropdown = (!$user_field_setting || in_array($user_field_setting, array_keys($user_field_captions), true));
                                                        $user_field_style = ($user_field_show_dropdown) ? '' : 'display:none;';
                                                        ?>
                                                        <select name='<?php echo esc_attr($user_field_name); ?>' id='<?php echo esc_attr($user_field_id); ?>' class='ppp-suggestion pp-field-select2' style='<?php echo esc_attr($user_field_style); ?>width: 100%;' title='<?php echo esc_attr($user_field_title); ?>' <?php echo esc_attr($user_field_disabled);?> autocomplete='off'>
                                                            <?php foreach ($user_field_captions as $field_name => $caption) : ?>
                                                                <option value='<?php echo esc_attr($field_name); ?>' <?php if ($field_name == $user_field_setting) echo 'selected=selected'; ?>>
                                                                <?php echo esc_html($caption); ?>
                                                                </option>
                                                            <?php endforeach; ?>

                                                            <option value='(other)' <?php if (!in_array($user_field_setting, array_keys($user_field_captions), true)) echo 'selected=selected'; ?>>
                                                            <?php esc_html_e('(other)', 'presspermit-pro'); ?>
                                                            </option>
                                                        </select>

                                                        <?php
                                                        $user_field_style = ($user_field_show_dropdown) ? 'display:none;' : '';
                                                        ?>
                                                        <input name="<?php echo esc_attr($user_field_name); ?>" type="text" class="ppp-text-field" id="<?php echo esc_attr($user_field_id); ?>" value="<?php echo esc_attr($user_field_setting); ?>" title="<?php echo esc_attr($titles['sync_posts_to_users_user_field_text']); ?>" style='<?php echo esc_attr($user_field_style); ?>width: 100%;' <?php echo esc_attr($user_field_disabled); ?> />
                                                        <input type='hidden' value='<?php echo esc_attr($user_field_setting); ?>' class='ppp-field-buffer' />
                                                        <a href="javascript:void(0)" class="ppp-suggest" title='<?php echo esc_attr($suggestion_title); ?>' style='<?php echo esc_attr($user_field_style); ?>'><?php esc_html_e('select...', 'presspermit-pro'); ?></a>
                                                        <a href="javascript:void(0)" class="ppp-cancel" style="display:none"><?php esc_html_e('cancel', 'presspermit-pro'); ?></a>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Template Parameters Info Box -->
                                            <div class="pp-template-info-box" style="margin-top: 20px; padding: 15px; background: #f0f6fc; <?php echo esc_attr($user_field_style); ?>">
                                                <h4 style="margin: 0 0 10px 0; color: #0073aa; display: flex; align-items: center; gap: 8px;">
                                                    <?php esc_html_e('Available Template Parameters', 'presspermit-pro'); ?>
                                                </h4>
                                                <p style="margin: 0 0 10px 0; color: #555; font-size: 13px;">
                                                    <?php esc_html_e('You can use these placeholders in the User Match Field to create custom templates:', 'presspermit-pro'); ?>
                                                </p>
                                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px; margin-bottom: 10px;">
                                                    <code style="background: #fff; padding: 4px 8px; border-radius: 3px; font-size: 12px;">[ID]</code>
                                                    <code style="background: #fff; padding: 4px 8px; border-radius: 3px; font-size: 12px;">[user_login]</code>
                                                    <code style="background: #fff; padding: 4px 8px; border-radius: 3px; font-size: 12px;">[user_email]</code>
                                                    <code style="background: #fff; padding: 4px 8px; border-radius: 3px; font-size: 12px;">[user_nicename]</code>
                                                    <code style="background: #fff; padding: 4px 8px; border-radius: 3px; font-size: 12px;">[display_name]</code>
                                                    <code style="background: #fff; padding: 4px 8px; border-radius: 3px; font-size: 12px;">[first_name]</code>
                                                    <code style="background: #fff; padding: 4px 8px; border-radius: 3px; font-size: 12px;">[last_name]</code>
                                                    <code style="background: #fff; padding: 4px 8px; border-radius: 3px; font-size: 12px;">[nickname]</code>
                                                </div>
                                                <div style="background: #fff; padding: 10px; border-radius: 3px; margin-top: 10px;">
                                                    <strong style="color: #555; font-size: 13px;"><?php esc_html_e('Examples:', 'presspermit-pro'); ?></strong>
                                                    <ul style="margin: 8px 0 0 20px; color: #666; font-size: 12px;">
                                                        <li><code style="background: #f5f5f5; padding: 2px 6px; border-radius: 2px;">Staff: [display_name] - [user_email]</code></li>
                                                        <li><code style="background: #f5f5f5; padding: 2px 6px; border-radius: 2px;">User [user_login] - [ID]</code></li>
                                                        <li><code style="background: #f5f5f5; padding: 2px 6px; border-radius: 2px;">Member [first_name] [last_name] Page</code></li>
                                                    </ul>
                                                </div>
                                            </div>
                                            
                                            <?php
                                            // Additional Field Section for hierarchical post types
                                            $post_type_object = get_post_type_object($object_type);
                                            if (!empty($post_type_object) && !empty($post_type_object->hierarchical)) : ?>
                                            <h4 style="margin: 25px 0 15px 0; color: #555; border-top: 1px solid #e0e0e0; padding-top: 20px;">
                                                <?php esc_html_e('Additional Field', 'presspermit-pro'); ?>
                                                <?php $this->generateTooltip(esc_html__('Additional configuration options for post creation.', 'presspermit-pro'),'','top'); ?>
                                            </h4>
                                            <div class="pp-detail-fields">
                                                <div class="pp-field-group">
                                                    <label class="pp-field-label">
                                                        <?php esc_html_e('Parent Post ID', 'presspermit-pro'); ?>
                                                        <?php $this->generateTooltip(esc_html__('Specify the Post ID of a parent post for hierarchical post types.', 'presspermit-pro'),'','top'); ?>
                                                    </label>
                                                    <div class="pp-field-container">
                                                        <?php
                                                        $parent_option_name = 'sync_posts_to_users_post_parent';
                                                        $parent_id = $parent_option_name . '-' . $object_type;
                                                        $parent_name = "{$parent_option_name}[$object_type]";
                                                        $parent_disabled = ($row_active) ? '' : ' disabled ';
                                                        $parent_title = $titles[$parent_option_name];
                                                        $parent_setting = (!empty($opt_values[$parent_option_name][$object_type])) ? $opt_values[$parent_option_name][$object_type] : '0';
                                                        ?>
                                                        <input name="<?php echo esc_attr($parent_name); ?>" type="text" class="ppp-parent-field" id="<?php echo esc_attr($parent_id); ?>" value="<?php echo esc_attr($parent_setting); ?>" title='<?php echo esc_attr($parent_title); ?>' <?php echo esc_attr($parent_disabled); ?> style="width: 100%;" placeholder="<?php esc_attr_e('Enter parent post ID (0 for no parent)', 'presspermit-pro'); ?>" />
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <tr class="pp-sync-submit-row" style="display:none">
                                <td colspan="7" style="text-align:right">
                                    <input type="submit" name="presspermit_submit" class="button-primary pp-sync-now-button" value="<?php esc_attr_e('Create Posts for Current Users', 'presspermit'); ?>" title='<?php echo esc_attr($sync_existing_title); ?>' />
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
        </td>
        </tr>
    <?php
    endif; // any options accessable in this section
    } // end function optionsUI

    private function get_suggested_meta_keys($post_types, $key_like = '%email%', $count_limit = 10000)
    {
        global $wpdb;

        if (!$post_types) return [];

        // if a post type has too many posts, don't risk the overhead of scanning for meta keys
        foreach ($post_types as $post_type) {
            $num_posts = (array)wp_count_posts($post_type);
            if (array_sum($num_posts) > $count_limit) {
                $post_types = array_diff($post_types, (array)$post_type);
            }
        }

        if (false === $key_like) {
            $skip_meta_keys = (array)apply_filters(
                'presspermit_sync_posts_to_users_skip_meta_keys', 
                ['_edit_last', 
                '_edit_lock', 
                '_pp_is_autodraft', 
                '_pp_last_parent', 
                '_wp_attached_file', 
                '_wp_attachment_metadata', 
                '_wp_desired_post_slug', 
                '_wp_page_template', 
                '_wp_trash_meta_status', 
                '_wp_trash_meta_time', 
                '_yoast_wpseo_content_score', 
                '_yoast_wpseo_primary_category'
                ]
            );
            
            $meta_key_csv = implode("','", array_map('sanitize_key', $skip_meta_keys));
            $key_like_clause = " AND meta_key NOT IN ('$meta_key_csv')";
        } else {
            $key_like_clause = ' AND (';
            $or = '';
            foreach ($key_like as $like) {
                // work around parenthesis getting converted to braced UID
                $like = str_replace('%', '&', $like);

                if ($or) {
                    $key_like_clause .= $wpdb->prepare("OR meta_key LIKE '%s'", $like);
                } else {
                    $key_like_clause .= $wpdb->prepare("meta_key LIKE '%s'", $like);
                }

                $key_like_clause = str_replace('&', '%', $key_like_clause);
                $or = ' OR ';
            }
            $key_like_clause .= ' )';
        }

        $type_csv = implode("','", array_map('sanitize_key', $post_types));

        // phpcs Note: Direct query of postmeta table on plugin admin query

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            "SELECT DISTINCT(pm.meta_key), p.post_type"
            . " FROM $wpdb->postmeta AS pm"
            . " INNER JOIN $wpdb->posts AS p ON p.ID = pm.post_id"
            . " WHERE p.post_type IN ('$type_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            . " $key_like_clause"                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            . " ORDER BY meta_id DESC"
        );

        // AND meta_key NOT RegExp '(^[_0-9].+$)' 
        // AND meta_key NOT RegExp '(^[0-9]+$)'

        $skip_keys = apply_filters('presspermit_suggested_meta_keys_skip', ['_thumbnail_id']);

        $meta_keys = [];
        foreach ($results as $row) {
            if (in_array($row->meta_key, $skip_keys, true)) continue;

            $meta_keys[$row->post_type][$row->meta_key] = true;
        }

        return $meta_keys;
    }

    function generateTooltip($tooltip, $text = '', $position = 'top', $useIcon = true)
    {
        if (!$tooltip) return;
        ?>
        <span data-toggle="tooltip" data-placement="<?php esc_attr_e($position); ?>">
        <?php esc_html_e($text);?>
        <span class="tooltip-text"><span><?php esc_html_e($tooltip);?></span><i></i></span>
        <?php 
        if ($useIcon) : ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 50 50" style="margin-left: 4px; vertical-align: text-bottom;">
                <path d="M 25 2 C 12.264481 2 2 12.264481 2 25 C 2 37.735519 12.264481 48 25 48 C 37.735519 48 48 37.735519 48 25 C 48 12.264481 37.735519 2 25 2 z M 25 4 C 36.664481 4 46 13.335519 46 25 C 46 36.664481 36.664481 46 25 46 C 13.335519 46 4 36.664481 4 25 C 4 13.335519 13.335519 4 25 4 z M 25 11 A 3 3 0 0 0 25 17 A 3 3 0 0 0 25 11 z M 21 21 L 21 23 L 23 23 L 23 36 L 21 36 L 21 38 L 29 38 L 29 36 L 27 36 L 27 21 L 21 21 z"></path>
            </svg>
        <?php
        endif; ?>
        </span>
        <?php
    }

    /**
     * Display validation errors as admin notices
     */
    function displayValidationErrors()
    {
        // Only display on settings page
        if (
            !isset($_GET['page']) || $_GET['page'] !== 'presspermit-settings'
            || !isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'presspermit-settings')
        ) {
            return;
        }

        // Check if there are validation errors
        if (isset($_GET['presspermit_validation_error'])) {
            $errors = get_transient('presspermit_sync_validation_errors_' . get_current_user_id());
            
            if (!empty($errors)) {
                // Delete the transient to avoid showing the same errors again
                delete_transient('presspermit_sync_validation_errors_' . get_current_user_id());
                
                echo '<div class="notice notice-error is-dismissible">';
                echo '<h4>' . esc_html__('Validation Errors in User Posts Settings:', 'presspermit-pro') . '</h4>';
                echo '<ul style="margin: 10px 0 10px 20px;">';
                
                foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                
                echo '</ul>';
                echo '<p>' . esc_html__('Please fix these errors and try again.', 'presspermit-pro') . '</p>';
                echo '</div>';
            }
        }
    }
} // end class