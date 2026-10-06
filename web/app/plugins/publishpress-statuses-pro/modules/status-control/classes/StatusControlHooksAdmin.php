<?php
namespace PublishPress\Statuses;

class StatusControlHooksAdmin 
{
    function __construct() {
        // This script executes on plugin load if is_admin()
        //
        define('PRESSPERMIT_STATUSES_URLPATH', plugins_url('', PRESSPERMIT_STATUSES_FILE));

        add_action('publishpress_status_capabilities_loaded', [$this, 'act_process_conditions'], 49);

        add_action('check_ajax_referer', [$this, 'act_inline_edit_status_helper']);
        add_action('check_admin_referer', [$this, 'act_bulk_edit_posts']);

        add_action('presspermit_condition_caption', [$this, 'act_condition_caption'], 10, 3);

        add_action('presspermit_permission_status_ui_done', [$this, 'act_permission_status_ui'], 10, 4);

        if (defined('DOING_AJAX') && DOING_AJAX && !defined('PP_AJAX_FINDPOSTS_STATI_OK'))
            add_action('wp_ajax_find_posts', [$this, 'ajax_find_posts'], 0);

        add_filter('acf/location/rule_values/post_status', [$this, 'acf_status_rule_options']);

        add_action('wp_loaded', [$this,'actLoadAjaxHandler'], 20);

        add_filter('presspermit_option_captions', [$this, 'optionCaptions'], 15);
        add_filter('presspermit_option_sections', [$this, 'optionSections'], 15);
        add_action('presspermit_options_ui_insertion', [$this, 'advanced_tab_permissions_options_ui'], 5, 3); // hook for UI insertion on Settings > Advanced tab

        add_filter('presspermit_cap_descriptions', [$this, 'flt_cap_descriptions'], 15);  // priority 5 for ordering between PPS and PPCC additions in caps list

        add_filter('cme_plugin_capabilities', [$this, 'fltRegisterCapabilities'], 20);

        add_filter('publishpress_status_edit_redirect_args', [$this, 'fltEditStatusRedirectArgs'], 10, 2);

        add_filter('presspermit_admin_get_string', [$this, 'fltAdminGetStr'], 20, 2);

        // Late init following status registration, including moderation property for PublishPress statuses
        add_action('init', [$this, 'actDefaultPrivacyWorkaround'], 72);

        require_once(PP_STATUS_CONTROL_CLASSPATH . '/UI/Admin.php'); // @todo: more conditional loading?
        new StatusControl\UI\Admin();
    }

    function fltRegisterCapabilities($caps) {
        if (!isset($caps['PublishPress Statuses'])) {
            $caps['PublishPress Statuses'] = [];
        }

        $caps['PublishPress Statuses'] = array_merge(
            $caps['PublishPress Statuses'], 
            ['set_posts_status', 'pp_moderate_any', 'pp_define_post_status', 'pp_define_privacy']
        );

        asort($caps['PublishPress Statuses']);

        return $caps;
    }

    function flt_cap_descriptions($pp_caps)
    {
        foreach(['pp_define_post_status', 'pp_define_privacy', 'set_posts_status', 'pp_moderate_any'] as $cap_name) {
            $pp_caps[$cap_name] = apply_filters('presspermit_admin_get_string', '', 'cap_' . $cap_name);
        }

        return $pp_caps;
    }

    public function fltAdminGetStr($display_string, $string_id)
    {
        switch ($string_id) {
                // Statuses Admin Page
            case 'define_privacy_statuses':
                return __("Statuses enabled here are available as Visibility options for post publishing. Affected posts become inaccessable without a corresponding status-specific role assignment.", 'publishpress-statuses-pro');

            case 'define_moderation_statuses':
                return __("Statuses enabled here are available in the editor as additional steps between Draft and Published.", 'publishpress-statuses-pro');

            case 'statuses_alter_accessibility':
                return __("Statuses alter your content's accessibility by imposing additional capability requirements.", 'publishpress-statuses-pro');

            case 'statuses_enable_custom_capabilities':
                return __('Enable Custom Capabilities by toggling the link below status name. If enabled, non-Editors will need a corresponding %ssupplemental role%s to edit posts of that status.', 'publishpress-statuses-pro');

            case 'statuses_moderation_default_by_sequence':
                return __('For post edit by a user who cannot publish, %sworkflow is configured%s to make the Publish button increment the post to the next workflow status permitted.', 'publishpress-statuses-pro');

            case 'statuses_moderation_workflow_gutenberg':
                return __('For post edit by a user who cannot publish, %sworkflow is configured%s to make the Publish button escalate the post to the highest-ordered workflow status permitted.', 'publishpress-statuses-pro');

            case 'statuses_moderation_workflow_classic':
                return __('For post edit by a user who cannot publish, the Publish button will escalate the post to the highest-order status permitted to the user.', 'publishpress-statuses-pro');

            case 'need_publishpress_statuses_enabled':
                return __('Please enable the PublishPress %sStatuses feature%s.', 'publishpress-statuses-pro');

            case 'statuses_permissions_post_type_enable_note':
                return __('Note that the Post Type itself will also need to have %sPermissions%s enabled.', 'publishpress-statuses-pro');

            case 'statuses_need_collab_module':
                return __('To define moderation statuses, %1$sactivate the Editing Permissions module%2$s.', 'presspermit');


                // Statuses
            case 'posts_using_custom_privacy':
                return __('To disable custom visibility statuses, first re-assign posts to a different status.', 'publishpress-statuses-pro');

            case 'supplemental_cap_moderate_any':
                return __('Note, this only applies if the role definition includes the pp_moderate_any capability', 'publishpress-statuses-pro');

            case 'cap_pp_define_post_status':
                return __('Create or edit custom Privacy or Workflow statuses.', 'publishpress-statuses-pro');

            case 'cap_pp_define_privacy':
                return __('Create or edit custom Privacy statuses.', 'publishpress-statuses-pro');

            case 'cap_set_posts_status':
                return __('Extra Roles created as a type-specific copy of this role will enable assignment of specified custom statuses.', 'publishpress-statuses-pro');

            case 'cap_pp_moderate_any':
                return __('Editors do not need status-specific editing capabilities for workflow posts (Assigned, In Progress, Approved).', 'publishpress-statuses-pro');
            
            case 'custom_privacy_edit_caps_hint':
                return __('If enabled, non-Editors will need to be granted editing access via the "Permissions" screen.', 'publishpress-statuses-pro');
            
            case 'quick_edit_custom_privacy_dropdown_hint':
                return __('If enabled, a Visibility Status dropdown will appear in Quick Edit for easy status assignment.', 'publishpress-statuses-pro');

            case 'privacy_statuses_enabled_hint':
                return __('If enabled, this will add Visibility Statuses support to the PublishPress Status plugin.', 'publishpress-statuses-pro');
        }

        return $display_string;
    }

    function fltEditStatusRedirectArgs($args, $status_obj) {
        
        // If custom capabilities are newly enabled, redirect back to Post Access tab for review / editing of Role Caps
        if (\PP_Statuses_Functions::is_REQUEST('status_capability_status')) {
            if (!$stored_capability_status = get_option('presspermit_status_capability_status')) {
                $stored_capability_status = [];
            }

            $set_status_capability_status = \PP_Statuses_Functions::REQUEST_key('status_capability_status');

            if (($status_obj->name == $set_status_capability_status) && empty($stored_capability_status[$status_obj->name])) {
                $args['action'] = 'edit-status';
                $args['name'] = $status_obj->name;
                $args['pp_tab'] = 'post_access';
            }
        }

        return $args;
    }

    public function actLoadAjaxHandler()
    {
        foreach (['set_privacy'] as $ajax_type) {
            if (isset($_REQUEST["pp_ajax_{$ajax_type}"])) {
                check_ajax_referer('pp-ajax');

                $class_name = str_replace('_', '', ucwords( $ajax_type, '_') ) . 'Ajax';
                
                $class_parent = ( in_array($class_name, ['SetPrivacyAjax']) ) ? 'Gutenberg' : '';
                
                $require_path = ( $class_parent ) ? "{$class_parent}/" : '';
                require_once(PP_STATUS_CONTROL_CLASSPATH . "/UI/{$require_path}{$class_name}.php");
                
                $load_class = "\\PublishPress\\Statuses\\StatusControl\UI\\";
                $load_class .= ($class_parent) ? $class_parent . "\\" . $class_name : $class_name;

                new $load_class();

                exit;
            }
        }
    }

    function ajax_find_posts()
    {
        require_once(PP_STATUS_CONTROL_CLASSPATH . '/UI/Dashboard/Ajax.php');
        StatusControl\UI\Dashboard\Ajax::wp_ajax_find_posts();
    }

    function act_process_conditions()
    {
        global $current_user;

        // unfortunate little hack due to execution order
        if (\PublishPress_Statuses::instance()->options->supplemental_cap_moderate_any && !empty($current_user->ID)) {
            do_action('publishpress_statuses_supplement_moderate_any_cap');
        }
    }

    function act_inline_edit_status_helper($referer)
    {
        if ('inlineeditnonce' == $referer) {
            if ($keep_custom_privacy = \PP_Statuses_Functions::POST_key('keep_custom_privacy')) {
                $_POST['_status'] = $keep_custom_privacy;
            }
        }
    }

    // prevent default_privacy option from forcing a draft/pending post into private publishing
    public function actDefaultPrivacyWorkaround()
    {
        if (\PP_Statuses_Functions::empty_POST('publish') && \PP_Statuses_Functions::is_POST('visibility') && \PP_Statuses_Functions::is_POST('post_type') 
        && \PublishPress_Statuses::instance()->options->default_privacy[\PP_Statuses_Functions::POST_key('post_type')]
        ) {
            $stati = get_post_stati(['moderation' => true], 'names');
            if (!\PP_Statuses_Functions::empty_POST('post_status') && in_array(\PP_Statuses_Functions::POST_key('post_status'), $stati, true)) {
                return;
            }

            $stati = get_post_stati(['public' => true, 'private' => true], 'names', 'or');

            if (!in_array(\PP_Statuses_Functions::POST_key('visibility'), ['public', 'password'], true) 
            && (!\PP_Statuses_Functions::is_POST('hidden_post_status') || !in_array(\PP_Statuses_Functions::POST_key('hidden_post_status'), $stati, true))
            ) {
                $_POST['post_status'] = \PP_Statuses_Functions::POST_key('hidden_post_status');
                $_REQUEST['post_status'] = \PP_Statuses_Functions::POST_key('hidden_post_status');

                $_POST['visibility'] = 'public';
                $_REQUEST['visibility'] = 'public';
            }
        }
    }

    function act_bulk_edit_posts($referer)
    {
        if ('bulk-posts' == $referer) {
            if (\PublishPress_Statuses::isContentAdministrator() || current_user_can('pp_force_quick_edit')) {
                // phpcs note: Nonce check not required because this code is already triggered by a check_admin_referer('bulk-posts') call.

                require_once(PP_STATUS_CONTROL_CLASSPATH . '/UI/Dashboard/BulkEdit.php');
                StatusControl\UI\Dashboard\BulkEdit::bulk_edit_posts($_REQUEST);  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }
        }
    }

    function act_condition_caption($cond_caption, $attrib, $cond)
    {
        $attributes = PPS::attributes();

        if (isset($attributes->attributes[$attrib]->conditions[$cond])) {
            $cond_caption = $attributes->attributes[$attrib]->conditions[$cond]->label;

        } elseif ('post_status' == $attrib) {
            if ($status_obj = get_post_status_object($cond))
                $cond_caption = $status_obj->label;
        }

        return $cond_caption;
    }

    function act_permission_status_ui($object_type, $type_caps, $role_name = '')
    {
        require_once(PP_STATUS_CONTROL_CLASSPATH . '/UI/Attributes.php');
        StatusControl\UI\Attributes::attributes_ui($object_type, $type_caps, $role_name);
    }

    function acf_status_rule_options($statuses)
    {
        $stati = get_post_stati(['internal' => false], 'object');
        foreach ($stati as $status => $status_obj) {
            if (!isset($statuses[$status]))
                $statuses[$status] = $status_obj->label;
        }

        return $statuses;
    }

    function optionCaptions($captions)
    {
        $captions['pattern_roles_include_custom_status_rolecaps'] = esc_html__('Type-specific Supplemental Roles grant all custom status capabilities in Pattern Role', 'publishpress-statuses-pro');
        return $captions;
    }

    function optionSections($sections)
    {
        if (function_exists('presspermit') && presspermit()->getOption('advanced_options')) {
            $new = [
                'role_integration' => ['pattern_roles_include_custom_status_rolecaps'],
            ];

            $tab = 'advanced';

            if (isset($sections[$tab])) {
                foreach ($new as $section => $options) {
                    $sections[$tab][$section] = (isset($sections[$tab][$section])) ? array_merge($sections[$tab][$section], $options) : $options;
                }
            }
        }

        return $sections;
    }

    function advanced_tab_permissions_options_ui ($tab, $section, $ui) {
        if (('advanced' == $tab) && ('role_integration' == $section)) {
            $hint = __('For example, if the Author role has the edit_pitch_posts capability, a Supplemental Role of Page Author will include edit_pitch_pages', 'publishpress-statuses-pro');
            $ui->optionCheckbox('pattern_roles_include_custom_status_rolecaps', $tab, $section, $hint);
        }
    }
}
