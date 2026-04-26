<?php
/**
 * Card DTO for LearnDash Commerce.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\DTO;

use Learndash_DTO;

/**
 * Card DTO class.
 *
 * @since 4.25.0
 */
class Card extends Learndash_DTO {
	/**
	 * Properties are being cast to the specified type on construction.
	 *
	 * @since 4.25.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'gateway_id'    => 'string',
		'card_id'       => 'string',
		'brand'         => 'string',
		'holder_name'   => 'string',
		'last_4_digits' => 'string',
		'expiry_date'   => 'string',
	];

	/**
	 * LearnDash gateway ID (e.g., paypal_checkout, stripe, etc.).
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public $gateway_id = '';

	/**
	 * Card ID used internally to identify a card.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public $card_id = '';

	/**
	 * Card brand (e.g., visa, mastercard, etc.).
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public $brand = '';

	/**
	 * Card holder's name.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public $holder_name = '';

	/**
	 * Last 4 digits of the card number.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public $last_4_digits = '';

	/**
	 * Card expiry date (format: MM/YY).
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public $expiry_date = '';
}
