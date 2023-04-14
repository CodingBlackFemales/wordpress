<?php
/**
 * LearnDash LD30 focus mode prints the comments list output.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $_GET['replytocom'] ) ) {

	/**
	 * Filters Focus mode comment list arguments.
	 *
	 * @since 3.0.0
	 *
	 * @param array $comment_list_args Comment List arguments to be used in wp_list_comments arguments.
	 */
	$args = apply_filters(
		'learndash_focus_mode_list_comments_args',
		array(
			'style'    => 'div',
			'page'     => get_query_var( 'cpage', 1 ),
			'callback' => 'learndash_focus_mode_comments_list',
		)
	);
	wp_list_comments( $args );
}

if ( ! function_exists( 'learndash_focus_mode_comments_list' ) ) {
	/**
	 * Prints the focus mode comments list output.
	 *
	 * Used as a callback by `wp_list_comments()` for displaying the comments.
	 *
	 * @since 3.1.0
	 *
	 * @global WP_Roles   $wp_roles WordPress role management object.
	 * @global WP_Post    $post     Global post object.
	 * @global WP_Comment $comment  Global comment object.
	 *
	 * @param WP_Comment $comment The comment object.
	 * @param array      $args    An array of comment arguments.
	 * @param int        $depth   The depth of the comment.
	 */
	function learndash_focus_mode_comments_list( $comment, $args, $depth ) {
		global $wp_roles;
		global $post;

		$GLOBALS['comment'] = $comment;

		$user_data    = get_userdata( $comment->user_id );
		$roles        = $user_data->roles;
		$role_classes = '';
		if ( ! empty( $roles ) ) {
			foreach ( $roles as $role ) :
				$role_classes .= 'role-' . $role;
				if ( 'administrator' === $role || 'group_leader' === $role ) {
					$role_name = translate_user_role( $wp_roles->roles[ $role ]['name'] );
				}

			endforeach;
		}

		$learndash_avatar_class = empty( get_avatar( $comment->comment_author_email ) ) ? ' ld-no-avatar-image' : '';

		?>

	<div <?php comment_class( 'ld-focus-comment ptype-' . $post->post_type . ' ' . $role_classes . $learndash_avatar_class ); ?> id="comment-<?php comment_ID(); ?>">
		<div class="ld-comment-wrapper">

			<?php if ( '0' == $comment->comment_approved ) : ?>
				<span class="ld-comment-alert">
					<?php
					esc_html_e(
						'Your response is awaiting for approval.',
						'buddyboss-theme'
					);
					?>
				</span>
			<?php endif; ?>

			<div class="ld-comment-avatar">
				<?php echo wp_kses_post( get_avatar( $comment->comment_author_email ) ); ?>
				<span class="ld-comment-avatar-author">
					<span class="ld-comment-author-name">
						<?php
						echo esc_html( $comment->comment_author );
						if ( ! empty( $role_name ) ) {
							echo esc_html( ' (' . $role_name . ')' );
						}
						?>
					</span>
					<a class="ld-comment-permalink" href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
					<?php
					printf(
					// translators: placeholders: %1$s: Comment Date, %2$s: Comment Time
						esc_html_x( '%1$s at %2$s', 'placeholders: comment date, comment time', 'buddyboss-theme' ),
						'<span> ' . esc_html( get_comment_date() ) . '</span>',
						'<span> ' . esc_html( get_comment_time() ) . '</span>'
					);
					?>

					</a>
				</span>
			</div>

			<div class="ld-comment-body">
				<?php comment_text(); ?>
				<div class="ld-comment-reply">
					<?php
					comment_reply_link(
						array_merge(
							$args,
							array(
								'reply_text' => esc_html__( 'Reply', 'buddyboss-theme' ),
								'after'      => '',
								'depth'      => $depth,
								'max_depth'  => $args['max_depth'],
							)
						)
					);
					?>
				</div>
			</div>
		</div>
		<?php
	}
}
