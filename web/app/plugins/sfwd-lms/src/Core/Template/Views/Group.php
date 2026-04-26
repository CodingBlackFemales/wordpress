<?php
/**
 * The group view class.
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
 * The view class for LD group post type.
 *
 * @since 4.6.0
 */
class Group extends View {
	/**
	 * The related model.
	 *
	 * @since 4.6.0
	 *
	 * @var Models\Group
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
		$this->model   = Models\Group::create_from_post( $post );

		parent::__construct(
			LDLMS_Post_Types::get_post_type_key( $post->post_type ),
			$this->build_context()
		);
	}

	/**
	 * Returns the model.
	 *
	 * @since 4.22.0
	 *
	 * @return Models\Group
	 */
	public function get_model(): Models\Group {
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
				'group'        => $this->model,
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
					'class' => 'ld-featured-image ld-featured-image--group',
				]
			) . $content;
		}

		$tabs_array = [
			[
				'id'      => 'content',
				'icon'    => 'group',
				'label'   => LearnDash_Custom_Label::get_label( 'group' ),
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
		 * Filters the group tabs.
		 *
		 * @since 4.22.0
		 *
		 * @param array<int, array<string, array{id: string, icon: string, label: string, content: string, order?: int}>> $tabs      The tabs.
		 * @param string                                                                                                  $view_slug The view slug.
		 * @param Group                                                                                                   $view      The view object.
		 * @param Models\Group                                                                                            $model     The group model.
		 *
		 * @return array<int, array<string, array{id: string, icon: string, label: string, content: string, order?: int}>>
		 */
		$tabs_array = (array) apply_filters( 'learndash_template_views_group_tabs', $tabs_array, $this->view_slug, $this, $this->model );

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
		// Map the number of courses. Paginate for memory reasons.

		$page_size = 100;

		$courses_number           = $this->model->get_courses_number();
		$completed_courses_number = 0;

		$courses_offset = 0;
		$courses        = $this->model->get_courses( $page_size, $courses_offset );

		while ( ! empty( $courses ) ) {
			$completed_courses_number += count(
				array_filter( $courses, fn( $course ) => $course->is_complete() )
			);

			$courses_offset += $page_size;

			$courses = $this->model->get_courses( $page_size, $courses_offset );
		}

		// Map the progress bar.

		$progress_bar = new Progression\Bar();

		$progress_bar
			->set_total_step_count( $courses_number )
			->set_completed_step_count( $completed_courses_number )
			->set_label( $this->model->get_post_type_label() )
			->set_last_activity( $this->model->get_last_activity() );

		/**
		 * Filters the group progress bar.
		 *
		 * @since 4.24.0
		 *
		 * @param Progression\Bar $progress_bar The progress bar.
		 * @param string          $view_slug    The view slug.
		 * @param Group           $view         The view object.
		 * @param Models\Group    $model        The group model.
		 *
		 * @return Progression\Bar
		 */
		return apply_filters( 'learndash_template_views_group_progress_bar', $progress_bar, $this->view_slug, $this, $this->model );
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
				'id'          => 'group-certificate',
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
		 * Filters the group alerts.
		 *
		 * @since 4.24.0
		 *
		 * @param array<string, string>[]|Alerts\Alert[] $alerts    The alerts.
		 * @param string                                 $view_slug The view slug.
		 * @param Group                                  $view      The view object.
		 * @param Models\Group                           $model     The group model.
		 *
		 * @return array<string, string>[]|Alerts\Alert[]
		 */
		$alerts = (array) apply_filters( 'learndash_template_views_group_alerts', $alerts, $this->view_slug, $this, $this->model );

		// Rebuild the Alerts objects after filtering.
		return new Alerts\Alerts( $alerts );
	}
}
