<?php
/**
 * The lesson view class.
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

namespace LearnDash\Core\Template\Views;

use InvalidArgumentException;
use LDLMS_Post_Types;
use LearnDash\Core\Models;
use LearnDash\Core\Mappers;
use LearnDash\Core\Template\Tabs;
use LearnDash\Core\Template\Breadcrumbs;
use LearnDash\Core\Template\Views\Traits\Has_Steps;
use LearnDash\Core\Traits\Memoizable;
use LearnDash_Custom_Label;
use WP_Post;

/**
 * The view class for LD lesson post type.
 *
 * @since 4.6.0
 */
class Lesson extends View implements Interfaces\Has_Steps {
	use Has_Steps;
	use Memoizable;

	/**
	 * The related model.
	 *
	 * @var Models\Lesson
	 */
	protected $model;

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @throws InvalidArgumentException If the post type is not allowed.
	 */
	public function __construct( WP_Post $post ) {
		$this->enable_memoization();

		$this->model = Models\Lesson::create_from_post( $post );
		$this->model->enable_memoization();

		parent::__construct(
			LDLMS_Post_Types::get_post_type_key( $post->post_type ),
			$this->build_context()
		);
	}

	/**
	 * Returns the total number of steps.
	 *
	 * TODO: Here it will contain the wrong number if some of the lessons or quizzes are not published. So we need to decide how we want to handle this case.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function get_total_steps(): int {
		return $this->memoize(
			function (): int {
				$steps_mapper = new Mappers\Steps\Lesson( $this->model );

				return $steps_mapper->total();
			}
		);
	}

	/**
	 * Returns the steps page size.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function get_steps_page_size(): int {
		return $this->memoize(
			function (): int {
				$course = $this->model->get_course();

				// TODO: Rename these settings to make more sense.
				return learndash_get_course_lessons_per_page(
					$course ? $course->get_id() : 0
				);
			}
		);
	}

	/**
	 * Builds context for the rendering of this view.
	 *
	 * @since 4.6.0
	 *
	 * @return array<string, mixed>
	 */
	protected function build_context(): array {
		$user   = wp_get_current_user();
		$course = $this->model->get_course();

		return [
			'lesson'      => $this->model,
			'course'      => $course,
			'breadcrumbs' => $this->get_breadcrumbs(),
			'title'       => $this->model->get_title(),
			'is_enrolled' => $course && $course->get_product()->user_has_access( $user ), // TODO: Not sure if it's correct.
			'tabs'        => $this->get_tabs(),
		];
	}

	/**
	 * Gets the tabs.
	 *
	 * @since 4.6.0
	 *
	 * @return Tabs\Tabs
	 */
	protected function get_tabs(): Tabs\Tabs {
		$tabs = new Tabs\Tabs(
			[
				[
					'id'      => 'content',
					'icon'    => 'lesson',
					'label'   => LearnDash_Custom_Label::get_label( 'lesson' ),
					'content' => $this->model->get_content() . $this->map_steps_content(),
					'order'   => 1,
				],
				[
					'id'      => 'materials',
					'icon'    => 'materials',
					'label'   => __( 'Materials', 'learndash' ),
					'content' => $this->model->get_materials(),
					'order'   => 2,
				],
			]
		);

		$tabs_array = $tabs->filter_empty_content()->sort()->all();

		/** This filter is documented in src/Core/Template/Views/Group.php */
		$tabs_array = (array) apply_filters( 'learndash_template_views_tabs', $tabs_array, $this->view_slug, $this );

		/**
		 * Filters the lesson tabs.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string, Tabs\Tab>|array<int, array<string, mixed>> $tabs      The tabs.
		 * @param string                                                   $view_slug The view slug.
		 * @param Lesson                                                   $view      The view object.
		 *
		 * @ignore
		 */
		$tabs_array = (array) apply_filters( 'learndash_template_views_lesson_tabs', $tabs_array, $this->view_slug, $this );

		// Rebuild the tabs object after filtering.
		return new Tabs\Tabs( $tabs_array );
	}

	/**
	 * Gets the breadcrumbs.
	 *
	 * @since 4.6.0
	 *
	 * @return Breadcrumbs\Breadcrumbs
	 */
	protected function get_breadcrumbs(): Breadcrumbs\Breadcrumbs {
		$course = $this->model->get_course();

		if ( $course ) {
			$breadcrumbs = $this->get_breadcrumbs_base();

			$breadcrumbs[] = [
				'url'   => $course->get_permalink(),
				'label' => $course->get_title(),
				'id'    => 'course',
			];
		} else {
			$breadcrumbs = [
				[
					'url'   => learndash_post_type_has_archive( $this->model->get_post()->post_type )
						? (string) get_post_type_archive_link( $this->model->get_post()->post_type )
						: '',
					'label' => LearnDash_Custom_Label::get_label( 'lessons' ),
					'id'    => 'lessons',
				],
			];
		}

		$breadcrumbs[] = array(
			'url'   => '',
			'label' => $this->model->get_title(),
			'id'    => 'lesson',
		);

		$breadcrumbs = new Breadcrumbs\Breadcrumbs( $breadcrumbs );

		$breadcrumbs = $breadcrumbs->update_is_last()->all();

		/** This filter is documented in src/Core/Template/Views/Group.php */
		$breadcrumbs = (array) apply_filters( 'learndash_template_views_breadcrumbs', $breadcrumbs, $this->view_slug, $this );

		/**
		 * Filters the lesson breadcrumbs.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string, string>[]|Breadcrumbs\Breadcrumb[] $breadcrumbs The breadcrumbs.
		 * @param string                                           $view_slug   The view slug.
		 * @param Lesson                                           $view        The view object.
		 *
		 * @ignore
		 */
		$breadcrumbs = (array) apply_filters( 'learndash_template_views_lesson_breadcrumbs', $breadcrumbs, $this->view_slug, $this );

		// Rebuild the breadcrumbs objects after filtering.
		return new Breadcrumbs\Breadcrumbs( $breadcrumbs );
	}

	/**
	 * Maps the steps content.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	protected function map_steps_content(): string {
		$steps_mapper = new Mappers\Steps\Lesson( $this->model );

		$steps = $steps_mapper
			->with_sub_steps()
			->paginated( $this->get_current_steps_page(), $this->get_steps_page_size() );

		return $this->get_steps_content( $steps );
	}
}
