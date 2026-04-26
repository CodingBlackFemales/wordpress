<?php
/**
 * ProPanel v2.x widget.
 *
 * @since 4.17.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports\Dashboard\Widgets\Types;

use LearnDash\Core\Template\Dashboards\Widgets\Widget;
use LearnDash_ProPanel_Widget;
use LearnDash\Core\Utilities\Cast;
use InvalidArgumentException;

/**
 * ProPanel v2.x Dashboard widget.
 *
 * @since 4.17.0
 */
abstract class ProPanel2_Widget extends Widget {
	/**
	 * ProPanel v2.x Widget instance.
	 *
	 * @since 4.17.0
	 *
	 * @var LearnDash_ProPanel_Widget|null
	 */
	protected ?LearnDash_ProPanel_Widget $propanel2_widget = null;

	/**
	 * Returns a ProPanel v2.x widget instance.
	 *
	 * @since 4.17.0
	 *
	 * @return LearnDash_ProPanel_Widget|null
	 */
	public function get_propanel2_widget(): ?LearnDash_ProPanel_Widget {
		return $this->propanel2_widget;
	}

	/**
	 * Sets a ProPanel v2.x widget instance.
	 *
	 * @since 4.17.0
	 *
	 * @param LearnDash_ProPanel_Widget $propanel2_widget Items.
	 *
	 * @return void
	 */
	public function set_propanel2_widget( LearnDash_ProPanel_Widget $propanel2_widget ): void {
		$this->propanel2_widget = $propanel2_widget;
	}

	/**
	 * Returns the initial widget template, which gets updated via JS on page load.
	 *
	 * @since 4.17.0
	 *
	 * @throws InvalidArgumentException When a ProPanel v2.x widget instance is not set.
	 *
	 * @return string
	 */
	public function get_initial_template(): string {
		if (
			empty( $this->propanel2_widget )
			|| ! $this->propanel2_widget instanceof LearnDash_ProPanel_Widget
		) {
			throw new InvalidArgumentException( 'You must set a ProPanel v2.x widget instance' );
		}

		ob_start();
		$this->propanel2_widget->initial_template();
		$html = Cast::to_string( ob_get_clean() );

		return wp_kses_post( $html );
	}

	/**
	 * Returns the name key which is used to identify the ProPanel v2.x widget.
	 *
	 * @since 4.17.0
	 *
	 * @throws InvalidArgumentException When a ProPanel v2.x widget instance is not set.
	 *
	 * @return string
	 */
	public function get_name(): string {
		if (
			empty( $this->propanel2_widget )
			|| ! $this->propanel2_widget instanceof LearnDash_ProPanel_Widget
		) {
			throw new InvalidArgumentException( 'You must set a ProPanel v2.x widget instance' );
		}

		return $this->propanel2_widget->get_name();
	}

	/**
	 * Returns the ProPanel v2.x widget's title.
	 *
	 * @since 4.17.0
	 *
	 * @throws InvalidArgumentException When a ProPanel v2.x widget instance is not set.
	 *
	 * @return string
	 */
	public function get_label(): string {
		if (
			empty( $this->propanel2_widget )
			|| ! $this->propanel2_widget instanceof LearnDash_ProPanel_Widget
		) {
			throw new InvalidArgumentException( 'You must set a ProPanel v2.x widget instance' );
		}

		return $this->propanel2_widget->get_label();
	}

	/**
	 * Returns a widget view name.
	 *
	 * @since 4.17.0
	 *
	 * @return string
	 */
	protected function get_view_name(): string {
		return 'reports/propanel2/widget';
	}
}
