<?php
/**
 * LearnDash `[ld_course_resume]` shortcode processing.
 *
 * @since 3.1.4
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[ld_course_resume]` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 3.1.4
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int     $course_id  Course ID. Default 0.
 *    @type int     $user_id    User ID. Default current user ID.
 *    @type string  $label      Resume label. Default empty.
 *    @type string  $html_class The resume CSS classes. Default 'ld-course-resume'.
 *    @type string  $html_id    The value for id HTML attribute. Default empty.
 *    @type boolean $button     Whether to show button. Default true.
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'ld_course_resume'.
 *
 * @return string The `ld_course_resume` shortcode output.
 */
function ld_course_resume_shortcode( $atts = array(), $content = '', $shortcode_slug = 'ld_course_resume' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	global $learndash_shortcode_used;

	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$defaults = array(
		'course_id'  => 0,
		'user_id'    => get_current_user_id(),
		'label'      => '',
		'html_class' => 'ld-course-resume',
		'html_id'    => '',
		'button'     => true,
	);

	$atts = shortcode_atts( $defaults, $atts );

	$atts['course_id'] = absint( $atts['course_id'] );
	$atts['user_id']   = absint( $atts['user_id'] );

	if ( empty( $atts['course_id'] ) ) {
		$atts['course_id'] = learndash_get_course_id();
		if ( ( empty( $atts['course_id'] ) ) && ( ! empty( $atts['user_id'] ) ) ) {
			$atts['course_id'] = learndash_get_last_active_course( $atts['user_id'] );
		}
	}

	if ( empty( $atts['label'] ) ) {
		if ( ! empty( $content ) ) {
			$atts['label'] = $content;
			$content       = '';
		} else {
			// translators: placeholder: Course.
			$atts['label'] = sprintf( esc_html_x( 'Resume %s', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) );
		}
	}

	if ( ( 'false' === $atts['button'] ) || ( '0' === $atts['button'] ) ) {
		$atts['button'] = false;
	} else {
		$atts['button']      = true;
		$atts['html_class'] .= ' ld-button ';
	}

	/**
	 * Filters `ld_course_resume` shortcode attributes.
	 *
	 * @param array $atts An array of shortcode attributes.
	 */
	$atts = apply_filters( 'learndash_shortcode_atts_ld_course_resume', $atts );

	/**
	 * Filters shortcode attributes.
	 *
	 * @param array  $atts           An array of shortcode attributes.
	 * @param string $shortcode_slug The current shortcode slug.
	 */
	$atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

	if ( ( ! empty( $atts['user_id'] ) ) && ( ! empty( $atts['course_id'] ) ) ) {
		if ( sfwd_lms_has_access( $atts['course_id'], $atts['user_id'] ) ) {
			// We only output for 'in progress' courses.
			$course_status = learndash_course_status( $atts['course_id'], $atts['user_id'], true );
			if ( 'completed' !== $course_status ) {
				$user_course_last_step_id = learndash_user_progress_get_first_incomplete_step( $atts['user_id'], $atts['course_id'] );
				if ( ! empty( $user_course_last_step_id ) ) {
					$user_course_last_step_id = learndash_user_progress_get_parent_incomplete_step( $atts['user_id'], $atts['course_id'], $user_course_last_step_id );
					$course_permalink         = learndash_get_step_permalink( $user_course_last_step_id, $atts['course_id'] );
					if ( ! empty( $course_permalink ) ) {
						$learndash_shortcode_used = true;

						$html_class = '';
						if ( ! empty( $atts['html_class'] ) ) {
							$html_class = ' class="' . esc_attr( $atts['html_class'] ) . '"';
						}

						$html_id = '';
						if ( ! empty( $atts['html_id'] ) ) {
							$html_id = ' id="' . esc_attr( $atts['html_id'] ) . '"';
						}

						if ( true === $atts['button'] ) {
							$content .= '<div class="learndash-wrapper">';
						}
						$content .= '<a ' . $html_id . ' ' . $html_class . ' href="' . $course_permalink . '">' . do_shortcode( $atts['label'] ) . '</a>';
						if ( true === $atts['button'] ) {
							$content .= '</div>';
						}
					}
				}
			}
		}
	}

	return $content;
}
add_shortcode( 'ld_course_resume', 'ld_course_resume_shortcode', 10, 3 );
