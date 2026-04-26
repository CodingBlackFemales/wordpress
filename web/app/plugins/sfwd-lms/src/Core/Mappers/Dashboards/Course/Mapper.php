<?php
/**
 * Course Dashboard Mapper.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Mappers\Dashboards\Course;

use InvalidArgumentException;
use LDLMS_Post_Types;
use LearnDash\Core\Mappers\Dashboards\Course\Widgets as Course_Widgets;
use LearnDash\Core\Mappers\Dashboards\Mapper as Base_Mapper;
use LearnDash\Core\Template\Dashboards\Sections\Section;
use LearnDash\Core\Template\Dashboards\Sections\Sections;
use LearnDash_Custom_Label;
use WP_Post;

/**
 * Course Dashboard Mapper.
 *
 * @since 4.9.0
 */
class Mapper extends Base_Mapper {
	/**
	 * Constructor.
	 *
	 * @since 4.9.0
	 *
	 * @param WP_Post $post The post.
	 *
	 * @throws InvalidArgumentException If the post type is not allowed.
	 */
	public function __construct( WP_Post $post ) {
		$correct_post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE );

		if ( $correct_post_type !== $post->post_type ) {
			throw new InvalidArgumentException( 'The post type is not allowed. Allowed post type is: ' . $correct_post_type );
		}

		parent::__construct( $post );
	}

	/**
	 * Maps the sections. Returns one section because it's the root section.
	 *
	 * @since 4.9.0
	 *
	 * @return Section
	 */
	public function map(): Section {
		$content_numbers              = new Course_Widgets\Content_Numbers();
		$enrollments_progress         = new Course_Widgets\Enrollments_Progress();
		$enrolled_students_number     = new Course_Widgets\Enrolled_Students_Number();
		$latest_enrollees             = new Course_Widgets\Latest_Enrollees();
		$latest_transactions          = new Course_Widgets\Latest_Transactions();
		$lifetime_sales               = new Course_Widgets\Lifetime_Sales();
		$students_progress_allocation = new Course_Widgets\Students_Progress_Allocation();

		$sections = Sections::make(
			[
				Section::create()
					->add_section(
						Section::create()
							->set_size( 5 )
							->set_title( __( 'Total Numbers', 'learndash' ) )
							->set_hint(
								sprintf(
									// Translators: %1$s: Course label, %2$s: Course label, %3$s: Orders label, %4$s: Course label, %5$s: Course label, %6$s: Course label, %7$s: Group label.
									__(
										'<b>Total numbers for the %1$s.</b><br/><br/>
										<b>Lifetime sales</b> are the total amount of money earned from the %2$s, considering all the %3$s for the %4$s in the current currency.<br/>
										It considers the %5$s price, the trial price (if any), and coupons.<br/>
										It does not consider potential refunds and recurring payments as they are not processed in LearnDash.<br/><br/>
										<b>Enrolled students</b> are the total number of students who have enrolled in the %6$s, including those who have access via %7$s.',
										'learndash'
									),
									learndash_get_custom_label_lower( 'course' ),
									learndash_get_custom_label_lower( 'course' ),
									learndash_get_custom_label_lower( 'orders' ),
									learndash_get_custom_label_lower( 'course' ),
									learndash_get_custom_label_lower( 'course' ),
									learndash_get_custom_label_lower( 'course' ),
									learndash_get_custom_label_lower( 'group' )
								)
							)
							->add_widget( $lifetime_sales )
							->add_widget( $enrolled_students_number )
					)
					->add_section(
						Section::create()
							->set_size( 4 )
							->set_title( __( 'Content Numbers', 'learndash' ) )
							->add_widget( $content_numbers )
					)
					->add_section(
						Section::create()
							->set_size( 3 )
							->set_title( __( 'Last 7 Days', 'learndash' ) )
							->set_hint(
								__(
									'<b>Number of enrollments in the last 7 days.</b><br/><br/>
									It considers all users who have enrolled in the course during this period, including those who do not have access anymore.',
									'learndash'
								)
							)
							->add_widget( $enrollments_progress )
					),
				Section::create()
				->add_section(
					Section::create()
					->set_size( 8 )
					->add_section(
						Section::create()
							->set_title( __( 'Students Allocation by Progress Status', 'learndash' ) )
							->add_widget( $students_progress_allocation )
					)
					->add_section(
						Section::create()
							->set_title(
								sprintf(
									/* translators: %s: Transactions label */
									__( 'Latest %s', 'learndash' ),
									LearnDash_Custom_Label::get_label( 'transactions' )
								)
							)
							->add_widget( $latest_transactions )
					)
				)
				->add_section(
					Section::create()
						->set_size( 4 )
						->set_title( __( 'Newly Joined', 'learndash' ) )
						->set_hint(
							__(
								'<b>Latest students who have enrolled in the course.</b><br/><br/>
								It considers the latest users who have enrolled in the course, including those who have enrolled via course groups or do not have access anymore.',
								'learndash'
							)
						)
						->add_widget( $latest_enrollees )
				),
			]
		);

		/**
		 * Filters the course dashboard sections.
		 *
		 * @since 4.9.0
		 *
		 * @param Sections $sections The sections.
		 * @param WP_Post  $post     The post.
		 *
		 * @return Sections
		 */
		$sections = apply_filters( 'learndash_dashboard_sections_course', $sections, $this->post );

		return Section::create()->set_sections( $sections );
	}
}
