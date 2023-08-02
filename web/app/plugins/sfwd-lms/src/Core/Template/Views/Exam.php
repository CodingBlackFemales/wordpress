<?php
/**
 * The exam view class.
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
use LearnDash_Custom_Label;
use LearnDash\Core\Template\Breadcrumbs;
use WP_Post;

/**
 * The view class for LD exam post type.
 *
 * @since 4.6.0
 */
class Exam extends View {
	/**
	 * The related model.
	 *
	 * @var Models\Exam
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
		$this->model = Models\Exam::create_from_post( $post );
		$this->model->enable_memoization();

		parent::__construct(
			LDLMS_Post_Types::get_post_type_key( $post->post_type ),
			$this->build_context()
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
		$course = $this->model->get_course();
		$user   = wp_get_current_user();

		return [
			'topic'              => $this->model,
			'course'             => $course,
			'breadcrumbs'        => $this->get_breadcrumbs(),
			'title'              => $this->model->get_title(),
			'content_is_visible' => true, // TODO: Not sure what controls it.
			'is_enrolled'        => $course && $course->get_product()->user_has_access( $user ), // TODO: Not sure if it's correct.
			'tabs'               => [], // TODO: Add.
		];
	}

	/**
	 * Gets the breadcrumbs.
	 *
	 * @since 4.6.0
	 *
	 * @return BreadCrumbs\Breadcrumbs
	 */
	protected function get_breadcrumbs(): BreadCrumbs\Breadcrumbs {
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
					'url'   => '',
					'label' => LearnDash_Custom_Label::get_label( 'exams' ),
					'id'    => 'exams',
				],
			];
		}

		$breadcrumbs[] = array(
			'url'   => '',
			'label' => $this->model->get_title(),
			'id'    => 'exam',
		);

		$breadcrumbs = new Breadcrumbs\Breadcrumbs( $breadcrumbs );

		$breadcrumbs = $breadcrumbs->update_is_last()->all();

		/** This filter is documented in src/Core/Template/Views/Group.php */
		$breadcrumbs = (array) apply_filters( 'learndash_template_views_breadcrumbs', $breadcrumbs, $this->view_slug, $this );

		/**
		 * Filters the exam breadcrumbs.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string, string>[]|Breadcrumbs\Breadcrumb[] $breadcrumbs The breadcrumbs.
		 * @param string                                           $view_slug   The view slug.
		 * @param Exam                                             $view        The view object.
		 *
		 * @ignore
		 */
		$breadcrumbs = (array) apply_filters( 'learndash_template_views_exam_breadcrumbs', $breadcrumbs, $this->view_slug, $this );

		// Rebuild the breadcrumbs objects after filtering.
		return new Breadcrumbs\Breadcrumbs( $breadcrumbs );
	}
}
