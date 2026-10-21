<?php
namespace PublishPress\Permissions\SyncPosts\UI;

class Helper
{
    public static function teamPluginNotices()
    {
        $plugins = (array)get_option('active_plugins');

        $team_plugins = [
            'adl-team', 
            'amo-team-showcase', 
            'awsm-team', 
            'divi-team-members', 
            'cherry-team-members', 
            'company-presentation', 
            'employee-directory', 
            'employee-spotlight',
            'gs-team-members', 
            'profilegrid', 
            'simple-staff-list', 
            'staffer', 
            'tc-team-members', 
            'team/team.php', 
            'tlp-team', 
            'team-free', 
            'team-manager-free.php',
            'team-manager.php', 
            'team-rosters', 
            'staff-team', 
            'total-team', 
            'wp-team-manager', 
            'wp-team-showcase-and-slider',
        ];

        $matched = false;
        foreach ($team_plugins as $team_plugin) {
            foreach ($plugins as $plugin) {
                if (false !== strpos($plugin, $team_plugin)) {
                    $matched = true;
                    break;
                }
            }
        }

        if ($matched) {
            $msg_id = 'team-plugin-integration';
            $dismissals = (array)presspermit()->getOption('dismissals');

            if (isset($dismissals[$msg_id]))
                return;

            // thanks to GravityForms for the nifty dismissal script
            $class = 'updated pp-admin-notice';
            if (presspermitPluginPage())
                $class .= ' pp-admin-notice-plugin';
            ?>
            <div class='<?php echo esc_attr($class); ?>' id='pp_dashboard_message'>
            <?php
            printf(
                esc_html__('You seem to be using a Team / Staff plugin. To synchronize posts to users and give them editing permission, go to %1$sPermissions > Settings > Sync Posts%2$s', 'presspermit-pro'), 
                '<a href="' . esc_url(admin_url("admin.php?page=presspermit-settings&pp_tab=sync_posts")) . '">', 
                '</a>'
            );
            
            ?>&nbsp; &nbsp;
                <button type="button" class="notice-dismiss"><span
                            class="screen-reader-text"><?php esc_html_e("Dismiss this notice.") ?></span></button>
            </div>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    $('button.notice-dismiss').on('click', function()
                    {
                        jQuery("#pp_dashboard_message").slideUp();
                        jQuery.post('<?php echo esc_url(admin_url(''));?>', {
                            action: "pp_dismiss_msg",
                            msg_id: "<?php echo esc_attr($msg_id) ?>",
                            cookie: encodeURIComponent(document.cookie)
                        });
                    });
                });
            </script>
            <?php
        }
    }
}
