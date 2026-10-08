<?php
namespace PublishPress\StatusCapabilities;

class StatusAdminPro
{
    public function __construct() {
        add_action('publishpress_statuses_custom_column', [$this, 'actStatusesCustomColumn'], 10, 3);

        add_action('presspermit_statuses_edit_status_tab', [$this, 'act_status_edit_tab'], 10, 3);
    }

    public function act_status_edit_tab($tab, $status, $default_tab) {
        if ('post_access' != $tab) {
            return;
        }

        $display = ($default_tab == $tab) ? '' : 'display:none';
        
        $table_class = 'form-table pp-statuses-options';
        $new = false;

        $status_obj = $status;
        $status = $status_obj->slug;

        $attrib_type = (!empty($status_obj->for_revision)) ? 'revision' : 'moderation';

        $status_label = (!empty($status_obj->label)) ? $status_obj->label : '';
        $status_types = (!empty($status_obj) && !empty($status_obj->post_type)) ? $status_obj->post_type : [];

        $name_disabled = ($new) ? '' : ' disabled ';
        $label_disabled = ('future' == $status) ? ' disabled ' : '';

        $is_pubpress_status = !empty($status_obj->pp_custom);
        $pubpress_disable_edit = ($is_pubpress_status) ? ' disabled ' : '';

        if (in_array($attrib_type, ['moderation', 'revision'])) {
            $status_args = ('revision' == $attrib_type) ? ['for_revision' => true] : ['moderation' => true];

            $ordered_workflow_statuses = \PublishPress_Statuses::orderStatuses(
                \PublishPress\StatusCapabilities::getPostStatuses($status_args, 'object')
            );

            $capability_status = (!empty($status_obj) && !empty($status_obj->capability_status)) ? $status_obj->capability_status : '';

            $status_order = (!empty($status_obj) && isset($status_obj->order)) ? $status_obj->order : '';
            $status_parent = (!empty($status_obj) && isset($status_obj->status_parent)) ? $status_obj->status_parent : '';
        }

        ?>
        <div id='pp-<?php echo esc_attr($tab);?>' style='clear:both;margin:0;<?php echo esc_attr($display)?>' class='pp-options'>
        
        <?php
        do_action("presspermit_" . esc_attr($tab) . "_options_pre_ui");
        ?>
        
        <table class='<?php echo esc_attr($table_class);?>' id='pp-<?php echo esc_attr($tab);?>_table' style='<?php echo esc_attr($display);?>'>

        <?php
        if (empty($status_obj->publish) && empty($status_obj->private) && !in_array($status, ['future', 'draft', 'draft-revision'])) : ?>
            <tr>
            <th><label for="status_capability_status"><?php esc_html_e('Capability Requirements', 'publishpress-statuses-pro') ?></label></th>

            <td>
                <select name="status_capability_status" id="status_capability_status" autocomplete="off">
                    <option value="" <?php if ('' == $capability_status) echo ' selected '; ?>><?php esc_html_e('Default Capabilities', 'publishpress-statuses-pro'); ?></option>
                    
                    <option value="<?php echo esc_attr($status); ?>" <?php if ($status == $capability_status) echo ' selected '; ?>>
                    <?php esc_html_e('Custom Capabilities for this Status', 'publishpress-statuses-pro'); ?>
                    </option>
                    
                    <?php
                    foreach ($ordered_workflow_statuses as $other_status => $other_status_obj) :
                        if ($status == $other_status) {
                            continue;
                        }

                        if (!empty($other_status_obj->status_parent) && ($other_status_obj->status_parent != $status_parent)) {
                            continue;
                        }
                        
                        $selected = ($other_status == $capability_status) ? ' selected ' : '';

                        if (($other_status == $status_parent)
                        || (\PublishPress\StatusCapabilities::postStatusHasCustomCaps($other_status)
                            && (!isset($other_status_obj->capability_status) || ($other_status_obj->capability_status == $other_status))
                            )
                        ) :?>
                            <option value="<?php echo esc_attr($other_status); ?>" <?php echo esc_attr($selected); ?>>
                            <?php 
                            if ($other_status == $status_parent) {
                                printf(esc_html__('same as PARENT STATUS (%s)', 'publishpress-statuses-pro'), esc_html($other_status_obj->label));

                            } else {
                                printf(esc_html__('same as %s status', 'publishpress-statuses-pro'), esc_html($other_status_obj->label));
                            }
                            ?>
                            </option>
                        <?php endif;
                    endforeach;
                    ?>
                </select>

                <div class="pp-subtext"><?php esc_html_e('Enforce status-specific capabilities, or share capabilities with another status.', 'publishpress-statuses-pro'); ?></div>
            </td>
            </tr>
        <?php endif;

        if (!empty($status_obj->private)) {
            if ($do_postmeta_permissions = \PublishPress\StatusCapabilities::postStatusHasCustomCaps($status_obj->name)) {
                $capability_status = $status_obj->name;
            }
        } elseif (!empty($status_obj->public) || in_array($status_obj->name, ['draft', 'future', 'draft-revision'])) {
            $do_postmeta_permissions = false;
        } else {
            $do_postmeta_permissions = \PublishPress\StatusCapabilities::customStatusPostMetaPermissions('', $status_obj);
        }

        $custom_privacy_edit_caps_enabled = !empty($status_obj->private) && defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS') && PPS_CUSTOM_PRIVACY_EDIT_CAPS;

        if (empty($capability_status) || ($status_obj->name != $capability_status)) {
            do_action("presspermit_" . esc_attr($tab) . "_options_ui");
            ?>

            </table></div>

            <?php
            return;
        }

        $is_administrator = (function_exists('presspermit')) ? \presspermit()->isContentAdministrator() : is_super_admin();

        do_action('presspermit_post_filters');
        $status_cap_mapper = \PublishPress\StatusCapabilities\CapabilityFilters::instance();

        $is_english = (0 === strpos(get_locale(), 'en'));

        if ('private' == $status_obj->name) {
            $cap_property_prefixes = ($custom_privacy_edit_caps_enabled)
            ? [
                'read' => __('View'),
                'list' => __('List', 'publishpress-statuses-pro'), 
                'edit' => __('Edit Others', 'publishpress-statuses-pro'), 
                'delete' => __('Delete Others', 'publishpress-statuses-pro'),
                'copy' => __('Revise', 'publishpress-statuses-pro'),
            ]
    
            : [
                'read' => __('View'), 
                'list' => __('List', 'publishpress-statuses-pro'), 
                'unused' => '',
                'unused_2' => '',
                'unused_3' => ''
            ];

            if (!defined('PRESSPERMIT_VERSION')) {
                unset($cap_property_prefixes['list']);
            }

            if (!defined('PUBLISHPRESS_REVISIONS_VERSION') || !function_exists('rvy_get_option') || !rvy_get_option("copy_posts_capability")) {
                unset($cap_property_prefixes['copy']);
            }

        } elseif (!empty($status_obj->private)) {
            $cap_property_prefixes = ($custom_privacy_edit_caps_enabled)
            ? [
                'set' => __('Select'), 
                'read' => __('View'),
                'list' => __('List', 'publishpress-statuses-pro'), 
                'edit' => __('Edit Others', 'publishpress-statuses-pro'), 
                'delete' => __('Delete Others', 'publishpress-statuses-pro'),
                'copy' => __('Revise', 'publishpress-statuses-pro'),
            ]
    
            : [
                'set' => __('Select'), 
                'read' => __('View'), 
                'unused' => '',
                'unused_2' => ''
            ];

            if (!defined('PRESSPERMIT_VERSION')) {
                unset($cap_property_prefixes['list']);
            }

            if (!defined('PUBLISHPRESS_REVISIONS_VERSION') || !function_exists('rvy_get_option') || !rvy_get_option("copy_posts_capability")) {
                unset($cap_property_prefixes['copy']);
            }
        } else {
            $cap_property_prefixes = [
                'set' => __('Select'), 
                'edit' => __('Edit'),
                'edit_others' => __('Edit Others', 'publishpress-statuses-pro'),
                'delete' => __('Delete'),
                'delete_others' => __('Delete Others', 'publishpress-statuses-pro'),
            ];
        }

        $cap_tips = array( 
            // translators: %s is a post status name
            'set' =>            sprintf(esc_html__( 'Role can set posts to the "%s" status', 'publishpress-statuses-pro' ), $status_obj->label),

            // translators: %s is a post status name
            'read' =>           sprintf(esc_html__( 'Role can read "%s" posts', 'publishpress-statuses-pro' ), $status_obj->label),

            // translators: %s is a post status name
            'list' =>           sprintf(esc_html__( 'Role sees uneditable "%s" posts in admin listing', 'publishpress-statuses-pro' ), $status_obj->label),

            // translators: %s is a post status name
            'copy' =>           sprintf(esc_html__( 'Role can create revisions of "%s" posts', 'publishpress-statuses-pro' ), $status_obj->label),

            // translators: %s is a post status name
            'edit' =>           sprintf(esc_html__( 'Role can edit "%s" posts', 'publishpress-statuses-pro' ), $status_obj->label),

            // translators: %s is a post status name
            'edit_others' =>    sprintf(esc_html__( 'Role can edit other\'s "%s" posts', 'publishpress-statuses-pro' ), $status_obj->label),

            // translators: %s is a post status name
            'delete' =>         sprintf(esc_html__( 'Role can delete "%s" posts', 'publishpress-statuses-pro' ), $status_obj->label),

            // translators: %s is a post status name
            'delete_others' =>  sprintf(esc_html__( 'Role can delete other\'s "%s" posts', 'publishpress-statuses-pro' ), $status_obj->label)
        );

        $status_name = $status_obj->name;

        // Post types that have custom statuses enabled at all
        $custom_status_post_types = \PublishPress_Statuses::instance()->options->post_types;
        $custom_status_post_types = array_filter($custom_status_post_types);

        // Find post types that have distinct permissions, support custom statuses and don't have this status disabled
        $status_post_types = [];

        // Only control type-specific caps for set / edit / delete if they will be enforced
        foreach(array_keys($custom_status_post_types) as $post_type) {
            if (!in_array($post_type, array_keys(\PublishPress\StatusCapabilities::getEnabledPostTypes()))) {
                continue;
            }

            $status_post_types[$post_type] = get_post_type_object($post_type);
        }
        ?>

        <tr>
        <th colspan=2>
        
        <?php if (false && \PublishPress_Statuses::isContentAdministrator()):?>
        <label for="status_capabilities"><?php printf(esc_html__('Role Capabilities for "%s" status:', 'publishpress-statuses-pro'), esc_html($status_obj->label));?></label>
        <?php endif;?>

        <span style="float: right">
        <?php
            submit_button(esc_html__('Update Status', 'publishpress-statuses-pro'), 'primary', 'submit', false); 
        ?>
        </span>
        <br />

        <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready(function ($) {
            $('#pp-roles_table input[name*="roles_set_status"]').on('click', function()
            {
                $('input[name="' + $(this).attr('name') + '"]').prop('checked', $(this).prop('checked'));
            });

            <?php if (empty($status_obj->private)):?>
            $('#pp-post_access_table input.cme_status_set_basic').on('click', function() {
                var chks = $(this).closest('td').find('td.post-cap input[type="checkbox"]');
                $(chks).attr('disabled', !$(this).prop('checked'));

                if (!$(this).prop('checked')) {
                    $(chks).prop('checked', $(this).prop('checked'));
                }
            });
            <?php endif;?>

            $('#pp-post_access_table input.toggle_status_caps').on('click', function() {
                var chks = $(this).closest('tr').find('> td input[name!="toggle_status_caps"][type="checkbox"]');
                $(chks).prop('checked', !$(chks).first().is(':checked'));

                // enable / disable type-specific set caps based on selection of basic set capability
                var basicSet = $(this).closest('tr').find('td.status-caps-set input.cme_status_set_basic');
                $(this).closest('tr').find('td.status-caps-set table td.post-cap input[type="checkbox"]').attr('disabled', !$(basicSet).prop('checked'));

                return false;
            });

            $('#pp_edit_status_caps th.post-cap').on('click', function() {
                var col_num = $(this).parent().children().index($(this)) + 1;

                $('#pp_edit_status_caps tr.cme_status td:nth-child(' + col_num + ') div input').prop(
                    'checked',
                    !$('#pp_edit_status_caps tr.cme_status td:nth-child(' + col_num + ') div input').prop('checked')
                );
            });

            $('td.post-cap label').on('click', function() {
                $(this).closest('td').find('input').prop(
                    'checked',
                    !$(this).closest('td').find('input').prop('checked')
                )
            });
        });
        /* ]]> */
        </script>

        <style>
        #pp_edit_status_caps > tbody > tr > th {
            padding: 10px;
            user-select: none;
        }

        #pp_edit_status_caps td.status-label {
            vertical-align: middle;
        }

        td.post-cap label {
            cursor: pointer;
        }

        #pp_edit_status_caps.min-caps td.status-caps-set {
            width: 10%;
            text-align: center;
            vertical-align: top;
        }

        #pp_edit_status_caps input.cme_status_set_basic
        {
        margin-bottom: 8px;
        }

        #pp_edit_status_caps.min-caps input.cme_status_set_basic {
        margin-bottom: inherit;
        }

        #pp_edit_status_caps td.unused {
            background-color: white;
        }

        #pp_edit_status_caps td.post-cap {
            padding: 4px !important;
        }

        #pp_edit_status_caps td.status-caps input.cm-has-via-pp {
            background-color: #84fb84;
        }

        #pp_edit_status_caps input.cm-has-via-pp {
            background-color: #84fb84;
        }

        .ppc-tool-tip {
            display: inline-block;
            position: relative;
            cursor: pointer;
        }

        .ppc-tool-tip .tool-tip-text {
            display: none;
            min-width: 250px; 
            top: -20px;
            left: 50%;
            transform: translate(-50%, -100%);
            font-weight: normal;
            border-radius: 10px;
            position: absolute;
            z-index: 99999999;
            box-sizing: border-box;
            transition: all .65s cubic-bezier(.84,-0.18,.31,1.26);
            color: #6f6f6f;
            font-size: 13px;
            padding: 16px;
            text-align: left;
            background-color: #fff;
            border: 1px solid #d5d5d5;
            box-shadow: 0 2px 5px rgb(0 0 0 / 10%), 0 0 56px rgb(0 0 0 / 8%);
        }

        .ppc-tool-tip:hover .tool-tip-text {
            display: block;
        }

        .ppc-tool-tip .tool-tip-text i {
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -12px;
            width: 24px;
            height: 12px;
            overflow: hidden;
        }

        .ppc-tool-tip .tool-tip-text i::after {
            content: '';
            position: absolute;
            width: 12px;
            height: 12px;
            left: 50%;
            transform: translate(-50%,-50%) rotate(45deg);
            background-color: #fff;
            box-shadow: 0 1px 8px rgba(0,0,0,0.5);
        }
        </style>

        <br /><br />

        <?php
        $class = (!$do_postmeta_permissions || (!empty($status_obj->private) && !$custom_privacy_edit_caps_enabled)) ? ' min-caps' : '';
        ?>
        <table id="pp_edit_status_caps" class="widefat cme-typecaps<?php echo esc_html($class);?>">

        <?php if (\PublishPress_Statuses::isContentAdministrator()) :?>
        <tr class=''>
            <th>
            <?php 
            echo esc_html__('Role');
            ?>
            </th>

            <?php
            // label cap property column headers
            foreach($cap_property_prefixes as $prefix => $prefix_label) :
                $tip = ( isset( $cap_tips[$prefix] ) ) ? $cap_tips[$prefix] : '';
            ?>
                <th title='<?php echo esc_attr($tip);?>' class="post-cap">
                <?php 
                echo esc_html($prefix_label);
                ?>
                </th>
            <?php endforeach; ?>
        </tr>
        <?php endif;?>

        <?php
        $roles = \PP_Statuses_Functions::getRoles(true);

        if (!\PublishPress_Statuses::isContentAdministrator()) {
            $roles = [];
        }

        foreach ($roles as $role_name => $role_label) {

            if (!\PP_Statuses_Functions::isEditableRole($role_name)) {
                continue;
            }

            $role = get_role($role_name);
            $rcaps = $role->capabilities;

            $pp_metagroup_caps = self::get_metagroup_caps( $role_name );
            ?>

            <tr class='cme_status cme-postmeta-status cme_status_<?php echo esc_attr($status_name);?>'>
                <td class='status-label status-label-advanced'>
                    <?php if ($do_caps_link = defined('PUBLISHPRESS_CAPS_VERSION')) :
                    $url = admin_url("admin.php?page=pp-capabilities&role={$role_name}");
                    ?>
                        <a href='<?php echo esc_url($url);?>' target='rolecaps' title='<?php _e('Edit basic role capabilities', 'publishpress-statuses-pro');?>'>
                    <?php endif;

                    echo esc_html($role_label);

                    if ($do_caps_link):?>
                        </a>
                    <?php endif;

                    ?>
                    <br>
                    <div class="ppc-tool-tip disabled">
                    <input class="toggle_status_caps" name="toggle_status_caps" type="checkbox">
                        <div class="tool-tip-text">
                            <p>
                            <?php
                                printf('Toggle all capabilities for this role', 'publishpress-statuses-pro');
                            ?>
                            </p>
                            <i></i>
                        </div>
                    </div>
                </td>

            <?php
            foreach (array_keys($cap_property_prefixes) as $prefix) {
                $prop = "{$prefix}_{$status_name}_posts";
                ?>

                <td class="status-caps-<?php echo esc_attr($prefix);?><?php if (empty($cap_property_prefixes[$prefix])) echo ' unused';?>">

                <?php
                if (('set' == $prefix) && (empty($status_obj->private) || !$custom_privacy_edit_caps_enabled)) {
                    $td_classes = ['post-cap', 'cme_status_set_basic'];
                    $cap_slug = str_replace('-', '_', $status_name);
                    $cap_name = "status_change_{$cap_slug}";
                    $status_change_cap = $cap_name;

                    if ($is_administrator || current_user_can($cap_name)) {
                        $disabled = (('set' == $prefix) && empty($status_obj->private) && empty($rcaps[$status_change_cap])) ? 'disabled' : '';

                        $chk_classes = ['cme_status_set_basic'];

                        if ( ! empty($pp_metagroup_caps[$cap_name]) ) {
                            $chk_classes []= 'cm-has-via-pp';
                        }

                        $chk_class = ( $chk_classes ) ? implode(' ', $chk_classes) : '';

                        if (defined('PUBLISHPRESS_CAPS_PRO_VERSION')) :?>
                            <div class="ppc-tool-tip disabled"><input type="checkbox" class="<?php echo esc_attr($chk_class);?>" name="roles_set_status[<?php echo esc_attr($role_name);?>]" autocomplete="off" value="1" <?php checked(1, ! empty($rcaps[$cap_name]));?> />
                                <div class="tool-tip-text">
                                    <p>
                                    <?php
                                    if ( ! empty($pp_metagroup_caps[$cap_name]) ) {
                                        printf(esc_html__( '%s: assigned by Permission Group', 'publishpress-statuses-pro' ), '<strong>' . esc_html($cap_name) . '</strong>' );
                                    } else {
                                        printf(esc_html__( 'This capability is %s', 'publishpress-statuses-pro' ), '<strong>' . esc_html($cap_name) . '</strong>' );

                                        if (empty($status_obj->private) && (0 === strpos($cap_name, 'status_change_') && $do_postmeta_permissions)) :?>
                                            <br><br><?php esc_html_e('It is required in addition to the type-specific capability.', 'publishpress-statuses-pro');?>
                                        <?php endif;
                                    }
                                    ?>
                                    </p>
                                    <i></i>
                                </div>
                            </div>
                            
                        <?php else :
                            if (!empty($pp_metagroup_caps[$cap_name])) {
                                $title_text = sprintf( __( '%s: assigned by Permission Group', 'publishpress-statuses-pro' ), $cap_name );
                            } else {
                                $title_text = $cap_name;
                            }
                            ?>
                            <input type="checkbox" class="<?php echo esc_attr($chk_class);?>" title="<?php echo esc_attr($title_text);?>" 
                            name="roles_set_status[<?php echo esc_attr($role_name);?>]" autocomplete="off" 
                            value="1" <?php checked(1, ! empty($rcaps[$cap_name]));?> style="margin-bottom: 10px;" />
                        
                        <?php endif;
                    }
                }
                ?>
                <table>

                <?php
                $post_type_obj = get_post_type_object('post');
                $post_type_caps = (!empty($post_type_obj)) ? (array) $post_type_obj->cap : [];

                $page_type_obj = get_post_type_object('page');
                $page_type_caps = (!empty($page_type_obj)) ? (array) $page_type_obj->cap : [];

                foreach ($status_post_types as $post_type => $type_obj) {
                    $display_row = false;

                    if (!$type_obj || empty($type_obj->cap)) {
                        continue;
                    }

                    if (('private' == $status_name) && in_array($prefix, ['read', 'edit', 'delete'])) {
                        $prop_name = "{$prefix}_private_posts";
                        
                        if (!empty($type_obj->cap->$prop_name)) {
                            $cap_name = $type_obj->cap->$prop_name;

                            if ($is_administrator || current_user_can($cap_name)) {
                                $display_row = true;
                                $last_status_has_postmeta_caps = true;
                            }
                        }
                    
                    // If basic post type uses the same capability for edit_posts and edit_others_posts, don't display a checkbox for edit_others here either
                    } elseif (($prefix != 'edit_others' || $type_obj->cap->edit_others_posts != $type_obj->cap->edit_posts)
                    && ($prefix != 'delete_others' || $type_obj->cap->delete_others_posts != $type_obj->cap->delete_posts)
                    && (($prefix != 'set') || !empty($type_obj->cap->set_posts_status))
                    ) {
                        if ($prefix == 'set') {
                            $cap = $type_obj->cap->set_posts_status;

                        } elseif ('list' == $prefix) {
                            $cap = 'edit_post';
                        } else {
                            $basic_type_property = "{$prefix}_posts";
                            $cap = (in_array($prefix, ['edit_others', 'delete_others'])) ? $type_obj->cap->$basic_type_property : "{$prefix}_post";
                        }
    
                        $_caps = self::getStatusCaps($cap, $post_type, $status_name);

                        $caps = array_diff($_caps, [$cap, "status_change_{$status_name}"], (array) $type_obj->cap, $post_type_caps, $page_type_caps);
                        $cap_name = reset($caps);

                        if ('list' == $prefix) {
                            if ('private' == $status_name) {
                                $cap_name = (!empty($type_obj->cap->edit_private_posts)) ? str_replace('edit_', 'list_', $type_obj->cap->edit_private_posts) : '';
                            } else {
                                $cap_name = str_replace('edit_', 'list_', $cap_name);
                            }
                        }

                        if ($cap_name) {
                            if ($is_administrator || current_user_can($cap_name)) {
                                $display_row = true;
                                $last_status_has_postmeta_caps = true;
                            }
                        } else {
                            if (defined('WP_DEBUG') && WP_DEBUG && (false === strpos($cap_name, 'status_change_'))) {
                                echo "\r\n<!-- Shared capability for " . esc_html($prefix) . ' ' . esc_html($status_name) . ' ' . esc_html($type_obj->name) . ': ' . implode(', ', array_map('esc_html', $_caps)) . " -->";
                            }
                        }
                    }
                    
                    if ( $display_row ) {
                    ?>
                        <tr>
                        
                        <?php
                        $td_classes = [];
                        $td_classes []= "post-cap";

                        if (isset($rcaps[$cap_name]) && empty($rcaps[$cap_name])) {
                            $td_classes []= "cap-neg";
                        }

                        $td_class = ( $td_classes ) ? implode(' ', $td_classes): '';
                        ?>

                        <td class='<?php echo esc_attr($td_class);?>'>

                        <input type="hidden" name="status_caps[<?php echo esc_attr($role_name);?>][<?php echo esc_attr($cap_name);?>]" autocomplete="off" value="0" />

                        <?php
                        $disabled = ('set' == $prefix) && empty($status_obj->private) && empty($rcaps[$status_change_cap]) ? 'disabled' : '';

                        if (defined('PUBLISHPRESS_CAPS_PRO_VERSION')) {
                            $chk_classes = [];

                            if ( ! empty($pp_metagroup_caps[$cap_name]) ) {
                                $chk_classes []= 'cm-has-via-pp';
                            }

                            $chk_class = ( $chk_classes ) ? implode(' ', $chk_classes) : '';
                            ?>
                            <div class="ppc-tool-tip disabled">
                                <input type="checkbox" class="<?php echo esc_attr($chk_class);?>" name="status_caps[<?php echo esc_attr($role_name);?>][<?php echo esc_attr($cap_name);?>]" id="caps_<?php echo esc_attr($cap_name) ;?>" autocomplete="off" value="1" <?php checked(1, ! empty($rcaps[$cap_name]));?> <?php echo esc_attr($disabled);?> />
                                <div class="tool-tip-text">
                                    <p>
                                    <?php
                                    if ( ! empty($pp_metagroup_caps[$cap_name]) ) {
                                        printf(esc_html__( '%s: assigned by Permission Group', 'publishpress-statuses-pro' ), '<strong>' . esc_html($cap_name) . '</strong>' );
                                    } else {
                                        printf(esc_html__( 'This capability is %s', 'publishpress-statuses-pro' ), '<strong>' . esc_html($cap_name) . '</strong>' );
                                    }
                                    ?>
                                    </p>
                                    <i></i>
                                </div>
                            </div>

                            <label>
                            <?php echo esc_html($type_obj->label);?>
                            </label>
                        <?php
                        } else {
                            if (!empty($pp_metagroup_caps[$cap_name])) {
                                $title_text = sprintf( esc_html__( '%s: assigned by Permission Group', 'publishpress-statuses-pro' ), $cap_name );
                            } else {
                                $title_text = $cap_name;
                            }
                            ?>

                            <label>
                            <input type="checkbox" title="<?php echo esc_attr($title_text);?>" 
                            name="status_caps[<?php echo esc_attr($role_name);?>][<?php echo esc_attr($cap_name);?>]" autocomplete="off" 
                            value="1" <?php checked(1, ! empty($rcaps[$cap_name]));?> <?php echo esc_attr($disabled);?> /> 

                            <?php echo esc_html($type_obj->label);?>
                            </label>
                        <?php
                        }

                        if ( false !== strpos( $td_class, 'cap-neg' ) ) :?>
                            <input type="hidden" class="cme-negation-input" name="caps[<?php echo esc_attr($cap_name);?>]" value="" />
                        <?php 
                        endif;
                        ?>

                        </td>
                        </tr>
                    <?php
                    } // if display_row

                    if (!empty($status_header_done)) {
                        if (!empty($display_row)) {
                            echo '<div class="row-spacer">';
                        }
                        echo '</td>';
                    }

                } // endforeach status_post_types
                ?>

                </table>

                </td>

            <?php
            } // endforeach cap properties

            ?>
            <tr class=''>
            <th class='pp-caps-status-label'></th>
            </tr>

            <?php
        } // foreach $roles
        ?>
        </table>

        </th>
        </tr>
        <?php

        do_action("presspermit_" . esc_attr($tab) . "_options_ui");

        echo '</table></div>';
    }

    private static function get_metagroup_caps( $default ) {
        if (function_exists('presspermit')) {
            // @todo: API to Permissions Pro
            global $wpdb;

            // Direct query of plugin table for plugin admin UI generation

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $pp_supplemental_roles = $wpdb->get_col( 
                $wpdb->prepare( 
                    "SELECT role_name FROM $wpdb->ppc_roles AS r INNER JOIN $wpdb->pp_groups AS g ON g.ID = r.agent_id AND r.agent_type = 'pp_group' WHERE g.metagroup_type = 'wp_role' AND g.metagroup_id = %s", 
                    $default 
                ) 
            );

            $pp_filtered_types = \presspermit()->getEnabledPostTypes();
            $pp_metagroup_caps = [];
            $pp_cap_caster = \presspermit()->capCaster();

            foreach( $pp_supplemental_roles as $_role_name ) {
                $role_specs = explode( ':', $_role_name );
                if ( empty($role_specs[2]) || ! in_array( $role_specs[2], $pp_filtered_types ) )
                    continue;

                // add all type-specific caps whose base property cap is included in this pattern role
                // i.e. If 'edit_posts' is in the pattern role, grant $type_obj->cap->edit_posts
                $pp_metagroup_caps = array_merge( $pp_metagroup_caps, array_fill_keys( $pp_cap_caster->get_typecast_caps( $_role_name, 'site' ), true ) );
            }
        
            return $pp_metagroup_caps;
        } else {
            return [];
        }
    }
    
    private static function getStatusCaps($cap, $post_type, $post_status) {
        if (!$attributes = \PublishPress\StatusCapabilities::instance()) {
            return [$cap];
        }

        if (!\PublishPress\StatusCapabilities::customStatusPostMetaPermissions()) {
            return [$cap];
        }

        if (!isset($attributes->attributes['post_status']->conditions[$post_status])) {
            return [$cap];
        }

        $caps = [];

        if (isset($attributes->condition_metacap_map[$post_type][$cap]['post_status'][$post_status])) {
            $caps = array_merge($caps, (array) $attributes->condition_metacap_map[$post_type][$cap]['post_status'][$post_status]);
        }

        if (!empty($attributes->condition_cap_map[$cap]['post_status'][$post_status])) {
            $caps = array_merge($caps, (array) $attributes->condition_cap_map[$cap]['post_status'][$post_status]);
        }

        return $caps;
    }


    function actStatusesCustomColumn($column_name, $status_obj, $args = []) {
        $attrib = 'post_status';
        $attrib_type = (!empty($_REQUEST['status_type'])) ? sanitize_key($_REQUEST['status_type']) : 'moderation';    // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $cond = $status_obj->slug;

        if ('enabled' == $column_name) {
            $column_display_name = (isset($args['column_display_name'])) ? $args['column_display_name'] : '';

            $classes = "$column_name column-$column_name";

            if (in_array($status_obj->name, ['draft', 'future', 'publish', 'private', 'draft-revision'])) {
                esc_html_e('Standard', 'publishpress-statuses-pro');
            } else {
                if (!empty($status_obj->disabled)) {
                    $caption = esc_html__('Disabled', 'publishpress-statuses-pro');

                } elseif (empty($status_obj->public)) {
                    if (!empty($status_obj->private)
                    && (class_exists('\PublishPress\StatusCapabilities') && defined('PublishPress\StatusCapabilities::VERSION'))  // verison < 1.0.2 did not have this constant
                    ) {
                        if (defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS') && PPS_CUSTOM_PRIVACY_EDIT_CAPS) {
                            $caption = esc_html__('Custom', 'publishpress-statuses-pro');
                        } else {
                            $caption = esc_html__('Custom Read', 'publishpress-statuses-pro');
                        }

                    } elseif (empty($status_obj->capability_status)) {
                        $caption = esc_html__('Standard', 'publishpress-statuses-pro');
                    
                    } else {
                        if (!empty($status_obj->capability_status) && ($status_obj->capability_status != $status_obj->name)) {
                            if ($cap_status_obj = get_post_status_object($status_obj->capability_status)) {
                                // translators: %s is the name of the status that has the same capabilities
                                $caption = sprintf(esc_html__('(same as %s)', 'publishpress-statuses-pro'), esc_html($cap_status_obj->label));
                            } else {
                                $caption = esc_html__('Custom', 'publishpress-statuses-pro');
                            }
                        } else {
                            $caption = esc_html__('Custom', 'publishpress-statuses-pro');
                        }
                    }
                } else {
                    $caption = esc_html__('Standard', 'publishpress-statuses-pro');
                }

                $can_toggle_setting = empty($status_obj->disabled);

                if ($can_toggle_setting) {
                    echo '<a href="#" title="' . esc_attr(__('Toggle setting', 'publishpress-statuses-pro')) . '" style="cursor:n-resize">' . esc_html($caption) . '</a>';
                } else {
                    echo '<span>' . esc_html($caption) . '</span>';
                }
            }
        }
    }

} // end class
