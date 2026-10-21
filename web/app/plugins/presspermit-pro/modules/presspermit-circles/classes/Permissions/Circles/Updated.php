<?php
namespace PublishPress\Permissions\Circles;

class Updated
{
    public function __construct($prev_version)
    {
        // single-pass do loop to easily skip unnecessary version checks
        do {
            if (!get_option("ppperm_added_ppcc_role_caps_10beta")) {
                self::populateRoles();
            }
        } while (0); // end single-pass version check loop
    }

    public static function populateRoles($reload_user = false)
    {
        if ($role = @get_role('administrator')) {
            $role->add_cap('pp_exempt_read_circle');  // for all post types
            $role->add_cap('pp_exempt_edit_circle');  // for all post types
        }

        if ($role = @get_role('editor')) {
            $role->add_cap('pp_exempt_read_circle');
        }

        if ($role = @get_role('reviewer')) {
            $role->add_cap('pp_exempt_read_circle');
        }

        if ($role = @get_role('author')) {
            $role->add_cap('pp_exempt_read_circle');
        }

        if ($role = @get_role('contributor')) {
            $role->add_cap('pp_exempt_read_circle');
        }

        if ($role = @get_role('revisor')) {
            $role->add_cap('pp_exempt_read_circle');
        }

        update_option('ppperm_added_ppcc_role_caps_10beta', true);
    }
}
