<?php
/**
 * LearnDash sections collection class.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Dashboards\Sections;

use InvalidArgumentException;
use LearnDash\Core\Collections\Collection;

/**
 * The Sections collection.
 *
 * @since 4.9.0
 *
 * @extends Collection<Section, int>
 */
class Sections extends Collection {
	/**
	 * Constructor.
	 *
	 * @since 4.9.0
	 *
	 * @param Section[] $sections Array of sections.
	 *
	 * @throws InvalidArgumentException If a section is not a Section instance.
	 */
	public function __construct( array $sections = [] ) {
		parent::__construct();

		foreach ( $sections as $section ) {
			if ( ! $section instanceof Section ) {
				throw new InvalidArgumentException(
					sprintf( 'A section must be a %1$s instance.', Section::class )
				);
			}

			$this->push( $section );
		}
	}
}
