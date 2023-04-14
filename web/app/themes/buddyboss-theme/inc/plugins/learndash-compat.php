<?php

if ( ! function_exists( 'learndash_focus_mode_comments_list' ) ) {
	function learndash_focus_mode_comments_list( $comment, $args, $depth ) {
		global $wp_roles;
		global $post;

		$GLOBALS['comment'] = $comment;

		$user_data = get_userdata( $comment->user_id );
		$roles = $user_data->roles;
		$role_classes = '';
		if ( ! empty( $roles ) ) {
			foreach ( $roles as $role ):
				$role_classes .= 'role-' . $role;
				if ( $role === 'administrator' || $role === 'group_leader' ) {
					$role_name = translate_user_role( $wp_roles->roles[ $role ]['name'] );
				}

			endforeach;
		}

		$avatarClass = empty( get_avatar( $comment->comment_author_email ) ) ? ' ld-no-avatar-image' : '';

		?>

	<div <?php comment_class( 'ld-focus-comment ptype-' . $post->post_type . ' ' . $role_classes . $avatarClass ); ?> id="comment-<?php comment_ID(); ?>">
		<div class="ld-comment-wrapper">

			<?php if ( $comment->comment_approved == '0' ) : ?>
				<span class="ld-comment-alert"><?php esc_html_e( 'Your response is awaiting for approval.',
						'buddyboss-theme' ); ?></span>
			<?php endif; ?>

			<div class="ld-comment-avatar">
				<?php echo wp_kses_post( get_avatar( $comment->comment_author_email ) ); ?>
				<span class="ld-comment-avatar-author">
					<span class="ld-comment-author-name">
						<?php
						echo esc_html( $comment->comment_author );
						if ( ! empty( $role_name ) ) {
							echo ' (' . $role_name . ')';
						}
						?>
					</span>
					<a class="ld-comment-permalink" href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
					<?php
					printf( // translators: placeholders: %1$s: Comment Date, %2$s: Comment Time
						esc_html_x( '%1$s at %2$s', 'placeholders: comment date, comment time', 'buddyboss-theme' ),
						'<span> ' . get_comment_date() . '</span>',
						'<span> ' . get_comment_time() . '</span>' );
					?>

					</a>
				</span>
			</div>

			<div class="ld-comment-body">
				<?php comment_text(); ?>
				<div class="ld-comment-reply">
					<?php comment_reply_link( array_merge( $args,
						array(
							'reply_text' => esc_html__( 'Reply', 'buddyboss-theme' ),
							'after'      => '',
							'depth'      => $depth,
							'max_depth'  => $args['max_depth']
						) ) ); ?>
				</div>
			</div>
		</div>
		<?php
	}
}

/**
 * Fix reset password bug coming from LearnDash v3.1.1
 */
if ( function_exists( 'learndash_login_headerurl' ) ) {
	remove_action( 'login_headerurl', 'learndash_login_headerurl' );
}

/**
 * Remove filter from learndash_content to show sidebar in the lessons, quiz and topic when lesson will be available.
 */
if ( function_exists( 'lesson_visible_after' ) ) {
	remove_filter( 'learndash_content', 'lesson_visible_after', 1, 2 );

	if ( ! function_exists( 'buddyboss_lesson_visible_after' ) ) {

		/**
		 * Display when lesson will be available
		 * - from lesson_visible_after();
		 *
		 * @since LearnDash 2.1.0
		 *
		 * @param string $content content of lesson
		 * @param object $post    WP_Post object
		 *
		 * @return string          when lesson will be available
		 */
		function buddyboss_lesson_visible_after( $content, $post ) {
			if ( empty( $post->post_type ) ) {
				return $content;
			}

			$course_id = learndash_get_course_id( $post );
			if ( empty( $course_id ) ) {
				return $content;
			}

			if ( $post->post_type == 'sfwd-lessons' ) {
				$lesson_id = $post->ID;
			} else {
				if ( $post->post_type == 'sfwd-topic' || $post->post_type == 'sfwd-quiz' ) {
					$topic_id = $post->ID;
					if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
						$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
					} else {
						$lesson_id = learndash_get_setting( $post, 'lesson' );
					}
				} else {
					return $content;
				}
			}

			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
			} else {
				return $content;
			}

			if ( learndash_is_admin_user( $user_id ) ) {
				$bypass_course_limits_admin_users = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'bypass_course_limits_admin_users' );
				if ( $bypass_course_limits_admin_users == 'yes' ) {
					$bypass_course_limits_admin_users = true;
				} else {
					$bypass_course_limits_admin_users = false;
				}

			} else {
				$bypass_course_limits_admin_users = false;
			}

			// For logged in users to allow an override filter.
			$bypass_course_limits_admin_users = apply_filters( 'learndash_prerequities_bypass', $bypass_course_limits_admin_users, $user_id, $post->ID, $post );
			
			$lesson_access_from = learndash_course_step_available_date( $post->ID, $course_id, get_current_user_id(), true );
			if ( ( empty( $lesson_access_from ) ) || ( $bypass_course_limits_admin_users ) ) {
			    return $content;
			} else {

				$context = learndash_get_post_type_key( $post->post_type );

				if ( learndash_get_post_type_slug( 'lesson' ) === $post->post_type ) {
					$lesson_id = $post->ID;
				} else {
					$lesson_id = 0;
				}
		
				$content = SFWD_LMS::get_template(
					'learndash_course_lesson_not_available',
					array(
						'user_id'                 => get_current_user_id(),
						'course_id'               => learndash_get_course_id( $post->ID ),
						'step_id'                 => $post->ID,
						'lesson_id'               => $lesson_id,
						'lesson_access_from_int'  => $lesson_access_from,
						'lesson_access_from_date' => learndash_adjust_date_time_display( $lesson_access_from ),
						'context'                 => $context,
					),
					false
				);

				return $content;

			}

			return $content;
		}

		add_filter( 'buddyboss_learndash_content', 'buddyboss_lesson_visible_after', 10, 2 );
	}
}
