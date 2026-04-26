<?php
/**
 * LearnDash Commerce Product Cancellation Reason Enum.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Enums\Commerce;

use StellarWP\Learndash\MyCLabs\Enum\Enum;

/**
 * LearnDash Commerce Product Cancellation Reason enum.
 *
 * @since 4.25.3
 *
 * @extends Enum<Cancellation_Reason::*>
 *
 * @method static self REFUNDED()
 * @method static self FAILED_PAYMENT()
 * @method static self CANCELED_BY_ADMIN()
 * @method static self CANCELED_BY_STUDENT()
 */
class Cancellation_Reason extends Enum {
	/**
	 * Product cancellation reason 'Refunded'.
	 *
	 * @since 4.25.3
	 */
	private const REFUNDED = 'refunded';

	/**
	 * Product cancellation reason 'Failed payment'.
	 *
	 * @since 4.25.3
	 */
	private const FAILED_PAYMENT = 'failed_payment';

	/**
	 * Product cancellation reason 'Canceled by admin'.
	 *
	 * @since 4.25.3
	 */
	private const CANCELED_BY_ADMIN = 'canceled_by_admin';

	/**
	 * Product cancellation reason 'Canceled by student'.
	 *
	 * @since 4.25.3
	 */
	private const CANCELED_BY_STUDENT = 'canceled_by_student';

	/**
	 * Returns the human-readable description for the cancellation reason.
	 *
	 * @since 4.25.3
	 *
	 * @param string   $product_type_label The label of the product type.
	 * @param int|null $user_id            Optional user ID for student cancellation reason.
	 *
	 * @return string The description.
	 */
	public function get_description( string $product_type_label, ?int $user_id = null ): string {
		switch ( $this->getValue() ) {
			case self::REFUNDED:
				// translators: %s: Product type label.
				return sprintf( __( '%s canceled because of a refund', 'learndash' ), $product_type_label );
			case self::FAILED_PAYMENT:
				// translators: %s: Product type label.
				return sprintf( __( '%s canceled because of a failed payment', 'learndash' ), $product_type_label );
			case self::CANCELED_BY_ADMIN:
				// translators: %s: Product type label.
				return sprintf( __( '%s canceled by Admin', 'learndash' ), $product_type_label );
			case self::CANCELED_BY_STUDENT:
				if ( $user_id ) {
					$user = get_user_by( 'id', $user_id );

					if ( $user ) {
						return sprintf(
							// translators: %s: Product type label. %s: User display name.
							__( '%1$s canceled by %2$s', 'learndash' ),
							$product_type_label,
							$user->display_name
						);
					}
				}

				// translators: %s: Product type label.
				return sprintf( __( '%s canceled by Student', 'learndash' ), $product_type_label );
			default:
				// translators: %s: Product type label.
				return sprintf( __( '%s canceled', 'learndash' ), $product_type_label );
		}
	}
}
