<?php
namespace PublishPress\Capabilities;

/*
 * PublishPress Capabilities [Free]
 *
 * Capabilities Screen UI: Custom Statuses interface
 *
 * Note that the free version handles only the status selection capabilities.
 * Custom status editing and deletion capability control is Pro functionality.
 *
 */
class CustomStatusCapsUI {
    var $type_caps = [];

    function __construct() {
        add_filter('pp_capabilities_extra_post_capability_tabs', [$this, 'fltAddStatusCapsTab']);

        add_filter('pp_capabilities_full_width_tabs', function($wide_tabs) {
            if (\PublishPress\Capabilities\Pro::customStatusPostMetaPermissions()) {
                $wide_tabs['custom-status'] = true;
            }

            if (defined('PUBLISHPRESS_REVISIONS_VERSION')) {
                $wide_tabs['revision-status'] = true;
            }

            return $wide_tabs;
        });

        add_action('publishpress-caps_manager_postcaps_section', [$this, 'drawUI']);

        if (defined('PUBLISHPRESS_REVISIONS_VERSION')) {
            add_action('publishpress-caps_manager_postcaps_section', [$this, 'drawRevisionStatusUI'], 11);
        }

        add_filter('publishpress_caps_manager_typecaps', [$this, 'fltTypeCaps']);
        add_filter('publishpress_caps_manage_additional_caps', [$this, 'fltAdditionalCaps']);
    }

    function fltTypeCaps($type_caps) {
        return array_merge($type_caps, $this->type_caps);
    }

    // Make sure Additional Caps checkboxes don't include any status change capabilities for statuses that have distinct permissions disabled.
    // Any available status change capabilities will be shown in the Custom Statuses box.
    function fltAdditionalCaps($caps) {
        foreach(array_keys($caps) as $cap_name) {
            if (0 === strpos($cap_name, 'status_change_')) {
               unset($caps[$cap_name]);
            }
        }

        return $caps;
    }

    function fltAddStatusCapsTab($tabs) {
        if (\PublishPress\Capabilities\Pro::customStatusPermissionsAvailable()
        && (get_option('cme_custom_status_control') || Pro::presspermitStatusControlActive())
        ) {
            $tabs['custom-status'] = esc_html__('Custom Statuses', 'capabilities-pro');
        }

        if (defined('PUBLISHPRESS_REVISIONS_VERSION')) {
            $tabs['revision-status'] = esc_html__('Revision Statuses', 'capabilities-pro');
        }

        return $tabs;
    }

    function drawRevisionStatusUI($args) {
        $args['tab_id'] = 'revision-status';
        $this->drawUI($args);
    }

    function drawUI ($args = []) {
        global $capsman, $cme_cap_helper, $current_user, $publishpress;

        $defaults = [
            'tab_id' => 'custom-status',
            'current' => '',
            'rcaps' => [],
            'is_administrator' => false,
            'pp_metagroup_caps' => [],
            'default_caps' => [],
            'custom_types' => [],
            'defined' => [],
            'unfiltered' => [],
            'type_caps' => [],
            'active_tab_id' => '',
        ];

        foreach(array_keys($defaults) as $var) {
            $$var = (isset($args[$var])) ? $args[$var] : $defaults[$var];
        }

        $this->type_caps = $type_caps;

        $list_statuses = [];

        $statuses = get_post_stati(['internal' => false, 'public' => false, 'private' => false], 'object');

        // This function is only called if PublishPress and its Custom Statuses module are active
        $pp_terms = get_terms('post_status', ['hide_empty' => false]);
        foreach ($pp_terms as $term) {
            if (is_object($term)) {
                $list_statuses[$term->slug] = true;
            }
        }

        if (class_exists('PublishPress_Statuses')) {
            if (('revision-status' == $tab_id) && !has_filter('capabilities_pro_custom_status_args')) {
                $ordered_statuses = [];
            } else {
                $ordered_statuses = \PublishPress_Statuses::getPostStati(
                    apply_filters(
                        'capabilities_pro_custom_status_args',
                        ['moderation' => true],
                        $tab_id
                    ),
                    'object'
                );
            }

            $custom_status_post_types = \PublishPress_Statuses::instance()->options->post_types;
            $custom_status_post_types = array_filter($custom_status_post_types);
        } elseif (!empty($publishpress) && !empty($publishpress->custom_status) && method_exists($publishpress->custom_status, 'get_custom_statuses')) {
            $ordered_statuses = $publishpress->custom_status->get_custom_statuses();

            // Post types that are configured by PublishPress to support custom statuses (this is NOT a per-status configuration)
            $custom_status_post_types = array_intersect($publishpress->modules->custom_status->options->post_types, ['on']);
        } else {
            $ordered_statuses = [];
        }

        $empty_header_columns = [];
        $empty_headers = true;
        $postmeta_statuses = [];

        $do_postmeta_permissions = Pro::customStatusPostMetaPermissions()
        && (('revision-status' != $tab_id) || (defined('PUBLISHPRESS_REVISIONS_VERSION') && version_compare(PUBLISHPRESS_REVISIONS_VERSION, '3.6.0-beta', '>=') && get_option('rvy_permissions_compat_mode')));

        if ($do_postmeta_permissions) {
            if ($attributes = Pro::customStatusCapabilities()) {
                if (!empty($attributes->attributes['post_status'])) {
                    foreach($ordered_statuses as $status_term) {
                        $status_obj = get_post_status_object($status_term->slug);
                        if (empty($status_obj) || !empty($status_obj->private) || !empty($status_obj->public) || ('draft' == $status_term->slug)) {
                            continue;
                        }

                        if (!empty($attributes->attributes['post_status']->conditions[$status_term->slug])) {
                            $postmeta_statuses[$status_term->slug] = get_post_status_object($status_term->slug);
                        }
                    }

                    if (!defined('PUBLISHPRESS_STATUSES_VERSION')) {
                        // custom moderation status registered by PublishPress Permissions
                        if (!empty($attributes->attributes['post_status']->conditions['approved'])) {
                            $postmeta_statuses['approved'] = get_post_status_object('approved');
                            $ordered_statuses[99999] = (object) ['slug' => 'approved', 'name' => $postmeta_statuses['approved']->label];
                        }
                    }
                }
            }
        }

        $id = 'cme-cap-type-tables-' . $tab_id;
        $div_display = ($id == $active_tab_id) ? 'block' : 'none';

        echo '<div id="' . esc_attr($id) . '" style="display:' . esc_attr($div_display) . '">';

        $url = defined('PUBLISHPRESS_STATUSES_VERSION')
        ? admin_url("admin.php?page=publishpress-statuses")
        : admin_url("admin.php?page=pp-modules-settings&module=pp-custom-status-settings");

        $title = ('revision-status' == $tab_id) ? esc_html__('Revision Status Capabilities', 'capabilities-pro') : esc_html__('Custom Status Capabilities', 'capabilities-pro');

        echo "<h3><a href='" . esc_url_raw($url) . "' target='_blank'>"
        . $title
        . '</a></h3>';

        echo '<table class="widefat striped cme-typecaps">';

        if ($do_postmeta_permissions) {
            do_action('presspermit_post_filters');
        }

        $is_english = (0 === strpos(get_locale(), 'en'));

        $cap_property_prefixes = ($do_postmeta_permissions) ? [
            'set' => ($is_english) ? __('Set', 'capabilities-pro') : __('Select'),
            'edit' => __('Edit'),
            'edit_others' => __('Edit Others', 'capabilities-pro'),
            'delete' => __('Delete'),
            'delete_others' => __('Delete Others', 'capabilities-pro'),
        ]

        : ['set' => ($is_english) ? __('Set', 'capabilities-pro') : __('Select')];

        $cap_tips = array(
            'set' =>            __( 'Can set posts to this status', 'capabilities-pro' ),
            'edit' =>           __( 'Can edit posts of this status', 'capabilities-pro' ),
            'edit_others' =>    __( "Can edit other's posts of this status", 'capabilities-pro' ),
            'delete' =>         __( 'Can delete posts of this status', 'capabilities-pro' ),
            'delete_others' =>  __( "Can delete other's posts of this status", 'capabilities-pro' ),
        );

        $last_status_has_postmeta_caps = false;

        foreach($ordered_statuses as $status_term) {
            $empty_header_columns = [];

            $status = $status_term->slug;
            if (!$status_obj = get_post_status_object($status)) {
                continue;
            }

            if (!empty($status_obj->public) || !empty($status_obj->private) || in_array($status, ['draft', 'future'])) {
                continue;
            }

            // Only control type-specific caps for set / edit / delete if they will be enforced
            if ($do_postmeta_permissions && !empty($postmeta_statuses[$status])) {
                foreach( $defined['type'] as $post_type => $type_obj ) {

                    // Does this post type require distinct type-specific capabilities?
                    if ( in_array( $post_type, $unfiltered['type'] ) ) {
                        continue;
                    }

                    if (function_exists('presspermit') && !in_array($post_type, \presspermit()->getEnabledPostTypes())) {
                        continue;
                    }

                    // Do PublishPress module settings enable custom statuses for this post type?
                    if (empty($custom_status_post_types[$post_type])) {
                        continue;
                    }

                    if (!Pro::customStatusPostMetaPermissions($post_type, $status)) {
                        continue;
                    }
                }
            }

            // column header (Set / Edit / Edit Others / Delete / Delete Others)
            if ($do_postmeta_permissions && !empty($postmeta_statuses) && (!empty($postmeta_statuses[$status]) || $last_status_has_postmeta_caps || empty($first_row_done))
            || (empty($first_row_done))
            ) {
                $status_header = (!$do_postmeta_permissions) ? '<thead>' : '';

                $tr_class = ($last_status_has_postmeta_caps && !empty($first_row_done)) ? 'pp-capabilities-status-header' : 'pp-capabilities-status-header-min';
                $status_header .= "<tr class='" . esc_attr($tr_class) . "'><td></td>";

                $empty_header_columns = [];

                // label cap properties
                foreach($cap_property_prefixes as $prefix => $prefix_label) {
                    if (($prefix != 'set') && empty($postmeta_statuses[$status])) {
                        $empty_header_columns[] = $prefix;
                        continue;
                    } else {
                        $empty_headers = false;
                    }

                    $tip = ( isset( $cap_tips[$prefix] ) ) ? $cap_tips[$prefix] : '';
                    $class = "toggle-{$prefix}-caps";

                    $status_header .= "<td title='" . esc_attr($tip) . "' class='post-cap";

                    if ('set' != $prefix) $status_header .= ' post-edit-cap';

                    $status_header .= "'>";

                    if (!empty($postmeta_statuses[$status])) {
                        $class = ('set' == $prefix) ? 'toggle-set-caps' : '';
                        $status_header .= '<a href="#toggle_status_meta" class="' . esc_attr($class) . '" >' . $prefix_label . '</a>';
                    } else {
                        $status_header .= $prefix_label;
                    }

                    $status_header .= '</td>';
                }

                foreach ($empty_header_columns as $col) {
                    $status_header .= "<td></td>";
                }

                $status_header .= '</tr>';

                $status_header .= (!$do_postmeta_permissions) ? '</thead>' : '';
            } else {
                $status_header = '';//($do_postmeta_permissions) ? '<tr class="pp-capabilities-status-spacer"></tr>' : '';
            }

            $first_row_done = true;
            $last_status_has_postmeta_caps = false;

            $postmeta_class = ($do_postmeta_permissions) ? 'cme-postmeta-status' : '';
            $status_ui = "<tr class='kinigan cme_status $postmeta_class cme_status_{$status}'>";

            $td_class = ($do_postmeta_permissions && !empty($postmeta_statuses)) ? ' status-label-advanced' : '';
            $td_style = '';//($do_postmeta_permissions && !empty($postmeta_statuses)) ? '' : 'width: 150px;';

            $url = add_query_arg('status', $status, admin_url("admin.php?page=publishpress-statuses&pp_tab=post_access&action=edit-status&name=$status"));

            $status_ui .= "<td class='status-label " . esc_html($td_class) . "' style='" . esc_html($td_style) . "'>";

            $status_ui .= "<a href='" . esc_url($url) . "' target='_blank' title='" . esc_attr__('Configure status properties, post types, capability requirements', 'capabilities-pro') . "'>";
            $status_ui .= esc_html($status_obj->label);
            $status_ui .= "</a>";

            $status_ui .= !empty($postmeta_statuses[$status]) ? '<br><input class="toggle_status_caps" name="toggle_status_caps" type="checkbox" title="' . esc_html__('Toggle all capabilities for this status', 'capabilities-pro') . '">' : '';

            $status_ui .= "</td>";

            $display_status = false;

            foreach (array_keys($cap_property_prefixes) as $prefix) {
                $prop = "{$prefix}_{$status}_posts";

                $td_style = '';//($do_postmeta_permissions && !empty($postmeta_statuses)) ? '' : 'text-align: left;';

                $status_col_ui = "<td class='status-caps status-caps-" . esc_attr($prefix) . "' style='" . esc_attr($td_style) . "'>";

                if ('set' == $prefix) {
                    $cap_slug = str_replace('-', '_', $status);
                    $cap_name = "status_change_{$cap_slug}";
                    $status_change_cap = $cap_name;

                    if (('pending' == $status)
                    && class_exists('PublishPress_Statuses') && !empty(\PublishPress_Statuses::instance()->options) && empty(\PublishPress_Statuses::instance()->options->pending_status_regulation)
                    ) {
                        $cap_name = '';
                    }

                    if ($cap_name) {
                        if ($is_administrator || current_user_can($cap_name)) {
                            $checked = checked(1, ! empty($rcaps[$cap_name]), false );
                            $style = (!empty($postmeta_statuses[$status])) ? ' style="margin-bottom: 10px;"' : '';

                            $chk_classes = ['cme_status_set_basic'];

                            if ( ! empty($pp_metagroup_caps[$cap_name]) ) {
                                $tool_tip = sprintf(__( '%s: assigned by Permission Group', 'capabilities-pro' ), '<strong>' . $cap_name . '</strong>' );
                                $chk_classes []= 'cm-has-via-pp';
                            } else {
                                $tool_tip = sprintf(__( 'This capability is %s', 'capabilities-pro' ), '<strong>' . $cap_name . '</strong>' );

                                if (0 === strpos($cap_name, 'status_change_') && !empty($postmeta_statuses[$status])) {
                                    $tool_tip .= '<br><br>' . __('It is required in addition to the type-specific capability.', 'capabilities-pro');
                                }
                            }

                            $chk_class = ( $chk_classes ) ? implode(' ', $chk_classes) : '';

                            $checkbox = '<div class="ppc-tool-tip disabled"><input type="checkbox" class="' . esc_attr($chk_class) . '" name="caps[' . esc_attr($cap_name) . ']" autocomplete="off" value="1" ' . $checked . $style . ' />
                                <div class="tool-tip-text">
                                    <p>'. $tool_tip .'</p>
                                    <i></i>
                                </div>
                            </div>';

                            $status_col_ui .= $checkbox;

                            $display_status = true;
                        }

                        $this->type_caps[$cap_name] = true;
                    }

                    if ( empty($postmeta_statuses[$status]) ) {
                        // Escaped piecemeal upstream; cannot be late-escaped until UI construction logic is reworked
                        echo $status_header . $status_ui . $status_col_ui;
                        $status_ui = '';
                    }
                }

                if ($is_post_meta_status = !empty($postmeta_statuses[$status])) {
                    $status_col_ui .= '<table>';

                    $post_type_obj = get_post_type_object('post');
                    $post_type_caps = (!empty($post_type_obj)) ? (array) $post_type_obj->cap : [];

                    $page_type_obj = get_post_type_object('page');
                    $page_type_caps = (!empty($page_type_obj)) ? (array) $page_type_obj->cap : [];

                    foreach($defined['type'] as $post_type => $type_obj) {
                        if (!$type_obj || empty($type_obj->cap)) {
                            continue;
                        }

                        $display_row = false;

                        $td_classes = [];
                        $checkbox = '';
                        $title_text = '';

                        do {
                            if (empty($custom_status_post_types[$post_type])) {
                                continue;
                            }

                            // if edit_others is same as edit_posts cap, don't display a checkbox for it
                            if ( ($prefix != 'edit' || (!empty($type_obj->cap->edit_others_posts) && $type_obj->cap->edit_others_posts != $type_obj->cap->edit_posts))
                            && ($prefix != 'delete' || (!empty($type_obj->cap->delete_others_posts) && $type_obj->cap->delete_others_posts != $type_obj->cap->delete_posts))
                            && (($prefix != 'set') || !empty($type_obj->cap->set_posts_status))
                            ) {
                                $status_nodash = str_replace('-', '_', $status);

                                if ($prefix == 'set') {
                                    if (empty($type_obj->cap->set_posts_status)) {
                                        continue;
                                    }

                                    $cap = $type_obj->cap->set_posts_status;

                                    if ($type_obj->cap->set_posts_status == "status_change_{$status_nodash}") {
                                        continue;
                                    }
                                } else {
                                    $basic_type_property = "{$prefix}_posts";

                                    if (empty($type_obj->cap->$basic_type_property)) {
                                        continue;
                                    }

                                    $cap = (in_array($prefix, ['edit_others', 'delete_others'])) ? $type_obj->cap->$basic_type_property : "{$prefix}_post";
                                }

                                $_caps = Pro::getStatusCaps($cap, $post_type, $status);

                                $caps = array_diff($_caps, [$cap], (array) $type_obj->cap, $post_type_caps, $page_type_caps);
                                $cap_name = reset($caps);

                                if ($cap_name == "status_change_{$status_nodash}") {
                                    continue;
                                }

                                if ($cap_name) {
                                    $td_classes []= "post-cap";

                                    if ($is_administrator || current_user_can($cap_name)) {
                                        $disabled = (('set' == $prefix) && empty($rcaps[$status_change_cap])) ? 'disabled' : '';
                                        $checked = checked(1, ! empty($rcaps[$cap_name]), false );

                                        $chk_classes = [];

                                        if ( ! empty($pp_metagroup_caps[$cap_name]) ) {
                                            $tool_tip = sprintf(__( '%s: assigned by Permission Group', 'capabilities-pro' ), '<strong>' . $cap_name . '</strong>' );
                                            $chk_classes []= 'cm-has-via-pp';
                                        } else {
                                            $tool_tip = sprintf(__( 'This capability is %s', 'capabilities-pro' ), '<strong>' . $cap_name . '</strong>' );
                                        }

                                        $chk_class = ( $chk_classes ) ? implode(' ', $chk_classes) : '';

                                        $checkbox = '<div class="ppc-tool-tip disabled"><input type="checkbox" class="' . esc_attr($chk_class) . '" name="caps[' . esc_attr($cap_name) . ']" id="caps_' . $cap_name . '" autocomplete="off" value="1" ' . $checked . ' />
                                            <div class="tool-tip-text">
                                                <p>'. $tool_tip .'</p>
                                                <i></i>
                                            </div>
                                        </div>';

                                        $checkbox .= ' <label for="caps_' . $cap_name . '">'
                                        . esc_html($type_obj->label)
                                        . '</label>';

                                        $this->type_caps [$cap_name] = true;
                                        $display_row = true;
                                        $last_status_has_postmeta_caps = true;
                                    }
                                }
                            } elseif(!empty($type_obj->cap->$prop)) {
                                $title_text = sprintf( esc_attr__('shared capability: %s', 'capabilities-pro'), esc_attr($type_obj->cap->$prop));
                            }

                            if (isset($rcaps[$cap_name]) && empty($rcaps[$cap_name])) {
                                $td_classes []= "cap-neg";
                            }
                        } while (false);

                        echo $status_header . $status_ui . $status_col_ui;
                        $status_header = $status_ui = $status_col_ui = '';

                        if ( $display_row ) {
                            // Escaped piecemeal upstream; cannot be late-escaped until UI construction logic is reworked
                            echo $status_header . $status_ui . $status_col_ui;
                            $status_header = $status_ui = $status_col_ui = '';

                            $td_class = ( $td_classes ) ? implode(' ', $td_classes): '';

                            $row = "<tr><td class='" . esc_attr($td_class) . "' title='" . esc_attr($title_text) . "'>$checkbox";

                            if ( false !== strpos( $td_class, 'cap-neg' ) ) {
                                $row .= '<input type="hidden" class="cme-negation-input" name="caps[' . $cap_name . ']" value="" />';
                            }

                            $row .= '</td>';
                            $row .= '</tr>';

                            echo $row;
                        }
                    }

                    echo '</table>';

                } elseif ('set' !== $prefix) {
                    continue;
                }

                if (!$status_ui) {
                    if (!empty($display_row)) {
                        echo '<div class="row-spacer">';
                    }
                }
            } // endforeach cap properties

            // status row
            if (!empty($display_status)) {
                if ($status_ui) {
                    if (!$is_post_meta_status && $do_postmeta_permissions && !empty($postmeta_statuses)):?>
                        <tr><td class="row-spacer"></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <?php endif;

                    // Escaped piecemeal upstream; cannot be late-escaped until UI construction logic is reworked
                    echo $status_ui;
                }

                if (!$is_post_meta_status && $do_postmeta_permissions && !empty($postmeta_statuses)) :?>
                    <td></td><td></td><td></td><td></td>
                <?php endif;
            }

            // this is needed to match the column styles to headers
            if ($empty_headers) {
                foreach ($empty_header_columns as $empty_header_column) {
                    echo '<td></td>';
                }
            }

            echo '</tr>';
        } // endforeach statuses
        ?>

        <?php

        if (Pro::customStatusPermissionsAvailable() && !Pro::customStatusPostMetaPermissions()):?>
        <tr>
            <td class="cme-status-footer cme-custom-status-hints" colspan="7">
            <p>
            <?php
            $url = admin_url('admin.php?page=pp-capabilities-settings&pp_tab=capabilities');

            printf(
                esc_html__('Note: Status-specific post editing capabilities are %1$sdisabled%2$s.', 'capabilities-pro'),
                '<a href="' . esc_url($url) . '">',
                '</a>'
            );
            ?>
            </p>
            </td>
        </tr>
        <?php else:
            if ('revision-status' == $tab_id):?>
            <tr>
                <td class="cme-status-footer cme-custom-status-hints" colspan="7">
                <p>
                <?php
                if (!defined('PUBLISHPRESS_REVISIONS_VERSION') && !defined('PUBLISHPRESS_STATUSES_PRO_VERSION')) {
                    printf(
                        esc_html__('To control revision editing and deletion, install the %1$sPublishPress Revisions%2$s and %3$sStatuses Pro%4$s plugins.', 'capabilities-pro'),
                        '<a href="https://publishpress.com/revisions/" target="_blank">',
                        '</a>',
                        '<a href="https://publishpress.com/statuses/" target="_blank">',
                        '</a>'
                    );
                } elseif (!defined('PUBLISHPRESS_STATUSES_PRO_VERSION')) {
                    printf(
                        esc_html__('To define and control Revision statuses, install the %1$sStatuses Pro%2$s plugin.', 'capabilities-pro'),
                        '<a href="https://publishpress.com/statuses/" target="_blank">',
                        '</a>'
                    );
                } elseif (!defined('PUBLISHPRESS_REVISIONS_VERSION')) {
                    printf(
                        esc_html__('To define and control Revision statuses, install the %1$sPublishPress Revisions%2$s plugin.', 'capabilities-pro'),
                        '<a href="https://publishpress.com/revisions/" target="_blank">',
                        '</a>'
                    );

                } elseif (defined('PUBLISHPRESS_REVISIONS_VERSION') && version_compare(PUBLISHPRESS_REVISIONS_VERSION, '3.6.0-rc', '<')) {
                    printf(
                        esc_html__('To define and control Revision statuses, update the %1$sPublishPress Revisions%2$s plugin to version %3$s or higher.', 'capabilities-pro'),
                        '<a href="https://publishpress.com/revisions/" target="_blank">',
                        '</a>',
                        '3.6.0'
                    );
                } else {
                    if (!get_option('rvy_permissions_compat_mode')) {
                        $url = admin_url('admin.php?page=revisionary-settings&ppr_tab=revisions');

                        printf(
                            esc_html__('To control revision editing and deletion, please set %1$sRevisions Compatibility Mode%2$s to "Enhanced Revision access control"', 'capabilities-pro'),
                            '<a href="' . $url . '" target="_blank">',
                            '</a>'
                        );
                    } else {
                        $url = admin_url('admin.php?action=statuses&status_type=revision&page=publishpress-statuses');

                        printf(
                            esc_html__('To enable or disable enforcement of custom editing capabilities for any status, click its Post Access cell in the %1$sStatuses table%2$s, then reload this screen.', 'capabilities-pro'),
                            '<a href="' . $url . '" target="_blank">',
                            '</a>'
                        );
                    }
                }
                ?>
                </p>
                </td>
            </tr>
            <?php else:?>
                <tr>
                <td class="cme-status-footer cme-custom-status-hints" colspan="7">
                <p>
                <?php
                $url = admin_url('admin.php?page=publishpress-statuses');

                printf(
                    esc_html__('To enable or disable enforcement of custom editing capabilities for any status, click its Post Access cell in the %1$sStatuses table%2$s, then reload this screen.', 'capabilities-pro'),
                    '<a href="' . $url . '" target="_blank">',
                    '</a>'
                );
                ?>
                </p>
                </td>
            </tr>
            <?php endif;?>
        <?php endif;?>

        </table>

        <?php
        // clicking on post type name toggles corresponding checkbox selections
        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready( function($) {
            $('#cme-cap-type-tables-<?php echo $tab_id;?> input[name="toggle_status_caps"]').click( function() {
                var chks = $(this).closest('tr').find('> td input[name!="toggle_status_caps"][type="checkbox"]');
                $(chks).prop('checked', !$(chks).first().is(':checked'));

                // enable / disable type-specific set caps based on selection of basic set capability
                var basicSet = $(this).closest('tr').find('td.status-caps-set input.cme_status_set_basic');
                $(this).closest('tr').find('td.status-caps-set table td.post-cap input').attr('disabled', !$(basicSet).prop('checked'));

                return false;
            });

            $('#cme-cap-type-tables-<?php echo $tab_id;?> a[href="#toggle_status_meta"]').click( function() {
                var tdIndex = $(this).closest('td').index() - 1;
                var chks = $(this).closest('tr').next().find("td.status-caps:eq(" + tdIndex + ") td.post-cap input[type='checkbox']");
                var setChecked = !$(chks).first().prop('checked');

                $(chks).prop( 'checked', setChecked );

                // enable / disable type-specific set caps based on selection of basic set capability
                if ($(this).hasClass('toggle-set-caps') && $(chks).length) {
                    var basicSet = $(chks).first().closest('td.status-caps').find('input.cme_status_set_basic');
                    $(basicSet).prop('checked', setChecked);
                    $(chks).attr('disabled', !$(basicSet).prop('checked'));
                }

                return false;
            });

            $('#cme-cap-type-tables-<?php echo $tab_id;?> input.cme_status_set_basic').on('click', function() {
               // enable / disable type-specific set caps based on selection of basic set capability
               $(this).closest('td').find('td.post-cap input[type="checkbox"]').attr('disabled', !$(this).prop('checked'));

               // to avoid confusion, don't allow type-specific set caps to be assigned without basic set cap
               if (!$(this).prop('checked')) {
                    $(this).next('table').find('tbody tr td.post-cap label input[type="checkbox"]').prop('checked', $(this).prop('checked'));
                }
            });

            $('div.cme-show-status-hint').click(function() {
                $(this).hide();
                $('div.pp-status-control-notice').show();
                return false;
            });
        });
        /* ]]> */
        </script>

        </div>

        <?php
    }
}
