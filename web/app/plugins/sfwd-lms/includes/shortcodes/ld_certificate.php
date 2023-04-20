<?php
/**
 * LearnDash `[ld_certificate]` shortcode processing.
 *
 * @since 3.1.4
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[ld_certificate]` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 3.1.4
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int      $course_id Course ID. Default 0.
 *    @type int      $quiz_id   Quiz ID. Default 0.
 *    @type int      $user_id   User ID. Default current user ID.
 *    @type string   $label     Certificate label. Default translatable 'Certificate' string.
 *    @type string   $class     Certificate CSS class. Default 'button'.
 *    @type string   $content   Shortcode context. Default empty.
 *    @type callable $callback  Callback for certificate button HTML output. Default empty.
 * }
 * @param string $content        The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'ld_certificate'.
 *
 * @return string The `ld_certificate` shortcode output.
 */
function ld_certificate_shortcode( $atts = array(), $content = '', $shortcode_slug = 'ld_certificate' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	global $learndash_shortcode_used;

	static $shown_content = array();

	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$viewed_post_id = (int) get_the_ID();

	$defaults = array(
		'course_id'  => 0,
		'group_id'   => 0,
		'quiz_id'    => 0,
		'user_id'    => get_current_user_id(),
		'label'      => esc_html__( 'Certificate', 'learndash' ),
		'class'      => 'button',
		'context'    => '', // User defined value.
		'display_as' => '',
	);
	$atts     = shortcode_atts( $defaults, $atts );

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

	$atts['course_id'] = absint( $atts['course_id'] );
	$atts['group_id']  = absint( $atts['group_id'] );
	$atts['quiz_id']   = absint( $atts['quiz_id'] );
	$atts['user_id']   = absint( $atts['user_id'] );

	if ( ( empty( $atts['course_id'] ) ) && ( empty( $atts['group_id'] ) ) && ( empty( $atts['quiz_id'] ) ) ) {
		if ( ! empty( $viewed_post_id ) ) {
			$viewed_post_type = get_post_type( $viewed_post_id );

			if ( learndash_get_post_type_slug( 'group' ) === $viewed_post_type ) {
				$atts['group_id'] = absint( $viewed_post_id );
			}
			if ( learndash_get_post_type_slug( 'quiz' ) === $viewed_post_type ) {
				$atts['quiz_id'] = absint( $viewed_post_id );
			}
			if ( in_array( $viewed_post_type, learndash_get_post_types( 'course' ), true ) ) {
				$course_id = learndash_get_course_id( $viewed_post_id );
				if ( ! empty( $course_id ) ) {
					$atts['course_id'] = absint( $course_id );
				}
			}
		}
	}

	if ( ( empty( $atts['course_id'] ) ) && ( empty( $atts['group_id'] ) ) && ( empty( $atts['quiz_id'] ) ) ) {
		return $content;
	}

	if ( '' === $atts['display_as'] ) {
		if ( ( empty( $atts['course_id'] ) ) && ( empty( $atts['group_id'] ) ) ) {
			$atts['display_as'] = 'button';
		}
	}

	if ( ( 'banner' === $atts['display_as'] ) && ( 'legacy' === LearnDash_Theme_Register::get_active_theme_key() ) ) {
		$atts['display_as'] = 'button';
	}

	if ( ! empty( $atts['group_id'] ) ) {
		$shown_content_key = $atts['group_id'] . '_' . $atts['user_id'];
	} elseif ( ! empty( $atts['course_id'] ) ) {
		$shown_content_key = $atts['course_id'] . '_' . $atts['user_id'];
	} elseif ( ! empty( $atts['quiz_id'] ) ) {
		$shown_content_key = $atts['quiz_id'] . '_' . $atts['user_id'];
	}

	if ( empty( $shown_content_key ) ) {
		return $content;
	}

	$shown_content[ $shown_content_key ] = '';

	/**
	 * Filters `ld_certificate` shortcode attributes.
	 *
	 * @since 3.1.4
	 *
	 * @param array $atts An array of shortcode attributes.
	 */
	$atts = apply_filters( 'ld_certificate_shortcode_values', $atts ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

	$atts['cert_url'] = '';

	if ( ! empty( $atts['user_id'] ) ) {
		if ( ( ! empty( $atts['course_id'] ) ) || ( ! empty( $atts['group_id'] ) ) || ( ! empty( $atts['quiz_id'] ) ) ) {
			$learndash_shortcode_used = true;
			$cert_button_html         = '';
			if ( ! empty( $atts['quiz_id'] ) ) {
				$cert_details = learndash_certificate_details( $atts['quiz_id'], $atts['user_id'] );
				if ( isset( $cert_details['certificateLink'] ) && ! empty( $cert_details['certificateLink'] ) ) {
					if ( ! isset( $cert_details['certificate_threshold'] ) ) {
						$cert_details['certificate_threshold'] = floatval( 0.0 );
					} else {
						$cert_details['certificate_threshold'] = floatval( $cert_details['certificate_threshold'] ) * 100;
					}

					$user_quiz_progress = learndash_user_get_quiz_progress( $atts['user_id'], $atts['quiz_id'], $atts['course_id'] );
					if ( ( is_array( $user_quiz_progress ) ) && ( count( $user_quiz_progress ) ) ) {
						ksort( $user_quiz_progress );

						foreach ( $user_quiz_progress as $quiz_attempt ) {
							if ( $cert_details['certificate_threshold'] > '0.0' ) {
								if ( $quiz_attempt['percentage'] >= $cert_details['certificate_threshold'] ) {
									$atts['cert_url'] = add_query_arg( 'time', $quiz_attempt['time'], $cert_details['certificateLink'] );
									break;
								}
							} else {
								$atts['cert_url'] = add_query_arg( 'time', $quiz_attempt['time'], $cert_details['certificateLink'] );
								break;
							}
						}
					}
				}
			} elseif ( ! empty( $atts['course_id'] ) ) {
				// Ensure the user completed the Course.
				if ( 'completed' === learndash_course_status( $atts['course_id'], $atts['user_id'], true ) ) {
					$atts['cert_url'] = learndash_get_course_certificate_link( $atts['course_id'], $atts['user_id'] );
				}
			} elseif ( ! empty( $atts['group_id'] ) ) {
				$atts['cert_url'] = learndash_get_group_certificate_link( $atts['group_id'], $atts['user_id'] );
			}

			if ( ! empty( $atts['cert_url'] ) ) {
				/**
				 * Filters `ld_certificate` shortcode certificate URL.
				 *
				 * @since 3.1.4
				 *
				 * @param string $cert_url URL for Certificate.
				 */
				$atts['cert_url'] = apply_filters( 'ld_certificate_shortcode_cert_url', $atts['cert_url'] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

				if ( 'banner' === $atts['display_as'] ) {
					$cert_button_html = SFWD_LMS::get_template(
						'modules/alert.php',
						array(
							'type'    => 'success ld-alert-certificate',
							'icon'    => 'certificate',
							'message' => __( 'You\'ve earned a certificate!', 'learndash' ),
							'button'  => array(
								'url'    => $atts['cert_url'],
								'icon'   => 'download',
								'label'  => __( 'Download Certificate', 'learndash' ),
								'target' => '_new',
							),
						),
						false
					);
				} else {
					$cert_button_html = '<a href="' . esc_url( $atts['cert_url'] ) . '"' .
					( ! empty( $atts['class'] ) ? ' class="' . esc_attr( $atts['class'] ) : '' ) . '"' .
					( ! empty( $atts['id'] ) ? ' id="' . esc_attr( $atts['id'] ) . '"' : '' ) .
					'>';

					if ( ! empty( $atts['label'] ) ) {
						$cert_button_html .= do_shortcode( $atts['label'] );
					}

					$cert_button_html .= '</a>';
				}
			}

			/**
			 * Filters certificate button HTML output for `ld_certificate` shortcode.
			 *
			 * @since 3.1.4
			 * @deprecated 3.2.0 Use the {@see 'learndash_certificate_html'} filter instead.
			 *
			 * @param string $cert_button_html The HTML output of generated button element.
			 * @param array  $atts             An array of shortcode attributes used to generate $cert_button_html element.
			 * @param string $content          Shortcode additional content passed into handler function.
			 */
			if ( has_filter( 'learndash_ld_certificate_html' ) ) {
				$cert_button_html = apply_filters_deprecated( 'learndash_ld_certificate_html', array( $cert_button_html, $atts, $content ), '3.2.0', 'learndash_certificate_html' );
			}

			/**
			 * Filters certificate button HTML output for `ld_certificate` shortcode.
			 *
			 * @since 3.1.4
			 *
			 * @param string $cert_button_html The HTML output of generated button element.
			 * @param array  $atts             Optional. An array of shortcode attributes used to generate $cert_button_html element.
			 * @param string $content          Optional. Shortcode additional content passed into handler function.
			 */
			$cert_button_html = apply_filters( 'learndash_certificate_html', $cert_button_html, $atts, $content );

			if ( ! empty( $cert_button_html ) ) {
				$cert_button_html                    = '<div class="learndash-wrapper learndash-wrap learndash-shortcode-wrap learndash-shortcode-wrap-' . $shortcode_slug . '-' . $shown_content_key . '">' . $cert_button_html . '</div>';
				$shown_content[ $shown_content_key ] = $cert_button_html;
				$content                            .= $cert_button_html;
			}
		}
	}

	return $content;
}
add_shortcode( 'ld_certificate', 'ld_certificate_shortcode' );
