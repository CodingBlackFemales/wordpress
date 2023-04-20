<?php
/**
 * LearnDash LD30 Displays the Prerequisites
 *
 * Available Variables:
 * $current_post           : (WP_Post Object) Current Post object being display. Equal to global $post in most cases.
 * $prerequisite_post      : (WP_Post Object) Post object needed to be taken prior to $current_post
 * $prerequisite_posts_all : (WP_Post Object) Post object needed to be taken prior to $current_post
 * $content_type           : (string) Will contain the singlar lowercase common label 'course', 'lesson', 'topic', 'quiz'
 * $course_settings        : (array) Settings specific to current course
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_links = '';
$i          = 0;
if ( ! empty( $prerequisite_posts_all ) ) {
	foreach ( $prerequisite_posts_all as $pre_post_id => $pre_status ) {
		if ( false === (bool) $pre_status ) {
			$i++;
			if ( ! empty( $post_links ) ) {
				$post_links .= ', ';
			}
			$post_links .= '<a href="' . esc_url( get_the_permalink( $pre_post_id ) ) . '">' . wp_kses_post( get_the_title( $pre_post_id ) ) . '</a>';
		}
	}
}
?>

<div class="learndash-wrapper">
	<?php
	$message = '<p>';

	$course_prereq_compare = learndash_get_setting( $current_post, 'course_prerequisite_compare' );

	if ( 'ANY' === $course_prereq_compare && $i > 1 ) {

		$message .= sprintf(
			// translators: placeholders: course, courses.
			esc_html_x(
				'To take this %1$s, you need to complete any of the following %2$s first:',
				'placeholders: course, courses',
				'learndash'
			),
			$content_type,
			esc_html( learndash_get_custom_label_lower( 'courses' ) )
		);

	} else {

		$message .= sprintf(
			// translators: placeholders: (1) course singular, (2) course or courses.
			esc_html_x(
				'To take this %1$s, you need to complete the following %2$s first:',
				'placeholders: (1) course singular, (2) course or courses',
				'learndash'
			),
			$content_type,
			esc_html( _n( learndash_get_custom_label_lower( 'course' ), learndash_get_custom_label_lower( 'courses' ), $i, 'learndash' ) ) // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle, WordPress.WP.I18n.NonSingularStringLiteralPlural
		);

	}

	if ( ! empty( $post_links ) ) {
		$message .= ' <span class="ld-text">' . $post_links . '</span>';
	}

	$message .= '</p>';

	learndash_get_template_part(
		'modules/alert.php',
		array(
			'type'    => 'warning',
			'icon'    => 'alert',
			'message' => $message,
		),
		true
	);

	?>
</div>
