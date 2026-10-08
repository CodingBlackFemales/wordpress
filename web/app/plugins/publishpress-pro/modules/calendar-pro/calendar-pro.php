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

if (!class_exists('PP_Calendar_Pro')) {
    /**
     * class PP_Calendar_Pro
     */
    #[\AllowDynamicProperties]
    class PP_Calendar_Pro extends PP_Module
    {

        const SETTINGS_SLUG = 'pp-calendar-pro-settings';

        public $module_name = 'calendar_pro';

        public $module;
        public $module_url;

        /**
         * Construct the PP_Calendar_Pro class
         */
        public function __construct()
        {

            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'                => __('Content Calendar Pro', 'publishpress-pro'),
                'short_description'    => false,
                'extended_description' => false,
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-list-view',
                'slug'                 => 'calendar-pro',
                'default_options'      => [
                    'enabled' => 'on',
                ],
                'options_page'         => false,
                'autoload'             => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters(
                'pp_calendar_pro_default_options',
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
            add_filter('publishpress_content_calendar_datas', [$this, 'filterContentBoardData']);
            add_filter('publishpress_content_calendar_form_filters', [$this, 'filterContentBoardFilterForm'], 10, 2);
            add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
            add_action('wp_ajax_publishpress_content_calendar_update_post', [$this, 'updatePost']);
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

        /**
         * Enqueue scripts and stylesheets for the admin pages.
         */
        public function enqueueAdminScripts()
        {
            if (isset($_GET['page']) && $_GET['page'] === 'pp-calendar') {
                wp_enqueue_style(
                    'publishpress-calendar-pro',
                    PUBLISHPRESS_PRO_PLUGIN_URL . 'modules/calendar-pro/assets/css/calendar-pro.css',
                    [],
                    PUBLISHPRESS_PRO_VERSION
                );

                add_thickbox();
            }
        }

        public function updatePost() {
            global $publishpress;
    
            $response['status']  = 'error';
            $response['content'] = esc_html__('An error occured', 'publishpress-pro');

            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), 'publishpress-calendar-get-data')) {
                $response['content'] = esc_html__('Error validating nonce. Please reload this page and try again.', 'publishpress-pro');
            } elseif (empty($_POST['post_id'])) {
                $response['content'] = esc_html__('Invalid form request.', 'publishpress-pro');
            } else {
                $post_id            = (int) $_POST['post_id'];
                $post_title         = sanitize_text_field($_POST['post_title']);
                $post_date          = sanitize_text_field($_POST['post_date']);
                $post_author        = (is_array($_POST['post_author'])) ? array_map('sanitize_text_field', $_POST['post_author']) : sanitize_text_field($_POST['post_author']);
                $post_status        = sanitize_text_field($_POST['post_status']);
                $post_taxonomies    = map_deep($_POST['post_taxonomies'], 'sanitize_text_field');

                $post_data          = get_post($post_id);

                if (!is_object($post_data) || !isset($post_data->post_type)) {
                    $response['content'] = esc_html__('Error fetching post data.', 'publishpress-pro');
                } elseif (!current_user_can('edit_post', $post_id)) {
                    $response['content'] = esc_html__('You do not have permission to edit this post.', 'publishpress-pro');
                } else {
                    $post_type_object = get_post_type_object($post_data->post_type);
                    if (empty($post_type_object->cap->edit_posts) || !current_user_can($post_type_object->cap->edit_posts)) {
                        $response['content'] = esc_html__('You do not have permission to edit this post.', 'publishpress-pro');
                    } else {
                        // there seems to be a bug with the date time picker
                        $post_date = str_replace('H:i:s ', '', $post_date);
                        $post_date = date('Y-m-d H:i:s', strtotime($post_date));

                        if (! is_array($post_author)) {
                            $post_author = [$post_author];
                        }

                        $real_author = false;
                        foreach ($post_author as $author_id) {
                            if ($author_id > 0) {
                                $real_author = $author_id;
                            }
                        }

                        $post_args = [
                            'ID'          => $post_id,
                            'post_status' => $post_status,
                            'post_title'  => $post_title,
                            'post_date'  => $post_date,
                            'post_date_gmt'  => get_gmt_from_date($post_date)
                        ];
                        if ($real_author) {
                            $post_args['post_author'] = $real_author;
                        }
                        // update post
                        wp_update_post($post_args);
                        $post_data = get_post($post_id);

                        // set taxonomies
                        $taxonomy_terms = [];
                        foreach ($post_taxonomies as $taxonomy => $options) {
                            $taxonomy_object = get_taxonomy($taxonomy);
                            if (!empty($taxonomy_object->hierarchical) && !empty($options)) {
                                // hierarchical taxonomy take ids
                                $taxonomy_options = [];
                                foreach ($options as $option) {
                                    $option_term = term_exists($option, $taxonomy);
                                    if (is_array($option_term) && isset($option_term['term_id'])) {
                                        $taxonomy_options[] = $option_term['term_id'];
                                    }
                                }
                                wp_set_post_terms($post_id, $taxonomy_options, $taxonomy);
                            } elseif (!empty($options)) {
                                wp_set_post_terms($post_id, $options, $taxonomy);
                            }
                            $terms = get_the_terms($post_id, $taxonomy);
                            $taxonomy_terms[$taxonomy] = (!empty($terms) && !is_wp_error($terms)) ? $terms : [];
                        }

                        do_action('publishpress_calendar_after_create_post', $post_id, $post_author);

                        $post_author = get_userdata($post_data->post_author);
                        $author_name = is_object($post_author) ? $post_author->display_name : '';
                        $author_name = apply_filters('the_author', $author_name);
        
                        $author_name = apply_filters('publishpress_content_overview_author_column', $author_name, $post_data);
                        
                        $response['author_markup']  = $this->get_author_markup($post_data,true);
                        $response['author_name']    = $author_name;
                        $response['date_markup']    = $this->get_date_markup($post_data);
                        $response['taxonomy_terms'] = $taxonomy_terms;
                        $response['post_date']      = get_the_time(get_option("date_format"), $post_data->ID) . " " . get_the_time(get_option("time_format"), $post_data->ID);
                        $response['status']  = 'success';
                        $response['content'] = esc_html__('Changes saved!', 'publishpress-pro');
                    }
                }
    
            }
            
            wp_send_json($response);
        }

        /**
         * Filter Content Calendar data to include pro datas
         * 
         * @param array $datas
         * 
         * @return array $datas
         */
        public function filterContentBoardData($datas) {
            
            // add checklists
            if (defined('PPCH_VERSION')) {
                $CheckListlegacyPlugin  = PublishPress\Checklists\Core\Factory::getLegacyPlugin();
                $postTypeSlugs = PublishPress\Checklists\Core\Legacy\Util::getPostTypesForModule($CheckListlegacyPlugin->settings->module);
                $ppch_requirements = [];
                foreach ($postTypeSlugs as $postTypeSlug) {
                    $ppch_args = [
                        'post_type' => $postTypeSlug,
                        'posts_per_page' => 1
                    ];
                
                    $ppch_posts = get_posts($ppch_args);
                    if (!empty($ppch_posts)) {
                        $post_requirements = apply_filters('publishpress_checklists_requirement_list', [], $ppch_posts[0]);
                        if (!empty($post_requirements)) {
                            $ppch_requirements = array_merge($ppch_requirements, $post_requirements);
                        }
                    }
                }
                
                $checklists_items = [];
                if (!empty($ppch_requirements)) {
                    foreach ($ppch_requirements as $ppch_requirement_key => $ppch_requirement_option) {
                        $checklists_items['ppch_co_checklist_' . $ppch_requirement_key] = $ppch_requirement_option['label'];
                    }
                }
            
                $datas['checklists'] = $checklists_items;
            }

            // add yoast seo
            if (defined('WPSEO_VERSION')) {
                $prefix = 'ppch_co_yoast_seo_';
                $datas['yoast_seo'] = [
                    $prefix . '_yoast_wpseo_linkdex' => esc_html__('SEO score', 'publishpress-pro'),
                    $prefix . '_yoast_wpseo_content_score' => esc_html__('Readability', 'publishpress-pro'),
                    $prefix . '_yoast_wpseo_title' => esc_html__('SEO Title', 'publishpress-pro'),
                    $prefix . '_yoast_wpseo_metadesc' => esc_html__('Meta Description', 'publishpress-pro'),
                    $prefix . '_yoast_wpseo_focuskw' => esc_html__('Focus Keywords', 'publishpress-pro'),
                    $prefix . '_yoast_wpseo_outgoing_links' => esc_html__('Outgoing internal links', 'publishpress-pro'),
                    $prefix . '_yoast_wpseo_received_links' => esc_html__('Received internal links', 'publishpress-pro'),
                    $prefix . '_yoast_wpseo_opengraph-title' => esc_html__('Facebook Title', 'publishpress-pro'),
                    $prefix . '_yoast_wpseo_opengraph-description' => esc_html__('Facebook Description', 'publishpress-pro'),
                    $prefix . '_yoast_wpseo_opengraph-image' => esc_html__('Facebook Image', 'publishpress-pro'),
                    $prefix . '_yoast_wpseo_twitter-title' => esc_html__('Twitter Title', 'publishpress-pro'),
                    $prefix . '_yoast_wpseo_twitter-description' => esc_html__('Twitter Description', 'publishpress-pro'),
                    $prefix . '_yoast_wpseo_twitter-image' => esc_html__('Twitter Image', 'publishpress-pro')
                ];
            }
            
            return $datas;
        }


        /**
         * Filter Content Calendar filter form
         * 
         * @param array $filters
         * @param array $content_calendar_datas
         * 
         * @return array $filters
         */
        public function filterContentBoardFilterForm($filters, $content_calendar_datas) {

            // checklists filter
            if (isset($content_calendar_datas['checklists'])) {
                $filters['checklists'] = [
                    'title'     => esc_html__('PublishPress Checklists', 'publishpress-pro'),
                    'message'   => esc_html__('You do not have any checklists requirement enabled.', 'publishpress-pro'),
                    'filters'   => $content_calendar_datas['checklists']
                ];
            }

            // yoast seo filter
            if (isset($content_calendar_datas['yoast_seo'])) {
                $filters['yoast_seo'] = [
                    'title'     => esc_html__('Yoast SEO', 'publishpress-pro'),
                    'filters'   => [
                        'ppch_co_yoast_seo__yoast_wpseo_linkdex' => esc_html__('SEO Scores', 'publishpress-pro'),
                        'ppch_co_yoast_seo__yoast_wpseo_content_score' => esc_html__('Readability', 'publishpress-pro'),
                    ]
                ];
            }

            return $filters;
        }

    }
}
