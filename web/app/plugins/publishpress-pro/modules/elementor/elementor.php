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

if (!class_exists('PP_Elementor')) {
    /**
     * class PP_Elementor
     */
    class PP_Elementor extends PP_Module
    {
        const SETTINGS_SLUG = 'pp-elementor-settings';

        public $module_name = 'elementor';

        public $module;

        /**
         * Construct the PP_Elementor class
         */
        public function __construct()
        {
            $this->viewsPath = dirname(dirname(dirname(__FILE__))) . '/views';

            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'                => __('Elementor Integration', 'publishpress-pro'),
                'short_description'    => false,
                'extended_description' => false,
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-feedback',
                'slug'                 => 'elementor',
                'default_options'      => [
                    'enabled' => 'on',
                ],
                'options_page'         => false,
                'autoload'             => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters(
                'pp_elementor_default_options',
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
            add_filter('publishpress_notif_shortcode_post_data', [$this, 'elementorEditLinkShortcode'], 10, 3);
            add_filter('publishpress_notifications_shortcode_post_fields', [$this, 'addElementorShortcodePostFields']);
        }

        public function elementorEditLinkShortcode($custom, $item, $post)
        {
            if ($item === 'elementor_edit_link') {
                $custom = esc_url(
                    htmlspecialchars_decode(
                        admin_url('post.php?post=' . $post->ID . '&action=elementor')
                    )
                );
            }

            return $custom;
        }

        public function addElementorShortcodePostFields($fields)
        {
            $fields[] = 'elementor_edit_link';

            return $fields;
        }
    }
}
