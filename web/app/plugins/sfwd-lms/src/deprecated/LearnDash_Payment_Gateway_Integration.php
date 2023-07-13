<?php
/**
 * Deprecated. Use LearnDash_Payment_Gateway instead.
 * Base class for legacy payment gateways.
 *
 * @since 4.2.0
 * @deprecated 4.5.0
 *
 * @package LearnDash\Deprecated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

_deprecated_file(
	__FILE__,
	'4.5.0',
	esc_html( LEARNDASH_LMS_PLUGIN_DIR . '/includes/payments/gateways/class-learndash-payment-gateway.php' )
);

if ( ! class_exists( 'LearnDash_Payment_Gateway_Integration' ) ) {
	/**
	 * Payment gateway class.
	 *
	 * @since 4.2.0
	 * @deprecated 4.5.0
	 */
	class LearnDash_Payment_Gateway_Integration {
		/**
		 * Associates a course/group with a user.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @param int|null $post_id Post ID.
		 * @param int      $user_id User ID.
		 *
		 * @return void
		 */
		protected function add_post_access( ?int $post_id, int $user_id ): void {
			_deprecated_function( __METHOD__, '4.5.0' );

			$this->update_post_access( $post_id, $user_id );
		}

		/**
		 * Removes course/group access from a user.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @param int|null $post_id Post ID.
		 * @param int      $user_id User ID.
		 *
		 * @return void
		 */
		protected function remove_post_access( ?int $post_id, int $user_id ): void {
			_deprecated_function( __METHOD__, '4.5.0' );

			$this->update_post_access( $post_id, $user_id, true );
		}

		/**
		 * Updates course/group access for a user.
		 *
		 * @since 4.2.0
		 * @deprecated 4.5.0
		 *
		 * @param int|null $post_id Post ID.
		 * @param int      $user_id User ID.
		 * @param bool     $remove  True to remove, false to add.
		 *
		 * @return void
		 */
		private function update_post_access( ?int $post_id, int $user_id, bool $remove = false ): void {
			_deprecated_function( __METHOD__, '4.5.0' );

			if ( learndash_is_course_post( $post_id ) ) {
				ld_update_course_access( $user_id, $post_id, $remove );
			} elseif ( learndash_is_group_post( $post_id ) ) {
				ld_update_group_access( $user_id, $post_id, $remove );
			}
		}
	}
}
