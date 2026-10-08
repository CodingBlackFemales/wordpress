<?php
namespace PublishPress\Statuses\StatusControl;

class Workflow {
    // This is only called if Status Control is using built-in (Approved, Member, Staff, Premium) statuses without PublishPress Statuses or PublishPress Planner
    public static function getUserStatusPermissions($perm_name, $post_type, $check_statuses, $args = [])
    {
        global $wp_post_statuses;

        $defaults = ['reset' => false, 'force_refresh' => false];
        $args = array_merge($defaults, (array)$args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $user = publishpress_status_control()->getUser();

        $return = [];

        $check_statuses = (array)$check_statuses;

        $elem = reset($check_statuses);
        if (is_object($elem)) {
            $check_statuses = array_fill_keys(array_keys($check_statuses), true);
        } else {
            $check_statuses = array_fill_keys((array)$check_statuses, true);
        }

        $refresh_statuses = [];

        if (!isset($user->cfg[$perm_name])) {
            $user->cfg[$perm_name] = [];
        }

        if (!isset($user->cfg[$perm_name][$post_type]) || $reset) {
            $user->cfg[$perm_name][$post_type] = [];
        }

        if ($force_refresh) {
            $refresh_statuses = $check_statuses;

        } elseif (!$refresh_statuses = array_diff_key($check_statuses, $user->cfg[$perm_name][$post_type])) {
            // requested values already cached
            return $user->cfg[$perm_name][$post_type];
        }

        if (\PublishPress_Statuses::isContentAdministrator()) {
            // This function will probably not get called for Administrator, but return valid response if it is.
            $user->cfg[$perm_name][$post_type] = array_merge($user->cfg[$perm_name][$post_type], $check_statuses);
            return $user->cfg[$perm_name][$post_type];
        }

        switch ($perm_name) {
            case 'set_status':
                if (!$type_obj = get_post_type_object($post_type)) {
                    return [];
                }

                if (!isset($type_obj->cap->set_posts_status)) {
                    $type_obj->cap->set_posts_status = $type_obj->cap->publish_posts;
                }

                $attributes = PPS::attributes();

                $moderate_any = PPS::havePermission('moderate_any');

                foreach (array_keys($check_statuses) as $_status) {
                    if ($moderate_any && !empty($wp_post_statuses[$_status]) 
                    && !empty($wp_post_statuses[$_status]->moderation)
                    && empty($wp_post_statuses[$_status]->public)
                    && empty($wp_post_statuses[$_status]->private)
                    ) {
                        // The pp_moderate_any capability allows a non-Administrator to set any moderation status
                        $return[$_status] = true;
                        continue;
                    }

                    if ('draft' == $_status) {
                        $return['draft'] = true;
                        continue;
                    }

                    if (in_array(['public', 'private', 'future'])) {
                        $check_caps = [$type_obj->cap->publish_posts];
                    } else {
                        $check_caps = $attributes->getConditionCaps(
                            $type_obj->cap->set_posts_status, 
                            $post_type, 
                            'post_status', 
                            $_status
                        );

                        // @todo: confirm this implementation, requiring both basic status change cap and type-specific cap
                        if (empty($wp_post_statuses[$_status]->public) && empty($wp_post_statuses[$_status]->private)
                        && !in_array($_status, ['draft', 'future'])
                        ) {
                            $check_caps []= str_replace('-', '_', "status_change_{$_status}");
                        }
                    }

                    $return[$_status] = !array_diff($check_caps, array_keys(array_filter($user->allcaps)));
                }

                // Append cache for the statuses that were checked
                $user->cfg['set_status'][$post_type] = array_merge($user->cfg['set_status'][$post_type], $return);

                break;
            default:
        } // end switch

        return $return;
    }
}
