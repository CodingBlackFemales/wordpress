<?php
/**
 * Quiz creation AI module.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI;

use Exception;
use InvalidArgumentException;
use LearnDash\Core\Utilities\Sanitize;
use LearnDash\Core\Modules\AI\Quiz_Creation\DTO;
use LearnDash\Core\Modules\AI\Quiz_Creation\Parser;
use LearnDash\Core\Modules\AI\Quiz_Creation\Repository;
use LearnDash\Core\Services\ChatGPT;

/**
 * Quiz creation AI class.
 *
 * @since 4.8.0
 */
class Quiz_Creation {
	/**
	 * Single choice question type key.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public static $question_type_key_single_choice = 'single';

	/**
	 * Multiple choice question type key.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public static $question_type_key_multiple_choice = 'multiple';

	/**
	 * Free question type key.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public static $question_type_key_free_choice = 'free_answer';

	/**
	 * Sort question type key.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public static $question_type_key_sorting_choice = 'sort_answer';

	/**
	 * Matrix sort question type key.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public static $question_type_key_matrix_sorting_choice = 'matrix_sort_answer';

	/**
	 * Fill in the blank question type key.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public static $question_type_key_fill_in_the_blank = 'cloze_answer';

	/**
	 * Assessment question type key.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public static $question_type_key_assessment = 'assessment_answer';

	/**
	 * Essay question type key.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public static $question_type_key_essay = 'essay';

	/**
	 * Page slug.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public static $slug = 'learndash-ai-quiz-creation';

	/**
	 * Transient key to store messages.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public static $transient_key_messages = 'learndash_ai_quiz_creation_messages';

	/**
	 * Limit number of allowed questions and answers.
	 *
	 * 30 is supposedly a safe number of items to generate by AI provider before
	 * resulting in exceeded token per request or rate limit error.
	 *
	 * @since 4.8.0
	 *
	 * @var int
	 */
	private static $questions_limit = 30;

	/**
	 * ChatGPT client.
	 *
	 * @since 4.8.0
	 *
	 * @var ChatGPT
	 */
	private $chatgpt;

	/**
	 * Quiz creation AI response parser.
	 *
	 * @since 4.8.0
	 *
	 * @var Parser
	 */
	private $parser;

	/**
	 * Quiz creation AI repository object.
	 *
	 * @since 4.8.0
	 *
	 * @var Repository
	 */
	private $repository;

	/**
	 * Quiz DTO used to pass quiz data.
	 *
	 * @since 4.8.0
	 *
	 * @var DTO\Quiz
	 */
	private $quiz;

	/**
	 * Constructor.
	 *
	 * @since 4.8.0
	 *
	 * @param ChatGPT    $chatgpt    ChatGPT client.
	 * @param Parser     $parser     Quiz creation AI response parser.
	 * @param Repository $repository Repository object.
	 */
	public function __construct( ChatGPT $chatgpt, Parser $parser, Repository $repository ) {
		$this->chatgpt    = $chatgpt;
		$this->parser     = $parser;
		$this->repository = $repository;
	}

	/**
	 * Get LearnDash question types.
	 *
	 * @since 4.8.0
	 *
	 * @return array<string, string>
	 */
	public static function get_question_types(): array {
		return [
			self::$question_type_key_single_choice         => __( 'Single choice', 'learndash' ),
			self::$question_type_key_multiple_choice       => __( 'Multiple choice', 'learndash' ),
			self::$question_type_key_free_choice           => __( 'Free choice', 'learndash' ),
			self::$question_type_key_sorting_choice        => __( 'Sorting choice', 'learndash' ),
			self::$question_type_key_matrix_sorting_choice => __( 'Matrix Sorting choice', 'learndash' ),
			self::$question_type_key_fill_in_the_blank     => __( 'Fill in the blank', 'learndash' ),
			self::$question_type_key_assessment            => __( 'Assessment', 'learndash' ),
			self::$question_type_key_essay                 => __( 'Essay / Open Answer', 'learndash' ),
		];
	}

	/**
	 * Execute ChatGPT command.
	 *
	 * @since 4.8.0
	 *
	 * @return void
	 */
	public function init(): void {
		/**
		 * General process results object.
		 *
		 * @var array<DTO\Process>
		 */
		$processes = [];

		/**
		 * Notice messages for user.
		 *
		 * @var array<array{is_success: bool, message: string}>
		 */
		$messages = [];

		try {
			$args = $this->prepare_fields();

			/**
			 * Question types.
			 *
			 * @var array<string>
			 */
			$question_types = $args['question_types'] ?? [];

			foreach ( $question_types as $question_type ) {
				$request = DTO\Request::create(
					[
						'question_type'            => $question_type,
						'total_questions_per_type' => $args['total_questions_per_type'],
						'quiz_title'               => $args['quiz_title'],
						'quiz_idea'                => $args['quiz_idea'],
					]
				);

				$this->quiz = DTO\Quiz::create(
					[
						'id'               => ! empty( $this->quiz->id )
							? $this->quiz->id
							: $args['quiz_id'],
						'title'            => $args['quiz_title'],
						'course_id'        => $args['course_id'],
						'lesson_id'        => $args['lesson_id'],
						'topic_id'         => $args['topic_id'],
						'parent_id'        => $args['parent_id'],
						'parent_post_type' => $args['parent_post_type'],
					]
				);

				$command         = $this->prepare_command( $request );
				$response        = $this->chatgpt->send_command(
					$command,
					[
						'quiz' => $this->quiz,
					]
				);
				$parsed_response = $this->parser->parse( $response, $request );
				$processes       = array_merge(
					$processes,
					$this->process( $parsed_response, $request )
				);
			}
		} catch ( Exception $e ) {
			$processes = [
				DTO\Process::create(
					[
						'is_success' => false,
						'message'    => wp_sprintf(
							'%s',
							$e->getMessage()
						),
					]
				),
			];
		}

		// Redirect after processing.

		$successes = array_filter(
			$processes,
			function( $process ) {
				return $process->is_success;
			}
		);

		if ( ! empty( $successes ) ) {
			$messages[] = [
				'is_success' => true,
				'message'    => wp_sprintf(
					// translators: 1$: quiz label, 2$: quiz title.
					__( 'The request has been processed successfully for the %1$s: %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'quiz' ),
					'<a href="' . get_edit_post_link( $this->quiz->id ) . '">' . get_the_title( $this->quiz->id ) . '</a>'
				),
			];
		};

		/**
		 * Errors process results object.
		 *
		 * @var array<DTO\Process>
		 */
		$errors = array_filter(
			$processes,
			function( $process ) {
				return ! $process->is_success;
			}
		);

		$messages = array_merge(
			$messages,
			array_map(
				function( $error ) {
					return $error->to_array();
				},
				$errors
			)
		);

		set_transient( self::$transient_key_messages, $messages, MINUTE_IN_SECONDS );

		$redirect_url = add_query_arg(
			[
				'page'      => self::$slug,
				'processed' => true,
			],
			admin_url( 'admin.php' )
		);

		learndash_safe_redirect( $redirect_url );
	}

	/**
	 * Sanitize and prepare fields.
	 *
	 * @since 4.8.0
	 *
	 * @throws Exception                General error.
	 * @throws InvalidArgumentException Incorrect field value.
	 *
	 * @return array<string, mixed>
	 */
	protected function prepare_fields(): array {
		if (
			! current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK )
			|| ! check_admin_referer( self::$slug )
		) {
			throw new Exception( 'Unauthorized access.' );
		}

		$args = [];

		$args['total_questions_per_type'] = absint( wp_unslash( $_POST['total_questions_per_type'] ?? 0 ) );

		$args['question_types'] = isset( $_POST['question_types'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The helper method sanitizes the array.
			? Sanitize::array( wp_unslash( $_POST['question_types'] ) )
			: [];

		$args['total_questions'] = count( $args['question_types'] ) * $args['total_questions_per_type'];

		if ( $args['total_questions'] > self::$questions_limit ) {
			throw new InvalidArgumentException(
				wp_sprintf(
					// translators: 1$: Maximum amount of questions, 2$: Questions label.
					__( '%1$s is a maximum number of %2$s across all question types.', 'learndash' ),
					self::$questions_limit,
					learndash_get_custom_label_lower( 'questions' )
				)
			);
		}

		/**
		 * Decode `quiz` field. We pass json string from the field to be able to contain many information for a new or an existing quiz.
		 *
		 * @var ?array{id: ?int, new: ?string, title: string}
		 */
		$quiz = isset( $_POST['quiz'] )
			? json_decode(
				sanitize_text_field( wp_unslash( $_POST['quiz'] ) ),
				true
			)
			: null;

		if ( ! $quiz ) {
			throw new InvalidArgumentException(
				wp_sprintf(
					// translators: Quiz label.
					__( '%s is required field.', 'learndash' ),
					learndash_get_custom_label( 'quiz' )
				)
			);
		}

		$args['quiz_id']    = absint( $quiz['id'] ?? 0 );
		$args['quiz_title'] = empty( $args['quiz_id'] )
			? sanitize_text_field( $quiz['title'] )
			: get_the_title( $args['quiz_id'] );

		$args['course_id'] = isset( $_POST['course_id'] ) ? absint( wp_unslash( $_POST['course_id'] ) ) : null;

		$args['lesson_id'] = isset( $_POST['lesson_id'] ) ? absint( wp_unslash( $_POST['lesson_id'] ) ) : null;

		$args['topic_id'] = isset( $_POST['topic_id'] ) ? absint( wp_unslash( $_POST['topic_id'] ) ) : null;

		if ( ! empty( $args['topic_id'] ) ) {
			$args['parent_id'] = $args['topic_id'];
		} elseif ( ! empty( $args['lesson_id'] ) ) {
			$args['parent_id'] = $args['lesson_id'];
		} elseif ( ! empty( $args['course_id'] ) ) {
			$args['parent_id'] = $args['course_id'];
		} else {
			$args['parent_id'] = null;
		}

		$parent_post_type         = get_post_type( $args['parent_id'] );
		$args['parent_post_type'] = $parent_post_type !== false ? $parent_post_type : null;

		$args['quiz_idea'] = isset( $_POST['quiz_idea'] )
			? sanitize_textarea_field( wp_unslash( $_POST['quiz_idea'] ) )
			: '';

		return $args;
	}

	/**
	 * Prepare command to be sent to AI provider.
	 *
	 * @since 4.8.0
	 *
	 * @param DTO\Request $request Request arguments.
	 *
	 * @return string
	 */
	protected function prepare_command( DTO\Request $request ): string {
		$command = '';

		// This doesn't need translation gettext function nor custom label since it'll be used as AI prompt.
		$question_label = $request->total_questions_per_type > 1
			? 'questions'
			: 'question';

		$command_label = $request->total_questions_per_type > 1
			? 'commands'
			: 'command';

		switch ( $request->question_type ) {
			case self::$question_type_key_single_choice:
				$command = "Create {$request->total_questions_per_type} single choice {$question_label} with answer options, and correct answer options for a '{$request->quiz_title}' quiz on the topic of '{$request->quiz_idea}'. Please display the results in JSON format under 'questions' key. Put each question and its answer options and correct answer options under one object. Put the {$question_label} under 'question' key, the answer options under 'answer_options' key, and correct answer options under 'correct_answers' key.";
				break;

			case self::$question_type_key_multiple_choice:
				$command = "Create {$request->total_questions_per_type} multiple choice {$question_label} with answer options, and multiple correct answer options for a '{$request->quiz_title}' quiz on the topic of '{$request->quiz_idea}'. Please display the results in JSON format under 'questions' key. Put each question and its answer options and correct answer options under one object. Put the {$question_label} under 'question' key, the answer options under 'answer_options' key, and correct answer options under 'correct_answers' key.";
				break;

			case self::$question_type_key_free_choice:
				$command = "Create {$request->total_questions_per_type} free choice {$question_label} with multiple correct answers for a '{$request->quiz_title}' quiz on the topic of '{$request->quiz_idea}'. Please display the results in JSON format under 'questions' key. Put the {$question_label} under 'question' key and correct answer options under 'correct_answers' key.";
				break;

			case self::$question_type_key_sorting_choice:
				$command = "Create {$request->total_questions_per_type} {$question_label} or {$command_label} with answer options that requires sorting the answer options for a '{$request->quiz_title}' quiz on the topic of '{$request->quiz_idea}'. Please display the results in JSON format under 'questions' key. Put each question or command and its answer options under one object. Put the {$question_label} or {$command_label} under 'question' key, the answer options under 'answer_options' key and the correct order under 'correct_answers' key.";
				break;

			case self::$question_type_key_matrix_sorting_choice:
				$command = "Create {$request->total_questions_per_type} {$question_label} that have a list of multiple criteria and its corresponding values on the topic of '{$request->quiz_idea}'.

				Please display the results in JSON format under 'questions' key. Put the {$question_label} under 'question' key, the criteria key and its single corresponding value under 'criteria' key with 'key' and 'value' sub keys.";
				break;

			case self::$question_type_key_fill_in_the_blank:
				$command = "Create {$request->total_questions_per_type} 'fill in the blank' {$question_label} and a correct answer for a '{$request->quiz_title}' quiz on the topic of '{$request->quiz_idea}'. Please display the results in JSON format with 'questions' key. Put each question and its correct answer under one object. Put the {$question_label} under 'question' key and the correct answer under 'correct_answer' key.";
				break;

			case self::$question_type_key_assessment:
				$command = "Create {$request->total_questions_per_type} {$question_label} with likert scale answer options for a '{$request->quiz_title}' quiz on the topic of '{$request->quiz_idea}'. Please display the results in JSON format under `questions` key. Put each question and its answer options under one object. Put the {$question_label} under 'question' key and the answer options under 'answer_options' key.";
				break;

			case self::$question_type_key_essay:
				$command = "Create {$request->total_questions_per_type} essay {$question_label} for a '{$request->quiz_title}' quiz on the topic of '{$request->quiz_idea}'. Please display the results in JSON format under `questions` key. Put the question under 'question' key.";
				break;
		}

		return $command;
	}

	/**
	 * Process parsed response.
	 *
	 * @since 4.8.0
	 *
	 * @throws Exception No question returned by AI.
	 *
	 * @param DTO\Parsed_Response $parsed_response Response text after being parsed.
	 * @param DTO\Request         $request         User request parameters.
	 *
	 * @return array<DTO\Process>
	 */
	protected function process( DTO\Parsed_Response $parsed_response, DTO\Request $request ): array {
		$processes = [];

		// Error handlers.

		if ( ! $parsed_response->is_success ) {
			$processes[] = DTO\Process::create(
				[
					'is_success' => false,
					'message'    => $parsed_response->message,
				]
			);

			return $processes;
		} elseif ( count( $parsed_response->questions ) < 1 ) {
			$processes[] = DTO\Process::create(
				[
					'is_success' => false,
					'message'    => wp_sprintf(
						// translators: question type.
						__( 'No questions returned for the question type: %s. Please try again with a different idea.', 'learndash' ),
						$request->question_type
					),
				]
			);

			return $processes;
		}

		// Quiz and questions creation.

		if ( empty( $this->quiz->id ) ) {
			try {
				$this->quiz->id = $this->repository->create_quiz( $this->quiz );
			} catch ( Exception $e ) {
				$processes[] = DTO\Process::create(
					[
						'is_success' => false,
						'message'    => wp_sprintf(
							// translators: 1$: quiz label, 2$: error message.
							__( 'Failed to create %1$s: %2$s', 'learndash' ),
							learndash_get_custom_label_lower( 'quiz' ),
							$e->getMessage()
						),
					]
				);
			}
		}

		foreach ( $parsed_response->questions as $question ) {
			try {
				$this->repository->create_question( $this->quiz->id, $question );
			} catch ( Exception $e ) {
				$processes[] = DTO\Process::create(
					[
						'is_success' => false,
						'message'    => wp_sprintf(
							// translators: 1$: question label, 2$: error message.
							__( 'Failed to create %1$s: %2$s', 'learndash' ),
							learndash_get_custom_label_lower( 'question' ),
							$e->getMessage()
						),
					]
				);
			}
		}

		// Success.

		$processes[] = DTO\Process::create(
			[
				'is_success' => true,
			]
		);

		return $processes;
	}
}
