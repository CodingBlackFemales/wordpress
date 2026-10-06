<?php
namespace PublishPress\Capabilities;

class bbPress
{
    private $bbp_roles;

    function __construct() {
        $this->bbp_roles = ['bbp_participant', 'bbp_spectator', 'bbp_moderator', 'bbp_keymaster', 'bbp_blocked'];

        add_filter('bbp_get_caps_for_role', [$this, 'bbpRoleCaps'], 5, 2);
        add_filter('pp_capabilities_editable_role', [$this, 'isEditableRole'], 10, 2);

        add_filter('presspermit_enabled_post_types', [$this, 'enabled_types']);
        add_filter('presspermit_unfiltered_post_types', [$this, 'enable_bbp_types'], 20);
    }

    function isEditableRole($is_editable, $role_name) {
        if (in_array($role_name, $this->bbp_roles)) {
            return true;
        }

        return $is_editable;
    }

    // Enforce usage of db-stored bbPress role customizations, if any. 
    // This will reinstate the last-stored CME rolecap customization even if the stored WP role is reset.
    public function bbpRoleCaps($caps, $role)
    {
        if (!in_array($role, $this->bbp_roles, true) || did_action('bbp_deactivate'))
            return $caps;
    
        if ($customized = (array)get_option('pp_customized_roles')) {
            if (isset($customized[$role]) && !empty($customized[$role]->caps)) {
                $caps = $customized[$role]->caps;
            }
        }
    
        return $caps;
    }

    public static function enabled_types($types)
    {
        if (in_array('forum', $types, true))
            $types = array_unique(array_merge($types, ['reply' => 'reply', 'topic' => 'topic', 'forum' => 'forum']));

        return $types;
    }

    function enable_bbp_types($types)
    {
        return array_diff($types, ['forum', 'topic', 'reply']);
    }
}
