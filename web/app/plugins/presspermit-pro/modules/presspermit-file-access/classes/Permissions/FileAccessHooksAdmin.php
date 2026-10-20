<?php
namespace PublishPress\Permissions;

class FileAccessHooksAdmin
{
    function __construct() {
        add_action('admin_menu', [$this, 'actMenu'], 99);
        add_action('presspermit_options_ui', [$this, 'actOptions']);

        add_filter('presspermit_exception_types', [$this, 'fltExceptionTypes']);
        add_filter('presspermit_exception_operations', [$this, 'actExceptionOperations'], 1, 3);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts_styles']);
    }

    function actMenu()
    {
        // satisfy WordPress' demand that all admin links be properly defined in menu
        if ('presspermit-attachments_utility' == presspermitPluginPage()) {
            add_submenu_page(
                presspermit()->admin()->getMenuParams('permits'), 
                esc_html__('Attachment Utility', 'presspermit'), 
                esc_html__('Attachment Utility', 'presspermit'), 
                'read', 
                'presspermit-attachments_utility', 
                [$this, 'attachmentsUtility']
            );
        }
    }

    function enqueue_scripts_styles() {
        if ('presspermit-attachments_utility' == presspermitPluginPage()) {
            $urlpath = plugins_url('presspermit-file-access', PRESSPERMIT_FILEACCESS_FILE);
            wp_enqueue_style('presspermit-file-access-utility', $urlpath . '/common/file-access.css', [], PRESSPERMIT_FILEACCESS_FILE);
        }
    }

    function attachmentsUtility()
    {
        if (!current_user_can('pp_manage_settings')) {
            wp_die(esc_html(PWP::__wp('Cheatin&#8217; uh?')));
        }

        require_once(PRESSPERMIT_CLASSPATH . '/UI/SettingsAdmin.php');
        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/UI/SettingsUtility.php');
        FileAccess\UI\SettingsUtility::display();
    }

    function actOptions()
    {
        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/UI/SettingsTabFileAccess.php');
        new FileAccess\UI\SettingsTabFileAccess();
    }

    function fltExceptionTypes($types)
    {
        if (!isset($types['attachment']))
            $types['attachment'] = get_post_type_object('attachment');
        return $types;
    }

    function actExceptionOperations($ops, $for_item_source, $for_item_type)
    {
        if (('post' == $for_item_source) && ('attachment' == $for_item_type) && !isset($ops['read'])) {
            $op_obj = presspermit()->admin()->getOperationObject('read', $for_item_type);
            $ops['read'] = $op_obj->label;
        }

        return $ops;
    }
}
