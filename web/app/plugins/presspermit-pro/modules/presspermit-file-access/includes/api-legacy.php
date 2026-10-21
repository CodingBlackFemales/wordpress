<?php
if (!function_exists('pp_user_can_read_file')) {
    function pp_user_can_read_file($file)
    {
        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/FileFilters.php');

        // variables passed by reference
        $return_attachment_id = 0;
        $matched_public_post = [];

        return \PublishPress\Permissions\FileAccess\FileFilters::userCanReadFile($file, $return_attachment_id, $matched_public_post);
    }
}

if (!function_exists('pp_expire_file_rules')) {
    // forces content rules to be regenerated for every site at next access (or immediately for single-site installations)
    function pp_expire_file_rules()
    {
        if (!defined('PRESSPERMIT_LIMIT_HTACCESS_LEGACY_REGEN')) {
            update_option('presspermit_file_rules_expired', true);
        }
    }
}

if (!function_exists('pp_flush_file_rules')) {
    function pp_flush_file_rules($args = [])
    {
        if (!defined('PRESSPERMIT_LIMIT_HTACCESS_LEGACYFLUSH_REGEN')) {
            require_once(PRESSPERMIT_FILEACCESS_ABSPATH . '/classes/Permissions/FileAccess.php');
            \PublishPress\Permissions\FileAccess\FileAccess::expireFileRules();
        }
    }
}
