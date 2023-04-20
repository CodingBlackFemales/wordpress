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

		<a href="<?php echo esc_url( get_the_permalink( $course_id ) ); ?>" class="ld-item-name">
			<?php learndash_status_icon( $status, get_post_type(), null, true ); ?>
			<span class="ld-course-title"><?php echo esc_html( get_the_title( $course_id ) ); ?></span>
		</a> <!--/.ld-course-name-->

		<div class="ld-item-details">

			<?php
			$learndash_certificate_link = learndash_get_course_certificate_link( $course->ID, $user_id );
			if ( ! empty( $learndash_certificate_link ) ) :
				?>
				<a class="ld-certificate-link" target="_blank" href="<?php echo esc_url( $learndash_certificate_link ); ?>" aria-label="<?php esc_attr_e( 'Certificate', 'learndash' ); ?>"><span class="ld-icon ld-icon-certificate"></span></span></a>
			<?php endif; ?>

			<?php echo wp_kses_post( learndash_status_bubble( $status ) ); ?>

			<div class="ld-expand-button ld-primary-background ld-compact ld-not-mobile" data-ld-expands="<?php echo esc_attr( 'ld-course-list-item-' . $course_id ); ?>">
				<span class="ld-icon-arrow-down ld-icon"></span>
			</div> <!--/.ld-expand-button-->

			<div class="ld-expand-button ld-button-alternate ld-mobile-only" data-ld-expands="<?php echo esc_attr( 'ld-course-list-item-' . $course_id ); ?>"  data-ld-expand-text="<?php esc_html_e( 'Expand', 'learndash' ); ?>" data-ld-collapse-text="<?php esc_html_e( 'Collapse', 'learndash' ); ?>">
				<span class="ld-icon-arrow-down ld-icon"></span>
				<span class="ld-text ld-primary-color"><?php esc_html_e( 'Expand', 'learndash' ); ?></span>
			</div> <!--/.ld-expand-button-->

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

		$learndash_posts_per_page = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );
		if ( isset( $shortcode_atts['quiz_num'] ) && '' !== $shortcode_atts['quiz_num'] && intval( $shortcode_atts['quiz_num'] ) > 0 ) {
			$learndash_quizzes_per_page = intval( $shortcode_atts['quiz_num'] );
		} else {
			$learndash_quizzes_per_page = intval( $learndash_posts_per_page );
		}

		if ( $assignments || ! empty( $quiz_attempts[ $course_id ] ) ) :
			if ( isset( $quiz_attempts[ $course_id ] ) ) {
				$quiz_attempts['total_quiz_items'] = count( $quiz_attempts[ $course_id ] );
				$quiz_attempts['total_quiz_pages'] = ceil( count( $quiz_attempts[ $course_id ] ) / $learndash_quizzes_per_page );
				$quiz_attempts['quizzes-paged']    = ( isset( $_GET['profile-quizzes'] ) ? intval( $_GET['profile-quizzes'] ) : 1 );
				if ( $quiz_attempts['total_quiz_items'] >= $quiz_attempts['total_quiz_pages'] ) {
					$quiz_attempts[ $course_id ] = array_slice( $quiz_attempts[ $course_id ], ( $quiz_attempts['quizzes-paged'] * $learndash_quizzes_per_page ) - $learndash_quizzes_per_page, $learndash_quizzes_per_page, false );
				}
			}
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
				if ( ! empty( $quiz_attempts[ $course_id ] ) && isset( $shortcode_atts['show_quizzes'] ) && true === (bool) $shortcode_atts['show_quizzes'] && apply_filters( 'learndash_show_profile_quizzes', $shortcode_atts['show_quizzes'] ) ) :

					learndash_get_template_part(
						'shortcodes/profile/quizzes.php',
						array(
							'user_id'       => $user_id,
							'course_id'     => $course_id,
							'quiz_attempts' => $quiz_attempts,
						),
						true
					);

					$learndash_profile_quiz_pager = array(
						'paged'          => $quiz_attempts['quizzes-paged'],
						'total_items'    => $quiz_attempts['total_quiz_items'],
						'total_pages'    => $quiz_attempts['total_quiz_pages'],
						'quiz_num'       => $learndash_quizzes_per_page,
						'quiz_course_id' => $course_id,
					);

					learndash_get_template_part(
						'modules/pagination',
						array(
							'pager_results' => $learndash_profile_quiz_pager,
							'pager_context' => 'profile_quizzes',
						),
						true
					);
				endif;
				?>

				<?php
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
