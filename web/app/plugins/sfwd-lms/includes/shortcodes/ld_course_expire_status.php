<?php
/**
 * LearnDash `[ld_course_expire_status]` shortcode processing.
 *
 * @since 2.1.0
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[ld_course_expire_status]` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 2.1.0
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int     $course_id    Course ID. Default current course ID.
 *    @type int     $user_id      User ID. Default current user ID.
 *    @type string  $format       The date format. Default value of date_format option.
 *    @type boolean $autop        Whether to replace line breaks with paragraph elements. Default true.
 *    @type string  $label_before The content to print before label. Default a translatable string.
 *    @type string  $label_after  The content to print after label. Default a translatable string.
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'ld_course_expire_status'.
 *
 * @return string The `ld_course_expire_status` shortcode output.
 */
function learndash_course_expire_status_shortcode( $atts = array(), $content = '', $shortcode_slug = 'ld_course_expire_status' ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	$content_shortcode = '';

	$atts = shortcode_atts(
		array(
			'course_id'    => learndash_get_course_id(),
			'user_id'      => get_current_user_id(),
			// translators: placeholder: Course.
			'label_before' => sprintf( esc_html_x( '%s access will expire on:', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			// translators: placeholder: Course.
			'label_after'  => sprintf( esc_html_x( '%s access expired on:', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			'format'       => get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			'autop'        => true,
		),
		$atts
	);

	if ( ( true === $atts['autop'] ) || ( 'true' === $atts['autop'] ) || ( '1' === $atts['autop'] ) ) {
		$atts['autop'] = true;
	} else {
		$atts['autop'] = false;
	}

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

	/**
	 * Filters `ld_course_expire_status` shortcode attributes.
	 *
	 * @param array $attributes An array of ld_course_expire_status shortcode attributes.
	 */
	$atts = apply_filters( 'learndash_ld_course_expire_status_shortcode_atts', $atts );

	if ( ( ! empty( $atts['course_id'] ) ) && ( ! empty( $atts['user_id'] ) ) ) {
		if ( sfwd_lms_has_access( $atts['course_id'], $atts['user_id'] ) ) {

			$courses_access_from = ld_course_access_from( $atts['course_id'], $atts['user_id'] );
			if ( empty( $courses_access_from ) ) {
				$courses_access_from = learndash_user_group_enrolled_to_course_from( $atts['user_id'], $atts['course_id'] );
			}

			if ( ! empty( $courses_access_from ) ) {

				$atts['expires_on_timestamp'] = ld_course_access_expires_on( $atts['course_id'], $atts['user_id'] );
				if ( ! empty( $atts['expires_on_timestamp'] ) ) {
					if ( $atts['expires_on_timestamp'] > time() ) {
						$content_shortcode .= $atts['label_before'];
					}

					$atts['expires_on_formatted'] = learndash_adjust_date_time_display( $atts['expires_on_timestamp'], $atts['format'] );

					$content_shortcode .= ' <span class="learndash-course-expire-status-date learndash-course-expire-status-date-expires-on">' . $atts['expires_on_formatted'] . '</span>';
				}
			}
		} else {

			$atts['expired_on_timestamp'] = get_user_meta( $atts['user_id'], 'learndash_course_expired_' . $atts['course_id'], true );

			if ( ! empty( $atts['expired_on_timestamp'] ) ) {

				$content_shortcode .= $atts['label_after'];

				$atts['expired_on_formatted'] = learndash_adjust_date_time_display( $atts['expired_on_timestamp'], $atts['format'] );

				$content_shortcode .= ' <span class="learndash-course-expire-status-date learndash-course-expire-status-date-expired-on">' . $atts['expired_on_formatted'] . '</span>';

			}
		}

		if ( ! empty( $content_shortcode ) ) {

			$atts['content'] = do_shortcode( $content_shortcode );
				return SFWD_LMS::get_template(
					'learndash_course_expire_status_message',
					array(
						'shortcode_atts' => $atts,
					),
					false
				);
		}
	}

	if ( ! empty( $content_shortcode ) ) {
		$content .= $content_shortcode;
	}
	return $content;
}

add_shortcode( 'ld_course_expire_status', 'learndash_course_expire_status_shortcode', 10, 3 );
