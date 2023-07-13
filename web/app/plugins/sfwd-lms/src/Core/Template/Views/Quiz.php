<?php
/**
 * The quiz view class.
 *
 * @since   4.6.0
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

use LDLMS_Post_Types;
use LearnDash_Custom_Label;
use LearnDash\Core\Models;
use LearnDash\Core\Template\Tabs;
use LearnDash\Core\Template\Breadcrumbs;
use WP_Post;

/**
 * The view class for LD custom post types.
 *
 * @since 4.6.0
 */
class Quiz extends View {
	/**
	 * The quiz model.
	 *
	 * @var Models\Quiz
	 */
	protected $model;

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_Post      $post    The post object.
	 * @param array<mixed> $context Context.
	 */
	public function __construct( WP_Post $post, array $context = [] ) {
		$this->model = Models\Quiz::create_from_post( $post );
		$this->model->enable_memoization();

		parent::__construct(
			LDLMS_Post_Types::get_post_type_key( $post->post_type ),
			$this->build_context( $context )
		);
	}

	/**
	 * Builds context for the rendering of this view.
	 *
	 * @since 4.6.0
	 *
	 * @param array<string, mixed> $context Context.
	 *
	 * @return array<string, mixed>
	 */
	protected function build_context( array $context = [] ): array {
		$defaults = [
			'course'       => $this->model->get_course(),
			'quiz'         => $this->model,
			'breadcrumbs'  => $this->get_breadcrumbs(),
			'title'        => $this->model->get_title(),
			'content'      => get_the_content( null, false, $this->model->get_post() ),
			'tabs'         => $this->get_tabs(),
			// TODO: replace these mocked values (or function calls that have mocked values) with real ones.
			'show_content' => $this->model->is_content_visible(),
		];

		return array_merge( $context, $defaults );
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

			$lesson = $this->model->get_lesson();
			$topic  = $this->model->get_topic();

			if ( $lesson ) {
				$breadcrumbs[] = [
					'url'   => $lesson->get_permalink(),
					'label' => $lesson->get_title(),
					'id'    => 'lesson',
				];
			} elseif ( $topic ) {
				$breadcrumbs[] = [
					'url'   => $topic->get_permalink(),
					'label' => $topic->get_title(),
					'id'    => 'topic',
				];
			}
		} else {
			$breadcrumbs = [
				[
					'url'   => learndash_post_type_has_archive( $this->model->get_post()->post_type )
						? (string) get_post_type_archive_link( $this->model->get_post()->post_type )
						: '',
					'label' => LearnDash_Custom_Label::get_label( 'quizzes' ),
					'id'    => 'quizzes',
				],
			];
		}

		$breadcrumbs[] = [
			'url'   => '',
			'label' => $this->model->get_title(),
			'id'    => 'quiz',
		];

		$breadcrumbs = new Breadcrumbs\Breadcrumbs( $breadcrumbs );

		$breadcrumbs = $breadcrumbs->update_is_last()->all();

		/** This filter is documented in src/Core/Template/Views/Group.php */
		$breadcrumbs = (array) apply_filters( 'learndash_template_views_breadcrumbs', $breadcrumbs, $this->view_slug, $this );

		/**
		 * Filters the quiz breadcrumbs.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string, string>[]|Breadcrumbs\Breadcrumb[] $breadcrumbs The breadcrumbs.
		 * @param string                                           $view_slug   The view slug.
		 * @param Quiz                                             $view        The view object.
		 *
		 * @ignore
		 */
		$breadcrumbs = (array) apply_filters( 'learndash_template_views_quiz_breadcrumbs', $breadcrumbs, $this->view_slug, $this );

		// Rebuild the breadcrumbs objects after filtering.
		return new Breadcrumbs\Breadcrumbs( $breadcrumbs );
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
					'id'       => 'content',
					'icon'     => 'quiz',
					'label'    => LearnDash_Custom_Label::get_label( 'quiz' ),
					'template' => 'quiz/tabs/quiz',
					'order'    => 1,
				],
				[
					'id'       => 'materials',
					'icon'     => 'materials',
					'label'    => __( 'Materials', 'learndash' ),
					'template' => 'quiz/tabs/materials',
					'order'    => 2,
				],
			]
		);

		$tabs_array = $tabs->filter_empty_content()->sort()->all();

		/** This filter is documented in src/Core/Template/Views/Group.php */
		$tabs_array = (array) apply_filters( 'learndash_template_views_tabs', $tabs_array, $this->view_slug, $this );

		/**
		 * Filters the course tabs.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string, Tabs\Tab>|array<int, array<string, mixed>> $tabs      The tabs.
		 * @param string                                                   $view_slug The view slug.
		 * @param Quiz                                                     $view      The view object.
		 *
		 * @ignore
		 */
		$tabs_array = (array) apply_filters( 'learndash_template_views_quiz_tabs', $tabs_array, $this->view_slug, $this );

		// Rebuild the tabs object after filtering.
		return new Tabs\Tabs( $tabs_array );
	}
}
