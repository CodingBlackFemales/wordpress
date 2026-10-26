<?php
/**
 * REST routes for PublishPress editorial comments.
 *
 * PublishPress adds editorial comments through admin-ajax only, so agents
 * working through the REST API with an Application Password can't post the
 * review notes they collect. These routes read and add them as the
 * authenticated user, with the same permission PublishPress's own form uses:
 * the right to edit the post.
 *
 *   GET  /wp-json/cbf-editorial/v1/posts/<id>/comments
 *   POST /wp-json/cbf-editorial/v1/posts/<id>/comments  { content, parent?, notify? }
 *
 * Comments are stored exactly as the form stores them, so they display, thread
 * and edit as if typed into the Editorial Comments box. Posting the same text
 * twice returns the existing comment rather than adding a copy, and followers
 * aren't notified unless `notify` is true, so an agent posting a batch of
 * notes sends no email.
 *
 * @package CBF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Editorial comments REST controller.
 */
final class CBF_Editorial_Comments_API {

	/** REST namespace. */
	const NAMESPACE = 'cbf-editorial/v1';

	/** PublishPress's comment type, also used as the approval status. */
	const TYPE = 'editorial-comment';

	/** Markup PublishPress allows in an editorial comment. */
	const ALLOWED_TAGS = array(
		'a'          => array(
			'href'  => array(),
			'title' => array(),
		),
		'b'          => array(),
		'i'          => array(),
		'strong'     => array(),
		'em'         => array(),
		'u'          => array(),
		'del'        => array(),
		'blockquote' => array(),
		'sub'        => array(),
		'sup'        => array(),
	);


	/**
	 * Register the routes.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/posts/(?P<id>\d+)/comments',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'list_comments' ),
					'permission_callback' => array( __CLASS__, 'can_comment' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'create_comment' ),
					'permission_callback' => array( __CLASS__, 'can_comment' ),
					'args'                => array(
						'content' => array(
							'type'     => 'string',
							'required' => true,
						),
						'parent'  => array(
							'type'    => 'integer',
							'default' => 0,
							'minimum' => 0,
						),
						'notify'  => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
				),
			)
		);
	}


	/**
	 * Whether the current user can read and add editorial comments on the post.
	 *
	 * @param  WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public static function can_comment( WP_REST_Request $request ) {
		$post_id = (int) $request['id'];

		if ( ! get_post( $post_id ) ) {
			return new WP_Error( 'cbf_editorial_no_post', __( 'No post with that ID.', 'cbf' ), array( 'status' => 404 ) );
		}

		if ( ! self::covers( (string) get_post_type( $post_id ) ) ) {
			return new WP_Error( 'cbf_editorial_off', __( 'Editorial comments are not enabled for this post type.', 'cbf' ), array( 'status' => 400 ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error( 'cbf_editorial_forbidden', __( 'You can only comment on posts you can edit. For published content, comment on its revision.', 'cbf' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}


	/**
	 * Whether the Editorial Comments module is on for a post type.
	 *
	 * Read from the loaded module rather than the stored option, so it reflects
	 * PublishPress's own defaults.
	 *
	 * @param  string $post_type Post type.
	 * @return bool
	 */
	private static function covers( string $post_type ): bool {
		$options    = self::module_options();
		$post_types = (array) ( $options['post_types'] ?? array() );

		return ( $options['enabled'] ?? '' ) === 'on' && ( $post_types[ $post_type ] ?? '' ) === 'on';
	}


	/**
	 * The Editorial Comments module's options, or none when PublishPress isn't loaded.
	 *
	 * @return array
	 */
	private static function module_options(): array {
		global $publishpress;

		$module = is_object( $publishpress ) && isset( $publishpress->editorial_comments ) ? $publishpress->editorial_comments->module : null;

		return is_object( $module ) ? (array) ( $module->options ?? array() ) : array();
	}


	/**
	 * List a post's editorial comments, oldest first.
	 *
	 * @param  WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function list_comments( WP_REST_Request $request ): WP_REST_Response {
		return rest_ensure_response( array_map( array( __CLASS__, 'prepare' ), self::comments_on( (int) $request['id'] ) ) );
	}


	/**
	 * A post's editorial comments, oldest first, optionally only one user's.
	 *
	 * Reads the comments table directly rather than through get_comments().
	 * PublishPress Permissions filters comment queries to comments on posts
	 * with a readable status, which hides every editorial comment on a draft
	 * or other unpublished post. The routes have already checked that the
	 * user can edit the post, which is the access these comments need.
	 *
	 * @param  int      $post_id Post ID.
	 * @param  int|null $user_id Only this user's comments, if given.
	 * @return WP_Comment[]
	 */
	private static function comments_on( int $post_id, ?int $user_id = null ): array {
		global $wpdb;

		$user_clause = $user_id === null ? '' : $wpdb->prepare( ' AND user_id = %d', $user_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- see the docblock; $user_clause is prepared above.
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_post_ID = %d AND comment_type = %s AND comment_approved = %s{$user_clause} ORDER BY comment_date_gmt ASC, comment_ID ASC", $post_id, self::TYPE, self::TYPE ) );

		return array_values( array_filter( array_map( 'get_comment', $ids ) ) );
	}


	/**
	 * Add an editorial comment as the current user.
	 *
	 * @param  WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_comment( WP_REST_Request $request ) {
		$post_id = (int) $request['id'];
		$parent  = (int) $request['parent'];
		$content = wp_kses( esc_html( trim( (string) $request['content'] ) ), self::ALLOWED_TAGS );

		if ( $content === '' ) {
			return new WP_Error( 'cbf_editorial_empty', __( 'The comment is empty.', 'cbf' ), array( 'status' => 400 ) );
		}

		if ( $parent > 0 && (int) get_comment( $parent )?->comment_post_ID !== $post_id ) {
			return new WP_Error( 'cbf_editorial_parent', __( 'The parent comment is not on this post.', 'cbf' ), array( 'status' => 400 ) );
		}

		$existing = self::find( $post_id, $content );
		if ( $existing ) {
			return rest_ensure_response( self::prepare( $existing ) + array( 'existing' => true ) );
		}

		$comment = self::insert( $post_id, $parent, $content, (bool) $request['notify'] );
		if ( ! $comment ) {
			return new WP_Error( 'cbf_editorial_failed', __( 'The comment could not be added.', 'cbf' ), array( 'status' => 500 ) );
		}

		$response = rest_ensure_response( self::prepare( $comment ) + array( 'existing' => false ) );
		$response->set_status( 201 );

		return $response;
	}


	/**
	 * The current user's comment on the post with this content, if any.
	 *
	 * @param  int    $post_id Post ID.
	 * @param  string $content Content, as stored.
	 * @return WP_Comment|null
	 */
	private static function find( int $post_id, string $content ): ?WP_Comment {
		foreach ( self::comments_on( $post_id, get_current_user_id() ) as $comment ) {
			if ( $comment->comment_content === $content ) {
				return $comment;
			}
		}

		return null;
	}


	/**
	 * Insert the comment as PublishPress's editor form does.
	 *
	 * @param  int    $post_id Post ID.
	 * @param  int    $parent  Parent comment ID, or 0.
	 * @param  string $content Content, sanitised.
	 * @param  bool   $notify  Whether to fire PublishPress's notification hook.
	 * @return WP_Comment|null
	 */
	private static function insert( int $post_id, int $parent, string $content, bool $notify ): ?WP_Comment {
		$user = wp_get_current_user();
		$data = apply_filters(
			'pp_pre_insert_editorial_comment',
			array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => $user->display_name,
				'comment_author_email' => $user->user_email,
				'comment_author_url'   => $user->user_url,
				'comment_content'      => $content,
				'comment_type'         => self::TYPE,
				'comment_parent'       => $parent,
				'user_id'              => $user->ID,
				'comment_date'         => current_time( 'mysql' ),
				'comment_date_gmt'     => current_time( 'mysql', true ),
				'comment_approved'     => self::TYPE,
			)
		);

		$comment = get_comment( (int) wp_insert_comment( $data ) );
		if ( ! $comment instanceof WP_Comment ) {
			return null;
		}

		if ( $notify ) {
			do_action( 'pp_post_insert_editorial_comment', $comment );
		}

		return $comment;
	}


	/**
	 * Shape a comment for the response.
	 *
	 * @param  WP_Comment $comment Comment.
	 * @return array
	 */
	public static function prepare( WP_Comment $comment ): array {
		return array(
			'id'      => (int) $comment->comment_ID,
			'post'    => (int) $comment->comment_post_ID,
			'parent'  => (int) $comment->comment_parent,
			'author'  => $comment->comment_author,
			'date'    => mysql_to_rfc3339( $comment->comment_date ),
			'content' => wp_specialchars_decode( $comment->comment_content, ENT_QUOTES ),
		);
	}
}

add_action( 'rest_api_init', array( 'CBF_Editorial_Comments_API', 'register_routes' ) );
