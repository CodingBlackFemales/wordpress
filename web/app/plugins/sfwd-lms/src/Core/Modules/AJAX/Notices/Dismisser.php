<?php
/**
 * Notices AJAX module that handles dismiss forever request.
 *
 * @since 4.12.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AJAX\Notices;

use LearnDash\Core\Utilities\Cast;
use WP_Error;

/**
 * Notices AJAX module that handles dismiss forever request.
 *
 * @since 4.12.0
 */
class Dismisser {
	/**
	 * AJAX action.
	 *
	 * @since 4.12.0
	 *
	 * @var string
	 */
	public static $action = 'notice_dismiss_permanently';

	/**
	 * Classname for the notice that can be dismissed forever.
	 *
	 * @since 4.12.0
	 *
	 * @var string
	 */
	public static $classname = 'learndash-notice-permanently-dismissible';

	/**
	 * Notice ID prefix.
	 *
	 * @since 4.12.0
	 *
	 * @var string
	 */
	private const NOTICE_ID_PREFIX = 'learndash_notice_dismissed_';

	/**
	 * Handles dismiss forever request.
	 *
	 * @since 4.12.0
	 *
	 * @return void
	 */
	public function handle_dismiss_request(): void {
		$action_name = 'learndash_' . self::$action;

		if (
			! isset( $_POST['action'] )
			|| $action_name !== sanitize_key( wp_unslash( $_POST['action'] ) )
		) {
			return;
		}

		if (
			! isset( $_POST['id'] )
			|| ! isset( $_POST['nonce'] )
			|| ! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
				$action_name
			)
		) {
			wp_send_json_error(
				new WP_Error( $action_name, __( 'Invalid nonce.', 'learndash' ) )
			);
		}

		$notice_id = sanitize_key( wp_unslash( $_POST['id'] ) );

		if (
			empty( $notice_id )
		) {
			wp_send_json_error(
				new WP_Error( $action_name, __( 'Empty notice ID.', 'learndash' ) )
			);
		}

		self::dismiss( $notice_id );

		wp_send_json_success();
	}

	/**
	 * Dismisses a notice forever.
	 *
	 * @param string $id Notice ID.
	 *
	 * @return void
	 */
	public static function dismiss( string $id ): void {
		update_user_meta(
			get_current_user_id(),
			self::prefix_id( $id ),
			time()
		);
	}

	/**
	 * Checks if a notice is dismissed.
	 *
	 * @param string $id Notice ID.
	 *
	 * @return bool
	 */
	public static function is_dismissed( string $id ): bool {
		return metadata_exists(
			'user',
			get_current_user_id(),
			self::prefix_id( $id )
		);
	}

	/**
	 * Gets the time when a notice was dismissed.
	 *
	 * @since 4.15.2
	 *
	 * @param string $id Notice ID.
	 *
	 * @return int
	 */
	public static function get_dismissed_time( string $id ): int {
		return Cast::to_int(
			get_user_meta(
				get_current_user_id(),
				self::prefix_id( $id ),
				true
			)
		);
	}

	/**
	 * Creates notice ID with a prefix.
	 *
	 * @param string $id Notice ID.
	 *
	 * @return string
	 */
	protected static function prefix_id( string $id ): string {
		return self::NOTICE_ID_PREFIX . $id;
	}
}
