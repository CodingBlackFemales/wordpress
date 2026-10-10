<?php
/**
 * Adds a bulk row's review comment to its post as a PublishPress editorial comment.
 *
 * Editorial comments are ordinary WordPress comments with both `comment_type`
 * and `comment_approved` set to `editorial-comment`, which keeps them out of the
 * public comment stream. They are inserted here the way PublishPress's own
 * editor form inserts them, so they display, thread and edit exactly as if the
 * reviewer had typed them into the post's Editorial Comments box.
 *
 * Nothing here can fail an import. A review note is secondary to the content:
 * when the module is off, the post type is not covered, or the insert fails,
 * the outcome is a short note for the batch report.
 *
 * @class   Import\EditorialComments
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Import;

use CodingBlackFemales\SlidesImporter\Bulk\BatchReport;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EditorialComments class.
 */
final class EditorialComments {

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

	/** Outcomes whose reported post is this row's own post in this course. */
	const IMPORTED = array( BatchReport::OUTCOME_CREATED, BatchReport::OUTCOME_UPDATED, BatchReport::OUTCOME_REUSED );


	/**
	 * Whether the Editorial Comments module is on, and the post types it covers.
	 *
	 * Read from the loaded module rather than the stored option so it reflects
	 * PublishPress's own defaults. PublishPress loads its modules on `init` in
	 * every context, WP-Cron included; when the plugin is inactive there is no
	 * module and comments are off.
	 *
	 * @return array{enabled: bool, post_types: string[]}
	 */
	public static function settings(): array {
		global $publishpress;

		$module  = is_object( $publishpress ) && isset( $publishpress->editorial_comments ) ? $publishpress->editorial_comments->module : null;
		$options = is_object( $module ) ? (array) ( $module->options ?? array() ) : array();

		return array(
			'enabled'    => ( $options['enabled'] ?? '' ) === 'on',
			'post_types' => array_keys( array_filter( (array) ( $options['post_types'] ?? array() ), static fn( $on ): bool => $on === 'on' ) ),
		);
	}


	/**
	 * Pre-flight notes for a row carrying a review comment.
	 *
	 * @param  array $row      PlannedRow.
	 * @param  array $settings From settings().
	 * @return string[]
	 */
	public static function preflight_notes( array $row, array $settings ): array {
		if ( ! $settings['enabled'] ) {
			return array( __( 'PublishPress Editorial Comments is not enabled, so this row\'s comment will not be added.', 'cbf-slides-importer' ) );
		}

		if ( ! in_array( $row['post_type'], $settings['post_types'], true ) ) {
			return array( __( 'PublishPress Editorial Comments is not enabled for this post type, so this row\'s comment will not be added.', 'cbf-slides-importer' ) );
		}

		if ( $row['reviewer'] !== '' && ! get_user_by( 'email', $row['reviewer'] ) ) {
			return array(
				sprintf(
					/* translators: %s: reviewer email address */
					__( 'No user has the email %s; the editorial comment will show that address as its author.', 'cbf-slides-importer' ),
					$row['reviewer']
				),
			);
		}

		return array();
	}


	/**
	 * Add a batch row's review comment once its post exists.
	 *
	 * @param  array $config    The row's import config; carries `review`.
	 * @param  array $outcome   { outcome, extra } from the job runner.
	 * @param  int   $course_id Course the batch imports into.
	 * @param  int   $user_id   User running the batch, the author when no reviewer is given.
	 * @return string A note for the report, or '' when the row has no comment.
	 */
	public static function for_row( array $config, array $outcome, int $course_id, int $user_id ): string {
		$review  = array_merge(
			array(
				'reviewer' => '',
				'comments' => '',
			),
			(array) ( $config['review'] ?? array() )
		);
		$post_id = (int) ( $outcome['extra']['post_id'] ?? 0 );

		if ( $review['comments'] === '' ) {
			return '';
		}

		if ( ! self::is_rows_post( $outcome['outcome'], $post_id, $course_id ) ) {
			return __( 'The editorial comment was not added, as there is no post in this course to add it to.', 'cbf-slides-importer' );
		}

		return self::add( $post_id, (string) $review['reviewer'], (string) $review['comments'], $user_id );
	}


	/**
	 * Add an editorial comment to a post, unless it is already there.
	 *
	 * @param  int    $post_id       Post to comment on.
	 * @param  string $reviewer      Reviewer's email, or '' to use $fallback_user.
	 * @param  string $comments      Comment text.
	 * @param  int    $fallback_user Author when no reviewer is given.
	 * @return string A note for the report.
	 */
	public static function add( int $post_id, string $reviewer, string $comments, int $fallback_user ): string {
		$problem = self::problem( $post_id );

		if ( $problem !== null ) {
			return $problem;
		}

		$author  = self::author( $reviewer, $fallback_user );
		$content = wp_kses( esc_html( trim( $comments ) ), self::ALLOWED_TAGS );

		if ( self::exists( $post_id, $author['email'], $content ) ) {
			return __( 'Editorial comment already present.', 'cbf-slides-importer' );
		}

		$comment_id = self::insert( $post_id, $author, $content );

		return $comment_id > 0
			? __( 'Editorial comment added.', 'cbf-slides-importer' )
			: __( 'The editorial comment could not be added.', 'cbf-slides-importer' );
	}


	/**
	 * Why a post cannot take an editorial comment, if it cannot.
	 *
	 * @param  int $post_id Post ID.
	 * @return string|null
	 */
	private static function problem( int $post_id ): ?string {
		$settings = self::settings();

		if ( ! $settings['enabled'] ) {
			return __( 'Editorial comment not added: PublishPress Editorial Comments is not enabled.', 'cbf-slides-importer' );
		}

		if ( ! in_array( get_post_type( $post_id ), $settings['post_types'], true ) ) {
			return __( 'Editorial comment not added: Editorial Comments is not enabled for this post type.', 'cbf-slides-importer' );
		}

		return null;
	}


	/**
	 * Whether a reported post is this row's own post in this course.
	 *
	 * An imported row's post always is. A skipped row reports the post whose
	 * title it matched, which may belong to another course; commenting there
	 * would put the review on someone else's content.
	 *
	 * @param  string $outcome   BatchReport outcome.
	 * @param  int    $post_id   Reported post.
	 * @param  int    $course_id Course the batch imports into.
	 * @return bool
	 */
	private static function is_rows_post( string $outcome, int $post_id, int $course_id ): bool {
		if ( $post_id === 0 ) {
			return false;
		}

		if ( in_array( $outcome, self::IMPORTED, true ) ) {
			return true;
		}

		return $outcome === BatchReport::OUTCOME_SKIPPED && self::in_course( $post_id, $course_id );
	}


	/**
	 * Whether a post is one of the course's steps.
	 *
	 * @param  int $post_id   Post ID.
	 * @param  int $course_id Course post ID.
	 * @return bool
	 */
	private static function in_course( int $post_id, int $course_id ): bool {
		if ( ! function_exists( 'learndash_course_get_steps_by_type' ) ) {
			return false;
		}

		$steps = (array) learndash_course_get_steps_by_type( $course_id, (string) get_post_type( $post_id ) );

		return in_array( $post_id, array_map( 'intval', $steps ), true );
	}


	/**
	 * Who the comment is from.
	 *
	 * The reviewer's account when their email matches one, so they can edit
	 * their own comment later; otherwise the email alone, which PublishPress
	 * still shows as the author with its avatar.
	 *
	 * @param  string $reviewer      Reviewer's email, or ''.
	 * @param  int    $fallback_user Author when no reviewer is given.
	 * @return array{user_id: int, name: string, email: string, url: string}
	 */
	private static function author( string $reviewer, int $fallback_user ): array {
		$user = $reviewer !== '' ? get_user_by( 'email', $reviewer ) : get_userdata( $fallback_user );

		if ( ! $user ) {
			return array(
				'user_id' => 0,
				'name'    => $reviewer,
				'email'   => $reviewer,
				'url'     => '',
			);
		}

		return array(
			'user_id' => (int) $user->ID,
			'name'    => (string) $user->display_name,
			'email'   => (string) $user->user_email,
			'url'     => (string) $user->user_url,
		);
	}


	/**
	 * Whether the post already has this comment from this author.
	 *
	 * Re-running a batch, or overwriting, must not stack copies of the same
	 * review on a post.
	 *
	 * @param  int    $post_id Post ID.
	 * @param  string $email   Author email.
	 * @param  string $content Comment content, as it would be stored.
	 * @return bool
	 */
	private static function exists( int $post_id, string $email, string $content ): bool {
		$existing = get_comments(
			array(
				'post_id'      => $post_id,
				'type'         => self::TYPE,
				'status'       => self::TYPE,
				'author_email' => $email,
			)
		);

		foreach ( $existing as $comment ) {
			if ( $comment->comment_content === $content ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Insert the comment as PublishPress's editor form would.
	 *
	 * PublishPress's notification action is not fired by default: a bulk
	 * migration would otherwise email every follower of every post. The
	 * `cbf_si_notify_editorial_comments` filter turns it on.
	 *
	 * @param  int    $post_id Post ID.
	 * @param  array  $author  From author().
	 * @param  string $content Comment content.
	 * @return int Comment ID, or 0 on failure.
	 */
	private static function insert( int $post_id, array $author, string $content ): int {
		$data = apply_filters(
			'pp_pre_insert_editorial_comment',
			array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => $author['name'],
				'comment_author_email' => $author['email'],
				'comment_author_url'   => $author['url'],
				'comment_content'      => $content,
				'comment_type'         => self::TYPE,
				'comment_parent'       => 0,
				'user_id'              => $author['user_id'],
				'comment_date'         => current_time( 'mysql' ),
				'comment_date_gmt'     => current_time( 'mysql', true ),
				'comment_approved'     => self::TYPE,
			)
		);

		$comment_id = (int) wp_insert_comment( $data );

		/**
		 * Whether an imported editorial comment notifies the post's followers.
		 *
		 * @param bool $notify     Default false.
		 * @param int  $comment_id The new comment.
		 */
		if ( $comment_id > 0 && apply_filters( 'cbf_si_notify_editorial_comments', false, $comment_id ) ) {
			do_action( 'pp_post_insert_editorial_comment', get_comment( $comment_id ) );
		}

		return $comment_id;
	}
}
