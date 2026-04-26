<?php
/**
 * The lesson view class.
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
use LearnDash\Core\Template\Breadcrumbs;
use LearnDash\Core\Template\Progression;
use LearnDash\Core\Template\Alerts;
use LearnDash\Core\Utilities\Cast;

/**
 * The view class for LD lesson post type.
 *
 * @since 4.6.0
 */
class Lesson extends View {
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
	 * @param WP_Post              $post    The post object.
	 * @param array<string, mixed> $context Context.
	 *
	 * @throws InvalidArgumentException If the post type is not allowed.
	 */
	public function __construct( WP_Post $post, array $context = [] ) {
		$this->context = $context;
		$this->model   = Models\Lesson::create_from_post( $post );

		parent::__construct(
			LDLMS_Post_Types::get_post_type_key( $post->post_type ),
			$this->build_context()
		);
	}

	/**
	 * Returns the model.
	 *
	 * @since 4.24.0
	 *
	 * @return Models\Lesson
	 */
	public function get_model(): Models\Lesson {
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
		$user_id = get_current_user_id();

		$context = array_merge(
			// Parent context.
			$this->context,
			// Default context (is used across all themes).
			[
				'assignments'  => $this->model->get_assignments( $user_id ),
				'breadcrumbs'  => $this->get_breadcrumbs(),
				'has_access'   => $this->model->user_has_access(),
				'lesson'       => $this->model,
				'progress_bar' => $this->get_progress_bar(),
				'progression'  => new Progression\Step( $this->model, $user_id ),
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
					'class' => 'ld-featured-image ld-featured-image--lesson',
				]
			) . $content;
		}

		$tabs_array = [
			[
				'id'      => 'content',
				'icon'    => 'lesson',
				'label'   => LearnDash_Custom_Label::get_label( 'lesson' ),
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

		/** This filter is documented in src/Core/Template/Views/Course.php */
		$tabs_array = (array) apply_filters( 'learndash_template_views_tabs', $tabs_array, $this->view_slug, $this );

		/**
		 * Filters the lesson tabs.
		 *
		 * @since 4.24.0
		 *
		 * @param array<int, array<string, array{id: string, icon: string, label: string, content: string, order?: int}>> $tabs      The tabs.
		 * @param string                                                                                                  $view_slug The view slug.
		 * @param Lesson                                                                                                  $view      The view object.
		 * @param Models\Lesson                                                                                           $model     The lesson model.
		 *
		 * @return array<int, array<string, array{id: string, icon: string, label: string, content: string, order?: int}>>
		 */
		$tabs_array = (array) apply_filters( 'learndash_template_views_lesson_tabs', $tabs_array, $this->view_slug, $this, $this->model );

		$tabs = new Tabs\Tabs( $tabs_array );

		return $tabs->filter_empty_content()->sort();
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
			$breadcrumbs = [
				[
					'url'   => $course->get_permalink(),
					'label' => $course->get_title(),
					'id'    => 'course',
				],
			];
		} else {
			$breadcrumbs = [
				[
					'url'   => learndash_post_type_has_archive( $this->model->get_post_type() )
						? (string) get_post_type_archive_link( $this->model->get_post_type() )
						: '',
					'label' => LearnDash_Custom_Label::get_label( 'lessons' ),
					'id'    => 'lessons',
				],
			];
		}

		$breadcrumbs[] = [
			'url'   => '',
			'label' => $this->model->get_title(),
			'id'    => 'lesson',
		];

		$breadcrumbs = new Breadcrumbs\Breadcrumbs( $breadcrumbs );

		$breadcrumbs = $breadcrumbs->update_is_last()->all();

		/**
		 * Filters the breadcrumbs.
		 *
		 * @since 4.24.0
		 *
		 * @param array<string, string>[]|Breadcrumbs\Breadcrumb[] $breadcrumbs The breadcrumbs.
		 * @param string                                           $view_slug   The view slug.
		 * @param View                                             $view        The view object.
		 *
		 * @return array<string, string>[]|Breadcrumbs\Breadcrumb[]
		 */
		$breadcrumbs = (array) apply_filters( 'learndash_template_views_breadcrumbs', $breadcrumbs, $this->view_slug, $this );

		/**
		 * Filters the lesson breadcrumbs.
		 *
		 * @since 4.24.0
		 *
		 * @param array<string, string>[]|Breadcrumbs\Breadcrumb[] $breadcrumbs The breadcrumbs.
		 * @param string                                           $view_slug   The view slug.
		 * @param Lesson                                           $view        The view object.
		 * @param Models\Lesson                                    $model       The lesson model.
		 *
		 * @return array<string, string>[]|Breadcrumbs\Breadcrumb[]
		 */
		$breadcrumbs = (array) apply_filters( 'learndash_template_views_lesson_breadcrumbs', $breadcrumbs, $this->view_slug, $this, $this->model );

		// Rebuild the breadcrumbs objects after filtering.
		return new Breadcrumbs\Breadcrumbs( $breadcrumbs );
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

		$is_linear_progression_enabled = false;
		$course                        = $this->get_model()->get_course();

		if ( $course instanceof Models\Course ) {
			$is_linear_progression_enabled = $course->is_linear_progression_enabled();
		}

		if (
			$is_linear_progression_enabled
			&& ! $this->get_model()->is_content_visible()
			&& isset( $context['progression'] )
			&& $context['progression'] instanceof Progression\Step
		) {
			$previous_incomplete_step = $context['progression']->get_previous_incomplete_step();

			if ( $previous_incomplete_step instanceof Models\Step ) {
				$alerts[] = [
					'action_type' => 'link',
					'id'          => 'lesson-progression',
					'message'     => sprintf(
						// translators: placeholder: step label.
						__( 'Please go back and complete the previous %s.', 'learndash' ),
						$previous_incomplete_step->get_post_type_label( true )
					),
					'type'        => 'warning',
				];
			}
		}

		$assignment_alert_message = get_user_meta( get_current_user_id(), 'ld_assignment_message', true );

		// Handle assignment submission alerts created by learndash_check_upload().
		if (
			! empty( $assignment_alert_message )
			&& is_array( $assignment_alert_message )
			&& ! empty( $assignment_alert_message[0] )
		) {
			$alert_arguments = wp_parse_args(
				$assignment_alert_message[0],
				[
					'type'    => 'warning',
					'message' => '',
				]
			);

			if ( $alert_arguments['type'] === 'success' ) {
				$alert_arguments['type'] = 'info';
			}

			$alerts[] = [
				'id'      => 'lesson-assignment-submission',
				'message' => Cast::to_string( $alert_arguments['message'] ),
				'type'    => Cast::to_string( $alert_arguments['type'] ),
			];

			// Clear the alert message after it's been displayed.
			delete_user_meta( get_current_user_id(), 'ld_assignment_message' );
		}

		if (
			isset( $context['assignments'] )
			&& is_array( $context['assignments'] )
			&& $this->get_model()->get_approved_assignments_number() < count( $context['assignments'] )
		) {
			$alerts[] = [
				'id'      => 'lesson-assignments-awaiting-approval',
				'message' => sprintf(
					/* translators: %1$s: Assignment label singular, %2$s: Assignment label plural */
					_n( // phpcs:ignore WordPress.WP.I18n.MismatchedPlaceholders -- It's intentional to allow proper translation.
						'You have an %1$s awaiting approval.',
						'You have %2$s awaiting approval.',
						count( $context['assignments'] ),
						'learndash'
					),
					learndash_get_custom_label_lower( 'assignment' ),
					learndash_get_custom_label_lower( 'assignments' ),
				),
				'type'    => 'warning',
			];
		}

		/**
		 * Filters the alerts.
		 *
		 * @since 4.24.0
		 *
		 * @param array<string, string>[]|Alerts\Alert[] $alerts    The alerts.
		 * @param string                                 $view_slug The view slug.
		 * @param View                                   $view      The view object.
		 *
		 * @return array<string, string>[]|Alerts\Alert[]
		 */
		$alerts = (array) apply_filters( 'learndash_template_views_alerts', $alerts, $this->view_slug, $this );

		/**
		 * Filters the lesson alerts.
		 *
		 * @since 4.24.0
		 *
		 * @param array<string, string>[]|Alerts\Alert[] $alerts    The alerts.
		 * @param string                                 $view_slug The view slug.
		 * @param Lesson                                 $view      The view object.
		 * @param Models\Lesson                          $model     The lesson model.
		 *
		 * @return array<string, string>[]|Alerts\Alert[]
		 */
		$alerts = (array) apply_filters( 'learndash_template_views_lesson_alerts', $alerts, $this->view_slug, $this, $this->model );

		// Rebuild the Alerts objects after filtering.
		return new Alerts\Alerts( $alerts );
	}

	/**
	 * Maps the progress bar.
	 *
	 * @since 4.24.0
	 *
	 * @return Progression\Bar
	 */
	public function get_progress_bar(): Progression\Bar {
		$page_size = 100;

		// Map the number of topics. Paginate for memory reasons.

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

		// Map the number of quizzes. Paginate for memory reasons.

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
			->set_total_step_count( $topics_number + $quizzes_number )
			->set_completed_step_count( $completed_topics_number + $completed_quizzes_number )
			->set_label( $this->model->get_post_type_label() )
			->set_last_activity( $this->model->get_last_activity() )
			->set_complete( $this->model->is_complete() ? true : null );

		/**
		 * Filters the lesson progress bar.
		 *
		 * @since 4.24.0
		 *
		 * @param Progression\Bar $progress_bar The progress bar.
		 * @param string          $view_slug    The view slug.
		 * @param Lesson          $view         The view object.
		 * @param Models\Lesson   $model        The lesson model.
		 *
		 * @return Progression\Bar
		 */
		return apply_filters( 'learndash_template_views_lesson_progress_bar', $progress_bar, $this->view_slug, $this, $this->model );
	}
}
