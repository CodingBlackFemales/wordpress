<?php
/**
 * AJAX request abstract class file.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AJAX;

use Learndash_DTO;

/**
 * AJAX request handler abstract class.
 *
 * @since 4.8.0
 */
abstract class Request_Handler {
	/**
	 * AJAX action.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public static $action;

	/**
	 * Request.
	 *
	 * @since 4.8.0
	 *
	 * @var Learndash_DTO
	 */
	public $request;

	/**
	 * Request results.
	 *
	 * @since 4.8.0
	 *
	 * @var mixed
	 */
	protected $results;

	/**
	 * Response.
	 *
	 * @since 4.8.0
	 *
	 * @var Learndash_DTO
	 */
	protected $response;

	/**
	 * Handle AJAX request.
	 *
	 * @since 4.8.0
	 *
	 * @return void
	 */
	public function handle_request(): void {
		$this->check_user_capability();

		$this->verify_nonce();

		$this->set_up_request();

		$this->process();

		$this->prepare_response();

		$this->send_response();
	}

	/**
	 * Check user capability.
	 *
	 * @since 4.8.0
	 *
	 * @return void
	 */
	protected function check_user_capability(): void {
		if ( ! current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 401 );
		}
	}

	/**
	 * Verify request nonce.
	 *
	 * @since 4.8.0
	 *
	 * @return void
	 */
	protected function verify_nonce(): void {
		$nonce = isset( $_REQUEST['nonce'] )
			? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) )
			: null;

		$action = isset( $_REQUEST['action'] )
			? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) )
			: null;

		if (
			! isset( $nonce )
			|| ! isset( $action )
			|| ! wp_verify_nonce( $nonce, $action )
		) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 401 );
		}
	}

	/**
	 * Set up and build `request` property.
	 *
	 * @since 4.8.0
	 *
	 * @return void
	 */
	abstract protected function set_up_request(): void;

	/**
	 * Process request and build `results` property.
	 *
	 * @since 4.8.0
	 *
	 * @return void
	 */
	abstract protected function process(): void;

	/**
	 * Prepare response and build `response` property.
	 *
	 * @since 4.8.0
	 *
	 * @return void
	 */
	abstract protected function prepare_response(): void;

	/**
	 * Send response.
	 *
	 * @since 4.8.0
	 *
	 * @return void
	 */
	protected function send_response(): void {
		/**
		 * Filters AJAX handler response.
		 *
		 * @since 4.8.0
		 *
		 * @param array<string, mixed> $response Response to be sent.
		 * @param static               $handler  Request handler object.
		 *
		 * @return array<string, mixed>
		 */
		$response = apply_filters( 'learndash_ajax_send_response', $this->response->to_array(), $this );

		wp_send_json_success( $response );
	}
}
