<?php
/**
 * Turns a Google Forms API response into a quiz.
 *
 * Unlike the document parsers this one takes decoded JSON rather than a file
 * path: a Form has no binary export, so Google\FormsClient saves the API
 * response to disk and this class reads it back. It is otherwise a sibling of
 * the other parsers — pure, no WordPress calls beyond escaping-free string
 * handling — so it can be unit tested against fixture JSON.
 *
 * ─ Quiz shape ────────────────────────────────────────────────────────────────
 *
 * ParsedQuiz:
 *   { source_format: 'gform', unit_label: 'question', title: string,
 *     description: string, is_quiz: bool, questions: Question[],
 *     skipped: Skipped[], slides: [] }
 *
 * Question:
 *   { index: int, title: string, description: string,
 *     type: 'single'|'multiple'|'essay', points: int,
 *     answers: { text: string, correct: bool }[],
 *     correct_msg: string, incorrect_msg: string, warnings: string[] }
 *
 * Skipped:
 *   { index: int, title: string, reason: string }
 *
 * `index` is the 1-based position among the form's questions, counting skipped
 * ones, so a warning can be matched to the question an editor sees in Forms.
 *
 * `slides` is always empty. The pipeline keys several things off that field
 * (the slide-map UI, per-entry overrides), and an empty list is the honest
 * answer for a quiz: there is nothing to classify.
 *
 * @class   Forms\Parser
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Forms;

use CodingBlackFemales\SlidesImporter\Document\ParserFactory;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parser class.
 */
final class Parser {

	const TYPE_SINGLE   = 'single';
	const TYPE_MULTIPLE = 'multiple';
	const TYPE_ESSAY    = 'essay';

	/** Points a question is worth when the form does not say. */
	const DEFAULT_POINTS = 1;

	/**
	 * Parse a saved Forms API response.
	 *
	 * The second argument exists so the signature matches the other parsers'
	 * `parse( $path, $img_dir )`, letting ParserFactory dispatch without
	 * special-casing. A form has no images to extract.
	 *
	 * @param  string $path    Absolute path to the saved API response (JSON).
	 * @param  string $img_dir Unused.
	 * @return array|WP_Error  ParsedQuiz, or WP_Error when the file is unreadable.
	 */
	public static function parse( string $path, string $img_dir = '' ): array|WP_Error {
		unset( $img_dir );

		$json = is_readable( $path ) ? file_get_contents( $path ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$form = is_string( $json ) ? json_decode( $json, true ) : null;

		if ( ! is_array( $form ) || ( ! isset( $form['items'] ) && ! isset( $form['info'] ) ) ) {
			return new WP_Error(
				'cbf_si_form_unreadable',
				__( 'The saved Google Form could not be read. Re-run the import from scratch.', 'cbf-slides-importer' )
			);
		}

		return self::from_form( $form );
	}


	/**
	 * Build a ParsedQuiz from a decoded Forms API response.
	 *
	 * @param  array $form Decoded `forms.get` response.
	 * @return array ParsedQuiz.
	 */
	public static function from_form( array $form ): array {
		$form  = array_merge(
			array(
				'info'     => array(),
				'settings' => array(),
				'items'    => array(),
			),
			$form
		);
		$info  = array_merge(
			array(
				'title'         => '',
				'documentTitle' => '',
				'description'   => '',
			),
			(array) $form['info']
		);
		$items = self::collect_questions( (array) $form['items'] );
		$title = trim( (string) $info['title'] );

		return array(
			'source_format' => ParserFactory::FORMAT_GFORM,
			'unit_label'    => ParserFactory::unit_label( ParserFactory::FORMAT_GFORM ),
			// A form's own title, or the Drive file name when it has none.
			'title'         => $title !== '' ? $title : trim( (string) $info['documentTitle'] ),
			'description'   => trim( (string) $info['description'] ),
			'is_quiz'       => ! empty( $form['settings']['quizSettings']['isQuiz'] ),
			'questions'     => $items['questions'],
			'skipped'       => $items['skipped'],
			'slides'        => array(),
		);
	}


	/**
	 * Convert every question item, separating what can be imported from what
	 * cannot.
	 *
	 * @param  array $items The form's `items` list.
	 * @return array{questions: array, skipped: array}
	 */
	private static function collect_questions( array $items ): array {
		$questions = array();
		$skipped   = array();
		$position  = 0;

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || ! self::is_question_item( $item ) ) {
				continue;
			}

			++$position;
			$question = self::question_from_item( $item, $position );

			if ( is_string( $question ) ) {
				$skipped[] = array(
					'index'  => $position,
					'title'  => trim( (string) ( $item['title'] ?? '' ) ),
					'reason' => $question,
				);
				continue;
			}

			$questions[] = $question;
		}

		return array(
			'questions' => $questions,
			'skipped'   => $skipped,
		);
	}


	/**
	 * Whether a form item is a question at all.
	 *
	 * Page breaks, text blocks, images and videos share the `items` list with
	 * questions but carry no answer to grade, so they are dropped without a
	 * warning; only things an editor would call a question are reported when
	 * they cannot be imported.
	 *
	 * @param  array $item Form item.
	 * @return bool
	 */
	private static function is_question_item( array $item ): bool {
		return isset( $item['questionItem'] ) || isset( $item['questionGroupItem'] );
	}


	/**
	 * Convert one question item.
	 *
	 * @param  array $item     Form item containing a question.
	 * @param  int   $position 1-based position among the form's questions.
	 * @return array|string    Question, or the reason it cannot be imported.
	 */
	private static function question_from_item( array $item, int $position ): array|string {
		if ( isset( $item['questionGroupItem'] ) ) {
			return __( 'Grid and multi-part questions are not supported.', 'cbf-slides-importer' );
		}

		$question = (array) ( $item['questionItem']['question'] ?? array() );
		$grading  = (array) ( $question['grading'] ?? array() );

		if ( isset( $question['choiceQuestion'] ) ) {
			return self::choice_question( $item, $question, $grading, $position );
		}

		if ( isset( $question['textQuestion'] ) ) {
			return self::base_question( $item, $grading, $position, self::TYPE_ESSAY );
		}

		return __( 'This question type (scale, date, time, file upload or rating) is not supported.', 'cbf-slides-importer' );
	}


	/**
	 * Convert a multiple-choice, checkbox or dropdown question.
	 *
	 * @param  array $item     Form item.
	 * @param  array $question The item's `question` object.
	 * @param  array $grading  The question's `grading` object, possibly empty.
	 * @param  int   $position 1-based position among the form's questions.
	 * @return array|string    Question, or the reason it cannot be imported.
	 */
	private static function choice_question( array $item, array $question, array $grading, int $position ): array|string {
		$choice  = (array) $question['choiceQuestion'];
		$options = self::choice_options( (array) ( $choice['options'] ?? array() ), self::correct_values( $grading ) );
		$right   = count( array_filter( array_column( $options['answers'], 'correct' ) ) );
		$problem = self::choice_problem( count( $options['answers'] ), $right );

		if ( $problem !== null ) {
			return $problem;
		}

		// A radio or dropdown question whose key accepts several answers can only
		// be graded as a multiple-answer question.
		$type = ( $choice['type'] ?? '' ) === 'CHECKBOX' || $right > 1 ? self::TYPE_MULTIPLE : self::TYPE_SINGLE;

		$built             = self::base_question( $item, $grading, $position, $type );
		$built['answers']  = $options['answers'];
		$built['warnings'] = $options['warnings'];

		return $built;
	}


	/**
	 * Turn a choice question's options into answers.
	 *
	 * @param  array    $options The question's `options` list.
	 * @param  string[] $correct Option values the answer key marks correct.
	 * @return array{answers: array, warnings: string[]}
	 */
	private static function choice_options( array $options, array $correct ): array {
		$answers  = array();
		$warnings = array();

		foreach ( $options as $option ) {
			// "Other" is a free-text slot; there is nothing to mark right or wrong.
			if ( ! empty( $option['isOther'] ) ) {
				$warnings[] = __( 'The "Other" option was left out.', 'cbf-slides-importer' );
				continue;
			}

			$text = trim( (string) ( $option['value'] ?? '' ) );

			if ( $text !== '' ) {
				$answers[] = array(
					'text'    => $text,
					'correct' => in_array( $text, $correct, true ),
				);
			}
		}

		return array(
			'answers'  => $answers,
			'warnings' => $warnings,
		);
	}


	/**
	 * Why a choice question cannot be graded, if it cannot.
	 *
	 * @param  int $answers Number of usable options.
	 * @param  int $right   Number of options the key marks correct.
	 * @return string|null
	 */
	private static function choice_problem( int $answers, int $right ): ?string {
		if ( $answers < 2 ) {
			return __( 'A choice question needs at least two options.', 'cbf-slides-importer' );
		}

		if ( $right === 0 ) {
			return __( 'No correct answer is set in Google Forms, so it cannot be graded. Set an answer key and re-import.', 'cbf-slides-importer' );
		}

		return null;
	}


	/**
	 * The fields every question type shares.
	 *
	 * @param  array  $item     Form item.
	 * @param  array  $grading  The question's `grading` object, possibly empty.
	 * @param  int    $position 1-based position among the form's questions.
	 * @param  string $type     One of the TYPE_ constants.
	 * @return array Question with no answers yet.
	 */
	private static function base_question( array $item, array $grading, int $position, string $type ): array {
		return array(
			'index'         => $position,
			'title'         => self::question_title( $item, $position ),
			'description'   => trim( (string) ( $item['description'] ?? '' ) ),
			'type'          => $type,
			'points'        => max( self::DEFAULT_POINTS, (int) ( $grading['pointValue'] ?? self::DEFAULT_POINTS ) ),
			'answers'       => array(),
			'correct_msg'   => self::feedback( $grading, 'whenRight' ),
			'incorrect_msg' => self::feedback( $grading, 'whenWrong' ),
			'warnings'      => array(),
		);
	}


	/**
	 * A question's title, or "Question n" when the form left it blank.
	 *
	 * @param  array $item     Form item.
	 * @param  int   $position 1-based position among the form's questions.
	 * @return string
	 */
	private static function question_title( array $item, int $position ): string {
		$title = trim( (string) ( $item['title'] ?? '' ) );

		if ( $title !== '' ) {
			return $title;
		}

		return sprintf(
			/* translators: %d: 1-based question number */
			__( 'Question %d', 'cbf-slides-importer' ),
			$position
		);
	}


	/**
	 * Feedback text shown for a right or wrong answer.
	 *
	 * @param  array  $grading The question's `grading` object.
	 * @param  string $key     `whenRight` or `whenWrong`.
	 * @return string
	 */
	private static function feedback( array $grading, string $key ): string {
		$feedback = (array) ( $grading[ $key ] ?? array() );

		return trim( (string) ( $feedback['text'] ?? '' ) );
	}


	/**
	 * The option values Forms marks as correct.
	 *
	 * @param  array $grading The question's `grading` object.
	 * @return string[]
	 */
	private static function correct_values( array $grading ): array {
		$values = array();

		foreach ( (array) ( $grading['correctAnswers']['answers'] ?? array() ) as $answer ) {
			$values[] = trim( (string) ( $answer['value'] ?? '' ) );
		}

		return $values;
	}
}
