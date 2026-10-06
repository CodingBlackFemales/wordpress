<?php
namespace PublishPress\Statuses\StatusControl\UI;

class Attributes
{
    public static function attributes_ui($object_type, $type_caps, $role_name = '')
    {
        global $wp_post_statuses;

        $pp_attribs = PPS::attributes();

        // condition caps are mapped from basic meta cap, keyed by object type
        if (isset($type_caps['edit_posts']))
            $type_caps['edit_post'] = 'edit_post';

        if (isset($type_caps['delete_posts']))
            $type_caps['delete_post'] = 'delete_post';

        if (isset($type_caps['read']) || isset($type_caps[PRESSPERMIT_READ_PUBLIC_CAP]))
            $type_caps['read_post'] = 'read_post';
        //==========================

        if ($attributes = self::get_related_attributes(array_keys($type_caps), $object_type, ['return' => 'object'])) {
            echo '<div id="pp_select_custom_attribs">';

            foreach ($attributes as $attrib => $attrib_obj) {
                if (in_array($attrib, ['set_visibility', 'force_visibility', 'default_visibility'], true))  // the attributes pertain to the bulk/auto setting of visibility
                    continue;

                $organized_conditions = [];
                $attrib_caption = [];
                $attrib_caption[''] = (isset($attrib_obj->role_label)) ? sprintf(__('%s: ', 'publishpress-statuses-pro'), $attrib_obj->role_label) : sprintf(__('Custom %s: ', 'publishpress-statuses-pro'), $attrib_obj->label);

                if (!defined('PP_LEGACY_STATUS_ROLES_UI')) {
                    if (empty($type_caps['set_posts_status']) && !empty($type_caps['edit_published_posts'])) {
                        $type_obj = get_post_type_object($object_type);

                        if (!empty($type_obj->cap->set_posts_status)) {
                        	$type_caps['set_posts_status'] = $type_obj->cap->set_posts_status;
                        }
                    }
                }

                if (!$conditions = self::get_available_conditions($attrib, $type_caps, $object_type))
                    continue;

                if ('post_status' == $attrib) {
                    $attrib_caption['moderation'] = esc_html__('Main Workflow: ', 'publishpress-statuses-pro');
                    $attrib_caption['private'] = esc_html__('Visibility: ', 'publishpress-statuses-pro');
                    $attrib_caption['revision'] = esc_html__('Revision: ', 'publishpress-statuses-pro');

                    foreach ($conditions as $cond => $cond_obj) {
                        if ('private' == $cond)
                            continue;

                        if ((($cond == 'future')  // Possible todo, but this was never implemented in Permissions Pro Status Control
                        || ($cond == 'pending')) && (0 === strpos($role_name, 'submitter')))  // Pending status is currently settable with basic post caps, regardless of PP settings
                            continue;

                        if ($status_obj = get_post_status_object($cond)) {
                            if (!empty($status_obj->capability_status) && ($status_obj->capability_status != $cond)) {
                                continue;
                            }
                        }

                        $status_obj = (!empty($wp_post_statuses[$cond])) ? $wp_post_statuses[$cond] : false;
                        if ($status_obj) {
                            if ($status_obj->private)
                                $organized_conditions['private'][$cond] = $cond_obj;
                            elseif (!empty($status_obj->moderation))
                                $organized_conditions['moderation'][$cond] = $cond_obj;
                            elseif (!empty($status_obj->for_revision))
                                $organized_conditions['revision'][$cond] = $cond_obj;
                            else
                                $organized_conditions[''][$cond] = $status_obj;
                        }
                    }
                } else
                    $organized_conditions[''] = $conditions;

                foreach ($organized_conditions as $cond_class => $_conditions) {
                    echo '<div class="pp-attrib">';
                    $did_attrib_caption = false;
                    foreach ($_conditions as $cond => $cond_obj) {
                        if (!$cond)
                            continue;

                        if ($check_caps = $pp_attribs->getConditionCaps('edit_post', $object_type, $attrib, $cond)) {
                            $user = publishpress_status_control()->getUser();

                            if ($user && array_diff($check_caps, array_keys(array_filter($user->allcaps)))) {
                                continue;
                            }
                        }

                        if (!$did_attrib_caption) {
                            $display_caption = apply_filters('presspermit_status_class_caption', $attrib_caption[$cond_class], $cond_class, $cond_obj);
                            echo esc_html($display_caption) . '<br />';
                            $did_attrib_caption = true;
                        }

                        echo '<p class="pp-checkbox pp-attrib">'
                            . "<input type='checkbox' id='". esc_attr("pp_select_cond_{$attrib}:{$cond}") . "' name='pp_select_cond[]' value='" . esc_attr("{$attrib}:{$cond}") . "' /> "
                            . "<label id='" . esc_attr("lbl_pp_select_cond_{$attrib}:{$cond}") . "' for='" . esc_attr("pp_select_cond_{$attrib}:{$cond}") . "'>" . esc_html($cond_obj->label) . '</label>'
                            . '</p>';
                    }
                    
                    echo '</div>';
                }
            }

            echo '</div>';

            if (!empty($type_caps['edit_others_posts'])) {
                echo '<p class="pp-checkbox">'
                    . '<input type="checkbox" id="pp_select_cond_post_status:draft" name="pp_select_cond[]" value="post_status:draft" /> '
                    . '<label id="lbl_pp_select_cond_post_status:draft" for="pp_select_cond_post_status:draft">' . esc_html__('Draft') . '</label>'
                    . '</p>';
            }
        }
    }

    static function get_available_conditions($attrib, $pattern_role_caps, $object_type, $return = 'object')
    {
        $attributes = PPS::attributes();

        if (!isset($attributes->attributes[$attrib]->conditions))
            return [];

        // map basic caps to corresponding meta cap (@todo: some way to avoid this?)
        if (isset($pattern_role_caps['edit_posts']))
            $pattern_role_caps['edit_post'] = true;

        if (isset($pattern_role_caps['delete_posts']))
            $pattern_role_caps['delete_post'] = true;

        if (isset($pattern_role_caps['read']) || isset($pattern_role_caps[PRESSPERMIT_READ_PUBLIC_CAP]))
            $pattern_role_caps['read_post'] = true;
        //==========================

        $related_conditions = [];
        $metacap_map = (isset($attributes->condition_metacap_map[$object_type])) ? $attributes->condition_metacap_map[$object_type] : [];
        $arr = array_intersect_key(array_merge($attributes->condition_cap_map, $metacap_map), $pattern_role_caps);
        foreach (array_keys($arr) as $cap_name) {
            if (isset($arr[$cap_name][$attrib]))
                $related_conditions = array_merge($related_conditions, $arr[$cap_name][$attrib]);
        }

        if ('post_status' == $attrib) {
            $related_conditions = array_intersect_key($related_conditions, array_flip(\PP_Statuses_Functions::getPostStatuses(['post_type' => $object_type])));
        }

        $conditions = array_intersect_key($attributes->attributes[$attrib]->conditions, $related_conditions);

        foreach ($conditions as $cond => $cond_obj) {
            // if pattern role is disqualified for having 'edit_posts' but not 'edit_private_posts', etc.
            if (!empty($conditions[$cond]->pattern_role_availability_requirement)) {
                foreach ($conditions[$cond]->pattern_role_availability_requirement as $if_present => $require_also) {
                    if (in_array($if_present, array_keys($pattern_role_caps), true) && array_diff((array)$require_also, array_keys($pattern_role_caps)))
                        unset($conditions[$cond]);
                }
            }

            if (!empty($conditions[$cond]->pattern_role_unavailable_if)) {
                if (array_intersect_key($pattern_role_caps, array_flip((array)$conditions[$cond]->pattern_role_unavailable_if)))
                    unset($conditions[$cond]);
            }
        }

        return ('object' == $return) ? $conditions : array_keys($conditions);
    }

    static function get_related_attributes($reqd_caps, $object_type, $args = [])
    {
        $attributes = PPS::attributes();

        $metacap_map = (isset($attributes->condition_metacap_map[$object_type])) ? $attributes->condition_metacap_map[$object_type] : [];

        if (!$attrib_caps = array_intersect_key(array_merge($attributes->condition_cap_map, $metacap_map), array_flip((array)$reqd_caps))) {
            return [];
        }

        return array_intersect_key($attributes->attributes, \PP_Statuses_Functions::flatten($attrib_caps));
    }
} // end class
