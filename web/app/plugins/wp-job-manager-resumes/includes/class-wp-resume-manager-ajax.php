<?php
/**
 * File containing the WP_Resume_Manager_Ajax.
 *
 * @package wp-job-manager-resumes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Resume_Manager_Ajax class.
 */
class WP_Resume_Manager_Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_nopriv_resume_manager_get_resumes', [ $this, 'get_resumes' ] );
		add_action( 'wp_ajax_resume_manager_get_resumes', [ $this, 'get_resumes' ] );
	}

	/**
	 * Get resumes via ajax
	 */
	public function get_resumes() {
		global $wpdb;

		ob_start();

		$search_location   = isset( $_POST['search_location'] ) ? sanitize_text_field( wp_unslash( $_POST['search_location'] ) ) : '';
		$search_keywords   = isset( $_POST['search_keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['search_keywords'] ) ) : '';
		$search_categories = isset( $_POST['search_categories'] ) ? $_POST['search_categories'] : '';
		$search_skills     = isset( $_POST['search_skills'] ) ? sanitize_text_field( wp_unslash( $_POST['search_skills'] ) ) : '';

		if ( is_array( $search_categories ) ) {
			$search_categories = array_map( 'sanitize_text_field', array_map( 'wp_unslash', $search_categories ) );
		} else {
			$search_categories = [ sanitize_text_field( stripslashes( $search_categories ) ), 0 ];
		}

		$search_categories = array_filter( $search_categories );

		$args = [
			'search_location'   => $search_location,
			'search_keywords'   => $search_keywords,
			'search_categories' => $search_categories,
			'search_skills'     => $search_skills,
			'orderby'           => sanitize_text_field( $_POST['orderby'] ),
			'order'             => sanitize_text_field( $_POST['order'] ),
			'posts_per_page'    => absint( $_POST['per_page'] ),
		];

		if ( ! empty( $_POST['exclude_ids'] ) ) {
			$args['post__not_in'] = array_map( 'absint', $_POST['exclude_ids'] );
		}

		if ( ! in_array( $_POST['orderby'], [ 'rand', 'rand_featured' ], true ) ) {
			$args['offset'] = ( absint( $_POST['page'] ) - 1 ) * absint( $_POST['per_page'] );
		}

		if ( isset( $_POST['featured'] ) && ( $_POST['featured'] === 'true' || $_POST['featured'] === 'false' ) ) {
			$args['featured'] = $_POST['featured'] === 'true' ? true : false;
		}

		$resumes = get_resumes( apply_filters( 'resume_manager_get_resumes_args', $args ) );

		$result                  = [];
		$result['found_resumes'] = false;
		$result['post_ids']      = [];

		if ( $resumes->have_posts() ) :
			$result['found_resumes'] = true; ?>

			<?php
			while ( $resumes->have_posts() ) :
				$resumes->the_post();
				$result['post_ids'][] = the_resume_id();
				?>

				<?php get_job_manager_template_part( 'content', 'resume', 'wp-job-manager-resumes', RESUME_MANAGER_PLUGIN_DIR . '/templates/' ); ?>

			<?php endwhile; ?>

		<?php else : ?>

			<li class="no_resumes_found"><?php _e( 'No resumes found matching your selection.', 'wp-job-manager-resumes' ); ?></li>

			<?php
		endif;

		$result['html'] = ob_get_clean();

		// Generate 'showing' text
		if ( $search_keywords || $search_location || $search_categories || apply_filters( 'resume_manager_get_resumes_custom_filter', false ) ) {

			$showing_categories = [];

			if ( $search_categories ) {
				foreach ( $search_categories as $category ) {
					if ( ! is_numeric( $category ) ) {
						$category_object = get_term_by( 'slug', $category, 'resume_category' );
					}
					if ( is_numeric( $category ) || is_wp_error( $category_object ) || ! $category_object ) {
						$category_object = get_term_by( 'id', $category, 'resume_category' );
					}
					if ( ! is_wp_error( $category_object ) ) {
						$showing_categories[] = $category_object->name;
					}
				}
			}

			if ( $search_keywords ) {
				$showing_resumes = sprintf( __( 'Showing &ldquo;%1$s&rdquo; %2$sresumes', 'wp-job-manager-resumes' ), $search_keywords, implode( ', ', $showing_categories ) );
			} else {
				$showing_resumes = sprintf( __( 'Showing all %sresumes', 'wp-job-manager-resumes' ), implode( ', ', $showing_categories ) . ' ' );
			}

			$showing_location = $search_location ? sprintf( ' ' . __( 'located in &ldquo;%s&rdquo;', 'wp-job-manager-resumes' ), $search_location ) : '';

			$result['showing'] = apply_filters( 'resume_manager_get_resumes_custom_filter_text', $showing_resumes . $showing_location );

		} else {
			$result['showing'] = '';
		}

		// Generate RSS link
		$result['showing_links'] = resume_manager_get_filtered_links(
			[
				'search_location'   => $search_location,
				'search_categories' => $search_categories,
				'search_keywords'   => $search_keywords,
			]
		);

		// Generate pagination
		if ( isset( $_POST['show_pagination'] ) && $_POST['show_pagination'] === 'true' ) {
			$result['pagination'] = get_job_listing_pagination( $resumes->max_num_pages, absint( $_POST['page'] ) );
		}

		$result['max_num_pages'] = $resumes->max_num_pages;

		/**
		 * Filters the results of the resume listing Ajax query to be sent back to the client.
		 *
		 * @since 1.18.1
		 *
		 * @param array $result {
		 *    Package of the query results along with meta information.
		 *
		 *    @type bool   $found_resumes   Whether or not jobs were found in the query.
		 *    @type string $showing         Description of the search query and results.
		 *    @type int    $max_num_pages   Number of pages in the search result.
		 *    @type string $html            HTML representation of the search results
		 *    @type array  $pagination      Pagination links to use for stepping through filter results.
		 * }
		 * @param WP_Query $resumes Query result for resumes.
		 */
		wp_send_json( apply_filters( 'resume_manager_get_listings_result', $result, $resumes ) );
	}
}

new WP_Resume_Manager_Ajax();
