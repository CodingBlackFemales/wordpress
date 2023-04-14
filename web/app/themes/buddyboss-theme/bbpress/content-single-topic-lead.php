<?php

/**
 * Single Topic Lead Content Part
 *
 * @package BuddyBoss\Theme
 */

$topic_id   = bbp_get_topic_id();
$query_page = get_query_var( 'paged' );

do_action( 'bbp_template_before_lead_topic' );

$topic_reply_count = 0;
if ( bbp_show_lead_topic() ) {
	$topic_reply_count = (int) bbp_get_topic_reply_count( $topic_id );
} else {
	$topic_post_count = (int) bbp_get_topic_post_count( $topic_id );
}
?>

<ul id="topic-<?php echo esc_attr( bbp_get_topic_id() ); ?>-replies" class="bs-item-list bs-forums-items bs-single-forum-list bbp-lead-topic list-view <?php echo esc_attr( $topic_reply_count < 1 ? 'topic-list-no-replies' : '' ); ?>">
	<li class="bs-item-wrap bs-header-item align-items-center no-hover-effect topic-lead">
		<div class="item flex-1">
			<div class="item-title">
				<div class="title-wrap">
					<?php
					if ( ! empty( bbp_get_topic_forum_title() ) ) {
						$group_ids   = bbp_get_forum_group_ids( bbp_get_topic_forum_id() );
						$group_id    = ( ! empty( $group_ids ) ? current( $group_ids ) : 0 );
						$forum_title = ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) && $group_id ) ? bp_get_group_name( groups_get_group( $group_id ) ) : bbp_get_topic_forum_title();
						?>
						<div class="action bs-forums-meta flex align-items-center">
							<span class="color bs-meta-item forum-label">
								<a href="<?php bbp_forum_permalink( bbp_get_topic_forum_id() ); ?>"><?php echo esc_html( $forum_title ); ?></a>
							</span>
						</div>
						<?php
					}
					?>
					<h1 class="bb-reply-topic-title"><?php esc_html( bbp_reply_topic_title( bbp_get_reply_id() ) ); ?></h1>
				</div>

				<?php if ( bbp_show_lead_topic() && is_user_logged_in() ) : ?>
					<div class="bb-topic-states push-right flex">
						<?php
						/**
						 * Checked bbp_get_topic_stick_link() is empty or not.
						 */
						if ( ! bbp_is_topic_super_sticky( $topic_id ) && ! empty( bbp_get_topic_stick_link() ) ) {
							if ( bbp_is_topic_sticky() ) {
								?>
								<span data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Unstick', 'buddyboss-theme' ); ?>">
									<i class="bb-icon-l bb-icon-thumbtack bb-topic-status bb-sticky sticky"><?php bbp_topic_stick_link(); ?></i>
								</span>
								<?php
							} else {
								?>
								<span data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Sticky', 'buddyboss-theme' ); ?>">
									<i class="bb-icon-l bb-icon-thumbtack bb-topic-status bb-sticky unsticky"><?php bbp_topic_stick_link(); ?></i>
								</span>
								<?php
							}
						}
						/**
						 * Checked bbp_get_topic_stick_link() is empty or not.
						 */
						if ( ! empty( bbp_get_topic_stick_link() ) ) {
							if ( bbp_is_topic_super_sticky( $topic_id ) ) {
								?>
								<span data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Unstick', 'buddyboss-theme' ); ?>">
									<i class="bb-icon-l bb-icon-thumbtack-star bb-topic-status bb-super-sticky super-sticky"><?php bbp_topic_stick_link(); ?></i>
								</span>
								<?php
							} elseif ( ( ! bp_is_group() && ! bp_is_group_forum_topic() ) && ! bbp_is_topic_sticky() ) {
								?>
								<span data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Super Sticky', 'buddyboss-theme' ); ?>">
									<i class="bb-icon-l bb-icon-thumbtack-star bb-topic-status bb-super-sticky super-sticky unsticky"><?php bbp_topic_stick_link(); ?></i>
								</span>
								<?php
							}
						}

						/**
						 * Checked bbp_get_topic_close_link() is empty or not.
						 */
						if ( ! empty( bbp_get_topic_close_link() ) ) {
							if ( bbp_is_topic_open() ) {
								?>
								<span data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Close', 'buddyboss-theme' ); ?>">
									<i class="bb-icon-l bb-icon-lock-alt bb-topic-status open"><?php bbp_topic_close_link(); ?></i>
								</span>
							<?php } else { ?>
								<span data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Open', 'buddyboss-theme' ); ?>">
									<i class="bb-icon-l bb-icon-lock-alt-open bb-topic-status closed"><?php bbp_topic_close_link(); ?></i>
								</span>
								<?php
							}
						}

						/**
						 * Checked bbp_get_reply_admin_links() is empty or not if links not return then menu dropdown will not show.
						 */
						if ( is_user_logged_in() ) {
							?>
							<div class="bbp-meta push-right">
								<div class="more-actions bb-reply-actions bs-dropdown-wrap align-self-center">
									<?php
									$empty       = true;
									$topic_links = '';

									$args        = array(
										'sep'    => '',
										'before' => '',
										'after'  => '',
										'links'  => array(
											'edit'  => bbp_get_topic_edit_link( array( 'id' => bbp_get_topic_id() ) ),
											'close' => bbp_get_topic_close_link( array( 'id' => bbp_get_topic_id() ) ),
											'stick' => bbp_get_topic_stick_link( array( 'id' => bbp_get_topic_id() ) ),
											'merge' => bbp_get_topic_merge_link( array( 'id' => bbp_get_topic_id() ) ),
											'trash' => bbp_get_topic_trash_link( array( 'id' => bbp_get_topic_id() ) ),
											'spam'  => bbp_get_topic_spam_link( array( 'id' => bbp_get_topic_id() ) ),
										),
									);

									if ( bp_is_active( 'moderation' ) && function_exists( 'bbp_get_topic_report_link' ) ) {
										$report_link             = bbp_get_topic_report_link( array( 'id' => bbp_get_topic_id() ) );
										$args['links']['report'] = str_replace( 'button', '', $report_link );
									}

									$topic_links = bbp_get_topic_admin_links( $args );
									if ( ! empty( wp_strip_all_tags( $topic_links ) ) ) {
										unset( $args['before'] );
										unset( $args['after'] );
										$topic_links = bbp_get_topic_admin_links( $args );
										$empty       = false;
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
										if ( ! $empty ) {
											?>
											<a href="#" class="bs-dropdown-link bb-reply-actions-button" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'More actions', 'buddyboss-theme' ); ?>">
												<i class="bb-icon-menu-dots-v"></i>
											</a>
											<ul class="bs-dropdown bb-reply-actions-dropdown">
												<li>
													<?php
													do_action( 'bbp_theme_before_reply_admin_links' );
													echo $topic_links;
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
				<?php endif; ?>
			</div>

			<?php if ( 1 === $query_page || empty( $query_page ) ) { ?>
				<div class="item-meta">
					<span class="bs-replied">
						<?php esc_html_e( 'Posted by', 'buddyboss-theme' ); ?>
						<span class="bbp-topic-freshness-author">
							<?php
							bbp_author_link(
								array(
									'post_id' => $topic_id,
									'type'    => 'name',
								)
							);
							?>
						</span> <?php esc_html_e( 'on', 'buddyboss-theme' ); ?> <?php bbp_topic_post_date( $topic_id ); ?>
					</span>
				</div>

				<div class="item-description">
					<?php bbp_topic_content(); ?>
				</div>

				<?php
			}
			?>

			<div class="item-meta">
				<span class="bs-replied <?php echo esc_attr( $topic_reply_count < 1 ? 'bp-hide' : '' ); ?>">
					<span class="bbp-topic-freshness-author">
					<?php
					bbp_author_link(
						array(
							'post_id' => bbp_get_topic_last_active_id(),
							'type'    => 'name',
						)
					);
					?>
					</span> <?php esc_html_e( 'replied', 'buddyboss-theme' ); ?> <?php bbp_topic_freshness_link(); ?>
				</span>
				<span class="bs-voices-wrap">
					<?php
					$voice_count      = bbp_get_topic_voice_count( $topic_id );
					$voice_text       = $voice_count > 1 ? esc_html__( 'Members', 'buddyboss-theme' ) : esc_html__( 'Member', 'buddyboss-theme' );
					$reply_count      = bbp_get_topic_replies_link( $topic_id );
					$topic_reply_text = '';
					?>
					<span class="bs-voices"><?php bbp_topic_voice_count(); ?> <?php echo wp_kses_post( $voice_text ); ?></span>
					<span class="bs-separator">&middot;</span>
					<span class="bs-replies">
						<?php
						if ( bbp_show_lead_topic() ) {
							bbp_topic_reply_count( $topic_id );
							$topic_reply_text = 1 !== $topic_reply_count ? esc_html__( 'Replies', 'buddyboss-theme' ) : esc_html__( 'Reply', 'buddyboss-theme' );
						} else {
							bbp_topic_post_count( $topic_id );
							$topic_reply_text = 1 !== $topic_post_count ? esc_html__( 'Posts', 'buddyboss-theme' ) : esc_html__( 'Post', 'buddyboss-theme' );
						}
						echo ' ' . wp_kses_post( $topic_reply_text );
						?>
					</span>
				</span>
			</div>

			<?php
			$terms = bbp_get_form_topic_tags();
			if ( $terms && bbp_allow_topic_tags() ) {
				$tags_arr = explode( ', ', $terms );
				$html     = '';
				if ( ! empty( $tags_arr ) ) {
					foreach ( $tags_arr as $topic_tag ) {
						$html .= '<li><a href="' . esc_url( bbp_get_topic_tag_link( $topic_tag ) ) . '">' . esc_html( $topic_tag ) . '</a></li>';
					}
				}
				?>
				<div class="item-tags">
					<i class="bb-icon-tag"></i>
					<ul>
						<?php echo wp_kses_post( rtrim( $html, ',' ) ); ?>
					</ul>
				</div>
				<?php
			} else {
				?>
				<div class="item-tags" style="display: none;">
					<i class="bb-icon-tag"></i>
				</div>
				<?php
			}
			remove_filter( 'bbp_get_reply_content', 'bbp_reply_content_append_revisions', 99, 2 );
			?>
			<input type="hidden" name="bbp_topic_excerpt" id="bbp_topic_excerpt" value="<?php bbp_reply_excerpt( $topic_id, 50 ); ?>"/>
			<?php
			add_filter( 'bbp_get_reply_content', 'bbp_reply_content_append_revisions', 99, 2 );
			?>
		</div>
	</li><!-- .bbp-header -->

	<?php
	if ( ( 1 === $query_page || empty( $query_page ) ) ) {
		?>
		<li class="bs-item-wrap bs-header-item align-items-center header-total-reply-count <?php echo esc_attr( $topic_reply_count < 1 ? 'bp-hide' : '' ); ?>">
			<div class="topic-reply-count">
				<?php
				if ( bbp_show_lead_topic() ) {
					echo $topic_reply_count;
					$topic_reply_text = 1 !== $topic_reply_count ? esc_html__( 'Replies', 'buddyboss-theme' ) : esc_html__( 'Reply', 'buddyboss-theme' );
				} else {
					echo $topic_post_count;
					$topic_reply_text = 1 !== $topic_post_count ? esc_html__( 'Posts', 'buddyboss-theme' ) : esc_html__( 'Post', 'buddyboss-theme' );
				}
				echo ' ' . wp_kses_post( $topic_reply_text );
				?>
			</div>
		</li>
		<?php
	}
	?>
</ul>
<?php do_action( 'bbp_template_after_lead_topic' ); ?>
