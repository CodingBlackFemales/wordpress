<?php
switch ($post_type){
    case 'comments':
        $custom_type = new stdClass();
        $custom_type->labels = new stdClass();
        $custom_type->labels->name = __('Comments', 'wp-all-import-pro');
        $custom_type->labels->singular_name = __('Comment', 'wp-all-import-pro');
        $custom_type->labels->plural_name = __('Comments', 'wp-all-import-pro');
        break;
    case 'woo_reviews':
        $custom_type = new stdClass();
        $custom_type->labels = new stdClass();
        $custom_type->labels->name = __('WooCommerce Reviews', 'wp-all-import-pro');
        $custom_type->labels->singular_name = __('Review', 'wp-all-import-pro');
        $custom_type->labels->plural_name = __('Reviews', 'wp-all-import-pro');
        break;
    default:
        $custom_type = wp_all_import_custom_type( $post_type );
        break;
}
?>
<div class="wpallimport-collapsed">
	<div class="wpallimport-content-section" style="overflow: hidden; padding-bottom: 0;">
		<div class="wpallimport-collapsed-header" style="margin-bottom: 15px;">
            <h3><?php printf(__('%s Content','wp-all-import-pro'), $custom_type->labels->singular_name);?></h3>
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner wpallimport-user-data">
				<div class="comments-import-fields">
                    <table class="form-table">
                        <tr>
                            <td>
                                <div class="input">
                                    <h4 style="margin-bottom:5px; margin-top: 0;"><?php _e('<b>Content</b>', 'wp-all-import-pro');?></h4>
                                    <textarea name="content" class="widefat rad4" style="width:100%;margin-bottom:5px;"><?php echo esc_attr($post['content']) ?></textarea>
                                </div>
                            </td>
                        </tr>
                        <?php if ($post_type == 'woo_reviews'): ?>
                            <tr>
                                <td>
                                    <div class="input">
                                        <h4 style="margin-bottom:5px; margin-top: 0;"><?php _e('<b>Rating</b>', 'wp-all-import-pro');?><a href="#help" class="wpallimport-help" title="<?php _e('The number of stars, 1 through 5.', 'wp-all-import-pro'); ?>" style="top: -1px;">?</a></h4>
                                        <input name="comment_rating" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['comment_rating']) ?>"/>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($post_type == 'comments'): ?>
                        <tr>
                            <td>
                                <div class="input">
                                    <h4 style="margin-bottom:5px; margin-top: 0;"><?php _e('<b>Parent Post</b>', 'wp-all-import-pro');?><a href="#help" class="wpallimport-help" title="<?php _e('Comments can be matched to their parent post by post ID, slug, or post title. Comments without a match will be skipped.', 'wp-all-import-pro'); ?>" style="top: -1px;">?</a></h4>
                                    <input name="comment_post" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['comment_post']) ?>"/>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($post_type == 'woo_reviews'): ?>
                            <tr>
                                <td>
                                    <div class="input">
                                        <h4 style="margin-bottom:5px; margin-top: 0;"><?php _e('<b>WooCommerce Product</b>', 'wp-all-import-pro');?><a href="#help" class="wpallimport-help" title="<?php _e('Reviews can be matched to their parent WooCommerce Product by post ID, slug, or product title. Reviews without a match will be skipped.', 'wp-all-import-pro'); ?>" style="top: -1px;">?</a></h4>
                                        <input name="comment_post" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['comment_post']) ?>"/>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td>
                                <div class="input">
                                    <h4 style="margin-bottom:5px; margin-top: 0;"><b><?php printf(__('%s Date', 'wp-all-import-pro'), $custom_type->labels->singular_name); ?></b><a href="#help" class="wpallimport-help" style="position:relative; top: -1px;" title="<?php _e('Import the date and time of the comment in GMT. Use any format supported by the PHP <b>strtotime</b> function. Pretty much any human-readable date and time will work.', 'wp-all-import-pro') ?>">?</a></h4>
                                    <div class="input">
                                        <input type="radio" id="date_type_specific" class="switcher" name="date_type" value="specific" <?php echo 'random' != $post['date_type'] ? 'checked="checked"' : '' ?> />
                                        <label for="date_type_specific">
                                            <?php _e('As specified', 'wp-all-import-pro') ?>
                                        </label>
                                        <div class="switcher-target-date_type_specific" style="vertical-align:middle; margin-top: 5px; margin-bottom: 10px;">
                                            <input type="text" class="datepicker" name="date" value="<?php echo esc_attr($post['date']) ?>"/>
                                        </div>
                                    </div>
                                    <div class="input">
                                        <input type="radio" id="date_type_random" class="switcher" name="date_type" value="random" <?php echo 'random' == $post['date_type'] ? 'checked="checked"' : '' ?> />
                                        <label for="date_type_random">
                                            <?php _e('Random dates', 'wp-all-import-pro') ?><a href="#help" class="wpallimport-help" style="position:relative; top: -1px;" title="<?php printf(__('%s will be randomly assigned dates in this range.', 'wp-all-import-pro'), $custom_type->labels->plural_name ); ?>">?</a>
                                        </label>
                                        <div class="switcher-target-date_type_random" style="vertical-align:middle; margin-top:5px;">
                                            <input type="text" class="datepicker" name="date_start" value="<?php echo esc_attr($post['date_start']) ?>" />
                                            <?php _e('and', 'wp-all-import-pro') ?>
                                            <input type="text" class="datepicker" name="date_end" value="<?php echo esc_attr($post['date_end']) ?>" />
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
				</div>
			</div>
            <div class="wpallimport-collapsed closed">
                <div class="wpallimport-content-section rad0" style="margin:0; border-top:1px solid #ddd; border-bottom: none; border-right: none; border-left: none; background: #f1f2f2;">
                    <div class="wpallimport-collapsed-header">
                        <h3 style="color:#40acad;"><?php _e('Advanced Options','wp-all-import-pro');?></h3>
                    </div>
                    <div class="wpallimport-collapsed-content" style="padding: 0;">
                        <div class="wpallimport-collapsed-content-inner">
                            <div class="comments-import-fields">
                                <table class="form-table">
                                    <tr>
                                        <td style="width: 48%; padding-right: 1%;">
                                            <div class="input">
                                                <h4 style="margin-bottom:5px; margin-top: 0;"><?php _e('<b>Approval Status</b>', 'wp-all-import-pro');?></h4>
                                                <div class="input">
                                                    <input type="radio" id="comment_approved" name="comment_approved" value="1" <?php echo '1' === $post['comment_approved'] ? 'checked="checked"' : '' ?> class="switcher"/>
                                                    <label for="comment_approved"><?php printf(__('Approve all %s', 'wp-all-import-pro'), $custom_type->labels->plural_name); ?></label>
                                                </div>
                                                <div class="input" style="position:relative;">
                                                    <input type="radio" id="comment_approved_xpath" class="switcher" name="comment_approved" value="xpath" <?php echo 'xpath' === $post['comment_approved'] ? 'checked="checked"': '' ?>/>
                                                    <label for="comment_approved_xpath"><?php _e('Set manually', 'wp-all-import-pro' )?></label> <br>
                                                    <div class="switcher-target-comment_approved_xpath">
                                                        <div class="input">
                                                            <input type="text" class="smaller-text" name="comment_approved_xpath" value="<?php echo esc_attr($post['comment_approved_xpath']) ?>" style="width: 50%;"/>
                                                            <a href="#help" class="wpallimport-help" title="<?php _e('1 for approved, 0 for not approved.', 'wp-all-import-pro') ?>" style="position:relative; top:0px;">?</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if ($post_type == 'woo_reviews'): ?>
                                                <div class="input">
                                                    <h4 style="margin-bottom:5px; margin-top: 0;"><?php _e('<b>Verified Status</b>', 'wp-all-import-pro');?></h4>
                                                    <div class="input">
                                                        <input type="radio" id="comment_verified" name="comment_verified" value="1" <?php echo '1' === $post['comment_verified'] ? 'checked="checked"' : '' ?> class="switcher"/>
                                                        <label for="comment_verified"><?php printf(__('Verify all %s', 'wp-all-import-pro'), $custom_type->labels->plural_name); ?></label>
                                                    </div>
                                                    <div class="input" style="position:relative;">
                                                        <input type="radio" id="comment_verified_xpath" class="switcher" name="comment_verified" value="xpath" <?php echo 'xpath' === $post['comment_verified'] ? 'checked="checked"': '' ?>/>
                                                        <label for="comment_verified_xpath"><?php _e('Set manually', 'wp-all-import-pro' )?></label> <br>
                                                        <div class="switcher-target-comment_verified_xpath">
                                                            <div class="input">
                                                                <input type="text" class="smaller-text" name="comment_verified_xpath" value="<?php echo esc_attr($post['comment_verified_xpath']) ?>" style="width: 50%;"/>
                                                                <a href="#help" class="wpallimport-help" title="<?php _e('1 for verified, 0 for not verified.', 'wp-all-import-pro') ?>" style="position:relative; top:0px;">?</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($post_type == 'comments'): ?>
                                            <div class="input">
                                                <h4 style="margin-bottom:5px; margin-top: 0;"><?php _e('<b>Comment Type</b>', 'wp-all-import-pro');?></h4>
                                                <div class="input">
                                                    <input type="radio" id="comment_type" name="comment_type" value="" <?php echo empty($post['comment_type']) ? 'checked="checked"' : '' ?> class="switcher"/>
                                                    <label for="comment_type"><?php _e('Standard comment', 'wp-all-import-pro') ?></label>
                                                </div>
                                                <div class="input" style="position:relative;">
                                                    <input type="radio" id="comment_type_xpath" class="switcher" name="comment_type" value="xpath" <?php echo 'xpath' === $post['comment_type'] ? 'checked="checked"': '' ?>/>
                                                    <label for="comment_type_xpath"><?php _e('Set manually', 'wp-all-import-pro' )?></label> <br>
                                                    <div class="switcher-target-comment_type_xpath">
                                                        <div class="input">
                                                            <input type="text" class="smaller-text" name="comment_type_xpath" value="<?php echo esc_attr($post['comment_type_xpath']) ?>" style="width: 50%;"/>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="width: 48%; padding-left: 1%;">
                                            <div class="input">
                                                <h4 style="margin-bottom:5px; margin-top: 0;"><?php _e('<b>Karma</b>', 'wp-all-import-pro');?></h4>
                                                <input name="comment_karma" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['comment_karma']) ?>"/>
                                            </div>
                                            <?php if ($post_type == 'comments'): ?>
                                            <div class="input">
                                                <h4 style="margin-bottom:5px; margin-top: 0;"><?php _e('<b>Parent Comment</b>', 'wp-all-import-pro');?><a href="#help" class="wpallimport-help" title="<?php _e('Comments can be matched to their parent comment by comment ID or the date and time (GMT) of the parent comment. To match by comment date it must be an exact match down to the second.', 'wp-all-import-pro'); ?>" style="top:-1px;">?</a></h4>
                                                <input name="comment_parent" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['comment_parent']) ?>"/>
                                            </div>
                                            <?php endif; ?>
	                                        <?php if ($post_type == 'woo_reviews'): ?>
                                                <div class="input">
                                                    <h4 style="margin-bottom:5px; margin-top: 0;"><?php _e('<b>Parent Review</b>', 'wp-all-import-pro');?><a href="#help" class="wpallimport-help" title="<?php _e('Reviews can be matched to their parent review by review ID or the date and time (GMT) of the parent review. To match by review date it must be an exact match down to the second.', 'wp-all-import-pro'); ?>" style="top:-1px;">?</a></h4>
                                                    <input name="comment_parent" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['comment_parent']) ?>"/>
                                                </div>
	                                        <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
		</div>
	</div>
</div>
<div class="wpallimport-collapsed closed">
    <div class="wpallimport-content-section" style="overflow: hidden; padding-bottom: 0;">
        <div class="wpallimport-collapsed-header" style="margin-bottom: 15px;">
            <h3><?php printf(__('%s Author','wp-all-import-pro'), $custom_type->labels->singular_name);?></h3>
        </div>
        <div class="wpallimport-collapsed-content" style="padding: 0;">
            <div class="wpallimport-collapsed-content-inner wpallimport-user-data">
                <div class="form-table comments-import-fields">
                    <table class="form-table">
                        <tr>
                            <td style="width: 48%; padding-right: 1%;">
                                <div class="input">
                                    <h4 style="margin-bottom:5px; margin-top: 0;"><?php _e('<b>Author Name</b>', 'wp-all-import-pro');?></h4>
                                    <input name="comment_author" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['comment_author']) ?>"/>
                                </div>
                            </td>
                            <td style="width: 48%; padding-left: 1%;">
                                <div class="input">
                                    <h4 style="margin-bottom:5px; margin-top: 0;"><?php _e('<b>Author Email</b>', 'wp-all-import-pro');?></h4>
                                    <input name="comment_author_email" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['comment_author_email']) ?>"/>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="wpallimport-collapsed closed">
                <div class="wpallimport-content-section rad0" style="margin:0; border-top:1px solid #ddd; border-bottom: none; border-right: none; border-left: none; background: #f1f2f2;">
                    <div class="wpallimport-collapsed-header">
                        <h3 style="color:#40acad;"><?php _e('Advanced Options','wp-all-import-pro');?></h3>
                    </div>
                    <div class="wpallimport-collapsed-content" style="padding: 0;">
                        <div class="wpallimport-collapsed-content-inner">
                            <div class="form-table comments-import-fields">
                                <table class="form-table">
                                    <tr>
                                        <td style="width: 48%; padding-right: 1%;">
                                            <div class="input">
                                                <h4 style="margin-bottom:5px; margin-top: 0;"><?php _e('<b>Author User ID</b>', 'wp-all-import-pro');?></h4>
                                                <div class="input">
                                                    <input type="radio" id="comment_user_id_email" name="comment_user_id" value="email" <?php echo 'email' === $post['comment_user_id'] ? 'checked="checked"' : '' ?> class="switcher"/>
                                                    <label for="comment_user_id_email"><?php _e('Try to auto-detect User ID from Author Email', 'wp-all-import-pro') ?></label>
                                                </div>
                                                <div class="input">
                                                    <input type="radio" id="comment_user_id_exclude" name="comment_user_id" value="exclude" <?php echo 'exclude' === $post['comment_user_id'] ? 'checked="checked"' : '' ?> class="switcher"/>
                                                    <label for="comment_user_id_exclude"><?php _e('Do not import User ID', 'wp-all-import-pro') ?></label>
                                                </div>
                                                <div class="input" style="position:relative;">
                                                    <input type="radio" id="comment_user_id_xpath" class="switcher" name="comment_user_id" value="xpath" <?php echo 'xpath' === $post['comment_user_id'] ? 'checked="checked"': '' ?>/>
                                                    <label for="comment_user_id_xpath"><?php _e('Set manually', 'wp-all-import-pro' )?></label> <br>
                                                    <div class="switcher-target-comment_user_id_xpath">
                                                        <div class="input">
                                                            <input type="text" class="smaller-text" name="comment_user_id_xpath" value="<?php echo esc_attr($post['comment_user_id_xpath']) ?>" style="width: 50%;"/>
                                                            <a href="#help" class="wpallimport-help" title="<?php _e('Expects a number that is the author\'s user ID in this WordPress install. Use 0 for guest comments.', 'wp-all-import-pro') ?>" style="position:relative; top:0;">?</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="width: 48%; padding-left: 1%;">
                                            <div class="input">
                                                <h4 style="margin-bottom:5px; margin-top: 0;"><?php _e('<b>Author URL</b>', 'wp-all-import-pro');?></h4>
                                                <input name="comment_author_url" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['comment_author_url']) ?>"/>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="width: 48%; padding-right: 1%;">
                                            <div class="input">
                                                <h4 style="margin-bottom:5px; margin-top: 0;"><?php _e('<b>Author IP</b>', 'wp-all-import-pro');?></h4>
                                                <input name="comment_author_IP" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['comment_author_IP']) ?>"/>
                                            </div>
                                        </td>
                                        <td style="width: 48%; padding-left: 1%;">
                                            <div class="input">
                                                <h4 style="margin-bottom:5px; margin-top: 0;"><?php _e('<b>Comment Agent</b>', 'wp-all-import-pro');?></h4>
                                                <input name="comment_agent" type="text" class="widefat rad4" style="width:100%;margin-bottom:5px;" value="<?php echo esc_attr($post['comment_agent']) ?>"/>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
