<?php
namespace PublishPress_Statuses;

class RevisionsPostEdit {

    function __construct() {
        // Post Editor - Gutenberg
        add_action('publishpress_statuses_load_status_guidance', [$this, 'actBlockEditorLoadStatusGuidance']);

        add_filter('publishpress_statuses_workflow_captions', [$this, 'fltWorkflowCaptions'], 10, 2);

        add_filter(
            'pp_statuses_custom_status_block_args',
            function ($args, $post) {
                if (function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post)) {
                    $args['isRevision'] = true;
                }
                
                return $args;
            },
            10, 2
        );

        add_action('admin_print_scripts', [$this, 'actPrintScripts'], 100);
    }

    function fltWorkflowCaptions($captions, $post) {
        if (rvy_in_revision_workflow($post)) {
            $captions->postStatus = __('Revision Status', 'publishpress-statuses-pro');
        }

        return $captions;
    }

    // Block Editor
    function actPrintScripts() {
        global $post;

        if (did_action('enqueue_block_editor_assets') && rvy_in_revision_workflow($post)) {
            ?>
            <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function ($) {
                setInterval(function() {
                    $('div.components-panel__body div.components-panel__row').each(function(e) {
                        if ($(this).attr('class') == 'components-panel__row') {
                            if ($(this).find('div.components-checkbox-control').length) {
                                $(this).hide();
                            }
                        }
                    });
                }, 200);
            });
            /* ]]> */
            </script>
            <?php
        }
    }

    function actBlockEditorLoadStatusGuidance() {        
        if (!defined('PP_STATUSES_ALLOW_PREPUBLISH_DISABLE')) {
            wp_enqueue_style('publishpress-statuses-post-block-edit-pro', PUBLISHPRESS_STATUSES_PRO_URL . '/common/css/post-block-edit-pro.css', [], PUBLISHPRESS_STATUSES_PRO_VERSION);

            global $post;

            if (!empty($post) && function_exists('rvy_in_revision_workflow') && rvy_in_revision_workflow($post) && in_array($post->post_type, \PublishPress_Statuses::instance()->getEnabledPostTypes())) {
                wp_enqueue_style('publishpress-statuses-revision-block-edit-pro', PUBLISHPRESS_STATUSES_PRO_URL . '/common/css/revision-block-edit-pro.css', [], PUBLISHPRESS_STATUSES_PRO_VERSION);
            }
        }
    }
}
