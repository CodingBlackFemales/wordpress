<?php
/**
 * LearnDash Steps class.
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

use LearnDash\Core\Collections\Collection;

// TODO: Write tests for it.

/**
 * LearnDash Steps class.
 *
 * @since 4.6.0
 *
 * @extends Collection<Step, int>
 */
class Steps extends Collection {
	/**
	 * Collection of items.
	 *
	 * @since 4.6.0
	 *
	 * @var array<int, Step>
	 */
	protected $items = [];

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param Step[]|array<int, array<string, mixed>> $steps Array of objects which implement the Step interface.
	 */
	public function __construct( array $steps = [] ) {
		parent::__construct();

		$this->parse_steps( $steps );
	}

	/**
	 * Adds a step to the collection.
	 *
	 * @since 4.6.0
	 *
	 * @param Step $step Step to add.
	 *
	 * @return Step
	 */
	public function add( Step $step ): Step {
		return parent::set( $step->get_id(), $step );
	}

	/**
	 * Parses an array into an array of Step objects and sets.
	 *
	 * @since 4.6.0
	 *
	 * @param Step[]|array<int, array<string, mixed>> $steps The steps to parse.
	 *
	 * @return void
	 */
	protected function parse_steps( array $steps ): void {
		if ( empty( $steps ) ) {
			return;
		}

		foreach ( $steps as $step ) {
			$this->add( Step::parse( $step ) );
		}
	}
}
