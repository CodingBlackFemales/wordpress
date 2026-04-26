<?php
/**
 * LearnDash LD30 Modern Group Template Tweaks.
 *
 * @since 4.22.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern\Group;

use LDLMS_Post_Types;
use LearnDash\Core\Models;
use LearnDash\Core\Models\Product;
use LearnDash\Core\Template\View as View_Base;
use LearnDash\Core\Template\Views;
use LearnDash\Core\Themes\LD30\Modern\Course\Product_Access_Options_Mapper;
use Learndash_Payment_Button;
use WP_User;
use LearnDash_Settings_Section;
use LearnDash\Course_Grid\Shortcodes\LearnDash_Course_Grid as Course_Grid;
use LearnDash\Core\Template\Template as TemplateEngine;
use LearnDash\Core\Utilities\Cast;
use WP_Post;
use LearnDash\Core\Template\Progression;
use LearnDash\Core\Template\Alerts;

/**
 * LearnDash LD30 Modern Group Template Tweaks.
 *
 * @since 4.22.0
 *
 * @phpstan-import-type Payment_Params from Learndash_Payment_Button
 */
class Template {
	/**
	 * Group model object.
	 *
	 * @since 4.22.0
	 *
	 * @var Models\Group|null
	 */
	private ?Models\Group $group = null;

	/**
	 * Course Grid ID prefix.
	 *
	 * @since 4.22.0
	 *
	 * @var string
	 */
	private const COURSE_GRID_ID_PREFIX = 'ld-group__course-grid--group-';

	/**
	 * Adds additional context to the Group view.
	 *
	 * @since 4.22.0
	 *
	 * @param array<string,mixed> $context   The current context.
	 * @param string              $view_slug The view slug.
	 * @param bool                $is_admin  Whether the view is for an admin page.
	 * @param WP_User             $user      The user object.
	 * @param View_Base           $view      The view object.
	 *
	 * @return array<string, mixed>
	 */
	public function add_additional_context( $context, string $view_slug, bool $is_admin, WP_User $user, View_Base $view ): array {
		if ( ! $view instanceof Views\Group ) {
			return $context;
		}

		$this->group = $view->get_model();

		$product = $this->group->get_product();

		$custom_login_enabled = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_mode_enabled' ) === 'yes'
			/** This filter is documented in themes/ld30/templates/modules/infobar/course.php */
			&& apply_filters( 'learndash_login_modal', true, $this->group->get_id(), $user->ID );

		$context = array_merge(
			$context,
			[
				'access_options'       => ( new Product_Access_Options_Mapper() )->map( $product ),
				'courses_content'      => $this->get_courses_content(),
				'courses_number'       => $this->group->get_courses_number(),
				'custom_login_enabled' => $custom_login_enabled,
				'is_content_visible'   => $product->is_content_visible(),
				'show_sidebar'         => $this->should_show_sidebar( $view, $user ),
			]
		);

		$context['show_header'] = $this->should_show_header( $view, $user, $context );

		return $context;
	}

	/**
	 * Changes the payment button label on the group page.
	 *
	 * @since 4.22.0
	 *
	 * @param string              $label   The label of the payment button.
	 * @param Models\Product|null $product The product model.
	 * @param WP_User             $user    The user object.
	 *
	 * @return string
	 */
	public function change_payment_button_label( $label, ?Models\Product $product, WP_User $user ): string {
		// Don't change the label if the product is not a group or the user has access to the group.
		if (
			! $product
			|| ! is_singular( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::GROUP ) )
			|| $product->user_has_access( $user )
		) {
			return $label;
		}

		// Pre-order.

		if ( ! $product->has_started() ) {
			return sprintf(
				// translators: placeholder: Group label.
				esc_html_x( 'Pre-order this %s', 'placeholder: Group label', 'learndash' ),
				$product->get_type_label( true )
			);
		}

		return sprintf(
			// translators: placeholder: Group label.
			esc_html_x( 'Enroll in this %s', 'placeholder: Group label', 'learndash' ),
			$product->get_type_label( true )
		);
	}

	/**
	 * Adds the 'ld-enrollment__join-button' class to the payment button on Modern Group Pages.
	 *
	 * @since 4.22.0
	 *
	 * @param string $classes CSS classes for the payment button.
	 *
	 * @return string
	 */
	public function change_payment_button_classes( $classes ): string {
		if (
			! is_singular( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::GROUP ) )
		) {
			return $classes;
		}

		return "ld-enrollment__join-button {$classes}";
	}

	/**
	 * Updates the free payment button when a user is not logged in.
	 *
	 * @since 4.22.0
	 *
	 * @param string         $button_html The free payment button HTML.
	 * @param Payment_Params $params      Payment parameters.
	 *
	 * @return string
	 */
	public function change_free_payment_button( $button_html, array $params ): string { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint -- It's correct.
		if (
			! is_singular( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::GROUP ) )
			|| is_user_logged_in()
		) {
			return $button_html;
		}

		// Define the registration URL.

		// If LD registration page is set, use that URL.
		if ( learndash_registration_page_is_set() ) {
			$registration_url = learndash_registration_page_build_url( $params['product_id'] );
		} else {
			// Otherwise, use the default WP registration or login URL, depending on the registration setting.
			$registration_url = get_option( 'users_can_register' )
				? wp_registration_url()
				: wp_login_url( (string) get_permalink( $params['product_id'] ) );
		}

		return TemplateEngine::get_template(
			'modern/group/enrollment/join/registration-button',
			[
				'registration_url' => $registration_url,
				'button_label'     => $params['button_label'],
			]
		);
	}

	/**
	 * Loads the course grid assets by adding our course grid to the list of course grids.
	 *
	 * @since 4.22.0
	 *
	 * @param array<int, array{cards: array<int, mixed>, course_grids: array<int, array<int|string, mixed>>, skins: array<int, mixed>}> $course_grids Extra course grids to load assets for.
	 * @param WP_Post|null                                                                                                              $post         The post object.
	 *
	 * @return array<int, array{cards: array<int, mixed>, course_grids: array<int, array<int|string, mixed>>, skins: array<int, mixed>}>
	 */
	public function load_course_grid_assets( $course_grids, ?WP_Post $post ) {
		if ( ! $post ) {
			return $course_grids;
		}

		$group = Models\Group::find( $post->ID );

		if ( ! $group ) {
			return $course_grids;
		}

		$args = $this->get_course_grid_args( $group );

		/**
		 * The course_grids key is normally a created using a RegEx match for the Course Grid shortcode which is
		 * used to generate inline CSS for the Course Grid.
		 *
		 * However, all that it cares about is that it can extract the Arguments for the Course Grid, so we can pass
		 * in an array of arguments and it will work.
		 */
		$course_grids[] = [
			'cards'        => [ $args['card'] ],
			'course_grids' => [ $args ],
			'skins'        => [ $args['skin'] ],
		];

		return $course_grids;
	}

	/**
	 * Removes the progress bar on the group page for unenrolled courses.
	 *
	 * @since 4.22.0
	 *
	 * @param array<string,mixed> $shortcode_atts The shortcode attributes.
	 * @param WP_Post             $post           The post object.
	 * @param array<string,mixed> $post_atts      The post attributes.
	 *
	 * @return array<string,mixed> The shortcode attributes.
	 */
	public function remove_progress_bar_for_unenrolled_courses( $shortcode_atts, WP_Post $post, array $post_atts ) {
		if ( ! $this->is_group_course_grid( $shortcode_atts, $post ) ) {
			return $shortcode_atts;
		}

		$course_product = Product::find( $post->ID );

		if ( ! $course_product ) {
			return $shortcode_atts;
		}

		if (
			! $shortcode_atts['progress_bar']
			|| $course_product->user_has_access()
		) {
			return $shortcode_atts;
		}

		$shortcode_atts['progress_bar'] = false;

		return $shortcode_atts;
	}

	/**
	 * Adds a caret icon to the continue button on the group page.
	 *
	 * @since 4.22.0
	 *
	 * @param array<string,mixed> $post_atts      The post attributes.
	 * @param WP_Post             $post           The post object.
	 * @param array<string,mixed> $shortcode_atts The shortcode attributes.
	 *
	 * @return array<string,mixed> The shortcode attributes.
	 */
	public function add_icon_to_continue_button( $post_atts, WP_Post $post, array $shortcode_atts ) {
		if ( ! $this->is_group_course_grid( $shortcode_atts, $post ) ) {
			return $post_atts;
		}

		$course_product = Product::find( $post->ID );

		if ( ! $course_product ) {
			return $post_atts;
		}

		if (
			! $shortcode_atts['button']
			|| empty( $post_atts['button_text'] )
		) {
			return $post_atts;
		}

		$post_atts['button_text'] = sprintf(
			'%s %s',
			Cast::to_string( $post_atts['button_text'] ),
			TemplateEngine::get_template(
				'components/icons/caret-right',
				[
					'is_aria_hidden' => true,
				]
			)
		);

		return $post_atts;
	}

	/**
	 * Returns whether the provided Shortcode Atts and Post are for a Modern Group Page Course Grid.
	 *
	 * @since 4.22.0
	 *
	 * @param array<string,mixed> $shortcode_atts The shortcode attributes.
	 * @param WP_Post             $post           The post object.
	 *
	 * @return bool
	 */
	private function is_group_course_grid( array $shortcode_atts, WP_Post $post ): bool {
		$renderer = new Course_Grid();

		// Ensure we have the expected shortcode attribute keys.
		$shortcode_atts = wp_parse_args(
			$shortcode_atts,
			$renderer->get_default_atts()
		);

		$group = Models\Group::find( $shortcode_atts['group_id'] );

		if (
			! $group
			|| strpos( $shortcode_atts['id'], self::COURSE_GRID_ID_PREFIX ) !== 0
			|| $post->post_type !== LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Maps the courses content.
	 *
	 * @since 4.22.0
	 *
	 * @return string
	 */
	private function get_courses_content(): string {
		if ( ! class_exists( Course_Grid::class ) ) {
			return '';
		}

		if ( ! $this->group ) {
			return '';
		}

		if ( $this->group->get_courses_number() <= 0 ) {
			return '';
		}

		$course_grid_renderer = new Course_Grid();

		return $course_grid_renderer->render(
			$this->get_course_grid_args( $this->group )
		);
	}

	/**
	 * Gets the course grid renderer arguments.
	 *
	 * @since 4.22.0
	 *
	 * @param Models\Group $group The group model.
	 *
	 * @return array<string,mixed>
	 */
	private function get_course_grid_args( Models\Group $group ): array {
		// TODO: Pass through a post_status that will work with Course visibility per-User Role, similar to how Course Steps are handled.

		/**
		 * Filters the course grid renderer arguments.
		 *
		 * @since 4.22.0
		 *
		 * @param array<string,mixed> $args  The course grid renderer arguments.
		 * @param Models\Group        $group The group model.
		 *
		 * @return array<string,mixed>
		 */
		return apply_filters(
			'learndash_ld30_modern_group_course_grid_args',
			array_merge(
				[
					'button'            => $group->get_product()->user_has_access(),
					'card'              => 'grid-3',
					'class_name'        => 'learndash-course-grid--modern',
					'columns'           => 3, // Default, but worth specifying.
					'filter'            => false,
					'grid_height_equal' => false, // Default, but we need to ensure it is disabled to replicate this in a different way.
					'group_id'          => $group->get_id(),
					'id'                => self::COURSE_GRID_ID_PREFIX . $group->get_id(),
					'items_per_row'     => 3, // Default, but worth specifying.
					'pagination'        => 'button', // Default, but worth specifying.
					'per_page'          => 24,
					'post_meta'         => false,
					'post_type'         => LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ), // Default, but worth specifying.
					'progress_bar'      => true,
					'skin'              => 'grid', // Default, but worth specifying.
					'thumbnail_size'    => 'course-thumbnail',
					'thumbnail'         => true, // Default, but worth specifying.
					'title'             => true, // Default, but worth specifying.
				],
				learndash_get_group_courses_order( $group->get_id() )
			),
			$group
		);
	}

	/**
	 * Determines whether the header should be shown on the group page.
	 *
	 * @since 4.22.0
	 * @since 4.24.0 Added the $context parameter.
	 *
	 * @param Views\Group          $view    The group view.
	 * @param WP_User              $user    The user object.
	 * @param array<string, mixed> $context The view context array. Default is empty array.
	 *
	 * @return bool
	 */
	private function should_show_header( Views\Group $view, WP_User $user, array $context = [] ): bool {
		$group       = $view->get_model();
		$show_header = false;

		if (
			(
				isset( $context['progress_bar'] )
				&& $context['progress_bar'] instanceof Progression\Bar
				&& $context['progress_bar']->should_show()
				&& $user->exists()
				&& $group->get_product()->user_has_access( $user )
			)
			|| (
				isset( $context['alerts'] )
				&& $context['alerts'] instanceof Alerts\Alerts
				&& ! $context['alerts']->is_empty()
			)
		) {
			$show_header = true;
		}

		/**
		 * Filters whether the group header should be shown on the group page.
		 *
		 * @since 4.22.0
		 * @since 4.24.0 Added the $context parameter.
		 *
		 * @param bool                 $show_header Whether the group header should be shown. Default is false when neither the Progress Bar or Alerts are visible.
		 * @param Views\Group          $view        The group view.
		 * @param WP_User              $user        The user object.
		 * @param Models\Group         $group       The group model object. Default is null.
		 * @param array<string, mixed> $context     The view context array. Default is empty array.
		 *
		 * @return bool
		 */
		return apply_filters(
			'learndash_ld30_modern_group_show_header',
			$show_header,
			$view,
			$user,
			$group,
			$context
		);
	}

	/**
	 * Determines whether the sidebar should be shown on the group page.
	 *
	 * @since 4.22.0
	 *
	 * @param Views\Group $view The group view.
	 * @param WP_User     $user The user object.
	 *
	 * @return bool
	 */
	private function should_show_sidebar( Views\Group $view, WP_User $user ): bool {
		$group   = $view->get_model();
		$product = $group->get_product();

		/**
		 * Filters whether the group sidebar should be shown on the group page.
		 *
		 * @since 4.22.0
		 *
		 * @param bool           $show_sidebar Whether the group sidebar should be shown. If the User doesn't have access to the Group, this defaults to true.
		 * @param Views\Group    $view         The group view.
		 * @param WP_User        $user         The user object.
		 * @param Models\Group   $group        The group model object.
		 * @param Models\Product $product      The product model object.
		 *
		 * @return bool
		 */
		return apply_filters(
			'learndash_ld30_modern_group_show_sidebar',
			! $product->user_has_access( $user ),
			$view,
			$user,
			$group,
			$product
		);
	}
}
