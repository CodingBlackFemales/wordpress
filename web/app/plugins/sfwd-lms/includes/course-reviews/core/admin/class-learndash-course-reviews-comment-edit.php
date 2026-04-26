<?php
/**
 * Makes additions to the Comment Edit screen.
 *
 * @since 4.25.1
 *
 * @package LearnDash\Course_Reviews
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

use LearnDash\Core\Utilities\Cast;

/**
 * Main LearnDash_Course_Reviews_Comment_Edit class.
 *
 * @since 4.25.1
 */
final class LearnDash_Course_Reviews_Comment_Edit {
	/**
	 * LearnDash_Course_Reviews_Comment_Edit Constructor
	 *
	 * @since 4.25.1
	 *
	 * @return void
	 */
	public function __construct() {
		// WooCommerce includes a Meta Box that is able to see and manipulate our value, so we will let them.
		if ( class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_action( 'add_meta_boxes', array( $this, 'add_rating_meta_box' ) );

		add_filter( 'wp_update_comment_data', array( $this, 'update_rating' ) );
	}

	/**
	 * Add Meta Boxes to the Comment Edit screen.
	 *
	 * @since 4.25.1
	 *
	 * @return void
	 */
	public function add_rating_meta_box() {
		add_meta_box(
			'learndash-course-reviews-rating',
			__( 'Rating', 'learndash' ),
			array( $this, 'rating_metabox' ),
			'comment',
			'normal',
			'high'
		);
	}

	/**
	 * Output the Rating Metabox.
	 *
	 * @since 4.25.1
	 *
	 * @param WP_Comment $comment WP_Comment Object.
	 *
	 * @return void
	 */
	public function rating_metabox( $comment ) {
		wp_nonce_field( 'learndash_course_reviews_save_rating', 'learndash_course_reviews_rating_nonce' );

		$saved_rating = get_comment_meta(
			Cast::to_int( $comment->comment_ID ),
			'rating',
			true
		);

		?>

		<select name="rating" id="rating">
			<?php
			for ( $rating = 1; $rating <= 5; $rating++ ) {
				printf(
					'<option value="%1$s"%2$s>%1$s</option>',
					esc_attr( Cast::to_string( $rating ) ),
					selected( $saved_rating, $rating, false )
				);
			}
			?>
		</select>

		<?php
	}

	/**
	 * Update the Comment Meta from the backend.
	 *
	 * @since 4.25.1
	 *
	 * @param array{comment_ID: string} $data New, processed Comment Data. See wp_update_comment() and WP_Comment for all options.
	 *
	 * @return array{comment_ID: string} New, processed Comment Data.
	 */
	public function update_rating( $data ) {
		$post_data = wp_unslash( $_POST );

		if (
			empty( $post_data['learndash_course_reviews_rating_nonce'] )
			|| ! wp_verify_nonce(
				wp_unslash(
					$post_data['learndash_course_reviews_rating_nonce']
				),
				'learndash_course_reviews_save_rating'
			)
		) {
			return $data;
		}

		if ( ! isset( $post_data['rating'] ) ) {
			return $data;
		}

		if (
			$post_data['rating'] > 5
			|| $post_data['rating'] < 0
		) {
			return $data;
		}

		update_comment_meta(
			Cast::to_int( $data['comment_ID'] ),
			'rating',
			Cast::to_int(
				wp_unslash( $post_data['rating'] )
			)
		);

		return $data;
	}
}

$instance = new LearnDash_Course_Reviews_Comment_Edit();
