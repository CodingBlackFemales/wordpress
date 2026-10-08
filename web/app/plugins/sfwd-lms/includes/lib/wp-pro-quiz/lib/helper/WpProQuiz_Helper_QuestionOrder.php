<?php
/**
 * LearnDash ProQuiz Helper Question Order.
 *
 * @since 5.1.6
 * @package LearnDash
 */

use LearnDash\Core\Utilities\Cast;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LearnDash ProQuiz Helper Question Order Class.
 *
 * @since 5.1.6
 */
class WpProQuiz_Helper_QuestionOrder {
	/**
	 * Builds the question set to resume an attempt with: the saved order restored, then topped up to
	 * the target count with other valid questions.
	 *
	 * Single entry point for the resume restore so callers do not have to chain ordering and
	 * backfilling themselves. Used when resuming a quiz with random questions and/or a question
	 * subset, where the saved order can reference a question that has since been deleted, updated
	 * (new pro ID), or dropped from the subset.
	 *
	 * @since 5.1.6
	 *
	 * @param WpProQuiz_Model_Question[] $pool          All available questions to draw from.
	 * @param int[]                      $saved_pro_ids Saved order of question pro IDs (resume "randomOrder").
	 * @param int                        $target        Desired number of questions.
	 *
	 * @return WpProQuiz_Model_Question[] Questions in saved order with unresolvable IDs skipped, topped
	 *                                    up towards $target, as a sequential list.
	 */
	public static function get_resume_question_set( array $pool, array $saved_pro_ids, int $target ): array {
		$ordered = self::order_by_saved_pro_ids( $pool, $saved_pro_ids );

		return self::backfill_to_count( $ordered, $pool, $target );
	}

	/**
	 * Reorders a set of questions to match a saved list of question pro IDs (the resume
	 * "randomOrder"), skipping any saved ID that no longer resolves to a question in the set.
	 *
	 * Used when resuming a quiz that has random questions and/or a question subset enabled: the
	 * saved order can reference a question that has since been deleted, updated (new pro ID), or
	 * dropped from the rendered subset. Those are skipped so the resumed attempt keeps its valid
	 * progress instead of dereferencing a missing question.
	 *
	 * @since 5.1.6
	 *
	 * @param WpProQuiz_Model_Question[] $questions     Questions to reorder. May be keyed by question
	 *                                                  post ID or numerically indexed.
	 * @param int[]                      $saved_pro_ids Saved order of question pro IDs (resume
	 *                                                  "randomOrder").
	 *
	 * @return WpProQuiz_Model_Question[] Questions in saved order, with unresolvable saved IDs
	 *                                    skipped, as a sequential list.
	 */
	public static function order_by_saved_pro_ids( array $questions, array $saved_pro_ids ): array {
		$questions_by_key = array();
		$pro_id_to_key    = array();

		foreach ( $questions as $question ) {
			if ( ! $question instanceof WpProQuiz_Model_Question ) {
				continue;
			}

			$key = self::question_key( $question );

			$questions_by_key[ $key ]            = $question;
			$pro_id_to_key[ $question->getId() ] = $key;

			// If the question has since been updated, keep a reference to its old pro ID.
			if ( $question->getPreviousId() ) {
				$pro_id_to_key[ $question->getPreviousId() ] = $key;
			}
		}

		$ordered = array();

		foreach ( $saved_pro_ids as $saved_pro_id ) {
			// Skip saved IDs that no longer resolve to a question in the current set.
			if (
				! isset( $pro_id_to_key[ $saved_pro_id ] )
				|| ! isset( $questions_by_key[ $pro_id_to_key[ $saved_pro_id ] ] )
			) {
				continue;
			}

			$key = $pro_id_to_key[ $saved_pro_id ];

			$ordered[ $key ] = $questions_by_key[ $key ];
		}

		return array_values( $ordered );
	}

	/**
	 * Tops up an ordered question set to a target count by appending unused questions from the
	 * pool, preserving the existing order.
	 *
	 * Used when resuming a quiz whose saved subset lost questions (deleted/updated, or never fully
	 * persisted): the missing slots are replaced with other valid questions so the resumed quiz
	 * keeps its configured number of questions instead of shrinking.
	 *
	 * @since 5.1.6
	 *
	 * @param WpProQuiz_Model_Question[] $ordered Questions already selected, as a sequential list.
	 * @param WpProQuiz_Model_Question[] $pool    All available questions to draw replacements from.
	 * @param int                        $target  Desired number of questions.
	 *
	 * @return WpProQuiz_Model_Question[] The ordered set, topped up towards $target where the pool
	 *                                    allows, as a sequential list.
	 */
	private static function backfill_to_count( array $ordered, array $pool, int $target ): array {
		$needed = $target - count( $ordered );
		if ( $needed <= 0 ) {
			return $ordered;
		}

		// Pro IDs already in the set, so a replacement is never a question the attempt already has.
		$used = array();
		foreach ( $ordered as $question ) {
			$used[ $question->getId() ] = true;
		}

		$candidates = array();
		foreach ( $pool as $question ) {
			if ( ! $question instanceof WpProQuiz_Model_Question ) {
				continue;
			}
			if ( isset( $used[ $question->getId() ] ) ) {
				continue;
			}

			$candidates[] = $question;
		}

		// Pick replacements at random to stay consistent with the random-questions feature.
		shuffle( $candidates );

		foreach ( array_slice( $candidates, 0, $needed ) as $question ) {
			$ordered[] = $question;
		}

		return $ordered;
	}

	/**
	 * Returns the de-duplication key for a question, namespaced by ID space so a question's post ID
	 * and another question's pro ID (legacy data path, post ID 0) can never collide.
	 *
	 * @since 5.1.6
	 *
	 * @param WpProQuiz_Model_Question $question Question.
	 *
	 * @return string Namespaced question key.
	 */
	private static function question_key( $question ): string {
		$post_id = Cast::to_int( $question->getQuestionPostId() );

		if ( $post_id > 0 ) {
			return 'post:' . $post_id;
		}

		return 'pro:' . Cast::to_int( $question->getId() );
	}
}
