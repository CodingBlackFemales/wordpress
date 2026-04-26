<?php
/**
 * Frontend class file.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor;

use LDLMS_Post_Types;
use LearnDash\Core\Models\Product;
use LearnDash\Core\Models\Virtual_Instructor as Virtual_Instructor_Model;
use LearnDash\Core\Utilities\Cast;
use WP_Post;

/**
 * Frontend class.
 *
 * @since 4.13.0
 */
class Frontend {
	/**
	 * Outputs chatbox wrapper HTML.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function output_chatbox_wrapper(): void {
		global $post;

		if ( ! $this->chat_can_be_initialized() ) {
			return;
		}

		$course_id = Cast::to_int(
			learndash_get_course_id( $post->ID )
		);
		$model     = Virtual_Instructor_Model::get_by_course_id( $course_id );

		if ( ! $model instanceof Virtual_Instructor_Model ) {
			return;
		}

		printf(
			'<div class="learndash-virtual-instructor" data-id="%s" data-course_id="%s"></div>',
			esc_attr( Cast::to_string( $model->get_id() ) ),
			esc_attr( Cast::to_string( $course_id ) )
		);
	}

	/**
	 * Enqueues scripts and styles on frontend.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		if ( ! $this->chat_can_be_initialized() ) {
			return;
		}

		wp_enqueue_script(
			'learndash-chatbox',
			LEARNDASH_LMS_PLUGIN_URL . 'src/assets/dist/js/modules/ai/virtual-instructor/chatbox.js',
			[ 'jquery' ],
			LEARNDASH_SCRIPT_VERSION_TOKEN,
			true
		);

		wp_localize_script(
			'learndash-chatbox',
			'learndashVirtualInstructor',
			[
				'actions' => [
					'init'  => AJAX\Chat_Init::$action,
					'input' => AJAX\Chat_Input::$action,
					'send'  => AJAX\Chat_Send::$action,
				],
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => [
					'init'  => wp_create_nonce( AJAX\Chat_Init::$action ),
					'input' => wp_create_nonce( AJAX\Chat_Input::$action ),
					'send'  => wp_create_nonce( AJAX\Chat_Send::$action ),
				],
			]
		);
	}

	/**
	 * Checks if virtual instructor chatbox can be initialized.
	 *
	 * @since 4.13.0
	 *
	 * @return bool True if chatbox can be initialized, otherwise false.
	 */
	private function chat_can_be_initialized(): bool {
		global $post;

		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		if ( ! in_array(
			$post->post_type,
			learndash_get_post_type_slug(
				[
					LDLMS_Post_Types::COURSE,
					LDLMS_Post_Types::LESSON,
					LDLMS_Post_Types::TOPIC,
				]
			),
			true
		) ) {
			return false;
		}

		$course_id = Cast::to_int(
			learndash_get_course_id( $post->ID )
		);

		if ( $course_id <= 0 ) {
			return false;
		}

		$product = Product::find( $course_id );

		if ( ! $product instanceof Product ) {
			return false;
		}

		$user_id = get_current_user_id();

		if ( ! $product->user_has_access( $user_id ) ) {
			return false;
		}

		$model = Virtual_Instructor_Model::get_by_course_id( $course_id );

		if ( ! $model instanceof Virtual_Instructor_Model ) {
			return false;
		}

		return true;
	}
}
