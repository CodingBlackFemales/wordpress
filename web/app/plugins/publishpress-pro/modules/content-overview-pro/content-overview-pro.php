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

if (!class_exists('PP_Content_Overview_Pro')) {
    /**
     * class PP_Content_Overview_Pro
     */
    #[\AllowDynamicProperties]
    class PP_Content_Overview_Pro extends PP_Module
    {

        const SETTINGS_SLUG = 'pp-content-overview-pro-settings';

        public $module_name = 'content_overview_pro';

        public $module;

        /**
         * Construct the PP_Content_Overview_Pro class
         */
        public function __construct()
        {

            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'                => __('Content Overview Pro', 'publishpress-pro'),
                'short_description'    => false,
                'extended_description' => false,
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-list-view',
                'slug'                 => 'content-overview-pro',
                'default_options'      => [
                    'enabled' => 'on',
                ],
                'options_page'         => false,
                'autoload'             => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters(
                'pp_content_overview_pro_default_options',
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
            add_filter('publishpress_content_overview_datas', [$this, 'filterContentOverviewData']);
            add_filter('publishpress_content_overview_form_columns', [$this, 'filterContentOverviewColumnForm'], 10, 2);
            add_filter('publishpress_content_overview_form_filters', [$this, 'filterContentOverviewFilterForm'], 10, 2);
            add_filter('publishpress_content_overview_column_value', [$this, 'filterContentOverviewColumnValue'], 10, 3);
            add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
            add_action('wp_ajax_publishpress_content_overview_update_post', [$this, 'updatePost']);
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
            if (isset($_GET['page']) && $_GET['page'] === 'pp-content-overview') {
                wp_enqueue_style(
                    'publishpress-content_overview-pro',
                    PUBLISHPRESS_PRO_PLUGIN_URL . 'modules/content-overview-pro/assets/css/content-overview-pro.css',
                    [],
                    PUBLISHPRESS_PRO_VERSION
                );

                add_thickbox();
            
                wp_enqueue_script(
                    'publishpress-content_overview-pro',
                    PUBLISHPRESS_PRO_PLUGIN_URL . 'modules/content-overview-pro/assets/js/content-overview-pro.js',
                    ['jquery'],
                    PUBLISHPRESS_PRO_VERSION,
                    true
                );
            }
        }

        public function updatePost() {
            global $publishpress;
    
            $response['status']  = 'error';
            $response['content'] = esc_html__('An error occured', 'publishpress-pro');
    
    
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'content_overview_action_nonce')) {
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
                        $response['post_modified']      = $this->timesince(strtotime($post_data->post_modified_gmt . " GMT"));
                        $response['status']  = 'success';
                        $response['content'] = esc_html__('Changes saved!', 'publishpress-pro');
                    }
                }
    
            }
            
            wp_send_json($response);
        }

        /**
         * Filter content overview data to include pro datas
         * 
         * @param array $datas
         * 
         * @return array $datas
         */
        public function filterContentOverviewData($datas) {
            
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
         * Filter content overview column form
         * 
         * @param array $columns
         * @param array $content_overview_datas
         * 
         * @return array $columns
         */
        public function filterContentOverviewColumnForm($columns, $content_overview_datas) {

            // checklists column
            if (isset($content_overview_datas['checklists'])) {
                $columns['checklists'] = [
                    'title'     => esc_html__('PublishPress Checklists', 'publishpress-pro'),
                    'message'   => esc_html__('You do not have any checklists requirement enabled.', 'publishpress-pro'),
                    'columns'   => $content_overview_datas['checklists']
                ];
            }

            // yoast seo column
            if (isset($content_overview_datas['yoast_seo'])) {
                $columns['yoast_seo'] = [
                    'title'     => esc_html__('Yoast SEO', 'publishpress-pro'),
                    'columns'   => $content_overview_datas['yoast_seo']
                ];
            }

            return $columns;
        }


        /**
         * Filter content overview filter form
         * 
         * @param array $filters
         * @param array $content_overview_datas
         * 
         * @return array $filters
         */
        public function filterContentOverviewFilterForm($filters, $content_overview_datas) {

            // checklists filter
            if (isset($content_overview_datas['checklists'])) {
                $filters['checklists'] = [
                    'title'     => esc_html__('PublishPress Checklists', 'publishpress-pro'),
                    'message'   => esc_html__('You do not have any checklists requirement enabled.', 'publishpress-pro'),
                    'filters'   => $content_overview_datas['checklists']
                ];
            }

            // yoast seo filter
            if (isset($content_overview_datas['yoast_seo'])) {
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

        /**
         * Filter content overview column value
         *
         * @param string $column_name
         * @param object $post
         * @param string $module_url
         * 
         * @return mixed
         */
        public function filterContentOverviewColumnValue($column_name, $post, $module_url) {
            global $pp_co_checklists_req;
    
            if (!is_array($pp_co_checklists_req)) {
                $pp_co_checklists_req = [];
            }

            if (strpos($column_name, "ppch_co_checklist_") === 0) {
                // publishpress checklists requirement
                $real_meta_key = str_replace('ppch_co_checklist_', '', $column_name);
                if (!array_key_exists($post->ID, $pp_co_checklists_req)) {
                    $pp_co_checklists_req[$post->ID] = apply_filters('publishpress_checklists_requirement_list', [], $post);
                }
                
                if (!array_key_exists($real_meta_key, $pp_co_checklists_req[$post->ID])) {
                    // this post doesn't have the requirement enabled
                    return '<div class="column-icon black"><span class="dashicons dashicons-no-alt"></span></div>';
                } elseif (!empty($pp_co_checklists_req[$post->ID][$real_meta_key]['status'])) {
                    return '<div class="column-icon green"><span class="dashicons dashicons-yes-alt"></span></div>';
                } else {
                    return '<div class="column-icon red"><span class="dashicons dashicons-no-alt"></span></div>';
                }   
            }

            if (strpos($column_name, "ppch_co_yoast_seo_") === 0 && class_exists('WPSEO_Meta')) {
                // yoast seo
                $column_name = str_replace('ppch_co_yoast_seo__yoast_wpseo_', '', $column_name);
    
                switch ($column_name) {
                    case 'title':
                        $meta = $this->get_wpseo_meta($post->ID);
                        if ($meta) {
                            $yoast_meta_value = esc_html($meta->title);
                        }
                        return $yoast_meta_value;
                    case 'metadesc':
                        $meta = $this->get_wpseo_meta($post->ID);
                        if ($meta) {
                            $yoast_meta_value = esc_html($meta->meta_description);
                        }
                        return $yoast_meta_value;
                    case 'outgoing_links':
                        $meta = $this->get_wpseo_meta($post->ID);
                        if ($meta) {
                            $yoast_meta_value = $meta->context->indexable ? $meta->context->indexable->link_count : '';
                        }
                        return (int) $yoast_meta_value;
                    case 'received_links':
                        $meta = $this->get_wpseo_meta($post->ID);
                        if ($meta) {
                            $yoast_meta_value = $meta->context->indexable ? $meta->context->indexable->incoming_link_count : '';
                        }
                        return (int) $yoast_meta_value;
                    case 'opengraph-title':
                        $meta = $this->get_wpseo_meta($post->ID);
                        if ($meta) {
                            $yoast_meta_value = esc_html($meta->open_graph_title);
                        }
                        return $yoast_meta_value;
                    case 'opengraph-description':
                        $meta = $this->get_wpseo_meta($post->ID);
                        if ($meta) {
                            $yoast_meta_value = esc_html($meta->open_graph_description);
                        }
                        return $yoast_meta_value;
                    case 'twitter-title':
                        $meta = $this->get_wpseo_meta($post->ID);
                        if ($meta) {
                            $yoast_meta_value = esc_html($meta->twitter_title);
                        }
                        return $yoast_meta_value;
                    case 'twitter-description':
                        $meta = $this->get_wpseo_meta($post->ID);
                        if ($meta) {
                            $yoast_meta_value = esc_html($meta->twitter_description);
                        }
                        return $yoast_meta_value;
                }
    
                $yoast_meta_value = \WPSEO_Meta::get_value($column_name, $post->ID);
    
                if (in_array($column_name, ['opengraph-image', 'twitter-image']) && !empty($yoast_meta_value)) {
                    $yoast_meta_value = '<a target="_blank" href="'. $yoast_meta_value .'">'. $yoast_meta_value .'</a>';
                }
    
                return $yoast_meta_value;
            }

            return null;
        }

        private function get_wpseo_meta($post_id) {
            $admin_columns_cache = YoastSEO()->classes->get(Yoast\WP\SEO\Integrations\Admin\Admin_Columns_Cache_Integration::class);
            
            $indexable = $admin_columns_cache->get_indexable($post_id);

            return YoastSEO()->meta->for_indexable($indexable, 'Post_Type');
        }

    }
}