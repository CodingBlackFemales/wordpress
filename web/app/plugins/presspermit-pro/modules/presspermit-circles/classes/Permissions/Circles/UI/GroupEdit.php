<?php
namespace PublishPress\Permissions\Circles\UI;

class GroupEdit
{
    public static function circlesUI($group_type, $group_id)
    {
        if (!in_array($group_type, presspermit()->groups()->getGroupTypes(), true) 
        || !$circle_types = Circles::getCircleTypes()
        ) {
            return;
        }

        $pp = presspermit();

        ?>
        <style type="text/css">
        #pp_circle_activation tr {float: left;}
        #pp_circle_activation td {padding-right: 70px;}
        .toggle-group{margin-bottom:24px}
        .toggle-item{display:flex;align-items:center;margin-bottom:12px}
        .toggle-switch{position:relative;display:inline-block;width:50px;height:24px;margin-right:12px}
        .toggle-switch input{opacity:0;width:0;height:0}
        .slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background-color:#d1d5db;transition:.4s;border-radius:24px}
        .slider:before{position:absolute;content:"";height:16px;width:16px;left:4px;bottom:4px;background-color:#fff;transition:.4s;border-radius:50%}
        input:checked+.slider{background-color:#4f46e5}
        input:checked+.slider:before{transform:translateX(26px)}
        .info-banner{background-color:#f3f4f6;border-left:3px solid #4f46e5;border-radius:0 4px 4px 0;margin:12px 0;font-size:14px;display:flex;align-items:flex-start}
        .info-banner i{color:#4f46e5;margin-right:8px;font-size:16px}
        .banner-secondary{border-left-color:#818cf8 !important;}
        .post-type-selector{margin-top:16px;padding-left:62px}
        .post-type-selector h3{font-size:14px;font-weight:500;margin-bottom:12px;color:#374151}
        .checkbox-group{display:flex;flex-wrap:wrap;gap:16px;margin-bottom:12px}
        .checkbox-item{display:flex;align-items:center;cursor:pointer;user-select:none}
        .checkbox-item input{position:absolute;opacity:0;cursor:pointer;height:0;width:0}
        .checkmark{height:18px;width:18px;background-color:#fff;border:1px solid #d1d5db;border-radius:4px;margin-right:8px;display:flex;align-items:center;justify-content:center}
        .checkbox-item:hover input~.checkmark{background-color:#f3f4f6}
        .checkbox-item input:checked~.checkmark{background-color:#4f46e5;border-color:#4f46e5}
        .checkmark:after{content:"";display:none;width:4px;height:8px;border:solid #fff;border-width:0 2px 2px 0;transform:rotate(45deg);margin-bottom:2px}
        .checkbox-item input:checked~.checkmark:after{display:block}
        </style>
        <?php

        echo '<div id="pp_circle_activation" class="pp-group-box pp-group_post-types" style="flex: 1;">'
            . '<h3>';

        esc_html_e('Circle Activation', 'presspermit-pro');

        echo '</h3>';

        $all_types = $pp->getEnabledPostTypes([], 'object');
        $circles = Circles::getGroupCircles($group_type, $group_id);

        $types_csv = implode(",", array_merge(array_keys($all_types)));
        echo "<input type='hidden' name='reviewed_circle_types' value=" . esc_attr($types_csv) . " />";

        $circle_captions = ['read' => esc_html__('Visibility Circle', 'presspermit-pro'), 'edit' => esc_html__('Editorial Circle', 'presspermit-pro')];

        $disabled_circle_types = [];

        // If a WP role has exemption capability assigned, don't offer to make it a circle
        if ('pp_group' == $group_type) {
            if ($metagroup = presspermit()->groups()->isMetagroup('wp_role', $group_id)) {
                if ($role = get_role($metagroup->metagroup_id) ) {
                    foreach ($circle_types as $_circle_type) {
                        if (!empty($role->capabilities["pp_exempt_{$_circle_type}_circle"])) {
                            $disabled_circle_types[$_circle_type] = "pp_exempt_{$_circle_type}_circle";
                        }
                    }
                }
            }
        }

        $tooltips = [
            'read' => esc_html__('Users in this group will only be able to view posts that were authored by other users in this group.', 'presspermit-pro'),
            'edit' => esc_html__('Users in this group will only be able to edit posts that were authored by other users in this group.', 'presspermit-pro'),
        ];
        $mandatory_text = esc_html__('Normal capability requirements will also be enforced.', 'presspermit-pro');
        foreach ($circle_types as $_circle_type) {
            $is_circle = isset($circles[$group_id][$_circle_type]);

            $caption = sprintf(__('This group is a %s', 'presspermit-pro'), $circle_captions[$_circle_type]);
            
            if (!empty($disabled_circle_types[$_circle_type])) {
                $checked = 'disabled=disabled';
            } else {
                $checked = ($is_circle) ? 'checked="checked"' : '';
            }

            if (!empty($disabled_circle_types[$_circle_type])) {
                $capability = $disabled_circle_types[$_circle_type];
                $circle_name = $circle_captions[$_circle_type];
                $learn_more = '<a href="https://publishpress.com/knowledge-base/circles-visibility/">Learn more</a>';
                $tooltip = sprintf(
                    __('Circles cannot be enforced on roles with %s capabilities.', 'presspermit-pro'),
                    '<strong>' . esc_html($capability) . '</strong>'
                );
                $info_icon = sprintf(
                    '<span data-toggle="tooltip" data-placement="top" class="click"><i class="dashicons dashicons-info-outline"></i><span class="tooltip-text"><span>%s %s</span><i></i></span></span>',
                    wp_kses_post($tooltip),
                    wp_kses_post($learn_more)
                );
                $message = sprintf(
                    esc_html__('%s%s restrictions%s do not apply to this role. %s', 'presspermit-pro'),
                    '<a href="https://publishpress.com/knowledge-base/circles-visibility/">',
                    esc_html($circle_name),
                    '</a>',
                    $info_icon
                );
                printf(
                    '<div class="info-banner"><i class="fas fa-info-circle"></i><p>%s</p></div>',
                    wp_kses_post($message)
                );
                continue;
            }

            $hide = ($is_circle) ? '' : 'display:none';

            ?>
            <div class="toggle-group">
                <div class="toggle-item">
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_<?php echo esc_attr($_circle_type); ?>_circle" value="1" id="is_<?php echo esc_attr($_circle_type); ?>_circle" class="is_circle" <?php echo esc_attr($checked); ?> />
                        <span class="slider"></span>
                    </label>
                    <?php
                    printf(
                        '<span data-toggle="tooltip" data-placement="top">%s<span class="tooltip-text"><span>%s</span><i></i></span><i class="dashicons dashicons-info-outline" style="font-size: 18px;width: 16px;height: 16px;margin-left: 3px;"></i></span>',
                        esc_html($caption),
                        esc_html($tooltips[$_circle_type]) . ' ' . esc_html($mandatory_text)
                    ); ?>
                </div>

                <div class="post-type-selector" style="<?php echo esc_attr($hide); ?>">
                    <h3><?php esc_html_e('Apply limitation for post types:', 'presspermit-pro'); ?></h3>
                    <?php
                    $ordered_types = [];
                    foreach (array_keys($all_types) as $post_type) {
                        $ordered_types[$post_type] = $all_types[$post_type]->labels->singular_name;
                    }
                    asort($ordered_types);

                    ?>
                    <div class="checkbox-group">
                        <label class="checkbox-item">
                            <input type="checkbox" class="ppc-check-all-master"/>
                            <span class="checkmark"></span>
                            <?php esc_html_e('(toggle all)', 'presspermit'); ?>
                        </label>
                        <?php
                        foreach (array_keys($ordered_types) as $post_type) {
                            $checked = (isset($circles[$group_id][$_circle_type][$post_type]) || !$is_circle)
                                ? 'checked="checked"'
                                : '';

                            $type_caption = $all_types[$post_type]->labels->singular_name;
                            ?>
                            <label class="checkbox-item">
                                <input type="checkbox"
                                    name="<?php echo esc_attr($_circle_type); ?>_circle_post_types[]"
                                    value="<?php echo esc_attr($post_type); ?>"
                                    id="<?php echo esc_attr($_circle_type); ?>_circle_post_type_<?php echo esc_attr($post_type); ?>"
                                    class="ppc-check-item"
                                    <?php echo esc_attr($checked); ?>
                                />
                                <span class="checkmark"></span>
                                <?php echo esc_html($type_caption); ?>
                            </label>
                        <?php
                        }
                        ?>
                    </div>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Find all checkbox groups on the page
                        var groups = document.querySelectorAll('.checkbox-group');
                        groups.forEach(function(group) {
                            var master = group.querySelector('.ppc-check-all-master');
                            var items = group.querySelectorAll('.ppc-check-item');

                            if (master) {
                                // Master checkbox toggles all items
                                master.addEventListener('change', function() {
                                    items.forEach(function(item) {
                                        item.checked = master.checked;
                                    });
                                });

                                // If all are checked, check master; if any unchecked, uncheck master
                                items.forEach(function(item) {
                                    item.addEventListener('change', function() {
                                        master.checked = Array.from(items).every(function(i) { return i.checked; });
                                    });
                                });

                                // Set initial state of master checkbox
                                master.checked = Array.from(items).every(function(i) { return i.checked; });
                            }
                        });
                    });
                    </script>
                </div>
            </div>
            <?php
        }

        if ($pp->getOption('display_hints')) {
            echo '<div class="pp-current-roles-note pp-hint pp-no-hide" style="margin-top:5px;margin-left:0">';
            if (empty($metagroup)) {
                printf(
                    '<div class="info-banner banner-secondary"><i class="fas fa-info-circle"></i><p>%s</p></div>',
                    sprintf(
                        esc_html__('Users may be excused from Circle restrictions by adding the %1$s or %2$s capability to their role.', 'presspermit-pro'),
                        '<strong>pp_exempt_read_circle</strong>',
                        '<strong>pp_exempt_edit_circle</strong>'
                    )
                );
            }
            echo '</div>';
        }

        echo '</div>';
    }
}
