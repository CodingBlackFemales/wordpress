<?php
/**
 * The course view class.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Views;

use InvalidArgumentException;
use LDLMS_Post_Types;
use LearnDash\Core\Models;
use LearnDash\Core\Template\Tabs;
use LearnDash_Custom_Label;
use WP_Post;
use LearnDash\Core\Template\Progression;
use LearnDash\Core\Template\Alerts;

/**
 * The view class for LD course post type.
 *
 * @since 4.6.0
 */
class Course extends View {
	/**
	 * The related model.
	 *
	 * @since 4.6.0
	 *
	 * @var Models\Course
	 */
	protected $model;

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_Post              $post    The post object.
	 * @param array<string, mixed> $context Context.
	 *
	 * @throws InvalidArgumentException If the post type is not allowed.
	 */
	public function __construct( WP_Post $post, array $context = [] ) {
		$this->context = $context;
		$this->model   = Models\Course::create_from_post( $post );

		parent::__construct(
			LDLMS_Post_Types::get_post_type_key( $post->post_type ),
			$this->build_context()
		);
	}

	/**
	 * Returns the model.
	 *
	 * @since 4.21.0
	 *
	 * @return Models\Course
	 */
	public function get_model(): Models\Course {
		return $this->model;
	}

	/**
	 * Builds context for the rendering of this view.
	 *
	 * @since 4.6.0
	 *
	 * @return array<string, mixed>
	 */
	protected function build_context(): array {
		$context = array_merge(
			// Parent context.
			$this->context,
			// Default context (is used across all themes).
			[
				'course'       => $this->model,
				'has_access'   => $this->model->get_product()->user_has_access(),
				'login_url'    => learndash_get_login_url(),
				'product'      => $this->model->get_product(),
				'progress_bar' => $this->get_progress_bar(),
				'tabs'         => $this->get_tabs(),
			]
		);

		$context['alerts'] = $this->get_alerts( $context );

		return $context;
	}

	/**
	 * Gets the tabs.
	 *
	 * @since 4.6.0
	 *
	 * @return Tabs\Tabs
	 */
	protected function get_tabs(): Tabs\Tabs {
		$content = $this->model->get_content();

		if (
			! empty( $content )
			&& has_post_thumbnail( $this->model->get_post() )
		) {
			$content = get_the_post_thumbnail(
				$this->model->get_post(),
				'large', // We assume this is the default 1024x1024 size.
				[
					'class' => 'ld-featured-image ld-featured-image--course',
				]
			) . $content;
		}

		$tabs_array = [
			[
				'id'      => 'content',
				'icon'    => 'course',
				'label'   => LearnDash_Custom_Label::get_label( 'course' ),
				'content' => $content,
				'order'   => 10,
			],
			[
				'id'      => 'materials',
				'icon'    => 'materials',
				'label'   => __( 'Materials', 'learndash' ),
				'content' => $this->model->get_materials(),
				'order'   => 20,
			],
		];

		/** This filter is documented in themes/ld30/templates/modules/tabs.php */
		$tabs_array = (array) apply_filters(
			'learndash_content_tabs',
			$tabs_array,
			LDLMS_Post_Types::get_post_type_key( $this->model->get_post()->post_type ),
			$this->model->get_id(),
			get_current_user_id()
		);

		/**
		 * Filters the tabs.
		 *
		 * @since 4.21.0
		 *
		 * @param array<int, array<string, array{id: string, icon: string, label: string, content: string, order?: int}>> $tabs      The tabs.
		 * @param string                                                                                                  $view_slug The view slug.
		 * @param Course                                                                                                  $view      The view object.
		 *
		 * @return array<int, array<string, array{id: string, icon: string, label: string, content: string, order?: int}>>
		 */
		$tabs_array = (array) apply_filters( 'learndash_template_views_tabs', $tabs_array, $this->view_slug, $this );

		/**
		 * Filters the course tabs.
		 *
		 * @since 4.21.0
		 *
		 * @param array<int, array<string, array{id: string, icon: string, label: string, content: string, order?: int}>> $tabs      The tabs.
		 * @param string                                                                                                  $view_slug The view slug.
		 * @param Course                                                                                                  $view      The view object.
		 * @param Models\Course                                                                                           $model     The course model.
		 *
		 * @return array<int, array<string, array{id: string, icon: string, label: string, content: string, order?: int}>>
		 */
		$tabs_array = (array) apply_filters( 'learndash_template_views_course_tabs', $tabs_array, $this->view_slug, $this, $this->model );

		$tabs = new Tabs\Tabs( $tabs_array );

		return $tabs->filter_empty_content()->sort();
	}

	/**
	 * Maps the progress bar.
	 *
	 * @since 4.24.0
	 *
	 * @return Progression\Bar
	 */
	protected function get_progress_bar(): Progression\Bar {
		// Map the numbers.

		$page_size = 100;

		// Lessons.

		$lessons_number           = $this->model->get_lessons_number();
		$completed_lessons_number = 0;

		$lessons_offset = 0;
		$lessons        = $this->model->get_lessons( $page_size, $lessons_offset );

		while ( ! empty( $lessons ) ) {
			$completed_lessons_number += count(
				array_filter( $lessons, fn( $lesson ) => $lesson->is_complete() )
			);

			$lessons_offset += $page_size;

			$lessons = $this->model->get_lessons( $page_size, $lessons_offset );
		}

		// Topics.

		$topics_number           = $this->model->get_topics_number();
		$completed_topics_number = 0;

		$topics_offset = 0;
		$topics        = $this->model->get_topics( $page_size, $topics_offset );

		while ( ! empty( $topics ) ) {
			$completed_topics_number += count(
				array_filter( $topics, fn( $topic ) => $topic->is_complete() )
			);

			$topics_offset += $page_size;

			$topics = $this->model->get_topics( $page_size, $topics_offset );
		}

		// Quizzes.

		$quizzes_number           = $this->model->get_quizzes_number();
		$completed_quizzes_number = 0;

		$quizzes_offset = 0;
		$quizzes        = $this->model->get_quizzes( $page_size, $quizzes_offset, true );

		while ( ! empty( $quizzes ) ) {
			$completed_quizzes_number += count(
				array_filter( $quizzes, fn( $quiz ) => $quiz->is_complete() )
			);

			$quizzes_offset += $page_size;

			$quizzes = $this->model->get_quizzes( $page_size, $quizzes_offset );
		}

		// Map the progress bar.

		$progress_bar = new Progression\Bar();

		$progress_bar
			->set_total_step_count( $lessons_number + $topics_number + $quizzes_number )
			->set_completed_step_count( $completed_lessons_number + $completed_topics_number + $completed_quizzes_number )
			->set_label( $this->model->get_post_type_label() )
			->set_last_activity( $this->model->get_last_activity() );

		/**
		 * Filters the course progress bar.
		 *
		 * @since 4.24.0
		 *
		 * @param Progression\Bar $progress_bar The progress bar.
		 * @param string          $view_slug    The view slug.
		 * @param Course          $view         The view object.
		 * @param Models\Course   $model        The course model.
		 *
		 * @return Progression\Bar
		 */
		return apply_filters( 'learndash_template_views_course_progress_bar', $progress_bar, $this->view_slug, $this, $this->model );
	}

	/**
	 * Gets the alerts.
	 *
	 * @since 4.24.0
	 *
	 * @param array<string, mixed> $context The context.
	 *
	 * @return Alerts\Alerts
	 */
	protected function get_alerts( array $context ): Alerts\Alerts {
		$alerts = [];

		$certificate_link = $this->get_model()->get_certificate_link();

		if ( ! empty( $certificate_link ) ) {
			$alerts[] = [
				'id'          => 'course-certificate',
				'action_type' => 'button',
				'button_icon' => 'download-mini',
				'link_target' => '_new',
				'link_text'   => __( 'Download Certificate', 'learndash' ),
				'link_url'    => $certificate_link,
				'message'     => __( "You've earned a certificate!", 'learndash' ),
				'type'        => 'info',
			];
		}

		/** This filter is documented in themes/ld30/templates/modern/lesson.php */
		$alerts = (array) apply_filters( 'learndash_template_views_alerts', $alerts, $this->view_slug, $this );

		/**
		 * Filters the course alerts.
		 *
		 * @since 4.24.0
		 *
		 * @param array<string, string>[]|Alerts\Alert[] $alerts    The alerts.
		 * @param string                                 $view_slug The view slug.
		 * @param Course                                 $view      The view object.
		 * @param Models\Course                          $model     The course model.
		 *
		 * @return array<string, string>[]|Alerts\Alert[]
		 */
		$alerts = (array) apply_filters( 'learndash_template_views_course_alerts', $alerts, $this->view_slug, $this, $this->model );

		// Rebuild the Alerts objects after filtering.
		return new Alerts\Alerts( $alerts );
	}
}
