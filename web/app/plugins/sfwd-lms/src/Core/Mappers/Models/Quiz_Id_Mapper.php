<?php
/**
 * Quiz_Id_Mapper class for converting between Quiz Post IDs and Pro Quiz IDs.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Mappers\Models;

use LearnDash\Core\Utilities\Cast;

/**
 * Quiz_Id_Mapper class for converting between Quiz Post IDs and Pro Quiz IDs.
 *
 * LearnDash uses two systems: WordPress posts (quiz posts) and WP ProQuiz (internal quiz data).
 * Each quiz post has a 'quiz_pro_id' meta field that links to the ProQuiz system.
 * This mapper provides conversion utilities between these two ID systems.
 *
 * @since 5.0.0
 */
class Quiz_Id_Mapper {
	/**
	 * Converts Pro Quiz IDs to Quiz Post IDs.
	 *
	 * @since 5.0.0
	 *
	 * @param int[] $pro_quiz_ids Array of Pro Quiz IDs.
	 *
	 * @return int[] Array of Quiz Post IDs.
	 */
	public static function to_post_ids( array $pro_quiz_ids ): array {
		if ( empty( $pro_quiz_ids ) ) {
			return [];
		}

		$pro_quiz_ids  = array_map( [ Cast::class, 'to_int' ], $pro_quiz_ids );
		$pro_quiz_ids  = array_filter( $pro_quiz_ids );
		$quiz_post_ids = [];

		foreach ( $pro_quiz_ids as $pro_quiz_id ) {
			$quiz_post_ids[] = Cast::to_int( self::to_post_id( $pro_quiz_id ) );
		}

		return array_values(
			array_filter( $quiz_post_ids )
		);
	}

	/**
	 * Converts Quiz Post IDs to Pro Quiz IDs.
	 *
	 * @since 5.0.0
	 *
	 * @param int[] $post_ids Array of Quiz Post IDs.
	 *
	 * @return int[] Array of Pro Quiz IDs.
	 */
	public static function to_pro_quiz_ids( array $post_ids ): array {
		if ( empty( $post_ids ) ) {
			return [];
		}

		$post_ids     = array_map( [ Cast::class, 'to_int' ], $post_ids );
		$post_ids     = array_filter( $post_ids );
		$pro_quiz_ids = [];

		foreach ( $post_ids as $post_id ) {
			$pro_quiz_id = self::to_pro_quiz_id( $post_id );

			$pro_quiz_ids[] = $pro_quiz_id;
		}

		return array_values(
			array_filter( $pro_quiz_ids )
		);
	}

	/**
	 * Converts a single Pro Quiz ID to a Quiz Post ID.
	 *
	 * @since 5.0.0
	 *
	 * @param int $pro_quiz_id Pro Quiz ID.
	 *
	 * @return int|null Quiz Post ID or null if not found.
	 */
	public static function to_post_id( int $pro_quiz_id ): ?int {
		if ( empty( $pro_quiz_id ) ) {
			return null;
		}

		$post_id = Cast::to_int(
			learndash_get_quiz_id_by_pro_quiz_id( $pro_quiz_id )
		);

		return $post_id > 0 ? $post_id : null;
	}

	/**
	 * Converts a single Quiz Post ID to a Pro Quiz ID.
	 *
	 * @since 5.0.0
	 *
	 * @param int $post_id Quiz Post ID.
	 *
	 * @return int|null Pro Quiz ID or null if not found.
	 */
	public static function to_pro_quiz_id( int $post_id ): ?int {
		if ( empty( $post_id ) ) {
			return null;
		}

		$pro_quiz_id = Cast::to_int(
			get_post_meta( $post_id, 'quiz_pro_id', true )
		);

		return $pro_quiz_id > 0 ? $pro_quiz_id : null;
	}
}
