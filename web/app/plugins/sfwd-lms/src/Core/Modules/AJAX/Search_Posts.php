<?php
/**
 * Search Posts AJAX module.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AJAX;

use LDLMS_Post_Types;
use LearnDash\Core\Modules\AJAX\DTO;
use LearnDash\Core\Utilities;
use WP_Post;
use WP_Query;

/**
 * Search_Posts AJAX class.
 *
 * @since 4.8.0
 */
class Search_Posts extends Request_Handler {
	/**
	 * AJAX action.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public static $action = 'ld_ajax_select2_get_posts';

	/**
	 * Default posts per page.
	 *
	 * @since 4.8.0
	 *
	 * @var int
	 */
	protected static $posts_per_page = 10;

	/**
	 * Default paged value.
	 *
	 * @since 4.8.0
	 *
	 * @var int
	 */
	protected static $paged = 1;

	/**
	 * Request.
	 *
	 * @since 4.8.0
	 *
	 * @var DTO\Request\Search_Posts
	 */
	public $request;

	/**
	 * Request results.
	 *
	 * @since 4.8.0
	 *
	 * @var WP_Post[]
	 */
	protected $results;

	/**
	 * Response.
	 *
	 * @since 4.8.0
	 *
	 * @var DTO\Response\Select2
	 */
	protected $response;

	/**
	 * Set up and build `request` property.
	 *
	 * @since 4.8.0
	 *
	 * @return void
	 */
	protected function set_up_request(): void {
		$args = Utilities\Sanitize::array(
			$_GET, // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is done in Request_Handler::verify_nonce().
			function( $value ) {
				return ! empty( $value )
					? sanitize_text_field( $value )
					: null;
			}
		);

		$this->request = $this->build_request(
			DTO\Request\Search_Posts::create( $args )
		);
	}

	/**
	 * Set up necessary request parameters according to passed arguments.
	 *
	 * @since 4.8.0
	 *
	 * @param DTO\Request\Search_Posts $args Request parameters DTO.
	 *
	 * @return DTO\Request\Search_Posts
	 */
	protected function build_request( DTO\Request\Search_Posts $args ): DTO\Request\Search_Posts {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verification is done in Request_Handler::verify_nonce().

		$args->parent_ids = $this->get_parent_ids( $args );
		$args->has_parent = ! empty( $args->parent_ids );

		$args->posts_per_page = ! empty( $_REQUEST['per_page'] )
			? absint( $_REQUEST['per_page'] )
			: static::$posts_per_page;

		$args->paged = ! empty( $_REQUEST['page'] )
			? absint( $_REQUEST['page'] )
			: static::$paged;

		$args->label = learndash_get_custom_label(
			learndash_get_post_type_key( $args->post_type )
		);

		return $args;

		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Process request using specified parameters and build `results` property.
	 *
	 * @since 4.8.0
	 *
	 * @return void
	 */
	protected function process(): void {
		/**
		 * By default WP_Query search in post title, content, and excerpt.
		 * This filter modify it to only search in post title.
		 */
		add_filter(
			'posts_search',
			function( $search, $wp_query ) {
				if (
					isset( $wp_query->query['ld_action'] )
					&& $wp_query->query['ld_action'] === static::$action
				) {
					$search = preg_replace( '/(OR)\s.*?post_(excerpt|content)\sLIKE\s.*?\)/', '', $search );
				}

				return $search;
			},
			10,
			2
		);

		$this->results = $this->query_posts();
	}

	/**
	 * Get parent ID according to certain criteria.
	 *
	 * @since 4.8.0
	 *
	 * @param DTO\Request\Search_Posts $args Request arguments.
	 *
	 * @return array<int>
	 */
	protected function get_parent_ids( DTO\Request\Search_Posts $args ): array {
		if ( ! empty( $args->topic_ids ) ) {
			$parent_ids = $args->topic_ids;
		} elseif ( ! empty( $args->lesson_ids ) ) {
			$parent_ids = $args->lesson_ids;
		} elseif ( ! empty( $args->course_ids ) ) {
			$parent_ids = $args->course_ids;
		} else {
			$parent_ids = [];
		}

		/**
		 * For quiz post type, since it can also be a direct child of lesson
		 * or course, we need to take `lesson_ids` and `course_ids` into account
		 * when getting `parent_ids` if `parent_ids` or `topic_ids` in this case
		 * contains -1 ("All").
		 */

		if ( $args->post_type === learndash_get_post_type_slug( LDLMS_Post_Types::QUIZ ) ) {
			/**
			 * We set `parent_ids` value to `lesson_ids` when original `parent_ids`
			 * contains -1 value which indicates "All" objects and `lesson_ids` has
			 * at least a value which is not -1 ("All").
			 */

			if (
				in_array( -1, $parent_ids, true )
				&& (
					! in_array( -1, $args->lesson_ids, true )
					&& ! empty( $args->lesson_ids )
				)
			) {
				$parent_ids = $args->lesson_ids;
			} elseif (
				/**
				 * We set `parent_ids` value to `course_ids` when original
				 * `parent_ids` contains -1 value which indicates "All" objects and
				 * `course_ids` has at least a value which is not -1 ("All").
				 */

				in_array( -1, $parent_ids, true )
				&& (
					! in_array( -1, $args->course_ids, true )
					&& ! empty( $args->course_ids )
				)
			) {
				$parent_ids = $args->course_ids;
			}
		}

		return $parent_ids;
	}

	/**
	 * Query posts based on the request parameters.
	 *
	 * @since 4.8.0
	 *
	 * @return WP_Post[]
	 */
	protected function query_posts(): array {
		$posts      = [];
		$posts_args = [
			'post_type'        => $this->request->post_type,
			's'                => $this->request->keyword,
			'posts_per_page'   => $this->request->posts_per_page,
			'paged'            => $this->request->paged,
			'post_status'      => 'any',
			'orderby'          => 'relevance',
			'order'            => 'ASC',
			'suppress_filters' => false,
			'ld_action'        => static::$action,
		];

		/**
		 * Get `$post_ids` value that will be used in WP_Query `post__in`
		 * parameter if request parameters have `parent_ids` and `course_ids`
		 * parameters.
		 *
		 * We need this logic because we accept multiple course_ids and
		 * parent_ids values to make it possible for users to get children of
		 * multiple courses and parents. The `course_ids` and `parent_ids` can also
		 * have -1 value which indicates "All" object.
		 */

		$post_ids = [];

		if (
			! empty( $this->request->parent_ids )
			&& ! empty( $this->request->course_ids )
		) {
			foreach ( $this->request->parent_ids as $p_id ) {
				// If a specific `parent_ids` is set to "All", we can skip it.

				if ( $p_id === -1 ) {
					continue;
				}

				foreach ( $this->request->course_ids as $c_id ) {
					// If a specific `course_ids` is set to "All" and `parent_ids` value contains "All", we can reset `$post_ids` value and skip the whole logic to generate `$post_ids`.

					if (
						$c_id === -1
						&& (
							! empty( $parent_ids )
							&& in_array( -1, $parent_ids, true )
						)
					) {
						$post_ids = [];
						break 2;
					}

					if ( intval( $p_id ) === intval( $c_id ) ) {
						$post_ids = array_merge( learndash_course_get_steps_by_type( $c_id, $this->request->post_type ), $post_ids );
					} else {
						$post_ids = array_merge( learndash_course_get_children_of_step( $c_id, $p_id, $this->request->post_type ), $post_ids );
					}
				}
			}
		}

		// We can't have empty array in `post__in` parameter because it would return empty result.
		$posts_args['post__in'] = ! empty( $post_ids ) ? $post_ids : null;

		if (
			(
				$this->request->has_parent
				&& ! empty( $post_ids )
			)
			|| ! $this->request->has_parent
		) {
			$query = new WP_Query( $posts_args );
			/**
			 * Retrieved posts.
			 *
			 * @var WP_Post[]
			 */
			$posts = $query->get_posts();

			$this->request->query = $query;
		}

		return $posts;
	}

	/**
	 * Prepare response.
	 *
	 * @since 4.8.0
	 *
	 * @return void
	 */
	protected function prepare_response(): void {
		$items = [];

		foreach ( $this->results as $post ) {
			$items[] = [
				'id'   => $post->ID,
				'text' => $post->post_title . '  (ID: ' . $post->ID . ')',
			];
		}

		$this->response = DTO\Response\Select2::create(
			[
				'results'    => $items,
				'pagination' => [
					'more' => ! empty( $this->request->query->max_num_pages )
					&& $this->request->paged < $this->request->query->max_num_pages,
				],
			]
		);
	}
}
