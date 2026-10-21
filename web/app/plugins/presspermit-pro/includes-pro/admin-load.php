<?php

namespace PublishPress\Permissions;

use PublishPress\Permissions\UI\Settings as Settings;

class AdminLoadPro
{
    function __construct()
    {
        add_filter('presspermit_default_options', [$this, 'defaultOptions']);
        add_filter('presspermit_option_captions', [$this, 'optionCaptions'], 20);
        add_filter('presspermit_option_sections', [$this, 'optionSections'], 20);

        add_filter('presspermit_admin_get_string', [$this, 'fltAdminGetStr'], 10, 2);
        add_filter('presspermit_admin_echo_string', [$this, 'fltAdminEchoStr'], 10, 2);
        add_filter('presspermit_get_constant_descript', [$this, 'fltGetConstantDescription'], 10, 2);

        add_action('presspermit_load_modules', [$this, 'loadModules']);

        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_style('publishpress-statuses-admin', plugins_url('', PRESSPERMIT_PRO_FILE) . '/includes-pro/admin.css', [], PRESSPERMIT_PRO_VERSION);
        });

        if (is_admin() && !defined('PUBLISHPRESS_STATUSES_VERSION')) {
            require_once PRESSPERMIT_PRO_ABSPATH . '/includes-pro/statuses-intro.php';
        }

        // Define PRO module marker constants before core loads
        // FREE modules check for these and skip loading if present
        if (!defined('PRESSPERMIT_TEASER_PRO_FILE')) {
            define('PRESSPERMIT_TEASER_PRO_FILE', __DIR__ . '/modules/presspermit-teaser/presspermit-teaser.php');
        }
    }

    public function defaultOptions($options)
    {
        return $options;
    }

    public function optionCaptions($captions)
    {
        return $captions;
    }

    public function optionSections($sections)
    {
        $new = [
            'admin' => [],
        ];

        $key = 'core';

        if (isset($sections[$key]['admin'])) {
            $sections[$key]['admin'] = array_merge($sections[$key]['admin'], $new['admin']);
        } else {
            $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;
        }

        return $sections;
    }


    public function loadModules($args)
    {
        $defaults = ['available_modules' => [], 'inactive_modules' => []];
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $dir = PRESSPERMIT_PRO_ABSPATH . '/modules/';
            // Define PRO module marker constants before core loads
            // FREE modules check for these and skip loading if present
            if (!defined('PRESSPERMIT_TEASER_PRO_FILE')) {
                define('PRESSPERMIT_TEASER_PRO_FILE', __DIR__ . '/modules/presspermit-teaser/presspermit-teaser.php');
            }

        foreach ($available_modules as $module) {
            if (empty($inactive_modules[$module]) && file_exists("$dir/$module/$module.php")) {
                include_once("$dir/$module/$module.php");
            }
        }
    }

    public function fltAdminEchoStr($echo_done, $string_id)
    {
        $echo_done = true;

        switch ($string_id) {

                // Statuses
            case 'custom_privacy_edit_caps':
                $caption = __('Should pages with privacy status "premium" require set_pages_premium and edit_premium_pages capabilities? If so, you can %1$sassign a status-specific Page Editor role%2$s or %3$sadd the capabilities directly to a role%4$s.', 'presspermit-pro');

                if (defined('PUBLISHPRESS_CAPS_VERSION')) {
                    $url = admin_url('admin.php?page=pp-capabilities');

                    printf(
                        esc_html($caption),
                        "<a href='" . esc_url(admin_url('?page=presspermit-groups')) . "'>",
                        '</a>',
                        '<a href="' . esc_url($url) . '">',
                        '</a>'
                    );
                } else {
                    $url = Settings::pluginInfoURL('capability-manager-enhanced');

                    printf(
                        esc_html($caption),
                        "<a href='" . esc_url(admin_url('?page=presspermit-groups')) . "'>",
                        '</a>',
                        '<span class="plugins update-message"><a href="' . esc_url($url) . '" class="thickbox" title=" PublishPress Capabilities">',
                        '</a></span>'
                    );
                }

                break;

                // note: Not used when PublishPress Statuses is active
            case 'moderation_statuses_default_by_sequence':
                printf(esc_html__('Note: Workflow sequence and branching for pre-publication is configured %son a separate screen%s', 'presspermit-pro'), '<a href="' . esc_url(admin_url('admin.php?page=presspermit-statuses&attrib_type=moderation')) . '">', '</a>');
                break;

            default:
                $echo_done = false;
        }

        return $echo_done;
    }

    public function fltAdminGetStr($display_string, $string_id)
    {
        switch ($string_id) {

            case 'module_settings_tagline':
                return __('Additional settings provided by the %s module.', 'presspermit-pro');

                // Install
            case 'key-deactivation':
                return __('Note: If you deactive, re-entry of the license key will be required for re-activation.', 'presspermit-pro');


                // Compat
            case 'netwide_groups':
                return __('If enabled, custom group membership is applied network-wide (though role assignments are still site-specific).', 'presspermit-pro');

            case 'cap_pp_create_network_groups':
                return __('Can create network-wide permission groups', 'presspermit-pro');

            case 'cap_pp_manage_network_members':
                return __('If group editing is allowed, can also modify network group membership', 'presspermit-pro');


                // File Access
            case 'file_filtering':
                return __("This feature will control direct URL access to files in /uploads/ folder. By default, files are blocked if they are attached to a post which the user can't view.", 'presspermit-pro');

            case 'ms_blogs_file_filtering_config':
                return __('File Access on multisite installations will require the following rules to be inserted above the stock ms-files.php rules in the %1$smain .htaccess file%2$s:', 'presspermit-pro');

            case 'ms_blogs_htaccess_missing':
                return __('But your .htaccess is missing or not writeable!', 'presspermit-pro');

            case 'ms_blogs_htaccess_needs_update':
                return __('.htaccess needs to be updated to include these rules.', 'presspermit-pro');

            case 'ms_blogs_htaccess_ok':
                return __('.htaccess file has all required rules.', 'presspermit-pro');

            case 'ms_blogs_rule_maint':
                return __('These rules will not be inserted automatically.  You are responsible for editing .htaccess and later removing the rules if the functionality is no longer desired.', 'presspermit-pro');

            case 'ms_blogs_rule_maint_note':
                return __('Note that an additional rule will need to be added with each new site. %1$sTo eliminate this requirement, research "WordPress remove ms-files".%2$s', 'presspermit-pro');

            case 'ms_blogs_network_activated_warning':
                return __("You will need to manually restore the .htacces file to default contents if anything goes wrong. Proceed?", 'presspermit-pro');

            case 'ms_blogs_network_update_htaccess':
                return __('Update .htaccess now', 'presspermit-pro');

            case 'ms_blogs_network_update_htaccess_if_files':
                return __('only for sites with protected files', 'presspermit-pro');

            case 'ms_blogs_network_update_htaccess_all_site':
                return __('for all sites', 'presspermit-pro');

            case 'ms_blogs_network_update_htaccess_remove_rules':
                return __('NONE: remove Permissions rules', 'presspermit-pro');

            case 'ms_blogs_not_network_activated':
                return __('Since the plugin is not network-activated, you will need to modify the .htaccess file manually, inserting a RewriteRule as shown above for each site which needs file filtering.', 'presspermit-pro');

            case 'unattached_files_private':
                return __('This extends the File Access feature to files that are not attached to any post. This will not apply to user who have the edit_private_files or pp_list_all_files capability.', 'presspermit-pro');

            case 'attached_files_private':
                return  __('Make attached files unreadable to users who do not have the edit_private_files or pp_list_all_files capability.', 'presspermit-pro');

            case 'small_thumbnails_unfiltered':
                return __('Improve Media Library performance by disabling file filtering for thumbnails (size specified in Settings > Media).', 'presspermit-pro');

            case 'file_access_apply_redirect':
                return __('On some sites, an additional redirect is required to correctly deliver protected files. Leave this disabled for better performance if possible.', 'presspermit-pro');

            case 'file_filtering_regen_multisite':
                return __('The File Access feature uses an %1$s.htaccess%2$s file located at %3$suploads/.htaccess%4$s. Normally no action is needed, but clicking the button below will update the URL keys (at next site access) that protect the files.', 'presspermit-pro');

            case 'file_filtering_regen':
                return __('The File Access feature uses an %1$s.htaccess%2$s file located at %3$suploads/.htaccess%4$s. Normally no action is needed, but clicking the button below will update the URL keys that protect the files.','presspermit-pro');

            case 'file_filtering_regen_best_practice':
                return __('Best practice is to access the above url periodically (using your own cron service) to prevent long-term bookmarking of protected files.', 'presspermit-pro');

            case 'file_filtering_regen_key_prompt':
                return __('Supply a custom key which will enable a support url to regenerate file access keys.  Then execute the url regularly (using your own cron service) to prevent long-term bookmarking of protected files.', 'presspermit-pro');

            case 'file_filtering_regen_attachment_util':
                return __('File Access can also protect files uploaded by FTP or other manual file copy, but does not detect them automatically. Click the button below to scan your uploads folder for files to protect.', 'presspermit-pro');

                // File Attachments Utility
            case 'attachments_util_invalid_regen_key':
                return __('Invalid file filtering key argument.', 'presspermit-pro');

            case 'attachments_util_no_regen_key':
                return __('Please configure File Access options!', 'presspermit-pro');

            case 'attachments_util_wp_tree':
                return __('The wp-content folder cannot be relocated outside the main WordPress folder.', 'presspermit-pro');

            case 'attachments_util_www':
                return __('Note that to be detected as attachments, your file links must include www.');

            case 'attachments_util_no_www':
                return __('Note that to be detected as attachments, your file links must NOT include www.');

            case 'attachments_util_search_replace':
                return __('Files linked in post content must be in %1$s (or a subfolder of it). If you move files, consider using a %2$s search and replace plugin%3$s to update those URLs.', 'presspermit-pro');

            case 'attachments_util_postmeta_link':
                return __('File Access can also protect files uploaded by FTP or other manual processes, but does not detect them automatically. This utility can find and protect any uploaded files.', 'presspermit-pro');

            case 'attachments_util_cron_task':
                return __('To run this utility by cron task or other direct request, use the following URL:', 'presspermit-pro');

            case 'attachments_util_cron_task_need_regen_key':
                return __('To run this utility by direct URL, set a file filtering regen key on Permissions Settings', 'presspermit-pro');

            case 'attachments_util_external_content_dir':
                return __('Note: Direct access to uploaded file attachments cannot be filtered because your WP_CONTENT_DIR is not in the WordPress branch.', 'presspermit-pro');

            case 'attachments_util_terminated':
                return __('The operation was terminated due to an invalid configuration.', 'presspermit-pro');

            case 'attachments_util_checking_posts_pages':
                return __("Checking %s posts / pages...", 'presspermit-pro');

            case 'attachments_util_skipping_unfilterable':
                return __('%1$s skipping unfilterable file in %2$s "%3$s":%4$s %5$s', 'presspermit-pro');

            case 'attachments_util_skipping_missing':
                return __('%1$s skipping missing file in %2$s "%3$s":%4$s %5$s', 'presspermit-pro');

            case 'attachments_util_new_attachment':
                return __('%1$sNew attachment in %2$s "%3$s":%4$s %5$s', 'presspermit-pro');

            case 'attachments_util_attachment_ok':
                return __('%1$sNew attachment in %2$s "%3$s":%4$s %5$s', 'presspermit-pro');

            case 'attachments_util_linked_uploads_found':
                return __("%s linked uploads were found in your post / page content.", 'presspermit-pro');

            case 'attachments_util_files_added':
                return __('%s attachment records were added to the database.', 'presspermit-pro');

            case 'attachments_util_already_registered':
                return __('All linked uploads are already registered as attachments.', 'presspermit-pro');


                // Statuses Admin Page
            case 'define_privacy_statuses':
                return __("Statuses enabled here are available as Visibility options for post publishing. Affected posts become inaccessable without a corresponding status-specific role assignment.", 'presspermit-pro');

            case 'define_moderation_statuses':
                return __("Statuses enabled here are available in the editor as additional steps between Draft and Published.", 'presspermit-pro');

            case 'statuses_alter_accessibility':
                return __("Statuses alter your content's accessibility by imposing additional capability requirements.", 'presspermit-pro');

            case 'statuses_enable_custom_capabilities':
                return __('Enable Custom Capabilities by toggling the link below status name. If enabled, non-Editors will need a corresponding %ssupplemental role%s to edit posts of that status.', 'presspermit-pro');

            case 'statuses_moderation_default_by_sequence':
                return __('For post edit by a user who cannot publish, %sworkflow is configured%s to make the Publish button increment the post to the next workflow status permitted.', 'presspermit-pro');

            case 'statuses_moderation_workflow_gutenberg':
                return __('For post edit by a user who cannot publish, %sworkflow is configured%s to make the Publish button escalate the post to the highest-ordered workflow status permitted.', 'presspermit-pro');

            case 'statuses_moderation_workflow_classic':
                return __('For post edit by a user who cannot publish, the Publish button will escalate the post to the highest-order status permitted to the user.', 'presspermit-pro');

            case 'need_publishpress_statuses_enabled':
                return __('Please enable the PublishPress %sStatuses feature%s.', 'presspermit-pro');

            case 'statuses_permissions_post_type_enable_note':
                return __('Note that the Post Type itself will also need to have %sPermissions%s enabled.', 'presspermit-pro');

            case 'statuses_need_collab_module':
                return __('To define moderation statuses, %1$sactivate the Editing Permissions module%2$s.', 'presspermit');


                // Statuses
            case 'posts_using_custom_privacy':
                return __('To disable custom visibility statuses, first re-assign posts to a different status.', 'presspermit-pro');

            case 'supplemental_cap_moderate_any':
                return __('Note, this only applies if the role definition includes the pp_moderate_any capability', 'presspermit-pro');

            case 'cap_pp_define_post_status':
                return __('Create or edit custom Privacy or Workflow statuses.', 'presspermit-pro');

            case 'cap_pp_define_privacy':
                return __('Create or edit custom Privacy statuses.', 'presspermit-pro');

            case 'cap_set_posts_status':
                return __('Extra Roles created as a type-specific copy of this role will enable assignment of specified custom statuses.', 'presspermit-pro');

            case 'cap_pp_moderate_any':
                return __('Editors do not need status-specific editing capabilities for workflow posts (Assigned, In Progress, Approved).', 'presspermit-pro');
            
            case 'custom_privacy_edit_caps_hint':
                return __('If enabled, non-Editors will need to be granted editing access via the "Permissions" screen.', 'presspermit-pro');
            
            case 'quick_edit_custom_privacy_dropdown_hint':
                return __('If enabled, a Visibility Status dropdown will appear in Quick Edit for easy status assignment.', 'presspermit-pro');

            case 'privacy_statuses_enabled_hint':
                return __('If enabled, this will add Visibility Statuses support to the PublishPress Status plugin.', 'presspermit-pro');

                // Sync

            case 'sync_title_sync_posts_to_users_post_field':
                return esc_attr(__('Post property or meta field to match with user field', 'presspermit-pro'));

            case 'sync_title_sync_posts_to_users_user_field':
                return esc_attr(__('User property or meta field to match with post field', 'presspermit-pro'));

            case 'sync_title_sync_posts_to_users_user_field_text':
                return esc_attr(__('User meta field to match with post field', 'presspermit-pro'));

            case 'sync_title_sync_posts_to_users_role':
                return esc_attr(__('User role to include in synchronization', 'presspermit-pro'));

            case 'sync_title_sync_posts_to_users_post_parent':
                return esc_attr(__('Parent id for created posts', 'presspermit-pro'));

            case 'sync_title_suggestions':
                return esc_attr(__('Choose post field from suggested meta key names', 'presspermit-pro'));

            case 'sync_title_main_checkbox':
                return esc_attr(__('When a new user of specified role is added, create or designate a post for them.', 'presspermit-pro'));

            case 'sync_existing_title':
                return  esc_attr(__('Create or designate a post for existing users.', 'presspermit-pro'));

            case 'sync_not_hierarchical':
                return  esc_attr(__('This post type is not hierarchical', 'presspermit-pro'));

            case 'sync_posts_to_users':
                return __('Automatically create a post for each user in selected roles.', 'presspermit-pro');

            case 'create_post_for_meta_detection':
                return __('Hint: If %s have custom fields (like email address), create one to assist field name discovery.', 'presspermit-pro');

            case 'sync_posts_to_users_apply_permissions':
                return __('Enable each user to edit their own User Posts.', 'presspermit-pro');

                // Teaser
            case 'teaser_tab':
                return sprintf(__('Settings for replacing unreadable content with teaser text, provided by the %s module.', 'presspermit-pro'), __('Teaser', 'ppe'));

            case 'teaser_settings_not_applicable':
                return __('Settings on this tab do not apply because teaser filtering is disabled for all post types.', 'presspermit-pro');

            case 'teaser_block_all_rss':
                return __('Since some browsers will cache feeds without regard to user login, block RSS content even for qualified users.', 'presspermit-pro');

            case 'display_teaser':
                return esc_html__('This feature controls what users will see when trying to view content they are blocked from accessing.', 'presspermit-pro');

            case 'teaser_coverage':
                return __('These settings adjust which views, link types and visibility statuses the teaser is applied to.', 'presspermit-pro');

                // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                /*
            case 'teaser_prefix_suffix_note' :
            return __('Note: the prefix and suffix settings below will always be applied unless the teaser mode is "no teaser".', 'presspermit-pro');
            */

            case 'nav_menu_hide_terms_caption':
                return __('You can override Teaser settings within the Nav Menu by hiding links to unreadable posts that are associated with the terms you select below.', 'presspermit-pro');

            case 'tease_direct_access_only':
                return __('Keep unreadable content hidden in the blogroll and link lists, but show the teaser on direct access attempts.', 'presspermit-pro');

            case 'hide_unreadable_private_posts':
                return  __('Hide unreadable private posts, but show a teaser for posts which are unreadable due to regular Privacy or Role Restrictions.', 'presspermit-pro');

            case 'teaser_redirect_page':
                return  __('If anyone tries to view a post they don\'t have access to, redirect to another post instead of displaying a teaser.', 'presspermit-pro');

            case 'teaser_text':
                return __('Replace post content or excerpt with custom text, possibly including an inline login form. You can also add text before or after the post title and excerpt.', 'presspermit-pro');

            case 'teaser_hide_nav_menu_types':
                return  __('Unreadable items of these comma-separated types will have nav menu item hidden.', 'presspermit-pro');

                // Circles
            case 'cap_pp_exempt_read_circle':
                return __('Visibility Circle membership does not limit viewing access.', 'presspermit-pro');

            case 'cap_pp_exempt_edit_circle':
                return __('Editorial Circle membership does not limit editing access.', 'presspermit-pro');
        }

        return $display_string;
    }

    public static function fltGetConstantDescription($description, $constant)
    {
        if (is_multisite()) {
            switch ($constant) {
                    // 'user-selection'
                case 'PP_NETWORK_GROUPS_SITE_USERS_ONLY':
                    return esc_html__("When searching for users via Permissions ajax, return return only users registered to current site", 'presspermit-pro');

                case 'PP_NETWORK_GROUPS_MAIN_SITE_ALL_USERS':
                    return esc_html__("If user is a super admin or has 'pp_manage_network_members' capability, user searches via Permissions ajax return users from all sites", 'presspermit-pro');
            }
        }

        if (class_exists('BuddyPress', false)) {
            switch ($constant) {
                    // 'buddypress'
                case 'PPBP_GROUP_MODERATORS_ONLY':
                    return esc_html__("Count users as a member of a BuddyPress Permissions Group only if they are a moderator of the BP group", 'presspermit-pro');

                case 'PPBP_GROUP_ADMINS_ONLY':
                    return esc_html__("Count users as a member of a BuddyPress Permissions Group only if they are an administrator of the BP group", 'presspermit-pro');
            }
        }

        if (defined('CMS_TPV_VERSION')) {
            switch ($constant) {
                    // 'cms-tree-page-view'
                case 'PP_CMS_TREE_NO_ADD':
                    return esc_html__("CMS Page Tree View plugin: hide 'add' links (for all hierarchical post types) based on user's association permissions", 'presspermit-pro');

                case 'PP_CMS_TREE_NO_ADD_PAGE':
                    return esc_html__("CMS Page Tree View plugin: hide 'add' links (for pages) based on user's page association permissions", 'presspermit-pro');

                case 'PP_CMS_TREE_NO_ADD_CUSTOM_POST_TYPE_NAME_HERE':
                    return esc_html__("CMS Page Tree View plugin: hide 'add' links (for specified hierarchical post type) based on user's association permissions", 'presspermit-pro');
            }
        }

        if (class_exists('NestedPages')) {
            switch ($constant) {
                case 'PP_NESTED_PAGES_DISABLE_FILTERING':
                    return esc_html__("Prevent Permission from filtering the Nested Pages listing at all", 'presspermit-pro');

                case 'PP_NESTED_PAGES_ENABLE_FILTERING':
                    return esc_html__("Force Permissions to filter the Nested Pages listing", 'presspermit-pro');

                case 'PP_NESTED_PAGES_QUICKEDIT_ROLES':
                    return esc_html__("Comma-separated list of roles that are allowed to Quick Edit nested pages", 'presspermit-pro');

                case 'PP_NESTED_PAGES_CONTEXT_MENU_ROLES':
                    return esc_html__("Comma-separated list of roles that are allowed to use the Context Menu", 'presspermit-pro');

                case 'PP_NESTED_PAGES_NO_CONTEXT_MENU_ALLOWANCE':
                    return esc_html__("Never allow unfiltered users to use the Context Menu, even if they have pp_force_quick_edit capability", 'presspermit-pro');

                case 'PRESSPERMIT_NESTED_PAGES_LIMIT_ADD_CHILD_LINK':
                    return esc_html__("Disable Add Child link unless user is unfiltered (Administrator or has pp_nested_pages_unfiltered capability)", 'presspermit-pro');

                case 'PRESSPERMIT_NESTED_PAGES_LIMIT_ADD_CHILD_PAGE':
                    return esc_html__("Disable Add Child Page link unless user is unfiltered", 'presspermit-pro');

                case 'PRESSPERMIT_NESTED_PAGES_LIMIT_INSERT_PAGE_BEFORE':
                    return esc_html__("Disable Insert Page Before link unless user is unfiltered", 'presspermit-pro');

                case 'PRESSPERMIT_NESTED_PAGES_LIMIT_INSERT_PAGE_AFTER':
                    return esc_html__("Disable Insert Page After link unless user is unfiltered", 'presspermit-pro');

                case 'PRESSPERMIT_NESTED_PAGES_LIMIT_PUSH_TO_TOP':
                    return esc_html__("Disable Push to Top link unless user is unfiltered", 'presspermit-pro');

                case 'PRESSPERMIT_NESTED_PAGES_LIMIT_PUSH_TO_BOTTOM':
                    return esc_html__("Disable Push to Bottom link unless user is unfiltered", 'presspermit-pro');

                case 'PRESSPERMIT_NESTED_PAGES_LIMIT_CLONE':
                    return esc_html__("Disable Clone link unless user is unfiltered", 'presspermit-pro');
            }
        }

        return $description;
    }
}
