<?php
/**
 * REST API functionality.
 *
 * @since 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || die;

/**
 * Class LearnDash_ProPanel_REST
 *
 * API functionality.
 *
 * @since 4.17.0
 */
class LearnDash_ProPanel_REST {
	protected static $instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @since 4.17.0
	 *
	 * @return LearnDash_ProPanel_REST The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Override class function for 'this'.
	 *
	 * @return LearnDash_ProPanel_REST  Reference to the current instance.
	 */
	static function this() {
		return self::$instance;
	}

	/**
	 * LearnDash_ProPanel_REST constructor.
	 *
	 * @since 4.17.0
	 */
	function __construct() {
		add_action( 'rest_api_init', array( $this, 'add_endpoints' ) ); }

	/**
	 * Adds REST API endpoints
	 *
	 * @since 4.17.0
	 *
	 * @return void
	 */
	public function add_endpoints() {
		register_rest_route(
			'ld-propanel/v1',
			'/gutenberg-get-posts/',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'gutenberg_get_posts' ),
				'args'                => array(
					's'         => array(
						'required' => true,
					),
					'post_type' => array(
						'required' => true,
					),
					'offset'    => array(),
					'per_page'  => array(),
				),
				'permission_callback' => function ( $request ) {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_rest_route(
			'ld-propanel/v1',
			'/gutenberg-get-post/',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'gutenberg_get_post' ),
				'args'                => array(
					'id' => array(
						'required' => true,
					),
				),
				'permission_callback' => function ( $request ) {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_rest_route(
			'ld-propanel/v1',
			'/gutenberg-get-users/',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'gutenberg_get_users' ),
				'args'                => array(
					's'        => array(
						'required' => true,
					),
					'offset'   => array(),
					'per_page' => array(),
				),
				'permission_callback' => function ( $request ) {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_rest_route(
			'ld-propanel/v1',
			'/gutenberg-get-user/',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'gutenberg_get_user' ),
				'args'                => array(
					'id' => array(
						'required' => true,
					),
				),
				'permission_callback' => function ( $request ) {
					return current_user_can( 'edit_posts' );
				},
			)
		);  }

	/**
	 * Returns a list of Posts to be used within the Gutenberg Block
	 *
	 * @since 4.17.0
	 *
	 * @param WP_REST_Request $request   Request Object.
	 *
	 * @return WP_REST_Response            Response Object.
	 */
	public function gutenberg_get_posts( $request ) {
		try {
			$query_args = array(
				'post_type' => $request->get_param( 'post_type' ),
				's'         => $request->get_param( 's' ),
			);

			$total_query = new WP_Query(
				array_merge(
					$query_args,
					array(
						'posts_per_page' => -1,
						'fields'         => 'ids',
					)
				)
			);

			$total = count( $total_query->posts );

			$per_page = $request->get_param( 'per_page' );
			$per_page = ( $per_page ) ? (int) $per_page : 10;

			$offset = $request->get_param( 'offset' );
			$offset = ( $offset ) ? (int) $offset : 0;

			$results = new WP_Query(
				array_merge(
					$query_args,
					array(
						'offset'         => $offset,
						'posts_per_page' => $per_page,
						'orderby'        => 'title',
						'order'          => 'ASC',
					)
				)
			);

			$has_more = false;
			$options  = array();

			if ( $results->have_posts() ) {
				$posts = wp_list_pluck( $results->posts, 'post_title', 'ID' );

				// Converts the data to something that our Select field will tolerate.
				$options = array_values(
					array_map(
						function ( $key, $value ) {
							return array(
								'value' => $key,
								'label' => $value,
							);
						},
						array_keys( $posts ),
						$posts
					)
				);

				$processed_count = count( $results->posts ) + $offset;

				// Check if there are more to be found.
				if ( $processed_count < $total ) {
					$has_more = true;
				}
			}

			return new WP_REST_Response(
				array(
					'options' => $options,
					'hasMore' => $has_more,
					'total'   => (int) $total,
				)
			);
		} catch ( Exception $exception ) {
			return new WP_REST_Response(
				array(
					'options' => array(),
					'hasMore' => false,
				),
				500
			);
		}   }

	/**
	 * Returns a specific Post to be used when populating a default value
	 *
	 * @since 4.17.0
	 *
	 * @param WP_REST_Request $request Request Object.
	 *
	 * @return WP_REST_Response Response Object.
	 */
	public function gutenberg_get_post( $request ) {
		try {
			global $wpdb;

			$results = $wpdb->get_row( $wpdb->prepare( "SELECT id as value, post_title as label FROM {$wpdb->prefix}posts WHERE id = %d", $request->get_param( 'id' ) ) );

			return new WP_REST_Response(
				array(
					'post' => ( $results ) ? $results : array(),
				)
			);
		} catch ( Exception $exception ) {
			return new WP_REST_Response(
				array(
					'post' => array(),
				),
				500
			);
		}   }

	/**
	 * Returns a list of Users to be used within the Gutenberg Block
	 *
	 * @since 4.17.0
	 *
	 * @param WP_REST_Request $request Request Object.
	 *
	 * @return WP_REST_Response Response Object.
	 */
	public function gutenberg_get_users( $request ) {
		try {
			$query_args = array(
				'search' => "*{$request->get_param( 's' )}*",
			);

			$total_query = new WP_User_Query(
				array_merge(
					$query_args,
					array(
						'number' => -1,
						'fields' => 'ID',
					)
				)
			);

			$total = $total_query->get_total();

			$per_page = $request->get_param( 'per_page' );
			$per_page = ( $per_page ) ? (int) $per_page : 10;

			$offset = $request->get_param( 'offset' );
			$offset = ( $offset ) ? (int) $offset : 0;

			$current_query = new WP_User_Query(
				array_merge(
					$query_args,
					array(
						'offset' => $offset,
						'number' => $per_page,
					)
				)
			);

			$has_more = false;
			$options  = array();

			if ( $results = $current_query->get_results() ) {
				// Converts the data to something that our Select field will tolerate.
				$options = array_map(
					function ( $user ) {
						$user = new WP_User( $user->ID );

						$label = $user->user_login;

						if ( $user->user_email ) {
								$label .= " | {$user->user_email}";
						}

						return array(
							'value' => $user->ID,
							'label' => $label,
						);              },
					$results
				);

				$processed_count = count( $results ) + $offset;

				// Check if there are more to be found.
				if ( $processed_count < $total ) {
					$has_more = true;
				}
			}

			return new WP_REST_Response(
				array(
					'options' => $options,
					'hasMore' => $has_more,
					'total'   => (int) $total,
				)
			);
		} catch ( Exception $exception ) {
			return new WP_REST_Response(
				array(
					'options' => array(),
					'hasMore' => false,
				),
				500
			);
		}   }

	/**
	 * Returns a specific User to be used when populating a default value
	 *
	 * @since 4.17.0
	 *
	 * @param WP_REST_Request $request Request Object.
	 *
	 * @return WP_REST_Response Response Object.
	 */
	public function gutenberg_get_user( $request ) {
		try {
			global $wpdb;

			$results = $wpdb->get_row( $wpdb->prepare( "SELECT id as value, CONCAT( user_login, ' | ', user_email ) as label FROM {$wpdb->prefix}users WHERE id = %d", $request->get_param( 'id' ) ) );

			// In case an email address is not stored.
			$results->label = rtrim( $results->label, ' | ' );

			return new WP_REST_Response(
				array(
					'user' => ( $results ) ? $results : array(),
				)
			);
		} catch ( Exception $exception ) {
			return new WP_REST_Response(
				array(
					'user' => array(),
				),
				500
			);
		}   }
}
