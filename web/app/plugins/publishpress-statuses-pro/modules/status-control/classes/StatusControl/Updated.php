<?php
namespace PublishPress\Statuses\StatusControl;

class Updated
{
    public function __construct($prev_version)
    {
        // single-pass do loop to easily skip unnecessary version checks
        do {
            if (!get_option("ppperm_added_pps_role_caps_10beta"))
                self::populateRoles();

        } while (0); // end single-pass version check loop
    }

    public static function populateRoles($ver = '10beta')
    {
        // in case the role has been manually customized, don't force default caps back in
        if (get_option("ppperm_added_pps_role_caps_{$ver}"))
            return;

        switch ($ver) {
            case '10beta' :
                if ($role = @get_role('administrator')) {
                    $role->add_cap('pp_define_post_status');
                    $role->add_cap('set_posts_status');  // need this in pattern role to support mapping of set_posts_approved, etc.
                }

                if ($role = @get_role('editor')) {
                    $role->add_cap('set_posts_status');
                    $role->add_cap('pp_moderate_any');
                }

                if ($role = @get_role('author')) {
                    $role->add_cap('set_posts_status');
                }
                break;
        }

        update_option("ppperm_added_pps_role_caps_{$ver}", true);
    }
}
