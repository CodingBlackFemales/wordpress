<?php

/**
 * Replies Loop - Single Reply
 *
 * @package    bbPress
 * @subpackage Theme
 */

?>

<div id="post-<?php bbp_reply_id(); ?>" <?php bbp_reply_class( bbp_get_reply_id(), array(
	'bs-reply-list-item',
	'scrubberpost'
) ); ?> data-date="<?php echo get_post_time( 'F Y', false, bbp_get_reply_id(), true ); ?>">

    <div class="flex align-items-center bs-reply-header">

        <div class="bbp-reply-author item-avatar">
			<?php $args = array( 'type' => 'avatar' );
			echo bbp_get_reply_author_link( $args ); ?>
        </div><!-- .bbp-reply-author -->

        <div class="item-meta flex-1">
            <h3><?php
				$args = array( 'type' => 'name' );
				echo bbp_get_reply_author_link( $args );
				?></h3>

			<?php bbp_reply_author_role(); ?>
            <span class="bs-timestamp"><?php bbp_reply_post_date(); ?></span>

			<?php if ( bbp_is_single_user_replies() ) : ?>

                <span class="bbp-header">
				<?php esc_html_e( 'in reply to: ', 'buddyboss-theme' ); ?>
					<a class="bbp-topic-permalink"
                       href="<?php bbp_topic_permalink( bbp_get_reply_topic_id() ); ?>"><?php bbp_topic_title( bbp_get_reply_topic_id() ); ?></a>
				</span>

			<?php endif; ?>

        </div>

		<?php
		/**
		 * Checked bbp_get_reply_admin_links() is empty or not if links not return then munu dropdown will not show
		 */
		if ( is_user_logged_in() && ! empty( strip_tags( bbp_get_reply_admin_links() ) ) ) { ?>
            <div class="bbp-meta push-right">
                <div class="more-actions bb-reply-actions bs-dropdown-wrap align-self-center">
					<?php
					$empty       = false;
					$topic_links = '';
					$reply_links = '';
					// If post is a topic, print the topic admin links instead.
					if ( bbp_is_topic( bbp_get_reply_id() ) ) {
						$args = array(
							'links' => array(
								'edit'   => bbp_get_topic_edit_link( array( 'id' => bbp_get_topic_id() ) ),
								'close'  => bbp_get_topic_close_link( array( 'id' => bbp_get_topic_id() ) ),
								'stick'  => bbp_get_topic_stick_link( array( 'id' => bbp_get_topic_id() ) ),
								'merge'  => bbp_get_topic_merge_link( array( 'id' => bbp_get_topic_id() ) ),
								'trash'  => bbp_get_topic_trash_link( array( 'id' => bbp_get_topic_id() ) ),
								'spam'   => bbp_get_topic_spam_link( array( 'id' => bbp_get_topic_id() ) ),
							)
						);

						$topic_links = bbp_get_topic_admin_links( $args );
						if ( '' === wp_strip_all_tags( $topic_links ) ) {
							$empty = true;
						}
						// If post is a reply, print the reply admin links instead.
					} else {
						$args = array(
							'links' => array(
								'edit'  => bbp_get_reply_edit_link( array( 'id' => bbp_get_reply_id() ) ),
								'move'  => bbp_get_reply_move_link( array( 'id' => bbp_get_reply_id() ) ),
								'split' => bbp_get_topic_split_link( array( 'id' => bbp_get_reply_id() ) ),
							),
						);

						if ( bp_is_active( 'moderation' ) && function_exists( 'bbp_get_reply_report_link' ) ) {
							$args['links']['report'] = bbp_get_reply_report_link( array( 'id' => bbp_get_reply_id() ) );
						}

						$args['links']['spam']  = bbp_get_reply_spam_link( array( 'id' => bbp_get_reply_id() ) );
						$args['links']['trash'] = bbp_get_reply_trash_link( array( 'id' => bbp_get_reply_id() ) );
						
						$reply_links = bbp_get_reply_admin_links( $args );
						if ( '' === wp_strip_all_tags( $reply_links ) ) {
							$empty = true;
						}
					}

					$parent_class = '';
					if ( $empty ) {
						$parent_class = 'bb-theme-no-actions';
					} else {
						$parent_class = 'bb-theme-actions';
					}
					?>
                    <div class="bs-dropdown-wrap-inner <?php echo esc_attr( $parent_class ); ?>">
						<?php
						// If post is a topic, print the topic admin links instead.
						if ( bbp_is_topic( bbp_get_reply_id() ) ) {
							add_filter( 'bbp_get_topic_reply_link', 'bb_theme_topic_link_attribute_change', 9999, 3 );
							echo bbp_get_topic_reply_link();
							remove_filter( 'bbp_get_topic_reply_link', 'bb_theme_topic_link_attribute_change', 9999, 3 );
							// If post is a reply, print the reply admin links instead.
						} else {
							add_filter( 'bbp_get_reply_to_link', 'bb_theme_reply_link_attribute_change', 9999, 3 );
							echo bbp_get_reply_to_link();
							remove_filter( 'bbp_get_reply_to_link', 'bb_theme_reply_link_attribute_change', 9999, 3 );
						}
						if ( ! $empty ) {
							?>
                            <a href="#" class="bs-dropdown-link bb-reply-actions-button" data-balloon-pos="up"
                               data-balloon="<?php esc_html_e( 'More actions', 'buddyboss-theme' ); ?>"><i
                                        class="bb-icon-f bb-icon-ellipsis-v"></i></a>
                            <ul class="bs-dropdown bb-reply-actions-dropdown">
                                <li>
									<?php
									do_action( 'bbp_theme_before_reply_admin_links' );
									echo $topic_links;
									echo $reply_links;
									do_action( 'bbp_theme_after_reply_admin_links' );
									?>
                                </li>
                            </ul>
							<?php
						}
						?>
                    </div>
                </div>
            </div><!-- .bbp-meta -->
		<?php } ?>

    </div>

    <div class="bbp-after-author-hook">
		<?php do_action( 'bbp_theme_after_reply_author_details' ); ?>
    </div>

    <div class="bbp-reply-content bs-forum-content">

		<?php do_action( 'bbp_theme_before_reply_content' ); ?>

		<?php bbp_reply_content(); ?>

		<?php do_action( 'bbp_theme_after_reply_content' ); ?>

    </div><!-- .bbp-reply-content -->

</div><!-- .reply -->
