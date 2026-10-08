<?php
/**
 * Displays course information for a user
 *
 * Available:
 * $user_id
 * $courses_registered: course
 * $course_progress: Progress in courses
 * $quizzes
 *
 * @since 2.1.0
 *
 * @package LearnDash\Templates\Legacy\Shortcodes
 */

/**
 * Course registered
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $pagenow;

$shortcode_atts_json = htmlspecialchars( wp_json_encode( $shortcode_atts ) );
?>
<div id="ld_course_info" class="ld_course_info" data-shortcode-atts="<?php echo $shortcode_atts_json; ?>">

	<!-- Course info shortcode -->
	<?php if ( ( 'profile.php' !== $pagenow ) && ( 'user-edit.php' !== $pagenow ) ) { ?>
		<?php if ( $courses_registered ) : ?>
			<div id='ld_course_info_mycourses_list' class="ld_course_info_mycourses_list">
				<h4>
				<?php
				// translators: placeholder: courses.
				echo sprintf( esc_html_x( 'You are registered for the following %s', 'placeholder: courses', 'learndash' ), esc_html( learndash_get_custom_label_lower( 'courses' ) ) );
				?>
				</h4>
				<div class="ld-courseregistered-content-container">
				<?php

				$template_file = SFWD_LMS::get_template(
					'course_registered_rows',
					array(
						'courses_registered' => $courses_registered,
						'shortcode_atts'     => $shortcode_atts,
					),
					null,
					true
				);

				if ( ! empty( $template_file ) ) {
					include $template_file;
				}

				?>
				</div>
				<div class="ld-course-registered-pager-container">
				<?php
					echo SFWD_LMS::get_template(
						'learndash_pager.php',
						array(
							'pager_results' => $courses_registered_pager,
							'pager_context' => 'course_info_courses',
						)
					);
				?>
				</div>
			</div>
			<div style="clear:both"></div>

		<?php endif; ?>
	<?php } ?>

	<?php
	if ( is_admin() ) {
		if ( function_exists( 'get_current_screen' ) ) {
			$current_screen = get_current_screen();

			if ( ( 'profile.php' === $pagenow ) || ( 'user-edit.php' === $pagenow ) || ( 'learndash-lms_page_group_admin_page' === $current_screen->id ) ) {
				echo do_shortcode( '[ld_user_course_points user_id="' . $user_id . '" context="profile"]' );

				if ( ( learndash_is_admin_user() ) || ( ( learndash_is_group_leader_user() ) && ( learndash_is_group_leader_of_user( get_current_user_id(), $user_id ) ) ) ) {
					?>
						<p><label for="learndash-course-points-user"><strong>
						<?php
						// translators: placeholder: Course.
						printf( esc_html_x( 'Extra %s points', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
						?>
						</strong></label> <input id="learndash-course-points-user" name="learndash_course_points" type="number" min="0" step="any" value="<?php echo learndash_format_course_points( get_user_meta( $user_id, 'course_points', true ) ); ?>" /><?php } ?></p>
						<?php
			}
		}
	}
	?>
	<?php /* Course progress */ ?>
	<?php if ( ! empty( $course_progress ) ) : ?>
		<div id="course_progress_details" class="course_progress_details">
			<h4>
			<?php
			// translators: placeholder: Course.
			printf( esc_html_x( '%s progress details:', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
			?>
			</h4>
			<?php
			if ( learndash_show_user_course_complete( $user_id ) ) {
				?>
			<input type="hidden" id="user-progress-<?php echo esc_attr( $user_id ); ?>" name="user_progress[<?php echo (int) $user_id; ?>]" value="<?php echo htmlspecialchars( // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
				wp_json_encode(
					array(
						'course' => array(),
						'quiz'   => array(),
					),
					JSON_FORCE_OBJECT
				)
			); ?>" /> <?php // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect,Squiz.PHP.EmbeddedPhp.ContentBeforeEnd,PEAR.Functions.FunctionCallSignature.Indent,PEAR.Functions.FunctionCallSignature.CloseBracketLine ?>
			<input type="hidden" name="user_progress-<?php echo esc_attr( $user_id ); ?>-nonce" value="<?php echo esc_attr( wp_create_nonce( 'user_progress-' . $user_id ) ); ?>" />
				<?php
			}
			?>
				<div class="ld-course-progress-content-container">
				<?php
				$template_file = SFWD_LMS::get_template(
					'course_progress_rows',
					array(
						'user_id'         => $user_id,
						'course_progress' => $course_progress,
						'shortcode_atts'  => $shortcode_atts,
					),
					null,
					true
				);
				if ( ! empty( $template_file ) ) {
					include $template_file;
				}

				?>
				</div><div class="ld-course-progress-pager-container">
				<?php
				echo SFWD_LMS::get_template(
					'learndash_pager.php',
					array(
						'pager_results' => $course_progress_pager,
						'pager_context' => 'course_info_courses',
					)
				);
				?>
				</div><div style="clear:both"></div>		</div>
		<br>
	<?php endif; ?>

	<?php /* Quizzes */ ?>
	<?php if ( $quizzes ) : ?>
		<div id="quiz_progress_details" class="quiz_progress_details">
			<?php
				global $learndash_assets_loaded;

			if ( ! isset( $learndash_assets_loaded['scripts']['learndash_template_script_js'] ) ) {

				$filepath = SFWD_LMS::get_template( 'learndash_template_script.js', null, null, true );
				if ( ! empty( $filepath ) ) {
					wp_enqueue_script( 'learndash_template_script_js', learndash_template_url_from_path( $filepath ), array( 'jquery' ), LEARNDASH_SCRIPT_VERSION_TOKEN, true );
					$learndash_assets_loaded['scripts']['learndash_template_script_js'] = __FUNCTION__;

					$data            = array();
					$data['ajaxurl'] = admin_url( 'admin-ajax.php' );
					$data            = array( 'json' => wp_json_encode( $data ) );
					wp_localize_script( 'learndash_template_script_js', 'sfwd_data', $data );
				}
			}
				LD_QuizPro::showModalWindow();
			?>
			<h4>
			<?php
			// translators: placeholder: quizzes.
			echo sprintf( _x( 'You have taken the following %s:', 'placeholder: quizzes', 'learndash' ), learndash_get_custom_label_lower( 'quizzes' ) );
			?>
			</h4>

			<?php /* The confirm delete quiz message should not contain HTML. Use \r\n for line breaks */ ?>
			<div id="ld-confirm-quiz-delete-message" style="display:none">
			<?php
			// translators: placeholder: quiz.
			echo sprintf( _x( 'Are you sure that you want to remove this %s item?', 'placeholder: quiz', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) )
			?>
			</div>

			<div class="ld-quiz-progress-content-container">
			<?php
				$template_file = SFWD_LMS::get_template(
					'quiz_progress_rows',
					array(
						'user_id'        => $user_id,
						'quizzes'        => $quizzes,
						'shortcode_atts' => $shortcode_atts,
					),
					null,
					true
				);

			if ( ! empty( $template_file ) ) {
				include $template_file;
			}

			?>
			</div><div class="ld-quiz-progress-pager-container">
			<?php
			echo SFWD_LMS::get_template(
				'learndash_pager.php',
				array(
					'pager_results' => $quizzes_pager,
					'pager_context' => 'course_info_quizzes',
				)
			);
			?>
			</div><div style="clear:both"></div>		</div>
	<?php endif; ?>
	<!-- End Course info shortcode -->
</div>
