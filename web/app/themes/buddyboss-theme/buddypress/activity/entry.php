<?php
/**
 * BuddyBoss - Activity Feed (Single Item)
 *
 * This template is used by activity-loop.php and AJAX functions to show
 * each activity.
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 * @package BuddyBoss_Theme
 */

bp_nouveau_activity_hook( 'before', 'entry' );

$activity_id = bp_get_activity_id();
if ( function_exists( 'bb_activity_get_metadata' ) ) {
	$activity_metas    = bb_activity_get_metadata( $activity_id );
	$link_preview_data = ! empty( $activity_metas['_link_preview_data'][0] ) ? maybe_unserialize( $activity_metas['_link_preview_data'][0] ) : array();
	$link_embed        = $activity_metas['_link_embed'][0] ?? '';
} else {
	$link_preview_data = bp_activity_get_meta( $activity_id, '_link_preview_data', true );
	$link_embed        = bp_activity_get_meta( $activity_id, '_link_embed', true );
}

$link_preview_string = '';
$link_url            = '';

if ( ! empty( $link_preview_data ) && count( $link_preview_data ) ) {
	$link_preview_string = wp_json_encode( $link_preview_data );
	$link_url            = ! empty( $link_preview_data['url'] ) ? $link_preview_data['url'] : '';
}

if ( ! empty( $link_embed ) ) {
	$link_url = $link_embed;
}

// translators: %s: User display name.
$activity_popup_title = sprintf( esc_html__( "%s's Post", 'buddyboss-theme' ), bp_core_get_user_displayname( bp_get_activity_user_id() ) );
?>

<li
	class="<?php bp_activity_css_class(); ?>"
	id="activity-<?php echo esc_attr( $activity_id ); ?>"
	data-bp-activity-id="<?php echo esc_attr( $activity_id ); ?>"
	data-bp-timestamp="<?php bp_nouveau_activity_timestamp(); ?>"
	<?php if ( function_exists( 'bb_nouveau_activity_updated_timestamp' ) ) { ?>
		data-bb-updated-timestamp="<?php bb_nouveau_activity_updated_timestamp(); ?>"
	<?php } ?>
	data-bp-activity="<?php ( function_exists( 'bp_nouveau_edit_activity_data' ) ) ? bp_nouveau_edit_activity_data() : ''; ?>"
	data-link-preview='<?php echo esc_html( $link_preview_string ); ?>'
	data-link-url='<?php echo esc_url( $link_url ); ?>' data-activity-popup-title='<?php echo esc_attr( $activity_popup_title ); ?>'>

	<?php
	if ( function_exists( 'bb_nouveau_activity_entry_bubble_buttons' ) ) {
		bb_nouveau_activity_entry_bubble_buttons();
	}
	?>

	<div class="bb-pin-action">
		<span class="bb-pin-action_button" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Pinned Post', 'buddyboss-theme' ); ?>">
			<i class="bb-icon-f bb-icon-thumbtack"></i>
		</span>
		<?php
		$notification_type = function_exists( 'bb_activity_enabled_notification' ) ? bb_activity_enabled_notification( 'bb_activity_comment', bp_loggedin_user_id() ) : array();
		if ( ! empty( $notification_type ) && ! empty( array_filter( $notification_type ) ) ) {
			?>
			<span class="bb-mute-action_button" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Turned off notifications', 'buddyboss-theme' ); ?>">
				<i class="bb-icon-f bb-icon-bell-slash"></i>
			</span>
			<?php
		}
		?>
	</div>

	<?php
	if (
		function_exists( 'bb_pro_activity_post_feature_image_instance' ) &&
		bb_pro_activity_post_feature_image_instance() &&
		method_exists( bb_pro_activity_post_feature_image_instance(), 'bb_get_feature_image_data' )
	) {
		?>
		<div class="activity-feature-image">
			<?php
			$feature_image_data = bb_pro_activity_post_feature_image_instance()->bb_get_feature_image_data( $activity_id );
			if ( ! empty( $feature_image_data ) ) {
				?>
				<img class="activity-feature-image-media" src="<?php echo esc_url( $feature_image_data['url'] ); ?>" alt="<?php echo esc_attr( $feature_image_data['title'] ); ?>" />
				<?php
			}
			?>
		</div>
		<?php
	}
	?>

	<div class="bp-activity-head">

		<?php
		global $activities_template;

		$user_link       = bp_get_activity_user_link();
		$user_id         = bp_get_activity_user_id();
		$hp_profile_attr = ! empty( $user_id ) ? 'data-bb-hp-profile="' . esc_attr( $user_id ) . '"' : '';

		if ( bp_is_active( 'groups' ) && ! bp_is_group() && buddypress()->groups->id === bp_get_activity_object_name() ) :

			// If group activity.
			$group_id        = (int) $activities_template->activity->item_id;
			$group           = groups_get_group( $group_id );
			$group_name      = bp_get_group_name( $group );
			$group_name      = ! empty( $group_name ) ? esc_html( $group_name ) : '';
			$group_permalink = bp_get_group_permalink( $group );
			$activity_link   = bp_activity_get_permalink( $activities_template->activity->id, $activities_template->activity );
			$hp_group_attr   = ! empty( $group_id ) ? 'data-bb-hp-group="' . esc_attr( $group_id ) . '"' : '';
			?>
			<div class="bp-activity-head-group">
				<div class="activity-group-avatar">
					<div class="group-avatar">
						<a class="group-avatar-wrap mobile-center" href="<?php echo esc_url( $group_permalink ); ?>" <?php echo wp_kses_post( $hp_group_attr ); ?>>
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bp_core_fetch_avatar() returns HTML-escaped output
							echo bp_core_fetch_avatar(
								array(
									'item_id'    => $group->id,
									'avatar_dir' => 'group-avatars',
									'type'       => 'thumb',
									'object'     => 'group',
									'width'      => 100,
									'height'     => 100,
									'class'      => 'avatar bb-hp-group-avatar',
								)
							);
							?>
						</a>
					</div>
					<div class="author-avatar">
						<a href="<?php echo esc_url( $user_link ); ?>" <?php echo wp_kses_post( $hp_profile_attr ); ?>>
							<?php
							bp_activity_avatar(
								array(
									'type'  => 'thumb',
									'class' => 'avatar bb-hp-profile-avatar',
								)
							);
							?>
						</a>
					</div>
				</div>

				<div class="activity-header activity-header--group">
					<div class="activity-group-heading">
						<a href="<?php echo esc_url( $group_permalink ); ?>" <?php echo wp_kses_post( $hp_group_attr ); ?>>
							<?php echo esc_html( $group_name ); ?>
						</a>
					</div>
					<div class="activity-group-post-meta">
						<span class="activity-post-author">
							<?php
							$activity_type   = bp_get_activity_type();
							$activity_object = bp_get_activity_object_name();

							if (
								'groups' === $activity_object &&
								'activity_update' === $activity_type
							) {
								// Show only user link and display name.
								?>
								<a href="<?php echo esc_url( $user_link ); ?>" <?php echo wp_kses_post( $hp_profile_attr ); ?>>
									<?php echo esc_html( bp_core_get_user_displayname( $activities_template->activity->user_id ) ); ?>
								</a>
								<?php
							} else {
								// Show the default activity action.
								bp_activity_action();
							}
							?>
						</span>
						<a href="<?php echo esc_url( $activity_link ); ?>">
							<?php
							$activity_date_recorded = bp_get_activity_date_recorded();
							printf(
								'<span class="time-since" data-livestamp="%1$s">%2$s</span>',
								esc_attr( bp_core_get_iso8601_date( $activity_date_recorded ) ),
								esc_html( bp_core_time_since( $activity_date_recorded ) )
							);
							?>
						</a>
						<?php
						if ( function_exists( 'bp_nouveau_activity_is_edited' ) ) {
							bp_nouveau_activity_is_edited();
						}
						if ( function_exists( 'bp_nouveau_activity_privacy' ) ) {
							bp_nouveau_activity_privacy();
						}
						if (
							function_exists( 'bb_is_enabled_group_activity_topics' ) &&
							bb_is_enabled_group_activity_topics()
						) {
							?>
							<p class="activity-topic">
								<?php
								if (
									function_exists( 'bb_activity_topics_manager_instance' ) &&
									method_exists( bb_activity_topics_manager_instance(), 'bb_get_activity_topic_url' )
								) {
									echo wp_kses_post(
										bb_activity_topics_manager_instance()->bb_get_activity_topic_url(
											array(
												'activity_id' => bp_get_activity_id(),
												'html'        => true,
											)
										)
									);
								}
								?>
							</p>
							<?php
						}
						?>
					</div>
				</div>
			</div>

		<?php else : ?>

			<div class="activity-avatar item-avatar">
				<a href="<?php echo esc_url( $user_link ); ?>" <?php echo wp_kses_post( $hp_profile_attr ); ?>>
					<?php
					bp_activity_avatar(
						array(
							'type'  => 'full',
							'class' => 'avatar bb-hp-profile-avatar',
						)
					);
					?>
				</a>
			</div>

			<div class="activity-header">
				<?php bp_activity_action(); ?>
				<p class="activity-date">
					<a href="<?php echo esc_url( bp_activity_get_permalink( $activity_id ) ); ?>">
						<?php
						$activity_date_recorded = bp_get_activity_date_recorded();
						printf(
							'<span class="time-since" data-livestamp="%1$s">%2$s</span>',
							esc_attr( bp_core_get_iso8601_date( $activity_date_recorded ) ),
							esc_html( bp_core_time_since( $activity_date_recorded ) )
						);
						?>
					</a>
					<?php
					if ( function_exists( 'bp_nouveau_activity_is_edited' ) ) {
						bp_nouveau_activity_is_edited();
					}
					?>
				</p>
				<?php
				if ( function_exists( 'bp_nouveau_activity_privacy' ) ) {
					bp_nouveau_activity_privacy();
				}
				if (
					(
						'groups' === $activities_template->activity->component &&
						function_exists( 'bb_is_enabled_group_activity_topics' ) &&
						bb_is_enabled_group_activity_topics()
					) ||
					(
						'groups' !== $activities_template->activity->component &&
						function_exists( 'bb_is_enabled_activity_topics' ) &&
						bb_is_enabled_activity_topics()
					)
				) {
					?>
					<p class="activity-topic">
						<?php
						if (
							function_exists( 'bb_activity_topics_manager_instance' ) &&
							method_exists( bb_activity_topics_manager_instance(), 'bb_get_activity_topic_url' )
						) {
							echo wp_kses_post(
								bb_activity_topics_manager_instance()->bb_get_activity_topic_url(
									array(
										'activity_id' => bp_get_activity_id(),
										'html'        => true,
									)
								)
							);
						}
						?>
					</p>
					<?php
				}
				?>
			</div>

		<?php endif; ?>
	</div>

	<?php bp_nouveau_activity_hook( 'before', 'activity_content' ); ?>

	<?php
	if (
		function_exists( 'bb_activity_has_post_title' ) &&
		bb_activity_has_post_title() &&
		function_exists( 'bb_activity_post_title' )
	) {
		?>
		<div class="activity-title">
			<h2><?php bb_activity_post_title(); ?></h2>
		</div>
		<?php
	}
	?>

	<div class="activity-content <?php ( function_exists( 'bp_activity_entry_css_class' ) ) ? bp_activity_entry_css_class() : ''; ?>">
		<?php
		if ( bp_nouveau_activity_has_content() ) :
			?>
			<div class="activity-inner <?php echo ( function_exists( 'bp_activity_has_content' ) && empty( bp_activity_has_content() ) ) ? esc_attr( 'bb-empty-content' ) : esc_attr( '' ); ?>">
				<?php
				bp_nouveau_activity_content();

				if ( function_exists( 'bb_nouveau_activity_inner_buttons' ) ) {
					bb_nouveau_activity_inner_buttons();
				}
				?>
			</div>
			<?php
		endif;

		if ( function_exists( 'bp_nouveau_activity_state' ) ) {
			bp_nouveau_activity_state();
		}
		?>
	</div>

	<?php

	bp_nouveau_activity_hook( 'after', 'activity_content' );
	if ( function_exists( 'bb_activity_load_progress_bar_state' ) ) {
		bb_activity_load_progress_bar_state();
	}
	bp_nouveau_activity_entry_buttons();
	bp_nouveau_activity_hook( 'before', 'entry_comments' );

	$closed_notice = function_exists( 'bb_get_close_activity_comments_notice' ) ? bb_get_close_activity_comments_notice( $activity_id ) : '';
	if ( ! empty( $closed_notice ) ) {
		?>
		<div class='bb-activity-closed-comments-notice'><?php echo esc_html( $closed_notice ); ?></div>
		<?php
	}

	if ( bp_activity_can_comment() ) {

		$class = 'activity-comments';
		if ( 'blogs' === bp_get_activity_object_name() ) {
			$class .= get_option( 'thread_comments' ) ? ' threaded-comments threaded-level-' . get_option( 'thread_comments_depth' ) : '';
		} else {
			$class .= function_exists( 'bb_is_activity_comment_threading_enabled' ) && bb_is_activity_comment_threading_enabled() ? ' threaded-comments threaded-level-' . bb_get_activity_comment_threading_depth() : '';
		}
		?>

		<div class="<?php echo esc_attr( $class ); ?>">
			<?php
			if ( bp_activity_get_comment_count() ) {
				bp_activity_comments();
			}

			if ( is_user_logged_in() ) {
				bp_nouveau_activity_comment_form();
			}
			?>

		</div>
		<?php
	}

	bp_nouveau_activity_hook( 'after', 'entry_comments' );
	?>
</li>

<?php
bp_nouveau_activity_hook( 'after', 'entry' );
