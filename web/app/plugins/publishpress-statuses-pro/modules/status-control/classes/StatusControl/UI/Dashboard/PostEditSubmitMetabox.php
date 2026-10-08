<?php
namespace PublishPress\Statuses\StatusControl\UI\Dashboard;

class PostEditSubmitMetabox
{
    /**
     *  Classic Editor Post Submit Metabox: Post Visibility HTML
     */
    public static function post_visibility_display($post, $args)
    {
        $defaults = ['is_administrator' => false, 'type_obj' => false, 'post_status_obj' => false, 'can_publish' => false];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $attributes = PPS::attributes();

        echo esc_html(__('Visibility:')); ?>
        <span id="post-visibility-display"><?php
            $vis_status = $post_status_obj->name;
            $vis_status_obj = $post_status_obj;

            if ($vis_status_obj->private) {
                $visibility = $vis_status;
                $post->post_password = '';
                $visibility_trans = $vis_status_obj->labels->visibility;
            } elseif (!empty($post->post_password)) {
                $visibility = 'password';
                $visibility_trans = __('Password protected');
            } elseif ('publish' == $vis_status) {
                $post->post_password = '';
                $visibility = 'public';

                if (('post' == $post->post_type || post_type_supports($post->post_type, 'sticky')) && is_sticky($post->ID)) {
                    $visibility_trans = __('Public, Sticky');
                } else {
                    $visibility_trans = __('Public');
                }
            } elseif ($vis_status_obj->public) {
                $post->post_password = '';
                $visibility = $vis_status;

                if (('post' == $post->post_type || post_type_supports($post->post_type, 'sticky')) && is_sticky($post->ID)) {
                    $visibility_trans = sprintf(__('%s, Sticky', 'publishpress-statuses-pro'), $vis_status_obj->label);
                } else {
                    $visibility_trans = $vis_status_obj->labels->visibility;
                }
            } else {
                $visibility = 'public';
                $visibility_trans = __('Public');
            }

            echo esc_html($visibility_trans); ?>
        </span>

        <?php if ($can_publish) { ?>
        <a href="#visibility" class="edit-visibility hide-if-no-js"><?php echo esc_html(__('Edit')); ?></a>

        <div id="post-visibility-select" class="hide-if-js">
            <input type="hidden" name="hidden_post_password" id="hidden-post-password" value="<?php echo esc_attr($post->post_password); ?>" autocomplete="off" />
            <?php if (post_type_supports($post->post_type, 'sticky')): ?>
                <input type="checkbox" style="display:none" name="hidden_post_sticky" id="hidden-post-sticky" value="sticky" <?php checked(is_sticky($post->ID)); ?> />
            <?php endif; ?>
            <input type="hidden" name="hidden_post_visibility" id="hidden-post-visibility" value="<?php echo esc_attr($visibility); ?>" autocomplete="off"/>

            <input type="radio" name="visibility" id="visibility-radio-public" value="public" autocomplete="off" <?php checked($visibility, 'public'); ?> /> 
                <label for="visibility-radio-public" class="selectit"><?php echo esc_html(__('Public')); ?></label><br/>

            <?php
            if ((($post->post_type == 'post') || post_type_supports($post->post_type, 'sticky')) && current_user_can('edit_others_posts')) : ?>
                <span id="sticky-span">
                    <input id="sticky" name="sticky" type="checkbox" value="sticky" <?php checked(is_sticky($post->ID)); ?> tabindex="4" autocomplete="off"/> 
                    <label for="sticky" class="selectit"><?php echo esc_html(__('Stick this to the front page')) ?></label><br/>
                </span>
            <?php endif; ?>

            <input type="radio" name="visibility" id="visibility-radio-password" value="password" autocomplete="off" <?php checked($visibility, 'password'); ?> /> 
            <label for="visibility-radio-password" class="selectit"><?php echo esc_html(__('Password protected')); ?></label><br/>

            <span id="password-span"><label for="post_password"><?php echo esc_html(__('Password:')); ?></label>
            <input type="text" name="post_password" id="post_password" value="<?php echo esc_attr($post->post_password); ?>" autocomplete="off"/><br/>
            </span>

            <?php if ($_status_obj = get_post_status_object('private')) : ?>
                <input type="radio" name="visibility" id="visibility-radio-private" value="private" autocomplete="off" <?php checked($visibility, 'private'); ?> /> 
                <label for="visibility-radio-private" class="selectit"><?php echo esc_html($_status_obj->label) ?></label>
                <br/>
            <?php endif; ?>

            <?php
            if (!PPS::privacyStatusesDisabled()) :
                $i = 0;
                $pvt_stati = publishpress_status_control()->orderTypes(
                    \PP_Statuses_Functions::getPostStatuses(
                        ['private' => true, 'post_type' => $post->post_type], 
                        'object'
                    ),
                    ['order_property' => 'label']
                );

                foreach ($pvt_stati as $_status => $status_obj) :
                    $i++;

                    if ('private' == $_status)
                        continue;

                    if (!$is_administrator) {
                        if (empty($type_obj->cap->set_posts_status)) {
                            $set_status_cap = $type_obj->cap->publish_posts;
                        } else {
                            $_caps = $attributes->getConditionCaps(
                                $type_obj->cap->set_posts_status, 
                                $post->post_type, 
                                'post_status', 
                                $_status
                            );

                            if (!$set_status_cap = reset($_caps)) {
                                $set_status_cap = $type_obj->cap->set_posts_status;
                            }
                        }

                        if (!current_user_can($set_status_cap))
                            continue;
                    }
                    ?>
                    <input type="radio" name="visibility" class="pvt-custom" id="visibility-radio-<?php echo esc_attr($_status) ?>" value="<?php echo esc_attr($_status) ?>" autocomplete="off" <?php checked($visibility, $_status); ?> />
                    <label for="visibility-radio-<?php echo esc_attr($_status) ?>" class="selectit"><?php echo esc_html($status_obj->label) ?></label>

                    <br/>
                <?php
                endforeach;

            endif; // custom privacy statuses enabled
            ?>
            <?php if ($type_obj->hierarchical) : ?>
                <p>
        <span id="pp-propagate-privacy-span">
            <input id="pp-propagate-privacy" name="pp-propagate-privacy" class="pp-submitbox-customize " type="checkbox" value="1"/>
            <label for="pp-propagate-privacy" class="selectit">
            <?php printf(
                esc_html__('mirror selection to %1$s sub%2$s%3$s', 'publishpress-statuses-pro'), 
                '<a href="#child-visibility" class="pp-edit-ch-visibility">', 
                esc_html(strtolower($type_obj->labels->name)), 
                '</a>'
                ); 
            ?>
            </label>
        </span>
            </p>
            <?php endif; ?>
            <p>
                <a href="#visibility" class="save-post-visibility hide-if-no-js button"><?php echo esc_html(__('OK')); ?></a>
                <a href="#visibility" class="cancel-post-visibility hide-if-no-js"><?php echo esc_html(__('Cancel')); ?></a>
            </p>
        </div>
    <?php }
    }

    /**
     *  Classic Editor Post Submit Metabox: Subpost Visibility HTML
     */
    public static function subpost_visibility_display($post, $args)
    {
        $defaults = ['is_administrator' => false, 'type_obj' => false, 'can_publish' => false, 'ch_visibility' => ''];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $attributes = PPS::attributes();

        printf(esc_html(_x('Sub%1$s %2$s: ', 'restriction_attribute', 'publishpress-statuses-pro')), esc_html(strtolower($type_obj->labels->singular_name)), esc_html(__('Visibility'))); ?>
        <span id="ch_post-visibility-display"><?php
            if ($ch_visibility) {
                $child_status_obj = get_post_status_object($ch_visibility);
                $visibility_trans = (!empty($child_status_obj->labels)) ? $child_status_obj->labels->visibility : '';
            } else {
                $visibility_trans = esc_html__('(manual)', 'publishpress-statuses-pro');
            }

            echo esc_html($visibility_trans); ?></span>
        <?php
        if (!$can_publish) {
            return;
        } ?>

        <a href="#ch_visibility" class="ch_edit-visibility hide-if-no-js"><?php echo esc_html(__('Edit')); ?></a>

        <div id="ch_post-visibility-select" class="hide-if-js item-condition-select ">
            <input type="hidden" name="ch_hidden_post_visibility" id="ch_hidden-post-visibility" value="<?php echo esc_attr($ch_visibility); ?>" autocomplete="off"/>

            <input type="radio" name="ch_visibility" id="ch_visibility-radio-manual" value="" autocomplete="off" <?php checked($ch_visibility, ''); ?> /> 
            <label for="ch_visibility-radio-manual" class="selectit" title="<?php esc_attr_e('visibility of subpages set individually', 'publishpress-statuses-pro'); ?>">
            <?php esc_html_e('(manual)', 'publishpress-statuses-pro'); ?>
            </label><br/>

            <input type="radio" name="ch_visibility" id="ch_visibility-radio-publish" value="publish" autocomplete="off" <?php checked($ch_visibility, 'publish'); ?> /> 
            <label for="ch_visibility-radio-publish" class="selectit" title="<?php esc_attr_e('visibility of subpages set individually', 'publishpress-statuses-pro'); ?>">
            <?php echo esc_html(__('Public')); ?>
            </label><br/>

            <?php if ($_status_obj = get_post_status_object('private')) : ?>
                <input type="radio" name="ch_visibility" id="ch_visibility-radio-private" value="private" autocomplete="off" <?php checked($ch_visibility, 'private'); ?> /> 
                <label for="ch_visibility-radio-private" class="selectit"><?php echo esc_html($_status_obj->label) ?>
                </label>
                <br/>
            <?php endif; ?>

            <?php
            if (get_option('presspermit_privacy_statuses_enabled', 1)):
                $pvt_stati = publishpress_status_control()->orderTypes(\PP_Statuses_Functions::getPostStatuses(['private' => true, 'post_type' => $post->post_type], 'object'), ['order_property' => 'label']);
                
                foreach ($pvt_stati as $_status => $_status_obj) :
                    if ('private' == $_status)
                        continue;

                    if (!$is_administrator) {
                        if (empty($type_obj->cap->set_posts_status)) {
                            $set_status_cap = $type_obj->cap->publish_posts;
                        } else {
                            $cond_caps = $attributes->getConditionCaps($type_obj->cap->set_posts_status, $post->post_type, 'post_status', $_status);
                            if (!$set_status_cap = reset($cond_caps)) {
                                $set_status_cap = $type_obj->cap->set_posts_status;
                            }
                        }

                        if (!current_user_can($set_status_cap))
                            continue;
                    }
                    ?>
                    <input type="radio" name="ch_visibility" class="pvt-custom" id="ch_visibility-radio-<?php echo esc_attr($_status) ?>" value="<?php echo esc_attr($_status) ?>" autocomplete="off" <?php checked($ch_visibility, $_status); ?> /> 
                    <label for="ch_visibility-radio-<?php echo esc_attr($_status) ?>" class="selectit"><?php echo esc_html($_status_obj->label) ?>
                    </label>
                    <br/>
                <?php
                endforeach; 
            endif;    
            ?>
            <p>
                <a href="#child-visibility" class="ch_save-post-visibility hide-if-no-js button"><?php echo esc_html(__('OK')); ?></a>
            </p>
        </div>
        <?php
    }
}
