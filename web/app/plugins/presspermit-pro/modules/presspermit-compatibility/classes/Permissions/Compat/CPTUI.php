<?php
namespace PublishPress\Permissions\Compat;

/**
 * Class CPTUI
 *
 * Supports restricted management of post types with Custom Post Type UI plugin
 *
 * Note: This registers the 'manage_cpt' capability using the cptui_required_capabilities filter,
 *       unless a different capability has already been registered.
 **/
class CPTUI
{
    var $cptui_capability = 'manage_cpt';

    function __construct()
    {
        add_action('init', [$this, 'set_cptui_capability']);

        // filter available taxonomies displayed on CPT edit form
        add_filter('cptui_get_taxonomies_for_post_types', [$this, 'flt_available_taxonomies'], 10, 2);

        // filter submitted CPT settings, including associated taxonomies
        add_filter('cptui_pre_save_post_type', [$this, 'flt_update_posttype'], 10, 2);
    }

    function set_cptui_capability()
    {
        // if this CPTUI filter has already been hooked, use the established capability name
        $this->cptui_capability = apply_filters('cptui_required_capabilities', 'manage_cpt');

        if (!has_filter('cptui_required_capabilities')) {
            add_filter('cptui_required_capabilities', [$this, 'cptui_required_cap']);
        }

        if ($role = @get_role('administrator')) {
            if (empty($role->capabilities[$this->cptui_capability])) {
                $role->add_cap($this->cptui_capability);
            }
        }
    }

    function cptui_required_cap($cap_name)
    {
        return ($this->cptui_capability) ? $this->cptui_capability : $cap_name;
    }

    function flt_available_taxonomies($taxonomies)
    {
        if (current_user_can('administrator')) {
            return $taxonomies;
        }

        foreach ($taxonomies as $k => $tx_obj) {
            if (current_user_can($tx_obj->cap->manage_terms) || current_user_can($tx_obj->cap->assign_terms)) {
                continue;
            }

            unset($taxonomies[$k]);
        }

        return $taxonomies;
    }

    function flt_update_posttype($cpt_data, $post_type)
    {
        if (current_user_can('administrator')) {
            return $cpt_data;
        }

        $current_data = get_option('cptui_post_types');

        if (isset($current_data[$post_type]['taxonomies'])) {
            $lock_taxonomies = array_diff(
                $current_data[$post_type]['taxonomies'], 
                $cpt_data[$post_type]['taxonomies']
            );

            foreach ($lock_taxonomies as $k => $taxonomy) {
                if ($tx_obj = get_taxonomy($taxonomy)) {
                    if (current_user_can($tx_obj->cap->manage_terms) || current_user_can($tx_obj->cap->assign_terms)) {
                        unset($lock_taxonomies[$k]);
                    }
                }
            }

            if ($lock_taxonomies) {
                $cpt_data[$post_type]['taxonomies'] = array_merge($cpt_data[$post_type]['taxonomies'], $lock_taxonomies);
            }
        }

        if ($new_taxonomies = array_diff($cpt_data[$post_type]['taxonomies'], $current_data[$post_type]['taxonomies'])) {
            $deny_taxonomies = [];
            foreach ($new_taxonomies as $k => $taxonomy) {
                if ($tx_obj = get_taxonomy($taxonomy)) {
                    if (current_user_can($tx_obj->cap->manage_terms) || current_user_can($tx_obj->cap->assign_terms)) {
                        continue;
                    }
                }

                $deny_taxonomies [] = $taxonomy;
            }

            if ($deny_taxonomies) {
                $cpt_data[$post_type]['taxonomies'] = array_diff($cpt_data[$post_type]['taxonomies'], $deny_taxonomies);
            }
        }

        return $cpt_data;
    }
}
