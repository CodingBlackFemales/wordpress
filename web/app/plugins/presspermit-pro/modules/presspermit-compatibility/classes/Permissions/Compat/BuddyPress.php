<?php
namespace PublishPress\Permissions\Compat;

class BuddyPress
{
    function __construct() {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BuddyPress/PermissionGroups/Hooks.php');
        new BuddyPress\PermissionGroups\Hooks();

        if (is_admin()) {
            require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BuddyPress/PermissionGroups/HooksAdmin.php');
            new BuddyPress\PermissionGroups\HooksAdmin();
        }

        add_filter('bp_has_activities', [$this, 'fltHasActivities'], 10, 2);
        add_filter('bp_get_caps_for_role', [$this, 'fltRoleCaps'], 5, 2);
        add_filter('bp_replace_the_content', [$this, 'fltContent'], 50);
    }

    function fltHasActivities($bp_activities, $bp_activities_template)
    {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BuddyPress/Helper.php');
        return BuddyPress\Helper::fltHasActivities($bp_activities, $bp_activities_template);
    }

    // Enforce usage of db-stored BuddyPress role customizations, if any. 
    // This will reinstate the last-stored CME rolecap customization even if the stored WP role is reset or circumvented.
    function fltRoleCaps($caps, $role)
    {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BuddyPress/Helper.php');
        return BuddyPress\Helper::roleCaps($caps, $role);
    }


    function fltContent($content)
    {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BuddyPress/Helper.php');
        return BuddyPress\Helper::fltContent($content);
    }
}
