<?php
/**
 * Reports Dashboard Mapper.
 *
 * @since 4.17.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports\Dashboard;

use LearnDash\Core\Modules\Reports\Dashboard\Widgets as Reports_Widgets;
use LearnDash\Core\Template\Dashboards\Sections\Section;
use LearnDash\Core\Template\Dashboards\Sections\Sections;

/**
 * Reports Dashboard Mapper.
 *
 * @since 4.17.0
 */
class Mapper {
	/**
	 * Maps the sections. Returns one section because it's the root section.
	 *
	 * @since 4.17.0
	 *
	 * @return Section
	 */
	public function map(): Section {
		$filtering      = new Reports_Widgets\Filtering();
		$overview       = new Reports_Widgets\Overview();
		$activity       = new Reports_Widgets\Activity();
		$progress_chart = new Reports_Widgets\Progress_Chart();
		$reporting      = new Reports_Widgets\Reporting();

		$sections = Sections::make(
			[
				Section::create()
					->set_size( 6, Section::$screen_medium )
					->set_size( 4 )
					->add_widget( $filtering ),
				Section::create()
					->set_size( 6, Section::$screen_medium )
					->set_size( 8 )
					->add_section(
						Section::create()
							->set_size( 6 )
							->add_widget( $overview )
							->add_widget( $activity )
					)
					->add_section(
						Section::create()
							->set_size( 6 )
							->add_widget( $progress_chart )
							->add_widget( $reporting )
					),
			]
		);

		/**
		 * Filters the reports dashboard sections.
		 *
		 * @since 4.17.0
		 *
		 * @param Sections $sections The sections.
		 *
		 * @return Sections
		 */
		$sections = apply_filters( 'learndash_dashboard_sections_reports', $sections );

		return Section::create()->set_sections( $sections );
	}
}
