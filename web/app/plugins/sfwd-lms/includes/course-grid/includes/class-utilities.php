<?php
/**
 * Utilities class.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Course_Grid;

use LDLMS_Post_Types;
use LearnDash\Core\Models\Course;
use LearnDash\Core\Models\Group;
use LearnDash\Core\Utilities\Cast;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Class Utilities.
 *
 * @since 4.21.4
 */
class Utilities {
	/**
	 * Gets a template path based on the provided file name.
	 *
	 * @since 4.21.4
	 *
	 * @param string $template Template file name, without extension.
	 *
	 * @return string|false Template path if found, false if not.
	 */
	public static function get_template( $template ) {
		$template_file = $template . '.php';

		$template = locate_template( 'learndash/course-grid/' . $template_file );

		$template_in_allowed_directory = (
			( is_string( realpath( $template ) ) && is_string( realpath( STYLESHEETPATH ) ) && 0 === strpos( realpath( $template ), realpath( STYLESHEETPATH ) ) )
			|| ( is_string( realpath( $template ) ) && is_string( realpath( TEMPLATEPATH ) ) && 0 === strpos( realpath( $template ), realpath( TEMPLATEPATH ) ) )
			|| ( is_string( realpath( $template ) ) && is_string( ABSPATH . WPINC . '/theme-compat/' ) && 0 === strpos( realpath( $template ), ABSPATH . WPINC . '/theme-compat/' ) )
		);

		if ( $template && $template_in_allowed_directory ) {
			return $template;
		} elseif ( file_exists( LEARNDASH_COURSE_GRID_PLUGIN_TEMPLATE_PATH . $template_file ) ) {
			return LEARNDASH_COURSE_GRID_PLUGIN_TEMPLATE_PATH . $template_file;
		} else {
			return false;
		}
	}

	/**
	 * Gets template URL from a file.
	 *
	 * @since 4.21.4
	 *
	 * @param string $template_file Template file.
	 *
	 * @return false|string
	 */
	public static function get_template_url( $template_file ) {
		$template = locate_template( 'learndash/course-grid/' . $template_file );

		$template_in_allowed_directory = (
			( is_string( realpath( $template ) ) && is_string( realpath( STYLESHEETPATH ) ) && 0 === strpos( realpath( $template ), realpath( STYLESHEETPATH ) ) )
			|| ( is_string( realpath( $template ) ) && is_string( realpath( TEMPLATEPATH ) ) && 0 === strpos( realpath( $template ), realpath( TEMPLATEPATH ) ) )
			|| ( is_string( realpath( $template ) ) && is_string( ABSPATH . WPINC . '/theme-compat/' ) && 0 === strpos( realpath( $template ), ABSPATH . WPINC . '/theme-compat/' ) )
		);

		if ( $template && $template_in_allowed_directory ) {
			return get_stylesheet_directory_uri() . '/learndash/course-grid/' . $template_file;
		} elseif ( file_exists( LEARNDASH_COURSE_GRID_PLUGIN_TEMPLATE_PATH . $template_file ) ) {
			return LEARNDASH_COURSE_GRID_PLUGIN_TEMPLATE_URL . $template_file;
		} else {
			return false;
		}
	}

	/**
	 * Gets the pagination template based on the specified type.
	 *
	 * @since 4.21.4
	 *
	 * @param string $type The pagination type to retrieve.
	 *
	 * @return string|false Template path if found, false if not.
	 */
	public static function get_pagination_template( $type ) {
		return self::get_template( 'pagination/' . $type );
	}

	/**
	 * Gets the URL for the pagination style CSS file.
	 *
	 * @since 4.21.4
	 *
	 * @return string|false Style URL if found, false if not.
	 */
	public static function get_pagination_style() {
		return self::get_template_url( 'pagination/style.css' );
	}

	/**
	 * Gets the URL for the pagination JavaScript file.
	 *
	 * @since 4.21.4
	 *
	 * @return string|false Script URL if found, false if not.
	 */
	public static function get_pagination_script() {
		return self::get_template_url( 'pagination/script.js' );
	}


	/**
	 * Gets the template path for a skin layout.
	 *
	 * @since 4.21.4
	 *
	 * @param string $skin The skin name.
	 *
	 * @return string|false Template path if found, false if not.
	 */
	public static function get_skin_layout( $skin ) {
		return self::get_template( 'skins/' . $skin . '/layout' );
	}

	/**
	 * Gets the template path for a skin item.
	 *
	 * @since 4.21.4
	 *
	 * @param string $skin The skin name.
	 *
	 * @return string|false Template path if found, false if not.
	 */
	public static function get_skin_item( $skin ) {
		return self::get_template( 'skins/' . $skin . '/item' );
	}

	/**
	 * Gets the template path for a card layout.
	 *
	 * @since 4.21.4
	 *
	 * @param string $card The card name.
	 *
	 * @return string|false Template path if found, false if not.
	 */
	public static function get_card_layout( $card ) {
		return self::get_template( 'cards/' . $card . '/layout' );
	}

	/**
	 * Gets the URL for the skin style CSS file.
	 *
	 * @since 4.21.4
	 *
	 * @param string $skin The skin name.
	 *
	 * @return string|false Style URL if found, false if not.
	 */
	public static function get_skin_style( $skin ) {
		return self::get_template_url( 'skins/' . $skin . '/style.css' );
	}

	/**
	 * Gets the URL for the skin JavaScript file.
	 *
	 * @since 4.21.4
	 *
	 * @param string $skin The skin name.
	 *
	 * @return string|false Script URL if found, false if not.
	 */
	public static function get_skin_script( $skin ) {
		return self::get_template_url( 'skins/' . $skin . '/script.js' );
	}

	/**
	 * Gets the URL for the card style CSS file.
	 *
	 * @since 4.21.4
	 *
	 * @param string $card The card name.
	 *
	 * @return string|false Style URL if found, false if not.
	 */
	public static function get_card_style( $card ) {
		return self::get_template_url( 'cards/' . $card . '/style.css' );
	}

	/**
	 * Gets the URL for the card JavaScript file.
	 *
	 * @since 4.21.4
	 *
	 * @param string $card The card name.
	 *
	 * @return string|false Script URL if found, false if not.
	 */
	public static function get_card_script( $card ) {
		return self::get_template_url( 'cards/' . $card . '/script.js' );
	}

	/**
	 * Parses a textual representation of taxonomies and their terms. Used by Shortcode Attributes.
	 *
	 * @since 4.21.4
	 *
	 * @param string $taxonomies Taxonomy data separated by semicolons, with the taxonomy name followed by a colon and terms separated by commas. Example: taxonomy1:term1,term2;taxonomy2:term3,term4.
	 *
	 * @return array<string,array{terms: array<string>}> Parsed taxonomy data.
	 */
	public static function parse_taxonomies( $taxonomies ) {
		$taxonomies = ! empty( $taxonomies ) ? array_filter( explode( ';', sanitize_text_field( $taxonomies ) ) ) : [];

		$results = [];
		foreach ( $taxonomies as $taxonomy_entry ) {
			$taxonomy_parts = explode( ':', $taxonomy_entry );

			if ( empty( $taxonomy_parts[0] ) || empty( $taxonomy_parts[1] ) ) {
				continue;
			}

			$taxonomy = trim( $taxonomy_parts[0] );
			$terms    = array_map( 'trim', explode( ',', $taxonomy_parts[1] ) );

			if ( ! empty( $taxonomy ) && ! empty( $terms ) ) {
				$results[ $taxonomy ] = [
					'terms' => $terms,
				];
			}
		}

		return $results;
	}

	/**
	 * Build Post Query Args to use based on the provided Args.
	 *
	 * @since 4.21.4
	 *
	 * @param array<string,mixed> $atts Post Query Args to start with.
	 *
	 * @return array<string,mixed> Modified Post Query Args.
	 */
	public static function build_posts_query_args( $atts = [] ) {
		if ( empty( $atts['per_page'] ) ) {
			$atts['per_page'] = -1;
		}

		$tax_query = [];

		$taxonomies = ! empty( $atts['taxonomies'] ) ? array_filter( explode( ';', sanitize_text_field( str_replace( '"', '', wp_unslash( $atts['taxonomies'] ) ) ) ) ) : [];

		foreach ( $taxonomies as $taxonomy_entry ) {
			$taxonomy_parts = explode( ':', $taxonomy_entry );

			if ( empty( $taxonomy_parts[0] ) || empty( $taxonomy_parts[1] ) ) {
				continue;
			}

			$taxonomy = trim( $taxonomy_parts[0] );
			$terms    = array_map( 'trim', explode( ',', $taxonomy_parts[1] ) );

			if ( ! empty( $taxonomy ) && ! empty( $terms ) ) {
				$tax_query[] = [
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $terms,
				];
			}
		}

		$tax_query['relation'] = 'OR';

		$post__in = null;
		if ( in_array( $atts['post_type'], [ 'sfwd-courses', 'groups' ] ) ) {
			$user_id  = get_current_user_id();
			$post_ids = [];

			if ( isset( $atts['enrollment_status'] ) && $atts['enrollment_status'] == 'enrolled' ) {
				$courses = learndash_user_get_enrolled_courses( $user_id );

				$group_ids      = learndash_get_users_group_ids( $user_id );
				$groups_courses = learndash_get_groups_courses_ids( $user_id, $group_ids );

				$course_ids = array_merge( $courses, $groups_courses );

				if ( $atts['post_type'] == 'sfwd-courses' ) {
					$post_ids = $course_ids;

					if ( isset( $atts['progress_status'] ) && ! empty( $atts['progress_status'] ) ) {
						$progress_status = [ strtoupper( $atts['progress_status'] ) ];

						$activity_query_args             = [
							'post_types'      => 'sfwd-courses',
							'activity_types'  => 'course',
							'activity_status' => $progress_status,
							'orderby_order'   => 'users.ID, posts.post_title',
							'date_format'     => 'F j, Y H:i:s',
							'per_page'        => '',
						];
						$activity_query_args['user_ids'] = [ $user_id ];
						$activity_query_args['post_ids'] = $post_ids;

						$user_courses_reports = learndash_reports_get_activity( $activity_query_args );

						$user_courses_ids = [];
						if ( ! empty( $user_courses_reports['results'] ) ) {
							foreach ( $user_courses_reports['results'] as $result ) {
								$user_courses_ids[] = absint( $result->post_id );
							}

							$post_ids = array_unique( $user_courses_ids );
						} else {
							// It means course with such progress status doesn't exist,
							// we return empty array
							$post_ids = [];
						}
					}
				} elseif ( $atts['post_type'] == 'groups' ) {
					$post_ids = $group_ids;
				}

				if ( empty( $post_ids ) ) {
					// Add literal 0 in an array because post__in param
					// ignores empty array
					$post_ids = [ 0 ];
				}
			} elseif ( isset( $atts['enrollment_status'] ) && $atts['enrollment_status'] == 'not-enrolled' ) {
				$price_types = [ 'open', 'free', 'paynow', 'subscribe', 'closed' ];

				$all_posts = [];
				foreach ( $price_types as $price_type ) {
					$post_ids_by_price_type = learndash_get_posts_by_price_type( $atts['post_type'], $price_type );
					$all_posts              = array_merge( $all_posts, $post_ids_by_price_type );
				}

				$courses = learndash_user_get_enrolled_courses( $user_id );

				$group_ids      = learndash_get_users_group_ids( $user_id );
				$groups_courses = learndash_get_groups_courses_ids( $user_id, $group_ids );

				$course_ids = array_merge( $courses, $groups_courses );

				if ( $atts['post_type'] == 'sfwd-courses' ) {
					$post_ids = array_diff( $all_posts, $course_ids );
				} elseif ( $atts['post_type'] == 'groups' ) {
					$post_ids = array_diff( $all_posts, $group_ids );
				}
			} elseif ( empty( $atts['enrollment_status'] ) ) {
				$price_types = [
					LEARNDASH_PRICE_TYPE_OPEN,
					LEARNDASH_PRICE_TYPE_FREE,
					LEARNDASH_PRICE_TYPE_PAYNOW,
					LEARNDASH_PRICE_TYPE_SUBSCRIBE,
					LEARNDASH_PRICE_TYPE_CLOSED,
				];

				$all_posts = [];
				foreach ( $price_types as $price_type ) {
					$all_posts = array_merge(
						$all_posts,
						learndash_get_posts_by_price_type(
							$atts['post_type'],
							$price_type
						)
					);
				}

				$post_ids = array_values( array_unique( $all_posts ) );
			}

			$group_id = Cast::to_int( $atts['group_id'] ?? 0 );

			if (
				$group_id > 0
				&& $atts['post_type'] === LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE )
			) {
				$group = Group::find( $group_id );

				if ( $group instanceof Group ) {
					$post_ids = array_values(
						array_intersect(
							$post_ids,
							array_map(
								static fn( Course $course ) => $course->get_id(),
								$group->get_courses()
							)
						)
					);
				}
			}

			// Add literal 0 in an array because post__in param ignores empty array.
			// TODO: Write test to check if it works with all enrollment statuses argument and without it.
			$post__in = ! empty( $post_ids ) ? $post_ids : [ 0 ];
		}

		/**
		 * Filters the query arguments for Course Grid queries.
		 *
		 * @since 4.21.4
		 *
		 * @param array<string,mixed> $query_args Query arguments for WP_Query.
		 * @param array<string,mixed> $atts       Attributes passed to the function.
		 * @param mixed              $filter     Additional filter parameter, defaults to null.
		 *
		 * @return array<string,mixed> Modified query arguments.
		 */
		$query_args = apply_filters(
			'learndash_course_grid_query_args',
			[
				'post_type'      => sanitize_text_field( $atts['post_type'] ),
				'posts_per_page' => intval( $atts['per_page'] ),
				'post_status'    => 'publish',
				'orderby'        => sanitize_text_field( $atts['orderby'] ?? 'ID' ),
				'order'          => sanitize_text_field( $atts['order'] ?? 'DESC' ),
				'tax_query'      => $tax_query,
				'post__in'       => $post__in,
			],
			$atts,
			$filter = null
		);

		return $query_args;
	}

	/**
	 * Gets all available post types for use in Course Grid.
	 *
	 * @since 4.21.4
	 *
	 * @return array<string, WP_Post_Type> Array of post type objects.
	 */
	public static function get_post_types() {
		$post_types = get_post_types(
			[
				'public' => true,
			],
			'objects'
		);

		$excluded_post_types = self::get_excluded_post_types();

		$returned_post_types = [];
		foreach ( $post_types as $slug => $post_type ) {
			if ( in_array( $slug, $excluded_post_types ) ) {
				continue;
			}

			$returned_post_types[ $slug ] = $post_type;
		}

		/**
		 * Filters the available post types for Course Grid.
		 *
		 * @since 4.21.4
		 *
		 * @param array<string, WP_Post_Type> $returned_post_types Array of post type objects.
		 *
		 * @return array<string, WP_Post_Type> Filtered array of post type objects.
		 */
		return apply_filters( 'learndash_course_grid_post_types', $returned_post_types );
	}

	/**
	 * Gets all available post type slugs for use in Course Grid.
	 *
	 * @since 4.21.4
	 *
	 * @return array<string> Array of post type slugs.
	 */
	public static function get_post_types_slugs() {
		$post_types      = self::get_post_types();
		$temp_post_types = [];
		foreach ( $post_types as $slug => $post_type ) {
			$temp_post_types[] = $slug;
		}
		$post_types = $temp_post_types;

		return $post_types;
	}

	/**
	 * Gets all available post types formatted for block editor.
	 *
	 * @since 4.21.4
	 *
	 * @return array<array{label: string, value: string}> Array of post type options for block editor.
	 */
	public static function get_post_types_for_block_editor() {
		$post_types = self::get_post_types();

		$returned_post_types = [];
		foreach ( $post_types as $slug => $post_type ) {
			$returned_post_types[] = [
				'label' => $post_type->label,
				'value' => $slug,
			];
		}

		/**
		 * Filters the post type options for the block editor.
		 *
		 * @since 4.21.4
		 *
		 * @param array<array{label: string, value: string}> $returned_post_types Array of post type options formatted for block editor.
		 *
		 * @return array<array{label: string, value: string}> Filtered array of post type options.
		 */
		return apply_filters( 'learndash_course_grid_block_editor_post_types', $returned_post_types );
	}

	/**
	 * Get a list of excluded Post Types for queries.
	 *
	 * @since 4.21.4
	 *
	 * @return array<string>
	 */
	public static function get_excluded_post_types() {
		/**
		 * Filters the list of excluded Post Types for queries.
		 *
		 * @since 4.21.4
		 *
		 * @param array<string> Excluded post types.
		 *
		 * @return Filtered excluded post types.
		 */
		return apply_filters(
			'learndash_course_grid_excluded_post_types',
			[
				'sfwd-transactions',
				'sfwd-essays',
				'sfwd-assignment',
				'sfwd-certificates',
				'attachment',
			]
		);
	}

	/**
	 * Gets image sizes formatted for block editor.
	 *
	 * @since 4.21.4
	 *
	 * @return array<array{label: string, value: string}> Array of image size options for block editor.
	 */
	public static function get_image_sizes_for_block_editor() {
		$sizes = get_intermediate_image_sizes();

		$image_sizes = [];
		foreach ( $sizes as $size ) {
			$image_sizes[] = [
				'label' => $size,
				'value' => $size,
			];
		}

		/**
		 * Filters image size options for the block editor.
		 *
		 * @since 4.21.4
		 *
		 * @param array<array{label: string, value: string}> $image_sizes Array of image size options formatted for block editor.
		 *
		 * @return array<array{label: string, value: string}> Filtered array of image size options.
		 */
		return apply_filters( 'learndash_course_grid_block_editor_image_sizes', $image_sizes );
	}

	/**
	 * Gets orderby options formatted for block editor.
	 *
	 * @since 4.21.4
	 *
	 * @return array<array{label: string, value: string}> Array of orderby options for block editor.
	 */
	public static function get_orderby_for_block_editor() {
		$orderby = [
			[
				'label' => __( 'ID', 'learndash' ),
				'value' => 'ID',
			],
			[
				'label' => __( 'Title', 'learndash' ),
				'value' => 'title',
			],
			[
				'label' => __( 'Published Date', 'learndash' ),
				'value' => 'date',
			],
			[
				'label' => __( 'Modified Date', 'learndash' ),
				'value' => 'modified',
			],
			[
				'label' => __( 'Author', 'learndash' ),
				'value' => 'author',
			],
			[
				'label' => __( 'Menu Order', 'learndash' ),
				'value' => 'menu_order',
			],
		];

		/**
		 * Filters the orderby options available for block editor.
		 *
		 * @since 4.21.4
		 *
		 * @param array<array{label: string, value: string}> $orderby Array of orderby options formatted for block editor.
		 *
		 * @return array<array{label: string, value: string}> Filtered array of orderby options.
		 */
		return apply_filters( 'learndash_course_grid_block_editor_orderby', $orderby );
	}

	/**
	 * Gets taxonomies formatted for block editor.
	 *
	 * @since 4.21.4
	 *
	 * @return array<array{label: string, value: string}> Array of taxonomy options for block editor.
	 */
	public static function get_taxonomies_for_block_editor() {
		$taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );

		$return = [];
		foreach ( $taxonomies as $tax ) {
			$return[] = [
				'label' => $tax->label,
				'value' => $tax->name,
			];
		}

		/**
		 * Filters the taxonomy options available for block editor.
		 *
		 * @since 4.21.4
		 *
		 * @param array<array{label: string, value: string}> $return Array of taxonomy options formatted for block editor.
		 *
		 * @return array<array{label: string, value: string}> Filtered array of taxonomy options.
		 */
		return apply_filters( 'learndash_course_grid_block_editor_taxonomies', $return );
	}

	/**
	 * Gets available pagination types formatted for block editor.
	 *
	 * @since 4.21.4
	 *
	 * @return array<array{label: string, value: string}> Array of pagination options for block editor.
	 */
	public static function get_paginations_for_block_editor() {
		/**
		 * Gets the available pagination types formatted for block editor.
		 *
		 * @since 4.21.4
		 *
		 * @return array<array{label: string, value: string}> Array of pagination options for block editor.
		 */
		return apply_filters(
			'learndash_course_grid_block_editor_paginations',
			[
				[
					'label' => __( 'Load More Button', 'learndash' ),
					'value' => 'button',
				],
				[
					'label' => __( 'Infinite Scrolling', 'learndash' ),
					'value' => 'infinite',
				],
				[
					'label' => __( 'Disable', 'learndash' ),
					'value' => 'false',
				],
			]
		);
	}

	/**
	 * Generates a random ID string for use in Course Grid elements.
	 *
	 * @since 4.21.4
	 *
	 * @return string A unique 16 character string prefixed with 'ld-cg-'.
	 */
	public static function generate_random_id() {
		return substr( uniqid( 'ld-cg-' ), 0, 16 );
	}

	/**
	 * Retrieves the amount of time it takes for the course to be completed.
	 *
	 * @since 4.21.4
	 *
	 * @param int    $post_id Post ID that the Course Grid saved on.
	 * @param string $format  Format to display the time in. Defaults to "plain", which just outputs the saved value. "Output" will format it as something like "1 hour 30 minutes".
	 *
	 * @return string Formatted duration.
	 */
	public static function get_duration( $post_id, $format = 'plain' ) {
		$duration = get_post_meta( $post_id, '_learndash_course_grid_duration', true );

		if ( ! empty( $duration ) && is_numeric( $duration ) ) {
			switch ( $format ) {
				case 'plain':
					$duration = $duration;
					break;

				case 'output':
					$duration_h = is_numeric( $duration ) ? floor( $duration / HOUR_IN_SECONDS ) : null;
					$duration_m = is_numeric( $duration ) ? floor( ( $duration % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS ) : null;
					$duration   = sprintf( _x( '%1$d h %2$d min', 'Duration, e.g. 1 hour 30 minutes', 'learndash' ), $duration_h, $duration_m );
					break;

				default:
					$duration = false;
					break;
			}
		}

		return $duration;
	}

	/**
	 * Formats the displayed price for a Course.
	 *
	 * @since 4.21.4
	 *
	 * @param string $price  Input price.
	 * @param string $format How to format the price. Defaults to "plain", which does nothing. "output" will format it as currency.
	 *
	 * @return string Formatted price.
	 */
	public static function format_price( $price, $format = 'plain' ) {
		if ( $format == 'output' ) {
			preg_match( '/(((\d+)[,\.]?)*(\d+)([\.,]?\d+)?)/', $price, $matches );

			$price = $matches[1];

			if ( ! empty( $price ) ) {
				$match_comma_decimal = preg_match( '/(?:\d+\.?)*\d+(,\d{1,2})$/', $price, $comma_matches );

				$match_dot_decimal = preg_match( '/(?:\d+,?)*\d+(\.\d{1,2})$/', $price, $dot_matches );

				if ( $match_comma_decimal ) {
					$has_decimal         = ! empty( $comma_matches[1] ) ? true : false;
					$thousands_separator = '.';
					$decimal_separator   = ',';
					$price               = str_replace( '.', '', $price );
					$price               = str_replace( ',', '.', $price );
				} else {
					$has_decimal         = ! empty( $dot_matches[1] ) ? true : false;
					$thousands_separator = ',';
					$decimal_separator   = '.';
					$price               = str_replace( ',', '', $price );
				}

				$price = floatval( $price );

				if ( $has_decimal ) {
					$price = number_format( $price, 2, $decimal_separator, $thousands_separator );
				} else {
					$price = number_format( $price, 0, $decimal_separator, $thousands_separator );
				}
			}

			return $price;
		}

		return $price;
	}

	/**
	 * Helper method similar to checked() that also has the ability to set the given input element as disabled.
	 *
	 * @since 4.21.4
	 *
	 * @param mixed        $checked  Value being searched for.
	 * @param array<mixed> $data     Value to search within.
	 * @param bool         $disabled Whether to mark the found value as disabled after marking it as checked.
	 *
	 * @return void
	 */
	public static function checked_array( $checked, $data, $disabled = false ) {
		$output = '';

		if ( is_array( $data ) && in_array( $checked, $data ) ) {
			$output .= 'checked="checked"';

			if ( $disabled ) {
				$output .= ' disabled="disabled"';
			}
		}

		echo $output;
	}

	/**
	 * Recursively extracts values for a specified key from a nested associative array.
	 *
	 * This method traverses a multi-dimensional associative array and collects all values
	 * associated with the specified key. If the value found is an array, it will be flattened
	 * and each item will be added to the result.
	 *
	 * @since 4.21.4
	 *
	 * @param array<mixed> $list         The array to search through.
	 * @param string       $find_key     The key to search for.
	 * @param array<mixed> $returned_list The array to store results in (passed by reference).
	 *
	 * @return array<mixed> The array of collected values.
	 */
	public static function associative_list_pluck( $list, $find_key, &$returned_list = [] ) {
		foreach ( $list as $key => $value ) {
			if ( $key === $find_key ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $sub_key => $sub_value ) {
						if ( isset( $sub_value[ $key ] ) ) {
							unset( $sub_value[ $key ] );
						}

						array_push( $returned_list, $sub_value );
					}
				} else {
					array_push( $returned_list, $value );
				}
			}

			if ( is_array( $value ) ) {
				$returned_list = self::associative_list_pluck( $value, $find_key, $returned_list );
			}
		}

		return $returned_list;
	}
}
