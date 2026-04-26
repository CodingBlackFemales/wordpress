<?php
/**
 * Reviews REST API.
 *
 * TODO: Refactor to use a namespace and autoload.
 *
 * @since 4.25.1
 *
 * @package LearnDash\Course_Reviews
 */

defined( 'ABSPATH' ) || die();

use LearnDash\Core\Utilities\Cast;

/**
 * Class LearnDash_Course_Reviews_REST.
 *
 * Shows the Sets up the REST API Endpoints used in the plugin.
 *
 * @since 4.25.1
 */
class LearnDash_Course_Reviews_REST {
	/**
	 * LearnDash_Course_Reviews_REST constructor.
	 *
	 * @since 4.25.1
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'add_endpoints' ) );
	}

	/**
	 * Add API Endpoints for our application.
	 *
	 * @since 4.25.1
	 *
	 * @return void
	 */
	public function add_endpoints() {
		register_rest_route(
			'learndashCourseReviews/v1',
			'/addReview/(?P<course_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_review' ),
				'permission_callback' => function ( $request ) {
					if ( ! $request instanceof WP_REST_Request ) {
						return false;
					}

					return learndash_course_reviews_user_has_started_course(
						Cast::to_int(
							$request->get_param( 'course_id' )
						)
					)
					&& self::permission_callback_filterable( $request, 'add_review' );
				},
			)
		);
	}

	/**
	 * Add a Review for a Course.
	 *
	 * @since 4.25.1
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Request Object.
	 *
	 * @return WP_REST_Response
	 */
	public function add_review( $request ) {
		if ( ! $request->get_param( 'rating' ) ) {
			return self::error_message(
				__( 'A Rating must be included in your review', 'learndash' )
			);
		}

		if ( ! $request->get_param( 'review_title' ) ) {
			return self::error_message(
				__( 'A Title must be included in your review', 'learndash' )
			);
		}

		$result = learndash_course_reviews_add_review(
			array(
				'comment_post_ID' => $request->get_param( 'course_id' ),
				'rating'          => $request->get_param( 'rating' ),
				'review_title'    => $request->get_param( 'review_title' ),
				'comment_content' => ( $request->get_param( 'review_content' ) ) ? $request->get_param( 'review_content' ) : '',
			)
		);

		if ( is_wp_error( $result ) ) {
			return self::error_message(
				implode( ';', $result->get_error_messages() )
			);
		}

		return new WP_REST_Response(
			array(
				'comment_id' => $result,
			)
		);
	}

	/**
	 * Outputs an Error Message as HTML.
	 *
	 * @since 4.25.1
	 *
	 * @param string $message     Error Message. Defaults to empty string.
	 * @param string $notice_type Notice type. Defaults to "alert".
	 * @param int    $status_code Status code. Defaults to 500 (same as what WP_Error would choose).
	 *
	 * @return WP_REST_Response
	 */
	public static function error_message( string $message = '', string $notice_type = 'alert', int $status_code = 500 ): WP_REST_Response {
		ob_start();

		learndash_course_reviews_locate_template(
			'notice.php',
			array(
				'message' => $message,
				'type'    => $notice_type,
			)
		);

		return new WP_REST_Response(
			array(
				'html' => ob_get_clean(),
			),
			$status_code
		);
	}

	/**
	 * This allows 3rd party integrations to force API calls to fail under certain conditions.
	 *
	 * This will force a 403, so it should only be used when you're trying to prevent abuse by someone who is being sneaky by attempting to run API calls for something they do not have access to.
	 *
	 * @since 4.25.1
	 *
	 * @param WP_REST_Request<array{mixed}> $request Request object.
	 * @param string                        $method  Method Name.
	 *
	 * @return bool Whether this API call should be allowed or not.
	 */
	public static function permission_callback_filterable( $request, string $method ): bool {
		/**
		 * Filters the permission callback for a request.
		 *
		 * @since 4.25.1
		 *
		 * @param bool                          $bool    Allow/Disallow.
		 * @param WP_REST_Request<array{mixed}> $request Current Request Object.
		 *
		 * @return bool Allow/Disallow.
		 */
		return apply_filters(
			"learndash_course_reviews_permission_callback_$method",
			true,
			$request
		);
	}
}

$instance = new LearnDash_Course_Reviews_REST();
