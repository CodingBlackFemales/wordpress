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

use PublishPress\NotificationsLog\NotificationsLogModel;

if (!class_exists('PP_Notifynetworkadmin')) {
    /**
     * class PP_Notifynetworkadmin
     */
    #[\AllowDynamicProperties]
    class PP_Notifynetworkadmin extends PP_Module
    {

        const SETTINGS_SLUG = 'pp-notifynetworkadmin-settings';

        public $module_name = 'notifynetworkadmin';

        public $module;

        /**
         * Construct the PP_Notifynetworkadmin class
         */
        public function __construct()
        {
            $this->viewsPath = dirname(dirname(dirname(__FILE__))) . '/views';

            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'                => __('Notify Network Admin', 'publishpress-pro'),
                'short_description'    => false,
                'extended_description' => false,
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-feedback',
                'slug'                 => 'notifynetworkadmin',
                'default_options'      => [
                    'enabled' => 'on',
                ],
                'options_page'         => false,
                'autoload'             => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters(
                'pp_notify_network_admin_default_options',
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
            if (is_multisite()) {
                add_filter('publishpress_notif_workflow_steps_receiver', [$this, 'filterStepReceivers']);
                add_action('publishpress_notifications_log_registered', [$this, 'registerClonedLog'], 10, 2);
            }
        }

        /**
         * Load default editorial metadata the first time the module is loaded
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

        public function filterStepReceivers($classes)
        {
            $classes[] = '\\PublishPressPro\\Notifications\\Workflow\\Step\\Receiver\\Network_Admin';

            return $classes;
        }

        public function registerClonedLog($logId, $commentData)
        {
            if ('network_admin' === $commentData['comment_meta'][NotificationsLogModel::META_NOTIF_RECEIVER_GROUP]) {
                $mainSiteId = get_main_site_id();

                if ($mainSiteId !== $commentData['comment_meta'][NotificationsLogModel::META_NOTIF_BLOG_ID]) {
                    switch_to_blog($mainSiteId);

                    $commentData['comment_meta'] = [
                        NotificationsLogModel::META_NOTIF_STATUS    => 'cloned',
                        NotificationsLogModel::META_NOTIF_BLOG_ID   => sanitize_text_field($commentData['comment_meta'][NotificationsLogModel::META_NOTIF_BLOG_ID]),
                        NotificationsLogModel::META_NOTIF_PARENT_ID => (int)$logId,
                    ];

                    wp_insert_comment($commentData);

                    restore_current_blog();
                }
            }
        }
    }
}
