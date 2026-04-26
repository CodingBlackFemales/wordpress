<?php
/**
 * Select2 response DTO for AJAX module.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AJAX\DTO\Response;

use Learndash_DTO;

/**
 * Select2 response DTO class.
 *
 * @since 4.8.0
 */
class Select2 extends Learndash_DTO {
	/**
	 * Properties are being cast to the specified type on construction.
	 *
	 * @since 4.8.0
	 *
	 * @var array<string, string>
	 */
	protected $cast = [
		'results'    => 'array',
		'pagination' => 'array',
	];

	/**
	 * Results.
	 *
	 * @since 4.8.0
	 *
	 * @var array<int, array{id: int, text: string}>
	 */
	public $results;

	/**
	 * Pagination.
	 *
	 * @since 4.8.0
	 *
	 * @var array{more: bool}
	 */
	public $pagination;
}
