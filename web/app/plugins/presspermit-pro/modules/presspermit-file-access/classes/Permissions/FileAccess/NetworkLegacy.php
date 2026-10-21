<?php
namespace PublishPress\Permissions\FileAccess;

class NetworkLegacy {
    function __construct() {
        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/NetworkLegacyHooks.php');
        new NetworkLegacyHooks();

        // If an htaccess regeneration is triggered by somebody else on multisite install that does blogs.dir rewriting, 
        // insert ppff rules into main .htaccess
        add_filter('mod_rewrite_rules', [__CLASS__, 'fltModRewriteRules']); 
        add_action('presspermit_activate', [__CLASS__, 'flushMainRules']);
        add_action('presspermit_deactivate', [__CLASS__, 'clearMainRules']);
    }

    public static function flushMainRules()
    {
        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRulesNetLegacy.php');
        $rules = RewriteRulesNetLegacy::update_main_rules(true);
    }
    
    public static function clearMainRules()
    {
        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRulesNetLegacy.php');
        remove_filter('mod_rewrite_rules', [__CLASS__, 'fltModRewriteRules']);
        RewriteRulesNetLegacy::update_main_rules(false);
    }

    // htaccess directive intercepts direct access to uploaded files, converts to WP call with custom args to be caught by subsequent parse_query filter
    // parse_query filter will return content only if user can read a containing post/page
    public static function fltModRewriteRules($rules)
    {
        if (!defined('PRESSPERMIT_VERSION'))
            return $rules;

        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRulesNetLegacy.php');
        return RewriteRulesNetLegacy::insert_main_rules($rules);
    }
}