<?php
/**
 * File containing the global functions.
 *
 * @package wp-job-manager-resumes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'get_resumes' ) ) :
	/**
	 * Queries job listings with certain criteria and returns them
	 *
	 * @access public
	 * @return void
	 */
	function get_resumes( $args = [] ) {
		global $resume_manager_keyword;

		$args = wp_parse_args(
			$args,
			[
				'search_location'   => '',
				'search_keywords'   => '',
				'search_categories' => [],
				'search_skills'     => '',
				'offset'            => '',
				'posts_per_page'    => '-1',
				'orderby'           => 'date',
				'order'             => 'DESC',
				'featured'          => null,
				'fields'            => 'all',
			]
		);

		$query_args = [
			'post_type'              => 'resume',
			'post_status'            => 'publish',
			'ignore_sticky_posts'    => 1,
			'offset'                 => absint( $args['offset'] ),
			'posts_per_page'         => intval( $args['posts_per_page'] ),
			'orderby'                => $args['orderby'],
			'order'                  => $args['order'],
			'tax_query'              => [],
			'meta_query'             => [],
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
			'cache_results'          => false,
			'fields'                 => $args['fields'],
		];

		if ( $args['posts_per_page'] < 0 ) {
			$query_args['no_found_rows'] = true;
		}

		if ( ! empty( $args['search_location'] ) ) {
			$location_meta_keys = [ 'geolocation_formatted_address', '_candidate_location', 'geolocation_state_long' ];
			$location_search    = [ 'relation' => 'OR' ];
			foreach ( $location_meta_keys as $meta_key ) {
				$location_search[] = [
					'key'     => $meta_key,
					'value'   => $args['search_location'],
					'compare' => 'like',
				];
			}
			$query_args['meta_query'][] = $location_search;
		}

		if ( ! empty( $args['search_skills'] ) ) {
			$skills_search = [
				'key'     => '_resume_skills',
				'value'   => $args['search_skills'],
				'compare' => 'like',
			];

			$query_args['meta_query'][] = $skills_search;
		}

		if ( ! is_null( $args['featured'] ) ) {
			$query_args['meta_query'][] = [
				'key'     => '_featured',
				'value'   => '1',
				'compare' => $args['featured'] ? '=' : '!=',
			];
		}

		if ( ! empty( $args['search_categories'] ) ) {
			$field                     = is_numeric( $args['search_categories'][0] ) ? 'term_id' : 'slug';
			$operator                  = 'all' === get_option( 'resume_manager_category_filter_type', 'all' ) && count( $args['search_categories'] ) > 1 ? 'AND' : 'IN';
			$query_args['tax_query'][] = [
				'taxonomy'         => 'resume_category',
				'field'            => $field,
				'terms'            => array_values( $args['search_categories'] ),
				'include_children' => $operator !== 'AND',
				'operator'         => $operator,
			];
		}

		if ( 'featured' === $args['orderby'] ) {
			$query_args['orderby'] = [
				'menu_order' => 'ASC',
				'date'       => 'DESC',
				'ID'         => 'DESC',
			];
		}

		if ( 'rand_featured' === $args['orderby'] ) {
			$query_args['orderby'] = [
				'menu_order' => 'ASC',
				'rand'       => 'ASC',
			];
		}

		if ( ! empty( $args['post__not_in'] ) ) {
			$query_args['post__not_in'] = $args['post__not_in'];
		}

		if ( $resume_manager_keyword = sanitize_text_field( $args['search_keywords'] ) ) {
			$query_args['s'] = $resume_manager_keyword;
			add_filter( 'posts_search', 'get_resumes_keyword_search' );
		}

		$query_args = apply_filters( 'resume_manager_get_resumes', $query_args, $args );

		if ( empty( $query_args['meta_query'] ) ) {
			unset( $query_args['meta_query'] );
		}

		if ( empty( $query_args['tax_query'] ) ) {
			unset( $query_args['tax_query'] );
		}

		// Filter args
		$query_args = apply_filters( 'get_resumes_query_args', $query_args, $args );

		// Generate hash
		$to_hash         = defined( 'ICL_LANGUAGE_CODE' ) ? json_encode( $query_args ) . ICL_LANGUAGE_CODE : json_encode( $query_args );
		$query_args_hash = 'jm_' . md5( $to_hash ) . WP_Job_Manager_Cache_Helper::get_transient_version( 'get_resume_listings' );

		do_action( 'before_get_resumes', $query_args, $args );
		$cached_query = true;
		if ( false === ( $result = get_transient( $query_args_hash ) ) ) {
			$cached_query = false;
			$result       = new WP_Query( $query_args );
			set_transient( $query_args_hash, $result, DAY_IN_SECONDS );
		}
		if ( $cached_query ) {
			// random order is cached so shuffle them
			if ( 'rand_featured' === $args['orderby'] ) {
				usort( $result->posts, '_wpjm_resumes_shuffle_featured_post_results_helper' );
			} elseif ( 'rand' === $args['orderby'] ) {
				shuffle( $result->posts );
			}
		}
		do_action( 'after_get_resumes', $query_args, $args );

		remove_filter( 'posts_search', 'get_resumes_keyword_search' );

		return $result;
	}
endif;

if ( ! function_exists( '_wpjm_resumes_shuffle_featured_post_results_helper' ) ) :
	/**
	 * Helper function to maintain featured status when shuffling results.
	 *
	 * @param WP_Post $a
	 * @param WP_Post $b
	 *
	 * @return bool
	 */
	function _wpjm_resumes_shuffle_featured_post_results_helper( $a, $b ) {
		if ( -1 === $a->menu_order || -1 === $b->menu_order ) {
			// Left is featured
			if ( 0 === $b->menu_order ) {
				return -1;
			}
			// Right is featured
			if ( 0 === $a->menu_order ) {
				return 1;
			}
		}
		return rand( -1, 1 );
	}
endif;

if ( ! function_exists( 'get_resumes_keyword_search' ) ) :
	/**
	 * Join and where query for keywords
	 *
	 * @param array $search Search query args.
	 * @return array
	 */
	function get_resumes_keyword_search( $search ) {
		global $wpdb, $resume_manager_keyword;

		// Searchable Meta Keys: set to empty to search all meta keys.
		$searchable_meta_keys = [
			'_candidate_name',
			'_candidate_title',
			'_candidate_location',
		];
		/**
		 * Filters meta fields used during search.
		 *
		 * @since 1.18.4
		 *
		 * @param array $meta_keys Meta key used in search.
		 */
		$searchable_meta_keys = apply_filters( 'resume_manager_searchable_meta_keys', $searchable_meta_keys );

		// Set Search DB Conditions.
		$conditions = [];

		/**
		 * Filters whether to search post meta.
		 *
		 * @since 1.18.4
		 *
		 * @param bool $search_meta Switch to search meta or not.
		 */
		if ( apply_filters( 'resume_manager_search_post_meta', true ) ) {

			// Only selected meta keys.
			if ( $searchable_meta_keys ) {
				$conditions[] = "{$wpdb->posts}.ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ( '" . implode( "','", array_map( 'esc_sql', $searchable_meta_keys ) ) . "' ) AND meta_value LIKE '%" . esc_sql( $resume_manager_keyword ) . "%' )";
			} else {
				// No meta keys defined, search all post meta value.
				$conditions[] = "{$wpdb->posts}.ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%" . esc_sql( $resume_manager_keyword ) . "%' )";
			}
		}

		// Search taxonomy.
		$conditions[] = "{$wpdb->posts}.ID IN ( SELECT object_id FROM {$wpdb->term_relationships} AS tr LEFT JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id LEFT JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id WHERE t.name LIKE '%" . esc_sql( $resume_manager_keyword ) . "%' )";

		/**
		 * Filters the conditions to use when querying resume listings. Resulting array is joined with OR statements.
		 *
		 * @since 1.18.4
		 *
		 * @param array  $conditions          Conditions to join by OR when querying resume listings.
		 * @param string $job_manager_keyword Search query.
		 */
		$conditions = apply_filters( 'resume_manager_search_conditions', $conditions, $resume_manager_keyword );
		if ( empty( $conditions ) ) {
			return $search;
		}

		$conditions_str = implode( ' OR ', $conditions );

		if ( ! empty( $search ) ) {
			$search = preg_replace( '/^ AND /', '', $search );
			$search = " AND ( {$search} OR ( {$conditions_str} ) )";
		} else {
			$search = " AND ( {$conditions_str} )";
		}

		return $search;
	}
endif;

if ( ! function_exists( 'order_featured_resume' ) ) :
	/**
	 * WP Core doens't let us change the sort direction for invidual orderby params - http://core.trac.wordpress.org/ticket/17065
	 *
	 * @access public
	 * @param array $args
	 * @return array
	 */
	function order_featured_resume( $args ) {
		global $wpdb;

		$args['orderby'] = "$wpdb->postmeta.meta_value+0 DESC, $wpdb->posts.post_title ASC";

		return $args;
	}
endif;

if ( ! function_exists( 'get_resume_share_link' ) ) :
	/**
	 * Generates a sharing link which allows someone to view the resume directly (even if permissions do not usually allow it)
	 *
	 * @access public
	 * @return array
	 */
	function get_resume_share_link( $resume_id ) {
		if ( ! $key = get_post_meta( $resume_id, 'share_link_key', true ) ) {
			$key = wp_generate_password( 32, false );
			update_post_meta( $resume_id, 'share_link_key', $key );
		}

		return add_query_arg( 'key', $key, get_permalink( $resume_id ) );
	}
endif;

if ( ! function_exists( 'get_resume_categories' ) ) :
	/**
	 * Outputs a form to submit a new job to the site from the frontend.
	 *
	 * @access public
	 * @return array
	 */
	function get_resume_categories() {
		if ( ! get_option( 'resume_manager_enable_categories' ) ) {
			return [];
		}

		return get_terms(
			'resume_category',
			[
				'orderby'    => 'name',
				'order'      => 'ASC',
				'hide_empty' => false,
			]
		);
	}
endif;

if ( ! function_exists( 'resume_manager_get_filtered_links' ) ) :
	/**
	 * Shows links after filtering resumes
	 */
	function resume_manager_get_filtered_links( $args = [] ) {

		$links = apply_filters(
			'resume_manager_resume_filters_showing_resumes_links',
			[
				'reset' => [
					'name' => __( 'Reset', 'wp-job-manager-resumes' ),
					'url'  => '#',
				],
			],
			$args
		);

		$return = '';

		foreach ( $links as $key => $link ) {
			$return .= '<a href="' . esc_url( $link['url'] ) . '" class="' . esc_attr( $key ) . '">' . $link['name'] . '</a>';
		}

		return $return;
	}
endif;

/**
 * True if an the user can edit a resume.
 *
 * @param $resume_id
 *
 * @return bool
 */
function resume_manager_user_can_edit_resume( $resume_id ) {
	$can_edit = true;

	if ( ! $resume_id || ! is_user_logged_in() ) {
		$can_edit = false;
		if ( $resume_id
			 && ! resume_manager_user_requires_account()
			 && isset( $_COOKIE[ 'wp-job-manager-submitting-resume-key-' . $resume_id ] )
			 && $_COOKIE[ 'wp-job-manager-submitting-resume-key-' . $resume_id ] === get_post_meta( $resume_id, '_submitting_key', true )
		) {
			$can_edit = true;
		}
	} else {

		$resume = get_post( $resume_id );

		if ( ! $resume || ( absint( $resume->post_author ) !== get_current_user_id() && ! current_user_can( 'edit_post', $resume_id ) ) ) {
			$can_edit = false;
		}
	}

	return apply_filters( 'resume_manager_user_can_edit_resume', $can_edit, $resume_id );
}

/**
 * Checks if users are allowed to edit published resumes.
 *
 * @since 1.18.0
 *
 * @return bool
 */
function resume_manager_user_can_edit_published_submissions() {
	$can_edit_published_submissions = in_array( get_option( 'resume_manager_user_edit_published_submissions' ), [ 'yes', 'yes_moderated' ], true );

	/**
	 * Override the setting for allowing a user to edit published resumes.
	 *
	 * @since 1.18.0
	 *
	 * @param bool $can_edit_published_submissions
	 */
	return apply_filters( 'resume_manager_user_can_edit_published_submissions', $can_edit_published_submissions );
}

/**
 * Checks if moderation is required when users edit published resumes.
 *
 * @since 1.18.0
 *
 * @return bool
 */
function resume_manager_published_submission_edits_require_moderation() {
	$require_moderation = 'yes_moderated' === get_option( 'resume_manager_user_edit_published_submissions' );

	/**
	 * Override the setting for user edits to published resumes requiring moderation.
	 *
	 * @since 1.18.0
	 *
	 * @param bool $require_moderation True if moderation is required before making edits public.
	 */
	return apply_filters( 'resume_manager_published_submission_edits_require_moderation', $require_moderation );
}

/**
 * Checks if users are allowed to edit reesumes that are pending approval.
 *
 * @since 1.18.0
 * @return bool
 */
function resume_manager_user_can_edit_pending_submissions() {
	return apply_filters( 'resume_manager_user_can_edit_pending_submissions', 1 === intval( get_option( 'resume_manager_user_can_edit_pending_submissions' ) ) );
}

/**
 * True if an the user can browse resumes.
 *
 * @return bool
 */
function resume_manager_user_can_browse_resumes() {
	$can_browse = true;
	$caps       = array_filter( array_map( 'trim', array_map( 'strtolower', explode( ',', get_option( 'resume_manager_browse_resume_capability' ) ) ) ) );

	if ( $caps ) {
		$can_browse = false;
		foreach ( $caps as $cap ) {
			if ( current_user_can( $cap ) ) {
				$can_browse = true;
				break;
			}
		}
	}

	return apply_filters( 'resume_manager_user_can_browse_resumes', $can_browse );
}

/**
 * True if an the user can view the full resume name.
 *
 * @return bool
 */
function resume_manager_user_can_view_resume_name( $resume_id ) {
	$can_view = true;
	$resume   = get_post( $resume_id );
	$caps     = array_filter( array_map( 'trim', array_map( 'strtolower', explode( ',', get_option( 'resume_manager_view_name_capability' ) ) ) ) );

	// Allow previews
	if ( $resume->post_status === 'preview' ) {
		return true;
	}

	if ( $caps ) {
		$can_view = false;
		foreach ( $caps as $cap ) {
			if ( current_user_can( $cap ) ) {
				$can_view = true;
				break;
			}
		}
	}

	if ( $resume->post_author > 0 && $resume->post_author == get_current_user_id() ) {
		$can_view = true;
	}

	if ( ( $key = get_post_meta( $resume_id, 'share_link_key', true ) ) && ! empty( $_GET['key'] ) && $key == $_GET['key'] ) {
		$can_view = true;
	}

	return apply_filters( 'resume_manager_user_can_view_resume_name', $can_view, $resume_id );
}

/**
 * Checks to see if the standard password setup email should be used.
 *
 * @since 1.18.0
 *
 * @return bool True if they are to use standard email, false to allow user to set password at first job creation.
 */
function resume_manager_use_standard_password_setup_email() {
	$use_standard_password_setup_email = true;

	// If username is being automatically generated, force them to send password setup email.
	if ( ! resume_manager_generate_username_from_email() ) {
		$use_standard_password_setup_email = get_option( 'resume_manager_use_standard_password_setup_email' ) == 1 ? true : false;
	}

	/**
	 * Allows an override of the setting for if a password should be auto-generated for new users.
	 *
	 * @since 1.18.0
	 *
	 * @param bool $use_standard_password_setup_email True if a standard account setup email should be sent.
	 */
	return apply_filters( 'resume_manager_use_standard_password_setup_email', $use_standard_password_setup_email );
}

/**
 * Check if the option to discourage resume search indexing is enabled.
 *
 * @since 1.16.1
 *
 * @return bool
 */
function resume_manager_discourage_resume_search_indexing() {
	/**
	 * Allows overriding the option to discourage search indexing.
	 *
	 * @since 1.16.1
	 *
	 * @param bool $discourage_search_indexing
	 */
	return apply_filters( 'resume_manager_discourage_resume_search_indexing', 1 == get_option( 'resume_manager_discourage_resume_search_indexing' ) );
}

/**
 * True if an the user can view a resume.
 *
 * @return bool
 */
function resume_manager_user_can_view_resume( $resume_id ) {
	$can_view = true;
	$resume   = get_post( $resume_id );

	// Allow previews
	if ( $resume->post_status === 'preview' ) {
		return true;
	}

	$caps = array_filter( array_map( 'trim', array_map( 'strtolower', explode( ',', get_option( 'resume_manager_view_resume_capability' ) ) ) ) );

	if ( $caps ) {
		$can_view = false;
		foreach ( $caps as $cap ) {
			if ( current_user_can( $cap ) ) {
				$can_view = true;
				break;
			}
		}
	}

	if ( $resume->post_status === 'expired' ) {
		$can_view = false;
	}

	if ( $resume->post_author > 0 && $resume->post_author == get_current_user_id() ) {
		$can_view = true;
	}

	if ( ( $key = get_post_meta( $resume_id, 'share_link_key', true ) ) && ! empty( $_GET['key'] ) && $key == $_GET['key'] ) {
		$can_view = true;
	}

	return apply_filters( 'resume_manager_user_can_view_resume', $can_view, $resume_id );
}

/**
 * True if an the user can view a resume.
 *
 * @return bool
 */
function resume_manager_user_can_view_contact_details( $resume_id ) {
	$can_view = true;
	$resume   = get_post( $resume_id );
	$caps     = array_filter( array_map( 'trim', array_map( 'strtolower', explode( ',', get_option( 'resume_manager_contact_resume_capability' ) ) ) ) );

	if ( $caps ) {
		$can_view = false;
		foreach ( $caps as $cap ) {
			if ( current_user_can( $cap ) ) {
				$can_view = true;
				break;
			}
		}
	}

	if ( $resume->post_author > 0 && $resume->post_author == get_current_user_id() ) {
		$can_view = true;
	}

	if ( ( $key = get_post_meta( $resume_id, 'share_link_key', true ) ) && ! empty( $_GET['key'] ) && $key == $_GET['key'] ) {
		$can_view = true;
	}

	return apply_filters( 'resume_manager_user_can_view_contact_details', $can_view, $resume_id );
}

if ( ! function_exists( 'get_resume_post_statuses' ) ) :
	/**
	 * Get post statuses used for resumes
	 *
	 * @access public
	 * @return array
	 */
	function get_resume_post_statuses() {
		return apply_filters(
			'resume_post_statuses',
			[
				'draft'           => _x( 'Draft', 'post status', 'wp-job-manager-resumes' ),
				'expired'         => _x( 'Expired', 'post status', 'wp-job-manager-resumes' ),
				'hidden'          => _x( 'Hidden', 'post status', 'wp-job-manager-resumes' ),
				'preview'         => _x( 'Preview', 'post status', 'wp-job-manager-resumes' ),
				'pending'         => _x( 'Pending approval', 'post status', 'wp-job-manager-resumes' ),
				'pending_payment' => _x( 'Pending payment', 'post status', 'wp-job-manager-resumes' ),
				'publish'         => _x( 'Published', 'post status', 'wp-job-manager-resumes' ),
			]
		);
	}
endif;

/**
 * Upload dir
 */
function resume_manager_upload_dir( $dir, $field ) {
	if ( 'resume_file' === $field ) {
		$dir = 'resumes/resume_files';
	}
	return $dir;
}
add_filter( 'job_manager_upload_dir', 'resume_manager_upload_dir', 10, 2 );

/**
 * Count user resumes
 *
 * @param  integer $user_id
 * @return int
 */
function resume_manager_count_user_resumes( $user_id = 0 ) {
	global $wpdb;

	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'resume' AND post_status IN ( 'publish', 'pending', 'expired', 'hidden' );", $user_id ) );
}

/**
 * Get the permalink of a page if set
 *
 * @param  string $page e.g. candidate_dashboard, submit_resume_form, resumes
 * @return string|bool
 */
function resume_manager_get_permalink( $page ) {
	$page_id = get_option( 'resume_manager_' . $page . '_page_id', false );
	if ( $page_id ) {
		return get_permalink( $page_id );
	} else {
		return false;
	}
}

/**
 * Calculate and return the resume expiry date
 *
 * @param  int $resume_id
 * @return string
 */
function calculate_resume_expiry( $resume_id ) {
	// Get duration from the product if set...
	$duration = get_post_meta( $resume_id, '_resume_duration', true );

	// ...otherwise use the global option
	if ( ! $duration ) {
		$duration = absint( get_option( 'resume_manager_submission_duration' ) );
	}

	if ( $duration ) {
		return date( 'Y-m-d', strtotime( "+{$duration} days", current_time( 'timestamp' ) ) );
	}

	return '';
}

/**
 * Checks if the visitor is currently on a WP Resume Manager page, resume, or taxonomy.
 *
 * @since 1.17.1
 *
 * @return bool
 */
function is_wp_resume_manager() {
	/**
	 * Filter the result of is_wp_resume_manager()
	 *
	 * @since 1.17.1
	 *
	 * @param bool $is_wp_resume_manager
	 */
	return apply_filters( 'is_wp_resume_manager', ( is_wp_resume_manager_page() || has_wp_resume_manager_shortcode() || is_wp_resume_manager_resume() || is_wp_resume_manager_taxonomy() ) );
}

/**
 * Checks if the visitor is currently on a WP Resume Manager page.
 *
 * @since 1.17.1
 *
 * @return bool
 */
function is_wp_resume_manager_page() {
	$is_wp_resume_manager_page = is_post_type_archive( 'resume' );

	if ( ! $is_wp_resume_manager_page ) {
		$wp_resume_manager_page_ids = array_filter(
			[
				get_option( 'resume_manager_submit_resume_form_page_id', false ),
				get_option( 'resume_manager_candidate_dashboard_page_id', false ),
				get_option( 'resume_manager_resumes_page_id', false ),
			]
		);

		/**
		 * Filters a list of all page IDs related to WP Resume Manager.
		 *
		 * @since 1.17.1
		 *
		 * @param int[] $wp_resume_manager_page_ids
		 */
		$wp_resume_manager_page_ids = array_unique( apply_filters( 'resume_manager_page_ids', $wp_resume_manager_page_ids ) );

		$is_wp_resume_manager_page = is_page( $wp_resume_manager_page_ids );
	}

	/**
	 * Filter the result of is_wp_resume_manager_page()
	 *
	 * @since 1.17.1
	 *
	 * @param bool $is_wp_resume_manager_page
	 */
	return apply_filters( 'is_wp_resume_manager_page', $is_wp_resume_manager_page );
}

/**
 * Checks if the provided content or the current single page or post has a WP Resume Manager shortcode.
 *
 * @param string|null       $content   Content to check. If not provided, it uses the current post content.
 * @param string|array|null $tag Check specifically for one or more shortcodes. If not provided, checks for any WP Resume Manager shortcode.
 *
 * @return bool
 */
function has_wp_resume_manager_shortcode( $content = null, $tag = null ) {
	global $post;

	$has_wp_resume_manager_shortcode = false;

	if ( null === $content && is_singular() && is_a( $post, 'WP_Post' ) ) {
		$content = $post->post_content;
	}

	if ( ! empty( $content ) ) {
		$wp_resume_manager_shortcodes = [ 'submit_resume_form', 'candidate_dashboard', 'resumes' ];
		/**
		 * Filters a list of all shortcodes associated with WP Resume Manager.
		 *
		 * @since 1.17.1
		 *
		 * @param string[] $wp_resume_manager_shortcodes
		 */
		$wp_resume_manager_shortcodes = array_unique( apply_filters( 'resume_manager_shortcodes', $wp_resume_manager_shortcodes ) );

		if ( null !== $tag ) {
			if ( ! is_array( $tag ) ) {
				$tag = [ $tag ];
			}
			$wp_resume_manager_shortcodes = array_intersect( $wp_resume_manager_shortcodes, $tag );
		}

		foreach ( $wp_resume_manager_shortcodes as $shortcode ) {
			if ( has_shortcode( $content, $shortcode ) ) {
				$has_wp_resume_manager_shortcode = true;
				break;
			}
		}
	}

	/**
	 * Filter the result of has_wp_resume_manager_shortcode()
	 *
	 * @since 1.17.1
	 *
	 * @param bool $has_wp_resume_manager_shortcode
	 */
	return apply_filters( 'has_wp_resume_manager_shortcode', $has_wp_resume_manager_shortcode );
}

/**
 * Checks if the current page is a job listing.
 *
 * @since 1.17.1
 *
 * @return bool
 */
function is_wp_resume_manager_resume() {
	return is_singular( [ 'resume' ] );
}

/**
 * Checks if the visitor is on a page for a WP Resume Manager taxonomy.
 *
 * @since 1.17.1
 *
 * @return bool
 */
function is_wp_resume_manager_taxonomy() {
	return is_tax( get_object_taxonomies( 'resume' ) );
}

/**
 * Whether to create attachments for files that are uploaded with a Resume.
 *
 * @since 1.17.1
 *
 * @return bool
 */
function resume_manager_attach_uploaded_files() {
	return apply_filters( 'resume_manager_attach_uploaded_files', false );
}
