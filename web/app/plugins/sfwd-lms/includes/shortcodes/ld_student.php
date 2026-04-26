<?php
/**
 * LearnDash `[student]` shortcode processing.
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

use LearnDash\Core\Models\Product;
use LearnDash\Core\Utilities\Cast;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[student]` shortcode output.
 *
 * Shortcode to display content to users that have access to current course ID.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 2.1.0
 *
 * @param array  $atts {
 *     An array of shortcode attributes.
 *
 *    @type int     $course_id Course ID. Default current course ID.
 *    @type int     $user_id   User ID. Default current user ID.
 *    @type string  $content   The shortcode content. Default null.
 *    @type boolean $autop     Whether to replace line breaks with paragraph elements. Default true.
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'student'.
 *
 * @return string The `student` shortcode output.
 */
function learndash_student_check_shortcode( $atts = array(), $content = '', $shortcode_slug = 'student' ) {
	global $learndash_shortcode_used;

	if ( ( ! empty( $content ) ) && ( is_user_logged_in() ) ) {
		if ( ! is_array( $atts ) ) {
			if ( ! empty( $atts ) ) {
				$atts = array( $atts );
			} else {
				$atts = array();
			}
		}

		$defaults = array(
			'course_id' => '',
			'group_id'  => '',
			'user_id'   => get_current_user_id(),
			'content'   => $content,
			'autop'     => true,
		);
		$atts     = wp_parse_args( $atts, $defaults );

		/** This filter is documented in includes/shortcodes/ld_course_resume.php */
		$atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

		if ( ( true === $atts['autop'] ) || ( 'true' === $atts['autop'] ) || ( '1' === $atts['autop'] ) ) {
			$atts['autop'] = true;
		} else {
			$atts['autop'] = false;
		}

		if ( ! empty( $atts['course_id'] ) ) {
			if ( learndash_get_post_type_slug( 'course' ) !== get_post_type( $atts['course_id'] ) ) {
				$atts['course_id'] = 0;
			}
		}

		if ( ! empty( $atts['group_id'] ) ) {
			if ( learndash_get_post_type_slug( 'group' ) !== get_post_type( $atts['group_id'] ) ) {
				$atts['group_id'] = 0;
			}
		}

		// If 'course_id' and 'group_id' are empty we check if we are showing a Course or Group related post type.
		if ( ( empty( $atts['course_id'] ) ) && ( empty( $atts['group_id'] ) ) ) {
			$viewed_post_id = (int) get_the_ID();
			if ( ! empty( $viewed_post_id ) ) {
				if ( in_array( get_post_type( $viewed_post_id ), learndash_get_post_types( 'course' ), true ) ) {
					$atts['course_id'] = learndash_get_course_id( $viewed_post_id );
				} elseif ( get_post_type( $viewed_post_id ) === learndash_get_post_type_slug( 'group' ) ) {
					$atts['group_id'] = $viewed_post_id;
				}
			}
		}

		/**
		 * Filters student shortcode attributes.
		 *
		 * @param array $attributes An array of student shortcode attributes.
		 */
		$atts = apply_filters( 'learndash_student_shortcode_atts', $atts );

		$atts['user_id']   = absint( $atts['user_id'] );
		$atts['group_id']  = absint( $atts['group_id'] );
		$atts['course_id'] = absint( $atts['course_id'] );

		$view_content = false;

		if ( ( ! empty( $atts['user_id'] ) ) && ( get_current_user_id() === $atts['user_id'] ) ) {
			if (
				! empty( $atts['course_id'] )
				|| ! empty( $atts['group_id'] )
			) {
				$product_id = 0;

				if ( ! empty( $atts['course_id'] ) ) {
					$course_id = Cast::to_int( learndash_get_course_id( $atts['course_id'] ) );

					if ( $course_id === $atts['course_id'] ) {
						$product_id = $course_id;
					}
				} else {
					$product_id = $atts['group_id'];
				}

				/**
				 * The product object.
				 *
				 * @var Product|null $product
				 */
				$product = Product::find( $product_id );

				$view_content = $product
								&& (
									$product->user_has_access( $atts['user_id'] )
									|| $product->is_pre_ordered( $atts['user_id'] )
								);
			} else {
				$user_enrolled_courses = learndash_user_get_enrolled_courses( $atts['user_id'], array() );
				$user_enrolled_groups  = learndash_get_users_group_ids( $atts['user_id'] );

				// If the user is enrolled in any courses or groups then we show the content.
				if ( ( count( $user_enrolled_courses ) ) || ( count( $user_enrolled_groups ) ) ) {
					$view_content = true;
				}
			}
		}

		/**
		 * Filters student shortcode if user can view content.
		 *
		 * @since 4.4.0
		 *
		 * @param bool  $view_content Whether to view content.
		 * @param array $atts         An array of shortcode attributes.
		 */
		$view_content = apply_filters( 'learndash_student_shortcode_view_content', $view_content, $atts );

		if ( $view_content ) {
			$learndash_shortcode_used = true;
			$atts['content']          = do_shortcode( $atts['content'] );

			$shortcode_out = SFWD_LMS::get_template(
				'learndash_course_student_message',
				array(
					'shortcode_atts' => $atts,
				),
				false
			);
			if ( ! empty( $shortcode_out ) ) {
				$content = '<div class="learndash-wrapper learndash-wrap learndash-shortcode-wrap">' . $shortcode_out . '</div>';
			}
		} else {
			$content = '';
		}
	}

	if ( ! is_user_logged_in() ) {
		$content = '';
	}

	return $content;
}
add_shortcode( 'student', 'learndash_student_check_shortcode' );
