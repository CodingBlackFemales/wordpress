<?php
/**
 * Creates a LearnDash quiz, with its questions, from a parsed Google Form.
 *
 * Deliberately does not go through learndash-bulk-lessons-or-topics. That
 * plugin's quiz support is written against TSTPrep\LDAdvancedQuizzes, which is
 * not installed here, so this builds the WP-Pro-Quiz records directly — the
 * same approach as scripts/create-containerisation-quiz.php.
 *
 * @class   Import\QuizImporter
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Import;

use CodingBlackFemales\SlidesImporter\Forms\Parser as FormsParser;
use CodingBlackFemales\SlidesImporter\Utils;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * QuizImporter class.
 */
final class QuizImporter {

	const POST_TYPE          = 'sfwd-quiz';
	const QUESTION_POST_TYPE = 'sfwd-question';

	/**
	 * Import a parsed quiz.
	 *
	 * Mirrors LearnDashImporter::import(): the same result shape, and every post
	 * created is reverted to draft when anything goes wrong, so students never
	 * see a half-built quiz.
	 *
	 * @param  array $quiz    ParsedQuiz from Forms\Parser.
	 * @param  array $summary Job result_summary (contains `config` and `deck_name`).
	 * @return array|WP_Error { created_post_ids: int[], skipped_post_ids: int[], errors: string[] }
	 */
	public function import( array $quiz, array $summary ): array|WP_Error {
		$params  = self::params( $quiz, $summary );
		$problem = self::precondition( $quiz, $params );

		if ( $problem !== null ) {
			return $problem;
		}

		$existing = self::find_existing( $params['title'], $params['course_id'] );
		if ( $existing && ! $params['overwrite'] ) {
			return array(
				'created_post_ids' => array(),
				'skipped_post_ids' => array( $existing ),
				'errors'           => array(),
			);
		}

		return $this->build( $quiz, $params, $existing );
	}


	/**
	 * Read the import settings out of a job summary.
	 *
	 * The quiz sits under the topic when one was chosen, otherwise the lesson.
	 * Its title falls back from the configured title to the form's own, then to
	 * the job's name.
	 *
	 * @param  array $quiz    ParsedQuiz.
	 * @param  array $summary Job result_summary.
	 * @return array{course_id: int, parent_id: int, title: string, overwrite: bool}
	 */
	private static function params( array $quiz, array $summary ): array {
		$config = array_merge(
			array(
				'course_id'  => 0,
				'lesson_id'  => 0,
				'topic_id'   => 0,
				'post_title' => '',
				'overwrite'  => false,
			),
			(array) ( $summary['config'] ?? array() )
		);

		$titles = array( (string) $config['post_title'], (string) ( $quiz['title'] ?? '' ), (string) ( $summary['deck_name'] ?? '' ), 'Imported Quiz' );

		return array(
			'course_id' => (int) $config['course_id'],
			'parent_id' => (int) $config['topic_id'] > 0 ? (int) $config['topic_id'] : (int) $config['lesson_id'],
			'title'     => (string) current( array_filter( $titles, static fn( string $t ): bool => $t !== '' ) ),
			'overwrite' => ! empty( $config['overwrite'] ),
		);
	}


	/**
	 * Why a quiz cannot be imported at all, if it cannot.
	 *
	 * @param  array $quiz   ParsedQuiz.
	 * @param  array $params Import settings from params().
	 * @return WP_Error|null
	 */
	private static function precondition( array $quiz, array $params ): ?WP_Error {
		if ( ! class_exists( 'WpProQuiz_Model_Quiz' ) || ! function_exists( 'learndash_course_add_child_to_parent' ) ) {
			return new WP_Error( 'cbf_si_no_learndash', __( 'LearnDash is not active, so a quiz cannot be created.', 'cbf-slides-importer' ) );
		}

		if ( $params['course_id'] <= 0 || $params['parent_id'] <= 0 ) {
			return new WP_Error(
				'cbf_si_quiz_no_parent',
				__( 'Choose the course and the lesson or topic this quiz belongs to.', 'cbf-slides-importer' )
			);
		}

		if ( $quiz['questions'] === array() ) {
			return new WP_Error( 'cbf_si_quiz_empty', __( 'This form has no questions that can be imported.', 'cbf-slides-importer' ) );
		}

		return null;
	}


	/**
	 * Create or overwrite the quiz and its questions.
	 *
	 * @param  array $quiz     ParsedQuiz.
	 * @param  array $params   Import settings from params().
	 * @param  int   $existing Quiz post ID being overwritten, or 0.
	 * @return array { created_post_ids: int[], skipped_post_ids: int[], errors: string[] }
	 */
	private function build( array $quiz, array $params, int $existing ): array {
		$errors  = array();
		$created = array();

		try {
			$quiz_id = $this->save_quiz_post( $existing, $params['title'], (string) $quiz['description'] );
			$pro_id  = $this->save_pro_quiz( $quiz_id, $params['title'] );
			$this->link_to_course( $quiz_id, $pro_id, $params['course_id'], $params['parent_id'] );
			$this->remove_questions( $quiz_id );

			$created[] = $quiz_id;
			$questions = $this->save_questions( $quiz_id, $pro_id, $quiz['questions'], $errors );

			update_post_meta( $quiz_id, 'ld_quiz_questions', $questions['map'] );
			$created = array_merge( $created, $questions['post_ids'] );
		} catch ( \Throwable $e ) {
			$errors[] = $e->getMessage();
			Utils::log( 'Quiz import failed.', array( 'error' => $e->getMessage() ) );
		}

		if ( $errors !== array() ) {
			$this->revert_to_draft( $created );
		}

		return array(
			'created_post_ids' => $created,
			'skipped_post_ids' => array(),
			'errors'           => $errors,
		);
	}


	/**
	 * Find a quiz this course already has with the same title.
	 *
	 * Title is the identity the lesson importer uses, so re-importing a form
	 * behaves like re-importing a deck: skipped unless overwrite is on.
	 *
	 * @param  string $title     Quiz title.
	 * @param  int    $course_id Course post ID.
	 * @return int Quiz post ID, or 0.
	 */
	private static function find_existing( string $title, int $course_id ): int {
		$found = get_posts(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'title'       => $title,
				'numberposts' => 1,
				'fields'      => 'ids',
				'meta_key'    => 'course_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => $course_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		return $found ? (int) $found[0] : 0;
	}


	/**
	 * Create the quiz post, or update the one being overwritten.
	 *
	 * @param  int    $existing    Post ID to overwrite, or 0.
	 * @param  string $title       Quiz title.
	 * @param  string $description The form's description, used as the quiz intro.
	 * @return int Quiz post ID.
	 */
	private function save_quiz_post( int $existing, string $title, string $description ): int {
		$post = array(
			'post_title'   => $title,
			'post_type'    => self::POST_TYPE,
			'post_status'  => 'publish',
			'post_content' => $description !== '' ? wpautop( esc_html( $description ) ) : '',
		);

		if ( $existing ) {
			$post['ID'] = $existing;
		}

		$id = wp_insert_post( $post, true );

		if ( is_wp_error( $id ) ) {
			throw new \RuntimeException( esc_html( $id->get_error_message() ) );
		}

		return (int) $id;
	}


	/**
	 * Create or update the WP-Pro-Quiz record behind a quiz post.
	 *
	 * @param  int    $quiz_id Quiz post ID.
	 * @param  string $title   Quiz title.
	 * @return int WP-Pro-Quiz ID.
	 */
	private function save_pro_quiz( int $quiz_id, string $title ): int {
		$pro_id = (int) get_post_meta( $quiz_id, 'quiz_pro_id', true );

		$model = new \WpProQuiz_Model_Quiz(
			array(
				'id'                   => $pro_id,
				'name'                 => $title,
				'quizModus'            => \WpProQuiz_Model_Quiz::QUIZ_MODUS_SINGLE,
				'questionRandom'       => false,
				'answerRandom'         => false,
				'showMaxQuestion'      => false,
				'autostart'            => true,
				'showReviewQuestion'   => false,
				'quizSummaryHide'      => true,
				'skipQuestionDisabled' => true,
			)
		);

		$saved = ( new \WpProQuiz_Model_QuizMapper() )->save( $model );
		if ( ! $saved ) {
			throw new \RuntimeException( 'Could not save the quiz settings.' );
		}

		return (int) $saved->getId();
	}


	/**
	 * Point the quiz post at its WP-Pro-Quiz record and attach it to the course.
	 *
	 * @param int $quiz_id   Quiz post ID.
	 * @param int $pro_id    WP-Pro-Quiz ID.
	 * @param int $course_id Course post ID.
	 * @param int $parent_id Lesson or topic post ID the quiz sits under.
	 */
	private function link_to_course( int $quiz_id, int $pro_id, int $course_id, int $parent_id ): void {
		update_post_meta( $quiz_id, 'quiz_pro_id', $pro_id );
		update_post_meta( $quiz_id, 'quiz_pro_id_' . $pro_id, $pro_id );
		update_post_meta( $quiz_id, 'quiz_pro_primary_' . $pro_id, $pro_id );
		update_post_meta(
			$quiz_id,
			'_sfwd-quiz',
			array(
				'sfwd-quiz_quiz_pro'                  => $pro_id,
				'sfwd-quiz_course'                    => $course_id,
				'sfwd-quiz_lesson'                    => $parent_id,
				'sfwd-quiz_autostart'                 => true,
				'sfwd-quiz_quizModus_single_feedback' => 'each',
			)
		);

		learndash_update_setting( $quiz_id, 'course', $course_id );
		learndash_update_setting( $quiz_id, 'lesson', $parent_id );
		update_post_meta( $quiz_id, 'course_id', $course_id );
		update_post_meta( $quiz_id, 'ld_course_' . $course_id, $course_id );

		if ( ! learndash_course_add_child_to_parent( $course_id, $quiz_id, $parent_id ) ) {
			throw new \RuntimeException( 'Could not attach the quiz to the chosen lesson or topic.' );
		}
	}


	/**
	 * Delete the questions of a quiz being overwritten.
	 *
	 * @param int $quiz_id Quiz post ID.
	 */
	private function remove_questions( int $quiz_id ): void {
		$old = get_post_meta( $quiz_id, 'ld_quiz_questions', true );
		if ( ! is_array( $old ) ) {
			return;
		}

		$mapper = new \WpProQuiz_Model_QuestionMapper();

		foreach ( $old as $post_id => $pro_question_id ) {
			$mapper->delete( (int) $pro_question_id );
			wp_delete_post( (int) $post_id, true );
		}

		delete_post_meta( $quiz_id, 'ld_quiz_questions' );
	}


	/**
	 * Create every question.
	 *
	 * A question that fails is reported and the rest still go in, so one bad
	 * question shows up in the error list alongside its neighbours' results
	 * rather than hiding them.
	 *
	 * @param  int   $quiz_id  Quiz post ID.
	 * @param  int   $pro_id   WP-Pro-Quiz ID.
	 * @param  array $items    Parsed questions.
	 * @param  array $errors   Accumulated errors.
	 * @return array{map: array<int, int>, post_ids: int[]}
	 */
	private function save_questions( int $quiz_id, int $pro_id, array $items, array &$errors ): array {
		$mapper   = new \WpProQuiz_Model_QuestionMapper();
		$map      = array();
		$post_ids = array();

		foreach ( array_values( $items ) as $order => $item ) {
			try {
				$saved = $this->save_question( $mapper, $quiz_id, $pro_id, $item, $order );
			} catch ( \Throwable $e ) {
				$errors[] = sprintf( 'Question %d: %s', (int) $item['index'], $e->getMessage() );
				continue;
			}

			$map[ $saved['post_id'] ] = $saved['pro_id'];
			$post_ids[]               = $saved['post_id'];
		}

		return array(
			'map'      => $map,
			'post_ids' => $post_ids,
		);
	}


	/**
	 * Create one question post and its WP-Pro-Quiz record.
	 *
	 * @param  \WpProQuiz_Model_QuestionMapper $mapper  Question mapper.
	 * @param  int                              $quiz_id Quiz post ID.
	 * @param  int                              $pro_id  WP-Pro-Quiz ID.
	 * @param  array                            $item    Parsed question.
	 * @param  int                              $order   Zero-based position.
	 * @return array{post_id: int, pro_id: int}
	 */
	private function save_question( \WpProQuiz_Model_QuestionMapper $mapper, int $quiz_id, int $pro_id, array $item, int $order ): array {
		$title = wp_trim_words( $item['title'], 12, '…' );
		$body  = self::question_html( $item );
		$type  = (string) $item['type'];

		$post_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_content' => $body,
				'post_type'    => self::QUESTION_POST_TYPE,
				'post_status'  => 'publish',
				'menu_order'   => $order,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			throw new \RuntimeException( esc_html( $post_id->get_error_message() ) );
		}

		$points = (int) $item['points'];

		$model = new \WpProQuiz_Model_Question(
			array(
				'id'                    => 0,
				'questionPostId'        => $post_id,
				'quizId'                => $pro_id,
				'sort'                  => $order,
				'title'                 => $title,
				'question'              => $body,
				'correctMsg'            => self::feedback_html( (string) $item['correct_msg'] ),
				'incorrectMsg'          => self::feedback_html( (string) $item['incorrect_msg'] ),
				'points'                => $points,
				'answerType'            => $type,
				'answerData'            => self::answer_models( $item ),
				'answerPointsActivated' => false,
				'showPointsInBox'       => false,
				'correctSameText'       => false,
			)
		);

		$saved = $mapper->save( $model );
		if ( ! $saved ) {
			throw new \RuntimeException( 'Could not save the question.' );
		}

		$pro_question_id = (int) $saved->getId();

		update_post_meta( $post_id, 'question_pro_id', $pro_question_id );
		update_post_meta( $post_id, 'question_type', $type );
		update_post_meta( $post_id, 'question_points', $points );
		update_post_meta( $post_id, 'quiz_id', $quiz_id );
		update_post_meta( $post_id, 'ld_quiz_id', $pro_id );
		update_post_meta( $post_id, '_sfwd-question', array( 'sfwd-question_quiz' => (string) $quiz_id ) );

		return array(
			'post_id' => (int) $post_id,
			'pro_id'  => $pro_question_id,
		);
	}


	/**
	 * Build the answer models for a question.
	 *
	 * An essay has exactly one, carrying the grading settings; its points are
	 * only awarded when an instructor grades it.
	 *
	 * @param  array $item Parsed question.
	 * @return \WpProQuiz_Model_AnswerTypes[]
	 */
	private static function answer_models( array $item ): array {
		if ( $item['type'] === FormsParser::TYPE_ESSAY ) {
			$essay = new \WpProQuiz_Model_AnswerTypes();
			$essay->setAnswer( '' );
			$essay->setPoints( (int) $item['points'] );
			$essay->setGraded( true );
			$essay->setGradedType( 'text' );
			$essay->setGradingProgression( 'not-graded-none' );

			return array( $essay );
		}

		$models = array();

		foreach ( $item['answers'] as $answer ) {
			$models[] = new \WpProQuiz_Model_AnswerTypes(
				array(
					'answer'         => $answer['text'],
					'html'           => false,
					'points'         => $answer['correct'] ? 1 : 0,
					'correct'        => $answer['correct'],
					'sortString'     => '',
					'sortStringHtml' => false,
				)
			);
		}

		return $models;
	}


	/**
	 * The question stem as post content: the title, then any helper text.
	 *
	 * @param  array $item Parsed question.
	 * @return string
	 */
	public static function question_html( array $item ): string {
		$html = '<p>' . esc_html( $item['title'] ) . '</p>';

		if ( $item['description'] !== '' ) {
			$html .= wpautop( esc_html( $item['description'] ) );
		}

		return $html;
	}


	/**
	 * Feedback text as HTML, or '' when the form gave none.
	 *
	 * @param  string $text Plain feedback text.
	 * @return string
	 */
	private static function feedback_html( string $text ): string {
		return $text === '' ? '' : wpautop( esc_html( $text ) );
	}


	/**
	 * Revert posts to draft after an error.
	 *
	 * @param int[] $post_ids Post IDs.
	 */
	private function revert_to_draft( array $post_ids ): void {
		foreach ( $post_ids as $post_id ) {
			wp_update_post(
				array(
					'ID'          => (int) $post_id,
					'post_status' => 'draft',
				)
			);
		}
	}
}
