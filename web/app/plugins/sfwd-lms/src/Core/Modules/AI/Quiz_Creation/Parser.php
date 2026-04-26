<?php
/**
 * Quiz creation AI parser.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Quiz_Creation;

use Exception;
use LearnDash\Core\Modules\AI\Quiz_Creation;
use LearnDash\Core\Modules\AI\Quiz_Creation\DTO;

/**
 * Quiz creation AI parser class.
 *
 * @since 4.8.0
 */
class Parser {
	/**
	 * Parse ChatGPT response.
	 *
	 * @since 4.8.0
	 *
	 * @param string      $response Response text given by AI service provider.
	 * @param DTO\Request $request  Request parameters.
	 *
	 * @return DTO\Parsed_Response
	 */
	public function parse( string $response, DTO\Request $request ): DTO\Parsed_Response {
		try {
			switch ( $request->question_type ) {
				case Quiz_Creation::$question_type_key_single_choice:
					$parsed_response = $this->parse_single_type( $response, $request );
					break;

				case Quiz_Creation::$question_type_key_multiple_choice:
					$parsed_response = $this->parse_multiple_type( $response, $request );
					break;

				case Quiz_Creation::$question_type_key_free_choice:
					$parsed_response = $this->parse_free_choice_type( $response, $request );
					break;

				case Quiz_Creation::$question_type_key_sorting_choice:
					$parsed_response = $this->parse_sorting_choice_type( $response, $request );
					break;

				case Quiz_Creation::$question_type_key_matrix_sorting_choice:
					$parsed_response = $this->parse_matrix_sorting_choice_type( $response, $request );
					break;

				case Quiz_Creation::$question_type_key_fill_in_the_blank:
					$parsed_response = $this->parse_fill_in_the_blank_type( $response, $request );
					break;

				case Quiz_Creation::$question_type_key_assessment:
					$parsed_response = $this->parse_assessment_type( $response, $request );
					break;

				case Quiz_Creation::$question_type_key_essay:
					$parsed_response = $this->parse_essay_type( $response, $request );
					break;

				default:
					$parsed_response = DTO\Parsed_Response::create();
					break;
			}
		} catch ( Exception $e ) {
			return DTO\Parsed_Response::create(
				[
					'is_success' => false,
					'message'    => $e->getMessage(),
				]
			);
		}

		/**
		 * Filters the parsed response data.
		 *
		 * @since 4.8.0
		 *
		 * @param DTO\Parsed_Response $parsed_response Response data that is already parsed.
		 * @param DTO\Request         $request         Request parameters.
		 * @param string              $response        Original response by AI.
		 *
		 * @return DTO\Parsed_Response Parsed response DTO that will be processed.
		 */
		return apply_filters( 'learndash_module_ai_quiz_creation_parsed_response', $parsed_response, $request, $response );
	}

	/**
	 * Helper method to parse response JSON string.
	 *
	 * @since 4.8.0
	 *
	 * @param string      $response AI response.
	 * @param DTO\Request $request  Request DTO.
	 *
	 * @return DTO\Parsed_Response
	 */
	private function parse_json_response( string $response, DTO\Request $request ): DTO\Parsed_Response {
		$response       = json_decode( wp_unslash( $response ), true );
		$response_array = is_array( $response )
			? array_values( $response )
			: [];

		if ( empty( $response_array ) ) {
			return DTO\Parsed_Response::create();
		}

		$questions = [];
		foreach ( $response_array[0] as $key => $object ) {
			$questions[ $key ] = DTO\Question::create(
				[
					'type'    => $request->question_type,
					'title'   => $object['question'] ?? '',
					'answers' => isset( $object['answer_options'] )
						? array_map(
							function( $answer, $key ) use ( $object ) {
								// Anticipate if correct_answers only contains answer keys instead of full answer texts.

								/**
								 * Find answer key.
								 *
								 * Regex patterns:
								 *
								 * 1st part:
								 *   ^([\w\d]+) : Find letter or number key in the beginning of text before any separator, such as 1., A), 2), and so on.
								 *
								 * 2nd part:
								 *   [\.\)] : Find any separator after the letter or number key.
								 *
								 * 1st capturing group: Letter or number key.
								 */
								preg_match( '/^([\w\d]+)[\.\)]/i', trim( $answer ), $matches );

								$is_correct = false;

								// First correct answer check.

								if (
									isset( $object['correct_answers'] )
									&& is_array( $object['correct_answers'] )
									&& in_array( $answer, $object['correct_answers'], true )
								) {
									$is_correct = true;
								}

								// Second correct answer check.

								if (
									! empty( $matches[1] )
									&& isset( $object['correct_answers'] )
									&& is_array( $object['correct_answers'] )
									&& in_array( $matches[1], $object['correct_answers'], true )
								) {
									$is_correct = true;
								}

								return DTO\Answer::create(
									[
										'id'         => $key,
										'title'      => $answer,
										'is_correct' => $is_correct,
									]
								);
							},
							$object['answer_options'],
							array_keys( $object['answer_options'] )
						)
						: [],
				]
			);
		}

		return DTO\Parsed_Response::create(
			[
				/**
				 * Filters questions created from JSON parser.
				 *
				 * @since 4.8.0
				 *
				 * @param DTO\Question[]              $questions      Collection of questions.
				 * @param array<array<string, mixed>> $response_array Response JSON that has been converted to array.
				 * @param DTO\Request                 $request        Request object.
				 *
				 * @return DTO\Question[] Filtered questions.
				 */
				'questions' => apply_filters(
					'learndash_module_ai_quiz_creation_parsed_questions',
					$questions,
					$response_array,
					$request
				),
			]
		);
	}

	/**
	 * Parse AI response for single question type.
	 *
	 * @since 4.8.0
	 *
	 * @param string      $response AI response.
	 * @param DTO\Request $request  Request DTO.
	 *
	 * @return DTO\Parsed_Response
	 */
	private function parse_single_type( string $response, DTO\Request $request ): DTO\Parsed_Response {
		return $this->parse_json_response( $response, $request );
	}

	/**
	 * Parse AI response for multiple question type.
	 *
	 * @since 4.8.0
	 *
	 * @param string      $response AI response.
	 * @param DTO\Request $request  Request DTO.
	 *
	 * @return DTO\Parsed_Response
	 */
	private function parse_multiple_type( string $response, DTO\Request $request ): DTO\Parsed_Response {
		add_filter(
			'learndash_module_ai_quiz_creation_parsed_questions',
			function( $questions, $response_array, $request ) {
				if ( $request->question_type !== Quiz_Creation::$question_type_key_multiple_choice ) {
					return $questions;
				}

				foreach ( $questions as $key => $question ) {
					// Check if question answer options don't have correct answers.

					// Skip if there's correct answer option.
					foreach ( $question->answers as $answer_key => $answer ) {
						if ( $answer->is_correct ) {
							continue 2;
						}
					}

					$object = $response_array[0][ $key ];

					$answer_options  = $object['answer_options'] ?? [];
					$correct_answers = $object['correct_answers'] ?? [];

					// We merge original response answer options with correct answers to prevent issue with multiple choice question type which sometimes its correct answers are not included in the answer options.

					$final_answer_options = is_array( $answer_options )
					&& is_array( $correct_answers )
						? array_unique(
							array_merge(
								$answer_options,
								$correct_answers
							)
						)
						: [];

					$new_answers = [];
					foreach ( $final_answer_options as $answer_key => $answer_text ) {
						$new_answers[] = DTO\Answer::create(
							[
								'id'         => $answer_key,
								'title'      => $answer_text,
								'is_correct' => in_array( $answer_text, $correct_answers, true ),
							]
						);
					}

					$questions[ $key ]->answers = $new_answers;
				}

				return $questions;
			},
			10,
			3
		);

		return $this->parse_json_response( $response, $request );
	}

	/**
	 * Parse AI response for free choice question type.
	 *
	 * @since 4.8.0
	 *
	 * @param string      $response AI response.
	 * @param DTO\Request $request  Request DTO.
	 *
	 * @return DTO\Parsed_Response
	 */
	private function parse_free_choice_type( string $response, DTO\Request $request ): DTO\Parsed_Response {
		add_filter(
			'learndash_module_ai_quiz_creation_parsed_questions',
			function( $questions, $response_array, $request ) {
				if ( $request->question_type === Quiz_Creation::$question_type_key_free_choice ) {
					foreach ( $questions as $key => $question ) {
						$questions[ $key ]->answers = [
							DTO\Answer::create(
								[
									'id'    => 0,
									'title' => implode(
										"\n",
										array_map(
											function( $answer ) {
												return trim( $answer );
											},
											$response_array[0][ $key ]['correct_answers']
										)
									),
								]
							),
						];
					}
				}

				return $questions;
			},
			10,
			3
		);

		return $this->parse_json_response( $response, $request );
	}

	/**
	 * Parse AI response for sorting choice question type.
	 *
	 * @since 4.8.0
	 *
	 * @param string      $response AI response.
	 * @param DTO\Request $request  Request DTO.
	 *
	 * @return DTO\Parsed_Response
	 */
	private function parse_sorting_choice_type( string $response, DTO\Request $request ): DTO\Parsed_Response {
		add_filter(
			'learndash_module_ai_quiz_creation_parsed_questions',
			function( $questions, $response_array, $request ) {
				if ( $request->question_type === Quiz_Creation::$question_type_key_sorting_choice ) {
					foreach ( $questions as $key => $question ) {
						$object = $response_array[0][ $key ];

						$questions[ $key ]->answers = array_map(
							function( $answer, $key ) {
								return DTO\Answer::create(
									[
										'id'    => $key,
										'title' => $answer,
									]
								);
							},
							$object['correct_answers'],
							array_keys( $object['correct_answers'] )
						);
					}
				}

				return $questions;
			},
			10,
			3
		);

		return $this->parse_json_response( $response, $request );
	}


	/**
	 * Parse AI response for matrix sorting choice question type.
	 *
	 * @since 4.8.0
	 *
	 * @param string      $response AI response.
	 * @param DTO\Request $request  Request DTO.
	 *
	 * @throws Exception If the response is invalid.
	 *
	 * @return DTO\Parsed_Response
	 */
	private function parse_matrix_sorting_choice_type( string $response, DTO\Request $request ): DTO\Parsed_Response {
		add_filter(
			'learndash_module_ai_quiz_creation_parsed_questions',
			function( $questions, $response_array, $request ) {
				if ( $request->question_type !== Quiz_Creation::$question_type_key_matrix_sorting_choice ) {
					return $questions;
				}

				foreach ( $questions as $key => $question ) {
					$object = $response_array[0][ $key ];

					$questions[ $key ]->answers = array_map(
						function( $criterion, $key ) {
							if (
								empty( $criterion['key'] )
								|| empty( $criterion['value'] )
								|| ! is_string( $criterion['key'] )
								|| ! is_string( $criterion['value'] )
							) {
								throw new Exception( __( 'The response for matrix sorting question type is invalid so it was skipped.', 'learndash' ) );
							}

							return DTO\Answer::create(
								[
									'id'     => $key,
									'params' => [
										'criterion'       => $criterion['key'],
										'criterion_value' => $criterion['value'],
									],
								]
							);
						},
						$object['criteria'] ?? [],
						array_keys( $object['criteria'] ?? [] )
					);
				}

				return $questions;
			},
			10,
			3
		);

		return $this->parse_json_response( $response, $request );
	}

	/**
	 * Parse AI response for fill in the blank question type.
	 *
	 * @since 4.8.0
	 *
	 * @param string      $response AI response.
	 * @param DTO\Request $request  Request DTO.
	 *
	 * @return DTO\Parsed_Response
	 */
	private function parse_fill_in_the_blank_type( string $response, DTO\Request $request ): DTO\Parsed_Response {
		add_filter(
			'learndash_module_ai_quiz_creation_parsed_questions',
			function( $questions, $response_array, $request ) {
				if ( $request->question_type === Quiz_Creation::$question_type_key_fill_in_the_blank ) {
					foreach ( $questions as $key => $question ) {
						$object = $response_array[0][ $key ];

						$questions[ $key ]->answers[0] = DTO\Answer::create(
							[
								'id'    => $key,
								'title' => $object['correct_answer'],
							]
						);
					}
				}

				return $questions;
			},
			10,
			3
		);

		return $this->parse_json_response( $response, $request );
	}

	/**
	 * Parse AI response for assessment question type.
	 *
	 * @since 4.8.0
	 *
	 * @param string      $response AI response.
	 * @param DTO\Request $request  Request DTO.
	 *
	 * @return DTO\Parsed_Response
	 */
	private function parse_assessment_type( string $response, DTO\Request $request ): DTO\Parsed_Response {
		add_filter(
			'learndash_module_ai_quiz_creation_parsed_questions',
			function( $questions, $response_array, $request ) {
				if ( $request->question_type === Quiz_Creation::$question_type_key_assessment ) {
					foreach ( $questions as $key => $question ) {
						// Convert answers to assessment type specific answer format, e.g. "{ [Answer 1] [Answer 2] [Answer 3]}".

						$questions[ $key ]->answers = [
							DTO\Answer::create(
								[
									'id'    => 0,
									'title' => '{ ' . implode(
										' ',
										array_map(
											function( $answer ) {
												return '[' . $answer->title . ']';
											},
											$question->answers
										)
									) . ' }',
								]
							),
						];
					}
				}

				return $questions;
			},
			10,
			3
		);

		return $this->parse_json_response( $response, $request );
	}

	/**
	 * Parse AI response for essay question type.
	 *
	 * @since 4.8.0
	 *
	 * @param string      $response AI response.
	 * @param DTO\Request $request  Request DTO.
	 *
	 * @return DTO\Parsed_Response
	 */
	private function parse_essay_type( string $response, DTO\Request $request ): DTO\Parsed_Response {
		return $this->parse_json_response( $response, $request );
	}
}
