<?php
/**
 * Quiz creation AI view.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Quiz_Creation;

use LDLMS_Post_Types;
use LearnDash\Core\App;
use LearnDash\Core\Modules\AI\Quiz_Creation;
use LearnDash\Core\Modules\AJAX;
use LearnDash\Core\Services\ChatGPT;
use SFWD_LMS;

/**
 * Quiz creation AI view class.
 *
 * @since 4.8.0
 */
class View {
	/**
	 * ChatGPT client.
	 *
	 * @since 4.8.0
	 *
	 * @var ChatGPT;
	 */
	private $chatgpt;

	/**
	 * Constructor.
	 *
	 * @since 4.8.0
	 *
	 * @param ChatGPT $chatgpt ChatGPT client.
	 */
	public function __construct( ChatGPT $chatgpt ) {
		$this->chatgpt = $chatgpt;
	}

	/**
	 * Get form fields.
	 *
	 * @since 4.8.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_form_fields(): array {
		/**
		 * Filters quiz creation AI form fields.
		 *
		 * @since 4.8.0
		 *
		 * @param array<string, array<string, mixed>> $fields Array of fields details to be passed to LearnDash_Settings_Fields::create_section_field() method.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		return apply_filters(
			'learndash_module_ai_quiz_creation_form_fields',
			[
				'course_id'                => [
					'name'      => 'course_id',
					'id'        => 'course_id',
					'type'      => 'select',
					'label'     => wp_sprintf(
						// translators: Course label.
						esc_html__( 'Associated %s', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'help_text' => wp_sprintf(
						// translators: Course label.
						esc_html__( 'Please select an existing %s.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'value'     => '',
					'class'     => 'select2 ld-w-full ld-block !ld-max-w-full',
					'required'  => 'required',
				],
				'lesson_id'                => [
					'name'      => 'lesson_id',
					'id'        => 'lesson_id',
					'type'      => 'select',
					'label'     => wp_sprintf(
						// translators: Lesson label.
						esc_html__( 'Associated %s', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
					'help_text' => wp_sprintf(
						// translators: 1$: lesson label, 2$: quiz label, 3$: course label.
						esc_html__( 'Please select an existing %1$s. You can leave this blank if you want to associate the %2$s with the %3$s above.', 'learndash' ),
						learndash_get_custom_label_lower( 'lesson' ),
						learndash_get_custom_label_lower( 'quiz' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'value'     => '',
					'class'     => 'select2 ld-w-full ld-block !ld-max-w-full',
				],
				'topic_id'                 => [
					'name'      => 'topic_id',
					'id'        => 'topic_id',
					'type'      => 'select',
					'label'     => wp_sprintf(
						// translators: Topic label.
						esc_html__( 'Associated %s', 'learndash' ),
						learndash_get_custom_label( 'topic' )
					),
					'help_text' => wp_sprintf(
						// translators: 1$: topic label, 2$: quiz label, 3$: lesson label.
						esc_html__( 'Please select an existing %1$s. You can leave this blank if you want to associate the %2$s with the %3$s above.', 'learndash' ),
						learndash_get_custom_label_lower( 'topic' ),
						learndash_get_custom_label_lower( 'quiz' ),
						learndash_get_custom_label_lower( 'lesson' )
					),
					'value'     => '',
					'class'     => 'select2 ld-w-full ld-block !ld-max-w-full',
				],
				'quiz'                     => [
					'name'      => 'quiz',
					'id'        => 'quiz',
					'type'      => 'select',
					'label'     => wp_sprintf(
						// translators: Quiz label.
						esc_html__( '%s Title', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'help_text' => wp_sprintf(
						// translators: Quiz label.
						esc_html__( 'You can select an existing %s or create a new one.', 'learndash' ),
						learndash_get_custom_label_lower( 'quiz' )
					),
					'value'     => '',
					'class'     => 'select2 ld-w-full ld-block !ld-max-w-full',
					'required'  => 'required',
				],
				'question_types'           => [
					'name'      => 'question_types[]',
					'id'        => 'question_types',
					'type'      => 'select',
					'label'     => wp_sprintf(
						// translators: Question label.
						esc_html__( '%s Types', 'learndash' ),
						esc_html( learndash_get_custom_label( 'question' ) )
					),
					'help_text' => wp_sprintf(
						// translators: 1$: Question label, 2$: Questions label, 3$: Question label.
						esc_html__( '%1$s types the generated %2$s will have. You can select multiple %3$s types.', 'learndash' ),
						esc_html( learndash_get_custom_label( 'question' ) ),
						esc_html( learndash_get_custom_label_lower( 'questions' ) ),
						esc_html( learndash_get_custom_label_lower( 'question' ) )
					),
					'value'     => '',
					'options'   => Quiz_Creation::get_question_types(),
					'class'     => 'ld-w-full ld-block !ld-max-w-full',
					'required'  => 'required',
					'attrs'     => [
						'multiple' => 'multiple',
					],
				],
				'total_questions_per_type' => [
					'name'      => 'total_questions_per_type',
					'id'        => 'total_questions_per_type',
					'type'      => 'number',
					'label'     => wp_sprintf(
						// translators: Questions label.
						esc_html__( 'Number of %s', 'learndash' ),
						learndash_get_custom_label( 'questions' )
					),
					'help_text' => wp_sprintf(
						// translators: Questions label.
						esc_html__( 'Number of %s and answers you want to generate for each question type.', 'learndash' ),
						esc_html( learndash_get_custom_label_lower( 'questions' ) )
					),
					'value'     => '',
					'class'     => 'ld-w-full ld-block !ld-max-w-full',
					'required'  => 'required',
				],
				'quiz_idea'                => [
					'name'      => 'quiz_idea',
					'id'        => 'quiz_idea',
					'type'      => 'textarea',
					'label'     => wp_sprintf(
						// translators: Quiz label.
						esc_html__( 'Describe Your %s', 'learndash' ),
						esc_html( learndash_get_custom_label( 'quiz' ) )
					),
					'help_text' => wp_sprintf(
						// translators: Quiz label.
						esc_html__( '%s idea in clear and brief description.', 'learndash' ),
						esc_html( learndash_get_custom_label( 'quiz' ) )
					),
					'value'     => '',
					'class'     => 'ld-w-full ld-block !ld-max-w-full',
					'required'  => 'required',
				],
			]
		);
	}

	/**
	 * Register Quiz Creation AI page.
	 *
	 * @since 4.8.0
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_submenu_page(
			'learndash-lms',
			wp_sprintf(
				// translators: Quiz label.
				__( 'Create %s from AI', 'learndash' ),
				learndash_get_custom_label( 'quiz' )
			),
			'',
			LEARNDASH_ADMIN_CAPABILITY_CHECK,
			Quiz_Creation::$slug,
			App::callback( $this, 'render' )
		);
	}

	/**
	 * Remove submenu item created when adding submenu page.
	 *
	 * @since 4.8.0
	 *
	 * @return void
	 */
	public function remove_submenu_item(): void {
		remove_submenu_page( 'learndash-lms', Quiz_Creation::$slug );
	}

	/**
	 * Add AI quiz creation button to header buttons.
	 *
	 * @since 4.23.1
	 *
	 * @param array<string,mixed> $buttons Array of header buttons.
	 *
	 * @return array<int|string,mixed> Modified array of header buttons.
	 */
	public function add_header_buttons( $buttons ) {
		$screen = get_current_screen();

		if (
			is_object( $screen )
			&& $screen->id === 'edit-' . learndash_get_post_type_slug( LDLMS_Post_Types::QUIZ )
		) {
			$buttons[] = [
				'text' => wp_sprintf(
					// translators: Quiz label.
					__( 'Create %s from AI', 'learndash' ),
					learndash_get_custom_label( 'quiz' )
				),
				'href' => add_query_arg(
					[
						'page' => Quiz_Creation::$slug,
					],
					admin_url( 'admin.php' )
				),
			];
		}

		return $buttons;
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since 4.8.0
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts(): void {
		$screen = get_current_screen();

		if (
			is_object( $screen )
			&& $screen->id === 'learndash-lms_page_' . Quiz_Creation::$slug
		) {
			wp_enqueue_style( 'ld-tailwindcss' );

			wp_enqueue_script(
				Quiz_Creation::$slug,
				LEARNDASH_LMS_PLUGIN_URL . 'src/assets/dist/js/admin/modules/ai/quiz-creation/index.js',
				[ 'jquery' ],
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);

			wp_localize_script(
				Quiz_Creation::$slug,
				'LearnDashQuizCreationAi',
				[
					'ajaxurl'    => admin_url( 'admin-ajax.php' ),
					'action'     => AJAX\Search_Posts::$action,
					'nonce'      => wp_create_nonce( AJAX\Search_Posts::$action ),
					'post_types' => [
						'course' => learndash_get_post_type_slug( LDLMS_Post_Types::COURSE ),
						'lesson' => learndash_get_post_type_slug( LDLMS_Post_Types::LESSON ),
						'topic'  => learndash_get_post_type_slug( LDLMS_Post_Types::TOPIC ),
						'quiz'   => learndash_get_post_type_slug( LDLMS_Post_Types::QUIZ ),
					],
				]
			);
		}
	}

	/**
	 * Filter AJAX quiz search response.
	 *
	 * @since 4.8.0
	 *
	 * @param array<string, mixed> $response Response array.
	 * @param AJAX\Request_Handler $ajax     AJAX request object.
	 *
	 * @return array<string, mixed>
	 */
	public function filter_quiz_search( array $response, AJAX\Request_Handler $ajax ): array {
		if (
			! ( $ajax instanceof AJAX\Search_Posts )
			|| $ajax->request->post_type !== learndash_get_post_type_slug( LDLMS_Post_Types::QUIZ )
		) {
			return $response;
		}

		$new_item = ! empty( $ajax->request->keyword )
			? [
				[
					'id'   => wp_json_encode(
						[
							// Random key with unique value to make sure displayed result are not cached one and to indicate this is a new item.
							'new'   => md5( microtime() ),
							'title' => $ajax->request->keyword,
						]
					),
					'text' => wp_sprintf(
						// translators: 1$: keyword, 2$: quiz label.
						esc_html__( '%1$s (New %2$s)', 'learndash' ),
						$ajax->request->keyword,
						learndash_get_custom_label( 'quiz' )
					),
				],
			]
			: [];

		if (
			isset( $response['results'] )
			&& is_array( $response['results'] )
		) {
			// Modify results 'id' key to unify the format of quiz field between new existing items and new item.

			$response['results'] = array_map(
				function( $result ) {
					return [
						'id'   => wp_json_encode(
							[
								'id'    => $result['id'],
								'title' => $result['text'],
							]
						),
						'text' => $result['text'],
					];
				},
				$response['results']
			);

			$response['results'] = array_merge( $new_item, $response['results'] );
		}

		return $response;
	}

	/**
	 * Render page.
	 *
	 * @since 4.8.0
	 *
	 * TODO: Replace with the new admin view render after Breezy template update.
	 *
	 * @return void
	 */
	public function render(): void {
		SFWD_LMS::get_view(
			'AI/quiz-creation',
			[
				'api_key'         => $this->chatgpt->get_api_key(),
				'question_types'  => Quiz_Creation::get_question_types(),
				'form_fields'     => $this->get_form_fields(),
				'ai_settings_url' => add_query_arg(
					[
						'section-advanced' => 'settings_ai_integrations',
					],
					menu_page_url( 'learndash_lms_advanced', false )
				),
			],
			true
		);
	}
}
