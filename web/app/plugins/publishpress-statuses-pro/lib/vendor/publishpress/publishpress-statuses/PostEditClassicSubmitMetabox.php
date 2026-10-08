<?php
namespace PublishPress_Statuses;

class PostEditClassicSubmitMetabox
{
    public static function post_submit_meta_box($post, $args = [])
    {
        // @todo: move moderation_statuses setup into separate function that can be called by Ajax 'pp_get_selectable_statuses'

        $is_administrator = \PublishPress_Statuses::isContentAdministrator();
        $type_obj = get_post_type_object($post->post_type);

        $post_status = apply_filters('presspermit_editor_ui_status', $post->post_status, $post, $args);

        if ('auto-draft' == $post_status)
            $post_status = 'draft';

        if (!$post_status_obj = get_post_status_object($post_status)) {
            $post_status_obj = get_post_status_object('draft');
        }

        if (!empty($post_status_obj->private) && ('private' != $post_status)) {
            // visibility property may be used by Permissions Pro
            if (!empty($post_status_obj->labels) && is_object($post_status_obj->labels)) {
                if (empty($post_status_obj->labels->visibility)) {
                    $post_status_obj->labels->visibility = $post_status_obj->label;
                }
            }
        }

        $moderation_statuses = Admin::get_selectable_statuses($post, $args);
        $moderation_statuses = array_diff_key($moderation_statuses, array_fill_keys(['_public', 'publish', 'private', 'future'], true)); // entry for current post status added downstream

        $can_publish = current_user_can($type_obj->cap->publish_posts);

        $_args = compact('is_administrator', 'type_obj', 'post_status_obj', 'can_publish', 'moderation_statuses');
        $_args = array_merge($args, $_args);  // in case args passed into metabox are needed within static calls in the future
        ?>
        <div class="submitbox" id="submitpost">

            <div id="minor-publishing">
                <div id="minor-publishing-actions">
                    <div id="save-action">
                        <?php self::post_save_button($post, $_args); ?>
                    </div>
                    <div id="preview-action">
                        <?php self::post_preview_button($post, $_args); ?>
                    </div>
                    <div class="clear"></div>
                </div><?php // minor-publishing-actions ?>

                <div id="misc-publishing-actions">
                    <div class="misc-pub-section misc-pub-post-status">
                        <?php self::post_status_display($post, $_args); ?>
                    </div>

                    <?php if ((!defined('PRESSPERMIT_STATUSES_VERSION') || !get_option('presspermit_privacy_statuses_enabled', 1)) && apply_filters('pp_statuses_display_visibility_ui', true, $post)):?>
                    <div class="misc-pub-section misc-pub-visibility" id="visibility">
                        <?php _e('Visibility:', 'publishpress-statuses'); ?>
                        <span id="post-visibility-display">
                            <?php
                            if ( 'private' === $post->post_status ) {
                                $post->post_password = '';
                                $visibility          = 'private';
                                $visibility_trans    = __( 'Private' );
                            } elseif ( ! empty( $post->post_password ) ) {
                                $visibility       = 'password';
                                $visibility_trans = __( 'Password protected' );
                            } elseif ( 'post' === $post->post_type && is_sticky( $post->ID ) ) {
                                $visibility       = 'public';
                                $visibility_trans = __( 'Public, Sticky' );
                            } else {
                                $visibility       = 'public';
                                $visibility_trans = __( 'Public' );
                            }

                            echo esc_html( $visibility_trans );
                            ?>
                        </span>

                        <?php if ( $can_publish ) { ?>
                            <a href="#visibility" class="edit-visibility hide-if-no-js" role="button"><span aria-hidden="true"><?php _e( 'Edit' ); ?></span> <span class="screen-reader-text">
                                <?php
                                /* translators: Hidden accessibility text. */
                                _e( 'Edit visibility' );
                                ?>
                            </span></a>

                            <div id="post-visibility-select" class="hide-if-js">
                                <input type="hidden" name="hidden_post_password" id="hidden-post-password" value="<?php echo esc_attr( $post->post_password ); ?>" />
                                <?php if ( 'post' === $post->post_type ) : ?>
                                    <input type="checkbox" style="display:none" name="hidden_post_sticky" id="hidden-post-sticky" value="sticky" <?php checked( is_sticky( $post->ID ) ); ?> />
                                <?php endif; ?>

                                <input type="hidden" name="hidden_post_visibility" id="hidden-post-visibility" value="<?php echo esc_attr( $visibility ); ?>" />
                                <input type="radio" name="visibility" id="visibility-radio-public" value="public" <?php checked( $visibility, 'public' ); ?> /> <label for="visibility-radio-public" class="selectit"><?php _e( 'Public' ); ?></label><br />

                                <?php if ( 'post' === $post->post_type && current_user_can( 'edit_others_posts' ) ) : ?>
                                    <span id="sticky-span"><input id="sticky" name="sticky" type="checkbox" value="sticky" <?php checked( is_sticky( $post->ID ) ); ?> /> <label for="sticky" class="selectit"><?php _e( 'Stick this post to the front page' ); ?></label><br /></span>
                                <?php endif; ?>

                                <input type="radio" name="visibility" id="visibility-radio-password" value="password" <?php checked( $visibility, 'password' ); ?> /> <label for="visibility-radio-password" class="selectit"><?php _e( 'Password protected' ); ?></label><br />
                                <span id="password-span"><label for="post_password"><?php _e( 'Password:' ); ?></label> <input type="text" name="post_password" id="post_password" value="<?php echo esc_attr( $post->post_password ); ?>"  maxlength="255" /><br /></span>

                                <input type="radio" name="visibility" id="visibility-radio-private" value="private" <?php checked( $visibility, 'private' ); ?> /> <label for="visibility-radio-private" class="selectit"><?php _e( 'Private' ); ?></label><br />

                                <p>
                                    <a href="#visibility" class="save-post-visibility hide-if-no-js button"><?php _e( 'OK' ); ?></a>
                                    <a href="#visibility" class="cancel-post-visibility hide-if-no-js button-cancel"><?php _e( 'Cancel' ); ?></a>
                                </p>
                            </div>
                        <?php } ?>
                    </div>
                    <?php endif;?>

                    <?php 
                    // Prevent Permissions Pro Status control from adding a second Visibility div
                    if (!defined('PRESSPERMIT_STATUSES_VERSION') || version_compare(PRESSPERMIT_STATUSES_VERSION, '4.0.8', '>') || get_option('presspermit_privacy_statuses_enabled', 1)) {
                        do_action('pp_statuses_post_submitbox_misc_sections', $post, $_args);
                    }
                    ?>

                    <?php
                    if (!empty($args['args']['revisions_count']) && apply_filters('pp_statuses_display_revisions_ui', true, $post)) :
                        $revisions_to_keep = wp_revisions_to_keep($post);
                        ?>
                        <div class="misc-pub-section num-revisions">
                            <?php
                            if ($revisions_to_keep > 0 && $revisions_to_keep <= $args['args']['revisions_count']) {
                                echo '<span title="' . esc_attr(sprintf(__('Your site is configured to keep only the last %s revisions.'),
                                        number_format_i18n($revisions_to_keep))) . '">';
                                printf(esc_html(__('Revisions: %s')), '<b>' . (int) number_format_i18n($args['args']['revisions_count']) . '+</b>');
                                echo '</span>';
                            } else {
                                printf(esc_html(__('Revisions: %s')), '<b>' . (int) number_format_i18n($args['args']['revisions_count']) . '</b>');
                            }
                            ?>
                            <a class="hide-if-no-js"
                               href="<?php echo esc_url(get_edit_post_link($args['args']['revision_id'])); ?>"><?php _ex('Browse', 'revisions'); ?></a>
                        </div>
                    <?php
                    endif;
                    ?>

                    <?php
                    if ($can_publish) : // Contributors don't get to choose the date of publish
                        ?>
                        <div class="misc-pub-section curtime misc-pub-section-last">
                            <?php self::post_time_display($post, $_args); ?>
                        </div>
                    <?php endif; ?>

                    <?php do_action('post_submitbox_misc_actions', $post); ?>

                </div> <?php // misc-publishing-actions ?>

                <div class="clear"></div>
            </div> <?php // minor-publishing ?>

            <div id="major-publishing-actions">
                <?php do_action('post_submitbox_start', $post); ?>

                <div id="delete-action">
                    <?php // PP: no change from WP core
                    if (current_user_can("delete_post", $post->ID)) {
                        if (!EMPTY_TRASH_DAYS)
                            $delete_text = __('Delete Permanently');
                        else
                            $delete_text = __('Move to Trash');
                        ?>
                        <a class="submitdelete deletion"
                           href="<?php echo get_delete_post_link($post->ID); ?>"><?php echo esc_html($delete_text); ?></a><?php
                    } ?>
                </div>

                <div id="publishing-action">
                    <?php self::post_publish_ui($post, $_args); ?>
                </div>
                
                <?php 
                global $current_user;

				$disable_bypass = apply_filters(
					'publishpress_statuses_disable_sequence_bypass',
					false,
					$post
				);

                if (!$disable_bypass
                && (empty($post_status_obj) || (empty($post_status_obj->public) && empty($post_status_obj->private) && ('future' != $post_status)))
                && \PublishPress_Statuses::instance()->workflow_by_sequence
                && (current_user_can('administrator')
                || (!empty($type_obj->cap->publish_posts) && current_user_can($type_obj->cap->publish_posts)) 
                || current_user_can('pp_bypass_status_sequence'))
                && (!isset($current_user->allcaps['pp_bypass_status_sequence']) || !empty($current_user->allcaps['pp_bypass_status_sequence'])) // allow explicit blockage
                )
                :?>
                <div class="clear">
                    <span id="pp-override-sequence" style="float:right; margin: 5px; margin-bottom: 0;">
                        <label title="<?php echo esc_attr(__('Restore default behavior of publish button', 'publishpress-statuses'));?>">
                            <input type="checkbox" name="pp_statuses_bypass_sequence" id="pp_statuses_bypass_sequence" /> <?php esc_html_e('Bypass sequence', 'publishpress-statuses');?> 
                        </label>
                    </span>
                </div>
                <?php endif;?>

                <br />

                <div class="clear"></div>
            </div> <?php // major-publishing-actions ?>

        </div> <?php // submitpost ?>

        <?php
    }

    /*
     *  Classic Editor Post Submit Metabox: Post Save Button HTML
     */
    public static function post_save_button($post, $args)
    {
        $post_status_obj = apply_filters(
        	'publishpress_statuses_post_status_object', 
        	$args['post_status_obj'],
        	$post,
        	array_merge($args, ['require_labels' => true])
        );
        
        $is_moderation = apply_filters(
			'pp_statuses_is_moderation',
			!empty($post_status_obj->moderation),
			$args
		);
        // @todo: confirm we don't need a hidden save button when current status is private */
        if (!$post_status_obj->public && empty($post_status_obj->private) && !$is_moderation && ('future' != $post_status_obj->name)) :
            if (!empty($post_status_obj->labels->update)) {
                $save_as = $post_status_obj->labels->update;
            } else {
                $post_status_obj = get_post_status_object('draft');
                $save_as = $post_status_obj->labels->save_as;
            }
            ?>
            <input type="submit" name="save" id="save-post" value="<?php echo esc_attr($save_as) ?>"
                   tabindex="4" class="button button-highlighted"/>
        <?php elseif ($is_moderation && ('future' != $post_status_obj->name)) :
            if (apply_filters('presspermit_display_save_as_button', true, $post, $args)):
                $save_caption = isset($post_status_obj->labels->save_as) ? $post_status_obj->labels->save_as : __('Save');
                ?>
                <input type="submit" name="save" id="save-post" value="<?php echo esc_attr($save_caption) ?>"
                    tabindex="4" class="button button-highlighted"/>
                <?php 
            endif;
            ?>
        <?php else : ?>
            <input type="submit" name="save" id="save-post" value="<?php echo esc_attr(__('Save')); ?>"
                   class="button button-highlighted" style="display:none"/>
        <?php endif; ?>

        <span class="spinner" style="margin:2px 2px 0; display: none"></span>
        <?php
    }

    /**
     *  Classic Editor Post Submit Metabox: Post Preview Button HTML
     */
    public static function post_preview_button($post, $args)
    {
        if (empty($args['post_status_obj'])) return;

        if ($type_obj = get_post_type_object($post->post_type)) {
            if (empty($type_obj->public) && empty($type_obj->publicly_queryable)) {
                return;
            }
        }

        $post_status_obj = $args['post_status_obj'];
        ?>
        <?php
        if ($post_status_obj->public) {
            $preview_link = esc_url(get_permalink($post->ID));
            $preview_button = __('Preview');
            $preview_title = '';
        } else {
            $preview_link = esc_url(apply_filters(
                'preview_post_link', 
                add_query_arg('preview', 'true', get_permalink($post->ID)),
                $post
            ));
            
            $preview_button = apply_filters('presspermit_preview_post_label', __('Preview'));
            $preview_title = apply_filters('presspermit_preview_post_title', '');
        }
        ?>
        <a class="preview button" href="<?php echo esc_url($preview_link); ?>" target="wp-preview" id="post-preview"
           tabindex="4" title="<?php echo esc_attr($preview_title);?>"><?php echo esc_html($preview_button); ?></a>
        <input type="hidden" name="wp-preview" id="wp-preview" value=""/>
        <?php
    }

    /**
     *  Classic Editor Post Submit Metabox: Post Status Dropdown HTML
     */
    public static function post_status_display($post, $args)
    {
        $defaults = ['post_status_obj' => false, 'can_publish' => false, 'moderation_statuses' => []];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

		$post_status_obj = apply_filters('publishpress_statuses_post_status_object', $post_status_obj, $post);

        if (!isset($moderation_statuses['draft'])) {
            $moderation_statuses = apply_filters(
            	'publishpress_statuses_dropdown_statuses',
	            array_merge(
	                ['draft' => get_post_status_object('draft')], 
	                $moderation_statuses
	            ),
	            $post
	    	);
        }

        ?>
        <label for="post_status"><?php echo esc_html(__('Status:')); ?></label>
        <?php
        $post_status = $post_status_obj->name;
        ?>
        <span id="post-status-display">
        <?php
        if (!empty($post_status_obj->private))
            echo esc_html(__('Privately Published'));
        elseif (!empty($post_status_obj->public))
            echo esc_html(__('Published'));
        elseif (!empty($post_status_obj->labels->caption))
            echo esc_html($post_status_obj->labels->caption);
        else
            echo esc_html($post_status_obj->label);
        ?>
        </span> 
        <?php

        // multiple moderation stati are selectable or a single non-current moderation stati is selectable
        $select_moderation = (count($moderation_statuses) > 1 || ($post_status != key($moderation_statuses)));

        if (!empty($post_status_obj->public) || !empty($post_status_obj->private) || $can_publish || $select_moderation) { 
            ?>
            <a href="#post_status"
            <?php if (!empty($post_status_obj->private) || (!empty($post_status_obj->public) && 'publish' != $post_status)) { ?>style="display:none;"
            <?php } ?>class="edit-post-status hide-if-no-js" tabindex='4'><?php echo esc_html(__('Edit')) ?></a>

            <div id="post-status-select" class="hide-if-js">
                <input type="hidden" name="hidden_post_status" id="hidden_post_status"
                    value="<?php echo esc_attr($post_status); ?>"/>
                <select name='post_status' id='post_status' tabindex='4' autocomplete='off'>

                    <?php if (!empty($post_status_obj->public) || !empty($post_status_obj->private) || ('future' == $post_status)) : ?>
                        <option <?php selected(true, true); ?> value='_public'>
                        <?php echo esc_html($post_status_obj->labels->caption) ?>
                        </option>
                    <?php endif; ?>

                    <?php
                    foreach ($moderation_statuses as $_status => $_status_obj) : 										// @todo: API
                        if (!empty($_status_obj->public) || !empty($_status_obj->private) || ('future' == $_status) || ('future-revision' == $_status)) {
                            continue;
                        }
                    ?>
                        <option <?php selected($post_status, $_status); ?> value='<?php echo esc_attr($_status) ?>'>
                        <?php 
                        $caption = (!empty($_status_obj->status_parent) && !empty($moderation_statuses[$_status_obj->status_parent])) 
                        ? '— ' . $_status_obj->labels->caption
                        : $_status_obj->labels->caption;

                        echo esc_html($caption);
                        ?>
                        </option>
                    <?php endforeach;?>
                </select>
                <a href="#post_status" class="save-post-status hide-if-no-js button"><?php echo esc_html(__('OK')); ?></a>

                <div class="pp-status-cancel">
                <a href="#post_status" class="pp-cancel-post-status hide-if-no-js"><?php echo esc_html(__('Cancel')); ?></a>
                </div>

                <span id="pp_statuses_ui_rendered" style="display:none"></span>
            </div>

        <?php }
    }

    /**
     *  Classic Editor Post Submit Metabox: Post Time Display HTML
     */
    public static function post_time_display($post, $args)
    {
        global $action;

        if (empty($args['post_status_obj'])) return;

        if (!defined('PRESSPERMIT_MIN_DATE_STRING')) {
            define('PRESSPERMIT_MIN_DATE_STRING', '0000-00-00 00:00:00');
        }

        $post_status_obj = $args['post_status_obj'];
        ?>
        <span id="timestamp">
        <?php
        // translators: Publish box date formt, see http://php.net/date
        $datef = __('M j, Y @ G:i');

        if (0 != $post->ID) {
            $published_stati = get_post_stati(['public' => true, 'private' => true], 'names', 'or');
            $date = date_i18n($datef, strtotime($post->post_date));

            if ('future' == $post_status_obj->name) { // scheduled for publishing at a future date
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                printf(__('Scheduled for: %s'), '<b>' . esc_html($date) . '</b>');

            } elseif (in_array($post_status_obj->name, $published_stati)) { // already published
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                printf(__('Published on: %s'),  '<b>' . esc_html($date) . '</b>');

            } elseif (in_array($post->post_date_gmt, [constant('PRESSPERMIT_MIN_DATE_STRING'), '0000-00-00 00:00:00'])) { // draft, 1 or more saves, no date specified
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo __('Publish <b>immediately</b>');

            } elseif (time() < strtotime($post->post_date_gmt . ' +0000')) { // draft, 1 or more saves, future date specified
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                printf(esc_html(__('Schedule for: %s')), '<b>' . esc_html($date) . '</b>');

            } elseif (!function_exists('rvy_in_revision_workflow') || !rvy_in_revision_workflow($post->ID) || (strtotime($post->post_date_gmt) > strtotime( gmdate("Y-m-d H:i:s") ))) { // draft, 1 or more saves, date specified
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                printf(__('Publish on: %s'), '<b>' . esc_html($date) . '</b>');
            } else {
                // translators: %s is html markup
                printf(esc_html__('Publish %1$s on approval %2$s', 'publishpress-statuses'), '<b>', '</b>');
            }
        } else { // draft (no saves, and thus no date specified)
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo __('Publish <b>immediately</b>');
        }
        ?></span>
        <a href="#edit_timestamp" class="edit-timestamp hide-if-no-js" tabindex='4'><?php echo esc_html(__('Edit')) ?></a>
        <div id="timestampdiv" class="hide-if-js"><?php touch_time(($action == 'edit'), 1, 4); ?></div>
        <?php
    }

    /**
     *  Classic Editor Post Submit Metabox: Post Publish Button HTML
     */
    public static function post_publish_ui($post, $args)
    {
        $defaults = ['post_status_obj' => false, 'can_publish' => false, 'moderation_statuses' => []];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        if (\PublishPress_Statuses::instance()->workflow_disabled) :?>
            <div id="publishing-action">
                <span class="spinner"></span>
                <input name="original_publish" type="hidden" id="original_publish" value="<?php esc_attr_e( 'Update' ); ?>" />
                <input name="save" type="submit" class="button button-primary button-large" id="publish" value="<?php esc_attr_e( 'Update' ); ?>" />
            </div>
            <div class="clear"></div>
        <?php 
            return;
        endif;
        ?>
        <span class="spinner" style="display:none"></span>

        <?php
        if ((empty($post_status_obj->public) && empty($post_status_obj->private) && ('future' != $post_status_obj->name))) {
            $status_obj = \PublishPress_Statuses::defaultStatusProgression($post);

            if (!empty($status_obj->public) || !empty($status_obj->private)) :
                if (!empty($post->post_date_gmt) && time() < strtotime($post->post_date_gmt . ' +0000')) :
                    $future_status_obj = get_post_status_object('future');
                    ?>
                    <input name="original_publish" type="hidden" id="original_publish" value="<?php echo esc_attr($future_status_obj->labels->publish) ?>"/>
                    <input name="publish" type="submit" class="button button-primary button-large" id="publish" tabindex="5" accesskey="p" value="<?php echo esc_attr($future_status_obj->labels->publish) ?>"/>
                <?php
                else :
                    $publish_status_obj = get_post_status_object('publish');
                    ?>
                    <input name="original_publish" type="hidden" id="original_publish" value="<?php echo esc_attr($publish_status_obj->labels->publish) ?>"/>
                    <input name="publish" type="submit" class="button button-primary button-large" id="publish" tabindex="5" accesskey="p" value="<?php echo esc_attr(__('Update')); ?>"/>
                <?php
                endif;
            else :
                echo '<input name="pp_submission_status" type="hidden" id="pp_submission_status" value="' . esc_attr($status_obj->name) . '" />';
                ?>
                <input name="original_publish" type="hidden" id="original_publish" value="<?php echo esc_attr($status_obj->labels->publish) ?>"/>
                <input name="publish" type="submit" class="button button-primary button-large" id="publish" tabindex="5" accesskey="p" value="<?php echo esc_attr($status_obj->labels->publish) ?>"/>
            <?php
            endif;
        } else { ?>
            <input name="original_publish" type="hidden" id="original_publish" value="<?php echo esc_attr(__('Update')); ?>"/>
            <input name="save" type="submit" class="button button-primary button-large" id="publish" tabindex="5" accesskey="p" value="<?php echo esc_attr(__('Update')); ?>"/>
            <?php
        }
    }
}