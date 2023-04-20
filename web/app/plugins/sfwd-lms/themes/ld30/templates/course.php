<?php
/**
 * LearnDash LD30 Displays a course
 *
 * Available Variables:
 * $course_id                   : (int) ID of the course
 * $course                      : (object) Post object of the course
 * $course_settings             : (array) Settings specific to current course
 *
 * $courses_options             : Options/Settings as configured on Course Options page
 * $lessons_options             : Options/Settings as configured on Lessons Options page
 * $quizzes_options             : Options/Settings as configured on Quiz Options page
 *
 * $user_id                     : Current User ID
 * $logged_in                   : User is logged in
 * $current_user                : (object) Currently logged in user object
 *
 * $course_status               : Course Status
 * $has_access                  : User has access to course or is enrolled.
 * $materials                   : Course Materials
 * $has_course_content          : Course has course content
 * $lessons                     : Lessons Array
 * $quizzes                     : Quizzes Array
 * $lesson_progression_enabled  : (true/false)
 * $has_topics                  : (true/false)
 * $lesson_topics               : (array) lessons topics
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_lesson_quizzes = learndash_30_has_lesson_quizzes( $course_id, $lessons ); ?>

<div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">

	<?php
	global $course_pager_results;

	/**
	 * Fires before the topic.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id   Post ID.
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	do_action( 'learndash-course-before', get_the_ID(), $course_id, $user_id );

	/**
	 * Fires before the course certificate link.
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	do_action( 'learndash-course-certificate-link-before', $course_id, $user_id );

	/**
	 * Certificate link
	 */

	if ( ( defined( 'LEARNDASH_TEMPLATE_CONTENT_METHOD' ) ) && ( 'shortcode' === LEARNDASH_TEMPLATE_CONTENT_METHOD ) ) {
		$shown_content_key = 'learndash-shortcode-wrap-ld_certificate-' . absint( $course_id ) . '_' . absint( $user_id );
		if ( false === strstr( $content, $shown_content_key ) ) {
			$shortcode_out = do_shortcode( '[ld_certificate course_id="' . $course_id . '" user_id="' . $user_id . '" display_as="banner"]' );
			if ( ! empty( $shortcode_out ) ) {
				echo $shortcode_out;
			}
		}
	} else {
		if ( ! empty( $course_certficate_link ) ) :
			learndash_get_template_part(
				'modules/alert.php',
				array(
					'type'    => 'success ld-alert-certificate',
					'icon'    => 'certificate',
					'message' => __( 'You\'ve earned a certificate!', 'learndash' ),
					'button'  => array(
						'url'    => $course_certficate_link,
						'icon'   => 'download',
						'label'  => __( 'Download Certificate', 'learndash' ),
						'target' => '_new',
					),
				),
				true
			);
		endif;
	}

	/**
	 * Fires after the course certificate link.
	 *
	 * @since 3.0.0
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	do_action( 'learndash-course-certificate-link-after', $course_id, $user_id );

	if ( ( defined( 'LEARNDASH_TEMPLATE_CONTENT_METHOD' ) ) && ( 'shortcode' === LEARNDASH_TEMPLATE_CONTENT_METHOD ) ) {
		$shown_content_key = 'learndash-shortcode-wrap-ld_infobar-' . absint( $course_id ) . '_' . (int) get_the_ID() . '_' . absint( $user_id );
		if ( false === strstr( $content, $shown_content_key ) ) {
			$shortcode_out = do_shortcode( '[ld_infobar course_id="' . $course_id . '" user_id="' . $user_id . '" post_id="' . get_the_ID() . '"]' );
			if ( ! empty( $shortcode_out ) ) {
				echo $shortcode_out;
			}
		}
	} else {
		/**
		 * Course info bar
		 */
		learndash_get_template_part(
			'modules/infobar.php',
			array(
				'context'       => 'course',
				'course_id'     => $course_id,
				'user_id'       => $user_id,
				'has_access'    => $has_access,
				'course_status' => $course_status,
				'post'          => $post,
			),
			true
		);
	}
	?>

	<?php
	/** This filter is documented in themes/legacy/templates/course.php */
	echo apply_filters( 'ld_after_course_status_template_container', '', learndash_course_status_idx( $course_status ), $course_id, $user_id );

	/**
	 * Content tabs
	 */
	learndash_get_template_part(
		'modules/tabs.php',
		array(
			'course_id' => $course_id,
			'post_id'   => get_the_ID(),
			'user_id'   => $user_id,
			'content'   => $content,
			'materials' => $materials,
			'context'   => 'course',
		),
		true
	);

	/**
	 * Identify if we should show the course content listing
	 */
	$show_course_content = ( ! $has_access && 'on' === $course_meta['sfwd-courses_course_disable_content_table'] ? false : true );

	if ( $has_course_content && $show_course_content ) :

		if ( ( defined( 'LEARNDASH_TEMPLATE_CONTENT_METHOD' ) ) && ( 'shortcode' === LEARNDASH_TEMPLATE_CONTENT_METHOD ) ) {
			$shown_content_key = 'learndash-shortcode-wrap-course_content-' . absint( $course_id ) . '_' . (int) get_the_ID() . '_' . absint( $user_id );
			if ( false === strstr( $content, $shown_content_key ) ) {
				$shortcode_out = do_shortcode( '[course_content course_id="' . $course_id . '" user_id="' . $user_id . '" post_id="' . get_the_ID() . '"]' );
				if ( ! empty( $shortcode_out ) ) {
					echo $shortcode_out;
				}
			}
		} else {
		
			?>

			<div class="ld-item-list ld-lesson-list">
				<div class="ld-section-heading">

					<?php
					/**
					 * Fires before the course heading.
					 *
					 * @since 3.0.0
					 *
					 * @param int $course_id Course ID.
					 * @param int $user_id   User ID.
					 */
					do_action( 'learndash-course-heading-before', $course_id, $user_id );
					?>

					<h2>
					<?php
					printf(
						// translators: placeholder: Course.
						esc_html_x( '%s Content', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					);
					?>
					</h2>

					<?php
					/**
					 * Fires after the course heading.
					 *
					 * @since 3.0.0
					 *
					 * @param int $course_id Course ID.
					 * @param int $user_id   User ID.
					 */
					do_action( 'learndash-course-heading-after', $course_id, $user_id );
					?>

					<div class="ld-item-list-actions" data-ld-expand-list="true">

						<?php
						/**
						 * Fires before the course expand.
						 *
						 * @since 3.0.0
						 *
						 * @param int $course_id Course ID.
						 * @param int $user_id   User ID.
						 */
						do_action( 'learndash-course-expand-before', $course_id, $user_id );
						?>

						<?php
						// Only display if there is something to expand.
						if ( $has_topics || $has_lesson_quizzes ) :
							?>
							<div class="ld-expand-button ld-primary-background" id="<?php echo esc_attr( 'ld-expand-button-' . $course_id ); ?>" data-ld-expands="<?php echo esc_attr( 'ld-item-list-' . $course_id ); ?>" data-ld-expand-text="<?php echo esc_attr_e( 'Expand All', 'learndash' ); ?>" data-ld-collapse-text="<?php echo esc_attr_e( 'Collapse All', 'learndash' ); ?>">
								<span class="ld-icon-arrow-down ld-icon"></span>
								<span class="ld-text"><?php echo esc_html_e( 'Expand All', 'learndash' ); ?></span>
							</div> <!--/.ld-expand-button-->
							<?php
							/**
							 * Filters whether to expand all course steps by default. Default is false.
							 *
							 * @since 2.5.0
							 *
							 * @param boolean $expand_all Whether to expand all course steps.
							 * @param int     $course_id  Course ID.
							 * @param string  $context    The context where course is expanded.
							 */
							if ( apply_filters( 'learndash_course_steps_expand_all', false, $course_id, 'course_lessons_listing_main' ) ) {
								?>
								<script>
									jQuery( function(){
										setTimeout(function(){
											jQuery("<?php echo esc_attr( '#ld-expand-button-' . $course_id ); ?>").trigger('click');
										}, 1000);
									});
								</script>
								<?php
							}
						endif;

						/**
						 * Fires after the course content expand button.
						 *
						 * @since 3.0.0
						 *
						 * @param int $course_id Course ID.
						 * @param int $user_id   User ID.
						 */
						do_action( 'learndash-course-expand-after', $course_id, $user_id );
						?>

					</div> <!--/.ld-item-list-actions-->
				</div> <!--/.ld-section-heading-->

				<?php
				/**
				 * Fires before the course content listing
				 *
				 * @since 3.0.0
				 *
				 * @param int $course_id Course ID.
				 * @param int $user_id   User ID.
				 */
				do_action( 'learndash-course-content-list-before', $course_id, $user_id );

				/**
				 * Content content listing
				 *
				 * @since 3.0.0
				 *
				 * ('listing.php');
				 */
				learndash_get_template_part(
					'course/listing.php',
					array(
						'course_id'                  => $course_id,
						'user_id'                    => $user_id,
						'lessons'                    => $lessons,
						'lesson_topics'              => ! empty( $lesson_topics ) ? $lesson_topics : [],
						'quizzes'                    => $quizzes,
						'has_access'                 => $has_access,
						'course_pager_results'       => $course_pager_results,
						'lesson_progression_enabled' => $lesson_progression_enabled,
					),
					true
				);

				/**
				 * Fires before the course content listing.
				 *
				 * @since 3.0.0
				 *
				 * @param int $course_id Course ID.
				 * @param int $user_id   User ID.
				 */
				do_action( 'learndash-course-content-list-after', $course_id, $user_id );
				?>

			</div> <!--/.ld-item-list-->

			<?php
		}
	endif;

	/**
	 * Fires before the topic.
	 *
	 * @since 3.0.0
	 *
	 * @param int $post_id   Post ID.
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	do_action( 'learndash-course-after', get_the_ID(), $course_id, $user_id );
	learndash_load_login_modal_html();
	?>
</div>
