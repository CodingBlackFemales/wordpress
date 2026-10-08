<?php
namespace PublishPress\Statuses\StatusControl\UI\Dashboard;

class PostEdit 
{
    function __construct() {
        // Classic Editor support
        //
        // This script executes on the 'init' action if is_admin() and $pagenow is 'post-new.php' or 'post.php' and the block editor is not active.
        //

        add_action('add_meta_boxes', [$this, 'act_comments_metabox'], 10, 2);

        add_action('admin_print_footer_scripts', [$this, 'act_force_visibility_js'], 99);

        add_action('pp_statuses_post_submitbox_misc_sections', [$this, 'act_publish_metabox_misc_pub_section'], 10, 2);

        if (\PP_Statuses_Functions::is_REQUEST('message', 6)) {
            add_filter('post_updated_messages', [$this, 'flt_post_updated_messages'], 50);
    	}

        add_action('admin_head', [$this, 'act_object_edit_scripts'], 100);  // needs to load after post.js to unbind handlers
    }

    public function act_object_edit_scripts()
    {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

        wp_enqueue_script(
            'presspermit-object-edit', 
            PRESSPERMIT_STATUSES_URLPATH . "/common/js/object-edit{$suffix}.js", 
            ['jquery', 'jquery-form'], 
            PRESSPERMIT_STATUSES_VERSION, 
            true
        );

        global $wp_scripts;
        $wp_scripts->in_footer [] = 'presspermit-object-edit';  // otherwise it will not be printed in footer (@todo review)
    }

    function act_publish_metabox_misc_pub_section($post, $args) {
        if (!get_option('presspermit_privacy_statuses_enabled', 1)) {
            return;
        }
        ?>
        <div class="misc-pub-section " id="visibility">
            <?php 
            require_once(PP_STATUS_CONTROL_CLASSPATH . '/UI/Dashboard/PostEditSubmitMetabox.php');
            PostEditSubmitMetabox::post_visibility_display($post, $args); 
            ?>
        </div>

        <?php 
        $type_obj = get_post_type_object($post->post_type);

        if (!empty($type_obj) && $type_obj->hierarchical) :
            $attributes = PPS::attributes();
            $ch_visibility = $attributes->getItemCondition('post', 'force_visibility', ['assign_for' => 'children', 'id' => $post->ID]);
            ?>
            <div class="misc-pub-section<?php if (!$ch_visibility) echo ' hide-if-js'; ?>"
                    id="ch_visibility"
                    title="<?php printf(esc_attr(__('force visibility of all sub%s', 'publishpress-statuses-pro')), esc_html(strtolower($type_obj->labels->name))); ?>">
                <?php
                require_once(PP_STATUS_CONTROL_CLASSPATH . '/UI/Dashboard/PostEditSubmitMetabox.php');
                $args['ch_visibility'] = $ch_visibility;
                PostEditSubmitMetabox::subpost_visibility_display($post, $args); 
                ?>
            </div>
        <?php endif; ?>
        <?php
    }

    // ensure Comments metabox for custom published / private stati
    public function act_comments_metabox($post_type, $post)
    {
        global $wp_meta_boxes;
        if (isset($wp_meta_boxes[$post_type]['normal']['core']['commentsdiv']))
            return;

        if ($post_status_obj = get_post_status_object($post->post_status)) {
            if (('publish' == $post->post_status || 'private' == $post->post_status) 
            && post_type_supports($post_type, 'comments')
            ) {
                add_meta_box('commentsdiv', __('Comments'), 'post_comment_meta_box', $post_type, 'normal', 'core');
            }
        }
    }

    public function act_force_visibility_js()
    {
        global $post;

        if (empty($post) || $post->post_password)
            return;

        $current_status_obj = get_post_status_object($post->post_status);
        $attribute_defs = PPS::attributes();

        $_args = is_post_type_hierarchical($post->post_type) ? ['id' => $post->ID] : ['default_only' => true];
        $_args['post_type'] = $post->post_type;

        // causes PPCE filter to return object indicating if forced status is due to a "Subpage visibility" setting or a forced default privacy
        $_args['return_meta'] = true;

        if (!$force = $attribute_defs->getItemCondition('post', 'force_visibility', $_args))
            return;

        if (!is_object($force)) {
            $force = (object)['force_status' => $force, 'force_basis' => 'direct'];
        }

        if ('publish' == $force->force_status)
            $status_label = esc_html__('Public');
        else {
            $force_status_object = get_post_status_object($force->force_status);
            $status_label = $force_status_object->label;
        }

        $post_type_object = get_post_type_object($post->post_type);

        $force_caption = sprintf(__('Visibility forced to %1$s', 'publishpress-statuses-pro'), $status_label, $post_type_object->labels->singular_name);

        $vis = ('publish' == $force->force_status) ? 'public' : $force->force_status;

// @todo: move to .js
        if ($force) :?>
        <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function ($) {
                <?php if ( $current_status_obj->public || $current_status_obj->private ) : ?>
                $('#visibility-radio-<?php echo esc_attr($vis); ?>').prop('checked', true);
                $('#post-visibility-display').html($('#visibility-radio-<?php echo esc_attr($vis); ?>').next('label').html());
                updateStatusDropdownElements();
                updateStatusCaptions();
                <?php endif;?>

                $('input[name="visibility"][value!="<?php echo esc_attr($vis)?>"][value!="public"][value!="password"]').prop('disabled', true).siblings('label').addBack().attr('title', '<?php echo esc_attr($force_caption); ?>');
                $('input[name="visibility"][value="public"]').siblings('label').attr('title', '<?php echo esc_attr($force_caption); ?>'); <?php /* can't disable public option because it's needed to select unpublished stati, but do alter its title */ ?>

                <?php if ( 'default' != $force->force_basis ) :  /* if the visibility forcing stems from the default privacy setting for the post type, still allow subpages to be custom-forced to a different privacy */?>
                $('input[name="ch_visibility"][value!="<?php echo esc_attr($vis);?>"]').prop('disabled', true).siblings('label').addBack().attr('title', '<?php echo esc_attr($force_caption); ?>');
                <?php endif; ?>
            });
            /* ]]> */
        </script>
        <?php
        endif;
    } // end function act_force_visibility_js

    public function flt_post_updated_messages($messages)
    {
        if (!\PublishPress_Statuses::isContentAdministrator()) {
            if ($type_obj = get_post_type_object(\PP_Statuses_Functions::findPostType())) {
                if (!current_user_can($type_obj->cap->publish_posts)) {
                    global $post;

                    if ($post) {
                        if ($status_obj = get_post_status_object($post->post_status)) {
                            $messages['post'][6] = esc_attr(sprintf(__('Post set as %s', 'presspermit'), $status_obj->label));
                            $messages['page'][6] = esc_attr(sprintf(__('Page set as %s', 'presspermit'), $status_obj->label));
                        }
                    }
                }
            }
        }

        return $messages;
    }
}
