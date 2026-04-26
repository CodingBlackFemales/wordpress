<?php
/**
 * Action DTO for Validators.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Validations\Validators\DTO;

use Learndash_DTO;

/**
 * Action DTO class.
 *
 * @since 4.8.0
 */
class Action extends Learndash_DTO {
	/**
	 * Properties are being cast to the specified type on construction.
	 *
	 * @since 4.8.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'url'   => 'string',
		'label' => 'string',
	];

	/**
	 * Action URL.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public $url = '';

	/**
	 * Action label.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public $label = '';
}
