<?php
namespace PublishPress\Permissions;

class MembershipHooksAdmin
{
    function __construct() 
    {
        if (in_array(presspermitPluginPage(), ['presspermit-edit-permissions', 'presspermit-group-new'], true)) {
            require_once(PRESSPERMIT_MEMBERSHIP_CLASSPATH . '/UI/GroupEdit.php');
            new Membership\UI\GroupEdit();
        }

        add_filter('presspermit_custom_agent_update', [$this, 'fltCustomAgentUpdate'], 10, 4);
    }

    function fltCustomAgentUpdate($custom_handled, $agent_type, $group_id, $selected)
    {
        if (in_array($agent_type, ['pp_group', 'pp_net_group'], true)) {
            require_once(PRESSPERMIT_MEMBERSHIP_CLASSPATH . '/DB/GroupUpdate.php');
            Membership\DB\GroupUpdate::updateGroup($agent_type, $group_id, $selected);
            return true;
        }

        return $custom_handled;
    }
}
