<?php
namespace PublishPress\Permissions\Compat\BuddyPress\PermissionGroups;

require_once(PRESSPERMIT_CLASSPATH . '/UI/GroupsListTableBase.php');

// wrapper class to allow multi-purpose calling of single_row_role_column() outside groups table context 
class GroupsListTableShell extends \PublishPress\Permissions\UI\GroupsListTableBase
{
    function __construct()
    {
    }

    function prepare_items()
    {
    }

    function get_views()
    {
    }

    function get_columns()
    {
    }

    function display_rows()
    {
    }
}
