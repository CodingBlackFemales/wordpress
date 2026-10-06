<?php
namespace PublishPress\Statuses\StatusControl;

/**
 * Attributes
 *
 * @package PressPermit
 * @author Kevin Behrens
 * @copyright Copyright (c) 2026 PublishPress
 *
 * Custom status capabilities are implemented by defining 'post_status' as an Attribute.
 * 
 * For any attribute, the generic equivalent of "status" is "condition"
 * 
 * This class also provides for retrieval of 'force_visibility' conditions, 
 * which are stored in the conditions DB table
 * 
 */
class Attributes extends \PublishPress\StatusCapabilities
{
    var $pattern_role_cond_caps = [];
    var $do_status_cap_map;

    function __construct()
    {
        add_filter('presspermit_pattern_role_caps', [$this, 'fltLogPatternRoleCondCaps']);
        add_filter('presspermit_exclude_arbitrary_caps', [$this, 'fltExcludeArbitraryCaps']);
        add_filter('presspermit_get_typecast_caps', [$this, 'fltGetTypecastCaps'], 10, 3);
        add_filter('presspermit_administrator_caps', [$this, 'fltAdministratorCaps']);
        add_filter('presspermit_base_cap_replacements', [$this, 'fltBaseCapReplacements'], 10, 3);

        parent::__construct();

        // selectively enabled by PostFilters::getPostsWhere()
        $this->do_status_cap_map = false;
    }

    function fltBaseCapReplacements($replace_caps, $reqd_caps, $object_type)
    {
        static $busy;

        if (!empty($busy) || !function_exists('presspermit')) {
            return $replace_caps;
        }

        $busy = true;

        if (isset($this->all_privacy_caps[$object_type]) && is_array($this->all_privacy_caps[$object_type])) {
            if ($cond_caps = array_intersect_key(
                $this->all_privacy_caps[$object_type], 
                array_fill_keys($reqd_caps, true)
            )) {
                if (!defined('PP_CUSTOM_PRIVACY_STRICT_CAPS')) {
                    // note: for author's editing access to their own post, private and custom private post_status caps 
                    // will be removed, but _published caps will remain
                    $replace_caps = array_merge($cond_caps, $replace_caps);
                }
            }
        }

        foreach($this->condition_cap_map as $type_cap => $cond_caps) {
            if (!empty($cond_caps['post_status'])) {
                if ($base_caps = \PublishPress\Permissions\PostFilters::getBaseCaps([$type_cap], $object_type)) {
                    if (array_diff([$type_cap], $base_caps)) {
                        $replace_caps = array_merge($replace_caps, array_fill_keys($cond_caps['post_status'], reset($base_caps)));
                    }
                }
            }
        }

        $busy = false;

        return $replace_caps;
    }

    function fltAdministratorCaps($caps)
    {
        return array_merge($caps, array_fill_keys(array_keys(\PP_Statuses_Functions::flatten($this->all_custom_condition_caps)), true));
    }

    function fltGetTypecastCaps($caps, $arr_name, $type_obj)
    {
        $base_role_name = $arr_name[0];
        $source_name = $arr_name[1];
        $object_type = $arr_name[2];
        $attribute = (!empty($arr_name[3])) ? $arr_name[3] : '';
        $condition = (!empty($arr_name[4])) ? $arr_name[4] : '';

        // If the typecast role assignment is for a specific condition (i.e. custom post_status), only add caps for that condition
        if ($attribute) {
            // -------- exclude "published_" and "private_" caps from typecasting except for custom private post status -----------

            // NOTE: post_status casting involves both read and edit caps (whichever are in pattern rolecaps).
            if ('post_status' == $attribute) {
                $status_obj = get_post_status_object($condition);

                if (!$status_obj || (('private' != $condition) && !\PublishPress\StatusCapabilities::postStatusHasCustomCaps($condition))) {
                    return [];
                }

                // Due to complications with the publishing metabox and WP edit_post() handler, 'publish' and 'private' 
                // caps need to be included with custom privacy caps. However, it's not necessary to include the deletion 
                // caps. Withhold them unless an Editor role is assigned for standard statuses, unless constant is defined.
                if (!defined('PP_LEGACY_STATUS_CAPS')) {
                    $caps = array_diff_key(
                        $caps, 
                        array_fill_keys(['delete_published_posts', 'delete_private_posts', 'delete_others_posts'], true)
                    );
                }

                if (empty($status_obj) || !$status_obj->private) {
                    $caps = array_diff_key(
                        $caps, 
                        array_fill_keys([
                            'edit_published_posts', 
                            'publish_posts', 
                            'read_private_posts', 
                            'edit_private_posts', 
                            'delete_published_posts', 
                            'delete_private_posts'
                        ], true)
                    );
                }

                if ($status_obj->private && PPS::customPrivacyEditCapsEnabled() && !defined('PP_LEGACY_STATUS_EDIT_CAPS')) {
                    $caps = array_diff_key($caps, array_fill_keys(['edit_published_posts'], true));
                }
            }
            // ---------------------------------------------------------------------------------------------------------

            $match_caps = $caps;
            if (isset($caps[PRESSPERMIT_READ_PUBLIC_CAP]))
                $match_caps['read_post'] = 'read_post';

            if (isset($caps['edit_posts']))
                $match_caps['edit_post'] = 'edit_post';

            if (isset($caps['delete_posts']))
                $match_caps['delete_post'] = 'delete_post';

            $caps = $this->getConditionCaps($match_caps, $object_type, $attribute, $condition, ['merge_caps' => $caps]);

            if ('post_status' == $attribute) {
                if (!function_exists('presspermit') || !presspermit()->getOption('pattern_roles_include_generic_rolecaps')) {
                    unset ($caps['set_posts_status']);
                }
            }

        } elseif ('term_taxonomy' != $source_name) {
            $plural_name = (isset($type_obj->plural_name)) ? $type_obj->plural_name : $object_type . 's';

            // also cast all condition caps which are in the pattern role
            if (!empty($this->pattern_role_cond_caps[$base_role_name])) {
                foreach (array_keys($this->pattern_role_cond_caps[$base_role_name]) as $cap_name) {
                    $cast_cap_name = str_replace('_posts', "_{$plural_name}", $cap_name);
                    $caps[$cast_cap_name] = $cast_cap_name;
                }
            }
        }

        return $caps;
    }

    function fltLogPatternRoleCondCaps($pattern_role_caps)
    {
        if (function_exists('presspermit') && presspermit()->getOption('pattern_roles_include_custom_status_rolecaps')) {
            foreach (array_keys($pattern_role_caps) as $role_name) {
                // log condition caps for the "post" type
                if (isset($this->all_custom_condition_caps['post'])) {
                    $this->pattern_role_cond_caps[$role_name] = array_intersect_key(
                        $pattern_role_caps[$role_name], $this->all_custom_condition_caps['post']
                    );
                } else {
                    $this->pattern_role_cond_caps[$role_name] = [];
                }
            }
        }

        return $pattern_role_caps;
    }

    // prevent condition caps from being included when a pattern role is assigned without any condition specification
    function fltExcludeArbitraryCaps($exclude_caps)
    {
        return array_merge($exclude_caps, \PP_Statuses_Functions::flatten($this->all_custom_condition_caps));
    }

    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
   /*
    * returns array [item_id][condition] = true or (if return_array=true) [ 'inherited_from' => $row->inherited_from ]      // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    * source_name = item source name (i.e. 'post') 
    */
    function getItemCondition($source_name, $attribute, $args = [])
    {
        require_once(PP_STATUS_CONTROL_CLASSPATH . '/AttributesAdmin.php');

        return apply_filters(
            'presspermit_getItemCondition', 
            AttributesAdmin::getItemCondition(
                $source_name, 
                $attribute, 
                $args
            ), 
            $source_name, 
            $attribute, 
            $args
        );
    }
}
