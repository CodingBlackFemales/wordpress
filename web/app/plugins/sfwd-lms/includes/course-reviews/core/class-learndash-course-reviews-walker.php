<?php
/**
 * Walker for outputting Reviews on the frontend.
 *
 * @since 4.25.1
 *
 * @package LearnDash\Course_Reviews
 *
 * cSpell:ignore vcard allowedentitynames
 */

defined( 'ABSPATH' ) || die();

use LearnDash\Core\Utilities\Cast;

/**
 * Class LearnDash_Course_Reviews_Walker.
 *
 * Walker for outputting Reviews on the frontend.
 *
 * @since 4.25.1
 */
class LearnDash_Course_Reviews_Walker extends Walker_Comment {
	/**
	 * What the class handles.
	 *
	 * @since 4.25.1
	 * @access public
	 * @var string
	 *
	 * @see Walker::$tree_type
	 */
	public $tree_type = 'ld_review';

	/**
	 * Start the element output.
	 *
	 * @since 4.25.1
	 *
	 * @see Walker::start_el()
	 * @see wp_list_comments()
	 *
	 * @global int    $comment_depth
	 * @global object $comment
	 *
	 * @param string                     $output  Passed by reference. Used to append additional content.
	 * @param WP_Comment                 $comment Comment data object.
	 * @param int                        $depth   Depth of comment in reference to parents.
	 * @param array{callback?: callable} $args    An array of arguments.
	 * @param int                        $id      Current Object ID.
	 *
	 * @return void
	 */
	public function start_el( &$output, $comment, $depth = 0, $args = array(), $id = 0 ) {
		$depth++;

		$GLOBALS['comment_depth'] = $depth; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- WP Core does this in this method as well.
		$GLOBALS['comment']       = $comment; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		if ( ! empty( $args['callback'] ) ) {
			ob_start();
			call_user_func( $args['callback'], $comment, $args, $depth );
			$output .= ob_get_clean();
			return;
		}

		ob_start();
		$this->html5_comment( $comment, $depth, $args );
		$output .= ob_get_clean();
	}

	/**
	 * Output a comment in the HTML5 format.
	 *
	 * @since 4.25.1
	 *
	 * @see wp_list_comments()
	 *
	 * @param WP_Comment                               $comment Comment to display.
	 * @param int                                      $depth   Depth of comment.
	 * @param array{avatar_size?: int, style?: string} $args    An array of arguments.
	 *
	 * @return void
	 */
	protected function html5_comment( $comment, $depth, $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'style'       => 'div',
				'avatar_size' => 0,
			)
		);

		$tag = ( 'div' === $args['style'] ) ? 'div' : 'li';

		ob_start();

		/**
		 * Runs before starting the output of each Review. Anything echo'd here will be placed before the Review HTML.
		 *
		 * @since 4.25.1
		 *
		 * @param WP_Comment $comment Comment data object.
		 * @param int        $depth   Depth of comment in reference to  parents.
		 * @param array      $args    An array of arguments.
		 */
		do_action(
			'learndash_course_reviews_before_review',
			$comment,
			$depth,
			$args
		);
		?>

		<?php // This opening tag gets closed in end_el(). ?>
		<<?php echo esc_attr( $tag ); ?> id="ld-review-<?php esc_attr( get_comment_ID() ); ?>" <?php comment_class( $this->has_children ? 'parent' : '' ); ?>>

			<div id="div-learndash-course-reviews-<?php echo esc_attr( get_comment_ID() ); ?>" class="learndash-course-reviews-body">

				<div class="learndash-course-reviews-meta">
					<div class="learndash-course-reviews-author vcard">
						<?php
						echo wp_kses_post(
							strval(
								get_avatar(
									$comment->comment_author_email,
									100,
									'',
									'',
									array(
										'class' => 'alignleft',
									)
								)
							)
						);
						?>
						<?php if ( 1 === $depth ) : ?>

							<div class="alignleft">

								<b class="review-title">
									<?php
									echo esc_html(
										Cast::to_string(
											get_comment_meta(
												Cast::to_int( $comment->comment_ID ),
												'review_title',
												true
											)
										)
									);
									?>
								</b>

								<div class="learndash-course-reviews-metadata">
									<?php
									if ( $args['avatar_size'] > 0 ) {
										echo get_avatar( $comment, $args['avatar_size'] );
									}
									?>
									<?php
									echo wp_kses_post(
										sprintf(
											// translators: By Author on .
											__( '<span class="author">By %s</span> on ', 'learndash' ),
											get_comment_author_link()
										)
									);
									?>
									<a
										href="
										<?php
										echo esc_url(
											get_comment_link(
												Cast::to_int( $comment->comment_ID ),
												$args
											)
										);
										?>
										"
									>
										<time datetime="<?php comment_time( 'c' ); ?>">
											<?php
											echo esc_html(
												sprintf(
													// translators: Date at time.
													__( '%1$s at %2$s', 'learndash' ),
													get_comment_date(),
													get_comment_time()
												)
											);
											?>
										</time>
									</a> <?php edit_comment_link( __( 'Edit', 'learndash' ), '<span class="edit-link">', '</span>' ); ?>
								</div>

								<?php
								learndash_course_reviews_star_rating(
									Cast::to_float(
										get_comment_meta(
											Cast::to_int( $comment->comment_ID ),
											'rating',
											true
										)
									)
								);
								?>

							</div>

						<?php endif; ?>
					</div>

					<?php if ( '0' === $comment->comment_approved ) : ?>
						<p class="learndash-course-reviews-awaiting-moderation">
							<i>
								<?php esc_html_e( 'Your review is awaiting moderation.', 'learndash' ); ?>
							</i>
						</p>
					<?php endif; ?>
				</div>

				<div class="learndash-course-reviews-content">
					<?php comment_text(); ?>
				</div>

			</div>
		<?php

		$allowed_html = wp_kses_allowed_html( 'post' );

		global $allowedentitynames;
		$allowedentitynames[] = 'starf'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Unfortunately necessary to allow &starf; to be used. See wp_kses(), wp_kses_normalize_entities(), and wp_kses_named_entities().

		echo wp_kses(
			strval( ob_get_clean() ),
			array_merge(
				$allowed_html,
				array(
					'style' => array(
						'type' => true,
					),
				)
			)
		);
	}

	/**
	 * Ends the element output, if needed.
	 *
	 * @since 4.25.1
	 *
	 * @see Walker::end_el()
	 * @see wp_list_comments()
	 *
	 * @param string                                         $output  Used to append additional content. Passed by reference.
	 * @param WP_Comment                                     $comment The current comment object. Default current comment.
	 * @param int                                            $depth   Optional. Depth of the current comment. Default 0.
	 * @param array{style?: string, end-callback?: callable} $args    Optional. An array of arguments. Default empty array.
	 *
	 * @return void
	 */
	public function end_el( &$output, $comment, $depth = 0, $args = array() ) {
		if ( ! empty( $args['end-callback'] ) ) {
			ob_start();

			call_user_func(
				$args['end-callback'],
				$comment,
				$args,
				$depth
			);

			$output .= ob_get_clean();
			return;
		}

		$args = wp_parse_args(
			$args,
			array(
				'style' => 'div',
			)
		);

		$tag = ( 'div' === $args['style'] ) ? 'div' : 'li';

		$output .= '</' . esc_attr( $tag ) . ">\n";

		ob_start();

		/**
		 * Runs after finishing the output of each Review. Anything echo'd here will be placed after the Review HTML.
		 *
		 * @since 4.25.1
		 *
		 * @param WP_Comment $comment Comment data object.
		 * @param int        $depth   Depth of comment in reference to  parents.
		 * @param array      $args    An array of arguments.
		 */
		do_action(
			'learndash_course_reviews_after_review',
			$comment,
			$depth,
			$args
		);

		$output .= ob_get_clean();
	}
}
