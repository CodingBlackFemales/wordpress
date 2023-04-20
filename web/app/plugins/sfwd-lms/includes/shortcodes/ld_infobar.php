<?php
/**
 * LearnDash `[ld_infobar]` shortcode processing.
 *
 * @since 4.0.0
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[ld_infobar]` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 4.0.0
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int $post_id   ID of the content being displayed.
 *    @type int $course_id ID of the course when $post_id is a step such as a lesson/topic/quiz
 *    @type int $user_id   ID of the user
 *
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'ld_infobar'.
 */
function learndash_infobar_shortcode( $atts = array(), $content = '', $shortcode_slug = 'ld_infobar' ) {
	if ( learndash_is_active_theme( 'legacy' ) ) {
		return $content;
	}

	global $learndash_shortcode_used;

	static $shown_content = array();

	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$viewed_post_id = (int) get_the_ID();

	$atts_defaults = array(
		'course_id' => '',
		'post_id'   => '',
		'group_id'  => '',
		'user_id'   => '',
	);

	$atts = shortcode_atts( $atts_defaults, $atts );

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

	if ( ! empty( $atts['user_id'] ) ) {
		$atts['user_id'] = absint( $atts['user_id'] );
	} else {
		$atts['user_id'] = get_current_user_id();
	}

	if ( ! empty( $atts['course_id'] ) ) {
		if ( learndash_get_post_type_slug( 'course' ) !== get_post_type( $atts['course_id'] ) ) {
			$atts['course_id'] = 0;
		}
	}

	if ( ! empty( $atts['post_id'] ) ) {
		if ( ! in_array( get_post_type( $atts['post_id'] ), learndash_get_post_types( 'course' ), true ) ) {
			$atts['post_id'] = 0;
		}
	}

	if ( ! empty( $atts['group_id'] ) ) {
		if ( learndash_get_post_type_slug( 'group' ) !== get_post_type( $atts['group_id'] ) ) {
			$atts['group_id'] = 0;
		}
	}

	// If the 'post_id' is set to '0' it will trigger showing the course lessons listing.
	if ( '0' === $atts['post_id'] ) {
		if ( ! empty( $atts['course_id'] ) ) {
			$atts['post_id'] = $atts['course_id'];
		}
	} else {
		if ( empty( $atts['post_id'] ) ) {
			if ( ! empty( $viewed_post_id ) ) {
				if ( in_array( get_post_type( $viewed_post_id ), learndash_get_post_types( 'course' ), true ) ) {
					$atts['post_id'] = $viewed_post_id;
					if ( empty( $atts['course_id'] ) ) {
						$atts['course_id'] = learndash_get_course_id( $atts['post_id'] );
					}
				} elseif ( get_post_type( $viewed_post_id ) === learndash_get_post_type_slug( 'group' ) ) {
					$atts['group_id']  = $viewed_post_id;
					$atts['post_id']   = 0;
					$atts['course_id'] = 0;
				} else {
					if ( ! empty( $atts['course_id'] ) ) {
						$atts['post_id'] = $atts['course_id'];
					} elseif ( ! empty( $atts['group_id'] ) ) {
						$atts['post_id'] = $atts['group_id'];
					}
				}
			}
		}
	}

	$atts['group_id']  = absint( $atts['group_id'] );
	$atts['post_id']   = absint( $atts['post_id'] );
	$atts['course_id'] = absint( $atts['course_id'] );
	$atts['user_id']   = absint( $atts['user_id'] );

	if ( ! empty( $atts['group_id'] ) ) {
		$shown_content_key = $atts['group_id'] . '_' . $atts['user_id'];
	} elseif ( ! empty( $atts['course_id'] ) ) {
		$shown_content_key = $atts['course_id'] . '_' . $atts['post_id'] . '_' . $atts['user_id'];
	}

	if ( ( ! isset( $shown_content_key ) ) || ( empty( $shown_content_key ) ) ) {
		return $content;
	}

	$shown_content[ $shown_content_key ] = '';

	if ( ( ! empty( $atts['course_id'] ) ) && ( ! empty( $atts['post_id'] ) ) ) {
		if ( $atts['course_id'] === $atts['post_id'] ) {
			$course_status = learndash_course_status( $atts['course_id'], $atts['user_id'] );
			$post_post     = get_post( $atts['course_id'] );
		} elseif ( ! empty( $atts['course_id'] ) && ! empty( $atts['post_id'] ) ) {
			$course_status = learndash_is_item_complete( $atts['post_id'], $atts['user_id'], $atts['course_id'] );
			if ( $course_status ) {
				$course_status = 'complete';
			} else {
				$course_status = 'incomplete';
			}
			$post_post = get_post( $atts['post_id'] );
		}

		$context = learndash_get_post_type_key( $post_post->post_type );

		$has_access = sfwd_lms_has_access( $atts['course_id'], $atts['user_id'] );

		$shortcode_output = SFWD_LMS::get_template(
			'modules/infobar.php',
			array(
				'context'       => $context,
				'course_id'     => $atts['course_id'],
				'user_id'       => $atts['user_id'],
				'has_access'    => $has_access,
				'course_status' => $course_status,
				'post'          => $post_post,
			)
		);

		if ( ! empty( $shortcode_output ) ) {
			$shown_content[ $shown_content_key ] .= $shortcode_output;
		}
	} elseif ( ! empty( $atts['group_id'] ) ) {

		$post_post    = get_post( $atts['group_id'] );
		$has_access   = learndash_is_user_in_group( $atts['user_id'], $atts['group_id'] );
		$group_status = learndash_get_user_group_status( $atts['group_id'], $atts['user_id'] );

		$shortcode_output = SFWD_LMS::get_template(
			'modules/infobar_group.php',
			array(
				'context'      => 'group',
				'group_id'     => $atts['group_id'],
				'user_id'      => $atts['user_id'],
				'has_access'   => $has_access,
				'group_status' => $group_status,
				'post'         => $post_post,
			)
		);

		if ( ! empty( $shortcode_output ) ) {
			$shown_content[ $shown_content_key ] .= $shortcode_output;
		}
	}

	if ( ( isset( $shown_content[ $shown_content_key ] ) ) && ( ! empty( $shown_content[ $shown_content_key ] ) ) ) {
		$content                 .= '<div class="learndash-wrapper learndash-wrap learndash-shortcode-wrap learndash-shortcode-wrap-' . $shortcode_slug . '-' . $shown_content_key . '">' . $shown_content[ $shown_content_key ] . '</div>';
		$learndash_shortcode_used = true;
	}
	return $content;
}

add_shortcode( 'ld_infobar', 'learndash_infobar_shortcode', 10, 3 );
