<?php
namespace PublishPress\Statuses\StatusControl\UI\Dashboard;

class PostsListing
{
    var $post_ids = [];

    function __construct()
    {
        // This script executes on the 'init' action if is_admin() for 'edit.php' and ajax action 'inline-save', if the post type is enabled for PP filtering. 
        //

        add_action('admin_print_footer_scripts', [$this, 'act_modify_inline_edit_ui']);

        if (\PP_Statuses_Functions::empty_REQUEST('post_status') && \PP_Statuses_Functions::empty_REQUEST('author')) {
            add_action('init', [$this, 'maybe_force_all_posts_view'], 70);
        }

        add_filter('presspermit_hide_quickedit', [$this, 'flt_hide_quickedit'], 10, 2);

        if (!\PP_Statuses_Functions::empty_REQUEST('pp_submission_done')) {
            add_action('admin_notices', [$this, 'act_submission_notice']);
        }
    }

    // Since we are providing WYSIWYCE, don't default non-Editors to "Mine" view
    function maybe_force_all_posts_view() {
        $user = publishpress_status_control()->getUser();

        if (!$post_type = \PP_Statuses_Functions::REQUEST_key('post_type')) {
            $post_type = 'post';
        }
    
        if (\PublishPress_Statuses::DisabledForPostType($post_type)) {
            return;
        }

        if ($type_obj = get_post_type_object($post_type)) {
            if (!empty($type_obj->cap->edit_others_posts) && empty($user->allcaps[$type_obj->cap->edit_others_posts])) {
                $_REQUEST['all_posts'] = 1;
            }
        }
    }

    function act_submission_notice()
    {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php
                if (!\PP_Statuses_Functions::empty_REQUEST('pp_submission_done')) {
                    if ($status_obj = get_post_status_object(\PP_Statuses_Functions::REQUEST_key('pp_submission_done'))) {
                        $type_obj = get_post_type_object(\PP_Statuses_Functions::findPostType());

                        if ($type_obj && \PublishPress_Statuses::DisabledForPostType($type_obj->name)) {
                            return;
                        }

                        $type_label = ($type_obj) ? strtolower($type_obj->labels->singular_name) : esc_html__('post', 'ppx');
                        printf(
                                esc_html__('Your %1$s was successfully submitted, but you cannot make further edits at this time. The current status of the %1$s is %2$s.', 'publishpress-statuses-pro'), 
                                esc_html($type_label), 
                                esc_html($status_obj->label)
                        );
                    }
                }
                ?>
            </p>
        </div>
        <?php
    }

    function flt_hide_quickedit($hide, $type_obj)
    {
        if (empty($type_obj) || \PublishPress_Statuses::DisabledForPostType($type_obj->name)) {
            return false;
        }

        return !PPS::havePermission('moderate_any');
    }

    // @todo: move to .js
    // add "keep" checkboxes for custom private stati; set checked based on current or scheduled post status
    // add conditions UI to inline edit
    function act_modify_inline_edit_ui()
    {
        $screen = get_current_screen();
        $post_type_object = get_post_type_object($screen->post_type);

        if (empty($post_type_object) || \PublishPress_Statuses::DisabledForPostType($screen->post_type)) {
            return;
        }
        
        /* Override a leftover Permissions Pro CSS rule */?>
        <style>
        #wpbody-content .quick-edit-row-page .inline-edit-status-sub span.title, #wpbody-content .bulk-edit-row-page .inline-edit-status-sub span.title {margin-right: 0 !important;}
        </style>

        <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function ($) {
                <?php
                $isContentAdministrator = \PublishPress_Statuses::isContentAdministrator();
                $moderation_statuses = [];
                global $typenow;

                $can_set_status = [];

                $can_set_status['publish'] = \PublishPress_Statuses::haveStatusPermission('set_status', $screen->post_type, 'publish');
                $can_set_status['private'] = \PublishPress_Statuses::haveStatusPermission('set_status', $screen->post_type, 'private');
                $can_set_status['future'] = $can_set_status['publish'];

                $_stati = publishpress_status_control()->orderTypes(
                    \PP_Statuses_Functions::getPostStatuses(
                        ['_builtin' => false, 'moderation' => true, 'post_type' => $typenow], 
                        'object'
                    ), 
                    ['order_property' => 'order']
                );
                
                foreach ($_stati as $status => $status_obj) {
                    $moderation_statuses[$status] = $status_obj;
                    
                    if ($isContentAdministrator || \PublishPress_Statuses::haveStatusPermission('set_status', $screen->post_type, $status)) {
                        $can_set_status[$status] = true;
                    }
                }

                $pvt_stati = [];
                $_stati = publishpress_status_control()->orderTypes(
                    \PP_Statuses_Functions::getPostStatuses(
                        ['private' => true, 'post_type' => $typenow], 
                        'object'
                    ), 
                    ['order_property' => 'label']
                );

                foreach ($_stati as $status => $status_obj) {
                    $pvt_stati[$status] = $status_obj;

                    if ($isContentAdministrator || \PublishPress_Statuses::haveStatusPermission('set_status', $screen->post_type, $status)) {
                        $can_set_status[$status] = true;
                    }
                }
                ?>

                <?php foreach( $moderation_statuses as $status => $status_obj ) :?>
                    if (!$('select[name="_status"] option[value="<?php echo esc_attr($status);?>"]').length) {
                        $('<option value="<?php echo esc_attr($status);?>"<?php if (empty($can_set_status[$status])) echo " disabled " ?>><?php echo esc_html($status_obj->label);?></option>').insertBefore('select[name="_status"] option[value="pending"]');
                    }
                <?php endforeach;?>

                <?php 
                if (!PPS::privacyStatusesDisabled()): 
                    ?>
                    if ($('select[name="_status"] option[value="-1"]').length) {
                        <?php foreach( $pvt_stati as $status => $status_obj ) :?>
                        if (!$('select[name="_status"] option[value="<?php echo esc_attr($status);?>"]').length) {
                            $('<option value="<?php echo esc_attr($status);?>"<?php if (empty($can_set_status[$status])) echo " disabled " ?>><?php echo esc_html($status_obj->label);?></option>').insertAfter('select[name="_status"] option[value="publish"]');
                        }
                        <?php endforeach;?>
                    }
                <?php endif;

                // also support forcing of default privacy for non-hierarchical types
                $is_hierarchical = is_post_type_hierarchical($typenow);

                if ( $is_hierarchical || (!empty(\PublishPress_Statuses::instance()->options->force_default_privacy[$typenow])) ) {
                    global $posts;
                    
                    if ( !empty($posts) ) {
                        $attributes = PPS::attributes();
        
                        foreach( array_keys($posts) as $key ) :
                            if (!in_array($posts[$key]->ID, $this->post_ids)) continue;
            
                            $force_vis = $attributes->getItemCondition(
                                'post',
                                'force_visibility',
                                ['id' => $posts[$key]->ID, 'assign_for' => 'item', 'default_only' => !$is_hierarchical, 'post_type' => $typenow]
                            );
            
                            if ( $is_hierarchical ) :
                                $child_status = $attributes->getItemCondition('post', 'force_visibility', ['id' => $posts[$key]->ID, 'assign_for' => 'children']);
                            ?>
                                $('#inline_<?php echo (int) $posts[$key]->ID;?> div._status').after('<div class="_status_sub"><?php echo esc_html($child_status);?></div>');
                            <?php endif; ?>
                            
                            $('#inline_<?php echo (int) $posts[$key]->ID;?> div._status').after('<div class="_force_vis"><?php echo esc_html($force_vis);?></div>');
                        <?php
                        endforeach;
                    }
                    ?>

                    $("tr.inline-edit-row-page label.inline-edit-status").parent().after('<div class="inline-edit-group"><label class="inline-edit-status-sub alignleft">'
                    + '<span class="title"><?php printf(esc_html__('Subpages', 'publishpress-statuses-pro'), esc_html($post_type_object->label));?></span>'
                    + '<select name="_status_sub" title="<?php printf(esc_html__('Force visibility of sub-%s', 'publishpress-statuses-pro'), esc_html($post_type_object->label));?>" autocomplete="off"></select>'
                    + '</label><span>'
                    + '<label for="pp_propagate_visibility" class="alignleft" style="display:none; margin-top:0.5em"><input type="checkbox" name="pp_propagate_visibility" id="pp_propagate_visibility" checked disabled />'
                    + ' <?php printf(esc_html__('apply to existing sub-%s', 'publishpress-statuses-pro'), esc_html($post_type_object->label));?></label>'
                    + '</span></div>');

                    var elems = '';
                    if (!$('select[name="_status_sub"] option[value="<?php echo esc_attr($status);?>"]').length) {
                        elems = elems + '<option value="publish"><?php echo esc_html(__('Public'));?></option>';
                        <?php foreach( $pvt_stati as $status => $status_obj ) :?>
                        elems = elems + '<option value="<?php echo esc_attr($status);?>"><?php echo esc_html($status_obj->label);?></option>';
                        <?php endforeach;?>
                    }
                    $("select[name='_status_sub']").html('<option value=""><?php esc_html_e('Manual Visibility selection', 'publishpress-statuses-pro');?></option>' + elems);
                    $('.inline-edit-status-sub select').prepend('<option value="-1"><?php esc_html_e('No Visibility Restrictions', 'publishpress-statuses-pro');?></option>');
                    $('.inline-edit-status-sub select option[value="-1"]').prop('selected', true);

                    $("label.inline-edit-status-sub span.title").append('<span class="pp_disclaimer" title="<?php esc_attr_e('Status may also be altered by category or term', 'publishpress-statuses-pro');?>"></span>');
                    $("select[name='_status_sub']").siblings('span').attr('title', $("select[name='_status_sub']").attr('title'));

                    $(document).on('click', 'select[name="_status_sub"]', function (e) {
                        $('input[name="pp_propagate_visibility"]').parent().toggle($(this).val() != -1 && $(this).val() != '');
                    });

                <?php } // endif hier ?>
            });
            //]]>
        </script>
        <?php
    } // end function modify_inline_edit_ui
}
