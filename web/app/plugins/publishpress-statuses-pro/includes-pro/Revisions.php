<?php
namespace PublishPress_Statuses;

class Revisions {
    private $revision_statuses;

    private $filtered_post_status = false;
    private $filtered_post_mime_type = false;

    function __construct() {
        $this->revision_statuses = [];

        if (!defined('PUBLISHPRESS_STATUSES_PRO_VERSION')) {
            define('PUBLISHPRESS_STATUSES_PRO_VERSION', constant('PUBLISHPRESS_STATUSES_VERSION'));
        }

        if (!taxonomy_exists('pp_revision_status')) {
            register_taxonomy(
                'pp_revision_status',
                'post',
                [
                    'hierarchical'          => false,
                    'label'                 => __('Revision Statuses', 'publishpress-statuses-pro'),
                    'labels'                => (object) ['name' => __('Revision Statuses', 'publishpress-statuses-pro'), 'singular_name' => __('Revision Status', 'publishpress-statuses-pro')],
                    'query_var'             => false,
                    'rewrite'               => false,
                    'show_ui'               => false,
                ]
            );
        }

        $this->revision_statuses = ['draft-revision', 'pending-revision', 'future-revision', 'revision-deferred', 'revision-needs-work', 'revision-rejected'];

        $statuses = get_terms(['taxonomy' => 'pp_revision_status', 'hide_empty' => false, 'return' => 'name']);

        foreach ($statuses as $status) {
            $this->revision_statuses[] = $status->slug;
        }

        $this->revision_statuses = array_values(array_unique($this->revision_statuses));

        if (is_admin()) {
            require_once(__DIR__ . '/RevisionsAdmin.php');
            new \PublishPress_Statuses\RevisionsAdmin();
        }

        add_filter('rvy_revision_statuses', [$this, 'fltRevisionStatuses']);

        add_filter('publishpress_statuses_taxonomies', [$this, 'fltTaxonomies']);
        add_filter('publishpress_statuses_default_position', [$this, 'fltDefaultStatusPosition'], 10, 3);
        add_filter('publishpress_statuses_default_main_position', [$this, 'fltDefaultStatusMainPosition'], 10, 3);
        
        add_filter('publishpress_statuses_get_terms', [$this, 'fltGetTerms'], 10, 2);
        add_filter('publishpress_statuses_default_statuses', [$this, 'fltDefaultStatuses']);
        add_filter('publishpress_revisions_all_statuses', [$this, 'fltAllStatuses']);

        add_filter('pp_calendar_posts_query_args', 
            function($query_args) {
                if (!class_exists('PP_Revision_Integration')) {
                    $query_args['is_pp_calendar'] = true;
                    add_filter('posts_where', [$this, 'fltPlannerCalendarWhere'], 10, 2);
                }

                return $query_args;
            }
        );

        add_filter('publishpress_calendar_revision_statuses', 
            function($statuses) {
                if (class_exists('PP_Revision_Integration')) {
                    $statuses = array_merge(
                        $statuses,
                        rvy_revision_statuses()
                    );
                }

                return $statuses;
            }
        );

        add_filter('pp_dashboard_post_status_widget_statuses', 
            function($statuses) {
                foreach ($statuses as $k => $status) {
                    if (in_array($status->name, $this->revision_statuses)) {
                        unset($statuses[$k]);
                    }
                }

                return $statuses;
            }
        );

        add_filter(
            'publishpress_statuses_omit_admin_statuses',
            function ($omit_statuses) {
                $omit_statuses = array_merge(
                    $omit_statuses,
                    ['_revision-workflow', '_revision-alternate']
                );

                return $omit_statuses;
            }
        );

        // REST
        add_action('rest_api_init', [$this, 'actRestInit'], 2);
        add_filter('publishpress_statuses_rest_property', [$this, 'fltRestProperty'], 10, 2);

        // Post Update
        add_filter('publishpress_statuses_override_post_status', [$this, 'fltOverridePostStatus'], 10, 3);
        
        add_filter('wp_insert_post_data', [$this, 'fltSetRevisionStatus'], 1000, 2);
        add_filter('publishpress_statuses_insert_post_data', [$this, 'fltRevisionPostData'], 99999, 2);
        add_filter('wp_insert_post_empty_content', [$this, 'fltInsertPostEmptyContent'], 10, 2);

        // Status config / selection
        add_filter(
			'pp_statuses_assignment_cap_taxonomies',
			function ($taxonomies) {
				$taxonomies []= 'pp_revision_status';
				
				return $taxonomies;
			}
        );
        
        add_filter(
            'publishpress_statuses_skip_status_registration',
            function ($skip, $status_name) {
                if (in_array($status_name, ['_revision_workflow', '_revision-alternate'])) {
                    $skip = true;
                }
                
                return $skip;	
            },
            10, 2
        );

        add_filter(
            'publishpress_statuses_status_properties',
            function($arr, $status) {
                $arr['for_revision'] = !empty($status->for_revision);
                $arr['revision_alternate'] = !empty($status->revision_alternate);
    
                return $arr;
            },
            10, 2
        );

        add_filter(
			'publishpress_statuses_current_workflow_status',
			function($post_status, $post_id, $args) {
                $post = (!empty($args['post'])) ? $args['post'] : false;
                
                if ($post_id
				&& (function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post_id))
				) {
					if (!empty($args['post_status'])
					&& function_exists('rvy_is_revision_status')
					&& rvy_is_revision_status($args['post_status'])
					) {
						$post_status = $args['post_status'];
					} else {
						if (empty($post)) {
			                $post_status = 'draft-revision';
			            } else {
			                $post_status = $post->post_mime_type;
			            }
		          	}
				}
				
				return $post_status;	
			},
			10, 3
		);

        add_filter(
			'publishpress_statuses_default_position',
			function($pos, $taxonomy, $all_statuses) {
				switch ($taxonomy) {
					case 'post_status':
						$pos = $all_statuses['_pre-publish-alternate']->position;
						break;
						
					case 'post_visibility_pp':
						$pos = $all_statuses['private']->position;
						break;
						
					case 'pp_revision_status':
						$pos = $all_statuses['_revision-alternate']->position;
						break;
				}

				return $pos;
			},
			10, 3
        );

        add_filter(
            'publishpress_statuses_alternate_properties', 
            function($props) {
                return array_merge($props, ['revision_alternate']);
            }
        );

        add_filter(
    		'publishpress_statuses_default_status',
    		function($post_status, $post_id, $args = []) {
                $can_set_status = (!empty($args['can_set_status'])) ? $args['can_set_status'] : [];

    			if (function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post_id)) {
					$post_status = 'draft-revision';

                } elseif (defined('PP_LEGACY_PENDING_STATUS') || !empty($can_set_status['pending'])) {
                    $post_status = 'pending';
                }
    			
    			return $post_status;	
    		},
    		10, 2
        );
        
        add_filter(
			'pp_statuses_get_next_moderation_statuses',
			function ($filtered_statuses, $moderation_statuses, $post_id, $args = []) {
				$post = (isset($args['post'])) ? $args['post'] : false;
				$post_status_obj = (isset($args['post_status_obj'])) ? $args['post_status_obj'] : false;
                $current_status = (isset($args['current_status'])) ? $args['current_status'] : '';
				
                if (!rvy_in_revision_workflow($post_id)) {
                    return $filtered_statuses;
                }

				if (!is_array($filtered_statuses)) {
					$filtered_statuses = $moderation_statuses;	
				}
				
				if ($post_status_obj && function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post_id)) {
		            if ('future-revision' != $current_status) {
                        foreach ($filtered_statuses as $k => $_status) {
                            if ('future-revision' == $_status->name) {
                                unset($filtered_statuses[$k]);
                            }
                        }
		            }
		
		            if (empty($post_status_obj->revision_alternate)) {
                        foreach ($filtered_statuses as $k => $_status) {
		                    if (!empty($_status->revision_alternate)
		                    || (!empty($post_status_obj->status_parent) && ($_status->status_parent != $post_status_obj->status_parent) && ($_status->status_parent != $post_status_obj->name))
		                    || (('future-revision' == $_status->name) && !empty($omit_future_revision))
		                    && !in_array($k, ['draft-revision'])
		                    ) {
		                        unset($filtered_statuses[$k]);
		                    }
		                }
		            } else {
		                foreach ($filtered_statuses as $k => $_status) {
		                    if ((empty($_status->revision_alternate) 
                            || (!empty($post_status_obj->status_parent) && ($_status->status_parent != $post_status_obj->status_parent)))
                            || (empty($post_status_obj->status_parent) && ($_status->status_parent != $current_status) && !defined('PUBLISHPRESS_STATUSES_SINGLE_ALTERNATE_WORKFLOW'))
		                    && !in_array($k, ['draft-revision'])
		                    ) {
		                        unset($filtered_statuses[$k]);
		                    }
		                }
		            }
		        }

				return $filtered_statuses;
            },
            10, 4
        );

        add_filter('publishpress_statuses_edit_post_status_args', [$this, 'fltPostEditorStatusArgs'], 10, 2);

        add_filter(
        	'pp_statuses_status_section',
        	function ($status_section, $status_obj) {
        		if (!empty($status_obj->for_revision)) {
        			$status_section = 'pp_revision';		
        		}
        	
        		return $status_section;
        	},
        	10, 2
    	);

        add_filter(
            'presspermit_register_access_statuses',
            function ($statuses) {
                $revision_statuses = \PublishPress_Statuses::instance()->getPostStatuses(['for_revision' => true], 'object', 'and', ['context' => 'load']);

                return array_merge($statuses, $revision_statuses);
            }
        );

        add_filter(
			'publishpress_statuses_status_field',
			function($status_field, $post_id) {
				if (function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post_id)) {
					$status_field = 'post_mime_type';
				}
			
				return $status_field;
			},
			10, 2
		);

        add_filter(
            'publishpress_statuses_post_status',
            function ($post_status, $post) {
                if (rvy_in_revision_workflow($post)) {
                    $post_status = $post->post_mime_type;
                }
                
                return $post_status;
            },
            10, 2
        );

        add_filter(
            'publishpress_statuses_filter_post_status',
            function ($_post_status, $post_id, $args) {
                if (isset($args['is_revision'])) {
                    $is_revision = $args['is_revision'];
                } else {
                    $is_revision = rvy_in_revision_workflow($post_id);
                }
    
                if ($is_revision) {
                    if (!empty($args['doing_rest'])) {
                        $rest = \PublishPress_Statuses\REST::instance();
                        $_post_status = isset($rest->params['pp_post_mime_type']) ? sanitize_key($rest->params['pp_post_mime_type']) : false;
                    }
                }
    
                return $_post_status;
            },
            10, 3
        );

        add_filter(
            'publishpress_statuses_get_post_status_object',
            function ($post_status_obj, $post_status, $post) {
                if (rvy_in_revision_workflow($post)) {
                    if (!$post_status_obj = \PublishPress_Statuses::instance()->get_post_status_object($post_status)) {
                        $post_status_obj = get_post_status_object('draft-revision');
                    }
                }
    
                return $post_status_obj;
            }, 
            10, 3
        );

        add_filter(
            'publishpress_statuses_edit_post_status_args',
            function ($status_args, $post_id) {
                if (rvy_in_revision_workflow($post_id)) {
                    $status_args['taxonomy'] = 'pp_revision_status';
                    $status_args['internal'] = false;
                    
                    // @todo: review
                    unset($status_args['moderation']);
                }
    
                return $status_args;
            },
            10, 2
        );

        add_filter(
            'publishpress_statuses_available_moderation_statuses',
            function ($moderation_statuses, $post) {
                if (rvy_in_revision_workflow($post)) {
                    unset ($moderation_statuses['draft']);
                }
                
                return $moderation_statuses;
            },
            10, 2
        );

        if (defined('REVISIONARY_DECLINE_STATUS')
        || (defined('PUBLISHPRESS_REVISIONS_VERSION') && version_compare(PUBLISHPRESS_REVISIONS_VERSION, '3.9.0', '>='))
        ) {
            add_filter(
                'revisionary_revision_decline_status',
                function ($decline_status) {
                    $_status = (defined('REVISIONARY_DECLINE_STATUS')) ? REVISIONARY_DECLINE_STATUS : 'revision-rejected';
    
                    $status_obj = get_post_status_object($_status);
    
                    if ($status_obj && empty($status_obj->disabled)) {
                        $decline_status = $_status;
                    }
    
                    return $decline_status;
                }
            );
        }

        // Capabilities Pro
        add_filter(
            'capabilities_pro_custom_status_args',
            function ($status_args, $tab_id) {
                if ('revision-status' == $tab_id) {
                    $status_args['for_revision'] = true;
                    $status_args['internal'] = false;
                    unset( $status_args['moderation']);
                }
                
                return $status_args;
            },
            10, 2
        );
    
        // Status Update
        add_filter(
            'publishpress_statuses_add_status',
            function ($status_name, $taxonomy) {
                if ('pp_revision_status' == $taxonomy) {
                    $status_name = str_replace('_', '-', $status_name);
                }
                
                return $status_name;
            },
            10, 2
        );
    
        add_filter(
            'publishpress_statuses_taxonomy',
            function ($taxonomy, $status_obj) {
                if (!empty($status_obj->for_revision)) {
                    $taxonomy = 'pp_revision_status';
                }
            
                return $taxonomy;	
            },
            10, 2
        );
    
        add_filter(
            'publishpress_statuses_sanitize_status_name',
            function ($name, $taxonomy) {
                if ('pp_revision_status' == $taxonomy) {
                    $name = str_replace('_', '-', $name);
                }
            
                return $name;
            },
            10, 2
        );
    }

    function fltRevisionStatuses($statuses, $args = []) {
        return ($this->revision_statuses) ? $this->revision_statuses : $statuses;
    }

    function fltTaxonomies($taxonomies) {
        $taxonomies []= 'pp_revision_status';
        return $taxonomies;
    }

    function fltDefaultStatusPosition($position, $taxonomy, $all_statuses) {
        if ('pp_revision_status' == $taxonomy) {
            $position = $all_statuses['_revision-alternate']->position;
        }

        return $position;
    }

    function fltDefaultStatusMainPosition($position, $taxonomy, $all_statuses) {
        if ('pp_revision_status' == $taxonomy) {
            $position = $all_statuses['_revision-alternate']->position - 1;
        }

        return $position;
    }

    function fltGetTerms($terms, $taxonomy) {
        if ('pp_revision_status' == $taxonomy) {
            foreach ($terms as $k => $term) {
                $terms[$k]->for_revision = true;
            }
        }

        return $terms;
    }

    function fltDefaultStatuses($all_statuses) {
        $statuses = [];

        $default_revision_position = 18;
        $default_revision_alternate_position = 22;

        if ($stored_positions = (array) get_option('publishpress_status_positions')) {
            if ($pos = array_search('_revision-workflow', $stored_positions)) {
                if ($pos > $default_revision_position) {
                    $default_revision_position = $pos;
                }
            }

            if ($pos = array_search('_revision-alternate', $stored_positions)) {
                if ($pos > $default_revision_alternate_position) {
                    $default_revision_alternate_position = $pos;
                }
            }
        }

        $statuses['_revision-workflow'] = (object) [
            'label' => __('Main Revision Workflow:', 'publishpress-statuses-pro'),
            'description' => '',
            'class' => 'revision-status',
            'color' => '',
            'icon' => '',
            'position' => $default_revision_position + 1,
            'order' => 1000,
            'for_revision' => true,
            'internal' => true,
            'disabled' => false,
            'taxonomy' => 'pseudo_status_pp',
            'status_type' => 'revision',
        ];

        if (!isset($all_statuses['pseudo_status_pp'])) {
            $all_statuses['pseudo_status_pp'] = [];
        }

        $all_statuses['pseudo_status_pp'] = array_merge($all_statuses['pseudo_status_pp'], $statuses);


        $statuses = [];

        if (!rvy_get_option('revision_statuses_noun_labels')) {
            $statuses['draft-revision'] = (object) [
                'default_label' => 'Unsubmitted Revision',
                'label' => __('Unsubmitted Revision', 'revisionary'),
                'label_count' => _n_noop('Not Submitted <span class="count">(%s)</span>', 'Not Submitted <span class="count">(%s)</span>', 'revisionary'),
                'description' => __('Revision created; not yet submitted.', 'revisionary'),
                'color' => '#767676',
                'icon' => 'dashicons-edit-page',
                'position' => $default_revision_position + 2,
                'order' => 1000,
                'internal' => false,
                'for_revision' => true,
                'pp_builtin' => true,
            ];
    
            $statuses['pending-revision'] = (object) [
                'default_label' => 'Submitted Revision',
                'label' => __('Submitted Revision', 'revisionary'),
                'label_count' => _n_noop('Submitted <span class="count">(%s)</span>', 'Submitted <span class="count">(%s)</span>', 'revisionary'),
                'default_labels' => (object) ['publish' => 'Submit'],
                'labels' => (object) ['publish' => _x('Submit', 'post action/button label', 'revisionary')],
                'description' => __('Revision submitted; waiting for approval.', 'revisionary'),
                'color' => '#b95c00',
                'icon' => 'dashicons-upload',
                'position' => $default_revision_position + 3,
                'order' => 1020,
                'internal' => false,
                'for_revision' => true,
                'pp_builtin' => true,
            ];

            $statuses['future-revision'] = (object) [
                'default_label' => 'Scheduled Revision',
                'label' => __('Scheduled Revision', 'revisionary'),
                'label_count' => _n_noop('Scheduled <span class="count">(%s)</span>', 'Scheduled <span class="count">(%s)</span>', 'revisionary'),
                'default_labels' => (object) ['publish' => 'Schedule'],
                'labels' => (object) ['publish' => _x('Schedule', 'post action/button label', 'revisionary')],
                'description' => __('Revision scheduled for publication.', 'revisionary'),
                'color' => '#8440f0',
                'icon' => 'dashicons-schedule',
                'position' => $default_revision_position + 4,
                'order' => 1090,
                'internal' => false,
                'for_revision' => true,
                'pp_builtin' => true,
            ];
        } else {
            $statuses['draft-revision'] = (object) [
                'default_label' => 'Working Copy',
                'label' => __('Working Copy', 'revisionary'),
                'label_count' => _n_noop('Working Copies <span class="count">(%d)</span>', 'Working Copies <span class="count">(%d)</span>', 'revisionary'),
                'description' => __('Revision created; not yet submitted.', 'revisionary'),
                'color' => '#767676',
                'icon' => 'dashicons-edit-page',
                'position' => $default_revision_position + 2,
                'order' => 1000,
                'internal' => false,
                'for_revision' => true,
                'pp_builtin' => true,
            ];
    
            $statuses['pending-revision'] = (object) [
                'default_label' => 'Change Request',
                'label' => __('Change Request', 'revisionary'),
                'label_count' => _n_noop('Change Requests <span class="count">(%d)</span>', 'Change Requests <span class="count">(%d)</span>', 'revisionary'),
                'default_labels' => (object) ['publish' => 'Submit'],
                'labels' => (object) ['publish' => _x('Submit', 'post action/button label', 'revisionary')],
                'description' => __('Revision submitted; waiting for approval.', 'revisionary'),
                'color' => '#b95c00',
                'icon' => 'dashicons-upload',
                'position' => $default_revision_position + 3,
                'order' => 1020,
                'internal' => false,
                'for_revision' => true,
                'pp_builtin' => true,
            ];

            $statuses['future-revision'] = (object) [
                'default_label' => 'Scheduled Change',
                'label' => __('Scheduled Change', 'revisionary'),
                'label_count' => _n_noop('Scheduled <span class="count">(%d)</span>', 'Scheduled <span class="count">(%d)</span>', 'revisionary'),
                'default_labels' => (object) ['publish' => 'Schedule'],
                'labels' => (object) ['publish' => _x('Schedule', 'post action/button label', 'revisionary')],
                'description' => __('Revision scheduled for publication.', 'revisionary'),
                'color' => '#8440f0',
                'icon' => 'dashicons-schedule',
                'position' => $default_revision_position + 4,
                'order' => 1090,
                'internal' => false,
                'for_revision' => true,
                'pp_builtin' => true,
            ];
        }

        $options = \PublishPress_Statuses::instance()->options;

        $all_statuses['pseudo_status_pp'] = array_merge(
            $all_statuses['pseudo_status_pp'],
            [
                '_revision-alternate' => (object) [
                    'label' => (empty($options->hide_manual_status_selectors)) ? __('Alternate Revision Workflow:', 'publishpress-statuses-pro') : __('Disabled Statuses:', 'publishpress-statuses-pro'),
                    'description' => '',
                    'class' => (empty($options->hide_manual_status_selectors)) ? 'alternate-revision-status' : 'disabled-status',
                    'color' => '',
                    'icon' => '',
                    'position' => $default_revision_alternate_position,
                    'order' => 1050,
                    'for_revision' => true,
                    'internal' => true,
                    'alternate' => true,
                    'disabled' => false,
                    'taxonomy' => 'pseudo_status_pp',
                    'status_type' => 'revision',
                ]
            ]
        );

        if (!rvy_get_option('revision_statuses_noun_labels')) {
            $statuses['revision-deferred'] = (object) [
                'default_label' => 'Revision Deferred',
                'label' => __('Revision Deferred', 'publishpress-statuses-pro'),
                'default_labels' => (object) ['publish' => 'Defer'],
                'labels' => (object) ['publish' => __('Defer', 'publishpress-statuses-pro')],
                'description' => __('Revision has been deferred for future consideration.', 'publishpress-statuses-pro'),
                'color' => '#9b9b9b',
                'icon' => 'dashicons-coffee',
                'position' => $default_revision_alternate_position + 1,
                'order' => 1051,
                'for_revision' => true,
                'alternate' => true,
                'pp_builtin' => true,
            ];

            $statuses['revision-needs-work'] = (object) [
                'default_label' => 'Revision Needs Work',
                'label' => __('Revision Needs Work', 'publishpress-statuses-pro'),
                'default_labels' => (object) ['publish' => 'Set to Needs Work'],
                'labels' => (object) ['publish' => __('Set to Needs Work', 'publishpress-statuses-pro')],
                'description' => __('Revision needs work before further review.', 'publishpress-statuses-pro'),
                'color' => '#A88F8D',
                'icon' => 'dashicons-image-crop',
                'position' => $default_revision_alternate_position + 2,
                'order' => 1052,
                'for_revision' => true,
                'alternate' => true,
                'pp_builtin' => true,
            ];

            $statuses['revision-rejected'] = (object) [
                'default_label' => 'Revision Rejected',
                'label' => __('Revision Rejected', 'revisionary'),
                'label_count' => _n_noop('Rejected <span class="count">(%s)</span>', 'Rejected <span class="count">(%s)</span>', 'revisionary'),
                'description' => __('Revision was declined / rejected.', 'revisionary'),
                'color' => '#767676',
                'icon' => 'dashicons-edit-page',
                'position' => $default_revision_alternate_position + 3,
                'order' => 1053,
                'internal' => false,
                'for_revision' => true,
                'alternate' => true,
                'pp_builtin' => true,
            ];
        } else {
            $statuses['revision-deferred'] = (object) [
                'default_label' => 'Change Deferred',
                'label' => __('Change Deferred', 'publishpress-statuses-pro'),
                'default_labels' => (object) ['publish' => 'Defer'],
                'labels' => (object) ['publish' => __('Defer', 'publishpress-statuses-pro')],
                'description' => __('Change has been deferred for future consideration.', 'publishpress-statuses-pro'),
                'color' => '#9b9b9b',
                'icon' => 'dashicons-coffee',
                'position' => $default_revision_alternate_position + 1,
                'order' => 1051,
                'for_revision' => true,
                'alternate' => true,
                'pp_builtin' => true,
            ];

            $statuses['revision-needs-work'] = (object) [
                'default_label' => 'Change Needs Work',
                'label' => __('Change Needs Work', 'publishpress-statuses-pro'),
                'default_labels' => (object) ['publish' => 'Set to Needs Work'],
                'labels' => (object) ['publish' => __('Set to Needs Work', 'publishpress-statuses-pro')],
                'description' => __('Change needs work before further review.', 'publishpress-statuses-pro'),
                'color' => '#A88F8D',
                'icon' => 'dashicons-image-crop',
                'position' => $default_revision_alternate_position + 2,
                'order' => 1052,
                'for_revision' => true,
                'alternate' => true,
                'pp_builtin' => true,
            ];

            $statuses['revision-rejected'] = (object) [
                'default_label' => 'Change Rejected',
                'label' => __('Change Rejected', 'revisionary'),
                'label_count' => _n_noop('Rejected <span class="count">(%d)</span>', 'Rejected <span class="count">(%d)</span>', 'revisionary'),
                'description' => __('Change was declined / rejected.', 'revisionary'),
                'color' => '#767676',
                'icon' => 'dashicons-edit-page',
                'position' => $default_revision_alternate_position + 3,
                'order' => 1053,
                'internal' => false,
                'for_revision' => true,
                'alternate' => true,
                'pp_builtin' => true,
            ];
        }

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

        foreach ($statuses as $slug => $status) {
            if (empty($status->taxonomy)) {
                $statuses[$slug]->taxonomy = 'pp_revision_status';
                $statuses[$slug]->for_revision = true;
                $statuses[$slug]->disabled = false;
            }
        }

        $statuses = apply_filters('publishpress_statuses_default_revision_statuses', $statuses);
        $all_statuses['pp_revision_status'] = $statuses;

        return $all_statuses;
    }

    function fltAllStatuses($all_statuses, $args = []) {
        $defaults = [
            'disabled_statuses' => [], 
            'function_args' => [], 
        ];

        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        foreach ([false, true] as $disabled) {
            // Disabled statuses are postioned right after the Published / Private statuses in the management list, 
            // unavailable in status selection UI and not included in any status workflow auto-progression.
            if ($disabled && empty($function_args['show_disabled'])) {
                break;
            }

            // Classify the statuses based on stored position relative to core statuses
            foreach ($all_statuses as $key => $status) {
                // None of the customizations / default checks in this loop apply to the Draft status
                if (empty($status->for_revision)) {
                    continue;
                }

                $status_is_disabled = !empty($status->disabled) || in_array($status->name, $disabled_statuses, true);

                if (
                (!$disabled && $status_is_disabled)
                || ($disabled && !$status_is_disabled)
                ) {
                    continue;
                }

                if ($status->position >= $all_statuses['_disabled']->position) {
                    $all_statuses[$key]->disabled = true; // Fallback in case the disabled_statuses array is missing or out of sync (privacy statuses are pulled from a different taxonomy)
                } else { 
                    // Alternate revision workflow statuses will be displayed lower in the management list, 
                    // de-emphasized in status selection UI and not included in any status workflow auto-progression.
                    if ($status->position >= $all_statuses['_revision-alternate']->position) {
                        $all_statuses[$key]->revision_alternate = true;
                    }
                }
            }
        }

        return $all_statuses;
    }

    /*
     * REST
     */
    function actRestInit()
    {
        foreach(get_post_types(['public' => true, 'show_ui' => true], 'names', 'or') as $post_type) {
            register_rest_field( $post_type, 'pp_post_mime_type', array(
                'get_callback' => [__CLASS__, 'getPostMimeType'],
                'update_callback' => [__CLASS__, 'updatePostMimeType'],
                'schema' => [
                    'description'   => 'Mime Type / Revision Status',
                    'type'          => 'string',
                    'context'       =>  ['view','edit']
                    ]
                )
            );
        }
    }

    function fltRestProperty($prop, $post) {
        if (rvy_in_revision_workflow($post)) {
            $prop = 'pp_post_mime_type';
        }

        return $prop;
    }

    public static function getPostMimeType( $object ) {
        $mime_type_selection = '';

        if ($post_id = \PP_Statuses_Functions::getPostID()) {
            if ($mime_type = get_post_field('post_mime_type', $post_id)) {
                if (get_post_status_object($mime_type)) {
                    $mime_type_selection = $mime_type;
                }
            }
        }

        return $mime_type_selection;
    }

    public static function updatePostMimeType( $value, $object ) {
        return $value;
    }

    /*
     * Post Update
     */
    public function fltSetRevisionStatus($data, $postarr) {
        static $busy;

        if (!empty($busy)) {
            return $data;
        }

        $busy = true;

        if (rvy_in_revision_workflow($postarr['ID']) && !did_action('publishpress_statuses_workflow_progression')) {
        
            if (\PublishPress_Statuses::instance()->doing_rest) {
                require_once(PUBLISHPRESS_STATUSES_ABSPATH . '/REST.php');
                $rest = \PublishPress_Statuses\REST::instance();
                $set_revision_status = isset($rest->params['pp_post_mime_type']) ? sanitize_key($rest->params['pp_post_mime_type']) : false;
            } else {
                $set_revision_status = \PP_Statuses_Functions::REQUEST_key('post_status');
            }

            $set_revision_status = $this->filterPostMimeType($set_revision_status);

            if ($set_revision_status) {
                $post_type = \PP_Statuses_Functions::findPostType();

                $status_args = ['taxonomy' => 'pp_revision_status', 'post_type' => $post_type];

                $statuses = \PublishPress_Statuses::getPostStati($status_args, 'names');
                
                if ($statuses && in_array($set_revision_status, $statuses)) {
                    $data['post_mime_type'] = $set_revision_status;

                    wp_update_post(
                        [
                            'ID' => $postarr['ID'], 
                            'post_mime_type' => $set_revision_status
                        ]
                    );
                }
            } else {
                // @todo return REST error
            }
        }

        $busy = false;

        return $data;
    }

    public function fltRevisionPostData($data, $postarr) {
        if (empty($postarr['ID'])) {
            return $data;
        }

        if (!function_exists('rvy_in_revision_workflow') || !rvy_in_revision_workflow($postarr['ID'])) {
            return $data;
        }

        // @todo: review Classic Editor implementation
        if (!\PP_Statuses_Functions::isBlockEditorActive()) {
            $data['post_mime_type'] = $data['post_status'];

            switch ($data['post_status']) {
                case 'draft-revision':
                    $data['post_status'] = 'draft';
                    break;

                case 'future-revision':
                    $data['post_status'] = 'future';
                    break;

                default:
                    $data['post_status'] = 'pending';
            }
        }

        if (!empty($data['post_status']) && function_exists('rvy_is_revision_status') && rvy_is_revision_status($data['post_status'])) {
            $this->filtered_post_mime_type = $data['post_status'];
        }

        $post_mime_type = (empty($data['post_mime_type'])) ? '' : $data['post_mime_type'];
        $data['post_mime_type'] = $this->filterPostMimeType($post_mime_type, ['post_id' => $postarr['ID'], 'is_revision' => true]);

        $post_status = (empty($data['post_status'])) ? '' : $data['post_status'];
        $data['post_status'] = $this->filterPostStatus($post_status, ['post_id' => $postarr['ID'], 'is_revision' => true]);

        return $data;
    }

    public function fltOverridePostStatus ($override_status, $post_status, $args) {
        if (false !== $this->filtered_post_status) {
            $override_status = $this->filtered_post_status;
        }

        return $override_status;
    }

    public function filterPostStatus($post_status, $args = []) {
        if (isset($args['is_revision'])) {
            $is_revision = $args['is_revision'];
        } else {
            $is_revision = rvy_in_revision_workflow(\PP_Statuses_Functions::getPostID());
        }

        if ($is_revision) {
            // This is already filtered by PublishPress_Statuses::filterStatus()
            if (function_exists('rvy_is_revision_status') && rvy_is_revision_status($post_status)) {
            $this->filtered_post_mime_type = $post_status;
                switch ($post_status) {
                    case 'draft-revision':
                        $post_status = 'draft';
                        break;

                    case 'future-revision':
                        $post_status = 'future';
                        break;

                    default:
                        $post_status = 'pending';
                }
            }
        }

        return $post_status;
    }

    public function filterPostMimeType($post_mime_type, $args = []) {
        if (false !== $this->filtered_post_mime_type) {
            $post_mime_type = $this->filtered_post_mime_type;
        } else {
            if (empty($args['is_revision'])) {
                $post_id = (isset($args['post_id'])) ? $args['post_id'] : \PP_Statuses_Functions::getPostID();

                if (!rvy_in_revision_workflow($post_id)) {
                    return $post_mime_type;
                }
            }

			if (!empty($_REQUEST['action']) && ('editpost' == $_REQUEST['action']) && empty($_REQUEST['publish'])) {        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$args['workflow_action'] = 'specified';	
			}

            $post_mime_type = \PublishPress_Statuses::instance()->filterStatus($post_mime_type, $args);

            switch ($post_mime_type) {
                case 'draft-revision':
                    $this->filtered_post_status = 'draft';
                    break;

                case 'future-revision':
                    $this->filtered_post_status = 'future';
                    break;

                default:
                    $this->filtered_post_status = 'pending';
            }
        }

        return $post_mime_type;
    }

    public function fltPlannerCalendarWhere($where, $wp_query) {
        if (!empty($wp_query->query) && !empty($wp_query->query['is_pp_calendar'])) {
            $revision_status_csv = implode("','", array_map('sanitize_key', rvy_revision_statuses()));
            $where .= " AND post_mime_type NOT IN ('$revision_status_csv')";
        }

        return $where;
    }

    public function fltInsertPostEmptyContent($maybe_empty, $postarr) {
        $this->filtered_post_status = false;
        $this->filtered_post_mime_type = false;

        return $maybe_empty;
    }

    /*
     * Admin
     */ 
    function fltPostEditorStatusArgs($status_args, $post) {
        if (rvy_in_revision_workflow($post)) {
            if (!is_array($status_args)) {
                $status_args = [];
            }

            $status_args['taxonomy'] = 'pp_revision_status';
        }

        return $status_args;
    }
}
