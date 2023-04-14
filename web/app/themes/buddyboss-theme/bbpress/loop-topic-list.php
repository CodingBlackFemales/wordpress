<li>
	<?php $class = bbp_is_topic_open() ? '' : 'closed'; ?>
	<div class="bs-item-wrap <?php echo esc_attr( $class ); ?>">
		<div class="flex flex-1">
			<div class="item-avatar bb-item-avatar-wrap">
				<?php bbp_topic_author_link( array( 'size' => '180' ) ); ?>

				<?php if ( ! bbp_is_topic_open() ) { ?>
					<span class="bb-topic-status-wrapper" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Closed', 'buddyboss-theme' ); ?>"><i class="bb-icon-rl bb-icon-lock-alt-open bb-topic-status closed"></i></span>
					<?php
				}

				if ( bbp_is_topic_super_sticky() ) {
					?>
					<span class="bb-topic-status-wrapper" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Super Sticky', 'buddyboss-theme' ); ?>"><i class="bb-icon-rl bb-icon-thumbtack-star bb-topic-status super-sticky"></i></span>
				<?php } elseif ( bbp_is_topic_sticky() ) { ?>
					<span class="bb-topic-status-wrapper" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Sticky', 'buddyboss-theme' ); ?>"><i class="bb-icon-rl bb-icon-thumbtack bb-topic-status sticky"></i></span>
					<?php
				}

				if ( is_user_logged_in() ) {
					$is_subscribed = bbp_is_user_subscribed_to_topic( get_current_user_id(), bbp_get_topic_id() );
					if ( $is_subscribed ) {
						?>
						<span class="bb-topic-status-wrapper" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Subscribed', 'buddyboss-theme' ); ?>"><i class="bb-icon-rl bb-icon-rss bb-topic-status subscribed"></i></span>
						<?php
					}
				}
				?>
			</div>

			<div class="item">
				<div class="item-title">
					<a class="bbp-topic-permalink" href="<?php bbp_topic_permalink(); ?>"><?php bbp_topic_title(); ?></a>
				</div>

				<div class="item-meta bb-reply-meta">
					<i class="bb-icon-f bb-icon-reply"></i>
					<div>
						<span class="bs-replied">
							<span class="bbp-topic-freshness-author">
							<?php
							bbp_author_link(
								array(
									'post_id' => bbp_get_topic_last_active_id(),
									'size'    => 1,
								)
							);
							?>
							</span> <?php esc_html_e( 'replied', 'buddyboss-theme' ); ?> <?php bbp_topic_freshness_link(); ?>
						</span>
						<span class="bs-voices-wrap">
							<?php
								$voice_count = bbp_get_topic_voice_count( bbp_get_topic_id() );
								$voice_text  = $voice_count > 1 ? __( 'Members', 'buddyboss-theme' ) : __( 'Member', 'buddyboss-theme' );

								$topic_reply_count = bbp_get_topic_reply_count( bbp_get_topic_id() );
								$topic_post_count  = bbp_get_topic_post_count( bbp_get_topic_id() );
								$topic_reply_text  = '';
							?>
							<span class="bs-voices"><?php bbp_topic_voice_count(); ?> <?php echo wp_kses_post( $voice_text ); ?></span>
							<span class="bs-separator">&middot;</span>
							<span class="bs-replies">
							<?php
							bbp_topic_reply_count();
							$topic_reply_text = 1 !== (int) $topic_reply_count ? esc_html__( ' Replies', 'buddyboss-theme' ) : esc_html__( ' Reply', 'buddyboss-theme' );
							echo esc_html( $topic_reply_text );
							?>
							</span>
						</span>
					</div>
				</div>
			</div>
		</div>

		<?php
		if ( ! empty( bbp_get_topic_forum_title() ) ) {

			$group_ids   = bbp_get_forum_group_ids( bbp_get_topic_forum_id() );
			$group_id    = ( ! empty( $group_ids ) ? current( $group_ids ) : 0 );
			$topic_title = ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) && $group_id ) ? bp_get_group_name( groups_get_group( $group_id ) ) : bbp_get_topic_forum_title();

			?>
			<div class="action bs-forums-meta flex align-items-center">
				<span class="color bs-meta-item forum-label <?php echo bbp_is_single_forum() ? esc_attr( 'no-action' ) : ''; ?>" style="background: <?php echo esc_attr( color2rgba( textToColor( bbp_get_topic_forum_title() ), 0.6 ) ); ?>">
					<?php
					if ( bbp_is_single_forum() ) {
						?>
						<span class="no-links forum-label__is-single"><?php echo esc_html( $topic_title ); ?></span>
						<?php
					} else {
						?>
						<a href="<?php echo esc_url( bbp_get_forum_permalink( bbp_get_topic_forum_id() ) ); ?>"><?php echo esc_html( $topic_title ); ?></a>
						<?php
					}
					?>
				</span>
			</div>
		<?php } ?>
	</div>
</li>
