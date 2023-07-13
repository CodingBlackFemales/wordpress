<?php
/**
 * LearnDash Step loader class, for steps AJAX loading.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

namespace LearnDash\Core\Template\Steps;

use InvalidArgumentException;
use LearnDash\Core\Template\Steps;
use LearnDash\Core\Factories\Model_Factory;
use LearnDash\Core\Factories\Step_Mapper_Factory;
use LearnDash\Core\Template\Views\Traits\Supports_Steps_Context;

// TODO: Write tests for it.

/**
 * The Step loader class.
 *
 * @since 4.6.0
 */
class Loader {
	use Supports_Steps_Context;

	/**
	 * Ajax action name.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	public static $sub_steps_ajax_action_name = 'learndash_steps_loader_get_sub_steps';

	/**
	 * Steps walker.
	 *
	 * @since 4.6.0
	 *
	 * @var Walker
	 */
	private $steps_walker;

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param Steps\Walker $walker Steps walker.
	 */
	public function __construct( Steps\Walker $walker ) {
		$this->steps_walker = $walker;
	}

	/**
	 * Adds the scripts data for the steps loader.
	 *
	 * @since 4.6.0
	 *
	 * @param array<string,mixed> $data The data.
	 *
	 * @return array<string,mixed>
	 */
	public function add_scripts_data( array $data ): array {
		$data['steps'] = [
			'sub_steps_ajax_action_name' => self::$sub_steps_ajax_action_name,
			'sub_steps_ajax_nonce'       => wp_create_nonce( self::$sub_steps_ajax_action_name ),
			'default_error_message'      => esc_html__( 'Something went wrong. Please refresh the page and try it again.', 'learndash' ),
		];

		return $data;
	}

	/**
	 * Handles the sub steps ajax request.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function handle_sub_steps_ajax_request(): void {
		if (
			! isset( $_POST['nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
				self::$sub_steps_ajax_action_name
			)
			) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'Page expired. Please refresh the page and try it again.', 'learndash' ),
				]
			);
		}

		if (
			! isset( $_POST['step_id'] )
			|| ! isset( $_POST['step_parent_id'] )
			|| ! isset( $_POST['page'] )
		) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'Required parameters ("step_id", "step_parent_id", "page") are missing.', 'learndash' ),
				]
			);
		}

		$step_id        = absint( sanitize_text_field( wp_unslash( $_POST['step_id'] ) ) );
		$step_parent_id = absint( sanitize_text_field( wp_unslash( $_POST['step_parent_id'] ) ) );
		$page           = absint( sanitize_text_field( wp_unslash( $_POST['page'] ) ) );

		if (
			$step_id < 1
			|| $step_parent_id < 1
			|| $page < 1
		) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'Invalid parameters.', 'learndash' ),
				]
			);
		}

		$post = get_post( $step_parent_id );

		if ( ! $post ) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'Parent step not found.', 'learndash' ),
				]
			);
		}

		try {
			$model = Model_Factory::create( $post );
		} catch ( InvalidArgumentException $e ) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'Parent step not found.', 'learndash' ),
				]
			);
		}

		try {
			$steps_mapper = Step_Mapper_Factory::create( $model );
		} catch ( InvalidArgumentException $e ) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'Steps could not be mapped for the parent step.', 'learndash' ),
				]
			);
		}

		$steps_html = $this->steps_walker
			->set_depth_modificator( 1 ) // It is needed to set the depth modificator to 1, because the steps are already nested. So we need to start from the second level.
			->walk(
				$steps_mapper->get_sub_steps( $step_id, $page )->all(),
				$this->steps_walker_max_depth,
				self::build_steps_context( $model )
			);

		wp_send_json_success( compact( 'steps_html' ) );
	}
}
