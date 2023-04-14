<?php

/**
 * No Discussions Feedback Part
 *
 * @package BuddyBoss\Theme
 */

?>

<ul id="bbp-forum-<?php bbp_forum_id(); ?>" class="bbp-topics1 bs-item-list bs-forums-items list-view">

	<li class="bs-item-wrap bs-header-item align-items-center no-hover-effect">
		<div class="flex-1">
			<h2 class="bs-section-title">
				<?php
				if ( bbp_is_topic_tag() ) {
					$bbp_topic_tag = get_query_var( 'bbp_topic_tag' );

					if ( function_exists( 'bbp_is_shortcode' ) && bbp_is_shortcode() && bbp_is_query_name( 'bbp_topic_tag' ) && ! empty( bbpress()->current_topic_tag_id ) ) {
						$bbp_tag_term = get_term( bbpress()->current_topic_tag_id );
						if ( ! empty( $bbp_tag_term->name ) ) {
							$bbp_topic_tag = $bbp_tag_term->name;
						}
					}

					echo sprintf(
					/* translators: Discussions tags. */
						wp_kses_post( __( "Discussions tagged with '%s' ", 'buddyboss-theme' ) ),
						wp_kses_post( $bbp_topic_tag )
					);
				} else {
					if ( function_exists( 'bbp_is_shortcode' ) && bbp_is_shortcode() && bbp_is_query_name( 'bbp_view' ) && 'popular' === bbpress()->current_view_id ) {
						esc_html_e( 'Popular Discussions', 'buddyboss-theme' );
					} elseif ( function_exists( 'bbp_is_shortcode' ) && bbp_is_shortcode() && bbp_is_query_name( 'bbp_view' ) && 'no-replies' === bbpress()->current_view_id ) {
						esc_html_e( 'Unanswered Discussions', 'buddyboss-theme' );
					} else {
						esc_html_e( 'All Discussions', 'buddyboss-theme' );
					}
				}
				?>
			</h2>
			<div class="bbp-forum-buttons-wrap">
				<?php
				if ( ( ! is_active_sidebar( 'forums' ) || bp_is_groups_component() ) && bbp_is_single_forum() && ! bbp_is_forum_category() && ( bbp_current_user_can_access_create_topic_form() || bbp_current_user_can_access_anonymous_user_form() ) ) {

					// Remove subscription link if forum assigned to the group.
					if ( ! function_exists( 'bb_is_forum_group_forum' ) || ! bb_is_forum_group_forum( bbp_get_forum_id() ) ) {
						bbp_forum_subscription_link();
					}
					?>
					<div class="bbp_before_forum_new_post">
						<a href="#new-post" data-modal-id="bbp-topic-form" class="button full btn-new-topic">
							<i class="bb-icon-l bb-icon-edit"></i>
							<?php esc_html_e( 'New discussion', 'buddyboss-theme' ); ?>
						</a>
					</div>
					<?php
				}

				if ( function_exists( 'bbp_forum_report_link' ) && function_exists( 'bp_is_active' ) && bp_is_active( 'moderation' ) && ! empty( $post->ID ) && bbp_get_forum_report_link( array( 'id' => $post->ID ) ) ) {
					?>

					<div class="bb_more_options action">
						<a href="#" class="bb_more_options_action">
							<i class="bb-icon-menu-dots-h"></i>
						</a>
						<div class="bb_more_options_list">
							<?php bbp_forum_report_link( array( 'id' => $post->ID ) ); ?>
						</div>
					</div><!-- .bb_more_options -->

					<?php
				}
				?>
			</div>
		</div>
	</li>
	<li class="bs-item-wrap">
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php esc_html_e( 'Sorry, there were no discussions found.', 'buddyboss-theme' ); ?></p>
		</div>
	</li>

</ul><!-- #bbp-forum-<?php bbp_forum_id(); ?> -->
