<?php
/**
 * Course Grid module functions.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

use LearnDash\Course_Grid;
use LearnDash\Course_Grid\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prepares post attributes for the course grid template.
 *
 * This function gathers all necessary information about a post to display
 * in the course grid, including pricing, duration, students count, completion status,
 * and other metadata needed for proper display in the grid layout.
 *
 * @since 4.21.4
 *
 * @param WP_Post|int $post The post object or post ID.
 * @param array       $atts The shortcode attributes.
 * @param array       $args Additional arguments.
 *
 * @return array      Array of post attributes for template display.
 */
function learndash_course_grid_prepare_template_post_attributes( $post, $atts = [], $args = [] ) {
	if ( is_numeric( $post ) ) {
		$post = get_post( $post );
	}

	$user_id = get_current_user_id();

	// Default values.
	$students              = array();
	$duration              = '';
	$trial_price           = 0;
	$trial_duration        = '';
	$subscription_duration = '';
	$reviews               = '';
	$categories            = '';
	$tags                  = '';
	$author                = array();
	$total_steps           = 0;
	$courses               = array();
	$lessons               = array();
	$topics                = array();
	$quizzes               = array();
	$forums                = array();

	$course_options = null;
	if ( $post->post_type == 'sfwd-courses' ) {
		$course_options = get_post_meta( $post->ID, '_sfwd-courses', true );
	}

	if ( defined( 'LEARNDASH_VERSION' ) ) {
		$course_id = $args['course_id'] ?? null;
		$course_id = $course_id ?? learndash_get_course_id( $post->ID );

		if ( $post->post_type == 'sfwd-courses' ) {
			$total_steps    = learndash_get_course_steps_count( $post->ID );
			$students_count = learndash_course_grid_count_students( $post->ID );
			$lessons_data   = learndash_course_get_steps_by_type( $post->ID, 'sfwd-lessons' );
			$topics_data    = learndash_course_get_steps_by_type( $post->ID, 'sfwd-topic' );
			$quizzes_data   = learndash_course_get_steps_by_type( $post->ID, 'sfwd-quiz' );
		} elseif ( $post->post_type == 'groups' ) {
			$students_count = learndash_course_grid_count_students( $post->ID );
			$courses        = learndash_group_enrolled_courses( $post->ID );
		} elseif ( $post->post_type == 'sfwd-lessons' ) {
			$students_count = learndash_course_grid_count_students( $course_id );
			$topics_data    = learndash_course_get_children_of_step( $course_id, $post->ID, 'sfwd-topic' );
			$quizzes_data   = learndash_course_get_children_of_step( $course_id, $post->ID, 'sfwd-quiz' );
		} elseif ( $post->post_type == 'sfwd-topic' ) {
			$students_count = learndash_course_grid_count_students( $course_id );
			$quizzes_data   = learndash_course_get_children_of_step( $course_id, $post->ID, 'sfwd-quiz' );
		}
	}

	$ribbon_text = get_post_meta( $post->ID, '_learndash_course_grid_custom_ribbon_text', true );
	$ribbon_text = ! empty( $ribbon_text ) ? $ribbon_text : '';

	$description = get_post_meta( $post->ID, '_learndash_course_grid_short_description', true );

	$description = wpautop( do_shortcode( htmlspecialchars_decode( $description ) ) );

	if ( ! empty( $atts['description_char_max'] ) ) {
		$description = strlen( $description ) > $atts['description_char_max'] ? mb_strimwidth( $description, 0, $atts['description_char_max'] ) . '...' : $description;
	}

	$video = get_post_meta( $post->ID, '_learndash_course_grid_enable_video_preview', true );

	$embed_code = get_post_meta( $post->ID, '_learndash_course_grid_video_embed_code', true );

	// Retrieve oembed HTML if a URL is provided.
	if ( preg_match( '/^http/', $embed_code ) ) {
		$embed_code = wp_oembed_get(
			$embed_code,
			array(
				'height' => 600,
				'width'  => 400,
			)
		);
	}

	if ( defined( 'LEARNDASH_VERSION' ) && learndash_course_grid_is_learndash_post_type( $post->post_type ) ) {
		$button_link = learndash_get_step_permalink( $post->ID, $course_id );
	} else {
		$button_link = get_permalink( $post->ID );
	}

	/**
	 * Filters the button link URL for course grid items.
	 *
	 * @since 4.21.4
	 *
	 * @param string $button_link The URL that the button will link to.
	 * @param int    $post_id     The ID of the current post.
	 *
	 * @return string Modified button link URL.
	 */
	$button_link = apply_filters( 'learndash_course_grid_custom_button_link', $button_link, $post->ID );

	$duration = Utilities::get_duration( $post->ID, 'output' );

	switch ( $post->post_type ) {
		case 'sfwd-courses':
			$cat_taxonomies = [ 'category', 'ld_course_category' ];
			$tag_taxonomies = [ 'post_tag', 'ld_course_tag' ];
			break;

		case 'sfwd-lessons':
			$cat_taxonomies = [ 'category', 'ld_lesson_category' ];
			$tag_taxonomies = [ 'post_tag', 'ld_lesson_tag' ];
			break;

		case 'sfwd-topic':
			$cat_taxonomies = [ 'category', 'ld_topic_category' ];
			$tag_taxonomies = [ 'post_tag', 'ld_topic_tag' ];
			break;

		case 'sfwd-quiz':
			$cat_taxonomies = [ 'category', 'ld_quiz_category' ];
			$tag_taxonomies = [ 'post_tag', 'ld_quiz_tag' ];
			break;

		case 'sfwd-question':
			$cat_taxonomies = [ 'category', 'ld_question_category' ];
			$tag_taxonomies = [ 'post_tag', 'ld_question_tag' ];
			break;

		case 'groups':
			$cat_taxonomies = [ 'category', 'ld_group_category' ];
			$tag_taxonomies = [ 'post_tag', 'ld_group_tag' ];
			break;

		default:
			$cat_taxonomies = [ 'category' ];
			$tag_taxonomies = [ 'post_tag' ];
			break;
	}

	if ( isset( $cat_taxonomies ) ) {
		$categories_from_db = get_terms(
			[
				'taxonomy'   => $cat_taxonomies,
				'object_ids' => $post->ID,
				'orderby'    => 'name',
				'fields'     => 'names',
			]
		);

		if ( ! is_wp_error( $categories_from_db ) && is_array( $categories_from_db ) ) {
			$categories = implode( ', ', $categories_from_db );
		}
	}

	if ( isset( $tag_taxonomies ) ) {
		$tags_from_db = get_terms(
			[
				'taxonomy'   => $tag_taxonomies,
				'object_ids' => $post->ID,
				'orderby'    => 'name',
				'fields'     => 'names',
			]
		);

		if ( ! is_wp_error( $tags_from_db ) && is_array( $tags_from_db ) ) {
			$tags = implode( ', ', $tags_from_db );
		}
	}

	if ( defined( 'LEARNDASH_VERSION' ) ) {
		if ( isset( $students_count ) ) {
			$students = [
				'count' => $students_count,
			];
		}

		if ( isset( $lessons_data ) && is_array( $lessons_data ) ) {
			$lessons = [
				'count' => count( $lessons_data ),
				'list'  => $lessons_data,
			];
		}

		if ( isset( $topics_data ) && is_array( $topics_data ) ) {
			$topics = [
				'count' => count( $topics_data ),
				'list'  => $topics_data,
			];
		}

		if ( isset( $quizzes_data ) && is_array( $quizzes_data ) ) {
			$quizzes = [
				'count' => count( $quizzes_data ),
				'list'  => $quizzes_data,
			];
		}
	}

	if ( function_exists( 'bbpress' ) && defined( 'LEARNDASH_BBPRESS_VERSION' ) ) {
		global $wpdb;

		$meta_key = false;
		if ( $post->post_type == 'sfwd-courses' ) {
			$meta_key = '_ld_associated_courses';
		} elseif ( $post->post_type == 'groups' ) {
			$meta_key = '_ld_associated_groups';
		}

		$forums_data = false;
		if ( $meta_key ) {
			$forums_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = %s AND meta_value LIKE '%%:%d;%%'", $meta_key, $post->ID ) );
		}

		if ( is_array( $forums_data ) && ! empty( $forums_data ) ) {
			$forums = [
				'count' => count( $forums_data ),
				'list'  => [],
			];

			foreach ( $forums_data as $forum ) {
				$forums['list'][] = $forum->post_id;
			}
		}
	}

	$currency = '';

	if ( function_exists( 'learndash_get_currency_symbol' ) ) {
		$currency = learndash_get_currency_symbol();
	} else {
		$paypal_enabled  = class_exists( 'LearnDash_Settings_Section' ) ? LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_PayPal', 'enabled' ) : null;
		$paypal_currency = class_exists( 'LearnDash_Settings_Section' ) ? LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_PayPal', 'paypal_currency' ) : null;

		$stripe_settings = get_option( 'learndash_stripe_settings', [] );

		if ( class_exists( 'NumberFormatter' ) ) {
			if ( $paypal_enabled == 'on' && ! empty( $paypal_currency ) ) {
				$locale        = get_locale();
				$number_format = new NumberFormatter( $locale . '@currency=' . $paypal_currency, NumberFormatter::CURRENCY );
				$currency      = $number_format->getSymbol( NumberFormatter::CURRENCY_SYMBOL );
			} elseif ( isset( $stripe_settings['enabled'] ) && $stripe_settings['enabled'] == 'yes' && ! empty( $stripe_settings['currency'] ) ) {
				$locale        = get_locale();
				$number_format = new NumberFormatter( $locale . '@currency=' . $stripe_settings['currency'], NumberFormatter::CURRENCY );
				$currency      = $number_format->getSymbol( NumberFormatter::CURRENCY_SYMBOL );
			}
		}
	}

	/**
	 * Filters the currency symbol used in course grid display.
	 *
	 * @since 4.21.4
	 *
	 * @param string $currency Currency symbol.
	 * @param int    $post_id  Post ID.
	 *
	 * @return string Modified currency symbol.
	 */
	$currency = apply_filters( 'learndash_course_grid_currency', $currency, $post->ID );

	$price      = '';
	$price_type = '';
	$price_text = '';
	if ( function_exists( 'learndash_get_course_price' ) && function_exists( 'learndash_get_group_price' ) ) {
		if ( $post->post_type == 'sfwd-courses' ) {
			$price_args = learndash_get_course_price( $post->ID );
		} elseif ( $post->post_type == 'groups' ) {
			$price_args = learndash_get_group_price( $post->ID );
		}

		if ( ! empty( $price_args ) ) {
			$price      = $price_args['price'];
			$price_type = $price_args['type'];

			/**
			 * Filters the format for displaying price text in the course grid.
			 *
			 * Default format is '{currency}{price}' where placeholders are replaced with actual values.
			 *
			 * @since 4.21.4
			 *
			 * @param string $format The price format string with placeholders.
			 *
			 * @return string Modified price format.
			 */
			$price_format = apply_filters( 'learndash_course_grid_price_text_format', '{currency}{price}' );

			if ( is_numeric( $price ) && ! empty( $price ) ) {
				$price = Utilities::format_price( $price, 'output' );

				$price_text = str_replace( [ '{currency}', '{price}' ], [ $currency, $price ], $price_format );
			} elseif ( is_string( $price ) && ! empty( $price ) ) {
				if ( preg_match( '/(((\d+),?)*(\d+)(\.?\d+)?)/', $price ) ) {
					$price = Utilities::format_price( $price, 'output' );

					$price_text = str_replace( [ '{currency}', '{price}' ], [ $currency, $price ], $price_format );
				} else {
					$price_text = $price;
				}
			} elseif ( empty( $price ) ) {
				if ( 'closed' === $price_type || 'open' === $price_type ) {
					$price_text = '';
				} else {
					$price_text = __( 'Free', 'learndash' );
				}
			}

			if ( $price_type == 'subscribe' ) {
				$trial_price = $price_args['trial_price'] ?? false;

				$trial_duration = isset( $price_args['trial_interval'] ) && isset( $price_args['trial_frequency'] ) ? $price_args['trial_interval'] . ' ' . $price_args['trial_frequency'] : false;

				if ( isset( $price_args['interval'] ) && isset( $price_args['frequency'] ) ) {
					$subscription_duration = $price_args['interval'] > 1 ? $price_args['interval'] . ' ' . $price_args['frequency'] : $price_args['frequency'];

					$price_text = sprintf( '%s%s', $price_text, $subscription_duration ? '/' . $subscription_duration : '' );
				}
			}
		}
	}

	if ( empty( $price ) ) {
		$price = __( 'Free', 'learndash' );
	}

	/**
	 * Filters the course or group price value.
	 *
	 * @since 4.21.4
	 *
	 * @param mixed $price   The price value (can be numeric or string).
	 * @param int   $post_id The ID of the current post.
	 *
	 * @return mixed Modified price value.
	 */
	$price = apply_filters( 'learndash_course_grid_price', $price, $post->ID );

	/**
	 * Filters the reviews for the course grid.
	 *
	 * @since 4.21.4
	 *
	 * @param mixed $reviews The reviews data for the course.
	 * @param int   $post_id The ID of the current post.
	 *
	 * @return mixed Modified reviews data.
	 */
	$reviews = apply_filters( 'learndash_course_grid_reviews', $reviews, $post->ID );

	$user_object = get_user_by( 'ID', $post->post_author );

	/**
	 * Filters the author data for the course grid.
	 *
	 * @since 4.21.4
	 *
	 * @param array $author    The author data containing name and avatar.
	 * @param int   $post_id   The ID of the current post.
	 * @param int   $author_id The ID of the post author.
	 *
	 * @return array Modified author data.
	 */
	$author = apply_filters(
		'learndash_course_grid_author',
		[
			'name'   => $user_object->display_name,
			'avatar' => get_avatar_url( $post->post_author ),
		],
		$post->ID,
		$post->post_author
	);

	$is_completed = false;
	$ribbon_class = 'ribbon';

	$has_access = false;
	if ( defined( 'LEARNDASH_VERSION' ) ) {
		if ( $post->post_type == 'sfwd-courses' ) {
			$has_access   = sfwd_lms_has_access( $post->ID, $user_id );
			$is_completed = learndash_course_completed( $user_id, $post->ID );
		} elseif ( $post->post_type == 'groups' ) {
			$has_access   = learndash_is_user_in_group( $user_id, $post->ID );
			$is_completed = learndash_get_user_group_completed_timestamp( $post->ID, $user_id );
		} elseif ( $post->post_type == 'sfwd-lessons' ) {
			$parent_course_id = learndash_get_course_id( $post->ID );
			$has_access       = is_user_logged_in() && ! empty( $parent_course_id ) ? sfwd_lms_has_access( $post->ID, $user_id ) : false;
			$is_completed     = learndash_is_lesson_complete( $user_id, $post->ID, $parent_course_id );
		} elseif ( $post->post_type == 'sfwd-topic' ) {
			$parent_course_id = learndash_get_course_id( $post->ID );
			$has_access       = is_user_logged_in() && ! empty( $parent_course_id ) ? sfwd_lms_has_access( $post->ID, $user_id ) : false;
			$is_completed     = learndash_is_topic_complete( $user_id, $post->ID, $parent_course_id );
		}

		if ( in_array( $post->post_type, [ 'sfwd-courses', 'groups' ] ) ) {
			if ( $price_type != 'open' && empty( $ribbon_text ) ) {
				if ( $has_access && ! $is_completed ) {
					$ribbon_class .= ' enrolled';
					$ribbon_text   = __( 'Enrolled', 'learndash' );
				} elseif ( $has_access && $is_completed ) {
					$ribbon_class .= ' completed';
					$ribbon_text   = __( 'Completed', 'learndash' );
				} elseif ( ! empty( $price ) ) {
					$ribbon_text = $price_text;
				} elseif ( $price_type == 'free' ) {
					$ribbon_class .= ' free';
					$ribbon_text   = __( 'Free', 'learndash' );
				} else {
					$ribbon_class .= ' available';
					$ribbon_text   = __( 'Available', 'learndash' );
				}
			} elseif ( $price_type == 'open' && empty( $ribbon_text ) ) {
				if ( is_user_logged_in() && ! $is_completed ) {
					$ribbon_class .= ' enrolled';
					$ribbon_text   = __( 'Enrolled', 'learndash' );
				} elseif ( is_user_logged_in() && $is_completed ) {
					$ribbon_class .= ' completed';
					$ribbon_text   = __( 'Completed', 'learndash' );
				} else {
					$ribbon_class .= ' free';
					$ribbon_text   = __( 'Free', 'learndash' );
				}
			}
		} elseif ( in_array( $post->post_type, [ 'sfwd-lessons', 'sfwd-topic' ] ) ) {
			$has_started = false;

			if ( $post->post_type == 'sfwd-lessons' ) {
				$activity_type = 'lesson';
			} elseif ( $post->post_type == 'sfwd-topic' ) {
				$activity_type = 'topic';
			}

			$activity = learndash_get_user_activity(
				[
					'course_id'     => $course_id,
					'user_id'       => $user_id,
					'post_id'       => $post->ID,
					'activity_type' => $activity_type,
				]
			);

			if ( ! empty( $activity ) ) {
				if ( ! empty( $activity->activity_started ) && ! $activity->activity_completed ) {
					$has_started = true;
				}
			}

			if ( $has_access && $is_completed ) {
				$ribbon_class .= ' enrolled completed';
				$ribbon_text   = __( 'Completed', 'learndash' );
			} elseif ( $has_access && ! $has_started ) {
				$ribbon_class .= ' enrolled not-started';
				$ribbon_text   = __( 'Not started', 'learndash' );
			} elseif ( $has_access && $has_started ) {
				$ribbon_class .= ' enrolled in-progress';
				$ribbon_text   = __( 'In progress', 'learndash' );
			} elseif ( learndash_is_sample( $post->ID ) ) {
				$ribbon_class .= ' free';
				$ribbon_text   = __( 'Free', 'learndash' );
			} else {
				$ribbon_class .= ' not-enrolled';
				$ribbon_text   = '';
			}
		}
	}

	$button_text = get_post_meta( $post->ID, '_learndash_course_grid_custom_button_text', true );

	if ( empty( $button_text ) ) {
		if ( in_array( $post->post_type, [ 'sfwd-courses', 'groups' ] ) && ! $has_access ) {
			$button_text = __( 'Enroll Now', 'learndash' );
		} elseif ( in_array( $post->post_type, [ 'sfwd-courses', 'groups' ] ) && $has_access ) {
			$button_text = __( 'Continue Study', 'learndash' );
		} else {
			$button_text = __( 'See More', 'learndash' );
		}
	}

	/**
	 * Filters the button text for course grid items.
	 *
	 * @since 4.21.4
	 *
	 * @param string $button_text The text displayed on the course grid button.
	 * @param int    $post_id     The ID of the current post.
	 *
	 * @return string Modified button text.
	 */
	$button_text = apply_filters( 'learndash_course_grid_custom_button_text', $button_text, $post->ID );

	/**
	 * Filters the individual course ribbon text.
	 *
	 * @since 4.21.4
	 *
	 * @param string $ribbon_text Returned ribbon text
	 * @param int    $post_id     Course ID
	 * @param string $price_type  Course price type
	 *
	 * @return string Ribbon text.
	 */
	$ribbon_text = apply_filters( 'learndash_course_grid_ribbon_text', $ribbon_text, $post->ID, $price_type );

	/**
	 * Filters the individual course ribbon class names.
	 *
	 * @since 4.21.4
	 *
	 * @param string $ribbon_class   Returned class names
	 * @param int    $post_id        Course ID
	 * @param array  $course_options Course's options
	 *
	 * @return string Ribbon class names.
	 */
	$ribbon_class = apply_filters( 'learndash_course_grid_ribbon_class', $ribbon_class, $post->ID, $course_options );

	$post_atts = [
		'user_id'               => $user_id,
		'post_type'             => $post->post_type,
		'title'                 => $post->post_title,
		'description'           => $description,
		'price'                 => $price,
		'currency'              => $currency,
		'price_text'            => $price_text,
		'video'                 => $video,
		'video_embed_code'      => $embed_code,
		'button_link'           => $button_link,
		'button_text'           => $button_text,
		'ribbon_text'           => $ribbon_text,
		'ribbon_class'          => $ribbon_class,
		'students'              => $students,
		'duration'              => $duration,
		'trial_price'           => $trial_price,
		'trial_duration'        => $trial_duration,
		'subscription_duration' => $subscription_duration,
		'reviews'               => $reviews,
		'categories'            => $categories,
		'tags'                  => $tags,
		'author'                => $author,
		'total_steps'           => $total_steps,
		'courses'               => $courses,
		'lessons'               => $lessons,
		'topics'                => $topics,
		'quizzes'               => $quizzes,
		'forums'                => $forums,
	];

	/**
	 * Filters the post attributes for course grid template.
	 *
	 * @since 4.21.4
	 *
	 * @param array<string, mixed>  $post_atts Array of post attributes prepared for template display.
	 * @param WP_Post               $post      The post object.
	 * @param array<string, mixed>  $atts      The shortcode attributes.
	 *
	 * @return array<string, mixed> Modified post attributes.
	 */
	return apply_filters( 'learndash_course_grid_template_post_attributes', $post_atts, $post, $atts );
}

/**
 * Loads the appropriate course grid card template.
 *
 * This function retrieves and includes the template file based on the card layout
 * specified in the attributes. It prepares post attributes for display in the template.
 *
 * @since 4.21.4
 *
 * @param array   $atts The shortcode attributes, must contain 'card' key for template selection.
 * @param WP_Post $post The post object for which to load the card template.
 *
 * @return void
 */
function learndash_course_grid_load_card_template( $atts, $post ) {
	$template = Utilities::get_card_layout( $atts['card'] );

	if ( $template ) {
		$post_atts = learndash_course_grid_prepare_template_post_attributes( $post, $atts );

		/**
		 * Filters the shortcode attributes to use for the course grid card template.
		 *
		 * Some arguments are set at at the shortcode-level, but may be desirable to modify at the card-level.
		 *
		 * @since 4.22.0
		 *
		 * @param array<string, mixed> $atts The shortcode attributes.
		 * @param WP_Post              $post The post object.
		 * @param array<string, mixed> $post_atts The post attributes.
		 *
		 * @return array<string, mixed> Modified shortcode attributes.
		 */
		$atts = apply_filters(
			'learndash_course_grid_template_post_shortcode_attributes',
			$atts,
			$post,
			$post_atts
		);

		extract( $post_atts );

		include $template;
	}
}

/**
 * Loads required CSS and JS resources for Course Grid.
 *
 * This function enqueues the necessary skin assets from the Course Grid module.
 * It also handles loading legacy v1 skin assets for backward compatibility.
 *
 * @since 4.21.4
 *
 * @return void
 */
function learndash_course_grid_load_resources() {
	Course_Grid::instance()->skins->enqueue_skin_assets();

	// Check and load legacy v1 skin assets
	$skin       = 'legacy-v1';
	$style_file = LearnDash\Course_Grid\Utilities::get_skin_style( $skin );

	if ( $style_file ) {
		wp_enqueue_style( 'learndash-course-grid-skin-' . $skin, $style_file, [], LEARNDASH_VERSION );
	}
}

/**
 * Checks if a post type is a LearnDash post type.
 *
 * This function determines whether a given post type belongs to LearnDash by
 * comparing it against a list of known LearnDash post types.
 *
 * @since 4.21.4
 *
 * @param string $post_type The post type to check.
 *
 * @return boolean True if it's a LearnDash post type, false otherwise.
 */
function learndash_course_grid_is_learndash_post_type( $post_type ) {
	if ( in_array( $post_type, [ 'groups', 'sfwd-courses', 'sfwd-lesson', 'sfwd-topic', 'sfwd-quiz', 'sfwd-certificates', 'ld-exam' ] ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Loads inline script data for localization.
 *
 * This function adds translation data to the wp-i18n script to enable
 * localization of JavaScript strings in the Course Grid module. It ensures
 * the data is only loaded once per page load.
 *
 * @since 4.21.4
 *
 * @return void
 */
function learndash_course_grid_load_inline_script_locale_data() {
	static $loaded = false;

	if ( false === $loaded ) {
		$loaded = true;

		// Get locale data
		$translations = get_translations_for_domain( 'learndash' );

		$locale_data = array(
			'' => array(
				'domain' => 'learndash',
				'lang'   => is_admin() ? get_user_locale() : get_locale(),
			),
		);

		if ( ! empty( $translations->headers['Plural-Forms'] ) ) {
			$locale_data['']['plural_forms'] = $translations->headers['Plural-Forms'];
		}

		foreach ( $translations->entries as $msgid => $entry ) {
			$locale_data[ $msgid ] = $entry->translations;
		}

		// Add inline locale data
		wp_add_inline_script(
			'wp-i18n',
			'wp.i18n.setLocaleData( ' . wp_json_encode( $locale_data ) . ', "learndash" );'
		);
	}
}

/**
 * Counts enrolled students for a LearnDash course or group.
 *
 * This function retrieves the number of users enrolled in a specific course or group.
 * It uses caching to improve performance for repeated calls.
 *
 * @since 4.21.4
 *
 * @param int $post_id The ID of the course or group.
 *
 * @return int|bool The number of enrolled students, or false if not a valid course/group.
 */
function learndash_course_grid_count_students( $post_id ) {
	$count = wp_cache_get( $post_id . '_students_count', 'ld_cg' );

	if ( false === $count ) {
		global $wpdb;

		$post_type = get_post_type( $post_id );
		if ( 'sfwd-courses' === $post_type ) {
			$meta_key = 'course_%d_access_from';
		} elseif ( 'groups' === $post_type ) {
			$meta_key = 'learndash_group_users_%d';
		} else {
			return false;
		}

		$query = "SELECT COUNT(*) FROM {$wpdb->base_prefix}usermeta WHERE meta_key = '{$meta_key}'";
		$query = $wpdb->prepare( $query, $post_id );
		$count = $wpdb->get_var( $query );

		wp_cache_set( $post_id . '_students_count', $count, 'ld_cg', HOUR_IN_SECONDS );
	}

	return $count;
}
