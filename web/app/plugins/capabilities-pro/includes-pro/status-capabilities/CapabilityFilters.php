<?php
namespace PublishPress\StatusCapabilities;

/**
 * Additional metacap mapping for PP-defined conditions
 *
 * @package PressPermit
 * @author Kevin Behrens
 * @copyright Copyright (c) PublishPress
 *
 */

class CapabilityFilters
{
    var $do_status_cap_map = true;
    private $meta_caps_always_status_mapped = [];
    private $orig_status_caps = [];
    private $status_mapped_edit_others_cap = [];

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new CapabilityFilters();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->meta_caps_always_status_mapped = apply_filters('publishpress_status_caps_status_mapped_meta_caps', ['copy_post']);

        // register early so other filters have a chance to review status/condition caps we append
        add_filter('map_meta_cap', [$this, 'fltMapStatusCaps'], 2, 4);

        // Disable list_posts cap replacement when map_meta_cap was called by Permissions PostFilters::mapMetaCap(), because the original 
        // edit_others status cap needs to be passed through for crediting of base edit status cap even if user does not have edit_others status cap.
        // @todo: resolve within Permissions code
        add_filter('presspermit_map_status_caps', [$this, 'fltLogOrigStatusCaps'], 5, 4);
        add_filter('presspermit_map_status_caps', [$this, 'fltMaybeRestoreStatusCaps'], 15, 4);

        add_filter('revisionary_allow_edit_others_revision', [$this, 'fltAllowEditOthersRevision'], 10, 2);
    }

    function fltLogOrigStatusCaps($caps, $meta_cap, $user_id, $post_id)
    {
        global $current_user, $typenow;

        // Presence of meta_cap_post property indicates this filter application is downstream from Permissions plugin's PostFilters::mapMetaCap()
        if (function_exists('presspermit') && !empty(\presspermit()->meta_cap_post) && empty(\presspermit()->doing_cap_check)) {
            if (\presspermit()->meta_cap_post->ID == $post_id) {
                if ($type_obj = get_post_type_object(\PP_Statuses_Functions::getPostType())) {
                    $orig_base_caps = [];

                    foreach ($caps as $_cap) {
                        $orig_base_caps []= str_replace('edit_others_', 'edit_', $_cap);
                    }

                    // Don't replace edit_others_{status}_posts with list_others_posts if Posts listing filter will use it as a basis for requiring an edit_{status}_posts capability which the user has.  
                    if (!array_diff($orig_base_caps, array_keys(array_filter($current_user->allcaps)))) {
                        $cap_key = "{$meta_cap}:{$post_id}";

                        $this->orig_status_caps[$cap_key] = $caps;
                    }
                }
            }
        }

        return $caps;
    }

    function fltMaybeRestoreStatusCaps($caps, $meta_cap, $user_id, $post_id)
    {
        if (!empty($this->orig_status_caps)) {
            $cap_key = "{$meta_cap}:{$post_id}";

            if (!empty($this->orig_status_caps[$cap_key])) {
                $caps = $this->orig_status_caps[$cap_key];

                unset($this->orig_status_caps[$cap_key]);
            }
        }

        return $caps;
    }

    function fltMapStatusCaps($caps, $meta_cap, $user_id, $wp_args, $args = [])
    {
        global $current_user;

        static $busy;

        if (!empty($busy)) {
            return $caps;
        }

        $busy = true;

        $is_administrator = (function_exists('presspermit')) ? \presspermit()->isContentAdministrator() : is_super_admin();

        $busy = false;

        // PostFilters::generate_where_clause selectively enables this (rather than repeatedly adding/removing filter)
        if (empty($args['force']) && !in_array($meta_cap, $this->meta_caps_always_status_mapped) && (!$this->do_status_cap_map || (($user_id == $current_user->ID) && $is_administrator))) {
            return $caps;
        }

        $attributes = \PublishPress\StatusCapabilities::instance();

        $meta_cap = str_replace('_page', '_post', $meta_cap);

        if (isset($args['post'])) {
            $post = $args['post'];
        } else {
            if (empty($wp_args[0]))
                return $caps;

            if (!$post = get_post($wp_args[0]))
                return $caps;
        }

        if (('public' == $post->post_status) || ('private' == $post->post_status) && !in_array($meta_cap, $this->meta_caps_always_status_mapped)) {
            return $caps;
        }

        if (defined('PUBLISHPRESS_REVISIONS_VERSION') && in_array($post->post_status, ['draft', 'pending']) && function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post->ID)) {
            return $caps;
        }

        $busy = true;

        // @todo: collapse condition_metacap_map and condition_cap_map arrays to post_status only
        if (isset($attributes->attributes['post_status']->conditions[$post->post_status])) {
            $map_caps = (isset($attributes->condition_metacap_map[$post->post_type][$meta_cap]))
            ? $attributes->condition_metacap_map[$post->post_type][$meta_cap]['post_status']
            : [];

            if ($custom_mapped_caps = array_intersect_key($attributes->condition_cap_map, array_fill_keys($caps, true))) {
                foreach(array_keys($custom_mapped_caps) as $_mapped_cap) {
                    foreach($custom_mapped_caps[$_mapped_cap]['post_status'] as $_status => $status_cap) {
                        if (!isset($map_caps[$_status])) {
                            $map_caps[$_status] = (array) $status_cap;
                        } else {
                            $map_caps[$_status] = array_merge((array) $map_caps[$_status], (array) $status_cap);
                        }
                    }
                }
            }

            if (isset($map_caps[$post->post_status])) {
                $caps = array_merge($caps, (array)$map_caps[$post->post_status]);

                if (('edit_post' == $meta_cap) && ($post->post_author != $current_user->ID)) {
                    $this->status_mapped_edit_others_cap[$post->post_type . ':' . $post->post_status] = $map_caps[$post->post_status];
                }

                // If mapping a status-specific edit_others_{$status}_posts requirement, don't also require edit_others_posts
                foreach(['edit_others_posts', 'delete_others_posts'] as $base_cap) {
                    if (!empty($attributes->condition_cap_map[$base_cap])) {
                        if ($type_obj = get_post_type_object($post->post_type)) {
                            if (!empty($type_obj->cap->$base_cap)) {
                                $caps = array_diff($caps, [$type_obj->cap->$base_cap]);
                            }
                        }
                    }
                }

                if (!empty($wp_args[0])) {
                    $post_status = false;

                    if (is_scalar($wp_args[0])) {
                        if ($_post = get_post($wp_args[0])) {
                            $post_status = $_post->post_status;
                            $post_type = $_post->post_type;
                        }
                    } else {
                        $wp_args[0] = (object)$wp_args[0];

                        if (!empty($wp_args[0]->post_status)) {
                            $post_status = $wp_args[0]->post_status;
                            $post_type = $wp_args[0]->post_type;
                        }
                    }

                    if ($post_status) {
                        $status_obj = get_post_status_object($post_status);

                        if ($status_obj->private) {
                            if ($type_obj = get_post_type_object($post_type)) {
                                $custom_privacy_edit_caps_enabled = defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS') && PPS_CUSTOM_PRIVACY_EDIT_CAPS;

                                if (0 === strpos($meta_cap, 'read_')) {
                                    $caps = array_diff($caps, [$type_obj->cap->read_private_posts]);

                                    if (!$custom_privacy_edit_caps_enabled) {
                                        if (function_exists('presspermit')) {
                                            $user = \presspermit()->getUser();
                                        } else {
                                            $user = $current_user;
                                        }

                                        // Extend Custom Privacy Edit Caps exemption so basic Editor role for the post type
                                        // also enables reading posts with a custom privacy status.
                                        if (($user_id == $user->ID) && !empty($user->allcaps[$type_obj->cap->edit_private_posts])
                                        && !empty($user->allcaps[$type_obj->cap->edit_others_posts])
                                        ) {
                                            $caps[] = $type_obj->cap->edit_private_posts;
                                            $caps = array_diff($caps, [$map_caps[$post->post_status]]);
                                        }
                                    }
                                } elseif ($custom_privacy_edit_caps_enabled) {
                                    if (0 === strpos($meta_cap, 'edit_'))
                                        $caps = array_diff($caps, [$type_obj->cap->edit_private_posts]);
                                    elseif (0 === strpos($meta_cap, 'delete_'))
                                        $caps = array_diff($caps, [$type_obj->cap->delete_private_posts]);
                                }
                            }
                        }
                    }
                }

                if (('draft' == $post->post_status) && in_array("read_draft_{$post_type}s", $caps, true)) {
                    if ($type_obj = get_post_type_object($post_type)) {
                        $caps = array_diff($caps, [$type_obj->cap->edit_others_posts]);
                    }
                }

                $caps = apply_filters('presspermit_map_status_caps', array_unique($caps), $meta_cap, $user_id, $post->ID);
            }
        }

        $busy = false;

        return $caps;
    }

    function fltAllowEditOthersRevision($allow, $post) {
        global $current_user;

        if (
            !empty($this->status_mapped_edit_others_cap[$post->post_type . ':' . $post->post_status]) 
            && !array_diff($this->status_mapped_edit_others_cap[$post->post_type . ':' . $post->post_status], array_keys(array_filter($current_user->allcaps)))
        ) {
            $allow = true;
        }

        return $allow;
    }
}
