<?php
namespace PublishPress\Permissions;

class MembershipHooks
{
    function __construct() 
    {
        add_action('presspermit_pre_init', [$this, 'actVersionCheck']);
        add_action('presspermit_pre_init', [$this, 'actUpdateMembershipStatus']);
    }

    function actVersionCheck()
    {
        $ver = get_option('ppm_version');

        if (!empty($ver['version'])) {
            // These maintenance operations only apply when a previous version of PPM was installed 
            if (version_compare(PRESSPERMIT_MEMBERSHIP_VERSION, $ver['version'], '!=')) {
                update_option('ppm_version', ['version' => PRESSPERMIT_MEMBERSHIP_VERSION, 'db_version' => 0]);
            }
        }
    }

    function actUpdateMembershipStatus()
    {
        $current_date = gmdate('Y-m-d');

        global $wpdb;
        $tables = ['' => $wpdb->pp_group_members];

        $pp = presspermit();

        if (is_multisite() && $pp->getOption('netwide_groups'))
            $tables['network'] = $wpdb->pp_group_members_netwide;

        foreach ($tables as $table_type => $members_table) {
            $wpdb->members_table = $members_table;

            $last_update_date = ('network' == $table_type) ? get_site_option('presspermit_membership_update_date') : $pp->getOption('membership_update_date');

            if (($last_update_date != $current_date) || !PWP::empty_REQUEST('pp_refresh_member_status')) {
                // Correct any missing scheduled statuses

                // phpcs Note: Direct query of plugin table during admin operation

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                if ($schedule_members = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT group_id, user_id FROM $wpdb->members_table WHERE date_limited = '1' AND status IN ('active', 'expired') AND start_date_gmt > %s AND end_date_gmt > %s",
                        $current_date,
                        $current_date
                    )
                )) {
                    foreach ($schedule_members AS $row) {
                        // phpcs Note: Direct query of plugin table during admin operation

                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        $wpdb->update($members_table, ['status' => 'scheduled'], ['group_id' => $row->group_id, 'user_id' => $row->user_id]);
                    }
                }

                // scheduled to active. Also corrects any invalid expirations (possibly due to manual clock changes).

                // phpcs Note: Direct query of plugin table during admin operation

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                if ($activate_members = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT group_id, user_id, end_date_gmt FROM $wpdb->members_table WHERE date_limited = '1' AND status IN ('scheduled', 'expired') AND start_date_gmt <= %s AND end_date_gmt > %s",
                        $current_date,
                        $current_date
                    )
                )) {    
                    foreach ($activate_members AS $row) {
                        // phpcs Note: Direct query of plugin table during admin operation

                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        $wpdb->update($members_table, ['status' => 'active'], ['group_id' => $row->group_id, 'user_id' => $row->user_id]);
                    }
                }

                // active to expired. Also corrects any invalid scheduled statuses.

                // phpcs Note: Direct query of plugin table during admin operation

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                if ($expire_members = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT group_id, user_id FROM $wpdb->members_table WHERE date_limited = '1' AND status IN ('active', 'scheduled') AND end_date_gmt <= %s",
                        $current_date
                    )
                )) {
                    foreach ($expire_members AS $row) {
                        // phpcs Note: Direct query of plugin table during admin operation

                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        $wpdb->update($members_table, ['status' => 'expired'], ['group_id' => $row->group_id, 'user_id' => $row->user_id]);
                    }
                }

                if ('network' == $table_type) {
                    update_site_option('presspermit_membership_update_date', $current_date);
                } else {
                    $pp->updateOption('membership_update_date', $current_date);
                }
            }
        }
    }
}
