<?php
/**
 * LearnDash `[groupinfo]` shortcode processing.
 *
 * @since 3.2.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[groupinfo]` shortcode output.
 *
 * @since 3.2.0
 *
 * @param array  $attr {
 *    An array of shortcode attributes.
 *
 *    @type string     $show           The course info field to display. Default 'course_title'.
 *    @type int|string $user_id        User ID. Default empty.
 *    @type int|string $group_id       Group ID. Default empty.
 *    @type int|string $format         Date display format. Default 'F j, Y, g:i a'.
 *    @type int        $decimals       The number of decimal points. Default 2.
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'groupinfo'.
 *
 * @return string shortcode output
 */
function learndash_groupinfo_shortcode( $attr = array(), $content = '', $shortcode_slug = 'groupinfo' ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	$shortcode_atts             = shortcode_atts(
		array(
			'show'     => 'group_title',
			'user_id'  => '',
			'group_id' => '',
			'format'   => 'F j, Y, g:i a',
			'decimals' => 2,
		),
		$attr
	);
	$shortcode_atts['group_id'] = absint( $shortcode_atts['group_id'] );
	$shortcode_atts['user_id']  = absint( $shortcode_atts['user_id'] );

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$shortcode_atts = apply_filters( 'learndash_shortcode_atts', $shortcode_atts, $shortcode_slug );

	$shortcode_atts['group_id'] = ! empty( $shortcode_atts['group_id'] ) ? $shortcode_atts['group_id'] : '';
	if ( '' === $shortcode_atts['group_id'] ) {
		if ( ( isset( $_GET['group_id'] ) ) && ( ! empty( $_GET['group_id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended 
			$shortcode_atts['group_id'] = intval( $_GET['group_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} else {
			$post_id = get_the_id();
			if ( learndash_get_post_type_slug( 'group' ) === get_post_type( $post_id ) ) {
				$shortcode_atts['group_id'] = absint( $post_id );
			}
		}
	}

	$shortcode_atts['user_id'] = ! empty( $shortcode_atts['user_id'] ) ? $shortcode_atts['user_id'] : '';
	if ( '' === $shortcode_atts['user_id'] ) {
		if ( ( isset( $_GET['user_id'] ) ) && ( ! empty( $_GET['user_id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$shortcode_atts['user_id'] = intval( $_GET['user_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}

	if ( empty( $shortcode_atts['user_id'] ) ) {
		$shortcode_atts['user_id'] = get_current_user_id();

		/**
		 * Added logic to allow admin and group_leader to view certificate from other users.
		 *
		 * @since 2.3.0
		 */
		$post_type = '';
		if ( get_query_var( 'post_type' ) ) {
			$post_type = get_query_var( 'post_type' );
		}

		if ( 'sfwd-certificates' == $post_type ) {
			if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( ( isset( $_GET['user'] ) ) && ( ! empty( $_GET['user'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$shortcode_atts['user_id'] = intval( $_GET['user'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}
	}

	if ( empty( $shortcode_atts['group_id'] ) || empty( $shortcode_atts['user_id'] ) ) {
		/**
		 * Filter Group info shortcode value.
		 *
		 * @since 3.2.0
		 *
		 * @param mixed $value          Determined return value.
		 * @param array $shortcode_atts Shortcode attributes.
		 */
		return apply_filters( 'learndash_groupinfo', '', $shortcode_atts );
	}

	$shortcode_atts['show'] = strtolower( $shortcode_atts['show'] );

	$group_post = get_post( $shortcode_atts['group_id'] );
	if ( ( $group_post ) && ( is_a( $group_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'group' ) === $group_post->post_type ) ) {
		switch ( $shortcode_atts['show'] ) {
			case 'group_title':
				$shortcode_atts[ $shortcode_atts['show'] ] = $group_post->post_title;
				/** This filter is documented in includes/shortcodes/ld_groupinfo.php */
				return apply_filters( 'learndash_groupinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );

			case 'group_url':
				$shortcode_atts[ $shortcode_atts['show'] ] = get_permalink( $shortcode_atts['group_id'] );
				/** This filter is documented in includes/shortcodes/ld_groupinfo.php */
				return apply_filters( 'learndash_groupinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );

			case 'group_price_type':
				$shortcode_atts[ $shortcode_atts['show'] ] = learndash_get_setting( $shortcode_atts['group_id'], 'group_price_type' );
				/** This filter is documented in includes/shortcodes/ld_groupinfo.php */
				return apply_filters( 'learndash_groupinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );

			case 'group_price':
				$shortcode_atts[ $shortcode_atts['show'] ] = learndash_get_setting( $shortcode_atts['group_id'], 'group_price' );
				/** This filter is documented in includes/shortcodes/ld_groupinfo.php */
				return apply_filters( 'learndash_groupinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );

			case 'group_users_count':
				$shortcode_atts[ $shortcode_atts['show'] ] = count( learndash_get_groups_user_ids( $shortcode_atts['group_id'] ) );
				/** This filter is documented in includes/shortcodes/ld_groupinfo.php */
				return apply_filters( 'learndash_groupinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );

			case 'group_courses_count':
				$shortcode_atts[ $shortcode_atts['show'] ] = count( learndash_group_enrolled_courses( $shortcode_atts['group_id'] ) );
				/** This filter is documented in includes/shortcodes/ld_groupinfo.php */
				return apply_filters( 'learndash_groupinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );

			default:
				/** This filter is documented in includes/shortcodes/ld_groupinfo.php */
				return apply_filters( 'learndash_groupinfo', '', $shortcode_atts );

			// The following cases required user_id.

			case 'user_group_status':
				if ( ( ! empty( $shortcode_atts['group_id'] ) ) && ( ! empty( $shortcode_atts['user_id'] ) ) ) {
					$shortcode_atts[ $shortcode_atts['show'] ] = learndash_get_user_group_status( $shortcode_atts['group_id'], $shortcode_atts['user_id'] );
					/** This filter is documented in includes/shortcodes/ld_groupinfo.php */
					return apply_filters( 'learndash_groupinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );
				}
				break;

			case 'enrolled_on':
				if ( ( ! empty( $shortcode_atts['group_id'] ) ) && ( ! empty( $shortcode_atts['user_id'] ) ) ) {
					$group_started_timestamp = learndash_get_user_group_started_timestamp( $shortcode_atts['group_id'], $shortcode_atts['user_id'] );
					if ( ! empty( $group_started_timestamp ) ) {
						$shortcode_atts[ $shortcode_atts['show'] ] = $group_started_timestamp;
						/** This filter is documented in includes/shortcodes/ld_groupinfo.php */
						return apply_filters( 'learndash_groupinfo', learndash_adjust_date_time_display( $group_started_timestamp, $shortcode_atts['format'] ), $shortcode_atts );
					} else {
						/** This filter is documented in includes/shortcodes/ld_groupinfo.php */
						return apply_filters( 'learndash_groupinfo', '-', $shortcode_atts );
					}
				}
				break;

			case 'completed_on':
				if ( ( ! empty( $shortcode_atts['group_id'] ) ) && ( ! empty( $shortcode_atts['user_id'] ) ) ) {
					$group_completed_timestamp = learndash_get_user_group_completed_timestamp( $shortcode_atts['group_id'], $shortcode_atts['user_id'] );
					if ( ! empty( $group_completed_timestamp ) ) {
						$shortcode_atts[ $shortcode_atts['show'] ] = $group_completed_timestamp;
						/** This filter is documented in includes/shortcodes/ld_groupinfo.php */
						return apply_filters( 'learndash_groupinfo', learndash_adjust_date_time_display( $group_completed_timestamp, $shortcode_atts['format'] ), $shortcode_atts );
					} else {
						/** This filter is documented in includes/shortcodes/ld_groupinfo.php */
						return apply_filters( 'learndash_groupinfo', '-', $shortcode_atts );
					}
				}
				break;

			case 'percent_completed':
				if ( ( ! empty( $shortcode_atts['group_id'] ) ) && ( ! empty( $shortcode_atts['user_id'] ) ) ) {
					$group_percent_completed = learndash_get_user_group_completed_percentage( $shortcode_atts['group_id'], $shortcode_atts['user_id'] );
					if ( ! empty( $group_percent_completed ) ) {
						$shortcode_atts[ $shortcode_atts['show'] ] = $group_percent_completed;
						/** This filter is documented in includes/shortcodes/ld_groupinfo.php */
						return apply_filters( 'learndash_groupinfo', number_format( $group_percent_completed, $shortcode_atts['decimals'] ), $shortcode_atts );
					} else {
						/** This filter is documented in includes/shortcodes/ld_groupinfo.php */
						return apply_filters( 'learndash_groupinfo', '-', $shortcode_atts );
					}
				}
				break;
		}
	}
	return '';
}
add_shortcode( 'groupinfo', 'learndash_groupinfo_shortcode', 10, 3 );
