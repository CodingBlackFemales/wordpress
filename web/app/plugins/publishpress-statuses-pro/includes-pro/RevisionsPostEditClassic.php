<?php
namespace PublishPress_Statuses;

class RevisionsPostEditClassic {

    function __construct() {
        // Post Editor - Classic
        add_filter(
            'publishpress_statuses_post_status_object',
            function ($status_obj, $post) {
                if (function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post)) {
                    if ($_status_obj = get_post_status_object($post->post_mime_type)) {
                        $status_obj = $_status_obj;
                    }
                }

                return $status_obj;
            },
            10, 2
        );

        add_filter(
            'presspermit_preview_post_title',
            function ($title) {
                global $post;

                if (function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post)) {
                    $title = esc_html__('Preview revision in progress', 'revisionary');
                }
                
                return $title;
            }            
        );

        add_filter(
            'pp_statuses_display_visibility_ui',
            function($show_ui, $post) {
                if (function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post)) {
                    $show_ui = false;
                }

                return $show_ui;
            },
            10, 2
        );

        add_filter(
            'pp_statuses_display_revisions_ui',
            function($show_ui, $post) {
                if (function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post)) {
                    $show_ui = false;
                }

                return $show_ui;
            },
            10, 2
        );

        // Post Edit Classic
        add_filter(
            'publishpress_statuses_moderation_status_args',
            function ($status_args, $post) {
                if (rvy_in_revision_workflow($post)) {
                    $status_args['taxonomy'] = 'pp_revision_status';
                    unset( $status_args['moderation']);
                }
                
                return $status_args;
            },
            10, 2
        );

        add_filter(
            'pp_statuses_is_moderation',
            function ($is_moderation, $args) {
                global $post;

                $is_revision = function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post);
                
                return $is_moderation || $is_revision;
            }
            , 10, 2
        );

    
        // Post Edit Classic Metabox
        add_filter(
            'publishpress_statuses_disable_sequence_bypass',
            function ($disable_bypass, $post) {
                if ($is_revision = function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post->ID)) {
                    $disable_bypass = true;	
                }
                
                return $disable_bypass;
            },
            10, 2
        );

        add_filter(
            'publishpress_statuses_dropdown_statuses',
            function($moderation_statuses, $post) {
                if (function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post)) {
                    unset($moderation_statuses['draft']);	
                }
                
                return $moderation_statuses;
            },
            10, 2
        );
    }

}
