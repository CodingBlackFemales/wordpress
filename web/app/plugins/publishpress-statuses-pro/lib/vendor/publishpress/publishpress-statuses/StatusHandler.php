<?php
namespace PublishPress_Statuses;

// Custom Status management: Handle status add / edit requests
class StatusHandler {
    /**
     * Handles a form's POST request to add a custom status
     *
     */
    public static function handleAddCustomStatus()
    {
        global $current_user;

        check_admin_referer('custom-status-add-nonce');

        if (!current_user_can('manage_options') && !current_user_can('pp_manage_statuses')) {
            wp_die(esc_html__('Sorry, you do not have permission to edit custom statuses.', 'publishpress-statuses'));
        }

        // Validate and sanitize the form data
        $status_label = !empty($_POST['status_label']) ? sanitize_text_field(trim(sanitize_text_field(wp_unslash($_POST['status_label'])))) : '';

        $status_name = !empty($_POST['slug']) ? sanitize_title(wp_unslash($_POST['slug'])) : '';

        $status_description = !empty($_POST['description']) ? stripslashes(wp_filter_nohtml_kses(trim(sanitize_text_field(wp_unslash($_POST['description']))))) : '';

        $status_color = !empty($_POST['status_color']) ? sanitize_hex_color(wp_unslash($_POST['status_color'])) : '';

        $status_icon = !empty($_POST['icon']) ? str_replace('dashicons|', '', sanitize_key($_POST['icon'])) : '';

        $taxonomy = (!empty($_POST['taxonomy'])) ? sanitize_key($_POST['taxonomy']) : \PublishPress_Statuses::TAXONOMY_PRE_PUBLISH;

        if ($taxonomy 
        && !in_array(
            $taxonomy, 
            apply_filters(
                'publishpress_statuses_taxonomies', 
                [\PublishPress_Statuses::TAXONOMY_PRE_PUBLISH, \PublishPress_Statuses::TAXONOMY_PRIVACY]
            )
        )) {
            $taxonomy = \PublishPress_Statuses::TAXONOMY_PRE_PUBLISH;
        }

        /**
         * Form validation
         * - Name is required and can't conflict with an existing name or slug
         * - Description is optional
         */
        $form_errors = [];

        // Check if name field was filled in
        if (empty($status_label)) {
            $form_errors['label'] = __('Please enter a name for the status', 'publishpress-statuses');
        }

        if (empty($status_name)) {
            $form_errors['label'] = __('Please enter a slug for the status', 'publishpress-statuses');
        } else {
        	$status_name = apply_filters('publishpress_statuses_add_status', sanitize_key($status_name), $taxonomy);
        }

        // Check that the name isn't numeric
        if (is_numeric($status_name)) {
            $form_errors['label'] = __(
                'Please enter a valid, non-numeric name for the status.',
                'publishpress-statuses'
            );
        }
        // Check that the status name doesn't exceed 20 chars
        $name_is_valid = true;
        if (function_exists('mb_strlen')) {
            if (mb_strlen($status_label) > 20) {
                $name_is_valid = false;
            }
        } else {
            if (strlen($status_label) > 20) {
                $name_is_valid = false;
            }
        }
        if (! $name_is_valid) {
            $form_errors['label'] = __(
                'Status name cannot exceed 20 characters. Please try a shorter name.',
                'publishpress-statuses'
            );

            return;
        }

        $name_is_valid = true;
        if (function_exists('mb_strlen')) {
            if (mb_strlen($status_name) > 20) {
                $name_is_valid = false;
            }
        } else {
            if (strlen($status_name) > 20) {
                $name_is_valid = false;
            }
        }
        if (! $name_is_valid) {
            $form_errors['label'] = __(
                'Status slug cannot exceed 20 characters. Please try a shorter name.',
                'publishpress-statuses'
            );

            return;
        }

        // Check to make sure the status doesn't already exist as another term because otherwise we'd get a weird slug
        if (get_term_by('slug', $status_name, \PublishPress_Statuses::TAXONOMY_PRE_PUBLISH)) {
            $form_errors['label'] = __(
                'Name conflicts with existing status. Please choose another.',
                'publishpress-statuses'
            );
        }
        // Check to make sure the name is not restricted
        if (self::is_restricted_status(strtolower($status_name))) {
            $form_errors['label'] = __(
                'Status name is restricted. Please choose another name.',
                'publishpress-statuses'
            );
        }

        // If there were any form errors, kick out and return them
        if (count($form_errors)) {
            \PublishPress_Statuses::instance()->form_errors = $form_errors;
            \PublishPress_Statuses::instance()->last_error = 'form-error';
            return;
        }

        // Try to add the status
        $status_args = [
            'description' => $status_description,
            'slug' => $status_name,
            'color' => $status_color,
            'icon' => $status_icon,
        ];
        
        $return = \PublishPress_Statuses::instance()->addStatus($taxonomy, $status_label, $status_args);

        if (is_wp_error($return)) {
            wp_die(esc_html__('Could not add status: ', 'publishpress-statuses') . esc_html($return->get_error_message()));
        }

        global $wp_roles;

        if (!empty($wp_roles) && is_object($wp_roles) && !empty($wp_roles->roles)) {
            foreach($wp_roles->role_objects as $role_name => $role) {

                // Mirror Planner behavior of enabling standard WP roles to assign statuses, but also grant to other roles based on post / page capabilities
                if (in_array($role_name, ['administrator', 'author', 'editor', 'contributor']) || $role->has_cap('edit_posts') || $role->has_cap('edit_pages')) {
                    $cap_name = 'status_change_' . str_replace('-', '_', $status_name);

                    if (empty($role->capabilties[$cap_name])) {
                        $role->add_cap($cap_name);
                    }
                }
            }
        }

        delete_option('publishpress_statuses_num_roles');

        $redirect_args = ['action' => 'edit-status', 'name' => $status_name, 'message' => 'status-added'];

        if ($status_type = \PP_Statuses_Functions::REQUEST_key('status_type')) {
            $redirect_args['status_type'] = $status_type;
        }

        if (!empty(\PublishPress_Statuses::instance()->options->new_statuses_main_workflow)) {
            $defaulted_to_main = (array) get_option('publishpress_statuses_defaulted_to_main', []);
            $defaulted_to_main[$status_name] = true;
            update_option('publishpress_statuses_defaulted_to_main', $defaulted_to_main);
        
            if ($status_positions = get_option('publishpress_status_positions')) {
                $status_positions = array_values($status_positions);
    
                if ('post_status' == $taxonomy) {
                    if ($alternate_pos = array_search('_pre-publish-alternate', $status_positions)) {
                        $status_positions = array_merge(
                            array_slice($status_positions, 0, $alternate_pos - 1),
                            [$status_name],
                            array_slice($status_positions, $alternate_pos)
                        );
    
                        update_option('publishpress_status_positions', $status_positions);
                    }
                } elseif ('pp_revision_status' == $taxonomy) {
                    if ($alternate_pos) {
                        $status_positions = array_merge(
                            array_slice($status_positions, 0, $alternate_pos - 1),
                            [$status_name],
                            array_slice($status_positions, $alternate_pos)
                        );
    
                        update_option('publishpress_status_positions', $status_positions);
                    }
                }
            }
        }

        // Redirect if successful
        $redirect_url = \PublishPress_Statuses::getLink($redirect_args);

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Handles a GET request to delete a specific term
     *
     */
    public static function handleDeleteCustomStatus()
    {
        // Check for proper nonce
        check_admin_referer('delete-status');

        // Only allow users with the proper caps
        if (!current_user_can('manage_options') && !current_user_can('pp_manage_statuses')) {
            wp_die(esc_html__('Sorry, you do not have permission to edit custom statuses.', 'publishpress-statuses'));
        }

        // Check to make sure the status isn't already deleted
        $name = !empty($_GET['name']) ? sanitize_key($_GET['name']) : '';
        $term = \PublishPress_Statuses::getStatusBy('id', $name);
        if (! $term) {
            wp_die(esc_html__('Status does not exist.', 'publishpress-statuses'));
        }

        $return = self::deleteCustomStatus($name);
        if (is_wp_error($return)) {
            wp_die(esc_html__('Could not delete the status: ', 'publishpress-statuses') . esc_html($return->get_error_message()));
        }

        $redirect_url = \PublishPress_Statuses::getLink(['message' => 'status-deleted']);
        wp_redirect($redirect_url);

        exit;
    }

    /**
     * Handles a POST request to edit a custom status
     *
     */
    public static function handleEditCustomStatus()
    {
        check_admin_referer('edit-status');

        if (!current_user_can('manage_options') && !current_user_can('pp_manage_statuses')) {
            wp_die(esc_html(__('Sorry, you are not allowed to access this page.')));
        }

        $name = (!empty($_REQUEST['name'])) ? sanitize_key($_REQUEST['name']) : '';

        if (!$existing_status = \PublishPress_Statuses::getStatusBy('slug', $name)) {
            wp_die(esc_html__("Post status doesn't exist.", 'publishpress-statuses'));
        }

        $color = !empty($_POST['status_color']) ? sanitize_hex_color(wp_unslash($_POST['status_color'])) : '';
        $icon = !empty($_POST['icon']) ? sanitize_text_field(wp_unslash($_POST['icon'])) : '';
        $icon = str_replace('dashicons|', '', $icon);

        $status_obj = $existing_status;

        // Prime the term_meta records if they don't already exist
        // Doing this in advance prevents seletions from being overridden by defaults.
        if (!empty($status_obj->_builtin)) {
            $taxonomy = \PublishPress_Statuses::TAXONOMY_CORE_STATUS;

        } elseif (in_array($name, ['_pre-publish-alternate', '_disabled'])) {
            $taxonomy = \PublishPress_Statuses::TAXONOMY_PSEUDO_STATUS;

        } elseif (!empty($status_obj->private)) {
            $taxonomy = \PublishPress_Statuses::TAXONOMY_PRIVACY;

        } else {
			if (!$taxonomy = apply_filters('publishpress_statuses_taxonomy', '', $status_obj)) {
            	$taxonomy = \PublishPress_Statuses::TAXONOMY_PRE_PUBLISH;
        	}
        }

        if (!$term = get_term_by('slug', $name, $taxonomy)) {
            \PublishPress_Statuses::instance()->addStatus($taxonomy, $status_obj->label, ['slug' => $name]);
        }

        $form_errors = [];

        // Try to edit the post status
        $args = [
            'name' => $name,
            'color' => $color,
            'icon' => $icon,
        ];

        $status_post_types = !empty($status_obj->post_type) ? $status_obj->post_type : [];

        if (!empty($_REQUEST['pp_status_all_types'])) {
            $args['post_type'] = [];

        } else {
            $set_post_types = !empty($_REQUEST['pp_status_post_types']) ? array_map('intval', $_REQUEST['pp_status_post_types']) : false;

            if ($set_post_types) {
                if ($add_types = array_filter($set_post_types)) {
                    $status_post_types = array_unique(array_merge($status_post_types, array_map('sanitize_key', array_keys($add_types))));
                }

                if ($remove_types = array_diff($set_post_types, ['1', true, 1])) {
                    $status_post_types = array_diff($status_post_types, array_keys($remove_types));
                }

                $args['post_type'] = $status_post_types;
            }
        }

        if (isset($_REQUEST['roles_set_status'])) {
            $cap_name = str_replace('-', '_', "status_change_{$status_obj->name}");
            
            $roles_set_status = array_map('intval', $_REQUEST['roles_set_status']);

            foreach ($roles_set_status as $role_name => $set_val) {
                $role_name = sanitize_key($role_name);
                $set_val = boolval($set_val);

                if (!\PP_Statuses_Functions::isEditableRole($role_name)) {
                    continue;
                }

                if ($role = get_role($role_name)) {
                    if ($set_val && empty($role->capabilities[$cap_name])) {
                        $role->add_cap($cap_name);
                        $changed = true;

                    } elseif (!$set_val && !empty($role->capabilities[$cap_name])) {
                        $role->remove_cap($cap_name);
                        $changed = true;
                    }
                }

            }

            if (!empty($changed)) {
                \PublishPress_Statuses::updateStatusNumRoles($status_obj->name, ['force_refresh' => true]);
            }
        }

        $status_obj = get_post_status_object($name);

        if (!\PP_Statuses_Functions::empty_REQUEST('return_module')) {
            $arr = ['message' => 'status-updated'];
            $arr['page'] = 'pp-modules-settings';
            $arr['settings_module'] = \PP_Statuses_Functions::REQUEST_key('return_module');

            $redirect_url = \PublishPress_Statuses::getLink($arr);
        } else {
            $arr = ['message' => 'status-updated'];
            $arr['page'] = 'publishpress-statuses';
            $arr['action'] = 'edit-status';
            $arr['name'] = $name;

            if (!empty($_REQUEST['pp_tab'])) {
                $arr['pp_tab'] = str_replace('pp-', '', sanitize_key($_REQUEST['pp_tab']));
            }

            $arr = apply_filters('publishpress_status_edit_redirect_args', $arr, $status_obj);

            $redirect_url = \PublishPress_Statuses::getLink($arr);
        }

        // work around bug in status capabilities library (displaying Set capability checkbox for disabled post types)
        if (isset($_REQUEST['status_caps'])) {                                                          // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            foreach (array_keys($_REQUEST['status_caps']) as $role_name) {                              // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                if (isset($_REQUEST['status_caps'][$role_name]["status_change_{$status_obj->name}"])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
                    unset($_REQUEST['status_caps'][$role_name]["status_change_{$status_obj->name}"]);   // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
                }
            }
        }

        if (!empty($_POST['pp_statuses_enable_status_post_caps'])) {
            update_option('cme_custom_status_control', true);
            update_option('cme_custom_status_postmeta_caps', true);
        }

        do_action('publishpress_statuses_edit_status', $existing_status->name, $args);

        $return = self::updateCustomStatus($existing_status->name, $args);

        if (is_wp_error($return)) {
            wp_die(esc_html__('Error updating post status.', 'publishpress-statuses'));
        }

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Handles a POST request to edit a custom status
     *
     */
    public static function handleEditCustomStatusLabels()
    {
        check_admin_referer('edit-status');

        if (!current_user_can('manage_options') && !current_user_can('pp_manage_statuses')) {
            wp_die(esc_html(__('Sorry, you are not allowed to access this page.')));
        }

        $name = !empty($_REQUEST['name']) ? trim(sanitize_text_field(wp_unslash($_REQUEST['name']))) : '';

        if (!$existing_status = \PublishPress_Statuses::getStatusBy('name', sanitize_key($name))) {
            wp_die(esc_html__("Post status doesn't exist.", 'publishpress-statuses'));
        }

        $status_obj = $existing_status;

        // Prime the term_meta records if they don't already exist
        // Doing this in advance prevents seletions from being overridden by defaults.
        if (!empty($status_obj->_builtin)) {
            $taxonomy = \PublishPress_Statuses::TAXONOMY_CORE_STATUS;

        } elseif (in_array($name, ['_pre-publish-alternate', '_disabled'])) {
            $taxonomy = \PublishPress_Statuses::TAXONOMY_PSEUDO_STATUS;

        } elseif (!empty($status_obj->private)) {
            $taxonomy = \PublishPress_Statuses::TAXONOMY_PRIVACY;

        } else {
			if (!$taxonomy = apply_filters('publishpress_statuses_taxonomy', '', $status_obj)) {
            	$taxonomy = \PublishPress_Statuses::TAXONOMY_PRE_PUBLISH;
        	}
        }

        if (!$term = get_term_by('slug', $name, $taxonomy)) {
            \PublishPress_Statuses::instance()->addStatus($taxonomy, $status_obj->label, ['slug' => $name]);
        }

        $form_errors = [];

        if (isset($_REQUEST['description'])) {
            $description = stripslashes(wp_filter_nohtml_kses(trim(sanitize_text_field(wp_unslash($_REQUEST['description'])))));
        }

        if (isset($_REQUEST['status_label'])) {
            /**
             * Form validation for editing custom status
             *
             * Details
             * - 'name' is a required field and can't conflict with existing name or slug
             * - 'description' is optional
             */

            $label = !empty($_POST['status_label']) ? trim(sanitize_text_field(wp_unslash($_POST['status_label']))) : '';

            // Check if name field was filled in
            if (empty($label)) {
                $form_errors['status_label'] = __('Please enter a name for the status', 'publishpress-statuses');
            }

            // Check that the name isn't numeric
            if (is_numeric($label)) {
                $form_errors['status_label'] = __(
                    'Please enter a valid, non-numeric name for the status.',
                    'publishpress-statuses'
                );
            }
            // Check that the status name doesn't exceed 20 chars
            $name_is_valid = true;

            if (function_exists('mb_strlen')) {
                if (mb_strlen($label) > 20) {
                    $name_is_valid = false;
                }
            } else {
                if (strlen($label) > 20) {
                    $name_is_valid = false;
                }
            }

            if (! $name_is_valid) {
                $form_errors['status_label'] = __(
                    'Status name cannot exceed 20 characters. Please try a shorter name.',
                    'publishpress-statuses'
                );
            }

            if (!empty($form_errors)) {
                \PublishPress_Statuses::instance()->form_errors = $form_errors;
            }
        }

        // Try to edit the post status
        $args = [
            'name' => $name,
        ];

        if (isset($label)) {
            $args['label'] = $label;
        }

        if (isset($description)) {
            $args['description'] = $description;
        }


        $labels = [];
        $labels['save_as'] = !empty($_REQUEST['status_save_as_label']) ? sanitize_text_field(wp_unslash($_REQUEST['status_save_as_label'])) : '';
        $labels['publish'] = !empty($_REQUEST['status_publish_label']) ? sanitize_text_field(wp_unslash($_REQUEST['status_publish_label'])) : '';
        $args['labels'] = (object) $labels;

        $name = apply_filters('publishpress_statuses_sanitize_status_name', sanitize_key($name), $taxonomy);

        $status_obj = get_post_status_object($name);

        if (!\PP_Statuses_Functions::empty_REQUEST('return_module')) {
            $arr = ['message' => 'status-updated'];
            $arr['page'] = 'pp-modules-settings';
            $arr['settings_module'] = \PP_Statuses_Functions::REQUEST_key('return_module');

            $redirect_url = \PublishPress_Statuses::getLink($arr);
        } else {
            $arr = ['message' => 'status-updated'];
            $arr['page'] = 'publishpress-statuses';
            $arr['action'] = 'edit-status-labels';
            $arr['name'] = $name;

            if (!empty($_REQUEST['pp_tab'])) {
                $arr['pp_tab'] = str_replace('pp-', '', sanitize_key($_REQUEST['pp_tab']));
            }

            $arr = apply_filters('publishpress_status_edit_redirect_args', $arr, $status_obj);

            $redirect_url = \PublishPress_Statuses::getLink($arr);
        }

        do_action('publishpress_statuses_edit_status_labels', $existing_status->name, $args);

        $return = self::updateCustomStatus($existing_status->name, $args);

        if (is_wp_error($return)) {
            wp_die(esc_html__('Error updating post status labels.', 'publishpress-statuses'));
        }

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Update an existing custom status
     *
     * @param int @status_id ID for the status
     * @param array $args Any arguments to be updated
     *
     * @return object|WP_Error|false $updated_status Newly updated status object
     */
    public static function updateCustomStatus($name, $args = [])
    {
        if (in_array($name, ['_pre-publish-alternate', '_disabled'])) {
            return;
        }

        $status_obj = \PublishPress_Statuses::getStatusBy('slug', $name);

        if (! $status_obj || is_wp_error($status_obj)) {
            return new \WP_Error('invalid', __("Custom status ($name) doesn't exist.", 'publishpress-statuses'));
        }

        // Reset our internal object cache
        \PublishPress_Statuses::instance()->clearStatusCache();

        $updatedStatusId = $name;


        // We're encoding metadata that isn't supported by default in the term's description field
        $args_to_encode = [];

        if (!empty($status_obj->_builtin)) {
            $taxonomy = \PublishPress_Statuses::TAXONOMY_CORE_STATUS;

        } elseif (in_array($name, ['_pre-publish-alternate', '_disabled'])) {
            $taxonomy = \PublishPress_Statuses::TAXONOMY_PSEUDO_STATUS;

        } elseif (!empty($status_obj->private)) {
            $taxonomy = \PublishPress_Statuses::TAXONOMY_PRIVACY;

        } else {
			if (!$taxonomy = apply_filters('publishpress_statuses_taxonomy', '', $status_obj)) {
            	$taxonomy = \PublishPress_Statuses::TAXONOMY_PRE_PUBLISH;
        	}
        }

        // Also re-encode any existing properties, since the plugin that defined it may be temporarily deactivated.
        if (!$term = get_term_by('slug', $name, $taxonomy)) {
            if ($term_id = \PublishPress_Statuses::instance()->addStatus($taxonomy, $status_obj->label, ['slug' => $name])) {
                $term = get_term_by('slug', $name, $taxonomy);
                $updated_status_array = (array) $term;
            }
        }

        if (!empty($status_obj)) {
            $label_storage = \PublishPress_Statuses::instance()->options->label_storage;

            switch ($label_storage) {
                case 'user':
                    if (!empty($status_obj->pp_builtin) || !empty($status_obj->_builtin)
                    || in_array($name, ['draft', 'pending', 'publish', 'private', 'future'])
                    ) {
                        $label_locked = true;
                    }

                    break;

                default:
                    if ((!empty($status_obj->_builtin) && ('pending' != $name))
                    || in_array($name, ['draft', 'publish', 'private', 'future'])
                    ) {
                        $label_locked = true;
                    }
            }

            if (!isset($args['label'])) {
                $label_locked = true;
            }
        }

        if ($term) {
            $term_meta_fields = apply_filters('publishpress_statuses_meta_fields', ['labels', 'post_type', 'roles', 'status_parent', 'color', 'icon']);

            if (!empty($label_locked)) {
                $term_meta_fields = array_diff($term_meta_fields, ['labels']);
            }

            foreach ($args as $field => $set_value) {
                if (in_array($field, $term_meta_fields)) {
                    if (is_array($args[$field])) {
                        $meta_val = [];

                        foreach ($set_value as $k => $val) {
                            $meta_val[$k] = sanitize_textarea_field($val);
                        }
                    } elseif (is_object($set_value)) {
                        $meta_val = \get_object_vars($set_value);

                        foreach($meta_val as $k => $val) {
                            $meta_val[$k] = sanitize_text_field(wp_unslash($val));
                        }

                        $meta_val = (object) $meta_val;
                    } else {
                        $meta_val = sanitize_textarea_field($set_value);
                    }

                    $result = update_term_meta($term->term_id, $field, $meta_val);

                    if (is_wp_error($result)) {
                        return $result;
                    }
                }
            }

            $args = array_intersect_key(
                $args, 
                array_fill_keys(['term_id', 'name', 'slug', 'label', 'term_group', 'term_taxonomy_id', 'taxonomy', 'description', 'parent'], true)
            );

            $args['description'] = (isset($args['description'])) ? $args['description'] : $term->description;

            if (!empty($args['name'])) {
                $args['slug'] = $args['name'];
            }

            if (!empty($args['label'])) {
                $args['name'] = $args['label'];
            }

            // temp (@todo: test status slug rename
            unset($args['slug']);

            if (!empty($status_obj->_builtin) && ('pending' != $name)) {
                $args['name'] = $status_obj->label;
            }

            if (!empty($label_locked)) {
                $args = array_diff_key($args, array_fill_keys(['label', 'labels', 'name'], true));
            }

            $updated_status_array = wp_update_term($term->term_id, $taxonomy, $args);

            if (is_wp_error($updated_status_array)) {
                return $updated_status_array;
            }
        }

        if (!$term || !is_array($updated_status_array) || !isset($updated_status_array['term_id'])) {
            $term_id = (!empty($term)) ? $term->term_id : 0;
            return new \WP_Error('custom-status-term_id', esc_html__("Error while updating the status ($name)", 'publishpress-statuses'));
        }

        $updatedStatusId = $updated_status_array['term_id'];

        return \PublishPress_Statuses::getStatusBy('id', $updatedStatusId);
    }

    private function statusDeleted($status_name, $reassign_status = '') {

    }


    /**
     * Deletes a custom status from the wp_terms table.
     *
     * Reassigns posts that currently have the deleted status assigned.
     */
    public static function deleteCustomStatus($old_status, $args = [], $reassign_status = '')
    {
        if ($reassign_status == $old_status) {
            return new \WP_Error('invalid', __('Cannot reassign to the status you want to delete', 'publishpress-statuses'));
        }

        // Reset our internal object cache
        \PublishPress_Statuses::instance()->clearStatusCache();

        if (! self::is_restricted_status($old_status)) {
            $default_status = \PublishPress_Statuses::DEFAULT_STATUS;
            
            // If new status in $reassign, use that for all posts of the old_status
            if (!empty($reassign_status)) {
                if ($_status = \PublishPress_Statuses::getStatusBy('id', $reassign_status)) {
                    $new_status = $_status->name;
                }
            }

            if (empty($new_status)) {
                $new_status = $default_status;
            }
            
            if ($old_status == $default_status) {
                $new_status = 'draft';

                // @todo: If we support a custom default status, set it to draft
            }

            self::reassign_post_status($old_status, $new_status);

            if (!$status_obj = \PublishPress_Statuses::getStatusBy('name', $old_status)) {
                return false;
            }

            if (!empty($status_obj->private)) {
                $taxonomy = \PublishPress_Statuses::TAXONOMY_PRIVACY;
            } elseif (!empty($status_obj->taxonomy)) {
                $taxonomy = $status_obj->taxonomy;
            } else {
                $taxonomy = \PublishPress_Statuses::TAXONOMY_PRE_PUBLISH;
            }

            if ($term = get_term_by('slug', $old_status, $taxonomy)) {
                return wp_delete_term($term->term_id, $taxonomy, $args);
            }

        } else {
            return new \WP_Error(
                'restricted',
                __('Restricted status ', 'publishpress-statuses') . '(' . \PublishPress_Statuses::getStatusBy(
                    'id',
                    $old_status
                )->label . ')'
            );
        }
    }

    public static function handleAjaxDeleteStatus() {
        check_ajax_referer('custom-status-sortable');

        if ($status_name = \PP_Statuses_Functions::REQUEST_key('delete_status')) {
            if (!current_user_can('manage_options') && !current_user_can('pp_manage_statuses')) {
                self::printAjaxResponse('error', esc_html(__('Sorry, you are not allowed to access this page.')));
            }

            if ($status = \PublishPress_Statuses::getStatusBy('slug', $status_name)) {
                if (!empty($status->_builtin) || !empty($status->pp_builtin)) {
                    self::printAjaxResponse('error', esc_html(__('Sorry, you are not allowed to access this page.')));
                    return;
                }
                
                $return = self::deleteCustomStatus($status_name);

                if (is_wp_error($return)) {
                    self::printAjaxResponse('error', __('Could not delete the status: ', 'publishpress-statuses'));
                } else {
                    self::printAjaxResponse('success', __('Status deleted', 'publishpress-statuses'));
                }
            } else {
                self::printAjaxResponse('error', esc_html__('Status does not exist.', 'publishpress-statuses'));
            }
        }
    }

    /**
     * Handle an ajax request to update the order of custom statuses
     *
     * @since 0.7
     */
    public static function handleAjaxUpdateStatusPositions()
    {
        check_ajax_referer('custom-status-sortable');

        if (!current_user_can('manage_options') && !current_user_can('pp_manage_statuses')) {
            self::printAjaxResponse('error', esc_html(__('Sorry, you are not allowed to access this page.')));
        }

        if (!isset($_POST['status_positions']) || !is_array($_POST['status_positions'])) {
            self::printAjaxResponse('error', __('Status positions were not sent.', 'publishpress-statuses'));
        }

        update_option('publishpress_status_positions', 
            array_values(
                array_filter(
                    array_map('sanitize_key', $_POST['status_positions'])
                )
            )
        );

        // @todo: update 'publishpress_disabled_statuses' based on ordering relative to '_disabled'

        if (!empty($_REQUEST['status_hierarchy'])) {
            $status_parents = [];
			
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            foreach ($_REQUEST['status_hierarchy'] as $arr) { // (elements of multi-dim array sanitized below)
                $status_name = str_replace('status_row_', '', sanitize_key($arr['id']));
                
                $status_parents[$status_name] = '';
                
                if (!empty($arr['children']) && !empty($status_name)) {
                    foreach ($arr['children'] as $child_arr) {
                        $child_status_name = str_replace('status_row_', '', sanitize_key($child_arr['id']));

                        $status_obj = get_post_status_object($child_status_name);
                        if (!empty($status_obj) && !empty($status_obj->private)) {
                            continue;
                        }

                        $status_parents[$child_status_name] = $status_name;
                    }
                }
            }

            $statuses = \PublishPress_Statuses::getPostStati(
                [], 
                ['output' => 'object'], 
                ['show_disabled' => true, 'context' => 'load']
            );

            // Update any modified status_parent value as an term meta value
            foreach ($status_parents as $status_name => $edited_status_parent) {
                $current_status_parent = (!empty($statuses[$status_name]) && !empty($statuses[$status_name]->status_parent)) 
                ? $statuses[$status_name] : '';

                if ($edited_status_parent == $current_status_parent) {
                    continue; // no change
                }

                $result = self::updateCustomStatus($status_name, ['status_parent' => $edited_status_parent]);

                if (is_wp_error($result)) {
                    self::printAjaxResponse('error', $result->get_error_message());
                }
            }
        }
        
        self::printAjaxResponse('success', esc_html__('Status order updated.', 'publishpress-statuses'));
    }

    /**
     * Take a status and a message, JSON encode and print
     *
     * @param string $status Whether it was a 'success' or an 'error'
     * @param string $message
     * @param array $data
     *
     * @since 0.7
     *
     */
    public static function printAjaxResponse($status, $message = '', $data = null)
    {
        \PP_Statuses_Functions::printAjaxResponse($status, $message, $data);
    }

    /**
     * Determines whether the slug indicated belongs to a restricted status or not
     *
     * @param string $slug Slug of the status
     *
     * @return bool $restricted True if restricted, false if not
     */
    private static function is_restricted_status($slug)
    {
        switch ($slug) {
            case 'publish':
            case 'private':
            case 'future':
            case 'pending':
            case 'draft':
            case 'new':
            case 'inherit':
            case 'auto-draft':
            case 'trash':
                $restricted = true;
                break;

            default:
                $restricted = false;
                break;
        }

        return $restricted;
    }

    public static function settings_validate_and_save()
    {
        if (!wp_verify_nonce(\PP_Statuses_Functions::POST_key('_wpnonce'), 'edit-publishpress-settings')
        || !current_user_can('manage_options')
        ) {
            wp_die(esc_html__('Cheatin&#8217; uh?'));
        }

        if (!isset($_POST['action'], $_POST['_wpnonce'], $_POST['option_page'], $_POST['_wp_http_referer'], $_POST['publishpress_module_name'], $_POST['submit']) || !is_admin()) {
            return false;
        }
        
        if (($_POST['action'] != 'update') || 
        (!in_array('publishpress_statuses', (array)$_POST['publishpress_module_name']))
        ) {
            return false;
        }

        $module = \PublishPress_Statuses::instance();

        $new_options = [];

        $options = array_merge($module->default_options, ['privacy_statuses_enabled' => 1]);

        foreach ($options as $option_name => $current_val) {
            if ('loaded_once' == $option_name) {
                continue;
            }

            if (isset($_POST[\PublishPress_Statuses::SETTINGS_SLUG][$option_name])) {
                switch ($option_name) {
                    case 'privacy_statuses_enabled' :
                        update_option('presspermit_privacy_statuses_enabled', intval($_POST[\PublishPress_Statuses::SETTINGS_SLUG][$option_name]));
                        break;

                    case 'post_types':
                        $new_options[$option_name] = array_intersect_key(
                            array_map('intval', (array) $_POST[\PublishPress_Statuses::SETTINGS_SLUG][$option_name]), 
                            \PublishPress_Statuses::instance()->get_supported_post_types()
                        );

                        break;

                    case 'default_privacy':
                    case 'force_default_privacy':
                        $new_options[$option_name] = array_intersect_key(
                            array_map('sanitize_key', (array) $_POST[\PublishPress_Statuses::SETTINGS_SLUG][$option_name]), 
                            \PublishPress_Statuses::instance()->get_supported_post_types()
                        );

                        break;

                    case 'force_editor_detection':
                    case 'label_storage':
                    case 'moderation_statuses_default_by_sequence':
                        $new_options[$option_name] = sanitize_key($_POST[\PublishPress_Statuses::SETTINGS_SLUG][$option_name]);
                        break;

                    default:
                        $new_options[$option_name] = (int) $_POST[\PublishPress_Statuses::SETTINGS_SLUG][$option_name];
                }
            } else {
                $new_options[$option_name] = $current_val;
            }
        }

        // Cast our object and save the data.
        update_option('publishpress_custom_status_options', (object) $new_options);
        
        // Import / Backup Operations
        if (\PP_Statuses_Functions::is_POST('publishpress_statuses_import_operation', 'do_status_control_import')) {
            update_option('pp_statuses_force_status_control_import', true);
            update_option('pp_statuses_force_planner_import', true);

        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_import_operation', 'do_planner_import')) {
            update_option('pp_statuses_force_planner_import', true);

        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_import_operation', 'do_planner_import_only')) {
            update_option('pp_statuses_skip_status_control_import', true);
            update_option('pp_statuses_force_planner_import', true);

        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_backup_operation', 'backup_status_properties')) {
            update_option('pp_statuses_set_backup_props', true);
            
        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_backup_operation', 'restore_status_colors')) {
            update_option('pp_statuses_restore_backup_colors', true);

        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_backup_operation', 'restore_status_icons')) {
            update_option('pp_statuses_restore_backup_icons', true);

        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_backup_operation', 'restore_status_labels')) {
            update_option('pp_statuses_restore_backup_labels', true);

        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_backup_operation', 'restore_status_post_types')) {
            update_option('pp_statuses_restore_backup_post_types', true);
        
        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_backup_operation', 'restore_status_colors_auto')) {
            update_option('pp_statuses_restore_autobackup_colors', true);

        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_backup_operation', 'restore_status_icons_auto')) {
            update_option('pp_statuses_restore_autobackup_icons', true);

        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_backup_operation', 'restore_status_labels_auto')) {
            update_option('pp_statuses_restore_autobackup_labels', true);

        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_backup_operation', 'restore_status_post_types_auto')) {
            update_option('pp_statuses_restore_autobackup_post_types', true);

        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_backup_operation', 'default_status_colors')) {
            update_option('pp_statuses_default_colors', true);

        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_backup_operation', 'default_status_icons')) {
            update_option('pp_statuses_default_icons', true);

        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_backup_operation', 'default_status_labels')) {
            update_option('pp_statuses_default_labels', true);

        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_backup_operation', 'default_status_post_types')) {
            update_option('pp_statuses_default_post_types', true);
        
        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_backup_operation', 'default_status_colors_planner')) {
            update_option('pp_statuses_default_planner_colors', true);

        } elseif (\PP_Statuses_Functions::is_POST('publishpress_statuses_backup_operation', 'default_status_icons_planner')) {
            update_option('pp_statuses_default_planner_icons', true);
        }

        // Redirect back to the settings page that was submitted without any previous messages
        $goback = add_query_arg('message', 'settings-updated', remove_query_arg(['message'], wp_get_referer()));

        if (!empty($_REQUEST['pp_tab']) && ('workflow' != $_REQUEST['pp_tab'])) {
            $goback = add_query_arg('pp_tab', esc_attr(wp_unslash($_REQUEST['pp_tab'])), $goback);      // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        }

        wp_safe_redirect($goback);

        exit;
    }

    /**
     * Assign a new status to all posts currently set to another specified status.
     *
     * @param string $old_status Slug for the old status
     * @param string $new_status Slug for the new status
     */
    private static function reassign_post_status($old_status, $new_status = '')
    {
        global $wpdb;

        if (empty($new_status)) {
            $new_status = \PublishPress_Statuses::DEFAULT_STATUS;
        }

        // phpcs Note: Direct DB query for efficient and reliable update of all existing $old_status posts to $new_status

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->update(
            $wpdb->posts,
            ['post_status' => $new_status],
            ['post_status' => $old_status],
            ['%s']
        );

        wp_cache_flush();
    }
}
