<?php
/**
 * @package PublishPress
 * @author  PublishPress
 *
 * Copyright (c) 2022 PublishPress
 *
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * This file is part of PublishPress
 *
 * PublishPress is free software: you can redistribute it and/or modify
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

if (!class_exists('PP_Revision_Integration')) {
    /**
     * class PP_Revision_Integration
     */
    #[\AllowDynamicProperties]
    class PP_Revision_Integration extends PP_Module
    {

        const SETTINGS_SLUG = 'pp-revision-integration-settings';

        public $module_name = 'revision_integration';

        public $module;
        public $module_url;

        /**
         * Construct the PP_Revision_Integration class
         */
        public function __construct()
        {

            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'                => __('PublishPress Revision Integration', 'publishpress-pro'),
                'short_description'    => false,
                'extended_description' => false,
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-list-view',
                'slug'                 => 'revision-integration',
                'default_options'      => [
                    'enabled' => 'on',
                ],
                'options_page'         => false,
                'autoload'             => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters(
                'pp_revision_integration_default_options',
                $args['default_options']
            );

            $this->module = PublishPress()->register_module($this->module_name, $args);

            parent::__construct();
        }

        /**
         * Initialize the module. Conditionally loads if the module is enabled
         */
        public function init()
        {
            // filter query
            add_filter('pp_calendar_posts_query_args', [$this, 'addRevisionQuery'], 10, 4);
            add_filter('PP_Content_Board_posts_query_args', [$this, 'addRevisionQuery'], 10, 4);
            add_filter('PP_Content_Overview_posts_query_args', [$this, 'addRevisionQuery'], 10, 4);

            // filter displayed title
            add_action('pp_calendar_post_title_html', [$this, 'filterRevisionPostTitle'], 10, 2);
            add_action('pp_content_overview_post_title_html', [$this, 'filterRevisionPostTitle'], 10, 2);
            add_action('pp_content_board_post_title_html', [$this, 'filterRevisionPostTitle'], 10, 2);

            // add show revision button
            add_action('pp_content_board_filter_after_me_mode', [$this, 'addShowRevisionButton']);
            add_action('pp_content_overview_filter_after_me_mode', [$this, 'addShowRevisionButton']);
            add_action('pp_content_calendar_filter_after_me_mode', [$this, 'addShowRevisionButton']);

            // enqueue scripts
            add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);

            // add filter hidden fieds
            add_action('pp_content_board_filter_hidden_fields', [$this, 'addFilterInpput']);
            add_action('pp_content_overview_filter_hidden_fields', [$this, 'addFilterInpput']);
            add_action('pp_content_calendar_filter_hidden_fields', [$this, 'addFilterInpput']);

            // add filter reset fieds
            add_action('pp_content_board_filter_reset_hidden_fields', [$this, 'addFilterResetInpput']);
            add_action('pp_content_overview_filter_reset_hidden_fields', [$this, 'addFilterResetInpput']);
            // Add revision to calendar datas
            add_filter('publishpress_content_calendar_datas', [$this, 'revisionCalendarData']);
            // Add revision to calendar form
            add_filter('publishpress_calendar_post_type_fields', [$this, 'revisionCalendarFormField'], 10, 2);

            add_filter('publishpress_calendar_filter_options_args', [$this, 'fltRevisionStatusFilterOptionsArgs']);
            add_filter('publishpress_calendar_custom_filter', [$this, 'fltRevisionStatusCustomFilterUI'], 10, 3);
            // Filter Calendar filter get
            add_filter('publishpress_calendar_get_filters', [$this, 'fltCalendarFilterResult'], 10, 2);

            // Filter Content Overview datas
            add_filter('publishpress_content_overview_datas', [$this, 'fltContentOverviewDatas']);

            add_filter('publishpress_content_overview_filter_options_args', [$this, 'fltRevisionStatusFilterOptionsArgs']);

            // Filter content overview custom filter
            add_filter('publishpress_content_overview_custom_filter', [$this, 'filterContentOverviewCustomFilter'], 10, 3);
        }

        /**
         * Install function
         *
         * @since 0.7
         */
        public function install()
        {
        }

        /**
         * Upgrade our data in case we need to
         *
         * @since 0.7
         */
        public function upgrade($previous_version)
        {
        }


        // Used by Content Calendar and Content Overview
        public function fltRevisionStatusFilterOptionsArgs ($args) {
            $select_id = (isset($args['select_id'])) ? $args['select_id'] : '';

            switch ($select_id) {
                case 'post_status':
                case 'revision_status':
                    if (function_exists('rvy_revision_statuses')) {
                        if (class_exists('PublishPress_Statuses') && defined('PUBLISHPRESS_STATUSES_PRO_VERSION')) {
                            $args['revision_statuses'] = \PublishPress_Statuses::instance()->getPostStatuses(['for_revision' => true], 'object');
                        } else {
                            $args['revision_statuses'] = rvy_revision_statuses(['output' => 'object']);
                        }
                    } else {
                        $args['revision_statuses'] = [];
                    }

                    if ($args['revision_statuses'] && !empty($args['post_statuses'])) {
                        foreach ($args['post_statuses'] as $k => $status_obj) {
                            if (in_array($status_obj->slug, array_keys($args['revision_statuses']))) {
                                unset($args['post_statuses'][$k]);
                            }
                        }
                    }
            }

            return $args;
        }

        // Used by Content Calendar and Content Overview
        public function fltRevisionStatusCustomFilterUI ($custom_filter_set, $select_id, $args) {
            if ('revision_status' == $select_id) {
                $filters = (isset($args['user_filters'])) ? $args['user_filters'] : [];
                $revision_statuses = (isset($args['revision_statuses'])) ? $args['revision_statuses'] : [];

                $filter_label   = esc_html__('Revision Status', 'publishpress-pro');
                ?>
                <select id="revision_status" name="revision_status"><!-- Status selectors -->
                    <option value=""><?php
                        _e('All statuses', 'publishpress-pro'); ?></option>
                    <?php
                    $selected_value = '';
                    foreach ($revision_statuses as $post_status) {
                        if ($post_status->name == $filters['revision_status']) {
                            $selected_value = $post_status->label;
                        }
                        echo "<option value='" . esc_attr($post_status->name) . "' " . selected(
                                $post_status->name,
                                $filters['revision_status']
                            ) . ">" . esc_html($post_status->label) . "</option>";
                    }
                    ?>
                </select>
                <?php

                return compact('selected_value', 'filter_label');
            }

            return $custom_filter_set;
        }


        /**
         * Enqueue scripts and stylesheets for the admin pages.
         */
        public function enqueueAdminScripts()
        {
            if (isset($_GET['page']) && in_array($_GET['page'], ['pp-content-board', 'pp-content-overview', 'pp-calendar'])) {
                wp_enqueue_script(
                    'publishpress-revision-integration',
                    PUBLISHPRESS_PRO_PLUGIN_URL . 'modules/revision-integration/assets/js/revision-integration.js',
                    ['jquery'],
                    PUBLISHPRESS_PRO_VERSION,
                    true
                );
            }
        }

        /**
         * Check if revision settings is checked/enabled 
         * 
         * 
         * @param array $user_filters
         * 
         * @return bool
         */
        private function hideRevision($user_filters) {
            $hide_revision = false;
            
            if ( isset($user_filters['hide_revision']) && (int)$user_filters['hide_revision'] > 0 ) {
                // default state
                $hide_revision = true;
            }

            return $hide_revision;
        }

        /**
         * Filter query to add revision query
         *
         * @param array $args
         * @param string $context
         * @param array $enabled_filters
         * @param array $user_filters
         * 
         * @return array
         */
        public function addRevisionQuery($args, $context, $enabled_filters, $user_filters)
        {
            if (!$this->hideRevision($user_filters)) {
                $args['is_revisions_query'] = true;
            }
            
            return $args;
        }

        /**
         * Filter query to add revision query
         *
         * @param string $post_title
         * @param object $post
         * 
         * @return string
         */
        public function filterRevisionPostTitle($post_title, $post)
        {
            if (!empty($post->post_mime_type) && in_array($post->post_mime_type, apply_filters('publishpress_calendar_revision_statuses', ['draft-revision','pending-revision','future-revision']))) {
                $revision_prefix = (defined('PUBLISHPRESS_CALENDAR_REVISION_PREFIX')) ? PUBLISHPRESS_CALENDAR_REVISION_PREFIX : __('[Rev]', 'publishpress-pro');
                
                $revision_title = pp_revisions_status_label($post->post_mime_type, 'name');
                $post_title = '<span class="title-tag pp-title-tooltip" title="'. $revision_title .'">' . $revision_prefix . '</span> ' . $post_title;
            } else {
                $status_obj = get_post_status_object($post->post_status);
                $status_label = (!empty($status_obj)) ? $status_obj->label : $post->post_status;
                $post_title = '<span class="title-tag pp-title-tooltip" title="'. $status_label .'">' . $post_title . '</span>';
            }
            
            return $post_title;
        }

        /**
         * Add show revision button
         *
         * @param array $user_filters
         * 
         * @return void
         */
        public function addShowRevisionButton($user_filters)
        {
            $active_hide_revision = !$this->hideRevision($user_filters) ? 'active-filter' : '';
            ?>
            <div class="item action pp-show-revision-btn <?php echo esc_attr($active_hide_revision); ?>">
                <span class="dashicons dashicons-backup"></span> <?php esc_html_e('Show Revisions', 'publishpress-pro'); ?>
            </div>
            <?php
        }

        /**
         * Add show revision button
         *
         * @param array $user_filters
         * 
         * @return void
         */
        public function addFilterInpput($user_filters)
        {
            $hide_revision = $this->hideRevision($user_filters) ? 1 : 0;
            $revision_status = (!empty($user_filters['revision_status'])) ? $user_filters['revision_status'] : '';
            ?>
            <input type="hidden" name="hide_revision" id="pp_hide_revision_input" value="<?php echo esc_attr($hide_revision); ?>" />
            <input type="hidden" name="revision_status" id="pp_revision_status" value="<?php echo esc_attr($revision_status); ?>" />
            <?php
        }

        /**
         * Add reset input
         *
         * @param array $user_filters
         * 
         * @return void
         */
        public function addFilterResetInpput($user_filters)
        {
            ?>
            <input type="hidden" name="hide_revision" value=""/>
            <input type="hidden" name="revision_status" value=""/>
            <?php
        }

        /**
         * Add revision to calendar datas
         * 
         * @param array $datas
         * 
         * @return array
         */
        public function revisionCalendarData($datas) {
            if (!function_exists('rvy_in_revision_workflow')) {
                unset($datas['content_calendar_filters']['revision_status']);
                unset($datas['content_calendar_custom_filters']['revision_status']);
            } else {
                if (isset($datas['content_calendar_filters']['post_status'])) {
                    $datas['content_calendar_filters']['revision_status'] = esc_html__('Revision Status', 'publishpress-pro');
                } else {
                    unset($datas['content_calendar_filters']['revision_status']);
                }
                
                if (isset($datas['content_calendar_custom_filters']['post_status'])) {
                    $datas['content_calendar_custom_filters']['revision_status'] = esc_html__('Revision Status', 'publishpress-pro');
                } else {
                    unset($datas['content_calendar_custom_filters']['revision_status']);
                }
            }

            return $datas;
        }

        /**
         * Add revision to calendar form
         * 
         * @param array $fields
         * @param array $post_status_options
         * 
         * @return array
         */
        public function revisionCalendarFormField ($fields, $post_status_options) {
            $fields['revision_status'] = [
                'label' => __('Revision Status', 'publishpress-pro'),
                'value' => 'draft',
                'type' => 'status',
                'options' => $post_status_options
            ];

            return $fields;
        }

        /**
         * Filter Calendar filter get
         * 
         * @param array $calendar_filters
         * @param array $args
         * 
         * @return array
         */
        public function fltCalendarFilterResult($calendar_filters, $args) {
            // force Revision Status selector to display right after Post Status
            if (isset($calendar_filters['revision_status'])) {
                $revision_status_filter = $calendar_filters['revision_status'];

                $_calendar_filters = [];

                foreach ($calendar_filters as $select_id => $filter) {
                    if ('revision_status' == $select_id) {
                        continue;
                    }

                    $_calendar_filters[$select_id] = $filter;

                    if ('post_status' == $select_id) {
                        $_calendar_filters['revision_status'] = $revision_status_filter;
                    }
                }

                $calendar_filters = $_calendar_filters;
            }

            return $calendar_filters;
        }

        /**
         * Filter Content Overview datas
         * 
         * @param array $datas
         * 
         * @return array
         */
        public function fltContentOverviewDatas($datas) {
            if (!function_exists('rvy_in_revision_workflow')) {
                unset($datas['content_overview_filters']['revision_status']);
                unset($datas['content_overview_custom_filters']['revision_status']);
            } else {
                if (isset($datas['content_overview_filters']['post_status'])) {
                    $datas['content_overview_filters']['revision_status'] = esc_html__('Revision Status', 'publishpress-pro');
                } else {
                    unset($datas['content_overview_filters']['revision_status']);
                }
                
                if (isset($datas['content_overview_custom_filters']['post_status'])) {
                    $datas['content_overview_custom_filters']['revision_status'] = esc_html__('Revision Status', 'publishpress-pro');
                } else {
                    unset($datas['content_overview_custom_filters']['revision_status']);
                }
            }

            return $datas;
        }

        /**
         * Filter content overview custom filter
         * 
         * @param mixed $custom_filter_set
         * @param string $select_id
         * @param array $args
         * 
         * @return array
         */
        public function filterContentOverviewCustomFilter($custom_filter_set, $select_id, $args) {
            // Don't add the Revision Status UI if not dispalying revisions
            if (!empty($_REQUEST['hide_revision'])) {
                if (apply_filters('publishpress_content_overview_suppressing_revisions_ui', true, $args)) {
                    return $custom_filter_set;
                }

            } else {
                global $current_user;

                $user_filters = apply_filters(
                    'pp_get_user_meta', 
                    get_user_meta($current_user->ID, 'PP_Content_Overview_filters', true), 
                    $current_user->ID, 
                    'PP_Content_Overview_filters', 
                    true
                );

                if (!empty($user_filters['hide_revision'])) {
                    if (apply_filters('publishpress_content_overview_suppressing_revisions_ui', true, $args)) {
                        return $custom_filter_set;
                    }
                }
            }

            $args['plugin_page'] = 'pp-content-overview';
            return $this->fltRevisionStatusCustomFilterUI($custom_filter_set, $select_id, $args);
        }

    }
}