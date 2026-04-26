<?php
/**
 * LearnDash `[visitor]` shortcode processing.
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

use LearnDash\Core\Models\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[visitor]` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 2.1.0
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int     $course_id Course ID. Default current course ID.
 *    @type string  $content   The shortcode content. Default empty
 *    @type boolean $autop     Whether to replace line breaks with paragraph elements. Default true.
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'visitor'.
 *
 * @return string The `visitor` shortcode output.
 */
function learndash_visitor_check_shortcode( $atts = array(), $content = '', $shortcode_slug = 'visitor' ) {
	global $learndash_shortcode_used;

	if ( ! empty( $content ) ) {
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
		 * Filters visitor shortcode attributes.
		 *
		 * @param array $atts An array of shortcode attributes.
		 */
		$atts = apply_filters( 'learndash_visitor_shortcode_atts', $atts );

		$atts['user_id']   = absint( $atts['user_id'] );
		$atts['group_id']  = absint( $atts['group_id'] );
		$atts['course_id'] = absint( $atts['course_id'] );

		$view_content = false;

		if ( ( empty( $atts['user_id'] ) ) || ( get_current_user_id() === $atts['user_id'] ) ) {
			if (
				! empty( $atts['course_id'] )
				|| ! empty( $atts['group_id'] )
			) {
				if ( empty( $atts['user_id'] ) ) {
					$view_content = true;
				} else {
					$product_id = ! empty( $atts['course_id'] ) ? $atts['course_id'] : $atts['group_id'];

					/**
					 * The product object.
					 *
					 * @var Product|null $product
					 */
					$product = Product::find( $product_id );

					$view_content = ! $product
									|| (
										! $product->user_has_access( $atts['user_id'] )
										&& ! $product->is_pre_ordered( $atts['user_id'] )
									);
				}
			} else {
				if ( get_current_user_id() ) {
					$user_enrolled_courses = learndash_user_get_enrolled_courses( get_current_user_id(), array() );
					$user_enrolled_groups  = learndash_get_users_group_ids( get_current_user_id() );

					// If the user is not enrolled in any courses or groups then we show the content.
					if ( ( ! count( $user_enrolled_courses ) ) && ( ! count( $user_enrolled_groups ) ) ) {
						$view_content = true;
					}
				} else {
					$view_content = true;
				}
			}
		}

		/**
		 * Filters visitor shortcode if user can view content.
		 *
		 * @since 4.4.0
		 *
		 * @param bool  $view_content Whether to view content.
		 * @param array $atts         An array of shortcode attributes.
		 */
		$view_content = apply_filters( 'learndash_visitor_shortcode_view_content', $view_content, $atts );

		if ( $view_content ) {
			$learndash_shortcode_used = true;
			$atts['content']          = do_shortcode( $atts['content'] );
			$shortcode_out            = SFWD_LMS::get_template(
				'learndash_course_visitor_message',
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
	return $content;
}
add_shortcode( 'visitor', 'learndash_visitor_check_shortcode' );
