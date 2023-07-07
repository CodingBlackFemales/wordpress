<?php
/**
 * LearnDash Has_Steps interface.
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

namespace LearnDash\Core\Template\Views\Interfaces;

/**
 * Interface for views that have steps.
 */
interface Has_Steps {
	/**
	 * Returns the total number of steps.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function get_total_steps(): int;

	/**
	 * Returns the steps page size.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function get_steps_page_size(): int;
}
