<?php
namespace PublishPress\Permissions;

class FileAccess {
    private static $instance = null;
    public $doing_rest = false; 

    public static function instance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new FileAccess();
        }

        return self::$instance;
    }

    private function __construct()
    {
        
    }

    public static function expireFileRules()
    {
        update_option('presspermit_file_rules_expired', true);
    }

    public static function flushFileRules($args = [])
    {
        static $done;
        if (!empty($done)) {
            return false;
        }
        $done = true;

        if (get_transient('presspermit_flushing_file_rules'))
            return false;

        set_transient('presspermit_flushing_file_rules', true, 60);
        register_shutdown_function([__CLASS__, 'clearFlushingTransient']);

        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRules.php');
        FileAccess\RewriteRules::updateSiteFileRules($args);

        delete_transient('presspermit_flushing_file_rules');

        return true;
    }

    public static function flushAllFileRules($args=[])
    {
        if (is_multisite() && (PWP::isNetworkActivated() || FileAccess\Network::networkActivating())) {
            require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RuleFlush.php');
            FileAccess\RuleFlush::flushAllFileRules($args);
        } else {
            FileAccess::flushFileRules(['regenerate_keys' => true]);

            if (!empty($args['echo'])) {
                esc_html_e("File attachment access keys and rewrite rules were regenerated for this site.", 'presspermit');
            }
        }
    }

    public static function clearAllFileRules()
    {
        delete_option('presspermit_file_rules_expired');

        // this is important, so if a flush is in progress, schedule a shutdown function to wait it out
        if (get_transient('presspermit_flushing_file_rules')) {
            register_shutdown_function([__CLASS__, 'ruleClearingQueue']);
        } else {
            self::doClearAllFileRules();
        }
    }

    public static function clearFlushingTransient()
    {
        delete_transient('presspermit_flushing_file_rules');
    }

    public static function ruleClearingQueue()
    {
        for ($i = 0; $i < 240; $i++) { // this is important, so let the shutdown operation wait up to 60 seconds
            if (!get_transient('presspermit_flushing_file_rules')) {
                $ok = true;
                break;
            }

            usleep(250000); // 250 milliseconds
        }

        if (!empty($ok))
            self::doClearAllFileRules();
        else {
            $log = get_option('presspermit_deactivation_log');
            if (!$log)
                $log = [];

            $log[] = current_time('mysql', 1) . ' : file_rule_flush_failed';
            update_option('presspermit_file_rules', $log);
        }
    }

    private static function doClearAllFileRules()
    {
        if (is_multisite() && PWP::isNetworkActivated()) {
            require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RuleFlush.php');
            FileAccess\RuleFlush::clearAllFileRules();
        } else {
            require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRules.php');
            FileAccess\RewriteRules::updateSiteFileRules(['include_pp_rules' => false]);
        }
    }

    public static function getUploadInfo()
    {
        // prevent determination and creation of date-based upload subfolders
        add_filter('option_uploads_use_yearmonth_folders', [__CLASS__, 'fltReturnFalse'], 99);
        
        $upload_info = wp_upload_dir();
        remove_filter('option_uploads_use_yearmonth_folders', [__CLASS__, 'fltReturnFalse'], 99);

        return $upload_info;
    }

    public static function fltReturnFalse($a)
    {
        return false;
    }
}
