<?php
/**
 * Values Dashboard Widget Item.
 *
 * @since   4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Dashboards\Widgets\Types\DTO;

use Learndash_DTO;

/**
 * Values Dashboard Widget Item.
 *
 * @since 4.9.0
 */
class Values_Item extends Learndash_DTO {
	/**
	 * Label. Default empty string.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	public $label = '';

	/**
	 * Value. Default 0.
	 *
	 * @since 4.9.0
	 *
	 * @var string|int|float
	 */
	public $value = 0;

	/**
	 * Sub label. Default empty string.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	public $sub_label = '';
}
