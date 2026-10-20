<?php
namespace PublishPress\Permissions;

class SyncHooksAdmin
{
    function __construct()
    {
        add_action('presspermit_options_ui', [$this, 'optionsUI']);
        add_action('admin_enqueue_scripts', [$this, 'act_scripts']);
        add_filter('presspermit_custom_sanitize_setting', [$this, 'flt_custom_sanitize_setting'], 10, 4);
        add_action('presspermit_handle_submission', [$this, 'validateSyncSettings'], 5, 2);
    }

    function optionsUI()
    {
        require_once(PRESSPERMIT_SYNC_CLASSPATH . '/UI/SettingsTabSyncPosts.php');
        new SyncPosts\UI\SettingsTabSyncPosts();
    }

    function act_scripts()
    {
        if ('presspermit-settings' == presspermitPluginPage()) {
            $urlpath = plugins_url('', PRESSPERMIT_SYNC_FILE);
            wp_enqueue_style('presspermit-sync-settings', $urlpath . '/common/css/settings.css', [], PRESSPERMIT_SYNC_VERSION);

            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
            wp_enqueue_script('presspermit-sync-settings', $urlpath . "/common/js/settings{$suffix}.js", ['jquery'], PRESSPERMIT_SYNC_VERSION);
            
            // Enqueue Select2 for sync posts settings
            $this->enqueueSelect2Scripts();
        }
    }
    
    function enqueueSelect2Scripts() {
        // Always use our own Select2 version with unique handles to avoid conflicts with other plugins
        // (e.g., WooCommerce) that may load different versions
        $base_url = plugins_url('lib/vendor/publishpress/publishpress-permissions/common/lib/select2-4.0.13/', PRESSPERMIT_PRO_FILE);
        
        // Register and enqueue with unique handles
        if (!wp_style_is('presspermit-select2', 'registered')) {
            wp_register_style('presspermit-select2', $base_url . 'css/select2.min.css', array(), '4.0.13', 'screen');
        }
        
        if (!wp_script_is('presspermit-select2', 'registered')) {
            wp_register_script('presspermit-select2', $base_url . 'js/select2.full.min.js', ['jquery'], '4.0.13', true);
        }
        
        wp_enqueue_style('presspermit-select2');
        wp_enqueue_script('presspermit-select2');
    }

    /**
     * Custom sanitization for sync settings that have nested array structures
     */
    function flt_custom_sanitize_setting($is_custom_sanitized, $option_basename, $default_prefix, $args) {
        // Handle sync_posts_to_users_role which has nested array structure: [post_type][role_name]
        if ($option_basename === 'sync_posts_to_users_role') {
            // phpcs Note: this is triggered by our filter application, so additional nonce verification is unnecessary
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            if (isset($_POST[$option_basename]) && is_array($_POST[$option_basename])) {
                $sanitized_data = [];
                
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                foreach ($_POST[$option_basename] as $post_type => $roles) {
                    $sanitized_post_type = sanitize_text_field($post_type);
                    
                    if (is_array($roles)) {
                        $sanitized_data[$sanitized_post_type] = array_map('sanitize_text_field', $roles);
                    }
                }
                
                presspermit()->updateOption($default_prefix . $option_basename, $sanitized_data, $args);
                return true;
            }
        }

        // Handle sync_posts_to_users_user_field which contains template placeholders with square brackets
        if ($option_basename === 'sync_posts_to_users_user_field') {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            if (isset($_POST[$option_basename]) && is_array($_POST[$option_basename])) {
                $sanitized_data = [];
                
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                foreach ($_POST[$option_basename] as $post_type => $template) {
                    $sanitized_post_type = sanitize_text_field($post_type);
                    
                    // Sanitize template value while preserving square brackets for placeholders
                    // Only allow alphanumeric, spaces, underscores, hyphens, colons, and square brackets
                    $sanitized_template = preg_replace('/[^a-zA-Z0-9\s\[\]_\-:@.]+/', '', $template);
                    
                    $sanitized_data[$sanitized_post_type] = $sanitized_template;
                }
                
                presspermit()->updateOption($default_prefix . $option_basename, $sanitized_data, $args);
                return true;
            }
        }

        return $is_custom_sanitized;
    }

    /**
     * Validate sync settings before processing
     */
    function validateSyncSettings($action, $args) {
        // Only validate on update action
        if ($action !== 'update') {
            return;
        }

        // Verify nonce before processing form data
        if (
            empty($_POST['_wpnonce']) ||
            !wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), 'presspermit_sync_settings')
        ) {
            return;
        }

        // Check if sync is enabled
        if (empty($_POST['sync_posts_to_users'])) {
            return;
        }

        $validation_errors = [];
        $enabled_post_types = [];

        // Get enabled post types
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
        if (isset($_POST['sync_posts_to_users_types']) && is_array($_POST['sync_posts_to_users_types'])) {
            // Sanitize post type keys and values
            $sanitized_types = [];
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below
            foreach ($_POST['sync_posts_to_users_types'] as $post_type => $enabled) {
                $sanitized_post_type = sanitize_text_field($post_type);
                $sanitized_enabled = sanitize_text_field($enabled);
                $sanitized_types[$sanitized_post_type] = $sanitized_enabled;
            }
            foreach ($sanitized_types as $post_type => $enabled) {
                if (!empty($enabled)) {
                    $enabled_post_types[] = $post_type;
                }
            }
        }

        // Validate role selection for each enabled post type
        if (!empty($enabled_post_types)) {
            foreach ($enabled_post_types as $post_type) {
                $roles = [];
                
                // Get selected roles for this post type
                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
                if (isset($_POST['sync_posts_to_users_role'][$post_type])) {
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below
                    $roles_input = $_POST['sync_posts_to_users_role'][$post_type];
                    if (is_array($roles_input)) {
                        // Sanitize and filter out empty values
                        $roles = array_filter(array_map('sanitize_text_field', $roles_input), function($role) {
                            return !empty($role);
                        });
                    }
                }

                // Check if no valid roles are selected
                if (empty($roles)) {
                    $post_type_object = get_post_type_object($post_type);
                    $post_type_name = $post_type_object ? $post_type_object->labels->name : $post_type;
                    
                    $validation_errors[] = sprintf(
                        esc_html__('Role selection is required for %s. Please select at least one role.', 'presspermit-pro'),
                        $post_type_name
                    );
                }
            }
        }

        // If there are validation errors, add them to admin notices and redirect back
        if (!empty($validation_errors)) {
            // Store errors in transient for display
            set_transient('presspermit_sync_validation_errors_' . get_current_user_id(), $validation_errors, 60);
            
            // Get the current tab for redirect
            $tab = (!empty($_POST['pp_tab'])) ? "&pp_tab=" . sanitize_text_field($_POST['pp_tab']) : '';
            
            // Redirect back to settings page with error flag
            wp_redirect(admin_url("admin.php?page=presspermit-settings{$tab}&presspermit_validation_error=1"));
            exit;
        }
    }
}
