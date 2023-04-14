<?php
/**
 * LearnDash LD30 Displays a user's profile course progress row.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course      = get_post( $course_id );
$course_link = get_permalink( $course_id );

$progress = learndash_course_progress(
	array(
		'user_id'   => $user_id,
		'course_id' => $course_id,
		'array'     => true,
	)
);

if ( empty( $progress ) ) {
	$progress = array(
		'percentage' => 0,
		'completed'  => 0,
		'total'      => 0,
	);
}

$status = ( 100 === absint( $progress['percentage'] ) ) ? 'completed' : 'notcompleted';

if ( absint( $progress['percentage'] ) > 0 && 100 !== absint( $progress['percentage'] ) ) {
	$status = 'progress';
}

/**
 * Filters shortcode course row CSS class.
 *
 * @since 3.0.0
 *
 * @param string $course_row_class List of the course row CSS classes
 */
$course_class = apply_filters(
	'learndash-course-row-class',
	'ld-item-list-item ld-item-list-item-course ld-expandable ' . ( 100 === absint( $progress['percentage'] ) ? 'learndash-complete' : 'learndash-incomplete' ),
	$course,
	$user_id
);
?>

<div class="<?php echo esc_attr( $course_class ); ?>" id="<?php echo esc_attr( 'ld-course-list-item-' . $course_id ); ?>">
	<div class="ld-item-list-item-preview">

		<div class="bb-sketch-expand-button">
			<div class="ld-expand-button ld-primary-background ld-compact ld-not-mobile" data-ld-expands="<?php echo esc_attr( 'ld-course-list-item-' . $course_id ); ?>">
				<span class="ld-icon-arrow-down ld-icon"></span>
			</div> <!--/.ld-expand-button-->

			<div class="ld-expand-button ld-button-alternate ld-mobile-only" data-ld-expands="<?php echo esc_attr( 'ld-course-list-item-' . $course_id ); ?>">
				<span class="ld-icon-arrow-down ld-icon"></span>
				<span class="ld-text ld-primary-color"><?php esc_html_e( 'Expand', 'buddyboss-theme' ); ?></span>
			</div> <!--/.ld-expand-button-->
		</div>

		<a href="<?php echo esc_url( get_the_permalink( $course_id ) ); ?>" class="ld-item-name">
			<span class="ld-course-title"><?php echo esc_html( get_the_title( $course_id ) ); ?></span>
		</a> <!--/.ld-course-name-->

		<div class="ld-item-details">

			<?php
			$certificateLink = learndash_get_course_certificate_link( $course->ID, $user_id );
			if ( ! empty( $certificateLink ) ) :
				?>
				<a class="ld-certificate-link" target="_blank" href="<?php echo esc_url( $certificateLink ); ?>" aria-label="<?php esc_attr_e( 'Certificate', 'buddyboss-theme' ); ?>"><span class="ld-icon ld-icon-certificate"></span></span></a>
			<?php endif; ?>

			<?php echo wp_kses_post( learndash_status_bubble( $status ) ); ?>

			<?php learndash_status_icon( $status, get_post_type(), null, true ); ?>

		</div> <!--/.ld-course-details-->

	</div> <!--/.ld-course-preview-->
	<div class="ld-item-list-item-expanded" data-ld-expand-id="<?php echo esc_attr( 'ld-course-list-item-' . $course_id ); ?>">

		<?php
		learndash_get_template_part(
			'shortcodes/profile/course-progress.php',
			array(
				'user_id'   => $user_id,
				'course_id' => $course_id,
				'progress'  => $progress,
			),
			true
		);

		$assignments = learndash_get_course_assignments( $course_id, $user_id );

		if ( $assignments || ! empty( $quiz_attempts[ $course_id ] ) ) :
			?>

			<div class="ld-item-contents">

				<?php
				/**
				 * Filters Whether to show profiles quizzes.
				 *
				 * @since 2.5.8
				 *
				 * @param boolean $show_quizzes Whether to show profile quizzes.
				 */
				if ( ! empty( $quiz_attempts[ $course_id ] ) && isset( $shortcode_atts['show_quizzes'] ) && true === $shortcode_atts['show_quizzes'] && apply_filters( 'learndash_show_profile_quizzes', $shortcode_atts['show_quizzes'] ) ) :

					learndash_get_template_part(
						'shortcodes/profile/quizzes.php',
						array(
							'user_id'       => $user_id,
							'course_id'     => $course_id,
							'quiz_attempts' => $quiz_attempts,
						),
						true
					);

				endif;

				if ( $assignments && ! empty( $assignments ) ) :

					learndash_get_template_part(
						'shortcodes/profile/assignments.php',
						array(
							'user_id'     => $user_id,
							'course_id'   => $course_id,
							'assignments' => $assignments,
						),
						true
					);

				endif;
				?>

			</div> <!--/.ld-course-contents-->

		<?php endif; ?>

	</div> <!--/.ld-course-list-item-expanded-->

</div> <!--/.ld-course-list-item-->
