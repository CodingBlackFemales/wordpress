<?php
namespace PublishPress\Permissions;

class Circles
{
    public static function getCircleTypes()
    {
        return apply_filters('presspermit_circle_types', ['read', 'edit']);
    }

    public static function getGroupCircles($group_type, $group_id, $circle_type = false)
    {
        require_once(PRESSPERMIT_CIRCLES_CLASSPATH . '/DB/Groups.php');
        return \PublishPress\Permissions\Circles\DB\Groups::getGroupCircles($group_type, $group_id, $circle_type);
    }

    // (note: this limits the user scope of assigned editing role(s), does not bestow editing access)
    public static function getCircleMembers($circle_type, $user = false, $force_refresh = false)
    {
        if (!in_array($circle_type, self::getCircleTypes(), true)) {
            return [];
        }

        require_once(PRESSPERMIT_CIRCLES_CLASSPATH . '/DB/Groups.php');
        return \PublishPress\Permissions\Circles\DB\Groups::getCircleMembers($circle_type, $user, $force_refresh);
    }
}
