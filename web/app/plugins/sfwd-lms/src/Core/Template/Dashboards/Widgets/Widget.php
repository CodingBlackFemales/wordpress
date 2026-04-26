<?php
/**
 * A dashboard widget base class.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Dashboards\Widgets;

use LearnDash\Core\Template\Template;

/**
 * A dashboard widget base class.
 *
 * @since 4.9.0
 */
abstract class Widget {
	/**
	 * A flag that indicates whether the widget data is loaded.
	 * It is used to avoid multiple loading. Default is false.
	 *
	 * @since 4.9.0
	 *
	 * @var bool
	 */
	protected $is_loaded = false;

	/**
	 * Renders a widget.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	public function render(): void {
		$this->load();

		Template::show_admin_template(
			$this->get_view_path(),
			[
				'widget' => $this,
			]
		);
	}

	/**
	 * Returns a widget empty state text. Default is 'No data'. It is used when there is no data to show.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_empty_state_text(): string {
		return __( 'No data.', 'learndash' );
	}

	/**
	 * Loads required data.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	abstract protected function load_data(): void;

	/**
	 * Returns a widget view name.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	abstract protected function get_view_name(): string;

	/**
	 * Returns a widget view path.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	protected function get_view_path(): string {
		/**
		 * Filters the dashboard widget view path.
		 *
		 * @since 4.9.0
		 *
		 * @param string $view_path The view path.
		 * @param Widget $widget    The widget instance.
		 *
		 * @return string The view path.
		 */
		return apply_filters(
			'learndash_dashboard_widget_view_path',
			'dashboard/widgets/' . $this->get_view_name(),
			$this
		);
	}

	/**
	 * Loads a widget if it's not loaded, it is executed before rendering.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	protected function load(): void {
		if ( $this->is_loaded ) {
			return;
		}

		/*
		 * Fires before the dashboard widget is loaded.
		 *
		 * @since 4.9.0
		 *
		 * @param Widget $this The widget instance.
		 */
		do_action( 'learndash_dashboard_widget_before_loading', $this );

		// Load widget data.
		$this->load_data();

		$this->is_loaded = true;

		/*
		 * Fires after the dashboard widget is loaded.
		 *
		 * @since 4.9.0
		 *
		 * @param Widget $this The widget instance.
		 */
		do_action( 'learndash_dashboard_widget_after_loading', $this );
	}
}
