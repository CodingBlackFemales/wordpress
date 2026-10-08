<?php
/**
 * @package PublishPress
 * @author  PublishPress
 *
 * Copyright (c) 2026 PublishPress
 *
 * ------------------------------------------------------------------------------
 * Portions of this code were originally derived from the Edit Flow plugin
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * This file is part of PublishPress Statuses
 *
 * PublishPress Statuses is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */

if (! class_exists('PublishPress_Statuses')) {
/**
 * class PublishPress_Statuses
 * Custom statuses make it simple to define the different stages in your publishing workflow.
 *
 */
class PublishPress_Statuses extends \PublishPress\PPP_Module_Base
{
    const MODULE_NAME = 'custom_status';
    const SETTINGS_SLUG = 'publishpress_custom_status_options';
    const MENU_SLUG = 'publishpress-statuses';

    private $post_type_support_slug = 'pp_custom_statuses'; // This has been plural in all of our docs

    const DEFAULT_STATUS = 'draft';

    const DEFAULT_COLOR = '#888';
    const DEFAULT_ICON = 'dashicons-welcome-add-page';

    const TAXONOMY_PRE_PUBLISH = 'post_status';
    const TAXONOMY_PRIVACY = 'post_visibility_pp';
    const TAXONOMY_CORE_STATUS = 'post_status_core_wp_pp';
    const TAXONOMY_PSEUDO_STATUS = 'pseudo_status_pp';

    private $custom_statuses_cache = [];
    private $sanitizing_post_id = false;

    public $messages = [];

    public $module;
    public $doing_rest = false;
    public $workflow_by_sequence = false;
    public $workflow_disabled = false;

    private $all_moderation_statuses = [];
    public $filtered_post_status = [];

    public $last_error = '';
    public $form_errors = [];

    public $version;
    public $slug;
    public $name;

    private static $instance = null;

    public $lock_status = [];

    public static function instance($reload = false) {
        if ( is_null(self::$instance) || $reload) {
            self::$instance = new \PublishPress_Statuses(false);
            self::$instance->load();
        }

        return self::$instance;
    }

    public static function doingREST()
    {
        return self::instance()->doing_rest;
    }

    public function clearStatusCache() {
        $this->custom_statuses_cache = [];
    }

    /**
     * Register the module with PublishPress but don't do anything else
     */
    public function __construct($do_load = true)
    {
        $this->version = PUBLISHPRESS_STATUSES_VERSION;

        if ($do_load) {
            $this->load();
        }
    }

    private function load() {
        $plugin_page = \PP_Statuses_Functions::getPluginPage();

        if (is_admin()) {
            // Methods for handling the actions of creating, making default, and deleting post stati

            add_action('wp_ajax_pp_get_selectable_statuses', [$this, 'get_ajax_selectable_statuses']);
            add_action('wp_ajax_pp_set_workflow_action', [$this, 'set_workflow_action']);

            add_action('wp_ajax_pp_update_status_positions', [$this, 'handle_ajax_update_status_positions']);
            add_action('wp_ajax_pp_delete_custom_status', [$this, 'handle_ajax_delete_custom_status']);

            add_filter('presspermit_get_post_statuses', [$this, 'flt_get_post_statuses'], 99, 5);
            add_filter('_presspermit_get_post_statuses', [$this, 'flt_get_post_statuses_internal'], 99, 4);

            add_filter('presspermit_order_statuses', [$this, 'orderStatuses'], 10, 2);
        }

        // Log the post ID field for the sanitize_post() call by wp_insert_post(), 
        // to provide context for subsequent pre_post_status, pre_post_parent, pre_post_category, pre_post_tags_input filter applications
        add_filter('pre_post_ID', 
            function($post_id) {
                $this->sanitizing_post_id = $post_id;
                return $post_id;
            }
        );

        // Use the next filter called by wp_insert_post() too mark the end of sanitize_text_field() calls for this post
        add_filter('wp_insert_post_empty_content',
            function($maybe_empty, $postarr) {
                if ($this->sanitizing_post_id && !empty($postarr['ID']) && ($postarr['ID'] == $this->sanitizing_post_id)) {
                    $this->sanitizing_post_id = false;
                }

                return $maybe_empty;
            }, 1, 2
        );

        add_filter('pre_post_status', [$this, 'fltPostStatus'], 20);
        add_filter('publishpress_statuses_default_visibility', [$this, 'fltDefaultPrivacy'], 10, 2);
        add_filter('pre_post_status', [$this, 'fltAppyDefaultVisibility'], 30);

        add_action('user_has_cap', [$this, 'fltUserHasCap'], 20, 3);

        add_filter('get_user_metadata', [$this, 'fltForcePrepublishPanel'], 10, 5);
        add_filter('rest_pre_dispatch', [$this, 'fltRestPreDispatch'], 10, 3);
        add_action('rest_api_init', [$this, 'actRestInit'], 1);
        
        add_filter('pre_post_status', [$this, 'fltApplySelectedPostStatus'], 2);
        add_filter('wp_insert_post_data', [$this, 'fltPostData'], 50, 2);
        add_filter('wp_insert_post_data', [$this, 'fltEnsureValidStatus'], 1000, 2);

        add_filter('cme_plugin_capabilities', [$this, 'fltRegisterCapabilities']);
        add_filter('cme_capability_descriptions', [$this, 'fltCapDescriptions']);

        // ShortPixel Critical CSS plugin: https://wordpress.org/support/topic/conflict-with-taxonomies-that-have-same-name-as-a-wp_post-field/
        add_filter('shortpixel_critical_css_manual_term_css', function($val) {return false;}, 5);

        global $pagenow;

        // Don't allow plugin name to be translated on Plugins screen
        if (is_admin() && !empty($pagenow) && ('plugins.php' == $pagenow)) {
            add_filter(
                'gettext', 
                function($translation, $text, $domain) {
                    if ('PublishPress Statuses Free' == $text) {
                        return 'PublishPress Statuses Free';
                    }

                    return $translation;
                }, 50, 3
            );
            
            // @todo: Ideally, WordPress would provide a post-translation filter to eliminate the need for gettext filtering.
            // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
            /* 
            add_filter(
                'plugins_list', 
                function($plugins) {
                    $plugin_dir = trailingslashit(str_replace('\\', '/', WP_PLUGIN_DIR));
                    $plugin_relpath = str_replace($plugin_dir, '', str_replace('\\', '/', PUBLISHPRESS_STATUSES_FILE));

                    if (isset($plugins['all'][$plugin_relpath])) {
                        $plugins['all'][$plugin_relpath]['Name'] = 'PublishPress Statuses Free';
                        $plugins['all'][$plugin_relpath]['Title'] = 'PublishPress Statuses Free';
                        $plugins['all'][$plugin_relpath]['AuthorName'] = 'PublishPress';
                    }
                    return $plugins;
                }, 50
            );
            */
        }

        // Register the module with PublishPress
        
        $this->slug = 'publishpress_statuses';
        $this->name = 'publishpress_statuses';

        $this->default_options = [
            'enabled' => 1,
            'post_types' => [
                'post' => 1,
                'page' => 1,
            ],
            'default_privacy' => [
                'post' => '',
                'page' => '',
            ],
            'force_default_privacy' => [
                'post' => false,
                'page' => false,
            ],
            'lock_publication' => true,
            'supplemental_cap_moderate_any' => 0,
            'planner_add_post_custom_statuses' => 1,
            'moderation_statuses_default_by_sequence' => 0,
            'hide_manual_status_selectors' => 0,
            'status_dropdown_show_current_branch_only' => 0,
            'force_editor_detection' => '',
            'label_storage' => '',
            'pending_status_regulation' => '',
            'auto_import' => 1,
            'new_statuses_main_workflow' => 1,

            'custom_privacy_edit_caps' => defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS') ? PPS_CUSTOM_PRIVACY_EDIT_CAPS : 0,
            'quick_edit_custom_privacy_dropdown' => 1,
        ];

        $this->post_type_support_slug = 'pp_custom_statuses'; // This has been plural in all of our docs

        $args = [
            'title' => 'PublishPress Statuses',
            'short_description' => false,
            'extended_description' => false,
            'module_url' => PUBLISHPRESS_STATUSES_URL,
            'icon_class' => 'dashicons dashicons-tag',
            'slug' => 'custom-status',
            'default_options' => $this->default_options,
            'post_type_support' => 'pp_custom_statuses', // This has been plural in all of our docs
            'autoload' => false,
            'options_page' => false,
        ];

        $this->module = (object) array_merge($args, ['name' => 'custom-status']);

        $this->load_options(self::SETTINGS_SLUG);
        
        if ('none' === $this->options->moderation_statuses_default_by_sequence) {
            $this->workflow_disabled = true;

        } elseif ($this->options->moderation_statuses_default_by_sequence) {
            $this->workflow_by_sequence = true;
        }

        $this->maybeGrantPendingStatusCap();
        
        if (is_admin()) {
            // Status Administration (@todo: separate modules for Add New, Settings)
            if (isset($plugin_page) && (0 === strpos($plugin_page, 'publishpress-statuses'))) {
                add_action('pp_statuses_init', function() {
                    require_once(__DIR__ . '/StatusesUI.php');
                    \PublishPress_Statuses\StatusesUI::instance();
                });
            }
        }

        if (did_action('init')) {
            $this->init();
        } else {
            $init_priority = (defined('PUBLISHPRESS_ACTION_PRIORITY_INIT')) ? PUBLISHPRESS_ACTION_PRIORITY_INIT : 10;
            add_action('init', [$this, 'init'], $init_priority);
        }
    }

    public function useSanitizePostID($post_id = 0) {
        if (!empty($this->sanitizing_post_id)) {
            $post_id = $this->sanitizing_post_id;
        }

        return $post_id;
    }

    public function getCurrentSanitizePostID()
    {
        if (defined('PRESSPERMIT_LEGACY_POST_ID_DETECT')) {
            return 0;
        }

        if (!empty($this->sanitizing_post_id)) {
            $post_id = $this->sanitizing_post_id;
        }

        return 0;
    }

    public function fltEnsureValidStatus($data, $postarr) {
        if (is_array($data) && !empty($data['post_status'])) {
            // These are used internally to work around WordPress UI integration limitations. Ensure they are never stored to posts table.
            if ('_pending' == $data['post_status']) {
                $data['post_status'] = 'pending';
            }

            if (('_public' == $data['post_status']) || ('public' == $data['post_status']) && !defined('PP_STATUSES_ALLOW_PUBLIC_STATUS')) {
                if (!empty($data['post_date_gmt'])) {
                    $data['post_status'] = (time() < strtotime($data['post_date_gmt'] . ' +0000'))
                    ? 'future'
                    : 'publish';

                } elseif (!empty($postarr['post_date_gmt'])) {
                    $postarr['post_status'] = (time() < strtotime($postarr['post_date_gmt'] . ' +0000'))
                    ? 'future'
                    : 'publish';
                } else {
                    $postarr['post_status'] = 'publish';
                }
            }
        }

        return $data;
    }

    public function fltForcePrepublishPanel($meta_value, $object_id, $meta_key, $single, $meta_type) {
        if (('wp_persisted_preferences' != $meta_key) || defined('PP_STATUSES_NO_FORCED_PREPUBLISH') || \PublishPress_Statuses::DisabledForPostType()) {
            return $meta_value;
        }

        global $post;

        if (!empty($post)) {
            if (\PublishPress_Statuses::isPostBlacklisted($post->ID)) {
                return $meta_value;
            }
        }

        $meta_cache = wp_cache_get( $object_id, $meta_type . '_meta' );

        if ( ! $meta_cache ) {
            $meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
            if ( isset( $meta_cache[ $object_id ] ) ) {
                $meta_cache = $meta_cache[ $object_id ];
            } else {
                $meta_cache = null;
            }
        }
 
        if ( isset( $meta_cache[ $meta_key ] ) ) {
            // This is serialized by WP Core, and we have no other way to modify it.
            $meta_value = array_map( 'maybe_unserialize', $meta_cache[ $meta_key ] );   // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
        }

        if ($meta_value) {
            if (isset($meta_value['core/edit-post']) && isset($meta_value['core/edit-post'])) {
                $meta_value['core/edit-post']['isPublishSidebarEnabled'] = true;
            }

            if (isset($meta_value[0]) && isset($meta_value[0]['core/edit-post'])) {
                $meta_value[0]['core/edit-post']['isPublishSidebarEnabled'] = true;
            }
        }

        return $meta_value;
    }

    function fltDefaultPrivacy($status, $post_type) {
        if (defined('PRESSPERMIT_PRO_VERSION') && version_compare(PRESSPERMIT_PRO_VERSION, '4.6.4', '<')) {
            return $status;
        }
        
        $options = \PublishPress_Statuses::instance()->options;
        $status = (is_object($options) && !empty($options->default_privacy) && !empty($options->default_privacy[$post_type])) ? $options->default_privacy[$post_type] : '';

        return $status;
    }

    public function fltRegisterCapabilities($cme_caps) {
        $cme_caps['PublishPress Statuses'] = ['pp_manage_statuses', 'pp_bypass_status_sequence'];

        $options = \PublishPress_Statuses::instance()->options;

        if (!empty($options) && !empty($options->lock_publication)) {
            $cme_caps['PublishPress Statuses'] []= 'pp_unpublish_posts';
        }

        return $cme_caps;
    }

    function fltCapDescriptions($pp_caps)
    {
        $pp_caps['pp_bypass_status_sequence'] = esc_html__('Can bypass normal status progression. User can publish immediately or move post to the furthest possible status.', 'publishpress-statuses');
        $pp_caps['pp_manage_statuses'] = esc_html__('Manage Statuses plugin settings.', 'publishpress-statuses');

        $options = \PublishPress_Statuses::instance()->options;

        if (!empty($options) && !empty($options->lock_publication)) {
            $pp_caps['pp_unpublish_posts'] = esc_html__('Can unpublish posts even if Visibility Status is locked.', 'publishpress-statuses');
        }

        return $pp_caps;
    }

    /**
     * Initialize the PP_Custom_Status class if the module is active
     */
    public function init()
    {
        global $pagenow;

        static $done;

        if (!empty($done)) { // Avoid redundant execution with Post Editor
            return;
        } else {
            $done = true;
        }

        if (empty($this->version)) {
            $this->load();
        }

        // Register new taxonomy so that we can store all our fancy new custom statuses (or is it stati?)
        if (!taxonomy_exists(self::TAXONOMY_PRE_PUBLISH)) {
            register_taxonomy(
                self::TAXONOMY_PRE_PUBLISH,
                'post',
                [
                    'hierarchical'          => false,
                    'label'                 => __('Statuses', 'publishpress-statuses'),
                    'labels'                => (object) ['name' => __('Statuses', 'publishpress-statuses'), 'singular_name' => __('Status', 'publishpress-statuses')],
                    'query_var'             => false,
                    'rewrite'               => false,
                    'show_ui'               => false,
                ]
            );
        }

        if (! taxonomy_exists(self::TAXONOMY_CORE_STATUS)) {
            register_taxonomy(
                self::TAXONOMY_CORE_STATUS,
                [],
                [
                    'hierarchical' => false,
                    'label' => __('Core Post Statuses', 'publishpress-statuses'),
                    'query_var' => false,
                    'rewrite' => false,
                    'show_ui' => false,
                ]
            );
        }

        if (self::isStatusManagement()) {
            if (! taxonomy_exists(self::TAXONOMY_PSEUDO_STATUS)) {
                register_taxonomy(
                    self::TAXONOMY_PSEUDO_STATUS,
                    [],
                    [
                        'hierarchical' => false,
                        'label' => 'Pseudo Statuses',
                        'query_var' => false,
                        'rewrite' => false,
                        'show_ui' => false,
                    ]
                );
            }
        } else {
            $disable_statuses = self::disable_custom_statuses_for_post_type(null, ['override_exemptions' => true]);
        }

        do_action('publishpress_statuses_register_taxonomies');
        do_action('publishpress_statuses_pre_init');

        $statuses = $this->getPostStatuses([], 'object', ['context' => 'load']);

        add_action('admin_init', function() {
            $this->getPostStatuses();
        });

        // Register custom statuses, which are stored as taxonomy terms
        $this->register_moderation_statuses($statuses);

        // @todo: check for disable
        // Register visibility statuses, which are stored as taxonomy terms
        $this->register_visibility_statuses($statuses);

        $this->set_core_status_properties($statuses);

        require_once(__DIR__ . '/Workarounds.php');
        new \PublishPress_Statuses\Workarounds();

        if (is_admin()) {
            if ($activated = get_option('publishpress_statuses_activate')) {
                delete_option('publishpress_statuses_activate');
            }

            // WordPress Dashboard integration
            require_once(__DIR__ . '/Admin.php');
            new \PublishPress_Statuses\Admin($activated);

            if (empty($disable_statuses)) {
                // Implementation for Posts screen, Post Editor
                if (!empty($pagenow) && in_array($pagenow, ['edit.php', 'post.php', 'post-new.php'])) {
                    require_once(__DIR__ . '/PostsListing.php');
                    new \PublishPress_Statuses\PostsListing();
                }

                // Scripts for Posts Editor only
                if (!empty($pagenow) && in_array($pagenow, ['post.php', 'post-new.php'])) {
                    require_once(__DIR__ . '/PostEdit.php');
                    new \PublishPress_Statuses\PostEdit();
                }
            }
        }

        $this->maybeGrantPendingStatusCap();

        do_action('pp_statuses_init');

        if (is_admin() && $activated) {
            wp_safe_redirect(admin_url("admin.php?page=publishpress-statuses"));
            exit;
        }
    }

    function maybeGrantPendingStatusCap() {
        global $current_user;

        if (!\PublishPress_Statuses::instance()->options->pending_status_regulation) {
            if (!empty($current_user) && !empty($current_user->allcaps['read'])) {
                // post editing capability will be checked separately; this just prevents an additional requirement
                $current_user->allcaps['status_change_pending'] = true;
            }
        } elseif (current_user_can('administrator')) {
            $current_user->allcaps['status_change_pending'] = true;
        }
    }

    // Capability filter applied by WP_User->has_cap (usually via WP current_user_can function)
    //
    // $wp_sitecaps = current user's site-wide capabilities
    // $reqd_caps = primitive capabilities being tested / requested
    // $args = array with:
    //      $args[0] = original capability requirement passed to current_user_can (possibly a meta cap)
    //      $args[1] = user being tested
    //      $args[2] = post id (could be a post_id, link_id, term_id or something else)
    //
    public function fltUserHasCap($wp_sitecaps, $orig_reqd_caps, $args)
    {
        $args = (array)$args;
        $orig_cap = (isset($args[0])) ? sanitize_key($args[0]) : '';

        if (isset($args[2]) && is_object($args[2])) {
            $args[2] = (isset($args[2]->ID)) ? $args[2]->ID : 0;
        }

        $post_id = (isset($args[2])) ? (int) $args[2] : 0;

        if ($post_id && in_array($orig_cap, ['edit_post', 'edit_page', 'delete_post', 'delete_page'])) {

            // Block users who who cannot set a status from editing or deleting posts of that status either, 
            // unless another plugin is applying a different status capability model.
            if (!defined('PRESSPERMIT_STATUSES_VERSION') && apply_filters('publishpress_statuses_postmeta_cap_check', true, $post_id, $args)) {
                if (!self::isContentAdministrator()) {
                    if ($_post = get_post($post_id)) {
                        if (!in_array($_post->post_status, ['draft', 'auto-draft', 'future']) && in_array($_post->post_status, $this->all_moderation_statuses)) {
                            if (!\PublishPress_Statuses::haveStatusPermission('set_status', $_post->post_type, $_post->post_status)) {
                                $wp_sitecaps = array_diff_key(
                                    $wp_sitecaps,
                                    array_fill_keys($orig_reqd_caps, true)
                                );
                            }
                        }
                    }
                }
            }
        }

        return $wp_sitecaps;
    }

    /**
     * Handle an ajax request to update the order of custom statuses
     *
     * @since 0.7
     */
    public function handle_ajax_update_status_positions()
    {
        require_once(__DIR__ . '/StatusHandler.php');
        \PublishPress_Statuses\StatusHandler::handleAjaxUpdateStatusPositions();
    }

    public function handle_ajax_delete_custom_status()
    {
        require_once(__DIR__ . '/StatusHandler.php');
        \PublishPress_Statuses\StatusHandler::handleAjaxDeleteStatus();
    }

    public function get_ajax_selectable_statuses()
    {
        if (!empty($_REQUEST['post_id'])) {
            if (!wp_verify_nonce(\PP_Statuses_Functions::POST_key('pp_nonce'),'pp-custom-statuses-nonce')) {
                exit;
            }

            if (!current_user_can('edit_post', intval($_REQUEST['post_id']))) {
                exit;
            }

            $post_id = (int) $_REQUEST['post_id'];
            require_once(__DIR__ . '/PostEdit.php');

            $args = [];
            $params = [];

            $_post = get_post($post_id);

            if (!empty($_REQUEST['selected_status']) && ('auto-draft' != $_REQUEST['selected_status'])) {
                $args['post_status'] = sanitize_key($_REQUEST['selected_status']);

                // @todo: separate ajax call for setting status
                if ($status_obj = get_post_status_object($args['post_status'])) {
                    if ($_post) {

                        if (($_post->post_status != $args['post_status'])
                        && \PublishPress_Statuses::haveStatusPermission('set_status', $_post->post_type, $status_obj->name)
                        ) {
                            wp_update_post(
                            	['ID' => $post_id, 
                            	apply_filters('publishpress_statuses_status_field', 'post_status', $post_id) => $status_obj->name
                            	]
                            );
                        }

                        $next_status_obj = \PublishPress_Statuses::getNextStatusObject(0, ['default_by_sequence' => true, 'post_type' => $_post->post_type, 'post_status' => $status_obj->name]);
                        $max_status_obj = \PublishPress_Statuses::getNextStatusObject($post_id, ['default_by_sequence' => false, 'post_type' => $_post->post_type, 'post_status' => $status_obj->name]);

                        if ($next_status_obj) {
                            $params = [
                                'nextStatus' => $next_status_obj->name
                            ];
                        } else {
                            $params = [
                                'nextStatus' => $status_obj->name
                            ];
                        }

                        if ($max_status_obj) {
                            $params['maxStatus'] = $max_status_obj->name;
                        }
                    }
                }
            }

            $statuses = array_keys(\PublishPress_Statuses\Admin::get_selectable_statuses($post_id, $args));

            if (!empty($_post)) {
                if ($type_obj = get_post_type_object($_post->post_type)) {
                    if (!empty($type_obj->cap->publish_posts) && current_user_can($type_obj->cap->publish_posts)) {
                        $statuses[] = 'publish';
                    }
                }
            }

            \PP_Statuses_Functions::printAjaxResponse('success', '', $statuses, $params);
        } else {
            \PP_Statuses_Functions::printAjaxResponse('success', '', [], []);
        }

        exit;
    }

    public function set_workflow_action($action) {
        global $current_user;

        if ($post_id = \PP_Statuses_Functions::REQUEST_int('post_id')) {
            if ($workflow_action = \PP_Statuses_Functions::REQUEST_key('workflow_action')) {
                if (!wp_verify_nonce(\PP_Statuses_Functions::POST_key('pp_nonce'),'pp-custom-statuses-nonce')) {
                    exit;
                }

                if (!current_user_can('edit_post', $post_id)) {
                    exit;
                }

                update_user_meta($current_user->ID, "_pp_statuses_workflow_action_" . $post_id, sanitize_key($workflow_action));

                \PP_Statuses_Functions::printAjaxResponse('success', '', [], []);
            }
        }
    }

    public static function isStatusManagement() {
        $plugin_page = \PP_Statuses_Functions::getPluginPage();
        
        return
            in_array($plugin_page, ['publishpress-statuses', 'pp-capabilities'])
            || (
                isset($_SERVER['SCRIPT_NAME']) 
                && false !== strpos(sanitize_text_field(wp_unslash($_SERVER['SCRIPT_NAME'])), 'admin-ajax.php') 
                && \PP_Statuses_Functions::is_REQUEST('action', ['pp_update_status_positions', 'pp_statuses_toggle', 'pp_delete_custom_status'])
            );
    }

    protected function get_core_statuses($args = []) {
        global $wp_post_statuses;

        $statuses = [
            'draft' =>  (object) [
                'default_label' => 'Draft',
                'label' => !empty($wp_post_statuses['draft']) && !empty($wp_post_statuses['draft']->label) ? $wp_post_statuses['draft']->label : __('Draft'),
                'default_labels' => (object) [
                    'save_as' => 'Save Draft'
                ],
                'labels' => (object) [
                    'save_as' => __('Save Draft')
                ],
                'description' => __('New post, not yet submitted.', 'publishpress-statuses'),
                'default_description' => 'New post, not yet submitted.',
                'color' => '#767676',
                'icon' => 'dashicons-media-default',
                'position' => 0,
                'order' => 0,
                '_builtin' => true,
                'protected' => true,
            ],

            'pending' => (object) [
                'default_label' => 'Pending Review',
                'label' => !empty($wp_post_statuses['pending']) && !empty($wp_post_statuses['pending']->label) && ('Pending' != $wp_post_statuses['pending']->label) ? $wp_post_statuses['pending']->label : __('Pending Review'),
                'label_friendly' => !empty($wp_post_statuses['pending']) && !empty($wp_post_statuses['pending']->label) && ('Pending' != $wp_post_statuses['pending']->label) ? $wp_post_statuses['pending']->label : __('Pending Review'),
                'default_labels' => (object) [
                    'save_as' => 'Save as Pending',
                    'publish' => 'Submit for Review'
                ],
                'labels' => (object) [
                    'save_as' => __('Save as Pending'),
                    'publish' => __('Submit for Review')
                ],
                'description' => __('Post is awaiting review.', 'publishpress-statuses'),
                'default_description' => 'Post is awaiting review.',
                'color' => '#b95c00',
                'icon' => 'dashicons-clock',
                'position' => 4,
                'order' => 200,
                '_builtin' => true,
                'moderation' => true,
                'protected' => true,    // this is also applied as a default property for all moderation statuses
            ],

            'future' => (object) [
                'default_label' => 'Scheduled',
                'label' => !empty($wp_post_statuses['future']) && !empty($wp_post_statuses['future']->label) ? $wp_post_statuses['future']->label : __('Scheduled'),
                'default_labels' => (object) [
                    'publish' => 'Schedule'
                ],
                'labels' => (object) [
                    'publish' => __('Schedule')
                ],
                'description' => __('Post is scheduled for publication.', 'publishpress-statuses'),
                'default_description' => 'Post is scheduled for publication.',
                'color' => '#8440f0',
                'icon' => 'dashicons-calendar-alt',
                'position' => 7,
                'order' => 700,
                '_builtin' => true,
                'protected' => true,
            ],

            'publish' => (object) [
                'default_label' => 'Published',
                'label' => !empty($wp_post_statuses['publish']) && !empty($wp_post_statuses['publish']->label) ? $wp_post_statuses['publish']->label : __('Published'),
                'default_labels' => (object) [
                    'publish' => 'Publish'
                ],
                'labels' => (object) [
                    'publish' => __('Publish')
                ],
                'description' => __('Post is published, publicly visible.', 'publishpress-statuses'),
                'default_description' => 'Post is published, publicly visible.',
                'color' => '#207720',
                'icon' => 'dashicons-yes',
                'position' => 8,
                'order' => 800,
                '_builtin' => true,
                'public' => true,
            ],

            'private' => (object) [
                'default_label' => 'Private',
                'label' => !empty($wp_post_statuses['private']) && !empty($wp_post_statuses['private']->label) ? $wp_post_statuses['private']->label : __('Private'),
                'default_labels' => (object) [
                    'publish' => 'Save'
                ],
                'labels' => (object) [
                    'publish' => __('Save')
                ],
                'description' => __('Post is published with private visibility.', 'publishpress-statuses'),
                'default_description' => 'Post is published with private visibility.',
                'color' => '#b40000',
                'icon' => 'dashicons-lock',
                'position' => 9,
                'order' => 900,
                '_builtin' => true,
                'private' => true,
            ]
        ];

        foreach (array_keys($statuses) as $slug) {
            $statuses[$slug]->name = $slug;
            $statuses[$slug]->slug = $slug;  // @todo: eliminate in favor of name?
        
            $statuses[$slug]->taxonomy = self::TAXONOMY_CORE_STATUS;
            $statuses[$slug]->disabled = false;
        }

        return $statuses;
    }

    // deprecated
    protected function get_default_statuses($taxonomy, $args = []) {
        static $all_statuses;

        if (self::TAXONOMY_CORE_STATUS == $taxonomy) {
            return $this->get_core_statuses();
        } else {
            if (!empty($all_statuses)) {
                $all_statuses = $this->get_all_default_statuses();
            }

            return (!empty($all_statuses[$taxonomy])) ? $all_statuses[$taxonomy] : [];
        }
    }

    /**
     * @return array
     */
    protected function get_all_default_statuses($args = []) {
        $all_statuses = [];
        
        $default_alternate_position = 6;

        if ($stored_positions = (array) get_option('publishpress_status_positions')) {
            if ($pos = array_search('_pre-publish-alternate', $stored_positions)) {
                $default_alternate_position = $pos;
            }
        }

        $taxonomy = self::TAXONOMY_CORE_STATUS;
        $all_statuses[$taxonomy] = $this->get_core_statuses();
        

        $taxonomy = self::TAXONOMY_PRE_PUBLISH;

        $statuses = [
            'pitch' => (object) [
                'default_label' => 'Pitch',
                'label' => __('Pitch', 'publishpress-statuses'),
                'default_labels' => (object) ['publish' => 'Pitch'],
                'labels' => (object) ['publish' => _x('Pitch', 'post action/button label', 'publishpress-statuses')],
                'description' => __('Idea proposed; waiting for acceptance.', 'publishpress-statuses'),
                'default_description' => 'Idea proposed; waiting for acceptance.',
                'color' => '#887618',
                'icon' => 'dashicons-lightbulb',
                'position' => 1,
                'order' => 100,
                'moderation' => true,
                'pp_builtin' => true,
            ],

            'assigned' => (object) [
                'default_label' => 'Assigned',
                'label' => __('Assigned', 'publishpress-statuses'),
                'default_labels' => (object) ['publish' => 'Assign'],
                'labels' => (object) ['publish' => __('Assign', 'publishpress-statuses')],
                'description' => __('Post idea assigned to writer.', 'publishpress-statuses'),
                'default_description' => 'Post idea assigned to writer.',
                'color' => '#009ba0',
                'icon' => 'dashicons-admin-users',
                'position' => 2,
                'order' => 200,
                'moderation' => true,
                'pp_builtin' => true,
            ],

            'in-progress' => (object) [
                'default_label' => 'In Progress',
                'label' => __('In Progress', 'publishpress-statuses'),
                'default_labels' => (object) ['publish' => 'Mark In Progress'],
                'labels' => (object) ['publish' => __('Mark In Progress', 'publishpress-statuses')],
                'description' => __('Writer is working on the post.', 'publishpress-statuses'),
                'default_description' => 'Writer is working on the post.',
                'color' => '#8c5400',
                'icon' => 'dashicons-performance',
                'position' => 3,
                'order' => 225,
                'moderation' => true,
                'pp_builtin' => true,
            ],

            'approved' => (object) [
                'default_label' => 'Approved',
                'label' => __('Approved', 'publishpress-statuses'),
                'default_labels' => (object) ['publish' => 'Approve'],
                'labels' => (object) ['publish' => __('Approve', 'publishpress-statuses')],
                'description' => __('Post has been approved for publication.', 'publishpress-statuses'),
                'default_description' => 'Post has been approved for publication.',
                'color' => '#304baa',
                'icon' => 'dashicons-yes-alt',
                'position' => 5,
                'order' => 250,
                'moderation' => true,
                'pp_builtin' => true,
            ],

            'needs-work' => (object) [
                'default_label' => 'Needs Work',
                'label' => __('Needs Work', 'publishpress-statuses'),
                'default_labels' => (object) ['publish' => 'Set to Needs Work'],
                'labels' => (object) ['publish' => __('Set to Needs Work', 'publishpress-statuses')],
                'description' => __('Post needs work before further review.', 'publishpress-statuses'),
                'default_description' => 'Post needs work before further review.',
                'color' => '#A88F8D',
                'icon' => 'dashicons-image-crop',
                'position' => $default_alternate_position + 2,
                'order' => 290,
                'moderation' => true,
                'pp_builtin' => true,
            ],

            'rejected' => (object) [
                'default_label' => 'Rejected',
                'label' => __('Rejected', 'publishpress-statuses'),
                'default_labels' => (object) ['publish' => 'Reject'],
                'labels' => (object) ['publish' => __('Reject', 'publishpress-statuses')],
                'description' => __('Post has been rejected.', 'publishpress-statuses'),
                'default_description' => 'Post has been rejected.',
                'color' => '#6b0000',
                'icon' => 'dashicons-thumbs-down',
                'position' => $default_alternate_position + 3,
                'order' => 290,
                'moderation' => true,
                'pp_builtin' => true,
            ],
        ];

        $statuses = apply_filters('publishpress_statuses_default_prepub_statuses', $statuses);

        // Basic validation of filtered entries. Default properties will be applied downstream.
        foreach ($statuses as $status_name => $status_obj) {
            if (is_array($status_obj)) {
                $statuses[$status_name] = (object) $status_obj;
            }

            if ((sanitize_key($status_name) != $status_name)
            || !is_object($status_obj)
            ) {
                unset($statuses[$status_name]);
                continue;
            }
        }

        $all_statuses[$taxonomy] = $statuses;

        $taxonomy = self::TAXONOMY_PSEUDO_STATUS;

        $statuses = [
            // [Fake status to support organization by table position re-ordering]: "Pre-Publication Statuses:"
            '_pre-publish-alternate' => (object) [
                'label' => __('Alternate Pre-Publication Workflow:', 'publishpress-statuses'),
                'description' => '',
                'class' => 'alternate-moderation-status',
                'color' => '',
                'icon' => '',
                'position' => $default_alternate_position,
                'order' => 300,
                'moderation' => true,
                'alternate' => true,
                'disabled' => false,
            ]
        ];

        $all_statuses[$taxonomy] = $statuses;


        $taxonomy = self::TAXONOMY_PRIVACY;
        $statuses = apply_filters('publishpress_statuses_get_default_statuses', [], $taxonomy);

        // cover for older versions of Permissions Pro
        if (!empty($statuses['member']) && !empty($statuses['member']->private) && !empty($statuses['member']->pp_builtin)) {
            $statuses['member']->default_label = 'Member';
        }

        if (!empty($statuses['premium']) && !empty($statuses['premium']->private) && !empty($statuses['premium']->pp_builtin)) {
            $statuses['premium']->default_label = 'Premium';
        }

        if (!empty($statuses['staff']) && !empty($statuses['staff']->private) && !empty($statuses['staff']->pp_builtin)) {
            $statuses['staff']->default_label = 'Staff';
        }

        $all_statuses[$taxonomy] = $statuses;
    

        $all_statuses = apply_filters('publishpress_statuses_default_statuses', $all_statuses);


        // Account for filter-inserted statuses (ensuring they are not defaulted to a position beyond default disabled position)
        $max_pos = 0;
        $status_count = 0;

        foreach ($all_statuses as $taxonomy => $_statuses) {
            $status_count += count($all_statuses[$taxonomy]);

            foreach (array_keys($_statuses) as $status) {
                if (!empty($_statuses[$status]->position) && empty($_statuses[$status]->disabled)) {
                    if ($_statuses[$status]->position > $max_pos) {
                        $max_pos = $_statuses[$status]->position;
                    }
                }
            }
        }
        
        $default_disabled_position = max($status_count, $max_pos) + 1;

        if ($stored_positions = (array) get_option('publishpress_status_positions')) {
            if ($stored_disabled_pos = array_search('_disabled', $stored_positions)) {
                $disabled_pos = $stored_disabled_pos;
                
                // If stored disabled position is invalid, fix it
                if ($rev_pos = array_search('_revision-workflow', $stored_positions)) {
                    if ($rev_pos > $stored_disabled_pos) {
                        if ($alternate_rev_pos = array_search('_revision-alternate', $stored_positions)) {
                            if ($alternate_rev_pos > $stored_disabled_pos) {
                                $disabled_pos = $alternate_rev_pos + 1;
        
                                $rev_status_count = $alternate_rev_pos - $rev_pos - 1;
                                $alt_rev_status_count = count($stored_positions) - $alternate_rev_pos - 1;
                                $disabled_count = $rev_pos - $stored_disabled_pos - 1;

                                $stored_positions = array_merge(
                                    array_slice($stored_positions, 0, $stored_disabled_pos),
                                    array_slice($stored_positions, $rev_pos, $rev_status_count + 1),
                                    array_slice($stored_positions, $alternate_rev_pos, $alt_rev_status_count + 1),
                                    ['_disabled'],
                                    array_slice($stored_positions, $stored_disabled_pos + 1, $disabled_count)
                                );

                                update_option('publishpress_status_positions', $stored_positions);

                                $disabled_pos = $alternate_rev_pos + 1;
                            }
                        }
                    }
                }

                if ($disabled_pos > $default_disabled_position) {
                    $default_disabled_position = $disabled_pos;
                }
            }
        }

        // [Fake status to support organization by table position re-ordering]: "Disabled Statuses:"
        $all_statuses['pseudo_status_pp']['_disabled'] = (object) [
            'label' => __('Disabled Statuses:', 'publishpress-statuses'),
            'description' => '',
            'class' => 'disabled-status',
            'color' => '',
            'icon' => '',
            'position' => $default_disabled_position,
            'order' => 300,
            'moderation' => false,
            'disabled' => true,
        ];
        
        $taxonomy = self::TAXONOMY_PRE_PUBLISH;

        $statuses = [
            'deferred' => (object) [
                'default_label' => 'Deferred',
                'label' => __('Deferred', 'publishpress-statuses'),
                'default_labels' => (object) ['publish' => 'Defer'],
                'labels' => (object) ['publish' => __('Defer', 'publishpress-statuses')],
                'description' => __('Post has been deferred for future consideration.', 'publishpress-statuses'),
                'default_description' => 'Post has been deferred for future consideration.',
                'color' => '#9b9b9b',
                'icon' => 'dashicons-coffee',
                'position' => $default_disabled_position + 1,
                'order' => 280,
                'moderation' => true,
                'pp_builtin' => true,
            ],

            'committee' => (object) [
                'default_label' => 'Committee',
                'label' => __('Committee', 'publishpress-statuses'),
                'default_labels' => (object) ['publish' => 'Refer to Committee'],
                'labels' => (object) ['publish' => __('Refer to Committee', 'publishpress-statuses')],
                'description' => __('Post has been referred to committee.', 'publishpress-statuses'),
                'default_description' => 'Post has been referred to committee.',
                'color' => '#791bb7',
                'icon' => 'dashicons-welcome-learn-more',
                'position' => $default_disabled_position + 2,
                'order' => 350,
                'moderation' => true,
                'pp_builtin' => true,
                'status_parent' => 'committee',
            ],

            'committee-review' => (object) [
                'default_label' => 'Committee Review',
                'label' => __('Committee Review', 'publishpress-statuses'),
                'default_labels' => (object) ['publish' => 'Set to Committee Review'],
                'labels' => (object) ['publish' => __('Set to Committee Review', 'publishpress-statuses')],
                'description' => __('Committee is reviewing the post.', 'publishpress-statuses'),
                'default_description' => 'Committee is reviewing the post.',
                'color' => '#ba7925',
                'icon' => 'dashicons-search',
                'position' => $default_disabled_position + 3,
                'order' => 352,
                'moderation' => true,
                'pp_builtin' => true,
                'status_parent' => 'committee',
            ],

            'committee-progress' => (object) [
                'default_label' => 'Committee Progress',
                'label' => __('Committee Progress', 'publishpress-statuses'),
                'default_labels' => (object) ['publish' => 'Set to Committee Progress'],
                'labels' => (object) ['publish' => __('Set to Committee Progress', 'publishpress-statuses')],
                'description' => __('Committee is editing the post.', 'publishpress-statuses'),
                'default_description' => 'Committee is editing the post.',
                'color' => '#A8902B',
                'icon' => 'dashicons-format-chat',
                'position' => $default_disabled_position + 4,
                'order' => 354,
                'moderation' => true,
                'pp_builtin' => true,
                'status_parent' => 'committee',
            ],

            'committee-approved' => (object) [
                'default_label' => 'Committee Approved',
                'label' => __('Committee Approved', 'publishpress-statuses'),
                'default_labels' => (object) ['publish' => 'Set Committee Approval'],
                'labels' => (object) ['publish' => __('Set Committee Approval', 'publishpress-statuses')],
                'description' => __('Committee has approved the post.', 'publishpress-statuses'),
                'default_description' => 'Committee has approved the post.',
                'color' => '#22a522',
                'icon' => 'dashicons-editor-break',
                'position' => $default_disabled_position + 5,
                'order' => 358,
                'moderation' => true,
                'pp_builtin' => true,
                'status_parent' => 'committee',
            ],
        ];

        foreach(array_keys($statuses) as $slug) {
            if (!empty($disabled_pos)) {
                if ($stored_pos = array_search($slug, $stored_positions)) {
                    if ($stored_pos < $disabled_pos) {
                        continue;
                    }
                }
            }

            $statuses[$slug]->disabled = true;
        }

        $all_statuses[$taxonomy] = array_merge($all_statuses[$taxonomy], $statuses);

        foreach (array_keys($all_statuses) as $taxonomy) {
            foreach (array_keys($all_statuses[$taxonomy]) as $slug) {
                $all_statuses[$taxonomy][$slug]->name = $slug;
                $all_statuses[$taxonomy][$slug]->slug = $slug;  // @todo: eliminate in favor of name?
                
                $all_statuses[$taxonomy][$slug]->taxonomy = $taxonomy;

                if (!isset($all_statuses[$taxonomy][$slug]->disabled)) {
                    $all_statuses[$taxonomy][$slug]->disabled = false;
                }
            }
        }

        return $all_statuses;
    }

    private function apply_default_status_properties($status) {
        foreach (['public', 'private', 'moderation', 'label', 'description', 'disabled'] as $prop) {
            if (!isset($status->$prop)) {
                switch ($prop) {
                    case 'label':
                        $status->$prop = $slug;
                        break;

                    case 'public':
                    case 'private':
                    case 'moderation':
                    case 'disabled':
                        $status->$prop = false;
                        break;

                    default:
                        $status->$prop = '';
                }
            }
        }

        if (is_admin()) {
            require_once(__DIR__ . '/Admin.php');

            $status = \PublishPress_Statuses\Admin::set_status_labels($status);
        }

        return $status;
    }

    private function set_core_status_properties($statuses) {
        global $wp_post_statuses;
        
        foreach ($statuses as $status) {
            if (in_array($status->name, ['pending'])) {
                foreach (['capability_status'] as $prop) {
                    if (!empty($status->$prop)) {
                        $wp_post_statuses[$status->name]->$prop = $status->$prop;
                    }
                }
            }
        }

        foreach ($wp_post_statuses as $k => $status) {
            if (empty($status->labels)) {
                $wp_post_statuses[$k]->labels = (object) array();
                $wp_post_statuses[$k]->labels->name = $wp_post_statuses[$k]->label;
            }
        }

        if (is_admin()) {
            $wp_post_statuses['publish']->labels->publish = esc_attr(__('Publish'));
            $wp_post_statuses['future']->labels->publish = esc_attr(_x('Schedule', 'post action/button label'));
    
            if (empty($wp_post_statuses['pending']->labels->publish)) {
                $wp_post_statuses['pending']->labels->save_as = esc_attr(__('Save as Pending'));
                $wp_post_statuses['pending']->labels->publish = esc_attr(__('Submit for Review'));
            }

            $wp_post_statuses['draft']->labels->save_as = esc_attr(__('Save Draft'));
            $wp_post_statuses['draft']->labels->publish = esc_attr(__('Save Draft'));
    
            if (empty($wp_post_statuses['pending']->labels->caption)) {
                $wp_post_statuses['pending']->labels->caption = $wp_post_statuses['pending']->label;
            }

            $wp_post_statuses['private']->labels->caption = __('Privately Published');
        }
    }

    /**
     * Makes the call to register_post_status to register the user's custom statuses.
     */
    private function register_moderation_statuses($statuses)
    {
        global $wp_post_statuses;

        if (function_exists('register_post_status')) {
            foreach ($statuses as $status) {
                // Ignore visibility statues and all core statuses, which are registered elsewhere.
                if (!empty($status->_builtin) || !empty($status->public) || !empty($status->private) 
                || in_array($status->slug, ['publish', 'private', 'pending', 'draft', 'future'])
                || !empty($status->disabled)
                || in_array($status->slug, ['_pre-publish-alternate', '_disabled'])
                || apply_filters('publishpress_statuses_skip_status_registration', false, $status->slug)
                ) {
                    continue;
                }

                $postStatusArgs = $this->moderation_status_properties($status);

                if (empty($postStatusArgs['taxonomy'])) {
                    $postStatusArgs['taxonomy'] = 'post_status';
                }

                register_post_status($status->slug, $postStatusArgs);
            }
        }
    }

    private function moderation_status_properties($status) {
        $label = (!empty($status->label)) ? $status->label : $status->name;
        
        return apply_filters(
            'publishpress_statuses_status_properties',
            [
                'label' => $label,
                'protected' => true,
                'date_floating' => true,
                '_builtin' => false,
                'pp_builtin' => !empty($status->pp_builtin),
                'moderation' => !empty($status->moderation),
                'alternate' => !empty($status->alternate),
                'disabled' => !empty($status->disabled),
                'status_parent' => !empty($status->status_parent) ? $status->status_parent : '',
                'post_type' => (!empty($status->post_type)) ? $status->post_type : [],
                'icon' => (!empty($status->icon)) ? $status->icon : '',
                'color' => (!empty($status->color)) ? $status->color : '',
                'labels' => (!empty($status->labels)) ? $status->labels : (object) ['publish' => '', 'save_as' => '', 'name' => $label],
                'label_count' => _n_noop(
                    "{$status->label} <span class='count'>(%s)</span>",
                    "{$status->label} <span class='count'>(%s)</span>"
                ),
            ],
            $status
        );
    }

    private function apply_moderation_status_properties($status) {
        foreach ($this->moderation_status_properties($status) as $prop =>$val) {
            if (!isset($status->$prop)) {
                $status->$prop = $val;
            }

			// Safeguard for plugin API
            $status->moderation = true;
            $status->protected = true;
        }

        return $status;
    }

    /**
     * Makes the call to register_post_status to register the user's post visibility statuses.
     *
     * @param array $args
     */
    private function register_visibility_statuses($statuses)
    {
        do_action('publishpress_statuses_register_visibility_statuses', $statuses);
    }

    public static function visibility_status_properties($status) {
        $label = (!empty($status->label)) ? $status->label : $status->name;
        
        return [
            'label' => $label,
            'date_floating' => true,
            '_builtin' => false,
            'pp_builtin' => false,
            'private' => true,
            'post_type' => (!empty($status->post_type)) ? $status->post_type : [],
            'labels' => (!empty($status->labels)) ? $status->labels : (object) ['publish' => '', 'save_as' => '', 'name' => $label],
            'label_count' => _n_noop(
                "{$status->label} <span class='count'>(%s)</span>",
                "{$status->label} <span class='count'>(%s)</span>"
            ),
        ];
    }

    private function apply_visibility_status_properties($status) {
        foreach (self::visibility_status_properties($status) as $prop =>$val) {
            if (!isset($status->$prop)) {
                $status->$prop = $val;
            }

			// Safeguard for plugin API
            $status->private = true;
        }

        return $status;
    }

    public static function getCurrentPostType() {
        return \PP_Statuses_Functions::getPostType();
    }

    public static function DisabledForPostType($post_type = null) {
        return self::disable_custom_statuses_for_post_type($post_type);
    }

    public static function getEnabledPostTypes() {
        return self::instance()->get_enabled_post_types();
    }

    public static function isUnknownStatus($post_status) {
        if (!in_array($post_status, ['auto-draft', 'draft', 'pending', 'publish', 'private', 'future'])) {
            $statuses = self::instance()->getPostStatuses([], 'names');

            if (!in_array($post_status, $statuses)) {
                return true;
            }
        }

        return false;
    }

    public static function isPostBlacklisted($post_id) {
        // Don't load our scripts if the post has one of these meta values
        $post_meta_blacklist = (array) apply_filters(
            'publishpress_statuses_postmeta_blacklist', 
            ['_dp_is_rewrite_republish_copy', '_dp_has_rewrite_republish_copy']
        );

        foreach ($post_meta_blacklist as $post_meta_key) {
            if (is_scalar($post_meta_key)) {
	            if (get_post_meta($post_id, $post_meta_key, true)) {
	                return true;
	            }
	    	}
        }

        return false;
    }

    /**
     * Whether custom post statuses should be disabled for this post type.
     * Used to stop custom statuses from being registered for post types that don't support them.
     *
     * @return bool
     * @since 0.7.5
     */
    public static function disable_custom_statuses_for_post_type($post_type = null, $args = [])
    {
        // Only allow deregistering on 'edit.php' and 'post.php'
        if (self::isStatusManagement()) {
            return false;
        }

        if (is_null($post_type)) {
            if (defined('DOING_AJAX') && DOING_AJAX) {
                return false;
            }

            $post_type = self::getCurrentPostType();
        }

        // Always allow for the notification workflows
        if (defined('PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW') && empty($args['override_exemptions'])) {
            if (PUBLISHPRESS_NOTIF_POST_TYPE_WORKFLOW === $post_type) {
                return false;
            }
        }

        if ($post_type && !in_array($post_type, self::getEnabledPostTypes())) {
            return true;
        }

        return false;
    }

    public static function get_status_property($status, $property)
    {
        if (! isset($status->$property)) {
            if ($property === 'color') {
                $value = self::DEFAULT_COLOR;
            } else {
                $value = '';
            }
        } else {
            $value = $status->$property;
        }

        return $value;
    }

    /**
     * Get core's 'draft' and 'pending' post statuses, but include our special attributes
     *
     * @return array
     * @since 0.8.1
     *
     */
    protected function getCorePostStatuses($return_args = [])
    {
        global $wp_post_statuses;

        // $return_args: return array structure
        //
        // support get_post_stati() argument structure
        if (is_string($return_args)) {
            $return_args = ['output' => $return_args];

        } elseif (!is_array($return_args)) {
            $return_args = [];
        }

        foreach (
            [
                'output' => 'names',
                'return_key' => 'name'
            ] 
        as $prop => $default_val) {
            if (!isset($return_args[$prop])) {
                $return_args[$prop] = $default_val;
            }
        }

        $statuses = self::get_default_statuses(self::TAXONOMY_CORE_STATUS);

        foreach (array_keys($statuses) as $status_name) {
            if (!is_object($statuses[$status_name])) {
                continue;
            }

            $statuses[$status_name]->slug = $status_name;
            $statuses[$status_name]->name = $status_name;

            $statuses[$status_name] = $this->apply_default_status_properties($statuses[$status_name]);

            $status_name = $statuses[$status_name]->name;

            foreach (get_object_vars($statuses[$status_name]) as $prop => $val) {
                if (isset($wp_post_statuses[$status_name])) {
                    if (!isset($wp_post_statuses[$status_name]->$prop)) {
                        $wp_post_statuses[$status_name]->$prop = $val;
                    }
                }
            }
        }

        return ($return_args['output'] == 'object') ? $statuses : array_keys($statuses);
    }

    public static function isDefaultLabel($status, $caption, $property = 'label') {
        if (!is_object($status)) {
            if (!$status_obj = get_post_status_object($status)) {
                return false;
            }
        } else {
            $status_obj = $status;
        }

        if (!empty($status_obj->_builtin) || !empty($status_obj->pp_builtin)) {
            $options = \PublishPress_Statuses::instance()->options;

            if (
                !empty($options) 
                && (!empty($options->label_storage) && ('user' == $options->label_storage))
            ) {
                return true;
            }
        }
        
        switch ($property) {
            case 'label':
                return (!empty($status_obj->default_label) && ($caption == $status_obj->default_label));
                break;

            case 'description':
                return (!empty($status_obj->default_description) && ($caption == $status_obj->default_description));
                break;

            case 'save_as':
                $default = (!empty($status_obj->default_labels) && !empty($status_obj->default_labels->$property)) 
                ? $status_obj->default_labels->$property
                : sprintf('Save as %s', $status_obj->label);

                return ($caption == $default);
                break;

            case 'publish':
                $default = (!empty($status_obj->default_labels) && !empty($status_obj->default_labels->$property)) 
                ? $status_obj->default_labels->$property
                : sprintf('Set to %s', $status_obj->label);

                return ($caption == $default);
                break;
        }

        return false;
    }

    /**
     * Alias for getPostStatuses()
     * 
     * @return array
     */
    public function get_post_statuses($args = [], $return_args = [], $function_args = [])
    {
        return $this->getPostStatuses($args, $return_args, $function_args);
    }

    public function get_post_status_object($status_name, $status_args = []) {
        $statuses = $this->getPostStatuses($status_args, ['output' => 'object']);

        return (!empty($statuses[$status_name])) ? $statuses[$status_name] : (object) [];
    }

    public static function getCustomStatuses($status_args = [], $return_args = [], $function_args = []) {
        $status_args = array_merge($status_args, ['_builtin' => false]);

        if (!$return_args) {
            $return_args = 'object';
        }

        return self::instance()->getPostStatuses($status_args, $return_args, $function_args);
    }

    public static function getPostStati($status_args = [], $return_args = [], $function_args = []) {
        return self::instance()->getPostStatuses($status_args, $return_args, $function_args);
    }

    /**
     * Get all custom statuses as an ordered array
     *
     * @param array|string $statuses
     * @param array $args
     *
     * @return array $statuses All of the statuses
     */

    // @todo: suppress internal statuses by default?

    public function getPostStatuses($status_args = [], $return_args = [], $function_args = [])
    {
        global $wp_post_statuses;

        $plugin_page = \PP_Statuses_Functions::getPluginPage();

        if (!is_array($function_args)) {
            $function_args = [$function_args => $function_args];
        }

        // $status_args: filtering of return array based on status properties, applied outside the cache by function process_return_array()
        //

        // $return_args: return array structure, applied outside the cache by function process_return_array()
        //

        // $function_args: variations on the logic for populating or ordering the statuses array, cause a separate statuses cache entry    
        //
        foreach (
            [
                'show_disabled' => ('publishpress-statuses' === $plugin_page)
            ] 
        as $prop => $default_val) {
            if (!isset($function_args[$prop])) {
                $function_args[$prop] = $default_val;
            }
        }
        
        if (self::disable_custom_statuses_for_post_type()) {
            return $this->getCorePostStatuses($return_args);
        }

        if (!isset($function_args['show_disabled'])) {
            $function_args['show_disabled'] = ('publishpress-statuses' === $plugin_page);
        }

        //   On the Edit Post screen, the Status Control plugin has two implementations for statuses without a non-zero order:
        //      * No status_parent set: Excluded from automatic workflow status progression (but available for manual selection)
        //      * status_parent set: Normally unavailable, but available for manual selection after a parent status is selected

        // Internal object cache for repeat requests
        $arg_hash = md5(wp_json_encode($function_args));

        if (! empty($this->custom_statuses_cache[$arg_hash])) {
            return $this->process_return_array($this->custom_statuses_cache[$arg_hash], $status_args, $return_args, $function_args);
        }

        $_all_statuses = apply_filters(
            'publishpress_statuses_all_default_statuses',
            $this->get_all_default_statuses()
        );

        $core_statuses = $_all_statuses[self::TAXONOMY_CORE_STATUS];
        $pseudo_statuses = $_all_statuses[self::TAXONOMY_PSEUDO_STATUS];
        $default_moderation_statuses = $_all_statuses[self::TAXONOMY_PRE_PUBLISH];
        $default_privacy_statuses = $_all_statuses[self::TAXONOMY_PRIVACY];

        $all_statuses = [];
        
        foreach (array_keys($_all_statuses) as $taxonomy) {
            $all_statuses = array_merge($all_statuses, $_all_statuses[$taxonomy]);
        }

        $disabled_statuses = (array) get_option('publishpress_disabled_statuses');

        $positions = get_option('publishpress_status_positions');

        $stored_status_positions = (is_array($positions) && $positions) ? array_flip($positions) : [];

        $stored_status_terms = [];

        $term_meta_fields = apply_filters('publishpress_statuses_meta_fields', ['labels', 'post_type', 'roles', 'status_parent', 'color', 'icon']);

        $_disabled_statuses = [];

        if (!empty($all_statuses['_disabled'])) {
            $_disabled_statuses['_disabled'] = $all_statuses['_disabled'];
        }

        foreach ($all_statuses as $status_name => $status) {
            if (!empty($status->disabled)) {
                unset($all_statuses[$status_name]);

                if (!isset($_disabled_statuses[$status_name])) {
                    $_disabled_statuses[$status_name] = $status;
                }
            }
        }

        $disabled_position = (is_array($positions)) ? array_search('_disabled', $positions) : 0;
        if (!$disabled_position) {$disabled_position = count($all_statuses) + 100;}
        $stored_status_positions['_disabled'] = $disabled_position;
        $pos = $disabled_position;

        foreach (array_keys($_disabled_statuses) as $status_name) {
            $_disabled_statuses[$status_name]->position = $pos;
            $pos++;
        }

        $all_statuses = array_merge($all_statuses, $_disabled_statuses);

        // Merge stored positions with defaults
        foreach ($all_statuses as $status_name => $status) {
            if (empty($stored_status_positions[$status_name])) {
                $stored_status_positions[$status_name] = (!empty($status->position)) ? $status->position : 0;
            }
        }

        asort($stored_status_positions);

        // Make sure Revision statuses are not improperly disabled
        if ($positions && !empty($stored_status_positions)) {
            foreach (array_keys($stored_status_positions) as $status_name) {
                if ('_disabled' == $status_name) {
                    $in_disabled_statuses = true;
                    $pos = $disabled_position;
                }

                if (!empty($in_disabled_statuses) && !in_array($status_name, ['_revision-workflow', '_revision-alternate'])) {
                    $pos++;
                    $stored_status_positions[$status_name] = $pos;
                }
            }
        }

        if (!did_action('publishpress_statuses_pre_init')) {
            $function_args['skip_archive'] = true;
        }

        $status_taxonomies = apply_filters(
            'publishpress_statuses_taxonomies',
            [self::TAXONOMY_PRE_PUBLISH, self::TAXONOMY_PRIVACY, self::TAXONOMY_CORE_STATUS]
        );

        // We are using the terms and term_taxonomy tables to store and configure several types of post statuses, but disregarding term_id and term_taxonomy_id. 
        // Status name (slug) is the unique key (as used in the post_status column of the posts table), and there is no expectation to join the term tables to post queries for status filtering.
        foreach ($status_taxonomies as $taxonomy) {
            $stored_status_terms[$taxonomy] = [];

            $_terms = get_terms(
                ['taxonomy' => $taxonomy, 'hide_empty' => false] // @todo: support other args?
            );

            if (is_wp_error($_terms) || empty($_terms)) {
                continue;
            }

            $_terms = apply_filters('publishpress_statuses_get_terms', $_terms, $taxonomy);

            // Under PublishPress / PublishPress Planner, post_status properties were encoded in the description column of the term_taxonomy table
            if (is_admin() && empty($function_args['skip_archive']) && in_array($taxonomy, [self::TAXONOMY_CORE_STATUS, self::TAXONOMY_PRE_PUBLISH]) 
            && (did_action('pp_statuses_init') || (!empty($_REQUEST['message']) && ('settings-updated' == $_REQUEST['message'])))                       // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ) {
                require_once(__DIR__ . '/Admin.php');

                $_terms = \PublishPress_Statuses\Admin::apply_status_maintenance($_terms, $taxonomy);
                $did_status_maint = true;
            }

            foreach ($_terms as $term) {
                // Ignore any storage of terms to the wrong taxonomy
                if (isset($all_statuses[$term->slug]) && ($all_statuses[$term->slug]->taxonomy != $taxonomy)) {
                    continue;
                }

                if (isset($stored_status_terms[$taxonomy][$term->slug]) || ('pending-review' == $term->slug)) {
                    continue;
                }

                // Map taxonomy schema columns to Post Status properties

                // We need to avoid replacing a translation of "Pending Review" with the stored default English caption
                
                $term->label = (!empty($core_statuses[$term->slug]) && (('pending' != $term->slug) || ($core_statuses[$term->slug]->label == $term->name))) 
                ? $core_statuses[$term->slug]->label 
                : $term->name;

                $term->name = $term->slug;
                
                $term_meta = get_term_meta($term->term_id);

                if (is_array($term_meta)) {
                    foreach ($term_meta as $meta_key => $value) {
                        if (in_array($meta_key, $term_meta_fields)) {
                            $value = maybe_unserialize($value);                     // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
                            $value = (is_array($value)) ? reset($value) : $value;
                            $value = maybe_unserialize($value);                     // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize

                            if (is_object($value)) {
                                foreach (get_object_vars($value) as $k => $val) {
                                    $value->$k = sanitize_text_field(wp_unslash($val));
                                }
                            } elseif (is_array($value)) {
                                foreach ($value as $k => $val) {
                                    $value[$k] = sanitize_text_field(wp_unslash($val));
                                }
                            } else {
                                $value = sanitize_text_field(wp_unslash($value));
                            }

                            $term->$meta_key = $value;
                        }
                    }
                }

                // Hardcode properties for some statuses 
                if ('draft' == $term->slug) {
                    $term->position = 0;
                }

                if (self::TAXONOMY_PRIVACY == $taxonomy) {
                    $term->private = true;
                } elseif (self::TAXONOMY_PRE_PUBLISH == $taxonomy) {
                    $term->moderation = true;
                }

                // (@todo: support status slug (name) change using term_taxonomy_id?)
                $term = (object) array_diff_key(
                    (array) $term,
                    array_fill_keys(    // Strip out most properties related to taxonomy storage schema. status_parent property is stored as a term_meta field using parent status_name.
                        ['term_group', 'term_id', 'taxonomy', 'count', 'parent', 'filter'], 
                        true
                    )
                );

                $label_storage = \PublishPress_Statuses::instance()->options->label_storage;

                $stored_status_terms[$taxonomy][$term->slug] = $term;
                $stored_status_terms[$taxonomy][$term->slug]->taxonomy = $taxonomy;

                $status_name = $term->slug;

                if (!isset($all_statuses[$status_name])) {
                    $all_statuses[$status_name] = $stored_status_terms[$taxonomy][$status_name];
                } else {
                    foreach (get_object_vars($stored_status_terms[$taxonomy][$status_name]) as $prop => $value) {
                        if (in_array($prop, ['label', 'labels'])) {
                            // Disregard the stored value if it is the same as hardcoded defaults. This allows for translation.
                            if ('label' == $prop) {
                                if (!$value || self::isDefaultLabel($all_statuses[$status_name], $value)) {
                                    continue;
                                }
                            }

                            // Disregard the stored value if plugin is not configured to use stored labels with this status
                            switch ($label_storage) {
                                case 'user':
                                    if (!empty($all_statuses[$status_name]->pp_builtin) || !empty($all_statuses[$status_name]->_builtin)
                                    || in_array($status_name, ['draft', 'pending', 'publish', 'private', 'future'])
                                    ) {
                                        continue 2;
                                    }

                                    break;

                                default:
                                    if ((!empty($all_statuses[$status_name]->_builtin) && ('pending' != $status_name))
                                    || in_array($status_name, ['draft', 'publish', 'private', 'future'])
                                    ) {
                                        continue 2;
                                    }
                            }

                            if ('labels' == $prop) {
                                if (is_object($value)) {
                                    if (isset($value->save_as) && self::isDefaultLabel($all_statuses[$status_name], $value->save_as, 'save_as')) {
                                        unset($value->save_as);
                                    }

                                    if (isset($value->publish) && self::isDefaultLabel($all_statuses[$status_name], $value->publish, 'publish')) {
                                        unset($value->publish);
                                    }

                                    if (empty($value)) {
                                        continue;
                                    }

                                    // Set only the label properties that are saved to a non-default value, allowing others to be translated from default
                                    foreach($value as $label_prop => $caption) {
                                        if (empty($all_statuses[$status_name]->labels)) {
                                            $all_statuses[$status_name]->labels = (object) [];
                                        }

                                        $all_statuses[$status_name]->labels->$label_prop = $caption;
                                    }

                                    continue;
                                }
                            }
                        }
                        
                        if (('description' != $prop) || !in_array($value, ['', '-'])) {
                            $all_statuses[$status_name]->$prop = $value;
                        }
                    }
                }

                // @todo: register Revision Statuses upstream
                if ('pp_revision_status' == $taxonomy) {
                    if (!isset($wp_post_statuses[$status_name])) {
                        register_post_status($status_name, $all_statuses[$status_name]);
                    }
                }
            }
        }

        if (!empty($did_status_maint)) {
            do_action('publishpress_statuses_maintenance_done');
        }

        if (!empty(\PublishPress_Statuses::instance()->options->new_statuses_main_workflow)) {
            $statuses_defaulted_to_main = (array) get_option('publishpress_statuses_defaulted_to_main', []);
        } else {
            $statuses_defaulted_to_main = [];
        }
        
        $default_positions = [];
        $default_main_positions = [];

        // restore previously merged status positions (@todo: restore any other properties?)
        foreach ($all_statuses as $status_name => $status) {
            if (isset($stored_status_positions[$status_name])) {
                // Deal with deactivation / reactivation of custom privacy statuses due to Status Control module deactivation / re-activation
                //
                // (@todo: cleaner solution)

                if (empty($all_statuses[$status_name]->private)) {
                    // This is a non-private status whose position may have been artificially backed up from the disabled section into the private section
                    if (('pending' != $status_name) && !empty($stored_status_positions) && ($stored_status_positions[$status_name] >= $stored_status_positions['_disabled']) && ('_disabled' != $status_name)) {
                        $all_statuses[$status_name]->disabled = true;
                    
                    } elseif (('pending' == $status_name) && ($stored_status_positions[$status_name] >= $stored_status_positions['_pre-publish-alternate'])) {
                        $stored_status_positions[$status_name] = 1;
                        $all_statuses[$status_name]->disabled = false;
                    }
                } else {
                    // This is a private status whose position may have been artificially advanced from the private section into the disabled section
                    if (($stored_status_positions[$status_name] < $stored_status_positions['private']) && !empty($stored_status_positions[$status_name])
                    ) {
                        $stored_status_positions[$status_name] = $stored_status_positions['private'];

                    } elseif (!empty($stored_status_positions) && ($stored_status_positions[$status_name] >= $stored_status_positions['_disabled']) && ('_disabled' != $status_name)) {
                        $all_statuses[$status_name]->disabled = true;
                    }
                }

                $all_statuses[$status_name]->position = $stored_status_positions[$status_name];

            } else {
                // position has not been stored for this status, so default into correct section
                if (!empty($all_statuses[$status_name]->private)) {
                    $all_statuses[$status_name]->position = $all_statuses['private']->position;
                    $stored_status_positions[$status_name] = $all_statuses['private']->position;
                } else {
                    $taxonomy = (!empty($status->taxonomy)) ? $status->taxonomy : 'post_status';

                    if (!empty($statuses_defaulted_to_main[$status_name]) || !empty($all_statuses[$status_name]->pp_builtin)) {
                        if (!isset($default_main_positions[$taxonomy])) {
                            $set_pos = $all_statuses['_pre-publish-alternate']->position - 1;

                            $default_main_positions[$taxonomy] = apply_filters(
                                'publishpress_statuses_default_main_position',
                                $set_pos,
                                $taxonomy,
                                $all_statuses
                            );
                        }

                        $all_statuses[$status_name]->position = $default_main_positions[$taxonomy];
                        $stored_status_positions[$status_name] = $default_main_positions[$taxonomy];
                    } else {
                        $all_statuses['_pre-publish-alternate']->position;

                        if (!isset($default_positions[$taxonomy])) {
                            $set_pos = $all_statuses['_pre-publish-alternate']->position;
    
                            $default_positions[$taxonomy] = apply_filters(
                                'publishpress_statuses_default_position',
                                $set_pos,
                                $taxonomy,
                                $all_statuses
                            );
                        }

                        $all_statuses[$status_name]->position = $default_positions[$taxonomy];
                        $stored_status_positions[$status_name] = $default_positions[$taxonomy];
                    }
                }
            }

            if (!empty($status->moderation)) {
                $all_statuses[$status_name] = $this->apply_moderation_status_properties($all_statuses[$status_name]);
                $this->all_moderation_statuses[]= $status_name;

            } elseif (!empty($status->private)) {
                $all_statuses[$status_name] = $this->apply_visibility_status_properties($all_statuses[$status_name]);
            }
        }

        // establish the position of the disabled section
        $privacy_statuses = array_merge($default_privacy_statuses, $stored_status_terms[self::TAXONOMY_PRIVACY]);
        $all_statuses['_disabled']->disabled = true;

        // A status can't be its own parent
        foreach ($all_statuses as $status_name => $status) {
            if (!empty($status->status_parent) && ($status_name == $status->status_parent)) {
                $all_statuses[$status_name]->status_parent = '';
            }
        }

        $all_statuses['draft']->moderation = false;

        $all_statuses = apply_filters(
            'publishpress_revisions_all_statuses', 
            $all_statuses, 
            compact('disabled_statuses', 'function_args')
        );

        $status_by_position = [];

        // Draft status is always at position and order zero.
        // Note: order value is not stored, but derived from position value with consideration of other status properties.
        $this->addItemToArray($status_by_position, 0, $all_statuses['draft']);

        foreach ([false, true] as $disabled) {
            // Disabled statuses are postioned right after the Published / Private statuses in the management list, 
            // unavailable in status selection UI and not included in any status workflow auto-progression.
            if ($disabled && !$function_args['show_disabled']) {
                break;
            }

            // Classify the statuses based on stored position relative to core statuses
            foreach ($all_statuses as $key => $status) {
                // None of the customizations / default checks in this loop apply to the Draft status
                if (in_array($status->name, ['draft', 'draft-revision'])) {
                    if ('draft' == $status->name) {
                        continue; // Draft status added to array prior to statuses loop
                    }
                    
                    $status->position = 0;
                    $status->disabled = false;
                    $status->alternate = false;
                } else {
                    if ($status_is_disabled = !empty($status->disabled) || in_array($status->name, $disabled_statuses, true)) {
                        $status->disabled = true;

                        if ($status->position <= $all_statuses['_disabled']->position) {
                            $status->position = $all_statuses['_disabled']->position;
                        }
                    }

                    if (!$disabled && $status_is_disabled) {
                        continue;
                    }
                    
                    if ($disabled && !$status_is_disabled) {
                        continue;
                    }

                    // Correct previous storage ambiguity
                    if ('pending-review' === $status->name) {
                        $status->name = 'pending';
                        $status->slug = 'pending';
                    }

                    if (empty($core_statuses[$status->slug]) && empty($pseudo_statuses[$status->slug])) {
                        if (!empty($stored_status_positions) && ($status->position >= $all_statuses['_disabled']->position)) {
                            $status->disabled = true; // Fallback in case the disabled_statuses array is missing or out of sync (privacy statuses are pulled from a different taxonomy)

                        } elseif (!empty($status->moderation)) { 
                            // Alternate workflow statuses will be displayed right before the Future and Published / Private statuses in the management list, 
                            // de-emphasized in status selection UI and not included in any status workflow auto-progression.
                            if ($status->position >= $all_statuses['_pre-publish-alternate']->position) {
                                $status->alternate = true;
                            }
                        }
                    }
                }

                // Post type and status parent are not customizable for Pending status
                if ('pending' === $status->name) {
                    $status->status_parent = '';
                    $status->post_type = [];
                } else {
                    if (! isset($status->post_type)) {
                        $status->post_type = [];
                    }

                    if (! isset($status->status_parent)) {
                        $status->status_parent = '';
                    }
                }

                // Capability Status has never been set for this status
                if (! isset($status->capability_status)) {
                    $status->capability_status = '';
                }

                // Check if we need to set default colors and icons for current status
                if (! isset($status->color) || empty($status->color)) {
                    $status->color = self::DEFAULT_COLOR;
                }

                if (! isset($status->icon) || empty($status->icon)) {
                    $status->icon = self::DEFAULT_ICON;
                }

                // Allow spacing for previously unconfigured statuses to be inserted
                $disabled_offset = ($disabled) ? 100000 : 0;
                $this->addItemToArray($status_by_position, $disabled_offset + (int)$status->position * 1000, $status);
            }
        }

        // Sort the items numerically by position key
        ksort($status_by_position, SORT_NUMERIC);

        // Now set the order value (used for status selection UI and automation) based on stored position and other status properties

        $last_order =
        [   'pre_pending' => 0,             // 'draft' status is order 0, Pre-Pending workflow statuses allotted order 1 to 199
            'post_pending' => 200,          // 'pending' status is order 200, Post-Pending workflow statuses allotted order 201 to 299
            'alternate_workflow' => 299,    // Alternate Workflow statuses allotted order 300 to 699
            'private' => 800,               // 'private' status is order 800, Custom privacy statuses allotted order 801 to 899
            'disabled' => 999,              // Disabled statuses allotted order >= 1000
        ];

        foreach ($status_by_position as $position => $status) {
            if (in_array($status->slug, ['draft', 'pending', 'future', 'publish', 'private', '_pre-publish-alternate', '_disabled'])) {
                continue;
            }

            // Disabled statuses
            if (!empty($status->disabled)) {
                $last_order['disabled']++;
                $status_by_position[$position]->order = $last_order['disabled'];

            } elseif ($status->position > $all_statuses['private']->position) {
                $last_order['private']++;
                $status_by_position[$position]->order = $last_order['private'];
                
            } elseif ($status->position > $all_statuses['future']->position) {
                //possible future support for custom future statuses
                // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                /*
                $last_order['future']++;
                $status->order = $last_order['future'];
                */
            
            // Alternate workflow statuses
            } elseif ($status->position >= $all_statuses['_pre-publish-alternate']->position) {
                $last_order['alternate_workflow']++;
                $status_by_position[$position]->order = $last_order['alternate_workflow'];
            
            } elseif ($status->position >= $all_statuses['pending']->position) {
                $last_order['post_pending']++;
                $status_by_position[$position]->order = $last_order['post_pending'];

            } elseif ($status->position > $all_statuses['draft']->position) {
                $last_order['pre_pending']++;
                $status_by_position[$position]->order = $last_order['pre_pending'];
            }
        }

        $label_storage = \PublishPress_Statuses::instance()->options->label_storage;

        foreach (array_keys($status_by_position) as $key) {
            if (!$function_args['show_disabled']) {
                if (!empty($status_by_position[$key]->disabled)) {
                    unset($status_by_position[$key]);
                    continue;
                }
            }

            if (!is_object($status_by_position[$key])) {
                if (is_array($status_by_position[$key])) {
                    $status_by_position[$key] = (object) $status_by_position[$key];
                } else {
                    unset($status_by_position[$key]);
                    continue;
                }
            }

            $status_by_position[$key] = $this->apply_default_status_properties($status_by_position[$key]);

            $status_name = $status_by_position[$key]->name;

            foreach (get_object_vars($status_by_position[$key]) as $prop => $val) {
                if (isset($wp_post_statuses[$status_name])) {
                    if (!isset($wp_post_statuses[$status_name]->$prop)) {
                        $wp_post_statuses[$status_name]->$prop = $val;
                    } else {
                        if (in_array($prop, ['post_type'])) {
                            $wp_post_statuses[$status_name]->$prop = $val;
                        }

                        if ('label' == $prop) {
                            // Disregard the stored value if it is the same as hardcoded defaults. This allows for translation.
                            if (!$val || (!empty($all_statuses[$status_name]->default_label) && ($val == $all_statuses[$status_name]->default_label))) {
                                continue;
                            }

                            // Disregard the stored value if plugin is not configured to use stored labels with this status
                            switch ($label_storage) {
                                case 'user':
                                    if (!empty($all_statuses[$status_name]->pp_builtin) || !empty($all_statuses[$status_name]->_builtin)
                                    || in_array($status_name, ['draft', 'pending', 'publish', 'private', 'future'])
                                    ) {
                                        continue 2;
                                    }

                                    break;

                                default:
                                    if ((!empty($all_statuses[$status_name]->_builtin) && ('pending' != $status_name))
                                    || in_array($status_name, ['draft', 'publish', 'private', 'future'])
                                    ) {
                                        continue 2;
                                    }
                            }

                            if (is_object($val) && !empty($val->name)) {
                                $wp_post_statuses[$status_name]->label = $val->name;
                            }
                        }

						// We already confirmed that the wp_post_statuses object has this property
                        if (is_object($wp_post_statuses[$status_name]->$prop)) {
                            // If our property is not an object, skip it as invalid
                            if (is_object($val)) {
                                $wp_post_statuses[$status_name]->$prop = (object) array_merge(
                                    (array) $wp_post_statuses[$status_name]->$prop,
                                    (array) $val
                                );
                            }

                        } elseif (is_array($wp_post_statuses[$status_name]->$prop)) {
                            // If our property is not an array, skip it as invalid
                            if (is_array($val)) {
                                $wp_post_statuses[$status_name]->$prop = array_merge(
                                    $wp_post_statuses[$status_name]->$prop,
                                    $val
                                );
                            }
                        } elseif (!in_array($status_name, ['draft', 'pending', 'future', 'publish', 'private'])) {
                            $wp_post_statuses[$status_name]->$prop = $val;
                        }
                    }
                }
            }
        }

        $this->custom_statuses_cache[$arg_hash] = $status_by_position;

        return $this->process_return_array($status_by_position, $status_args, $return_args, $function_args);
    }

    // Simple filtering / structuring applied to the statuses post-cache
    private function process_return_array($status_by_position, $status_args, $return_args, $function_args) {
        
        // $status_args: filtering of return array based on status properties
        //
        if (!is_array($status_args)) {
            $status_args = [];
        }

        // $return_args: return array structure
        //
        // support get_post_stati() argument structure
        if (is_string($return_args)) {
            $return_args = ['output' => $return_args];

        } elseif (!is_array($return_args)) {
            $return_args = [];
        }

        foreach (
            [
                'output' => 'names',
                'return_key' => 'name'
            ] 
        as $prop => $default_val) {
            if (!isset($return_args[$prop])) {
                $return_args[$prop] = $default_val;
            }
        }

        if (!self::isStatusManagement()) {
            $skip_admin_statuses = apply_filters(
                'publishpress_statuses_omit_admin_statuses',
                ['_pre-publish-alternate', '_disabled']
            );

            foreach (array_keys($status_by_position) as $k) {
                if (in_array($status_by_position[$k]->slug, $skip_admin_statuses)) {
                    unset ($status_by_position[$k]);
                }
            }
        }

        $operator = (!empty($function_args['operator'])) ? $function_args['operator'] : 'AND';
        $operator = strtoupper($operator);

        if (in_array($operator, ['AND', 'OR', 'NOT'])) {
            $count    = count( $status_args );
            $filtered = [];

            foreach ($status_by_position as $key => $status) {
                $matched = 0;

                foreach ($status_args as $prop => $arg_val) {
                    if ('post_type' == $prop) {  // special treatment: require queried post type to be in status definition, or for status to have post_type empty
                        if (empty($status->{$prop}) || array_intersect((array)$arg_val, (array)$status->{$prop})) {
                            ++$matched;
                        }
                    } else {
                        if ($arg_val && !empty($status->{$prop}) && (is_bool($arg_val) || ($status->{$prop} == $arg_val))) {
                            ++$matched;

                        } elseif (!$arg_val && empty($status->{$prop})) {
                            ++$matched;
                        }
                    }
                }

                if ( ( 'AND' === $operator && $matched !== $count )
                    || ( 'OR' === $operator && !$matched)
                    || ( 'NOT' === $operator && $matched )
                ) {
                    unset($status_by_position[$key]);
                }
            }
        }

        $order = 0;

        foreach(array_keys($status_by_position) as $k) {
            $status_by_position[$k]->order = $order;
            $order++;
        }

        $return_arr = [];

        $return_key_order_val = !empty($return_args['return_key']) && ('order' == $return_args['return_key']);

        if (isset($return_args['output']) && in_array($return_args['output'], ['slug', 'name', 'names'])) {
            foreach (array_keys($status_by_position) as $key) {
                if ($return_key_order_val) {
                    $return_arr[] = $status_by_position[$key]->name;
                } else {
                    $return_arr[ $status_by_position[$key]->name ] = $status_by_position[$key]->name;
                }
            }

            return apply_filters('_presspermit_get_post_statuses', $return_arr, $status_args, $return_args, $function_args);

        } elseif (isset($return_args['output']) && in_array($return_args['output'], ['label'])) {
            foreach (array_keys($status_by_position) as $key) {
                if ($return_key_order_val) {
                    $return_arr[] = $status_by_position[$key]->label;
                } else {
                    $return_arr[ $status_by_position[$key]->name ] = $status_by_position[$key]->label;
                }
            }

            return apply_filters('_presspermit_get_post_statuses', $return_arr, $status_args, $return_args, $function_args);

        } elseif ($return_key_order_val) {
            return apply_filters('_presspermit_get_post_statuses', $status_by_position, $status_args, $return_args, $function_args);
        }
    
        // While maintaining the same array ordering, return array keys will be changed to status name (slug) unless this function arg is set to 'order'
        foreach (array_keys($status_by_position) as $key) {
            $return_arr[ $status_by_position[$key]->name ] = $status_by_position[$key];
        }

        // Don't currently allow nested child statuses
        foreach ($return_arr as $key => $status) {
            if (!empty($status->status_parent)) {
                if (!empty($return_arr[ $status->status_parent ]->status_parent)) {
                    $return_arr[$key]->status_parent = '';
                }
            }
        }

        // @todo: why do custom statuses sometimes break Planner Calendar's add post modal?
        $options = \PublishPress_Statuses::instance()->options;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (defined('DOING_AJAX') && DOING_AJAX && empty($options->planner_add_post_custom_statuses) && !empty($_REQUEST['action'] && ('publishpress_calendar_get_post_type_fields' == $_REQUEST['action']))) {
            foreach ($return_arr as $k => $obj) {
                if (is_object($obj) && !in_array($obj->slug, ['draft', 'pending', 'publish', 'private'])) {
                    unset($return_arr[$k]);
                }
            }
        }

        return apply_filters('_presspermit_get_post_statuses', $return_arr, $status_args, $return_args, $function_args);
    }

    // filter PublishPress Permissions Pro results
    function flt_get_post_statuses($statuses, $status_args, $return_args, $operator, $function_args) {
        if (self::disable_custom_statuses_for_post_type()) {
            return $statuses;
        }
        
        $function_args['operator'] = $operator;

        $context = (!empty($function_args['context'])) ? $function_args['context'] : '';

        // Don't double-filter posts query
        if (in_array($context, ['edit'])) {
            return $statuses;
        }

        return $this->getPostStatuses($status_args, $return_args, $function_args);
    }

    // filter our own results
    function flt_get_post_statuses_internal($statuses, $status_args, $return_args, $function_args) {
        global $current_user, $post;
        
        $context = (!empty($function_args['context'])) ? $function_args['context'] : '';

		$pp_status_capabilities_active = class_exists('\PublishPress\StatusCapabilities');

        if (('load' == $context) || !did_action('pp_statuses_init') 
        || ($pp_status_capabilities_active && !did_action('publishpress_status_capabilities_loaded'))
        || self::isContentAdministrator() 
        || self::disable_custom_statuses_for_post_type() 
        ) {
            return $statuses;
        }

        // Maintain default PublishPress behavior (Permissions add-on / Capabilities Pro) for statuses that do not have custom capabilities enabled
        foreach ($statuses as $k => $obj) {
            $status_name = (is_object($obj)) ? $obj->name : $k;
            $_status = str_replace('-', '_', $status_name);

            if (!empty($obj->moderation) 
            && !in_array($status_name, ['draft', 'future']) 
            && (empty($post) || empty($post->post_status) || ($obj->name != $post->post_status))
            && (empty($current_user->allcaps['pp_moderate_any']) || !empty($obj->public) || !empty($obj->private))
            && (!$pp_status_capabilities_active || !\PublishPress\StatusCapabilities::postStatusHasCustomCaps($status_name))
            && (('pending' != $status_name) || !\PublishPress_Statuses::instance()->options->pending_status_regulation)
            && empty($current_user->allcaps["status_change_{$_status}"])) {
                unset($statuses[$k]);
            }
        }
        
        return $statuses;
    }

    /**
     * Add item to Array without overwrite any item, in case an item is already set for the position.
     *
     * @param $array
     * @param $position
     * @param $item
     */
    private function addItemToArray(&$array, $position, $item)
    {
        if (isset($array[$position])) {
            $this->addItemToArray($array, $position + 1, $item);
        } else {
            $array[$position] = $item;
        }
    }

    public static function getStatusBy($field, $value) {
        return self::instance()->get_custom_status_by($field, $value);
    }

    public static function getCustomStatus($value, $function_args = []) {
        return self::instance()->get_custom_status_by('name', $value, $function_args);
    }

    /**
     * Returns a single status object
     *
     * @param string|int $string_or_int The status slug (name) or label to search for
     *
     * @return object|WP_Error|false $status
     */
    public function get_custom_status_by($field, $value, $function_args = [])
    {
        if (! in_array($field, ['id', 'slug', 'name', 'label'])) {
            return false;
        }

        $function_args = (array) $function_args;

        if (in_array($field, ['id', 'slug'])) {
            $field = 'name';
        }

        // New and auto-draft do not exist as status. So we map them to draft for now.
        if ('name' === $field && in_array($value, ['new', 'auto-draft'])) {
            $value = 'draft';
        }

        // Pull from $wp_post_statuses if we already put a copy there.
        global $wp_post_statuses;

        if (!empty($wp_post_statuses) && empty($function_args)) {
            if ($custom_status = wp_filter_object_list($wp_post_statuses, [$field => $value])) {
                $custom_status = false;
            }
        }

        if (empty($custom_status)) {
            // Support retrieval of disabled status unless explicitly declined
            if (!isset($function_args['show_disabled'])) {
                $function_args['show_disabled'] = true;
            }

            $function_args = array_merge($function_args, ['context' => 'load']);

            $custom_statuses = $this->getPostStatuses(
                [], 
                'object',
                $function_args
            );

            $custom_status = wp_filter_object_list($custom_statuses, [$field => $value]);
        }

        if (! empty($custom_status)) {
            return array_shift($custom_status);
        }

        return false;
    }

    // PublishPress Planner compat
    public static function get_link($args = [])
    {
        return self::getLink($args);
    }

    /**
     * Generate a link to one of the custom status actions
     *
     * @param array $args (optional) Action and any query args to add to the URL
     *
     * @return string $link Direct link to complete the action
     * @since 0.7
     *
     */
    public static function getLink($args = [])
    {
        if (! isset($args['action'])) {
            $args['action'] = '';
        }
        
        // Add other things we may need depending on the action
        switch ($args['action']) {
            case 'add-new':
                unset($args['action']);
                unset($args['page']);
                $args = array_merge(['page' => 'publishpress-statuses-add-new'], $args);
                break;

            case 'edit-status':
            case 'edit-status-labels':
                $args['page'] = 'publishpress-statuses';

                if (!empty($args['slug'])) {
                    if (empty($args['name'])) {
                        $args['name'] = $args['slug'];
                    }
                }

                break;
            case 'delete-status':
            	$args['page'] = 'publishpress-statuses';
                $args['_wpnonce'] = wp_create_nonce($args['action']);
                break;
                
            default:
                if (empty($args['page'])) {
                    $args['page'] = 'publishpress-statuses';
                }

                break;
        }

        return add_query_arg($args, get_admin_url(null, 'admin.php'));
    }

    public static function import_status_properties($terms, $taxonomy) {
        require_once(__DIR__ . '/Admin.php');
        $retval = \PublishPress_Statuses\Admin::apply_status_maintenance($terms, $taxonomy);

        do_action('publishpress_statuses_maintenance_done');

        return $retval;
    }

    /**
     * Adds a new custom status as a term in the wp_terms table.
     * Basically a wrapper for the wp_insert_term class.
     *
     * The arguments decide how the term is handled based on the $args parameter.
     * The following is a list of the available overrides and the defaults.
     *
     * 'description'. There is no default. If exists, will be added to the database
     * along with the term. Expected to be a string.
     *
     * 'slug'. Expected to be a string. There is no default.
     *
     * @param int|string $term The status to add or update
     * @param array|string $args Change the values of the inserted term
     *
     * @return array|WP_Error $response The Term ID and Term Taxonomy ID
     */
    public function addStatus($taxonomy, $status_name, $args = [])
    {
        $slug = (! empty($args['slug'])) ? $args['slug'] : sanitize_title($status_name);
        unset($args['slug']);

        $response = wp_insert_term(
            $status_name,
            $taxonomy,
            ['slug' => $slug, 'description' => !empty($args['description']) ? $args['description'] : '']
        );

        if (is_array($response) && !empty($response['term_id'])) {
            $term_meta_fields = apply_filters('publishpress_statuses_meta_fields', ['labels', 'post_type', 'roles', 'status_parent', 'color', 'icon']);

            $term_id = $response['term_id'];

            foreach ($args as $field => $set_value) {
                if (in_array($field, $term_meta_fields)) {
                    if (is_array($args[$field])) {
                        $meta_val = [];

                        foreach ($set_value as $k => $val) {
                            $meta_val[$k] = sanitize_textarea_field(wp_unslash($val));
                        }
                    } elseif (is_object($set_value)) {
                        $meta_val = \get_object_vars($set_value);

                        foreach($meta_val as $k => $val) {
                            $meta_val[$k] = sanitize_text_field(wp_unslash($val));
                        }

                        $meta_val = (object) $meta_val;
                    } else {
                        $meta_val = sanitize_textarea_field(wp_unslash($set_value));
                    }

                    $result = update_term_meta($term_id, $field, $meta_val);

                    if (is_wp_error($result)) {
                        return $result;
                    }
                }
            }
        }

        // Reset our internal object cache
        $this->custom_statuses_cache = [];

        if (in_array(
        	$taxonomy, 
        	apply_filters(
        		'pp_statuses_assignment_cap_taxonomies',
        		[self::TAXONOMY_PRE_PUBLISH]
        	)
        )) {   // @todo: review implementation for visibility statuses
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
        }

        return $response;
    }

    /**
     * Get array of all supported post type slugs
     */
    public function get_supported_post_types()
    {
        $postTypes = get_post_types(
            [
                'show_ui'  => true,
            ], 
            'objects'
        );

        $postTypes = array_diff_key($postTypes, array_fill_keys(['attachment', 'wp_block', 'wp_navigation'], true));

        $postTypes = apply_filters('publishpress_statuses_supported_post_types', $postTypes);

        // Hide notification workflows from the list
        if (isset($postTypes['psppnotif_workflow'])) {
            unset($postTypes['psppnotif_workflow']);
        }

        return $postTypes;
    }

    public static function orderStatuses($statuses = false, $args = [])
    {
        if (false === $statuses) {
            global $wp_post_statuses;
            $statuses = $wp_post_statuses;
        }

        if (self::disable_custom_statuses_for_post_type()) {
            return $statuses;
        }

        $defaults = [
            'min_order' => 0, 
            'status_parent' => false, 
            'omit_status' => [], 
            'include_status' => [], 
            'whitelist_status' => [], 
            'require_order' => false
        ];

        $args = array_merge($defaults, (array)$args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        // convert integer keys to slugs
        foreach ($statuses as $status => $obj) {
            if (is_numeric($status)) {
                $statuses[$obj->name] = $obj;
                unset($statuses[$status]);
            }
        }

        $include_status = (array)$include_status;
        $whitelist_status = (array)$whitelist_status;

        if (!empty($omit_status)) {
            $statuses = array_diff_key(
                $statuses, 
                array_fill_keys(array_diff((array)$omit_status, $include_status), true)
            );
        }

        if (!empty($min_order)) {
            foreach ($statuses as $status => $status_obj) {
                if (!in_array($status, $include_status, true) 
                && (empty($status_obj->position) || ($status_obj->position < $min_order))
                ) {
                    unset($statuses[$status]);
                }
            }
        }

        foreach ($statuses as $status => $status_obj) {
            if ($require_order && empty($status_obj->position)) {
                unset($statuses[$status]);
                continue;
            }

            if ((false !== $status_parent) && !in_array($status, $whitelist_status)) {
                $_parent = (isset($status_obj->status_parent)) ? $status_obj->status_parent : '';

                if (($_parent != $status_parent) && ($require_order || ($status != $status_parent))) {
                    unset($statuses[$status]);
                    continue;
                }
            }
        }

        return $statuses;
    }

    private static function isAlternateStatus($status_obj) {
        static $alternate_props;

        if (!isset($alternate_props)) {
            $alternate_props = apply_filters('publishpress_statuses_alternate_properties', ['alternate']);
        }

        foreach ($alternate_props as $prop) {
            if (!empty($status_obj->$prop)) {
                return true;
            }
        }

        return false;
    }

    public static function getNextStatusObject($post_id = 0, $args = [])
    {
        global $wp_post_statuses;

        $defaults = ['moderation_statuses' => [], 'can_set_status' => [], 'force_main_channel' => false, 'post_type' => '', 'post_status' => '', 'default_by_sequence' => null, 'skip_post_id_check' => false, 'skip_current_status_check' => false];

        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $post_type = sanitize_key($post_type);

        if (is_object($post_id)) {
            $post = $post_id;
            $post_id = $post->ID;
        } else {
            if (!$post_id && !$skip_post_id_check) {
                $post_id = \PP_Statuses_Functions::getPostID();
            }

            if ($post_id) {
                $post = get_post($post_id);
            } else {
                $post = false;
            }
        }

        if (empty($post)) {
            $post_type = (!empty($args['post_type'])) ? $args['post_type'] : \PP_Statuses_Functions::findPostType();
        } else {
            $post_type = $post->post_type;
        }

        if (!empty($args['post_status'])) {
            $post_status = $args['post_status'];
        } else {
        	$post_status = (!empty($post)) ? $post->post_status : 'draft';
        }

        if ('auto-draft' == $post_status) {
            $post_status = 'draft';
        }

		if (!empty($post)) {
			$args['post'] = $post;	
		}

		$post_status = apply_filters(
			'publishpress_statuses_current_workflow_status',
			$post_status,
			$post_id,
			$args
		);

    	if (!$post_status_obj = self::instance()->get_post_status_object($post_status)) {
            $_post_status = apply_filters(
            	'publishpress_statuses_default_status',
            	'draft',
            	$post_id
        	);
        	
        	$post_status_obj = get_post_status_object($_post_status);
        }

        $current_status = $post_status;

        $is_administrator = self::isContentAdministrator();
        if (!$type_obj = get_post_type_object($post_type)) {
            return $post_status_obj;
        }

        if (empty($moderation_statuses)) {
        	if (!$status_args = apply_filters('publishpress_statuses_edit_post_status_args', false, $post_id)) {
		        $status_args = ['moderation' => true, 'internal' => false, 'post_type' => $post_type];
		    }
        	
            $moderation_statuses = \PublishPress_Statuses::getPostStati($status_args, 'object');
        }

		if ($filtered_moderation_statuses = apply_filters(
			'pp_statuses_get_next_moderation_statuses',
			false,
			$moderation_statuses,
			$post_id,
			compact('post_status_obj', 'post', 'current_status')
		)) {
            $moderation_statuses = $filtered_moderation_statuses;
        }
		
		if (!is_array($filtered_moderation_statuses)) {
            if (self::isAlternateStatus($post_status_obj)) {
                foreach ($moderation_statuses as $k => $_status) {
                    if (!self::isAlternateStatus($_status)
                    || (!empty($post_status_obj->status_parent) && ($_status->status_parent != $post_status_obj->status_parent) && ($_status->status_parent != $post_status))
                    || (empty($post_status_obj->status_parent) && ($_status->status_parent != $post_status) && !defined('PUBLISHPRESS_STATUSES_SINGLE_ALTERNATE_WORKFLOW'))
                    ) {
                        unset($moderation_statuses[$k]);
                    }
                }
            } else {
                foreach ($moderation_statuses as $k => $_status) {
                    if ((self::isAlternateStatus($_status)
                    || (!empty($post_status_obj->status_parent) && ($_status->status_parent != $post_status_obj->status_parent)))
                    && !in_array($k, ['draft', 'publish'])
                    ) {
                        unset($moderation_statuses[$k]);
                    }
                }
            }
        }

		$is_revision = function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post_id);

        if (empty($can_set_status)) {
            $can_set_status = self::getUserStatusPermissions('set_status', $type_obj->name, $moderation_statuses);
        }

        if ('auto-draft' == $post_status)
            $post_status = 'draft';
    
        $default_privacy = 'publish';

        if (!defined('PRESSPERMIT_PRO_VERSION') || version_compare(PRESSPERMIT_PRO_VERSION, '4.6.4', '>=')) {
            $options = \PublishPress_Statuses::instance()->options;
            $default_privacy = (is_object($options) && !empty($options->default_privacy) && !empty($options->default_privacy[$post_type])) ? $options->default_privacy[$post_type] : 'publish';
            
            if ($default_privacy && ('publish' != $default_privacy)) {
                if (!$default_privacy_obj = get_post_status_object($default_privacy)) {
                    $default_privacy = 'publish';
                }
            }
    
            if (!empty($post_status_obj) && !empty($post_status_obj->name) && ('publish' == $post_status_obj->name) 
                && !empty($default_privacy_obj) && ($default_privacy_obj->name != 'publish')
            ) {
                $post_status_obj = $default_privacy_obj;
            }
        }

        if (!empty($post_status_obj->public) || !empty($post_status_obj->private) || (!empty($post_status_obj->name) && ('future' == $post_status_obj->name))) {
            if (!$skip_current_status_check) {
                return $post_status_obj;
            }
        }

        if (is_null($default_by_sequence)) {
            $default_by_sequence = \PublishPress_Statuses::instance()->workflow_by_sequence;
        }

        if (!$is_revision && current_user_can($type_obj->cap->publish_posts) 
        && (!$default_by_sequence || apply_filters('presspermit_editor_default_publish', false, $post))
        ) {
            if (!empty($post) && !empty($post->post_date_gmt) && time() < strtotime($post->post_date_gmt . ' +0000')) {
                return get_post_status_object('future');
            } else {
                return get_post_status_object($default_privacy);
            }
        } else {
            if (empty($moderation_statuses)) {
                $moderation_statuses = \PublishPress_Statuses::getPostStati(
                    $status_args
                    , 'object'
                );
                
                unset($moderation_statuses['future']);
            }

            // Don't default to another moderation status of equal or lower order
            $status_order = (!empty($post_status_obj->position)) ? $post_status_obj->position : 0;
            $_args = ['min_order' => $status_order + 1, 'omit_status' => 'future', 'require_order' => true];

            if (!$force_main_channel) {
                if (empty($post_status_obj->name)) {    // @todo: review causes
                    $_args['status_parent'] = ''; 

                } elseif (!empty($post_status_obj->status_parent)) {
                    // If current status is a Workflow branch child, only offer other statuses in that branch
                    $_args['status_parent'] = $post_status_obj->status_parent;

                } elseif ($status_children = self::getStatusChildren($post_status_obj->name, $moderation_statuses)) {
                    if ($default_by_sequence) {
                        // If current status is a Workflow branch parent, only offer other statuses in that branch
                        $_args['status_parent'] = $post_status_obj->name;
                        unset($_args['min_order']);
                        $moderation_statuses = $status_children;
                    } else {
                        $_args['status_parent'] = '';
                    }
                } else {
                    $_args['status_parent'] = '';  // don't default from a main channel into a branch status
                }
            }

            $_post = (!empty($post)) ? $post : get_post($post_id);

            if (!$_moderation_statuses = self::orderStatuses($moderation_statuses, $_args)) {
                // If there are no more statuses in a branch, return next status outside branch
                unset($_args['status_parent']);
                $_moderation_statuses = self::orderStatuses($moderation_statuses, $_args);
            }

            $moderation_statuses = apply_filters(
                'presspermit_editpost_next_status_priority_order', 
                $_moderation_statuses, 
                ['post' => $_post]
            );

            $_post_status_obj_name = (!empty($post_status_obj->name)) ? $post_status_obj->name : '';

            // If this user cannot set any further progression steps, return current post status
            if (!$moderation_statuses) {
                if ((!empty($post_status_obj->status_parent) || !empty($status_children)) && !$force_main_channel) {
                    $args['force_main_channel'] = true;
                    return self::getNextStatusObject($post_id, $args);
                }
            } else {
                if (!$default_by_sequence || (defined('PRESSPERMIT_CHILD_STATUSES_ALWAYS_SEQUENCED') && (!empty($status_children) || !empty($post_status_obj->status_parent)))) {

                    // Defaulting to highest order that can be set by the user...
                    $moderation_statuses = array_reverse($moderation_statuses);
                }

                foreach ($moderation_statuses as $_status_obj) {
                    if (!empty($can_set_status[$_status_obj->name]) && ($_status_obj->name != $_post_status_obj_name)) {
                        $post_status_obj = $_status_obj;
                        $_post_status_obj_name = (!empty($post_status_obj->name)) ? $post_status_obj->name : '';
                        break;
                    }
                }
            }

            // If logic somehow failed, default to draft
            if (empty($post_status_obj)) {
            	$_post_status = apply_filters(
	            	'publishpress_statuses_default_status',
	            	'draft',
	            	$post_id,
	            	compact(['can_set_status'])
	        	);
        	
        		$post_status_obj = get_post_status_object($_post_status);
                $_post_status_obj_name = (!empty($post_status_obj->name)) ? $post_status_obj->name : '';
            }

            $override_status = apply_filters(
                'presspermit_workflow_progression', 
                $_post_status_obj_name, 
                $post_id, 
                compact('moderation_statuses')
            );

            if (($override_status != $_post_status_obj_name) 
            && $can_set_status[$override_status]
            ) {
                $post_status_obj = get_post_status_object($override_status);
                $_post_status_obj_name = (!empty($post_status_obj->name)) ? $post_status_obj->name : '';
            }

            if (($_post_status_obj_name == $post_status) && current_user_can($type_obj->cap->publish_posts) && !$is_revision) {
                $post_status_obj = get_post_status_object('publish');
                $_post_status_obj_name = (!empty($post_status_obj->name)) ? $post_status_obj->name : '';
            }

            // If we are at the end of an alternate workflow status, default to returning to the last main workflow status this post was saved with
            if ('alternate' == self::getStatusSection($current_status)) {
                $current_status_obj = self::getCustomStatus($current_status);

                if ((('publish' == $_post_status_obj_name) && ('publish' != $current_status))  // Would default to Publish / Schedule
                || ($current_status_obj && empty($current_status_obj->public) && empty($current_status_obj->private) && ($current_status == $_post_status_obj_name)) // At last child status in alternate workflow, would default to staying there
                || ($current_status_obj && empty($current_status_obj->status_parent) && !self::getStatusChildren($current_status, $moderation_statuses))  // At a top-level alternate status with no children, would default to next top-level alternate status 
                ) {
                    if ($main_status_obj = self::getLastMainStatus($post_id)) {
                        $post_status_obj = $main_status_obj;
                        $_post_status_obj_name = (!empty($post_status_obj->name)) ? $post_status_obj->name : '';
                    }
                }
            }

            if (!empty($post_status_obj) && ('publish' == $_post_status_obj_name)) {
                if (!empty($_post) && !empty($_post->post_date_gmt) && time() < strtotime($_post->post_date_gmt . ' +0000')) {
                    $post_status_obj = get_post_status_object('future');
                    $_post_status_obj_name = (!empty($post_status_obj->name)) ? $post_status_obj->name : '';
                }
            }
        }

        if (empty($post_status_obj) || ('auto-draft' == $_post_status_obj_name)) {
            return get_post_status_object('draft');
        } else {
            if (!empty($post_status_obj) && !empty($post_status_obj->name) && ('publish' == $post_status_obj->name) 
                && !empty($default_privacy_obj) && ($default_privacy_obj->name != 'publish')
            ) {
                $post_status_obj = $default_privacy_obj;
            }
        }

        return $post_status_obj;
    }

    public static function defaultStatusProgression($post_id = 0, $args = [])
    {
        $defaults = ['return' => 'object', 'moderation_statuses' => [], 'can_set_status' => [], 'force_main_channel' => false, 'post_type' => '', 'post_status' => '', 'default_by_sequence' => null, 'skip_current_status_check' => false];
        $args = array_merge($defaults, (array)$args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $post_type = sanitize_key($post_type);

        if (!$status_obj = self::getNextStatusObject($post_id, $args)) {
            $status_obj = get_post_status_object('draft');
        }

        $_post_status_obj_name = (!empty($status_obj->name)) ? $status_obj->name : '';

        return ('name' == $return) ? $_post_status_obj_name : $status_obj;
    }

    public static function getLastMainStatus($post_id, $args = []) {
        $args = (array) $args;
        
        $post_type = (!empty($args['post_type'])) ? $args['post_type'] : get_post_field('post_type', $post_id);

		$current_status = (!empty($args['current_status'])) 
		? $args['current_status'] 
		: get_post_field(
			apply_filters('publishpress_statuses_status_field', 'post_status', $post_id),
			$post_id
			);

        $current_section = self::getStatusSection($current_status);

        if ('alternate' != $current_section) {
            if ('main' == $current_section) {
                return self::getCustomStatus($current_status);
            } else {
                return false;
            }
        }

		if (!$status_args = apply_filters('publishpress_statuses_edit_post_status_args', false, $post_id)) {
	        $status_args = ['moderation' => true, 'post_type' => $post_type];
	    }

        $valid_statuses = self::getPostStati($status_args, 'object');

        if ($last_main_status = get_post_meta($post_id, '_pp_statuses_last_main_status', true)) {
            if (!empty($valid_statuses[$last_main_status])) {
                // Make sure it's still in the main workflow
                if ('main' == self::getStatusSection($valid_statuses[$last_main_status])) {
                    if (empty($valid_statuses[$last_main_status]->public) && empty($valid_statuses[$last_main_status]->private)) {
                        $main_status_obj = $valid_statuses[$last_main_status];
                    }
                }
            }
        }

        $can_set_status = self::getUserStatusPermissions('set_status', $post_type, $valid_statuses);

        // If no valid last main status is stored, default to In Progress, Pending Review or Approved as applicable.
        if (empty($main_status_obj)) {
            foreach (['in-progress', 'pending', 'approved'] as $default_status_name) {
                                                                  // don't default to one of these unless current user can set it
                if (!empty($valid_statuses[$default_status_name]) && !empty($can_set_status[$default_status_name])) {
                    if ('main' == self::getStatusSection($valid_statuses[$default_status_name])) {
                        $main_status_obj = $valid_statuses[$default_status_name];
                        break;
                    }
                }
            }
        }

        $_return_status = (!empty($main_status_obj)) ? $main_status_obj->name : '';

        $return_status = apply_filters(
            'publishpress_statuses_return_status',
            $_return_status,
            compact('post_id', 'last_main_status', 'current_status', 'can_set_status')
        );

        if ($return_status != $_return_status) {
            // Validate the filtered status
            if (!empty($valid_statuses[$return_status])) {
                if ('main' == self::getStatusSection($valid_statuses[$return_status])) {
                    $main_status_obj = $valid_statuses[$return_status];
                }
            }
        }

        if (!empty($main_status_obj)) {
            return $main_status_obj;
        }

        return false;
    }

    public static function getStatusChildNames($status, $statuses = false)
    {
        return array_keys(
            self::getStatusChildren($status, $statuses)
        );
    }

    public static function getStatusChildren($status, $statuses = false)
    {
        if (!get_post_status_object($status)) {
            return [];
        }

        if (false === $statuses) {
            $statuses = self::getPostStati(['internal' => false], 'object');
        } else {
            $statuses = (array)$statuses;
        }

        $return = [];

        foreach ($statuses as $other_status_obj) {
            if (!empty($other_status_obj->status_parent) && ($status == $other_status_obj->status_parent)) {
                $key = (isset($other_status_obj->slug)) ? $other_status_obj->slug : strtolower($other_status_obj->name);
                $return [$key] = $other_status_obj;
            }
        }

        return $return;
    }

    public static function haveStatusPermission($perm_name, $post_type, $post_status, $args = [])
    {
        $perms = self::getUserStatusPermissions($perm_name, $post_type, $post_status, $args);
        return !empty($perms[$post_status]);
    }

    public static function getUserStatusPermissions($perm_name, $post_type, $check_statuses, $args = [])
    {
        global $wp_post_statuses, $current_user;

        if ('set_status' != $perm_name) {
            return [];
        }

        $check_statuses = (array) $check_statuses;

        $elem = reset($check_statuses);
        if (is_object($elem)) {
            $check_statuses = array_fill_keys(array_keys($check_statuses), true);
        } else {
            $check_statuses = array_fill_keys((array)$check_statuses, true);
        }

        if (\PublishPress_Statuses::isContentAdministrator()) {
            // This function will probably not get called for Administrator, but return valid response if it is.
            return $check_statuses;
        }

        // perm_name 'set_status'
        if (!$type_obj = get_post_type_object($post_type)) {
            return [];
        }

        $return = [];

        $moderate_any = !empty($current_user->allcaps['pp_moderate_any']);

        foreach (array_keys($check_statuses) as $_status) {
            if ($moderate_any && !empty($wp_post_statuses[$_status]) 
            && !empty($wp_post_statuses[$_status]->moderation)
            && empty($wp_post_statuses[$_status]->public)
            && empty($wp_post_statuses[$_status]->private)
            ) {
                // The pp_moderate_any capability allows a non-Administrator to set any moderation status
                $return[$_status] = true;
                continue;
            }

            if ('draft' == $_status) {
                $return['draft'] = true;
                continue;
            }

            $orig_status = (string) $_status;

            if (in_array($_status, ['publish', 'private', 'future'])) {
                $check_caps = [$type_obj->cap->publish_posts];
            } else {
                if ('_pending' == $_status) {
                    $_status = 'pending';
                }

                $status_change_cap = str_replace('-', '_', "status_change_{$_status}");
                $check_caps = [$status_change_cap];
                $check_caps = apply_filters('publishpress_statuses_required_caps', $check_caps, 'set_status', $_status, $post_type);
            }

            $return[$orig_status] = !array_diff($check_caps, array_keys($current_user->allcaps));
        }

        return $return;
    }

    public static function filterAvailablePostStatuses($statuses, $post_type, $post_status)
    {
        if (!$post_type) {
            return $statuses;
        }

        // convert integer keys to slugs
        foreach ($statuses as $status => $obj) {
            if (is_numeric($status)) {
                $statuses[$obj->name] = $obj;
                unset($statuses[$status]);
            }

            if (!empty($obj->post_type) && !in_array($post_type, $obj->post_type)) {
                unset($statuses[$obj->name]);
            }
        }

        $can_set_status = self::getUserStatusPermissions('set_status', $post_type, $statuses);

        $can_set_status[$post_status] = true;

        return array_intersect_key($statuses, array_filter($can_set_status));
    }

    public static function isContentAdministrator() {
        return current_user_can('administrator') || current_user_can('pp_administer_content') || (is_multisite() && is_super_admin());
    }

    public static function updateStatusNumRoles($check_status_name, $args = []) {
        if (!$status_num_roles = get_option('publishpress_statuses_num_roles')) {
            $status_num_roles = [];
        }

        if (empty($args['force_refresh']) && !empty($status_num_roles[$check_status_name])) {
            return $status_num_roles[$check_status_name];
        }

        $statuses = [$check_status_name];

        if (!$status_num_roles) {
            $statuses = self::getPostStati(['moderation' => true], 'names');
            

            $statuses = array_diff($statuses, ['future']);
            $statuses = array_unique(array_merge($statuses, [$check_status_name]));
        }

        $role_caps = [];
        $roles = \PP_Statuses_Functions::getRoles(true);

        foreach (array_keys($roles) as $role_name) {
            if ($role = get_role($role_name)) {
                $role_caps[$role_name] = $role->capabilities;
            }
        }

        foreach ($statuses as $status_name) {
            $cap_name = str_replace('-', '_', "status_change_{$status_name}");
            $admin_cap_name = 'manage_options';

            $status_num_roles[$status_name] = 0;

            foreach ($role_caps as $caps) {
                if (!empty($caps[$cap_name]) || !empty($caps[$admin_cap_name]) || !empty($caps['administrator'])) {
                    $status_num_roles[$status_name]++;
                }
            }
        }

        update_option('publishpress_statuses_num_roles', $status_num_roles);

        return (isset($status_num_roles[$check_status_name])) ? $status_num_roles[$check_status_name] : 0;
    }

    public static function getStatusSection($post_status) {
        if (is_scalar($post_status)) {
            if (!$status_obj = self::getCustomStatus($post_status)) {
                return false;
            }
        } elseif (is_object($post_status)) {
            $status_obj = $post_status;
        } else {
            return false;
        }

        if (!empty($status_obj->internal)) {
            return false;
        }
        
        if (empty($status_obj->moderation) 
        && empty($status_obj->public) 
        && empty($status_obj->private)
        && !in_array($post_status, ['draft', 'publish', 'private'])
        && !apply_filters('pp_statuses_status_section', false, $status_obj)
        ) {
            return false;
        }

        if (!empty($status_obj->disabled)) {
            return 'disabled';

        } elseif (self::isAlternateStatus($status_obj)) {
            return 'alternate';

        }
        
        return 'main';
    }

    public function fltApplySelectedPostStatus($post_status) {
        if ($this->doing_rest
        && (!defined('DOING_AUTOSAVE') || ! DOING_AUTOSAVE)
        ) {
            $rest = \PublishPress_Statuses\REST::instance();

            if (!empty($rest->params['pp_status_selection'])) {
                if ($post_type = \PP_Statuses_Functions::findPostType()) {
                    $check_status_selection = ('_pending' == $rest->params['pp_status_selection']) ? 'pending' : $rest->params['pp_status_selection'];
                    
                    if (\PublishPress_Statuses::haveStatusPermission('set_status', $post_type, $check_status_selection)) {
                        $post_status = $rest->params['pp_status_selection'];
                    }
                }
            }
        }

        return $post_status;
    }

    public function fltPostData($data, $postarr) {
        if (empty($postarr['ID'])) {
            return $data;
        }

        if (!empty($this->lock_status[$postarr['ID']])) {
            $data['post_status'] = $this->lock_status[$postarr['ID']];

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } elseif (!isset($_REQUEST['visibility'])) { // bypass this filter with Classic Editor
            $post_status = (empty($data['post_status'])) ? '' : $data['post_status'];
            $data['post_status'] = $this->fltPostStatus($post_status, ['post_id' => $postarr['ID']]);
        } 
        
        if ('publish' == $data['post_status']) {
            if (!empty($data['post_date_gmt'])) {
                if (time() < strtotime($data['post_date_gmt'] . ' +0000')) {
                    $data['post_status'] = 'future';
                }
            } elseif (!empty($postarr['post_date_gmt']) && time() < strtotime($postarr['post_date_gmt'] . ' +0000')) {
                $data['post_status'] = 'future';
            }
        }

        return apply_filters('publishpress_statuses_insert_post_data', $data, $postarr);
    }

    public function fltPostStatus($post_status, $args = []) {
        global $pagenow, $post;

        if (in_array($post_status, ['inherit', 'auto-draft', 'trash']) 
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || ('async-upload.php' == $pagenow)) {
            return $post_status;
        }

        $orig_status = $post_status;

        if ($_post_status = apply_filters('publishpress_statuses_override_post_status', false, $post_status, $args)) { 
            $post_status = $_post_status;
        } else {
            $post_status = $this->filterStatus($post_status);
        }

        if (defined('PRESSPERMIT_PRO_VERSION') && version_compare(PRESSPERMIT_PRO_VERSION, '4.6.4', '<')) {
            return $post_status;
        }

        $post_status = $this->flt_force_visibility($post_status);

        if ($orig_status != $post_status) {
            if (!$post_id = \PublishPress_Statuses::instance()->getCurrentSanitizePostID()) {
                $post_id = \PP_Statuses_Functions::getPostID();
            }

            if ($post_id) {
                if (!in_array($post_status, ['draft'])) {
                    $this->filtered_post_status[$post_id] = $post_status;
                }
            }
        }

        return $post_status;
    }

    public function fltAppyDefaultVisibility($status, $args = [])
    {
        global $pagenow;
        if (in_array($status, ['auto-draft', 'inherit', 'trash']) 
        || (!is_admin() && !$this->doing_rest)
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) 
        || ('async-upload.php' == $pagenow) 
        || ((strpos($status, 'wc-') === 0) && class_exists('WooCommerce') && !empty($_GET) && !empty($_GET['wc-ajax']) && ('checkout' == $_GET['wc-ajax']))     // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ) {
            return $status;
        }

        require_once(__DIR__ . '/PostSave.php');

        $orig_status = $status;

        $status = \PublishPress\Statuses\PostSave::fltPostStatus($status, $args);
        $status = \PublishPress\Statuses\PostSave::flt_force_visibility($status);

        if (!$post_id = \PublishPress_Statuses::instance()->getCurrentSanitizePostID()) {
            $post_id = \PP_Statuses_Functions::getPostID();
        }
        
        if ($post_id) {
            $post_type = get_post_field('post_type', $post_id);

            if (!in_array($post_type, self::instance()->get_enabled_post_types())) {
                return $orig_status;
            }
            
            if ($orig_status != $status) {
                if (!in_array($status, ['draft'])) {
                    $this->filtered_post_status[$post_id] = $status;
                }
            }

            if (!in_array($post_type, apply_filters('publishpress_statuses_basic_filtering_post_types', ['forum', 'topic', 'reply']))) {
                if (!\PublishPress_Statuses::haveStatusPermission('set_status', $post_type, $status)) {
                    if ($current_status = get_post_field('post_status', $post_id)) {
                        return $current_status;
                    }
                }
            }
        }

        return $status;
    }

    // If a public or private status is selected, change it to the specified force_visibility status
    public static function flt_force_visibility($post_status)
    {
        /*
        * On frontend requests, PP_Statuses_Functions::getPostID() can fall back
        * to the globally queried post. During wp_insert_post() sanitization this
        * may be unrelated to the post currently being inserted. In that case,
        * applying forced/default visibility can overwrite third-party internal
        * post statuses, such as Tutor LMS enrollment status "completed".
        */
        if (!is_admin()
        && !\PublishPress_Statuses::instance()->doing_rest
        && (!defined('DOING_AJAX') || !DOING_AJAX)
        && empty($_REQUEST['post']) && empty($_REQUEST['post_ID']) && empty($_REQUEST['post_id'])   // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ) {
            return $post_status;
        }

        if (defined('PRESSPERMIT_PRO_VERSION') && version_compare(PRESSPERMIT_PRO_VERSION, '4.6.4', '<')) {
            return $post_status;
        }
        
        if (function_exists('rvy_is_revision_status') && rvy_is_revision_status($post_status)) {
            return $post_status;
        }
        
        if ('public' == $post_status) {
            $post_status = 'publish';
        }

        if (!$status_obj = get_post_status_object($post_status)) {
            return $post_status;
        }

        static $done;
        if (!empty($done)) return $post_status;  // Important: if other plugin code inserts additional posts in response, don't filter those
        $done = true;

        if ($post_id = \PP_Statuses_Functions::getPostID()) {
            $_post = get_post($post_id);
        } else {
            $_post = false;
        }
        
        if (!\PP_Statuses_Functions::empty_POST('post_password')) {
            return $post_status;
        }
    
        if (!empty($_post) && !empty($_post->post_type)) {
            $post_type = $_post->post_type;
        } else {
            if (!$post_type = \PP_Statuses_Functions::findPostType()) {
                return $post_status;
            }
        }

        if (!in_array($post_type, self::instance()->get_enabled_post_types())) {
            return $post_status;
        }
        
        $options = \PublishPress_Statuses::instance()->options;

        if (!empty($_post) && !empty($options->force_default_privacy) && !empty($options->force_default_privacy[$post_type])) {
            $current_status = get_post_field('post_status', $_post->ID);
            $current_status_obj = get_post_status_object($current_status);

            if (!empty($current_status_obj) && (!empty($current_status_obj->public) || !empty($current_status_obj->private)) && !empty($_post)
                && !current_user_can('pp_unpublish_posts')
            ) {
                $lock_publication = (is_object($options) && !empty($options->lock_publication) && !empty($options->lock_publication)) ? $options->lock_publication : '';

                if ($lock_publication) {
                    $current_status_obj = get_post_status_object($current_status);

                    if (empty($current_status_obj) || (empty($current_status_obj->public) && empty($current_status_obj->private))) {
                        $lock_publication = false;
                    }
                }
            }

            if (!empty($status_obj->public) || !empty($status_obj->private) || !empty($lock_publication)) {
                $default_privacy = (is_object($options) && !empty($options->default_privacy) && !empty($options->default_privacy[$post_type])) ? $options->default_privacy[$post_type] : 'publish';
        
                if (get_post_status_object($default_privacy)) {    
                    if ($post_id = \PP_Statuses_Functions::getPostID()) {
                        $_post = get_post($post_id);
                    }
    
                    if (!empty($_post)) {
                    if ( $stored_status = get_post_meta($_post->ID, '_pp_original_status') ) {
                        $stored_status_obj = get_post_status_object($stored_status);
                    }
                    }
    
                    if (empty($stored_status_obj) || (empty($stored_status_obj->public) && empty($stored_status_obj->private))) {
                        $post_status = $default_privacy;
    
                        if (!empty($_post)) {
                        delete_post_meta($_post->ID, '_pp_original_status');
                        }
                    }
                }

                if (!empty($lock_publication)) {
                    \PublishPress_Statuses::instance()->lock_status[$_post->ID] = $post_status;
                }
            }
        }

        $post_status = apply_filters('publishpress_statuses_post_status', $post_status, $_post);

        return $post_status;
    }

    // Apply Workflow Progression to Post Status (for posts) or Post Mime Type (for revisions)
    public function filterStatus($post_status, $args = [])
    {
        global $current_user, $pagenow;

        if ('_pending' == $post_status) {
            $save_as_pending = true;
            $post_status = 'pending';   
        }

        if (('auto-draft' == $post_status)
        || in_array($post_status, ['inherit', 'trash'])
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || ('async-upload.php' == $pagenow)
        ) {
            return $post_status;
        }

        if (\PublishPress_Statuses::isUnknownStatus($post_status)
        && !in_array($post_status, ['public', '_public', '_pending'])
        ) {
            return $post_status;
        }


        if (isset($args['post_id'])) {
            $post_id = $args['post_id'];
        } else {
            if (!$post_id = \PublishPress_Statuses::instance()->useSanitizePostID()) {
                $post_id = \PP_Statuses_Functions::getPostID();
            }
        }

        if (isset($args['is_revision'])) {
            $is_revision = $args['is_revision'];
        } else {
            $is_revision = function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post_id);
        }

        if ($post_id && !$is_revision) {
            if (\PublishPress_Statuses::isPostBlacklisted($post_id)) {
                if (in_array($post_status, ['public', '_public'])) {
                    $post_date_gmt = get_post_field('post_date_gmt', $post_id);
                    
                    $post_status = (!empty($post_date_gmt) && time() < strtotime($post_date_gmt . ' +0000'))
                    ? 'future'
                    : 'publish';
                }

                return $post_status;
            }
        }

        if ($_post = get_post($post_id)) {
            $type_obj = get_post_type_object($_post->post_type);
        }

        if (empty($save_as_pending) && ('pending' == $post_status) && !empty($type_obj) && current_user_can($type_obj->cap->publish_posts)) {
            $save_as_pending = true;
        }

        $post_type = ($_post) ? $_post->post_type : \PP_Statuses_Functions::findPostType();

        if (!in_array($post_type, \PublishPress_Statuses::getEnabledPostTypes())) {
            return $post_status;
        }

        $is_administrator = \PublishPress_Statuses::isContentAdministrator();

        if ($stored_status = get_post_field(
        	apply_filters(
        		'publishpress_statuses_status_field', 
        		'post_status', 
        		$post_id
        	),
        	$post_id
        )) {
            $stored_status_obj = get_post_status_object($stored_status);
        }

        if ($doing_rest = defined('REST_REQUEST') && (!\PP_Statuses_Functions::empty_REQUEST('meta-box-loader') || $this->doing_rest))  {
            $rest = \PublishPress_Statuses\REST::instance();
        }

        if ($doing_rest && !empty($rest->params['pp_status_selection'])) {
            $check_status_selection = ('_pending' == $rest->params['pp_status_selection']) ? 'pending' : $rest->params['pp_status_selection'];
            
            if (\PublishPress_Statuses::haveStatusPermission('set_status', $post_type, $check_status_selection)) {
                $_post_status = $rest->params['pp_status_selection'];
            }
        } else {
            if (('_public' === \PP_Statuses_Functions::REQUEST_key('post_status')) && !$doing_rest) {
                $_post_status = 'public';
                $classic_explicit_publish = true;
            } else {
                $_post_status = \PP_Statuses_Functions::POST_key('post_status');
            }
        }

        $args['doing_rest'] = $doing_rest;

        $_post_status = apply_filters(
            'publishpress_statuses_filter_post_status',
            $_post_status,
            $post_id,
            $args
        );

        $this->logLastMainSection($_post_status, $post_id);

        if (\PP_Statuses_Functions::REQUEST_key('save') && !$doing_rest
        || ($doing_rest && !empty($rest->params['pp_status_selection']))
        ) {
            return $_post_status;
        }

        $selected_status = ($_post_status && ('publish' != $_post_status)) ? $_post_status : $post_status;

        if ('public' == $selected_status) {
            $selected_status = (!empty($_post) && !empty($_post->post_date_gmt) && time() < strtotime($_post->post_date_gmt . ' +0000')) 
			? 'future'
			: 'publish';
        }

        $post_status_obj = $this->get_post_status_object($selected_status);

        if (!$post_status_obj) {
            return $post_status;
        }

        if (!empty($post_status_obj->private)) { // This filter only deals with pre-publication workflow statuses
            return $post_status;
        }

        // Important: if other plugin code inserts additional posts in response, don't filter those
        static $done;
        if (!empty($done)) return $post_status;  
        $done = true;

        $post_status = $selected_status;

        $_post = get_post($post_id);

        if (empty($_post)) {
            return $post_status;
        }

        // Allow Publish / Submit button to trigger our desired workflow progression instead of Publish / Pending status.
        // Apply this change only if stored post is not already published or scheduled.
        // Also skip retain normal WP editor behavior if the newly posted status is privately published or future.
        if (in_array($selected_status, ['publish', 'pending', 'future']) && !in_array($stored_status, ['publish', 'private', 'future']) 
            && empty($classic_explicit_publish)
            && empty($stored_status_obj->public) && empty($stored_status_obj->private)
            && !$this->workflow_disabled
        ) {
            // Gutenberg REST gives no way to distinguish between Publish and Save request. Treat as Publish (next workflow progression) if any of the following:
            //  * user cannot set pending status
            //  * already set to pending status
            if (
                empty($save_as_pending) /* Pending status was not explicitly selected by dropdown / checkbox */
                || ! defined('REST_REQUEST')
                || ! $doing_rest
                || (('publish' == $selected_status) && !\PublishPress_Statuses::haveStatusPermission('set_status', $_post->post_type, 'pending')) 
            ) {
                do_action('publishpress_statuses_workflow_progression');

                // Users who have publish capability do not need defaultStatusProgression() functionality, so do not get the status="_pending" dropdown option to indicate an explicit Save as Pending request
                if ('pending' == $selected_status) {
                    $type_obj = get_post_type_object($_post->post_type);
                    $can_publish = ($type_obj) ? !empty($current_user->allcaps[$type_obj->cap->publish_posts]) : false;
                }

                require_once(__DIR__ . '/REST.php');
                $rest = \PublishPress_Statuses\REST::instance();

                if (!empty($rest->params['pp_workflow_action'])) {
                    $workflow_action = $rest->params['pp_workflow_action'];
                } else {
                    if (!empty($args['workflow_action'])) {
                        $workflow_action = $args['workflow_action'];
                    } elseif (!empty($_REQUEST['pp_statuses_workflow_selection'])) {                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        $workflow_action = sanitize_key($_REQUEST['pp_statuses_workflow_selection']);   // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    } else {
                        $workflow_action = false;
                    }
                }

                $selected_status_dropdown = $selected_status;

                if ('_pending' == $selected_status_dropdown) {
                    $selected_status_dropdown = 'pending';
                }

                $post_type = ($post_id) ? '' : \PP_Statuses_Functions::findPostType();

                switch ($workflow_action) {
                    case 'specified':
                        $post_status = $selected_status_dropdown;
                        break;

                    case 'current':
                        $post_status = $stored_status;
                        break;

                    case 'next':
                        $post_status = \PublishPress_Statuses::defaultStatusProgression($post_id, ['default_by_sequence' => true, 'return' => 'name', 'post_type' => $post_type]);
                        break;

                    case 'max':
                        $post_status = \PublishPress_Statuses::defaultStatusProgression($post_id, ['default_by_sequence' => false, 'return' => 'name', 'post_type' => $post_type]);
                        break;

                    default:
                        if ((($doing_rest && !empty($rest->params['pp_statuses_selecting_workflow']))
                        || !\PP_Statuses_Functions::empty_POST('publish'))
                        || ($is_revision && !empty($_POST) && !empty($_POST['originalaction']) && ('editpost' == $_POST['originalaction']))     // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
                        ) {
                            if (empty($save_as_pending) 
                            && (($selected_status != $stored_status) || (('pending' == $selected_status) && !$can_publish) || $is_revision)
                            ) {
                                $_args = ['return' => 'name', 'post_type' => $post_type];

                                // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
                                if (!empty($_REQUEST['pp_statuses_bypass_sequence'])) {
                                    $type_obj = get_post_type_object($_post->post_type);

                                    if ((current_user_can('administrator') 
                                    || ($type_obj && !empty($type_obj->cap->publish_posts) && current_user_can($type_obj->cap->publish_posts)) 
                                    || current_user_can('pp_bypass_status_sequence'))
                                    && (!isset($current_user->allcaps['pp_bypass_status_sequence']) || !empty($current_user->allcaps['pp_bypass_status_sequence'])) // allow explicit blockage
                                    ) {
                                        $_args['default_by_sequence'] = false;
                                    }
                                } 

                                // Submission status inferred using same logic as UI generation (including permission check)
                                $post_status = \PublishPress_Statuses::defaultStatusProgression($post_id, $_args);
                            }
                        }
                }

                $filtered_status = apply_filters('presspermit_selected_moderation_status', $post_status, $post_id);

                if (($filtered_status != $post_status) 
                && \PublishPress_Statuses::haveStatusPermission('set_status', $_post->post_type, $filtered_status)
                ) {
                    $post_status = $filtered_status;
                }
            }

        // Final permission check to cover all other custom statuses (draft, publish and private status capabilities are already checked by WP)
        } elseif (($is_revision && !defined('PUBLISHPRESS_STATUSES_PRO_VERSION')) 
        || (!empty($_post) && !$is_administrator && ($post_status != $stored_status) 
        && !in_array($post_status, ['draft', 'publish', 'private']) 
        && (!\PublishPress_Statuses::haveStatusPermission('set_status', $_post->post_type, $post_status))
        )) {
            $post_status = ($stored_status) ? $stored_status : 'draft';
        }

        // Log last main status for this post, to allow for sensible return from alternate workflows
        $this->logLastMainSection($post_status, $post_id);

        return $post_status;
    }

    private function logLastMainSection($post_status, $post_id) {
        if (!empty($post_id)) {
            $status_section = self::getStatusSection($post_status);

            if ('main' == $status_section) {
                if ($post_status_obj = get_post_status_object($post_status)) {
                    if (empty($post_status_object->public) && empty($post_status_object->private)) {
                        update_post_meta($post_id, '_pp_statuses_last_main_status', $post_status);
                    }
                }
            }
        }
    }

    // log request and handler parameters for possible reference by subsequent PP filters; block unpermitted create/edit/delete requests 
    function fltRestPreDispatch($rest_response, $rest_server, $request)
    {
        require_once(__DIR__ . '/REST.php');
        $rest = \PublishPress_Statuses\REST::instance();
        $rest_response = $rest->pre_dispatch($rest_response, $rest_server, $request);

        if (!empty($rest->is_posts_request)) {
            $this->doing_rest = true;
        }

        return $rest_response;
    }

    function actRestInit()
    {
        register_post_status(
            '_pending', 
            [
                'label'                     => esc_html(__('Pending')),
                'label_count'               => false,
                'labels' => (object) [
                    'save_as' => __('Save as Pending'),
                    'publish' => __('Submit for Review')
                ],
                'exclude_from_search'       => true,
                'public'                    => false,
                'internal'                  => false,
                'protected'                 => true,
                'private'                   => false,
                'publicly_queryable'        => false,
                'show_in_admin_status_list' => false,
                'show_in_admin_all_list'    => false,
            ]
        );

        foreach(get_post_types(['public' => true, 'show_ui' => true], 'names', 'or') as $post_type) {
            register_rest_field( $post_type, 'pp_statuses_selecting_workflow', array(
                'get_callback' => [__CLASS__, 'getSelectingWorkflow'],
                'update_callback' => [__CLASS__, 'updateSelectingWorkflow'],
                'schema' => [
                    'description'   => 'Selecting Workflow',
                    'type'          => 'boolean',
                    'context'       =>  ['view','edit']
                    ]
                )
            );

            register_rest_field( $post_type, 'pp_workflow_action', array(
                'get_callback' => [__CLASS__, 'getWorkflowAction'],
                'update_callback' => [__CLASS__, 'updateWorkflowAction'],
                'schema' => [
                    'description'   => 'Workflow Action',
                    'type'          => 'string',
                    'context'       =>  ['view','edit']
                    ]
                )
            );

            register_rest_field( $post_type, 'pp_status_selection', array(
                'get_callback' => [__CLASS__, 'getStatusSelection'],
                'update_callback' => [__CLASS__, 'updateStatusSelection'],
                'schema' => [
                    'description'   => 'StatusSelection',
                    'type'          => 'string',
                    'context'       =>  ['view','edit']
                    ]
                )
            );
        }
    }

    public static function getSelectingWorkflow($object) {
        return false;
    }

    public static function updateSelectingWorkflow($value, $object) {
        return false;
    }

    public static function getWorkflowAction( $object ) {
        $default_action = '';
        $post_status = '';

        if ($post_id = \PP_Statuses_Functions::getPostID()) {
            if ($post_status = get_post_field('post_status', $post_id)) {
                if ($status_obj = get_post_status_object($post_status)) {
                    if (in_array($post_status, ['publish', 'private', 'future']) || !empty($stored_status_obj->public) || !empty($stored_status_obj->private)) {
                        $default_action = 'current';
                    }
                }
            }

            $post_type = get_post_field('post_type', $post_id);
        } else {
            $post_type = \PP_Statuses_Functions::findPostType();
        }

        if (!$default_action) {
            $default_status = \PublishPress_Statuses::defaultStatusProgression($post_id, ['return' => 'name', 'post_type' => $post_type]);

            if ($default_status != $post_status) {
                if (\PublishPress_Statuses::instance()->workflow_by_sequence) {
                    $default_action = 'next';
                } else {
                    $default_action = 'max';
                }
            }
        }
        
        return $default_action;
    }

    public static function updateWorkflowAction( $value, $object ) {
        return false;
    }

    public static function getStatusSelection( $object ) {
        $status_selection = '';

        if ($post_id = \PP_Statuses_Functions::getPostID()) {
            if ($post_status = get_post_field('post_status', $post_id)) {
                if (get_post_status_object($post_status)) {
                    $status_selection = $post_status;
                }
            }
        }

        return $status_selection;
    }

    public static function updateStatusSelection( $value, $object ) {
        return false;
    }

    public static function isPluginPage() {
        static $pp_plugin_page = null;

        if (is_null($pp_plugin_page)) {
            $pp_plugin_page = (is_admin() && !empty($_REQUEST['page']) && (false !== strpos(sanitize_key($_REQUEST['page']), 'publishpress-statuses')))   // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                ? sanitize_key($_REQUEST['page'])                                                                                                         // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                : false;
        }

        return $pp_plugin_page;
    }
}
}
