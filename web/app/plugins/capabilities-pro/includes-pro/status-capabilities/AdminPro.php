<?php
namespace PublishPress\StatusCapabilities;

class AdminPro {
    function __construct() {
        add_action('publishpress_statuses_list_table_init', [$this, 'actStatusesListTable']);
        add_filter('publishpress_statuses_admin_columns', [$this, 'fltStatusesColumns']);

        add_filter('publishpress_statuses_edit_status_tabs', [$this, 'fltStatusEditTabs'], 10, 2);
        add_action('publishpress_statuses_edit_status_tab_content', [$this, 'actStatusEditTabSections'], 10, 2);

        add_action('wp_ajax_pp_statuses_toggle_post_access', [$this, 'handleAjaxToggleStatusPostAccess']);

        add_action('publishpress_statuses_edit_status', [$this, 'actHandleEditStatus'], 10, 2);
    }

    function actStatusesListTable($status_type) {
        if (in_array($status_type, ['moderation', 'revision'])) {
            require_once(__DIR__ . '/StatusAdminPro.php');
            new \PublishPress\StatusCapabilities\StatusAdminPro();
        }
    }

    function fltStatusesColumns($cols) {
        if (\PublishPress\StatusCapabilities::customStatusPostMetaPermissions()) {
            if (!empty($cols['description'])) {
                $col_descript = $cols['description'];
                unset($cols['description']);
            }

            $status_type = (!empty($_REQUEST['status_type'])) ? sanitize_key($_REQUEST['status_type']) : 'moderation';  // phpcs:ignore WordPress.Security.NonceVerification.Recommended

            if (in_array($status_type, apply_filters('pp_statuses_postmeta_status_types', ['moderation', 'visibility', 'post_visibility_pp', 'revision']))) {
                $cols['enabled'] = esc_html__('Post Access', 'publishpress-statuses-pro');
            }

            if (!empty($col_descript)) {
                $cols['description'] = $col_descript;
            }
        }

        return $cols;
    }

    function actStatusEditTabSections($status, $default_tab) {
        require_once(__DIR__ . '/StatusAdminPro.php');
        new \PublishPress\StatusCapabilities\StatusAdminPro();

        do_action('presspermit_statuses_edit_status_tab', 'post_access', $status, $default_tab);
    }

    function fltStatusEditTabs($tabs, $status_name) {
        if (\PublishPress\StatusCapabilities::customStatusPostMetaPermissions()) {
            if ($status_obj = get_post_status_object($status_name)) {
                if (empty($status_obj->publish) 
                && (empty($status_obj->private) || \PublishPress\StatusCapabilities::postStatusHasCustomCaps($status_name))
                && !in_array($status_name, ['draft', 'future', 'publish', 'draft-revision'])
                && in_array($status_obj->taxonomy, apply_filters('publishpress_statuses_taxonomies', ['post_status', 'post_visibility_pp', 'post_status_core_wp_pp', 'revision']))
                ) {
                    $tabs['post_access'] = __('Post Access', 'publishpress-statuses-pro');
                }
            }
        }

        return $tabs;
    }

    public function handleAjaxToggleStatusPostAccess()
    {
        require_once(__DIR__ . '/StatusSavePro.php');
        \PublishPress\StatusCapabilities\StatusSavePro::handleAjaxToggleStatusPostAccess();
    }

    function actHandleEditStatus($status_name, $args) {
        require_once(__DIR__ . '/StatusSavePro.php');
        \PublishPress\StatusCapabilities\StatusSavePro::save($status_name);
    }
}
