<?php
/**
 * The base view class for a dashboard.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Admin_Views\Dashboards;

use LearnDash\Core\Template\Admin_Views\View;
use LearnDash\Core\Template\Dashboards\Sections\Section;
use LearnDash\Core\Template\Dashboards\Sections\Sections;

/**
 * The base view class for a dashboard.
 *
 * @since 4.9.0
 */
class Dashboard extends View {
	/**
	 * Whether the dashboard is enabled. Default true.
	 *
	 * @since 4.9.0
	 *
	 * @var bool
	 */
	protected $is_enabled = true;

	/**
	 * Constructor.
	 *
	 * @since 4.9.0
	 *
	 * @param string  $view_slug View slug.
	 * @param Section $section   Section.
	 */
	public function __construct( string $view_slug, Section $section ) {
		/**
		 * Filters the sections of the dashboard.
		 *
		 * @since 4.9.0
		 *
		 * @param Sections $sections  The sections.
		 * @param string   $view_slug View slug.
		 *
		 * @return Sections
		 */
		$child_sections = apply_filters(
			'learndash_dashboard_sections',
			$section->get_sections(),
			$view_slug
		);

		$section->set_sections( $child_sections );

		/**
		 * Filters whether the dashboard is enabled. Default true.
		 *
		 * @since 4.9.0
		 *
		 * @param bool    $is_enabled Whether the dashboard is enabled.
		 * @param string  $view_slug  View slug.
		 * @param Section $section    Section.
		 *
		 * @return bool
		 */
		$this->is_enabled = apply_filters( 'learndash_dashboard_is_enabled', $this->is_enabled, $view_slug, $section );

		parent::__construct(
			$view_slug,
			[
				'section'            => $section,
				'propanel_is_active' => is_plugin_active( 'learndash-propanel/learndash_propanel.php' ),
				'is_enabled'         => $this->is_enabled,
			]
		);
	}
}
