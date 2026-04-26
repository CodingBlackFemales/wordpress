<?php
/**
 * LearnDash Quiz OpenAPI Schema Class.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 *
 * cspell:ignore showin .
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Schemas;

use WpProQuiz_Model_Form;

/**
 * Class that provides LearnDash Quiz OpenAPI schema.
 *
 * @since 5.0.0
 */
class Quiz extends WP_Post {
	/**
	 * Returns the OpenAPI response schema for a LearnDash Quiz.
	 *
	 * @since 5.0.0
	 *
	 * @return array{
	 *     type: string,
	 *     properties: array<string,array<string,mixed>>,
	 *     required: array<string>,
	 * }
	 */
	public static function get_schema(): array {
		// Get the base WP_Post schema.
		$base_schema = parent::get_schema();

		$quiz_singular_lowercase = learndash_get_custom_label_lower( 'quiz' );

		// Add LearnDash Quiz specific properties based on actual API response.
		$quiz_properties = [
			// Quiz visibility and access.
			'visible_type'                    => [
				'type'        => 'string',
				'description' => __( 'Availability Release Schedule. Empty means immediately available, "visible_after" means available X days after enrollment, "visible_after_specific_date" means available on a specific date.', 'learndash' ),
				'enum'        => [ '', 'visible_after', 'visible_after_specific_date' ],
				'example'     => 'visible_after_specific_date',
			],
			'visible_after'                   => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( 'Released X day(s) after %s enrollment', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'example'     => '',
			],
			'visible_after_specific_date'     => [
				'type'        => 'string',
				'description' => __( 'Available after a specific date as a unix timestamp', 'learndash' ),
				'example'     => '1751587200',
			],
			'is_external'                     => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Quiz label (lowercase) */
					__( 'Whether the %s takes place in a virtual setting (e.g, Zoom) or in-person outside of LearnDash.', 'learndash' ),
					$quiz_singular_lowercase
				),
				'example'     => false,
			],
			'external_type'                   => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Quiz label (lowercase) */
					__( 'The type of external %1$s. "virtual" means the %1$s takes place in a virtual setting (e.g, Zoom). "in-person" means the %1$s takes place in-person.', 'learndash' ),
					$quiz_singular_lowercase
				),
				'enum'        => [ 'virtual', 'in-person' ],
				'example'     => 'virtual',
			],
			'external_require_attendance'     => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Quiz label (lowercase) */
					__( 'Whether attendance is required for the external %s.', 'learndash' ),
					$quiz_singular_lowercase
				),
				'example'     => '',
			],

			// Quiz prerequisites and access.
			'prerequisites'                   => [
				'type'        => 'array',
				'description' => sprintf(
					/* translators: %s: Quiz label (lowercase) */
					__( 'The %1$s prerequisites for this %1$s.', 'learndash' ),
					$quiz_singular_lowercase
				),
				'items'       => [
					'type' => 'integer',
				],
				'example'     => [],
			],
			'registered_users_only'           => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Quiz label (lowercase) */
					__( 'Only logged-in users can take this %s', 'learndash' ),
					$quiz_singular_lowercase
				),
				'example'     => true,
			],

			// Quiz scoring and completion.
			'passing_percentage'              => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %s: Quiz label */
					__( '%s Passing Score Percentage', 'learndash' ),
					learndash_get_custom_label( 'quiz' )
				),
				'example'     => 80,
			],
			'certificate_award_threshold'     => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %s: Quiz label */
					__( '%1$s certificate threshold percentage to earn the certificate. The %1$s Certificate value must be set for this to take effect.', 'learndash' ),
					learndash_get_custom_label( 'quiz' )
				),
				'example'     => 80,
			],
			'certificate'                     => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %s: Quiz label (lowercase) */
					__( 'The certificate ID associated with the %s.', 'learndash' ),
					$quiz_singular_lowercase
				),
				'example'     => 556,
			],

			// Quiz behavior and settings.
			'quiz_resume'                     => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Quiz label */
					__( 'Whether the %s can be saved and resumed later.', 'learndash' ),
					learndash_get_custom_label( 'quiz' )
				),
				'example'     => true,
			],
			'quiz_resume_cookie_send_timer'   => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %s: Quiz label */
					__( 'Save %s resume cookie data to the server every x seconds.', 'learndash' ),
					learndash_get_custom_label( 'quiz' )
				),
				'example'     => 30,
			],
			'retry_restrictions_enabled'      => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Quiz label */
					__( 'Whether the %s can be retaken only a certain number of times.', 'learndash' ),
					learndash_get_custom_label( 'quiz' )
				),
				'example'     => true,
			],
			'retry_repeats'                   => [
				'type'        => 'integer',
				'description' => __( 'Number of repeats allowed. blank is unlimited, 0 is 1 repeats, 1 is 2 repeats, etc.', 'learndash' ),
				'example'     => 1,
			],
			'answer_all_questions_enabled'    => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %1$s: Questions label, %2$s: Quiz label */
					__( 'Whether all %1$s are required to complete the %2$s.', 'learndash' ),
					learndash_get_custom_label( 'questions' ),
					learndash_get_custom_label( 'quiz' )
				),
				'example'     => true,
			],

			// Quiz timing.
			'time_limit_enabled'              => [
				'type'        => 'boolean',
				'description' => __( 'Time Limit Enabled', 'learndash' ),
				'example'     => false,
			],
			'time_limit_time'                 => [
				'type'        => 'integer',
				'description' => __( 'Automatically submit after x seconds. Requires the "Time Limit Enabled" setting to be enabled.', 'learndash' ),
				'example'     => 0,
			],

			// Quiz materials.
			'materials_enabled'               => [
				'type'        => 'boolean',
				'description' => __( 'Supplemental Materials Enabled', 'learndash' ),
				'example'     => true,
			],
			'materials'                       => [
				'type'        => 'object',
				'description' => __( 'Supplemental Materials', 'learndash' ),
				'properties'  => [
					'rendered' => [
						'type'        => 'string',
						'description' => __( 'The rendered materials content.', 'learndash' ),
						'example'     => '<p>Test Materials</p>',
					],
				],
			],

			// Quiz display and behavior.
			'auto_start'                      => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Quiz label (lowercase) */
					__( 'Whether the %1$s starts automatically, without the "Start %1$s" button', 'learndash' ),
					$quiz_singular_lowercase
				),
				'example'     => false,
			],
			'quiz_modus'                      => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %1$s: Quiz label (lowercase), %2$s: Question label (lowercase), %3$s: Questions label (lowercase) */
					__( 'The display mode of the %1$s. "single" means one %2$s at a time, "multiple" means all %3$s at once.', 'learndash' ),
					$quiz_singular_lowercase,
					learndash_get_custom_label_lower( 'question' ),
					learndash_get_custom_label_lower( 'questions' )
				),
				'enum'        => [ 'single', 'multiple' ],
				'example'     => 'single',
			],
			'review_table_enabled'            => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Question label (lowercase) */
					__( 'Whether the %s overview table is enabled.', 'learndash' ),
					learndash_get_custom_label_lower( 'question' )
				),
				'example'     => true,
			],
			'summary_hide'                    => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %1$s: Quiz label (lowercase), %2$s: Question label (lowercase) */
					__( 'Whether the %1$s summary is enabled in the %2$s overview table.', 'learndash' ),
					$quiz_singular_lowercase,
					learndash_get_custom_label_lower( 'question' )
				),
				'example'     => false,
			],
			'skip_question_disabled'          => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %1$s: Questions label (lowercase), %2$s: Question label */
					__( 'Whether the %1$s can be skipped. Must use the "One %2$s at a time" and "Display results after each submitted answer" settings in the %2$s Display setting.', 'learndash' ),
					learndash_get_custom_label_lower( 'questions' ),
					learndash_get_custom_label( 'question' )
				),
				'example'     => true,
			],

			// Question sorting and display.
			'custom_sorting'                  => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Quiz label (lowercase) */
					__( 'Whether custom sorting is enabled for the %s.', 'learndash' ),
					$quiz_singular_lowercase
				),
				'example'     => true,
			],
			'sort_categories'                 => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Questions label (lowercase) */
					__( 'Sort %s by category.', 'learndash' ),
					learndash_get_custom_label_lower( 'questions' )
				),
				'example'     => true,
			],
			'question_random'                 => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Question label (lowercase) */
					__( 'Randomize %s order.', 'learndash' ),
					learndash_get_custom_label_lower( 'question' )
				),
				'example'     => true,
			],
			'show_max_question'               => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %1$s: Questions label (lowercase), %2$s: Question label (lowercase) */
					__( 'Whether to only display subset of %1$s. Randomize %2$s order must be enabled.', 'learndash' ),
					learndash_get_custom_label_lower( 'questions' ),
					learndash_get_custom_label_lower( 'question' )
				),
				'example'     => false,
			],
			'show_points'                     => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %1$s: Question label (lowercase), %2$s: Quiz label (lowercase) */
					__( 'Whether to display the point value for each %1$s when taking the %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'question' ),
					$quiz_singular_lowercase
				),
				'example'     => true,
			],
			'show_category'                   => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %1$s: Question label (lowercase), %2$s: Quiz label (lowercase) */
					__( 'Whether to display the category for each %1$s when taking the %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'question' ),
					$quiz_singular_lowercase
				),
				'example'     => true,
			],
			'hide_question_position_overview' => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %1$s: Question label (lowercase), %2$s: Quiz label (lowercase) */
					__( 'Whether to show the position of each %1$s when taking the %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'question' ),
					$quiz_singular_lowercase
				),
				'example'     => true,
			],
			'hide_question_numbering'         => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %1$s: Question label (lowercase), %2$s: Quiz label (lowercase) */
					__( 'Whether to show the number for each %1$s when taking the %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'question' ),
					$quiz_singular_lowercase
				),
				'example'     => false,
			],
			'numbered_answer'                 => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %1$s: Question label (lowercase), %2$s: Quiz label (lowercase) */
					__( 'Whether to show the number for each %1$s answer when taking the %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'question' ),
					$quiz_singular_lowercase
				),
				'example'     => true,
			],
			'answer_random'                   => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %1$s: Question label (lowercase), %2$s: Quiz label (lowercase) */
					__( 'Whether to randomize the answers for each %1$s when taking the %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'question' ),
					$quiz_singular_lowercase
				),
				'example'     => true,
			],
			'title_hidden'                    => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Quiz label (lowercase) */
					__( 'Whether the title is hidden for the %s.', 'learndash' ),
					$quiz_singular_lowercase
				),
				'example'     => false,
			],
			'restart_button_hide'             => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Quiz label (lowercase) */
					__( 'Whether the restart button is hidden for the %s.', 'learndash' ),
					$quiz_singular_lowercase
				),
				'example'     => false,
			],

			// Results display.
			'show_average_result'             => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Quiz label (lowercase) */
					__( 'Whether to show average result for the %s.', 'learndash' ),
					$quiz_singular_lowercase
				),
				'example'     => true,
			],
			'show_category_score'             => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Quiz label (lowercase) */
					__( 'Whether to show category score for the %s.', 'learndash' ),
					$quiz_singular_lowercase
				),
				'example'     => true,
			],
			'hide_result_points'              => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Quiz label */
					__( 'Whether to show the overall %s score on the results page.', 'learndash' ),
					learndash_get_custom_label( 'quiz' )
				),
				'example'     => false,
			],
			'hide_result_correct_question'    => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Questions label */
					__( 'Whether to show the number of correctly answered %s on the results page.', 'learndash' ),
					learndash_get_custom_label( 'questions' )
				),
				'example'     => false,
			],
			'hide_result_quiz_time'           => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Quiz label */
					__( 'Whether to show the time spent on the %s on the results page.', 'learndash' ),
					learndash_get_custom_label( 'quiz' )
				),
				'example'     => false,
			],
			'custom_answer_feedback'          => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Quiz label (lowercase) */
					__( 'Whether custom answer feedback is enabled for the %s.', 'learndash' ),
					$quiz_singular_lowercase
				),
				'example'     => false,
			],
			'hide_answer_message_box'         => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Question label */
					__( 'Whether to show the correct / incorrect messages for each %s on the results page. Requires the "Custom Answer Feedback" setting to be enabled.', 'learndash' ),
					learndash_get_custom_label( 'question' )
				),
				'example'     => false,
			],
			'disabled_answer_mark'            => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Question label */
					__( 'Whether to show the correct / incorrect answer marks for each %s on the results page. Requires the "Custom Answer Feedback" setting to be enabled.', 'learndash' ),
					learndash_get_custom_label( 'question' )
				),
				'example'     => false,
			],
			'view_question_button_hidden'     => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Questions label */
					__( 'Whether to show the "View %s" button on the results page. Requires the "Custom Answer Feedback" setting to be enabled.', 'learndash' ),
					learndash_get_custom_label( 'questions' )
				),
				'example'     => false,
			],

			// Leaderboard settings (from admin data handling settings).
			'toplist_enabled'                 => [
				'type'        => 'boolean',
				'description' => __( 'Whether the leaderboard is enabled.', 'learndash' ),
				'example'     => true,
			],
			'toplist_data_add_permissions'    => [
				'type'        => 'string',
				'description' => __( 'Who can apply to the leaderboard. 1 is all users, 2 is registered users only, 3 is anonymous users only.', 'learndash' ),
				'enum'        => [ '1', '2', '3' ],
				'example'     => '2',
			],
			'toplist_data_add_multiple'       => [
				'type'        => 'boolean',
				'description' => __( 'Whether users can apply more than once to the leaderboard.', 'learndash' ),
				'example'     => true,
			],
			'toplist_data_add_automatic'      => [
				'type'        => 'boolean',
				'description' => __( 'Whether users are added automatically to the leaderboard.', 'learndash' ),
				'example'     => true,
			],
			'toplist_data_show_limit'         => [
				'type'        => 'integer',
				'description' => __( 'The number of entries to display on the leaderboard.', 'learndash' ),
				'example'     => 25,
			],
			'toplist_data_sort'               => [
				'type'        => 'string',
				'description' => __( 'The sort order of the leaderboard. 1 is best user, 2 is newest entry, 3 is oldest entry.', 'learndash' ),
				'enum'        => [ '1', '2', '3' ],
				'example'     => '1',
			],
			'toplist_data_showin_enabled'     => [
				'type'        => 'boolean',
				'description' => __( 'Whether the leaderboard is displayed on the results page.', 'learndash' ),
				'example'     => true,
			],
			'toplist_data_add_delay'          => [
				'type'        => 'integer',
				'description' => __( 'The delay in minutes before a user can add more data to the leaderboard.', 'learndash' ),
				'example'     => 60,
			],
			'toplist_data_shown'              => [
				'type'        => 'string',
				'description' => __( 'The location of the leaderboard on the results page. 1 is below the result text, 2 is in a button.', 'learndash' ),
				'enum'        => [ '1', '2' ],
				'example'     => '1',
			],

			// Statistics settings.
			'statistics_enabled'              => [
				'type'        => 'boolean',
				'description' => __( 'Whether statistics are enabled.', 'learndash' ),
				'example'     => true,
			],
			'view_profile_statistics_enabled' => [
				'type'        => 'boolean',
				'description' => __( 'Whether the front-end profile display is enabled for statistics.', 'learndash' ),
				'example'     => true,
			],
			'statistics_ip_lock_enabled'      => [
				'type'        => 'boolean',
				'description' => __( 'Whether statistics are protected from spam by checking the IP address.', 'learndash' ),
				'example'     => true,
			],
			'statistics_ip_lock'              => [
				'type'        => 'integer',
				'description' => __( 'How often statistics will be saved in minutes from the same IP. The "statistics_ip_lock_enabled" setting must be enabled for this to take effect.', 'learndash' ),
				'example'     => 5,
			],

			// Email settings.
			'email_enabled'                   => [
				'type'        => 'boolean',
				'description' => 'Whether email notifications are enabled.',
				'example'     => true,
			],
			'email_admin_enabled'             => [
				'type'        => 'boolean',
				'description' => 'Whether admin email notifications are enabled.',
				'example'     => true,
			],
			'email_user_enabled'              => [
				'type'        => 'boolean',
				'description' => 'Whether user email notifications are enabled.',
				'example'     => true,
			],
			'email_notification'              => [
				'type'        => 'string',
				'description' => sprintf(
					// translators: placeholder: %1$s quiz label, %2$d all users, %3$d registered users only.
					__( 'Which users should cause the admin to receive an email notification on %1$s completion. "%2$d" is all users, "%3$d" is registered users only.', 'learndash' ),
					learndash_get_custom_label_lower( 'quiz' ),
					1,
					2
				),
				'enum'        => [ '1', '2' ],
				'example'     => '1',
			],

			// Custom fields settings.
			'custom_fields'                   => [
				'type'        => 'array',
				'description' => __( 'Custom fields.', 'learndash' ),
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'name'     => [
							'type'        => 'string',
							'description' => esc_html__( 'Field name.', 'learndash' ),
						],
						'type'     => [
							'type'        => 'string',
							'description' => sprintf(
								/* translators: placeholder: %1$s: text field value, %2$s: textarea field value, %3$s: number field value, %4$s: email field value, %5$s: date field value, %6$s: checkbox field value, %7$s: radio field value, %8$s: select field value, %9$s: yes/no field value */
								__( 'Field type. "%1$s" is text, "%2$s" is textarea, "%3$s" is number, "%4$s" is email, "%5$s" is date, "%6$s" is checkbox, "%7$s" is radio, "%8$s" is select, "%9$s" is yes/no.', 'learndash' ),
								(string) WpProQuiz_Model_Form::FORM_TYPE_TEXT,
								(string) WpProQuiz_Model_Form::FORM_TYPE_TEXTAREA,
								(string) WpProQuiz_Model_Form::FORM_TYPE_NUMBER,
								(string) WpProQuiz_Model_Form::FORM_TYPE_EMAIL,
								(string) WpProQuiz_Model_Form::FORM_TYPE_DATE,
								(string) WpProQuiz_Model_Form::FORM_TYPE_CHECKBOX,
								(string) WpProQuiz_Model_Form::FORM_TYPE_RADIO,
								(string) WpProQuiz_Model_Form::FORM_TYPE_SELECT,
								(string) WpProQuiz_Model_Form::FORM_TYPE_YES_NO,
							),
							'enum'        => [
								(string) WpProQuiz_Model_Form::FORM_TYPE_TEXT,
								(string) WpProQuiz_Model_Form::FORM_TYPE_TEXTAREA,
								(string) WpProQuiz_Model_Form::FORM_TYPE_NUMBER,
								(string) WpProQuiz_Model_Form::FORM_TYPE_EMAIL,
								(string) WpProQuiz_Model_Form::FORM_TYPE_DATE,
								(string) WpProQuiz_Model_Form::FORM_TYPE_CHECKBOX,
								(string) WpProQuiz_Model_Form::FORM_TYPE_RADIO,
								(string) WpProQuiz_Model_Form::FORM_TYPE_SELECT,
								(string) WpProQuiz_Model_Form::FORM_TYPE_YES_NO,
							],
						],
						'sort'     => [
							'type'        => 'integer',
							'description' => esc_html__( 'Field sort order.', 'learndash' ),
						],
						'data'     => [
							'type'        => 'array',
							'description' => esc_html__( 'Field data. Used as options for select and radio fields.', 'learndash' ),
							'items'       => [
								'type' => 'string',
							],
							'nullable'    => true,
						],
						'id'       => [
							'type'        => 'integer',
							'description' => esc_html__( 'Field ID.', 'learndash' ),
						],
						'required' => [
							'type'        => 'boolean',
							'description' => esc_html__( 'Whether the field is required.', 'learndash' ),
							'default'     => false,
						],
					],
					'example'    => [
						(object) [
							'name'     => __( 'Example Radio Field', 'learndash' ),
							'type'     => (string) WpProQuiz_Model_Form::FORM_TYPE_RADIO,
							'data'     => [
								__( 'Option 1', 'learndash' ),
								__( 'Option 2', 'learndash' ),
							],
							'sort'     => 0,
							'id'       => 1,
							'required' => false,
						],
						(object) [
							'name'     => __( 'Example Required Text Field', 'learndash' ),
							'type'     => (string) WpProQuiz_Model_Form::FORM_TYPE_TEXT,
							'data'     => null,
							'sort'     => 1,
							'id'       => 2,
							'required' => true,
						],
					],
				],
			],

			// Additional fields.
			'password'                        => [
				'type'        => 'string',
				'description' => __( 'Password if password protected.', 'learndash' ),
				'example'     => '',
			],

			// Quiz links (extending WP_Post _links).
			'_links'                          => [
				'type'        => 'object',
				'description' => sprintf(
					/* translators: %s: Quiz label (lowercase) */
					__( 'HAL links for the %s (extends WP_Post links).', 'learndash' ),
					$quiz_singular_lowercase
				),
				'properties'  => [
					'about'               => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'href' => [
									'type'        => 'string',
									'description' => __( 'The link URL.', 'learndash' ),
								],
							],
						],
					],
					'version-history'     => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'count' => [
									'type'        => 'integer',
									'description' => __( 'Number of revisions.', 'learndash' ),
								],
								'href'  => [
									'type'        => 'string',
									'description' => __( 'The link URL.', 'learndash' ),
								],
							],
						],
					],
					'predecessor-version' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id'   => [
									'type'        => 'integer',
									'description' => __( 'The revision ID.', 'learndash' ),
								],
								'href' => [
									'type'        => 'string',
									'description' => __( 'The link URL.', 'learndash' ),
								],
							],
						],
					],
					'statistics'          => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'href'       => [
									'type'        => 'string',
									'description' => __( 'The link URL.', 'learndash' ),
								],
								'embeddable' => [
									'type'        => 'boolean',
									'description' => __( 'Whether the link is embeddable.', 'learndash' ),
								],
							],
						],
					],
					'users'               => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'href'       => [
									'type'        => 'string',
									'description' => __( 'The link URL.', 'learndash' ),
								],
								'embeddable' => [
									'type'        => 'boolean',
									'description' => __( 'Whether the link is embeddable.', 'learndash' ),
								],
							],
						],
					],
					'wp:attachment'       => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'href' => [
									'type'        => 'string',
									'description' => __( 'The link URL.', 'learndash' ),
								],
							],
						],
					],
					'curies'              => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'name'      => [
									'type'        => 'string',
									'description' => __( 'The curie name.', 'learndash' ),
								],
								'href'      => [
									'type'        => 'string',
									'description' => __( 'The curie href template.', 'learndash' ),
								],
								'templated' => [
									'type'        => 'boolean',
									'description' => __( 'Whether the href is templated.', 'learndash' ),
								],
							],
						],
					],
				],
			],
		];

		$links = $quiz_properties['_links']['properties'];
		unset( $quiz_properties['_links'] );

		// Merge the base schema properties with quiz-specific properties.
		$base_schema['properties'] = array_merge(
			$base_schema['properties'],
			$quiz_properties
		);

		$base_links = is_array( $base_schema['properties']['_links']['properties'] ) ? $base_schema['properties']['_links']['properties'] : [];

		// Merge the _links properties to extend WP_Post links instead of overwriting them.
		$base_schema['properties']['_links']['properties'] = array_merge(
			$base_links,
			$links
		);

		// Add quiz-specific required fields.
		$base_schema['required'] = array_unique(
			array_merge(
				$base_schema['required'],
				[
					'visible_type',
					'visible_after',
					'visible_after_specific_date',
					'is_external',
					'external_type',
					'external_require_attendance',
					'prerequisites',
					'registered_users_only',
					'passing_percentage',
					'certificate_award_threshold',
					'certificate',
					'quiz_resume',
					'quiz_resume_cookie_send_timer',
					'retry_restrictions_enabled',
					'retry_repeats',
					'answer_all_questions_enabled',
					'time_limit_enabled',
					'time_limit_time',
					'materials_enabled',
					'materials',
					'auto_start',
					'quiz_modus',
					'review_table_enabled',
					'summary_hide',
					'skip_question_disabled',
					'custom_sorting',
					'sort_categories',
					'question_random',
					'show_max_question',
					'show_points',
					'show_category',
					'hide_question_position_overview',
					'hide_question_numbering',
					'numbered_answer',
					'answer_random',
					'title_hidden',
					'restart_button_hide',
					'show_average_result',
					'show_category_score',
					'hide_result_points',
					'hide_result_correct_question',
					'hide_result_quiz_time',
					'custom_answer_feedback',
					'hide_answer_message_box',
					'disabled_answer_mark',
					'view_question_button_hidden',
					'toplist_enabled',
					'toplist_data_add_permissions',
					'toplist_data_add_multiple',
					'toplist_data_add_automatic',
					'toplist_data_show_limit',
					'toplist_data_sort',
					'toplist_data_showin_enabled',
					'toplist_data_add_delay',
					'toplist_data_shown',
					'statistics_enabled',
					'view_profile_statistics_enabled',
					'statistics_ip_lock_enabled',
					'statistics_ip_lock',
					'email_enabled',
					'email_admin_enabled',
					'email_user_enabled',
					'email_notification',
					'custom_fields',
					'password',
				]
			)
		);

		return $base_schema;
	}
}
