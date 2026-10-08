<?php
namespace PublishPress\Statuses\StatusControl\UI;

class Admin
{
    function __construct() 
    {
        // This script executes on plugin load
        //
        add_action('init', [$this, 'act_post_listing_ui'], 71);
        add_action('init', [$this, 'act_post_edit_ui'], 71);

        add_action('presspermit_exceptions_status_ui_done', [$this, 'actExceptionsStatusUi'], 8, 2);  // Ajax: UI generation

        add_action('admin_enqueue_scripts', [$this, 'act_scripts']);

        add_filter('publishpress_statuses_row-actions', [$this, 'fltStatusesRowActions'], 10, 2);
    }

    function act_scripts()
    {
        global $pagenow;

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

        if ('publishpress-statuses' == \PP_Statuses_Functions::getPluginPage()) {
            wp_enqueue_style('publishpress-statuses-permissions', PRESSPERMIT_STATUSES_URLPATH . '/common/css/statuses.css', [], PRESSPERMIT_STATUSES_VERSION);

        } elseif (in_array($pagenow, ['post.php', 'post-new.php'])) {
            wp_enqueue_style('presspermit-statuses-post-edit', PRESSPERMIT_STATUSES_URLPATH . '/common/css/post-edit.css', [], PRESSPERMIT_STATUSES_VERSION);
            wp_enqueue_style('presspermit-statuses-post-edit', PRESSPERMIT_STATUSES_URLPATH . '/common/css/post-edit-ie.css', [], PRESSPERMIT_STATUSES_VERSION);
        }

        if (!PPS::privacyStatusesDisabled()) {
            wp_enqueue_script('presspermit-statuses-misc', PRESSPERMIT_STATUSES_URLPATH . "/common/js/statuses{$suffix}.js", ['jquery'], PRESSPERMIT_STATUSES_VERSION, false);
        }
    }

    function act_post_listing_ui()
    {
        global $pagenow;

        if ('edit.php' != $pagenow) {
            return;
        }

        require_once(PP_STATUS_CONTROL_CLASSPATH . '/UI/Dashboard/PostsListing.php');
        new Dashboard\PostsListing();
    }

    function act_post_edit_ui()
    {
        global $pagenow;

        if (!in_array($pagenow, ['post.php', 'post-new.php'])) {
            return;
        }

        if (in_array(\PP_Statuses_Functions::findPostType(), ['forum', 'topic', 'reply'])) // future @todo: support bbp custom privacy as applicable
            return;

        if (\PP_Statuses_Functions::isBlockEditorActive()) {
            require_once(PP_STATUS_CONTROL_CLASSPATH . '/UI/Gutenberg/PostEdit.php');
            new Gutenberg\PostEdit();
        } else {
            require_once(PP_STATUS_CONTROL_CLASSPATH . '/UI/Dashboard/PostEdit.php');
            new Dashboard\PostEdit();
        }
    }

    function actExceptionsStatusUi($for_type, $args = [])
    {
        require_once(PP_STATUS_CONTROL_CLASSPATH . '/UI/PermissionsAjax.php');
        PermissionsAjax::actExceptionsStatusUi($for_type, $args);
    }

    function fltStatusesRowActions($actions, $status) {
        static $base_url;
        static $can_manage_cond;

        if (!empty($status->taxonomy) && (\PublishPress_Statuses::TAXONOMY_PSEUDO_STATUS == $status->taxonomy)) {
            return [];
        }

        if (!isset($can_manage_cond))
            $can_manage_cond = current_user_can('pp_define_post_status');

        $base_url = apply_filters('presspermit_conditions_base_url', 'admin.php');

        $attrib = 'post_status';
        $attrib_type = 'moderation';

        $cond_obj = get_post_status_object($status->slug);
        $cond = $status->slug;

        if (!$can_manage_cond) {
            unset($actions['edit']);
        }

        if ($cond && empty($cond_obj->_builtin)) {
            // Custom Caps now reviewed / enabled / disabled by clicking Post Access cell

        } elseif (empty($actions)) {
            $actions[''] = ['url' => '', 'label' => '&nbsp;'];  // temp workaround to prevent shrunken row
        }

        if (empty($cond_obj->_builtin) && empty($cond_obj->pp_builtin) && !empty($cond_obj->private)) { 
            $actions['delete'] = [
                'url' => wp_nonce_url($base_url . "?page=publishpress-statuses&amp;pp_action=delete&amp;attrib_type=$attrib_type&amp;status=$cond", 'bulk-conditions'),
                'label' => __('X')
            ];
        }

        $actions = apply_filters('presspermit_condition_row-actions', $actions, $attrib, $cond_obj);

        return $actions;
    }
}
