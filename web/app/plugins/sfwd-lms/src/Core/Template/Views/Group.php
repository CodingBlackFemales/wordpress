<?php
/**
 * The group view class.
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
use LearnDash\Core\Template\Tabs;
use LearnDash\Core\Template\Views\Traits\Has_Steps;
use LearnDash\Core\Traits\Memoizable;
use LearnDash_Custom_Label;
use LearnDash\Core\Template\Breadcrumbs;
use WP_Post;
use LearnDash\Core\Mappers;

/**
 * The view class for LD group post type.
 *
 * @since 4.6.0
 */
class Group extends View implements Interfaces\Has_Steps {
	use Has_Steps;
	use Memoizable;

	/**
	 * The related model.
	 *
	 * @var Models\Group
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

		$this->model = Models\Group::create_from_post( $post );
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
				$steps_mapper = new Mappers\Steps\Group( $this->model );

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
				return learndash_get_group_courses_per_page( $this->model->get_id() );
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
		$user    = wp_get_current_user();
		$product = $this->model->get_product();

		return [
			'group'              => $this->model,
			'breadcrumbs'        => $this->get_breadcrumbs(),
			'title'              => $this->model->get_title(),
			'content_is_visible' => $product->is_content_visible( $user ),
			'is_enrolled'        => $product->user_has_access( $user ),
			'product'            => $product,
			'instructors'        => $this->model->get_instructors(),
			'tabs'               => $this->get_tabs(),
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
					'icon'    => 'course',
					'label'   => LearnDash_Custom_Label::get_label( 'courses' ),
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

		/**
		 * Filters the tabs.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string, Tabs\Tab>|array<int, array<string, mixed>> $tabs      The tabs.
		 * @param string                                                   $view_slug The view slug.
		 * @param Group                                                    $view      The view object.
		 */
		$tabs_array = (array) apply_filters( 'learndash_template_views_tabs', $tabs_array, $this->view_slug, $this );

		/**
		 * Filters the group tabs.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string, Tabs\Tab>|array<int, array<string, mixed>> $tabs      The tabs.
		 * @param string                                                   $view_slug The view slug.
		 * @param Group                                                    $view      The view object.
		 *
		 * @ignore
		 */
		$tabs_array = (array) apply_filters( 'learndash_template_views_group_tabs', $tabs_array, $this->view_slug, $this );

		// Rebuild the tabs object after filtering.
		return new Tabs\Tabs( $tabs_array );
	}

	/**
	 * Gets the breadcrumbs.
	 *
	 * @since 4.6.0
	 *
	 * @return BreadCrumbs\Breadcrumbs
	 */
	protected function get_breadcrumbs(): BreadCrumbs\Breadcrumbs {
		$breadcrumbs = [
			[
				'url'   => learndash_post_type_has_archive( $this->model->get_post()->post_type )
					? (string) get_post_type_archive_link( $this->model->get_post()->post_type )
					: '',
				'label' => LearnDash_Custom_Label::get_label( 'groups' ),
				'id'    => 'groups',
			],
		];

		if ( learndash_is_groups_hierarchical_enabled() && $this->model->get_parent() ) {
			$breadcrumbs[] = [
				'url'   => get_permalink( $this->model->get_parent()->get_post() ),
				'label' => $this->model->get_parent()->get_title(),
				'id'    => 'group-parent',
			];
		}

		$breadcrumbs[] = [
			'url'   => '',
			'label' => $this->model->get_title(),
			'id'    => 'group',
		];

		$breadcrumbs = new Breadcrumbs\Breadcrumbs( $breadcrumbs );

		$breadcrumbs = $breadcrumbs->update_is_last()->all();

		/**
		 * Filters the breadcrumbs.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string, string>[]|Breadcrumbs\Breadcrumb[] $breadcrumbs The breadcrumbs.
		 * @param string                                           $view_slug   The view slug.
		 * @param View                                             $view        The view object.
		 */
		$breadcrumbs = (array) apply_filters( 'learndash_template_views_breadcrumbs', $breadcrumbs, $this->view_slug, $this );

		/**
		 * Filters the group breadcrumbs.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string, string>[]|Breadcrumbs\Breadcrumb[] $breadcrumbs The breadcrumbs.
		 * @param string                                           $view_slug   The view slug.
		 * @param Group                                            $view        The view object.
		 *
		 * @ignore
		 */
		$breadcrumbs = (array) apply_filters( 'learndash_template_views_group_breadcrumbs', $breadcrumbs, $this->view_slug, $this );

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
		$steps_mapper = new Mappers\Steps\Group( $this->model );

		$steps = $steps_mapper->paginated( $this->get_current_steps_page(), $this->get_steps_page_size() );

		return $this->get_steps_content( $steps );
	}
}
