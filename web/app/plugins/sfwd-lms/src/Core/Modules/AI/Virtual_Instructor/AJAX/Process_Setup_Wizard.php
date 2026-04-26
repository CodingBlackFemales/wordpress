<?php
/**
 * LearnDash AI Virtual Instructor Process Setup Wizard AJAX handler.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor\AJAX;

use LDLMS_Post_Types;
use LearnDash\Core\Modules\AI\Virtual_Instructor\DTO;
use LearnDash\Core\Modules\AI\Virtual_Instructor\Settings\Page_Section;
use LearnDash\Core\Modules\AJAX\Request_Handler;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Utilities\Sanitize;
use LearnDash_Custom_Label;
use LearnDash_Settings_Section_AI_Integrations;

/**
 * LearnDash AI Virtual Instructor Process Setup Wizard AJAX handler.
 *
 * @since 4.13.0
 */
class Process_Setup_Wizard extends Request_Handler {
	/**
	 * AJAX action.
	 *
	 * @since 4.13.0
	 *
	 * @var string
	 */
	public static $action = 'learndash_virtual_instructor_process_setup_wizard';

	/**
	 * Request.
	 *
	 * @since 4.13.0
	 *
	 * @var DTO\Process_Setup_Wizard_Request
	 */
	public $request;

	/**
	 * Results.
	 *
	 * @since 4.13.0
	 *
	 * @var array<string, mixed>
	 */
	protected $results;

	/**
	 * Response.
	 *
	 * @since 4.13.0
	 *
	 * @var DTO\Process_Setup_Wizard_Response
	 */
	protected $response;

	/**
	 * Sets up the request.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	protected function set_up_request(): void {
		$this->request = DTO\Process_Setup_Wizard_Request::create(
			[
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is done in parent's verify_nonce method.
				'openai_api_key'       => sanitize_text_field( wp_unslash( $_POST['openAiApiKey'] ?? '' ) ),
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is done in parent's verify_nonce method.
				'banned_words'         => sanitize_text_field( wp_unslash( $_POST['bannedWords'] ?? '' ) ),
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is done in parent's verify_nonce method.
				'error_message'        => sanitize_text_field( wp_unslash( $_POST['errorMessage'] ?? '' ) ),
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is done in parent's verify_nonce method.
				'name'                 => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is done in parent's verify_nonce method.
				'custom_instruction'   => sanitize_text_field( wp_unslash( $_POST['customInstruction'] ?? '' ) ),
				// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification is done in parent's verify_nonce method and sanitization is done in Cast::to_bool.
				'apply_to_all_courses' => Cast::to_bool( wp_unslash( $_POST['applyToAllCourses'] ?? '' ) === 'true' ),
				'course_ids'           => Sanitize::array(
					// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification is done in parent's verify_nonce method and sanitization is done in Sanitize::array.
					wp_unslash( $_POST['courseIds'] ?? [] ),
					[ Cast::class, 'to_int' ]
				),
				// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification is done in parent's verify_nonce method and sanitization is done in Cast::to_bool.
				'apply_to_all_groups'  => Cast::to_bool( wp_unslash( $_POST['applyToAllGroups'] ?? '' ) === 'true' ),
				'group_ids'            => Sanitize::array(
					// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification is done in parent's verify_nonce method and sanitization is done in Sanitize::array.
					wp_unslash( $_POST['groupIds'] ?? [] ),
					[ Cast::class, 'to_int' ]
				),
			]
		);
	}

	/**
	 * Processes the request.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	protected function process(): void {
		$this->save_global_settings();
		$this->save_individual_settings();

		$this->results = [
			'status'  => 'success',
			'message' => sprintf(
				// translators: %s: virtual instructor.
				esc_html__( 'Your %s has been created and configured.', 'learndash' ),
				LearnDash_Custom_Label::label_to_lower( LDLMS_Post_Types::VIRTUAL_INSTRUCTOR )
			),
		];
	}

	/**
	 * Prepares the response.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	protected function prepare_response(): void {
		$this->response = DTO\Process_Setup_Wizard_Response::create( $this->results );
	}

	/**
	 * Saves global virtual instructor settings.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	private function save_global_settings(): void {
		// Saves OpenAI API key if it's not empty. If it's empty, it means it's already available in the settings because we skip the step to set up API key if it's already set up.

		if ( ! empty( $this->request->openai_api_key ) ) {
			LearnDash_Settings_Section_AI_Integrations::set_setting( 'openai_api_key', $this->request->openai_api_key );
		}

		// Saves global configuration.

		Page_Section::set_setting( 'banned_words', $this->request->banned_words );
		Page_Section::set_setting( 'error_message', $this->request->error_message );
	}

	/**
	 * Saves individual virtual instructor settings.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	private function save_individual_settings(): void {
		$post_id = wp_insert_post(
			[
				'post_title'  => $this->request->name,
				'post_type'   => learndash_get_post_type_slug( LDLMS_Post_Types::VIRTUAL_INSTRUCTOR ),
				'post_status' => 'publish',
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error(
				[
					'status'  => 'error',
					'message' => sprintf(
						// translators: %s: error message.
						__( 'Error creating virtual instructor. Message: %s.', 'learndash' ),
						$post_id->get_error_message()
					),
				],
				400
			);
		}

		learndash_update_setting( $post_id, 'custom_instruction', $this->request->custom_instruction );
		learndash_update_setting( $post_id, 'apply_to_all_courses', $this->request->apply_to_all_courses ? 'on' : '' );
		learndash_update_setting( $post_id, 'course_ids', $this->request->course_ids );
		learndash_update_setting( $post_id, 'apply_to_all_groups', $this->request->apply_to_all_groups ? 'on' : '' );
		learndash_update_setting( $post_id, 'group_ids', $this->request->group_ids );

		// Uploads avatar.

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is done in parent's verify_nonce method.
		if ( ! empty( $_FILES['avatar'] ) ) {
			$avatar_id = media_handle_upload( 'avatar', $post_id );
			learndash_update_setting( $post_id, 'avatar_id', $avatar_id );
		}
	}
}
