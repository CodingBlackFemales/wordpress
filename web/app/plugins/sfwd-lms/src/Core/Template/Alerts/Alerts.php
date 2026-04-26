<?php
/**
 * LearnDash Alerts collection class.
 *
 * @since 4.24.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Alerts;

use LearnDash\Core\Collections\Collection;

/**
 * The Alerts collection.
 *
 * @since 4.24.0
 *
 * @extends Collection<Alert, string>
 */
class Alerts extends Collection {
	/**
	 * Constructor.
	 *
	 * @since 4.24.0
	 *
	 * @param array<string, string>[]|Alert[] $alerts Array of alerts.
	 */
	public function __construct( array $alerts = [] ) {
		parent::__construct();

		$this->parse_alerts( $alerts );
	}

	/**
	 * Adds an alert to the collection.
	 *
	 * @since 4.24.0
	 *
	 * @param Alert $alert Alert to add.
	 *
	 * @return Alert
	 */
	public function add( Alert $alert ): Alert {
		return parent::set( $alert->get_id(), $alert );
	}

	/**
	 * Parses an array into an array of Alert objects and sets.
	 *
	 * @since 4.24.0
	 *
	 * @param array<string, string>[]|Alert[] $alerts The alerts to parse.
	 *
	 * @return void
	 */
	protected function parse_alerts( array $alerts ): void {
		if ( empty( $alerts ) ) {
			return;
		}

		foreach ( $alerts as $alert ) {
			$this->add( Alert::parse( $alert ) );
		}
	}
}
