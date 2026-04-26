<?php
/**
 * Card model class.
 *
 * Represents a saved card in the LearnDash payment system.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models\Commerce;

use LearnDash\Core\Models\Model;
use LearnDash\Core\Utilities\Cast;

/**
 * Card model class.
 *
 * @since 4.25.0
 */
class Card extends Model {
	/**
	 * Returns the masked card number.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_masked_number(): string {
		return sprintf(
			'**** **** **** %s',
			Cast::to_string( $this->getAttribute( 'last_4_digits' ) )
		);
	}

	/**
	 * Returns the brand icon name.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_icon_name(): string {
		return Cast::to_string( $this->getAttribute( 'brand' ) );
	}

	/**
	 * Returns the holder name.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_holder_name(): string {
		return Cast::to_string( $this->getAttribute( 'holder_name' ) );
	}

	/**
	 * Returns the expiry date.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_expiry_date(): string {
		return Cast::to_string( $this->getAttribute( 'expiry_date' ) );
	}

	/**
	 * Returns the card ID.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_id(): string {
		return Cast::to_string( $this->getAttribute( 'card_id' ) );
	}

	/**
	 * Returns the gateway ID.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_gateway_id(): string {
		return Cast::to_string( $this->getAttribute( 'gateway_id' ) );
	}
}
