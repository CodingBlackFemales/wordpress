<?php
/**
 * The topic view class.
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
 * The view class for LD topic post type.
 *
 * @since 4.6.0
 */
class Topic extends View {
	/**
	 * The related model.
	 *
	 * @var Models\Topic
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
		$this->model   = Models\Topic::create_from_post( $post );

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
	 * @return Models\Topic
	 */
	public function get_model(): Models\Topic {
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
				'progress_bar' => $this->get_progress_bar(),
				'progression'  => new Progression\Step( $this->model, $user_id ),
				'tabs'         => $this->get_tabs(),
				'topic'        => $this->model,
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
				'large',
				[ 'class' => 'ld-featured-image ld-featured-image--topic' ]
			) . $content;
		}

		$tabs_array = [
			[
				'id'      => 'content',
				'icon'    => 'lesson',
				'label'   => LearnDash_Custom_Label::get_label( 'topic' ),
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
		 * Filters the topic tabs.
		 *
		 * @since 4.24.0
		 *
		 * @param array<int, array<string, array{id: string, icon: string, label: string, content: string, order?: int}>> $tabs      The tabs.
		 * @param string                                                                                                  $view_slug The view slug.
		 * @param Topic                                                                                                   $view      The view object.
		 * @param Models\Topic                                                                                            $model     The topic model.
		 *
		 * @return array<int, array<string, array{id: string, icon: string, label: string, content: string, order?: int}>>
		 */
		$tabs_array = (array) apply_filters( 'learndash_template_views_topic_tabs', $tabs_array, $this->view_slug, $this, $this->model );

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
		$lesson = $this->model->get_lesson();

		if ( $course && $lesson ) {
			$breadcrumbs = [
				[
					'url'   => $course->get_permalink(),
					'label' => $course->get_title(),
					'id'    => 'course',
				],
				[
					'url'   => $lesson->get_permalink(),
					'label' => $lesson->get_title(),
					'id'    => 'lesson',
				],
			];
		} else {
			$breadcrumbs = [
				[
					'url'   => learndash_post_type_has_archive( $this->model->get_post_type() )
						? (string) get_post_type_archive_link( $this->model->get_post_type() )
						: '',
					'label' => LearnDash_Custom_Label::get_label( 'topics' ),
					'id'    => 'topics',
				],
			];
		}

		$breadcrumbs[] = [
			'url'   => '',
			'label' => $this->model->get_title(),
			'id'    => 'topic',
		];

		$breadcrumbs = new Breadcrumbs\Breadcrumbs( $breadcrumbs );

		$breadcrumbs = $breadcrumbs->update_is_last()->all();

		/** This filter is documented in src/Core/Template/Views/Lesson.php */
		$breadcrumbs = (array) apply_filters( 'learndash_template_views_breadcrumbs', $breadcrumbs, $this->view_slug, $this );

		/**
		 * Filters the topic breadcrumbs.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string, string>[]|Breadcrumbs\Breadcrumb[] $breadcrumbs The breadcrumbs.
		 * @param string                                           $view_slug   The view slug.
		 * @param Topic                                            $view        The view object.
		 * @param Models\Topic                                     $model       The topic model.
		 *
		 * @return array<string, string>[]|Breadcrumbs\Breadcrumb[]
		 */
		$breadcrumbs = (array) apply_filters( 'learndash_template_views_topic_breadcrumbs', $breadcrumbs, $this->view_slug, $this, $this->model );

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
				// If the previous incomplete step is the current topic, then maybe show the video required alert.
				if ( $previous_incomplete_step->get_id() === $this->get_model()->get_id() ) {
					$parent_step = $this->get_model()->get_parent_step();

					if (
						$parent_step instanceof Models\Step
						&& method_exists( $parent_step, 'requires_watching_video_before_sub_steps' )
						&& method_exists( $parent_step, 'is_video_watched' )
						&& $parent_step->requires_watching_video_before_sub_steps()
						&& ! $parent_step->is_video_watched( get_current_user_id() )
					) {
						$alerts[] = [
							'action_type' => 'link',
							'id'          => 'topic-progression-video-required',
							'link_text'   => sprintf(
								// translators: placeholder: step label.
								__( 'Back to %s', 'learndash' ),
								$parent_step->get_post_type_label()
							),
							'link_url'    => $parent_step->get_permalink(),
							'message'     => sprintf(
								// translators: placeholder: step label.
								__( 'Please go back and watch the video for the previous %s.', 'learndash' ),
								$parent_step->get_post_type_label( true )
							),
							'type'        => 'warning',
						];
					}
				} else {
					// Otherwise, show the previous step required alert.
					$alerts[] = [
						'action_type' => 'link',
						'id'          => 'topic-progression',
						'message'     => sprintf(
							// translators: placeholder: step label.
							__( 'Please go back and complete the previous %s.', 'learndash' ),
							$previous_incomplete_step->get_post_type_label( true )
						),
						'type'        => 'warning',
					];
				}
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
				'id'      => 'topic-assignment-submission',
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
				'id'      => 'topic-assignments-awaiting-approval',
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

		/** This filter is documented in src/Core/Template/Views/Lesson.php */
		$alerts = (array) apply_filters( 'learndash_template_views_alerts', $alerts, $this->view_slug, $this );

		/**
		 * Filters the topic alerts.
		 *
		 * @since 4.24.0
		 *
		 * @param array<string, string>[]|Alerts\Alert[] $alerts    The alerts.
		 * @param string                                 $view_slug The view slug.
		 * @param Topic                                  $view      The view object.
		 * @param Models\Topic                           $model     The topic model.
		 *
		 * @return array<string, string>[]|Alerts\Alert[]
		 */
		$alerts = (array) apply_filters( 'learndash_template_views_topic_alerts', $alerts, $this->view_slug, $this, $this->model );

		// Rebuild the Alerts objects after filtering.
		return new Alerts\Alerts( $alerts );
	}

	/**
	 * Maps the progress bar.
	 * Topics should use the lesson progress label if available.
	 *
	 * @since 4.24.0
	 *
	 * @return Progression\Bar
	 */
	protected function get_progress_bar(): Progression\Bar {
		$lesson = $this->model->get_lesson();

		if ( $lesson ) {
			$progress_bar = ( new Lesson( $lesson->get_post() ) )->get_progress_bar();
		} else {
			$quizzes = $this->model->get_quizzes();

			$quizzes_number           = count( $quizzes );
			$completed_quizzes_number = count(
				array_filter(
					$quizzes,
					fn( $quiz ) => $quiz->is_complete()
				)
			);

			$progress_bar = new Progression\Bar();

			$progress_bar
				->set_total_step_count( $quizzes_number )
				->set_completed_step_count( $completed_quizzes_number )
				->set_label( $this->model->get_post_type_label() )
				->set_last_activity( $this->model->get_last_activity() )
				->set_complete( $this->model->is_complete() ? true : null );
		}

		/**
		 * Filters the topic progress bar.
		 *
		 * @since 4.24.0
		 *
		 * @param Progression\Bar $progress_bar The progress bar.
		 * @param string          $view_slug    The view slug.
		 * @param Topic           $view         The view object.
		 * @param Models\Topic    $model        The topic model.
		 *
		 * @return Progression\Bar
		 */
		return apply_filters( 'learndash_template_views_topic_progress_bar', $progress_bar, $this->view_slug, $this, $this->model );
	}
}
