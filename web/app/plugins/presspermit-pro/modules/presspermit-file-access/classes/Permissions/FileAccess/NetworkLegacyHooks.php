<?php
namespace PublishPress\Permissions\FileAccess;

class NetworkLegacyHooks 
{
    function __construct() {
        add_action('delete_option', [$this, 'act_maybe_rewrite_inclusions']);
        add_action('delete_transient_rewrite_rules', [$this, 'act_rewrite_inclusions']);

        if (defined('PP_FORCE_FILE_INCLUSIONS')) {
            // workaround to avoid file error on get_home_path() call
            if (file_exists(ABSPATH . '/wp-admin/includes/file.php')) {
                include_once(ABSPATH . '/wp-admin/includes/file.php');
            }
        }

        add_action('init', [$this, 'act_ms_init']);
    }

    function act_ms_init()
    {
        if (!defined('PRESSPERMIT_VERSION'))
            return;

        if (!PWP::empty_REQUEST('ppff_update_mu_htaccess') && PWP::is_REQUEST('page', 'presspermit-settings')) {
            check_admin_referer('ppff-admin', '_ppff-nonce');
            
            if (current_user_can('pp_manage_settings')) {
                require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRulesNetLegacy.php');

                $user_selection = prespermit_REQUEST_int('pp_htaccess_all_sites');

                if ('remove' === $user_selection) {
                    $include_pp_rules = false;
                    $all_sites = true;
                    delete_site_option('presspermit_last_file_rules_all_sites');
                } else {
                    $include_pp_rules = true;
                    $all_sites = $user_selection;
                    update_site_option('presspermit_last_file_rules_all_sites', $all_sites);
                }

                FileAccess\RewriteRulesNetLegacy::update_ms_htaccess($include_pp_rules, true, ['ms_all_sites' => $all_sites]);
                $_REQUEST['pp_tab'] = 'file_access';
            }
        }
    }

    function act_maybe_rewrite_inclusions($option_name = '')
    {
        if (!defined('PRESSPERMIT_VERSION'))
            return;

        if ($option_name == 'rewrite_rules')
            pp_rewrite_inclusions();
    }

    function act_rewrite_inclusions($option_name = '')
    {
        if (!defined('PRESSPERMIT_VERSION'))
            return;

        // force inclusion of required files in case flush_rules() is called from outside wp-admin, to prevent error when calling get_home_path() function
        if (file_exists(ABSPATH . '/wp-admin/includes/misc.php'))
            include_once(ABSPATH . '/wp-admin/includes/misc.php');

        if (file_exists(ABSPATH . '/wp-admin/includes/file.php'))
            include_once(ABSPATH . '/wp-admin/includes/file.php');
    }
}
