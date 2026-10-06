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
class CustomPrivacyCapsUI {
    var $type_caps = [];

    function __construct() {
        add_filter('pp_capabilities_extra_post_capability_tabs', [$this, 'fltAddPrivacyCapsTab']);

        add_filter('pp_capabilities_full_width_tabs', function($wide_tabs) {
            if (defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS') && PPS_CUSTOM_PRIVACY_EDIT_CAPS) {
                $wide_tabs['custom-privacy'] = true;
            }

            return $wide_tabs;
        });

        add_action('publishpress-caps_manager_postcaps_section', [$this, 'drawUI']);

        add_filter('publishpress_caps_manager_typecaps', [$this, 'fltTypeCaps']);
    }

    function fltTypeCaps($type_caps) {
        return array_merge($type_caps, $this->type_caps);
    }

    function fltAddPrivacyCapsTab($tabs) {
        if (\PublishPress\Capabilities\Pro::customPrivacyStatusesAvailable()) {
            $tabs['custom-privacy'] = esc_html__('Visibility Statuses', 'capabilities-pro');
        }

        return $tabs;
    }

    function drawUI ($args = []) {
        global $capsman, $cme_cap_helper, $current_user, $publishpress;

        $defaults = [
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

        if (!Pro::customPrivacyStatusesAvailable()) {
            return;
        }

        $custom_privacy_edit_caps_enabled = defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS') && PPS_CUSTOM_PRIVACY_EDIT_CAPS;

        // This function is only called if PublishPress and its Custom Statuses module are active        
        $pp_terms = get_terms('post_status', ['hide_empty' => false]);
        foreach ($pp_terms as $term) {
            if (is_object($term)) {
                $list_statuses[$term->slug] = true;
            }
        }

        if (class_exists('PublishPress_Statuses')) {
            $ordered_statuses = \PublishPress_Statuses::getCustomStatuses(['private' => true]);

            $custom_status_post_types = \PublishPress_Statuses::instance()->options->post_types;
            $custom_status_post_types = array_filter($custom_status_post_types);
        
        } elseif (class_exists('PressShack\LibWP') && method_exists('PressShack\LibWP', 'getPostStatuses')) {
            $ordered_statuses = \PressShack\LibWP::getPostStatuses(['private' => true], 'object');
        }

        if (empty($ordered_statuses)) {
            return;
        }

        $do_postmeta_permissions = true;

        $postmeta_statuses = [];

        if ($attributes = Pro::customStatusCapabilities()) {
            if (!empty($attributes->attributes['post_status'])) {
                foreach($ordered_statuses as $status_term) {
                    if (!isset($status_term->slug)) {
                        $status_term->slug = $status_term->name;
                    }

                    $status_obj = get_post_status_object($status_term->slug);
                    if (empty($status_obj) || empty($status_obj->private)) {
                        continue;
                    }

                    if (!empty($attributes->attributes['post_status']->conditions[$status_term->slug])) {
                        $postmeta_statuses[$status_term->slug] = get_post_status_object($status_term->slug);
                    }
                }
            }
        }

        $id = 'cme-cap-type-tables-custom-privacy';
        $div_display = ($id == $active_tab_id) ? 'block' : 'none';

        $class = ($custom_privacy_edit_caps_enabled) ? '' : 'min-caps';

        echo '<div id="' . esc_attr($id) . '" class="' . esc_attr($class) . '" style="display:' . esc_attr($div_display) . '">';

        $url = defined('PUBLISHPRESS_STATUSES_VERSION') 
        ? admin_url("admin.php?page=publishpress-statuses&status_type=visibility")
        : admin_url("admin.php?page=presspermit-visibility-statuses");

        echo "<h3><a href='" . esc_url_raw($url) . "' target='_blank'>"
        . esc_html__('Visibility Status Capabilities', 'capabilities-pro') 
        . '</a></h3>';

        echo '<table class="widefat striped cme-typecaps">';

        do_action('presspermit_post_filters');

        $item_type = 'post';

        $is_english = (0 === strpos(get_locale(), 'en'));

        $cap_property_prefixes = ($custom_privacy_edit_caps_enabled)
        ? [
            'set' => ($is_english) ? __('Set', 'capabilities-pro') : __('Select'), 
            'read' => ($is_english) ? __('Read', 'capabilities-pro') : __('View'), 
            'edit' => __('Edit Others', 'capabilities-pro'), 
            'delete' => __('Delete Others', 'capabilities-pro'),
        ]

        : [
            'set' => ($is_english) ? __('Set', 'capabilities-pro') : __('Select'), 
            'read' => ($is_english) ? __('Read', 'capabilities-pro') : __('View'),
            'unused' => '',
        ]; 

        $cap_tips = [
            'set' =>    __( 'Can set posts to this status', 'capabilities-pro' ),
            'read' =>   __( 'Can read posts in this status', 'capabilities-pro' ),
            'edit' =>   __( "Can edit other users' posts in this status", 'capabilities-pro' ),
            'delete' => __("Can delete other users' posts in this status", 'capabilities-pro' ),
        ];

        $cap_property_prefixes = apply_filters('cme_visibility_cap_property_prefixes', $cap_property_prefixes);
        $cap_tips = apply_filters('cme_visibility_cap_property_tips', $cap_tips);

        if (!$custom_privacy_edit_caps_enabled) {
            $cap_tips['set'] = __('Can set posts to this status', 'capabilities-pro');
        }

        foreach($ordered_statuses as $status_term) {
            if (empty($status_term->slug)) {
                continue;
            }
            
            $status = $status_term->slug;
            if (!$status_obj = get_post_status_object($status)) {
                continue;
            }

            if (in_array($status, ['private', 'future'])) {
                continue;
            }

            // Only control type-specific caps for set / edit / delete if they will be enforced
            if (!empty($postmeta_statuses[$status])) {
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

            // column header (Set / Edit / Delete)
            if (count($cap_property_prefixes) > 1 || empty($first_row_done)) {
                $tr_class = (!empty($first_row_done)) ? 'pp-capabilities-status-header' : 'pp-capabilities-status-header-min';
                
                $status_header = "<tr class='" . esc_attr($tr_class) . "'><td></td>";

                // label cap properties
                foreach( $cap_property_prefixes as $prefix => $prefix_label ) {
                    if (($prefix != 'set') && empty($postmeta_statuses[$status])) {
                        $status_header .= '<td></td>';
                        continue;
                    }

                    $tip = ( isset( $cap_tips[$prefix] ) ) ? $cap_tips[$prefix] : '';

                    $status_header .= "<td title='" . esc_attr($tip) . "' class='post-cap'>";

                    if ($custom_privacy_edit_caps_enabled || ('read' == $prefix)) {
                        $status_header .= '<a href="#toggle_status_meta">' . $prefix_label . '</a>';
                    } else {
                        $status_header .= $prefix_label;
                    }

                    $status_header .= '</td>';
                }
            
                $status_header .= '</tr>';
            } else {
                $status_header = '';//'<tr class="pp-capabilities-status-spacer"></tr>';
            }

            $first_row_done = true;

            $status_ui = "<tr class='cme_status cme-postmeta-status cme_status_{$status}'>";

            $url = add_query_arg('status', $status, admin_url("admin.php?page=publishpress-statuses&action=edit-status&name=$status"));

            $status_ui .= "<td class='status-label status-label-advanced'>";

            $status_ui .= "<a href='" . esc_url($url) . "' target='_blank' title='" . esc_attr__('Configure post status properties, post types', 'capabilities-pro') . "'>";
            $status_ui .= esc_html($status_obj->label);
            $status_ui .= "</a>";

            $status_ui .= ($custom_privacy_edit_caps_enabled) ? '<br><input class="toggle_status_caps" name="toggle_status_caps" type="checkbox" title="' . esc_html__('Toggle all capabilities for this status', 'capabilities-pro') . '">' : '';

            $status_ui .= "</td>";

            $display_status = false;

            foreach( array_keys($cap_property_prefixes) as $prefix ) {
                $prop = "{$prefix}_{$status}_posts";

                $td_style = '';//($do_postmeta_permissions && !empty($postmeta_statuses)) ? '' : 'text-align: left;';

                $unused_class = (empty($cap_property_prefixes[$prefix])) ? 'unused ' : '';

                $status_col_ui = "<td class='" . esc_attr($unused_class) . "status-caps status-caps-" . esc_attr($prefix) . "' style='" . esc_attr($td_style) . "'>";

                if (('set' == $prefix) && !$custom_privacy_edit_caps_enabled) {
                    $td_classes = ['post-cap', 'cme_status_set_basic'];
                    $cap_slug = str_replace('-', '_', $status);
                    $cap_name = "status_change_{$cap_slug}";
                    $status_change_cap = $cap_name;

                    if ($is_administrator || current_user_can($cap_name)) {
                        $checked = checked(1, ! empty($rcaps[$cap_name]), false );
                        $style = (!empty($postmeta_statuses[$status])) ? ' style="margin-bottom: 10px;"' : '';
                        
                        $chk_classes = [];
                        
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

                    /*
                    if ( empty($postmeta_statuses[$status]) ) {
                        // Escaped piecemeal upstream; cannot be late-escaped until UI construction logic is reworked
                        echo $status_header . $status_ui . $status_col_ui;
                        $status_ui = '';
                    }
                    */
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
                            if (empty($custom_status_post_types[$post_type]) 
                            && (defined('PUBLISHPRESS_STATUSES_VERSION') || ('attachment' == $post_type))  // Permissions Pro Status Control applies type-specific reading capabilities without any post type enable 
                            ) {
                                continue;
                            }
                        
                            // if edit_others is same as edit_posts cap, don't display a checkbox for it
                            if ( ($prefix != 'edit_others' || (!empty($type_obj->cap->edit_others_posts) && $type_obj->cap->edit_others_posts != $type_obj->cap->edit_posts))
                            && ($prefix != 'delete_others' || (!empty($type_obj->cap->delete_others_posts) && $type_obj->cap->delete_others_posts != $type_obj->cap->delete_posts))
                            && (($prefix != 'set') || !empty($type_obj->cap->set_posts_status))
                            ) {
                                if ($prefix == 'set') {
                                    if (empty($type_obj->cap->set_posts_status)) {
                                        continue;
                                    }

                                    $cap = $type_obj->cap->set_posts_status;

                                    if ($type_obj->cap->set_posts_status == "status_change_{$status}") {
                                        continue;
                                    }
                                    
                                } else {
                                    if ('read' != $prefix) {
                                        $basic_type_property = "{$prefix}_posts";
        
                                        if (empty($type_obj->cap->$basic_type_property)) {
                                            continue;
                                        }
                                    }

                                    $cap = (in_array($prefix, ['edit_others', 'delete_others'])) ? $type_obj->cap->$basic_type_property : "{$prefix}_post";
                                }

                                $_caps = Pro::getStatusCaps($cap, $post_type, $status);

                                $caps = array_diff($_caps, [$cap], (array) $type_obj->cap, $post_type_caps, $page_type_caps);
                                $cap_name = reset($caps);

                                if ($cap_name == "status_change_{$status}") {
                                    continue;
                                }

                                if ($cap_name) {
                                    $td_classes []= "post-cap";
                                    
                                    if ($is_administrator || current_user_can($cap_name)) {
                                        if (!empty($pp_metagroup_caps[$cap_name])) {
                                            $title_text = sprintf( __( '%s: assigned by Permission Group', 'capabilities-pro' ), $cap_name );
                                        } else {
                                            $title_text = $cap_name;
                                        }
                                        
                                        $disabled = false; // (('set' == $prefix) && empty($rcaps[$status_change_cap])) ? 'disabled' : '';
                                        $checked = checked(1, ! empty($rcaps[$cap_name]), false );

                                        $chk_classes = [];

                                        if (!empty($pp_metagroup_caps[$cap_name])) {
                                            $chk_classes []= 'cm-has-via-pp';
                                        }

                                        $chk_class = ( $chk_classes ) ? implode(' ', $chk_classes) : '';

                                        if ( ! empty($pp_metagroup_caps[$cap_name]) ) {
                                            $tool_tip = sprintf(__( '%s: assigned by Permission Group', 'capabilities-pro' ), '<strong>' . $cap_name . '</strong>' );
                                        } else {
                                            $tool_tip = sprintf(__( 'This capability is %s', 'capabilities-pro' ), '<strong>' . $cap_name . '</strong>' );
                                        }

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
                    echo '</td>';
                }
            } // endforeach cap properties

            // status row
            if (!empty($display_status)) {
                if ($status_ui) {
                    if (!empty($postmeta_statuses)):?>
                        <tr><td class="row-spacer"></td><td></td><td></td><td></td></tr>
                    <?php endif;

                    // Escaped piecemeal upstream; cannot be late-escaped until UI construction logic is reworked
                    echo $status_ui;
                }

                if (!empty($postmeta_statuses)) :?>
     
                <?php endif;

                echo '</tr>';
            }
            
        } // endforeach statuses
        ?>

        </tr>

        </table>

        <?php 
        // clicking on post type name toggles corresponding checkbox selections
        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready( function($) {
            $('#cme-cap-type-tables-custom-privacy input[name="toggle_status_caps"]').click( function() {
                //var chks = $(this).closest('tr').find('> td').find('input[name!="toggle_status_caps"][type="checkbox"]');
                var chks = $(this).closest('tr').find('> td').find('input[name!="toggle_status_caps"][type="checkbox"]');
                $(chks).prop('checked', !$(chks).first().is(':checked'));

                //$(this).closest('tr').prev().find('a.toggle-set-caps').trigger('click');

                return false;
            });

            $('#cme-cap-type-tables-custom-privacy a[href="#toggle_status_meta"]').click( function() {
                var tdIndex = $(this).closest('td').index() - 1;
                var chks = $(this).closest('tr').next().find("td.status-caps:eq(" + tdIndex + ") td.post-cap input[type='checkbox']");
                var setChecked = !$(chks).first().prop('checked');
                
                $(chks).prop( 'checked', setChecked );

                /*
                // Trigger enable / disable of type-specific status set capabilities based on selection of basic set capability
                if ($(this).hasClass('toggle-set-caps')) {
                    var basicSet = $(this).closest('tr').next().find("td.status-caps:eq(" + tdIndex + ") input.cme_status_set_basic");
                    $(basicSet).prop('checked', setChecked);
                    $(chks).attr('disabled', !$(basicSet).prop('checked'));
                }
                */

                return false;
            });

            /*
            $('#cme-cap-type-tables-custom-privacy input.cme_status_set_basic').on('click', function() {
               $(this).next('table').find('td.post-cap input[type="checkbox"]').attr('disabled', !$(this).prop('checked'));

               if (!$(this).prop('checked')) {
                    $(this).next('table').find('tbody tr td.post-cap label input[type="checkbox"]').prop('checked', $(this).prop('checked'));
                }
            });
            */

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
